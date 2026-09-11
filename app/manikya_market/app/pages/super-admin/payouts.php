<?php

declare(strict_types=1);

auth_require_role('super_admin');

$title = 'Payouts';

if (request_method() === 'POST') {
    $merchantId = (int)post_string('merchant_id');
    $amount     = (float)post_string('amount');
    $method     = post_string('method') ?: 'bank_transfer';
    $referenceNo= post_string('reference_no');
    $notes      = post_string('notes');

    if ($merchantId <= 0 || $amount <= 0) {
        flash_set('error', 'Seller and amount are required.');
        redirect_to('super-admin/payouts');
    }

    // Guard: payout cannot exceed the seller's available wallet balance.
    $sellerBalance = wallet_balance($db, $merchantId);
    if ($amount > $sellerBalance + 0.005) {
        flash_set('error', sprintf(
            'Payout ₹%s exceeds seller\'s available balance ₹%s. Reduce amount or wait for more order earnings to land.',
            number_format($amount, 2), number_format($sellerBalance, 2)
        ));
        redirect_to('super-admin/payouts');
    }

    $payoutId = platform_record_payout(
        $db, $merchantId, $amount, $method, $referenceNo, $notes, (int)auth_user_id()
    );

    if ($payoutId > 0) {
        flash_set('success', 'Payout #' . $payoutId . ' recorded. Seller wallet debited ₹' . number_format($amount, 2) . '.');
    } else {
        flash_set('error', 'Could not record payout.');
    }
    redirect_to('super-admin/payouts');
}

$payableMerchants = db_fetch_all($db, "
    SELECT u.id AS merchant_id, u.full_name, mp.business_name,
           COALESCE(SUM(CASE WHEN wt.type='credit' THEN wt.amount ELSE -wt.amount END), 0) AS balance
    FROM users u
    LEFT JOIN merchant_profile mp     ON mp.merchant_user_id = u.id
    LEFT JOIN wallet_transactions wt  ON wt.user_id = u.id
    WHERE u.role = 'merchant'
    GROUP BY u.id
    HAVING balance > 0
    ORDER BY balance DESC, u.full_name
");

$recentPayouts = db_fetch_all($db, "
    SELECT p.*, u.full_name AS merchant_name, mp.business_name
    FROM payouts p
    JOIN users u ON u.id = p.merchant_id
    LEFT JOIN merchant_profile mp ON mp.merchant_user_id = p.merchant_id
    ORDER BY p.created_at DESC LIMIT 50
");

$totalPaidOut = 0.0;
foreach ($recentPayouts as $p) {
    if ($p['status'] === 'paid') $totalPaidOut += (float)$p['amount'];
}

$content = function () use ($payableMerchants, $recentPayouts, $totalPaidOut) {
    require __DIR__ . '/../../views/partials/super-admin-nav.php';
    $success = flash_get('success');
    $error = flash_get('error');
    ?>
    <div class="container">
      <h1 class="h4 mb-3">Payouts to Sellers</h1>
      <p class="text-muted small">
        Record a transfer from the platform's bank to a seller. Debits the seller's wallet so their
        "available for payout" balance updates.
      </p>

      <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
      <?php if ($error):   ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

      <div class="row g-3 mb-4">
        <div class="col-md-6">
          <div class="bg-white border rounded-3 p-3">
            <div class="text-muted small mb-1">Total payable to sellers</div>
            <?php $outstanding = array_sum(array_column($payableMerchants, 'balance')); ?>
            <div class="h4 mb-0 text-warning">₹<?= number_format($outstanding, 2) ?></div>
            <div class="small text-muted mt-1"><?= count($payableMerchants) ?> seller(s) with positive balance.</div>
          </div>
        </div>
        <div class="col-md-6">
          <div class="bg-white border rounded-3 p-3">
            <div class="text-muted small mb-1">Total paid out (recent 50)</div>
            <div class="h4 mb-0 text-success">₹<?= number_format($totalPaidOut, 2) ?></div>
          </div>
        </div>
      </div>

      <div class="row g-3">
        <!-- Record payout -->
        <div class="col-lg-5">
          <div class="bg-white border rounded-3 p-3">
            <h2 class="h6 mb-3">Record a payout</h2>
            <?php if (empty($payableMerchants)): ?>
              <div class="text-muted small">No sellers have a positive balance right now.</div>
            <?php else: ?>
              <form method="post" class="d-grid gap-2">
                <div>
                  <label class="form-label small">Seller</label>
                  <select class="form-select form-select-sm" name="merchant_id" required>
                    <option value="">— Choose —</option>
                    <?php foreach ($payableMerchants as $m): ?>
                      <option value="<?= (int)$m['merchant_id'] ?>" data-balance="<?= (float)$m['balance'] ?>">
                        <?= e((string)($m['business_name'] ?: $m['full_name'])) ?>
                        — ₹<?= number_format((float)$m['balance'], 2) ?> available
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div>
                  <label class="form-label small">Amount (₹)</label>
                  <input class="form-control form-control-sm" type="number" step="0.01" min="0.01" name="amount" required>
                </div>
                <div>
                  <label class="form-label small">Method</label>
                  <select class="form-select form-select-sm" name="method">
                    <option value="bank_transfer">Bank transfer</option>
                    <option value="razorpay_payout">Razorpay Payout</option>
                    <option value="upi">UPI</option>
                    <option value="cash">Cash</option>
                    <option value="other">Other</option>
                  </select>
                </div>
                <div>
                  <label class="form-label small">Reference no. <span class="text-muted">(UTR / txn id)</span></label>
                  <input class="form-control form-control-sm" name="reference_no" maxlength="120">
                </div>
                <div>
                  <label class="form-label small">Notes</label>
                  <textarea class="form-control form-control-sm" name="notes" rows="2"></textarea>
                </div>
                <button class="btn btn-sm btn-mm mt-2" type="submit">Record payout</button>
              </form>
            <?php endif; ?>
          </div>
        </div>

        <!-- Recent payouts -->
        <div class="col-lg-7">
          <div class="bg-white border rounded-3 overflow-hidden">
            <div class="px-3 py-2 bg-light border-bottom small text-uppercase fw-semibold text-muted">
              Recent payouts (latest 50)
            </div>
            <?php if (empty($recentPayouts)): ?>
              <div class="p-4 text-muted text-center">No payouts yet.</div>
            <?php else: ?>
              <table class="table table-sm mb-0 align-middle">
                <thead class="table-light">
                  <tr>
                    <th>Date</th><th>Seller</th><th>Method</th><th>Reference</th>
                    <th class="text-end">Amount</th><th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($recentPayouts as $p): ?>
                    <tr>
                      <td class="small text-muted"><?= e(date('d M Y H:i', strtotime((string)$p['created_at']))) ?></td>
                      <td>
                        <div><?= e((string)($p['business_name'] ?: $p['merchant_name'])) ?></div>
                      </td>
                      <td class="small"><?= e(str_replace('_',' ',(string)$p['method'])) ?></td>
                      <td class="small text-muted"><?= e((string)($p['reference_no'] ?? '')) ?></td>
                      <td class="text-end fw-semibold">₹<?= number_format((float)$p['amount'], 2) ?></td>
                      <td>
                        <?php $b = $p['status'] === 'paid' ? 'success' : ($p['status'] === 'failed' ? 'danger' : 'warning'); ?>
                        <span class="badge bg-<?= $b ?>"><?= e(ucfirst((string)$p['status'])) ?></span>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
    <?php
};

require __DIR__ . '/../../views/layout.php';
