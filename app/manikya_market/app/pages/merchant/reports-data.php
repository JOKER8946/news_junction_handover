<?php

declare(strict_types=1);

auth_require_role('merchant');

header('Content-Type: application/json');

$merchantId = auth_user_id();

// Parse filters (same defaults as reports.php)
$period = $_GET['period'] ?? 'month'; // day | month | year
$productId = isset($_GET['product_id']) && is_numeric($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
$from = null;
$to = null;

if ($period === 'day') {
    $d = $_GET['date'] ?? date('Y-m-d');
    $from = $d . ' 00:00:00';
    $to = $d . ' 23:59:59';
} elseif ($period === 'year') {
    $y = $_GET['year'] ?? date('Y');
    $from = $y . '-01-01 00:00:00';
    $to = $y . '-12-31 23:59:59';
} else {
    $m = $_GET['month'] ?? date('Y-m');
    $first = $m . '-01';
    $t = date('Y-m-t', strtotime($first));
    $from = $first . ' 00:00:00';
    $to = $t . ' 23:59:59';
}

$whereOrderStatus = "o.status IN ('paid','packed','ready_to_pick','shipped','in_transit','delivered')";
$productFilterSqlInner = '';
$productFilterSqlOuter = '';
$params = ['from' => $from, 'to' => $to, 'mid' => $merchantId];
if ($productId > 0) {
    $productFilterSqlInner = ' AND oi.product_id = :product_id';
    $productFilterSqlOuter = ' AND p.id = :product_id';
    $params['product_id'] = $productId;
}

// Products with sales > 0 only for the pie chart — SCOPED to this seller's
// own products + their own orders. Without these merchant_id filters, any
// seller could read global sales aggregates across the whole platform.
$topProducts = db_fetch_all($db, "
    SELECT p.id AS product_id, p.name AS product_name, s.qty_sold
    FROM products p
    JOIN (
        SELECT oi.product_id, SUM(oi.qty_kg) AS qty_sold
        FROM order_items oi
        JOIN orders o ON o.id = oi.order_id
        WHERE o.created_at BETWEEN :from AND :to
          AND {$whereOrderStatus}
          AND o.merchant_id = :mid
        GROUP BY oi.product_id
    ) s ON s.product_id = p.id
    WHERE p.is_active = 1
      AND p.merchant_id = :mid
      {$productFilterSqlOuter}
    ORDER BY s.qty_sold DESC
", $params) ?: [];

$timeseries = db_fetch_all($db, "
    SELECT DATE(o.created_at) AS d, SUM(oi.qty_kg) AS qty
    FROM order_items oi
    JOIN orders o ON o.id = oi.order_id
    WHERE o.created_at BETWEEN :from AND :to
      AND {$whereOrderStatus}
      AND o.merchant_id = :mid
      " . $productFilterSqlInner . "
    GROUP BY DATE(o.created_at)
    ORDER BY DATE(o.created_at) ASC
", $params) ?: [];

$resp = [
    'chartLabels' => array_map(fn($r) => $r['product_name'], $topProducts),
    'chartDataQty' => array_map(fn($r) => (float)$r['qty_sold'], $topProducts),
    'tsLabels' => array_map(fn($r) => $r['d'], $timeseries),
    'tsData' => array_map(fn($r) => (float)$r['qty'], $timeseries),
];

echo json_encode($resp);
