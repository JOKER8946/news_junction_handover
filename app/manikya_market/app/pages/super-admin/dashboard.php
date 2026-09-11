<?php

declare(strict_types=1);

auth_require_role('super_admin');

$title = 'Super Admin Dashboard';

$counts = [
    'merchants_pending'  => (int)(db_fetch_one($db, "SELECT COUNT(*) AS c FROM merchant_profile WHERE status='pending'")['c'] ?? 0),
    'merchants_approved' => (int)(db_fetch_one($db, "SELECT COUNT(*) AS c FROM merchant_profile WHERE status='approved'")['c'] ?? 0),
    'merchants_disabled' => (int)(db_fetch_one($db, "SELECT COUNT(*) AS c FROM merchant_profile WHERE status='disabled'")['c'] ?? 0),
    'buyers'             => (int)(db_fetch_one($db, "SELECT COUNT(*) AS c FROM users WHERE role='buyer'")['c'] ?? 0),
    'products'           => (int)(db_fetch_one($db, "SELECT COUNT(*) AS c FROM products WHERE is_active=1")['c'] ?? 0),
    'categories'         => (int)(db_fetch_one($db, "SELECT COUNT(*) AS c FROM categories WHERE is_active=1")['c'] ?? 0),
    'orders'             => (int)(db_fetch_one($db, "SELECT COUNT(*) AS c FROM orders")['c'] ?? 0),
    'revenue'            => (float)(db_fetch_one($db, "SELECT COALESCE(SUM(total_amount),0) AS s FROM orders WHERE status NOT IN ('cancelled','refunded')")['s'] ?? 0),
];

// Failed notifications in the last 24h — surface so we don't quietly miss
// buyer/merchant order emails. Best-effort: table may not exist on fresh installs.
$notifFailed24h = 0; $notifFailedSamples = [];
try {
    $notifFailed24h = (int)(db_fetch_one($db,
        "SELECT COUNT(*) AS c FROM notification_logs
          WHERE status = 'failed'
            AND created_at > (NOW() - INTERVAL 24 HOUR)"
    )['c'] ?? 0);
    if ($notifFailed24h > 0) {
        $notifFailedSamples = db_fetch_all($db,
            "SELECT to_address, error_message, created_at FROM notification_logs
              WHERE status = 'failed'
                AND created_at > (NOW() - INTERVAL 24 HOUR)
              ORDER BY created_at DESC LIMIT 5") ?: [];
    }
} catch (Throwable $t) { /* ignore */ }

$content = function () use ($counts, $notifFailed24h, $notifFailedSamples) {
    require __DIR__ . '/../../views/partials/super-admin-nav.php';
    ?>
    <div class="container">
      <h1 class="h4 mb-4">Platform overview</h1>

      <?php if ($counts['merchants_pending'] > 0): ?>
        <div class="alert alert-warning d-flex align-items-center gap-2">
          <i data-lucide="alert-triangle"></i>
          <div>
            <strong><?= $counts['merchants_pending'] ?></strong> merchant application(s) awaiting approval.
            <a href="?p=super-admin/merchants&status=pending" class="alert-link">Review now</a>
          </div>
        </div>
      <?php endif; ?>

      <?php if ($notifFailed24h > 0): ?>
        <div class="alert alert-danger d-flex align-items-start gap-2">
          <i data-lucide="mail-x" style="flex-shrink:0; margin-top:3px;"></i>
          <div class="flex-grow-1">
            <strong><?= (int)$notifFailed24h ?></strong> email notification(s) failed to send in the last 24 hours.
            <details class="mt-1">
              <summary class="small text-muted" style="cursor:pointer;">Show recent failures</summary>
              <ul class="small mb-0 mt-2">
                <?php foreach ($notifFailedSamples as $s): ?>
                  <li>
                    <code><?= e((string)$s['to_address']) ?></code> &mdash;
                    <?= e(substr((string)$s['error_message'], 0, 120)) ?>
                    <span class="text-muted">(<?= e((string)$s['created_at']) ?>)</span>
                  </li>
                <?php endforeach; ?>
              </ul>
            </details>
            <div class="small text-muted mt-1">Likely cause: SMTP credentials in Platform Settings are wrong, or the provider rejected the message.</div>
          </div>
        </div>
      <?php endif; ?>

      <div class="row g-3">
        <?php
        $cards = [
            ['Pending merchants',  $counts['merchants_pending'],  'clock',      'warning'],
            ['Approved merchants', $counts['merchants_approved'], 'check',      'success'],
            ['Disabled merchants', $counts['merchants_disabled'], 'ban',        'secondary'],
            ['Buyers',             $counts['buyers'],             'users',      'primary'],
            ['Active categories',  $counts['categories'],         'tag',        'info'],
            ['Active products',    $counts['products'],           'package',    'primary'],
            ['Total orders',       $counts['orders'],             'shopping-bag','dark'],
            ['Gross revenue (₹)',  number_format($counts['revenue'], 2),  'banknote', 'success'],
        ];
        foreach ($cards as [$label, $value, $icon, $color]):
        ?>
          <div class="col-6 col-md-4 col-lg-3">
            <div class="bg-white border rounded-3 p-3 h-100">
              <div class="d-flex align-items-center gap-2 text-muted small mb-1">
                <i data-lucide="<?= e($icon) ?>" style="width:16px;height:16px;"></i>
                <?= e($label) ?>
              </div>
              <div class="h4 mb-0 text-<?= e($color) ?>"><?= is_int($value) ? $value : e((string)$value) ?></div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php
};

require __DIR__ . '/../../views/layout.php';
