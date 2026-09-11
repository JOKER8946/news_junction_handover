<?php

declare(strict_types=1);

auth_require_role('merchant');

$title = 'Earnings & Wallet';
$merchantId = (int)auth_user_id();

$balance = wallet_balance($db, $merchantId);
$transactions = wallet_transactions($db, $merchantId);

// Summary stats
$totalCredited = 0.0;
$totalDebited  = 0.0;
$orderCount    = 0;
foreach ($transactions as $tx) {
    if ($tx['type'] === 'credit') {
        $totalCredited += (float)$tx['amount'];
        if (($tx['ref_type'] ?? '') === 'order') $orderCount++;
    } else {
        $totalDebited += (float)$tx['amount'];
    }
}

// Pending earnings: orders that haven't yet been credited
$pendingRow = db_fetch_one($db,
    "SELECT COALESCE(SUM(o.total_amount), 0) AS pending
     FROM orders o
     WHERE o.merchant_id = :mid
       AND o.status IN ('new','paid','packed','ready_to_pick','shipped','in_transit')
       AND NOT EXISTS (
           SELECT 1 FROM wallet_transactions wt
           WHERE wt.user_id = o.merchant_id AND wt.ref_type='order' AND wt.ref_id = o.id
       )",
    ['mid' => $merchantId]
);
$pendingEarnings = (float)($pendingRow['pending'] ?? 0);

$content = function () use ($balance, $transactions, $totalCredited, $totalDebited, $orderCount, $pendingEarnings) {
    ?>
    <div class="container py-4" style="max-width: 980px;">
      <div class="d-flex align-items-center justify-content-between mb-4">
        <h1 class="h4 mb-0 d-flex align-items-center gap-2">
          <i data-lucide="wallet" class="mm-icon"></i>
          Earnings &amp; Wallet
        </h1>
      </div>

      <div class="row g-3 mb-4">
        <div class="col-md-4">
          <div class="bg-white border rounded-3 p-3">
            <div class="text-muted small mb-1">Available balance</div>
            <div class="h3 mb-0 text-success">₹<?= number_format($balance, 2) ?></div>
            <div class="small text-muted mt-1">Credits minus debits across all transactions.</div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="bg-white border rounded-3 p-3">
            <div class="text-muted small mb-1">Total credited</div>
            <div class="h3 mb-0">₹<?= number_format($totalCredited, 2) ?></div>
            <div class="small text-muted mt-1"><?= (int)$orderCount ?> orders contributed.</div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="bg-white border rounded-3 p-3">
            <div class="text-muted small mb-1">Pending (not yet cleared)</div>
            <div class="h3 mb-0 text-warning">₹<?= number_format($pendingEarnings, 2) ?></div>
            <div class="small text-muted mt-1">Orders placed but not delivered/paid yet.</div>
          </div>
        </div>
      </div>

      <div class="bg-white border rounded-3 overflow-hidden">
        <div class="px-3 py-2 bg-light border-bottom small text-uppercase fw-semibold text-muted">
          Transaction history
        </div>
        <?php if (empty($transactions)): ?>
          <div class="p-4 text-center text-muted">
            <i data-lucide="inbox" style="width:32px;height:32px;color:#ccc;"></i>
            <div class="mt-2">No transactions yet. Your wallet will be credited when orders are paid or delivered.</div>
          </div>
        <?php else: ?>
          <table class="table mb-0 align-middle">
            <thead class="table-light">
              <tr>
                <th>Date</th>
                <th>Description</th>
                <th>Reference</th>
                <th class="text-end">Amount</th>
                <th>Type</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($transactions as $tx): ?>
                <tr>
                  <td class="small text-muted"><?= e(date('d M Y H:i', strtotime((string)$tx['created_at']))) ?></td>
                  <td><?= e((string)$tx['description']) ?></td>
                  <td class="small text-muted">
                    <?php if ($tx['ref_type']): ?>
                      <?= e((string)$tx['ref_type']) ?>#<?= (int)$tx['ref_id'] ?>
                    <?php else: ?>
                      —
                    <?php endif; ?>
                  </td>
                  <td class="text-end fw-semibold <?= $tx['type'] === 'credit' ? 'text-success' : 'text-danger' ?>">
                    <?= $tx['type'] === 'credit' ? '+' : '−' ?>₹<?= number_format((float)$tx['amount'], 2) ?>
                  </td>
                  <td>
                    <span class="badge bg-<?= $tx['type'] === 'credit' ? 'success' : 'secondary' ?>"><?= e(ucfirst((string)$tx['type'])) ?></span>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>

      <div class="text-muted small mt-3">
        <i data-lucide="info" style="width:14px;height:14px;"></i>
        Earnings are credited when an order's payment clears: Razorpay orders credit on payment confirmation; COD orders credit on delivery.
      </div>
    </div>
    <script>if (window.lucide) window.lucide.createIcons();</script>
    <?php
};

require __DIR__ . '/../../views/layout.php';
