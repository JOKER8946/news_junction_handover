<?php

declare(strict_types=1);

auth_require_role('merchant');

$title = 'GRN Detail';
$merchantId = auth_user_id();
$grnId = (int)($_GET['id'] ?? 0);

require_once __DIR__ . '/../../lib/grn.php';
ensure_grn_tables($db);

// Get GRN
$grn = db_fetch_one($db, 'SELECT g.*, u.full_name AS created_by_name FROM grn g JOIN users u ON u.id = g.created_by WHERE g.id = :id AND g.merchant_user_id = :merchant_id LIMIT 1', [
    'id' => $grnId,
    'merchant_id' => $merchantId,
]);

if (!$grn) {
    redirect_to('merchant/grn');
}

// Get GRN items
$items = db_fetch_all($db, '
    SELECT gi.*, p.name, p.product_type, p.part_code FROM grn_items gi
    JOIN products p ON p.id = gi.product_id
    WHERE gi.grn_id = :grn_id
    ORDER BY gi.id ASC
', ['grn_id' => $grnId]) ?: [];

$error = '';
$success = '';

// Handle item quantity update
if (request_method() === 'POST' && isset($_POST['action'])) {
    $action = post_string('action');

    if ($action === 'update_item_qty') {
        $itemId = (int)post_string('item_id');
        $receivedQty = (float)post_string('received_qty');

        try {
            db_exec($db, '
                UPDATE grn_items SET received_qty_kg = :qty
                WHERE id = :id AND grn_id = :grn_id
            ', [
                'qty' => $receivedQty,
                'id' => $itemId,
                'grn_id' => $grnId,
            ]);

            // Update GRN total received
            $totalReceived = db_fetch_one($db, '
                SELECT SUM(received_qty_kg) as total FROM grn_items WHERE grn_id = :grn_id
            ', ['grn_id' => $grnId]);

            db_exec($db, '
                UPDATE grn SET received_qty_kg = :qty WHERE id = :id
            ', [
                'qty' => (float)($totalReceived['total'] ?? 0),
                'id' => $grnId,
            ]);

            $success = 'Quantity updated!';
            redirect_to('merchant/grn-detail?id=' . $grnId);
        } catch (Throwable $e) {
            $error = 'Failed to update: ' . $e->getMessage();
        }
    }

    if ($action === 'approve_grn') {
        try {
            approve_grn($db, $grnId, $merchantId);
            $success = 'GRN approved! Stock has been inwardered.';
            redirect_to('merchant/grn-detail?id=' . $grnId);
        } catch (Throwable $e) {
            $error = 'Failed to approve: ' . $e->getMessage();
        }
    }
}

$content = function() use ($grn, $items, $error, $success) {
    ?>
    <div class="container py-4">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h1 class="h5 mb-0 d-flex align-items-center gap-2"><i data-lucide="file-check" class="mm-icon"></i> <?= e((string)$grn['grn_no']) ?></h1>
            <a class="btn btn-sm btn-outline-secondary" href="?p=merchant/grn">Back to GRN</a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= e($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?= e($success) ?></div>
        <?php endif; ?>

        <div class="bg-white border rounded-4 p-4 mm-card">
            <div class="row g-4">
                <div class="col-md-3">
                    <div class="text-muted small">Supplier</div>
                    <div class="fw-semibold"><?= e((string)$grn['supplier_name']) ?></div>
                </div>
                <div class="col-md-3">
                    <div class="text-muted small">Invoice #</div>
                    <div class="fw-semibold"><?= !empty($grn['invoice_number']) ? e((string)$grn['invoice_number']) : '-' ?></div>
                </div>
                <div class="col-md-3">
                    <div class="text-muted small">DC #</div>
                    <div class="fw-semibold"><?= !empty($grn['dc_number']) ? e((string)$grn['dc_number']) : '-' ?></div>
                </div>
                <div class="col-md-3">
                    <div class="text-muted small">Status</div>
                    <div>
                        <span class="badge text-bg-<?= $grn['status'] === 'approved' ? 'success' : 'warning' ?>">
                            <?= ucfirst(str_replace('_', ' ', (string)$grn['status'])) ?>
                        </span>
                    </div>
                </div>
            </div>

            <hr class="my-4">

            <div class="fw-semibold mb-3">Items Received</div>
            <div class="table-responsive">
                <table class="table align-middle mb-0 small">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Type</th>
                            <th>Expected (kg)</th>
                            <th>Received (kg)</th>
                            <th>Status</th>
                            <?php if ($grn['status'] !== 'approved'): ?>
                                <th class="text-end">Action</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td class="fw-semibold"><?= e((string)$item['name']) ?></td>
                                <td><?= e((string)($item['product_type'] ?? '')) ?></td>
                                <td><?= number_format((float)$item['expected_qty_kg'], 2) ?></td>
                                <td><?= number_format((float)$item['received_qty_kg'], 2) ?></td>
                                <td>
                                    <span class="badge text-bg-<?= $item['qc_status'] === 'pass' ? 'success' : 'warning' ?>">
                                        <?= ucfirst((string)$item['qc_status']) ?>
                                    </span>
                                </td>
                                <?php if ($grn['status'] !== 'approved'): ?>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#updateQtyModal<?= (int)$item['id'] ?>">Update</button>
                                    </td>
                                <?php endif; ?>
                            </tr>

                            <?php if ($grn['status'] !== 'approved'): ?>
                                <!-- Modal for updating quantity -->
                                <div class="modal fade" id="updateQtyModal<?= (int)$item['id'] ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Update Received Quantity</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form method="post">
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <label class="form-label">Product: <?= e((string)$item['name']) ?></label>
                                                        <input class="form-control" type="number" step="0.01" value="<?= (float)$item['received_qty_kg'] ?>" readonly>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Expected Quantity (kg)</label>
                                                        <input class="form-control" type="number" step="0.01" value="<?= (float)$item['expected_qty_kg'] ?>" readonly>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label fw-semibold">Received Quantity (kg) *</label>
                                                        <input class="form-control" type="number" step="0.01" name="received_qty" value="<?= (float)$item['received_qty_kg'] ?>" required>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <input type="hidden" name="action" value="update_item_qty">
                                                    <input type="hidden" name="item_id" value="<?= (int)$item['id'] ?>">
                                                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-primary">Update</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <hr class="my-4">

            <div class="row g-3">
                <div class="col-md-6">
                    <div class="fw-semibold">Summary</div>
                    <div class="d-flex justify-content-between mt-2">
                        <span>Total Expected:</span>
                        <strong><?= number_format((float)$grn['expected_qty_kg'], 2) ?> kg</strong>
                    </div>
                    <div class="d-flex justify-content-between mt-1">
                        <span>Total Received:</span>
                        <strong><?= number_format((float)$grn['received_qty_kg'], 2) ?> kg</strong>
                    </div>
                </div>
                <div class="col-md-6 d-flex align-items-end">
                    <?php if ($grn['status'] !== 'approved'): ?>
                        <form method="post" class="w-100">
                            <input type="hidden" name="action" value="approve_grn">
                            <button type="submit" class="btn btn-success w-100">Approve GRN & Inward Stock</button>
                        </form>
                    <?php else: ?>
                        <div class="alert alert-success w-100 mb-0">GRN Approved - Stock has been inwardered</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <script>if (window.lucide) window.lucide.createIcons();</script>
    <?php
};

require __DIR__ . '/../../views/layout.php';
