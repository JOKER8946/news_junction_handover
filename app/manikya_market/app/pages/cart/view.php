<?php

declare(strict_types=1);

$title = 'Cart';

$totals = cart_totals($db);
$merchantGroups = cart_groups_by_merchant($totals['items']);

$content = function () use ($totals, $merchantGroups) {
    $items = $totals['items'];
?>
  <style>
    .km-shop {
      --ink: #111827;
      --muted: #6B7280;
      --line: #E5E7EB;
      --line-strong: #D1D5DB;
      --bg-soft: #F9FAFB;
      --primary: #F39200;
      --primary-dark: #C97500;
      --danger: #DC2626;
      color: var(--ink);
      background: #FFFFFF;
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
    }
    body.bg-light:has(.km-shop) { background:#fff !important; }

    .km-shop .container { max-width: 1180px; }
    .km-page-head { display:flex; align-items:flex-end; justify-content:space-between; margin: 16px 0 24px; gap: 12px; flex-wrap: wrap; }
    .km-page-head h1 { font-size: 1.75rem; font-weight: 700; margin: 0; letter-spacing: -0.01em; }
    .km-page-head .km-sub { color: var(--muted); font-size: .92rem; margin-top: 4px; }

    .km-card {
      background: #fff; border: 1px solid var(--line); border-radius: 12px;
    }
    .km-card-pad { padding: 20px 22px; }
    .km-card + .km-card { margin-top: 14px; }
    .km-card-h { font-size: .95rem; font-weight: 600; color: var(--ink); margin: 0 0 14px; letter-spacing: .01em; text-transform: uppercase; font-size: .78rem; color: var(--muted); }

    /* Item row */
    .km-item { display:flex; gap: 16px; padding: 18px 22px; border-bottom: 1px solid var(--line); }
    .km-item:last-child { border-bottom: 0; }
    .km-item-img { width: 88px; height: 88px; border-radius: 8px; overflow: hidden; flex-shrink: 0; background: var(--bg-soft); display:flex; align-items:center; justify-content:center; }
    .km-item-img img { width:100%; height:100%; object-fit: cover; }
    .km-item-img .ph { color: var(--line-strong); }
    .km-item-body { flex:1; display:flex; flex-direction: column; min-width: 0; }
    .km-item-row1 { display:flex; justify-content: space-between; gap: 12px; }
    .km-item-name { font-weight: 600; font-size: 1rem; color: var(--ink); margin-bottom: 2px; }
    .km-item-meta { color: var(--muted); font-size: .85rem; }
    .km-item-price { font-weight: 600; font-size: 1rem; color: var(--ink); white-space: nowrap; }
    .km-item-row2 { display:flex; align-items:center; justify-content: space-between; margin-top: 12px; gap: 12px; flex-wrap: wrap; }
    .km-qty { display:inline-flex; align-items:stretch; border:1px solid var(--line); border-radius: 8px; overflow:hidden; }
    .km-qty input[type=number] { border:0; outline:0; width: 60px; text-align:center; font-weight: 600; padding: 6px 4px; -moz-appearance: textfield; background:#fff; }
    .km-qty input::-webkit-outer-spin-button, .km-qty input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
    .km-qty .km-qty-label { background: var(--bg-soft); padding: 6px 10px; color: var(--muted); font-size:.85rem; border-right: 1px solid var(--line); display:flex; align-items:center; }
    .km-qty button { background:#fff; border:0; padding: 6px 14px; font-weight:600; font-size: 1.05rem; color: var(--ink); cursor: pointer; line-height: 1; min-width: 38px; }
    .km-qty button:hover:not(:disabled) { background: var(--bg-soft); }
    .km-qty button:disabled { color: var(--line-strong); cursor: not-allowed; }
    .km-qty .km-qty-step-down { border-right: 1px solid var(--line); }
    .km-qty .km-qty-step-up { border-left: 1px solid var(--line); }
    .km-qty .km-qty-update { border-left: 1px solid var(--line); padding: 6px 12px; font-size: .85rem; }
    .km-link-danger { color: var(--danger); background: none; border: 0; padding: 4px 8px; font-size: .85rem; text-decoration: none; }
    .km-link-danger:hover { text-decoration: underline; color: var(--danger); }

    /* Summary */
    .km-summary-row { display:flex; justify-content: space-between; padding: 8px 0; font-size: .92rem; color: var(--muted); }
    .km-summary-row .v { color: var(--ink); font-weight: 500; }
    .km-summary-divider { border-top: 1px solid var(--line); margin: 8px 0; }
    .km-summary-total { display:flex; justify-content: space-between; padding: 10px 0 4px; }
    .km-summary-total .l { font-weight: 700; font-size: 1.05rem; }
    .km-summary-total .v { font-weight: 700; font-size: 1.15rem; }

    /* Buttons */
    .km-btn {
      display: inline-flex; align-items:center; justify-content:center; gap: 8px;
      padding: 10px 18px; font-weight: 600; font-size: .95rem;
      border-radius: 8px; text-decoration: none; cursor: pointer;
      transition: background .15s, color .15s, border-color .15s;
      border: 1px solid transparent;
    }
    .km-btn-primary { background: var(--primary); color:#fff; }
    .km-btn-primary:hover { background: var(--primary-dark); color:#fff; }
    .km-btn-outline { background:#fff; color: var(--ink); border-color: var(--line-strong); }
    .km-btn-outline:hover { background: var(--bg-soft); color: var(--ink); }
    .km-btn-block { width: 100%; }
    .km-btn-lg { padding: 13px 22px; font-size: 1rem; }

    /* Empty state */
    .km-empty {
      text-align:center; padding: 56px 24px; border:1px dashed var(--line-strong); border-radius: 12px; color: var(--muted);
    }
    .km-empty h3 { color: var(--ink); font-size: 1.1rem; font-weight: 600; margin-bottom: 4px; }

    .km-grid-2 { display:grid; grid-template-columns: 1.6fr 1fr; gap: 18px; }
    @media (max-width: 991px) {
      .km-grid-2 { grid-template-columns: 1fr; }
    }

    @media (max-width: 575px) {
      .km-page-head h1 { font-size: 1.4rem; }
      .km-item { padding: 14px 16px; gap: 12px; flex-wrap: wrap; }
      .km-item-img { width: 72px; height: 72px; }
      .km-item-body { min-width: 0; flex: 1 1 calc(100% - 84px); }
      .km-item-row2 { flex-direction: column; align-items: flex-start; gap: 8px; }
      .km-item-row2 form { flex-wrap: wrap; }
      .km-qty input[type=number] { width: 50px; }
      .km-card-pad { padding: 16px; }
    }
  </style>

  <div class="km-shop">
    <div class="container py-4">
      <div class="km-page-head">
        <div>
          <h1>Your Cart</h1>
          <div class="km-sub"><?= count($items) ?> <?= count($items) === 1 ? 'item' : 'items' ?> in your bag</div>
        </div>
        <?php if ($items): ?>
          <a class="km-btn km-btn-outline" href="?p=cart/clear">Clear cart</a>
        <?php endif; ?>
      </div>

      <?php if (!$items): ?>
        <div class="km-empty">
          <h3>Your cart is empty</h3>
          <p class="mb-3">Add mangoes from the home page to get started.</p>
          <a class="km-btn km-btn-primary" href="?p=home">Continue shopping</a>
        </div>
      <?php else: ?>
        <?php if (count($merchantGroups) > 1): ?>
          <div class="alert alert-info small mb-3">
            <i data-lucide="info" style="width:16px;height:16px;"></i>
            Your cart has items from <strong><?= count($merchantGroups) ?> sellers</strong>.
            Checkout will create one order per seller and you'll pay each separately.
          </div>
        <?php endif; ?>
        <div class="km-grid-2">
          <!-- Items grouped by merchant -->
          <div>
            <?php foreach ($merchantGroups as $group): ?>
              <div class="km-card mb-3">
                <div class="px-3 pt-3 pb-2 d-flex align-items-center justify-content-between"
                     style="border-bottom: 1px solid var(--line);">
                  <div class="d-flex align-items-center gap-2">
                    <i data-lucide="store" style="width:16px;height:16px;color:var(--muted);"></i>
                    <span class="small text-muted">Sold by</span>
                    <strong><?= e((string)$group['merchant_business']) ?></strong>
                  </div>
                  <div class="small text-muted">
                    Subtotal: <strong style="color:var(--ink);">₹<?= e(number_format((float)$group['subtotal'], 2)) ?></strong>
                  </div>
                </div>
                <?php foreach ($group['items'] as $it): $p = $it['product'];
                  $itemSoldAs = (string)($p['sold_as'] ?? 'bulk');
                  $itemPackG  = (float)($p['pack_size_grams'] ?? 0);
                  if ($itemSoldAs === 'packet' && $itemPackG > 0) {
                      $packGFmt = rtrim(rtrim(number_format($itemPackG, 2, '.', ''), '0'), '.');
                      $cUnit    = $packGFmt . 'g pack';
                      $qtyLabel = 'packets';
                      $qtyStep  = '1';
                      $lineWeightG = (int)round((float)$it['qty_kg'] * $itemPackG);
                      $totalWeightText = $packGFmt . 'g × ' . rtrim(rtrim(number_format((float)$it['qty_kg'], 2, '.', ''), '0'), '.') . ' = ' . $lineWeightG . 'g';
                  } else {
                      $cUnit = product_unit_label($p['unit'] ?? 'kg');
                      $qtyLabel = $cUnit;
                      $qtyStep  = '0.25';
                      $totalWeightText = '';
                  }
                ?>
                  <div class="km-item">
                    <div class="km-item-img">
                      <?php if (!empty($p['image_path'])): ?>
                        <img src="<?= e((string)$p['image_path']) ?>" alt="<?= e((string)$p['name']) ?>">
                      <?php else: ?>
                        <i data-lucide="image" class="ph" style="width:24px;height:24px;"></i>
                      <?php endif; ?>
                    </div>
                    <div class="km-item-body">
                      <div class="km-item-row1">
                        <div>
                          <div class="km-item-name"><?= e((string)$p['name']) ?></div>
                          <div class="km-item-meta">₹<?= e((string)$p['price_per_kg']) ?> per <?= e($cUnit) ?></div>
                          <?php if ($totalWeightText !== ''): ?>
                            <div class="km-item-meta" style="font-size:.78rem;">Weight: <?= e($totalWeightText) ?></div>
                          <?php endif; ?>
                        </div>
                        <div class="km-item-price">₹<?= e((string)$it['line_total']) ?></div>
                      </div>
                      <div class="km-item-row2">
                        <form method="post" action="?p=cart/update" class="km-qty-form d-inline-flex align-items-center gap-2">
                          <?= csrf_field() ?>
                          <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                          <div class="km-qty">
                            <span class="km-qty-label"><?= e($qtyLabel) ?></span>
                            <button type="button" class="km-qty-step-down" aria-label="Decrease quantity">−</button>
                            <input type="number" step="<?= e($qtyStep) ?>" min="0" name="qty" value="<?= e((string)$it['qty_kg']) ?>" data-step="<?= e($qtyStep) ?>" required>
                            <button type="button" class="km-qty-step-up" aria-label="Increase quantity">+</button>
                            <button class="km-qty-update" type="submit" title="Update quantity">Update</button>
                          </div>
                          <span class="km-item-meta">Shipping ₹<?= e((string)$it['line_shipping']) ?></span>
                        </form>
                        <a class="km-link-danger" href="?p=cart/remove&id=<?= (int)$p['id'] ?>">Remove</a>
                      </div>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endforeach; ?>
          </div>

          <!-- Summary -->
          <div>
            <div class="km-card km-card-pad" style="position: sticky; top: 90px;">
              <div class="km-card-h">Order Summary</div>
              <div class="km-summary-row"><span>Subtotal</span><span class="v">₹<?= e((string)$totals['subtotal']) ?></span></div>
              <div class="km-summary-row"><span>Shipping</span><span class="v">₹<?= e((string)$totals['shipping']) ?></span></div>
              <div class="km-summary-divider"></div>
              <div class="km-summary-total"><span class="l">Total</span><span class="v">₹<?= e((string)$totals['total']) ?></span></div>
              <div style="font-size: .8rem; color: var(--muted); margin: 6px 0 14px;">Tax included where applicable</div>
              <a class="km-btn km-btn-primary km-btn-block km-btn-lg" href="?p=buyer/checkout">
                Proceed to checkout
                <i data-lucide="arrow-right" style="width:18px;height:18px;"></i>
              </a>
              <a class="km-btn km-btn-outline km-btn-block" href="?p=home" style="margin-top:10px;">
                Continue shopping
              </a>
            </div>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <script>
    if (window.lucide) window.lucide.createIcons();

    (function () {
      function clamp(v) {
        if (isNaN(v) || v < 0) return 0;
        return Math.round(v * 100) / 100;
      }
      document.querySelectorAll('.km-qty-form').forEach(function (form) {
        var input = form.querySelector('input[name="qty"]');
        var down = form.querySelector('.km-qty-step-down');
        var up = form.querySelector('.km-qty-step-up');
        if (!input) return;

        // Packet products bump by 1; bulk products bump by 1 too (the input
        // itself supports fractional entry, but the +/- buttons should step
        // by whole units for a cleaner UX).
        var stepFromAttr = parseFloat(input.getAttribute('data-step') || '1');
        var step = stepFromAttr >= 1 ? 1 : 1;

        function syncDisabled() {
          if (down) down.disabled = parseFloat(input.value || '0') <= 0;
        }
        syncDisabled();
        input.addEventListener('input', syncDisabled);

        if (down) {
          down.addEventListener('click', function () {
            var v = clamp(parseFloat(input.value || '0') - step);
            input.value = v;
            syncDisabled();
            form.submit();
          });
        }
        if (up) {
          up.addEventListener('click', function () {
            var v = clamp(parseFloat(input.value || '0') + step);
            input.value = v;
            syncDisabled();
            form.submit();
          });
        }
      });
    })();
  </script>

  <script>
  (function () {
    function getCartItems() {
      var items = [];
      document.querySelectorAll('.km-item').forEach(function (row, i) {
        var nameEl   = row.querySelector('.km-item-name');
        var metaEl   = row.querySelector('.km-item-meta');
        var idInput  = row.querySelector('input[name="id"]');
        var qtyInput = row.querySelector('input[name="qty"]');
        var name      = nameEl ? nameEl.textContent.trim() : '';
        var unitPrice = metaEl ? parseFloat(metaEl.textContent.replace(/[^0-9.]/g, '')) || 0 : 0;
        var qty       = qtyInput ? parseFloat(qtyInput.value) || 1 : 1;
        var id        = idInput  ? idInput.value : ('item_' + i);
        items.push({ item_id: id, item_name: name, price: unitPrice, quantity: qty });
      });
      return items;
    }

    function getSummary() {
      var rows    = document.querySelectorAll('.km-summary-row .v');
      var totalEl = document.querySelector('.km-summary-total .v');
      return {
        subtotal: rows[0] ? parseFloat(rows[0].textContent.replace(/[^0-9.]/g, '')) || 0 : 0,
        shipping: rows[1] ? parseFloat(rows[1].textContent.replace(/[^0-9.]/g, '')) || 0 : 0,
        total:    totalEl ? parseFloat(totalEl.textContent.replace(/[^0-9.]/g, '')) || 0 : 0
      };
    }

    var cartItems = getCartItems();
    var summary   = getSummary();

    gtag('event', 'view_cart', { currency: 'INR', value: summary.total, items: cartItems });
    if (window.fbq) {
      fbq('track', 'ViewContent', {
        value: summary.total, currency: 'INR',
        content_ids: cartItems.map(function(i){ return i.item_name; }),
        content_type: 'product', num_items: cartItems.length
      });
    }

    // 2. PROCEED TO CHECKOUT
    var checkoutBtn = document.querySelector('a[href="?p=buyer/checkout"]');
    if (checkoutBtn) {
      checkoutBtn.addEventListener('click', function () {
        gtag('event', 'begin_checkout', { currency: 'INR', value: summary.total, items: cartItems });
        if (window.fbq) {
          fbq('track', 'InitiateCheckout', {
            value: summary.total, currency: 'INR', num_items: cartItems.length,
            content_ids: cartItems.map(function(i){ return i.item_name; })
          });
        }
      });
    }

    // 3. REMOVE ITEM
    document.querySelectorAll('a.km-link-danger').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var row      = btn.closest('.km-item');
        var nameEl   = row ? row.querySelector('.km-item-name')     : null;
        var metaEl   = row ? row.querySelector('.km-item-meta')     : null;
        var idInput  = row ? row.querySelector('input[name="id"]')  : null;
        var qtyInput = row ? row.querySelector('input[name="qty"]') : null;
        var name      = nameEl  ? nameEl.textContent.trim() : '';
        var unitPrice = metaEl  ? parseFloat(metaEl.textContent.replace(/[^0-9.]/g, '')) || 0 : 0;
        var qty       = qtyInput ? parseFloat(qtyInput.value) || 1 : 1;
        var id        = idInput  ? idInput.value : '';
        gtag('event', 'remove_from_cart', {
          currency: 'INR', value: unitPrice * qty,
          items: [{ item_id: id, item_name: name, price: unitPrice, quantity: qty }]
        });
      });
    });

    // 4. QUANTITY UPDATE (+ / - and Update button)
    document.querySelectorAll('.km-qty-form').forEach(function (form) {
      var idInput  = form.querySelector('input[name="id"]');
      var qtyInput = form.querySelector('input[name="qty"]');
      var row      = form.closest('.km-item');
      var nameEl   = row ? row.querySelector('.km-item-name') : null;
      function fireQtyUpdate() {
        gtag('event', 'update_cart_quantity', {
          item_id:      idInput  ? idInput.value              : '',
          item_name:    nameEl   ? nameEl.textContent.trim()  : '',
          new_quantity: qtyInput ? parseFloat(qtyInput.value) : 0
        });
      }
      form.addEventListener('submit', fireQtyUpdate);
      var stepUp   = form.querySelector('.km-qty-step-up');
      var stepDown = form.querySelector('.km-qty-step-down');
      if (stepUp)   stepUp.addEventListener('click',   fireQtyUpdate);
      if (stepDown) stepDown.addEventListener('click', fireQtyUpdate);
    });

    // 5. CLEAR CART
    var clearBtn = document.querySelector('a[href="?p=cart/clear"]');
    if (clearBtn) {
      clearBtn.addEventListener('click', function () {
        gtag('event', 'clear_cart', { currency: 'INR', value: summary.total, items_count: cartItems.length });
      });
    }

    // 6. CONTINUE SHOPPING
    var continueBtn = document.querySelector('a.km-btn-outline[href="?p=home"]');
    if (continueBtn) {
      continueBtn.addEventListener('click', function () {
        gtag('event', 'continue_shopping', { from_page: 'cart', cart_value: summary.total });
      });
    }
  })();
  </script>
<?php
};

require __DIR__ . '/../../views/layout.php';
