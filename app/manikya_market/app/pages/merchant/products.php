<?php

declare(strict_types=1);

auth_require_role('merchant');

$title = 'Products';
$merchantId = (int)auth_user_id();
$merchantStatus = merchant_profile_status($db, $merchantId) ?? 'pending';

function _ensure_merchant_owns_product(PDO $db, int $productId, int $merchantId): bool
{
    $row = db_fetch_one($db, 'SELECT merchant_id FROM products WHERE id = :id LIMIT 1', ['id' => $productId]);
    return $row && (int)$row['merchant_id'] === $merchantId;
}

// Handle toggle status
if (request_method() === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_status') {
    $productId = (int)($_POST['product_id'] ?? 0);
    if ($productId > 0 && _ensure_merchant_owns_product($db, $productId, $merchantId)) {
        $product = db_fetch_one($db, 'SELECT is_active FROM products WHERE id = :id LIMIT 1', ['id' => $productId]);
        if ($product) {
            $newStatus = (int)$product['is_active'] === 1 ? 0 : 1;
            db_exec($db, 'UPDATE products SET is_active = :status WHERE id = :id', ['status' => $newStatus, 'id' => $productId]);
            flash_set('success', $newStatus ? 'Product activated' : 'Product hidden');
        }
        redirect_to('merchant/products');
    }
}

// Handle delete
if (request_method() === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $productId = (int)($_POST['product_id'] ?? 0);
    if ($productId > 0 && _ensure_merchant_owns_product($db, $productId, $merchantId)) {
        try {
            db_exec($db, 'UPDATE order_items SET product_id = NULL WHERE product_id = :id', ['id' => $productId]);
            try { db_exec($db, 'DELETE FROM inventory_movements WHERE product_id = :id', ['id' => $productId]); } catch (Throwable $e) {}
            try { db_exec($db, 'DELETE FROM inventory WHERE product_id = :id', ['id' => $productId]); } catch (Throwable $e) {}
            db_exec($db, 'DELETE FROM products WHERE id = :id', ['id' => $productId]);
            flash_set('success', 'Product deleted successfully');
        } catch (Throwable $t) {
            flash_set('error', 'Failed to delete product: ' . $t->getMessage());
        }
        redirect_to('merchant/products');
    }
}

$products = db_fetch_all($db,
    'SELECT p.*, c.name AS category_name
     FROM products p
     LEFT JOIN categories c ON c.id = p.category_id
     WHERE p.merchant_id = :mid
     ORDER BY p.created_at DESC',
    ['mid' => $merchantId]
);

$content = function () use ($products, $merchantStatus) {
    $success = flash_get('success');
    $error = flash_get('error');
    $canAdd = $merchantStatus === 'approved';
    ?>
    <div class="container py-4">
      <?php if ($merchantStatus !== 'approved'): ?>
        <div class="alert alert-<?= $merchantStatus === 'disabled' ? 'danger' : 'warning' ?>">
          <strong><?= e(ucfirst($merchantStatus)) ?>:</strong>
          <?= $merchantStatus === 'disabled'
              ? 'Your merchant account has been disabled. Please contact support.'
              : 'Your application is awaiting super-admin approval. You can browse the dashboard, but listing products is locked until approved.' ?>
        </div>
      <?php endif; ?>
      <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show"><?= e($success) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
      <?php endif; ?>
      <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show"><?= e($error) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
      <?php endif; ?>

      <div class="d-flex align-items-center justify-content-between mb-3" style="flex-direction:row!important;">
        <h1 class="h5 mb-0 d-flex align-items-center gap-2"><i data-lucide="package" class="mm-icon"></i> My Products</h1>
        <?php if ($canAdd): ?>
          <a class="btn btn-mm btn-sm d-inline-flex align-items-center gap-1" href="?p=merchant/product-add"><i data-lucide="plus" class="mm-icon"></i> Add</a>
        <?php else: ?>
          <button class="btn btn-mm btn-sm d-inline-flex align-items-center gap-1" disabled title="Approval required"><i data-lucide="plus" class="mm-icon"></i> Add</button>
        <?php endif; ?>
      </div>

      <?php if (!$products): ?>
        <div class="bg-white border rounded-4 p-4 mm-card text-center">
          <i data-lucide="inbox" class="mm-icon mb-2" style="width:40px;height:40px;color:#ccc;"></i>
          <div class="text-muted">No products yet. Click "+ Add" to create your first product.</div>
        </div>
      <?php else: ?>
        <div class="row g-3">
          <?php foreach ($products as $p):
            $isActive = (int)$p['is_active'] === 1;
          ?>
            <div class="col-12 col-md-6 col-xl-4">
              <div class="bg-white border rounded-4 p-0 mm-card h-100 d-flex flex-column<?= !$isActive ? ' opacity-75' : '' ?>" style="overflow:hidden;">
                <!-- Product Image -->
                <div style="height:160px;background:#f8f5f0;position:relative;">
                  <?php if (!empty($p['image_path'])): ?>
                    <img src="<?= e((string)$p['image_path']) ?>" alt="<?= e((string)$p['name']) ?>" style="width:100%;height:100%;object-fit:cover;">
                  <?php else: ?>
                    <div class="d-flex align-items-center justify-content-center h-100"><i data-lucide="image" style="width:40px;height:40px;color:#ccc;"></i></div>
                  <?php endif; ?>
                  <!-- Category badge -->
                  <?php if (!empty($p['category_name'])): ?>
                    <span class="badge bg-dark" style="position:absolute;top:10px;left:10px;font-size:11px;opacity:0.85;"><?= e((string)$p['category_name']) ?></span>
                  <?php endif; ?>
                  <!-- Part code -->
                  <?php if (!empty($p['part_code'])): ?>
                    <span class="badge bg-light text-dark border" style="position:absolute;top:10px;right:10px;font-size:11px;"><?= e((string)$p['part_code']) ?></span>
                  <?php endif; ?>
                </div>

                <!-- Product Info -->
                <div class="p-3 flex-grow-1 d-flex flex-column">
                  <div class="fw-semibold mb-1" style="font-size:1rem;"><?= e((string)$p['name']) ?></div>
                  <?php if (!empty($p['description'])): ?>
                    <div class="text-muted small mb-2 text-truncate" style="max-width:100%;"><?= e((string)$p['description']) ?></div>
                  <?php endif; ?>

                  <!-- Price & Stock Row -->
                  <?php
                    $pSoldAs = (string)($p['sold_as'] ?? 'bulk');
                    $pPackG  = (float)($p['pack_size_grams'] ?? 0);
                    $pUnit = product_unit_label($p['unit'] ?? 'kg');
                    $pUnitUpper = strtoupper($pUnit);
                    if ($pSoldAs === 'packet') {
                        $priceLabel = 'PRICE/PACKET';
                        $stockLabel = 'PACKETS';
                        $stockUnit  = ($pPackG > 0 ? ' × ' . rtrim(rtrim(number_format($pPackG, 2, '.', ''), '0'), '.') . 'g' : '');
                    } else {
                        $priceLabel = 'PRICE/' . $pUnitUpper;
                        $stockLabel = 'STOCK';
                        $stockUnit  = ' ' . $pUnit;
                    }
                  ?>
                  <div class="d-flex gap-3 mb-3" style="flex-direction:row!important;">
                    <div>
                      <div class="text-muted" style="font-size:11px;"><?= e($priceLabel) ?></div>
                      <div class="fw-semibold" style="color:#E07800;">₹<?= e((string)$p['price_per_kg']) ?></div>
                    </div>
                    <div>
                      <div class="text-muted" style="font-size:11px;"><?= e($stockLabel) ?></div>
                      <div class="fw-semibold"><?= e((string)$p['stock_kg']) ?><?= e($stockUnit) ?></div>
                    </div>
                    <div>
                      <div class="text-muted" style="font-size:11px;">SHIPPING</div>
                      <div class="fw-semibold">₹<?= e((string)$p['shipping_rate']) ?><?= $p['shipping_type'] === 'per_kg' ? '/' . ($pSoldAs === 'packet' ? 'kg' : e($pUnit)) : '' ?></div>
                    </div>
                  </div>

                  <!-- Status Toggle + Actions -->
                  <div class="mt-auto pt-2 border-top d-flex align-items-center justify-content-between" style="flex-direction:row!important;">
                    <!-- Toggle Switch -->
                    <form method="POST" action="?p=merchant/products" class="d-flex align-items-center gap-2" style="flex-direction:row!important;">
                      <input type="hidden" name="action" value="toggle_status">
                      <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
                      <button type="submit" class="mm-toggle-btn" title="<?= $isActive ? 'Hide product' : 'Activate product' ?>">
                        <span class="mm-toggle <?= $isActive ? 'active' : '' ?>">
                          <span class="mm-toggle-knob"></span>
                        </span>
                      </button>
                      <span class="small <?= $isActive ? 'text-success' : 'text-muted' ?> fw-semibold"><?= $isActive ? 'Active' : 'Hidden' ?></span>
                    </form>

                    <!-- Action Buttons -->
                    <div class="d-flex gap-1" style="flex-direction:row!important;">
                      <a class="btn btn-outline-secondary btn-sm" href="?p=merchant/product-edit&id=<?= (int)$p['id'] ?>" title="Edit">
                        <i data-lucide="pencil" class="mm-icon"></i>
                      </a>
                      <form method="POST" action="?p=merchant/products" style="display:inline;" onsubmit="return confirm(<?= e(json_encode('Delete ' . $p['name'] . '? This cannot be undone.')) ?>)">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
                        <button type="submit" class="btn btn-outline-danger btn-sm" title="Delete">
                          <i data-lucide="trash-2" class="mm-icon"></i>
                        </button>
                      </form>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <style>
      /* Toggle Switch */
      .mm-toggle-btn {
        background: none;
        border: none;
        padding: 0;
        cursor: pointer;
        outline: none;
      }
      .mm-toggle {
        display: inline-block;
        width: 40px;
        height: 22px;
        background: #ccc;
        border-radius: 11px;
        position: relative;
        transition: background 0.2s ease;
      }
      .mm-toggle.active {
        background: #2E7D32;
      }
      .mm-toggle-knob {
        display: block;
        width: 18px;
        height: 18px;
        background: #fff;
        border-radius: 50%;
        position: absolute;
        top: 2px;
        left: 2px;
        transition: transform 0.2s ease;
        box-shadow: 0 1px 3px rgba(0,0,0,0.2);
      }
      .mm-toggle.active .mm-toggle-knob {
        transform: translateX(18px);
      }
      /* Product card hover */
      .col-12.col-md-6.col-xl-4 .mm-card {
        transition: box-shadow 0.2s ease, transform 0.15s ease;
      }
      .col-12.col-md-6.col-xl-4 .mm-card:hover {
        box-shadow: 0 6px 20px rgba(255, 140, 0, 0.15);
        transform: translateY(-2px);
      }
    </style>

    <script>if (window.lucide) window.lucide.createIcons();</script>
    <?php
};

require __DIR__ . '/../../views/layout.php';
