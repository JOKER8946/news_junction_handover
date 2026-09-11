<?php

declare(strict_types=1);

auth_require_role('merchant');

$title = 'Orders';
$merchantId = (int)auth_user_id();

$filter = $_GET['filter'] ?? 'all';
$allowedFilters = ['all', 'pending', 'packed', 'ready_to_pick', 'shipped', 'delivered', 'cancelled', 'returned'];
if (!in_array($filter, $allowedFilters, true)) {
    $filter = 'all';
}

ensure_order_column($db, 'cancelled_at', 'DATETIME NULL');
ensure_order_column($db, 'cancel_reason', "VARCHAR(255) NULL");
ensure_order_column($db, 'returned_at', 'DATETIME NULL');
ensure_order_column($db, 'return_reason', "VARCHAR(255) NULL");

// Hide 'new'-status orders from merchants. 'new' means the buyer started
// checkout but Razorpay hasn't confirmed payment yet — those rows are payment
// drafts, not real orders. Merchants only see orders that have actually been
// paid (or progressed further). Abandoned drafts get tidied up by
// cleanup_abandoned_payment_orders() (called from buyer orders page).
$whereClause = "o.merchant_id = :mid AND o.status != 'new'";
$params = ['mid' => $merchantId];
if ($filter === 'pending') {
    $whereClause .= " AND o.status = 'paid'";
} elseif ($filter === 'cancelled') {
    $whereClause .= " AND o.status = 'cancelled'";
} elseif ($filter === 'returned') {
    $whereClause .= " AND o.status = 'returned'";
} elseif ($filter !== 'all') {
    $whereClause .= " AND o.status = :filter_status";
    $params['filter_status'] = $filter;
}

$orders = db_fetch_all($db, "
    SELECT
        o.id, o.order_no, o.status, o.total_amount, o.created_at,
        o.cancelled_at, o.cancel_reason,
        o.returned_at, o.return_reason,
        o.invoice_file, o.invoice_generated_at,
        u.full_name, u.phone, u.email,
        COUNT(oi.id) AS item_count
    FROM orders o
    JOIN users u ON u.id = o.buyer_id
    LEFT JOIN order_items oi ON oi.order_id = o.id
    WHERE $whereClause
    GROUP BY o.id
    ORDER BY o.created_at DESC
", $params);

$counts = db_fetch_one($db, "
    SELECT
      SUM(CASE WHEN status != 'new' THEN 1 ELSE 0 END) AS total,
      SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) AS pending,
      SUM(CASE WHEN status = 'packed' THEN 1 ELSE 0 END) AS packed,
      SUM(CASE WHEN status = 'ready_to_pick' THEN 1 ELSE 0 END) AS ready_to_pick,
      SUM(CASE WHEN status IN ('shipped','in_transit') THEN 1 ELSE 0 END) AS shipped,
      SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) AS delivered,
      SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) AS cancelled,
      SUM(CASE WHEN status = 'returned' THEN 1 ELSE 0 END) AS returned,
      COALESCE(SUM(CASE WHEN status NOT IN ('new','cancelled','returned') THEN total_amount ELSE 0 END), 0) AS total_revenue
    FROM orders
    WHERE merchant_id = :mid
", ['mid' => $merchantId]) ?: [];

$content = function () use ($orders, $filter, $allowedFilters, $counts) {
    $filterLabels = [
        'all' => 'All',
        'pending' => 'Pending',
        'packed' => 'Packed',
        'ready_to_pick' => 'Ready to pick',
        'shipped' => 'Shipped',
        'delivered' => 'Delivered',
        'cancelled' => 'Cancelled',
        'returned' => 'Returned',
    ];
    $countMap = [
        'all' => (int)($counts['total'] ?? 0),
        'pending' => (int)($counts['pending'] ?? 0),
        'packed' => (int)($counts['packed'] ?? 0),
        'ready_to_pick' => (int)($counts['ready_to_pick'] ?? 0),
        'shipped' => (int)($counts['shipped'] ?? 0),
        'delivered' => (int)($counts['delivered'] ?? 0),
        'cancelled' => (int)($counts['cancelled'] ?? 0),
        'returned' => (int)($counts['returned'] ?? 0),
    ];

    $statusPillClass = function (string $s): string {
        switch ($s) {
            case 'new': return 'km-pill--new';
            case 'paid': return 'km-pill--paid';
            case 'packed': return 'km-pill--packed';
            case 'ready_to_pick': return 'km-pill--ready';
            case 'shipped':
            case 'in_transit': return 'km-pill--shipped';
            case 'delivered': return 'km-pill--delivered';
            case 'cancelled':
            case 'returned': return 'km-pill--cancelled';
        }
        return 'km-pill--neutral';
    };

    $statusLabel = function (string $s): string {
        return ucwords(str_replace('_', ' ', $s));
    };
?>
  <style>
    /* Page-local refinements layered on the global .km-admin theme */
    .km-admin .km-table-scroll {
      max-height: calc(100vh - 360px);
      min-height: 320px;
      overflow-y: auto;
      overflow-x: auto;
    }
    .km-admin .km-orders-table { width: 100%; border-collapse: collapse; font-size: .88rem; background: #fff; }
    .km-admin .km-orders-table th,
    .km-admin .km-orders-table td { padding: 11px 14px; border-bottom: 1px solid var(--km-line); vertical-align: middle; }
    .km-admin .km-orders-table th {
      background: #F9FAFB; text-align: left;
      font-size: .7rem; font-weight: 600; letter-spacing: .04em; text-transform: uppercase;
      color: var(--km-muted); white-space: nowrap;
      position: sticky; top: 0; z-index: 1;
      box-shadow: inset 0 -1px 0 var(--km-line);
    }
    .km-admin .km-orders-table tbody tr:last-child td { border-bottom: 0; }
    .km-admin .km-orders-table tbody tr:hover { background: #F9FAFB; }
    .km-admin .km-col-narrow { width: 1%; white-space: nowrap; }
    .km-admin .km-customer { max-width: 220px; }
    .km-admin .km-customer-name { font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .km-admin .km-customer-meta { color: var(--km-muted); font-size: .78rem; margin-top: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .km-admin .km-orderno { font-weight: 600; font-variant-numeric: tabular-nums; }
    .km-admin .km-amount { font-weight: 600; font-variant-numeric: tabular-nums; }
    .km-admin .km-date-main { white-space: nowrap; }
    .km-admin .km-date-sub { color: var(--km-muted); font-size: .76rem; white-space: nowrap; margin-top: 2px; }
    .km-admin .km-row-actions { display: inline-flex; gap: 6px; flex-wrap: nowrap; }
    .km-admin .km-btn-xs {
      padding: 4px 10px; font-size: .75rem; font-weight: 500; border-radius: 6px;
      display: inline-flex; align-items: center; gap: 4px;
      border: 1px solid var(--km-line-strong); background: #fff; color: var(--km-text);
      text-decoration: none; transition: background .15s, border-color .15s;
    }
    .km-admin .km-btn-xs:hover { background: #F9FAFB; color: var(--km-text); }
    .km-admin .km-btn-xs.km-btn-dark {
      background: var(--km-ink); color: #fff; border-color: var(--km-ink);
    }
    .km-admin .km-btn-xs.km-btn-dark:hover { background: #000; color: #fff; }

    /* By-status chip strip (HRMS "By Type" style) */
    .km-admin .km-bystatus { display: flex; flex-wrap: wrap; gap: 8px; }
    .km-admin .km-bystatus a {
      display: inline-flex; align-items: center; gap: 6px;
      padding: 6px 12px; border-radius: 999px;
      background: #F3F4F6; color: var(--km-text);
      font-size: .82rem; font-weight: 500;
      text-decoration: none; border: 1px solid var(--km-line);
    }
    .km-admin .km-bystatus a:hover { background: #E5E7EB; }
    .km-admin .km-bystatus a strong { font-weight: 700; }

    /* Mobile: collapse table into stacked cards */
    @media (max-width: 768px) {
      .km-admin .km-orders-table thead { display: none; }
      .km-admin .km-orders-table, .km-admin .km-orders-table tbody,
      .km-admin .km-orders-table tr, .km-admin .km-orders-table td { display: block; width: 100%; }
      .km-admin .km-orders-table tr { padding: 14px 16px; border-bottom: 1px solid var(--km-line); }
      .km-admin .km-orders-table td { padding: 4px 0; border: 0; display: flex; justify-content: space-between; gap: 12px; }
      .km-admin .km-orders-table td::before {
        content: attr(data-label);
        color: var(--km-muted); font-size: .76rem; font-weight: 600;
        text-transform: uppercase; letter-spacing: .04em;
      }
      .km-admin .km-orders-table .km-row-actions { justify-content: flex-end; flex-wrap: wrap; }
    }
  </style>

  <div class="km-admin">
    <div class="container py-4">

      <!-- Page head -->
      <div class="km-page-head">
        <div>
          <h1>Orders</h1>
          <div class="km-sub"><?= number_format((int)$counts['total']) ?> total orders</div>
        </div>
        <a class="km-btn" href="?p=merchant/dashboard">
          <i data-lucide="arrow-left" style="width:16px;height:16px;"></i> Back
        </a>
      </div>

      <!-- KPI cards (HRMS-style summary row) -->
      <div class="row g-3 mb-3">
        <div class="col-6 col-md-3">
          <div class="km-stat km-stat--blue" style="text-align:left; padding: 18px;">
            <div class="km-stat-label" style="text-transform:uppercase; letter-spacing:.06em; font-size:.72rem;">Total Orders</div>
            <div class="km-stat-num" style="font-size:1.9rem;"><?= number_format((int)$counts['total']) ?></div>
            <div class="km-stat-label" style="margin-top:2px;">All time</div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="km-stat km-stat--orange" style="text-align:left; padding: 18px;">
            <div class="km-stat-label" style="text-transform:uppercase; letter-spacing:.06em; font-size:.72rem;">Pending</div>
            <div class="km-stat-num" style="font-size:1.9rem;"><?= number_format((int)$counts['pending']) ?></div>
            <div class="km-stat-label" style="margin-top:2px;">Awaiting action</div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="km-stat km-stat--green" style="text-align:left; padding: 18px;">
            <div class="km-stat-label" style="text-transform:uppercase; letter-spacing:.06em; font-size:.72rem;">Delivered</div>
            <div class="km-stat-num" style="font-size:1.9rem;"><?= number_format((int)$counts['delivered']) ?></div>
            <div class="km-stat-label" style="margin-top:2px;">Completed</div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="km-stat km-stat--red" style="text-align:left; padding: 18px;">
            <div class="km-stat-label" style="text-transform:uppercase; letter-spacing:.06em; font-size:.72rem;">Issues</div>
            <div class="km-stat-num" style="font-size:1.9rem;"><?= number_format((int)$counts['cancelled'] + (int)$counts['returned']) ?></div>
            <div class="km-stat-label" style="margin-top:2px;"><?= (int)$counts['cancelled'] ?> cancelled · <?= (int)$counts['returned'] ?> returned</div>
          </div>
        </div>
      </div>

      <!-- Filter tabs -->
      <div class="km-tab-segment mb-3">
        <?php foreach ($allowedFilters as $f):
          $isActive = $filter === $f;
          $count = $countMap[$f] ?? 0;
        ?>
          <a class="km-tab <?= $isActive ? 'active' : '' ?>" href="?p=merchant/orders&filter=<?= urlencode($f) ?>">
            <span><?= e($filterLabels[$f] ?? ucfirst($f)) ?></span>
            <?php if ($count > 0): ?>
              <span class="km-count"><?= $count ?></span>
            <?php endif; ?>
          </a>
        <?php endforeach; ?>
      </div>

      <!-- Orders table -->
      <div class="km-card" style="overflow:hidden;">
        <?php if (!$orders): ?>
          <div class="text-center py-5" style="color: var(--km-muted);">No orders found</div>
        <?php else: ?>
          <div class="km-table-scroll">
            <table class="km-orders-table">
              <thead>
                <tr>
                  <th class="km-col-narrow">Order #</th>
                  <th class="km-customer">Customer</th>
                  <th class="km-col-narrow">Items</th>
                  <th class="km-col-narrow">Total</th>
                  <th class="km-col-narrow">Status</th>
                  <th class="km-col-narrow">Date</th>
                  <th class="km-col-narrow text-end">Action</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($orders as $order): ?>
                  <tr>
                    <td data-label="Order #" class="km-col-narrow">
                      <span class="km-orderno"><?= e((string)$order['order_no']) ?></span>
                    </td>
                    <td data-label="Customer" class="km-customer">
                      <div class="km-customer-name" title="<?= e((string)$order['full_name']) ?>"><?= e((string)$order['full_name']) ?></div>
                      <?php if (!empty($order['phone'])): ?>
                        <div class="km-customer-meta"><?= e((string)$order['phone']) ?></div>
                      <?php endif; ?>
                    </td>
                    <td data-label="Items" class="km-col-narrow">
                      <?= (int)$order['item_count'] ?> <?= ((int)$order['item_count']) === 1 ? 'item' : 'items' ?>
                    </td>
                    <td data-label="Total" class="km-col-narrow">
                      <span class="km-amount">₹<?= number_format((float)$order['total_amount'], 0) ?></span>
                    </td>
                    <td data-label="Status" class="km-col-narrow">
                      <span class="km-pill <?= $statusPillClass((string)$order['status']) ?>">
                        <?= e($statusLabel((string)$order['status'])) ?>
                      </span>
                      <?php if ($order['status'] === 'cancelled' && !empty($order['cancelled_at'])): ?>
                        <div class="km-customer-meta" style="margin-top:4px;">
                          <?= date('d M Y', strtotime((string)$order['cancelled_at'])) ?>
                        </div>
                      <?php endif; ?>
                      <?php if ($order['status'] === 'returned' && !empty($order['returned_at'])): ?>
                        <div class="km-customer-meta" style="margin-top:4px;">
                          <?= date('d M Y', strtotime((string)$order['returned_at'])) ?>
                        </div>
                      <?php endif; ?>
                    </td>
                    <td data-label="Date" class="km-col-narrow">
                      <div class="km-date-main"><?= date('d M Y', strtotime((string)$order['created_at'])) ?></div>
                      <div class="km-date-sub"><?= date('h:i A', strtotime((string)$order['created_at'])) ?></div>
                    </td>
                    <td data-label="Action" class="km-col-narrow text-end">
                      <div class="km-row-actions">
                        <a class="km-btn-xs km-btn-dark" href="?p=merchant/order&id=<?= (int)$order['id'] ?>">View</a>
                        <?php if (!empty($order['invoice_file']) || !empty($order['invoice_generated_at'])): ?>
                          <?php if (!empty($order['invoice_file'])): ?>
                            <a class="km-btn-xs" href="<?= e($order['invoice_file']) ?>" target="_blank">Invoice</a>
                          <?php else: ?>
                            <a class="km-btn-xs" href="?p=merchant/order-invoice&id=<?= (int)$order['id'] ?>">Invoice</a>
                          <?php endif; ?>
                        <?php endif; ?>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <script>if (window.lucide) window.lucide.createIcons();</script>
<?php
};

require __DIR__ . '/../../views/layout.php';
