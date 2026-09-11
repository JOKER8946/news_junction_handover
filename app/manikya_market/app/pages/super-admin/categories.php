<?php

declare(strict_types=1);

auth_require_role('super_admin');

$title = 'Categories';

/**
 * Save the uploaded image (if any) for the given category id.
 * Returns the new web path on success, null if no file uploaded, or false on error.
 */
/**
 * Save the uploaded category image (HD-quality only: no resize, no recompress).
 * Returns:
 *   string  – the new web path on success
 *   null    – no file was uploaded
 *   string  – an error message when validation fails (prefixed "ERR:")
 */
$saveCategoryImage = function (int $catId) {
    if (empty($_FILES['image']) || ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null; // no upload attempted
    }
    if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        return 'ERR:Upload failed (PHP error ' . (int)$_FILES['image']['error'] . '). The file may be larger than the server limit.';
    }
    $allowed = ['jpg' => 1, 'jpeg' => 1, 'png' => 1, 'webp' => 1];
    $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
    if (!isset($allowed[$ext])) {
        return 'ERR:Only jpg / jpeg / png / webp files are accepted (gif rejected: too low quality for HD banners).';
    }
    $info = @getimagesize($_FILES['image']['tmp_name']);
    if (!$info) {
        return 'ERR:Not a valid image file.';
    }
    // No minimum-dimension check — any image size is accepted.
    // Cap file size at ~10 MB so super-admins don't accidentally upload 50 MB RAW files.
    $size = (int)($_FILES['image']['size'] ?? 0);
    if ($size > 10 * 1024 * 1024) {
        return 'ERR:Image is too large (' . round($size / 1024 / 1024, 1) . ' MB). Max 10 MB.';
    }

    $dir = __DIR__ . '/../../../uploads/categories';
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    $safeName = 'cat_' . $catId . '_' . time() . '_' . random_int(1000, 9999) . '.' . $ext;
    $dest = $dir . '/' . $safeName;
    // Pure move — NO resize, NO recompression. Whatever HD bytes the
    // super-admin uploaded are stored as-is.
    if (!move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {
        return 'ERR:Could not save the file to disk.';
    }
    return '/manikya_market/uploads/categories/' . $safeName;
};

if (request_method() === 'POST') {
    $action = post_string('action');

    if ($action === 'create') {
        $name = trim(post_string('name'));
        if ($name === '') {
            flash_set('error', 'Name is required.');
            redirect_to('super-admin/categories');
        }
        $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name));
        $slug = trim($slug, '-');
        if ($slug === '') {
            $slug = 'category-' . time();
        }
        $sortOrder = (int)post_string('sort_order');
        if ($sortOrder === 0) {
            $row = db_fetch_one($db, 'SELECT COALESCE(MAX(sort_order),0)+10 AS next FROM categories');
            $sortOrder = (int)($row['next'] ?? 10);
        }
        try {
            db_exec($db,
                'INSERT INTO categories (name, slug, sort_order, is_active, created_at) VALUES (:n, :s, :o, 1, NOW())',
                ['n' => $name, 's' => $slug, 'o' => $sortOrder]
            );
            $newId = (int)$db->lastInsertId();
            $img = $saveCategoryImage($newId);
            if (is_string($img) && str_starts_with($img, 'ERR:')) {
                flash_set('error', 'Category added but image upload failed: ' . substr($img, 4));
            } else {
                if (is_string($img)) {
                    db_exec($db, 'UPDATE categories SET image_path = :p WHERE id = :id', ['p' => $img, 'id' => $newId]);
                }
                flash_set('success', 'Category added.');
            }
        } catch (Throwable $t) {
            flash_set('error', 'Could not add category (slug may be in use).');
        }
        redirect_to('super-admin/categories');
    }

    if ($action === 'update') {
        $id        = (int)post_string('id');
        $name      = trim(post_string('name'));
        $sortOrder = (int)post_string('sort_order');
        $isActive  = post_string('is_active') === '1' ? 1 : 0;

        // Category-level discount. Applied automatically to every product in
        // this category, seen by every buyer, until discount_ends_at.
        //   discount_pct   0..100        (0 = no sale)
        //   discount_label short text    ("Summer Sale") — shown as a badge on tiles
        //   discount_ends_at DATETIME    (optional) — sale auto-expires when NOW() > this
        $discountPct = (float)post_string('discount_pct');
        if ($discountPct < 0)   $discountPct = 0;
        if ($discountPct > 100) $discountPct = 100;
        $discountLabel = trim(post_string('discount_label'));
        if (mb_strlen($discountLabel) > 80) $discountLabel = mb_substr($discountLabel, 0, 80);
        $discountEndsAt = trim(post_string('discount_ends_at'));
        // Accept "YYYY-MM-DDTHH:MM" (datetime-local) or blank. Normalise to
        // "YYYY-MM-DD HH:MM:00" for MySQL DATETIME.
        if ($discountEndsAt !== '' && strpos($discountEndsAt, 'T') !== false) {
            $discountEndsAt = str_replace('T', ' ', $discountEndsAt) . ':00';
        }

        if ($id <= 0 || $name === '') {
            flash_set('error', 'Invalid update.');
            redirect_to('super-admin/categories');
        }
        db_exec($db,
            'UPDATE categories
                SET name = :n, sort_order = :o, is_active = :a,
                    discount_pct = :dp, discount_label = :dl, discount_ends_at = :de
              WHERE id = :id', [
            'n'  => $name,
            'o'  => $sortOrder,
            'a'  => $isActive,
            'dp' => $discountPct,
            'dl' => $discountLabel !== '' ? $discountLabel : null,
            'de' => $discountEndsAt !== '' ? $discountEndsAt : null,
            'id' => $id,
        ]);
        $img = $saveCategoryImage($id);
        if (is_string($img) && str_starts_with($img, 'ERR:')) {
            flash_set('error', 'Category updated but image upload failed: ' . substr($img, 4));
        } else {
            if (is_string($img)) {
                db_exec($db, 'UPDATE categories SET image_path = :p WHERE id = :id', ['p' => $img, 'id' => $id]);
            }
            flash_set('success', 'Category updated.');
        }
        redirect_to('super-admin/categories');
    }

    if ($action === 'remove_image') {
        $id = (int)post_string('id');
        if ($id > 0) {
            db_exec($db, 'UPDATE categories SET image_path = NULL WHERE id = :id', ['id' => $id]);
            flash_set('success', 'Category image removed.');
        }
        redirect_to('super-admin/categories');
    }

    flash_set('error', 'Unknown action.');
    redirect_to('super-admin/categories');
}

$categories = db_fetch_all($db,
    "SELECT c.*, (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id) AS product_count
     FROM categories c
     ORDER BY c.sort_order ASC, c.id ASC"
);

$content = function () use ($categories) {
    require __DIR__ . '/../../views/partials/super-admin-nav.php';
    $success = flash_get('success');
    $error   = flash_get('error');
    ?>
    <div class="container">
      <h1 class="h4 mb-3">Categories</h1>
      <p class="text-muted small">Sellers pick from this list when adding products. Disabling a category hides it from buyer-facing navigation but keeps existing products intact.</p>

      <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
      <?php if ($error):   ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

      <div class="row g-3 mb-3">
        <div class="col-lg-5 col-xl-4">
          <div class="bg-white border rounded-3 p-3">
            <h2 class="h6 mb-3">Add category</h2>
            <form method="post" class="d-grid gap-2" enctype="multipart/form-data">
              <input type="hidden" name="action" value="create">
              <div>
                <label class="form-label small">Name</label>
                <input class="form-control form-control-sm" name="name" required>
              </div>
              <div>
                <label class="form-label small">Sort order</label>
                <input class="form-control form-control-sm" name="sort_order" type="number" placeholder="auto">
              </div>
              <div>
                <label class="form-label small">Image (optional)</label>
                <input class="form-control form-control-sm" name="image" type="file" accept="image/jpeg,image/png,image/webp">
                <small class="text-muted">jpg / png / webp · any size · max 10 MB</small>
              </div>
              <button class="btn btn-sm btn-mm" type="submit">Add</button>
            </form>
          </div>
        </div>
      </div>

      <div class="bg-white border rounded-3 table-responsive">
            <table class="table table-sm mb-0 align-middle">
              <thead class="table-light">
                <tr>
                  <th>Image</th>
                  <th>Name</th>
                  <th class="text-center" style="min-width:90px;">Sort</th>
                  <th class="text-center">Products</th>
                  <th class="text-center">Active</th>
                  <th class="text-end"></th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($categories as $c): ?>
                  <tr>
                    <form method="post" class="d-contents" enctype="multipart/form-data">
                      <input type="hidden" name="action" value="update">
                      <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                      <td style="width:130px;">
                        <?php if (!empty($c['image_path'])): ?>
                          <img src="<?= e((string)$c['image_path']) ?>" alt="" style="width:60px;height:60px;object-fit:cover;border-radius:6px;border:1px solid #e5e5e5;">
                          <button class="btn btn-link btn-sm text-danger p-0 d-block mt-1" formaction="?p=super-admin/categories" name="action" value="remove_image" type="submit" onclick="return confirm('Remove this image?')" style="font-size:11px;">Remove</button>
                        <?php else: ?>
                          <div style="width:60px;height:60px;background:#f5f5f5;border-radius:6px;display:flex;align-items:center;justify-content:center;color:#bbb;font-size:11px;">No image</div>
                        <?php endif; ?>
                        <input class="form-control form-control-sm mt-1" name="image" type="file" accept="image/*" style="font-size:11px;">
                      </td>
                      <td style="min-width:160px;"><input class="form-control form-control-sm" name="name" value="<?= e((string)$c['name']) ?>" required></td>
                      <?php
                        // Category-level discount is managed via Coupons ("Expires on"), so
                        // the % off / Sale label / Ends at columns were removed from this
                        // screen. Preserve any existing values as hidden fields so saving a
                        // category here does not wipe discount data already in the DB.
                        $dp     = (float)($c['discount_pct'] ?? 0);
                        $dl     = (string)($c['discount_label'] ?? '');
                        $dEnds  = (string)($c['discount_ends_at'] ?? '');
                        $dEndsInput = $dEnds !== '' ? str_replace(' ', 'T', substr($dEnds, 0, 16)) : '';
                      ?>
                      <td class="text-center" style="width:90px;">
                        <input class="form-control form-control-sm text-center" name="sort_order" type="number" value="<?= (int)$c['sort_order'] ?>">
                        <input type="hidden" name="discount_pct" value="<?= $dp > 0 ? rtrim(rtrim(number_format($dp, 2, '.', ''), '0'), '.') : '0' ?>">
                        <input type="hidden" name="discount_label" value="<?= e($dl) ?>">
                        <input type="hidden" name="discount_ends_at" value="<?= e($dEndsInput) ?>">
                      </td>
                      <td class="text-center"><?= (int)$c['product_count'] ?></td>
                      <td class="text-center" style="min-width:110px;">
                        <select class="form-select form-select-sm" name="is_active">
                          <option value="1" <?= ((int)$c['is_active'] === 1) ? 'selected' : '' ?>>Active</option>
                          <option value="0" <?= ((int)$c['is_active'] === 0) ? 'selected' : '' ?>>Hidden</option>
                        </select>
                      </td>
                      <td class="text-end"><button class="btn btn-sm btn-outline-primary" type="submit">Save</button></td>
                    </form>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
      </div>
    </div>
    <?php
};

require __DIR__ . '/../../views/layout.php';
