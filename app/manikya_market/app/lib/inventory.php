<?php

declare(strict_types=1);

/**
 * Transaction helpers for the stock-mutating functions below.
 *
 * These are called both standalone (merchant screens) and from inside a
 * caller's transaction — checkout.php wraps order creation in one and calls
 * inventory_sale_for_order() within it. PDO has no nested transactions, so an
 * unconditional beginTransaction() throws "There is already an active
 * transaction"; checkout swallowed that in a bare catch, which is why COD
 * orders never deducted stock. Own the transaction only when there isn't one,
 * otherwise join the caller's and let them commit or roll back.
 */
function inventory_txn_begin(PDO $db): bool
{
    if ($db->inTransaction()) {
        return false;
    }
    $db->beginTransaction();

    return true;
}

function inventory_txn_commit(PDO $db, bool $owns): void
{
    if ($owns && $db->inTransaction()) {
        $db->commit();
    }
}

function inventory_txn_rollback(PDO $db, bool $owns): void
{
    if ($owns && $db->inTransaction()) {
        $db->rollBack();
    }
}

function inventory_tables_ready(PDO $db): bool
{
    try {
        $row = db_fetch_one($db, 'SELECT DATABASE() AS dbname');
        $dbName = (string)($row['dbname'] ?? '');
        if ($dbName === '') {
            return false;
        }

        $t1 = db_fetch_one($db, 'SELECT COUNT(*) AS c FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :t', [
            'db' => $dbName,
            't' => 'inventory',
        ]);
        $t2 = db_fetch_one($db, 'SELECT COUNT(*) AS c FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :t', [
            'db' => $dbName,
            't' => 'inventory_movements',
        ]);

        return ((int)($t1['c'] ?? 0) > 0) && ((int)($t2['c'] ?? 0) > 0);
    } catch (Throwable $t) {
        return false;
    }
}

function product_meta_ready(PDO $db): bool
{
    try {
        $row = db_fetch_one($db, 'SELECT DATABASE() AS dbname');
        $dbName = (string)($row['dbname'] ?? '');
        if ($dbName === '') {
            return false;
        }

        $c1 = db_fetch_one($db, 'SELECT COUNT(*) AS c FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :t AND COLUMN_NAME = :c', [
            'db' => $dbName,
            't' => 'products',
            'c' => 'product_type',
        ]);
        $c2 = db_fetch_one($db, 'SELECT COUNT(*) AS c FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :t AND COLUMN_NAME = :c', [
            'db' => $dbName,
            't' => 'products',
            'c' => 'part_code',
        ]);

        return ((int)($c1['c'] ?? 0) > 0) && ((int)($c2['c'] ?? 0) > 0);
    } catch (Throwable $t) {
        return false;
    }
}

function inventory_ensure_product(PDO $db, int $productId): void
{
    if (!inventory_tables_ready($db)) {
        return;
    }

    $row = db_fetch_one($db, 'SELECT id FROM inventory WHERE product_id = :product_id LIMIT 1', ['product_id' => $productId]);
    if ($row) {
        return;
    }

    $product = db_fetch_one($db, 'SELECT stock_kg FROM products WHERE id = :id LIMIT 1', ['id' => $productId]);
    $stock = $product ? (float)$product['stock_kg'] : 0.0;

    db_exec($db, 'INSERT INTO inventory (product_id, qty_kg, updated_at) VALUES (:product_id, :qty_kg, NOW())', [
        'product_id' => $productId,
        'qty_kg' => $stock,
    ]);
}

function inventory_get_qty(PDO $db, int $productId): float
{
    if (!inventory_tables_ready($db)) {
        $p = db_fetch_one($db, 'SELECT stock_kg FROM products WHERE id = :id LIMIT 1', ['id' => $productId]);
        return $p ? (float)$p['stock_kg'] : 0.0;
    }

    inventory_ensure_product($db, $productId);

    $row = db_fetch_one($db, 'SELECT qty_kg FROM inventory WHERE product_id = :product_id LIMIT 1', ['product_id' => $productId]);
    if (!$row) {
        return 0.0;
    }

    return (float)$row['qty_kg'];
}

function inventory_sync_product_stock(PDO $db, int $productId): void
{
    if (!inventory_tables_ready($db)) {
        return;
    }

    $qty = inventory_get_qty($db, $productId);
    db_exec($db, 'UPDATE products SET stock_kg = :stock WHERE id = :id', ['stock' => $qty, 'id' => $productId]);
}

function inventory_inward(PDO $db, int $productId, float $qtyKg, string $notes = ''): void
{
    if ($productId <= 0 || $qtyKg <= 0) {
        return;
    }

    if (!inventory_tables_ready($db)) {
        throw new RuntimeException('Inventory tables are missing. Run setup.php again.');
    }

    $owns = inventory_txn_begin($db);
    try {
        inventory_ensure_product($db, $productId);

        $current = inventory_get_qty($db, $productId);
        $newQty = round($current + $qtyKg, 2);

        db_exec($db, 'UPDATE inventory SET qty_kg = :qty_kg, updated_at = NOW() WHERE product_id = :product_id', [
            'qty_kg' => $newQty,
            'product_id' => $productId,
        ]);

        db_exec($db, "INSERT INTO inventory_movements (product_id, movement_type, qty_kg, ref_type, ref_id, notes, created_at) VALUES (:product_id, 'inward', :qty_kg, NULL, NULL, :notes, NOW())", [
            'product_id' => $productId,
            'qty_kg' => round($qtyKg, 2),
            'notes' => $notes,
        ]);

        inventory_sync_product_stock($db, $productId);

        inventory_txn_commit($db, $owns);
    } catch (Throwable $t) {
        inventory_txn_rollback($db, $owns);
        throw $t;
    }
}

function inventory_set_qty(PDO $db, int $productId, float $newQtyKg, string $notes = ''): void
{
    if ($productId <= 0) {
        return;
    }

    if (!inventory_tables_ready($db)) {
        throw new RuntimeException('Inventory tables are missing. Run setup.php again.');
    }

    $newQtyKg = round(max(0, $newQtyKg), 2);

    $owns = inventory_txn_begin($db);
    try {
        inventory_ensure_product($db, $productId);

        $current = inventory_get_qty($db, $productId);
        if (round($current, 2) === $newQtyKg) {
            inventory_txn_commit($db, $owns);
            return;
        }

        db_exec($db, 'UPDATE inventory SET qty_kg = :qty_kg, updated_at = NOW() WHERE product_id = :product_id', [
            'qty_kg' => $newQtyKg,
            'product_id' => $productId,
        ]);

        $delta = round($newQtyKg - $current, 2);
        db_exec($db, "INSERT INTO inventory_movements (product_id, movement_type, qty_kg, ref_type, ref_id, notes, created_at) VALUES (:product_id, 'adjustment', :qty_kg, NULL, NULL, :notes, NOW())", [
            'product_id' => $productId,
            'qty_kg' => $delta,
            'notes' => $notes !== '' ? $notes : 'Stock adjustment',
        ]);

        inventory_sync_product_stock($db, $productId);

        inventory_txn_commit($db, $owns);
    } catch (Throwable $t) {
        inventory_txn_rollback($db, $owns);
        throw $t;
    }
}

function inventory_sale_for_order(PDO $db, int $orderId): void
{
    if ($orderId <= 0) {
        return;
    }

    if (!inventory_tables_ready($db)) {
        throw new RuntimeException('Inventory tables are missing. Run setup.php again.');
    }

    $existing = db_fetch_one(
        $db,
        "SELECT id FROM inventory_movements WHERE ref_type = 'order' AND ref_id = :ref_id AND movement_type = 'sale' LIMIT 1",
        ['ref_id' => $orderId]
    );
    if ($existing) {
        return;
    }

    $items = db_fetch_all($db, 'SELECT product_id, qty_kg FROM order_items WHERE order_id = :order_id', ['order_id' => $orderId]);
    if (!$items) {
        return;
    }

    $owns = inventory_txn_begin($db);
    try {
        foreach ($items as $it) {
            $productId = (int)$it['product_id'];
            $qtyKg = (float)$it['qty_kg'];

            inventory_ensure_product($db, $productId);
            $current = inventory_get_qty($db, $productId);
            $newQty = round(max(0, $current - $qtyKg), 2);

            db_exec($db, 'UPDATE inventory SET qty_kg = :qty_kg, updated_at = NOW() WHERE product_id = :product_id', [
                'qty_kg' => $newQty,
                'product_id' => $productId,
            ]);

            db_exec($db, "INSERT INTO inventory_movements (product_id, movement_type, qty_kg, ref_type, ref_id, notes, created_at) VALUES (:product_id, 'sale', :qty_kg, 'order', :ref_id, :notes, NOW())", [
                'product_id' => $productId,
                'qty_kg' => round($qtyKg, 2),
                'ref_id' => $orderId,
                'notes' => 'Order stock deduction',
            ]);

            inventory_sync_product_stock($db, $productId);
        }

        inventory_txn_commit($db, $owns);
    } catch (Throwable $t) {
        inventory_txn_rollback($db, $owns);
        throw $t;
    }
}

function inventory_cancel_order(PDO $db, int $orderId): void
{
    if ($orderId <= 0) {
        return;
    }

    if (!inventory_tables_ready($db)) {
        throw new RuntimeException('Inventory tables are missing. Run setup.php again.');
    }

    $cancelled = db_fetch_one(
        $db,
        "SELECT id FROM inventory_movements WHERE ref_type = 'order' AND ref_id = :ref_id AND movement_type = 'cancel' LIMIT 1",
        ['ref_id' => $orderId]
    );
    if ($cancelled) {
        return;
    }

    $sales = db_fetch_all(
        $db,
        "SELECT product_id, qty_kg FROM inventory_movements WHERE ref_type = 'order' AND ref_id = :ref_id AND movement_type = 'sale'",
        ['ref_id' => $orderId]
    );
    if (!$sales) {
        return;
    }

    $owns = inventory_txn_begin($db);
    try {
        foreach ($sales as $sale) {
            $productId = (int)$sale['product_id'];
            $qtyKg = (float)$sale['qty_kg'];

            inventory_ensure_product($db, $productId);
            $current = inventory_get_qty($db, $productId);
            $newQty = round($current + $qtyKg, 2);

            db_exec($db, 'UPDATE inventory SET qty_kg = :qty_kg, updated_at = NOW() WHERE product_id = :product_id', [
                'qty_kg' => $newQty,
                'product_id' => $productId,
            ]);

            db_exec($db, "INSERT INTO inventory_movements (product_id, movement_type, qty_kg, ref_type, ref_id, notes, created_at) VALUES (:product_id, 'cancel', :qty_kg, 'order', :ref_id, :notes, NOW())", [
                'product_id' => $productId,
                'qty_kg' => round($qtyKg, 2),
                'ref_id' => $orderId,
                'notes' => 'Order stock restoration on cancellation',
            ]);

            inventory_sync_product_stock($db, $productId);
        }

        inventory_txn_commit($db, $owns);
    } catch (Throwable $t) {
        inventory_txn_rollback($db, $owns);
        throw $t;
    }
}

