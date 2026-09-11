<?php

declare(strict_types=1);

auth_require_role('super_admin');

$title = 'Seller Commissions';

// CSV download — handled before any HTML output.
if (($_GET['export'] ?? '') === 'csv') {
    $period   = (string)($_GET['period'] ?? 'month');   // month | quarter | year
    $year     = (int)($_GET['year'] ?? (int)date('Y'));
    $month    = (int)($_GET['month'] ?? (int)date('n'));
    $quarter  = max(1, min(4, (int)($_GET['quarter'] ?? ceil((int)date('n') / 3))));
    $sellerId = (int)($_GET['seller_id'] ?? 0);

    if ($period === 'month') {
        $start = sprintf('%04d-%02d-01', $year, $month);
        $end   = date('Y-m-01', strtotime($start . ' +1 month'));
        $label = date('M_Y', strtotime($start));
    } elseif ($period === 'quarter') {
        $startMonth = ($quarter - 1) * 3 + 1;
        $start = sprintf('%04d-%02d-01', $year, $startMonth);
        $end   = date('Y-m-01', strtotime($start . ' +3 month'));
        $label = 'Q' . $quarter . '_' . $year;
    } else {
        $start = sprintf('%04d-01-01', $year);
        $end   = sprintf('%04d-01-01', $year + 1);
        $label = (string)$year;
    }

    $whereSeller = '';
    $bind = ['start' => $start, 'end' => $end];
    if ($sellerId > 0) {
        $whereSeller = ' AND o.merchant_id = :sid';
        $bind['sid'] = $sellerId;
    }

    $rows = db_fetch_all($db, "
        SELECT
            o.order_no, o.created_at, o.status,
            u.full_name AS seller_name, mp.business_name,
            p.name AS product_name, p.part_code, p.unit,
            oi.qty_kg, oi.price_per_kg, oi.line_total,
            oi.commission_pct, oi.commission_amount, oi.seller_payable
        FROM order_items oi
        JOIN orders o ON o.id = oi.order_id
        LEFT JOIN products p ON p.id = oi.product_id
        LEFT JOIN users u ON u.id = o.merchant_id
        LEFT JOIN merchant_profile mp ON mp.merchant_user_id = o.merchant_id
        WHERE o.created_at >= :start AND o.created_at < :end
          AND o.status NOT IN ('new','cancelled','returned','refunded')
          $whereSeller
        ORDER BY o.created_at, o.id, oi.id
    ", $bind) ?: [];

    $filename = 'commissions_' . $label . ($sellerId > 0 ? '_seller-' . $sellerId : '') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Date', 'Order #', 'Seller', 'Product', 'Part Code', 'Unit', 'Qty', 'Price', 'Line Total', 'Commission %', 'Commission ₹', 'Seller Payable ₹']);
    $totLine = $totComm = $totPay = 0.0;
    foreach ($rows as $r) {
        fputcsv($out, [
            date('Y-m-d', strtotime((string)$r['created_at'])),
            (string)$r['order_no'],
            (string)($r['business_name'] ?: $r['seller_name']),
            (string)$r['product_name'],
            (string)($r['part_code'] ?? ''),
            (string)($r['unit'] ?? 'kg'),
            (string)$r['qty_kg'],
            (string)$r['price_per_kg'],
            (string)$r['line_total'],
            (string)$r['commission_pct'],
            (string)$r['commission_amount'],
            (string)$r['seller_payable'],
        ]);
        $totLine += (float)$r['line_total'];
        $totComm += (float)$r['commission_amount'];
        $totPay  += (float)$r['seller_payable'];
    }
    fputcsv($out, []);
    fputcsv($out, ['TOTALS', '', '', '', '', '', '', '', number_format($totLine, 2, '.', ''), '', number_format($totComm, 2, '.', ''), number_format($totPay, 2, '.', '')]);
    fclose($out);
    exit;
}

// POST: update commission_pct for a product
if (request_method() === 'POST' && post_string('action') === 'update_pct') {
    $productId = (int)post_string('product_id');
    $rawPct    = trim(post_string('commission_pct'));
    if ($productId <= 0) {
        flash_set('error', 'Missing product.');
        redirect_to('super-admin/commissions');
    }
    if ($rawPct === '') {
        db_exec($db, 'UPDATE products SET commission_pct = NULL WHERE id = :id', ['id' => $productId]);
        flash_set('success', 'Reverted to platform default.');
    } else {
        $pct = max(0.0, min(100.0, (float)$rawPct));
        db_exec($db, 'UPDATE products SET commission_pct = :pct WHERE id = :id', ['pct' => $pct, 'id' => $productId]);
        flash_set('success', 'Commission updated.');
    }
    $qs = (int)post_string('seller_id') > 0 ? '?seller_id=' . (int)post_string('seller_id') : '';
    redirect_to('super-admin/commissions' . $qs);
}

$platformPct = platform_commission_pct($db);

$sellerFilter = (int)($_GET['seller_id'] ?? 0);
$sellers = db_fetch_all($db, "
    SELECT u.id, u.full_name, mp.business_name
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

// Aggregate "earned commission" and "seller payable" so far for each product.
$rows = db_fetch_all($db, "
    SELECT
        p.id, p.name, p.part_code, p.product_type, p.unit, p.price_per_kg,
        p.commission_pct AS override_pct, p.is_active,
        p.merchant_id,
        u.full_name AS seller_name,
        mp.business_name,
        COALESCE(agg.lifetime_revenue, 0) AS lifetime_revenue,
        COALESCE(agg.lifetime_commission, 0) AS lifetime_commission,
        COALESCE(agg.lifetime_payable, 0) AS lifetime_payable,
        COALESCE(agg.orders_count, 0) AS orders_count
    FROM products p
    LEFT JOIN users u ON u.id = p.merchant_id
    LEFT JOIN merchant_profile mp ON mp.merchant_user_id = p.merchant_id
    LEFT JOIN (
        SELECT oi.product_id,
               SUM(oi.line_total)       AS lifetime_revenue,
               SUM(oi.commission_amount) AS lifetime_commission,
               SUM(oi.seller_payable)    AS lifetime_payable,
               COUNT(DISTINCT oi.order_id) AS orders_count
        FROM order_items oi
        JOIN orders o ON o.id = oi.order_id
        WHERE o.status NOT IN ('new','cancelled','returned','refunded')
        GROUP BY oi.product_id
    ) agg ON agg.product_id = p.id
    $whereSeller
    ORDER BY u.full_name, p.name
", $bind) ?: [];

// Group by seller
$grouped = [];
$totalCommissionEarned = 0.0;
$totalSellerPayable    = 0.0;
foreach ($rows as $r) {
    $sid = (int)$r['merchant_id'];
    if (!isset($grouped[$sid])) {
        $grouped[$sid] = [
            'seller_name'        => (string)($r['business_name'] ?: $r['seller_name'] ?: ('Seller #' . $sid)),
            'products'           => [],
            'lifetime_commission'=> 0.0,
            'lifetime_payable'   => 0.0,
            'lifetime_revenue'   => 0.0,
        ];
    }
    $grouped[$sid]['products'][] = $r;
    $grouped[$sid]['lifetime_commission'] += (float)$r['lifetime_commission'];
    $grouped[$sid]['lifetime_payable']    += (float)$r['lifetime_payable'];
    $grouped[$sid]['lifetime_revenue']    += (float)$r['lifetime_revenue'];
    $totalCommissionEarned += (float)$r['lifetime_commission'];
    $totalSellerPayable    += (float)$r['lifetime_payable'];
}

$content = function () use ($grouped, $sellers, $sellerFilter, $platformPct, $totalCommissionEarned, $totalSellerPayable) {
    require __DIR__ . '/../../views/partials/super-admin-nav.php';
    $success = flash_get('success');
    $error   = flash_get('error');
    ?>
    <div class="container">
      <h1 class="h4 mb-1">Seller Commissions</h1>
      <p class="text-muted small mb-3">
        Set the commission % the platform takes per product. Leave the input blank to fall back to the
        platform default (<strong><?= number_format($platformPct, 2) ?>%</strong>). The rate is snapshotted into each
        order line at sale time, so changes here don't retroactively affect past orders.
      </p>

      <?php if ($success): ?><div class="alert alert-success py-2"><?= e($success) ?></div><?php endif; ?>
      <?php if ($error):   ?><div class="alert alert-danger py-2"><?= e($error) ?></div><?php endif; ?>

      <div class="row g-3 mb-3">
        <div class="col-md-4">
          <div class="bg-white border rounded-3 p-3">
            <div class="text-muted small mb-1">Lifetime platform earnings</div>
            <div class="h5 mb-0 text-success">₹<?= number_format($totalCommissionEarned, 2) ?></div>
            <div class="small text-muted mt-1">Commission already earned across all sellers.</div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="bg-white border rounded-3 p-3">
            <div class="text-muted small mb-1">Lifetime seller earnings</div>
            <div class="h5 mb-0">₹<?= number_format($totalSellerPayable, 2) ?></div>
            <div class="small text-muted mt-1">Total payable to sellers (gross of payouts).</div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="bg-white border rounded-3 p-3">
            <div class="text-muted small mb-1">Default platform commission</div>
            <div class="h5 mb-0"><?= number_format($platformPct, 2) ?>%</div>
            <div class="small text-muted mt-1"><a href="?p=super-admin/platform-settings">Change platform default</a></div>
          </div>
        </div>
      </div>

      <!-- Filter -->
      <form method="get" class="d-flex align-items-center gap-2 mb-3 flex-wrap">
        <input type="hidden" name="p" value="super-admin/commissions">
        <label class="form-label small mb-0">Seller:</label>
        <select name="seller_id" class="form-select form-select-sm" style="max-width: 280px;" onchange="this.form.submit()">
          <option value="0">— All sellers —</option>
          <?php foreach ($sellers as $s): ?>
            <option value="<?= (int)$s['id'] ?>" <?= $sellerFilter === (int)$s['id'] ? 'selected' : '' ?>>
              <?= e((string)($s['business_name'] ?: $s['full_name'])) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <?php if ($sellerFilter > 0): ?>
          <a class="btn btn-sm btn-outline-secondary" href="?p=super-admin/commissions">Clear</a>
        <?php endif; ?>
      </form>

      <!-- Report download -->
      <form method="get" class="bg-white border rounded-3 p-3 mb-4">
        <input type="hidden" name="p" value="super-admin/commissions">
        <input type="hidden" name="export" value="csv">
        <input type="hidden" name="seller_id" value="<?= $sellerFilter ?>">
        <div class="row g-2 align-items-end">
          <div class="col-auto">
            <div class="fw-semibold small mb-1">Download commission report</div>
            <div class="small text-muted">Filters by order date. Excludes cancelled/returned/refunded orders.</div>
          </div>
          <div class="col-auto">
            <label class="form-label small mb-1">Period</label>
            <select name="period" class="form-select form-select-sm" id="periodSel" onchange="togglePeriodFields()">
              <option value="month">Month</option>
              <option value="quarter">Quarter</option>
              <option value="year">Year</option>
            </select>
          </div>
          <div class="col-auto" id="monthCol">
            <label class="form-label small mb-1">Month</label>
            <select name="month" class="form-select form-select-sm">
              <?php for ($m = 1; $m <= 12; $m++): ?>
                <option value="<?= $m ?>" <?= $m === (int)date('n') ? 'selected' : '' ?>><?= date('M', strtotime('2026-' . $m . '-01')) ?></option>
              <?php endfor; ?>
            </select>
          </div>
          <div class="col-auto" id="quarterCol" style="display:none;">
            <label class="form-label small mb-1">Quarter</label>
            <select name="quarter" class="form-select form-select-sm">
              <option value="1">Q1 (Jan–Mar)</option>
              <option value="2">Q2 (Apr–Jun)</option>
              <option value="3">Q3 (Jul–Sep)</option>
              <option value="4">Q4 (Oct–Dec)</option>
            </select>
          </div>
          <div class="col-auto">
            <label class="form-label small mb-1">Year</label>
            <input class="form-control form-control-sm" type="number" name="year" min="2024" max="2099" value="<?= (int)date('Y') ?>" style="width: 100px;">
          </div>
          <div class="col-auto">
            <button class="btn btn-sm btn-mm" type="submit">
              <i data-lucide="download" class="mm-icon" style="width:14px;height:14px;"></i> Download CSV
            </button>
          </div>
        </div>
      </form>
      <script>
        function togglePeriodFields() {
          var sel = document.getElementById('periodSel').value;
          document.getElementById('monthCol').style.display   = sel === 'month'   ? '' : 'none';
          document.getElementById('quarterCol').style.display = sel === 'quarter' ? '' : 'none';
        }
      </script>

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
                  &middot; Lifetime revenue ₹<?= number_format($g['lifetime_revenue'], 2) ?>
                  &middot; Platform earned ₹<?= number_format($g['lifetime_commission'], 2) ?>
                  &middot; Seller earned ₹<?= number_format($g['lifetime_payable'], 2) ?>
                </div>
              </div>
            </div>
            <table class="table table-sm mb-0 align-middle">
              <thead class="table-light">
                <tr>
                  <th>Product</th>
                  <th>Part Code</th>
                  <th>Unit</th>
                  <th class="text-end">Price</th>
                  <th>Commission %</th>
                  <th class="text-end">Lifetime Revenue</th>
                  <th class="text-end">Platform Earned</th>
                  <th class="text-end">Seller Earned</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($g['products'] as $p): ?>
                  <?php
                  $override = $p['override_pct'] !== null ? (float)$p['override_pct'] : null;
                  $effective = $override !== null ? $override : $platformPct;
                  ?>
                  <tr>
                    <td>
                      <div class="fw-semibold"><?= e((string)$p['name']) ?></div>
                      <?php if (!empty($p['product_type'])): ?>
                        <div class="small text-muted"><?= e((string)$p['product_type']) ?></div>
                      <?php endif; ?>
                    </td>
                    <td class="small text-muted"><?= e((string)($p['part_code'] ?? '')) ?></td>
                    <td><?= e(product_unit_label($p['unit'] ?? 'kg')) ?></td>
                    <td class="text-end">₹<?= number_format((float)$p['price_per_kg'], 2) ?></td>
                    <td>
                      <form method="post" class="d-flex align-items-center gap-2">
                        <input type="hidden" name="action" value="update_pct">
                        <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
                        <input type="hidden" name="seller_id" value="<?= $sid ?>">
                        <input class="form-control form-control-sm" type="number" step="0.01" min="0" max="100"
                               name="commission_pct"
                               value="<?= $override !== null ? e(number_format($override, 2, '.', '')) : '' ?>"
                               placeholder="<?= number_format($platformPct, 2) ?>"
                               style="max-width: 90px;">
                        <span class="small text-muted">%</span>
                        <button class="btn btn-sm btn-mm" type="submit">Save</button>
                        <?php if ($override === null): ?>
                          <span class="badge bg-light text-muted border" title="Uses platform default">Default</span>
                        <?php else: ?>
                          <span class="badge bg-primary" title="Overrides the platform default">Custom</span>
                        <?php endif; ?>
                      </form>
                      <div class="small text-muted mt-1">Effective: <?= number_format($effective, 2) ?>%</div>
                    </td>
                    <td class="text-end">₹<?= number_format((float)$p['lifetime_revenue'], 2) ?></td>
                    <td class="text-end text-success">₹<?= number_format((float)$p['lifetime_commission'], 2) ?></td>
                    <td class="text-end">₹<?= number_format((float)$p['lifetime_payable'], 2) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
              <tfoot class="table-light">
                <tr>
                  <th colspan="5" class="text-end">Seller totals</th>
                  <th class="text-end">₹<?= number_format($g['lifetime_revenue'], 2) ?></th>
                  <th class="text-end text-success">₹<?= number_format($g['lifetime_commission'], 2) ?></th>
                  <th class="text-end">₹<?= number_format($g['lifetime_payable'], 2) ?></th>
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
