<?php

declare(strict_types=1);

// Razorpay webhook handler. Configure the endpoint in the Razorpay dashboard
// (Settings → Webhooks) as POST https://newsjunction.net/manikya_market/?p=webhook/razorpay
// with a Webhook Secret. Store the same secret in platform_settings (column
// razorpay_webhook_secret).
//
// We validate the X-Razorpay-Signature header and react to:
//   - payment.failed   → mark our payment row as 'failed' so the buyer sees a
//                        proper failed state, the order is auto-cancelled.
//   - refund.created   → record the refund id on our payment row.
//   - refund.processed → no-op (informational).
// Everything else is acknowledged with 200 OK and silently ignored.

@ini_set('display_errors', '0');
error_reporting(0);

$log = function (string $msg) {
    @file_put_contents('/var/lib/php-uploads/_rzp_webhook.log',
        gmdate('H:i:s') . ' ' . $msg . PHP_EOL, FILE_APPEND);
    @chmod('/var/lib/php-uploads/_rzp_webhook.log', 0666);
};
$log('--- webhook hit ---');

http_response_code(200);
header('Content-Type: application/json');

$raw = (string)file_get_contents('php://input');
$sigHeader = (string)($_SERVER['HTTP_X_RAZORPAY_SIGNATURE'] ?? '');

// Fetch the webhook secret from platform_settings (best-effort column).
$secret = '';
try {
    ensure_table_column($db, 'platform_settings', 'razorpay_webhook_secret', "VARCHAR(190) NULL");
    $row = db_fetch_one($db, 'SELECT razorpay_webhook_secret FROM platform_settings ORDER BY id ASC LIMIT 1');
    $secret = trim((string)($row['razorpay_webhook_secret'] ?? ''));
} catch (Throwable $t) { /* ignore */ }

if ($secret === '' || $sigHeader === '' || $raw === '') {
    $log('REJECT empty secret/sig/body');
    echo json_encode(['ok' => false]); exit;
}

$expected = hash_hmac('sha256', $raw, $secret);
if (!hash_equals($expected, $sigHeader)) {
    $log('REJECT signature mismatch');
    echo json_encode(['ok' => false]); exit;
}

$event = json_decode($raw, true);
if (!is_array($event)) {
    $log('REJECT non-json body');
    echo json_encode(['ok' => true]); exit;
}

$type = (string)($event['event'] ?? '');
$log('OK event=' . $type);

require_once __DIR__ . '/../../lib/razorpay.php';
razorpay_ensure_columns($db);

try {
    switch ($type) {
        case 'payment.failed': {
            $pay = $event['payload']['payment']['entity'] ?? [];
            $rzpOrderId   = (string)($pay['order_id']   ?? '');
            $rzpPaymentId = (string)($pay['id']         ?? '');
            $errCode      = (string)($pay['error_code'] ?? '');
            $errDesc      = (string)($pay['error_description'] ?? '');
            if ($rzpOrderId !== '') {
                // Mark all the per-merchant payment rows under this Razorpay
                // order as failed, and cancel their orders so the buyer can
                // retry. Atomic UPDATE — idempotent on retry of the webhook.
                $db->prepare(
                    "UPDATE payments p
                        JOIN orders o ON o.id = p.order_id
                        SET p.status = 'failed',
                            p.razorpay_payment_id = COALESCE(:pid, p.razorpay_payment_id),
                            o.status = 'cancelled',
                            o.cancelled_at = NOW(),
                            o.cancel_reason = CONCAT('Razorpay failure: ', LEFT(:desc, 100))
                      WHERE p.razorpay_order_id = :rz
                        AND p.status = 'initiated'"
                )->execute([
                    'rz'   => $rzpOrderId,
                    'pid'  => $rzpPaymentId !== '' ? $rzpPaymentId : null,
                    'desc' => $errDesc !== '' ? $errDesc : $errCode,
                ]);
            }
            break;
        }

        case 'refund.created':
        case 'refund.processed': {
            $refund = $event['payload']['refund']['entity'] ?? [];
            $rzpPaymentId = (string)($refund['payment_id'] ?? '');
            $refundId     = (string)($refund['id']         ?? '');
            $amountPaise  = (int)($refund['amount']        ?? 0);
            if ($rzpPaymentId !== '' && $refundId !== '') {
                razorpay_record_refund($db, $rzpPaymentId, $refundId, $amountPaise);
            }
            break;
        }

        default:
            // Acknowledge any other event without action.
            break;
    }
} catch (Throwable $t) {
    $log('handler error: ' . $t->getMessage());
}

echo json_encode(['ok' => true]);
