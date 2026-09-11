<?php

declare(strict_types=1);

auth_require_role('super_admin');

$title = 'Logistics Vendor Onboard';

$merchantId = auth_user_id();

$dbNameRow = db_fetch_one($db, 'SELECT DATABASE() AS dbname');
$dbName = (string)($dbNameRow['dbname'] ?? '');

$ensureColumn = function (string $column, string $definition) use ($db, $dbName): void {
    if ($dbName === '') return;
    $row = db_fetch_one($db, 'SELECT COUNT(*) AS c FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :t AND COLUMN_NAME = :c', [
        'db' => $dbName,
        't' => 'merchant_profile',
        'c' => $column,
    ]);
    $exists = (int)($row['c'] ?? 0) > 0;
    if ($exists) return;
    $db->exec("ALTER TABLE `merchant_profile` ADD COLUMN `{$column}` {$definition}");
};

$ensureColumn('logistics_vendor_id', 'INT NULL');
$ensureColumn('logistics_service_charge', 'DECIMAL(10,2) NULL DEFAULT 0');
$ensureColumn('logistics_service_charge_type', "ENUM('flat','percent') NULL DEFAULT 'flat'");
$ensureColumn('logistics_onboarded_at', 'DATETIME NULL');

$profile = db_fetch_one($db, 'SELECT * FROM merchant_profile WHERE merchant_user_id = :id LIMIT 1', ['id' => $merchantId]);
if (!$profile) {
    db_exec($db, 'INSERT INTO merchant_profile (merchant_user_id, created_at) VALUES (:id, NOW())', ['id' => $merchantId]);
    $profile = db_fetch_one($db, 'SELECT * FROM merchant_profile WHERE merchant_user_id = :id LIMIT 1', ['id' => $merchantId]);
}

if (request_method() === 'POST') {
    $action = post_string('action');

    if ($action === 'link_logistics') {
      $linkedLogisticsId = post_int('linked_logistics_id');
      $linkedLogisticsEmail = post_string('linked_logistics_email');
      $logisticsCharge = post_string('logistics_service_charge');
      $logisticsChargeType = post_string('logistics_service_charge_type') ?: 'flat';

      // If email provided, resolve to user id
      if ($linkedLogisticsEmail !== '') {
        $u = db_fetch_one($db, 'SELECT id FROM users WHERE email = :email AND role = :role LIMIT 1', ['email' => $linkedLogisticsEmail, 'role' => 'logistics']);
        if ($u) {
          $linkedLogisticsId = (int)$u['id'];
        } else {
          flash_set('error', 'No logistics user found with that email');
          redirect_to('merchant/logistics-onboard');
        }
      }

      if ($linkedLogisticsId) {
        // ensure logistics_profile exists for the user
        try {
          $exists = db_fetch_one($db, 'SELECT id FROM logistics_profile WHERE logistics_user_id = :uid LIMIT 1', ['uid' => $linkedLogisticsId]);
          if (!$exists) {
            db_exec($db, 'INSERT INTO logistics_profile (logistics_user_id, created_at) VALUES (:uid, NOW())', ['uid' => $linkedLogisticsId]);
          }
        } catch (Throwable $t) {
          // ignore if table missing
        }

        db_exec($db, 'UPDATE merchant_profile SET logistics_vendor_id = :lid, logistics_service_charge = :charge, logistics_service_charge_type = :ctype, logistics_onboarded_at = COALESCE(logistics_onboarded_at, NOW()) WHERE merchant_user_id = :id', [
          'lid' => $linkedLogisticsId,
          'charge' => $logisticsCharge ?: 0,
          'ctype' => in_array($logisticsChargeType, ['flat','percent'], true) ? $logisticsChargeType : 'flat',
          'id' => $merchantId,
        ]);

        flash_set('success', 'Linked to logistics vendor ID ' . (int)$linkedLogisticsId);
        // expose linked id for view
        flash_set('linked_logistics_id', (string)$linkedLogisticsId);
      } else {
        flash_set('error', 'Please provide a logistics user ID or email to link');
      }
      redirect_to('merchant/logistics-onboard');
    }

    if ($action === 'create_logistics') {
        $logCompany = post_string('log_company');
        $logEmail = post_string('log_email');
        $logPhone = post_string('log_phone');
        $logVehicle = post_string('log_vehicle');
        $logServiceCharge = post_string('log_service_charge');
        $logServiceChargeType = post_string('log_service_charge_type') ?: 'flat';

        if ($logEmail === '') {
            flash_set('error', 'Please provide an email for the logistics vendor');
            redirect_to('merchant/logistics-onboard');
        }

        try {
            $plainPassword = bin2hex(random_bytes(4));
            $hash = password_hash($plainPassword, PASSWORD_DEFAULT);
            db_exec($db, 'INSERT INTO users (role, full_name, phone, email, password_hash, status, created_at) VALUES (:role, :full_name, :phone, :email, :password_hash, :status, NOW())', [
                'role' => 'logistics',
                'full_name' => $logCompany ?: 'Logistics Vendor',
                'phone' => $logPhone,
                'email' => $logEmail,
                'password_hash' => $hash,
                'status' => 'active',
            ]);
            $newLogId = (int)$db->lastInsertId();

            try {
                db_exec($db, 'INSERT INTO logistics_profile (logistics_user_id, company_name, vehicle_no, phone, created_at) VALUES (:user_id, :company, :vehicle, :phone, NOW())', [
                    'user_id' => $newLogId,
                    'company' => $logCompany,
                    'vehicle' => $logVehicle,
                    'phone' => $logPhone,
                ]);
            } catch (Throwable $t) {
                // ignore if table missing
            }

            db_exec($db, 'UPDATE merchant_profile SET logistics_vendor_id = :lid, logistics_service_charge = :charge, logistics_service_charge_type = :ctype, logistics_onboarded_at = COALESCE(logistics_onboarded_at, NOW()) WHERE merchant_user_id = :id', [
                'lid' => $newLogId,
                'charge' => $logServiceCharge ?: 0,
                'ctype' => in_array($logServiceChargeType, ['flat','percent'], true) ? $logServiceChargeType : 'flat',
                'id' => $merchantId,
            ]);

            // Save credentials separately so merchant can copy/share them
            $creds = json_encode(['id' => $newLogId, 'email' => $logEmail, 'password' => $plainPassword]);
            flash_set('logistics_credentials', $creds);

            // Optionally email credentials to the logistics contact
            $emailCreds = (bool)($_POST['email_credentials'] ?? false);
            $mailSent = null;
            if ($emailCreds && filter_var($logEmail, FILTER_VALIDATE_EMAIL)) {
              $from = 'no-reply@localhost';
              if (!empty($GLOBALS['config']['app']['email_from'] ?? '')) {
                $from = $GLOBALS['config']['app']['email_from'];
              }
              $subject = 'Your Logistics Account for Manikya Market';
              $message = "Hello,\n\nA logistics account has been created for you.\n\nUser ID: $newLogId\nEmail: $logEmail\nPassword: $plainPassword\n\nPlease login at: " . rtrim(app_base_path($GLOBALS['config'] ?? []), '/') . "/index.php?p=logistics/login\n\nRegards,\nManikya Market";
              $headers = 'From: ' . $from . "\r\n" . 'Content-Type: text/plain; charset=UTF-8';
              $mailSent = @mail($logEmail, $subject, $message, $headers);
              if ($mailSent) {
                flash_set('success', 'Created logistics user ' . e($logEmail) . ' and emailed credentials.');
              } else {
                flash_set('success', 'Created logistics user ' . e($logEmail) . '. Email failed — copy credentials below.');
              }
            } else {
              flash_set('success', 'Created logistics user ' . e($logEmail) . '. Credentials are available below.');
            }

            redirect_to('merchant/logistics-onboard');
        } catch (Throwable $t) {
            flash_set('error', 'Failed to create logistics user: ' . $t->getMessage());
            redirect_to('merchant/logistics-onboard');
        }
    }
}

$content = function () use ($profile) {
  $success = flash_get('success');
  $error = flash_get('error');
  $credsJson = flash_get('logistics_credentials');
  $createdCreds = null;
  if ($credsJson) {
    $createdCreds = json_decode($credsJson, true);
  }
  $linkedIdFlash = flash_get('linked_logistics_id');
    ?>
    <div class="container py-4" style="max-width: 980px;">
      <div class="d-flex align-items-center justify-content-between mb-3">
        <h1 class="h5 mb-0 d-flex align-items-center gap-2"><i data-lucide="truck" class="mm-icon"></i> Logistics Vendor Onboard</h1>
        <a class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1" href="?p=merchant/dashboard"><i data-lucide="arrow-left" class="mm-icon"></i> Back</a>
      </div>

      <?php if ($success): ?>
        <div class="alert alert-success"><?= e($success) ?></div>
      <?php endif; ?>

      <?php if ($error): ?>
        <div class="alert alert-danger"><?= e($error) ?></div>
      <?php endif; ?>

      <?php if ($createdCreds): ?>
        <div class="alert alert-info">
          <strong>Logistics Credentials</strong>
          <div class="mt-2">
            <div><strong>User ID:</strong> <span id="cred-id"><?= e((string)($createdCreds['id'] ?? '')) ?></span>
              <button class="btn btn-sm btn-outline-secondary ms-2" onclick="copyText('#cred-id')">Copy</button>
            </div>
            <div class="mt-1"><strong>Email:</strong> <span id="cred-email"><?= e((string)($createdCreds['email'] ?? '')) ?></span>
              <button class="btn btn-sm btn-outline-secondary ms-2" onclick="copyText('#cred-email')">Copy</button>
            </div>
            <div class="mt-1"><strong>Password:</strong> <span id="cred-pass"><?= e((string)($createdCreds['password'] ?? '')) ?></span>
              <button class="btn btn-sm btn-outline-secondary ms-2" onclick="copyText('#cred-pass')">Copy</button>
            </div>
            <div class="mt-2 small text-muted">Share these credentials with your logistics team. They can login at <code>?p=logistics/login</code></div>
          </div>
        </div>
      <?php endif; ?>

      <?php if ($linkedIdFlash): ?>
        <div class="alert alert-success">Linked Logistics User ID: <strong><?= e($linkedIdFlash) ?></strong></div>
      <?php endif; ?>

      <div class="bg-white border rounded-4 p-4 mm-card mt-0">
        <div class="d-flex justify-content-between align-items-center">
          <h3 class="h6 mb-3">Link Existing Logistics Vendor</h3>
          <a href="?p=merchant/logistics-vendors" class="small">View all vendors</a>
        </div>
        <form method="post" class="row g-3">
          <input type="hidden" name="action" value="link_logistics">
            <div class="col-md-4">
              <label class="form-label">Existing Logistics User ID</label>
              <input class="form-control" name="linked_logistics_id" type="number" value="<?= e((string)($profile['logistics_vendor_id'] ?? '')) ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label">Or Logistics Email</label>
              <input class="form-control" name="linked_logistics_email" type="email" placeholder="logistics@example.com">
              <small class="text-muted">Provide either user ID or email to auto-resolve</small>
            </div>
          <div class="col-md-4">
            <label class="form-label">Service Charge</label>
            <input class="form-control" name="logistics_service_charge" value="<?= e((string)($profile['logistics_service_charge'] ?? '0')) ?>">
          </div>
          <div class="col-md-4">
            <label class="form-label">Charge Type</label>
            <select class="form-select" name="logistics_service_charge_type">
              <option value="flat" <?= (($profile['logistics_service_charge_type'] ?? 'flat') === 'flat') ? 'selected' : '' ?>>Flat</option>
              <option value="percent" <?= (($profile['logistics_service_charge_type'] ?? '') === 'percent') ? 'selected' : '' ?>>Percent</option>
            </select>
          </div>
          <div class="col-12 d-grid mt-1">
            <button class="btn btn-outline-primary" type="submit">Link Logistics Vendor</button>
          </div>
        </form>
      </div>

      <div class="bg-white border rounded-4 p-4 mm-card mt-3">
        <h3 class="h6 mb-3">Create New Logistics Vendor</h3>
        <form method="post" class="row g-3">
          <input type="hidden" name="action" value="create_logistics">
          <div class="col-md-4">
            <label class="form-label">Company</label>
            <input class="form-control" name="log_company">
          </div>
          <div class="col-md-4">
            <label class="form-label">Email</label>
            <input class="form-control" name="log_email" type="email">
          </div>
          <div class="col-md-4">
            <label class="form-label">Phone</label>
            <input class="form-control" name="log_phone">
          </div>
          <div class="col-md-4">
            <label class="form-label">Vehicle No (optional)</label>
            <input class="form-control" name="log_vehicle">
          </div>
          <div class="col-md-4">
            <label class="form-label">Service Charge</label>
            <input class="form-control" name="log_service_charge">
          </div>
          <div class="col-md-4">
            <label class="form-label">Charge Type</label>
            <select class="form-select" name="log_service_charge_type">
              <option value="flat">Flat</option>
              <option value="percent">Percent</option>
            </select>
          </div>
          <div class="col-12">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" value="1" id="email_creds" name="email_credentials">
              <label class="form-check-label small" for="email_creds">Email these credentials to the logistics contact</label>
            </div>
          </div>
          <div class="col-12 d-grid mt-1">
            <button class="btn btn-mm" type="submit">Create Logistics Vendor & Link</button>
          </div>
        </form>
      </div>
    </div>
    ?>
    <script>
      function copyText(sel) {
        try {
          const t = document.querySelector(sel).innerText;
          navigator.clipboard.writeText(t).then(()=>{ alert('Copied to clipboard'); });
        } catch (e) { alert('Copy failed'); }
      }
    </script>
    <?php
};

require __DIR__ . '/../../views/layout.php';
