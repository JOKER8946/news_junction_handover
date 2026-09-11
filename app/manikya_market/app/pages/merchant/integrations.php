<?php

declare(strict_types=1);

auth_require_role('merchant');

$title = 'Integrations';

$platform = function_exists('platform_settings_get') ? platform_settings_get($db) : null;

$status = [
    'razorpay'  => !empty($platform['razorpay_key_id']) && !empty($platform['razorpay_key_secret']),
    'delhivery' => !empty($platform['delhivery_api_token']),
    'smtp'      => !empty($platform['smtp_username']) && !empty($platform['smtp_password']),
];

$content = function () use ($status) {
    ?>
    <div class="container py-4" style="max-width: 760px;">
      <div class="d-flex align-items-center justify-content-between mb-3">
        <h1 class="h4 mb-0 d-flex align-items-center gap-2"><i data-lucide="plug" class="mm-icon"></i> Integrations</h1>
      </div>

      <div class="alert alert-info d-flex align-items-start gap-2">
        <i data-lucide="info" class="mt-1" style="width:18px;height:18px;flex-shrink:0;"></i>
        <div>
          <strong>Manikya Market handles all integrations centrally.</strong>
          You don't need to bring your own Razorpay, Delhivery, or email service —
          the platform takes care of buyer payments, shipping pickups, and order emails.
        </div>
      </div>

      <div class="bg-white border rounded-4 p-4 mm-card">
        <h2 class="h6 text-uppercase text-muted small fw-semibold mb-3">Platform-managed services</h2>
        <ul class="list-unstyled mb-0">
          <?php
          $rows = [
              ['Razorpay (buyer payments)',
               'Buyers pay Manikya Market — your earnings appear in your wallet, settled via Payouts.',
               $status['razorpay']],
              ['Delhivery (courier pickup & delivery)',
               'Shipments go through Manikya Market\'s Delhivery account from your warehouse pickup address.',
               $status['delhivery']],
              ['SMTP (order emails)',
               'Order placed / shipped / delivered emails go out from Manikya Market\'s mailbox.',
               $status['smtp']],
          ];
          foreach ($rows as [$label, $desc, $ready]):
          ?>
            <li class="d-flex align-items-start gap-3 py-3 border-bottom">
              <div class="mt-1">
                <?php if ($ready): ?>
                  <i data-lucide="check-circle" style="width:22px;height:22px;color:#15803d;"></i>
                <?php else: ?>
                  <i data-lucide="alert-circle" style="width:22px;height:22px;color:#b45309;"></i>
                <?php endif; ?>
              </div>
              <div class="flex-grow-1">
                <div class="fw-semibold"><?= e($label) ?></div>
                <div class="small text-muted"><?= e($desc) ?></div>
              </div>
              <div>
                <span class="badge bg-<?= $ready ? 'success' : 'warning' ?>">
                  <?= $ready ? 'Live' : 'Awaiting platform setup' ?>
                </span>
              </div>
            </li>
          <?php endforeach; ?>
        </ul>

        <div class="text-muted small mt-3">
          <i data-lucide="lock" style="width:13px;height:13px;"></i>
          These are configured by the platform admin — they're the same for every merchant on Manikya Market.
        </div>
      </div>

      <div class="text-center mt-3">
        <a class="btn btn-outline-secondary" href="?p=merchant/settings">
          <i data-lucide="arrow-left" style="width:14px;height:14px;"></i>
          Back to My Business
        </a>
      </div>
    </div>

    <script>if (window.lucide) window.lucide.createIcons();</script>
    <?php
};

require __DIR__ . '/../../views/layout.php';
