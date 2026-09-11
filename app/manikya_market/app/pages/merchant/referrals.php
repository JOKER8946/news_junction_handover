<?php

declare(strict_types=1);

auth_require_role('super_admin');

$title = 'Referral Program';
$merchantId = auth_user_id();

// Ensure referral tables exist
referral_ensure_tables($db);

// Ensure referral config columns exist on merchant_profile
$dbNameRow = db_fetch_one($db, 'SELECT DATABASE() AS dbname');
$dbName = (string)($dbNameRow['dbname'] ?? '');
if ($dbName !== '') {
    $cols = [
        'referral_referrer_pct' => 'DECIMAL(5,2) NOT NULL DEFAULT 5.00',
        'referral_referee_pct'  => 'DECIMAL(5,2) NOT NULL DEFAULT 10.00',
        'referral_max_per_user' => 'INT NOT NULL DEFAULT 5',
    ];
    foreach ($cols as $col => $def) {
        $row = db_fetch_one($db, 'SELECT COUNT(*) AS c FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :t AND COLUMN_NAME = :c', [
            'db' => $dbName, 't' => 'merchant_profile', 'c' => $col,
        ]);
        if ((int)($row['c'] ?? 0) === 0) {
            $db->exec("ALTER TABLE `merchant_profile` ADD COLUMN `{$col}` {$def}");
        }
    }
}

$profile = db_fetch_one($db, 'SELECT * FROM merchant_profile WHERE merchant_user_id = :id LIMIT 1', ['id' => $merchantId]);

// Handle settings update
if (request_method() === 'POST' && post_string('action') === 'update_referral_settings') {
    $referrerPct = (float)(post_string('referral_referrer_pct') ?: '5');
    $refereePct  = (float)(post_string('referral_referee_pct') ?: '10');
    $maxPerUser  = (int)(post_string('referral_max_per_user') ?: '5');

    $referrerPct = max(0, min(50, $referrerPct));
    $refereePct  = max(0, min(50, $refereePct));
    $maxPerUser  = max(1, min(50, $maxPerUser));

    db_exec($db, 'UPDATE merchant_profile SET referral_referrer_pct = :rr, referral_referee_pct = :re, referral_max_per_user = :mx WHERE merchant_user_id = :id', [
        'rr' => $referrerPct, 're' => $refereePct, 'mx' => $maxPerUser, 'id' => $merchantId,
    ]);

    flash_set('success', 'Referral settings updated');
    redirect_to('merchant/referrals');
}

// Reload profile after possible update
$profile = db_fetch_one($db, 'SELECT * FROM merchant_profile WHERE merchant_user_id = :id LIMIT 1', ['id' => $merchantId]);

// Summary stats
$referralStats = ['total' => 0, 'completed' => 0, 'pending' => 0, 'total_rewards' => 0.00, 'total_discounts' => 0.00, 'buyers_with_codes' => 0];
try {
    $row = db_fetch_one($db, "SELECT COUNT(*) AS total, COALESCE(SUM(status='completed'),0) AS completed, COALESCE(SUM(status='pending'),0) AS pending, COALESCE(SUM(reward_amount),0) AS total_rewards, COALESCE(SUM(discount_amount),0) AS total_discounts FROM referrals");
    if ($row) {
        $referralStats['total']          = (int)($row['total'] ?? 0);
        $referralStats['completed']      = (int)($row['completed'] ?? 0);
        $referralStats['pending']        = (int)($row['pending'] ?? 0);
        $referralStats['total_rewards']  = round((float)($row['total_rewards'] ?? 0), 2);
        $referralStats['total_discounts']= round((float)($row['total_discounts'] ?? 0), 2);
    }
    $codeRow = db_fetch_one($db, 'SELECT COUNT(*) AS cnt FROM referral_codes');
    $referralStats['buyers_with_codes'] = (int)($codeRow['cnt'] ?? 0);
} catch (Throwable $e) {
    // tables may not exist
}

// All referrals with referrer & referee details
$referralList = [];
try {
    $referralList = db_fetch_all($db, "
        SELECT r.*,
               referrer.full_name AS referrer_name, referrer.phone AS referrer_phone, referrer.email AS referrer_email,
               referee.full_name  AS referee_name,  referee.phone  AS referee_phone,  referee.email  AS referee_email,
               rc.code AS referrer_code
        FROM referrals r
        JOIN users referrer ON referrer.id = r.referrer_id
        JOIN users referee  ON referee.id  = r.referee_id
        LEFT JOIN referral_codes rc ON rc.user_id = r.referrer_id
        ORDER BY r.created_at DESC
    ") ?: [];
} catch (Throwable $e) {
    $referralList = [];
}

// Top referrers
$topReferrers = [];
try {
    $topReferrers = db_fetch_all($db, "
        SELECT r.referrer_id, u.full_name, u.phone, rc.code,
               COUNT(*) AS total_referrals,
               SUM(r.status='completed') AS completed,
               COALESCE(SUM(r.reward_amount),0) AS total_earned
        FROM referrals r
        JOIN users u ON u.id = r.referrer_id
        LEFT JOIN referral_codes rc ON rc.user_id = r.referrer_id
        GROUP BY r.referrer_id, u.full_name, u.phone, rc.code
        ORDER BY total_referrals DESC
        LIMIT 10
    ") ?: [];
} catch (Throwable $e) {
    $topReferrers = [];
}

$content = function () use ($profile, $referralStats, $referralList, $topReferrers) {
    $success = flash_get('success');
    $error   = flash_get('error');
    ?>
    <div class="container py-4">
      <div class="d-flex align-items-center justify-content-between mb-4">
        <h1 class="h5 mb-0 d-flex align-items-center gap-2"><i data-lucide="gift" class="mm-icon"></i> Referral Program</h1>
        <a class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1" href="?p=merchant/dashboard"><i data-lucide="arrow-left" class="mm-icon"></i> Dashboard</a>
      </div>

      <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show"><?= e($success) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
      <?php endif; ?>
      <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show"><?= e($error) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
      <?php endif; ?>

      <!-- Stats Cards -->
      <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
          <div class="bg-white border rounded-4 p-3 text-center mm-card">
            <div class="h4 mb-0 text-primary"><?= (int)$referralStats['total'] ?></div>
            <div class="text-muted small">Total Referrals</div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="bg-white border rounded-4 p-3 text-center mm-card">
            <div class="h4 mb-0 text-success"><?= (int)$referralStats['completed'] ?></div>
            <div class="text-muted small">Completed</div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="bg-white border rounded-4 p-3 text-center mm-card">
            <div class="h4 mb-0 text-warning"><?= (int)$referralStats['pending'] ?></div>
            <div class="text-muted small">Pending</div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="bg-white border rounded-4 p-3 text-center mm-card">
            <div class="h4 mb-0 text-success">₹<?= number_format($referralStats['total_rewards'], 2) ?></div>
            <div class="text-muted small">Total Rewards Paid</div>
          </div>
        </div>
      </div>

      <div class="row g-3">
        <!-- Settings Card -->
        <div class="col-12 col-lg-4">
          <div class="bg-white border rounded-4 p-3 mm-card">
            <div class="fw-semibold d-flex align-items-center gap-2 mb-3"><i data-lucide="sliders" class="mm-icon"></i> Referral Settings</div>
            <form method="post">
              <input type="hidden" name="action" value="update_referral_settings">

              <div class="mb-3">
                <label class="form-label small">Referrer Reward (%)</label>
                <input class="form-control" type="number" name="referral_referrer_pct" value="<?= e((string)($profile['referral_referrer_pct'] ?? '5.00')) ?>" min="0" max="50" step="0.5">
                <div class="text-muted small mt-1">% of order total credited to referrer's wallet when referee's first order is delivered</div>
              </div>

              <div class="mb-3">
                <label class="form-label small">Referee Discount (%)</label>
                <input class="form-control" type="number" name="referral_referee_pct" value="<?= e((string)($profile['referral_referee_pct'] ?? '10.00')) ?>" min="0" max="50" step="0.5">
                <div class="text-muted small mt-1">% discount on the referred buyer's first order</div>
              </div>

              <div class="mb-3">
                <label class="form-label small">Max Referrals Per User</label>
                <input class="form-control" type="number" name="referral_max_per_user" value="<?= e((string)($profile['referral_max_per_user'] ?? '5')) ?>" min="1" max="50" step="1">
                <div class="text-muted small mt-1">Maximum people each buyer can refer</div>
              </div>

              <button class="btn btn-mm w-100" type="submit">Save Settings</button>
            </form>

            <hr>
            <div class="small text-muted">
              <div class="d-flex justify-content-between mb-1">
                <span>Buyers with referral codes</span>
                <span class="fw-semibold"><?= (int)$referralStats['buyers_with_codes'] ?></span>
              </div>
              <div class="d-flex justify-content-between">
                <span>Total discounts given</span>
                <span class="fw-semibold">₹<?= number_format($referralStats['total_discounts'], 2) ?></span>
              </div>
            </div>
          </div>
        </div>

        <!-- Main Content -->
        <div class="col-12 col-lg-8">
          <!-- Top Referrers -->
          <?php if (!empty($topReferrers)): ?>
          <div class="bg-white border rounded-4 p-3 mm-card mb-3">
            <div class="fw-semibold d-flex align-items-center gap-2 mb-3"><i data-lucide="trophy" class="mm-icon"></i> Top Referrers</div>
            <div class="table-responsive">
              <table class="table table-sm table-hover mb-0">
                <thead>
                  <tr>
                    <th class="small text-muted">#</th>
                    <th class="small text-muted">Buyer</th>
                    <th class="small text-muted">Code</th>
                    <th class="small text-muted text-center">Referrals</th>
                    <th class="small text-muted text-center">Completed</th>
                    <th class="small text-muted text-end">Earned</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($topReferrers as $i => $tr): ?>
                    <tr>
                      <td class="small"><?= $i + 1 ?></td>
                      <td class="small"><?= e((string)$tr['full_name']) ?><br><span class="text-muted"><?= e((string)$tr['phone']) ?></span></td>
                      <td><span class="badge bg-light text-dark border font-monospace"><?= e((string)$tr['code']) ?></span></td>
                      <td class="small text-center"><?= (int)$tr['total_referrals'] ?></td>
                      <td class="small text-center"><?= (int)$tr['completed'] ?></td>
                      <td class="small text-end fw-semibold text-success">₹<?= number_format((float)$tr['total_earned'], 2) ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
          <?php endif; ?>

          <!-- All Referrals -->
          <div class="bg-white border rounded-4 p-3 mm-card">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
              <div class="fw-semibold d-flex align-items-center gap-2"><i data-lucide="users" class="mm-icon"></i> All Referrals</div>
              <span class="badge text-bg-light border"><?= count($referralList) ?></span>
            </div>

            <?php if (!empty($referralList)): ?>
              <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                  <thead>
                    <tr>
                      <th class="small text-muted">Referrer</th>
                      <th class="small text-muted">Code</th>
                      <th class="small text-muted">Referred Buyer</th>
                      <th class="small text-muted">Status</th>
                      <th class="small text-muted">Discount</th>
                      <th class="small text-muted">Reward</th>
                      <th class="small text-muted">Date</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($referralList as $ref): ?>
                      <tr>
                        <td class="small">
                          <?= e((string)$ref['referrer_name']) ?>
                          <br><span class="text-muted"><?= e((string)$ref['referrer_phone']) ?></span>
                        </td>
                        <td><span class="badge bg-light text-dark border font-monospace small"><?= e((string)($ref['referrer_code'] ?? '')) ?></span></td>
                        <td class="small">
                          <?= e((string)$ref['referee_name']) ?>
                          <br><span class="text-muted"><?= e((string)$ref['referee_phone']) ?></span>
                        </td>
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
                          <?php if ($ref['discount_amount']): ?>
                            ₹<?= number_format((float)$ref['discount_amount'], 2) ?>
                            <span class="text-muted">(<?= number_format((float)$ref['referee_discount_pct'], 1) ?>%)</span>
                          <?php else: ?>
                            <span class="text-muted"><?= number_format((float)$ref['referee_discount_pct'], 1) ?>%</span>
                          <?php endif; ?>
                        </td>
                        <td class="small">
                          <?php if ($ref['status'] === 'completed' && $ref['reward_amount']): ?>
                            <span class="text-success fw-semibold">₹<?= number_format((float)$ref['reward_amount'], 2) ?></span>
                          <?php else: ?>
                            <span class="text-muted">--</span>
                          <?php endif; ?>
                        </td>
                        <td class="small text-muted text-nowrap"><?= date('M d, Y', strtotime((string)$ref['created_at'])) ?></td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php else: ?>
              <div class="text-muted small text-center py-4">
                <i data-lucide="inbox" class="mm-icon mb-2" style="width:32px;height:32px;"></i>
                <div>No referrals yet. Buyers can start referring friends from their Refer & Earn page.</div>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <script>if (window.lucide) window.lucide.createIcons();</script>
    <?php
};

require __DIR__ . '/../../views/layout.php';
