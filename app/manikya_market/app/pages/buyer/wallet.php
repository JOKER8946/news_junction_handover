<?php

declare(strict_types=1);

auth_require_role('buyer');

$title = 'My Wallet';
$buyerId = auth_user_id();

$balance = wallet_balance($db, $buyerId);
$transactions = wallet_transactions($db, $buyerId);

$content = function () use ($balance, $transactions) {
    ?>
    <div class="container py-4" style="max-width: 700px;">
      <div class="d-flex align-items-center justify-content-between mb-3">
        <h1 class="h5 mb-0 d-flex align-items-center gap-2"><i data-lucide="wallet" class="mm-icon"></i> My Wallet</h1>
        <a class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1" href="?p=buyer/referral"><i data-lucide="gift" class="mm-icon"></i> Refer & Earn</a>
      </div>

      <!-- Balance Card -->
      <div class="bg-white border rounded-4 p-4 mm-card mb-3 text-center">
        <div class="text-muted small mb-1">Available Balance</div>
        <div class="h2 fw-bold text-success mb-1">₹<?= e(number_format($balance, 2)) ?></div>
        <div class="text-muted small">Earned from referrals</div>
      </div>

      <!-- Transactions -->
      <div class="bg-white border rounded-4 p-3 mm-card">
        <div class="fw-semibold d-flex align-items-center gap-2 mb-3"><i data-lucide="list" class="mm-icon"></i> Transaction History</div>

        <?php if (empty($transactions)): ?>
          <div class="text-muted text-center py-4">
            <i data-lucide="inbox" class="mm-icon mb-2" style="width:32px;height:32px;"></i>
            <div>No transactions yet</div>
            <div class="small mt-1">Refer friends to start earning!</div>
          </div>
        <?php else: ?>
          <div class="d-grid gap-2">
            <?php foreach ($transactions as $txn): ?>
              <div class="border rounded-3 p-3 d-flex justify-content-between align-items-center">
                <div>
                  <div class="small fw-semibold">
                    <?php if ($txn['type'] === 'credit'): ?>
                      <span class="text-success">+ ₹<?= e(number_format((float)$txn['amount'], 2)) ?></span>
                    <?php else: ?>
                      <span class="text-danger">- ₹<?= e(number_format((float)$txn['amount'], 2)) ?></span>
                    <?php endif; ?>
                  </div>
                  <div class="text-muted small"><?= e((string)$txn['description']) ?></div>
                </div>
                <div class="text-muted small text-end">
                  <?= date('M d, Y', strtotime((string)$txn['created_at'])) ?><br>
                  <?= date('h:i A', strtotime((string)$txn['created_at'])) ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
    <?php
};

require __DIR__ . '/../../views/layout.php';
