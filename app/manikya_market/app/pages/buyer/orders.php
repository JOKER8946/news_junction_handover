<?php
declare(strict_types=1);

auth_require_role('buyer');

$title = 'My Orders';

$buyerId = auth_user_id();

// Abandoned-payment cleanup: DISABLED 2026-07-15.
//
// This block used to cancel any 'new' order whose payment was still
// 'initiated' after 30 minutes, tagging it 'Payment abandoned'. It assumed
// 'initiated' means "Razorpay modal still open", but that assumption never
// held for COD: a COD order is created 'new' + 'initiated' and nothing in the
// codebase ever advances the payment row (order 3 reached 'delivered' still
// showing 'initiated'). The rule therefore matched every COD order older than
// 30 minutes and cancelled real, paid-on-delivery business — 2 confirmed
// losses (orders 20 and 27) before it was switched off.
//
// Kept as a comment rather than deleted: if abandoned Razorpay drafts ever
// need tidying, gate any replacement on p.provider = 'razorpay' and never on
// payment status alone.

// ensure invoice_file column exists to avoid SELECT errors on older DBs
ensure_order_column($db, 'invoice_file', "VARCHAR(255) NULL");
// ensure invoice_generated_at exists
ensure_order_column($db, 'invoice_generated_at');
// ensure cancel columns exist
ensure_order_column($db, 'cancelled_at', 'DATETIME NULL');
ensure_order_column($db, 'cancel_reason', "VARCHAR(255) NULL");
// (return flow removed)

// Handle cancel order request
if (request_method() === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel_order') {
    $cancelOrderId = (int)($_POST['order_id'] ?? 0);
    $cancelReason = post_string('cancel_reason');
    if (empty($cancelReason)) {
        flash_set('error', 'Please provide a reason for cancellation.');
        redirect_to('buyer/orders?order_id=' . $cancelOrderId);
        return;
    }
    
    // Get order to verify ownership and check 24-hour window
    $cancelOrder = db_fetch_one($db, 'SELECT id, status, created_at, buyer_id FROM orders WHERE id = :id LIMIT 1', ['id' => $cancelOrderId]);
    
    if ($cancelOrder && (int)$cancelOrder['buyer_id'] === $buyerId) {
        $hoursOld = (time() - strtotime($cancelOrder['created_at'])) / 3600;
        if ($hoursOld <= 24 && in_array($cancelOrder['status'], ['new', 'paid'])) {
            $refundMsg = '';
            if ($cancelOrder['status'] === 'paid') {
                $payment = db_fetch_one($db, "SELECT id, razorpay_payment_id, amount FROM payments WHERE order_id = :oid AND status = 'paid' AND provider = 'razorpay' LIMIT 1", ['oid' => $cancelOrderId]);
                if ($payment && !empty($payment['razorpay_payment_id'])) {
                    require_once __DIR__ . '/../../lib/razorpay.php';
                    // Refund through the platform's Razorpay (where the buyer actually paid).
                    $rzpConfig = platform_razorpay_get_config($db);
                    if ($rzpConfig) {
                        $refundAmountPaise = (int)round((float)$payment['amount'] * 100);
                        $refundRes = razorpay_refund_payment_full(
                            $rzpConfig['key_id'], $rzpConfig['key_secret'],
                            $payment['razorpay_payment_id'], $refundAmountPaise
                        );
                        $success  = $refundRes['ok'];
                        $refundId = $refundRes['refund_id'] ?? '';

                        // The cURL call sometimes times out even though Razorpay has
                        // actually processed the refund. Verify with Razorpay before
                        // declaring failure.
                        if (!$success) {
                            try {
                                $ch = curl_init("https://api.razorpay.com/v1/payments/" . $payment['razorpay_payment_id']);
                                curl_setopt_array($ch, [
                                    CURLOPT_RETURNTRANSFER => true,
                                    CURLOPT_USERPWD        => $rzpConfig['key_id'] . ':' . $rzpConfig['key_secret'],
                                    CURLOPT_TIMEOUT        => 10,
                                ]);
                                $resp = curl_exec($ch);
                                $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
                                curl_close($ch);
                                if ($http >= 200 && $http < 300 && $resp) {
                                    $pd = json_decode((string)$resp, true);
                                    if (is_array($pd)
                                        && in_array(($pd['refund_status'] ?? null), ['full', 'partial'], true)
                                        && (int)($pd['amount_refunded'] ?? 0) >= $refundAmountPaise) {
                                        $success = true;
                                        // Last refund id from the verify response, if available.
                                        $refundId = (string)($pd['last_refund_id'] ?? $refundId);
                                    }
                                }
                            } catch (Throwable $t) {
                                error_log('refund verify fetch failed: ' . $t->getMessage());
                            }
                        }

                        if ($success) {
                            // Atomic, idempotent: only flips 'paid' -> 'refunded',
                            // stamps the refund id, and skips on retry.
                            razorpay_record_refund($db,
                                (string)$payment['razorpay_payment_id'],
                                (string)$refundId, $refundAmountPaise);
                            $refundMsg = ' A refund has been initiated to your original payment method.';
                        } else {
                            $refundMsg = ' Order cancelled, but automated refund failed. Please contact support.';
                        }
                    }
                }
            }

            db_exec($db, 'UPDATE orders SET status = :status, cancelled_at = NOW(), cancel_reason = :reason WHERE id = :id', [
                'status' => 'cancelled',
                'reason' => $cancelReason,
                'id' => $cancelOrderId,
            ]);
            // Release the coupon usage count so a limited-quota coupon isn't
            // exhausted by cancelled orders.
            try {
                $cpRow = db_fetch_one($db, 'SELECT coupon_id FROM orders WHERE id = :id LIMIT 1', ['id' => $cancelOrderId]);
                $cpId = (int)($cpRow['coupon_id'] ?? 0);
                if ($cpId > 0 && function_exists('coupon_release_usage')) {
                    coupon_release_usage($db, $cpId);
                }
            } catch (Throwable $t) {
                error_log('coupon release on cancel failed: ' . $t->getMessage());
            }
            // Reverse seller wallet credit + platform commission for this order.
            try {
                reverse_merchant_wallet_credit_for_order($db, $cancelOrderId, 'cancelled');
            } catch (Throwable $t) {
                error_log('wallet reversal on cancel failed: ' . $t->getMessage());
            }
            try {
                require_once __DIR__ . '/../../lib/inventory.php';
                inventory_cancel_order($db, $cancelOrderId);
            } catch (Throwable $t) {
                // ignore
            }
            // Cancel Delhivery shipment if exists
            try {
                delhivery_ensure_schema($db);
                $cancelledOrder = db_fetch_one($db, 'SELECT delhivery_waybill FROM orders WHERE id = :id LIMIT 1', ['id' => $cancelOrderId]);
                if (!empty($cancelledOrder['delhivery_waybill'])) {
                    delhivery_cancel_shipment($db, $cancelOrderId);
                }
            } catch (Throwable $t) {
            }
            flash_set('success', 'Order cancelled successfully.' . $refundMsg);
        } else {
            flash_set('error', 'Cannot cancel order: 24-hour window expired or order already in progress');
        }
    }
    redirect_to('buyer/orders?order_id=' . $cancelOrderId);
}

// Handle feedback/rating submission
if (request_method() === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_rating') {
    $ratingOrderId = (int)($_POST['order_id'] ?? 0);
    $rating = (int)($_POST['rating'] ?? 0);
    $feedback = post_string('feedback');
    
    // Validate
    if ($ratingOrderId > 0 && $rating >= 1 && $rating <= 5) {
        // Check order exists and belongs to buyer and is delivered
        $ratingOrder = db_fetch_one($db, 'SELECT id, buyer_id, status FROM orders WHERE id = :id LIMIT 1', ['id' => $ratingOrderId]);
        
        if ($ratingOrder && (int)$ratingOrder['buyer_id'] === $buyerId && $ratingOrder['status'] === 'delivered') {
            // Ensure ratings table exists
            try {
                db_exec($db, '
                    CREATE TABLE IF NOT EXISTS ratings (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        order_id INT NOT NULL,
                        buyer_id INT NOT NULL,
                        rating INT NOT NULL,
                        feedback TEXT,
                        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                        FOREIGN KEY (order_id) REFERENCES orders(id),
                        FOREIGN KEY (buyer_id) REFERENCES users(id),
                        UNIQUE KEY unique_order_rating (order_id)
                    )
                ');
            } catch (Throwable $e) {
                // table may already exist
            }
            
            // Check if rating already exists
            $existingRating = db_fetch_one($db, 'SELECT id FROM ratings WHERE order_id = :order_id LIMIT 1', ['order_id' => $ratingOrderId]);
            
            if ($existingRating) {
                // Update existing rating
                db_exec($db, 'UPDATE ratings SET rating = :rating, feedback = :feedback, created_at = NOW() WHERE order_id = :order_id', [
                    'rating' => $rating,
                    'feedback' => $feedback,
                    'order_id' => $ratingOrderId,
                ]);
                flash_set('success', 'Rating updated successfully');
            } else {
                // Insert new rating
                db_exec($db, 'INSERT INTO ratings (order_id, buyer_id, rating, feedback) VALUES (:order_id, :buyer_id, :rating, :feedback)', [
                    'order_id' => $ratingOrderId,
                    'buyer_id' => $buyerId,
                    'rating' => $rating,
                    'feedback' => $feedback,
                ]);
                flash_set('success', 'Thank you for your feedback!');
            }
            redirect_to('buyer/orders?order_id=' . $ratingOrderId);
        }
    }
}

// Get buyer's ratings
$buyerRatings = [];
try {
    $buyerRatings = db_fetch_all($db, 'SELECT * FROM ratings WHERE buyer_id = :buyer_id', ['buyer_id' => $buyerId]) ?: [];
} catch (Throwable $e) {
    $buyerRatings = [];
}
$ratingsByOrderId = [];
foreach ($buyerRatings as $r) {
    $ratingsByOrderId[$r['order_id']] = $r;
}

// return flow removed

// ── Amazon-style filters: time range, tab, free-text search ──
$timeRange   = (string)($_GET['range'] ?? '3m');
$validRanges = ['3m' => 3, '6m' => 6, '1y' => 12, 'all' => 0];
if (!array_key_exists($timeRange, $validRanges)) $timeRange = '3m';

$tab = (string)($_GET['tab'] ?? 'orders');
if (!in_array($tab, ['orders', 'buy_again', 'unshipped'], true)) $tab = 'orders';

$searchQ = trim((string)($_GET['q'] ?? ''));

$filterSql    = ['o.buyer_id = :buyer_id'];
$filterParams = ['buyer_id' => $buyerId];

if ($validRanges[$timeRange] > 0) {
    $filterSql[]            = 'o.created_at >= DATE_SUB(NOW(), INTERVAL :months MONTH)';
    $filterParams['months'] = $validRanges[$timeRange];
}
if ($tab === 'unshipped') {
    $filterSql[] = "o.status IN ('new','paid','packed','ready_to_pick')";
}
if ($searchQ !== '') {
    $filterSql[]       = '(o.order_no LIKE :q1 OR EXISTS (SELECT 1 FROM order_items oi2 LEFT JOIN products p2 ON p2.id = oi2.product_id WHERE oi2.order_id = o.id AND p2.name LIKE :q2))';
    $filterParams['q1'] = '%' . $searchQ . '%';
    $filterParams['q2'] = '%' . $searchQ . '%';
}
$filterWhere = implode(' AND ', $filterSql);

$orders = db_fetch_all($db, "
    SELECT
        o.id, o.order_no, o.status, o.created_at,
        o.total_amount, o.shipping_tracking_no, o.expected_delivery_date,
        o.delhivery_waybill, o.delhivery_status, o.delhivery_status_synced_at,
        o.invoice_file, o.invoice_generated_at, o.delivery_address_id,
        o.shipped_at, o.delivered_at, o.cancelled_at, o.returned_at,
        COUNT(oi.id) as item_count
    FROM orders o
    LEFT JOIN order_items oi ON oi.order_id = o.id
    WHERE $filterWhere
    GROUP BY o.id
    ORDER BY o.created_at DESC
", $filterParams);

// Per-order status timestamps. Build a [order_id][status] => timestamp map
// from the order_tracking history so the buyer's progress timeline can show
// when each step actually happened.
$orderStatusTimestamps = [];
if (!empty($orders)) {
    $orderIdsForTracking = array_map(fn($o) => (int)$o['id'], $orders);
    $ph = implode(',', array_fill(0, count($orderIdsForTracking), '?'));
    try {
        $trackRows = db_fetch_all($db, "
            SELECT order_id, status, MIN(updated_at) AS first_at
            FROM order_tracking
            WHERE order_id IN ($ph)
            GROUP BY order_id, status
        ", $orderIdsForTracking) ?: [];
        foreach ($trackRows as $tr) {
            $orderStatusTimestamps[(int)$tr['order_id']][(string)$tr['status']] = (string)$tr['first_at'];
        }
    } catch (Throwable $t) { /* tracking table may be empty */ }
}

// Fetch first item (image + name) for each displayed order — Amazon-style preview
$orderItemsPreview = [];
$orderItemsAll = [];
if (!empty($orders)) {
    $orderIds = array_map(fn($o) => (int)$o['id'], $orders);
    $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
    try {
        $rows = db_fetch_all($db, "
            SELECT oi.order_id, oi.product_id, oi.qty_kg, oi.price_per_kg, p.name AS product_name, p.image_path AS product_image, p.unit AS product_unit
            FROM order_items oi
            LEFT JOIN products p ON p.id = oi.product_id
            WHERE oi.order_id IN ($placeholders)
            ORDER BY oi.order_id, oi.id
        ", $orderIds);
        foreach ($rows as $r) {
            $oid = (int)$r['order_id'];
            if (!isset($orderItemsAll[$oid])) $orderItemsAll[$oid] = [];
            $orderItemsAll[$oid][] = $r;
            if (!isset($orderItemsPreview[$oid])) $orderItemsPreview[$oid] = $r;
        }
    } catch (Throwable $t) { /* ignore */ }
}

// Buyer name + ship-to address for headers
$buyerInfo = db_fetch_one($db, 'SELECT full_name FROM users WHERE id = :id LIMIT 1', ['id' => $buyerId]);
$buyerName = (string)($buyerInfo['full_name'] ?? 'You');
$addressMap = [];
try {
    $addrRows = db_fetch_all($db, 'SELECT id, label, city FROM buyer_addresses WHERE buyer_id = :bid', ['bid' => $buyerId]) ?: [];
    foreach ($addrRows as $a) $addressMap[(int)$a['id']] = $a;
} catch (Throwable $t) { /* ignore */ }

// Get tracking history for orders if viewing a specific order
$selectedOrderId = (int)($_GET['order_id'] ?? 0);
$showFullDetails = isset($_GET['details']) && $_GET['details'] === '1';
$trackingHistory = [];
$orderItems = [];
if ($selectedOrderId > 0) {
    // Verify this order belongs to the logged-in buyer
    $ownerCheck = db_fetch_one($db, 'SELECT id FROM orders WHERE id = :id AND buyer_id = :buyer_id LIMIT 1', ['id' => $selectedOrderId, 'buyer_id' => $buyerId]);
    if ($ownerCheck) {
        // Select the latest entry per status (exclude return_ statuses) to avoid repeated duplicate rows
        $trackingHistory = db_fetch_all($db, "
            SELECT ot.*, u.full_name as updated_by_name
            FROM order_tracking ot
            LEFT JOIN users u ON u.id = ot.updated_by_id AND ot.updated_by_type = 'logistics'
            JOIN (
                SELECT status, MAX(updated_at) AS max_updated
                FROM order_tracking
                WHERE order_id = :order_id_sub
                  AND status NOT LIKE 'return_%'
                GROUP BY status
            ) grouped ON grouped.status = ot.status AND grouped.max_updated = ot.updated_at
            WHERE ot.order_id = :order_id
            ORDER BY CASE ot.status
                WHEN 'paid' THEN 1
                WHEN 'packed' THEN 2
                WHEN 'ready_to_pick' THEN 3
                WHEN 'shipped' THEN 4
                WHEN 'in_transit' THEN 5
                WHEN 'delivered' THEN 6
                WHEN 'cancelled' THEN 7
                ELSE 8
            END
        ", ['order_id_sub' => $selectedOrderId, 'order_id' => $selectedOrderId]);

        // Get order items if showing full details
        if ($showFullDetails) {
            $orderItems = db_fetch_all($db, '
                SELECT oi.*, p.name as product_name, p.image_path as product_image, p.unit as product_unit
                FROM order_items oi
                LEFT JOIN products p ON p.id = oi.product_id
                WHERE oi.order_id = :order_id
            ', ['order_id' => $selectedOrderId]);
        }
    } else {
        $selectedOrderId = 0;
    }
}

// Load Delhivery tracking events for the selected order
$delhiveryEvents = [];
if ($selectedOrderId > 0) {
    $selOrder = null;
    foreach ($orders as $o) {
        if ((int)$o['id'] === $selectedOrderId) { $selOrder = $o; break; }
    }
    if ($selOrder && !empty($selOrder['delhivery_waybill'])) {
        // Auto-refresh if stale (>2 hours) and not delivered
        if ($selOrder['status'] !== 'delivered') {
            $lastSync = $selOrder['delhivery_status_synced_at'] ?? null;
            if (!$lastSync || (time() - strtotime($lastSync)) > 7200) {
                try {
                    delhivery_ensure_schema($db);
                    delhivery_track_shipment($db, $selectedOrderId);
                } catch (Throwable $t) {}
            }
        }
        try {
            $delhiveryEvents = db_fetch_all($db, 'SELECT * FROM delhivery_tracking_events WHERE order_id = :oid ORDER BY event_time DESC', ['oid' => $selectedOrderId]);
        } catch (Throwable $t) { $delhiveryEvents = []; }
    }
}

$content = function () use ($orders, $selectedOrderId, $trackingHistory, $showFullDetails, $orderItems, $buyerId, $ratingsByOrderId, $delhiveryEvents, $orderItemsPreview, $orderItemsAll, $buyerName, $addressMap, $timeRange, $tab, $searchQ, $orderStatusTimestamps) {
    $statusBadgeClass = function($status) {
        $map = [
            'new' => 'bg-secondary',
            'paid' => 'bg-info',
            'packed' => 'bg-warning text-dark',
            'ready_to_pick' => 'bg-warning text-dark',
            'shipped' => 'bg-primary',
            'in_transit' => 'bg-primary',
            'delivered' => 'bg-success',
            'cancelled' => 'bg-danger',
        ];
        return $map[$status] ?? 'bg-secondary';
    };

    $statusLabel = function($status) {
        $map = [
            'new' => 'New',
            'paid' => 'Confirmed',
            'packed' => 'Packed',
            'ready_to_pick' => 'Ready to Pick',
            'shipped' => 'Shipped',
            'in_transit' => 'In Transit',
            'delivered' => 'Delivered',
            'cancelled' => 'Cancelled',
        ];
        return $map[$status] ?? ucfirst($status);
    };
    ?>
    <div class="container py-4">
        <!-- Amazon-style page header -->
        <div class="amz-page-head">
            <h1 class="amz-page-title">Your Orders</h1>
            <form method="get" class="amz-search-form">
                <input type="hidden" name="p" value="buyer/orders">
                <input type="hidden" name="range" value="<?= e($timeRange) ?>">
                <input type="hidden" name="tab" value="<?= e($tab) ?>">
                <input class="amz-search-input" type="search" name="q" placeholder="Search all orders" value="<?= e($searchQ) ?>">
                <button class="amz-search-btn" type="submit">Search Orders</button>
            </form>
        </div>

        <!-- Tabs -->
        <div class="amz-tabs">
            <a class="amz-tab <?= $tab === 'orders' ? 'active' : '' ?>" href="?p=buyer/orders&tab=orders&range=<?= e($timeRange) ?>">Orders</a>
            <a class="amz-tab <?= $tab === 'buy_again' ? 'active' : '' ?>" href="?p=buyer/orders&tab=buy_again&range=<?= e($timeRange) ?>">Buy Again</a>
            <a class="amz-tab <?= $tab === 'unshipped' ? 'active' : '' ?>" href="?p=buyer/orders&tab=unshipped&range=<?= e($timeRange) ?>">Not Yet Shipped</a>
        </div>

        <!-- Filter row -->
        <div class="amz-filter-row">
            <span class="amz-filter-count"><?= count($orders) ?> order<?= count($orders) !== 1 ? 's' : '' ?> placed in</span>
            <form method="get" class="d-inline">
                <input type="hidden" name="p" value="buyer/orders">
                <input type="hidden" name="tab" value="<?= e($tab) ?>">
                <?php if ($searchQ !== ''): ?><input type="hidden" name="q" value="<?= e($searchQ) ?>"><?php endif; ?>
                <select name="range" class="amz-filter-select" onchange="this.form.submit()">
                    <option value="3m"  <?= $timeRange === '3m'  ? 'selected' : '' ?>>past 3 months</option>
                    <option value="6m"  <?= $timeRange === '6m'  ? 'selected' : '' ?>>past 6 months</option>
                    <option value="1y"  <?= $timeRange === '1y'  ? 'selected' : '' ?>>past year</option>
                    <option value="all" <?= $timeRange === 'all' ? 'selected' : '' ?>>anytime</option>
                </select>
            </form>
        </div>

        <?php $success = flash_get('success'); ?>
        <?php $error = flash_get('error'); ?>
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= e($success) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= e($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (empty($orders)): ?>
            <div class="amz-empty">
                <i data-lucide="inbox" class="mm-icon" style="width:32px;height:32px;color:#999;"></i>
                <h3>No orders yet</h3>
                <p>You haven't placed any orders. <a href="?p=home">Start shopping</a></p>
            </div>
        <?php else: ?>
            <div class="row g-3">
                <!-- Orders List -->
                <div class="<?= $selectedOrderId > 0 ? 'col-lg-7' : 'col-12' ?>">
                    <div class="amz-orders">
                        <?php foreach ($orders as $order):
                            $oid = (int)$order['id'];
                            $items = $orderItemsAll[$oid] ?? [];
                            $preview = $orderItemsPreview[$oid] ?? null;
                            $shipAddr = $addressMap[(int)($order['delivery_address_id'] ?? 0)] ?? null;
                            $status = (string)$order['status'];

                            // Pick a contextual hero status line (like Amazon "Delivered today" / "Arriving Friday")
                            $heroTitle = '';
                            $heroSub = '';
                            $heroColor = '#067D62';
                            if ($status === 'delivered') {
                                $heroTitle = 'Delivered';
                                $heroSub = 'Your order was delivered.';
                                $heroColor = '#067D62';
                            } elseif (in_array($status, ['shipped','in_transit'])) {
                                if (!empty($order['expected_delivery_date'])) {
                                    $heroTitle = 'Arriving by ' . date('D, d M', strtotime((string)$order['expected_delivery_date']));
                                } else {
                                    $heroTitle = 'On the way';
                                }
                                $heroSub = 'Your package is in transit.';
                                $heroColor = '#067D62';
                            } elseif ($status === 'ready_to_pick') {
                                $heroTitle = 'Ready to pick';
                                $heroSub = 'Awaiting courier pickup.';
                                $heroColor = '#B45309';
                            } elseif ($status === 'packed') {
                                $heroTitle = 'Packed';
                                $heroSub = 'Your order has been packed.';
                                $heroColor = '#B45309';
                            } elseif (in_array($status, ['new','paid'])) {
                                $heroTitle = 'Order confirmed';
                                $heroSub = 'We will start preparing your order shortly.';
                                $heroColor = '#1D4ED8';
                            } elseif ($status === 'cancelled') {
                                $heroTitle = 'Cancelled';
                                $heroSub = 'This order was cancelled.';
                                $heroColor = '#B91C1C';
                            } elseif ($status === 'returned') {
                                $heroTitle = 'Returned';
                                $heroSub = 'This order was returned.';
                                $heroColor = '#B91C1C';
                            }

                            $hoursOld = (time() - strtotime((string)$order['created_at'])) / 3600;
                            $canCancel = ($hoursOld <= 24) && in_array($status, ['new','paid'], true);
                            $hasInvoice = in_array($status, ['paid','packed','ready_to_pick','shipped','in_transit','delivered'], true);
                            $canTrack = !empty($order['delhivery_waybill']) || in_array($status, ['shipped','in_transit'], true);

                            // 4-step progress timeline. Map raw status → step index (0..3).
                            // Negative index = cancelled/returned (rendered separately below).
                            $progressSteps = [
                                ['label' => 'Order placed', 'icon' => 'shopping-bag'],
                                ['label' => 'Packed',       'icon' => 'package'],
                                ['label' => 'Shipped',      'icon' => 'truck'],
                                ['label' => 'Delivered',    'icon' => 'check-circle'],
                            ];
                            $statusToStep = [
                                'new'           => 0,
                                'paid'          => 0,
                                'packed'        => 1,
                                'ready_to_pick' => 1,
                                'shipped'       => 2,
                                'in_transit'    => 2,
                                'delivered'     => 3,
                            ];
                            $currentStep = $statusToStep[$status] ?? -1;
                            $isTerminalFail = in_array($status, ['cancelled','returned','refunded','return_requested'], true);

                            // Resolve the timestamp for each step. Prefer the order_tracking
                            // history (first time we recorded the status) and fall back to the
                            // dedicated columns on `orders` where they exist.
                            $tsTracking = $orderStatusTimestamps[$oid] ?? [];
                            $stepTimestamps = [
                                // Order placed → always the order's created_at
                                0 => (string)$order['created_at'],
                                // Packed → tracking 'packed'; if missing but later step reached, no fallback
                                1 => $tsTracking['packed'] ?? ($tsTracking['ready_to_pick'] ?? null),
                                // Shipped → orders.shipped_at, fallback to tracking
                                2 => !empty($order['shipped_at'])
                                        ? (string)$order['shipped_at']
                                        : ($tsTracking['shipped'] ?? ($tsTracking['in_transit'] ?? null)),
                                // Delivered → orders.delivered_at, fallback to tracking
                                3 => !empty($order['delivered_at'])
                                        ? (string)$order['delivered_at']
                                        : ($tsTracking['delivered'] ?? null),
                            ];
                            $fmtTs = function (?string $ts): string {
                                if (!$ts) return '';
                                $t = strtotime($ts);
                                if (!$t) return '';
                                return date('d M, h:i A', $t);
                            };
                        ?>
                            <div class="amz-order-card">
                                <!-- Top meta strip -->
                                <div class="amz-order-head">
                                    <div class="amz-meta-block">
                                        <div class="amz-label">Order placed</div>
                                        <div class="amz-value"><?= date('d M Y', strtotime((string)$order['created_at'])) ?></div>
                                    </div>
                                    <div class="amz-meta-block">
                                        <div class="amz-label">Total</div>
                                        <div class="amz-value">₹<?= number_format((float)$order['total_amount'], 2) ?></div>
                                    </div>
                                    <div class="amz-meta-block">
                                        <div class="amz-label">Ship to</div>
                                        <div class="amz-value"><?= e($buyerName) ?><?= $shipAddr ? ' &middot; ' . e((string)$shipAddr['city']) : '' ?></div>
                                    </div>
                                    <div class="amz-meta-block amz-meta-right">
                                        <div class="amz-label">Order # <?= e((string)$order['order_no']) ?></div>
                                        <div class="amz-meta-actions">
                                            <a href="?p=buyer/orders&order_id=<?= $oid ?>&details=1">View order details</a>
                                            <?php if ($hasInvoice): ?>
                                                <span class="amz-sep">|</span>
                                                <a href="?p=buyer/order-invoice&id=<?= $oid ?>">Invoice</a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- Body -->
                                <div class="amz-order-body">
                                    <div class="amz-body-main">
                                        <?php if ($heroTitle): ?>
                                        <div class="amz-status-title" style="color: <?= $heroColor ?>;"><?= e($heroTitle) ?></div>
                                        <?php if ($heroSub): ?>
                                        <div class="amz-status-sub"><?= e($heroSub) ?></div>
                                        <?php endif; ?>
                                        <?php endif; ?>

                                        <?php if ($isTerminalFail): ?>
                                            <?php
                                                $failTs = '';
                                                if (!empty($order['cancelled_at'])) {
                                                    $failTs = $fmtTs((string)$order['cancelled_at']);
                                                } elseif (!empty($order['returned_at'])) {
                                                    $failTs = $fmtTs((string)$order['returned_at']);
                                                }
                                            ?>
                                            <div class="amz-progress-failed">
                                                <i data-lucide="x-circle" style="width:16px;height:16px;"></i>
                                                <span><?= e($heroTitle ?: ucfirst($status)) ?></span>
                                                <?php if ($failTs !== ''): ?>
                                                    <span class="amz-progress-failed-time">· <?= e($failTs) ?></span>
                                                <?php endif; ?>
                                            </div>
                                        <?php elseif ($currentStep >= 0): ?>
                                            <div class="amz-progress" role="progressbar"
                                                 aria-label="Order status timeline"
                                                 aria-valuemin="0" aria-valuemax="<?= count($progressSteps) - 1 ?>"
                                                 aria-valuenow="<?= $currentStep ?>">
                                                <?php foreach ($progressSteps as $i => $step):
                                                    $isDone    = $i <  $currentStep;
                                                    $isCurrent = $i === $currentStep;
                                                ?>
                                                    <div class="amz-progress-step <?= $isDone ? 'done' : ($isCurrent ? 'current' : 'upcoming') ?>">
                                                        <div class="amz-progress-dot">
                                                            <?php if ($isDone): ?>
                                                                <i data-lucide="check" style="width:14px;height:14px;"></i>
                                                            <?php else: ?>
                                                                <i data-lucide="<?= e($step['icon']) ?>" style="width:14px;height:14px;"></i>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="amz-progress-label"><?= e($step['label']) ?></div>
                                                        <?php $stepTs = $fmtTs($stepTimestamps[$i] ?? null); ?>
                                                        <?php if ($stepTs !== ''): ?>
                                                            <div class="amz-progress-time"><?= e($stepTs) ?></div>
                                                        <?php elseif ($isCurrent || $isDone): ?>
                                                            <div class="amz-progress-time amz-progress-time-muted">—</div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <?php if ($i < count($progressSteps) - 1): ?>
                                                        <div class="amz-progress-line <?= $i < $currentStep ? 'done' : '' ?>"></div>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>

                                        <?php if (!empty($items)): ?>
                                        <div class="amz-items">
                                            <?php foreach (array_slice($items, 0, 3) as $it): ?>
                                                <div class="amz-item">
                                                    <?php if (!empty($it['product_image'])): ?>
                                                        <img class="amz-item-img" src="<?= e((string)$it['product_image']) ?>" alt="<?= e((string)$it['product_name']) ?>">
                                                    <?php else: ?>
                                                        <div class="amz-item-img amz-item-img-ph"><i data-lucide="image" style="width:24px;height:24px;color:#ccc;"></i></div>
                                                    <?php endif; ?>
                                                    <div class="amz-item-info">
                                                        <div class="amz-item-name"><?= e((string)$it['product_name']) ?></div>
                                                        <div class="amz-item-meta"><?= number_format((float)$it['qty_kg'], 2) ?> <?= e(product_unit_label($it['product_unit'] ?? 'kg')) ?></div>
                                                        <a class="amz-item-link" href="?p=buyer/orders&order_id=<?= $oid ?>&details=1">View your item</a>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                            <?php if (count($items) > 3): ?>
                                                <div class="amz-more"><a href="?p=buyer/orders&order_id=<?= $oid ?>&details=1">+ <?= count($items) - 3 ?> more item<?= count($items) - 3 > 1 ? 's' : '' ?></a></div>
                                            <?php endif; ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Action buttons -->
                                    <div class="amz-actions">
                                        <?php if ($canTrack): ?>
                                            <a class="amz-btn amz-btn-primary" href="?p=buyer/orders&order_id=<?= $oid ?>">Track package</a>
                                        <?php endif; ?>
                                        <a class="amz-btn" href="?p=buyer/orders&order_id=<?= $oid ?>&details=1">View order details</a>
                                        <?php if ($hasInvoice): ?>
                                            <a class="amz-btn" href="?p=buyer/order-invoice&id=<?= $oid ?>">View invoice</a>
                                        <?php endif; ?>
                                        <?php if ($status === 'delivered'): ?>
                                            <a class="amz-btn" href="?p=buyer/orders&order_id=<?= $oid ?>">Leave feedback</a>
                                        <?php endif; ?>
                                        <?php if ($canCancel): ?>
                                            <a class="amz-btn" href="?p=buyer/orders&order_id=<?= $oid ?>">Cancel order</a>
                                        <?php endif; ?>
                                        <a class="amz-btn" href="?p=buyer/tickets">Get product support</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Order Details / Tracking -->
                <?php if ($selectedOrderId > 0): ?>
                    <div class="col-lg-5">
                        <?php
                        $selectedOrder = array_filter($orders, fn($o) => (int)$o['id'] === $selectedOrderId);
                        $selectedOrder = reset($selectedOrder);
                        
                        if ($selectedOrder):
                        ?>
                            <div class="bg-white border rounded-3 p-4 mm-card" style="<?= $showFullDetails ? '' : 'position: sticky; top: 90px; max-height: calc(100vh - 110px); overflow-y: auto;' ?>">
                                <?php if ($showFullDetails): ?>
                                    <!-- Full Details View -->
                                    <div>
                                        <a href="?p=buyer/orders&order_id=<?= (int)$selectedOrder['id'] ?>" class="btn btn-sm btn-outline-secondary mb-3">
                                            <i data-lucide="arrow-left" class="mm-icon" style="width: 14px; height: 14px;"></i> Back
                                        </a>
                                        
                                        <div class="fw-semibold mb-4">Order #<?= e((string)$selectedOrder['order_no']) ?></div>
                                        
                                        <!-- Order Items -->
                                        <div class="mb-4">
                                            <div class="fw-semibold mb-3 small text-muted">Order Items</div>
                                            <?php if (!empty($orderItems)): ?>
                                                <div class="space-y-3">
                                                    <?php foreach ($orderItems as $item): ?>
                                                        <div class="border-bottom pb-3">
                                                            <div class="d-flex gap-2">
                                                                <?php if (!empty($item['product_image'])): ?>
                                                                    <img src="<?= e($item['product_image']) ?>" alt="<?= e((string)$item['product_name']) ?>" style="width: 60px; height: 60px; object-fit: cover; border-radius: 4px;">
                                                                <?php else: ?>
                                                                    <div style="width: 60px; height: 60px; background: #f0f0f0; border-radius: 4px;"></div>
                                                                <?php endif; ?>
                                                                <div class="flex-grow-1">
                                                                    <div class="fw-semibold small"><?= e((string)$item['product_name']) ?></div>
                                                                    <?php $itUnit = product_unit_label($item['product_unit'] ?? 'kg'); ?>
                                                                    <div class="text-muted small">Quantity: <?= (float)$item['qty_kg'] ?> <?= e($itUnit) ?></div>
                                                                    <div class="text-muted small">₹<?= e((string)$item['price_per_kg']) ?> per <?= e($itUnit) ?></div>
                                                                    <div class="fw-semibold small">Subtotal: ₹<?= e((string)$item['line_total']) ?></div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php else: ?>
                                                <div class="text-muted small">No items found</div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <hr>
                                        
                                        <!-- Order Summary -->
                                        <div class="mb-4">
                                            <div class="d-flex justify-content-between mb-2">
                                                <div class="text-muted small">Total Amount</div>
                                                <div class="fw-semibold">₹<?= e((string)$selectedOrder['total_amount']) ?></div>
                                            </div>
                                            <div class="d-flex justify-content-between">
                                                <div class="text-muted small">Order Date</div>
                                                <div class="small"><?= date('M d, Y', strtotime((string)$selectedOrder['created_at'])) ?></div>
                                            </div>
                                            <?php if (!empty($selectedOrder['shipping_tracking_no'])): ?>
                                                <div class="d-flex justify-content-between mt-2">
                                                    <div class="text-muted small">Tracking Number</div>
                                                    <div class="small fw-semibold"><?= e((string)$selectedOrder['shipping_tracking_no']) ?></div>
                                                </div>
                                            <?php endif; ?>
                                            <?php if (!empty($selectedOrder['expected_delivery_date'])): ?>
                                                <div class="d-flex justify-content-between mt-2">
                                                    <div class="text-muted small">Expected Delivery</div>
                                                    <div class="small"><?= date('M d, Y', strtotime((string)$selectedOrder['expected_delivery_date'])) ?></div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <!-- Cancel Button & Buttons -->
                                        <?php 
                                            $hoursSinceOrder = (time() - strtotime((string)$selectedOrder['created_at'])) / 3600;
                                            $canCancel = in_array($selectedOrder['status'], ['new', 'paid']) && $hoursSinceOrder <= 24;
                                            $canReturn = false; // return flow removed
                                        ?>
                                        <div class="d-grid gap-2">
                                            <?php if ($canCancel): ?>
                                                <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#cancelOrderModal">
                                                    <i data-lucide="x-circle" class="mm-icon" style="width: 14px; height: 14px;"></i> Cancel Order
                                                </button>
                                            <?php elseif (in_array($selectedOrder['status'], ['new', 'paid'])): ?>
                                                <div class="alert alert-info small mb-0">24-hour cancellation window expired</div>
                                            <?php endif; ?>
                                            
                                            <!-- Return functionality removed -->
                                            
                                            <?php if (in_array($selectedOrder['status'], ['paid', 'packed', 'ready_to_pick', 'shipped', 'in_transit', 'delivered'])): ?>
                                                <?php if (!empty($selectedOrder['invoice_file'])): ?>
                                                    <a href="<?= e($selectedOrder['invoice_file']) ?>" target="_blank" class="btn btn-sm btn-primary">Download Invoice</a>
                                                <?php else: ?>
                                                    <a href="?p=buyer/order-invoice&id=<?= (int)$selectedOrder['id'] ?>" class="btn btn-sm btn-primary">View / Download Invoice</a>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <!-- Cancel Order Modal -->
                                        <div class="modal fade" id="cancelOrderModal" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Cancel Order</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <form method="post">
                                                        <div class="modal-body">
                                                            <p class="text-muted">You can cancel this order within 24 hours of placing it. Are you sure you want to cancel?</p>
                                                            <div class="mb-3">
                                                                <label class="form-label">Reason for cancellation <span class="text-danger">*</span></label>
                                                                <textarea class="form-control" name="cancel_reason" rows="3" placeholder="Tell us why you're cancelling..." required></textarea>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <input type="hidden" name="action" value="cancel_order">
                                                            <input type="hidden" name="order_id" value="<?= (int)$selectedOrder['id'] ?>">
                                                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Keep Order</button>
                                                            <button type="submit" class="btn btn-danger">Cancel Order</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Return modal removed -->
                                    </div>
                                <?php else: ?>
                                    <!-- Tracking View -->
                                    <div class="fw-semibold mb-3">Order Details</div>
                                
                                <!-- Tracking Timeline -->
                                <div class="mb-4">
                                    <div class="fw-semibold mb-3 small text-muted">Tracking Status</div>
                                    
                                    <div class="position-relative">
                                        <!-- Timeline Line -->
                                        <div class="position-absolute" style="left: 11px; top: 30px; bottom: 0; width: 2px; background: #dee2e6;"></div>
                                        
                                        <!-- Status Steps -->
                                        <div class="space-y-3">
                                            <!-- Order Placed -->
                                            <div class="d-flex gap-2">
                                                <div class="position-relative" style="width: 24px;">
                                                    <div class="rounded-circle bg-success border-2 border-success" style="width: 24px; height: 24px;"></div>
                                                </div>
                                                <div>
                                                    <div class="fw-semibold small">Order Placed</div>
                                                    <div class="text-muted small"><?= date('M d, Y H:i', strtotime((string)$selectedOrder['created_at'])) ?></div>
                                                </div>
                                            </div>
                                            
                                            <!-- Display actual tracking history from DB -->
                                            <?php if (!empty($trackingHistory)): ?>
                                                <?php foreach ($trackingHistory as $th): ?>
                                                    <?php
                                                        // Map status to badge color (no return statuses shown)
                                                        $statusColor = 'secondary';
                                                        $s = strtolower((string)$th['status']);
                                                        if (strpos($s, 'cancel') !== false || strpos($s, 'reject') !== false) {
                                                            $statusColor = 'danger';
                                                        } elseif (in_array($s, ['shipped','in_transit','in transit'], true)) {
                                                            $statusColor = 'primary';
                                                        } elseif (in_array($s, ['packed','ready_to_pick'], true)) {
                                                            $statusColor = 'warning';
                                                        } elseif (in_array($s, ['delivered'], true)) {
                                                            $statusColor = 'success';
                                                        } elseif (in_array($s, ['paid'], true)) {
                                                            $statusColor = 'info';
                                                        } else {
                                                            $statusColor = 'secondary';
                                                        }
                                                    ?>
                                                    <div class="d-flex gap-2">
                                                        <div class="position-relative" style="width: 24px;">
                                                            <div class="rounded-circle bg-<?= $statusColor ?> border-2 border-<?= $statusColor ?>" style="width: 24px; height: 24px;"></div>
                                                        </div>
                                                        <div>
                                                            <div class="fw-semibold small"><?= ucwords(str_replace('_', ' ', (string)$th['status'])) ?></div>
                                                            <div class="text-muted small"><?= date('M d, Y H:i', strtotime((string)$th['updated_at'])) ?></div>
                                                            <?php if (!empty($th['notes'])): ?>
                                                                <div class="text-muted small"><em>"<?= e((string)$th['notes']) ?>"</em></div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- Delhivery Live Tracking -->
                                <?php if (!empty($delhiveryEvents)): ?>
                                <div class="mb-4">
                                    <div class="fw-semibold mb-2 small text-muted">🚚 Live Courier Tracking (Delhivery)</div>
                                    <?php if (!empty($selectedOrder['delhivery_waybill'])): ?>
                                        <div class="small mb-2">
                                            <span class="text-muted">AWB:</span>
                                            <span class="fw-monospace fw-semibold"><?= e((string)$selectedOrder['delhivery_waybill']) ?></span>
                                            <?php if (!empty($selectedOrder['delhivery_status'])): ?>
                                                <span class="badge bg-primary ms-2"><?= e((string)$selectedOrder['delhivery_status']) ?></span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm small mb-0">
                                            <thead><tr><th>Time</th><th>Status</th><th>Location</th></tr></thead>
                                            <tbody>
                                                <?php foreach ($delhiveryEvents as $evt): ?>
                                                    <tr>
                                                        <td class="text-nowrap"><?= $evt['event_time'] ? date('M d H:i', strtotime((string)$evt['event_time'])) : '-' ?></td>
                                                        <td><?= e((string)$evt['scan_status']) ?></td>
                                                        <td><?= e((string)$evt['location']) ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <hr>

                                <!-- Order Summary -->
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between mb-2">
                                        <div class="text-muted small">Subtotal</div>
                                        <div class="small">₹<?= e((string)$selectedOrder['total_amount']) ?></div>
                                    </div>
                                </div>

                                <!-- Rating/Feedback Form (only for delivered orders) -->
                                <?php if ($selectedOrder['status'] === 'delivered'): ?>
                                    <?php $existingRating = $ratingsByOrderId[$selectedOrder['id']] ?? null; ?>
                                    <div class="bg-light p-3 rounded-3 mb-3">
                                        <div class="fw-semibold small mb-2">Your Rating</div>
                                        <form method="post">
                                            <input type="hidden" name="action" value="submit_rating">
                                            <input type="hidden" name="order_id" value="<?= (int)$selectedOrder['id'] ?>">
                                            
                                            <div class="mb-2">
                                                <label class="form-label small">Rate this order (1-5 stars)</label>
                                                <div class="btn-group w-100" role="group">
                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                        <input type="radio" class="btn-check" name="rating" id="rating<?= $i ?>" value="<?= $i ?>" <?= ($existingRating && $existingRating['rating'] == $i) ? 'checked' : '' ?> required>
                                                        <label class="btn btn-outline-warning" for="rating<?= $i ?>">
                                                            <?php for ($s = 0; $s < $i; $s++): ?><i data-lucide="star" class="mm-icon" style="width:14px;height:14px;fill:gold;stroke:gold;"></i><?php endfor; ?>
                                                        </label>
                                                    <?php endfor; ?>
                                                </div>
                                            </div>
                                            
                                            <div class="mb-2">
                                                <label class="form-label small">Your Feedback (optional)</label>
                                                <textarea class="form-control" name="feedback" rows="2" placeholder="Share your experience..." maxlength="500"><?= $existingRating ? e((string)$existingRating['feedback']) : '' ?></textarea>
                                            </div>
                                            
                                            <button type="submit" class="btn btn-sm btn-mm w-100"><?= $existingRating ? 'Update' : 'Submit' ?> Rating</button>
                                        </form>
                                    </div>
                                <?php endif; ?>

                                <!-- View Full Order Details -->
                                <div class="d-grid gap-2">
                                    <a href="?p=buyer/orders&order_id=<?= (int)$selectedOrder['id'] ?>&details=1" class="btn btn-sm btn-outline-secondary">
                                        View Full Details
                                    </a>
                                    <?php if (!empty($selectedOrder['invoice_generated_at'])): ?>
                                        <a href="?p=buyer/order-invoice&id=<?= (int)$selectedOrder['id'] ?>" class="btn btn-sm btn-primary">View Invoice</a>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Icons
        if (window.lucide) window.lucide.createIcons();
    </script>

    <style>
        .space-y-3 > * + * { margin-top: 1rem; }
        .hover-shadow:hover { box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); }

        /* Amazon-style page header */
        .amz-page-head {
            display: flex; align-items: center; justify-content: space-between;
            gap: 24px; flex-wrap: wrap;
            margin-bottom: 14px;
        }
        .amz-page-title {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            font-size: 1.7rem; font-weight: 500; color: #0F1111; margin: 0;
        }
        .amz-search-form {
            display: flex; flex: 1 1 auto; max-width: 540px;
        }
        .amz-search-input {
            flex: 1; min-width: 0;
            border: 1px solid #888C8C; border-right: 0;
            background: #fff; color: #0F1111;
            padding: 8px 14px;
            border-radius: 8px 0 0 8px;
            font-size: .92rem; outline: 0;
            box-shadow: 0 1px 0 rgba(0,0,0,.05);
        }
        .amz-search-input:focus {
            border-color: #007185;
            box-shadow: 0 0 3px 2px rgba(228,121,17,.5);
        }
        .amz-search-btn {
            background: #232F3E; color: #fff;
            border: 1px solid #232F3E; border-left: 0;
            padding: 8px 18px;
            border-radius: 0 8px 8px 0;
            font-size: .9rem; font-weight: 500;
            cursor: pointer;
            white-space: nowrap;
        }
        .amz-search-btn:hover { background: #131A22; }

        /* Tabs */
        .amz-tabs {
            display: flex; gap: 24px;
            border-bottom: 1px solid #D5D9D9;
            margin-bottom: 14px;
        }
        .amz-tab {
            color: #007185;
            text-decoration: none;
            font-size: .98rem;
            padding: 10px 0;
            border-bottom: 3px solid transparent;
            font-weight: 400;
            transition: color .15s, border-color .15s;
        }
        .amz-tab:hover { color: #C7511F; text-decoration: underline; }
        .amz-tab.active {
            color: #0F1111; font-weight: 700;
            border-bottom-color: #C7511F;
            text-decoration: none;
        }
        .amz-tab.active:hover { text-decoration: none; color: #0F1111; }

        /* Filter row */
        .amz-filter-row {
            display: flex; align-items: center; gap: 8px;
            margin-bottom: 18px;
            font-size: .95rem; color: #0F1111;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }
        .amz-filter-count { font-weight: 500; }
        .amz-filter-select {
            border: 1px solid #888C8C;
            border-radius: 8px;
            background: #F0F2F2;
            padding: 4px 28px 4px 10px;
            font-size: .9rem;
            cursor: pointer;
            outline: 0;
            -webkit-appearance: none; -moz-appearance: none; appearance: none;
            background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%230F1111'><path d='M4 6l4 4 4-4'/></svg>");
            background-repeat: no-repeat;
            background-position: right 8px center;
            background-size: 12px;
        }
        .amz-filter-select:focus { border-color: #007185; }

        @media (max-width: 575px) {
            .amz-page-head { gap: 12px; }
            .amz-page-title { font-size: 1.4rem; }
            .amz-search-form { width: 100%; }
            .amz-tabs { gap: 16px; overflow-x: auto; white-space: nowrap; }
            .amz-tab { padding: 8px 0; font-size: .88rem; }
        }

        /* Amazon-style order cards */
        .amz-orders { display: flex; flex-direction: column; gap: 18px; }
        .amz-order-card {
            background: #fff;
            border: 1px solid #D5D9D9;
            border-radius: 8px;
            overflow: hidden;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            color: #0F1111;
        }
        .amz-order-head {
            display: grid;
            grid-template-columns: 1.4fr 1fr 1.4fr 1.6fr;
            gap: 16px;
            background: #F0F2F2;
            padding: 14px 22px;
            border-bottom: 1px solid #D5D9D9;
            align-items: start;
        }
        .amz-meta-block .amz-label {
            font-size: 0.72rem;
            font-weight: 600;
            color: #565959;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-bottom: 2px;
        }
        .amz-meta-block .amz-value {
            font-size: 0.92rem;
            font-weight: 400;
            color: #0F1111;
        }
        .amz-meta-right { text-align: right; }
        .amz-meta-right .amz-label { color: #565959; font-weight: 400; text-transform: none; letter-spacing: 0; font-size: 0.82rem; }
        .amz-meta-actions a {
            color: #007185;
            font-size: 0.85rem;
            text-decoration: none;
        }
        .amz-meta-actions a:hover { color: #C7511F; text-decoration: underline; }
        .amz-meta-actions .amz-sep { color: #D5D9D9; margin: 0 6px; }

        .amz-order-body {
            display: grid;
            grid-template-columns: 1fr 220px;
            gap: 22px;
            padding: 18px 22px;
        }
        .amz-status-title {
            font-size: 1.4rem;
            font-weight: 500;
            line-height: 1.2;
            margin-bottom: 4px;
        }

        /* ── Order progress timeline (4-step) ───────────────────── */
        .amz-progress {
            display: flex;
            align-items: flex-start;
            margin: 18px 0 22px;
            padding: 0 6px;
        }
        .amz-progress-step {
            display: flex; flex-direction: column;
            align-items: center; gap: 8px;
            min-width: 0;
        }
        .amz-progress-dot {
            width: 32px; height: 32px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            background: #FFFFFF;
            border: 2px solid #D5D9D9;
            color: #8A8A8A;
            transition: background .2s, border-color .2s, color .2s;
            position: relative; z-index: 1;
        }
        .amz-progress-label {
            font-size: .78rem;
            color: #8A8A8A;
            text-align: center;
            font-weight: 500;
            line-height: 1.2;
            max-width: 90px;
            transition: color .2s;
        }
        .amz-progress-time {
            font-size: .68rem;
            color: #565959;
            text-align: center;
            font-weight: 500;
            line-height: 1.2;
            margin-top: 2px;
            max-width: 100px;
            white-space: nowrap;
        }
        .amz-progress-time-muted {
            color: #B7BABA;
        }
        .amz-progress-step.current .amz-progress-time,
        .amz-progress-step.done .amz-progress-time {
            color: #067D62;
        }
        .amz-progress-failed-time {
            font-weight: 500;
            opacity: .8;
        }
        .amz-progress-line {
            flex: 1 1 0;
            height: 3px;
            background: #E3E6E6;
            margin: 14px 6px 0;
            border-radius: 2px;
            min-width: 16px;
            transition: background .2s;
        }
        .amz-progress-step.done .amz-progress-dot,
        .amz-progress-step.current .amz-progress-dot {
            background: #067D62;
            border-color: #067D62;
            color: #FFFFFF;
        }
        .amz-progress-step.done .amz-progress-label,
        .amz-progress-step.current .amz-progress-label {
            color: #0F1111;
        }
        .amz-progress-step.current .amz-progress-dot {
            box-shadow: 0 0 0 4px rgba(6, 125, 98, .14);
        }
        .amz-progress-step.current .amz-progress-label {
            font-weight: 700;
            color: #067D62;
        }
        .amz-progress-line.done {
            background: #067D62;
        }

        /* Cancelled / Returned / Refunded — non-progressive states */
        .amz-progress-failed {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(177, 39, 4, .08);
            color: #B12704;
            border: 1px solid rgba(177, 39, 4, .25);
            padding: 8px 14px;
            border-radius: 8px;
            font-weight: 600;
            font-size: .88rem;
            margin: 14px 0 18px;
        }
        .amz-status-sub {
            color: #0F1111;
            font-size: 0.92rem;
            margin-bottom: 14px;
        }

        .amz-items { display: flex; flex-direction: column; gap: 14px; }
        .amz-item { display: flex; gap: 14px; align-items: flex-start; }
        .amz-item-img {
            width: 76px;
            height: 76px;
            object-fit: cover;
            border-radius: 4px;
            background: #F5F5F5;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .amz-item-img-ph { background: #F5F5F5; }
        .amz-item-info { flex: 1; min-width: 0; }
        .amz-item-name {
            color: #007185;
            font-size: 0.95rem;
            font-weight: 400;
            line-height: 1.3;
            margin-bottom: 4px;
        }
        .amz-item-meta { color: #565959; font-size: 0.85rem; margin-bottom: 8px; }
        .amz-item-link {
            display: inline-block;
            padding: 6px 14px;
            border: 1px solid #D5D9D9;
            border-radius: 8px;
            background: #F0F2F2;
            color: #0F1111;
            text-decoration: none;
            font-size: 0.82rem;
        }
        .amz-item-link:hover { background: #E3E6E6; color: #0F1111; }
        .amz-more a { color: #007185; font-size: 0.85rem; text-decoration: none; }
        .amz-more a:hover { color: #C7511F; text-decoration: underline; }

        .amz-actions { display: flex; flex-direction: column; gap: 8px; }
        .amz-btn {
            display: block;
            text-align: center;
            padding: 8px 14px;
            border: 1px solid #D5D9D9;
            border-radius: 100px;
            background: linear-gradient(180deg, #F7F8F8 0%, #E7E9EC 100%);
            color: #0F1111;
            text-decoration: none;
            font-size: 0.82rem;
            font-weight: 400;
            transition: background 0.15s, border-color 0.15s;
        }
        .amz-btn:hover {
            background: linear-gradient(180deg, #E7E9EC 0%, #D6D9DC 100%);
            border-color: #B7BABA;
            color: #0F1111;
        }
        .amz-btn-primary {
            background: linear-gradient(180deg, #FFD814 0%, #F7CA00 100%);
            border-color: #FCD200;
            font-weight: 500;
        }
        .amz-btn-primary:hover {
            background: linear-gradient(180deg, #F7CA00 0%, #F2C200 100%);
            border-color: #F2C200;
        }

        .amz-empty {
            text-align: center;
            padding: 60px 24px;
            border: 1px solid #D5D9D9;
            border-radius: 8px;
            background: #fff;
        }
        .amz-empty h3 { font-size: 1.2rem; font-weight: 500; margin: 12px 0 4px; }
        .amz-empty a { color: #007185; text-decoration: none; }
        .amz-empty a:hover { color: #C7511F; text-decoration: underline; }

        @media (max-width: 991px) {
            .amz-order-head { grid-template-columns: 1fr 1fr; gap: 12px; }
            .amz-meta-right { text-align: left; grid-column: 1 / -1; }
            .amz-order-body { grid-template-columns: 1fr; }
            .amz-actions { flex-direction: row; flex-wrap: wrap; }
            .amz-actions .amz-btn { flex: 1 1 calc(50% - 4px); }
        }
        @media (max-width: 575px) {
            .amz-order-head { grid-template-columns: 1fr; padding: 12px 16px; }
            .amz-order-body { padding: 14px 16px; }
            .amz-status-title { font-size: 1.2rem; }
            .amz-progress { margin: 12px 0 16px; padding: 0; }
            .amz-progress-dot { width: 26px; height: 26px; }
            .amz-progress-dot i { width: 12px !important; height: 12px !important; }
            .amz-progress-label { font-size: .68rem; max-width: 64px; }
            .amz-progress-time { font-size: .58rem; max-width: 70px; }
            .amz-progress-line { margin-top: 11px; min-width: 8px; }
            .amz-actions .amz-btn { flex: 1 1 100%; }
        }
    </style>

    <script>
    (function () {

      // Only fires when arriving from checkout after payment
      var cameFromCheckout = document.referrer &&
                             document.referrer.indexOf('buyer/checkout') !== -1;
      var alreadyFired     = sessionStorage.getItem('km_purchase_fired');

      if (!cameFromCheckout || alreadyFired) return;

      sessionStorage.setItem('km_purchase_fired', '1');

      window.addEventListener('load', function () {

        var firstCard = document.querySelector('.amz-order-card');
        if (!firstCard) return;

        // Order ID
        var orderLabelEl = firstCard.querySelector('.amz-meta-right .amz-label');
        var orderId = orderLabelEl ? orderLabelEl.textContent.replace('Order #', '').trim() : '';

        // Order Total
        var metaBlocks = firstCard.querySelectorAll('.amz-meta-block');
        var total = 0;
        if (metaBlocks[1]) {
          var totalEl = metaBlocks[1].querySelector('.amz-value');
          if (totalEl) total = parseFloat(totalEl.textContent.replace(/[^0-9.]/g, '')) || 0;
        }

        // Items — captures mango variety name
        var items = [];
        firstCard.querySelectorAll('.amz-item').forEach(function (row, i) {
          var nameEl = row.querySelector('.amz-item-name');
          var metaEl = row.querySelector('.amz-item-meta');
          var name   = nameEl ? nameEl.textContent.trim() : '';
          var qty    = 1;
          if (metaEl) {
            var qtyMatch = metaEl.textContent.match(/([\d.]+)/);
            if (qtyMatch) qty = parseFloat(qtyMatch[1]) || 1;
          }
          items.push({
            item_id:       'mango_' + name.toLowerCase().replace(/\s+/g, '_'),
            item_name:     name,
            item_category: 'Mango',
            quantity:      qty
          });
        });

        // GA4 Purchase
        gtag('event', 'purchase', {
          transaction_id: orderId,
          currency:       'INR',
          value:          total,
          items:          items
        });

        // Meta Pixel Purchase
        if (window.fbq) {
          fbq('track', 'Purchase', {
            value:        total,
            currency:     'INR',
            content_ids:  items.map(function(i){ return i.item_name; }),
            content_type: 'product',
            num_items:    items.length
          });
        }

        console.log('King Mango — purchase fired:', orderId, total, items);

      });

    })();
    </script>
    <?php
};

require __DIR__ . '/../../views/layout.php';
