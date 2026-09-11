<?php

declare(strict_types=1);

auth_require_role('super_admin');

$title = 'Products';

// Filters
$fCategory = (int)($_GET['category'] ?? 0);
$fSearch   = trim((string)($_GET['q'] ?? ''));

$where = [];
$binds = [];
if ($fCategory > 0) { $where[] = 'p.category_id = :cid'; $binds['cid'] = $fCategory; }
if ($fSearch !== '') {
    $where[] = '(p.name LIKE :q1 OR p.part_code LIKE :q2)';
    $binds['q1'] = '%' . $fSearch . '%';
    $binds['q2'] = '%' . $fSearch . '%';
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$categories = db_fetch_all($db, 'SELECT id, name FROM categories ORDER BY sort_order, name') ?: [];

$products = db_fetch_all($db,
    "SELECT p.id, p.name, p.part_code, p.price_per_kg, p.image_path, p.is_active,
            p.sold_as, p.pack_size_grams,
            p.category_id,
            c.name AS category_name,
            u.full_name AS merchant_name,
            mp.business_name AS merchant_business
       FROM products p
       LEFT JOIN categories c ON c.id = p.category_id
       LEFT JOIN users u ON u.id = p.merchant_id
       LEFT JOIN merchant_profile mp ON mp.merchant_user_id = p.merchant_id
       $whereSql
       ORDER BY p.created_at DESC",
    $binds
) ?: [];

// Coupons already scoped to each product (for the "Coupons for this product" column).
$productIds = array_map(fn($p) => (int)$p['id'], $products);
$couponsByProduct = [];
if ($productIds) {
    $placeholders = implode(',', array_fill(0, count($productIds), '?'));
    $rows = db_fetch_all($db,
        "SELECT id, code, product_id, discount_type, discount_value, is_active, expires_at
         FROM coupons
         WHERE product_id IN ($placeholders)",
        $productIds
    ) ?: [];
    foreach ($rows as $r) {
        $pid = (int)$r['product_id'];
        if (!isset($couponsByProduct[$pid])) $couponsByProduct[$pid] = [];
        $couponsByProduct[$pid][] = $r;
    }
}

$content = function () use ($products, $categories, $fCategory, $fSearch, $couponsByProduct) {
    require __DIR__ . '/../../views/partials/super-admin-nav.php';
?>
  <div class="container">
    <h1 class="h4 mb-1">Products</h1>
    <p class="text-muted small mb-3">
      Full catalogue across all sellers. To discount a specific product, click
      <em>Create discount</em> — the buyer enters that code at checkout and it only applies to this product.
    </p>

    <form method="get" class="row g-2 mb-3">
      <input type="hidden" name="p" value="super-admin/products">
      <div class="col-md-4">
        <input class="form-control form-control-sm" name="q" placeholder="Search name or part code" value="<?= e($fSearch) ?>">
      </div>
      <div class="col-md-3">
        <select class="form-select form-select-sm" name="category">
          <option value="0">All categories</option>
          <?php foreach ($categories as $c): ?>
            <option value="<?= (int)$c['id'] ?>" <?= (int)$c['id'] === $fCategory ? 'selected' : '' ?>>
              <?= e((string)$c['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <button class="btn btn-sm btn-mm w-100" type="submit">Filter</button>
      </div>
      <div class="col-md-3 text-end small text-muted d-flex align-items-center justify-content-end">
        <?= count($products) ?> product<?= count($products) === 1 ? '' : 's' ?>
      </div>
    </form>

    <div class="bg-white border rounded-3 overflow-hidden">
      <table class="table table-sm mb-0 align-middle">
        <thead class="table-light">
          <tr>
            <th style="width:60px;"></th>
            <th>Product</th>
            <th>Category</th>
            <th>Seller</th>
            <th class="text-end">Price</th>
            <th>Discounts for this product</th>
            <th class="text-center">Status</th>
            <th class="text-end"></th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$products): ?>
            <tr><td colspan="8" class="text-center py-4 text-muted">No products match your filters.</td></tr>
          <?php endif; ?>
          <?php foreach ($products as $p):
            $pid            = (int)$p['id'];
            $productCoupons = $couponsByProduct[$pid] ?? [];
          ?>
            <tr>
              <td>
                <?php if (!empty($p['image_path'])): ?>
                  <img src="<?= e((string)$p['image_path']) ?>" alt="" style="width:44px;height:44px;object-fit:cover;border-radius:6px;">
                <?php else: ?>
                  <div style="width:44px;height:44px;background:#f5f5f5;border-radius:6px;display:flex;align-items:center;justify-content:center;color:#bbb;font-size:9px;">—</div>
                <?php endif; ?>
              </td>
              <td>
                <div class="fw-semibold" style="line-height:1.15;"><?= e((string)$p['name']) ?></div>
                <div class="small text-muted"><?= e((string)($p['part_code'] ?? '')) ?></div>
                <?php if ($p['sold_as'] === 'packet' && $p['pack_size_grams']): ?>
                  <span class="badge bg-info text-white" style="font-size:9px;"><?= rtrim(rtrim(number_format((float)$p['pack_size_grams'], 2, '.', ''), '0'), '.') ?>g pack</span>
                <?php endif; ?>
              </td>
              <td class="small"><?= e((string)($p['category_name'] ?? '—')) ?></td>
              <td class="small"><?= e((string)($p['merchant_business'] ?? $p['merchant_name'] ?? '—')) ?></td>
              <td class="text-end">₹<?= e((string)$p['price_per_kg']) ?></td>
              <td>
                <?php if (!$productCoupons): ?>
                  <span class="text-muted small">None</span>
                <?php else: ?>
                  <?php foreach ($productCoupons as $pc):
                    $expired = !empty($pc['expires_at']) && strtotime((string)$pc['expires_at']) < time();
                    $badgeClass = (int)$pc['is_active'] === 1 && !$expired ? 'bg-success' : 'bg-secondary';
                  ?>
                    <span class="badge <?= $badgeClass ?> me-1" style="font-size:10px;">
                      <?= e((string)$pc['code']) ?>
                      <?php if ($pc['discount_type'] === 'percent'): ?>
                        · <?= (int)round((float)$pc['discount_value']) ?>%
                      <?php else: ?>
                        · ₹<?= (int)round((float)$pc['discount_value']) ?>
                      <?php endif; ?>
                    </span>
                  <?php endforeach; ?>
                <?php endif; ?>
              </td>
              <td class="text-center">
                <?php if ((int)$p['is_active'] === 0): ?>
                  <span class="badge bg-secondary">Hidden</span>
                <?php else: ?>
                  <span class="badge bg-success">Active</span>
                <?php endif; ?>
              </td>
              <td class="text-end">
                <a class="btn btn-sm btn-outline-primary" href="?p=super-admin/coupons&product_id=<?= $pid ?>"
                   title="Create a discount that only works for this product">
                  <i data-lucide="ticket-percent" style="width:14px;height:14px;"></i>
                  Create discount
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <script>if (window.lucide) window.lucide.createIcons();</script>
<?php
};

require __DIR__ . '/../../views/layout.php';
