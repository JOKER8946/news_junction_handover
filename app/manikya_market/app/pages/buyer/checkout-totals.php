<?php

declare(strict_types=1);

auth_require_role('buyer');
header('Content-Type: application/json');

$buyerId = auth_user_id();

$totals = cart_totals($db);
$items = $totals['items'];

if (!$items) {
    echo json_encode(['ok' => false, 'error' => 'Cart is empty.']);
    exit;
}

$addressId = (int)($_POST['address_id'] ?? $_GET['address_id'] ?? 0);
$address = null;
if ($addressId > 0) {
    $address = db_fetch_one($db, 'SELECT * FROM buyer_addresses WHERE id = :id AND buyer_id = :bid LIMIT 1', [
        'id' => $addressId, 'bid' => $buyerId,
    ]);
}

$destPin = $address ? (string)($address['pincode'] ?? '') : '';

// Calculate shipping via Delhivery or fallback
$totals = $destPin ? cart_totals_with_delhivery($db, $destPin) : cart_totals($db);

// Referral discount
$discountAmount = 0.0;
$discountPctDisplay = 0.0;
$isFirstOrder = referral_is_first_order($db, $buyerId);
if ($isFirstOrder) {
    $referralDiscount = referral_get_pending_discount($db, $buyerId);
    if ($referralDiscount) {
        $discountPctDisplay = (float)$referralDiscount['referee_discount_pct'];
        $discountAmount = round($totals['subtotal'] * ($discountPctDisplay / 100), 2);
    }
}

// Wallet
$walletBalance = wallet_balance($db, $buyerId);
$afterDiscount = round($totals['total'] - $discountAmount, 2);
if ($afterDiscount < 0) {
    $afterDiscount = 0;
}
$walletDeduction = 0.0;
if ($walletBalance > 0 && $afterDiscount > 0) {
    $walletDeduction = min($walletBalance, $afterDiscount);
}
$finalTotal = round($afterDiscount - $walletDeduction, 2);
if ($finalTotal < 0) {
    $finalTotal = 0;
}

echo json_encode([
    'ok' => true,
    'subtotal' => $totals['subtotal'],
    'shipping' => $totals['shipping'],
    'discount' => $discountAmount,
    'discount_pct' => $discountPctDisplay,
    'wallet_balance' => $walletBalance,
    'wallet_deduction' => $walletDeduction,
    'total' => $finalTotal,
    'serviceable' => (bool)($totals['serviceable'] ?? true),
]);
exit;
