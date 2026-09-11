<?php

$role = auth_role();
$cartCount = cart_count();

$navBase = '';
if (isset($GLOBALS['config']) && is_array($GLOBALS['config']) && function_exists('app_base_path')) {
    $navBase = app_base_path($GLOBALS['config']);
}

// Platform branding.
$brandName = 'Manikya Market';
$brandLogo = $navBase . '/assets/img/footer-logo2.png';

$currentPage = (string)($_GET['p'] ?? '');
$isSignupPage = $currentPage === 'buyer/signup';

// Buyer-only personalization for the Amazon-style "Deliver to / Hello, name".
$navBuyerFirstName = '';
$navBuyerCity      = '';
$navBuyerPincode   = '';
if ($role === 'buyer' && isset($db)) {
    try {
        $bid = (int)auth_user_id();
        $row = db_fetch_one($db, 'SELECT full_name FROM users WHERE id = :id LIMIT 1', ['id' => $bid]);
        if ($row && !empty($row['full_name'])) {
            $parts = preg_split('/\s+/', trim((string)$row['full_name']));
            $navBuyerFirstName = (string)($parts[0] ?? '');
        }
        $addr = db_fetch_one($db,
            'SELECT city, pincode FROM buyer_addresses
             WHERE buyer_id = :b ORDER BY is_default DESC, created_at DESC LIMIT 1',
            ['b' => $bid]
        );
        if ($addr) {
            $navBuyerCity    = (string)($addr['city'] ?? '');
            $navBuyerPincode = (string)($addr['pincode'] ?? '');
        }
    } catch (Throwable $t) { /* ignore */ }
}

// Decide which nav style to render. The buyer/guest navbar uses the new
// Amazon-style three-section dark header; merchant/admin/logistics keep
// the simpler light navbar since they navigate via their own sidebar.
$useAmazonNav = ($role === 'buyer' || (!$role && !in_array($currentPage, ['merchant/login','merchant/signup','logistics/login','super-admin/login'], true)));
?>

<?php if ($useAmazonNav): ?>
<nav class="amz-nav sticky-top">
  <!-- ── Top row: logo · deliver-to · search · account · orders · cart ── -->
  <div class="amz-nav-top">
    <a class="amz-nav-brand" href="?p=home" aria-label="<?= e($brandName) ?>">
      <img src="<?= e($brandLogo) ?>" alt="<?= e($brandName) ?>">
    </a>

    <a class="amz-nav-deliver" href="<?= $role === 'buyer' ? '?p=buyer/addresses' : '?p=buyer/login' ?>">
      <i data-lucide="map-pin" class="mm-icon" style="width:18px;height:18px;"></i>
      <span class="lbl">
        <span class="line1">Deliver to <?= e($navBuyerFirstName !== '' ? $navBuyerFirstName : 'guest') ?></span>
        <span class="line2">
          <?php if ($navBuyerCity !== '' || $navBuyerPincode !== ''): ?>
            <?= e(trim($navBuyerCity . ' ' . $navBuyerPincode)) ?>
          <?php else: ?>
            Update location
          <?php endif; ?>
        </span>
      </span>
    </a>

    <div class="amz-nav-spacer"></div>

    <a class="amz-nav-back-nj" href="/stream.php"
       style="display:inline-flex; align-items:center; gap:6px;
              margin-right:10px; padding:6px 12px;
              background:#f39200; color:#fff; border-radius:4px;
              text-decoration:none; font-size:13px; font-weight:600;
              white-space:nowrap;">
      <i data-lucide="arrow-left" style="width:14px;height:14px;"></i>
      Back to News Junction
    </a>

    <?php if ($role === 'buyer'): ?>
      <a class="amz-nav-stack" href="?p=buyer/addresses">
        <span class="line1">Hello, <?= e($navBuyerFirstName !== '' ? $navBuyerFirstName : 'you') ?></span>
        <span class="line2">Account</span>
      </a>
      <a class="amz-nav-stack" href="?p=buyer/orders">
        <span class="line1">Your</span>
        <span class="line2">Orders</span>
      </a>
    <?php else: ?>
      <a class="amz-nav-stack" href="?p=buyer/login">
        <span class="line1">Hello, sign in</span>
        <span class="line2">Account</span>
      </a>
      <a class="amz-nav-stack" href="?p=buyer/login">
        <span class="line1">Your</span>
        <span class="line2">Orders</span>
      </a>
    <?php endif; ?>

    <a class="amz-nav-cart" href="?p=cart" aria-label="Cart">
      <span class="cart-count"><?= (int)$cartCount ?></span>
      <i data-lucide="shopping-cart" style="width:30px;height:30px;"></i>
      <span class="cart-lbl">Cart</span>
    </a>
  </div>

  <!-- ── Bottom row: hamburger + quick links ── -->
  <div class="amz-nav-bot">
    <a class="amz-nav-all" href="?p=products">
      <i data-lucide="menu" style="width:16px;height:16px;"></i>
      <span>All</span>
    </a>
    <?php if ($role === 'buyer'): ?>
      <a class="amz-nav-q" href="?p=buyer/wishlist">
        <i data-lucide="heart" style="width:14px;height:14px;"></i> Wishlist
      </a>
      <a class="amz-nav-q" href="?p=buyer/orders">My Orders</a>
      <a class="amz-nav-q" href="?p=buyer/referral">Refer &amp; Earn</a>
      <a class="amz-nav-q" href="?p=buyer/wallet">Wallet</a>
      <a class="amz-nav-q" href="?p=buyer/addresses">Addresses</a>
      <a class="amz-nav-q" href="?p=buyer/tickets">Support</a>
      <a class="amz-nav-q amz-nav-q-right" href="?p=logout">Logout</a>
    <?php else: ?>
      <a class="amz-nav-q" href="?p=products">Browse</a>
      <a class="amz-nav-q amz-nav-q-right" href="?p=buyer/login">Buyer Login</a>
      <a class="amz-nav-q" href="?p=merchant/login">Seller Login</a>
    <?php endif; ?>
  </div>
</nav>
<?php else: ?>
<!-- Compact navbar for merchant / super_admin / logistics (they navigate via sidebar). -->
<nav class="navbar navbar-expand-lg bg-white border-bottom sticky-top">
  <div class="container-fluid px-3 px-lg-4">
    <a class="navbar-brand fw-semibold d-flex align-items-center gap-2" href="<?php
      if ($role === 'logistics') {
        echo '?p=logistics/dashboard';
      } elseif ($role === 'merchant') {
        echo '?p=merchant/dashboard';
      } elseif ($currentPage === 'logistics/login') {
        echo '?p=logistics/login';
      } else {
        echo '?p=home';
      }
    ?>">
      <img src="<?= e($brandLogo) ?>" alt="<?= e($brandName) ?>" style="height:70px;width:auto;object-fit:contain;">
      <span><?= e($brandName) ?></span>
    </a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain" aria-controls="navMain" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navMain">
      <ul class="navbar-nav ms-auto">
        <?php if ($role === 'merchant'): ?>
          <li class="nav-item d-flex align-items-center">
            <button id="sidebarToggle" class="btn btn-sm btn-outline-secondary me-2">☰</button>
          </li>
          <li class="nav-item"><a class="nav-link d-inline-flex align-items-center gap-1" href="?p=logout"><i data-lucide="log-out" class="mm-icon"></i>Logout</a></li>
        <?php elseif ($role === 'super_admin'): ?>
          <li class="nav-item"><a class="nav-link d-inline-flex align-items-center gap-1" href="?p=super-admin/dashboard"><i data-lucide="shield-check" class="mm-icon"></i>Admin</a></li>
          <li class="nav-item"><a class="nav-link d-inline-flex align-items-center gap-1" href="?p=logout"><i data-lucide="log-out" class="mm-icon"></i>Sign out</a></li>
        <?php elseif ($role === 'logistics'): ?>
          <li class="nav-item"><a class="nav-link d-inline-flex align-items-center gap-1" href="?p=logistics/dashboard"><i data-lucide="truck" class="mm-icon"></i>Dashboard</a></li>
          <li class="nav-item"><a class="nav-link d-inline-flex align-items-center gap-1" href="?p=logistics/reports"><i data-lucide="file-text" class="mm-icon"></i>Reports</a></li>
          <li class="nav-item"><a class="nav-link d-inline-flex align-items-center gap-1" href="?p=logistics/completed-orders"><i data-lucide="check" class="mm-icon"></i>Completed</a></li>
          <li class="nav-item"><a class="nav-link d-inline-flex align-items-center gap-1" href="?p=logout"><i data-lucide="log-out" class="mm-icon"></i>Logout</a></li>
        <?php else: ?>
          <li class="nav-item"><a class="nav-link" href="?p=buyer/login">Buyer Login</a></li>
          <li class="nav-item"><a class="nav-link" href="?p=merchant/login">Seller Login</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>
<?php endif; ?>
