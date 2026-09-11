<?php

declare(strict_types=1);

$title = 'Home';

$products = [];
$currentPage = (int)($_GET['page'] ?? 1);
$productsPerPage = 12;
$totalProducts = 0;
$totalPages = 1;

try {
  $countResult = db_fetch_one($db, 'SELECT COUNT(*) as total FROM products WHERE is_active = 1');
  $totalProducts = (int)($countResult['total'] ?? 0);
  $totalPages = max(1, (int)ceil($totalProducts / $productsPerPage));

  if ($currentPage < 1) $currentPage = 1;
  if ($currentPage > $totalPages && $totalPages > 0) $currentPage = $totalPages;

  $offset = ($currentPage - 1) * $productsPerPage;

  if (product_meta_ready($db)) {
    $query = '
            SELECT p.id, p.name, p.product_type, p.part_code, p.price_per_kg, p.stock_kg, p.unit, p.image_path,
                   p.sold_as, p.pack_size_grams,
                   COALESCE(i.qty_kg, p.stock_kg) AS inventory_qty,
                   c.discount_pct AS cat_discount_pct,
                   c.discount_label AS cat_discount_label,
                   c.discount_ends_at AS cat_discount_ends_at
            FROM products p
            LEFT JOIN inventory i ON i.product_id = p.id
            LEFT JOIN categories c ON c.id = p.category_id
            WHERE p.is_active = 1
            ORDER BY (CASE WHEN COALESCE(i.qty_kg, p.stock_kg) > 0 THEN 1 ELSE 0 END) DESC, p.created_at DESC
            LIMIT ' . $productsPerPage . ' OFFSET ' . $offset;
  } else {
    $query = '
            SELECT p.id, p.name, NULL AS product_type, NULL AS part_code, p.price_per_kg, p.stock_kg, p.unit, p.image_path,
                   p.sold_as, p.pack_size_grams,
                   COALESCE(i.qty_kg, p.stock_kg) AS inventory_qty,
                   c.discount_pct AS cat_discount_pct,
                   c.discount_label AS cat_discount_label,
                   c.discount_ends_at AS cat_discount_ends_at
            FROM products p
            LEFT JOIN inventory i ON i.product_id = p.id
            LEFT JOIN categories c ON c.id = p.category_id
            WHERE p.is_active = 1
            ORDER BY (CASE WHEN COALESCE(i.qty_kg, p.stock_kg) > 0 THEN 1 ELSE 0 END) DESC, p.created_at DESC
            LIMIT ' . $productsPerPage . ' OFFSET ' . $offset;
  }
  $products = db_fetch_all($db, $query);
} catch (Throwable $e) {
  $products = [];
}

// Auto-apply product coupons (Amazon-style "Deal price"). Batch-look-up so
// the tile render loop can decide per product whether to show a deal price
// vs plain price without an extra DB call per row.
$autoCouponByProduct = [];
try {
  if ($products && function_exists('coupon_auto_apply_for_products')) {
    $productIds = array_map(fn($p) => (int)$p['id'], $products);
    $autoCouponByProduct = coupon_auto_apply_for_products($db, $productIds);
  }
} catch (Throwable $e) { $autoCouponByProduct = []; }

// Categories with up to 4 sample product thumbnails each (Amazon-style tiles).
$categoryTiles = [];
try {
  $cats = db_fetch_all($db, 'SELECT id, name, slug, image_path FROM categories WHERE is_active = 1 ORDER BY sort_order, name') ?: [];
  foreach ($cats as $c) {
    $samples = db_fetch_all($db,
      'SELECT id, name, image_path FROM products
       WHERE category_id = :cid AND is_active = 1 AND image_path IS NOT NULL AND image_path <> ""
       ORDER BY created_at DESC LIMIT 4',
      ['cid' => (int)$c['id']]
    ) ?: [];
    if (count($samples) < 4) {
      // Fill with any products from this category, even without images, so tile still renders.
      $extra = db_fetch_all($db,
        'SELECT id, name, image_path FROM products WHERE category_id = :cid AND is_active = 1
         ORDER BY created_at DESC LIMIT 4',
        ['cid' => (int)$c['id']]
      ) ?: [];
      $seen = array_column($samples, 'id');
      foreach ($extra as $e2) {
        if (count($samples) >= 4) break;
        if (!in_array((int)$e2['id'], $seen, true)) $samples[] = $e2;
      }
    }
    $catCount = db_fetch_one($db, 'SELECT COUNT(*) AS n FROM products WHERE category_id = :cid AND is_active = 1', ['cid' => (int)$c['id']]);
    $categoryTiles[] = [
      'id'    => (int)$c['id'],
      'name'  => (string)$c['name'],
      'slug'  => (string)$c['slug'],
      'image' => isset($c['image_path']) ? (string)$c['image_path'] : '',
      'count' => (int)($catCount['n'] ?? 0),
      'samples' => $samples,
    ];
  }
} catch (Throwable $e) { $categoryTiles = []; }

// Promotion Updates (super-admin's storefront copy) — read from the super-admin's profile row.
$homeBg = null;
$homeBgColor = '';
$bannerImages = [];   // legacy plain-string list (kept as fallback)
$bannerSlides = [];   // new: per-slide [image, link, name] — derived from categories
$merchant = [];
try {
  $merchant = db_fetch_one($db,
      "SELECT mp.* FROM merchant_profile mp
       JOIN users u ON u.id = mp.merchant_user_id
       WHERE u.role = 'super_admin'
       ORDER BY u.id ASC LIMIT 1"
  ) ?: [];
  if (!$merchant) {
      $merchant = db_fetch_one($db, 'SELECT * FROM merchant_profile ORDER BY id ASC LIMIT 1') ?: [];
  }
  if ($merchant) {
    $homeBg = $merchant['home_bg_image'] ?? null;
    $homeBgColor = (string)($merchant['home_bg_color'] ?? '');
    $rawList = (string)($merchant['home_banner_images'] ?? '');
    if ($rawList !== '') {
        $decoded = json_decode($rawList, true);
        if (is_array($decoded)) {
            foreach ($decoded as $u) { if (is_string($u) && $u !== '') $bannerImages[] = $u; }
        }
    }
    if (empty($bannerImages) && !empty($homeBg)) {
        $bannerImages[] = (string)$homeBg;
    }
  }
} catch (Throwable $e) { /* defaults */ }

// Hero carousel: the "Promotion Updates" screen (super-admin's merchant
// profile) is the SOURCE OF TRUTH for the banner. Whatever images the
// super-admin uploaded there are what the storefront shows.
//
// Only when NO promotion banner images have been uploaded do we fall back
// to active-category images, so the hero carousel is never empty.
if (!empty($bannerImages)) {
    // Promotion Updates images win — build slide structs (no per-slide link).
    $bannerSlides = [];
    foreach ($bannerImages as $u) {
        $bannerSlides[] = ['image' => (string)$u, 'link' => '', 'name' => ''];
    }
} else {
    // Fallback: derive slides from active categories that have an image.
    // Each slide is clickable and links to the category page.
    try {
        $catSlideRows = db_fetch_all($db,
            'SELECT name, slug, image_path
             FROM categories
             WHERE is_active = 1 AND image_path IS NOT NULL AND image_path <> ""
             ORDER BY sort_order ASC, id ASC'
        ) ?: [];
        foreach ($catSlideRows as $cs) {
            $bannerSlides[] = [
                'image' => (string)$cs['image_path'],
                'link'  => '?p=products&category=' . urlencode((string)$cs['slug']),
                'name'  => (string)$cs['name'],
            ];
        }
    } catch (Throwable $e) { /* no category slides */ }
    $bannerImages = array_map(fn($s) => $s['image'], $bannerSlides);
}

$mv = function (string $key, string $fallback) use ($merchant): string {
    $v = (string)($merchant[$key] ?? '');
    return $v !== '' ? $v : $fallback;
};

$heroEyebrow      = $mv('hero_eyebrow', 'Welcome to Manikya Market');
$heroTitleMain    = $mv('hero_title_main', 'Discover Premium Products');
$heroTitleSub     = $mv('hero_title_sub', 'Direct from Trusted Sellers');
$heroDescription  = $mv('hero_description', 'Curated quality products delivered to your doorstep across India.');
$heroDiscountText = $mv('hero_discount_text', 'Up to 30% OFF');
$heroDiscountSub  = $mv('hero_discount_sub', 'on free shipping orders');
$heroCtaText      = $mv('hero_cta_text', 'Shop Now');
$heroCtaLink      = $mv('hero_cta_link', '#mk-featured');

$promo1Active      = (int)($merchant['promo1_active'] ?? 1) === 1;
$promo1Title       = $mv('promo1_title', 'Spend ₹500, get free delivery');
$promo1Description = $mv('promo1_description', 'Order worth ₹500 and we cover the shipping anywhere across India.');
$promo1CtaText     = $mv('promo1_cta_text', 'Shop now');
$promo1CtaLink     = $mv('promo1_cta_link', '#mk-featured');
$promo1Emoji       = $mv('promo1_emoji', '🚚');
$promo1Image       = $mv('promo1_image', '');
$promo1BgColor     = $mv('promo1_bg_color', '');

$promo2Active      = (int)($merchant['promo2_active'] ?? 1) === 1;
$promo2Title       = $mv('promo2_title', 'Sign up & save');
$promo2Description = $mv('promo2_description', 'New customers get up to 30% off their first order. Join today.');
$promo2CtaText     = $mv('promo2_cta_text', 'Sign up');
$promo2CtaLink     = $mv('promo2_cta_link', '?p=buyer/signup');
$promo2Emoji       = $mv('promo2_emoji', '✨');
$promo2Image       = $mv('promo2_image', '');
$promo2BgColor     = $mv('promo2_bg_color', '');

$content = function () use (
  $products, $autoCouponByProduct, $homeBgColor, $bannerImages, $bannerSlides, $currentPage, $totalPages, $categoryTiles,
  $heroEyebrow, $heroTitleMain, $heroTitleSub, $heroDescription,
  $heroDiscountText, $heroDiscountSub, $heroCtaText, $heroCtaLink,
  $promo1Active, $promo1Title, $promo1Description, $promo1CtaText, $promo1CtaLink, $promo1Emoji, $promo1Image, $promo1BgColor,
  $promo2Active, $promo2Title, $promo2Description, $promo2CtaText, $promo2CtaLink, $promo2Emoji, $promo2Image, $promo2BgColor
) {
  $flash = flash_get('success');
?>
  <style>
    /* ─── Marketplace home — Amazon-style international look ─── */
    .mk-home {
      --mk-bg: #EAEDED;
      --mk-card: #FFFFFF;
      --mk-text: #0F1111;
      --mk-text-sub: #565959;
      --mk-text-muted: #767676;
      --mk-link: #007185;
      --mk-link-hover: #C7511F;
      --mk-accent: #FF9900;
      --mk-accent-dark: #F08804;
      --mk-success: #007600;
      --mk-danger: #B12704;
      --mk-border: #D5D9D9;
      --mk-border-soft: #E7E7E7;
      --mk-yellow: #FFD814;
      --mk-yellow-hover: #F7CA00;
      color: var(--mk-text);
    }
    body.bg-light:has(.mk-home) { background: var(--mk-bg) !important; }
    .mk-home .container { max-width: 1500px; padding-left: 14px; padding-right: 14px; }

    /* ─── Hero ─── */
    <?php
      // Color is always the base. One or more images may be set; if 1, it sits
      // beside the text; if 2+, the slot becomes a Bootstrap carousel.
      $heroHasImage = !empty($bannerImages);
      $heroBgColor  = $homeBgColor !== '' ? $homeBgColor : '';
      // Determine if the chosen color is light/dark so we flip text contrast.
      $isLight = true;
      if ($heroBgColor !== '' && preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $heroBgColor)) {
        $hex = ltrim($heroBgColor, '#');
        if (strlen($hex) === 3) $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        $r = hexdec(substr($hex, 0, 2)); $g = hexdec(substr($hex, 2, 2)); $b = hexdec(substr($hex, 4, 2));
        $lum = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;
        $isLight = $lum > 0.55;
      }
      $heroTextColor = $heroBgColor === '' ? '#FFFFFF' : ($isLight ? '#0F1111' : '#FFFFFF');
      $heroSubColor  = $heroBgColor === '' ? 'rgba(255,255,255,.85)' : ($isLight ? '#3F3F3F' : 'rgba(255,255,255,.85)');
    ?>
    .mk-hero {
      position: relative;
      <?php if ($heroBgColor !== ''): ?>
      background:
        radial-gradient(circle at 20% 25%, rgba(255,255,255,0.04) 1px, transparent 2px),
        radial-gradient(circle at 60% 70%, rgba(255,255,255,0.03) 1px, transparent 2px),
        radial-gradient(circle at 85% 15%, rgba(255,255,255,0.05) 1px, transparent 2px),
        radial-gradient(circle at 35% 80%, rgba(255,255,255,0.04) 1px, transparent 2px),
        radial-gradient(circle at 75% 45%, rgba(255,255,255,0.03) 1px, transparent 2px),
        radial-gradient(ellipse at center, <?= e($heroBgColor) ?> 0%, <?= e($heroBgColor) ?>cc 100%),
        linear-gradient(135deg, <?= e($heroBgColor) ?> 0%, #1B2A5E 100%);
      background-size: 60px 60px, 90px 90px, 110px 110px, 70px 70px, 100px 100px, 100% 100%, 100% 100%;
      <?php else: ?>
      background:
        radial-gradient(circle at 20% 25%, rgba(255,255,255,0.04) 1px, transparent 2px),
        radial-gradient(circle at 60% 70%, rgba(255,255,255,0.03) 1px, transparent 2px),
        radial-gradient(circle at 85% 15%, rgba(255,255,255,0.05) 1px, transparent 2px),
        linear-gradient(135deg, #0E1B3F 0%, #1B2A5E 100%);
      background-size: 60px 60px, 90px 90px, 110px 110px, 100% 100%;
      <?php endif; ?>
      border-radius: 14px;
      overflow: hidden;
      color: <?= e($heroTextColor) ?>;
      margin-bottom: 14px;
      box-shadow: 0 6px 20px rgba(14, 27, 63, 0.18);
      min-height: 220px;
    }
    /* Carousel takes the right side of the banner; text on the left.
       Using object-fit: contain so the photo shows in full at its
       natural aspect — no stretching, no cropping. */
    .mk-hero-carousel {
      position: absolute; top: 0; right: 0; bottom: 0;
      width: 55%;
      z-index: 1;
      padding: 8px 12px;
    }
    .mk-hero-carousel .carousel-inner,
    .mk-hero-carousel .carousel-item {
      height: 100%;
    }
    .mk-hero-carousel .carousel-item {
      display: none; align-items: center; justify-content: center;
    }
    .mk-hero-carousel .carousel-item.active { display: flex; }
    .mk-hero-carousel .carousel-item img {
      width: 100%; height: 100%;
      max-width: 100%; max-height: 100%;
      object-fit: contain;
      display: block;
      filter: drop-shadow(0 4px 14px rgba(0,0,0,.18));
    }
    .mk-hero-content {
      position: relative; z-index: 3;
      max-width: 45%;
      padding: 20px 24px 20px 72px;
      display: flex; flex-direction: column; justify-content: center;
      min-height: 200px;
    }
    .mk-hero h1 {
      font-size: clamp(1.3rem, 2vw, 1.85rem);
      margin-bottom: 8px; line-height: 1.18;
      font-weight: 700;
      color: #FFFFFF;
      letter-spacing: -0.01em;
    }
    .mk-hero h1 .accent {
      color: #E8A23B;
      font-weight: 700;
    }
    .mk-hero-sub {
      font-size: .9rem;
      margin-bottom: 14px;
      color: #B8C7E0;
      line-height: 1.5;
    }
    .mk-hero-discount { margin-bottom: 9px; padding: 5px 10px; font-size: .78rem; }
    .mk-hero-discount small { font-size: .72rem; }
    .mk-hero-cta { padding: 7px 14px; font-size: .82rem; }
    .mk-hero-eyebrow { margin-bottom: 9px; padding: 3px 10px; font-size: .65rem; }
    /* Arrows sit at the outer edges of the banner */
    .mk-hero-arrow {
      position: absolute;
      top: 50%; transform: translateY(-50%);
      width: 44px; height: 44px;
      background: rgba(255,255,255,.92); border: 0; border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      cursor: pointer; opacity: 1; padding: 0;
      box-shadow: 0 2px 8px rgba(0,0,0,.18);
      transition: background .15s, transform .15s;
      z-index: 5;
    }
    .mk-hero-arrow:hover { background: #FFFFFF; transform: translateY(-50%) scale(1.06); }
    .mk-hero-arrow.left  { left: 14px; }
    .mk-hero-arrow.right { right: 14px; }
    .mk-hero-arrow i { color: #0F1111; }
    .mk-hero-carousel .carousel-indicators {
      bottom: 12px; margin: 0 auto;
      z-index: 4;
    }
    .mk-hero-carousel .carousel-indicators [data-bs-target] {
      background-color: rgba(255,255,255,.55);
      width: 8px; height: 8px; border-radius: 50%;
      border: 0;
      box-shadow: 0 0 0 1px rgba(0,0,0,.12);
    }
    .mk-hero-carousel .carousel-indicators .active {
      background-color: #FFFFFF;
    }
    .mk-hero-eyebrow {
      display: inline-flex; align-items: center; gap: 8px;
      align-self: flex-start;
      background: rgba(255,255,255,.06);
      color: #E8C896;
      border: 1px solid rgba(232, 162, 59, .35);
      padding: 6px 16px; border-radius: 999px;
      font-weight: 700; font-size: .72rem; letter-spacing: .08em; text-transform: uppercase;
      margin-bottom: 14px;
      backdrop-filter: blur(2px);
    }
    .mk-hero-eyebrow::before {
      content: ''; display: inline-block;
      width: 8px; height: 8px; border-radius: 50%;
      background: #FF5252;
      box-shadow: 0 0 8px rgba(255,82,82,.7);
    }
    .mk-hero-sub {
      font-size: .92rem; max-width: 560px; margin: 0 0 14px;
      color: #B8C7E0; line-height: 1.5;
    }
    .mk-hero-discount {
      display: inline-flex; align-items: center; gap: 8px;
      align-self: flex-start;
      background: rgba(255,255,255,.08);
      color: #FFFFFF;
      border: 1px solid rgba(255,255,255,.18);
      padding: 7px 16px; border-radius: 999px;
      font-weight: 700; font-size: .85rem;
      margin-bottom: 14px;
      backdrop-filter: blur(2px);
    }
    .mk-hero-discount i { color: #E8A23B; }
    .mk-hero-discount small {
      color: rgba(184, 199, 224, .85);
      font-weight: 500;
    }
    .mk-hero-cta {
      display: inline-flex; align-items: center; gap: 8px;
      align-self: flex-start;
      background: linear-gradient(135deg, #1FAE6F 0%, #18965E 100%);
      color: #FFFFFF;
      padding: 11px 26px; border: 0; border-radius: 999px;
      font-weight: 600; font-size: .92rem; text-decoration: none;
      box-shadow: 0 6px 18px rgba(31, 174, 111, .35);
      transition: transform .15s, box-shadow .15s, background .15s;
    }
    .mk-hero-cta:hover {
      color: #FFFFFF;
      transform: translateY(-1px);
      box-shadow: 0 8px 22px rgba(31, 174, 111, .45);
      background: linear-gradient(135deg, #25BE7C 0%, #1FAE6F 100%);
    }

    /* ─── Search bar ─── */
    .mk-search {
      display: flex; align-items: center;
      background: #FFFFFF; border: 1px solid var(--mk-border);
      border-radius: 8px;
      max-width: 900px; margin: 0 auto 18px;
      box-shadow: 0 1px 2px rgba(15,17,17,0.05);
      overflow: hidden;
    }
    .mk-search-loc {
      display: inline-flex; align-items: center; gap: 6px;
      padding: 10px 14px; background: #F0F2F2;
      color: var(--mk-text-sub); font-size: .85rem; white-space: nowrap;
      border-right: 1px solid var(--mk-border);
    }
    .mk-search-cat {
      background: #F0F2F2; color: var(--mk-text-sub);
      border: 0; outline: 0; border-right: 1px solid var(--mk-border);
      padding: 10px 28px 10px 14px;
      font-size: .85rem; cursor: pointer;
      -webkit-appearance: none; appearance: none;
      background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%230F1111'><path d='M4 6l4 4 4-4'/></svg>");
      background-repeat: no-repeat;
      background-position: right 8px center;
      background-size: 10px;
      max-width: 200px;
      white-space: nowrap; text-overflow: ellipsis; overflow: hidden;
    }
    .mk-search-cat:focus { background-color: #E3E6E6; }
    .mk-search input {
      border: 0; outline: 0; flex: 1; padding: 10px 14px;
      background: transparent; font-size: .92rem;
    }
    .mk-search button {
      background: var(--mk-accent); color: #0F1111;
      border: 0; padding: 10px 16px; cursor: pointer;
      transition: background .15s;
    }
    .mk-search button:hover { background: var(--mk-accent-dark); }

    /* ─── Section heading ─── */
    .mk-section-head {
      display: flex; align-items: baseline; justify-content: space-between;
      margin: 24px 0 14px;
    }
    .mk-section-head h2 {
      font-size: 1.35rem; font-weight: 700; margin: 0;
      color: var(--mk-text);
    }
    .mk-section-head .mk-link {
      color: var(--mk-link); font-size: .92rem; text-decoration: none; font-weight: 500;
    }
    .mk-section-head .mk-link:hover { color: var(--mk-link-hover); text-decoration: underline; }

    /* ─── Category tile cards (Amazon-style 4-up with thumbnail grid) ─── */
    .mk-cat-grid {
      display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px;
    }
    .mk-cat-card {
      background: var(--mk-card); border-radius: 4px; padding: 18px;
      display: flex; flex-direction: column;
      box-shadow: 0 1px 2px rgba(15,17,17,.06);
    }
    .mk-cat-card-title { font-size: 1.1rem; font-weight: 700; margin: 0 0 12px; color: var(--mk-text); }
    .mk-cat-thumbs {
      display: grid; grid-template-columns: 1fr 1fr; gap: 6px; margin-bottom: 12px;
    }
    .mk-cat-thumb {
      aspect-ratio: 1/1; background: #F7F7F7; border-radius: 2px;
      display: flex; align-items: center; justify-content: center; overflow: hidden;
      position: relative;
    }
    .mk-cat-thumb img { width: 100%; height: 100%; object-fit: cover; }
    .mk-cat-thumb .mk-thumb-ph { color: #BCBCBC; }
    .mk-cat-thumb .mk-thumb-label {
      position: absolute; left: 0; right: 0; bottom: 0;
      background: linear-gradient(0deg, rgba(0,0,0,.55) 0%, rgba(0,0,0,0) 100%);
      color: #FFF; font-size: .68rem; padding: 14px 6px 4px;
      white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .mk-cat-link {
      color: var(--mk-link); font-size: .92rem; text-decoration: none; font-weight: 500;
      margin-top: auto;
    }
    .mk-cat-link:hover { color: var(--mk-link-hover); text-decoration: underline; }
    .mk-cat-empty {
      grid-column: 1/-1; padding: 32px; text-align: center;
      color: var(--mk-text-muted); background: var(--mk-card); border-radius: 4px;
    }

    /* ─── Featured product grid ─── */
    .mk-grid {
      display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px;
    }
    .mk-product {
      background: var(--mk-card); border-radius: 4px; overflow: hidden;
      display: flex; flex-direction: column; position: relative;
      box-shadow: 0 1px 2px rgba(15,17,17,.06);
      transition: box-shadow .15s, transform .15s;
    }
    .mk-product:hover {
      box-shadow: 0 4px 10px rgba(15,17,17,.10);
      transform: translateY(-1px);
    }
    .mk-product-img {
      aspect-ratio: 1/1; background: #FAFAFA;
      display: flex; align-items: center; justify-content: center; overflow: hidden;
      border-bottom: 1px solid var(--mk-border-soft);
    }
    .mk-product-img img { width: 100%; height: 100%; object-fit: cover; transition: transform .35s; }
    .mk-product:hover .mk-product-img img { transform: scale(1.04); }
    .mk-product-body { padding: 14px; display: flex; flex-direction: column; gap: 4px; flex: 1; }
    .mk-product-name {
      font-size: .95rem; font-weight: 500; color: var(--mk-link); line-height: 1.3;
      display: -webkit-box; -webkit-line-clamp: 2; line-clamp: 2; -webkit-box-orient: vertical;
      overflow: hidden;
    }
    .mk-product:hover .mk-product-name { color: var(--mk-link-hover); }
    .mk-product-meta { color: var(--mk-text-muted); font-size: .8rem; }
    .mk-product-price-block { margin-top: 6px; }
    .mk-price {
      color: var(--mk-text); font-size: 1.2rem; font-weight: 700;
    }
    .mk-price .mk-price-unit { color: var(--mk-text-sub); font-size: .85rem; font-weight: 400; margin-left: 4px; }

    /* ─── Category discount UI ─── */
    /* Corner badge in top-left of tile: "Summer Sale" */
    .mk-sale-badge {
      position: absolute; top: 8px; left: 8px; z-index: 3;
      background: #B12704; color: #fff;
      padding: 4px 10px; border-radius: 3px;
      font-size: .72rem; font-weight: 700; letter-spacing: .02em;
      text-transform: uppercase;
      box-shadow: 0 2px 4px rgba(177,39,4,.35);
    }
    /* Countdown chip above the price */
    .mk-deal-timer {
      display: inline-flex; align-items: center; gap: 4px;
      background: #FFF3E0; color: #C7511F;
      padding: 3px 8px; border-radius: 3px;
      font-size: .72rem; font-weight: 600;
      margin: 4px 0;
      width: fit-content;
    }
    /* -XX% next to the discounted price */
    .mk-price-row { display: flex; align-items: baseline; gap: 8px; }
    .mk-discount-pct {
      color: #B12704; font-size: 1.1rem; font-weight: 700;
    }
    /* M.R.P. line with strikethrough */
    .mk-mrp { color: var(--mk-text-sub); font-size: .82rem; margin-top: 2px; }
    .mk-mrp-strike { text-decoration: line-through; }
    .mk-stock { font-size: .82rem; color: var(--mk-success); margin-top: 2px; }
    .mk-stock.out { color: var(--mk-danger); }
    .mk-product-cta {
      display: inline-flex; align-items: center; justify-content: center; gap: 6px;
      background: var(--mk-yellow); color: #0F1111;
      border: 1px solid #FCD200; border-radius: 100px;
      padding: 7px 14px; font-weight: 500; font-size: .88rem; text-decoration: none;
      margin-top: 12px; align-self: flex-start;
      transition: background .15s;
    }
    .mk-product-cta:hover { background: var(--mk-yellow-hover); color: #0F1111; }
    .mk-product-cta.disabled, .mk-product-cta[disabled] {
      background: #EAEAEA; border-color: #DDD; color: #888; cursor: not-allowed;
    }

    /* ─── Promo banners ─── */
    .mk-promo-row {
      display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-top: 24px;
    }
    /* When only one promo banner is enabled, let it span the full width
       instead of leaving an empty second column. */
    .mk-promo-row.mk-promo-row--single { grid-template-columns: 1fr; }
    .mk-promo {
      position: relative; overflow: hidden;
      background: var(--mk-card, #fff); border-radius: 14px; padding: 22px;
      display: flex; gap: 18px; align-items: center;
      border: 1px solid #dce0e0;
      box-shadow: 0 1px 2px rgba(15,17,17,.06);
      transition: transform .18s ease, box-shadow .18s ease;
    }
    .mk-promo:hover { transform: translateY(-3px); box-shadow: 0 10px 26px rgba(15,17,17,.14); }
    .mk-promo-emoji {
      flex: 0 0 92px; height: 92px; width: 92px; border-radius: 12px; overflow: hidden;
      background: #F0F2F2; display: flex; align-items: center; justify-content: center;
      font-size: 2.8rem; line-height: 1;
      box-shadow: 0 1px 3px rgba(15,17,17,.10);
    }
    .mk-promo-emoji img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .mk-promo h3 { font-size: 1.12rem; font-weight: 700; margin: 0 0 6px; color: var(--mk-text); }
    .mk-promo p { font-size: .88rem; color: var(--mk-text-sub); margin: 0 0 10px; }
    .mk-promo .mk-promo-cta {
      color: var(--mk-link); font-size: .92rem; text-decoration: none; font-weight: 600;
      display: inline-flex; align-items: center; gap: 4px;
    }
    .mk-promo .mk-promo-cta:hover { color: var(--mk-link-hover); text-decoration: underline; }

    /* ─── Pagination ─── */
    .mk-home .pagination { margin-top: 22px; }
    .mk-home .pagination .page-link {
      color: var(--mk-link); border-color: var(--mk-border);
      border-radius: 4px; margin: 0 3px; font-weight: 500;
    }
    .mk-home .pagination .page-link:hover {
      background: #F7FAFA; color: var(--mk-link-hover); border-color: var(--mk-border);
    }
    .mk-home .pagination .page-item.active .page-link {
      background: var(--mk-accent); border-color: var(--mk-accent); color: #0F1111;
    }

    /* ─── Empty state ─── */
    .mk-empty {
      background: var(--mk-card); border-radius: 4px; padding: 40px;
      text-align: center; color: var(--mk-text-muted);
      box-shadow: 0 1px 2px rgba(15,17,17,.06);
    }
    .mk-empty .mk-empty-icon {
      width: 64px; height: 64px; border-radius: 50%; background: #F0F2F2;
      display: inline-flex; align-items: center; justify-content: center; margin-bottom: 12px;
      color: var(--mk-text-sub);
    }

    /* ─── Responsive ─── */
    @media (max-width: 1199px) {
      .mk-cat-grid { grid-template-columns: repeat(3, 1fr); }
      .mk-grid { grid-template-columns: repeat(3, 1fr); }
    }
    @media (max-width: 991px) {
      .mk-cat-grid { grid-template-columns: repeat(2, 1fr); }
      .mk-grid { grid-template-columns: repeat(2, 1fr); }
      .mk-promo-row { grid-template-columns: 1fr; }
      .mk-hero { padding: 28px 24px; }
    }

    /* Tablet / mid */
    @media (max-width: 767px) {
      .mk-home .container { padding-left: 10px; padding-right: 10px; }
      .mk-hero { border-radius: 6px; min-height: 240px; }
      .mk-hero-content { padding: 14px 16px 14px 48px; min-height: auto; max-width: 100%; }
      .mk-hero-carousel { position: relative; width: 100%; height: 130px; }
      .mk-hero-arrow { width: 32px; height: 32px; }
      .mk-hero-arrow.left  { left: 6px; }
      .mk-hero-arrow.right { right: 6px; }
      .mk-hero h1 { font-size: 1.05rem; line-height: 1.18; }
      .mk-hero-sub { font-size: .78rem; }
      .mk-hero-discount { font-size: .85rem; padding: 6px 10px; }
      .mk-hero-cta { padding: 9px 18px; font-size: .9rem; }
      .mk-search { max-width: 100%; }
      .mk-search-loc { display: none; }
      .mk-search input { font-size: .88rem; padding: 9px 10px; }
      .mk-section-head { margin: 18px 0 10px; }
      .mk-section-head h2 { font-size: 1.1rem; }
      .mk-section-head .mk-link { font-size: .85rem; }
      .mk-cat-grid { gap: 10px; }
      .mk-cat-card { padding: 14px; }
      .mk-cat-card-title { font-size: .98rem; margin-bottom: 8px; }
      .mk-cat-thumbs { gap: 4px; margin-bottom: 8px; }
      .mk-cat-thumb .mk-thumb-label { font-size: .62rem; padding: 12px 4px 3px; }
      .mk-cat-link { font-size: .85rem; }
      .mk-grid { gap: 10px; }
      .mk-product-body { padding: 10px; gap: 2px; }
      .mk-product-name { font-size: .85rem; -webkit-line-clamp: 2; line-clamp: 2; }
      .mk-product-meta { font-size: .72rem; }
      .mk-price { font-size: 1.02rem; }
      .mk-price .mk-price-unit { font-size: .72rem; }
      .mk-stock { font-size: .72rem; }
      .mk-product-cta { padding: 6px 12px; font-size: .8rem; margin-top: 8px; }
      .mk-product-cta i { width: 12px !important; height: 12px !important; }
      .mk-promo { padding: 16px; gap: 12px; }
      .mk-promo-emoji { flex: 0 0 60px; height: 60px; width: 60px; font-size: 2rem; }
      .mk-promo h3 { font-size: .98rem; margin-bottom: 4px; }
      .mk-promo p { font-size: .82rem; margin-bottom: 8px; }
      .mk-promo .mk-promo-cta { font-size: .85rem; }
    }

    /* Phones */
    @media (max-width: 480px) {
      .mk-hero { padding: 18px 14px; }
      .mk-hero h1 { font-size: 1.3rem; }
      .mk-hero-eyebrow { font-size: .68rem; padding: 4px 10px; margin-bottom: 10px; }
      .mk-cat-grid { grid-template-columns: 1fr; }
      .mk-grid { grid-template-columns: repeat(2, 1fr); gap: 8px; }
      .mk-product-img { aspect-ratio: 1/1; }
      .mk-product-body { padding: 8px; }
      .mk-product-name { font-size: .8rem; }
      .mk-price { font-size: .95rem; }
      .mk-product-cta { padding: 5px 10px; font-size: .75rem; }
      .mk-promo { padding: 14px; }
      .mk-promo-emoji { flex: 0 0 52px; height: 52px; width: 52px; font-size: 1.7rem; }
      .mk-home .pagination .page-link { padding: 4px 8px; font-size: .82rem; }
    }
  </style>

  <div class="mk-home">
    <div class="container py-3">

      <?php if ($flash): ?>
        <div class="alert alert-success rounded-1 mb-3"><?= e($flash) ?></div>
      <?php endif; ?>

      <!-- Hero -->
      <section class="mk-hero">
        <?php if ($heroHasImage): ?>
          <div id="mkHeroCarousel" class="carousel slide mk-hero-carousel" data-bs-ride="carousel" data-bs-interval="5000">
            <?php if (count($bannerImages) > 1): ?>
            <div class="carousel-indicators">
              <?php foreach ($bannerImages as $bi => $u): ?>
                <button type="button" data-bs-target="#mkHeroCarousel" data-bs-slide-to="<?= (int)$bi ?>"
                        class="<?= $bi === 0 ? 'active' : '' ?>"
                        aria-current="<?= $bi === 0 ? 'true' : 'false' ?>"
                        aria-label="Slide <?= (int)$bi + 1 ?>"></button>
              <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <div class="carousel-inner">
              <?php foreach ($bannerSlides as $bi => $slide): ?>
                <div class="carousel-item <?= $bi === 0 ? 'active' : '' ?>">
                  <?php $alt = $slide['name'] !== '' ? $slide['name'] : ('Hero slide ' . ((int)$bi + 1)); ?>
                  <?php if (!empty($slide['link'])): ?>
                    <a href="<?= e($slide['link']) ?>" style="display:block;width:100%;height:100%;">
                      <img src="<?= e($slide['image']) ?>" alt="<?= e($alt) ?>">
                    </a>
                  <?php else: ?>
                    <img src="<?= e($slide['image']) ?>" alt="<?= e($alt) ?>">
                  <?php endif; ?>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>

        <div class="mk-hero-content">
          <?php if ($heroEyebrow !== ''): ?>
            <span class="mk-hero-eyebrow"><?= e($heroEyebrow) ?></span>
          <?php endif; ?>
          <h1><span class="accent"><?= e($heroTitleMain) ?></span><br/><?= e($heroTitleSub) ?></h1>
          <?php if ($heroDescription !== ''): ?>
            <p class="mk-hero-sub"><?= e($heroDescription) ?></p>
          <?php endif; ?>
          <?php if ($heroDiscountText !== ''): ?>
            <div class="mk-hero-discount">
              <i data-lucide="badge-percent" style="width:16px;height:16px;"></i>
              <?= e($heroDiscountText) ?> <small><?= e($heroDiscountSub) ?></small>
            </div>
          <?php endif; ?>
          <?php if ($heroCtaText !== ''): ?>
            <a class="mk-hero-cta" href="<?= e($heroCtaLink) ?>">
              <?= e($heroCtaText) ?> <i data-lucide="arrow-right" style="width:16px;height:16px;"></i>
            </a>
          <?php endif; ?>
        </div>

        <?php if ($heroHasImage && count($bannerImages) > 1): ?>
          <button class="mk-hero-arrow left" type="button" data-bs-target="#mkHeroCarousel" data-bs-slide="prev" aria-label="Previous">
            <i data-lucide="chevron-left" style="width:22px;height:22px;"></i>
          </button>
          <button class="mk-hero-arrow right" type="button" data-bs-target="#mkHeroCarousel" data-bs-slide="next" aria-label="Next">
            <i data-lucide="chevron-right" style="width:22px;height:22px;"></i>
          </button>
        <?php endif; ?>
      </section>

      <!-- Search bar -->
      <form class="mk-search" method="get" action="">
        <input type="hidden" name="p" value="products">
        <select class="mk-search-cat" name="category" aria-label="Category">
          <option value="">All</option>
          <?php foreach ($categoryTiles as $cat): ?>
            <option value="<?= e((string)$cat['slug']) ?>"><?= e((string)$cat['name']) ?></option>
          <?php endforeach; ?>
        </select>
        <input class="search-input" type="search" name="q" placeholder="Search Manikya Market" aria-label="Search">
        <button type="submit" aria-label="Search"><i data-lucide="search" style="width:18px;height:18px;"></i></button>
      </form>

      <!-- Category tile cards -->
      <div class="mk-section-head">
        <h2>Shop by category</h2>
        <a href="?p=products" class="mk-link">See all categories →</a>
      </div>
      <div class="mk-cat-grid">
        <?php if (empty($categoryTiles)): ?>
          <div class="mk-cat-empty">No categories yet.</div>
        <?php else: foreach ($categoryTiles as $cat): ?>
          <div class="mk-cat-card">
            <h3 class="mk-cat-card-title"><?= e($cat['name']) ?></h3>
            <?php if (!empty($cat['image'])): ?>
              <a href="?p=products&category=<?= e($cat['slug']) ?>" class="mk-cat-banner-link" style="display:block;">
                <div class="mk-cat-banner" style="width:100%;aspect-ratio:16/9;border-radius:8px;overflow:hidden;background:#f5f5f5;">
                  <img src="<?= e($cat['image']) ?>" alt="<?= e($cat['name']) ?>" style="width:100%;height:100%;object-fit:cover;display:block;">
                </div>
              </a>
            <?php else: ?>
              <?php
                // Only show tiles for products that actually exist — no empty
                // placeholder boxes. Narrow to a single column when there is
                // just one product so the grid doesn't look lopsided.
                $catSamples = array_values(array_filter($cat['samples'] ?? [], fn($s) => !empty($s)));
                $sampleCols = count($catSamples) <= 1 ? 1 : 2;
              ?>
              <?php if ($catSamples): ?>
                <div class="mk-cat-thumbs" style="grid-template-columns: repeat(<?= $sampleCols ?>, 1fr);">
                  <?php foreach ($catSamples as $s): ?>
                    <div class="mk-cat-thumb">
                      <?php if (!empty($s['image_path'])): ?>
                        <img src="<?= e((string)$s['image_path']) ?>" alt="<?= e((string)$s['name']) ?>">
                        <span class="mk-thumb-label"><?= e((string)$s['name']) ?></span>
                      <?php else: ?>
                        <i data-lucide="image" class="mk-thumb-ph" style="width:28px;height:28px;"></i>
                        <span class="mk-thumb-label"><?= e((string)$s['name']) ?></span>
                      <?php endif; ?>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            <?php endif; ?>
            <a href="?p=products&category=<?= e($cat['slug']) ?>" class="mk-cat-link">
              See more in <?= e($cat['name']) ?> (<?= (int)$cat['count'] ?>)
            </a>
          </div>
        <?php endforeach; endif; ?>
      </div>

      <!-- Promo banners -->
      <?php if ($promo1Active || $promo2Active): ?>
      <?php
        $promoSingle = ($promo1Active xor $promo2Active); // exactly one enabled
        // Build a "world-class" adaptive style from an optional background
        // colour: a soft diagonal gradient for depth, text/CTA colours that
        // auto-contrast (white on dark bg, ink on light), and a media tile
        // tint that reads on either. Blank bg → clean white card.
        $promoStyle = function (string $bg): array {
            $bg = trim($bg);
            if ($bg === '' || !preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $bg)) {
                return ['card' => '#ffffff', 'text' => 'var(--mk-text,#0F1111)', 'sub' => 'var(--mk-text-sub,#565959)',
                        'tile' => '#F0F2F2', 'cta' => 'var(--mk-link,#007185)', 'border' => '#dce0e0', 'onDark' => false];
            }
            $hex = ltrim($bg, '#');
            if (strlen($hex) === 3) { $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2]; }
            $r = hexdec(substr($hex,0,2)); $g = hexdec(substr($hex,2,2)); $b = hexdec(substr($hex,4,2));
            $lum = (0.2126*$r + 0.7152*$g + 0.0722*$b) / 255; // relative luminance
            $onDark = $lum < 0.62;
            return [
                'card'   => 'linear-gradient(135deg, ' . $bg . ' 0%, ' . $bg . 'e6 100%)',
                'text'   => $onDark ? '#ffffff' : '#0F1111',
                'sub'    => $onDark ? 'rgba(255,255,255,.88)' : '#3f4a4a',
                'tile'   => $onDark ? 'rgba(255,255,255,.16)' : 'rgba(0,0,0,.06)',
                'cta'    => $onDark ? '#ffffff' : '#0F1111',
                'border' => $onDark ? 'rgba(255,255,255,.18)' : 'rgba(0,0,0,.08)',
                'onDark' => $onDark,
            ];
        };
        $renderPromo = function (array $s, string $img, string $emoji, string $title, string $desc, string $ctaText, string $ctaLink) {
            ?>
            <div class="mk-promo" style="background: <?= $s['card'] ?>; color: <?= $s['text'] ?>; border-color: <?= $s['border'] ?>;">
              <?php if ($img !== '' || $emoji !== ''): ?>
                <div class="mk-promo-emoji" style="background: <?= $s['tile'] ?>;">
                  <?php if ($img !== ''): ?>
                    <img src="<?= e($img) ?>" alt="">
                  <?php else: ?>
                    <?= e($emoji) ?>
                  <?php endif; ?>
                </div>
              <?php endif; ?>
              <div class="flex-grow-1">
                <h3 style="color: <?= $s['text'] ?>;"><?= e($title) ?></h3>
                <?php if ($desc !== ''): ?><p style="color: <?= $s['sub'] ?>;"><?= e($desc) ?></p><?php endif; ?>
                <?php if ($ctaText !== ''): ?>
                  <a href="<?= e($ctaLink) ?>" class="mk-promo-cta" style="color: <?= $s['cta'] ?>;<?= $s['onDark'] ? 'text-decoration:underline;' : '' ?>"><?= e($ctaText) ?> →</a>
                <?php endif; ?>
              </div>
            </div>
            <?php
        };
      ?>
      <div class="mk-promo-row<?= $promoSingle ? ' mk-promo-row--single' : '' ?>">
        <?php if ($promo1Active): $renderPromo($promoStyle($promo1BgColor), $promo1Image, $promo1Emoji, $promo1Title, $promo1Description, $promo1CtaText, $promo1CtaLink); endif; ?>
        <?php if ($promo2Active): $renderPromo($promoStyle($promo2BgColor), $promo2Image, $promo2Emoji, $promo2Title, $promo2Description, $promo2CtaText, $promo2CtaLink); endif; ?>
      </div>
      <?php endif; ?>

      <!-- Featured Products -->
      <div class="mk-section-head" id="mk-featured">
        <h2>Featured products</h2>
        <span class="mk-link" style="color: var(--mk-text-muted); cursor: default;"><?= count($products) ?> showing</span>
      </div>

      <?php if (!$products): ?>
        <div class="mk-empty">
          <div class="mk-empty-icon"><i data-lucide="package" style="width:28px;height:28px;"></i></div>
          <div class="fw-semibold mb-1">No products yet</div>
          <div class="small">New listings will appear here once sellers add them.</div>
        </div>
      <?php else: ?>
        <div class="mk-grid" id="productsGrid">
          <?php foreach ($products as $p):
            $inventoryQty = (float)($p['inventory_qty'] ?? $p['stock_kg'] ?? 0);
            $isOutOfStock = $inventoryQty <= 0;
            // Packet vs bulk display: for packet products we show "500g pack"
            // as the "per" label, so a ₹450 shows as ₹450 / 500g pack.
            $pSoldAs = (string)($p['sold_as'] ?? 'bulk');
            $pPackG  = (float)($p['pack_size_grams'] ?? 0);
            if ($pSoldAs === 'packet' && $pPackG > 0) {
                $packGramsFmt = rtrim(rtrim(number_format($pPackG, 2, '.', ''), '0'), '.');
                $unitLabel = $packGramsFmt . 'g pack';
            } else {
                $unitLabel = product_unit_label($p['unit'] ?? 'kg');
            }
            // Live tile discount:
            //   1. Auto-apply product coupon (Amazon-style) — takes precedence
            //   2. Category sale (fallback)
            // Both compute a per-unit rebate + strikethrough MRP + optional countdown.
            $disc = product_auto_discount($p, $autoCouponByProduct[(int)$p['id']] ?? null);
            $orig  = (float)$p['price_per_kg'];
            $final = (float)$disc['final_unit'];
            $discPctInt = (int)round((float)$disc['pct']);
          ?>
            <div class="mk-product product-card" data-product-type="<?= !empty($p['product_type']) ? e((string)$p['product_type']) : 'all' ?>">
              <?php $productUrl = '?p=product&id=' . (int)$p['id']; ?>
              <?php if ($disc['label'] !== ''): ?>
                <div class="mk-sale-badge"><?= e($disc['label']) ?></div>
              <?php endif; ?>
              <a class="mk-product-img" href="<?= e($productUrl) ?>" target="_blank" rel="noopener" style="text-decoration:none;">
                <?php if (!empty($p['image_path'])): ?>
                  <img src="<?= e((string)$p['image_path']) ?>" alt="<?= e((string)$p['name']) ?>">
                <?php else: ?>
                  <i data-lucide="image" style="width:36px;height:36px;color:#BCBCBC;"></i>
                <?php endif; ?>
              </a>
              <div class="mk-product-body">
                <a class="mk-product-name" href="<?= e($productUrl) ?>" target="_blank" rel="noopener" style="text-decoration:none;"><?= e((string)$p['name']) ?></a>
                <?php if (!empty($p['product_type']) || !empty($p['part_code'])): ?>
                  <div class="mk-product-meta">
                    <?= e((string)($p['product_type'] ?? '')) ?><?= (!empty($p['product_type']) && !empty($p['part_code'])) ? ' • ' : '' ?><?= e((string)($p['part_code'] ?? '')) ?>
                  </div>
                <?php endif; ?>
                <?php if ($disc['pct'] > 0 && !empty($disc['ends_at'])): ?>
                  <div class="mk-deal-timer" data-ends-at="<?= e((string)$disc['ends_at']) ?>">
                    <i data-lucide="clock" style="width:12px;height:12px;"></i>
                    <span class="mk-deal-timer-text">Ends soon</span>
                  </div>
                <?php endif; ?>
                <div class="mk-product-price-block">
                  <?php if ($disc['pct'] > 0): ?>
                    <div class="mk-price-row">
                      <span class="mk-discount-pct">-<?= $discPctInt ?>%</span>
                      <span class="mk-price">₹<?= number_format($final, 2) ?><span class="mk-price-unit">/ <?= e($unitLabel) ?></span></span>
                    </div>
                    <div class="mk-mrp">M.R.P.: <span class="mk-mrp-strike">₹<?= number_format($orig, 2) ?></span></div>
                  <?php else: ?>
                    <div class="mk-price">₹<?= e((string)$p['price_per_kg']) ?><span class="mk-price-unit">/ <?= e($unitLabel) ?></span></div>
                  <?php endif; ?>
                  <div class="mk-stock <?= $isOutOfStock ? 'out' : '' ?>">
                    <?= $isOutOfStock ? 'Currently unavailable' : 'In stock' ?>
                  </div>
                </div>
                <?php if ($isOutOfStock): ?>
                  <button class="mk-product-cta disabled" type="button" disabled>
                    <i data-lucide="ban" style="width:14px;height:14px;"></i> Unavailable
                  </button>
                <?php else: ?>
                  <a class="mk-product-cta" href="?p=cart/add&id=<?= (int)$p['id'] ?>&qty=1">
                    <i data-lucide="shopping-cart" style="width:14px;height:14px;"></i> Add to cart
                  </a>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <!-- Pagination -->
      <?php if ($totalPages > 1): ?>
        <nav aria-label="Page navigation">
          <ul class="pagination justify-content-center">
            <li class="page-item <?= $currentPage === 1 ? 'disabled' : '' ?>">
              <a class="page-link" href="?p=home&page=<?= max(1, $currentPage - 1) ?>">Previous</a>
            </li>
            <?php
              $startPage = max(1, $currentPage - 2);
              $endPage   = min($totalPages, $currentPage + 2);
              if ($startPage > 1): ?>
              <li class="page-item"><a class="page-link" href="?p=home&page=1">1</a></li>
              <?php if ($startPage > 2): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
            <?php endif; ?>
            <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
              <li class="page-item <?= $i === $currentPage ? 'active' : '' ?>">
                <a class="page-link" href="?p=home&page=<?= $i ?>"><?= $i ?></a>
              </li>
            <?php endfor; ?>
            <?php if ($endPage < $totalPages): ?>
              <?php if ($endPage < $totalPages - 1): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
              <li class="page-item"><a class="page-link" href="?p=home&page=<?= $totalPages ?>"><?= $totalPages ?></a></li>
            <?php endif; ?>
            <li class="page-item <?= $currentPage === $totalPages ? 'disabled' : '' ?>">
              <a class="page-link" href="?p=home&page=<?= min($totalPages, $currentPage + 1) ?>">Next</a>
            </li>
          </ul>
        </nav>
      <?php endif; ?>

    </div>
  </div>

  <script>
    if (window.lucide) window.lucide.createIcons();
  </script>

  <!-- GA4 ecommerce + engagement events (home page only) -->
  <script>
  (function () {
    function bind() {
      if (typeof gtag !== 'function') return;

      function getProductData(card, index) {
        var nameEl  = card.querySelector('.mk-product-name');
        var priceEl = card.querySelector('.mk-price');
        var addBtn  = card.querySelector('a.mk-product-cta');

        var name = nameEl ? nameEl.textContent.trim() : '';
        var priceText = priceEl ? priceEl.firstChild.textContent.replace(/[^0-9.]/g, '') : '0';
        var price = parseFloat(priceText) || 0;

        var itemId = '';
        var qty = 1;
        if (addBtn) {
          var href = addBtn.getAttribute('href') || '';
          var idMatch = href.match(/id=(\d+)/);
          var qtyMatch = href.match(/qty=(\d+)/);
          if (idMatch) itemId = idMatch[1];
          if (qtyMatch) qty = parseInt(qtyMatch[1]);
        }

        return {
          item_id: itemId || ('prod_' + index),
          item_name: name,
          price: price,
          quantity: qty,
          index: index + 1,
          item_list_name: 'Featured Products'
        };
      }

      var allCards = Array.from(document.querySelectorAll('.mk-product'));
      var productList = allCards.map(function (card, i) { return getProductData(card, i); });
      if (productList.length > 0) {
        gtag('event', 'view_item_list', { item_list_name: 'Featured Products', items: productList });
      }

      document.querySelectorAll('a.mk-product-cta').forEach(function (btn) {
        btn.addEventListener('click', function () {
          var card = btn.closest('.mk-product');
          if (!card) return;
          var idx = allCards.indexOf(card);
          var data = getProductData(card, idx >= 0 ? idx : 0);
          gtag('event', 'add_to_cart', { currency: 'INR', value: data.price * data.quantity, items: [data] });
        });
      });

      var searchInput = document.querySelector('.mk-search input.search-input');
      if (searchInput) {
        var searchTimer = null;
        searchInput.addEventListener('input', function () {
          clearTimeout(searchTimer);
          searchTimer = setTimeout(function () {
            var q = searchInput.value.trim();
            if (q.length >= 2) gtag('event', 'search', { search_term: q });
          }, 1000);
        });
      }

      var shopNowBtn = document.querySelector('.mk-hero-cta');
      if (shopNowBtn) {
        shopNowBtn.addEventListener('click', function () {
          gtag('event', 'select_promotion', { promotion_name: 'Hero Banner', creative_name: 'Shop Now CTA' });
        });
      }

      var scrollMilestones = [25, 50, 75, 90];
      var scrollFired = {};
      window.addEventListener('scroll', function () {
        var scrolled = (window.scrollY + window.innerHeight) / document.body.scrollHeight * 100;
        scrollMilestones.forEach(function (milestone) {
          if (!scrollFired[milestone] && scrolled >= milestone) {
            scrollFired[milestone] = true;
            gtag('event', 'scroll_depth', { percent_scrolled: milestone });
          }
        });
      }, { passive: true });
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', bind);
    else bind();
  })();
  </script>

  <script>
  /* Live deal countdown for .mk-deal-timer chips on product tiles. */
  (function () {
    function fmt(diffMs) {
      if (diffMs <= 0) return null;
      var s = Math.floor(diffMs / 1000);
      var d = Math.floor(s / 86400); s %= 86400;
      var h = Math.floor(s / 3600);  s %= 3600;
      var m = Math.floor(s / 60);    s %= 60;
      if (d > 0) return 'Discount offer Ends in ' + d + 'd ' + h + 'h';
      if (h > 0) return 'Discount offer Ends in ' + h + 'h ' + m + 'm';
      if (m > 0) return 'Discount offer Ends in ' + m + 'm ' + s + 's';
      return 'Discount offer Ends in ' + s + 's';
    }
    function tick() {
      document.querySelectorAll('.mk-deal-timer').forEach(function (el) {
        var endsAt = el.getAttribute('data-ends-at');
        if (!endsAt) return;
        var end = new Date(endsAt.replace(' ', 'T')).getTime();
        var diff = end - Date.now();
        var txt = fmt(diff);
        if (txt === null) {
          el.style.display = 'none';
          return;
        }
        var span = el.querySelector('.mk-deal-timer-text');
        if (span) span.textContent = txt;
      });
    }
    tick();
    setInterval(tick, 1000);
  })();
  </script>
<?php
};

require __DIR__ . '/../views/layout.php';
