<?php

declare(strict_types=1);

auth_require_role('buyer');
header('Content-Type: application/json');

$buyerId = (int)auth_user_id();

// 1. Platform Razorpay must be configured for buyer payments to route here.
$config = platform_razorpay_get_config($db);
if (!$config) {
    echo json_encode(['ok' => false, 'error' => 'Razorpay is not configured by the platform admin yet.']);
    exit;
}

// 2. Resolve delivery address.
$addressId = (int)($_POST['address_id'] ?? $_GET['address_id'] ?? 0);
$address = db_fetch_one($db, 'SELECT * FROM buyer_addresses WHERE id = :id AND buyer_id = :bid LIMIT 1', [
    'id' => $addressId, 'bid' => $buyerId,
]);
if (!$address) {
    echo json_encode(['ok' => false, 'error' => 'Please select a delivery address.']);
    exit;
}

$destPin = (string)($address['pincode'] ?? '');

// 3. Cart + per-merchant grouping.
$totals = $destPin ? cart_totals_with_delhivery($db, $destPin) : cart_totals($db);
$items  = $totals['items'];
if (!$items) {
    echo json_encode(['ok' => false, 'error' => 'Cart is empty.']);
    exit;
}
$merchantGroups = cart_groups_by_merchant($items);

// 4. Recompute discount + wallet across the whole cart total.
$discountAmount = 0.0;
$referralDiscount = null;
if (referral_is_first_order($db, $buyerId)) {
    $referralDiscount = referral_get_pending_discount($db, $buyerId);
    if ($referralDiscount) {
        $discountAmount = round($totals['subtotal'] * ((float)$referralDiscount['referee_discount_pct'] / 100), 2);
    }
}
$walletBalance   = wallet_balance($db, $buyerId);
$afterDiscount   = max(0, round($totals['total'] - $discountAmount, 2));
$walletDeduction = ($walletBalance > 0 && $afterDiscount > 0) ? min($walletBalance, $afterDiscount) : 0.0;
$finalTotal      = max(0, round($afterDiscount - $walletDeduction, 2));

if ($finalTotal <= 0) {
    echo json_encode(['ok' => false, 'error' => 'Order total is zero. No payment required.']);
    exit;
}

$amountPaise   = (int)round($finalTotal * 100);
$cartTotal     = (float)$totals['total'];
$parentOrderNo = 'MM' . date('ymdHis') . random_int(100, 999);

ensure_table_column($db, 'orders', 'discount_amount', 'DECIMAL(10,2) NOT NULL DEFAULT 0');
ensure_table_column($db, 'orders', 'wallet_deduction', 'DECIMAL(10,2) NOT NULL DEFAULT 0');
razorpay_ensure_columns($db);

// 5. Create N per-merchant orders (status='new') + Razorpay order, atomically.
$db->beginTransaction();
try {
    $createdOrderIds = [];
    $groupIndex = 0;

    foreach ($merchantGroups as $group) {
        $groupIndex++;
        $groupTotal = (float)$group['total'];
        $sharePct   = $cartTotal > 0 ? $groupTotal / $cartTotal : 0.0;
        $groupDisc  = round($discountAmount * $sharePct, 2);
        $groupWall  = round($walletDeduction * $sharePct, 2);
        $groupFinal = max(0.0, round($groupTotal - $groupDisc - $groupWall, 2));
        $childOrderNo = $parentOrderNo . '-' . $groupIndex;

        db_exec($db,
            'INSERT INTO orders (order_no, parent_order_no, buyer_id, merchant_id, status,
                                 subtotal_amount, shipping_amount, discount_amount, wallet_deduction,
                                 total_amount, delivery_address_id, created_at)
             VALUES (:on, :pn, :bid, :mid, :st, :sub, :ship, :disc, :wall, :tot, :addr, NOW())',
            [
                'on' => $childOrderNo, 'pn' => $parentOrderNo,
                'bid' => $buyerId, 'mid' => (int)$group['merchant_id'],
                'st' => 'new',
                'sub' => $group['subtotal'], 'ship' => $group['shipping'],
                'disc' => $groupDisc, 'wall' => $groupWall, 'tot' => $groupFinal,
                'addr' => $addressId,
            ]
        );
        $orderId = (int)$db->lastInsertId();
        $createdOrderIds[] = $orderId;

        foreach ($group['items'] as $it) {
            $p = $it['product'];
            // Commission is calculated on the DISCOUNTED price (line_subtotal
            // → line_total), so seller absorbs the category discount. If we
            // ever want the platform to eat the discount instead, base
            // commission on line_original here.
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
                    (:oid, :pid, :qty, :pp,
                     :lorig, :ldisc, :dpct,
                     :lt, :cpct, :camt, :pay, NOW())',
                [
                    'oid'   => $orderId, 'pid' => (int)$p['id'],
                    'qty'   => $it['qty_kg'], 'pp' => (float)$p['price_per_kg'],
                    'lorig' => (float)($it['line_original'] ?? $it['line_total']),
                    'ldisc' => (float)($it['line_discount'] ?? 0),
                    'dpct'  => (float)($it['discount_pct']  ?? 0),
                    'lt'    => $it['line_total'],
                    'cpct'  => $linePct, 'camt' => $lineComm, 'pay' => $linePay,
                ]
            );
        }
    }

    // 6. Call Razorpay (platform's account) to create one combined order.
    $rzOrder = razorpay_create_order(
        $config['key_id'], $config['key_secret'], $amountPaise, $parentOrderNo
    );
    if (!$rzOrder || empty($rzOrder['id'])) {
        $db->rollBack();
        echo json_encode(['ok' => false, 'error' => 'Failed to create payment order with Razorpay. Check platform keys.']);
        exit;
    }

    // 7. Insert one payment row per merchant order, all sharing the same Razorpay order id.
    foreach ($createdOrderIds as $i => $oid) {
        $shareGroup = array_values($merchantGroups)[$i];
        $shareTotal = (float)$shareGroup['total'];
        $sharePct   = $cartTotal > 0 ? $shareTotal / $cartTotal : 0.0;
        $shareDisc  = round($discountAmount * $sharePct, 2);
        $shareWall  = round($walletDeduction * $sharePct, 2);
        $shareFinal = max(0.0, round($shareTotal - $shareDisc - $shareWall, 2));

        db_exec($db,
            'INSERT INTO payments (order_id, provider, status, amount, razorpay_order_id, created_at)
             VALUES (:oid, :prov, :st, :amt, :rz, NOW())',
            ['oid' => $oid, 'prov' => 'razorpay', 'st' => 'initiated', 'amt' => $shareFinal, 'rz' => $rzOrder['id']]
        );
    }

    // 8. Apply referral / wallet to the first (parent) order only.
    $firstOrderId = (int)($createdOrderIds[0] ?? 0);
    if ($referralDiscount && $discountAmount > 0 && $firstOrderId > 0) {
        db_exec($db, 'UPDATE referrals SET referee_order_id = :oid, discount_amount = :disc WHERE id = :id', [
            'oid' => $firstOrderId, 'disc' => $discountAmount, 'id' => (int)$referralDiscount['id'],
        ]);
    }
    if ($walletDeduction > 0 && $firstOrderId > 0) {
        // Race-safe: aborts the whole checkout if the buyer's wallet doesn't
        // actually have the funds. Prevents two parallel checkouts from spending
        // the same wallet balance twice.
        $ok = wallet_debit_if_sufficient(
            $db, $buyerId, $walletDeduction,
            'Used for order #' . $parentOrderNo, 'order', $firstOrderId
        );
        if (!$ok) {
            throw new RuntimeException('Wallet balance insufficient. Please refresh and try again.');
        }
    }

    // Auto-applied product coupons (Amazon-style deals): count each once per
    // order so usage_limit is honoured for the next buyer.
    $recordedAuto = [];
    foreach ($items as $it) {
        $acid = (int)($it['auto_coupon_id'] ?? 0);
        if ($acid > 0 && !isset($recordedAuto[$acid])) {
            coupon_record_usage($db, $acid);
            $recordedAuto[$acid] = true;
        }
    }

    $db->commit();
} catch (Throwable $t) {
    $db->rollBack();
    error_log('razorpay-create failed: ' . $t->getMessage());
    echo json_encode(['ok' => false, 'error' => 'Failed to record order. Please try again.']);
    exit;
}

// 9. Buyer info for Razorpay prefill.
$buyerInfo = db_fetch_one($db, 'SELECT full_name, email, phone FROM users WHERE id = :id LIMIT 1', ['id' => $buyerId]);

echo json_encode([
    'ok' => true,
    'razorpay_order_id' => $rzOrder['id'],
    'key_id'            => $config['key_id'],
    'amount'            => $amountPaise,
    'currency'          => 'INR',
    'order_no'          => $parentOrderNo,
    'name'              => 'Manikya Market',
    'description'       => 'Order ' . $parentOrderNo,
    'prefill'           => [
        'name'    => (string)($buyerInfo['full_name'] ?? ''),
        'email'   => (string)($buyerInfo['email'] ?? ''),
        'contact' => (string)($buyerInfo['phone'] ?? ''),
    ],
]);
exit;
