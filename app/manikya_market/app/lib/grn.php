<?php

declare(strict_types=1);

/**
 * Ensure GRN tables exist
 */
function ensure_grn_tables($db) {
    try {
        $db->query('SELECT 1 FROM gate_entry LIMIT 1');
        $db->query('SELECT 1 FROM grn LIMIT 1');
        $db->query('SELECT 1 FROM grn_items LIMIT 1');
        $db->query('SELECT 1 FROM grn_inspection LIMIT 1');
        
        // Add missing columns to existing tables
        try {
            $db->query('SELECT invoice_date FROM gate_entry LIMIT 1');
        } catch (Throwable $e) {
            db_exec($db, 'ALTER TABLE gate_entry ADD COLUMN invoice_date DATE NULL AFTER invoice_number');
        }
        
        try {
            $db->query('SELECT dc_date FROM gate_entry LIMIT 1');
        } catch (Throwable $e) {
            db_exec($db, 'ALTER TABLE gate_entry ADD COLUMN dc_date DATE NULL AFTER dc_number');
        }
        
        try {
            $db->query('SELECT invoice_packages FROM gate_entry LIMIT 1');
        } catch (Throwable $e) {
            db_exec($db, 'ALTER TABLE gate_entry ADD COLUMN invoice_packages INT NULL AFTER invoice_date');
        }
        
        try {
            $db->query('SELECT dc_packages FROM gate_entry LIMIT 1');
        } catch (Throwable $e) {
            db_exec($db, 'ALTER TABLE gate_entry ADD COLUMN dc_packages INT NULL AFTER dc_date');
        }
        
        return true;
    } catch (Throwable $e) {
        // Create tables
        db_exec($db, '
            CREATE TABLE IF NOT EXISTS gate_entry (
                id INT AUTO_INCREMENT PRIMARY KEY,
                merchant_user_id INT NOT NULL,
                entry_no VARCHAR(50) NOT NULL UNIQUE,
                supplier_name VARCHAR(150) NOT NULL,
                vehicle_number VARCHAR(30) NULL,
                driver_name VARCHAR(100) NULL,
                driver_phone VARCHAR(30) NULL,
                invoice_number VARCHAR(50) NULL,
                invoice_date DATE NULL,
                invoice_packages INT NULL,
                dc_number VARCHAR(50) NULL,
                dc_date DATE NULL,
                dc_packages INT NULL,
                remarks TEXT NULL,
                status ENUM("received","processing","completed","rejected") DEFAULT "received",
                created_at DATETIME NOT NULL,
                CONSTRAINT fk_gate_merchant FOREIGN KEY (merchant_user_id) REFERENCES users(id) ON DELETE CASCADE,
                INDEX idx_gate_merchant (merchant_user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ');

        db_exec($db, '
            CREATE TABLE IF NOT EXISTS grn (
                id INT AUTO_INCREMENT PRIMARY KEY,
                gate_entry_id INT NOT NULL,
                merchant_user_id INT NOT NULL,
                grn_no VARCHAR(50) NOT NULL UNIQUE,
                supplier_name VARCHAR(150) NOT NULL,
                invoice_number VARCHAR(50) NULL,
                dc_number VARCHAR(50) NULL,
                expected_qty_kg DECIMAL(10,2) NOT NULL DEFAULT 0,
                received_qty_kg DECIMAL(10,2) NOT NULL DEFAULT 0,
                status ENUM("draft","pending_qc","qc_passed","qc_rejected","approved","completed") DEFAULT "draft",
                approved_by INT NULL,
                approved_at DATETIME NULL,
                created_by INT NOT NULL,
                created_at DATETIME NOT NULL,
                CONSTRAINT fk_grn_gate FOREIGN KEY (gate_entry_id) REFERENCES gate_entry(id) ON DELETE CASCADE,
                CONSTRAINT fk_grn_merchant FOREIGN KEY (merchant_user_id) REFERENCES users(id) ON DELETE CASCADE,
                CONSTRAINT fk_grn_approver FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
                INDEX idx_grn_merchant (merchant_user_id),
                INDEX idx_grn_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ');

        db_exec($db, '
            CREATE TABLE IF NOT EXISTS grn_items (
                id INT AUTO_INCREMENT PRIMARY KEY,
                grn_id INT NOT NULL,
                product_id INT NOT NULL,
                expected_qty_kg DECIMAL(10,2) NOT NULL,
                received_qty_kg DECIMAL(10,2) NOT NULL DEFAULT 0,
                qc_status ENUM("pending","pass","fail") DEFAULT "pending",
                qc_remarks TEXT NULL,
                created_at DATETIME NOT NULL,
                CONSTRAINT fk_grn_item_grn FOREIGN KEY (grn_id) REFERENCES grn(id) ON DELETE CASCADE,
                CONSTRAINT fk_grn_item_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ');

        db_exec($db, '
            CREATE TABLE IF NOT EXISTS grn_inspection (
                id INT AUTO_INCREMENT PRIMARY KEY,
                grn_id INT NOT NULL,
                inspection_type ENUM("quality","quantity","condition") NOT NULL,
                inspector_id INT NOT NULL,
                status ENUM("pass","fail") NOT NULL,
                remarks TEXT NULL,
                inspected_at DATETIME NOT NULL,
                CONSTRAINT fk_insp_grn FOREIGN KEY (grn_id) REFERENCES grn(id) ON DELETE CASCADE,
                CONSTRAINT fk_insp_user FOREIGN KEY (inspector_id) REFERENCES users(id) ON DELETE RESTRICT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ');

        return true;
    }
}

/**
 * Create gate entry
 */
function create_gate_entry($db, int $merchantId, string $supplierName, ?string $vehicleNumber, ?string $driverName, ?string $driverPhone, ?string $invoiceNumber, ?string $invoiceDate, ?int $invoicePackages, ?string $dcNumber, ?string $dcDate, ?int $dcPackages, ?string $remarks): int {
    db_exec($db, '
        INSERT INTO gate_entry (merchant_user_id, entry_no, supplier_name, vehicle_number, driver_name, driver_phone, invoice_number, invoice_date, invoice_packages, dc_number, dc_date, dc_packages, remarks, status, created_at)
        VALUES (:merchant_id, :entry_no, :supplier_name, :vehicle_number, :driver_name, :driver_phone, :invoice_number, :invoice_date, :invoice_packages, :dc_number, :dc_date, :dc_packages, :remarks, :status, NOW())
    ', [
        'merchant_id' => $merchantId,
        'entry_no' => 'GE-' . date('YmdHis') . '-' . $merchantId,
        'supplier_name' => $supplierName,
        'vehicle_number' => $vehicleNumber,
        'driver_name' => $driverName,
        'driver_phone' => $driverPhone,
        'invoice_number' => $invoiceNumber,
        'invoice_date' => $invoiceDate ?: null,
        'invoice_packages' => $invoicePackages,
        'dc_number' => $dcNumber,
        'dc_date' => $dcDate ?: null,
        'dc_packages' => $dcPackages,
        'remarks' => $remarks,
        'status' => 'received',
    ]);

    return (int)$db->lastInsertId();
}

/**
 * Create GRN from gate entry
 */
function create_grn_from_gate_entry($db, int $gateEntryId, int $merchantId, array $items): int {
    $gateEntry = db_fetch_one($db, 'SELECT * FROM gate_entry WHERE id = :id AND merchant_user_id = :merchant_id LIMIT 1', [
        'id' => $gateEntryId,
        'merchant_id' => $merchantId,
    ]);

    if (!$gateEntry) {
        throw new Exception('Gate entry not found');
    }

    // Calculate total expected quantity
    $totalQty = 0;
    foreach ($items as $item) {
        $totalQty += (float)($item['qty_kg'] ?? 0);
    }

    db_exec($db, '
        INSERT INTO grn (gate_entry_id, merchant_user_id, grn_no, supplier_name, invoice_number, dc_number, expected_qty_kg, received_qty_kg, status, created_by, created_at)
        VALUES (:gate_entry_id, :merchant_id, :grn_no, :supplier_name, :invoice_number, :dc_number, :expected_qty_kg, :received_qty_kg, :status, :created_by, NOW())
    ', [
        'gate_entry_id' => $gateEntryId,
        'merchant_id' => $merchantId,
        'grn_no' => 'GRN-' . date('YmdHis') . '-' . $gateEntryId,
        'supplier_name' => $gateEntry['supplier_name'],
        'invoice_number' => $gateEntry['invoice_number'],
        'dc_number' => $gateEntry['dc_number'],
        'expected_qty_kg' => $totalQty,
        'received_qty_kg' => 0,
        'status' => 'draft',
        'created_by' => $merchantId,
    ]);

    $grnId = (int)$db->lastInsertId();

    // Make sure unit_price + unit columns exist (legacy installs).
    try { $db->query('SELECT unit_price FROM grn_items LIMIT 1'); }
    catch (Throwable $t) { db_exec($db, 'ALTER TABLE grn_items ADD COLUMN unit_price DECIMAL(10,2) NULL DEFAULT 0.00 AFTER expected_qty_kg'); }
    try { $db->query('SELECT unit FROM grn_items LIMIT 1'); }
    catch (Throwable $t) { db_exec($db, "ALTER TABLE grn_items ADD COLUMN unit VARCHAR(20) NOT NULL DEFAULT 'kg' AFTER product_id"); }

    $allowedUnits = ['kg', 'gm', 'piece', 'dozen', 'litre', 'bunch'];

    // Add GRN items
    foreach ($items as $item) {
        $qtyKg = (float)$item['qty_kg'];
        $unitPrice = (float)($item['unit_price'] ?? 0);
        $unit = strtolower((string)($item['unit'] ?? 'kg'));
        if (!in_array($unit, $allowedUnits, true)) { $unit = 'kg'; }
        db_exec($db, '
            INSERT INTO grn_items (grn_id, product_id, unit, expected_qty_kg, unit_price, received_qty_kg, qc_status, created_at)
            VALUES (:grn_id, :product_id, :unit, :expected_qty_kg, :unit_price, :received_qty_kg, :qc_status, NOW())
        ', [
            'grn_id' => $grnId,
            'product_id' => (int)$item['product_id'],
            'unit' => $unit,
            'expected_qty_kg' => $qtyKg,
            'unit_price' => $unitPrice,
            'received_qty_kg' => $qtyKg,
            'qc_status' => 'pending',
        ]);
    }

    // Update GRN total received quantity
    db_exec($db, '
        UPDATE grn SET received_qty_kg = :received_qty_kg WHERE id = :id
    ', [
        'received_qty_kg' => $totalQty,
        'id' => $grnId,
    ]);

    return $grnId;
}

/**
 * Approve GRN and inward inventory
 */
function approve_grn($db, int $grnId, int $approverId): bool {
    $grn = db_fetch_one($db, 'SELECT * FROM grn WHERE id = :id LIMIT 1', ['id' => $grnId]);
    if (!$grn) {
        throw new Exception('GRN not found');
    }

    // Update GRN status
    db_exec($db, '
        UPDATE grn SET status = :status, approved_by = :approved_by, approved_at = NOW()
        WHERE id = :id
    ', [
        'status' => 'approved',
        'approved_by' => $approverId,
        'id' => $grnId,
    ]);

    // Get GRN items and inward inventory
    $items = db_fetch_all($db, '
        SELECT gi.product_id, gi.received_qty_kg FROM grn_items gi
        WHERE gi.grn_id = :grn_id
    ', ['grn_id' => $grnId]);

    foreach ($items as $item) {
        $productId = (int)$item['product_id'];
        $qtyKg = (float)$item['received_qty_kg'];

        // Check if inventory exists
        $inventory = db_fetch_one($db, 'SELECT id FROM inventory WHERE product_id = :product_id LIMIT 1', ['product_id' => $productId]);

        if ($inventory) {
            db_exec($db, '
                UPDATE inventory SET qty_kg = qty_kg + :qty_kg, updated_at = NOW()
                WHERE product_id = :product_id
            ', [
                'qty_kg' => $qtyKg,
                'product_id' => $productId,
            ]);
        } else {
            db_exec($db, '
                INSERT INTO inventory (product_id, qty_kg, updated_at)
                VALUES (:product_id, :qty_kg, NOW())
            ', [
                'product_id' => $productId,
                'qty_kg' => $qtyKg,
            ]);
        }

        // Log inventory movement
        db_exec($db, '
            INSERT INTO inventory_movements (product_id, movement_type, qty_kg, ref_type, ref_id, notes, created_at)
            VALUES (:product_id, :movement_type, :qty_kg, :ref_type, :ref_id, :notes, NOW())
        ', [
            'product_id' => $productId,
            'movement_type' => 'inward',
            'qty_kg' => $qtyKg,
            'ref_type' => 'grn',
            'ref_id' => $grnId,
            'notes' => 'GRN ' . $grn['grn_no'],
        ]);
    }

    return true;
}
