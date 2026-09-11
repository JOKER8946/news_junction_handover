<?php

declare(strict_types=1);

auth_require_role('buyer');

$title   = 'My Wishlist';
$buyerId = (int)auth_user_id();

if (request_method() === 'POST') {
    $action    = post_string('action');
    $productId = (int)($_POST['product_id'] ?? 0);
    if ($action === 'add' && $productId > 0) {
        wishlist_add($db, $buyerId, $productId);
        flash_set('success', 'Added to wishlist.');
    } elseif ($action === 'remove' && $productId > 0) {
        wishlist_remove($db, $buyerId, $productId);
        flash_set('success', 'Removed from wishlist.');
    }
    $back = (string)($_POST['return_to'] ?? 'buyer/wishlist');
    redirect_to($back);
}

$items = wishlist_items($db, $buyerId);

// Filters from URL — buyer can refine the visible list.
$wq        = trim((string)($_GET['q'] ?? ''));
$wShow     = (string)($_GET['show'] ?? 'all');
if (!in_array($wShow, ['all', 'in_stock', 'out_of_stock'], true)) $wShow = 'all';
$wSort     = (string)($_GET['sort'] ?? 'recent');
if (!in_array($wSort, ['recent', 'oldest', 'price_low', 'price_high', 'name'], true)) $wSort = 'recent';

$buyerInfo = db_fetch_one($db, 'SELECT full_name FROM users WHERE id = :id LIMIT 1', ['id' => $buyerId]);
$buyerName = (string)($buyerInfo['full_name'] ?? 'You');

// Apply filters/sort in PHP (single list — small enough to handle in memory).
$filtered = array_values(array_filter($items, function ($p) use ($wq, $wShow) {
    if ($wq !== '') {
        $hay = strtolower((string)($p['name'] ?? '') . ' ' . (string)($p['merchant_business'] ?? '') . ' ' . (string)($p['product_type'] ?? '') . ' ' . (string)($p['part_code'] ?? ''));
        if (strpos($hay, strtolower($wq)) === false) return false;
    }
    $inStock = ((float)($p['stock_kg'] ?? 0) > 0) && ((int)($p['is_active'] ?? 0) === 1);
    if ($wShow === 'in_stock' && !$inStock) return false;
    if ($wShow === 'out_of_stock' && $inStock) return false;
    return true;
}));

usort($filtered, function ($a, $b) use ($wSort) {
    switch ($wSort) {
        case 'oldest':     return strtotime((string)($a['added_at'] ?? '')) <=> strtotime((string)($b['added_at'] ?? ''));
        case 'price_low':  return (float)($a['price_per_kg'] ?? 0) <=> (float)($b['price_per_kg'] ?? 0);
        case 'price_high': return (float)($b['price_per_kg'] ?? 0) <=> (float)($a['price_per_kg'] ?? 0);
        case 'name':       return strcasecmp((string)($a['name'] ?? ''), (string)($b['name'] ?? ''));
        case 'recent':
        default:           return strtotime((string)($b['added_at'] ?? '')) <=> strtotime((string)($a['added_at'] ?? ''));
    }
});

$content = function () use ($items, $filtered, $wq, $wShow, $wSort, $buyerName) {
    $success = flash_get('success');
    ?>
    <style>
      .amz-wl { max-width: 1240px; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; color: #0F1111; }

      /* Top tabs ("Your Lists" / "Your Friends") */
      .amz-wl-tabs {
        display: flex; align-items: center; justify-content: space-between;
        border-bottom: 1px solid #D5D9D9;
        margin-bottom: 20px;
        gap: 16px; flex-wrap: wrap;
      }
      .amz-wl-tabs .tab-group { display: flex; gap: 26px; }
      .amz-wl-tabs a.tab {
        color: #007185; font-size: 1.1rem; text-decoration: none;
        padding: 10px 0;
        border-bottom: 3px solid transparent;
        transition: color .15s, border-color .15s;
      }
      .amz-wl-tabs a.tab:hover { color: #C7511F; text-decoration: underline; }
      .amz-wl-tabs a.tab.active {
        color: #0F1111; font-weight: 700;
        border-bottom-color: #C7511F;
        text-decoration: none;
      }
      .amz-wl-tabs .create-list {
        color: #007185; font-size: .92rem; text-decoration: none;
      }
      .amz-wl-tabs .create-list:hover { color: #C7511F; text-decoration: underline; }

      /* Two-column main layout */
      .amz-wl-grid {
        display: grid; grid-template-columns: 240px 1fr; gap: 22px;
      }

      /* Left sidebar — list of saved lists */
      .amz-wl-side h2 {
        font-size: .85rem; font-weight: 700;
        color: #565959; text-transform: uppercase; letter-spacing: .04em;
        margin: 0 0 6px;
      }
      .amz-wl-list-item {
        display: flex; align-items: center; justify-content: space-between;
        padding: 10px 12px;
        background: transparent;
        border-radius: 6px;
        text-decoration: none;
        color: #0F1111;
        margin-bottom: 4px;
      }
      .amz-wl-list-item:hover { background: #F7FAFA; color: #0F1111; text-decoration: none; }
      .amz-wl-list-item.active {
        background: #FFFFFF; border: 1px solid #D5D9D9;
        box-shadow: 0 1px 2px rgba(15,17,17,.06);
      }
      .amz-wl-list-item .name { font-weight: 600; font-size: .95rem; }
      .amz-wl-list-item .meta { font-size: .72rem; color: #565959; }
      .amz-wl-list-item .vis  { font-size: .72rem; color: #565959; }

      /* Right main panel */
      .amz-wl-main { min-width: 0; }
      .amz-wl-head {
        display: flex; align-items: center; justify-content: space-between;
        gap: 16px; flex-wrap: wrap;
        margin-bottom: 14px;
      }
      .amz-wl-title {
        font-size: 1.4rem; font-weight: 700; color: #0F1111;
        text-transform: uppercase; letter-spacing: .02em;
        display: inline-flex; align-items: center; gap: 10px;
      }
      .amz-wl-title .private-badge {
        font-size: .68rem; font-weight: 600;
        background: #F0F2F2; color: #565959;
        border: 1px solid #D5D9D9; border-radius: 4px;
        padding: 2px 8px; letter-spacing: .04em;
        text-transform: uppercase;
      }
      .amz-wl-actions { display: flex; gap: 8px; align-items: center; }
      .amz-wl-actions .a-btn {
        background: #F0F2F2; border: 1px solid #888C8C; border-radius: 100px;
        color: #0F1111; padding: 7px 14px; font-size: .85rem; font-weight: 500;
        display: inline-flex; align-items: center; gap: 5px;
        text-decoration: none; cursor: pointer;
      }
      .amz-wl-actions .a-btn:hover { background: #E3E6E6; color: #0F1111; }
      .amz-wl-actions .a-icon-btn {
        width: 32px; height: 32px; padding: 0;
        display: inline-flex; align-items: center; justify-content: center;
      }

      .amz-wl-meta-row {
        display: flex; align-items: center; gap: 10px;
        margin-bottom: 18px; font-size: .88rem; color: #565959;
      }
      .amz-wl-meta-row .invite-btn {
        background: #F0F2F2; border: 1px solid #888C8C; border-radius: 100px;
        color: #0F1111; padding: 4px 12px; font-size: .82rem; font-weight: 500;
        display: inline-flex; align-items: center; gap: 4px;
        text-decoration: none;
      }
      .amz-wl-meta-row .invite-btn:hover { background: #E3E6E6; color: #0F1111; }

      .amz-wl-filters {
        display: flex; align-items: center; gap: 12px;
        padding: 12px 0;
        margin-bottom: 4px;
        flex-wrap: wrap;
      }
      .amz-wl-filters .view-toggle {
        display: inline-flex; gap: 2px;
        border-right: 1px solid #D5D9D9; padding-right: 12px; margin-right: 4px;
      }
      .amz-wl-filters .view-toggle button {
        background: transparent; border: 0; padding: 4px 6px;
        color: #888C8C; cursor: pointer;
        border-bottom: 2px solid transparent;
      }
      .amz-wl-filters .view-toggle button.active { color: #C7511F; border-bottom-color: #C7511F; }
      .amz-wl-filters .search-box {
        flex: 1 1 240px; max-width: 280px;
        display: flex; align-items: center; gap: 6px;
        border: 1px solid #888C8C; border-radius: 6px;
        padding: 5px 10px; background: #FFFFFF;
      }
      .amz-wl-filters .search-box i { color: #565959; }
      .amz-wl-filters .search-box input {
        border: 0; outline: 0; flex: 1;
        font-size: .88rem; background: transparent;
      }
      .amz-wl-filters .select-pill {
        background: #F0F2F2; border: 1px solid #888C8C; border-radius: 6px;
        color: #0F1111; font-size: .85rem; padding: 5px 28px 5px 12px;
        -webkit-appearance: none; appearance: none;
        background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%230F1111'><path d='M4 6l4 4 4-4'/></svg>");
        background-repeat: no-repeat; background-position: right 8px center; background-size: 12px;
        cursor: pointer;
      }
      .amz-wl-filters .select-pill:focus { border-color: #007185; outline: 0; }

      /* Item rows */
      .amz-wl-list-wrap {
        background: #FFFFFF; border: 1px solid #D5D9D9; border-radius: 8px;
        overflow: hidden;
      }
      .amz-wl-row {
        display: grid; grid-template-columns: 156px 1fr auto; gap: 18px;
        padding: 18px;
        border-top: 1px solid #E7E7E7;
      }
      .amz-wl-row:first-child { border-top: 0; }
      .amz-wl-img {
        width: 156px; height: 156px;
        background: #F7F7F7; border-radius: 4px;
        display: flex; align-items: center; justify-content: center; overflow: hidden;
      }
      .amz-wl-img img { width: 100%; height: 100%; object-fit: cover; }
      .amz-wl-info { min-width: 0; }
      .amz-wl-name {
        color: #007185; font-size: 1rem; font-weight: 500; line-height: 1.3;
        text-decoration: none;
        display: -webkit-box; -webkit-line-clamp: 2; line-clamp: 2; -webkit-box-orient: vertical;
        overflow: hidden;
      }
      .amz-wl-name:hover { color: #C7511F; text-decoration: underline; }
      .amz-wl-by { color: #565959; font-size: .82rem; margin-top: 2px; }
      .amz-wl-by a { color: #007185; text-decoration: none; }
      .amz-wl-by a:hover { color: #C7511F; text-decoration: underline; }
      .amz-wl-price {
        color: #0F1111; font-size: 1.15rem; font-weight: 700;
        margin-top: 8px;
      }
      .amz-wl-price .unit { font-size: .82rem; color: #565959; font-weight: 400; margin-left: 4px; }
      .amz-wl-stock { font-size: .82rem; color: #067D62; margin-top: 2px; }
      .amz-wl-stock.out { color: #B12704; }
      .amz-wl-added {
        font-size: .82rem; color: #565959; margin-top: 10px;
      }
      .amz-wl-actions-row {
        display: flex; flex-wrap: wrap; gap: 8px; margin-top: 12px;
      }
      .amz-wl-actions-row .a-btn-link {
        background: #F0F2F2; border: 1px solid #888C8C; border-radius: 100px;
        color: #0F1111; padding: 5px 14px; font-size: .82rem;
        text-decoration: none; cursor: pointer;
        display: inline-flex; align-items: center; gap: 4px;
      }
      .amz-wl-actions-row .a-btn-link:hover { background: #E3E6E6; color: #0F1111; }
      .amz-wl-actions-row .a-icon-mini {
        width: 30px; height: 30px; padding: 0;
        display: inline-flex; align-items: center; justify-content: center;
        background: #F0F2F2; border: 1px solid #888C8C; border-radius: 50%;
        cursor: pointer;
      }
      .amz-wl-actions-row .a-icon-mini:hover { background: #E3E6E6; }

      .amz-wl-cart-col {
        display: flex; flex-direction: column; align-items: stretch;
        gap: 8px; min-width: 160px;
      }
      .amz-wl-cta {
        display: inline-flex; align-items: center; justify-content: center; gap: 6px;
        background: linear-gradient(180deg, #FFD814 0%, #F7CA00 100%);
        color: #0F1111; border: 1px solid #FCD200; border-radius: 100px;
        padding: 8px 18px; font-size: .9rem; font-weight: 500;
        text-decoration: none;
      }
      .amz-wl-cta:hover {
        background: linear-gradient(180deg, #F7CA00 0%, #F2C200 100%);
        border-color: #F2C200; color: #0F1111;
      }
      .amz-wl-cta.disabled { background: #EAEAEA; border-color: #DDD; color: #888; cursor: not-allowed; pointer-events: none; }

      .amz-wl-end {
        text-align: center; padding: 18px; color: #565959; font-size: .82rem;
        background: #F7F7F7;
      }
      .amz-wl-end::before, .amz-wl-end::after {
        content: ''; display: inline-block; width: 60px; height: 1px;
        background: #D5D9D9; vertical-align: middle; margin: 0 14px;
      }

      .amz-wl-empty {
        background: #FFFFFF; border: 1px solid #D5D9D9; border-radius: 8px;
        padding: 44px 20px; text-align: center;
      }
      .amz-wl-empty .empty-icon {
        width: 60px; height: 60px; border-radius: 50%;
        background: #F0F2F2; color: #565959;
        display: inline-flex; align-items: center; justify-content: center;
        margin-bottom: 12px;
      }
      .amz-wl-empty .empty-icon i { width: 26px; height: 26px; }
      .amz-wl-empty h3 { color: #0F1111; font-weight: 500; font-size: 1.1rem; margin: 0 0 6px; }
      .amz-wl-empty p { color: #565959; margin: 0 0 14px; }

      @media (max-width: 991px) {
        .amz-wl-grid { grid-template-columns: 1fr; }
        .amz-wl-side { order: -1; }
      }
      @media (max-width: 575px) {
        .amz-wl-row { grid-template-columns: 110px 1fr; }
        .amz-wl-img { width: 110px; height: 110px; }
        .amz-wl-cart-col { grid-column: 1 / -1; min-width: 0; }
      }
    </style>

    <div class="container py-4 amz-wl">

      <?php if ($success): ?>
        <div class="alert alert-success py-2"><?= e($success) ?></div>
      <?php endif; ?>

      <!-- Tabs -->
      <div class="amz-wl-tabs">
        <div class="tab-group">
          <a href="?p=buyer/wishlist" class="tab active">Your Lists</a>
          <a href="?p=buyer/wishlist" class="tab" aria-disabled="true" title="Coming soon">Your Friends</a>
        </div>
      </div>

      <div class="amz-wl-grid">

        <!-- Left sidebar -->
        <aside class="amz-wl-side">
          <h2>My Lists</h2>
          <a href="?p=buyer/wishlist" class="amz-wl-list-item active">
            <div>
              <div class="name">Shopping List</div>
              <div class="meta">Default List</div>
            </div>
            <div class="vis">Private</div>
          </a>
        </aside>

        <!-- Right main panel -->
        <main class="amz-wl-main">

          <div class="amz-wl-head">
            <div class="amz-wl-title">
              Shopping List
              <span class="private-badge">Private</span>
            </div>
            <div class="amz-wl-actions">
              <a class="a-btn" href="?p=products">
                <i data-lucide="plus" style="width:14px;height:14px;"></i> Add item
              </a>
              <button type="button" class="a-btn a-icon-btn" title="Share" aria-label="Share">
                <i data-lucide="share-2" style="width:14px;height:14px;"></i>
              </button>
              <button type="button" class="a-btn a-icon-btn" title="More" aria-label="More options">
                <i data-lucide="more-horizontal" style="width:14px;height:14px;"></i>
              </button>
            </div>
          </div>

          <div class="amz-wl-meta-row">
            <i data-lucide="user" style="width:18px;height:18px;color:#565959;"></i>
            <span><?= e($buyerName) ?></span>
            <a href="#" class="invite-btn">
              <i data-lucide="plus" style="width:12px;height:12px;"></i> Invite
            </a>
          </div>

          <!-- Filter row -->
          <form method="get" class="amz-wl-filters">
            <input type="hidden" name="p" value="buyer/wishlist">
            <div class="view-toggle" aria-label="View">
              <button type="button" class="active" title="List view">
                <i data-lucide="list" style="width:16px;height:16px;"></i>
              </button>
              <button type="button" title="Grid view">
                <i data-lucide="grid" style="width:16px;height:16px;"></i>
              </button>
            </div>
            <div class="search-box">
              <i data-lucide="search" style="width:14px;height:14px;"></i>
              <input type="search" name="q" value="<?= e($wq) ?>"
                     placeholder="Search this list" onchange="this.form.submit()">
            </div>
            <select name="show" class="select-pill" onchange="this.form.submit()" aria-label="Show">
              <option value="all"          <?= $wShow === 'all' ? 'selected' : '' ?>>Show: All</option>
              <option value="in_stock"     <?= $wShow === 'in_stock' ? 'selected' : '' ?>>Show: In stock</option>
              <option value="out_of_stock" <?= $wShow === 'out_of_stock' ? 'selected' : '' ?>>Show: Out of stock</option>
            </select>
            <select name="sort" class="select-pill" onchange="this.form.submit()" aria-label="Sort by">
              <option value="recent"     <?= $wSort === 'recent' ? 'selected' : '' ?>>Sort by: Most recently added</option>
              <option value="oldest"     <?= $wSort === 'oldest' ? 'selected' : '' ?>>Sort by: Oldest first</option>
              <option value="price_low"  <?= $wSort === 'price_low' ? 'selected' : '' ?>>Sort by: Price low to high</option>
              <option value="price_high" <?= $wSort === 'price_high' ? 'selected' : '' ?>>Sort by: Price high to low</option>
              <option value="name"       <?= $wSort === 'name' ? 'selected' : '' ?>>Sort by: Name (A–Z)</option>
            </select>
          </form>

          <?php if (empty($items)): ?>
            <div class="amz-wl-empty">
              <div class="empty-icon"><i data-lucide="heart"></i></div>
              <h3>Your list is empty</h3>
              <p>Items you save will appear here so you can find them later.</p>
              <a class="amz-wl-cta" href="?p=products" style="display:inline-flex;">
                <i data-lucide="search" style="width:14px;height:14px;"></i> Browse products
              </a>
            </div>
          <?php elseif (empty($filtered)): ?>
            <div class="amz-wl-empty">
              <div class="empty-icon"><i data-lucide="search-x"></i></div>
              <h3>No items match your filters</h3>
              <p>Try a different search term or change the filter.</p>
              <a class="amz-wl-cta" href="?p=buyer/wishlist" style="display:inline-flex;">Clear filters</a>
            </div>
          <?php else: ?>
            <div class="amz-wl-list-wrap">
              <?php foreach ($filtered as $p):
                $inStock = ((float)($p['stock_kg'] ?? 0) > 0) && ((int)($p['is_active'] ?? 0) === 1);
                $unitLabel = product_unit_label($p['unit'] ?? 'kg');
              ?>
                <div class="amz-wl-row">
                  <div class="amz-wl-img">
                    <?php if (!empty($p['image_path'])): ?>
                      <img src="<?= e((string)$p['image_path']) ?>" alt="<?= e((string)$p['name']) ?>">
                    <?php else: ?>
                      <i data-lucide="image" style="width:32px;height:32px;color:#BCBCBC;"></i>
                    <?php endif; ?>
                  </div>

                  <div class="amz-wl-info">
                    <a class="amz-wl-name" href="?p=products"><?= e((string)$p['name']) ?></a>
                    <?php if (!empty($p['merchant_business'])): ?>
                      <div class="amz-wl-by">by <a href="#"><?= e((string)$p['merchant_business']) ?></a></div>
                    <?php endif; ?>
                    <div class="amz-wl-price">
                      ₹<?= number_format((float)$p['price_per_kg'], 2) ?>
                      <span class="unit">/ <?= e($unitLabel) ?></span>
                    </div>
                    <div class="amz-wl-stock <?= $inStock ? '' : 'out' ?>">
                      <?= $inStock ? 'In stock' : 'Currently unavailable' ?>
                    </div>
                    <?php if (!empty($p['added_at'])): ?>
                      <div class="amz-wl-added">Item added <?= e(date('d M Y', strtotime((string)$p['added_at']))) ?></div>
                    <?php endif; ?>

                    <div class="amz-wl-actions-row">
                      <a class="a-btn-link" href="?p=products" title="See more options">See all buying options</a>
                      <button type="button" class="a-btn-link" title="Add a note (coming soon)" disabled>Add a note</button>
                      <button type="button" class="a-btn-link" title="Move to another list (coming soon)" disabled>Move</button>
                      <button type="button" class="a-icon-mini" title="Share">
                        <i data-lucide="share-2" style="width:14px;height:14px;"></i>
                      </button>
                      <form method="post" onsubmit="return confirm('Remove this item from your list?');" style="display:inline;">
                        <input type="hidden" name="action" value="remove">
                        <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
                        <button type="submit" class="a-icon-mini" title="Remove from list">
                          <i data-lucide="trash-2" style="width:14px;height:14px;"></i>
                        </button>
                      </form>
                    </div>
                  </div>

                  <div class="amz-wl-cart-col">
                    <?php if ($inStock): ?>
                      <a class="amz-wl-cta" href="?p=cart/add&id=<?= (int)$p['id'] ?>&qty=1">
                        <i data-lucide="shopping-cart" style="width:14px;height:14px;"></i> Add to Cart
                      </a>
                    <?php else: ?>
                      <button type="button" class="amz-wl-cta disabled" disabled>
                        <i data-lucide="ban" style="width:14px;height:14px;"></i> Unavailable
                      </button>
                    <?php endif; ?>
                  </div>
                </div>
              <?php endforeach; ?>
              <div class="amz-wl-end">End of list</div>
            </div>
          <?php endif; ?>
        </main>
      </div>
    </div>

    <script>if (window.lucide) window.lucide.createIcons();</script>
    <?php
};

require __DIR__ . '/../../views/layout.php';
