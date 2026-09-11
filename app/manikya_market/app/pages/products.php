<?php

declare(strict_types=1);

$title = 'Products';

// pagination
$pageNum = max(1, (int)($_GET['page'] ?? 1));
$perPage = 12;
$offset = ($pageNum - 1) * $perPage;

$categorySlug = trim((string)($_GET['category'] ?? ''));
$searchQuery  = trim((string)($_GET['q'] ?? ''));
$activeCategory = null;
if ($categorySlug !== '') {
    try {
        $activeCategory = db_fetch_one($db,
            'SELECT id, name, slug FROM categories WHERE slug = :slug AND is_active = 1 LIMIT 1',
            ['slug' => $categorySlug]
        );
    } catch (Throwable $e) {
        $activeCategory = null;
    }
}

$where = 'p.is_active = 1';
$bindings = [];
if ($activeCategory) {
    $where .= ' AND p.category_id = :cat_id';
    $bindings['cat_id'] = (int)$activeCategory['id'];
}
if ($searchQuery !== '') {
    $where .= ' AND (p.name LIKE :q1 OR p.description LIKE :q2 OR p.product_type LIKE :q3)';
    $bindings['q1'] = '%' . $searchQuery . '%';
    $bindings['q2'] = '%' . $searchQuery . '%';
    $bindings['q3'] = '%' . $searchQuery . '%';
}

try {
    $total = (int)db_fetch_one($db, "SELECT COUNT(*) AS c FROM products p WHERE $where", $bindings)['c'];
    $products = db_fetch_all($db,
        "SELECT p.id, p.name, p.product_type, p.part_code, p.price_per_kg, p.stock_kg, p.unit, p.image_path,
                p.sold_as, p.pack_size_grams,
                COALESCE(i.qty_kg, p.stock_kg) AS inventory_qty,
                u.full_name AS merchant_name, mp.business_name AS merchant_business,
                c.discount_pct AS cat_discount_pct,
                c.discount_label AS cat_discount_label,
                c.discount_ends_at AS cat_discount_ends_at
         FROM products p
         LEFT JOIN inventory i ON i.product_id = p.id
         LEFT JOIN users u ON u.id = p.merchant_id
         LEFT JOIN merchant_profile mp ON mp.merchant_user_id = p.merchant_id
         LEFT JOIN categories c ON c.id = p.category_id
         WHERE $where
         ORDER BY (CASE WHEN COALESCE(i.qty_kg, p.stock_kg) > 0 THEN 1 ELSE 0 END) DESC, p.created_at DESC
         LIMIT :limit OFFSET :offset",
        array_merge($bindings, ['limit' => $perPage, 'offset' => $offset])
    );
} catch (Throwable $e) {
    $total = 0;
    $products = [];
}

// Amazon-style "Deal price" — batch fetch any auto-apply product coupons.
$autoCouponByProduct = [];
try {
    if ($products && function_exists('coupon_auto_apply_for_products')) {
        $productIds = array_map(fn($p) => (int)$p['id'], $products);
        $autoCouponByProduct = coupon_auto_apply_for_products($db, $productIds);
    }
} catch (Throwable $e) { $autoCouponByProduct = []; }

$paginationCategoryQs = $activeCategory ? '&category=' . urlencode((string)$activeCategory['slug']) : '';
if ($searchQuery !== '') {
    $paginationCategoryQs .= '&q=' . urlencode($searchQuery);
}

$content = function () use ($products, $autoCouponByProduct, $pageNum, $perPage, $total, $activeCategory, $paginationCategoryQs, $searchQuery) {
    $totalPages = max(1, (int)ceil($total / $perPage));
    ?>
    <style>
      /* Mango Theme Color Palette */
      .mm-card {
        border: none;
        background: #fff;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        border-radius: 16px;
        overflow: hidden;
      }

      .product-card .mm-card {
        height: 100%;
        box-shadow: 0 4px 12px rgba(255, 140, 0, 0.1);
      }

      .product-card .mm-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 12px 32px rgba(255, 140, 0, 0.25);
      }

      .product-card .card-img-top {
        transition: transform 0.4s ease;
      }

      .product-card .mm-card:hover .card-img-top {
        transform: scale(1.05);
      }

      .product-card .card-body {
        padding: 16px;
        display: flex;
        flex-direction: column;
      }

      .product-card .fw-semibold {
        color: #333;
        font-size: 1rem;
        margin-bottom: 6px;
      }

      .product-card .text-muted {
        color: #888 !important;
        font-size: 0.85rem;
        margin-bottom: 8px;
      }

      /* Button Styling */
      .btn-mm {
        background: linear-gradient(135deg, #FF8C00 0%, #FFD700 100%);
        color: #333;
        border: none;
        font-weight: 600;
        transition: all 0.3s ease;
        border-radius: 8px;
      }

      .btn-mm:hover {
        background: linear-gradient(135deg, #FF6347 0%, #FFA500 100%);
        color: #fff;
        transform: scale(1.05);
      }

      /* Icon Styling */
      .mm-icon {
        width: 18px;
        height: 18px;
        display: inline-block;
      }

      h2.h5 {
        color: #FF8C00;
        font-weight: 700;
        margin-bottom: 20px;
      }

      /* Pagination Styling */
      .pagination .page-link {
        color: #FF8C00;
        border-color: #FFD700;
        border-radius: 8px;
        margin: 0 4px;
      }

      .pagination .page-link:hover {
        background: linear-gradient(135deg, #FF8C00 0%, #FFD700 100%);
        color: #333;
        border-color: #FF8C00;
      }

      .pagination .page-item.active .page-link {
        background: linear-gradient(135deg, #FF8C00 0%, #FFD700 100%);
        border-color: #FF8C00;
        color: #fff;
        font-weight: 600;
      }

      .pagination .page-item.disabled .page-link {
        color: #BDBDBD !important;
        border-color: #E0E0E0 !important;
        background: #F7F7F7 !important;
        cursor: not-allowed;
        pointer-events: none;
        opacity: 0.7;
      }

      /* Container Padding */
      .container {
        max-width: 1700px;
      }

      /* Responsive Design */
      @media (max-width: 768px) {
        .product-card {
          flex: 0 0 50%;
        }
      }
    </style>

    <div class="container py-4">
      <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h2 class="h5 mb-0">
          <?= $activeCategory ? e((string)$activeCategory['name']) : 'All Products' ?>
          <?php if ($searchQuery !== ''): ?>
            <span class="text-muted small">— search: "<?= e($searchQuery) ?>"</span>
          <?php endif; ?>
        </h2>
        <form method="get" class="d-flex" style="max-width: 360px;">
          <input type="hidden" name="p" value="products">
          <?php if ($activeCategory): ?>
            <input type="hidden" name="category" value="<?= e((string)$activeCategory['slug']) ?>">
          <?php endif; ?>
          <input class="form-control form-control-sm me-2" type="search" name="q" value="<?= e($searchQuery) ?>" placeholder="Search products...">
          <button class="btn btn-sm btn-mm" type="submit">Go</button>
        </form>
        <div class="small text-muted">Showing page <?= (int)$pageNum ?> of <?= (int)$totalPages ?></div>
      </div>

      <?php if (!$products): ?>
        <div class="bg-white border rounded-4 p-3 mm-card">No products found.</div>
      <?php else: ?>
        <style>
          .products-page .card { position: relative; }
          .products-page .mm-sale-badge {
            position: absolute; top: 8px; left: 8px; z-index: 3;
            background: #B12704; color: #fff;
            padding: 3px 9px; border-radius: 3px;
            font-size: .68rem; font-weight: 700; letter-spacing: .02em;
            text-transform: uppercase;
          }
          .products-page .mm-discount-pct { color: #B12704; font-weight: 700; margin-right: 4px; }
          .products-page .mm-mrp-strike { text-decoration: line-through; color: #6b7280; font-size: .78rem; }
          .products-page .mm-deal-timer {
            display: inline-flex; align-items: center; gap: 4px;
            background: #FFF3E0; color: #C7511F;
            padding: 2px 7px; border-radius: 3px;
            font-size: .68rem; font-weight: 600;
          }
        </style>
        <div class="row g-3 products-page">
          <?php foreach ($products as $p):
            $pSoldAs = (string)($p['sold_as'] ?? 'bulk');
            $pPackG  = (float)($p['pack_size_grams'] ?? 0);
            if ($pSoldAs === 'packet' && $pPackG > 0) {
                $packGramsFmt = rtrim(rtrim(number_format($pPackG, 2, '.', ''), '0'), '.');
                $unitLabelP = $packGramsFmt . 'g pack';
            } else {
                $unitLabelP = product_unit_label($p['unit'] ?? 'kg');
            }
            // Auto-apply product coupon wins; else falls back to category discount.
            $disc = product_auto_discount($p, $autoCouponByProduct[(int)$p['id']] ?? null);
            $orig  = (float)$p['price_per_kg'];
            $final = (float)$disc['final_unit'];
            $discPctInt = (int)round((float)$disc['pct']);
          ?>
            <?php $productUrl = '?p=product&id=' . (int)$p['id']; ?>
            <div class="col-6 col-md-4 col-lg-3 product-card">
              <div class="card mm-card">
                <?php if ($disc['label'] !== ''): ?>
                  <span class="mm-sale-badge"><?= e($disc['label']) ?></span>
                <?php endif; ?>
                <a href="<?= e($productUrl) ?>" target="_blank" rel="noopener" style="display:block;text-decoration:none;color:inherit;">
                  <?php if (!empty($p['image_path'])): ?>
                    <img src="<?= e((string)$p['image_path']) ?>" class="card-img-top" alt="<?= e((string)$p['name']) ?>" style="aspect-ratio:1/1;object-fit:cover;">
                  <?php else: ?>
                    <div class="bg-light d-flex align-items-center justify-content-center" style="aspect-ratio:1/1;"><i data-lucide="image" class="mm-icon"></i></div>
                  <?php endif; ?>
                </a>
                <div class="card-body">
                  <a href="<?= e($productUrl) ?>" target="_blank" rel="noopener" style="text-decoration:none;color:inherit;">
                    <div class="fw-semibold text-truncate"><?= e((string)$p['name']) ?></div>
                  </a>
                  <div class="text-muted small"><?= e((string)($p['product_type'] ?? '')) ?> <?= e((string)($p['part_code'] ?? '')) ?></div>
                  <?php if ($disc['pct'] > 0 && !empty($disc['ends_at'])): ?>
                    <div class="mm-deal-timer mt-1" data-ends-at="<?= e((string)$disc['ends_at']) ?>">
                      <span class="mm-deal-timer-text">Ends soon</span>
                    </div>
                  <?php endif; ?>
                  <div class="d-flex justify-content-between align-items-center mt-2">
                    <div class="small">
                      <?php if ($disc['pct'] > 0): ?>
                        <span class="mm-discount-pct">-<?= $discPctInt ?>%</span>
                        <strong>₹<?= number_format($final, 2) ?></strong>/<?= e($unitLabelP) ?>
                        <div class="mm-mrp-strike">M.R.P.: ₹<?= number_format($orig, 2) ?></div>
                      <?php else: ?>
                        <span class="text-muted">₹<?= e((string)$p['price_per_kg']) ?>/<?= e($unitLabelP) ?></span>
                      <?php endif; ?>
                    </div>
                    <?php
                      // Prefer live inventory_qty (which reads from the
                      // inventory_movements ledger) over the legacy
                      // products.stock_kg snapshot.
                      $invQty = (float)($p['inventory_qty'] ?? $p['stock_kg'] ?? 0);
                    ?>
                    <?php if ($invQty > 0): ?>
                      <a class="btn btn-sm btn-mm" href="?p=cart/add&id=<?= (int)$p['id'] ?>&qty=1">Add</a>
                    <?php else: ?>
                      <span class="badge bg-secondary">Out of Stock</span>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <nav class="mt-4" aria-label="Products pagination">
          <ul class="pagination">
            <?php if ($pageNum > 1): ?>
              <li class="page-item"><a class="page-link" href="?p=products<?= $paginationCategoryQs ?>&page=<?= $pageNum-1 ?>">&laquo; Prev</a></li>
            <?php else: ?>
              <li class="page-item disabled"><span class="page-link">&laquo; Prev</span></li>
            <?php endif; ?>

            <?php for ($i = 1; $i <= min($totalPages, 9); $i++): ?>
              <li class="page-item <?= $i === $pageNum ? 'active' : '' ?>"><a class="page-link" href="?p=products<?= $paginationCategoryQs ?>&page=<?= $i ?>"><?= $i ?></a></li>
            <?php endfor; ?>

            <?php if ($pageNum < $totalPages): ?>
              <li class="page-item"><a class="page-link" href="?p=products<?= $paginationCategoryQs ?>&page=<?= $pageNum+1 ?>">Next &raquo;</a></li>
            <?php else: ?>
              <li class="page-item disabled"><span class="page-link">Next &raquo;</span></li>
            <?php endif; ?>
          </ul>
        </nav>
      <?php endif; ?>
    </div>
    <script>if (window.lucide) window.lucide.createIcons();</script>
    <script>
    /* Deal countdown for .mm-deal-timer chips on category listing tiles. */
    (function () {
      function fmt(diff) {
        if (diff <= 0) return null;
        var s = Math.floor(diff / 1000);
        var d = Math.floor(s / 86400); s %= 86400;
        var h = Math.floor(s / 3600);  s %= 3600;
        var m = Math.floor(s / 60);    s %= 60;
        if (d > 0) return 'Discount offer Ends in ' + d + 'd ' + h + 'h';
        if (h > 0) return 'Discount offer Ends in ' + h + 'h ' + m + 'm';
        if (m > 0) return 'Discount offer Ends in ' + m + 'm ' + s + 's';
        return 'Discount offer Ends in ' + s + 's';
      }
      function tick() {
        document.querySelectorAll('.mm-deal-timer').forEach(function (el) {
          var endsAt = el.getAttribute('data-ends-at');
          if (!endsAt) return;
          var end = new Date(endsAt.replace(' ', 'T')).getTime();
          var diff = end - Date.now();
          var txt = fmt(diff);
          if (txt === null) { el.style.display = 'none'; return; }
          var span = el.querySelector('.mm-deal-timer-text');
          if (span) span.textContent = txt;
        });
      }
      tick(); setInterval(tick, 1000);
    })();
    </script>
    <?php
};

require __DIR__ . '/../views/layout.php';
