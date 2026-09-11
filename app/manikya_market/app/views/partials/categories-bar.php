<?php
$role = function_exists('auth_role') ? auth_role() : null;
$currentPage = (string)($_GET['p'] ?? '');

if (in_array($role, ['merchant', 'logistics', 'super_admin'], true)) {
    return;
}
if (str_starts_with($currentPage, 'buyer/login')
    || str_starts_with($currentPage, 'buyer/signup')
    || str_starts_with($currentPage, 'merchant/login')
    || str_starts_with($currentPage, 'merchant/signup')
    || str_starts_with($currentPage, 'logistics/login')
    || str_starts_with($currentPage, 'super-admin/')) {
    return;
}

$bar_categories = [];
if (isset($db)) {
    try {
        $bar_categories = db_fetch_all($db,
            "SELECT id, name, slug FROM categories WHERE is_active = 1 ORDER BY sort_order ASC, id ASC"
        ) ?: [];
    } catch (Throwable $t) {
        $bar_categories = [];
    }
}

if (empty($bar_categories)) {
    return;
}

$activeCat = (string)($_GET['category'] ?? '');
?>
<style>
  /* Categories bar is pinned just under the fixed top navbar so users can
     hop between categories from any scroll position. Using position:fixed
     instead of sticky for guaranteed visibility regardless of ancestor
     overflow rules. A matching spacer pushes page content down so it
     doesn't hide behind the bar. */
  .mk-cats-bar {
    position: fixed;
    top: 110px;
    left: 0;
    right: 0;
    background: #FFFFFF;
    border-bottom: 1px solid #E7E7E7;
    box-shadow: 0 2px 4px rgba(15,17,17,.04);
    z-index: 1200;
    overflow-x: auto;
  }
  .mk-cats-spacer { height: 50px; flex-shrink: 0; }
  .mk-cats-inner {
    max-width: 1500px;
    margin: 0 auto;
    padding: 12px 18px;
    display: flex; align-items: center; gap: 22px;
    white-space: nowrap;
  }
  .mk-cats-inner a {
    color: #565959;
    text-decoration: none;
    font-size: .98rem;
    font-weight: 500;
    padding: 4px 2px;
    border-bottom: 2px solid transparent;
    transition: color .15s, border-color .15s;
    display: inline-flex; align-items: center; gap: 6px;
  }
  .mk-cats-inner a:hover { color: #C7511F; }
  .mk-cats-inner a.active {
    color: #0F1111; font-weight: 700;
    border-bottom-color: #FF9900;
  }
  @media (max-width: 767px) {
    .mk-cats-bar { top: 92px; }
    .mk-cats-spacer { height: 42px; }
    .mk-cats-inner { gap: 14px; padding: 8px 12px; }
    .mk-cats-inner a { font-size: .9rem; }
  }
</style>
<div class="mk-cats-bar">
  <div class="mk-cats-inner">
    <a href="?p=home" class="<?= $activeCat === '' && $currentPage === 'home' ? 'active' : '' ?>">
      <i data-lucide="grid" style="width:16px;height:16px;"></i>
      All
    </a>
    <?php foreach ($bar_categories as $c): ?>
      <a href="?p=products&amp;category=<?= e((string)$c['slug']) ?>"
         class="<?= $activeCat === (string)$c['slug'] ? 'active' : '' ?>">
        <?= e((string)$c['name']) ?>
      </a>
    <?php endforeach; ?>
  </div>
</div>
<div class="mk-cats-spacer" aria-hidden="true"></div>
