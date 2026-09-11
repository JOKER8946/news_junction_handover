<?php

declare(strict_types=1);

auth_require_role('super_admin');

$title = 'All Shipments';

// Ensure all shipment-related order columns exist (most are added lazily
// elsewhere; if super-admin hits this before any of those flows ran, the
// columns won't be in `orders` yet and the query crashes with 1054).
ensure_order_column($db, 'shipping_tracking_no', 'VARCHAR(100) NULL');
ensure_order_column($db, 'shipped_at',           'DATETIME NULL');
ensure_order_column($db, 'delivered_at',         'DATETIME NULL');
ensure_order_column($db, 'delhivery_waybill',    'VARCHAR(50) NULL');

$filter = (string)($_GET['status'] ?? 'all');
$valid  = ['all','new','paid','packed','ready_to_pick','shipped','in_transit','delivered','cancelled','returned'];
if (!in_array($filter, $valid, true)) $filter = 'all';

$where  = '';
$params = [];
if ($filter !== 'all') {
    $where = ' WHERE o.status = :st';
    $params['st'] = $filter;
}

$orders = db_fetch_all($db, "
    SELECT o.id, o.order_no, o.parent_order_no, o.status, o.total_amount, o.created_at,
           o.delhivery_waybill, o.shipping_tracking_no, o.shipped_at, o.delivered_at,
           bu.full_name AS buyer_name, ba.city AS dest_city, ba.pincode AS dest_pincode,
           mu.full_name AS merchant_name, mp.business_name AS merchant_business
    FROM orders o
    JOIN users bu ON bu.id = o.buyer_id
    LEFT JOIN buyer_addresses ba ON ba.id = o.delivery_address_id
    LEFT JOIN users mu ON mu.id = o.merchant_id
    LEFT JOIN merchant_profile mp ON mp.merchant_user_id = o.merchant_id
    $where
    ORDER BY o.created_at DESC
    LIMIT 300
", $params);

$counts = db_fetch_one($db, "
    SELECT
      COUNT(*) AS total,
      SUM(CASE WHEN status IN ('new','paid')         THEN 1 ELSE 0 END) AS to_pack,
      SUM(CASE WHEN status IN ('packed','ready_to_pick') THEN 1 ELSE 0 END) AS to_ship,
      SUM(CASE WHEN status IN ('shipped','in_transit') THEN 1 ELSE 0 END) AS in_transit_n,
      SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) AS delivered_n,
      SUM(CASE WHEN status IN ('cancelled','returned') THEN 1 ELSE 0 END) AS reversed
    FROM orders
") ?: [];

$content = function () use ($orders, $counts, $filter, $valid) {
    require __DIR__ . '/../../views/partials/super-admin-nav.php';
    ?>
    <div class="container">
      <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
        <h1 class="h4 mb-0">All Shipments</h1>
        <div class="btn-group" role="group">
          <?php foreach ($valid as $opt): ?>
            <a class="btn btn-sm btn-outline-secondary <?= $filter === $opt ? 'active' : '' ?>"
               href="?p=super-admin/shipments<?= $opt === 'all' ? '' : '&status=' . $opt ?>">
              <?= e(ucfirst(str_replace('_',' ',$opt))) ?>
            </a>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="row g-3 mb-4">
        <?php
        $kpis = [
            ['Total orders',  $counts['total']        ?? 0, 'primary'],
            ['To pack',       $counts['to_pack']      ?? 0, 'warning'],
            ['To ship',       $counts['to_ship']      ?? 0, 'info'],
            ['In transit',    $counts['in_transit_n'] ?? 0, 'primary'],
            ['Delivered',     $counts['delivered_n']  ?? 0, 'success'],
            ['Cancelled/Returned', $counts['reversed']     ?? 0, 'danger'],
        ];
        foreach ($kpis as [$label, $val, $color]): ?>
          <div class="col-6 col-md-2">
            <div class="bg-white border rounded-3 p-2 text-center">
              <div class="small text-muted"><?= e($label) ?></div>
              <div class="h5 mb-0 text-<?= $color ?>"><?= (int)$val ?></div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <div class="bg-white border rounded-3 overflow-hidden">
        <table class="table table-sm mb-0 align-middle">
          <thead class="table-light">
            <tr>
              <th>Order</th>
              <th>Buyer</th>
              <th>Destination</th>
              <th>Seller</th>
              <th>Status</th>
              <th>AWB / Tracking</th>
              <th>Placed</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($orders)): ?>
              <tr><td colspan="7" class="text-center text-muted py-4">No orders in this view.</td></tr>
            <?php else: foreach ($orders as $o):
                $status = (string)$o['status'];
                $badge = $status === 'delivered' ? 'success'
                       : (in_array($status, ['cancelled','returned'], true) ? 'danger'
                          : (in_array($status, ['shipped','in_transit'], true) ? 'primary'
                             : (in_array($status, ['packed','ready_to_pick'], true) ? 'info'
                                : 'warning')));
                $tracking = $o['delhivery_waybill'] ?: ($o['shipping_tracking_no'] ?: '');
            ?>
              <tr>
                <td>
                  <div class="fw-semibold small"><?= e((string)$o['order_no']) ?></div>
                  <?php if (!empty($o['parent_order_no']) && $o['parent_order_no'] !== $o['order_no']): ?>
                    <div class="text-muted" style="font-size: 11px;">part of <?= e((string)$o['parent_order_no']) ?></div>
                  <?php endif; ?>
                </td>
                <td class="small"><?= e((string)($o['buyer_name'] ?? '')) ?></td>
                <td class="small text-muted">
                  <?= e((string)($o['dest_city'] ?? '—')) ?> <?= e((string)($o['dest_pincode'] ?? '')) ?>
                </td>
                <td class="small">
                  <div><?= e((string)($o['merchant_business'] ?? '—')) ?></div>
                  <div class="text-muted"><?= e((string)($o['merchant_name'] ?? '')) ?></div>
                </td>
                <td>
                  <span class="badge bg-<?= $badge ?>"><?= e(ucfirst(str_replace('_',' ',$status))) ?></span>
                </td>
                <td class="small">
                  <?php if ($tracking !== ''): ?>
                    <code><?= e($tracking) ?></code>
                  <?php else: ?>
                    <span class="text-muted">—</span>
                  <?php endif; ?>
                </td>
                <td class="small text-muted"><?= e(date('d M H:i', strtotime((string)$o['created_at']))) ?></td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php
};

require __DIR__ . '/../../views/layout.php';
