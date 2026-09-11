<?php

declare(strict_types=1);

auth_require_role('super_admin');

$title = 'Seller Inventory';

// "Sold" = order items inside an order that's been paid and not cancelled/returned/refunded.
// Excluding 'new' (unpaid cart) keeps the count to confirmed sales only.
$soldStatusSql = "o.status NOT IN ('new','cancelled','returned','refunded')";

$sellerFilter = (int)($_GET['seller_id'] ?? 0);
$sellers = db_fetch_all($db, "
    SELECT u.id, u.full_name, u.email, mp.business_name
    FROM users u
    LEFT JOIN merchant_profile mp ON mp.merchant_user_id = u.id
    WHERE u.role = 'merchant'
    ORDER BY u.full_name
") ?: [];

$whereSeller = '';
$bind = [];
if ($sellerFilter > 0) {
    $whereSeller = ' WHERE p.merchant_id = :sid';
    $bind['sid'] = $sellerFilter;
}

$rows = db_fetch_all($db, "
    SELECT
        p.id, p.name, p.part_code, p.product_type, p.unit, p.price_per_kg, p.stock_kg, p.is_active,
        p.merchant_id,
        u.full_name AS seller_name,
        mp.business_name,
        COALESCE(i.qty_kg, p.stock_kg) AS available_qty,
        COALESCE(sold.qty_sold, 0) AS sold_qty,
        COALESCE(sold.revenue, 0) AS revenue
    FROM products p
    LEFT JOIN inventory i ON i.product_id = p.id
    LEFT JOIN users u ON u.id = p.merchant_id
    LEFT JOIN merchant_profile mp ON mp.merchant_user_id = p.merchant_id
    LEFT JOIN (
        SELECT oi.product_id,
               SUM(oi.qty_kg) AS qty_sold,
               SUM(oi.line_total) AS revenue
        FROM order_items oi
        JOIN orders o ON o.id = oi.order_id
        WHERE $soldStatusSql
        GROUP BY oi.product_id
    ) sold ON sold.product_id = p.id
    $whereSeller
    ORDER BY u.full_name, p.name
", $bind) ?: [];

// Group by seller
$grouped = [];
$grandStockValue = 0.0;
$grandRevenue   = 0.0;
$grandSoldQty   = 0.0;
$grandAvailQty  = 0.0;
foreach ($rows as $r) {
    $sid = (int)$r['merchant_id'];
    if (!isset($grouped[$sid])) {
        $grouped[$sid] = [
            'seller_name'   => (string)($r['business_name'] ?: $r['seller_name'] ?: ('Seller #' . $sid)),
            'seller_email'  => '',
            'products'      => [],
            'stock_value'   => 0.0,
            'revenue'       => 0.0,
            'sold_qty'      => 0.0,
            'avail_qty'     => 0.0,
        ];
    }
    $price   = (float)$r['price_per_kg'];
    $avail   = (float)$r['available_qty'];
    $sold    = (float)$r['sold_qty'];
    $revenue = (float)$r['revenue'];
    $stockValue = $avail * $price;

    $grouped[$sid]['products'][] = $r + ['stock_value' => $stockValue];
    $grouped[$sid]['stock_value'] += $stockValue;
    $grouped[$sid]['revenue']     += $revenue;
    $grouped[$sid]['sold_qty']    += $sold;
    $grouped[$sid]['avail_qty']   += $avail;

    $grandStockValue += $stockValue;
    $grandRevenue    += $revenue;
    $grandSoldQty    += $sold;
    $grandAvailQty   += $avail;
}

$content = function () use ($grouped, $sellers, $sellerFilter, $grandStockValue, $grandRevenue, $grandSoldQty, $grandAvailQty) {
    require __DIR__ . '/../../views/partials/super-admin-nav.php';
    ?>
    <div class="container">
      <h1 class="h4 mb-3">Seller Inventory</h1>
      <p class="text-muted small">
        Live view of every seller's catalog with current stock, units sold, and total value.
        "Sold qty" and "Revenue" only count paid orders that weren't cancelled or returned.
      </p>

      <!-- KPIs -->
      <div class="row g-3 mb-4">
        <div class="col-md-3">
          <div class="bg-white border rounded-3 p-3">
            <div class="text-muted small mb-1">Total stock value</div>
            <div class="h5 mb-0">₹<?= number_format($grandStockValue, 2) ?></div>
            <div class="small text-muted mt-1">Available qty × price.</div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="bg-white border rounded-3 p-3">
            <div class="text-muted small mb-1">Lifetime revenue</div>
            <div class="h5 mb-0 text-success">₹<?= number_format($grandRevenue, 2) ?></div>
            <div class="small text-muted mt-1">Across all sellers.</div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="bg-white border rounded-3 p-3">
            <div class="text-muted small mb-1">Units sold</div>
            <div class="h5 mb-0"><?= number_format($grandSoldQty, 2) ?></div>
            <div class="small text-muted mt-1">Total qty across products.</div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="bg-white border rounded-3 p-3">
            <div class="text-muted small mb-1">Units available</div>
            <div class="h5 mb-0"><?= number_format($grandAvailQty, 2) ?></div>
            <div class="small text-muted mt-1">Current inventory.</div>
          </div>
        </div>
      </div>

      <!-- Filter -->
      <form method="get" class="d-flex align-items-center gap-2 mb-3">
        <input type="hidden" name="p" value="super-admin/inventory">
        <label class="form-label small mb-0 me-2">Filter by seller:</label>
        <select name="seller_id" class="form-select form-select-sm" style="max-width: 320px;" onchange="this.form.submit()">
          <option value="0">— All sellers —</option>
          <?php foreach ($sellers as $s): ?>
            <option value="<?= (int)$s['id'] ?>" <?= $sellerFilter === (int)$s['id'] ? 'selected' : '' ?>>
              <?= e((string)($s['business_name'] ?: $s['full_name'])) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <?php if ($sellerFilter > 0): ?>
          <a class="btn btn-sm btn-outline-secondary" href="?p=super-admin/inventory">Clear</a>
        <?php endif; ?>
      </form>

      <?php if (empty($grouped)): ?>
        <div class="bg-white border rounded-3 p-4 text-muted text-center">No products to show.</div>
      <?php else: ?>
        <?php foreach ($grouped as $sid => $g): ?>
          <div class="bg-white border rounded-3 mb-4 overflow-hidden">
            <div class="px-3 py-2 bg-light border-bottom d-flex align-items-center justify-content-between">
              <div>
                <div class="fw-semibold"><?= e($g['seller_name']) ?></div>
                <div class="small text-muted">
                  <?= count($g['products']) ?> product<?= count($g['products']) !== 1 ? 's' : '' ?>
                  &middot; Stock value ₹<?= number_format($g['stock_value'], 2) ?>
                  &middot; Revenue ₹<?= number_format($g['revenue'], 2) ?>
                </div>
              </div>
              <a class="btn btn-sm btn-outline-secondary"
                 href="?p=super-admin/merchant-wallet-detail&merchant_id=<?= $sid ?>">View wallet</a>
            </div>
            <table class="table table-sm mb-0 align-middle">
              <thead class="table-light">
                <tr>
                  <th>Item</th>
                  <th>Part Code</th>
                  <th>Unit</th>
                  <th class="text-end">Price</th>
                  <th class="text-end">Qty Available</th>
                  <th class="text-end">Sold Qty</th>
                  <th class="text-end">Stock Value</th>
                  <th class="text-end">Revenue</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($g['products'] as $p): ?>
                  <?php $unit = product_unit_label($p['unit'] ?? 'kg'); ?>
                  <tr>
                    <td>
                      <div class="fw-semibold"><?= e((string)$p['name']) ?></div>
                      <?php if (!empty($p['product_type'])): ?>
                        <div class="small text-muted"><?= e((string)$p['product_type']) ?></div>
                      <?php endif; ?>
                    </td>
                    <td class="small text-muted"><?= e((string)($p['part_code'] ?? '')) ?></td>
                    <td><?= e($unit) ?></td>
                    <td class="text-end">₹<?= number_format((float)$p['price_per_kg'], 2) ?></td>
                    <td class="text-end"><?= number_format((float)$p['available_qty'], 2) ?></td>
                    <td class="text-end"><?= number_format((float)$p['sold_qty'], 2) ?></td>
                    <td class="text-end">₹<?= number_format((float)$p['stock_value'], 2) ?></td>
                    <td class="text-end fw-semibold text-success">₹<?= number_format((float)$p['revenue'], 2) ?></td>
                    <td>
                      <?php if ((int)$p['is_active'] === 1): ?>
                        <span class="badge bg-success">Active</span>
                      <?php else: ?>
                        <span class="badge bg-secondary">Hidden</span>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
              <tfoot class="table-light">
                <tr>
                  <th colspan="4" class="text-end">Seller totals</th>
                  <th class="text-end"><?= number_format($g['avail_qty'], 2) ?></th>
                  <th class="text-end"><?= number_format($g['sold_qty'], 2) ?></th>
                  <th class="text-end">₹<?= number_format($g['stock_value'], 2) ?></th>
                  <th class="text-end text-success">₹<?= number_format($g['revenue'], 2) ?></th>
                  <th></th>
                </tr>
              </tfoot>
            </table>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
    <?php
};

require __DIR__ . '/../../views/layout.php';
