<?php

// Render the super-admin sidebar exactly once per request. Layout.php calls
// this at the top of the page, and individual pages also call it from inside
// their $content closure (legacy) — the global flag makes the 2nd call a no-op.
// (PHP `static` at file scope is a no-op outside a function, so use $GLOBALS.)
if (!empty($GLOBALS['_sa_nav_rendered'])) {
    return;
}
$GLOBALS['_sa_nav_rendered'] = true;

$current = (string)($_GET['p'] ?? '');
$linkClass = function (string $route) use ($current): string {
    return 'sa-nav-link' . ($current === $route ? ' active' : '');
};

$navSections = [
    ['Overview', [
        ['super-admin/dashboard',        'Dashboard',   'layout-dashboard'],
    ]],
    ['Marketplace', [
        ['super-admin/merchants',        'Sellers',     'store'],
        ['super-admin/buyers',           'Buyers',      'users'],
        ['super-admin/categories',       'Categories',  'tag'],
        ['super-admin/products',         'Products',    'package'],
        ['super-admin/coupons',          'Discounts',   'ticket-percent'],
    ]],
    ['Operations', [
        ['super-admin/orders',           'Orders',      'shopping-bag'],
        ['super-admin/inventory',        'Inventory',   'boxes'],
        ['super-admin/shipments',        'Shipments',   'truck'],
        ['super-admin/tickets',          'Tickets',     'life-buoy'],
        ['merchant/logistics-vendors',   'Logistics',   'package'],
    ]],
    ['Finance', [
        ['super-admin/platform-ledger',  'Ledger',      'book-open'],
        ['super-admin/merchant-wallets', 'Earnings',    'wallet'],
        ['super-admin/commissions',      'Commissions', 'percent'],
        ['super-admin/payouts',          'Payouts',     'banknote'],
    ]],
    ['Growth', [
        ['merchant/promotions',          'Promotions',  'megaphone'],
        ['merchant/referrals',           'Referrals',   'gift'],
    ]],
    ['Settings', [
        ['super-admin/platform-settings','Platform',    'settings'],
    ]],
];
?>
<style>
  /* ─── Super-admin theme (scoped to body[data-sa-admin]) ─────────────── */
  body[data-sa-admin="1"].bg-light { background: #F4F5F9 !important; }

  body[data-sa-admin="1"] .sa-sidebar {
    position: fixed;
    top: 0; left: 0; bottom: 0;
    width: 240px;
    background: linear-gradient(180deg, #1E2A78 0%, #2D2F8F 60%, #4338CA 100%);
    color: #fff;
    padding: 0;
    z-index: 1000;
    overflow-y: auto;
    box-shadow: 2px 0 20px rgba(67, 56, 202, 0.15);
  }
  body[data-sa-admin="1"] .sa-brand {
    display: flex; align-items: center; gap: 10px;
    padding: 20px 18px 16px;
    border-bottom: 1px solid rgba(255,255,255,.10);
  }
  body[data-sa-admin="1"] .sa-brand-name { font-weight: 700; letter-spacing: .02em; font-size: 1rem; line-height: 1.2; }
  body[data-sa-admin="1"] .sa-brand-sub { font-size: .68rem; font-weight: 500; opacity: .72; text-transform: uppercase; letter-spacing: .14em; }

  body[data-sa-admin="1"] .sa-section {
    padding: 14px 18px 4px;
    color: rgba(255,255,255,.45);
    font-size: .68rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: .12em;
  }

  body[data-sa-admin="1"] .sa-nav { list-style: none; padding: 0 10px; margin: 0; }
  body[data-sa-admin="1"] .sa-nav li { margin: 2px 0; }
  body[data-sa-admin="1"] .sa-nav-link {
    display: flex; align-items: center; gap: 10px;
    padding: 9px 12px;
    color: rgba(255,255,255,.82);
    text-decoration: none;
    border-radius: 8px;
    font-size: .9rem; font-weight: 500;
    transition: background .15s, color .15s;
  }
  body[data-sa-admin="1"] .sa-nav-link i { width: 16px; height: 16px; flex-shrink: 0; }
  body[data-sa-admin="1"] .sa-nav-link:hover { background: rgba(255,255,255,.10); color: #fff; }
  body[data-sa-admin="1"] .sa-nav-link.active { background: rgba(255,255,255,.18); color: #fff; font-weight: 700; }

  body[data-sa-admin="1"] .sa-foot {
    margin-top: 16px;
    padding: 14px 18px;
    border-top: 1px solid rgba(255,255,255,.10);
    font-size: .82rem; color: rgba(255,255,255,.75);
  }
  body[data-sa-admin="1"] .sa-foot a { color: #fff; font-weight: 600; text-decoration: none; }
  body[data-sa-admin="1"] .sa-foot a:hover { text-decoration: underline; }

  body[data-sa-admin="1"] main.sa-main { margin-left: 240px; padding: 28px 24px; min-height: 100vh; }

  body[data-sa-admin="1"] .container { max-width: 1240px; }

  /* Cards + KPIs */
  body[data-sa-admin="1"] .bg-white { border-color: #E5E7EB !important; border-radius: 12px !important; }
  body[data-sa-admin="1"] h1.h4 { font-weight: 700; color: #111827; letter-spacing: -.01em; }
  body[data-sa-admin="1"] h1.h5 { font-weight: 700; color: #111827; }
  body[data-sa-admin="1"] h2.h6 { letter-spacing: .04em; color: #6B7280; }
  body[data-sa-admin="1"] .text-muted { color: #6B7280 !important; }

  body[data-sa-admin="1"] .bg-white.border.rounded-3.p-3.text-center {
    box-shadow: 0 1px 2px rgba(17,24,39,.04);
    transition: transform .15s, box-shadow .15s;
  }
  body[data-sa-admin="1"] .bg-white.border.rounded-3.p-3.text-center:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 16px rgba(67, 56, 202, .08);
  }

  /* Tables */
  body[data-sa-admin="1"] table.table { margin: 0; }
  body[data-sa-admin="1"] table.table thead th {
    text-transform: uppercase; font-size: .72rem; letter-spacing: .06em;
    color: #6B7280; font-weight: 600;
    background: #F9FAFB !important; border-bottom: 1px solid #E5E7EB;
  }
  body[data-sa-admin="1"] table.table tbody tr { transition: background .12s; }
  body[data-sa-admin="1"] table.table tbody tr:hover { background: #F9FAFB; }
  body[data-sa-admin="1"] table.table tbody td { vertical-align: middle; }

  body[data-sa-admin="1"] .btn-group .btn-outline-secondary {
    border-color: #E5E7EB; color: #4B5563; background: #fff;
  }
  body[data-sa-admin="1"] .btn-group .btn-outline-secondary.active {
    background: #1E2A78; color: #fff; border-color: #1E2A78;
  }
  body[data-sa-admin="1"] .btn-group .btn-outline-secondary:hover {
    background: #F4F5F9; color: #1E2A78;
  }

  body[data-sa-admin="1"] .btn-mm {
    background: #1E2A78; color: #fff; border: 0;
    padding: 9px 18px; border-radius: 8px; font-weight: 600;
  }
  body[data-sa-admin="1"] .btn-mm:hover { background: #4338CA; color: #fff; }
  body[data-sa-admin="1"] .form-control:focus,
  body[data-sa-admin="1"] .form-select:focus {
    border-color: #6366F1; box-shadow: 0 0 0 .2rem rgba(99,102,241,.15);
  }

  body[data-sa-admin="1"] .badge.bg-warning { background: #F59E0B !important; color: #fff; }
  body[data-sa-admin="1"] .badge.bg-success { background: #10B981 !important; }
  body[data-sa-admin="1"] .badge.bg-info    { background: #0EA5E9 !important; color: #fff; }
  body[data-sa-admin="1"] .badge.bg-primary { background: #4338CA !important; }
  body[data-sa-admin="1"] .badge.bg-danger  { background: #DC2626 !important; }

  /* Hide the public top navbar inside super-admin (the sidebar replaces it). */
  body[data-sa-admin="1"] > nav.navbar { display: none; }

  @media (max-width: 768px) {
    body[data-sa-admin="1"] .sa-sidebar { width: 64px; }
    body[data-sa-admin="1"] main.sa-main { margin-left: 64px; padding: 20px 14px; }
    body[data-sa-admin="1"] .sa-brand-name,
    body[data-sa-admin="1"] .sa-brand-sub,
    body[data-sa-admin="1"] .sa-section,
    body[data-sa-admin="1"] .sa-nav-link span,
    body[data-sa-admin="1"] .sa-foot { display: none; }
    body[data-sa-admin="1"] .sa-nav-link { justify-content: center; padding: 10px 6px; }
  }
</style>

<aside class="sa-sidebar">
  <div class="sa-brand">
    <i data-lucide="shield-check" style="width: 22px; height: 22px;"></i>
    <div>
      <div class="sa-brand-name">Manikya Market</div>
      <div class="sa-brand-sub">Super Admin</div>
    </div>
  </div>

  <?php foreach ($navSections as [$section, $links]): ?>
    <div class="sa-section"><?= e($section) ?></div>
    <ul class="sa-nav">
      <?php foreach ($links as [$route, $label, $icon]): ?>
        <li>
          <a class="<?= $linkClass($route) ?>" href="?p=<?= e($route) ?>">
            <i data-lucide="<?= e($icon) ?>"></i>
            <span><?= e($label) ?></span>
          </a>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endforeach; ?>

  <div class="sa-foot">
    <div><?= e((string)($_SESSION['role'] ?? 'super_admin')) ?></div>
    <a href="?p=logout"><i data-lucide="log-out" style="width:13px;height:13px;"></i> Sign out</a>
  </div>
</aside>
