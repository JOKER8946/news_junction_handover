<?php

declare(strict_types=1);

/**
 * Razorpay integration helpers.
 * Reads credentials from merchant_profile.razorpay_key_id / razorpay_key_secret.
 */

function razorpay_ensure_columns(PDO $db): void
{
    static $done = false;
    if ($done) return;
    $done = true;
    try {
        $row = db_fetch_one($db, 'SELECT DATABASE() AS d');
        $dbName = (string)($row['d'] ?? '');
        if ($dbName === '') return;
        foreach ([
            ['razorpay_order_id', 'VARCHAR(60) NULL'],
            ['razorpay_payment_id', 'VARCHAR(60) NULL'],
            ['razorpay_signature', 'VARCHAR(190) NULL'],
        ] as $col) {
            $exists = db_fetch_one($db, 'SELECT COUNT(*) AS c FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :t AND COLUMN_NAME = :c', [
                'db' => $dbName, 't' => 'payments', 'c' => $col[0],
            ]);
            if ((int)($exists['c'] ?? 0) === 0) {
                $db->exec("ALTER TABLE payments ADD COLUMN `{$col[0]}` {$col[1]}");
            }
        }
    } catch (Throwable $t) { /* ignore */ }
}

function razorpay_get_config(PDO $db): ?array
{
    try {
        $row = db_fetch_one($db, "
            SELECT mp.razorpay_key_id, mp.razorpay_key_secret
            FROM merchant_profile mp
            JOIN users u ON u.id = mp.merchant_user_id
            WHERE u.role = 'merchant'
            ORDER BY mp.id ASC LIMIT 1
        ");
        if (!$row) return null;
        $keyId = trim((string)($row['razorpay_key_id'] ?? ''));
        $keySecret = trim((string)($row['razorpay_key_secret'] ?? ''));
        if ($keyId === '' || $keySecret === '') return null;
        return ['key_id' => $keyId, 'key_secret' => $keySecret];
    } catch (Throwable $t) { return null; }
}

/**
 * Get the Razorpay credentials for a specific merchant. Returns null if the
 * merchant hasn't connected their Razorpay account yet.
 */
function razorpay_get_config_for_merchant(PDO $db, int $merchantId): ?array
{
    if ($merchantId <= 0) return null;
    try {
        $row = db_fetch_one($db,
            'SELECT razorpay_key_id, razorpay_key_secret FROM merchant_profile WHERE merchant_user_id = :uid LIMIT 1',
            ['uid' => $merchantId]
        );
        if (!$row) return null;
        $keyId = trim((string)($row['razorpay_key_id'] ?? ''));
        $keySecret = trim((string)($row['razorpay_key_secret'] ?? ''));
        if ($keyId === '' || $keySecret === '') return null;
        return ['key_id' => $keyId, 'key_secret' => $keySecret];
    } catch (Throwable $t) { return null; }
}

function razorpay_create_order(string $keyId, string $keySecret, int $amountPaise, string $receipt): ?array
{
    $payload = json_encode([
        'amount' => $amountPaise,
        'currency' => 'INR',
        'receipt' => substr($receipt, 0, 40),
        'payment_capture' => 1,
    ]);
    $ch = curl_init('https://api.razorpay.com/v1/orders');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_USERPWD => $keyId . ':' . $keySecret,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT => 20,
    ]);
    $resp = curl_exec($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $errno = curl_errno($ch);
    curl_close($ch);
    if ($errno !== 0 || $httpCode < 200 || $httpCode >= 300 || !$resp) {
        error_log("razorpay_create_order failed: http={$httpCode} resp={$resp}");
        return null;
    }
    $data = json_decode((string)$resp, true);
    if (!is_array($data) || empty($data['id'])) return null;
    return $data;
}

function razorpay_verify_signature(string $orderId, string $paymentId, string $signature, string $keySecret): bool
{
    $expected = hash_hmac('sha256', $orderId . '|' . $paymentId, $keySecret);
    return hash_equals($expected, $signature);
}

function razorpay_refund_payment(string $keyId, string $keySecret, string $paymentId, int $amountPaise): bool
{
    return razorpay_refund_payment_full($keyId, $keySecret, $paymentId, $amountPaise)['ok'];
}

/**
 * Issue a refund and return Razorpay's full response (including refund id).
 * Use this from the refund flow so we can persist the refund_id and update
 * payments.status atomically.
 */
function razorpay_refund_payment_full(string $keyId, string $keySecret, string $paymentId, int $amountPaise): array
{
    $payload = json_encode([
        'amount' => $amountPaise,
        'speed' => 'normal',
    ]);

    $ch = curl_init("https://api.razorpay.com/v1/payments/{$paymentId}/refund");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_USERPWD => $keyId . ':' . $keySecret,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT => 20,
    ]);
    $resp = curl_exec($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $errno = curl_errno($ch);
    curl_close($ch);

    if ($errno !== 0 || $httpCode < 200 || $httpCode >= 300 || !$resp) {
        error_log("razorpay_refund_payment failed: http={$httpCode} resp={$resp}");
        return ['ok' => false, 'http' => $httpCode, 'body' => $resp ?: ''];
    }

    $data = json_decode((string)$resp, true);
    if (!is_array($data) || empty($data['id'])) {
        return ['ok' => false, 'http' => $httpCode, 'body' => $resp ?: ''];
    }

    return ['ok' => true, 'http' => $httpCode, 'refund_id' => (string)$data['id'], 'data' => $data];
}

/**
 * Record a refund against a payment row atomically. Idempotent: calling twice
 * with the same razorpay_payment_id is a no-op if the row is already 'refunded'.
 */
function razorpay_record_refund(PDO $db, string $razorpayPaymentId, string $refundId, int $amountPaise): bool
{
    if ($razorpayPaymentId === '') return false;
    try {
        // Add refund_id column on first call (best-effort).
        ensure_table_column($db, 'payments', 'refund_id', "VARCHAR(60) NULL");
        ensure_table_column($db, 'payments', 'refunded_at', 'DATETIME NULL');
    } catch (Throwable $t) { /* ignore */ }

    try {
        $stmt = $db->prepare(
            "UPDATE payments
                SET status      = 'refunded',
                    refund_id   = :rid,
                    refunded_at = NOW()
              WHERE razorpay_payment_id = :pid
                AND status = 'paid'"
        );
        $stmt->execute(['rid' => $refundId, 'pid' => $razorpayPaymentId]);
        return $stmt->rowCount() > 0;
    } catch (Throwable $t) {
        error_log('razorpay_record_refund failed: ' . $t->getMessage());
        return false;
    }
}
