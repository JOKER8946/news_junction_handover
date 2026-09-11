<?php

declare(strict_types=1);

auth_require_role('super_admin');

$title = 'Discounts';

if (request_method() === 'POST') {
    $action = post_string('action');

    if ($action === 'create' || $action === 'update') {
        $editId = $action === 'update' ? (int)post_string('id') : 0;
        if ($action === 'update' && $editId <= 0) {
            flash_set('error', 'Invalid discount id.');
            redirect_to('super-admin/coupons');
        }

        $code = strtoupper(trim(post_string('code')));
        $code = preg_replace('/[^A-Z0-9_-]/', '', $code) ?? '';
        if ($code === '') {
            flash_set('error', 'Code is required (letters, digits, underscore, hyphen).');
            redirect_to($action === 'update' ? 'super-admin/coupons&edit=' . $editId : 'super-admin/coupons');
        }
        $type   = post_string('discount_type') === 'fixed' ? 'fixed' : 'percent';
        $value  = (float)post_string('discount_value');
        $minOrd = (float)post_string('min_order_value');
        $maxDis = post_string('max_discount') !== '' ? (float)post_string('max_discount') : null;
        $usage  = post_string('usage_limit') !== '' ? (int)post_string('usage_limit') : null;
        $expires= post_string('expires_at');
        $desc   = post_string('description');
        // Product scope: 'all' = platform-wide, N = only for that product.
        $scope     = post_string('scope');
        $productId = (int)post_string('product_id');
        if ($scope !== 'product' || $productId <= 0) {
            $productId = 0;   // 0 means NULL in DB (platform-wide)
        } else {
            // Validate that the product actually exists.
            $chk = db_fetch_one($db, 'SELECT id FROM products WHERE id = :id LIMIT 1', ['id' => $productId]);
            if (!$chk) {
                flash_set('error', 'Selected product does not exist.');
                redirect_to($action === 'update' ? 'super-admin/coupons&edit=' . $editId : 'super-admin/coupons');
            }
        }
        if ($value <= 0) {
            flash_set('error', 'Discount value must be greater than zero.');
            redirect_to($action === 'update' ? 'super-admin/coupons&edit=' . $editId : 'super-admin/coupons');
        }

        try {
            if ($action === 'create') {
                db_exec($db, '
                    INSERT INTO coupons (code, description, discount_type, discount_value, min_order_value, max_discount, usage_limit, expires_at, product_id, is_active, created_at)
                    VALUES (:c, :d, :t, :v, :min, :max, :u, :exp, :pid, 1, NOW())
                ', [
                    'c' => $code, 'd' => $desc ?: null, 't' => $type,
                    'v' => $value, 'min' => $minOrd, 'max' => $maxDis,
                    'u' => $usage, 'exp' => $expires !== '' ? $expires . ' 23:59:59' : null,
                    'pid' => $productId > 0 ? $productId : null,
                ]);
                flash_set('success', 'Discount ' . $code . ' created.');
            } else {
                // Update existing coupon. is_active is preserved (managed via Enable/Disable).
                db_exec($db, '
                    UPDATE coupons
                       SET code = :c, description = :d, discount_type = :t, discount_value = :v,
                           min_order_value = :min, max_discount = :max, usage_limit = :u,
                           expires_at = :exp, product_id = :pid
                     WHERE id = :id
                ', [
                    'c' => $code, 'd' => $desc ?: null, 't' => $type,
                    'v' => $value, 'min' => $minOrd, 'max' => $maxDis,
                    'u' => $usage, 'exp' => $expires !== '' ? $expires . ' 23:59:59' : null,
                    'pid' => $productId > 0 ? $productId : null,
                    'id' => $editId,
                ]);
                flash_set('success', 'Discount ' . $code . ' updated.');
            }
        } catch (Throwable $t) {
            flash_set('error', $action === 'create'
                ? 'Could not create (code may already exist).'
                : 'Could not update (code may collide with another discount).');
        }
        redirect_to('super-admin/coupons');
    }

    if ($action === 'toggle') {
        $id = (int)post_string('id');
        db_exec($db, 'UPDATE coupons SET is_active = 1 - is_active WHERE id = :id', ['id' => $id]);
        flash_set('success', 'Discount updated.');
        redirect_to('super-admin/coupons');
    }

    if ($action === 'delete') {
        $id = (int)post_string('id');
        if ($id > 0) {
            db_exec($db, 'DELETE FROM coupons WHERE id = :id', ['id' => $id]);
            flash_set('success', 'Discount deleted.');
        }
        redirect_to('super-admin/coupons');
    }
}

$coupons = db_fetch_all($db,
    'SELECT c.*, p.name AS scoped_product_name, p.part_code AS scoped_product_code
     FROM coupons c
     LEFT JOIN products p ON p.id = c.product_id
     ORDER BY c.is_active DESC, c.created_at DESC'
) ?: [];

// Product list for the "Applies to" picker in the create/edit form.
$productPickList = db_fetch_all($db,
    'SELECT id, name, part_code FROM products WHERE is_active = 1 ORDER BY name'
) ?: [];

// ── Mode resolution ─────────────────────────────────────────────
// The left-hand form has two modes:
//   Create  — blank form (default, or optional ?product_id=X prefill)
//   Edit    — ?edit=X pre-fills the form from an existing coupon
$editId   = (int)($_GET['edit'] ?? 0);
$editRow  = null;
if ($editId > 0) {
    $editRow = db_fetch_one($db, 'SELECT * FROM coupons WHERE id = :id LIMIT 1', ['id' => $editId]) ?: null;
    if (!$editRow) $editId = 0;
}
$isEditMode = $editRow !== null;

// Pre-fill from ?product_id=X (Products page links here with this) — only in create mode.
$prefillProductId = 0;
$prefillProduct   = null;
if (!$isEditMode) {
    $prefillProductId = (int)($_GET['product_id'] ?? 0);
    if ($prefillProductId > 0) {
        $prefillProduct = db_fetch_one($db,
            'SELECT id, name, part_code FROM products WHERE id = :id LIMIT 1',
            ['id' => $prefillProductId]
        ) ?: null;
        if (!$prefillProduct) $prefillProductId = 0;
    }
}

// ── Form field defaults ─────────────────────────────────────────
if ($isEditMode) {
    $fCode       = (string)$editRow['code'];
    $fDesc       = (string)($editRow['description'] ?? '');
    $fType       = (string)($editRow['discount_type'] ?? 'percent');
    $fValue      = rtrim(rtrim(number_format((float)$editRow['discount_value'], 2, '.', ''), '0'), '.');
    $fMinOrder   = rtrim(rtrim(number_format((float)$editRow['min_order_value'], 2, '.', ''), '0'), '.');
    $fMaxDisc    = $editRow['max_discount'] !== null ? rtrim(rtrim(number_format((float)$editRow['max_discount'], 2, '.', ''), '0'), '.') : '';
    $fUsage      = $editRow['usage_limit'] !== null ? (string)(int)$editRow['usage_limit'] : '';
    $fExpires    = $editRow['expires_at'] ? substr((string)$editRow['expires_at'], 0, 10) : '';
    $fScopeAll   = empty($editRow['product_id']);
    $fProductId  = (int)($editRow['product_id'] ?? 0);
} else {
    $fCode       = $prefillProduct ? 'PRD-' . str_pad((string)$prefillProduct['id'], 5, '0', STR_PAD_LEFT) : '';
    $fDesc       = $prefillProduct ? (string)$prefillProduct['name'] : '';
    $fType       = 'percent';
    $fValue      = '';
    $fMinOrder   = '0';
    $fMaxDisc    = '';
    $fUsage      = '';
    $fExpires    = '';
    $fScopeAll   = !$prefillProduct;
    $fProductId  = $prefillProductId;
}

$content = function () use ($coupons, $productPickList, $isEditMode, $editRow, $prefillProduct,
                            $fCode, $fDesc, $fType, $fValue, $fMinOrder, $fMaxDisc, $fUsage, $fExpires,
                            $fScopeAll, $fProductId) {
    require __DIR__ . '/../../views/partials/super-admin-nav.php';
    $success = flash_get('success'); $error = flash_get('error');
    ?>
    <div class="container">
      <h1 class="h4 mb-3">Discounts</h1>
      <p class="text-muted small">Codes buyers can enter at checkout. The discount is computed on the cart subtotal and split proportionally across seller orders.</p>

      <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
      <?php if ($error):   ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

      <div class="row g-3">
        <div class="col-lg-4">
          <div class="bg-white border rounded-3 p-3">
            <h2 class="h6 mb-3 d-flex align-items-center justify-content-between">
              <span><?= $isEditMode ? 'Edit discount' : 'Create discount' ?></span>
              <?php if ($isEditMode): ?>
                <a class="small text-muted" href="?p=super-admin/coupons">Cancel</a>
              <?php endif; ?>
            </h2>

            <?php if (!$isEditMode && $prefillProduct): ?>
              <div class="alert alert-info small py-2 mb-3">
                Creating a discount for <strong><?= e((string)$prefillProduct['name']) ?></strong>
                (<?= e((string)($prefillProduct['part_code'] ?? '')) ?>).
                Buyers who type this code will only get the discount if they have this product in their cart.
              </div>
            <?php endif; ?>

            <form method="post" class="d-grid gap-2">
              <input type="hidden" name="action" value="<?= $isEditMode ? 'update' : 'create' ?>">
              <?php if ($isEditMode): ?>
                <input type="hidden" name="id" value="<?= (int)$editRow['id'] ?>">
              <?php endif; ?>

              <div>
                <label class="form-label small">Code *</label>
                <input class="form-control form-control-sm" name="code" required style="text-transform:uppercase;"
                       value="<?= e($fCode) ?>">
              </div>
              <div>
                <label class="form-label small">Description</label>
                <input class="form-control form-control-sm" name="description"
                       value="<?= e($fDesc) ?>">
              </div>

              <div class="border rounded p-2 mt-1" style="background:#f9fafb;">
                <label class="form-label small fw-semibold mb-1">Applies to</label>
                <div class="form-check">
                  <input class="form-check-input scope-radio" type="radio" name="scope" id="scope_all" value="all" <?= $fScopeAll ? 'checked' : '' ?>>
                  <label class="form-check-label small" for="scope_all">
                    <strong>Whole cart</strong> — any product qualifies.
                  </label>
                </div>
                <div class="form-check">
                  <input class="form-check-input scope-radio" type="radio" name="scope" id="scope_product" value="product" <?= $fScopeAll ? '' : 'checked' ?>>
                  <label class="form-check-label small" for="scope_product">
                    <strong>Specific product</strong> — discount only works on this product.
                  </label>
                </div>
                <div id="productPicker" class="mt-2" style="display:<?= $fScopeAll ? 'none' : '' ?>;">
                  <select class="form-select form-select-sm" name="product_id">
                    <option value="0">— pick a product —</option>
                    <?php foreach ($productPickList as $p): ?>
                      <option value="<?= (int)$p['id'] ?>" <?= $fProductId === (int)$p['id'] ? 'selected' : '' ?>>
                        <?= e((string)$p['name']) ?><?= !empty($p['part_code']) ? ' — ' . e((string)$p['part_code']) : '' ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>

              <div class="row g-2">
                <div class="col-6">
                  <label class="form-label small">Type</label>
                  <select class="form-select form-select-sm" name="discount_type">
                    <option value="percent" <?= $fType === 'percent' ? 'selected' : '' ?>>% off</option>
                    <option value="fixed"   <?= $fType === 'fixed'   ? 'selected' : '' ?>>₹ off</option>
                  </select>
                </div>
                <div class="col-6">
                  <label class="form-label small">Value *</label>
                  <input class="form-control form-control-sm" type="number" step="0.01" min="0.01" name="discount_value" required
                         value="<?= e($fValue) ?>">
                </div>
              </div>
              <div>
                <label class="form-label small">Min order value (₹)</label>
                <input class="form-control form-control-sm" type="number" step="0.01" min="0" name="min_order_value"
                       value="<?= e($fMinOrder) ?>">
              </div>
              <div>
                <label class="form-label small">Max discount cap (₹) <span class="text-muted">(optional)</span></label>
                <input class="form-control form-control-sm" type="number" step="0.01" min="0" name="max_discount"
                       value="<?= e($fMaxDisc) ?>">
              </div>
              <div>
                <label class="form-label small">Usage limit <span class="text-muted">(blank = unlimited)</span></label>
                <input class="form-control form-control-sm" type="number" min="1" name="usage_limit"
                       value="<?= e($fUsage) ?>">
              </div>
              <div>
                <label class="form-label small">Expires on <span class="text-muted">(blank = never)</span></label>
                <input class="form-control form-control-sm" type="date" name="expires_at"
                       value="<?= e($fExpires) ?>">
              </div>
              <button class="btn btn-sm btn-mm mt-2" type="submit">
                <?= $isEditMode ? 'Save changes' : 'Create' ?>
              </button>
            </form>
          </div>
        </div>

        <div class="col-lg-8">
          <div class="bg-white border rounded-3 overflow-hidden">
            <table class="table table-sm mb-0 align-middle">
              <thead class="table-light">
                <tr>
                  <th>Code</th>
                  <th>Applies to</th>
                  <th>Discount</th>
                  <th>Min order</th>
                  <th>Used / Limit</th>
                  <th>Expires</th>
                  <th>Active</th>
                  <th class="text-end"></th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($coupons)): ?>
                  <tr><td colspan="8" class="text-center text-muted py-4">No discounts yet.</td></tr>
                <?php else: foreach ($coupons as $c):
                  $isBeingEdited = $isEditMode && (int)$editRow['id'] === (int)$c['id'];
                ?>
                  <tr <?= $isBeingEdited ? 'style="background:#FEF3C7;"' : '' ?>>
                    <td><strong><?= e((string)$c['code']) ?></strong>
                      <?php if (!empty($c['description'])): ?>
                        <div class="small text-muted"><?= e((string)$c['description']) ?></div>
                      <?php endif; ?>
                    </td>
                    <td class="small">
                      <?php if (!empty($c['product_id'])): ?>
                        <span class="badge bg-info text-white">Product</span>
                        <div class="small mt-1"><?= e((string)($c['scoped_product_name'] ?? '(missing)')) ?></div>
                        <?php if (!empty($c['scoped_product_code'])): ?>
                          <div class="text-muted" style="font-size:11px;"><?= e((string)$c['scoped_product_code']) ?></div>
                        <?php endif; ?>
                      <?php else: ?>
                        <span class="badge bg-secondary">Whole cart</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <?php if ($c['discount_type'] === 'percent'): ?>
                        <?= e((string)$c['discount_value']) ?>% off
                        <?php if (!empty($c['max_discount'])): ?>
                          <div class="small text-muted">capped ₹<?= e((string)$c['max_discount']) ?></div>
                        <?php endif; ?>
                      <?php else: ?>
                        ₹<?= e((string)$c['discount_value']) ?> off
                      <?php endif; ?>
                    </td>
                    <td class="small">₹<?= e((string)$c['min_order_value']) ?></td>
                    <td class="small">
                      <?= (int)$c['used_count'] ?> /
                      <?= $c['usage_limit'] !== null ? (int)$c['usage_limit'] : '∞' ?>
                    </td>
                    <td class="small text-muted">
                      <?= $c['expires_at'] ? e(date('d M Y', strtotime((string)$c['expires_at']))) : 'never' ?>
                    </td>
                    <td>
                      <span class="badge bg-<?= ((int)$c['is_active'] === 1) ? 'success' : 'secondary' ?>">
                        <?= ((int)$c['is_active'] === 1) ? 'Active' : 'Disabled' ?>
                      </span>
                    </td>
                    <td class="text-end" style="white-space:nowrap;">
                      <a class="btn btn-sm btn-outline-primary" href="?p=super-admin/coupons&edit=<?= (int)$c['id'] ?>" title="Edit this discount">
                        <i data-lucide="pencil" style="width:12px;height:12px;"></i> Edit
                      </a>
                      <form method="post" class="d-inline">
                        <input type="hidden" name="action" value="toggle">
                        <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                        <button class="btn btn-sm btn-outline-secondary" type="submit">
                          <?= ((int)$c['is_active'] === 1) ? 'Disable' : 'Enable' ?>
                        </button>
                      </form>
                      <form method="post" class="d-inline" onsubmit="return confirm(<?= e(json_encode('Delete discount ' . $c['code'] . '? This cannot be undone.')) ?>);">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                        <button class="btn btn-sm btn-outline-danger" type="submit" title="Delete this discount">
                          <i data-lucide="trash-2" style="width:12px;height:12px;"></i>
                        </button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
    <script>if (window.lucide) window.lucide.createIcons();</script>
    <script>
    (function () {
      var picker = document.getElementById('productPicker');
      document.querySelectorAll('.scope-radio').forEach(function (r) {
        r.addEventListener('change', function () {
          if (!picker) return;
          picker.style.display = (r.checked && r.value === 'product') ? '' : 'none';
        });
      });
    })();
    </script>
    <?php
};

require __DIR__ . '/../../views/layout.php';
