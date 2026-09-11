<?php

declare(strict_types=1);

auth_require_role('merchant');

$merchantId = (int)auth_user_id();
$id = (int)($_GET['id'] ?? 0);

// Scope by merchant_id — sellers may only view invoices for orders they own.
$order = db_fetch_one($db,
    'SELECT o.*, u.full_name AS buyer_name, u.phone AS buyer_phone, u.email AS buyer_email
     FROM orders o
     JOIN users u ON u.id = o.buyer_id
     WHERE o.id = :id AND o.merchant_id = :mid LIMIT 1',
    ['id' => $id, 'mid' => $merchantId]
);
if (!$order) {
    flash_set('error', 'Order not found or you do not have access to it.');
    redirect_to('merchant/dashboard');
}

$address = null;
if (!empty($order['delivery_address_id'])) {
    $address = db_fetch_one($db, 'SELECT * FROM buyer_addresses WHERE id = :id LIMIT 1', ['id' => (int)$order['delivery_address_id']]);
}

$items = db_fetch_all($db,
    'SELECT oi.id, oi.product_id, oi.qty_kg, oi.price_per_kg, oi.line_total,
            p.name, p.unit, p.part_code, p.product_type
     FROM order_items oi
     JOIN products p ON p.id = oi.product_id
     WHERE oi.order_id = :order_id ORDER BY oi.id ASC',
    ['order_id' => $id]
) ?? [];

// Sold By = the PLATFORM (Super Admin), not the individual seller.
// Pulls the super-admin's merchant_profile row (where business name, address,
// PAN/GST/CIN, logo are stored for the marketplace itself).
$company = db_fetch_one($db,
    "SELECT mp.* FROM merchant_profile mp
     JOIN users u ON u.id = mp.merchant_user_id
     WHERE u.role = 'super_admin'
     ORDER BY u.id ASC LIMIT 1"
) ?: [];

$platformSettings = db_fetch_one($db, 'SELECT gst_rate_pct FROM platform_settings LIMIT 1') ?: [];
$gstRate = (float)($platformSettings['gst_rate_pct'] ?? 18);

$payment = db_fetch_one($db,
    "SELECT provider, status, amount, razorpay_payment_id, razorpay_order_id, created_at
     FROM payments WHERE order_id = :order_id ORDER BY id DESC LIMIT 1",
    ['order_id' => (int)$order['id']]
) ?: [];

$title = 'Invoice ' . (string)($order['order_no'] ?? '');
$proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && (string)$_SERVER['SERVER_PORT'] === '443') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? 'localhost');
$fullBase = $proto . '://' . $host . rtrim(app_base_path($config), '/');
$backHref = $fullBase . '/?p=merchant/order&id=' . (int)$id;

ensure_order_column($db, 'invoice_generated_at');
if (empty($order['invoice_generated_at'])) {
    try {
        $db->prepare('UPDATE orders SET invoice_generated_at = NOW() WHERE id = :id')->execute(['id' => $id]);
    } catch (Throwable $e) { /* ignore */ }
}
ensure_order_column($db, 'invoice_file', 'VARCHAR(255) NULL');

// Render via the shared partial, capturing the HTML so we can also save it.
ob_start();
require __DIR__ . '/../../views/partials/invoice-template.php';
$invoiceHtml = (string)ob_get_clean();

$uploadsDir = __DIR__ . '/../../../uploads/invoices';
if (!is_dir($uploadsDir)) { @mkdir($uploadsDir, 0755, true); }
$fileNameBase = 'invoice-' . (int)$id;
$fileUrl = null;

if (class_exists('\\Dompdf\\Dompdf')) {
    try {
        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($invoiceHtml);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $filePath = $uploadsDir . DIRECTORY_SEPARATOR . $fileNameBase . '.pdf';
        file_put_contents($filePath, $dompdf->output());
        $fileUrl = app_base_path($config) . '/uploads/invoices/' . $fileNameBase . '.pdf';
    } catch (Throwable $e) { $fileUrl = null; }
}
if (empty($fileUrl)) {
    $filePath = $uploadsDir . DIRECTORY_SEPARATOR . $fileNameBase . '.html';
    @file_put_contents($filePath, $invoiceHtml);
    $fileUrl = app_base_path($config) . '/uploads/invoices/' . $fileNameBase . '.html';
}

try {
    $db->prepare('UPDATE orders SET invoice_file = :f WHERE id = :id')->execute(['f' => $fileUrl, 'id' => $id]);
} catch (Throwable $e) { /* ignore */ }

echo $invoiceHtml;
