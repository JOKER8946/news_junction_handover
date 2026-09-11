<?php

declare(strict_types=1);

// Suppress every warning/notice/deprecation from polluting the JSON response.
@ini_set('display_errors', '0');
@ini_set('display_startup_errors', '0');
error_reporting(0);

// Log everything that goes through here so we can debug user-reported failures.
$rzpLog = function (string $msg) {
    @file_put_contents('/var/lib/php-uploads/_rzp_verify.log',
        gmdate('H:i:s') . ' ' . $msg . PHP_EOL, FILE_APPEND);
    @chmod('/var/lib/php-uploads/_rzp_verify.log', 0666);
};
$rzpLog('--- request start ---');

ob_start();
header('Content-Type: application/json');

$send = function (array $payload, int $code = 200) use ($rzpLog): void {
    // Flush every nested output buffer (auto-buffer + our own + any nested).
    while (ob_get_level() > 0) { ob_end_clean(); }
    if (!headers_sent()) {
        http_response_code($code);
        header('Content-Type: application/json');
    }
    $json = json_encode($payload);
    $rzpLog('SEND code=' . $code . ' body=' . substr((string)$json, 0, 300));
    echo $json;
    exit;
};

set_exception_handler(function (Throwable $t) use ($send, $rzpLog) {
    $rzpLog('UNCAUGHT ' . get_class($t) . ': ' . $t->getMessage() . ' @ ' . $t->getFile() . ':' . $t->getLine());
    $send(['ok' => false, 'error' => 'Server error during verification: ' . $t->getMessage()], 500);
});

register_shutdown_function(function () use ($rzpLog) {
    $e = error_get_last();
    if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        $rzpLog('SHUTDOWN FATAL: ' . json_encode($e));
    }
});

if (auth_user_id() === null || auth_role() !== 'buyer') {
    $send(['ok' => false, 'error' => 'Session expired. Please log in again.'], 401);
}

$buyerId = (int)auth_user_id();

$config = platform_razorpay_get_config($db);
if (!$config) {
    $send(['ok' => false, 'error' => 'Razorpay is not configured.']);
}

$razorpayOrderId   = (string)($_POST['razorpay_order_id'] ?? '');
$razorpayPaymentId = (string)($_POST['razorpay_payment_id'] ?? '');
$razorpaySignature = (string)($_POST['razorpay_signature'] ?? '');

if ($razorpayOrderId === '' || $razorpayPaymentId === '' || $razorpaySignature === '') {
    $send(['ok' => false, 'error' => 'Missing payment fields.']);
}

if (!razorpay_verify_signature($razorpayOrderId, $razorpayPaymentId, $razorpaySignature, $config['key_secret'])) {
    $send(['ok' => false, 'error' => 'Payment signature mismatch.']);
}

// Find every payment (one per merchant order) linked to this Razorpay order.
$payments = db_fetch_all($db,
    'SELECT id, order_id FROM payments WHERE razorpay_order_id = :rz',
    ['rz' => $razorpayOrderId]
);
if (!$payments) {
    $send(['ok' => false, 'error' => 'Order not found.']);
}

// We process each merchant order independently. To avoid races (two concurrent
// verify requests from the same Razorpay order both decrementing inventory or
// double-crediting wallets), we use an atomic "claim" UPDATE on the payment row:
// only the thread that actually flips status from 'initiated' to 'paid' proceeds
// with inventory/wallet for that order. Inner helpers (inventory_sale_for_order,
// merchant_wallet_credit_for_order) each manage their own DB transaction; we do
// NOT wrap an outer transaction around them, because PDO doesn't allow nested
// begin/commit.
$orderIds      = [];
$parentOrderNo = '';
require_once __DIR__ . '/../../lib/inventory.php';

foreach ($payments as $pay) {
    $payId   = (int)$pay['id'];
    $orderId = (int)$pay['order_id'];

    // Ownership: every order must belong to this buyer.
    $order = db_fetch_one($db,
        'SELECT id, buyer_id, order_no, parent_order_no FROM orders WHERE id = :id LIMIT 1',
        ['id' => $orderId]
    );
    if (!$order || (int)$order['buyer_id'] !== $buyerId) {
        $rzpLog('OWNERSHIP_FAIL payId=' . $payId . ' orderId=' . $orderId . ' buyer=' . $buyerId);
        $send(['ok' => false, 'error' => 'Order ownership check failed.']);
    }
    if ($parentOrderNo === '') {
        $parentOrderNo = (string)($order['parent_order_no'] ?? $order['order_no']);
    }

    // Atomic claim. Only one concurrent thread will get rowCount > 0; the other
    // sees that the row was already flipped and skips inventory/wallet to avoid
    // double-processing.
    try {
        $stmt = $db->prepare(
            "UPDATE payments SET status = 'paid', razorpay_payment_id = :pid, razorpay_signature = :sig
             WHERE id = :id AND status = 'initiated'"
        );
        $stmt->execute(['pid' => $razorpayPaymentId, 'sig' => $razorpaySignature, 'id' => $payId]);
        $claimed = $stmt->rowCount() > 0;
    } catch (Throwable $t) {
        $rzpLog('CLAIM_FAIL payId=' . $payId . ': ' . $t->getMessage());
        $send(['ok' => false, 'error' => 'Failed to confirm payment.']);
    }

    if (!$claimed) {
        // Another concurrent request already processed this payment — that's fine,
        // include the order in the redirect bundle and move on.
        $orderIds[] = $orderId;
        continue;
    }

    // We won the claim — proceed with order status, inventory, wallet credits.
    try {
        db_exec($db, "UPDATE orders SET status = 'paid' WHERE id = :id", ['id' => $orderId]);
    } catch (Throwable $t) {
        $rzpLog('ORDER_PAID_UPDATE_FAIL orderId=' . $orderId . ': ' . $t->getMessage());
    }

    try {
        inventory_sale_for_order($db, $orderId);
    } catch (Throwable $t) {
        // Inventory failure is logged loudly so super-admin can reconcile —
        // the payment is already marked paid, so customer-facing flow continues.
        error_log('INVENTORY FAIL order=' . $orderId . ': ' . $t->getMessage());
        $rzpLog('INVENTORY_FAIL orderId=' . $orderId . ': ' . $t->getMessage());
    }

    try {
        merchant_wallet_credit_for_order($db, $orderId);
    } catch (Throwable $t) {
        // Wallet credit failure means merchant didn't get paid — flag for admin.
        error_log('WALLET CREDIT FAIL order=' . $orderId . ': ' . $t->getMessage());
        $rzpLog('WALLET_CREDIT_FAIL orderId=' . $orderId . ': ' . $t->getMessage());
    }

    // Ring the merchant's popup — payment is confirmed, so the order is real.
    // This sits inside the won-the-claim branch on purpose: $orderIds below
    // also collects orders whose claim was LOST to a concurrent request, and
    // alerting from there would ring twice for one order.
    alert_push_new_order($db, $orderId);

    $orderIds[] = $orderId;
}

// Fire notifications (merchant + buyer) for each order. Best-effort, after commit.
foreach ($orderIds as $oid) {
    try { notify_merchant_new_order($db, $oid); } catch (Throwable $t) { error_log('notify merchant new order: ' . $t->getMessage()); }
    try { notify_order_event($db, $oid, 'order_placed'); } catch (Throwable $t) { error_log('notify order_placed: ' . $t->getMessage()); }
}

try { cart_clear(); } catch (Throwable $t) { /* don't break the response */ }
try { flash_set('success', 'Payment successful. Order ' . $parentOrderNo . ' confirmed.'); } catch (Throwable $t) {}

$send([
    'ok'        => true,
    'order_no'  => $parentOrderNo,
    'order_ids' => $orderIds,
    'redirect'  => '?p=buyer/orders',
]);
