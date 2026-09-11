<?php

declare(strict_types=1);

auth_require_role('super_admin');

$title = 'Seller Earnings';

$merchants = db_fetch_all($db, "
    SELECT u.id AS merchant_id, u.full_name, u.email, mp.business_name, mp.status,
           COALESCE(SUM(CASE WHEN wt.type='credit' THEN wt.amount ELSE -wt.amount END), 0) AS balance,
           COALESCE(SUM(CASE WHEN wt.type='credit' AND wt.ref_type='order' THEN wt.amount ELSE 0 END), 0) AS total_credited,
           COALESCE(SUM(CASE WHEN wt.type='debit'  AND wt.ref_type='order_reversal' THEN wt.amount ELSE 0 END), 0) AS total_reversed,
           COALESCE(SUM(CASE WHEN wt.type='debit'  AND wt.ref_type='payout' THEN wt.amount ELSE 0 END), 0) AS total_paid_out,
           COUNT(DISTINCT CASE WHEN wt.ref_type='order' THEN wt.ref_id END) AS orders_credited
    FROM users u
    LEFT JOIN merchant_profile mp     ON mp.merchant_user_id = u.id
    LEFT JOIN wallet_transactions wt  ON wt.user_id = u.id
    WHERE u.role = 'merchant'
    GROUP BY u.id
    ORDER BY balance DESC, u.full_name
");

// Pending earnings per merchant: orders placed but not yet wallet-credited
$pendingByMerchant = [];
$pendingRows = db_fetch_all($db, "
    SELECT o.merchant_id, COALESCE(SUM(o.total_amount), 0) AS pending
    FROM orders o
    WHERE o.merchant_id IS NOT NULL
      AND o.status IN ('new','paid','packed','ready_to_pick','shipped','in_transit')
      AND NOT EXISTS (
          SELECT 1 FROM wallet_transactions wt
          WHERE wt.user_id = o.merchant_id AND wt.ref_type='order' AND wt.ref_id = o.id
      )
    GROUP BY o.merchant_id
");
foreach ($pendingRows as $r) {
    $pendingByMerchant[(int)$r['merchant_id']] = (float)$r['pending'];
}

// Platform totals
$platformBalance      = 0.0;
$platformCredited     = 0.0;
$platformPending      = 0.0;
foreach ($merchants as $m) {
    $platformBalance  += (float)$m['balance'];
    $platformCredited += (float)$m['total_credited'];
    $platformPending  += $pendingByMerchant[(int)$m['merchant_id']] ?? 0;
}

$content = function () use ($merchants, $pendingByMerchant, $platformBalance, $platformCredited, $platformPending) {
    require __DIR__ . '/../../views/partials/super-admin-nav.php';
    ?>
    <div class="container">
      <h1 class="h4 mb-3">Seller Earnings</h1>
      <p class="text-muted small">
        Each seller gets paid directly through their own Razorpay/COD — the platform doesn't hold any of this money.
        This view shows what each seller has earned to date plus what's still pending delivery.
      </p>

      <div class="row g-3 mb-4">
        <div class="col-md-4">
          <div class="bg-white border rounded-3 p-3">
            <div class="text-muted small mb-1">Total seller balances</div>
            <div class="h4 mb-0 text-success">₹<?= number_format($platformBalance, 2) ?></div>
            <div class="small text-muted mt-1">Across all sellers combined.</div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="bg-white border rounded-3 p-3">
            <div class="text-muted small mb-1">Total credited</div>
            <div class="h4 mb-0">₹<?= number_format($platformCredited, 2) ?></div>
            <div class="small text-muted mt-1">Lifetime earnings booked.</div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="bg-white border rounded-3 p-3">
            <div class="text-muted small mb-1">Total pending</div>
            <div class="h4 mb-0 text-warning">₹<?= number_format($platformPending, 2) ?></div>
            <div class="small text-muted mt-1">Placed orders, awaiting delivery.</div>
          </div>
        </div>
      </div>

      <div class="bg-white border rounded-3 overflow-hidden">
        <table class="table table-sm mb-0 align-middle">
          <thead class="table-light">
            <tr>
              <th>Seller</th>
              <th>Status</th>
              <th class="text-center">Orders credited</th>
              <th class="text-end">Total credited</th>
              <th class="text-end">Reversed</th>
              <th class="text-end">Paid out</th>
              <th class="text-end">Balance (available)</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($merchants)): ?>
              <tr><td colspan="8" class="text-muted text-center py-4">No sellers yet.</td></tr>
            <?php else: foreach ($merchants as $m): ?>
              <?php
              $mid = (int)$m['merchant_id'];
              $reversed = (float)($m['total_reversed'] ?? 0);
              $paidOut  = (float)($m['total_paid_out'] ?? 0);
              $status = (string)($m['status'] ?? 'pending');
              $badge = $status === 'approved' ? 'success' : ($status === 'pending' ? 'warning' : 'secondary');
              ?>
              <tr>
                <td>
                  <div class="fw-semibold"><?= e((string)($m['business_name'] ?? $m['full_name'])) ?></div>
                  <div class="small text-muted"><?= e((string)$m['full_name']) ?> &middot; <?= e((string)$m['email']) ?></div>
                </td>
                <td><span class="badge bg-<?= $badge ?>"><?= e(ucfirst($status)) ?></span></td>
                <td class="text-center"><?= (int)$m['orders_credited'] ?></td>
                <td class="text-end">₹<?= number_format((float)$m['total_credited'], 2) ?></td>
                <td class="text-end <?= $reversed > 0 ? 'text-danger' : 'text-muted' ?>">
                  −₹<?= number_format($reversed, 2) ?>
                </td>
                <td class="text-end <?= $paidOut > 0 ? 'text-primary' : 'text-muted' ?>">
                  ₹<?= number_format($paidOut, 2) ?>
                </td>
                <td class="text-end fw-semibold text-success">₹<?= number_format((float)$m['balance'], 2) ?></td>
                <td class="text-end">
                  <a class="btn btn-sm btn-outline-secondary"
                     href="?p=super-admin/merchant-wallet-detail&merchant_id=<?= $mid ?>">
                    View ledger
                  </a>
                </td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php
};

require __DIR__ . '/../../views/layout.php';
