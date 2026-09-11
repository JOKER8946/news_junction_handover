<?php

declare(strict_types=1);

auth_require_role('super_admin');

$merchantId = (int)($_GET['merchant_id'] ?? 0);
if ($merchantId <= 0) {
    redirect_to('super-admin/merchant-wallets');
}

$merchant = db_fetch_one($db, "
    SELECT u.id, u.full_name, u.email, mp.business_name, mp.status
    FROM users u
    LEFT JOIN merchant_profile mp ON mp.merchant_user_id = u.id
    WHERE u.id = :id AND u.role = 'merchant' LIMIT 1
", ['id' => $merchantId]);

if (!$merchant) {
    flash_set('error', 'Seller not found.');
    redirect_to('super-admin/merchant-wallets');
}

$title = 'Seller Wallet — ' . ($merchant['business_name'] ?: $merchant['full_name']);

$balance      = wallet_balance($db, $merchantId);
$transactions = wallet_transactions($db, $merchantId);

$content = function () use ($merchant, $balance, $transactions) {
    require __DIR__ . '/../../views/partials/super-admin-nav.php';
    ?>
    <div class="container">
      <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
          <h1 class="h4 mb-1"><?= e((string)($merchant['business_name'] ?: $merchant['full_name'])) ?></h1>
          <div class="small text-muted">
            <?= e((string)$merchant['full_name']) ?> &middot; <?= e((string)$merchant['email']) ?>
          </div>
        </div>
        <a class="btn btn-sm btn-outline-secondary" href="?p=super-admin/merchant-wallets">
          <i data-lucide="arrow-left" style="width:14px;height:14px;"></i> Back
        </a>
      </div>

      <div class="bg-white border rounded-3 p-3 mb-3 d-flex align-items-center gap-3">
        <div class="d-flex align-items-center gap-2">
          <i data-lucide="wallet"></i>
          <span class="text-muted">Current balance:</span>
          <strong class="h5 mb-0 text-success">₹<?= number_format($balance, 2) ?></strong>
        </div>
      </div>

      <div class="bg-white border rounded-3 overflow-hidden">
        <div class="px-3 py-2 bg-light border-bottom small text-uppercase fw-semibold text-muted">
          Transaction history
        </div>
        <?php if (empty($transactions)): ?>
          <div class="p-4 text-center text-muted">No transactions yet.</div>
        <?php else: ?>
          <table class="table table-sm mb-0 align-middle">
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
    </div>
    <script>if (window.lucide) window.lucide.createIcons();</script>
    <?php
};

require __DIR__ . '/../../views/layout.php';
