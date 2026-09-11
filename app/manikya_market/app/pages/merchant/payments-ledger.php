<?php

declare(strict_types=1);

auth_require_role('merchant');

$merchantId = auth_user_id();

$period = $_GET['period'] ?? 'all';
$allowedPeriods = ['all', 'today', 'month', 'year', 'custom'];
if (!in_array($period, $allowedPeriods, true)) $period = 'all';

$from = null; $to = null;
if ($period === 'today') {
    $from = date('Y-m-d') . ' 00:00:00';
    $to = date('Y-m-d') . ' 23:59:59';
} elseif ($period === 'month') {
    $from = date('Y-m-01') . ' 00:00:00';
    $to = date('Y-m-t') . ' 23:59:59';
} elseif ($period === 'year') {
    $from = date('Y-01-01') . ' 00:00:00';
    $to = date('Y-12-31') . ' 23:59:59';
} elseif ($period === 'custom') {
    $from = ($_GET['from'] ?? date('Y-m-d')) . ' 00:00:00';
    $to = ($_GET['to'] ?? date('Y-m-d')) . ' 23:59:59';
}

$status = $_GET['status'] ?? 'all';
if (!in_array($status, ['all', 'paid', 'initiated', 'failed'], true)) $status = 'all';

$where = ["p.provider = 'razorpay'", "o.merchant_id = :mid"];
$params = ['mid' => (int)$merchantId];
if ($from && $to) {
    $where[] = 'p.created_at BETWEEN :from AND :to';
    $params['from'] = $from;
    $params['to'] = $to;
}
if ($status !== 'all') {
    $where[] = 'p.status = :status';
    $params['status'] = $status;
}
$whereSql = implode(' AND ', $where);

$payments = db_fetch_all($db, "
    SELECT p.id, p.order_id, p.provider, p.status, p.amount, p.razorpay_order_id,
           p.razorpay_payment_id, p.created_at,
           o.order_no, o.status AS order_status, o.total_amount,
           o.commission_amount AS order_commission,
           o.merchant_payable  AS order_payable,
           u.full_name AS buyer_name, u.phone AS buyer_phone, u.email AS buyer_email,
           COALESCE(items.line_commission, 0) AS line_commission,
           COALESCE(items.line_payable, 0)    AS line_payable
    FROM payments p
    JOIN orders o ON o.id = p.order_id
    JOIN users u ON u.id = o.buyer_id
    LEFT JOIN (
        SELECT order_id,
               SUM(commission_amount) AS line_commission,
               SUM(seller_payable)    AS line_payable
        FROM order_items
        GROUP BY order_id
    ) items ON items.order_id = o.id
    WHERE $whereSql
    ORDER BY p.created_at DESC
", $params) ?: [];

// KPI computation — scoped to this merchant's orders only.
$kpiBase = "SELECT COUNT(*) AS c, COALESCE(SUM(p.amount), 0) AS s
            FROM payments p
            JOIN orders o ON o.id = p.order_id
            WHERE p.provider = 'razorpay' AND p.status = 'paid' AND o.merchant_id = :mid";
$kpiAll   = db_fetch_one($db, $kpiBase, ['mid' => (int)$merchantId]) ?: [];
$kpiToday = db_fetch_one($db, $kpiBase . " AND DATE(p.created_at) = CURDATE()", ['mid' => (int)$merchantId]) ?: [];
$kpiMonth = db_fetch_one($db, $kpiBase . " AND YEAR(p.created_at) = YEAR(CURDATE()) AND MONTH(p.created_at) = MONTH(CURDATE())", ['mid' => (int)$merchantId]) ?: [];
$kpiYear  = db_fetch_one($db, $kpiBase . " AND YEAR(p.created_at) = YEAR(CURDATE())", ['mid' => (int)$merchantId]) ?: [];

// Visible filtered total
$visibleTotal = 0.0;
$visibleCount = 0;
foreach ($payments as $p) {
    if ($p['status'] === 'paid') {
        $visibleTotal += (float)$p['amount'];
        $visibleCount++;
    }
}

// CSV export
if (($_GET['export'] ?? '') === 'csv') {
    $filename = 'KingMango_Payments_' . $period . '_' . date('Ymd_His') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['King Mango — Inward Payments Ledger']);
    fputcsv($out, ['Period', $period]);
    if ($from && $to) { fputcsv($out, ['From', $from]); fputcsv($out, ['To', $to]); }
    fputcsv($out, []);
    fputcsv($out, ['Date', 'Order #', 'Buyer', 'Phone', 'Amount (INR)', 'Commission (INR)', 'Net Payable (INR)', 'Razorpay Payment ID', 'Razorpay Order ID', 'Status', 'Order Status']);
    foreach ($payments as $p) {
        $comm = (float)($p['line_commission'] ?? 0);
        $pay  = (float)($p['line_payable'] ?? 0);
        if ($comm <= 0 && (float)($p['order_commission'] ?? 0) > 0) $comm = (float)$p['order_commission'];
        if ($pay  <= 0 && (float)($p['order_payable'] ?? 0)    > 0) $pay  = (float)$p['order_payable'];
        fputcsv($out, [
            $p['created_at'],
            $p['order_no'],
            $p['buyer_name'],
            $p['buyer_phone'],
            number_format((float)$p['amount'], 2, '.', ''),
            number_format($comm, 2, '.', ''),
            number_format($pay, 2, '.', ''),
            $p['razorpay_payment_id'],
            $p['razorpay_order_id'],
            $p['status'],
            $p['order_status'],
        ]);
    }
    fputcsv($out, []);
    fputcsv($out, ['Visible total (paid only)', '', '', '', number_format($visibleTotal, 2, '.', '')]);
    fclose($out);
    exit;
}

$title = 'Payment Ledger';

$content = function () use ($payments, $period, $status, $from, $to, $kpiAll, $kpiToday, $kpiMonth, $kpiYear, $visibleTotal, $visibleCount) {
    $periodLabels = ['all' => 'All time', 'today' => 'Today', 'month' => 'This month', 'year' => 'This year', 'custom' => 'Custom'];

    $statusPill = function (string $s): string {
        switch ($s) {
            case 'paid': return 'km-pill--delivered';
            case 'initiated': return 'km-pill--paid';
            case 'failed': return 'km-pill--cancelled';
        }
        return 'km-pill--neutral';
    };

    $exportQs = http_build_query(array_filter([
        'p' => 'merchant/payments-ledger',
        'export' => 'csv',
        'period' => $period,
        'status' => $status,
        'from' => $period === 'custom' && $from ? substr($from, 0, 10) : null,
        'to' => $period === 'custom' && $to ? substr($to, 0, 10) : null,
    ]));
?>
  <style>
    .km-admin .km-ledger-table { width: 100%; border-collapse: collapse; font-size: .88rem; background: #fff; }
    .km-admin .km-ledger-table th,
    .km-admin .km-ledger-table td { padding: 11px 14px; border-bottom: 1px solid var(--km-line); vertical-align: middle; }
    .km-admin .km-ledger-table th {
      background: #F9FAFB; text-align: left;
      font-size: .7rem; font-weight: 600; letter-spacing: .04em; text-transform: uppercase;
      color: var(--km-muted); white-space: nowrap;
      position: sticky; top: 0; z-index: 1;
      box-shadow: inset 0 -1px 0 var(--km-line);
    }
    .km-admin .km-ledger-table tbody tr:last-child td { border-bottom: 0; }
    .km-admin .km-ledger-table tbody tr:hover { background: #F9FAFB; }
    .km-admin .km-ledger-scroll { max-height: calc(100vh - 380px); min-height: 320px; overflow-y: auto; overflow-x: auto; }
    .km-admin .km-mono { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size: .82rem; }
    .km-admin .km-amount { font-weight: 600; font-variant-numeric: tabular-nums; }
    .km-admin .km-col-narrow { width: 1%; white-space: nowrap; }
  </style>

  <div class="km-admin">
    <div class="container py-4">

      <div class="km-page-head">
        <div>
          <h1>Payment Ledger</h1>
          <div class="km-sub">Inward payments received from buyers via Razorpay</div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
          <a class="km-btn" href="?p=merchant/dashboard">
            <i data-lucide="arrow-left" style="width:16px;height:16px;"></i> Back
          </a>
          <a class="km-btn km-btn-primary" href="?<?= e($exportQs) ?>">
            <i data-lucide="download" style="width:16px;height:16px;"></i> Export CSV
          </a>
        </div>
      </div>

      <!-- KPIs -->
      <div class="row g-3 mb-3">
        <div class="col-6 col-md-3">
          <div class="km-stat km-stat--blue" style="text-align:left; padding: 14px 18px;">
            <div class="km-stat-label" style="text-transform:uppercase; letter-spacing:.06em; font-size:.7rem;">Total Received</div>
            <div class="km-stat-num" style="font-size:1.75rem;">₹<?= number_format((float)$kpiAll['s'], 2) ?></div>
            <div class="km-stat-label" style="margin-top:2px;"><?= (int)$kpiAll['c'] ?> payments</div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="km-stat km-stat--green" style="text-align:left; padding: 14px 18px;">
            <div class="km-stat-label" style="text-transform:uppercase; letter-spacing:.06em; font-size:.7rem;">Today</div>
            <div class="km-stat-num" style="font-size:1.75rem;">₹<?= number_format((float)$kpiToday['s'], 2) ?></div>
            <div class="km-stat-label" style="margin-top:2px;"><?= (int)$kpiToday['c'] ?> payments</div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="km-stat km-stat--orange" style="text-align:left; padding: 14px 18px;">
            <div class="km-stat-label" style="text-transform:uppercase; letter-spacing:.06em; font-size:.7rem;">This Month</div>
            <div class="km-stat-num" style="font-size:1.75rem;">₹<?= number_format((float)$kpiMonth['s'], 2) ?></div>
            <div class="km-stat-label" style="margin-top:2px;"><?= (int)$kpiMonth['c'] ?> payments</div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="km-stat km-stat--purple" style="text-align:left; padding: 14px 18px;">
            <div class="km-stat-label" style="text-transform:uppercase; letter-spacing:.06em; font-size:.7rem;">This Year</div>
            <div class="km-stat-num" style="font-size:1.75rem;">₹<?= number_format((float)$kpiYear['s'], 2) ?></div>
            <div class="km-stat-label" style="margin-top:2px;"><?= (int)$kpiYear['c'] ?> payments</div>
          </div>
        </div>
      </div>

      <!-- Filters -->
      <div class="km-card km-card-pad mb-3">
        <form method="get" class="row g-2 align-items-end">
          <input type="hidden" name="p" value="merchant/payments-ledger">
          <div class="col-md-3">
            <label class="form-label small fw-semibold mb-1">Period</label>
            <select class="form-select form-select-sm" name="period" onchange="this.form.submit()">
              <?php foreach ($periodLabels as $k => $v): ?>
                <option value="<?= $k ?>" <?= $period === $k ? 'selected' : '' ?>><?= $v ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <?php if ($period === 'custom'): ?>
            <div class="col-md-3">
              <label class="form-label small fw-semibold mb-1">From</label>
              <input type="date" class="form-control form-control-sm" name="from" value="<?= e($from ? substr($from, 0, 10) : '') ?>">
            </div>
            <div class="col-md-3">
              <label class="form-label small fw-semibold mb-1">To</label>
              <input type="date" class="form-control form-control-sm" name="to" value="<?= e($to ? substr($to, 0, 10) : '') ?>">
            </div>
          <?php endif; ?>
          <div class="col-md-3">
            <label class="form-label small fw-semibold mb-1">Status</label>
            <select class="form-select form-select-sm" name="status" onchange="this.form.submit()">
              <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>All statuses</option>
              <option value="paid" <?= $status === 'paid' ? 'selected' : '' ?>>Paid</option>
              <option value="initiated" <?= $status === 'initiated' ? 'selected' : '' ?>>Initiated (pending)</option>
              <option value="failed" <?= $status === 'failed' ? 'selected' : '' ?>>Failed</option>
            </select>
          </div>
          <div class="col-md-3 d-flex">
            <button class="km-btn km-btn-sm" type="submit">Apply</button>
          </div>
        </form>
      </div>

      <!-- Filtered total bar -->
      <div class="km-card km-card-pad mb-3" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
        <div>
          <div style="font-size:.78rem; color: var(--km-muted); text-transform:uppercase; letter-spacing:.06em;">Showing</div>
          <div style="font-size:1.1rem; font-weight: 600;"><?= count($payments) ?> records · <?= $visibleCount ?> paid</div>
        </div>
        <div style="text-align:right;">
          <div style="font-size:.78rem; color: var(--km-muted); text-transform:uppercase; letter-spacing:.06em;">Filtered total received</div>
          <div style="font-size:1.4rem; font-weight: 700; color: var(--km-green);">₹<?= number_format($visibleTotal, 2) ?></div>
        </div>
      </div>

      <!-- Ledger table -->
      <div class="km-card" style="overflow:hidden;">
        <?php if (!$payments): ?>
          <div class="text-center py-5" style="color: var(--km-muted);">No payments found for this filter.</div>
        <?php else: ?>
          <div class="km-ledger-scroll">
            <table class="km-ledger-table">
              <thead>
                <tr>
                  <th class="km-col-narrow">Date / Time</th>
                  <th class="km-col-narrow">Order #</th>
                  <th>Buyer</th>
                  <th class="km-col-narrow text-end">Gross</th>
                  <th class="km-col-narrow text-end">Commission</th>
                  <th class="km-col-narrow text-end">Net Payable</th>
                  <th class="km-col-narrow">Status</th>
                  <th class="km-col-narrow">Razorpay Payment ID</th>
                  <th class="km-col-narrow text-end">Action</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($payments as $p): ?>
                  <tr>
                    <td class="km-col-narrow">
                      <div><?= date('d M Y', strtotime((string)$p['created_at'])) ?></div>
                      <div style="color: var(--km-muted); font-size:.76rem;"><?= date('h:i A', strtotime((string)$p['created_at'])) ?></div>
                    </td>
                    <td class="km-col-narrow km-mono"><?= e((string)$p['order_no']) ?></td>
                    <td>
                      <div style="font-weight: 500;"><?= e((string)$p['buyer_name']) ?></div>
                      <?php if (!empty($p['buyer_phone'])): ?>
                        <div style="color: var(--km-muted); font-size:.78rem;"><?= e((string)$p['buyer_phone']) ?></div>
                      <?php endif; ?>
                    </td>
                    <?php
                      $rowComm = (float)($p['line_commission'] ?? 0);
                      $rowPay  = (float)($p['line_payable'] ?? 0);
                      if ($rowComm <= 0 && (float)($p['order_commission'] ?? 0) > 0) $rowComm = (float)$p['order_commission'];
                      if ($rowPay  <= 0 && (float)($p['order_payable'] ?? 0)    > 0) $rowPay  = (float)$p['order_payable'];
                    ?>
                    <td class="km-col-narrow km-amount text-end">₹<?= number_format((float)$p['amount'], 2) ?></td>
                    <td class="km-col-narrow text-end" style="color:#b45309;">−₹<?= number_format($rowComm, 2) ?></td>
                    <td class="km-col-narrow text-end fw-semibold" style="color:#059669;">₹<?= number_format($rowPay, 2) ?></td>
                    <td class="km-col-narrow">
                      <span class="km-pill <?= $statusPill((string)$p['status']) ?>"><?= e(ucfirst((string)$p['status'])) ?></span>
                    </td>
                    <td class="km-col-narrow km-mono">
                      <?= !empty($p['razorpay_payment_id']) ? e((string)$p['razorpay_payment_id']) : '<span style="color: var(--km-muted);">—</span>' ?>
                    </td>
                    <td class="km-col-narrow text-end">
                      <a class="km-btn km-btn-sm" href="?p=merchant/order&id=<?= (int)$p['order_id'] ?>">View</a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <script>if (window.lucide) window.lucide.createIcons();</script>
<?php
};

require __DIR__ . '/../../views/layout.php';
