<?php

declare(strict_types=1);

auth_require_role('merchant');

$merchantId = (int)auth_user_id();

// Products for filter (this merchant only)
$products = db_fetch_all($db, 'SELECT id, name FROM products WHERE merchant_id = :mid ORDER BY name', ['mid' => $merchantId]) ?: [];

// Parse filters
$period = $_GET['period'] ?? 'month'; // day | month | year
$productId = isset($_GET['product_id']) && is_numeric($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
$from = null;
$to = null;
$periodLabel = '';

if ($period === 'day') {
    $d = $_GET['date'] ?? date('Y-m-d');
    $from = $d . ' 00:00:00';
    $to = $d . ' 23:59:59';
    $periodLabel = 'Day_' . $d;
} elseif ($period === 'year') {
    $y = $_GET['year'] ?? date('Y');
    $from = $y . '-01-01 00:00:00';
    $to = $y . '-12-31 23:59:59';
    $periodLabel = 'Year_' . $y;
} else {
    $m = $_GET['month'] ?? date('Y-m');
    $first = $m . '-01';
    $t = date('Y-m-t', strtotime($first));
    $from = $first . ' 00:00:00';
    $to = $t . ' 23:59:59';
    $periodLabel = 'Month_' . $m;
}

$whereOrderStatus = "o.status IN ('paid','packed','ready_to_pick','shipped','in_transit','delivered')";

$productFilterSql = '';
$params = ['from' => $from, 'to' => $to, 'mid' => $merchantId];
if ($productId > 0) {
    $productFilterSql = ' AND p.id = :product_id';
    $params['product_id'] = $productId;
}

// ── Logistics spend computation ─────────────────────────────────
// Get merchant's manual-logistics service charge config
$msvc = db_fetch_one($db, '
    SELECT logistics_service_charge, logistics_service_charge_type
    FROM merchant_profile
    WHERE merchant_user_id = :uid LIMIT 1
', ['uid' => $merchantId]) ?: [];
$svcAmount = (float)($msvc['logistics_service_charge'] ?? 0);
$svcType = (string)($msvc['logistics_service_charge_type'] ?? 'flat');

// All shipped orders in the period (this merchant only)
$shippedOrders = db_fetch_all($db, "
    SELECT o.id, o.subtotal_amount, o.shipping_amount, o.total_amount,
           o.delhivery_waybill, o.logistics_user_id,
           lp.company_name AS vendor_name
    FROM orders o
    LEFT JOIN logistics_profile lp ON lp.logistics_user_id = o.logistics_user_id
    WHERE o.merchant_id = :mid
      AND o.created_at BETWEEN :from AND :to
      AND o.status IN ('paid','packed','ready_to_pick','shipped','in_transit','delivered')
", ['from' => $from, 'to' => $to, 'mid' => $merchantId]) ?: [];

$logisticsByVendor = [];   // vendor_name => ['orders'=>n, 'spend'=>float]
$logisticsTotalSpend = 0.0;
$logisticsTotalOrders = 0;

foreach ($shippedOrders as $o) {
    $waybill = (string)($o['delhivery_waybill'] ?? '');
    $manualUser = (int)($o['logistics_user_id'] ?? 0);
    $vendor = null;
    $spend = 0.0;

    if ($waybill !== '') {
        $vendor = 'Delhivery';
        $spend = (float)$o['shipping_amount'];
    } elseif ($manualUser > 0) {
        $vendor = ((string)($o['vendor_name'] ?? '')) !== '' ? (string)$o['vendor_name'] : 'Manual Vendor';
        if ($svcType === 'percent') {
            $spend = round(((float)$o['subtotal_amount']) * ($svcAmount / 100), 2);
        } else {
            $spend = $svcAmount;
        }
    } else {
        continue; // no shipment recorded
    }

    if (!isset($logisticsByVendor[$vendor])) {
        $logisticsByVendor[$vendor] = ['orders' => 0, 'spend' => 0.0];
    }
    $logisticsByVendor[$vendor]['orders']++;
    $logisticsByVendor[$vendor]['spend'] += $spend;
    $logisticsTotalSpend += $spend;
    $logisticsTotalOrders++;
}

// Sort vendors by spend desc
uasort($logisticsByVendor, fn($a, $b) => $b['spend'] <=> $a['spend']);

// All products with sales (zero rows for products with no sales in the period)
$allProducts = db_fetch_all($db, "
    SELECT p.id, p.name AS product_name,
           COALESCE(s.qty_sold, 0) AS qty_sold,
           COALESCE(s.orders_count, 0) AS orders_count,
           COALESCE(s.revenue, 0) AS revenue
    FROM products p
    LEFT JOIN (
        SELECT oi.product_id,
               SUM(oi.qty_kg) AS qty_sold,
               COUNT(DISTINCT oi.order_id) AS orders_count,
               SUM(oi.line_total) AS revenue
        FROM order_items oi
        JOIN orders o ON o.id = oi.order_id
        WHERE o.merchant_id = :mid
          AND o.created_at BETWEEN :from AND :to
          AND {$whereOrderStatus}
        GROUP BY oi.product_id
    ) s ON s.product_id = p.id
    WHERE p.is_active = 1 AND p.merchant_id = :mid_outer
      {$productFilterSql}
    ORDER BY qty_sold DESC, p.name ASC
", $params + ['mid_outer' => $merchantId]) ?: [];

// CSV export — must run BEFORE any output
if (($_GET['export'] ?? '') === 'csv') {
    $filename = 'KingMango_Report_' . $periodLabel . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $out = fopen('php://output', 'w');
    fputcsv($out, ['King Mango — Sales Report']);
    fputcsv($out, ['Period', $period]);
    fputcsv($out, ['From', $from]);
    fputcsv($out, ['To', $to]);
    fputcsv($out, []);
    fputcsv($out, ['Product', 'Quantity Sold (kg)', 'Orders', 'Revenue (INR)']);

    $totalQty = 0.0; $totalOrders = 0; $totalRev = 0.0;
    foreach ($allProducts as $row) {
        $qty = (float)$row['qty_sold'];
        $orders = (int)$row['orders_count'];
        $rev = (float)$row['revenue'];
        fputcsv($out, [
            $row['product_name'],
            number_format($qty, 2, '.', ''),
            $orders,
            number_format($rev, 2, '.', ''),
        ]);
        $totalQty += $qty;
        $totalOrders += $orders;
        $totalRev += $rev;
    }
    fputcsv($out, []);
    fputcsv($out, ['Total', number_format($totalQty, 2, '.', ''), $totalOrders, number_format($totalRev, 2, '.', '')]);

    fputcsv($out, []);
    fputcsv($out, ['Logistics Spend by Vendor']);
    fputcsv($out, ['Vendor', 'Orders Shipped', 'Spend (INR)']);
    foreach ($logisticsByVendor as $vendor => $info) {
        fputcsv($out, [$vendor, (int)$info['orders'], number_format((float)$info['spend'], 2, '.', '')]);
    }
    fputcsv($out, ['Total Logistics', $logisticsTotalOrders, number_format($logisticsTotalSpend, 2, '.', '')]);

    $netRev = $totalRev - $logisticsTotalSpend;
    fputcsv($out, []);
    fputcsv($out, ['Summary']);
    fputcsv($out, ['Revenue', number_format($totalRev, 2, '.', '')]);
    fputcsv($out, ['Logistics Spend', number_format($logisticsTotalSpend, 2, '.', '')]);
    fputcsv($out, ['Net (Revenue - Logistics)', number_format($netRev, 2, '.', '')]);

    fclose($out);
    exit;
}

$title = 'Reports & Analysis';

// Timeseries (this merchant only)
$timeseries = db_fetch_all($db, "
    SELECT DATE(o.created_at) AS d, SUM(oi.qty_kg) AS qty
    FROM order_items oi
    JOIN orders o ON o.id = oi.order_id
    JOIN products p ON p.id = oi.product_id
    WHERE o.merchant_id = :mid
      AND o.created_at BETWEEN :from AND :to
      AND {$whereOrderStatus}
      " . $productFilterSql . "
    GROUP BY DATE(o.created_at)
    ORDER BY DATE(o.created_at) ASC
", $params) ?: [];

// Build pie data — only products with sales > 0 for chart clarity
$productsWithSales = array_filter($allProducts, fn($r) => (float)$r['qty_sold'] > 0);
$chartLabels = array_map(fn($r) => $r['product_name'], array_values($productsWithSales));
$chartDataQty = array_map(fn($r) => (float)$r['qty_sold'], array_values($productsWithSales));
$tsLabels = array_map(fn($r) => $r['d'], $timeseries);
$tsData = array_map(fn($r) => (float)$r['qty'], $timeseries);

$content = function() use ($products, $period, $productId, $from, $to, $allProducts, $chartLabels, $chartDataQty, $tsLabels, $tsData, $logisticsByVendor, $logisticsTotalSpend, $logisticsTotalOrders, $svcAmount, $svcType) {
    $qstr = http_build_query(array_filter([
        'p' => 'merchant/reports',
        'product_id' => $productId ?: null,
    ]));
    $today = date('Y-m-d');
    $thisMonth = date('Y-m');
    $thisYear = date('Y');
    $exportDay   = "?{$qstr}&export=csv&period=day&date={$today}";
    $exportMonth = "?{$qstr}&export=csv&period=month&month={$thisMonth}";
    $exportYear  = "?{$qstr}&export=csv&period=year&year={$thisYear}";
?>
    <div class="container py-4">
        <div class="d-flex align-items-center justify-content-between mb-4 gap-2 flex-wrap">
            <div class="d-flex align-items-center gap-2">
                <a href="?p=merchant/dashboard" class="btn btn-sm btn-outline-secondary">
                    <i data-lucide="arrow-left" class="mm-icon"></i> <span class="d-none d-sm-inline">Back</span>
                </a>
                <h1 class="h5 mb-0">Reports & Analysis</h1>
            </div>
            <div class="dropdown">
                <button class="btn btn-mm btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i data-lucide="download" class="mm-icon"></i> Download Report
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="<?= e($exportDay) ?>"><i data-lucide="calendar" class="mm-icon" style="width:14px;height:14px;"></i> Daily (<?= date('d M Y') ?>)</a></li>
                    <li><a class="dropdown-item" href="<?= e($exportMonth) ?>"><i data-lucide="calendar-days" class="mm-icon" style="width:14px;height:14px;"></i> Monthly (<?= date('M Y') ?>)</a></li>
                    <li><a class="dropdown-item" href="<?= e($exportYear) ?>"><i data-lucide="calendar-range" class="mm-icon" style="width:14px;height:14px;"></i> Yearly (<?= date('Y') ?>)</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="?<?= e($qstr) ?>&export=csv&period=<?= e($period) ?>&date=<?= e(substr($from,0,10)) ?>&month=<?= e(substr($from,0,7)) ?>&year=<?= e(substr($from,0,4)) ?>"><i data-lucide="download" class="mm-icon" style="width:14px;height:14px;"></i> Current selection</a></li>
                </ul>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-12 col-lg-4">
                <div class="bg-white border rounded-4 p-3 mm-card">
                    <form method="get">
                        <input type="hidden" name="p" value="merchant/reports">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Period</label>
                            <select name="period" id="periodSelect" class="form-select form-select-sm">
                                <option value="day" <?= $period === 'day' ? 'selected' : '' ?>>Day</option>
                                <option value="month" <?= $period === 'month' ? 'selected' : '' ?>>Month</option>
                                <option value="year" <?= $period === 'year' ? 'selected' : '' ?>>Year</option>
                            </select>
                        </div>

                        <div class="mb-3" id="inputDay" style="display: <?= $period === 'day' ? 'block' : 'none' ?>;">
                            <label class="form-label small fw-semibold">Date</label>
                            <input type="date" name="date" class="form-control form-control-sm" value="<?= htmlspecialchars(substr($from,0,10)) ?>">
                        </div>

                        <div class="mb-3" id="inputMonth" style="display: <?= $period === 'month' ? 'block' : 'none' ?>;">
                            <label class="form-label small fw-semibold">Month</label>
                            <input type="month" name="month" class="form-control form-control-sm" value="<?= htmlspecialchars(substr($from,0,7)) ?>">
                        </div>

                        <div class="mb-3" id="inputYear" style="display: <?= $period === 'year' ? 'block' : 'none' ?>;">
                            <label class="form-label small fw-semibold">Year</label>
                            <input type="number" name="year" class="form-control form-control-sm" min="2000" max="2100" value="<?= htmlspecialchars(substr($from,0,4)) ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Product (optional)</label>
                            <select name="product_id" class="form-select form-select-sm">
                                <option value="0">All products</option>
                                <?php foreach ($products as $p): ?>
                                    <option value="<?= (int)$p['id'] ?>" <?= $p['id'] == $productId ? 'selected' : '' ?>><?= e((string)$p['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="d-grid">
                            <button class="btn btn-mm btn-sm">Generate Report</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-12 col-lg-8">
                <div class="bg-white border rounded-4 p-3 mm-card mb-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="fw-semibold small">Products (by quantity)</div>
                        <div class="text-muted small"><?= count($chartLabels) ?> with sales</div>
                    </div>
                    <div class="chart-container" style="height:280px;">
                        <?php if (empty($chartLabels)): ?>
                            <div class="d-flex align-items-center justify-content-center h-100 text-muted small">
                                No sales recorded for this period.
                            </div>
                        <?php else: ?>
                            <canvas id="pieChart"></canvas>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="bg-white border rounded-4 p-3 mm-card mb-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="fw-semibold small">Quantity Over Time</div>
                    </div>
                    <div class="chart-container" style="height:200px;">
                        <canvas id="lineChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Logistics Spend -->
        <div class="bg-white border rounded-4 p-3 mm-card mt-3">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div class="fw-semibold d-flex align-items-center gap-2">
                    <i data-lucide="truck" class="mm-icon"></i> Logistics Spend
                </div>
                <span class="text-muted small">
                    <?= $logisticsTotalOrders ?> shipped orders · Total <strong>₹<?= number_format($logisticsTotalSpend, 2) ?></strong>
                </span>
            </div>
            <?php if ($logisticsTotalOrders === 0): ?>
                <div class="text-muted small">No shipped orders in this period.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr class="text-muted small">
                                <th>Vendor</th>
                                <th class="text-end">Orders Shipped</th>
                                <th class="text-end">Avg per Order (₹)</th>
                                <th class="text-end">Spend (₹)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logisticsByVendor as $vendor => $info):
                                $avg = $info['orders'] > 0 ? $info['spend'] / $info['orders'] : 0;
                            ?>
                                <tr>
                                    <td><?= e((string)$vendor) ?></td>
                                    <td class="text-end"><?= (int)$info['orders'] ?></td>
                                    <td class="text-end">₹<?= number_format($avg, 2) ?></td>
                                    <td class="text-end fw-semibold">₹<?= number_format((float)$info['spend'], 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="fw-bold border-top">
                                <td>Total</td>
                                <td class="text-end"><?= $logisticsTotalOrders ?></td>
                                <td class="text-end"></td>
                                <td class="text-end">₹<?= number_format($logisticsTotalSpend, 2) ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <div class="form-text mt-2">
                    Delhivery spend = order shipping amount.
                    Manual-vendor spend = <?= $svcType === 'percent' ? number_format($svcAmount, 2) . '% of order subtotal' : '₹' . number_format($svcAmount, 2) . ' flat per order' ?>
                    (set in Logistics → Onboard).
                </div>
            <?php endif; ?>
        </div>

        <!-- All products breakdown -->
        <div class="bg-white border rounded-4 p-3 mm-card mt-3">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div class="fw-semibold">All Products</div>
                <span class="text-muted small"><?= count($allProducts) ?> products</span>
            </div>
            <?php if (empty($allProducts)): ?>
                <div class="text-muted small">No active products.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr class="text-muted small">
                                <th>#</th>
                                <th>Product</th>
                                <th class="text-end">Qty Sold (kg)</th>
                                <th class="text-end">Orders</th>
                                <th class="text-end">Revenue (₹)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $i = 0; $tQty = 0; $tOrders = 0; $tRev = 0;
                            foreach ($allProducts as $row):
                                $i++;
                                $qty = (float)$row['qty_sold'];
                                $orders = (int)$row['orders_count'];
                                $rev = (float)$row['revenue'];
                                $tQty += $qty; $tOrders += $orders; $tRev += $rev;
                            ?>
                                <tr class="<?= $qty > 0 ? '' : 'text-muted' ?>">
                                    <td><?= $i ?></td>
                                    <td><?= e((string)$row['product_name']) ?></td>
                                    <td class="text-end"><?= number_format($qty, 2) ?></td>
                                    <td class="text-end"><?= $orders ?></td>
                                    <td class="text-end">₹<?= number_format($rev, 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="fw-bold border-top">
                                <td></td>
                                <td>Total</td>
                                <td class="text-end"><?= number_format($tQty, 2) ?></td>
                                <td class="text-end"><?= $tOrders ?></td>
                                <td class="text-end">₹<?= number_format($tRev, 2) ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.getElementById('periodSelect').addEventListener('change', function(e){
            var v = this.value;
            document.getElementById('inputDay').style.display = v === 'day' ? 'block' : 'none';
            document.getElementById('inputMonth').style.display = v === 'month' ? 'block' : 'none';
            document.getElementById('inputYear').style.display = v === 'year' ? 'block' : 'none';
        });

        const initialPieLabels = <?= json_encode($chartLabels) ?>;
        const initialPieData = <?= json_encode($chartDataQty) ?>;
        const initialLineLabels = <?= json_encode($tsLabels) ?>;
        const initialLineData = <?= json_encode($tsData) ?>;

        let pieChart = null;
        const pieEl = document.getElementById('pieChart');
        if (pieEl) {
            pieChart = new Chart(pieEl.getContext('2d'), {
                type: 'pie',
                data: {
                    labels: initialPieLabels,
                    datasets: [{ data: initialPieData, backgroundColor: ['#4caf50','#03a9f4','#ffc107','#ff5722','#9c27b0','#00bcd4','#8bc34a','#cddc39','#607d8b','#795548','#e91e63','#3f51b5','#009688','#673ab7','#ff9800'] }]
                },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'top' } } }
            });
        }

        const ctxLine = document.getElementById('lineChart').getContext('2d');
        const lineChart = new Chart(ctxLine, {
            type: 'line',
            data: {
                labels: initialLineLabels,
                datasets: [{ label: 'Quantity (kg)', data: initialLineData, borderColor: '#3b82f6', backgroundColor: 'rgba(59,130,246,0.08)', tension: 0.3 }]
            },
            options: { responsive: true, maintainAspectRatio: false }
        });

        async function fetchReportData() {
            try {
                const params = new URLSearchParams(window.location.search);
                params.set('p', 'merchant/reports-data');
                const resp = await fetch('/index.php?' + params.toString(), { credentials: 'same-origin' });
                if (!resp.ok) return;
                const j = await resp.json();
                if (pieChart) {
                    pieChart.data.labels = j.chartLabels || [];
                    pieChart.data.datasets[0].data = j.chartDataQty || [];
                    pieChart.update();
                }
                lineChart.data.labels = j.tsLabels || [];
                lineChart.data.datasets[0].data = j.tsData || [];
                lineChart.update();
            } catch (e) {
                console.error('Report fetch error', e);
            }
        }

        fetchReportData();
        setInterval(fetchReportData, 30000);
    </script>
    <style>
        .chart-container { position: relative; width: 100%; }
        .chart-container canvas { width: 100% !important; height: 100% !important; }
    </style>
<?php
};

require __DIR__ . '/../../views/layout.php';
