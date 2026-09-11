<?php

declare(strict_types=1);

auth_require_role('merchant');

$title = 'Create GRN';
$merchantId = auth_user_id();
$gateEntryId = (int)($_GET['gate_entry_id'] ?? 0);

require_once __DIR__ . '/../../lib/grn.php';
ensure_grn_tables($db);

// Get gate entry
$gateEntry = db_fetch_one($db, 'SELECT * FROM gate_entry WHERE id = :id AND merchant_user_id = :merchant_id LIMIT 1', [
    'id' => $gateEntryId,
    'merchant_id' => $merchantId,
]);

if (!$gateEntry) {
    redirect_to('merchant/grn');
}

// Get all active products — SCOPED to this seller's catalog only. Without
// the merchant_id filter, the dropdown would expose competitor SKUs and let
// the seller inward stock onto another seller's product.
$products = db_fetch_all($db,
    'SELECT id, name, product_type, part_code, price_per_kg
     FROM products
     WHERE is_active = 1 AND merchant_id = :mid
     ORDER BY name ASC',
    ['mid' => $merchantId]
) ?: [];
$ownedProductIds = array_map(fn($p) => (int)$p['id'], $products);

$error = '';
$success = '';

// Check if package numbers match
$packageMismatch = false;
if (!empty($gateEntry['invoice_packages']) && !empty($gateEntry['dc_packages'])) {
    if ((int)$gateEntry['invoice_packages'] !== (int)$gateEntry['dc_packages']) {
        $packageMismatch = true;
        $error = 'Warning: Invoice has ' . (int)$gateEntry['invoice_packages'] . ' packages but DC has ' . (int)$gateEntry['dc_packages'] . ' packages. Please verify the details.';
    }
}

if (request_method() === 'POST') {
    $itemsData = $_POST['items'] ?? [];
    $items = [];

    foreach ($itemsData as $item) {
        if (!empty($item['product_id']) && !empty($item['qty_kg'])) {
            $pid = (int)$item['product_id'];
            // Reject any product_id that isn't in this seller's catalog (defends
            // against forged form POSTs that bypass the dropdown).
            if (!in_array($pid, $ownedProductIds, true)) {
                $error = 'One or more products are not in your catalog. Refresh and try again.';
                $items = [];
                break;
            }
            $items[] = [
                'product_id' => $pid,
                'qty_kg' => (float)$item['qty_kg'],
            ];
        }
    }

    if (empty($items) && $error === '') {
        $error = 'Please add at least one item';
    } elseif (empty($items)) {
        // had an ownership error above; fall through to render
    } else {
        try {
            $grnId = create_grn_from_gate_entry($db, $gateEntryId, $merchantId, $items);
            $success = 'GRN created successfully!';
            redirect_to('merchant/grn-detail?id=' . $grnId);
        } catch (Throwable $e) {
            $error = 'Failed to create GRN: ' . $e->getMessage();
        }
    }
}

$content = function() use ($gateEntry, $products, $error, $success, $packageMismatch) {
    ?>
    <div class="container py-4">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h1 class="h5 mb-0 d-flex align-items-center gap-2"><i data-lucide="file-text" class="mm-icon"></i> Create GRN</h1>
            <a class="btn btn-sm btn-outline-secondary" href="?p=merchant/grn">Back to GRN</a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-<?= $packageMismatch ? 'warning' : 'danger' ?>"><?= e($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?= e($success) ?></div>
        <?php endif; ?>

        <div class="bg-white border rounded-4 p-4 mm-card">
            <div class="mb-4">
                <div class="fw-semibold">Gate Entry Details</div>
                <div class="row g-3 mt-2">
                    <div class="col-md-3">
                        <div class="text-muted small">Supplier</div>
                        <div><?= e((string)$gateEntry['supplier_name']) ?></div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-muted small">Vehicle Number</div>
                        <div><?= !empty($gateEntry['vehicle_number']) ? e((string)$gateEntry['vehicle_number']) : '-' ?></div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-muted small">Invoice # / Date</div>
                        <div><?= !empty($gateEntry['invoice_number']) ? e((string)$gateEntry['invoice_number']) : '-' ?><?php if (!empty($gateEntry['invoice_date'])): ?> / <?= date('d M Y', strtotime((string)$gateEntry['invoice_date'])) ?><?php endif; ?></div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-muted small">Invoice Packages</div>
                        <div><?= !empty($gateEntry['invoice_packages']) ? (int)$gateEntry['invoice_packages'] : '-' ?></div>
                    </div>
                </div>
                <div class="row g-3 mt-2">
                    <div class="col-md-3">
                        <div class="text-muted small">DC # / Date</div>
                        <div><?= !empty($gateEntry['dc_number']) ? e((string)$gateEntry['dc_number']) : '-' ?><?php if (!empty($gateEntry['dc_date'])): ?> / <?= date('d M Y', strtotime((string)$gateEntry['dc_date'])) ?><?php endif; ?></div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-muted small">DC Packages</div>
                        <div><?= !empty($gateEntry['dc_packages']) ? (int)$gateEntry['dc_packages'] : '-' ?></div>
                    </div>
                    <?php if ($packageMismatch): ?>
                    <div class="col-md-6">
                        <div class="alert alert-warning mb-0 py-2">
                            <i data-lucide="alert-circle" class="mm-icon"></i> Package count mismatch detected
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <hr>

            <form method="post">
                <div class="fw-semibold mb-3">Items Received</div>

                <div id="itemsContainer">
                    <div class="item-row mb-3 p-3 border rounded-3">
                        <div class="row g-2">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Product *</label>
                                <select class="form-select form-select-sm" name="items[0][product_id]" required>
                                    <option value="">Select Product</option>
                                    <?php foreach ($products as $p): ?>
                                        <option value="<?= (int)$p['id'] ?>"><?= e((string)$p['name']) ?> (<?= e((string)$p['product_type']) ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Qty (kg) *</label>
                                <input class="form-control form-control-sm" type="number" step="0.01" name="items[0][qty_kg]" placeholder="0.00" required>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeItem(this)">Remove</button>
                            </div>
                        </div>
                    </div>
                </div>

                <button type="button" class="btn btn-outline-secondary btn-sm mb-3" onclick="addItem()">+ Add Item</button>

                <hr>

                <div class="d-flex gap-2">
                    <button class="btn btn-mm" type="submit">Create GRN</button>
                    <a class="btn btn-outline-secondary" href="?p=merchant/grn">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        let itemCount = 1;
        
        // Products list as JSON
        const products = <?php echo json_encode($products); ?>;
        
        function getProductOptionsHtml() {
            let html = '<option value="">Select Product</option>';
            products.forEach(function(p) {
                html += '<option value="' + p.id + '">' + p.name + ' (' + p.product_type + ')</option>';
            });
            return html;
        }

        function addItem() {
            const container = document.getElementById('itemsContainer');
            const itemHtml = `
                <div class="item-row mb-3 p-3 border rounded-3">
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Product *</label>
                            <select class="form-select form-select-sm" name="items[${itemCount}][product_id]" required>
                                ${getProductOptionsHtml()}
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Qty (kg) *</label>
                            <input class="form-control form-control-sm" type="number" step="0.01" name="items[${itemCount}][qty_kg]" placeholder="0.00" required>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeItem(this)">Remove</button>
                        </div>
                    </div>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', itemHtml);
            itemCount++;
            if (window.lucide) window.lucide.createIcons();
        }

        function removeItem(btn) {
            btn.closest('.item-row').remove();
        }
    </script>
    <script>if (window.lucide) window.lucide.createIcons();</script>
    <?php
};

require __DIR__ . '/../../views/layout.php';
