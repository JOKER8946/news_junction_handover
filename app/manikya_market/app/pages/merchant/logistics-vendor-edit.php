<?php

declare(strict_types=1);

auth_require_role('super_admin');

$title = 'Edit Logistics Vendor';

$merchantId = auth_user_id();

$vendorId = (int)($_GET['id'] ?? post_int('id'));
if ($vendorId <= 0) {
    http_response_code(404);
    $content = function () {
        echo '<div class="container py-4"><h1 class="h4">Vendor not found</h1></div>';
    };
    require __DIR__ . '/../../views/layout.php';
    exit;
}

$vendor = db_fetch_one($db, '
    SELECT u.id AS user_id, u.email, u.full_name, u.phone AS user_phone, u.created_at AS user_created_at, lp.company_name, lp.vehicle_no, lp.phone AS lp_phone
    FROM users u
    LEFT JOIN logistics_profile lp ON lp.logistics_user_id = u.id
    WHERE u.id = :id AND u.role = :role
    LIMIT 1
', ['id' => $vendorId, 'role' => 'logistics']);

if (!$vendor) {
    http_response_code(404);
    $content = function () {
        echo '<div class="container py-4"><h1 class="h4">Vendor not found</h1></div>';
    };
    require __DIR__ . '/../../views/layout.php';
    exit;
}

$mp = null;
try {
  $mp = db_fetch_one($db, 'SELECT logistics_service_charge, logistics_service_charge_type, logistics_onboarded_at FROM merchant_profile WHERE merchant_user_id = :mid AND logistics_vendor_id = :vid LIMIT 1', ['mid' => $merchantId, 'vid' => $vendorId]);
} catch (Throwable $t) {
  $mp = null;
}

$error = null;
$success = null;

if (request_method() === 'POST') {
    $company = post_string('company_name');
    $vehicle = post_string('vehicle_no');
    $lp_phone = post_string('lp_phone');
    $agreed = post_string('agreed_charge');
    $charge_type = post_string('charge_type');

    try {
        // Update logistics_profile
        db_exec($db, 'INSERT INTO logistics_profile (logistics_user_id, company_name, vehicle_no, phone, created_at) VALUES (:uid, :company, :vehicle, :phone, NOW()) ON DUPLICATE KEY UPDATE company_name = VALUES(company_name), vehicle_no = VALUES(vehicle_no), phone = VALUES(phone)', [
            'uid' => $vendorId,
            'company' => $company,
            'vehicle' => $vehicle,
            'phone' => $lp_phone,
        ]);

        // Ensure merchant_profile has columns for onboarding timestamp and charge fields
        ensure_table_column($db, 'merchant_profile', 'logistics_onboarded_at', 'DATETIME NULL');
        ensure_table_column($db, 'merchant_profile', 'logistics_service_charge', 'DECIMAL(10,2) NULL');
        ensure_table_column($db, 'merchant_profile', 'logistics_service_charge_type', 'VARCHAR(30) NULL');

        // Upsert merchant_profile row for agreed charge
        $mp = db_fetch_one($db, 'SELECT id FROM merchant_profile WHERE merchant_user_id = :mid AND logistics_vendor_id = :vid LIMIT 1', ['mid' => $merchantId, 'vid' => $vendorId]);
        if ($mp) {
            db_exec($db, 'UPDATE merchant_profile SET logistics_service_charge = :charge, logistics_service_charge_type = :ctype, logistics_onboarded_at = COALESCE(logistics_onboarded_at, NOW()) WHERE merchant_user_id = :mid AND logistics_vendor_id = :vid', [
                'charge' => $agreed === '' ? null : $agreed,
                'ctype' => $charge_type === '' ? null : $charge_type,
                'mid' => $merchantId,
                'vid' => $vendorId,
            ]);
        } else {
            db_exec($db, 'INSERT INTO merchant_profile (merchant_user_id, logistics_vendor_id, logistics_service_charge, logistics_service_charge_type, logistics_onboarded_at, created_at) VALUES (:mid, :vid, :charge, :ctype, NOW(), NOW())', [
                'mid' => $merchantId,
                'vid' => $vendorId,
                'charge' => $agreed === '' ? null : $agreed,
                'ctype' => $charge_type === '' ? null : $charge_type,
            ]);
        }

        $success = 'Vendor details updated.';
        // reload vendor data
        $vendor = db_fetch_one($db, '
            SELECT u.id AS user_id, u.email, u.full_name, u.phone AS user_phone, u.created_at AS user_created_at, lp.company_name, lp.vehicle_no, lp.phone AS lp_phone
            FROM users u
            LEFT JOIN logistics_profile lp ON lp.logistics_user_id = u.id
            WHERE u.id = :id AND u.role = :role
            LIMIT 1
        ', ['id' => $vendorId, 'role' => 'logistics']);
        try {
          $mp = db_fetch_one($db, 'SELECT logistics_service_charge, logistics_service_charge_type, logistics_onboarded_at FROM merchant_profile WHERE merchant_user_id = :mid AND logistics_vendor_id = :vid LIMIT 1', ['mid' => $merchantId, 'vid' => $vendorId]);
        } catch (Throwable $t) {
          $mp = null;
        }
    } catch (Throwable $t) {
        $error = 'Failed to update vendor: ' . $t->getMessage();
    }
}

$content = function () use ($vendor, $vendorId, $error, $success, $mp) {
    ?>
    <div class="container py-4">
      <div class="d-flex align-items-center justify-content-between mb-3">
        <h1 class="h5 mb-0">Edit Logistics Vendor</h1>
        <a class="btn btn-outline-secondary btn-sm" href="?p=merchant/logistics-vendors">Back</a>
      </div>

      <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
      <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>

      <div class="bg-white border rounded-4 p-3 mm-card">
        <form method="post">
          <input type="hidden" name="id" value="<?= (int)$vendorId ?>">
          <div class="mb-3">
            <label class="form-label">Vendor</label>
            <div class="fw-semibold"><?= e((string)$vendor['full_name']) ?></div>
            <div class="text-muted small"><?= e((string)$vendor['email']) ?> • <?= e((string)$vendor['user_phone']) ?></div>
            <div class="text-muted small">User created: <?= e((string)$vendor['user_created_at']) ?></div>
            <?php if (!empty($mp['logistics_onboarded_at'])): ?>
              <div class="text-muted small">Onboarded: <?= date('M d, Y H:i', strtotime((string)$mp['logistics_onboarded_at'])) ?></div>
            <?php endif; ?>
          </div>

          <div class="mb-3">
            <label class="form-label">Company name</label>
            <input class="form-control" name="company_name" value="<?= e((string)$vendor['company_name']) ?>">
          </div>

          <div class="mb-3">
            <label class="form-label">Vehicle no</label>
            <input class="form-control" name="vehicle_no" value="<?= e((string)$vendor['vehicle_no']) ?>">
          </div>

          <div class="mb-3">
            <label class="form-label">Vendor phone</label>
            <input class="form-control" name="lp_phone" value="<?= e((string)$vendor['lp_phone']) ?>">
          </div>

          <hr>

          <div class="mb-3">
            <label class="form-label">Agreed service charge</label>
            <div class="input-group">
              <input class="form-control" name="agreed_charge" placeholder="Amount (eg. 50.00)" value="<?= e((string)($mp['logistics_service_charge'] ?? '')) ?>">
              <select class="form-select" name="charge_type">
                <option value="">Select</option>
                <option value="flat" <?= isset($mp['logistics_service_charge_type']) && $mp['logistics_service_charge_type'] === 'flat' ? 'selected' : '' ?>>Flat</option>
                <option value="per_kg" <?= isset($mp['logistics_service_charge_type']) && $mp['logistics_service_charge_type'] === 'per_kg' ? 'selected' : '' ?>>Per kg</option>
              </select>
            </div>
            <div class="form-text">Set the rate you agreed with this vendor for shipping.</div>
          </div>

          <div class="d-flex gap-2">
            <button class="btn btn-primary" type="submit">Save</button>
            <a class="btn btn-outline-secondary" href="?p=merchant/logistics-vendors">Cancel</a>
          </div>
        </form>
      </div>
    </div>
    <?php
};

require __DIR__ . '/../../views/layout.php';

?>
