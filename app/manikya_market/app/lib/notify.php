<?php

declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;

/**
 * Insert a row into notification_logs. Returns the new id.
 */
function notify_log(PDO $db, array $fields): int
{
    $defaults = [
        'channel'       => 'email',
        'provider'      => 'smtp',
        'to_address'    => '',
        'message'       => '',
        'order_id'      => null,
        'merchant_id'   => null,
        'recipient_role'=> null,
        'status'        => 'queued',
        'error_message' => null,
    ];
    $row = array_merge($defaults, $fields);

    db_exec($db,
        'INSERT INTO notification_logs
            (channel, to_address, message, provider, order_id, merchant_id, recipient_role, status, error_message, created_at)
         VALUES
            (:channel, :to_address, :message, :provider, :order_id, :merchant_id, :recipient_role, :status, :error, NOW())',
        [
            'channel'    => $row['channel'],
            'to_address' => substr((string)$row['to_address'], 0, 190),
            'message'    => (string)$row['message'],
            'provider'   => $row['provider'],
            'order_id'   => $row['order_id'],
            'merchant_id'=> $row['merchant_id'],
            'recipient_role' => $row['recipient_role'],
            'status'     => $row['status'],
            'error'      => $row['error_message'] !== null ? substr((string)$row['error_message'], 0, 255) : null,
        ]
    );
    return (int)$db->lastInsertId();
}

/**
 * Send an email. Tries platform SMTP first (from platform_settings); falls
 * back to the merchant's own SMTP if the platform isn't configured. Records
 * the attempt in notification_logs regardless of outcome.
 */
function notify_send_mail_via_merchant(
    PDO $db,
    int $merchantId,
    string $toEmail,
    string $toName,
    string $subject,
    string $bodyHtml,
    ?int $orderId = null,
    ?string $recipientRole = null
): bool {
    $logBase = [
        'channel'        => 'email',
        'provider'       => 'smtp',
        'to_address'     => $toEmail,
        'message'        => $subject . "\n\n" . substr(strip_tags($bodyHtml), 0, 1500),
        'order_id'       => $orderId,
        'merchant_id'    => $merchantId,
        'recipient_role' => $recipientRole,
    ];

    if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
        notify_log($db, array_merge($logBase, ['status' => 'failed', 'error_message' => 'invalid recipient']));
        return false;
    }

    // Platform SMTP first (preferred — single source of truth).
    $cfg = function_exists('platform_smtp_get_config') ? platform_smtp_get_config($db) : null;
    $source = 'platform';
    if (!$cfg) {
        // Fall back to the merchant's own SMTP if platform isn't configured yet.
        $cfg = smtp_get_config_for_merchant($db, $merchantId);
        $source = 'merchant';
    }
    if (!$cfg) {
        notify_log($db, array_merge($logBase, ['status' => 'failed', 'error_message' => 'no SMTP configured (platform or merchant)']));
        error_log("notify: no SMTP configured for merchant {$merchantId} and no platform SMTP");
        return false;
    }

    $vendor = __DIR__ . '/../../vendor/autoload.php';
    if (is_file($vendor)) {
        require_once $vendor;
    }
    if (!class_exists(PHPMailer::class)) {
        notify_log($db, array_merge($logBase, ['status' => 'failed', 'error_message' => 'PHPMailer not installed']));
        return false;
    }

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = $cfg['host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $cfg['username'];
        $mail->Password   = $cfg['password'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = (int)$cfg['port'];
        $mail->setFrom($cfg['from_email'], $cfg['from_name']);
        $mail->addAddress($toEmail, $toName);
        $mail->Subject = $subject;
        $mail->isHTML(true);
        $mail->Body    = $bodyHtml;
        $mail->AltBody = trim(strip_tags(str_replace(['<br>', '</p>', '</li>'], "\n", $bodyHtml)));
        $mail->send();

        notify_log($db, array_merge($logBase, ['status' => 'sent', 'error_message' => 'via ' . $source]));
        return true;
    } catch (Throwable $t) {
        $err = 'via ' . $source . ': ' . ($mail->ErrorInfo ?: '') . ' | ' . $t->getMessage();
        notify_log($db, array_merge($logBase, ['status' => 'failed', 'error_message' => $err]));
        error_log('notify_send_mail failed: ' . $err);
        return false;
    }
}

/**
 * Backwards-compatible wrapper: emails using the first merchant's SMTP.
 * Retained so existing callers keep working until they migrate to the
 * per-merchant version.
 */
function notify_send_mail(PDO $db, string $toEmail, string $toName, string $subject, string $bodyHtml): bool
{
    $cfg = smtp_get_config($db);
    if (!$cfg) {
        error_log('notify_send_mail: SMTP not configured');
        return false;
    }

    $vendor = __DIR__ . '/../../vendor/autoload.php';
    if (is_file($vendor)) {
        require_once $vendor;
    }
    if (!class_exists(PHPMailer::class)) {
        error_log('notify_send_mail: PHPMailer not installed');
        return false;
    }

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = $cfg['host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $cfg['username'];
        $mail->Password   = $cfg['password'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = (int)$cfg['port'];
        $mail->setFrom($cfg['from_email'], $cfg['from_name']);
        $mail->addAddress($toEmail, $toName);
        $mail->Subject = $subject;
        $mail->isHTML(true);
        $mail->Body    = $bodyHtml;
        $mail->AltBody = trim(strip_tags(str_replace(['<br>', '</p>', '</li>'], "\n", $bodyHtml)));
        $mail->send();
        return true;
    } catch (Throwable $t) {
        error_log('notify_send_mail failed: ' . $mail->ErrorInfo . ' | ' . $t->getMessage());
        return false;
    }
}

/**
 * Build the HTML body for one order. Used by both the merchant new-order
 * notification and the buyer order confirmation.
 */
function _notify_render_order_html(PDO $db, array $order, string $headline, string $intro, string $brandName): string
{
    $items = db_fetch_all($db,
        'SELECT oi.qty_kg, oi.price_per_kg, oi.line_total, p.name AS product_name
         FROM order_items oi
         LEFT JOIN products p ON p.id = oi.product_id
         WHERE oi.order_id = :oid',
        ['oid' => (int)$order['id']]
    );

    $address = null;
    if (!empty($order['delivery_address_id'])) {
        $address = db_fetch_one($db,
            'SELECT label, full_name, phone, address_line1, address_line2, city, state, pincode
             FROM buyer_addresses WHERE id = :id LIMIT 1',
            ['id' => (int)$order['delivery_address_id']]
        );
    }

    $orderNo  = htmlspecialchars((string)$order['order_no']);
    $subtotal = number_format((float)$order['subtotal_amount'], 2);
    $shipping = number_format((float)$order['shipping_amount'], 2);
    $total    = number_format((float)$order['total_amount'], 2);

    $rows = '';
    foreach ($items as $it) {
        $rows .= '<tr>'
            . '<td style="padding:8px 4px;border-bottom:1px solid #eee;">' . htmlspecialchars((string)($it['product_name'] ?? 'Product')) . '</td>'
            . '<td style="padding:8px 4px;border-bottom:1px solid #eee;text-align:center;">' . htmlspecialchars((string)$it['qty_kg']) . '</td>'
            . '<td style="padding:8px 4px;border-bottom:1px solid #eee;text-align:right;">&#8377;' . number_format((float)$it['price_per_kg'], 2) . '</td>'
            . '<td style="padding:8px 4px;border-bottom:1px solid #eee;text-align:right;font-weight:600;">&#8377;' . number_format((float)$it['line_total'], 2) . '</td>'
            . '</tr>';
    }

    $addressBlock = '';
    if ($address) {
        $addressBlock = '<div style="margin-top:18px;padding:12px;background:#F9FAFB;border-radius:8px;">'
            . '<div style="font-size:12px;color:#6B7280;text-transform:uppercase;letter-spacing:.04em;font-weight:600;margin-bottom:6px;">Delivery address</div>'
            . '<div style="font-weight:600;">' . htmlspecialchars((string)($address['full_name'] ?? '')) . '</div>'
            . '<div style="color:#374151;font-size:14px;">'
            .   htmlspecialchars((string)($address['address_line1'] ?? '')) . ', '
            .   ($address['address_line2'] ? htmlspecialchars((string)$address['address_line2']) . ', ' : '')
            .   htmlspecialchars((string)($address['city'] ?? '')) . ', '
            .   htmlspecialchars((string)($address['state'] ?? '')) . ' &mdash; '
            .   htmlspecialchars((string)($address['pincode'] ?? ''))
            . '</div>'
            . ($address['phone'] ? '<div style="color:#6B7280;font-size:13px;margin-top:4px;">Phone: ' . htmlspecialchars((string)$address['phone']) . '</div>' : '')
            . '</div>';
    }

    return '<div style="font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,Helvetica,Arial,sans-serif;color:#111827;max-width:600px;margin:0 auto;padding:24px;">'
        . '<div style="font-weight:700;color:#F39200;font-size:22px;margin-bottom:18px;">' . htmlspecialchars($brandName) . '</div>'
        . '<h2 style="font-size:18px;margin:0 0 6px;">' . htmlspecialchars($headline) . '</h2>'
        . '<p style="color:#374151;margin:0 0 14px;">' . htmlspecialchars($intro) . '</p>'
        . '<p style="margin:0 0 14px;">Order number: <strong>' . $orderNo . '</strong></p>'
        . '<table style="width:100%;border-collapse:collapse;margin-top:8px;">'
        .   '<thead><tr style="background:#F9FAFB;color:#6B7280;font-size:12px;text-transform:uppercase;letter-spacing:.04em;">'
        .     '<th style="text-align:left;padding:8px 4px;">Item</th>'
        .     '<th style="text-align:center;padding:8px 4px;">Qty</th>'
        .     '<th style="text-align:right;padding:8px 4px;">Unit</th>'
        .     '<th style="text-align:right;padding:8px 4px;">Line</th>'
        .   '</tr></thead>'
        .   '<tbody>' . $rows . '</tbody>'
        . '</table>'
        . '<div style="margin-top:14px;text-align:right;color:#374151;font-size:14px;">'
        .   '<div>Subtotal: <strong>&#8377;' . $subtotal . '</strong></div>'
        .   '<div>Shipping: <strong>&#8377;' . $shipping . '</strong></div>'
        .   '<div style="margin-top:4px;font-size:16px;">Total: <strong>&#8377;' . $total . '</strong></div>'
        . '</div>'
        . $addressBlock
        . '<hr style="border:none;border-top:1px solid #E5E7EB;margin:24px 0 12px;">'
        . '<div style="color:#6B7280;font-size:12px;">Manikya Market &middot; You are receiving this because of an order on your store.</div>'
        . '</div>';
}

/**
 * Email the merchant about a new order they just received.
 */
function notify_merchant_new_order(PDO $db, int $orderId): bool
{
    $order = db_fetch_one($db,
        "SELECT o.*, mp.business_name AS merchant_business, mu.email AS merchant_email, mu.full_name AS merchant_name
         FROM orders o
         LEFT JOIN merchant_profile mp ON mp.merchant_user_id = o.merchant_id
         LEFT JOIN users mu ON mu.id = o.merchant_id
         WHERE o.id = :id LIMIT 1",
        ['id' => $orderId]
    );

    if (!$order || empty($order['merchant_email']) || empty($order['merchant_id'])) {
        return false;
    }

    $merchantBusiness = (string)($order['merchant_business'] ?? $order['merchant_name'] ?? 'Your store');
    $subject = 'New order #' . (string)$order['order_no'] . ' on ' . $merchantBusiness;
    $headline = 'You have a new order';
    $intro = 'A buyer just placed an order with ' . $merchantBusiness . '. Log in to your dashboard to confirm and arrange shipment.';

    $body = _notify_render_order_html($db, (array)$order, $headline, $intro, $merchantBusiness);

    return notify_send_mail_via_merchant(
        $db,
        (int)$order['merchant_id'],
        (string)$order['merchant_email'],
        (string)($order['merchant_name'] ?? $merchantBusiness),
        $subject,
        $body,
        $orderId,
        'merchant'
    );
}

/**
 * Send a transactional email to the BUYER for an order event.
 * Uses the merchant's SMTP (per-merchant order). Returns true on send success.
 * $event: 'order_placed' | 'shipped' | 'delivered'
 */
function notify_order_event(PDO $db, int $orderId, string $event): bool
{
    $order = db_fetch_one($db, '
        SELECT o.*, u.full_name AS buyer_name, u.email AS buyer_email,
               mp.business_name AS merchant_business
        FROM orders o
        JOIN users u ON u.id = o.buyer_id
        LEFT JOIN merchant_profile mp ON mp.merchant_user_id = o.merchant_id
        WHERE o.id = :id LIMIT 1
    ', ['id' => $orderId]);

    if (!$order || empty($order['buyer_email']) || empty($order['merchant_id'])) {
        return false;
    }

    $orderNo = (string)$order['order_no'];
    $merchantBusiness = (string)($order['merchant_business'] ?? 'Your seller');

    if ($event === 'order_placed') {
        $subject  = "Order #{$orderNo} confirmed";
        $headline = 'Thanks ' . ((string)$order['buyer_name'] ?: 'there') . ', your order is confirmed.';
        $intro    = 'Sold by ' . $merchantBusiness . '. We will notify you again when it ships.';
    } elseif ($event === 'shipped') {
        $awb = (string)($order['delhivery_waybill'] ?? '');
        $subject  = "Order #{$orderNo} shipped";
        $headline = 'Your order from ' . $merchantBusiness . ' is on its way.';
        $intro    = $awb !== '' ? 'Tracking (AWB): ' . $awb : 'Tracking will be available shortly.';
    } elseif ($event === 'delivered') {
        $subject  = "Order #{$orderNo} delivered";
        $headline = 'Your order has been delivered.';
        $intro    = 'Thanks for shopping ' . $merchantBusiness . ' on Manikya Market.';
    } else {
        return false;
    }

    $body = _notify_render_order_html($db, (array)$order, $headline, $intro, 'Manikya Market');

    return notify_send_mail_via_merchant(
        $db,
        (int)$order['merchant_id'],
        (string)$order['buyer_email'],
        (string)($order['buyer_name'] ?? ''),
        $subject,
        $body,
        $orderId,
        'buyer'
    );
}
