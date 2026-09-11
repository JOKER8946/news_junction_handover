<?php

declare(strict_types=1);

auth_require_role('buyer');

$title = 'Checkout';
$buyerId = auth_user_id();

$addresses = db_fetch_all($db, 'SELECT * FROM buyer_addresses WHERE buyer_id = :buyer_id ORDER BY is_default DESC, created_at DESC', [
    'buyer_id' => $buyerId,
]);

$selectedAddressId = (int)($_POST['address_id'] ?? $_GET['address_id'] ?? 0);
$selectedAddress = null;
if ($selectedAddressId > 0) {
    foreach ($addresses as $a) {
        if ((int)$a['id'] === $selectedAddressId) {
            $selectedAddress = $a;
            break;
        }
    }
}
if (!$selectedAddress && !empty($addresses)) {
    $selectedAddress = $addresses[0];
    $selectedAddressId = (int)$selectedAddress['id'];
}
$selectedPincode = $selectedAddress ? (string)($selectedAddress['pincode'] ?? '') : '';

$totals = $selectedPincode
    ? cart_totals_with_delhivery($db, $selectedPincode)
    : cart_totals($db);

$items = $totals['items'];

if (!$items) {
    redirect_to('cart');
}

$merchantGroups = cart_groups_by_merchant($items);
$isMultiMerchant = count($merchantGroups) > 1;

// Handle referral code application via GET param ?apply_ref=CODE
$referralFlash = null;
$referralFlashType = 'info';
if (isset($_GET['apply_ref'])) {
    $code = strtoupper(trim((string)$_GET['apply_ref']));
    if ($code !== '') {
        $isFirst = referral_is_first_order($db, $buyerId);
        $hasExisting = referral_get_pending_discount($db, $buyerId);
        if (!$isFirst) {
            $referralFlash = 'Referral discount only applies on your first order.';
            $referralFlashType = 'warning';
        } elseif ($hasExisting) {
            $referralFlash = 'A referral discount is already applied to your account.';
            $referralFlashType = 'success';
        } else {
            $applied = referral_apply_code($db, $buyerId, $code);
            if ($applied) {
                $referralFlash = 'Referral code applied successfully.';
                $referralFlashType = 'success';
            } else {
                $referralFlash = 'Invalid referral code, or it cannot be applied to your account.';
                $referralFlashType = 'danger';
            }
        }
    }
}

// Check if this buyer is a referee with a pending discount on first order
$referralDiscount = null;
$discountAmount = 0.0;
$isFirstOrder = referral_is_first_order($db, $buyerId);
if ($isFirstOrder) {
    $referralDiscount = referral_get_pending_discount($db, $buyerId);
    if ($referralDiscount) {
        $discountPct = (float)$referralDiscount['referee_discount_pct'];
        $discountAmount = round($totals['subtotal'] * ($discountPct / 100), 2);
    }
}

// Coupon (from session, set by apply_coupon POST)
$couponApplied = null;
$couponDiscount = 0.0;
if (!empty($_SESSION['applied_coupon_id'])) {
    $couponApplied = db_fetch_one($db, 'SELECT * FROM coupons WHERE id = :id LIMIT 1', ['id' => (int)$_SESSION['applied_coupon_id']]);
    if ($couponApplied) {
        $couponDiscount = coupon_compute_discount($couponApplied, (float)$totals['subtotal'], $totals['items'] ?? []);
        if ($couponDiscount <= 0) {
            // Doesn't qualify (below min order); drop silently from the session
            unset($_SESSION['applied_coupon_id']);
            $couponApplied = null;
        }
    } else {
        unset($_SESSION['applied_coupon_id']);
    }
}

// GST on (subtotal − referral − coupon discount). Shipping is treated as
// already-taxed in practice; we apply GST on the goods portion.
$gstRate     = platform_gst_rate($db);
$taxableBase = max(0.0, round((float)$totals['subtotal'] - $discountAmount - $couponDiscount, 2));
$gstAmount   = compute_gst($taxableBase, $gstRate);

// Check wallet balance and auto-apply
$walletBalance = wallet_balance($db, $buyerId);
$beforeWallet  = round((float)$totals['subtotal'] + (float)$totals['shipping'] + $gstAmount - $discountAmount - $couponDiscount, 2);
if ($beforeWallet < 0) $beforeWallet = 0;
$walletDeduction = 0.0;
if ($walletBalance > 0 && $beforeWallet > 0) {
    $walletDeduction = min($walletBalance, $beforeWallet);
}
$finalTotal = max(0.0, round($beforeWallet - $walletDeduction, 2));

// Razorpay routes through the PLATFORM's account (super-admin's keys in
// platform_settings). One combined payment for the whole cart; on success
// we split into N per-merchant orders and credit each merchant's wallet
// net of the platform commission.
$razorpayConfig  = platform_razorpay_get_config($db);
$razorpayEnabled = ($razorpayConfig !== null) && ($finalTotal > 0);

// Addresses already loaded above

if (request_method() === 'POST') {
    // Apply coupon (separate "Apply" button)
    if (post_string('action') === 'apply_coupon') {
        $code = strtoupper(trim(post_string('coupon_code')));
        if ($code === '') {
            flash_set('error', 'Enter a coupon code to apply.');
        } else {
            $c = coupon_lookup($db, $code);
            if (!$c) {
                flash_set('error', 'Invalid or expired coupon.');
            } else {
                $disc = coupon_compute_discount($c, (float)$totals['subtotal'], $totals['items'] ?? []);
                if ($disc <= 0) {
                    $scopedProductId = isset($c['product_id']) ? (int)$c['product_id'] : 0;
                    if ($scopedProductId > 0) {
                        // Distinguish "wrong product in cart" from "below min"
                        $hasProduct = false;
                        foreach (($totals['items'] ?? []) as $it) {
                            if ((int)($it['product']['id'] ?? 0) === $scopedProductId) { $hasProduct = true; break; }
                        }
                        if (!$hasProduct) {
                            flash_set('error', 'This coupon only applies to a specific product that isn\'t in your cart.');
                        } else {
                            flash_set('error', 'That product\'s spend doesn\'t meet the coupon minimum (₹' . number_format((float)$c['min_order_value'], 2) . ').');
                        }
                    } else {
                        flash_set('error', 'Order doesn\'t meet the minimum for this coupon (₹' . number_format((float)$c['min_order_value'], 2) . ').');
                    }
                } else {
                    $_SESSION['applied_coupon_id'] = (int)$c['id'];
                    flash_set('success', 'Coupon ' . $c['code'] . ' applied: ₹' . number_format($disc, 2) . ' off.');
                }
            }
        }
        redirect_to('buyer/checkout');
    }
    if (post_string('action') === 'remove_coupon') {
        unset($_SESSION['applied_coupon_id']);
        flash_set('success', 'Coupon removed.');
        redirect_to('buyer/checkout');
    }

    // Allow referral code POST (separate "Apply" button)
    if (post_string('action') === 'apply_referral') {
        $code = strtoupper(trim(post_string('referral_code')));
        if ($code === '') {
            flash_set('error', 'Enter a referral code to apply.');
        } elseif (!referral_is_first_order($db, $buyerId)) {
            flash_set('error', 'Referral discount only applies on your first order.');
        } elseif (referral_get_pending_discount($db, $buyerId)) {
            flash_set('success', 'A referral discount is already applied.');
        } else {
            $applied = referral_apply_code($db, $buyerId, $code);
            if ($applied) {
                flash_set('success', 'Referral code applied. Your discount will reflect below.');
            } else {
                flash_set('error', 'Invalid referral code, or it cannot be applied to your account.');
            }
        }
        redirect_to('buyer/checkout');
    }

    $addressId = (int)($_POST['address_id'] ?? 0);
    $address = null;
    foreach ($addresses as $a) {
        if ((int)$a['id'] === $addressId) {
            $address = $a;
            break;
        }
    }

    if (!$address) {
        flash_set('error', 'Please select a delivery address');
        redirect_to('buyer/checkout');
    }

    // Check Delhivery pincode serviceability
    delhivery_ensure_schema($db);
    $delhiveryConfig = delhivery_get_config($db);
    if ($delhiveryConfig) {
        $warehousePin = $delhiveryConfig['warehouse_pincode'];
        $destPin = (string)($address['pincode'] ?? '');
        if ($warehousePin && $destPin) {
            $pinCheck = delhivery_check_pincode($db, $warehousePin, $destPin);
            if (!$pinCheck['serviceable'] && $pinCheck['error'] === null) {
                flash_set('error', 'Sorry, delivery is not available to pincode ' . $destPin . '. Please try a different address.');
                redirect_to('buyer/checkout');
            }
        }
    }

    $paymentMethod = post_string('payment_method') === 'cod' ? 'cod' : 'razorpay';

    // Multi-merchant Razorpay payments are not wired through the sync POST path —
    // the JS coordinator handles them via razorpay-create per merchant. So if a
    // buyer with a multi-merchant cart submits Razorpay through this path, fall
    // back to COD-style order creation and surface a notice.
    if ($isMultiMerchant && $paymentMethod === 'razorpay') {
        // The JS layer should have intercepted; if we got here, fall through to
        // creating the orders without Razorpay (status = new, payment pending).
        $paymentMethod = 'razorpay';
    }

    $parentOrderNo = 'MM' . date('ymdHis') . random_int(100, 999);
    $isFirstOrder = referral_is_first_order($db, $buyerId);
    $pendingReferral = $isFirstOrder ? referral_get_pending_discount($db, $buyerId) : null;
    $walletBal = wallet_balance($db, $buyerId);

    // Recompute coupon + GST at submission time (cart could have changed).
    $postCoupon = null;
    $postCouponDisc = 0.0;
    if (!empty($_SESSION['applied_coupon_id'])) {
        $postCoupon = db_fetch_one($db, 'SELECT * FROM coupons WHERE id = :id AND is_active = 1 LIMIT 1', ['id' => (int)$_SESSION['applied_coupon_id']]);
        if ($postCoupon) $postCouponDisc = coupon_compute_discount($postCoupon, (float)$totals['subtotal'], $totals['items'] ?? []);
    }

    $cartSubtotal = (float)$totals['subtotal'];
    $cartShipping = (float)$totals['shipping'];

    // Referral discount (% of subtotal).
    $discountTotal = 0.0;
    if ($pendingReferral) {
        $discountTotal = round($cartSubtotal * ((float)$pendingReferral['referee_discount_pct'] / 100), 2);
    }

    // GST on (subtotal − referral − coupon).
    $postGstRate    = platform_gst_rate($db);
    $postTaxable    = max(0.0, round($cartSubtotal - $discountTotal - $postCouponDisc, 2));
    $postGstAmount  = compute_gst($postTaxable, $postGstRate);

    $beforeWallet  = round($cartSubtotal + $cartShipping + $postGstAmount - $discountTotal - $postCouponDisc, 2);
    if ($beforeWallet < 0) $beforeWallet = 0;
    $walletApplied = ($walletBal > 0) ? min($walletBal, $beforeWallet) : 0.0;
    $walletApplied = round($walletApplied, 2);
    $finalCartTotal = max(0.0, round($beforeWallet - $walletApplied, 2));

    ensure_table_column($db, 'orders', 'discount_amount', 'DECIMAL(10,2) NOT NULL DEFAULT 0');
    ensure_table_column($db, 'orders', 'wallet_deduction', 'DECIMAL(10,2) NOT NULL DEFAULT 0');

    // Warm every lazily-created table BEFORE opening the transaction.
    //
    // MySQL implicitly commits on any DDL — including a no-op
    // "CREATE TABLE IF NOT EXISTS" for a table that already exists. Running one
    // inside the transaction silently ends it, so the later commit() throws
    // "There is no active transaction" and the order is half-written with no
    // success page. alerts_ensure_schema() fired exactly that way via
    // alert_push_new_order() on the COD path.
    //
    // Both helpers carry a static "already done" guard, so warming them here
    // guarantees no DDL can run once the transaction is open.
    alerts_ensure_schema($db);
    referral_ensure_tables($db);

    $db->beginTransaction();
    try {
        $createdOrderIds = [];
        $groupIndex = 0;

        foreach ($merchantGroups as $group) {
            $groupIndex++;
            $groupGross  = (float)$group['total'];   // subtotal + shipping for this merchant
            $sharePct    = $cartSubtotal + $cartShipping > 0
                            ? $groupGross / ($cartSubtotal + $cartShipping) : 0.0;
            $groupDisc   = round($discountTotal  * $sharePct, 2);
            $groupCoup   = round($postCouponDisc * $sharePct, 2);
            $groupGst    = round($postGstAmount  * $sharePct, 2);
            $groupWallet = round($walletApplied  * $sharePct, 2);
            $groupFinal  = max(0.0, round($groupGross + $groupGst - $groupDisc - $groupCoup - $groupWallet, 2));

            $childOrderNo = $parentOrderNo . '-' . $groupIndex;

            // COD orders are confirmed the moment they're placed: there is no
            // gateway to wait on, so they open at 'paid' (i.e. actionable) and
            // the merchant can pack them straight away. The matching payments
            // row stays 'initiated' — that's what represents cash not yet
            // collected, and it stays that way through delivery.
            //
            // Razorpay orders open at 'new' and are advanced to 'paid' only by
            // razorpay-verify.php once the signature checks out, so an
            // unconfirmed draft is never shown to the merchant.
            //
            // Nothing is skipped by opening COD at 'paid': the only side-effect
            // keyed on the 'paid' transition is inventory_sale_for_order(),
            // which this function already calls for COD below, and the merchant
            // /buyer notifications fire here for both providers.
            $orderStatus = $paymentMethod === 'cod' ? 'paid' : 'new';

            db_exec($db,
                'INSERT INTO orders (order_no, parent_order_no, buyer_id, merchant_id, status,
                                     subtotal_amount, shipping_amount, discount_amount, wallet_deduction,
                                     coupon_id, coupon_code, coupon_amount, tax_amount,
                                     total_amount, delivery_address_id, created_at)
                 VALUES (:order_no, :parent_no, :buyer_id, :merchant_id, :status,
                         :subtotal, :shipping, :discount, :wallet,
                         :coup_id, :coup_code, :coup_amt, :tax,
                         :total, :addr_id, NOW())',
                [
                    'order_no'    => $childOrderNo,
                    'parent_no'   => $parentOrderNo,
                    'buyer_id'    => $buyerId,
                    'merchant_id' => (int)$group['merchant_id'],
                    'status'      => $orderStatus,
                    'subtotal'    => $group['subtotal'],
                    'shipping'    => $group['shipping'],
                    'discount'    => $groupDisc,
                    'wallet'      => $groupWallet,
                    'coup_id'     => $postCoupon ? (int)$postCoupon['id'] : null,
                    'coup_code'   => $postCoupon ? (string)$postCoupon['code'] : null,
                    'coup_amt'    => $groupCoup,
                    'tax'         => $groupGst,
                    'total'       => $groupFinal,
                    'addr_id'     => $addressId,
                ]
            );
            $orderId = (int)$db->lastInsertId();
            $createdOrderIds[] = $orderId;

            foreach ($group['items'] as $it) {
                $p = $it['product'];
                [$linePct, $lineComm, $linePay] = compute_line_commission(
                    (float)$it['line_total'],
                    product_commission_pct($db, (int)$p['id'])
                );
                db_exec($db,
                    'INSERT INTO order_items
                        (order_id, product_id, qty_kg, price_per_kg,
                         line_original, line_discount, discount_pct_applied,
                         line_total, commission_pct, commission_amount, seller_payable, created_at)
                     VALUES
                        (:order_id, :product_id, :qty_kg, :price_per_kg,
                         :line_original, :line_discount, :discount_pct,
                         :line_total, :cpct, :camt, :pay, NOW())',
                    [
                        'order_id'      => $orderId,
                        'product_id'    => (int)$p['id'],
                        'qty_kg'        => $it['qty_kg'],
                        'price_per_kg'  => (float)$p['price_per_kg'],
                        'line_original' => (float)($it['line_original'] ?? $it['line_total']),
                        'line_discount' => (float)($it['line_discount'] ?? 0),
                        'discount_pct'  => (float)($it['discount_pct']  ?? 0),
                        'line_total'    => $it['line_total'],
                        'cpct'          => $linePct,
                        'camt'          => $lineComm,
                        'pay'           => $linePay,
                    ]
                );
            }

            db_exec($db,
                'INSERT INTO payments (order_id, provider, status, amount, created_at)
                 VALUES (:order_id, :provider, :status, :amount, NOW())',
                [
                    'order_id' => $orderId,
                    'provider' => $paymentMethod,
                    'status'   => 'initiated',
                    'amount'   => $groupFinal,
                ]
            );

            if ($paymentMethod === 'cod') {
                try {
                    require_once __DIR__ . '/../../lib/inventory.php';
                    inventory_sale_for_order($db, $orderId);
                } catch (Throwable $t) {
                    // Stock deduction must not abort a placed order, but it
                    // must not fail silently either — this catch hid a nested
                    // "There is already an active transaction" for every COD
                    // order, so stock never moved.
                    error_log('inventory_sale_for_order failed for order ' . $orderId . ': ' . $t->getMessage());
                }
                // Ring the merchant's popup. COD only: the order is actionable
                // the moment it's placed. Razorpay orders ring from
                // razorpay-verify.php instead, once payment is confirmed —
                // otherwise an abandoned draft would ring the bell.
                alert_push_new_order($db, $orderId);
            }

            // Notify the merchant about their new order and the buyer about
            // confirmation. Both use this merchant's SMTP. Failures are logged
            // but do not abort checkout.
            try { notify_merchant_new_order($db, $orderId); } catch (Throwable $t) { error_log('notify_merchant_new_order failed: ' . $t->getMessage()); }
            try { notify_order_event($db, $orderId, 'order_placed'); } catch (Throwable $t) { error_log('notify_order_event(order_placed) failed: ' . $t->getMessage()); }
        }

        // Attach the referral / wallet ledgers to the first (parent) order
        $firstOrderId = (int)($createdOrderIds[0] ?? 0);
        if ($pendingReferral && $discountTotal > 0 && $firstOrderId > 0) {
            db_exec($db, 'UPDATE referrals SET referee_order_id = :oid, discount_amount = :disc WHERE id = :id', [
                'oid'  => $firstOrderId,
                'disc' => $discountTotal,
                'id'   => (int)$pendingReferral['id'],
            ]);
        }
        if ($walletApplied > 0 && $firstOrderId > 0) {
            $ok = wallet_debit_if_sufficient(
                $db, $buyerId, $walletApplied,
                'Used for order #' . $parentOrderNo, 'order', $firstOrderId
            );
            if (!$ok) {
                throw new RuntimeException('Wallet balance insufficient. Please refresh and try again.');
            }
        }

        if ($postCoupon) {
            coupon_record_usage($db, (int)$postCoupon['id']);
        }

        // Auto-applied product coupons that were baked into cart line prices
        // — count each one once per order so usage_limit is respected.
        $recordedAuto = [];
        foreach (($totals['items'] ?? []) as $it) {
            $acid = (int)($it['auto_coupon_id'] ?? 0);
            if ($acid > 0 && !isset($recordedAuto[$acid])) {
                coupon_record_usage($db, $acid);
                $recordedAuto[$acid] = true;
            }
        }

        // Defence in depth: if some helper still slips DDL in and implicitly
        // commits, the rows are already durable — don't turn a placed order
        // into an error page by calling commit() on a dead transaction.
        if ($db->inTransaction()) {
            $db->commit();
        }

        unset($_SESSION['applied_coupon_id']);
        cart_clear();
        $successMsg = $isMultiMerchant
            ? count($createdOrderIds) . ' orders placed (one per seller).'
            : 'Order placed.';
        if ($discountTotal > 0) {
            $successMsg .= ' Referral discount of ₹' . number_format($discountTotal, 2) . ' applied!';
        }
        if ($walletApplied > 0) {
            $successMsg .= ' ₹' . number_format($walletApplied, 2) . ' deducted from wallet.';
        }
        if ($paymentMethod === 'cod') {
            $successMsg .= ' Pay cash on delivery.';
        } elseif ($isMultiMerchant) {
            $successMsg .= ' Razorpay payment for split orders will be available once sellers connect their accounts.';
        }
        flash_set('success', $successMsg);
        redirect_to('home');
    } catch (Throwable $t) {
        // Log FIRST: helpers called inside the transaction run DDL
        // (CREATE TABLE IF NOT EXISTS / ALTER TABLE), and MySQL implicitly
        // commits on DDL. That leaves PDO thinking a transaction is open when
        // the server has none, so rollBack() throws "There is no active
        // transaction" — which used to escape this handler as an uncaught
        // fatal (HTTP 500) and swallow the real error before it was logged.
        error_log('checkout failed: ' . $t->getMessage() . ' @ ' . $t->getFile() . ':' . $t->getLine());
        if ($db->inTransaction()) {
            try {
                $db->rollBack();
            } catch (Throwable $r) {
                error_log('checkout rollback failed: ' . $r->getMessage());
            }
        }
        flash_set('error', 'Checkout failed: ' . $t->getMessage());
        redirect_to('buyer/checkout');
    }
}

$discountPctDisplay = $referralDiscount ? (float)$referralDiscount['referee_discount_pct'] : 0;
$canEnterReferral = $isFirstOrder && !$referralDiscount;

$content = function () use (
    $totals, $addresses, $selectedAddressId, $referralDiscount, $discountAmount, $discountPctDisplay,
    $walletBalance, $walletDeduction, $finalTotal, $items, $canEnterReferral, $razorpayEnabled,
    $merchantGroups, $isMultiMerchant,
    $couponApplied, $couponDiscount, $gstRate, $gstAmount
) {
    $error = flash_get('error');
    $success = flash_get('success');
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
      --success: #15803D;
      --success-bg: #ECFDF5;
      --danger: #DC2626;
      --warning-bg: #FFFBEB;
      --warning-text: #92400E;
      color: var(--ink);
      background: #FFFFFF;
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
    }
    body.bg-light:has(.km-shop) { background:#fff !important; }
    .km-shop .container { max-width: 1180px; }

    .km-page-head { display:flex; align-items:flex-end; justify-content:space-between; margin: 16px 0 22px; gap: 12px; flex-wrap: wrap; }
    .km-page-head h1 { font-size: 1.75rem; font-weight: 700; margin: 0; letter-spacing: -0.01em; }
    .km-page-head .km-sub { color: var(--muted); font-size: .92rem; margin-top: 4px; }

    .km-steps { display:flex; align-items:center; gap: 10px; font-size: .85rem; color: var(--muted); margin-bottom: 18px; }
    .km-steps .step { display:inline-flex; align-items:center; gap: 6px; }
    .km-steps .step.done { color: var(--success); }
    .km-steps .step.curr { color: var(--ink); font-weight: 600; }
    .km-steps .dot { width: 18px; height: 18px; border-radius:50%; border:1px solid var(--line-strong); display:inline-flex; align-items:center; justify-content:center; font-size: .7rem; }
    .km-steps .step.done .dot { background: var(--success); color:#fff; border-color: var(--success); }
    .km-steps .step.curr .dot { background: var(--ink); color:#fff; border-color: var(--ink); }
    .km-steps .sep { flex: 0 0 24px; height: 1px; background: var(--line); }

    .km-card { background: #fff; border: 1px solid var(--line); border-radius: 12px; }
    .km-card-pad { padding: 22px; }
    .km-card + .km-card { margin-top: 14px; }
    .km-section-h { font-size: .78rem; font-weight: 600; letter-spacing: .04em; text-transform: uppercase; color: var(--muted); margin: 0 0 14px; }

    /* Address radios */
    .km-address-list { display:flex; flex-direction: column; gap: 10px; }
    .km-address {
      display:flex; gap: 12px; padding: 14px 16px; border:1px solid var(--line); border-radius: 10px; cursor: pointer;
      background:#fff; transition: border-color .15s, background .15s;
    }
    .km-address:hover { border-color: var(--line-strong); }
    .km-address input[type=radio] { margin-top: 3px; accent-color: var(--primary); }
    .km-address input[type=radio]:checked + .km-addr-body { color: var(--ink); }
    .km-address:has(input:checked) { border-color: var(--primary); background: #FFFAF0; }
    .km-addr-label { font-weight: 600; margin-bottom: 2px; }
    .km-addr-text { color: var(--muted); font-size: .88rem; line-height: 1.4; }

    /* Referral code input */
    .km-ref-row { display:flex; gap: 8px; }
    .km-input {
      flex:1; padding: 10px 12px; border:1px solid var(--line-strong); border-radius: 8px; font-size: .95rem;
      background:#fff; outline: none; transition: border-color .15s;
      letter-spacing: .04em; text-transform: uppercase;
    }
    .km-input:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(243,146,0,.15); }
    .km-ref-help { font-size: .82rem; color: var(--muted); margin-top: 8px; }

    /* Summary */
    .km-summary-row { display:flex; justify-content: space-between; padding: 7px 0; font-size: .92rem; color: var(--muted); }
    .km-summary-row .v { color: var(--ink); font-weight: 500; }
    .km-summary-row.discount { color: var(--success); }
    .km-summary-row.discount .v { color: var(--success); }
    .km-summary-divider { border-top: 1px solid var(--line); margin: 8px 0; }
    .km-summary-total { display:flex; justify-content: space-between; padding: 10px 0 4px; }
    .km-summary-total .l { font-weight: 700; font-size: 1.05rem; }
    .km-summary-total .v { font-weight: 700; font-size: 1.2rem; }

    /* Items mini list */
    .km-mini-item { display:flex; gap: 12px; padding: 10px 0; border-bottom: 1px solid var(--line); font-size: .88rem; }
    .km-mini-item:last-child { border-bottom: 0; padding-bottom: 0; }
    .km-mini-item img { width: 44px; height: 44px; border-radius: 6px; object-fit: cover; flex-shrink: 0; }
    .km-mini-item .ph { width:44px; height:44px; border-radius:6px; background: var(--bg-soft); display:flex; align-items:center; justify-content:center; color: var(--line-strong); flex-shrink: 0; }
    .km-mini-info { flex:1; }
    .km-mini-name { font-weight: 600; color: var(--ink); }
    .km-mini-meta { color: var(--muted); font-size: .8rem; }

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

    /* Alerts */
    .km-alert { padding: 12px 14px; border-radius: 8px; font-size: .9rem; margin-bottom: 14px; border:1px solid transparent; }
    .km-alert-danger { background: #FEF2F2; color: var(--danger); border-color: #FECACA; }
    .km-alert-success { background: var(--success-bg); color: var(--success); border-color: #BBF7D0; }
    .km-alert-warning { background: var(--warning-bg); color: var(--warning-text); border-color: #FDE68A; }

    /* Layout */
    .km-grid-2 { display: grid; grid-template-columns: 1.5fr 1fr; gap: 18px; }
    @media (max-width: 991px) {
      .km-grid-2 { grid-template-columns: 1fr; }
      .km-card[style*="sticky"] { position: static !important; }
    }
    @media (max-width: 575px) {
      .km-page-head h1 { font-size: 1.4rem; }
      .km-card-pad { padding: 16px; }
      .km-ref-row { flex-direction: column; }
      .km-ref-row .km-btn { width: 100%; }
    }
  </style>

  <div class="km-shop">
    <div class="container py-4">

      <div class="km-page-head">
        <div>
          <h1>Checkout</h1>
          <div class="km-sub">Review your order and confirm delivery</div>
        </div>
        <a class="km-btn km-btn-outline" href="?p=cart">
          <i data-lucide="arrow-left" style="width:16px;height:16px;"></i> Back to cart
        </a>
      </div>

      <div class="km-steps">
        <span class="step done"><span class="dot">✓</span> Cart</span>
        <span class="sep"></span>
        <span class="step curr"><span class="dot">2</span> Checkout</span>
        <span class="sep"></span>
        <span class="step"><span class="dot">3</span> Confirmation</span>
      </div>

      <?php if ($error): ?>
        <div class="km-alert km-alert-danger"><?= e($error) ?></div>
      <?php endif; ?>
      <?php if ($success): ?>
        <div class="km-alert km-alert-success"><?= e($success) ?></div>
      <?php endif; ?>

      <div class="km-grid-2">

        <!-- LEFT: forms -->
        <div>

          <!-- Delivery Address -->
          <div class="km-card km-card-pad">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <div class="km-section-h" style="margin-bottom: 0;">Delivery Address</div>
              <a class="km-btn km-btn-outline" style="padding: 6px 12px; font-size: .85rem;" href="?p=buyer/addresses">Manage</a>
            </div>

            <?php if (!$addresses): ?>
              <div style="padding: 16px 0; color: var(--muted);">
                No saved addresses yet. Please add one to continue.
              </div>
              <a class="km-btn km-btn-primary" href="?p=buyer/address-add">Add Address</a>
            <?php else: ?>
              <form method="post" id="km-checkout-form">
                <div class="km-address-list">
                  <?php foreach ($addresses as $a): ?>
                    <label class="km-address">
                      <input type="radio" name="address_id" value="<?= (int)$a['id'] ?>" <?= ((int)$a['id'] === $selectedAddressId) ? 'checked' : '' ?> required>
                      <div class="km-addr-body">
                        <div class="km-addr-label"><?= e((string)($a['label'] ?: 'Address')) ?></div>
                        <div class="km-addr-text">
                          <?= e((string)$a['address_line1']) ?><?= $a['address_line2'] ? ', ' . e((string)$a['address_line2']) : '' ?><br>
                          <?= e((string)$a['city']) ?>, <?= e((string)$a['state']) ?> &mdash; <?= e((string)$a['pincode']) ?>
                        </div>
                      </div>
                    </label>
                  <?php endforeach; ?>
                </div>
              </form>
            <?php endif; ?>
          </div>

          <!-- Payment Method -->
          <div class="km-card km-card-pad">
            <div class="km-section-h" style="margin-bottom: 8px;">Payment Method</div>
            <div class="km-address-list">
              <label class="km-address">
                <input type="radio" name="payment_method" value="razorpay" form="km-checkout-form" checked>
                <div class="km-addr-body">
                  <div class="km-addr-label">Pay Online (Razorpay)</div>
                  <div class="km-addr-text">Secure online payment (Credit/Debit Card, UPI, NetBanking)</div>
                </div>
              </label>
              <label class="km-address">
                <input type="radio" name="payment_method" value="cod" form="km-checkout-form">
                <div class="km-addr-body">
                  <div class="km-addr-label">Cash on Delivery (COD)</div>
                  <div class="km-addr-text">Pay with cash when your order arrives at your door</div>
                </div>
              </label>
            </div>
          </div>

          <!-- Coupon Code -->
          <div class="km-card km-card-pad">
            <div class="km-section-h">Have a Coupon Code?</div>
            <?php if ($couponApplied): ?>
              <div class="km-alert km-alert-success d-flex justify-content-between align-items-center" style="margin-bottom: 0;">
                <span><strong><?= e((string)$couponApplied['code']) ?></strong> applied — ₹<?= e(number_format($couponDiscount, 2)) ?> off</span>
                <form method="post" class="d-inline">
                  <input type="hidden" name="action" value="remove_coupon">
                  <button class="btn btn-sm btn-link text-danger p-0" type="submit">Remove</button>
                </form>
              </div>
            <?php else: ?>
              <form method="post" action="?p=buyer/checkout" class="km-ref-row">
                <input type="hidden" name="action" value="apply_coupon">
                <input class="km-input" type="text" name="coupon_code" placeholder="Enter code" maxlength="40" autocomplete="off">
                <button class="km-btn km-btn-outline" type="submit">Apply</button>
              </form>
            <?php endif; ?>
          </div>

          <!-- Referral Code -->
          <div class="km-card km-card-pad">
            <div class="km-section-h">Have a Referral Code?</div>
            <?php if ($referralDiscount): ?>
              <div class="km-alert km-alert-success" style="margin-bottom: 0;">
                Referral discount of <?= e(number_format($discountPctDisplay, 1)) ?>% has been applied to your order.
              </div>
            <?php elseif (!$canEnterReferral): ?>
              <div style="color: var(--muted); font-size: .9rem;">
                Referral discounts are available only on your first order.
              </div>
            <?php else: ?>
              <form method="post" action="?p=buyer/checkout" class="km-ref-row">
                <input type="hidden" name="action" value="apply_referral">
                <input class="km-input" type="text" name="referral_code" placeholder="Enter code (e.g. F9E9A3AE)" maxlength="12" autocomplete="off">
                <button class="km-btn km-btn-outline" type="submit">Apply</button>
              </form>
              <div class="km-ref-help">
                A friend's referral code (from their <em>Refer &amp; Earn</em> page) gives you a discount on your first order.
              </div>
            <?php endif; ?>
          </div>

          <!-- Place order CTA (mobile) -->
          <div class="d-block d-lg-none mt-3">
            <?php if ($addresses): ?>
              <button form="km-checkout-form" type="submit" class="km-btn km-btn-primary km-btn-block km-btn-lg">
                Place Order &mdash; ₹<span id="km-mobile-total"><?= e(number_format($finalTotal, 2)) ?></span>
              </button>
            <?php endif; ?>
          </div>
        </div>

        <!-- RIGHT: summary -->
        <div>
          <div class="km-card km-card-pad" style="position: sticky; top: 90px;">
            <div class="km-section-h">Order Summary</div>

            <div style="margin-bottom: 14px;">
              <?php foreach ($merchantGroups as $group): ?>
                <?php if ($isMultiMerchant): ?>
                  <div class="small text-muted d-flex align-items-center gap-1 mt-2 mb-1"
                       style="border-top: 1px dashed var(--line); padding-top: 8px;">
                    <i data-lucide="store" style="width:13px;height:13px;"></i>
                    Sold by <strong style="color:var(--ink);"><?= e((string)$group['merchant_business']) ?></strong>
                    <span class="ms-auto">₹<?= e(number_format((float)$group['total'], 2)) ?></span>
                  </div>
                <?php endif; ?>
                <?php foreach ($group['items'] as $it): $p = $it['product'];
                  // Mirror the cart's unit wording: packet products are counted in
                  // packets (with the pack weight shown), not kg. See app/pages/cart/view.php.
                  $mQtyNum  = (float)$it['qty_kg'];
                  $mQtyFmt  = rtrim(rtrim(number_format($mQtyNum, 2, '.', ''), '0'), '.');
                  $mSoldAs  = (string)($p['sold_as'] ?? 'bulk');
                  $mPackG   = (float)($p['pack_size_grams'] ?? 0);
                  if ($mSoldAs === 'packet' && $mPackG > 0) {
                      $mPackFmt = rtrim(rtrim(number_format($mPackG, 2, '.', ''), '0'), '.');
                      $mQtyText = $mQtyFmt . ' ' . ($mQtyNum == 1.0 ? 'packet' : 'packets') . ' (' . $mPackFmt . 'g)';
                  } else {
                      $mQtyText = $mQtyFmt . ' ' . product_unit_label($p['unit'] ?? 'kg');
                  }
                ?>
                  <div class="km-mini-item" data-qty="<?= e($mQtyFmt) ?>" data-unit-price="<?= e((string)$p['price_per_kg']) ?>">
                    <?php if (!empty($p['image_path'])): ?>
                      <img src="<?= e((string)$p['image_path']) ?>" alt="">
                    <?php else: ?>
                      <span class="ph"><i data-lucide="image" style="width:18px;height:18px;"></i></span>
                    <?php endif; ?>
                    <div class="km-mini-info">
                      <div class="km-mini-name"><?= e((string)$p['name']) ?></div>
                      <div class="km-mini-meta"><?= e($mQtyText) ?> × ₹<?= e((string)$p['price_per_kg']) ?></div>
                    </div>
                    <div style="font-weight: 600;">₹<?= e((string)$it['line_total']) ?></div>
                  </div>
                <?php endforeach; ?>
              <?php endforeach; ?>
            </div>
            <?php if ($isMultiMerchant): ?>
              <div class="alert alert-info py-2 px-3 small mb-2">
                <i data-lucide="info" style="width:13px;height:13px;"></i>
                <?= count($merchantGroups) ?> sellers — <strong><?= count($merchantGroups) ?> orders</strong> will be created.
                You pay once; Manikya Market settles each seller behind the scenes.
              </div>
            <?php endif; ?>

            <div class="km-summary-divider"></div>

            <div class="km-summary-row"><span>Subtotal</span><span class="v" id="km-summary-subtotal">₹<?= e((string)$totals['subtotal']) ?></span></div>
            <div class="km-summary-row"><span>Shipping</span><span class="v" id="km-summary-shipping">₹<?= e((string)$totals['shipping']) ?></span></div>
            <div class="km-summary-row"><span>GST (<?= e(number_format($gstRate, 0)) ?>%)</span><span class="v">₹<?= e(number_format($gstAmount, 2)) ?></span></div>

            <?php if ($couponApplied && $couponDiscount > 0): ?>
              <div class="km-summary-row discount">
                <span>Coupon <strong><?= e((string)$couponApplied['code']) ?></strong></span>
                <span class="v">−₹<?= e(number_format($couponDiscount, 2)) ?></span>
              </div>
            <?php endif; ?>

            <div class="km-summary-row discount" id="km-summary-referral-row" style="<?= ($referralDiscount && $discountAmount > 0) ? 'display: flex;' : 'display: none;' ?>">
              <span>Referral discount (<?= e(number_format($discountPctDisplay, 1)) ?>%)</span>
              <span class="v" id="km-summary-referral">−₹<?= e(number_format($discountAmount, 2)) ?></span>
            </div>

            <div class="km-summary-row discount" id="km-summary-wallet-row" style="<?= ($walletDeduction > 0) ? 'display: flex;' : 'display: none;' ?>">
              <span>Wallet (₹<?= e(number_format($walletBalance, 2)) ?> available)</span>
              <span class="v" id="km-summary-wallet">−₹<?= e(number_format($walletDeduction, 2)) ?></span>
            </div>

            <div class="km-summary-divider"></div>
            <div class="km-summary-total">
              <span class="l">Total to pay</span>
              <span class="v" id="km-summary-total">₹<?= e(number_format($finalTotal, 2)) ?></span>
            </div>

            <?php if ($addresses): ?>
              <button form="km-checkout-form" type="submit" class="km-btn km-btn-primary km-btn-block km-btn-lg d-none d-lg-flex" style="margin-top: 18px;">
                <?= $razorpayEnabled ? 'Pay Now' : 'Place Order' ?>
              </button>
            <?php endif; ?>

            <div style="font-size: .8rem; color: var(--muted); margin-top: 12px; line-height: 1.4;">
              <?= $razorpayEnabled
                ? 'Secure payment powered by Razorpay.'
                : 'Razorpay payment will be enabled once the merchant links their account in profile.' ?>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>

  <?php if ($razorpayEnabled): ?>
  <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
  <script>
    (function () {
      var form = document.getElementById('km-checkout-form');
      if (!form) return;

      function showError(msg) {
        var existing = document.getElementById('km-pay-error');
        if (existing) existing.remove();
        var el = document.createElement('div');
        el.id = 'km-pay-error';
        el.className = 'km-alert km-alert-danger';
        el.textContent = msg;
        form.parentNode.insertBefore(el, form);
        window.scrollTo({ top: 0, behavior: 'smooth' });
      }

      function disableButtons(disabled) {
        document.querySelectorAll('button[form="km-checkout-form"]').forEach(function (b) {
          b.disabled = disabled;
          b.style.opacity = disabled ? '0.6' : '';
          b.style.cursor = disabled ? 'wait' : '';
        });
      }

      document.querySelectorAll('input[name="payment_method"]').forEach(function (radio) {
        radio.addEventListener('change', function () {
          var isCod = (this.value === 'cod');
          document.querySelectorAll('button[form="km-checkout-form"]').forEach(function (b) {
            b.innerHTML = isCod ? 'Place Order (COD)' : 'Pay Now';
          });
        });
      });

      form.addEventListener('submit', function (ev) {
        var addrInput = form.querySelector('input[name="address_id"]:checked');
        if (!addrInput) {
          ev.preventDefault();
          showError('Please select a delivery address.');
          return;
        }

        var pmInput = document.querySelector('input[name="payment_method"]:checked');
        var isCod = pmInput && pmInput.value === 'cod';

        if (isCod) {
          disableButtons(true);
          return; // Let browser submit natively
        }

        ev.preventDefault();
        disableButtons(true);

        var data = new FormData();
        data.append('address_id', addrInput.value);

        fetch('?p=buyer/razorpay-create', {
          method: 'POST',
          credentials: 'same-origin',
          body: data
        })
          .then(function (r) { return r.json(); })
          .then(function (j) {
            if (!j.ok) {
              disableButtons(false);
              showError(j.error || 'Failed to start payment.');
              return;
            }
            var options = {
              key: j.key_id,
              amount: j.amount,
              currency: j.currency,
              order_id: j.razorpay_order_id,
              name: j.name,
              description: j.description,
              prefill: j.prefill,
              theme: { color: '#F39200' },
              handler: function (response) {
                var verify = new FormData();
                verify.append('razorpay_order_id', response.razorpay_order_id);
                verify.append('razorpay_payment_id', response.razorpay_payment_id);
                verify.append('razorpay_signature', response.razorpay_signature);
                fetch('?p=buyer/razorpay-verify', {
                  method: 'POST',
                  credentials: 'same-origin',
                  body: verify
                })
                  .then(function (r) { return r.json(); })
                  .then(function (vj) {
                    if (vj.ok) {
                      if (window.fbq) {
                        fbq('track', 'Purchase', {
                          value: j.amount / 100,
                          currency: j.currency || 'INR'
                        });
                      }
                      window.location.href = vj.redirect || '?p=home';
                    } else {
                      disableButtons(false);
                      showError(vj.error || 'Payment verification failed.');
                    }
                  })
                  .catch(function () {
                    disableButtons(false);
                    showError('Payment verification network error. If amount was charged, please contact support.');
                  });
              },
              modal: {
                ondismiss: function () { disableButtons(false); }
              }
            };
            try {
              var rzp = new Razorpay(options);
              rzp.on('payment.failed', function (resp) {
                disableButtons(false);
                showError('Payment failed: ' + (resp.error && resp.error.description ? resp.error.description : 'Unknown error'));
              });
              rzp.open();
            } catch (e) {
              disableButtons(false);
              showError('Could not open Razorpay. Please refresh and try again.');
            }
          })
          .catch(function () {
            disableButtons(false);
            showError('Network error. Please try again.');
          });
      });
    })();
  </script>
  <?php endif; ?>

  <script>
    if (window.lucide) window.lucide.createIcons();
  </script>

  <script>
    (function () {
      var form = document.getElementById('km-checkout-form');
      if (!form) return;

      function updateTotals(addressId) {
        document.querySelectorAll('button[form="km-checkout-form"]').forEach(function (b) {
          b.disabled = true;
          b.style.opacity = '0.6';
        });

        var data = new FormData();
        data.append('address_id', addressId);

        fetch('?p=buyer/checkout-totals', {
          method: 'POST',
          credentials: 'same-origin',
          body: data
        })
        .then(function (r) { return r.json(); })
        .then(function (j) {
          document.querySelectorAll('button[form="km-checkout-form"]').forEach(function (b) {
            b.disabled = false;
            b.style.opacity = '';
          });

          if (!j.ok) return;

          var subtotalEl = document.getElementById('km-summary-subtotal');
          if (subtotalEl) subtotalEl.textContent = '₹' + parseFloat(j.subtotal).toFixed(2);

          var shippingEl = document.getElementById('km-summary-shipping');
          if (shippingEl) shippingEl.textContent = '₹' + parseFloat(j.shipping).toFixed(2);

          var refRow = document.getElementById('km-summary-referral-row');
          var refVal = document.getElementById('km-summary-referral');
          if (refRow && refVal) {
            if (parseFloat(j.discount) > 0) {
              refRow.style.display = 'flex';
              refRow.querySelector('span:first-child').textContent = 'Referral discount (' + parseFloat(j.discount_pct).toFixed(1) + '%)';
              refVal.textContent = '−₹' + parseFloat(j.discount).toFixed(2);
            } else {
              refRow.style.display = 'none';
            }
          }

          var walletRow = document.getElementById('km-summary-wallet-row');
          var walletVal = document.getElementById('km-summary-wallet');
          if (walletRow && walletVal) {
            if (parseFloat(j.wallet_deduction) > 0) {
              walletRow.style.display = 'flex';
              walletRow.querySelector('span:first-child').textContent = 'Wallet (₹' + parseFloat(j.wallet_balance).toFixed(2) + ' available)';
              walletVal.textContent = '−₹' + parseFloat(j.wallet_deduction).toFixed(2);
            } else {
              walletRow.style.display = 'none';
            }
          }

          var totalEl = document.getElementById('km-summary-total');
          if (totalEl) totalEl.textContent = '₹' + parseFloat(j.total).toFixed(2);

          var mobileTotalEl = document.getElementById('km-mobile-total');
          if (mobileTotalEl) mobileTotalEl.textContent = parseFloat(j.total).toFixed(2);
        })
        .catch(function (err) {
          document.querySelectorAll('button[form="km-checkout-form"]').forEach(function (b) {
            b.disabled = false;
            b.style.opacity = '';
          });
          console.error(err);
        });
      }

      document.querySelectorAll('input[name="address_id"]').forEach(function (radio) {
        radio.addEventListener('change', function () {
          updateTotals(this.value);
        });
      });

      var checkedAddr = form.querySelector('input[name="address_id"]:checked');
      if (checkedAddr) {
        updateTotals(checkedAddr.value);
      }
    })();
  </script>

  <script>
  (function () {

    function getCheckoutItems() {
      var items = [];
      document.querySelectorAll('.km-mini-item').forEach(function (row, i) {
        var nameEl = row.querySelector('.km-mini-name');
        var metaEl = row.querySelector('.km-mini-meta');
        var name      = nameEl ? nameEl.textContent.trim() : '';
        // Qty/price come from data-* attributes: the meta text varies by unit
        // (kg, packets, pieces) and is not safe to parse.
        var unitPrice = parseFloat(row.getAttribute('data-unit-price')) || 0;
        if (!unitPrice && metaEl) {
          var parts = metaEl.textContent.split('×');
          if (parts[1]) unitPrice = parseFloat(parts[1].replace(/[^0-9.]/g, '')) || 0;
        }
        var qty = parseFloat(row.getAttribute('data-qty')) || 0;
        if (!qty) qty = 1;
        items.push({
          item_id:       'mango_' + name.toLowerCase().replace(/\s+/g, '_'),
          item_name:     name,
          item_category: 'Mango',
          price:         unitPrice,
          quantity:      qty
        });
      });
      return items;
    }

    function getTotal() {
      var totalEl = document.querySelector('.km-summary-total .v');
      return totalEl ? parseFloat(totalEl.textContent.replace(/[^0-9.]/g, '')) || 0 : 0;
    }

    var checkoutItems = getCheckoutItems();
    var orderTotal    = getTotal();

    // 1. BEGIN CHECKOUT — fires on page load
    gtag('event', 'begin_checkout', { currency: 'INR', value: orderTotal, items: checkoutItems });
    if (window.fbq) {
      fbq('track', 'InitiateCheckout', {
        value: orderTotal, currency: 'INR', num_items: checkoutItems.length,
        content_ids: checkoutItems.map(function(i){ return i.item_name; })
      });
    }

    // 2. PAY NOW CLICKED
    var form = document.getElementById('km-checkout-form');
    if (form) {
      form.addEventListener('submit', function () {
        gtag('event', 'add_payment_info', { currency: 'INR', value: orderTotal, payment_type: 'Razorpay', items: checkoutItems });
      });
    }

    // 3. PAYMENT FAILED
    if (window.Razorpay) {
      var _origRazorpay = window.Razorpay;
      window.Razorpay = function (options) {
        var rzp = new _origRazorpay(options);
        rzp.on('payment.failed', function (resp) {
          gtag('event', 'payment_failed', {
            currency:       'INR',
            value:          orderTotal,
            failure_reason: resp.error && resp.error.description ? resp.error.description : 'Unknown'
          });
        });
        return rzp;
      };
    }

    // 4. ADDRESS SELECTED
    document.querySelectorAll('input[name="address_id"]').forEach(function (radio) {
      radio.addEventListener('change', function () {
        gtag('event', 'select_delivery_address', { address_id: radio.value });
      });
    });

    // 5. BACK TO CART
    var backBtn = document.querySelector('a[href="?p=cart"]');
    if (backBtn) {
      backBtn.addEventListener('click', function () {
        gtag('event', 'checkout_back_to_cart', { cart_value: orderTotal });
      });
    }

  })();
  </script>
<?php
};

require __DIR__ . '/../../views/layout.php';
