<?php

declare(strict_types=1);

auth_require_role('merchant');

$title = 'Gate Entry';
$merchantId = (int)auth_user_id();

require_once __DIR__ . '/../../lib/grn.php';
ensure_grn_tables($db);

$error = '';

// Load this seller's catalog for the product dropdown (cross-seller leak guard).
$products = db_fetch_all($db,
    'SELECT id, name, part_code, product_type, price_per_kg, unit
     FROM products
     WHERE merchant_id = :mid AND is_active = 1
     ORDER BY name ASC',
    ['mid' => $merchantId]
) ?: [];
$ownedProductIds = array_map(fn($p) => (int)$p['id'], $products);

if (request_method() === 'POST') {
    $supplierName    = post_string('supplier_name');
    $vehicleNumber   = post_string('vehicle_number') ?: null;
    $driverName      = post_string('driver_name') ?: null;
    $driverPhone     = post_string('driver_phone') ?: null;
    $invoiceNumber   = post_string('invoice_number') ?: null;
    $invoiceDate     = post_string('invoice_date') ?: null;
    $invoicePackages = (int)(post_string('invoice_packages') ?: 0) ?: null;
    $dcNumber        = post_string('dc_number') ?: null;
    $dcDate          = post_string('dc_date') ?: null;
    $dcPackages      = (int)(post_string('dc_packages') ?: 0) ?: null;
    $remarks         = post_string('remarks') ?: null;

    // Collect product line items
    $itemsRaw = $_POST['items'] ?? [];
    $items = [];
    if (is_array($itemsRaw)) {
        foreach ($itemsRaw as $row) {
            $pid = (int)($row['product_id'] ?? 0);
            $qty = (float)($row['qty_kg'] ?? 0);
            $px  = (float)($row['unit_price'] ?? 0);
            if ($pid <= 0 || $qty <= 0) continue;
            // Defence-in-depth: reject any product not in seller's own catalog.
            if (!in_array($pid, $ownedProductIds, true)) {
                $error = 'One of the selected products is not in your catalog.';
                $items = [];
                break;
            }
            $items[] = ['product_id' => $pid, 'qty_kg' => $qty, 'unit_price' => $px];
        }
    }

    if ($error === '' && !$supplierName) {
        $error = 'Supplier name is required.';
    }
    if ($error === '' && empty($items)) {
        $error = 'Please add at least one product with quantity.';
    }

    if ($error === '') {
        try {
            $db->beginTransaction();
            $gateEntryId = create_gate_entry(
                $db, $merchantId, $supplierName, $vehicleNumber, $driverName, $driverPhone,
                $invoiceNumber, $invoiceDate, $invoicePackages, $dcNumber, $dcDate, $dcPackages, $remarks
            );
            $grnId = create_grn_from_gate_entry($db, $gateEntryId, $merchantId, $items);
            $db->commit();
            flash_set('success', 'Gate entry recorded and GRN #' . $grnId . ' created with ' . count($items) . ' item(s). Verify quantities and approve to inward stock.');
            redirect_to('merchant/grn-detail?id=' . $grnId);
        } catch (Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            $error = 'Failed to create gate entry: ' . $e->getMessage();
        }
    }
}

$unitOptions = ['kg' => 'Kg', 'gm' => 'Gram', 'piece' => 'Piece', 'dozen' => 'Dozen', 'litre' => 'Litre', 'bunch' => 'Bunch'];

$content = function() use ($error, $products, $unitOptions) {
    ?>
    <div class="container py-4">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h1 class="h5 mb-0 d-flex align-items-center gap-2"><i data-lucide="truck" class="mm-icon"></i> Gate Entry</h1>
            <a class="btn btn-sm btn-outline-secondary" href="?p=merchant/grn">Back to GRN</a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" id="gateEntryForm">
            <!-- Supplier / Invoice header -->
            <div class="bg-white border rounded-4 p-4 mm-card mb-3">
                <h2 class="h6 fw-bold mb-3 d-flex align-items-center gap-2"><i data-lucide="user-check" class="mm-icon"></i> Supplier &amp; Invoice</h2>
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold">Supplier Name *</label>
                        <input class="form-control" type="text" name="supplier_name" required>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold">Vehicle Number</label>
                        <input class="form-control" type="text" name="vehicle_number" placeholder="e.g., KA01AB1234">
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold">Driver Name</label>
                        <input class="form-control" type="text" name="driver_name">
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold">Driver Phone</label>
                        <input class="form-control" type="tel" name="driver_phone" placeholder="+91...">
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold">Invoice Number</label>
                        <input class="form-control" type="text" name="invoice_number" placeholder="e.g., INV-001">
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold">Invoice Date</label>
                        <input class="form-control" type="date" name="invoice_date">
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold">Number of Packages (Invoice)</label>
                        <input class="form-control" type="number" name="invoice_packages" min="0" placeholder="e.g., 10">
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold">DC Number (Delivery Challan)</label>
                        <input class="form-control" type="text" name="dc_number" placeholder="e.g., DC-001">
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold">DC Date</label>
                        <input class="form-control" type="date" name="dc_date">
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold">Number of Packages (DC)</label>
                        <input class="form-control" type="number" name="dc_packages" min="0" placeholder="e.g., 10">
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Remarks</label>
                        <textarea class="form-control" name="remarks" rows="2" placeholder="Any special notes about the delivery..."></textarea>
                    </div>
                </div>
            </div>

            <!-- Product Items -->
            <div class="bg-white border rounded-4 p-4 mm-card mb-3">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h2 class="h6 fw-bold mb-0 d-flex align-items-center gap-2"><i data-lucide="package" class="mm-icon"></i> Items received</h2>
                    <button type="button" class="btn btn-sm btn-outline-mm" id="addItemRow">
                        <i data-lucide="plus" class="mm-icon" style="width:14px;height:14px;"></i> Add item
                    </button>
                </div>

                <?php if (empty($products)): ?>
                    <div class="alert alert-warning small mb-0">
                        You don't have any active products yet.
                        <a href="?p=merchant/product-add">Add a product</a> first, then create a Gate Entry against it.
                    </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0" id="itemsTable">
                        <thead class="table-light">
                            <tr>
                                <th style="min-width: 240px;">Product *</th>
                                <th style="width: 110px;">Unit</th>
                                <th class="text-end" style="width: 130px;">Qty *</th>
                                <th class="text-end" style="width: 150px;">Unit price (₹)</th>
                                <th class="text-end" style="width: 130px;">Line total</th>
                                <th style="width: 60px;"></th>
                            </tr>
                        </thead>
                        <tbody id="itemsBody">
                            <!-- One initial row, JS adds more -->
                            <tr class="item-row">
                                <td>
                                    <select name="items[0][product_id]" class="form-select form-select-sm item-product" required>
                                        <option value="">— Choose product —</option>
                                        <?php foreach ($products as $p): ?>
                                            <option value="<?= (int)$p['id'] ?>"
                                                    data-unit="<?= e(strtolower((string)($p['unit'] ?? 'kg'))) ?>"
                                                    data-price="<?= e((string)$p['price_per_kg']) ?>">
                                                <?= e((string)$p['name']) ?><?= !empty($p['part_code']) ? ' · ' . e((string)$p['part_code']) : '' ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td>
                                    <select name="items[0][unit]" class="form-select form-select-sm item-unit">
                                        <?php foreach ($unitOptions as $val => $label): ?>
                                            <option value="<?= e($val) ?>"><?= e($label) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td>
                                    <input class="form-control form-control-sm text-end item-qty"
                                           type="number" step="0.01" min="0" name="items[0][qty_kg]" placeholder="0.00">
                                </td>
                                <td>
                                    <input class="form-control form-control-sm text-end item-price"
                                           type="number" step="0.01" min="0" name="items[0][unit_price]" placeholder="0.00">
                                </td>
                                <td class="text-end fw-semibold item-total">₹0.00</td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-outline-danger item-remove" title="Remove row">
                                        <i data-lucide="trash-2" style="width:14px;height:14px;"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <th colspan="4" class="text-end">Grand total</th>
                                <th class="text-end" id="grandTotal">₹0.00</th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <?php endif; ?>
            </div>

            <div class="d-flex gap-2 mb-4">
                <button class="btn btn-mm" type="submit" <?= empty($products) ? 'disabled' : '' ?>>Create Gate Entry &amp; GRN</button>
                <a class="btn btn-outline-secondary" href="?p=merchant/grn">Cancel</a>
            </div>
        </form>
    </div>

    <!-- Template for new rows -->
    <template id="itemRowTemplate">
        <tr class="item-row">
            <td>
                <select name="items[__IDX__][product_id]" class="form-select form-select-sm item-product" required>
                    <option value="">— Choose product —</option>
                    <?php foreach ($products as $p): ?>
                        <option value="<?= (int)$p['id'] ?>"
                                data-unit="<?= e(strtolower((string)($p['unit'] ?? 'kg'))) ?>"
                                data-price="<?= e((string)$p['price_per_kg']) ?>">
                            <?= e((string)$p['name']) ?><?= !empty($p['part_code']) ? ' · ' . e((string)$p['part_code']) : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </td>
            <td>
                <select name="items[__IDX__][unit]" class="form-select form-select-sm item-unit">
                    <?php foreach ($unitOptions as $val => $label): ?>
                        <option value="<?= e($val) ?>"><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
            <td>
                <input class="form-control form-control-sm text-end item-qty"
                       type="number" step="0.01" min="0" name="items[__IDX__][qty_kg]" placeholder="0.00">
            </td>
            <td>
                <input class="form-control form-control-sm text-end item-price"
                       type="number" step="0.01" min="0" name="items[__IDX__][unit_price]" placeholder="0.00">
            </td>
            <td class="text-end fw-semibold item-total">₹0.00</td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-outline-danger item-remove" title="Remove row">
                    <i data-lucide="trash-2" style="width:14px;height:14px;"></i>
                </button>
            </td>
        </tr>
    </template>

    <script>
    (function () {
        if (window.lucide) window.lucide.createIcons();

        const tbody = document.getElementById('itemsBody');
        const addBtn = document.getElementById('addItemRow');
        const tpl = document.getElementById('itemRowTemplate');
        const grandTotalEl = document.getElementById('grandTotal');
        if (!tbody || !addBtn || !tpl) return;

        function fmt(n) {
            return '₹' + (Number(n) || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function recalcRow(row) {
            const qty = parseFloat(row.querySelector('.item-qty').value) || 0;
            const px  = parseFloat(row.querySelector('.item-price').value) || 0;
            const tot = qty * px;
            row.querySelector('.item-total').textContent = fmt(tot);
            return tot;
        }

        function recalcAll() {
            let g = 0;
            tbody.querySelectorAll('tr.item-row').forEach(r => { g += recalcRow(r); });
            grandTotalEl.textContent = fmt(g);
        }

        function bindRow(row) {
            const sel  = row.querySelector('.item-product');
            const qty  = row.querySelector('.item-qty');
            const px   = row.querySelector('.item-price');
            const rm   = row.querySelector('.item-remove');
            const unit = row.querySelector('.item-unit');

            sel.addEventListener('change', () => {
                const opt = sel.options[sel.selectedIndex];
                // Auto-select the product's default unit, but the user can override.
                if (opt && opt.dataset && opt.dataset.unit && unit) {
                    const wanted = opt.dataset.unit.toLowerCase();
                    for (let i = 0; i < unit.options.length; i++) {
                        if (unit.options[i].value.toLowerCase() === wanted) {
                            unit.selectedIndex = i;
                            break;
                        }
                    }
                }
                // Pre-fill price with the seller's catalog price as a starting hint
                if (opt && opt.dataset && opt.dataset.price && !px.value) {
                    px.value = opt.dataset.price;
                    recalcRow(row);
                    recalcAll();
                }
            });
            qty.addEventListener('input', recalcAll);
            px.addEventListener('input', recalcAll);
            rm.addEventListener('click', () => {
                if (tbody.querySelectorAll('tr.item-row').length <= 1) {
                    // Reset rather than remove the last row
                    sel.selectedIndex = 0;
                    if (unit) unit.selectedIndex = 0;
                    qty.value = '';
                    px.value = '';
                    row.querySelector('.item-total').textContent = '₹0.00';
                } else {
                    row.remove();
                }
                recalcAll();
            });
        }

        // Bind initial row
        tbody.querySelectorAll('tr.item-row').forEach(bindRow);

        let nextIdx = 1;
        addBtn.addEventListener('click', () => {
            const html = tpl.innerHTML.replaceAll('__IDX__', String(nextIdx++));
            const wrap = document.createElement('tbody');
            wrap.innerHTML = html.trim();
            const newRow = wrap.querySelector('tr');
            tbody.appendChild(newRow);
            if (window.lucide) window.lucide.createIcons();
            bindRow(newRow);
        });
    })();
    </script>
    <?php
};

require __DIR__ . '/../../views/layout.php';
