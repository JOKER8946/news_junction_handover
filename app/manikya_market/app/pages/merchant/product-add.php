<?php

declare(strict_types=1);

require_approved_merchant($db);

$title = 'Add Product';
$merchantId = (int)auth_user_id();
$basePath = app_base_path($config);

// Gallery images + intro video live on the products table (auto-created).
product_media_ensure_columns($db);

$categories = db_fetch_all($db, 'SELECT id, name FROM categories WHERE is_active = 1 ORDER BY sort_order, name') ?: [];

if (request_method() === 'POST') {
    if (!product_meta_ready($db) || !inventory_tables_ready($db)) {
        flash_set('error', 'Please run setup.php again to create product fields and inventory tables.');
        redirect_to('merchant/products');
    }

    $name = post_string('name');
    $categoryId = (int)($_POST['category_id'] ?? 0);
    $productType = mb_substr(post_string('product_type'), 0, 255);
    $partCode = strtoupper(post_string('part_code'));
    $description = post_string('description');
    $pricePerKg = (float)($_POST['price_per_kg'] ?? 0);
    $stockKg = (float)($_POST['stock_kg'] ?? 0);
    $unit = post_string('unit') ?: 'kg';
    $allowedUnits = ['kg', 'gm', 'piece', 'dozen', 'litre', 'bunch'];
    if (!in_array($unit, $allowedUnits, true)) { $unit = 'kg'; }

    // Bulk vs packet. Packet products are sold as fixed packs (e.g. 500g pack
    // at ₹450, buyer picks N packets). Unit is auto-flipped to 'piece' so the
    // "PRICE/PIECE" label makes sense across the app.
    $soldAs = post_string('sold_as') === 'packet' ? 'packet' : 'bulk';
    $packSizeGrams = (float)($_POST['pack_size_grams'] ?? 0);
    if ($soldAs === 'packet') {
        if ($packSizeGrams <= 0) {
            flash_set('error', 'Packet size (grams) is required for packaged products.');
            redirect_to('merchant/product-add');
        }
        $unit = 'piece';
    } else {
        $packSizeGrams = 0;
    }

    $shippingType = post_string('shipping_type') ?: 'flat';
    $shippingRate = (float)($_POST['shipping_rate'] ?? 0);
    $isActive = (int)($_POST['is_active'] ?? 0) === 1 ? 1 : 0;

    if ($name === '') {
        flash_set('error', 'Name is required');
        redirect_to('merchant/product-add');
    }
    if ($categoryId <= 0) {
        flash_set('error', 'Please pick a category.');
        redirect_to('merchant/product-add');
    }
    $catCheck = db_fetch_one($db, 'SELECT id FROM categories WHERE id = :id AND is_active = 1 LIMIT 1', ['id' => $categoryId]);
    if (!$catCheck) {
        flash_set('error', 'Selected category is not available.');
        redirect_to('merchant/product-add');
    }

    if (!in_array($shippingType, ['flat', 'per_kg'], true)) {
        $shippingType = 'flat';
    }

    $imagePath = null;
    if (isset($_FILES['image']) && is_array($_FILES['image']) && (int)($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
        $tmp = (string)($_FILES['image']['tmp_name'] ?? '');
        $orig = (string)($_FILES['image']['name'] ?? '');

        $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
        if ($ext === 'jpe') { $ext = 'jpeg'; }
        $allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'bmp', 'svg', 'tif', 'tiff', 'ico', 'heic', 'heif', 'avif'];
        $mime = '';
        if (function_exists('mime_content_type') && is_file($tmp)) {
            $mime = (string)@mime_content_type($tmp);
        }
        $isImageMime = ($mime !== '' && stripos($mime, 'image/') === 0);
        if (!in_array($ext, $allowedExts, true) && !$isImageMime) {
            flash_set('error', 'Invalid image type');
            redirect_to('merchant/product-add');
        }
        if ($ext === '' && $isImageMime) {
            $ext = strtolower(trim(substr($mime, 6)));
            $ext = preg_replace('/[^a-z0-9]/', '', $ext) ?: 'img';
        }

        $uploadDir = __DIR__ . '/../../../uploads/products';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $newName = 'p_' . date('YmdHis') . '_' . random_int(1000, 9999) . '.' . $ext;
        $dest = $uploadDir . '/' . $newName;

        if (!move_uploaded_file($tmp, $dest)) {
            flash_set('error', 'Image upload failed');
            redirect_to('merchant/product-add');
        }

        $imagePath = $basePath . '/uploads/products/' . $newName;
    }

    if ($partCode !== '') {
        $existing = db_fetch_one($db, 'SELECT id FROM products WHERE part_code = :part_code LIMIT 1', ['part_code' => $partCode]);
        if ($existing) {
            flash_set('error', 'Part code already exists');
            redirect_to('merchant/product-add');
        }
    }

    // Extra gallery images + optional short intro video (product page only).
    $galleryList = product_gallery_save([], $basePath, 8);
    $galleryJson = !empty($galleryList) ? json_encode(array_values($galleryList)) : null;

    [$videoPath, $videoErr] = product_video_save(null, $basePath);
    if ($videoErr !== null) {
        flash_set('error', $videoErr);
        redirect_to('merchant/product-add');
    }

    db_exec($db, 'INSERT INTO products (merchant_id, category_id, name, product_type, part_code, description, image_path, gallery_images, video_path, price_per_kg, unit, sold_as, pack_size_grams, shipping_type, shipping_rate, stock_kg, is_active, created_at) VALUES (:merchant_id, :category_id, :name, :product_type, :part_code, :description, :image_path, :gallery_images, :video_path, :price_per_kg, :unit, :sold_as, :pack_size_grams, :shipping_type, :shipping_rate, 0, :is_active, NOW())', [
        'merchant_id' => $merchantId,
        'category_id' => $categoryId,
        'name' => $name,
        'product_type' => $productType,
        'part_code' => ($partCode !== '' ? $partCode : null),
        'description' => $description,
        'image_path' => $imagePath,
        'gallery_images' => $galleryJson,
        'video_path' => $videoPath,
        'price_per_kg' => $pricePerKg,
        'unit' => $unit,
        'sold_as' => $soldAs,
        'pack_size_grams' => $soldAs === 'packet' ? $packSizeGrams : null,
        'shipping_type' => $shippingType,
        'shipping_rate' => $shippingRate,
        'is_active' => $isActive,
    ]);

    $productId = (int)$db->lastInsertId();

    if ($partCode === '') {
        $prefix = preg_replace('/[^A-Z0-9]/', '', strtoupper(substr($productType, 0, 3)));
        if ($prefix === '') {
            $prefix = 'PRD';
        }
        $generated = $prefix . '-' . str_pad((string)$productId, 5, '0', STR_PAD_LEFT);
        $exists = db_fetch_one($db, 'SELECT id FROM products WHERE part_code = :part_code LIMIT 1', ['part_code' => $generated]);
        if ($exists) {
            $generated = $prefix . '-' . str_pad((string)$productId, 5, '0', STR_PAD_LEFT) . '-' . random_int(10, 99);
        }
        db_exec($db, 'UPDATE products SET part_code = :part_code WHERE id = :id', ['part_code' => $generated, 'id' => $productId]);
    }

    inventory_ensure_product($db, $productId);
    if ($stockKg > 0) {
        inventory_inward($db, $productId, $stockKg, 'Initial stock');
    } else {
        inventory_sync_product_stock($db, $productId);
    }

    redirect_to('merchant/products');
}

$content = function () use ($categories) {
    $error = flash_get('error');
    ?>
    <div class="container py-4" style="max-width: 860px;">
      <div class="d-flex align-items-center justify-content-between mb-3">
        <h1 class="h5 mb-0 d-flex align-items-center gap-2"><i data-lucide="plus" class="mm-icon"></i> Add Product</h1>
        <a class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1" href="?p=merchant/products"><i data-lucide="arrow-left" class="mm-icon"></i> Back</a>
      </div>

      <?php if ($error): ?>
        <div class="alert alert-danger"><?= e($error) ?></div>
      <?php endif; ?>

      <div class="bg-white border rounded-4 p-4 mm-card">
        <form method="post" enctype="multipart/form-data" class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Name *</label>
            <input class="form-control" name="name" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Category *</label>
            <select class="form-select" name="category_id" required>
              <option value="">— Choose a category —</option>
              <?php foreach ($categories as $c): ?>
                <option value="<?= (int)$c['id'] ?>"><?= e((string)$c['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Type / sub-type <span class="text-muted">(optional)</span></label>
            <input class="form-control" name="product_type" maxlength="255" placeholder="e.g. Men's shirt, 24K, 32-inch TV">
          </div>
          <div class="col-md-6">
            <label class="form-label">Part / SKU code</label>
            <input class="form-control" name="part_code" placeholder="Auto-generated if blank">
          </div>
          <div class="col-md-6">
            <label class="form-label">Photo <span class="text-muted small">— main image</span></label>
            <input class="form-control" type="file" name="image" accept="image/*">
          </div>

          <!-- Additional images (Amazon-style gallery on the product page) -->
          <div class="col-12">
            <label class="form-label">Additional images <span class="text-muted small">— shown as a gallery on the product page (up to 8)</span></label>
            <input class="form-control" type="file" name="gallery_images[]" accept="image/*" multiple>
          </div>

          <!-- Product intro video -->
          <div class="col-12">
            <label class="form-label">Product video <span class="text-muted small">— MP4 / WebM, 20–30 seconds, max 5 MB</span></label>
            <input class="form-control" type="file" name="video" accept="video/*" id="productVideo">
            <div id="videoError" class="text-danger small mt-1" style="display:none;"></div>
          </div>

          <div class="col-12">
            <label class="form-label">Description</label>
            <textarea class="form-control" name="description" rows="2"></textarea>
          </div>
          <?php $unitOptions = ['kg' => 'Kg', 'gm' => 'Gram', 'piece' => 'Piece', 'dozen' => 'Dozen', 'litre' => 'Litre', 'bunch' => 'Bunch']; ?>

          <div class="col-12">
            <label class="form-label fw-semibold">How is this sold?</label>
            <div class="p-3 border rounded bg-light-subtle">
              <div class="form-check">
                <input class="form-check-input" type="radio" name="sold_as" id="sold_bulk" value="bulk" checked>
                <label class="form-check-label" for="sold_bulk">
                  <strong>Loose / by weight</strong>
                  <div class="small text-muted">Buyer picks any weight (e.g. 500 g of pickle, 2 kg of rice).</div>
                </label>
              </div>
              <div class="form-check mt-2">
                <input class="form-check-input" type="radio" name="sold_as" id="sold_packet" value="packet">
                <label class="form-check-label" for="sold_packet">
                  <strong>Packaged / fixed pack</strong>
                  <div class="small text-muted">Buyer picks how many packets (e.g. 1, 2, 3 × 500 g Millet Mix pack).</div>
                </label>
              </div>

              <div id="packetFields" class="mt-3 pt-3 border-top" style="display:none;">
                <div class="row g-3">
                  <div class="col-md-6">
                    <label class="form-label small">Packet size (grams) *</label>
                    <input class="form-control" type="number" step="0.01" min="0" name="pack_size_grams" placeholder="e.g. 500">
                    <div class="form-text">Total grams inside one packet. Used for shipping weight.</div>
                  </div>
                  <div class="col-md-6 d-flex align-items-end">
                    <div class="small text-muted">
                      When packet mode is on, the Unit is set to <strong>Piece</strong> automatically. The Price field means <strong>price per packet</strong>, Stock is <strong>packets in stock</strong>.
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="col-md-4" id="unitCol">
            <label class="form-label">Unit</label>
            <select class="form-select" name="unit">
              <?php foreach ($unitOptions as $val => $label): ?>
                <option value="<?= e($val) ?>"><?= e($label) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label" id="priceLabel">Price</label>
            <input class="form-control" type="number" step="0.01" min="0" name="price_per_kg" value="0" required>
          </div>
          <div class="col-md-4">
            <label class="form-label" id="stockLabel">Stock</label>
            <input class="form-control" type="number" step="0.01" min="0" name="stock_kg" value="0" required>
          </div>
          <div class="col-md-4">
            <label class="form-label">Shipping Type</label>
            <select class="form-select" name="shipping_type">
              <option value="flat">Flat per order</option>
              <option value="per_kg">Per Kg</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">Shipping Rate</label>
            <input class="form-control" type="number" step="0.01" min="0" name="shipping_rate" value="0" required>
          </div>
          <div class="col-md-4 d-flex align-items-end">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" checked>
              <label class="form-check-label" for="is_active">Active</label>
            </div>
          </div>
          <div class="col-12 d-grid">
            <button class="btn btn-mm" type="submit">Save</button>
          </div>
        </form>
      </div>
    </div>

    <script>
    (function () {
      var radios = document.querySelectorAll('input[name="sold_as"]');
      var pktBox = document.getElementById('packetFields');
      var unitCol = document.getElementById('unitCol');
      var priceLabel = document.getElementById('priceLabel');
      var stockLabel = document.getElementById('stockLabel');
      var pktSizeInput = document.querySelector('input[name="pack_size_grams"]');
      function apply() {
        var pkt = document.getElementById('sold_packet').checked;
        if (pktBox)     pktBox.style.display     = pkt ? '' : 'none';
        if (unitCol)    unitCol.style.display    = pkt ? 'none' : '';
        if (priceLabel) priceLabel.textContent   = pkt ? 'Price per packet (₹)' : 'Price';
        if (stockLabel) stockLabel.textContent   = pkt ? 'Stock (packets)' : 'Stock';
        if (pktSizeInput) pktSizeInput.required  = pkt;
      }
      radios.forEach(function (r) { r.addEventListener('change', apply); });
      apply();
    })();

    /* Validate the product video in-browser: ≤5 MB and 20–30 seconds long. */
    (function () {
      var input = document.getElementById('productVideo');
      var errBox = document.getElementById('videoError');
      if (!input) return;
      var form = input.closest('form');
      var videoOk = true;
      function showErr(msg) { if (errBox) { errBox.textContent = msg || ''; errBox.style.display = msg ? '' : 'none'; } }
      input.addEventListener('change', function () {
        videoOk = true; showErr('');
        var f = input.files && input.files[0];
        if (!f) return;
        if (f.size > 5 * 1024 * 1024) {
          videoOk = false; input.value = '';
          showErr('Video is too large (' + (f.size / 1048576).toFixed(1) + ' MB). Maximum is 5 MB.');
          return;
        }
        var url = URL.createObjectURL(f);
        var probe = document.createElement('video');
        probe.preload = 'metadata';
        probe.onloadedmetadata = function () {
          URL.revokeObjectURL(url);
          var d = probe.duration || 0;
          if (d < 20 || d > 30) {
            videoOk = false; input.value = '';
            showErr('Video must be 20–30 seconds long (this one is ' + Math.round(d) + 's).');
          }
        };
        probe.onerror = function () {
          URL.revokeObjectURL(url); videoOk = false; input.value = '';
          showErr('Could not read this video file. Please try another.');
        };
        probe.src = url;
      });
      if (form) form.addEventListener('submit', function (e) {
        if (!videoOk) { e.preventDefault(); showErr(errBox ? errBox.textContent : 'Please fix the video before saving.'); }
      });
    })();
    </script>
    <?php
};

require __DIR__ . '/../../views/layout.php';
