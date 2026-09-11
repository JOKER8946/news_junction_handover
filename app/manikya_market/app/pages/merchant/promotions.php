<?php

declare(strict_types=1);

auth_require_role('super_admin');

$title = 'Promotion Updates';

// The storefront hero + promo banners are driven by ONE canonical profile:
// the first (lowest-id) super_admin — the exact same row home.php renders from
// (see home.php: "... WHERE u.role='super_admin' ORDER BY u.id ASC LIMIT 1").
// There are multiple super_admin accounts and any of them may edit this page,
// so we must ALWAYS target that shared storefront profile. Using the logged-in
// user's own id (auth_user_id) would save edits to a personal profile row the
// storefront never reads — which is why changes appeared to "not update".
$storeAdminRow = db_fetch_one($db, "SELECT id FROM users WHERE role = 'super_admin' ORDER BY id ASC LIMIT 1");
$merchantId = (int)($storeAdminRow['id'] ?? auth_user_id());

$dbNameRow = db_fetch_one($db, 'SELECT DATABASE() AS dbname');
$dbName = (string)($dbNameRow['dbname'] ?? '');

$ensureColumn = function (string $column, string $definition) use ($db, $dbName): void {
    if ($dbName === '') {
        return;
    }
    $row = db_fetch_one($db, 'SELECT COUNT(*) AS c FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :t AND COLUMN_NAME = :c', [
        'db' => $dbName,
        't' => 'merchant_profile',
        'c' => $column,
    ]);
    if ((int)($row['c'] ?? 0) > 0) return;
    $db->exec("ALTER TABLE `merchant_profile` ADD COLUMN `{$column}` {$definition}");
};

$ensureColumn('hero_eyebrow', 'VARCHAR(120) NULL');
$ensureColumn('hero_title_main', 'VARCHAR(180) NULL');
$ensureColumn('hero_title_sub', 'VARCHAR(180) NULL');
$ensureColumn('hero_description', 'TEXT NULL');
$ensureColumn('hero_discount_text', 'VARCHAR(120) NULL');
$ensureColumn('hero_discount_sub', 'VARCHAR(120) NULL');
$ensureColumn('hero_cta_text', 'VARCHAR(60) NULL');
$ensureColumn('hero_cta_link', 'VARCHAR(255) NULL');

$ensureColumn('promo1_title', 'VARCHAR(180) NULL');
$ensureColumn('promo1_description', 'TEXT NULL');
$ensureColumn('promo1_cta_text', 'VARCHAR(60) NULL');
$ensureColumn('promo1_cta_link', 'VARCHAR(255) NULL');
$ensureColumn('promo1_emoji', 'VARCHAR(20) NULL');
$ensureColumn('promo1_active', 'TINYINT(1) NOT NULL DEFAULT 1');

$ensureColumn('promo2_title', 'VARCHAR(180) NULL');
$ensureColumn('promo2_description', 'TEXT NULL');
$ensureColumn('promo2_cta_text', 'VARCHAR(60) NULL');
$ensureColumn('promo2_cta_link', 'VARCHAR(255) NULL');
$ensureColumn('promo2_emoji', 'VARCHAR(20) NULL');
$ensureColumn('promo2_active', 'TINYINT(1) NOT NULL DEFAULT 1');

// Per-banner media (uploaded image, preferred over emoji) + background colour.
$ensureColumn('promo1_image', 'VARCHAR(255) NULL');
$ensureColumn('promo1_bg_color', 'VARCHAR(20) NULL');
$ensureColumn('promo2_image', 'VARCHAR(255) NULL');
$ensureColumn('promo2_bg_color', 'VARCHAR(20) NULL');

$profile = db_fetch_one($db, 'SELECT * FROM merchant_profile WHERE merchant_user_id = :id LIMIT 1', ['id' => $merchantId]);
if (!$profile) {
    db_exec($db, 'INSERT INTO merchant_profile (merchant_user_id, created_at) VALUES (:id, NOW())', ['id' => $merchantId]);
    $profile = db_fetch_one($db, 'SELECT * FROM merchant_profile WHERE merchant_user_id = :id LIMIT 1', ['id' => $merchantId]);
}

$basePath = app_base_path($config);

if (request_method() === 'POST') {
    // ─── Existing banner images: start from saved list, remove any
    // explicitly marked, then append new uploads. ───────────────────────
    $existingImages = [];
    $rawExisting = (string)($profile['home_banner_images'] ?? '');
    if ($rawExisting !== '') {
        $decoded = json_decode($rawExisting, true);
        if (is_array($decoded)) {
            foreach ($decoded as $u) { if (is_string($u) && $u !== '') $existingImages[] = $u; }
        }
    }
    // Legacy fallback: pull the single home_bg_image into the array on first save.
    if (empty($existingImages) && !empty($profile['home_bg_image'])) {
        $existingImages[] = (string)$profile['home_bg_image'];
    }

    $removeImages = (array)($_POST['remove_banner_images'] ?? []);
    if (!empty($removeImages)) {
        $existingImages = array_values(array_filter($existingImages, fn($u) => !in_array($u, $removeImages, true)));
    }

    $allowedExts = ['jpg','jpeg','png','webp','gif','bmp','svg','avif'];
    $uploadDir = __DIR__ . '/../../../uploads/company';
    if (!is_dir($uploadDir)) { mkdir($uploadDir, 0777, true); }

    // Multiple new uploads under name="home_banner_images[]"
    if (isset($_FILES['home_banner_images']) && is_array($_FILES['home_banner_images']['name'] ?? null)) {
        $files = $_FILES['home_banner_images'];
        $count = count($files['name']);
        for ($i = 0; $i < $count; $i++) {
            if ((int)($files['error'][$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) continue;
            $tmp  = (string)($files['tmp_name'][$i] ?? '');
            $orig = (string)($files['name'][$i] ?? '');
            $ext  = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
            $mime = function_exists('mime_content_type') && is_file($tmp) ? (string)@mime_content_type($tmp) : '';
            $isImageMime = ($mime !== '' && stripos($mime, 'image/') === 0);
            if (!in_array($ext, $allowedExts, true) && !$isImageMime) {
                flash_set('error', 'Banner files must be images (JPG/PNG/WEBP/...).');
                redirect_to('merchant/promotions');
            }
            if ($ext === '' && $isImageMime) {
                $ext = preg_replace('/[^a-z0-9]/', '', strtolower(trim(substr($mime, 6)))) ?: 'img';
            }
            $newName = 'hero_' . date('YmdHis') . '_' . random_int(1000, 9999) . '.' . $ext;
            $dest    = $uploadDir . '/' . $newName;
            if (!move_uploaded_file($tmp, $dest)) {
                flash_set('error', 'Banner upload failed for "' . $orig . '".');
                redirect_to('merchant/promotions');
            }
            $existingImages[] = $basePath . '/uploads/company/' . $newName;
        }
    }

    // Cap how many banners we keep (avoid runaway storage).
    if (count($existingImages) > 10) {
        $existingImages = array_slice($existingImages, -10);
    }

    $bannerImagesJson = !empty($existingImages) ? json_encode(array_values($existingImages)) : null;
    // Keep home_bg_image in sync with the first image for backward compat.
    $homeBgImage = !empty($existingImages) ? $existingImages[0] : null;

    $homeBgColor = trim(post_string('home_bg_color'));
    if ($homeBgColor === '') $homeBgColor = '#fff8f0';
    if (!preg_match('/^#[0-9a-fA-F]{3,8}$/', $homeBgColor)) $homeBgColor = '#fff8f0';

    // ─── Promo banner media + background colours ────────────────────────
    // Each promo banner can show an uploaded image (preferred) or an emoji,
    // on a custom background colour. Keep the saved image unless the admin
    // uploads a replacement or ticks "Remove".
    $savePromoImage = function (string $field) use ($uploadDir, $basePath, $allowedExts) {
        if (empty($_FILES[$field]) || ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null; // nothing uploaded — keep existing
        }
        if ($_FILES[$field]['error'] !== UPLOAD_ERR_OK) return null;
        $tmp  = (string)$_FILES[$field]['tmp_name'];
        $ext  = strtolower(pathinfo((string)$_FILES[$field]['name'], PATHINFO_EXTENSION));
        $mime = function_exists('mime_content_type') && is_file($tmp) ? (string)@mime_content_type($tmp) : '';
        $isImageMime = ($mime !== '' && stripos($mime, 'image/') === 0);
        if (!in_array($ext, $allowedExts, true) && !$isImageMime) return null;
        if ($ext === '' && $isImageMime) {
            $ext = preg_replace('/[^a-z0-9]/', '', strtolower(trim(substr($mime, 6)))) ?: 'img';
        }
        $name = 'promo_' . date('YmdHis') . '_' . random_int(1000, 9999) . '.' . $ext;
        if (!move_uploaded_file($tmp, $uploadDir . '/' . $name)) return null;
        return $basePath . '/uploads/company/' . $name;
    };

    $promo1Image = (string)($profile['promo1_image'] ?? '');
    if (!empty($_POST['remove_promo1_image'])) $promo1Image = '';
    $p1New = $savePromoImage('promo1_image_file');
    if ($p1New !== null) $promo1Image = $p1New;

    $promo2Image = (string)($profile['promo2_image'] ?? '');
    if (!empty($_POST['remove_promo2_image'])) $promo2Image = '';
    $p2New = $savePromoImage('promo2_image_file');
    if ($p2New !== null) $promo2Image = $p2New;

    $normHex = function (string $c): ?string {
        $c = trim($c);
        return ($c !== '' && preg_match('/^#[0-9a-fA-F]{3,8}$/', $c)) ? $c : null;
    };
    $promo1Bg = $normHex(post_string('promo1_bg_color'));
    $promo2Bg = $normHex(post_string('promo2_bg_color'));

    $payload = [
        'id' => (int)$profile['id'],
        'home_bg_image' => $homeBgImage,
        'home_banner_images' => $bannerImagesJson,
        'home_bg_color' => $homeBgColor,
        'hero_eyebrow' => post_string('hero_eyebrow'),
        'hero_title_main' => post_string('hero_title_main'),
        'hero_title_sub' => post_string('hero_title_sub'),
        'hero_description' => post_string('hero_description'),
        'hero_discount_text' => post_string('hero_discount_text'),
        'hero_discount_sub' => post_string('hero_discount_sub'),
        'hero_cta_text' => post_string('hero_cta_text'),
        'hero_cta_link' => post_string('hero_cta_link'),

        'promo1_title' => post_string('promo1_title'),
        'promo1_description' => post_string('promo1_description'),
        'promo1_cta_text' => post_string('promo1_cta_text'),
        'promo1_cta_link' => post_string('promo1_cta_link'),
        'promo1_emoji' => post_string('promo1_emoji'),
        'promo1_active' => (int)($_POST['promo1_active'] ?? 0) === 1 ? 1 : 0,
        'promo1_image' => $promo1Image !== '' ? $promo1Image : null,
        'promo1_bg_color' => $promo1Bg,

        'promo2_title' => post_string('promo2_title'),
        'promo2_description' => post_string('promo2_description'),
        'promo2_cta_text' => post_string('promo2_cta_text'),
        'promo2_cta_link' => post_string('promo2_cta_link'),
        'promo2_emoji' => post_string('promo2_emoji'),
        'promo2_active' => (int)($_POST['promo2_active'] ?? 0) === 1 ? 1 : 0,
        'promo2_image' => $promo2Image !== '' ? $promo2Image : null,
        'promo2_bg_color' => $promo2Bg,
    ];

    db_exec($db, '
        UPDATE merchant_profile SET
            home_bg_image = :home_bg_image,
            home_banner_images = :home_banner_images,
            home_bg_color = :home_bg_color,
            hero_eyebrow = :hero_eyebrow,
            hero_title_main = :hero_title_main,
            hero_title_sub = :hero_title_sub,
            hero_description = :hero_description,
            hero_discount_text = :hero_discount_text,
            hero_discount_sub = :hero_discount_sub,
            hero_cta_text = :hero_cta_text,
            hero_cta_link = :hero_cta_link,
            promo1_title = :promo1_title,
            promo1_description = :promo1_description,
            promo1_cta_text = :promo1_cta_text,
            promo1_cta_link = :promo1_cta_link,
            promo1_emoji = :promo1_emoji,
            promo1_active = :promo1_active,
            promo1_image = :promo1_image,
            promo1_bg_color = :promo1_bg_color,
            promo2_title = :promo2_title,
            promo2_description = :promo2_description,
            promo2_cta_text = :promo2_cta_text,
            promo2_cta_link = :promo2_cta_link,
            promo2_emoji = :promo2_emoji,
            promo2_active = :promo2_active,
            promo2_image = :promo2_image,
            promo2_bg_color = :promo2_bg_color
        WHERE id = :id
    ', $payload);

    flash_set('success', 'Promotion updates saved');
    redirect_to('merchant/promotions');
}

$content = function () use ($profile) {
    $error = flash_get('error');
    $success = flash_get('success');
    // Show the EXACT saved value (including empty string when the user
    // intentionally cleared the field). Only fall back to the supplied
    // default when the column is genuinely NULL (never been saved).
    $val = function (string $key, string $default = '') use ($profile): string {
        if (!is_array($profile) || !array_key_exists($key, $profile) || $profile[$key] === null) {
            return $default;
        }
        return (string)$profile[$key];
    };
?>
  <div class="container py-4" style="max-width: 980px;">
    <div class="d-flex align-items-center justify-content-between mb-3">
      <h1 class="h5 mb-0 d-flex align-items-center gap-2">
        <i data-lucide="megaphone" class="mm-icon"></i> Promotion Updates
      </h1>
      <a class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1" href="?p=home" target="_blank">
        <i data-lucide="external-link" class="mm-icon"></i> View Storefront
      </a>
    </div>

    <p class="text-muted small mb-4">
      These settings control the hero banner and the two promotion banners on your public storefront home page.
      Changes appear instantly after saving.
    </p>

    <?php if ($success): ?>
      <div class="alert alert-success rounded-4"><?= e($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div class="alert alert-danger rounded-4"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" class="d-flex flex-column gap-4" enctype="multipart/form-data">

      <!-- Hero Banner -->
      <div class="bg-white border rounded-4 p-4">
        <h2 class="h6 fw-bold mb-3 d-flex align-items-center gap-2">
          <i data-lucide="layout-template" class="mm-icon"></i> Hero Banner
        </h2>

        <!-- Background banner images (multiple) + color -->
        <?php
          // Build a list of existing banner images for the gallery preview.
          $existingBanners = [];
          $rawList = (string)($profile['home_banner_images'] ?? '');
          if ($rawList !== '') {
              $decoded = json_decode($rawList, true);
              if (is_array($decoded)) {
                  foreach ($decoded as $u) { if (is_string($u) && $u !== '') $existingBanners[] = $u; }
              }
          }
          if (empty($existingBanners) && !empty($profile['home_bg_image'])) {
              $existingBanners[] = (string)$profile['home_bg_image'];
          }
        ?>
        <div class="row g-3 mb-3 align-items-start">
          <div class="col-md-8">
            <label class="form-label">Banner images <span class="text-muted small">(multiple — rotated as a carousel)</span></label>
            <input class="form-control" type="file" name="home_banner_images[]" accept="image/*" multiple>
            <small class="text-muted d-block mt-1">
              JPG / PNG / WEBP. You can select multiple files. They'll be appended to the existing set
              and shown as a slideshow on the home page. Up to 10 images total.
            </small>

            <?php if (!empty($existingBanners)): ?>
              <div class="mt-3">
                <div class="small text-muted mb-2">Current banners (<?= count($existingBanners) ?>):</div>
                <div class="row row-cols-2 row-cols-md-4 g-2">
                  <?php foreach ($existingBanners as $bi): ?>
                    <div class="col">
                      <div class="border rounded-3 p-2 h-100">
                        <img src="<?= e($bi) ?>" alt="Banner"
                             style="width:100%; height:100px; object-fit:cover; border-radius:4px;">
                        <div class="form-check mt-2">
                          <input class="form-check-input" type="checkbox"
                                 id="rm_<?= md5($bi) ?>" name="remove_banner_images[]" value="<?= e($bi) ?>">
                          <label class="form-check-label small text-danger" for="rm_<?= md5($bi) ?>">Remove</label>
                        </div>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>
            <?php endif; ?>
          </div>
          <div class="col-md-4">
            <label class="form-label">Background color</label>
            <div class="d-flex align-items-center gap-2">
              <input class="form-control form-control-color" type="color" name="home_bg_color"
                     value="<?= e($val('home_bg_color', '#fff8f0')) ?>"
                     style="width:56px; height:38px; padding:2px;"
                     oninput="document.getElementById('homeBgColorHex').value = this.value">
              <input class="form-control" type="text" id="homeBgColorHex"
                     value="<?= e($val('home_bg_color', '#fff8f0')) ?>" readonly style="font-family:monospace;">
            </div>
            <small class="text-muted d-block mt-1">Used as the banner backdrop behind images.</small>
          </div>
        </div>
        <hr class="my-3">

        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Eyebrow tag</label>
            <input class="form-control" name="hero_eyebrow"
              value="<?= e($val('hero_eyebrow', 'Farm Fresh • Direct Delivery')) ?>"
              placeholder="Farm Fresh • Direct Delivery">
          </div>
          <div class="col-md-3">
            <label class="form-label">Title — line 1</label>
            <input class="form-control" name="hero_title_main"
              value="<?= e($val('hero_title_main', 'Premium Mangoes')) ?>"
              placeholder="Premium Mangoes">
            <small class="text-muted">Highlighted word stays orange</small>
          </div>
          <div class="col-md-3">
            <label class="form-label">Title — line 2</label>
            <input class="form-control" name="hero_title_sub"
              value="<?= e($val('hero_title_sub', 'Straight from the Orchard')) ?>"
              placeholder="Straight from the Orchard">
          </div>
          <div class="col-12">
            <label class="form-label">Description</label>
            <textarea class="form-control" name="hero_description" rows="2"
              placeholder="Hand-picked, naturally ripened, premium quality mangoes delivered to your doorstep across South India."><?= e($val('hero_description', 'Hand-picked, naturally ripened, premium quality mangoes delivered to your doorstep across South India.')) ?></textarea>
          </div>
          <div class="col-md-4">
            <label class="form-label">Discount text</label>
            <input class="form-control" name="hero_discount_text"
              value="<?= e($val('hero_discount_text', 'Up to 30% OFF')) ?>"
              placeholder="Up to 30% OFF">
          </div>
          <div class="col-md-4">
            <label class="form-label">Discount sub-text</label>
            <input class="form-control" name="hero_discount_sub"
              value="<?= e($val('hero_discount_sub', 'on free shipping orders')) ?>"
              placeholder="on free shipping orders">
          </div>
          <div class="col-md-2">
            <label class="form-label">CTA label</label>
            <input class="form-control" name="hero_cta_text"
              value="<?= e($val('hero_cta_text', 'Shop Now')) ?>" placeholder="Shop Now">
          </div>
          <div class="col-md-2">
            <label class="form-label">CTA link</label>
            <input class="form-control" name="hero_cta_link"
              value="<?= e($val('hero_cta_link', '#km-featured')) ?>" placeholder="#km-featured">
          </div>
        </div>
      </div>

      <!-- Promo Banner 1 -->
      <div class="bg-white border rounded-4 p-4">
        <div class="d-flex align-items-center justify-content-between mb-3">
          <h2 class="h6 fw-bold mb-0 d-flex align-items-center gap-2">
            <i data-lucide="badge-percent" class="mm-icon"></i> Promo Banner 1 (left)
          </h2>
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="promo1_active" value="1" id="promo1Active"
              <?= ((int)($profile['promo1_active'] ?? 1) === 1) ? 'checked' : '' ?>>
            <label class="form-check-label" for="promo1Active">Show on storefront</label>
          </div>
        </div>
        <div class="row g-3">
          <div class="col-md-5">
            <label class="form-label">Image <span class="text-muted small">— shown instead of the emoji</span></label>
            <input class="form-control" type="file" name="promo1_image_file" accept="image/*">
            <?php $p1img = $val('promo1_image'); if ($p1img !== ''): ?>
              <div class="d-flex align-items-center gap-2 mt-2">
                <img src="<?= e($p1img) ?>" alt="" style="width:56px;height:56px;object-fit:cover;border-radius:8px;border:1px solid #dce0e0;">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="remove_promo1_image" value="1" id="rmP1img">
                  <label class="form-check-label small text-danger" for="rmP1img">Remove image</label>
                </div>
              </div>
            <?php endif; ?>
          </div>
          <div class="col-md-4">
            <label class="form-label">Background colour</label>
            <div class="input-group">
              <input type="color" class="form-control form-control-color" style="max-width:48px;"
                     value="<?= e($val('promo1_bg_color') !== '' ? $val('promo1_bg_color') : '#ffffff') ?>"
                     oninput="this.nextElementSibling.value=this.value">
              <input class="form-control" name="promo1_bg_color" placeholder="#ffffff (blank = white)"
                     value="<?= e($val('promo1_bg_color')) ?>">
            </div>
          </div>
          <div class="col-md-3">
            <label class="form-label">Emoji <span class="text-muted small">— fallback</span></label>
            <input class="form-control text-center" name="promo1_emoji"
              value="<?= e($val('promo1_emoji', '🚚')) ?>" maxlength="6">
          </div>
          <div class="col-md-8">
            <label class="form-label">Title</label>
            <input class="form-control" name="promo1_title"
              value="<?= e($val('promo1_title', 'Spend ₹500, get free delivery')) ?>">
          </div>
          <div class="col-md-2">
            <label class="form-label">CTA label</label>
            <input class="form-control" name="promo1_cta_text"
              value="<?= e($val('promo1_cta_text', 'Shop now')) ?>">
          </div>
          <div class="col-md-2">
            <label class="form-label">CTA link</label>
            <input class="form-control" name="promo1_cta_link"
              value="<?= e($val('promo1_cta_link', '#km-featured')) ?>">
          </div>
          <div class="col-12">
            <label class="form-label">Description</label>
            <textarea class="form-control" name="promo1_description" rows="2"><?= e($val('promo1_description', 'Order mangoes worth ₹500 and we will cover the shipping anywhere across South India.')) ?></textarea>
          </div>
        </div>
      </div>

      <!-- Promo Banner 2 -->
      <div class="bg-white border rounded-4 p-4">
        <div class="d-flex align-items-center justify-content-between mb-3">
          <h2 class="h6 fw-bold mb-0 d-flex align-items-center gap-2">
            <i data-lucide="leaf" class="mm-icon"></i> Promo Banner 2 (right)
          </h2>
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="promo2_active" value="1" id="promo2Active"
              <?= ((int)($profile['promo2_active'] ?? 1) === 1) ? 'checked' : '' ?>>
            <label class="form-check-label" for="promo2Active">Show on storefront</label>
          </div>
        </div>
        <div class="row g-3">
          <div class="col-md-5">
            <label class="form-label">Image <span class="text-muted small">— shown instead of the emoji</span></label>
            <input class="form-control" type="file" name="promo2_image_file" accept="image/*">
            <?php $p2img = $val('promo2_image'); if ($p2img !== ''): ?>
              <div class="d-flex align-items-center gap-2 mt-2">
                <img src="<?= e($p2img) ?>" alt="" style="width:56px;height:56px;object-fit:cover;border-radius:8px;border:1px solid #dce0e0;">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="remove_promo2_image" value="1" id="rmP2img">
                  <label class="form-check-label small text-danger" for="rmP2img">Remove image</label>
                </div>
              </div>
            <?php endif; ?>
          </div>
          <div class="col-md-4">
            <label class="form-label">Background colour</label>
            <div class="input-group">
              <input type="color" class="form-control form-control-color" style="max-width:48px;"
                     value="<?= e($val('promo2_bg_color') !== '' ? $val('promo2_bg_color') : '#ffffff') ?>"
                     oninput="this.nextElementSibling.value=this.value">
              <input class="form-control" name="promo2_bg_color" placeholder="#ffffff (blank = white)"
                     value="<?= e($val('promo2_bg_color')) ?>">
            </div>
          </div>
          <div class="col-md-3">
            <label class="form-label">Emoji <span class="text-muted small">— fallback</span></label>
            <input class="form-control text-center" name="promo2_emoji"
              value="<?= e($val('promo2_emoji', '🥭')) ?>" maxlength="6">
          </div>
          <div class="col-md-8">
            <label class="form-label">Title</label>
            <input class="form-control" name="promo2_title"
              value="<?= e($val('promo2_title', 'Pure Organic')) ?>">
          </div>
          <div class="col-md-2">
            <label class="form-label">CTA label</label>
            <input class="form-control" name="promo2_cta_text"
              value="<?= e($val('promo2_cta_text', 'Sign up')) ?>">
          </div>
          <div class="col-md-2">
            <label class="form-label">CTA link</label>
            <input class="form-control" name="promo2_cta_link"
              value="<?= e($val('promo2_cta_link', '?p=buyer/signup')) ?>">
          </div>
          <div class="col-12">
            <label class="form-label">Description</label>
            <textarea class="form-control" name="promo2_description" rows="2"><?= e($val('promo2_description', 'Naturally ripened, no chemicals. Up to 30% off your first order.')) ?></textarea>
          </div>
        </div>
      </div>

      <div class="d-grid">
        <button class="btn btn-mm" type="submit">Save Changes</button>
      </div>
    </form>
  </div>
<?php
};

require __DIR__ . '/../../views/layout.php';
