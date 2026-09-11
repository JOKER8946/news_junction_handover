<?php

declare(strict_types=1);

auth_require_role('merchant');

$title = 'Inventory';
$merchantId = auth_user_id();

$tablesReady = inventory_tables_ready($db);
$metaReady = product_meta_ready($db);

// Get tab parameter
$tab = (string)($_GET['tab'] ?? 'stock');

// Load GRN library for GRN management tab
if ($tab === 'grn') {
    require_once __DIR__ . '/../../lib/grn.php';
    ensure_grn_tables($db);
}

if (request_method() === 'POST') {
    if (!$tablesReady) {
        flash_set('error', 'Inventory tables are missing. Please run setup.php again.');
        redirect_to('merchant/inventory');
    }

    $productId = (int)($_POST['product_id'] ?? 0);
    $qtyKg = (float)($_POST['qty_kg'] ?? 0);
    $notes = post_string('notes');

    if ($productId <= 0 || $qtyKg <= 0) {
        flash_set('error', 'Enter valid inward quantity');
        redirect_to('merchant/inventory');
    }

    // Ownership check: merchant can only inward stock for their own products.
    $owns = db_fetch_one($db, 'SELECT id FROM products WHERE id = :pid AND merchant_id = :mid LIMIT 1', [
        'pid' => $productId, 'mid' => (int)$merchantId,
    ]);
    if (!$owns) {
        flash_set('error', 'Product not found or not yours.');
        redirect_to('merchant/inventory');
    }

    try {
        inventory_inward($db, $productId, $qtyKg, $notes);
        flash_set('success', 'Stock updated');
    } catch (Throwable $t) {
        flash_set('error', 'Stock update failed');
    }

    redirect_to('merchant/inventory');
}

$rows = [];
if ($tablesReady && $metaReady) {
    $rows = db_fetch_all(
        $db,
        'SELECT p.id, p.name, p.part_code, p.product_type, p.image_path, p.is_active, p.stock_kg, p.unit, i.qty_kg AS inv_qty_kg, i.updated_at
         FROM products p
         LEFT JOIN inventory i ON i.product_id = p.id
         WHERE p.merchant_id = :mid
         ORDER BY p.created_at DESC',
        ['mid' => (int)$merchantId]
    );
} else {
    $rows = db_fetch_all(
        $db,
        'SELECT p.id, p.name, NULL AS part_code, NULL AS product_type, p.image_path, p.is_active, p.stock_kg, p.unit, NULL AS inv_qty_kg, NULL AS updated_at
         FROM products p
         WHERE p.merchant_id = :mid
         ORDER BY p.created_at DESC',
        ['mid' => (int)$merchantId]
    );
}

// Get GRNs for GRN tab
$grns = [];
if ($tab === 'grn') {
    $grns = db_fetch_all($db, '
      SELECT g.id, g.grn_no, g.supplier_name, g.status, g.created_at,
           COUNT(gi.id) AS item_count, SUM(gi.expected_qty_kg) AS total_qty
      FROM grn g
      LEFT JOIN grn_items gi ON gi.grn_id = g.id
      WHERE g.merchant_user_id = :merchant_id
      GROUP BY g.id, g.grn_no, g.supplier_name, g.status, g.created_at
      ORDER BY g.created_at DESC
    ', ['merchant_id' => $merchantId]) ?: [];
}

$content = function () use ($rows, $tablesReady, $metaReady, $tab, $grns) {
    $success = flash_get('success');
    $error = flash_get('error');
    ?>
    <div class="container py-4">
      <div class="d-flex align-items-center justify-content-between mb-3">
        <h1 class="h5 mb-0 d-flex align-items-center gap-2"><i data-lucide="warehouse" class="mm-icon"></i> Inventory Management</h1>
        <a class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1" href="?p=merchant/dashboard"><i data-lucide="arrow-left" class="mm-icon"></i> Back</a>
      </div>

      <?php if ($success): ?>
        <div class="alert alert-success"><?= e($success) ?></div>
      <?php endif; ?>
      <?php if ($error): ?>
        <div class="alert alert-danger"><?= e($error) ?></div>
      <?php endif; ?>

      <!-- Tabs Navigation -->
      <div class="mb-3 border-bottom">
        <div class="btn-group" role="tablist">
          <a href="?p=merchant/inventory&tab=stock" class="btn btn-sm <?= $tab === 'stock' ? 'btn-mm' : 'btn-outline-secondary' ?>">
            <i data-lucide="package" class="mm-icon"></i> Stock Management
          </a>
          <a href="?p=merchant/inventory&tab=grn" class="btn btn-sm <?= $tab === 'grn' ? 'btn-mm' : 'btn-outline-secondary' ?>">
            <i data-lucide="file-text" class="mm-icon"></i> GRN Management
          </a>
        </div>
      </div>

      <!-- Stock Management Tab -->
      <?php if ($tab === 'stock'): ?>

      <?php if (!$tablesReady): ?>
        <div class="alert alert-warning">
          Inventory tables are not created yet. Please run <strong>/setup.php</strong> once, then refresh this page.
        </div>
      <?php endif; ?>

      <?php if (!$metaReady): ?>
        <div class="alert alert-warning">
          Product fields (Part Code / Type) are not created yet. Please run <strong>/setup.php</strong> once, then refresh.
        </div>
      <?php endif; ?>

      <div class="bg-white border rounded-4 p-3 mm-card">
        <?php if (!$rows): ?>
          <div class="text-muted">No products yet.</div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table align-middle mb-0">
              <thead>
                <tr>
                  <th style="width:72px;">Photo</th>
                  <th>Product</th>
                  <th>Type</th>
                  <th>Part Code</th>
                  <th>Unit</th>
                  <th class="text-end">Inventory</th>
                  <th class="text-end">Product Stock</th>
                  <th style="min-width: 320px;">Inward</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($rows as $r): ?>
                  <?php
                  $inv = $r['inv_qty_kg'] === null ? (float)$r['stock_kg'] : (float)$r['inv_qty_kg'];
                  $rUnit = product_unit_label($r['unit'] ?? 'kg');
                  ?>
                  <tr>
                    <td>
                      <?php if (!empty($r['image_path'])): ?>
                        <img src="<?= e((string)$r['image_path']) ?>" alt="<?= e((string)$r['name']) ?>" class="rounded-3" style="width:56px;height:56px;object-fit:cover;">
                      <?php else: ?>
                        <div class="bg-light rounded-3 d-flex align-items-center justify-content-center" style="width:56px;height:56px;"><i data-lucide="image" class="mm-icon"></i></div>
                      <?php endif; ?>
                    </td>
                    <td>
                      <div class="fw-semibold"><?= e((string)$r['name']) ?></div>
                      <div class="text-muted small"><?= ((int)$r['is_active'] === 1) ? 'Active' : 'Disabled' ?></div>
                    </td>
                    <td><?= e((string)($r['product_type'] ?? '')) ?></td>
                    <td><?= e((string)($r['part_code'] ?? '')) ?></td>
                    <td><?= e($rUnit) ?></td>
                    <td class="text-end fw-semibold"><?= e((string)$inv) ?></td>
                    <td class="text-end"><?= e((string)$r['stock_kg']) ?></td>
                    <td>
                      <form method="post" class="d-flex gap-2">
                        <input type="hidden" name="product_id" value="<?= (int)$r['id'] ?>">
                        <input class="form-control form-control-sm" type="number" step="0.01" min="0" name="qty_kg" placeholder="<?= e($rUnit) ?>" style="max-width: 110px;" required <?= $tablesReady ? '' : 'disabled' ?>>
                        <input class="form-control form-control-sm" type="text" name="notes" placeholder="Notes (optional)" <?= $tablesReady ? '' : 'disabled' ?>>
                        <button class="btn btn-mm btn-sm d-inline-flex align-items-center gap-1" type="submit" <?= $tablesReady ? '' : 'disabled' ?>><i data-lucide="arrow-down-circle" class="mm-icon"></i> Inward</button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>

      <!-- GRN Management Tab -->
      <?php elseif ($tab === 'grn'): ?>

      <div class="bg-white border rounded-4 p-3 mm-card">
        <div class="d-flex align-items-center justify-content-between mb-3">
          <div class="fw-semibold">GRN List</div>
          <a class="btn btn-sm btn-mm" href="?p=merchant/gate-entry">+ New Gate Entry</a>
        </div>

        <?php if (!$grns): ?>
          <div class="text-muted py-4 text-center">No GRNs yet. Start by recording a gate entry.</div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table align-middle mb-0 small">
              <thead>
                <tr>
                  <th>GRN #</th>
                  <th>Supplier</th>
                  <th>Items</th>
                  <th>Total Qty (kg)</th>
                  <th>Status</th>
                  <th>Date</th>
                  <th class="text-end">Action</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($grns as $grn): ?>
                  <tr>
                    <td><strong><?= e((string)$grn['grn_no']) ?></strong></td>
                    <td><?= e((string)$grn['supplier_name']) ?></td>
                    <td><?= (int)($grn['item_count'] ?? 0) ?></td>
                    <td><?= number_format((float)($grn['total_qty'] ?? 0), 2) ?> kg</td>
                    <td>
                      <span class="badge text-bg-<?= $grn['status'] === 'approved' ? 'success' : ($grn['status'] === 'pending_qc' ? 'warning' : 'secondary') ?>">
                        <?= ucfirst(str_replace('_', ' ', (string)$grn['status'])) ?>
                      </span>
                    </td>
                    <td><?= date('M d, Y', strtotime((string)$grn['created_at'])) ?></td>
                    <td class="text-end">
                      <a class="btn btn-sm btn-primary" href="?p=merchant/grn-detail&id=<?= (int)$grn['id'] ?>">View</a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>

      <?php endif; ?>
    </div>
    <script>if (window.lucide) window.lucide.createIcons();</script>
    <?php
};

require __DIR__ . '/../../views/layout.php';
