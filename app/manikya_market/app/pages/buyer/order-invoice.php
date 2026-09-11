<?php

declare(strict_types=1);

// Allow buyer (their own order) or logistics (delivery person).
$role = auth_role();
if ($role !== 'buyer' && $role !== 'logistics') {
    redirect_to('buyer/login');
}

$id = (int)($_GET['id'] ?? 0);
$buyerId = (int)auth_user_id();

// Scope: buyers may only view invoices for their own orders.
// Logistics users see any order they're assigned to (delhivery_waybill set).
if ($role === 'buyer') {
    $order = db_fetch_one($db,
        'SELECT o.*, u.full_name AS buyer_name, u.phone AS buyer_phone, u.email AS buyer_email
         FROM orders o
         JOIN users u ON u.id = o.buyer_id
         WHERE o.id = :id AND o.buyer_id = :bid LIMIT 1',
        ['id' => $id, 'bid' => $buyerId]
    );
} else {
    $order = db_fetch_one($db,
        'SELECT o.*, u.full_name AS buyer_name, u.phone AS buyer_phone, u.email AS buyer_email
         FROM orders o
         JOIN users u ON u.id = o.buyer_id
         WHERE o.id = :id LIMIT 1',
        ['id' => $id]
    );
}
if (!$order) {
    redirect_to($role === 'buyer' ? 'buyer/orders' : 'logistics/dashboard');
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
$backHref = $fullBase . '/?p=' . ($role === 'buyer' ? 'buyer/orders' : 'logistics/dashboard');

ensure_order_column($db, 'invoice_generated_at');
ensure_order_column($db, 'invoice_file', 'VARCHAR(255) NULL');

require __DIR__ . '/../../views/partials/invoice-template.php';
