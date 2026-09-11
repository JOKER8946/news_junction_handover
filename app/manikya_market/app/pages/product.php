<?php

declare(strict_types=1);

// ─────────────────────────────────────────────────────────────────────────────
//  Buyer-facing product detail page (Amazon-style).
//  URL: ?p=product&id=<n>
//  Shows: large image, price + discount, MRP strike, description, seller,
//  quantity picker (packet-aware), and Add-to-cart.
// ─────────────────────────────────────────────────────────────────────────────

$productId = (int)($_GET['id'] ?? 0);
if ($productId <= 0) {
    flash_set('error', 'Product not found.');
    redirect_to('products');
}

$p = db_fetch_one($db,
    "SELECT p.*,
            COALESCE(i.qty_kg, p.stock_kg) AS inventory_qty,
            c.name  AS category_name,
            c.slug  AS category_slug,
            c.discount_pct       AS cat_discount_pct,
            c.discount_label     AS cat_discount_label,
            c.discount_ends_at   AS cat_discount_ends_at,
            u.full_name        AS merchant_name,
            mp.business_name   AS merchant_business
     FROM products p
     LEFT JOIN inventory i ON i.product_id = p.id
     LEFT JOIN categories c ON c.id = p.category_id
     LEFT JOIN users u ON u.id = p.merchant_id
     LEFT JOIN merchant_profile mp ON mp.merchant_user_id = p.merchant_id
     WHERE p.id = :id AND p.is_active = 1
     LIMIT 1",
    ['id' => $productId]
);

if (!$p) {
    flash_set('error', 'Product not found or no longer available.');
    redirect_to('products');
}

$title = (string)$p['name'];

// Live discount — auto coupon (product-scoped) wins, else category discount.
$autoByProduct = function_exists('coupon_auto_apply_for_products')
    ? coupon_auto_apply_for_products($db, [$productId])
    : [];
$disc = product_auto_discount($p, $autoByProduct[$productId] ?? null);

$origPrice     = (float)$p['price_per_kg'];
$finalPrice    = (float)$disc['final_unit'];
$discPctInt    = (int)round((float)$disc['pct']);
$saveAmount    = round($origPrice - $finalPrice, 2);

$soldAs   = (string)($p['sold_as'] ?? 'bulk');
$packG    = (float)($p['pack_size_grams'] ?? 0);
if ($soldAs === 'packet' && $packG > 0) {
    $packFmt   = rtrim(rtrim(number_format($packG, 2, '.', ''), '0'), '.');
    $unitLabel = $packFmt . 'g pack';
    $qtyLabel  = 'Packets';
    $qtyStep   = '1';
    $qtyMin    = '1';
    $qtyDefault = '1';
} else {
    $unitLabel = product_unit_label($p['unit'] ?? 'kg');
    $qtyLabel  = ucfirst($unitLabel);
    $qtyStep   = '0.25';
    $qtyMin    = '0.25';
    $qtyDefault = '1';
}

$inventoryQty = (float)($p['inventory_qty'] ?? $p['stock_kg'] ?? 0);
$isOutOfStock = $inventoryQty <= 0;

$sellerName = (string)($p['merchant_business'] ?? $p['merchant_name'] ?? 'Seller');

// Media gallery: primary image first, then any extra gallery images, plus an
// optional short intro video. Shown only here on the product detail page.
$galleryImages = function_exists('product_gallery_list') ? product_gallery_list($p['gallery_images'] ?? null) : [];
$videoPath = trim((string)($p['video_path'] ?? ''));
$mainImage = (string)($p['image_path'] ?? '');
$allImages = [];
if ($mainImage !== '') $allImages[] = $mainImage;
foreach ($galleryImages as $gi) { if ($gi !== '' && $gi !== $mainImage) $allImages[] = $gi; }

$content = function () use ($p, $disc, $origPrice, $finalPrice, $discPctInt, $saveAmount,
                            $unitLabel, $qtyLabel, $qtyStep, $qtyMin, $qtyDefault,
                            $isOutOfStock, $inventoryQty, $sellerName, $soldAs, $packG,
                            $allImages, $videoPath) {
?>
<style>
  .pd-page {
    --pd-ink: #0F1111;
    --pd-sub: #565959;
    --pd-muted: #767676;
    --pd-link: #007185;
    --pd-link-hover: #C7511F;
    --pd-danger: #B12704;
    --pd-success: #007600;
    --pd-yellow: #FFD814;
    --pd-yellow-hover: #F7CA00;
    --pd-orange: #FFA41C;
    --pd-orange-hover: #FA8900;
    --pd-border: #D5D9D9;
    --pd-border-soft: #E7E7E7;
    color: var(--pd-ink);
    background: #FFFFFF;
  }
  body.bg-light:has(.pd-page) { background: #FFFFFF !important; }

  .pd-page .container { max-width: 1400px; }

  .pd-breadcrumb {
    font-size: .82rem; color: var(--pd-muted);
    padding: 10px 0; border-bottom: 1px solid var(--pd-border-soft);
    margin-bottom: 20px;
  }
  .pd-breadcrumb a { color: var(--pd-link); text-decoration: none; }
  .pd-breadcrumb a:hover { color: var(--pd-link-hover); text-decoration: underline; }
  .pd-breadcrumb .sep { margin: 0 8px; color: var(--pd-muted); }

  .pd-grid {
    display: grid;
    grid-template-columns: 480px 1fr 320px;
    gap: 24px;
    align-items: start;
  }

  /* ─── Image column ─── */
  .pd-media {
    position: sticky; top: 90px;
    background: #FFFFFF; border: 1px solid var(--pd-border-soft);
    border-radius: 6px; padding: 14px;
  }
  .pd-media-frame {
    aspect-ratio: 1/1; background: #FAFAFA;
    border-radius: 4px; overflow: hidden;
    display: flex; align-items: center; justify-content: center;
    position: relative;
  }
  .pd-media-frame img { max-width: 100%; max-height: 100%; object-fit: contain; }
  .pd-media-frame video { width: 100%; height: 100%; object-fit: contain; background: #000; border-radius: 4px; }

  /* Amazon-style thumbnail strip */
  .pd-thumbs { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 12px; }
  .pd-thumb {
    width: 56px; height: 56px; padding: 0; background: #fff;
    border: 1px solid var(--pd-border); border-radius: 6px;
    overflow: hidden; cursor: pointer; position: relative;
    transition: border-color .12s, box-shadow .12s;
  }
  .pd-thumb img { width: 100%; height: 100%; object-fit: cover; display: block; }
  .pd-thumb:hover { border-color: #E77600; }
  .pd-thumb.active { border-color: #E77600; box-shadow: 0 0 0 1px #E77600; }
  .pd-thumb-video .pd-thumb-play {
    position: absolute; inset: 0; display: flex; align-items: center; justify-content: center;
    background: rgba(0,0,0,.38); color: #fff;
  }
  .pd-thumb-video .pd-thumb-play svg { width: 20px; height: 20px; }
  .pd-badge-sale {
    position: absolute; top: 10px; left: 10px;
    background: var(--pd-danger); color: #FFFFFF;
    padding: 4px 10px; border-radius: 3px;
    font-size: .72rem; font-weight: 700; letter-spacing: .04em;
    text-transform: uppercase;
    box-shadow: 0 2px 4px rgba(177,39,4,.35);
  }

  /* ─── Center info column ─── */
  .pd-info h1 { font-size: 1.5rem; font-weight: 600; margin: 0 0 8px; line-height: 1.28; color: var(--pd-ink); }
  .pd-partcode { color: var(--pd-link); font-size: .85rem; margin-bottom: 12px; }
  .pd-seller { font-size: .9rem; margin-bottom: 12px; }
  .pd-seller a { color: var(--pd-link); text-decoration: none; }
  .pd-hr { border: 0; border-top: 1px solid var(--pd-border-soft); margin: 14px 0; }
  .pd-deal-timer {
    display: inline-flex; align-items: center; gap: 6px;
    background: #FFF3E0; color: #C7511F;
    padding: 5px 10px; border-radius: 3px;
    font-size: .82rem; font-weight: 600;
    margin: 4px 0 12px;
  }
  .pd-price-row { display: flex; align-items: baseline; flex-wrap: wrap; gap: 10px; margin: 6px 0 8px; }
  .pd-discount-pct { color: var(--pd-danger); font-size: 1.6rem; font-weight: 400; }
  .pd-price { color: var(--pd-ink); font-size: 2rem; font-weight: 400; }
  .pd-price .fract { font-size: 1.05rem; vertical-align: super; margin-left: 2px; }
  .pd-price-unit { color: var(--pd-sub); font-size: .95rem; margin-left: 4px; font-weight: 400; }
  .pd-mrp { font-size: .95rem; color: var(--pd-sub); }
  .pd-mrp-strike { text-decoration: line-through; margin-right: 6px; }
  .pd-save-line { font-size: .95rem; color: var(--pd-success); font-weight: 600; }

  .pd-inclusive { color: var(--pd-muted); font-size: .8rem; margin-bottom: 12px; }

  .pd-about-h { font-weight: 700; font-size: 1rem; margin: 20px 0 8px; }
  .pd-about-text { font-size: .95rem; line-height: 1.55; color: var(--pd-ink); white-space: pre-line; }
  .pd-attr-grid {
    display: grid; grid-template-columns: 1fr 1fr; gap: 6px 20px; margin-top: 10px;
    font-size: .88rem;
  }
  .pd-attr-grid dt { font-weight: 700; color: var(--pd-ink); }
  .pd-attr-grid dd { margin: 0; color: var(--pd-sub); }

  /* ─── Right buy-box column ─── */
  .pd-buybox {
    position: sticky; top: 90px;
    border: 1px solid var(--pd-border); border-radius: 8px;
    padding: 16px; background: #FFFFFF;
  }
  .pd-buybox .pd-buybox-price { font-size: 1.55rem; font-weight: 400; margin-bottom: 8px; }
  .pd-stock { font-size: 1rem; margin-bottom: 12px; }
  .pd-stock.in  { color: var(--pd-success); font-weight: 600; }
  .pd-stock.out { color: var(--pd-danger);  font-weight: 600; }
  .pd-buybox .pd-sold-by { font-size: .85rem; color: var(--pd-sub); margin-bottom: 14px; }
  .pd-buybox .pd-sold-by strong { color: var(--pd-ink); }
  .pd-qty-row {
    display: flex; align-items: center; gap: 10px; margin-bottom: 12px;
    font-size: .9rem;
  }
  .pd-qty-row label { color: var(--pd-sub); }
  .pd-qty-row input[type=number] {
    width: 80px; padding: 8px 10px; text-align: center;
    border: 1px solid var(--pd-border); border-radius: 6px; font-weight: 600;
  }
  .pd-btn {
    display: block; width: 100%; text-align: center;
    padding: 10px 16px; border-radius: 100px; border: 1px solid transparent;
    font-weight: 500; font-size: .95rem; text-decoration: none; cursor: pointer;
    transition: background .15s, box-shadow .15s;
    margin-bottom: 10px;
  }
  .pd-btn-cart {
    background: var(--pd-yellow); color: var(--pd-ink); border-color: #FCD200;
  }
  .pd-btn-cart:hover { background: var(--pd-yellow-hover); color: var(--pd-ink); }
  .pd-btn-buy {
    background: var(--pd-orange); color: var(--pd-ink); border-color: #FF8F00;
  }
  .pd-btn-buy:hover  { background: var(--pd-orange-hover); color: var(--pd-ink); }
  .pd-btn.disabled { background: #EAEAEA; border-color: #DDD; color: #888; cursor: not-allowed; }

  @media (max-width: 991px) {
    .pd-grid { grid-template-columns: 1fr; }
    .pd-media { position: static; }
    .pd-buybox { position: static; }
  }
  @media (max-width: 575px) {
    .pd-info h1 { font-size: 1.15rem; }
    .pd-price { font-size: 1.55rem; }
    .pd-discount-pct { font-size: 1.2rem; }
  }
</style>

<div class="pd-page">
  <div class="container py-3">
    <div class="pd-breadcrumb">
      <a href="?p=home">Home</a>
      <?php if (!empty($p['category_name'])): ?>
        <span class="sep">›</span>
        <a href="?p=products&category=<?= e((string)$p['category_slug']) ?>"><?= e((string)$p['category_name']) ?></a>
      <?php endif; ?>
      <span class="sep">›</span>
      <span><?= e((string)$p['name']) ?></span>
    </div>

    <div class="pd-grid">
      <!-- ── LEFT: image gallery + video ── -->
      <div class="pd-media">
        <div class="pd-media-frame" id="pdMainFrame">
          <?php if ($disc['label'] !== ''): ?>
            <div class="pd-badge-sale"><?= e((string)$disc['label']) ?></div>
          <?php endif; ?>
          <?php if (!empty($allImages)): ?>
            <img id="pdMainImg" src="<?= e($allImages[0]) ?>" alt="<?= e((string)$p['name']) ?>">
          <?php else: ?>
            <i data-lucide="image" style="width:56px;height:56px;color:#BCBCBC;"></i>
          <?php endif; ?>
          <?php if ($videoPath !== ''): ?>
            <video id="pdMainVideo" src="<?= e($videoPath) ?>" controls playsinline preload="metadata" style="display:none;"></video>
          <?php endif; ?>
        </div>

        <?php $thumbCount = count($allImages) + ($videoPath !== '' ? 1 : 0); if ($thumbCount > 1): ?>
          <div class="pd-thumbs">
            <?php foreach ($allImages as $i => $img): ?>
              <button type="button" class="pd-thumb<?= $i === 0 ? ' active' : '' ?>" data-type="image" data-src="<?= e($img) ?>" aria-label="Image <?= $i + 1 ?>">
                <img src="<?= e($img) ?>" alt="">
              </button>
            <?php endforeach; ?>
            <?php if ($videoPath !== ''): ?>
              <button type="button" class="pd-thumb pd-thumb-video" data-type="video" data-src="<?= e($videoPath) ?>" aria-label="Play video">
                <?php if (!empty($allImages)): ?><img src="<?= e($allImages[0]) ?>" alt=""><?php endif; ?>
                <span class="pd-thumb-play"><i data-lucide="play"></i></span>
              </button>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      </div>

      <!-- ── MIDDLE: name / price / description ── -->
      <div class="pd-info">
        <h1><?= e((string)$p['name']) ?></h1>
        <?php if (!empty($p['part_code'])): ?>
          <div class="pd-partcode"><?= e((string)$p['part_code']) ?></div>
        <?php endif; ?>
        <div class="pd-seller">Sold by <strong><?= e($sellerName) ?></strong></div>

        <hr class="pd-hr">

        <?php if ($disc['pct'] > 0 && !empty($disc['ends_at'])): ?>
          <div class="pd-deal-timer" data-ends-at="<?= e((string)$disc['ends_at']) ?>">
            <i data-lucide="clock" style="width:14px;height:14px;"></i>
            <span class="pd-deal-timer-text">Ends soon</span>
          </div>
        <?php endif; ?>

        <div class="pd-price-row">
          <?php if ($disc['pct'] > 0): ?>
            <span class="pd-discount-pct">-<?= $discPctInt ?>%</span>
          <?php endif; ?>
          <span class="pd-price">₹<?= number_format($finalPrice, 2) ?><span class="pd-price-unit">/ <?= e($unitLabel) ?></span></span>
        </div>

        <?php if ($disc['pct'] > 0): ?>
          <div class="pd-mrp">M.R.P.: <span class="pd-mrp-strike">₹<?= number_format($origPrice, 2) ?></span></div>
          <div class="pd-save-line">You save: ₹<?= number_format($saveAmount, 2) ?> (<?= $discPctInt ?>% off)</div>
        <?php endif; ?>
        <div class="pd-inclusive">Inclusive of all taxes</div>

        <?php if (!empty($p['description'])): ?>
          <div class="pd-about-h">About this item</div>
          <div class="pd-about-text"><?= e((string)$p['description']) ?></div>
        <?php endif; ?>

        <div class="pd-about-h">Product details</div>
        <dl class="pd-attr-grid">
          <?php if (!empty($p['category_name'])): ?>
            <dt>Category</dt><dd><?= e((string)$p['category_name']) ?></dd>
          <?php endif; ?>
          <?php if (!empty($p['product_type'])): ?>
            <dt>Type</dt><dd><?= e((string)$p['product_type']) ?></dd>
          <?php endif; ?>
          <?php if ($soldAs === 'packet' && $packG > 0): ?>
            <dt>Packet size</dt><dd><?= rtrim(rtrim(number_format($packG, 2, '.', ''), '0'), '.') ?>g per pack</dd>
          <?php endif; ?>
          <dt>Sold as</dt><dd><?= $soldAs === 'packet' ? 'Packaged (fixed pack)' : 'Loose / by weight' ?></dd>
          <dt>Seller</dt><dd><?= e($sellerName) ?></dd>
        </dl>
      </div>

      <!-- ── RIGHT: buy box ── -->
      <div class="pd-buybox">
        <div class="pd-buybox-price">₹<?= number_format($finalPrice, 2) ?><span class="pd-price-unit">/ <?= e($unitLabel) ?></span></div>
        <?php if ($disc['pct'] > 0): ?>
          <div class="pd-mrp mb-2">M.R.P.: <span class="pd-mrp-strike">₹<?= number_format($origPrice, 2) ?></span></div>
        <?php endif; ?>

        <?php if ($isOutOfStock): ?>
          <div class="pd-stock out">Currently unavailable</div>
        <?php else: ?>
          <div class="pd-stock in">In stock</div>
        <?php endif; ?>

        <div class="pd-sold-by">Sold by <strong><?= e($sellerName) ?></strong></div>

        <?php if (!$isOutOfStock): ?>
          <form method="get" action="" class="pd-qty-form">
            <input type="hidden" name="p" value="cart/add">
            <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
            <div class="pd-qty-row">
              <label for="qty">Quantity:</label>
              <input id="qty" type="number" name="qty" step="<?= e($qtyStep) ?>" min="<?= e($qtyMin) ?>" value="<?= e($qtyDefault) ?>" required>
              <span class="text-muted small"><?= e($qtyLabel) ?></span>
            </div>
            <button type="submit" class="pd-btn pd-btn-cart">
              <i data-lucide="shopping-cart" style="width:14px;height:14px;"></i>
              Add to cart
            </button>
          </form>
          <a class="pd-btn pd-btn-buy" href="?p=cart/add&id=<?= (int)$p['id'] ?>&qty=<?= e($qtyDefault) ?>&next=checkout">
            Buy now
          </a>
        <?php else: ?>
          <button class="pd-btn disabled" disabled>Unavailable</button>
        <?php endif; ?>

        <div class="text-muted small mt-2" style="line-height:1.4;">
          <i data-lucide="truck" style="width:12px;height:12px;"></i>
          Shipping charges calculated at checkout based on your delivery pincode.
        </div>
      </div>
    </div>
  </div>
</div>

<script>if (window.lucide) window.lucide.createIcons();</script>
<script>
(function () {
  function fmt(diff) {
    if (diff <= 0) return null;
    var s = Math.floor(diff/1000);
    var d = Math.floor(s/86400); s %= 86400;
    var h = Math.floor(s/3600);  s %= 3600;
    var m = Math.floor(s/60);    s %= 60;
    if (d > 0) return 'Discount offer Ends in ' + d + 'd ' + h + 'h';
    if (h > 0) return 'Discount offer Ends in ' + h + 'h ' + m + 'm';
    if (m > 0) return 'Discount offer Ends in ' + m + 'm ' + s + 's';
    return 'Discount offer Ends in ' + s + 's';
  }
  function tick() {
    document.querySelectorAll('.pd-deal-timer').forEach(function (el) {
      var endsAt = el.getAttribute('data-ends-at');
      if (!endsAt) return;
      var end = new Date(endsAt.replace(' ', 'T')).getTime();
      var diff = end - Date.now();
      var txt = fmt(diff);
      if (txt === null) { el.style.display = 'none'; return; }
      var span = el.querySelector('.pd-deal-timer-text');
      if (span) span.textContent = txt;
    });
  }
  tick(); setInterval(tick, 1000);
})();
</script>
<script>
/* Amazon-style gallery: hover a thumbnail to swap the main image, click the
   video thumbnail to play the intro video inline. */
(function () {
  var thumbs = document.querySelectorAll('.pd-thumb');
  if (!thumbs.length) return;
  var mainImg = document.getElementById('pdMainImg');
  var mainVid = document.getElementById('pdMainVideo');
  function activate(btn, play) {
    thumbs.forEach(function (t) { t.classList.remove('active'); });
    btn.classList.add('active');
    var type = btn.getAttribute('data-type');
    var src  = btn.getAttribute('data-src');
    if (type === 'video' && mainVid) {
      if (mainImg) mainImg.style.display = 'none';
      mainVid.style.display = '';
      if (play) { mainVid.play().catch(function () {}); }
    } else {
      if (mainVid) { mainVid.pause(); mainVid.style.display = 'none'; }
      if (mainImg) { mainImg.style.display = ''; mainImg.src = src; }
    }
  }
  thumbs.forEach(function (t) {
    t.addEventListener('mouseenter', function () {
      if (t.getAttribute('data-type') === 'image') activate(t, false);
    });
    t.addEventListener('click', function () {
      activate(t, t.getAttribute('data-type') === 'video');
    });
  });
})();
</script>
<?php
};

require __DIR__ . '/../views/layout.php';
