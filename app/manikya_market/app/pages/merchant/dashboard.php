<?php

declare(strict_types=1);

auth_require_role('merchant');

$title = 'Seller Dashboard';

$merchantId = (int)auth_user_id();
$merchantStatus = function_exists('merchant_profile_status') ? (merchant_profile_status($db, $merchantId) ?? 'pending') : 'approved';

$newOrders = [];
try {
    // 'new' is deliberately excluded, matching merchant/orders.php. It now means
    // only "Razorpay draft, signature not yet verified" — an order the merchant
    // cannot act on. Listing it here while the Orders page hides it is what made
    // an order appear on the dashboard but be missing from Orders. COD orders are
    // created 'paid' by checkout.php, so they show up here immediately.
    $newOrders = db_fetch_all($db, "SELECT o.id, o.order_no, o.status, o.total_amount, o.created_at, u.full_name, u.phone FROM orders o JOIN users u ON u.id = o.buyer_id WHERE o.merchant_id = :mid AND o.status = 'paid' ORDER BY o.created_at DESC LIMIT 6", ['mid' => $merchantId]);
} catch (Throwable $e) {
    $newOrders = [];
}

$readyToPickOrders = [];
try {
    $readyToPickOrders = db_fetch_all($db, "SELECT o.id, o.order_no, o.status, o.total_amount, o.created_at, u.full_name FROM orders o JOIN users u ON u.id = o.buyer_id WHERE o.merchant_id = :mid AND o.status = 'ready_to_pick' ORDER BY o.created_at DESC LIMIT 5", ['mid' => $merchantId]);
} catch (Throwable $e) {
    $readyToPickOrders = [];
}

$returnedOrders = [];
try {
    $returnedOrders = db_fetch_all($db, "SELECT o.id, o.order_no, o.status, o.total_amount, o.created_at, u.full_name FROM orders o JOIN users u ON u.id = o.buyer_id WHERE o.merchant_id = :mid AND o.status = 'returned' ORDER BY o.created_at DESC LIMIT 5", ['mid' => $merchantId]);
} catch (Throwable $e) {
    $returnedOrders = [];
}

// KPIs (scoped to this merchant only)
$counts = [];
try {
    $counts = db_fetch_one($db, "
        SELECT
          SUM(CASE WHEN status != 'new' THEN 1 ELSE 0 END) AS total,
          SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) AS pending,
          SUM(CASE WHEN status = 'packed' THEN 1 ELSE 0 END) AS packed,
          SUM(CASE WHEN status = 'ready_to_pick' THEN 1 ELSE 0 END) AS ready,
          SUM(CASE WHEN status IN ('shipped','in_transit') THEN 1 ELSE 0 END) AS shipped,
          SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) AS delivered,
          SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) AS cancelled,
          SUM(CASE WHEN status = 'returned' THEN 1 ELSE 0 END) AS returned
        FROM orders
        WHERE merchant_id = :mid
    ", ['mid' => $merchantId]) ?: [];
} catch (Throwable $e) {
    $counts = [];
}

$todayRevenue = 0.0;
$monthRevenue = 0.0;
try {
    $row = db_fetch_one($db, "
        SELECT COALESCE(SUM(total_amount), 0) AS s
        FROM orders
        WHERE merchant_id = :mid
          AND DATE(created_at) = CURDATE()
          AND status NOT IN ('new','cancelled','returned')
    ", ['mid' => $merchantId]);
    $todayRevenue = (float)($row['s'] ?? 0);
    $row = db_fetch_one($db, "
        SELECT COALESCE(SUM(total_amount), 0) AS s
        FROM orders
        WHERE merchant_id = :mid
          AND YEAR(created_at) = YEAR(CURDATE())
          AND MONTH(created_at) = MONTH(CURDATE())
          AND status NOT IN ('new','cancelled','returned')
    ", ['mid' => $merchantId]);
    $monthRevenue = (float)($row['s'] ?? 0);
} catch (Throwable $e) { /* ignore */ }

$activeProducts = 0;
$totalCustomers = 0;
try {
    $row = db_fetch_one($db, "SELECT COUNT(*) AS c FROM products WHERE is_active = 1 AND merchant_id = :mid", ['mid' => $merchantId]);
    $activeProducts = (int)($row['c'] ?? 0);
    // Unique buyers who have ordered from THIS merchant
    $row = db_fetch_one($db, "SELECT COUNT(DISTINCT buyer_id) AS c FROM orders WHERE merchant_id = :mid", ['mid' => $merchantId]);
    $totalCustomers = (int)($row['c'] ?? 0);
} catch (Throwable $e) { /* ignore */ }

$userName = function_exists('auth_user_name') ? auth_user_name() : 'Seller';

$content = function () use ($newOrders, $readyToPickOrders, $returnedOrders, $counts, $todayRevenue, $monthRevenue, $activeProducts, $totalCustomers, $userName) {
    $pendingCount = (int)($counts['pending'] ?? 0);
    $readyCount   = (int)($counts['ready'] ?? 0);
    $returnedCount = (int)($counts['returned'] ?? 0);
    $totalOrders = (int)($counts['total'] ?? 0);

    // Status distribution for the bar chart
    $statusBars = [
        ['label' => 'Paid',           'count' => $pendingCount,                'color' => 'orange'],
        ['label' => 'Packed',         'count' => (int)($counts['packed'] ?? 0),    'color' => 'amber'],
        ['label' => 'Ready to pick',  'count' => $readyCount,                   'color' => 'green'],
        ['label' => 'Shipped',        'count' => (int)($counts['shipped'] ?? 0),   'color' => 'purple'],
        ['label' => 'Delivered',      'count' => (int)($counts['delivered'] ?? 0), 'color' => 'green'],
        ['label' => 'Cancelled',      'count' => (int)($counts['cancelled'] ?? 0), 'color' => 'red'],
        ['label' => 'Returned',       'count' => $returnedCount,                'color' => 'red'],
    ];
    $maxBar = max(1, ...array_map(fn($r) => (int)$r['count'], $statusBars));

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
?>
  <div class="km-admin">
    <div class="container py-4">

      <!-- Page head -->
      <div class="km-page-head">
        <div>
          <h1>Welcome back, <?= e($userName) ?></h1>
          <div class="km-sub"><?= date('l, d F Y') ?> &middot; Here's what's happening today</div>
        </div>
        <a class="km-btn km-btn-primary" href="?p=merchant/orders">
          <i data-lucide="shopping-bag" style="width:16px;height:16px;"></i>
          View All Orders
        </a>
      </div>

      <!-- Pending Actions strip -->
      <?php if ($pendingCount + $readyCount + $returnedCount > 0): ?>
      <div class="km-pending-strip">
        <div class="km-pending-head">
          <i data-lucide="bell" style="width:16px;height:16px;"></i> Pending Actions
        </div>
        <div class="km-pending-grid">
          <a class="km-pending-mini" href="?p=merchant/orders&filter=pending">
            <div class="km-pending-icon"><i data-lucide="inbox" style="width:18px;height:18px;"></i></div>
            <div>
              <div class="km-pending-num"><?= $pendingCount ?></div>
              <div class="km-pending-text">New / Paid orders</div>
            </div>
          </a>
          <a class="km-pending-mini" href="?p=merchant/orders&filter=ready_to_pick">
            <div class="km-pending-icon"><i data-lucide="package-check" style="width:18px;height:18px;"></i></div>
            <div>
              <div class="km-pending-num"><?= $readyCount ?></div>
              <div class="km-pending-text">Ready to pick</div>
            </div>
          </a>
          <a class="km-pending-mini" href="?p=merchant/orders&filter=returned">
            <div class="km-pending-icon"><i data-lucide="undo-2" style="width:18px;height:18px;"></i></div>
            <div>
              <div class="km-pending-num"><?= $returnedCount ?></div>
              <div class="km-pending-text">Returned orders</div>
            </div>
          </a>
        </div>
      </div>
      <?php endif; ?>

      <!-- KPI grid: row 1 -->
      <div class="row g-3 mb-3">
        <div class="col-6 col-md-3">
          <a class="km-stat-link km-stat km-stat--blue" href="?p=merchant/orders">
            <div class="km-stat-num"><?= number_format($totalOrders) ?></div>
            <div class="km-stat-label">Total Orders</div>
          </a>
        </div>
        <div class="col-6 col-md-3">
          <div class="km-stat km-stat--orange">
            <div class="km-stat-num">₹<?= number_format($todayRevenue, 0) ?></div>
            <div class="km-stat-label">Today's Revenue</div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="km-stat km-stat--green">
            <div class="km-stat-num">₹<?= number_format($monthRevenue, 0) ?></div>
            <div class="km-stat-label">This Month</div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <a class="km-stat-link km-stat km-stat--purple" href="?p=merchant/products">
            <div class="km-stat-num"><?= number_format($activeProducts) ?></div>
            <div class="km-stat-label">Active Products</div>
          </a>
        </div>
      </div>

      <!-- KPI grid: row 2 -->
      <div class="row g-3 mb-3">
        <div class="col-6 col-md-3">
          <a class="km-stat-link km-stat km-stat--orange" href="?p=merchant/orders&filter=pending">
            <div class="km-stat-num"><?= $pendingCount ?></div>
            <div class="km-stat-label">Pending Orders</div>
          </a>
        </div>
        <div class="col-6 col-md-3">
          <a class="km-stat-link km-stat km-stat--green" href="?p=merchant/orders&filter=ready_to_pick">
            <div class="km-stat-num"><?= $readyCount ?></div>
            <div class="km-stat-label">Ready to Pick</div>
          </a>
        </div>
        <div class="col-6 col-md-3">
          <a class="km-stat-link km-stat km-stat--red" href="?p=merchant/orders&filter=returned">
            <div class="km-stat-num"><?= $returnedCount ?></div>
            <div class="km-stat-label">Returns</div>
          </a>
        </div>
        <div class="col-6 col-md-3">
          <div class="km-stat km-stat--blue">
            <div class="km-stat-num"><?= number_format($totalCustomers) ?></div>
            <div class="km-stat-label">Customers</div>
          </div>
        </div>
      </div>

      <!-- Two-column body: chart + recent orders -->
      <div class="row g-3 mb-3">
        <div class="col-12 col-lg-7">
          <div class="km-card km-card-pad h-100">
            <div class="d-flex align-items-center justify-content-between mb-3">
              <div class="km-card-h" style="margin:0;">Orders by Status</div>
              <a class="km-btn km-btn-ghost km-btn-sm" href="?p=merchant/reports">
                <i data-lucide="bar-chart-2" style="width:14px;height:14px;"></i> Reports
              </a>
            </div>
            <?php if ($totalOrders === 0): ?>
              <div class="text-muted small">No orders yet.</div>
            <?php else: ?>
              <div class="km-bar-chart">
                <?php foreach ($statusBars as $b):
                  $pct = $maxBar > 0 ? round((int)$b['count'] / $maxBar * 100) : 0;
                ?>
                  <div class="km-bar-row" data-color="<?= e($b['color']) ?>">
                    <div class="km-bar-label"><?= e($b['label']) ?></div>
                    <div class="km-bar-track"><div class="km-bar-fill" style="width: <?= $pct ?>%;"></div></div>
                    <div class="km-bar-num"><?= number_format((int)$b['count']) ?></div>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <div class="col-12 col-lg-5">
          <div class="km-card km-card-pad h-100">
            <div class="d-flex align-items-center justify-content-between mb-3">
              <div class="km-card-h" style="margin:0;">Recent Orders</div>
              <?php if (!empty($newOrders)): ?>
                <a class="km-btn km-btn-ghost km-btn-sm" href="?p=merchant/orders">View all</a>
              <?php endif; ?>
            </div>
            <?php if (empty($newOrders)): ?>
              <div class="text-muted small">No new orders yet.</div>
            <?php else: ?>
              <div class="d-grid gap-2">
                <?php foreach ($newOrders as $o): ?>
                  <a class="d-block p-2 rounded text-decoration-none" style="border:1px solid var(--km-line); color:inherit;" href="?p=merchant/order&id=<?= (int)$o['id'] ?>">
                    <div class="d-flex justify-content-between align-items-start gap-2">
                      <div style="min-width:0;">
                        <div class="fw-semibold small" style="font-variant-numeric: tabular-nums;"><?= e((string)$o['order_no']) ?></div>
                        <div class="small text-truncate" style="color:var(--km-muted);"><?= e((string)$o['full_name']) ?></div>
                        <div class="small" style="color:var(--km-muted); font-size:.72rem;">
                          <?= !empty($o['created_at']) ? date('d M, h:i A', strtotime((string)$o['created_at'])) : '—' ?>
                        </div>
                      </div>
                      <div class="text-end" style="flex-shrink:0;">
                        <div class="fw-semibold small">₹<?= number_format((float)$o['total_amount'], 0) ?></div>
                        <span class="km-pill <?= $statusPillClass((string)$o['status']) ?>"><?= e(ucwords(str_replace('_', ' ', (string)$o['status']))) ?></span>
                      </div>
                    </div>
                  </a>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Quick Actions tile grid -->
      <div class="km-card km-card-pad mb-3">
        <div class="km-card-h">Quick Actions</div>
        <div class="km-tile-grid">
          <a class="km-tile" href="?p=merchant/products">
            <div class="km-tile-icon"><i data-lucide="package" style="width:20px;height:20px;"></i></div>
            <span>Manage Products</span>
          </a>
          <a class="km-tile" href="?p=merchant/inventory">
            <div class="km-tile-icon"><i data-lucide="warehouse" style="width:20px;height:20px;"></i></div>
            <span>Inventory</span>
          </a>
          <a class="km-tile" href="?p=merchant/orders">
            <div class="km-tile-icon"><i data-lucide="shopping-bag" style="width:20px;height:20px;"></i></div>
            <span>All Orders</span>
          </a>
          <a class="km-tile" href="?p=merchant/reports">
            <div class="km-tile-icon"><i data-lucide="bar-chart-2" style="width:20px;height:20px;"></i></div>
            <span>Reports</span>
          </a>
          <a class="km-tile" href="?p=merchant/ratings">
            <div class="km-tile-icon"><i data-lucide="star" style="width:20px;height:20px;"></i></div>
            <span>Customer Ratings</span>
          </a>
          <a class="km-tile" href="?p=merchant/wallet">
            <div class="km-tile-icon"><i data-lucide="wallet" style="width:20px;height:20px;"></i></div>
            <span>Earnings</span>
          </a>
          <a class="km-tile" href="?p=merchant/settings">
            <div class="km-tile-icon"><i data-lucide="settings" style="width:20px;height:20px;"></i></div>
            <span>Settings</span>
          </a>
        </div>
      </div>

      <?php if (!empty($readyToPickOrders)): ?>
      <div class="km-card km-card-pad">
        <div class="d-flex align-items-center justify-content-between mb-3">
          <div class="km-card-h" style="margin:0;">Ready for Pickup</div>
          <a class="km-btn km-btn-ghost km-btn-sm" href="?p=merchant/orders&filter=ready_to_pick">View all</a>
        </div>
        <div class="d-grid gap-2">
          <?php foreach ($readyToPickOrders as $o): ?>
            <a class="d-block p-2 rounded text-decoration-none" style="border:1px solid var(--km-line); color:inherit;" href="?p=merchant/order&id=<?= (int)$o['id'] ?>">
              <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap">
                <div>
                  <div class="fw-semibold small" style="font-variant-numeric: tabular-nums;"><?= e((string)$o['order_no']) ?></div>
                  <div class="small" style="color:var(--km-muted);">
                    <?= e((string)$o['full_name']) ?>
                    &middot;
                    <?= !empty($o['created_at']) ? date('d M Y, h:i A', strtotime((string)$o['created_at'])) : '—' ?>
                  </div>
                </div>
                <div class="text-end">
                  <div class="fw-semibold small">₹<?= number_format((float)$o['total_amount'], 0) ?></div>
                  <span class="km-pill km-pill--ready">Ready</span>
                </div>
              </div>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

    </div>
  </div>

  <script>
    if (window.lucide) window.lucide.createIcons();
  </script>
<?php
};

require __DIR__ . '/../../views/layout.php';
