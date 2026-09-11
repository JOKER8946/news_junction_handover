<?php

declare(strict_types=1);

/**
 * Ekart Logistics integration — implemented against the v2/v1 spec from
 * https://app.elite.ekartlogistics.in/settings/api/documentation
 *
 * Auth flow:
 *   POST /integrations/v2/auth/token/{client_id}  body: {username, password}
 *   →   {access_token, token_type, scope, expires_in}
 *   Pass `Authorization: Bearer <access_token>` on subsequent calls.
 *   The token is cached in platform_settings for ~24h (Ekart caches their side
 *   too) to avoid re-authing on every request.
 *
 * Public endpoints used here:
 *   GET   /api/v2/serviceability/{pincode}          — pincode check
 *   PUT   /api/v1/package/create                    — create shipment
 *   DELETE /api/v1/package/cancel?tracking_id=...   — cancel shipment
 *   GET   /api/v1/track/{id}                        — track (no auth)
 *
 * While credentials are missing (or onboarding pending), the library falls
 * back to STUB MODE so checkout still works.
 */

const EKART_DEFAULT_BASE_URL = 'https://app.elite.ekartlogistics.in';

function ekart_base_url(string $environment): string
{
    // The spec exposes a single host; environment is reserved for future
    // staging URLs if Ekart adds one. Default to production.
    return EKART_DEFAULT_BASE_URL;
}

/**
 * Read Ekart credentials + cached token from platform_settings.
 * Returns null only if the row is missing entirely (extremely unusual).
 */
function ekart_get_config(PDO $db): ?array
{
    $s = db_fetch_one($db, 'SELECT * FROM platform_settings WHERE id = 1 LIMIT 1');
    if (!$s) return null;

    $env = ($s['ekart_environment'] ?? 'sandbox') === 'production' ? 'production' : 'sandbox';
    $baseUrl = trim((string)($s['ekart_base_url'] ?? '')) ?: ekart_base_url($env);

    return [
        'client_id'         => trim((string)($s['ekart_client_id'] ?? '')),
        'username'          => trim((string)($s['ekart_username'] ?? '')),
        'password'          => (string)($s['ekart_password'] ?? ''),
        'environment'       => $env,
        'base_url'          => rtrim($baseUrl, '/'),
        'access_token'      => (string)($s['ekart_access_token'] ?? ''),
        'token_expires_at'  => (string)($s['ekart_token_expires_at'] ?? ''),
        // Pickup warehouse fields (kept on legacy delhivery_* columns).
        'warehouse_name'    => (string)($s['delhivery_warehouse_name'] ?? ''),
        'warehouse_address' => (string)($s['delhivery_warehouse_address'] ?? ''),
        'warehouse_city'    => (string)($s['delhivery_warehouse_city'] ?? ''),
        'warehouse_state'   => (string)($s['delhivery_warehouse_state'] ?? ''),
        'warehouse_pincode' => (string)($s['delhivery_warehouse_pincode'] ?? ''),
        'warehouse_phone'   => (string)($s['delhivery_warehouse_phone'] ?? ''),
    ];
}

/**
 * True when all 3 credentials needed for the v2 auth flow are present.
 */
function ekart_is_live(?array $config): bool
{
    if (!$config) return false;
    return $config['client_id'] !== '' && $config['username'] !== '' && $config['password'] !== '';
}

function ekart_ensure_schema(PDO $db): void
{
    // No-op — schema is managed via ALTER TABLE migrations.
}

/**
 * Return a valid Bearer access_token, refreshing via the auth endpoint when
 * the cached one is missing or within 60s of expiry. Returns '' on failure
 * (caller should treat as "auth unavailable").
 */
function ekart_get_access_token(PDO $db, array $config): string
{
    if (!ekart_is_live($config)) return '';

    $cached = $config['access_token'];
    $expiry = $config['token_expires_at'];
    if ($cached !== '' && $expiry !== '') {
        $expiryTs = strtotime($expiry);
        if ($expiryTs && $expiryTs > time() + 60) {
            return $cached;
        }
    }

    // Fetch a fresh token.
    $url  = $config['base_url'] . '/integrations/v2/auth/token/' . rawurlencode($config['client_id']);
    $body = json_encode([
        'username' => $config['username'],
        'password' => $config['password'],
    ]);
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $body,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'Accept: application/json'],
        CURLOPT_TIMEOUT        => 15,
    ]);
    $resp = curl_exec($ch);
    $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);

    if ($http !== 200 || !$resp) {
        error_log("ekart_get_access_token: HTTP=$http err=$err body=" . substr((string)$resp, 0, 300));
        return '';
    }
    $data = json_decode((string)$resp, true);
    $token = (string)($data['access_token'] ?? '');
    $expiresIn = (int)($data['expires_in'] ?? 0);
    if ($token === '' || $expiresIn <= 0) {
        error_log('ekart_get_access_token: malformed response: ' . substr((string)$resp, 0, 300));
        return '';
    }

    // Persist for re-use. Expire 60s before the actual expiry to be safe.
    $expiresAt = gmdate('Y-m-d H:i:s', time() + $expiresIn - 60);
    try {
        db_exec($db,
            'UPDATE platform_settings SET ekart_access_token = :t, ekart_token_expires_at = :e WHERE id = 1',
            ['t' => $token, 'e' => $expiresAt]
        );
    } catch (Throwable $t) { /* cache write failure is non-fatal */ }

    return $token;
}

/**
 * Perform an authenticated Ekart HTTP request and return the decoded body.
 * Returns ['ok' => bool, 'http' => int, 'body' => mixed, 'raw' => string, 'error' => string|null].
 */
function ekart_request(PDO $db, array $config, string $method, string $path, ?array $body = null, ?array $query = null): array
{
    $token = ekart_get_access_token($db, $config);
    if ($token === '') {
        return ['ok' => false, 'http' => 0, 'body' => null, 'raw' => '', 'error' => 'Ekart auth failed'];
    }

    $url = $config['base_url'] . $path;
    if (!empty($query)) {
        $url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($query);
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => strtoupper($method),
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
            'Accept: application/json',
        ],
        CURLOPT_TIMEOUT        => 30,
    ]);
    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    }
    $raw  = curl_exec($ch);
    $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);

    $decoded = $raw ? json_decode((string)$raw, true) : null;
    $ok = ($http >= 200 && $http < 300 && $raw);

    if (!$ok) {
        error_log("ekart_request $method $path → HTTP=$http err=$err body=" . substr((string)$raw, 0, 500));
    }

    return [
        'ok'    => $ok,
        'http'  => $http,
        'body'  => $decoded,
        'raw'   => (string)$raw,
        'error' => $ok ? null : ($err ?: ('HTTP ' . $http)),
    ];
}

/**
 * Check serviceability for a destination pincode via the V2 endpoint.
 * Spec: GET /api/v2/serviceability/{pincode}
 * Response: { status: bool, pincode, remark, details: { cod, max_cod_amount,
 *   forward_pickup, forward_drop, reverse_pickup, reverse_drop, city, state } }
 */
function ekart_check_pincode(PDO $db, string $originPincode, string $destinationPincode): array
{
    if (!preg_match('/^\d{6}$/', $destinationPincode)) {
        return ['serviceable' => false, 'prepaid' => false, 'cod' => false, 'error' => 'Invalid destination pincode'];
    }

    $config = ekart_get_config($db);
    if (!ekart_is_live($config)) {
        // Stub so buyers can still complete checkout while onboarding finishes.
        return [
            'serviceable'     => true,
            'prepaid'         => true,
            'cod'             => false,
            'shipping_charge' => 0.0,
            'mode'            => 'stub',
            'error'           => null,
        ];
    }

    $res = ekart_request($db, $config, 'GET', '/api/v2/serviceability/' . $destinationPincode);
    if (!$res['ok'] || !is_array($res['body'])) {
        return [
            'serviceable' => false, 'prepaid' => false, 'cod' => false,
            'error' => $res['error'] ?: 'Serviceability call failed',
        ];
    }

    $body    = $res['body'];
    $status  = (bool)($body['status'] ?? false);
    $details = is_array($body['details'] ?? null) ? $body['details'] : [];
    return [
        'serviceable'    => $status && (bool)($details['forward_drop'] ?? false),
        'prepaid'        => (bool)($details['forward_drop'] ?? false),
        'cod'            => (bool)($details['cod'] ?? false),
        'max_cod_amount' => (int)($details['max_cod_amount'] ?? 0),
        'city'           => (string)($details['city'] ?? ''),
        'state'          => (string)($details['state'] ?? ''),
        'mode'           => 'live',
        'error'          => null,
    ];
}

/**
 * Live shipping quote for the buyer's destination pincode.
 *
 * Spec: POST /data/pricing/estimate
 *
 * IMPORTANT: when running in stub mode (no credentials yet, missing pickup
 * pincode, API call failed, etc.) we return shipping_charge=NULL — that
 * signals cart_totals_with_delhivery() to KEEP the per-product shipping it
 * has already computed instead of overwriting it with 0. Returning 0.0 here
 * was the bug that made checkout show ₹0.
 */
function ekart_get_shipping_charge(PDO $db, string $destinationPincode, int $weightGrams, $merchantOrMode = 0): array
{
    // Back-compat: this signature used to be (db, dest, weight, mode='S'). Now
    // the 4th param is an int merchant id used to look up that merchant's
    // pickup pincode. Treat any non-int (e.g. the legacy 'S' string) as 0.
    $merchantId = is_int($merchantOrMode) ? $merchantOrMode : 0;

    if (!preg_match('/^\d{6}$/', $destinationPincode) || $weightGrams <= 0) {
        return ['ok' => true, 'serviceable' => false, 'shipping_charge' => null, 'mode' => 'invalid-input'];
    }

    $config = ekart_get_config($db);
    if (!ekart_is_live($config)) {
        // Stub: leave shipping_charge NULL so caller keeps the per-product rate.
        return ['ok' => true, 'serviceable' => true, 'shipping_charge' => null, 'mode' => 'stub'];
    }

    // Per-merchant pickup pincode lookup. If the merchant has a registered
    // warehouse pincode on their profile, use it; otherwise fall back to the
    // platform warehouse (and log so super-admin can fix it).
    $pickupPin = '';
    if ($merchantId > 0) {
        try {
            $mp = db_fetch_one($db,
                'SELECT delhivery_warehouse_pincode FROM merchant_profile WHERE merchant_user_id = :uid LIMIT 1',
                ['uid' => $merchantId]
            );
            $pickupPin = preg_replace('/\D/', '', (string)($mp['delhivery_warehouse_pincode'] ?? ''));
        } catch (Throwable $t) { /* ignore */ }
        if ($pickupPin === '' || strlen($pickupPin) !== 6) {
            error_log("ekart: merchant id={$merchantId} has no warehouse_pincode on file; falling back to platform warehouse");
            $pickupPin = '';
        }
    }
    if ($pickupPin === '') {
        $pickupPin = preg_replace('/\D/', '', (string)$config['warehouse_pincode']);
    }
    if ($pickupPin === '' || strlen($pickupPin) !== 6) {
        return ['ok' => true, 'serviceable' => true, 'shipping_charge' => null, 'mode' => 'no-pickup-pin'];
    }

    // Enum case rules — discovered via successive 400 responses:
    //   billingClientType:  PROSPECTIVE_CLIENT | EXISTING_CLIENT | EXISTING_CLIENT_CUSTOM_RATE_SNAPSHOT  (UPPER)
    //   shippingDirection:  FORWARD | REVERSE                                                            (UPPER)
    //   serviceType:        SURFACE | EXPRESS                                                            (UPPER)
    //   paymentMode:        Prepaid | COD | Pickup                                                       (TitleCase)
    $payload = [
        'billingClientType' => 'EXISTING_CLIENT',
        'shippingDirection' => 'FORWARD',
        'serviceType'       => 'SURFACE',
        'pickupPincode'     => (int)$pickupPin,
        'dropPincode'       => (int)$destinationPincode,
        'weight'            => $weightGrams,
        'length'            => 15,
        'height'            => 10,
        'width'             => 10,
        'invoiceAmount'     => 0,
        'paymentMode'       => 'Prepaid',
    ];

    $res = ekart_request($db, $config, 'POST', '/data/pricing/estimate', $payload);
    if (!$res['ok'] || !is_array($res['body'])) {
        return [
            'ok' => true, 'serviceable' => true,
            'shipping_charge' => null,
            'mode' => 'api-failed',
            'error' => $res['error'],
        ];
    }

    // Response fields are returned as strings per the spec — coerce safely.
    $total = (float)($res['body']['total'] ?? 0);
    if ($total <= 0) {
        // 'total' may be absent on partial responses; fall back to shippingCharge.
        $total = (float)($res['body']['shippingCharge'] ?? 0);
    }

    if ($total <= 0) {
        // Ekart returned an empty/zero estimate — keep per-product shipping.
        return ['ok' => true, 'serviceable' => true, 'shipping_charge' => null, 'mode' => 'empty-quote'];
    }

    return [
        'ok' => true,
        'serviceable' => true,
        'shipping_charge' => round($total, 2),
        'mode' => 'live',
    ];
}

/**
 * Build the create-shipment payload from one of our orders.
 * Pulls seller (merchant_profile), buyer (buyer_addresses), order items, and
 * platform pickup warehouse from platform_settings.
 *
 * Returns ['ok' => bool, 'payload' => array, 'error' => string|null].
 */
function ekart_build_shipment_payload(PDO $db, array $config, int $orderId): array
{
    $order = db_fetch_one($db,
        'SELECT o.*,
                mp.business_name             AS m_business,
                mp.business_address          AS m_billing_address,
                mp.business_gstin            AS m_gst,
                mp.delhivery_warehouse_name    AS m_wh_name,
                mp.delhivery_warehouse_address AS m_wh_addr,
                mp.delhivery_warehouse_city    AS m_wh_city,
                mp.delhivery_warehouse_state   AS m_wh_state,
                mp.delhivery_warehouse_pincode AS m_wh_pin,
                mp.delhivery_warehouse_phone   AS m_wh_phone,
                mp.ekart_pickup_alias          AS m_alias
         FROM orders o
         LEFT JOIN merchant_profile mp ON mp.merchant_user_id = o.merchant_id
         WHERE o.id = :id LIMIT 1',
        ['id' => $orderId]
    );
    if (!$order) return ['ok' => false, 'payload' => [], 'error' => 'Order not found'];

    $addr = db_fetch_one($db,
        'SELECT * FROM buyer_addresses WHERE id = :id LIMIT 1',
        ['id' => $order['delivery_address_id']]
    );
    if (!$addr) return ['ok' => false, 'payload' => [], 'error' => 'Delivery address not found'];

    $items = db_fetch_all($db,
        'SELECT oi.*, p.name AS product_name, p.unit
         FROM order_items oi
         LEFT JOIN products p ON p.id = oi.product_id
         WHERE oi.order_id = :oid',
        ['oid' => $orderId]
    ) ?: [];

    // Per-merchant pickup. If this merchant's warehouse was already registered
    // with Ekart (alias present), Ekart routes the pickup from that registered
    // address and we just send the alias. Otherwise we need their warehouse
    // details on file. We REFUSE to fall back to the platform warehouse here,
    // because doing so silently quotes/dispatches from the wrong city — instead
    // we error loudly so the super-admin can fix the merchant profile.
    $merchantAlias = trim((string)($order['m_alias'] ?? ''));
    $merchantWhPin = preg_replace('/\D/', '', (string)($order['m_wh_pin'] ?? ''));

    if ($merchantAlias === '' && (strlen($merchantWhPin) !== 6 || empty($order['m_wh_addr']))) {
        return [
            'ok'      => false,
            'payload' => [],
            'error'   => 'Merchant warehouse is not configured. Open the merchant profile in super-admin and set the warehouse pincode + address (or register the merchant\'s Ekart pickup) before creating a shipment for this order.',
        ];
    }

    $pickupName    = (string)($order['m_wh_name']  ?? '') ?: (string)($order['m_business'] ?? '') ?: 'Manikya Market';
    $pickupAddress = (string)($order['m_wh_addr']  ?? '');
    $pickupCity    = (string)($order['m_wh_city']  ?? '');
    $pickupState   = (string)($order['m_wh_state'] ?? '');
    $pickupPincode = (int)$merchantWhPin;
    $pickupPhone   = (int)preg_replace('/\D/', '', (string)($order['m_wh_phone'] ?? ''));

    $consigneeName = trim((string)($addr['full_name'] ?? ''));
    $consigneePhone = (int)preg_replace('/\D/', '', (string)($addr['phone'] ?? ''));

    $totalAmount = (float)($order['total_amount'] ?? 0);
    $taxValue    = (float)($order['tax_amount']   ?? 0);
    $taxableAmount = max(1.0, $totalAmount - $taxValue);

    // Weight in grams (sum item weights; default 500g/item if missing)
    $weight = 0;
    $quantity = 0;
    $productNames = [];
    foreach ($items as $it) {
        $qty = max(1, (int)($it['quantity'] ?? 1));
        $quantity += $qty;
        $weight += max(500, (int)(($it['weight_grams'] ?? 500) * $qty));
        $productNames[] = $it['product_name'] ?: ('Product #' . $it['product_id']);
    }
    if ($weight === 0) $weight = 500;

    $paymentMode = 'Prepaid'; // Razorpay payments are always Prepaid
    $codAmount   = 0;
    $now = gmdate('Y-m-d');

    $payload = [
        'seller_name'              => $pickupName,
        'seller_address'           => $pickupAddress ?: 'Bengaluru, Karnataka',
        'seller_gst_tin'           => (string)($order['m_gst'] ?? ''),
        'consignee_gst_amount'     => 0,
        'order_number'             => (string)$order['order_no'],
        'invoice_number'           => 'INV-' . (string)$order['order_no'],
        'invoice_date'             => $now,
        'consignee_name'           => $consigneeName ?: 'Buyer',
        // Ekart rejects payloads where consignee_alternate_phone == primary phone
        // ("Phone and Alternate Phone cannot be same"). We only have one phone for
        // the buyer, so omit alternate unless an actual secondary number exists.
        'payment_mode'             => $paymentMode,
        'category_of_goods'        => 'General',
        'products_desc'            => implode(', ', $productNames) ?: 'Mixed goods',
        'total_amount'             => $totalAmount > 0 ? $totalAmount : 1,
        'cod_amount'               => $codAmount,
        'taxable_amount'           => $taxableAmount,
        'commodity_value'          => (string)$taxableAmount,
        'tax_value'                => $taxValue,
        'return_reason'            => '',
        'quantity'                 => max(1, $quantity),
        'weight'                   => $weight,
        'length'                   => 15,
        'height'                   => 10,
        'width'                    => 10,
        'drop_location' => [
            'address' => trim(($addr['address_line1'] ?? '') . ' ' . ($addr['address_line2'] ?? '')),
            'city'    => (string)($addr['city']    ?? ''),
            'state'   => (string)($addr['state']   ?? ''),
            'country' => 'India',
            'name'    => $consigneeName ?: 'Buyer',
            'phone'   => $consigneePhone,
            'pin'     => (int)((string)($addr['pincode'] ?? '0')),
        ],
        // Pickup_location: if this merchant has a registered alias with Ekart,
        // we just send {name: alias} and Ekart pulls the address from its own
        // registry. Otherwise embed the full address.
        'pickup_location' => $merchantAlias !== ''
            ? ['name' => $merchantAlias]
            : [
                'address' => $pickupAddress ?: 'Pickup Warehouse',
                'city'    => $pickupCity,
                'state'   => $pickupState,
                'country' => 'India',
                'name'    => $pickupName,
                'phone'   => $pickupPhone ?: 9000000000,
                'pin'     => $pickupPincode ?: 560001,
            ],
        // return_location omitted when we sent an alias — Ekart defaults to
        // the pickup_location, which is the merchant's registered warehouse.
        'return_location' => $merchantAlias !== ''
            ? ['name' => $merchantAlias]
            : [
                'address' => $pickupAddress ?: 'Pickup Warehouse',
                'city'    => $pickupCity,
                'state'   => $pickupState,
                'country' => 'India',
                'name'    => $pickupName,
                'phone'   => $pickupPhone ?: 9000000000,
                'pin'     => $pickupPincode ?: 560001,
            ],
    ];

    return ['ok' => true, 'payload' => $payload, 'error' => null];
}

/**
 * Create a forward shipment with Ekart.
 * Spec: PUT /api/v1/package/create
 */
function ekart_create_shipment(PDO $db, int $orderId): array
{
    $order = db_fetch_one($db,
        'SELECT id, order_no, status, merchant_id, delhivery_waybill FROM orders WHERE id = :id LIMIT 1',
        ['id' => $orderId]
    );
    if (!$order) return ['ok' => false, 'error' => 'Order not found'];

    if (!empty($order['delhivery_waybill']) && strpos((string)$order['delhivery_waybill'], 'EKART_PENDING_') !== 0) {
        return ['ok' => true, 'waybill' => (string)$order['delhivery_waybill'], 'mode' => 'already-stamped'];
    }

    $config = ekart_get_config($db);
    if (!ekart_is_live($config)) {
        $placeholder = 'EKART_PENDING_' . (string)$order['order_no'];
        db_exec($db,
            'UPDATE orders SET delhivery_waybill = :w, delhivery_status = :s WHERE id = :id',
            ['w' => $placeholder, 's' => 'pending_ekart_activation', 'id' => $orderId]
        );
        return ['ok' => true, 'waybill' => $placeholder, 'mode' => 'stub'];
    }

    // Make sure this merchant's pickup is registered with Ekart so the
    // shipment payload can use their alias. Failure here is non-fatal — the
    // payload will fall back to embedding the full address — but logged.
    $merchantId = (int)($order['merchant_id'] ?? 0);
    if ($merchantId > 0) {
        $reg = ekart_register_pickup_location($db, $merchantId);
        if (!$reg['ok']) {
            error_log("ekart_create_shipment: pickup registration failed for merchant $merchantId: " . ($reg['error'] ?? 'unknown'));
        }
    }

    $built = ekart_build_shipment_payload($db, $config, $orderId);
    if (!$built['ok']) return ['ok' => false, 'error' => $built['error']];

    $res = ekart_request($db, $config, 'PUT', '/api/v1/package/create', $built['payload']);
    if (!$res['ok'] || !is_array($res['body']) || empty($res['body']['tracking_id'])) {
        return ['ok' => false, 'error' => $res['error'] ?: 'Shipment creation failed', 'response' => $res['body']];
    }

    $trackingId = (string)$res['body']['tracking_id'];
    $vendor     = (string)($res['body']['vendor'] ?? 'EKART');
    db_exec($db,
        'UPDATE orders SET delhivery_waybill = :w, delhivery_status = :s, delhivery_status_synced_at = NOW() WHERE id = :id',
        ['w' => $trackingId, 's' => 'shipment_created', 'id' => $orderId]
    );

    return [
        'ok'         => true,
        'waybill'    => $trackingId,
        'vendor'     => $vendor,
        'mode'       => 'live',
        'track_url'  => $config['base_url'] . '/track/' . $trackingId,
    ];
}

/**
 * Cancel a shipment.
 * Spec: DELETE /api/v1/package/cancel?tracking_id=...
 */
function ekart_cancel_shipment(PDO $db, int $orderId): array
{
    $order = db_fetch_one($db, 'SELECT id, delhivery_waybill FROM orders WHERE id = :id LIMIT 1', ['id' => $orderId]);
    if (!$order) return ['ok' => false, 'error' => 'Order not found'];

    $waybill = (string)($order['delhivery_waybill'] ?? '');
    if ($waybill === '') return ['ok' => true, 'mode' => 'noop-no-waybill'];

    // Stub placeholder waybills never went to Ekart; just clear locally.
    if (strpos($waybill, 'EKART_PENDING_') === 0) {
        db_exec($db, "UPDATE orders SET delhivery_waybill = NULL, delhivery_status = 'cancelled' WHERE id = :id", ['id' => $orderId]);
        return ['ok' => true, 'mode' => 'stub-cleared'];
    }

    $config = ekart_get_config($db);
    if (!ekart_is_live($config)) {
        // No way to talk to Ekart right now; clear locally and let admin reconcile.
        db_exec($db, "UPDATE orders SET delhivery_status = 'cancelled' WHERE id = :id", ['id' => $orderId]);
        return ['ok' => true, 'mode' => 'creds-missing'];
    }

    $res = ekart_request($db, $config, 'DELETE', '/api/v1/package/cancel', null, ['tracking_id' => $waybill]);
    if (!$res['ok']) {
        return ['ok' => false, 'error' => $res['error'] ?: 'Cancel call failed'];
    }
    db_exec($db, "UPDATE orders SET delhivery_status = 'cancelled', delhivery_status_synced_at = NOW() WHERE id = :id", ['id' => $orderId]);
    return ['ok' => true, 'mode' => 'live'];
}

/**
 * Public tracking (no auth required per spec).
 * Spec: GET /api/v1/track/{id}
 */
function ekart_track_shipment(PDO $db, int $orderId): array
{
    $order = db_fetch_one($db, 'SELECT id, delhivery_waybill FROM orders WHERE id = :id LIMIT 1', ['id' => $orderId]);
    if (!$order) return ['ok' => false, 'error' => 'Order not found'];

    $waybill = (string)($order['delhivery_waybill'] ?? '');
    if ($waybill === '' || strpos($waybill, 'EKART_PENDING_') === 0) {
        return ['ok' => true, 'events' => [], 'mode' => 'no-real-waybill'];
    }

    $config = ekart_get_config($db);
    $baseUrl = $config['base_url'] ?? EKART_DEFAULT_BASE_URL;

    $ch = curl_init($baseUrl . '/api/v1/track/' . rawurlencode($waybill));
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
    ]);
    $raw  = curl_exec($ch);
    $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http !== 200 || !$raw) {
        return ['ok' => false, 'error' => "Tracking HTTP $http"];
    }
    $body = json_decode((string)$raw, true);
    $track = is_array($body['track'] ?? null) ? $body['track'] : null;
    if (!$track) return ['ok' => true, 'events' => [], 'status' => null];

    $status = (string)($track['status'] ?? '');
    if ($status !== '') {
        db_exec($db,
            'UPDATE orders SET delhivery_status = :s, delhivery_status_synced_at = NOW() WHERE id = :id',
            ['s' => $status, 'id' => $orderId]
        );
    }

    // Best-effort persist of the latest events into delhivery_tracking_events.
    $events = is_array($track['details'] ?? null) ? $track['details'] : [];
    foreach ($events as $ev) {
        try {
            db_exec($db,
                'INSERT IGNORE INTO delhivery_tracking_events (order_id, event_time, status, description, location)
                 VALUES (:oid, FROM_UNIXTIME(:t), :st, :ds, :loc)',
                [
                    'oid' => $orderId,
                    't'   => (int)(($ev['ctime'] ?? 0) / 1000),
                    'st'  => (string)($ev['status'] ?? ''),
                    'ds'  => (string)($ev['desc'] ?? ''),
                    'loc' => (string)($ev['location'] ?? ''),
                ]
            );
        } catch (Throwable $t) { /* table may not exist; ignore */ }
    }

    return ['ok' => true, 'status' => $status, 'events' => $events];
}

function ekart_request_pickup(PDO $db, int $orderId): array
{
    // Ekart's "preferred dispatch date" is set on creation; there's no
    // separate pickup-request endpoint. Treat this as a no-op for now.
    return ['ok' => true, 'mode' => 'noop'];
}

/**
 * Register a merchant's pickup warehouse with Ekart.
 * Spec: POST /api/v2/address  body: {alias, phone, address_line1, pincode, city, state, country}
 *
 * On success, the alias is stored on merchant_profile.ekart_pickup_alias and
 * shipment creation for that merchant will pass the alias instead of the full
 * address (Ekart handles routing from the registered warehouse).
 *
 * Returns ['ok' => bool, 'alias' => string|null, 'error' => string|null, 'mode' => string].
 */
function ekart_register_pickup_location(PDO $db, int $merchantId): array
{
    $mp = db_fetch_one($db,
        'SELECT id, merchant_user_id, business_name,
                delhivery_warehouse_name, delhivery_warehouse_address,
                delhivery_warehouse_city, delhivery_warehouse_state,
                delhivery_warehouse_pincode, delhivery_warehouse_phone,
                ekart_pickup_alias
         FROM merchant_profile WHERE merchant_user_id = :uid LIMIT 1',
        ['uid' => $merchantId]
    );
    if (!$mp) return ['ok' => false, 'error' => 'Merchant profile not found', 'alias' => null];

    if (!empty($mp['ekart_pickup_alias'])) {
        return ['ok' => true, 'alias' => (string)$mp['ekart_pickup_alias'], 'mode' => 'already-registered'];
    }

    // Validate required pickup fields are present.
    $address = trim((string)$mp['delhivery_warehouse_address']);
    $pin     = preg_replace('/\D/', '', (string)$mp['delhivery_warehouse_pincode']);
    $phone   = preg_replace('/\D/', '', (string)$mp['delhivery_warehouse_phone']);
    if ($address === '' || $pin === '' || $phone === '') {
        return ['ok' => false, 'alias' => null,
                'error' => 'Merchant warehouse missing address/pincode/phone — fill those before registering with Ekart'];
    }

    $config = ekart_get_config($db);
    if (!ekart_is_live($config)) {
        // Stub: invent a deterministic alias so subsequent shipments work in stub mode.
        $stubAlias = 'MM_M' . (int)$mp['merchant_user_id'];
        db_exec($db,
            'UPDATE merchant_profile SET ekart_pickup_alias = :a WHERE merchant_user_id = :uid',
            ['a' => $stubAlias, 'uid' => $merchantId]
        );
        return ['ok' => true, 'alias' => $stubAlias, 'mode' => 'stub'];
    }

    $alias = 'MM_M' . (int)$mp['merchant_user_id'];
    $payload = [
        'alias'         => $alias,
        'phone'         => (int)$phone,
        'address_line1' => $address,
        'pincode'       => (int)$pin,
        'city'          => (string)$mp['delhivery_warehouse_city'],
        'state'         => (string)$mp['delhivery_warehouse_state'],
        'country'       => 'India',
    ];

    $res = ekart_request($db, $config, 'POST', '/api/v2/address', $payload);
    if (!$res['ok'] || empty($res['body']['status'])) {
        return ['ok' => false, 'alias' => null,
                'error' => $res['error'] ?: ('Address registration rejected: ' . substr($res['raw'], 0, 200))];
    }

    // Ekart echoes the alias back; trust whatever it returns.
    $registered = (string)($res['body']['alias'] ?? $alias);
    db_exec($db,
        'UPDATE merchant_profile SET ekart_pickup_alias = :a WHERE merchant_user_id = :uid',
        ['a' => $registered, 'uid' => $merchantId]
    );
    return ['ok' => true, 'alias' => $registered, 'mode' => 'live'];
}

// ── Backwards-compat shims so the still-untouched delhivery_* call sites work
//    until we finish the rename. They simply forward to the ekart_* twin.
if (!function_exists('delhivery_get_config'))          { function delhivery_get_config(PDO $db): ?array { return ekart_get_config($db); } }
if (!function_exists('delhivery_ensure_schema'))       { function delhivery_ensure_schema(PDO $db): void { ekart_ensure_schema($db); } }
if (!function_exists('delhivery_check_pincode'))       { function delhivery_check_pincode(PDO $db, string $o, string $d): array { return ekart_check_pincode($db, $o, $d); } }
if (!function_exists('delhivery_get_shipping_charge')) { function delhivery_get_shipping_charge(PDO $db, string $d, int $w, $merchantOrMode = 0): array { return ekart_get_shipping_charge($db, $d, $w, $merchantOrMode); } }

/**
 * Normalize an Ekart result to also expose the legacy 'success' / 'error' keys
 * that older callers (merchant/order.php, logistics/order-detail.php, etc.)
 * still check. The Ekart functions return ['ok' => bool, 'error' => string].
 * Without this bridge, a legacy caller reads $r['success'] (undefined) → false
 * → shows "shipment failed: Unknown error" even though the shipment succeeded.
 */
function _ekart_legacy_normalize(array $r): array
{
    if (!array_key_exists('success', $r)) {
        $r['success'] = isset($r['ok']) ? (bool)$r['ok'] : !empty($r['waybill']);
    }
    if (!array_key_exists('error', $r) && !$r['success']) {
        $r['error'] = '';
    }
    return $r;
}

if (!function_exists('delhivery_create_shipment'))     { function delhivery_create_shipment(PDO $db, int $o): array { return _ekart_legacy_normalize(ekart_create_shipment($db, $o)); } }
if (!function_exists('delhivery_track_shipment'))      { function delhivery_track_shipment(PDO $db, int $o): array { return _ekart_legacy_normalize(ekart_track_shipment($db, $o)); } }
if (!function_exists('delhivery_cancel_shipment'))     { function delhivery_cancel_shipment(PDO $db, int $o): array { return _ekart_legacy_normalize(ekart_cancel_shipment($db, $o)); } }
if (!function_exists('delhivery_request_pickup'))      { function delhivery_request_pickup(PDO $db, int $o): array { return _ekart_legacy_normalize(ekart_request_pickup($db, $o)); } }
