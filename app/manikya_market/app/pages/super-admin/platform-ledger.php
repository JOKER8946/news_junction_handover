<?php

declare(strict_types=1);

auth_require_role('super_admin');

/**
 * Platform ledger — every order that's been delivered credits the platform's
 * share to the super-admin's wallet as a single `commission`-typed credit
 * (value = commission + shipping + GST + any platform-absorbed discounts).
 * Cancellations / returns are reversed as `commission_reversal` debits.
 *
 * This page surfaces those entries, broken down per order with running totals
 * and CSV export.
 */

$adminId = function_exists('platform_admin_user_id') ? platform_admin_user_id($db) : 0;
if ($adminId <= 0) {
    flash_set('error', 'No super-admin user found.');
    redirect_to('super-admin/dashboard');
}

// ─── Filters ────────────────────────────────────────────────────────────
$period = $_GET['period'] ?? 'all';
$allowedPeriods = ['all', 'today', 'month', 'year', 'custom'];
if (!in_array($period, $allowedPeriods, true)) $period = 'all';

$from = null; $to = null;
if ($period === 'today') {
    $from = date('Y-m-d') . ' 00:00:00';
    $to   = date('Y-m-d') . ' 23:59:59';
} elseif ($period === 'month') {
    $from = date('Y-m-01') . ' 00:00:00';
    $to   = date('Y-m-t')  . ' 23:59:59';
} elseif ($period === 'year') {
    $from = date('Y-01-01') . ' 00:00:00';
    $to   = date('Y-12-31') . ' 23:59:59';
} elseif ($period === 'custom') {
    $from = ($_GET['from'] ?? date('Y-m-d')) . ' 00:00:00';
    $to   = ($_GET['to']   ?? date('Y-m-d')) . ' 23:59:59';
}

$kind = $_GET['kind'] ?? 'all';
if (!in_array($kind, ['all', 'credit', 'debit'], true)) $kind = 'all';

// ─── Query ──────────────────────────────────────────────────────────────
$where = [
    "wt.user_id = :uid",
    "wt.ref_type IN ('commission', 'commission_reversal')",
];
$params = ['uid' => (int)$adminId];

if ($from && $to) {
    $where[] = 'wt.created_at BETWEEN :from AND :to';
    $params['from'] = $from;
    $params['to']   = $to;
}
if ($kind === 'credit') {
    $where[] = "wt.type = 'credit'";
} elseif ($kind === 'debit') {
    $where[] = "wt.type = 'debit'";
}
$whereSql = implode(' AND ', $where);

$rows = db_fetch_all($db, "
    SELECT wt.id, wt.user_id, wt.type, wt.amount, wt.description, wt.ref_type,
           wt.ref_id AS order_id, wt.created_at,
           o.order_no, o.status AS order_status,
           o.total_amount     AS gross_amount,
           o.commission_amount AS commission,
           o.commission_pct,
           o.merchant_payable,
           bu.full_name AS buyer_name, bu.email AS buyer_email,
           mu.full_name AS merchant_name, mp.business_name
      FROM wallet_transactions wt
      LEFT JOIN orders o            ON o.id = wt.ref_id
      LEFT JOIN users  bu           ON bu.id = o.buyer_id
      LEFT JOIN users  mu           ON mu.id = o.merchant_id
      LEFT JOIN merchant_profile mp ON mp.merchant_user_id = o.merchant_id
     WHERE $whereSql
     ORDER BY wt.created_at DESC, wt.id DESC
", $params) ?: [];

// ─── KPIs (all time, today, this month, this year) ─────────────────────
$kpi = function (string $rangeSql, array $rangeParams = []) use ($db, $adminId): array {
    $base = "SELECT
                COALESCE(SUM(CASE WHEN type='credit' THEN amount ELSE 0 END), 0) AS credits,
                COALESCE(SUM(CASE WHEN type='debit'  THEN amount ELSE 0 END), 0) AS debits,
                COUNT(*) AS n
             FROM wallet_transactions
             WHERE user_id = :uid
               AND ref_type IN ('commission', 'commission_reversal')";
    $sql = $base . ($rangeSql !== '' ? " AND $rangeSql" : '');
    $r = db_fetch_one($db, $sql, array_merge(['uid' => $adminId], $rangeParams)) ?: [];
    return [
        'credits' => (float)($r['credits'] ?? 0),
        'debits'  => (float)($r['debits']  ?? 0),
        'net'     => (float)($r['credits'] ?? 0) - (float)($r['debits'] ?? 0),
        'n'       => (int)($r['n'] ?? 0),
    ];
};
$kpiAll   = $kpi('');
$kpiToday = $kpi('DATE(created_at) = CURDATE()');
$kpiMonth = $kpi('YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE())');
$kpiYear  = $kpi('YEAR(created_at) = YEAR(CURDATE())');

// Filtered totals
$visCredits = 0.0; $visDebits = 0.0;
foreach ($rows as $r) {
    if ($r['type'] === 'credit') $visCredits += (float)$r['amount'];
    else                          $visDebits  += (float)$r['amount'];
}
$visNet = $visCredits - $visDebits;

// Per-row computed breakdown: commission portion vs "other" (shipping + GST + discount-absorbed)
$breakdown = function (array $r): array {
    $platformShare = (float)$r['amount'];      // what landed in admin wallet (credit) or was reversed (debit)
    $commission    = (float)($r['commission'] ?? 0);
    $other         = max(0.0, round($platformShare - $commission, 2));   // shipping + GST + discount-absorbed (positive)
    return ['platform' => $platformShare, 'commission' => $commission, 'other' => $other];
};

// ─── CSV export ─────────────────────────────────────────────────────────
if (($_GET['export'] ?? '') === 'csv') {
    $filename = 'Platform_Ledger_' . $period . '_' . date('Ymd_His') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Manikya Market — Platform Ledger']);
    fputcsv($out, ['Period', $period]);
    if ($from && $to) { fputcsv($out, ['From', $from]); fputcsv($out, ['To', $to]); }
    fputcsv($out, []);
    fputcsv($out, ['Date', 'Order #', 'Buyer', 'Seller / Business', 'Type', 'Gross (INR)',
                   'Commission (INR)', 'Shipping + GST + Other (INR)', 'Platform Share (INR)',
                   'Order Status', 'Description']);
    foreach ($rows as $r) {
        $b = $breakdown($r);
        $sign = $r['type'] === 'credit' ? '' : '-';
        fputcsv($out, [
            $r['created_at'],
            $r['order_no'] ?? '',
            $r['buyer_name'] ?? '',
            ($r['business_name'] ?? '') ?: ($r['merchant_name'] ?? ''),
            $r['type'] === 'credit' ? 'Credit' : 'Reversal',
            number_format((float)($r['gross_amount'] ?? 0), 2, '.', ''),
            number_format($b['commission'], 2, '.', ''),
            number_format($b['other'],      2, '.', ''),
            $sign . number_format($b['platform'], 2, '.', ''),
            $r['order_status'] ?? '',
            $r['description']  ?? '',
        ]);
    }
    fputcsv($out, []);
    fputcsv($out, ['Filtered credits',  number_format($visCredits, 2, '.', '')]);
    fputcsv($out, ['Filtered reversals', number_format($visDebits, 2, '.', '')]);
    fputcsv($out, ['Filtered net',       number_format($visNet,    2, '.', '')]);
    fclose($out);
    exit;
}

$title = 'Platform Ledger';

$content = function () use ($rows, $period, $kind, $from, $to, $kpiAll, $kpiToday, $kpiMonth, $kpiYear,
                            $visCredits, $visDebits, $visNet, $breakdown) {

    $periodLabels = [
        'all'    => 'All time',
        'today'  => 'Today',
        'month'  => 'This month',
        'year'   => 'This year',
        'custom' => 'Custom',
    ];

    $exportQs = http_build_query(array_filter([
        'p'      => 'super-admin/platform-ledger',
        'export' => 'csv',
        'period' => $period,
        'kind'   => $kind,
        'from'   => $period === 'custom' && $from ? substr($from, 0, 10) : null,
        'to'     => $period === 'custom' && $to   ? substr($to,   0, 10) : null,
    ]));

    require __DIR__ . '/../../views/partials/super-admin-nav.php';
    ?>
    <style>
      .pl-table { width: 100%; border-collapse: collapse; font-size: .88rem; background: #fff; }
      .pl-table th, .pl-table td { padding: 11px 14px; border-bottom: 1px solid #E5E7EB; vertical-align: middle; }
      .pl-table th { background: #F9FAFB; text-align: left; font-size: .7rem; font-weight: 600;
                     letter-spacing: .04em; text-transform: uppercase; color: #6B7280; white-space: nowrap; }
      .pl-table tbody tr:hover { background: #F9FAFB; }
      .pl-scroll { max-height: calc(100vh - 380px); min-height: 320px; overflow: auto; }
      .pl-mono { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: .82rem; }
      .pl-amount { font-weight: 600; font-variant-numeric: tabular-nums; }
      .pl-narrow { width: 1%; white-space: nowrap; }
      .pl-card { background: #fff; border: 1px solid #E5E7EB; border-radius: 12px; padding: 16px 18px; }
      .pl-stat-label { text-transform: uppercase; letter-spacing: .06em; font-size: .7rem; color: #6B7280; }
      .pl-stat-num   { font-size: 1.6rem; font-weight: 700; color: #111827; }
      .pl-stat-sub   { font-size: .78rem; color: #6B7280; margin-top: 2px; }
      .pl-pill { display: inline-block; padding: 3px 9px; border-radius: 999px; font-size: .68rem; font-weight: 600;
                 letter-spacing: .04em; text-transform: uppercase; }
      .pl-pill--credit  { background: #ECFDF5; color: #047857; }
      .pl-pill--debit   { background: #FEF2F2; color: #B91C1C; }
      .pl-pill--status  { background: #F3F4F6; color: #374151; }
      .pl-row-debit { background: #FFFBFB; }
    </style>

    <div class="container">

      <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <div>
          <h1 class="h4 mb-1">Platform Ledger</h1>
          <div class="text-muted small">
            Every order delivered credits the platform's share (commission + shipping + GST) here.
            Cancellations / returns are recorded as reversals.
          </div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
          <a class="btn btn-outline-secondary btn-sm" href="?p=super-admin/dashboard">
            <i data-lucide="arrow-left" style="width:14px;height:14px;"></i> Back
          </a>
          <a class="btn btn-mm btn-sm" href="?<?= e($exportQs) ?>">
            <i data-lucide="download" style="width:14px;height:14px;"></i> Export CSV
          </a>
        </div>
      </div>

      <!-- KPIs -->
      <div class="row g-3 mb-3">
        <div class="col-6 col-md-3">
          <div class="pl-card">
            <div class="pl-stat-label">Total earned (net)</div>
            <div class="pl-stat-num">₹<?= number_format($kpiAll['net'], 2) ?></div>
            <div class="pl-stat-sub"><?= (int)$kpiAll['n'] ?> entries · ₹<?= number_format($kpiAll['debits'], 2) ?> reversed</div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="pl-card">
            <div class="pl-stat-label">Today</div>
            <div class="pl-stat-num">₹<?= number_format($kpiToday['net'], 2) ?></div>
            <div class="pl-stat-sub"><?= (int)$kpiToday['n'] ?> entries</div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="pl-card">
            <div class="pl-stat-label">This month</div>
            <div class="pl-stat-num">₹<?= number_format($kpiMonth['net'], 2) ?></div>
            <div class="pl-stat-sub"><?= (int)$kpiMonth['n'] ?> entries</div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="pl-card">
            <div class="pl-stat-label">This year</div>
            <div class="pl-stat-num">₹<?= number_format($kpiYear['net'], 2) ?></div>
            <div class="pl-stat-sub"><?= (int)$kpiYear['n'] ?> entries</div>
          </div>
        </div>
      </div>

      <!-- Filters -->
      <div class="pl-card mb-3">
        <form method="get" class="row g-2 align-items-end">
          <input type="hidden" name="p" value="super-admin/platform-ledger">
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
              <input type="date" class="form-control form-control-sm" name="from"
                     value="<?= e($from ? substr($from, 0, 10) : '') ?>">
            </div>
            <div class="col-md-3">
              <label class="form-label small fw-semibold mb-1">To</label>
              <input type="date" class="form-control form-control-sm" name="to"
                     value="<?= e($to ? substr($to, 0, 10) : '') ?>">
            </div>
          <?php endif; ?>
          <div class="col-md-3">
            <label class="form-label small fw-semibold mb-1">Entry type</label>
            <select class="form-select form-select-sm" name="kind" onchange="this.form.submit()">
              <option value="all"    <?= $kind === 'all'    ? 'selected' : '' ?>>All entries</option>
              <option value="credit" <?= $kind === 'credit' ? 'selected' : '' ?>>Credits only</option>
              <option value="debit"  <?= $kind === 'debit'  ? 'selected' : '' ?>>Reversals only</option>
            </select>
          </div>
          <div class="col-md-3 d-flex">
            <button class="btn btn-mm btn-sm" type="submit">Apply</button>
          </div>
        </form>
      </div>

      <!-- Filtered totals bar -->
      <div class="pl-card mb-3" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
        <div>
          <div class="pl-stat-label">Showing</div>
          <div style="font-size:1rem; font-weight: 600;"><?= count($rows) ?> entries</div>
        </div>
        <div style="text-align:right;">
          <div class="pl-stat-label">Filtered net</div>
          <div style="font-size:1.4rem; font-weight: 700; color: <?= $visNet >= 0 ? '#047857' : '#B91C1C' ?>;">
            ₹<?= number_format($visNet, 2) ?>
          </div>
          <div class="pl-stat-sub">
            Credits ₹<?= number_format($visCredits, 2) ?> · Reversals ₹<?= number_format($visDebits, 2) ?>
          </div>
        </div>
      </div>

      <!-- Ledger table -->
      <div class="pl-card" style="padding: 0; overflow: hidden;">
        <?php if (!$rows): ?>
          <div class="text-center py-5 text-muted">No platform earnings yet for this filter.</div>
        <?php else: ?>
          <div class="pl-scroll">
            <table class="pl-table">
              <thead>
                <tr>
                  <th class="pl-narrow">Date</th>
                  <th class="pl-narrow">Order #</th>
                  <th>Buyer</th>
                  <th>Seller</th>
                  <th class="pl-narrow text-end">Gross</th>
                  <th class="pl-narrow text-end">Commission</th>
                  <th class="pl-narrow text-end">Shipping + GST</th>
                  <th class="pl-narrow text-end">Platform Share</th>
                  <th class="pl-narrow">Type</th>
                  <th class="pl-narrow text-end">Action</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($rows as $r): $b = $breakdown($r); ?>
                  <tr class="<?= $r['type'] === 'debit' ? 'pl-row-debit' : '' ?>">
                    <td class="pl-narrow">
                      <div><?= date('d M Y', strtotime((string)$r['created_at'])) ?></div>
                      <div class="text-muted" style="font-size:.74rem;"><?= date('h:i A', strtotime((string)$r['created_at'])) ?></div>
                    </td>
                    <td class="pl-narrow pl-mono">
                      <?= $r['order_no'] ? e((string)$r['order_no']) : '<span class="text-muted">—</span>' ?>
                    </td>
                    <td>
                      <?php if (!empty($r['buyer_name'])): ?>
                        <div style="font-weight:500;"><?= e((string)$r['buyer_name']) ?></div>
                        <?php if (!empty($r['buyer_email'])): ?>
                          <div class="text-muted" style="font-size:.76rem;"><?= e(mask_email((string)$r['buyer_email'])) ?></div>
                        <?php endif; ?>
                      <?php else: ?>
                        <span class="text-muted">—</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <?php $sellerLabel = (string)($r['business_name'] ?? '') ?: (string)($r['merchant_name'] ?? ''); ?>
                      <?php if ($sellerLabel !== ''): ?>
                        <div style="font-weight:500;"><?= e($sellerLabel) ?></div>
                      <?php else: ?>
                        <span class="text-muted">—</span>
                      <?php endif; ?>
                    </td>
                    <td class="pl-narrow text-end pl-amount">
                      ₹<?= number_format((float)($r['gross_amount'] ?? 0), 2) ?>
                    </td>
                    <td class="pl-narrow text-end" style="color:#b45309;">
                      ₹<?= number_format($b['commission'], 2) ?>
                    </td>
                    <td class="pl-narrow text-end" style="color:#1d4ed8;">
                      ₹<?= number_format($b['other'], 2) ?>
                    </td>
                    <td class="pl-narrow text-end pl-amount" style="color: <?= $r['type'] === 'credit' ? '#047857' : '#B91C1C' ?>;">
                      <?= $r['type'] === 'credit' ? '+' : '−' ?>₹<?= number_format($b['platform'], 2) ?>
                    </td>
                    <td class="pl-narrow">
                      <span class="pl-pill <?= $r['type'] === 'credit' ? 'pl-pill--credit' : 'pl-pill--debit' ?>">
                        <?= $r['type'] === 'credit' ? 'Credit' : 'Reversal' ?>
                      </span>
                    </td>
                    <td class="pl-narrow text-end">
                      <?php if (!empty($r['order_id'])): ?>
                        <a class="btn btn-outline-secondary btn-sm" href="?p=merchant/order&id=<?= (int)$r['order_id'] ?>&view=1">View</a>
                      <?php else: ?>
                        <span class="text-muted">—</span>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>

    </div>
    <?php
};

require __DIR__ . '/../../views/layout.php';
