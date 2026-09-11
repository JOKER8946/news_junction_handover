<?php

declare(strict_types=1);

auth_require_role('buyer');

$title = 'Refer & Earn';
$buyerId = auth_user_id();

// Ensure the buyer has a referral code
$code = referral_get_code($db, $buyerId);
if (!$code) {
    $code = referral_generate_code($db, $buyerId);
}

$stats = referral_stats($db, $buyerId);
$referrals = referral_list($db, $buyerId);
$balance = wallet_balance($db, $buyerId);

// Build shareable link
$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
$basePath = app_base_path($GLOBALS['config'] ?? []);
$shareLink = $baseUrl . ($basePath ?: '') . '/index.php?p=buyer/signup&ref=' . urlencode($code);

$content = function () use ($code, $stats, $referrals, $balance, $shareLink) {
    ?>
    <div class="container py-4" style="max-width: 860px;">
      <div class="d-flex align-items-center justify-content-between mb-3">
        <h1 class="h5 mb-0 d-flex align-items-center gap-2"><i data-lucide="gift" class="mm-icon"></i> Refer & Earn</h1>
        <a class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1" href="?p=buyer/wallet"><i data-lucide="wallet" class="mm-icon"></i> Wallet</a>
      </div>

      <!-- How it works -->
      <div class="bg-white border rounded-4 p-3 mm-card mb-3">
        <div class="fw-semibold mb-2">How it works</div>
        <div class="row g-3">
          <div class="col-md-4">
            <div class="text-center p-2">
              <div class="fw-semibold text-primary mb-1">1. Share</div>
              <div class="text-muted small">Share your referral code with friends</div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="text-center p-2">
              <div class="fw-semibold text-primary mb-1">2. Friend Signs Up</div>
              <div class="text-muted small">They get <strong><?= e(number_format($stats['referee_pct'], 1)) ?>% off</strong> their first order</div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="text-center p-2">
              <div class="fw-semibold text-primary mb-1">3. You Earn</div>
              <div class="text-muted small">You get <strong><?= e(number_format($stats['referrer_pct'], 1)) ?>%</strong> of their first order as wallet credit</div>
            </div>
          </div>
        </div>
      </div>

      <div class="row g-3">
        <!-- Referral Code Card -->
        <div class="col-12 col-lg-6">
          <div class="bg-white border rounded-4 p-3 mm-card">
            <div class="fw-semibold d-flex align-items-center gap-2 mb-3"><i data-lucide="link" class="mm-icon"></i> Your Referral Code</div>
            <?php if ((int)$stats['remaining'] > 0): ?>
              <div class="d-flex align-items-center gap-2 mb-3">
                <input type="text" id="refCode" class="form-control form-control-lg text-center fw-bold" value="<?= e($code) ?>" readonly style="letter-spacing: 3px; max-width: 220px;">
                <button class="btn btn-outline-primary btn-sm" onclick="copyCode()" title="Copy code"><i data-lucide="copy" class="mm-icon"></i></button>
              </div>
            <?php else: ?>
              <div class="alert alert-warning small mb-3 py-2">You have used all <?= (int)$stats['max'] ?> referral slots. Thank you for sharing!</div>
            <?php endif; ?>
            <div class="mb-2">
              <label class="form-label small text-muted">Share link</label>
              <div class="input-group input-group-sm">
                <input type="text" id="refLink" class="form-control small" value="<?= e($shareLink) ?>" readonly>
                <button class="btn btn-outline-primary" onclick="copyLink()" title="Copy link"><i data-lucide="copy" class="mm-icon"></i></button>
              </div>
            </div>
            <div id="copyMsg" class="text-success small mt-1" style="display:none;">Copied!</div>
          </div>
        </div>

        <!-- Stats Card -->
        <div class="col-12 col-lg-6">
          <div class="bg-white border rounded-4 p-3 mm-card">
            <div class="fw-semibold d-flex align-items-center gap-2 mb-3"><i data-lucide="bar-chart-3" class="mm-icon"></i> Your Stats</div>
            <div class="row g-2">
              <div class="col-6">
                <div class="border rounded-3 p-2 text-center">
                  <div class="h5 mb-0 text-primary"><?= (int)$stats['total'] ?>/<?= (int)$stats['max'] ?></div>
                  <div class="text-muted small">Referrals Used</div>
                </div>
              </div>
              <div class="col-6">
                <div class="border rounded-3 p-2 text-center">
                  <div class="h5 mb-0 text-success"><?= (int)$stats['completed'] ?></div>
                  <div class="text-muted small">Completed</div>
                </div>
              </div>
              <div class="col-6">
                <div class="border rounded-3 p-2 text-center">
                  <div class="h5 mb-0 text-warning"><?= (int)$stats['pending'] ?></div>
                  <div class="text-muted small">Pending</div>
                </div>
              </div>
              <div class="col-6">
                <div class="border rounded-3 p-2 text-center">
                  <div class="h5 mb-0 text-success">₹<?= e(number_format($stats['earnings'], 2)) ?></div>
                  <div class="text-muted small">Total Earned</div>
                </div>
              </div>
            </div>
            <div class="mt-3 d-flex justify-content-between align-items-center border-top pt-2">
              <div class="text-muted small">Wallet Balance</div>
              <div class="fw-semibold text-success">₹<?= e(number_format($balance, 2)) ?></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Referral History -->
      <?php if (!empty($referrals)): ?>
        <div class="bg-white border rounded-4 p-3 mm-card mt-3">
          <div class="fw-semibold d-flex align-items-center gap-2 mb-3"><i data-lucide="users" class="mm-icon"></i> Referral History</div>
          <div class="table-responsive">
            <table class="table table-sm mb-0">
              <thead>
                <tr>
                  <th class="small text-muted">Friend</th>
                  <th class="small text-muted">Status</th>
                  <th class="small text-muted">Your Reward</th>
                  <th class="small text-muted">Date</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($referrals as $ref): ?>
                  <tr>
                    <td class="small"><?= e((string)$ref['referee_name']) ?></td>
                    <td>
                      <?php if ($ref['status'] === 'completed'): ?>
                        <span class="badge bg-success">Completed</span>
                      <?php elseif ($ref['status'] === 'pending'): ?>
                        <span class="badge bg-warning text-dark">Pending</span>
                      <?php else: ?>
                        <span class="badge bg-secondary"><?= e(ucfirst((string)$ref['status'])) ?></span>
                      <?php endif; ?>
                    </td>
                    <td class="small">
                      <?php if ($ref['status'] === 'completed' && $ref['reward_amount']): ?>
                        <span class="text-success fw-semibold">₹<?= e(number_format((float)$ref['reward_amount'], 2)) ?></span>
                      <?php else: ?>
                        <span class="text-muted">--</span>
                      <?php endif; ?>
                    </td>
                    <td class="small text-muted"><?= date('M d, Y', strtotime((string)$ref['created_at'])) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      <?php endif; ?>
    </div>

    <script>
      function copyCode() {
        var inp = document.getElementById('refCode');
        inp.select();
        navigator.clipboard.writeText(inp.value);
        showCopyMsg();
      }
      function copyLink() {
        var inp = document.getElementById('refLink');
        inp.select();
        navigator.clipboard.writeText(inp.value);
        showCopyMsg();
      }
      function showCopyMsg() {
        var el = document.getElementById('copyMsg');
        el.style.display = 'block';
        setTimeout(function() { el.style.display = 'none'; }, 2000);
      }
    </script>
    <?php
};

require __DIR__ . '/../../views/layout.php';
