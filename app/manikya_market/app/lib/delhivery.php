<?php

declare(strict_types=1);

/**
 * Delhivery API Integration Library
 * All functions for interacting with Delhivery courier API.
 */

function delhivery_base_url(string $mode): string
{
    return $mode === 'production'
        ? 'https://track.delhivery.com'
        : 'https://staging-express.delhivery.com';
}

/**
 * Internal HTTP helper for Delhivery API calls.
 */
function _delhivery_request(string $method, string $url, string $token, $data = null, array $extraHeaders = []): array
{
    $ch = curl_init();

    $headers = [
        'Authorization: Token ' . $token,
        'Accept: application/json',
    ];
    $headers = array_merge($headers, $extraHeaders);

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

    if (strtoupper($method) === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if (is_string($data)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        } elseif (is_array($data)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        }
        if (!in_array('Content-Type: application/json', $extraHeaders, true)) {
            $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        }
    }

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $response = curl_exec($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        return ['success' => false, 'status_code' => 0, 'body' => null, 'error' => 'cURL error: ' . $curlError];
    }

    $body = json_decode((string)$response, true);

    return [
        'success' => $httpCode >= 200 && $httpCode < 300,
        'status_code' => $httpCode,
        'body' => $body ?? $response,
        'error' => $httpCode >= 400 ? ('HTTP ' . $httpCode . ': ' . substr((string)$response, 0, 300)) : null,
    ];
}

/**
 * Load Delhivery config from merchant_profile. Returns null if not configured.
 */
function delhivery_get_config(PDO $db): ?array
{
    $profile = db_fetch_one($db, '
        SELECT delhivery_api_token, delhivery_mode,
               delhivery_warehouse_name, delhivery_warehouse_address,
               delhivery_warehouse_city, delhivery_warehouse_state,
               delhivery_warehouse_pincode, delhivery_warehouse_phone,
               business_pincode
        FROM merchant_profile LIMIT 1
    ');

    if (!$profile || empty($profile['delhivery_api_token'])) {
        return null;
    }

    $mode = ($profile['delhivery_mode'] ?? 'sandbox') === 'production' ? 'production' : 'sandbox';

    return [
        'token' => (string)$profile['delhivery_api_token'],
        'mode' => $mode,
        'base_url' => delhivery_base_url($mode),
        'warehouse_name' => (string)($profile['delhivery_warehouse_name'] ?? ''),
        'warehouse_address' => (string)($profile['delhivery_warehouse_address'] ?? ''),
        'warehouse_city' => (string)($profile['delhivery_warehouse_city'] ?? ''),
        'warehouse_state' => (string)($profile['delhivery_warehouse_state'] ?? ''),
        'warehouse_pincode' => (string)($profile['delhivery_warehouse_pincode'] ?? $profile['business_pincode'] ?? ''),
        'warehouse_phone' => (string)($profile['delhivery_warehouse_phone'] ?? ''),
    ];
}

/**
 * Per-merchant Delhivery config. Returns null when the merchant hasn't
 * configured Delhivery — the caller should fall back to per-product
 * shipping rates rather than refusing checkout.
 */
function delhivery_get_config_for_merchant(PDO $db, int $merchantId): ?array
{
    if ($merchantId <= 0) return null;
    $profile = db_fetch_one($db, '
        SELECT delhivery_api_token, delhivery_mode,
               delhivery_warehouse_name, delhivery_warehouse_address,
               delhivery_warehouse_city, delhivery_warehouse_state,
               delhivery_warehouse_pincode, delhivery_warehouse_phone,
               business_pincode
        FROM merchant_profile WHERE merchant_user_id = :uid LIMIT 1
    ', ['uid' => $merchantId]);

    if (!$profile || empty($profile['delhivery_api_token'])) {
        return null;
    }

    $mode = ($profile['delhivery_mode'] ?? 'sandbox') === 'production' ? 'production' : 'sandbox';

    return [
        'token' => (string)$profile['delhivery_api_token'],
        'mode' => $mode,
        'base_url' => delhivery_base_url($mode),
        'warehouse_name' => (string)($profile['delhivery_warehouse_name'] ?? ''),
        'warehouse_address' => (string)($profile['delhivery_warehouse_address'] ?? ''),
        'warehouse_city' => (string)($profile['delhivery_warehouse_city'] ?? ''),
        'warehouse_state' => (string)($profile['delhivery_warehouse_state'] ?? ''),
        'warehouse_pincode' => (string)($profile['delhivery_warehouse_pincode'] ?? $profile['business_pincode'] ?? ''),
        'warehouse_phone' => (string)($profile['delhivery_warehouse_phone'] ?? ''),
    ];
}

/**
 * Ensure all Delhivery-related DB columns and tables exist.
 */
function delhivery_ensure_schema(PDO $db): void
{
    ensure_order_column($db, 'delhivery_waybill', 'VARCHAR(50) NULL');
    ensure_order_column($db, 'delhivery_shipment_id', 'VARCHAR(100) NULL');
    ensure_order_column($db, 'delhivery_status', 'VARCHAR(80) NULL');
    ensure_order_column($db, 'delhivery_status_synced_at', 'DATETIME NULL');
    ensure_order_column($db, 'delhivery_pickup_token', 'VARCHAR(100) NULL');

    ensure_table_column($db, 'merchant_profile', 'delhivery_api_token', 'VARCHAR(255) NULL');
    ensure_table_column($db, 'merchant_profile', 'delhivery_mode', "VARCHAR(20) NOT NULL DEFAULT 'sandbox'");
    ensure_table_column($db, 'merchant_profile', 'delhivery_warehouse_name', 'VARCHAR(150) NULL');
    ensure_table_column($db, 'merchant_profile', 'delhivery_warehouse_address', 'TEXT NULL');
    ensure_table_column($db, 'merchant_profile', 'delhivery_warehouse_city', 'VARCHAR(80) NULL');
    ensure_table_column($db, 'merchant_profile', 'delhivery_warehouse_state', 'VARCHAR(80) NULL');
    ensure_table_column($db, 'merchant_profile', 'delhivery_warehouse_pincode', 'VARCHAR(12) NULL');
    ensure_table_column($db, 'merchant_profile', 'delhivery_warehouse_phone', 'VARCHAR(30) NULL');

    try {
        $db->exec('
            CREATE TABLE IF NOT EXISTS `delhivery_tracking_events` (
              `id` INT AUTO_INCREMENT PRIMARY KEY,
              `order_id` INT NOT NULL,
              `waybill` VARCHAR(50) NOT NULL,
              `scan_type` VARCHAR(50) NULL,
              `scan_status` VARCHAR(200) NULL,
              `location` VARCHAR(200) NULL,
              `event_time` DATETIME NULL,
              `raw_json` TEXT NULL,
              `created_at` DATETIME NOT NULL,
              INDEX `idx_dte_order` (`order_id`),
              INDEX `idx_dte_waybill` (`waybill`),
              CONSTRAINT `fk_dte_order` FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ');
    } catch (Throwable $t) {
        // table may already exist
    }
}

/**
 * Check pincode serviceability using the Delhivery charges API.
 */
function delhivery_check_pincode(PDO $db, string $originPincode, string $destinationPincode): array
{
    $config = delhivery_get_config($db);
    if (!$config) {
        return ['serviceable' => true, 'prepaid' => true, 'cod' => false, 'error' => 'Delhivery not configured'];
    }

    $url = $config['base_url'] . '/api/kinko/v1/invoice/charges/.json?'
        . http_build_query([
            'md' => 'S',
            'ss' => 'Delivered',
            'd_pin' => $destinationPincode,
            'o_pin' => $originPincode,
            'cgm' => 500,
            'pt' => 'Pre-paid',
            'cod' => 0,
        ]);

    $resp = _delhivery_request('GET', $url, $config['token']);

    if (!$resp['success'] || !is_array($resp['body'])) {
        if (is_array($resp['body']) && isset($resp['body']['error'])) {
            return ['serviceable' => false, 'prepaid' => false, 'cod' => false, 'error' => null];
        }
        return ['serviceable' => true, 'prepaid' => true, 'cod' => false, 'error' => $resp['error'] ?? 'API call failed'];
    }

    $body = $resp['body'];

    if (isset($body['error'])) {
        return ['serviceable' => false, 'prepaid' => false, 'cod' => false, 'error' => null];
    }

    if (is_array($body) && !empty($body) && isset($body[0]['total_amount'])) {
        return [
            'serviceable' => true,
            'prepaid' => true,
            'cod' => false,
            'shipping_charge' => (float)($body[0]['total_amount'] ?? 0),
            'error' => null,
        ];
    }

    return ['serviceable' => false, 'prepaid' => false, 'cod' => false, 'error' => null];
}

/**
 * Get the live shipping charge for a destination pincode and weight.
 * Returns ['serviceable' => bool, 'shipping_charge' => float|null, 'error' => string|null].
 * On any failure, shipping_charge is null and the caller should fall back.
 */
function delhivery_get_shipping_charge(PDO $db, string $destinationPincode, int $weightGrams, string $mode = 'S'): array
{
    $config = delhivery_get_config($db);
    if (!$config) {
        return ['serviceable' => true, 'shipping_charge' => null, 'error' => 'Delhivery not configured'];
    }

    $originPincode = $config['warehouse_pincode'];
    if ($originPincode === '' || $destinationPincode === '') {
        return ['serviceable' => true, 'shipping_charge' => null, 'error' => 'Missing pincode'];
    }

    if ($weightGrams < 100) {
        $weightGrams = 500;
    }

    $url = $config['base_url'] . '/api/kinko/v1/invoice/charges/.json?'
        . http_build_query([
            'md' => $mode,
            'ss' => 'Delivered',
            'd_pin' => $destinationPincode,
            'o_pin' => $originPincode,
            'cgm' => $weightGrams,
            'pt' => 'Pre-paid',
            'cod' => 0,
        ]);

    $resp = _delhivery_request('GET', $url, $config['token']);

    if (!$resp['success'] || !is_array($resp['body'])) {
        return ['serviceable' => true, 'shipping_charge' => null, 'error' => $resp['error'] ?? 'API call failed'];
    }

    $body = $resp['body'];

    if (isset($body['error'])) {
        return ['serviceable' => false, 'shipping_charge' => null, 'error' => null];
    }

    if (is_array($body) && !empty($body) && isset($body[0]['total_amount'])) {
        return [
            'serviceable' => true,
            'shipping_charge' => (float)$body[0]['total_amount'],
            'error' => null,
        ];
    }

    return ['serviceable' => false, 'shipping_charge' => null, 'error' => null];
}

/**
 * Create a shipment on Delhivery.
 */
function delhivery_create_shipment(PDO $db, int $orderId): array
{
    $config = delhivery_get_config($db);
    if (!$config) {
        return ['success' => false, 'waybill' => '', 'error' => 'Delhivery not configured'];
    }

    $order = db_fetch_one($db, '
        SELECT o.*, u.full_name AS buyer_name, u.phone AS buyer_phone, u.email AS buyer_email,
               ba.address_line1, ba.address_line2, ba.city, ba.state, ba.pincode, ba.phone AS addr_phone
        FROM orders o
        JOIN users u ON u.id = o.buyer_id
        LEFT JOIN buyer_addresses ba ON ba.id = o.delivery_address_id
        WHERE o.id = :id LIMIT 1
    ', ['id' => $orderId]);

    if (!$order) {
        return ['success' => false, 'waybill' => '', 'error' => 'Order not found'];
    }

    if (!empty($order['delhivery_waybill'])) {
        return ['success' => true, 'waybill' => (string)$order['delhivery_waybill'], 'error' => null];
    }

    $totalKg = db_fetch_one($db, 'SELECT SUM(qty_kg) AS total_kg FROM order_items WHERE order_id = :id', ['id' => $orderId]);
    $weightGrams = (float)($totalKg['total_kg'] ?? 1) * 1000;
    if ($weightGrams < 100) $weightGrams = 500;

    $deliveryAddress = trim(($order['address_line1'] ?? '') . ' ' . ($order['address_line2'] ?? ''));
    $buyerPhone = $order['addr_phone'] ?: $order['buyer_phone'] ?: '';
    $buyerPhone = preg_replace('/[^0-9]/', '', $buyerPhone);
    if (strlen($buyerPhone) > 10 && substr($buyerPhone, 0, 2) === '91') {
        $buyerPhone = substr($buyerPhone, 2);
    }

    // Check if payment method is COD
    $payment = db_fetch_one($db, 'SELECT provider FROM payments WHERE order_id = :oid LIMIT 1', ['oid' => $orderId]);
    $isCOD = ($payment && $payment['provider'] === 'cod');
    $paymentMode = $isCOD ? 'COD' : 'Prepaid';
    $codAmount = $isCOD ? (string)$order['total_amount'] : '0';

    $shipmentData = [
        'shipments' => [[
            'name' => (string)($order['buyer_name'] ?? ''),
            'add' => $deliveryAddress,
            'pin' => (string)($order['pincode'] ?? ''),
            'city' => (string)($order['city'] ?? ''),
            'state' => (string)($order['state'] ?? ''),
            'country' => 'India',
            'phone' => $buyerPhone,
            'order' => (string)$order['order_no'],
            'payment_mode' => $paymentMode,
            'return_pin' => $config['warehouse_pincode'],
            'return_city' => $config['warehouse_city'],
            'return_phone' => $config['warehouse_phone'],
            'return_add' => $config['warehouse_address'],
            'return_state' => $config['warehouse_state'],
            'return_country' => 'India',
            'return_name' => $config['warehouse_name'],
            'products_desc' => 'Fresh Mangoes',
            'hsn_code' => '0804',
            'cod_amount' => $codAmount,
            'order_date' => date('Y-m-d H:i:s', strtotime((string)$order['created_at'])),
            'total_amount' => (string)$order['total_amount'],
            'seller_add' => $config['warehouse_address'],
            'seller_name' => $config['warehouse_name'],
            'seller_inv' => (string)$order['order_no'],
            'quantity' => (int)($order['box_quantity'] ?? 1),
            'weight' => $weightGrams,
            'waybill' => '',
            'shipment_width' => 20,
            'shipment_height' => 15,
            'shipment_length' => 25,
        ]],
        'pickup_location' => [
            'name' => $config['warehouse_name'],
            'city' => $config['warehouse_city'],
            'pin' => $config['warehouse_pincode'],
            'country' => 'India',
            'phone' => $config['warehouse_phone'],
            'add' => $config['warehouse_address'],
        ],
    ];

    $postData = 'format=json&data=' . urlencode(json_encode($shipmentData));
    $url = $config['base_url'] . '/api/cmu/create.json';

    $resp = _delhivery_request('POST', $url, $config['token'], $postData);

    if (!$resp['success'] || !is_array($resp['body'])) {
        return ['success' => false, 'waybill' => '', 'error' => $resp['error'] ?? 'Shipment creation failed'];
    }

    $body = $resp['body'];
    $packages = $body['packages'] ?? [];
    if (empty($packages)) {
        $rmk = $body['rmk'] ?? ($body['error'] ?? 'No packages in response');
        return ['success' => false, 'waybill' => '', 'error' => is_string($rmk) ? $rmk : json_encode($rmk)];
    }

    $pkg = $packages[0];
    $waybill = (string)($pkg['waybill'] ?? '');
    $status = (string)($pkg['status'] ?? '');

    if (empty($waybill) || $status === 'Fail' || $status === 'Failure') {
        $remarks = $pkg['remarks'] ?? $pkg['remark'] ?? 'Unknown error';
        if (is_array($remarks)) $remarks = implode('; ', $remarks);
        return ['success' => false, 'waybill' => '', 'error' => (string)$remarks];
    }

    db_exec($db, '
        UPDATE orders
        SET delhivery_waybill = :waybill,
            delhivery_shipment_id = :ship_id,
            awb_number = :awb,
            shipping_tracking_no = :tracking
        WHERE id = :id
    ', [
        'waybill' => $waybill,
        'ship_id' => (string)($pkg['refnum'] ?? $waybill),
        'awb' => $waybill,
        'tracking' => $waybill,
        'id' => $orderId,
    ]);

    try {
        db_exec($db, '
            INSERT INTO order_tracking (order_id, status, updated_by_type, updated_by_id, notes, updated_at)
            VALUES (:order_id, :status, :type, :uid, :notes, NOW())
        ', [
            'order_id' => $orderId,
            'status' => (string)$order['status'],
            'type' => 'system',
            'uid' => 0,
            'notes' => 'Delhivery shipment created — AWB: ' . $waybill,
        ]);
    } catch (Throwable $t) {
    }

    if (function_exists('notify_order_event')) {
        try { notify_order_event($db, $orderId, 'shipped'); } catch (Throwable $t) { /* best-effort */ }
    }

    return ['success' => true, 'waybill' => $waybill, 'error' => null];
}

/**
 * Track a shipment and sync events to local DB.
 */
function delhivery_track_shipment(PDO $db, int $orderId): array
{
    $config = delhivery_get_config($db);
    if (!$config) {
        return ['success' => false, 'status' => '', 'events' => [], 'error' => 'Delhivery not configured'];
    }

    $order = db_fetch_one($db, 'SELECT id, delhivery_waybill, status FROM orders WHERE id = :id LIMIT 1', ['id' => $orderId]);
    if (!$order || empty($order['delhivery_waybill'])) {
        return ['success' => false, 'status' => '', 'events' => [], 'error' => 'No waybill for this order'];
    }

    $waybill = (string)$order['delhivery_waybill'];
    $url = $config['base_url'] . '/api/v1/packages/json/?waybill=' . urlencode($waybill) . '&token=' . urlencode($config['token']);

    $resp = _delhivery_request('GET', $url, $config['token']);

    if (!$resp['success'] || !is_array($resp['body'])) {
        return ['success' => false, 'status' => '', 'events' => [], 'error' => $resp['error'] ?? 'Tracking API failed'];
    }

    $shipmentData = $resp['body']['ShipmentData'] ?? [];
    if (empty($shipmentData)) {
        return ['success' => false, 'status' => '', 'events' => [], 'error' => $resp['body']['Error'] ?? 'No shipment data'];
    }

    $shipment = $shipmentData[0]['Shipment'] ?? [];
    $currentStatus = (string)($shipment['Status']['Status'] ?? '');
    $scans = $shipment['Scans'] ?? [];
    $expectedDate = $shipment['ExpectedDeliveryDate'] ?? null;

    $events = [];
    foreach ($scans as $scan) {
        $scanDetail = $scan['ScanDetail'] ?? [];
        $scanType = (string)($scanDetail['Scan'] ?? '');
        $scanStatus = (string)($scanDetail['Instructions'] ?? $scanDetail['StatusDescription'] ?? '');
        $location = (string)($scanDetail['ScannedLocation'] ?? '');
        $eventTimeStr = (string)($scanDetail['ScanDateTime'] ?? '');
        $eventTime = !empty($eventTimeStr) ? date('Y-m-d H:i:s', strtotime($eventTimeStr)) : null;

        $events[] = compact('scanType', 'scanStatus', 'location', 'eventTime');

        if ($eventTime) {
            $existing = db_fetch_one($db, '
                SELECT id FROM delhivery_tracking_events
                WHERE order_id = :oid AND waybill = :wb AND event_time = :et AND scan_type = :st
                LIMIT 1
            ', ['oid' => $orderId, 'wb' => $waybill, 'et' => $eventTime, 'st' => $scanType]);

            if (!$existing) {
                try {
                    db_exec($db, '
                        INSERT INTO delhivery_tracking_events (order_id, waybill, scan_type, scan_status, location, event_time, raw_json, created_at)
                        VALUES (:oid, :wb, :st, :ss, :loc, :et, :rj, NOW())
                    ', [
                        'oid' => $orderId,
                        'wb' => $waybill,
                        'st' => $scanType,
                        'ss' => $scanStatus,
                        'loc' => $location,
                        'et' => $eventTime,
                        'rj' => json_encode($scanDetail),
                    ]);
                } catch (Throwable $t) {
                }
            }
        }
    }

    db_exec($db, 'UPDATE orders SET delhivery_status = :ds, delhivery_status_synced_at = NOW() WHERE id = :id',
        ['ds' => $currentStatus, 'id' => $orderId]);

    // Persist Delhivery's expected delivery date when provided.
    if (!empty($expectedDate)) {
        $eddTs = strtotime((string)$expectedDate);
        if ($eddTs !== false) {
            db_exec($db, 'UPDATE orders SET expected_delivery_date = :edd WHERE id = :id',
                ['edd' => date('Y-m-d', $eddTs), 'id' => $orderId]);
        }
    }

    $statusMap = [
        'Delivered' => 'delivered',
        'In Transit' => 'in_transit',
        'Out For Delivery' => 'in_transit',
        'Dispatched' => 'shipped',
        'Manifested' => 'shipped',
        'Pending' => 'shipped',
    ];

    $internalStatus = $statusMap[$currentStatus] ?? null;
    $currentOrderStatus = (string)$order['status'];
    $statusOrder = ['new' => 0, 'paid' => 1, 'packed' => 2, 'ready_to_pick' => 3, 'shipped' => 4, 'in_transit' => 5, 'delivered' => 6];

    if ($internalStatus && isset($statusOrder[$internalStatus]) && isset($statusOrder[$currentOrderStatus])
        && $statusOrder[$internalStatus] > $statusOrder[$currentOrderStatus]) {
        $tsMap = ['in_transit' => 'in_transit_at', 'delivered' => 'delivered_at', 'shipped' => 'shipped_at'];
        if (isset($tsMap[$internalStatus])) {
            ensure_order_column($db, $tsMap[$internalStatus]);
            db_exec($db, 'UPDATE orders SET status = :status, ' . $tsMap[$internalStatus] . ' = NOW() WHERE id = :id',
                ['status' => $internalStatus, 'id' => $orderId]);
        } else {
            db_exec($db, 'UPDATE orders SET status = :status WHERE id = :id', ['status' => $internalStatus, 'id' => $orderId]);
        }

        try {
            db_exec($db, '
                INSERT INTO order_tracking (order_id, status, updated_by_type, updated_by_id, notes, updated_at)
                VALUES (:order_id, :status, :type, :uid, :notes, NOW())
            ', [
                'order_id' => $orderId, 'status' => $internalStatus, 'type' => 'system', 'uid' => 0,
                'notes' => 'Auto-updated from Delhivery: ' . $currentStatus,
            ]);
        } catch (Throwable $t) {
        }

        // Notify buyer on delivered transition.
        if ($internalStatus === 'delivered' && function_exists('notify_order_event')) {
            try { notify_order_event($db, $orderId, 'delivered'); } catch (Throwable $t) { /* best-effort */ }
        }
    }

    return ['success' => true, 'status' => $currentStatus, 'events' => $events, 'expected_date' => $expectedDate, 'error' => null];
}

/**
 * Cancel a shipment on Delhivery.
 */
function delhivery_cancel_shipment(PDO $db, int $orderId): array
{
    $config = delhivery_get_config($db);
    if (!$config) {
        return ['success' => false, 'error' => 'Delhivery not configured'];
    }

    $order = db_fetch_one($db, 'SELECT id, delhivery_waybill FROM orders WHERE id = :id LIMIT 1', ['id' => $orderId]);
    if (!$order || empty($order['delhivery_waybill'])) {
        return ['success' => true, 'error' => null];
    }

    $waybill = (string)$order['delhivery_waybill'];
    $url = $config['base_url'] . '/api/p/edit';
    $postData = json_encode(['waybill' => $waybill, 'cancellation' => true]);

    $resp = _delhivery_request('POST', $url, $config['token'], $postData, ['Content-Type: application/json']);

    if (!$resp['success']) {
        return ['success' => false, 'error' => $resp['error'] ?? 'Cancellation failed'];
    }

    try {
        db_exec($db, '
            INSERT INTO order_tracking (order_id, status, updated_by_type, updated_by_id, notes, updated_at)
            VALUES (:order_id, :status, :type, :uid, :notes, NOW())
        ', [
            'order_id' => $orderId, 'status' => 'cancelled', 'type' => 'system', 'uid' => 0,
            'notes' => 'Delhivery shipment cancelled — AWB: ' . $waybill,
        ]);
    } catch (Throwable $t) {
    }

    return ['success' => true, 'error' => null];
}

/**
 * Request a pickup from Delhivery.
 */
function delhivery_request_pickup(PDO $db, int $orderId): array
{
    $config = delhivery_get_config($db);
    if (!$config) {
        return ['success' => false, 'pickup_token' => '', 'error' => 'Delhivery not configured'];
    }

    $url = $config['base_url'] . '/fm/request/new/';
    $pickupData = json_encode([
        'pickup_time' => date('H:i:s'),
        'pickup_date' => date('Y-m-d'),
        'pickup_location' => $config['warehouse_name'],
        'expected_package_count' => 1,
    ]);

    $resp = _delhivery_request('POST', $url, $config['token'], $pickupData, ['Content-Type: application/json']);

    if (!$resp['success'] || !is_array($resp['body'])) {
        return ['success' => false, 'pickup_token' => '', 'error' => $resp['error'] ?? 'Pickup request failed'];
    }

    $pickupId = (string)($resp['body']['pickup_id'] ?? $resp['body']['incoming_center_name'] ?? '');

    if ($pickupId) {
        db_exec($db, 'UPDATE orders SET delhivery_pickup_token = :pt WHERE id = :id', ['pt' => $pickupId, 'id' => $orderId]);
    }

    return ['success' => true, 'pickup_token' => $pickupId, 'error' => null];
}
