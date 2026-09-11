<?php

declare(strict_types=1);

auth_require_role('merchant');

$title = 'My Business';
$merchantId = (int)auth_user_id();
$basePath   = app_base_path($config);

// Ensure all the merchant_profile columns we read/write exist (defensive
// for older installs — manikya_market v2 already has them).
$ensureColumn = function (string $column, string $definition) use ($db): void {
    try {
        $row = db_fetch_one($db,
            "SELECT COUNT(*) AS c FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'merchant_profile' AND COLUMN_NAME = :c",
            ['c' => $column]
        );
        if ((int)($row['c'] ?? 0) === 0) {
            $db->exec("ALTER TABLE merchant_profile ADD COLUMN `{$column}` {$definition}");
        }
    } catch (Throwable $t) { /* ignore */ }
};
foreach ([
    ['business_gstin',  'VARCHAR(20) NULL'],
    ['business_pan',    'VARCHAR(20) NULL'],
    ['business_state',  'VARCHAR(80) NULL'],
    ['business_pincode','VARCHAR(12) NULL'],
    ['business_bank_name',    'VARCHAR(120) NULL'],
    ['business_bank_account', 'VARCHAR(50) NULL'],
    ['business_bank_ifsc',    'VARCHAR(20) NULL'],
    ['business_logo_path',    'VARCHAR(255) NULL'],
    ['delhivery_warehouse_name',    'VARCHAR(150) NULL'],
    ['delhivery_warehouse_address', 'TEXT NULL'],
    ['delhivery_warehouse_city',    'VARCHAR(80) NULL'],
    ['delhivery_warehouse_state',   'VARCHAR(80) NULL'],
    ['delhivery_warehouse_pincode', 'VARCHAR(12) NULL'],
    ['delhivery_warehouse_phone',   'VARCHAR(30) NULL'],
] as [$col, $def]) {
    $ensureColumn($col, $def);
}

$profile = db_fetch_one($db, 'SELECT * FROM merchant_profile WHERE merchant_user_id = :id LIMIT 1', ['id' => $merchantId]);
if (!$profile) {
    db_exec($db, 'INSERT INTO merchant_profile (merchant_user_id, created_at) VALUES (:id, NOW())', ['id' => $merchantId]);
    $profile = db_fetch_one($db, 'SELECT * FROM merchant_profile WHERE merchant_user_id = :id LIMIT 1', ['id' => $merchantId]);
}

if (request_method() === 'POST') {
    $businessName        = post_string('business_name');
    $businessAddress     = post_string('business_address');
    $businessGstin       = strtoupper(post_string('business_gstin'));
    $businessPan         = strtoupper(post_string('business_pan'));
    $businessState       = post_string('business_state');
    $businessPincode     = post_string('business_pincode');
    $businessBankName    = post_string('business_bank_name');
    $businessBankAccount = post_string('business_bank_account');
    $businessBankIfsc    = strtoupper(post_string('business_bank_ifsc'));
    $whName              = post_string('delhivery_warehouse_name');
    $whAddress           = post_string('delhivery_warehouse_address');
    $whCity              = post_string('delhivery_warehouse_city');
    $whState             = post_string('delhivery_warehouse_state');
    $whPincode           = post_string('delhivery_warehouse_pincode');
    $whPhone             = post_string('delhivery_warehouse_phone');

    $logoPath = (string)($profile['business_logo_path'] ?? '');

    if (isset($_FILES['business_logo']) && (int)($_FILES['business_logo']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
        $tmp  = (string)$_FILES['business_logo']['tmp_name'];
        $orig = (string)$_FILES['business_logo']['name'];
        $size = (int)$_FILES['business_logo']['size'];
        $ext  = strtolower(pathinfo($orig, PATHINFO_EXTENSION));

        if ($size > 2 * 1024 * 1024) {
            flash_set('error', 'Logo must be ≤ 2 MB.');
            redirect_to('merchant/settings');
        }
        if (!in_array($ext, ['jpg','jpeg','png','webp'], true)) {
            flash_set('error', 'Logo must be PNG, JPG, or WEBP.');
            redirect_to('merchant/settings');
        }
        $uploadDir = __DIR__ . '/../../../uploads/company';
        if (!is_dir($uploadDir)) @mkdir($uploadDir, 0777, true);
        $newName = 'company_logo_' . date('Ymd_His') . '_' . random_int(1000, 9999) . '.' . $ext;
        if (!@move_uploaded_file($tmp, $uploadDir . '/' . $newName)) {
            flash_set('error', 'Failed to save logo.');
            redirect_to('merchant/settings');
        }
        $logoPath = $basePath . '/uploads/company/' . $newName;
    }

    db_exec($db,
        'UPDATE merchant_profile SET
            business_name = :bn, business_address = :ba,
            business_gstin = :gst, business_pan = :pan,
            business_state = :st, business_pincode = :pin,
            business_bank_name = :bkn, business_bank_account = :bka, business_bank_ifsc = :bki,
            business_logo_path = :logo,
            delhivery_warehouse_name = :whn, delhivery_warehouse_address = :wha,
            delhivery_warehouse_city = :whc, delhivery_warehouse_state = :whst,
            delhivery_warehouse_pincode = :whp, delhivery_warehouse_phone = :whph
         WHERE merchant_user_id = :id',
        [
            'bn' => $businessName, 'ba' => $businessAddress,
            'gst' => $businessGstin ?: null, 'pan' => $businessPan ?: null,
            'st' => $businessState, 'pin' => $businessPincode,
            'bkn' => $businessBankName ?: null, 'bka' => $businessBankAccount ?: null, 'bki' => $businessBankIfsc ?: null,
            'logo' => $logoPath ?: null,
            'whn' => $whName ?: null, 'wha' => $whAddress ?: null,
            'whc' => $whCity ?: null, 'whst' => $whState ?: null,
            'whp' => $whPincode ?: null, 'whph' => $whPhone ?: null,
            'id' => $merchantId,
        ]
    );
    flash_set('success', 'Business profile saved.');
    redirect_to('merchant/settings');
}

$content = function () use ($profile) {
    $success = flash_get('success');
    $error   = flash_get('error');
    ?>
    <div class="container py-4" style="max-width: 920px;">
      <div class="d-flex align-items-center justify-content-between mb-3">
        <h1 class="h4 mb-0 d-flex align-items-center gap-2"><i data-lucide="building-2" class="mm-icon"></i> My Business</h1>
      </div>
      <p class="text-muted small">
        Manage your seller profile. Manikya Market handles payments, shipping, and email for you —
        all you need to provide is your business info, bank account for payouts, and a pickup address.
      </p>

      <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
      <?php if ($error):   ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

      <form method="post" enctype="multipart/form-data" class="bg-white border rounded-4 p-4 mm-card">

        <h2 class="h6 text-uppercase text-muted small fw-semibold mb-3">Business identity</h2>
        <div class="row g-3 mb-4">
          <div class="col-md-8">
            <label class="form-label">Business name *</label>
            <input class="form-control" name="business_name" value="<?= e((string)($profile['business_name'] ?? '')) ?>" required>
            <div class="form-text">Shown to buyers as "Sold by …".</div>
          </div>
          <div class="col-md-4">
            <label class="form-label">Logo <span class="text-muted">(≤ 2 MB)</span></label>
            <?php if (!empty($profile['business_logo_path'])): ?>
              <div class="mb-2"><img src="<?= e((string)$profile['business_logo_path']) ?>" alt="" style="max-height: 56px; border-radius: 6px; border: 1px solid #eee;"></div>
            <?php endif; ?>
            <input class="form-control" type="file" name="business_logo" accept="image/*">
          </div>
          <div class="col-12">
            <label class="form-label">Business address *</label>
            <textarea class="form-control" name="business_address" rows="2" required><?= e((string)($profile['business_address'] ?? '')) ?></textarea>
          </div>
          <div class="col-md-6">
            <label class="form-label">State *</label>
            <input class="form-control" name="business_state" value="<?= e((string)($profile['business_state'] ?? '')) ?>" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Pincode *</label>
            <input class="form-control" name="business_pincode" maxlength="12" value="<?= e((string)($profile['business_pincode'] ?? '')) ?>" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">GSTIN <span class="text-muted">(for invoicing)</span></label>
            <input class="form-control" name="business_gstin" maxlength="20" style="text-transform:uppercase;" value="<?= e((string)($profile['business_gstin'] ?? '')) ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label">PAN <span class="text-muted">(for tax / payouts)</span></label>
            <input class="form-control" name="business_pan" maxlength="20" style="text-transform:uppercase;" value="<?= e((string)($profile['business_pan'] ?? '')) ?>">
          </div>
        </div>

        <h2 class="h6 text-uppercase text-muted small fw-semibold mb-3">
          Bank account
          <span class="text-muted text-lowercase fw-normal">— where Manikya Market pays out your earnings</span>
        </h2>
        <div class="row g-3 mb-4">
          <div class="col-md-6">
            <label class="form-label">Account holder name / Bank name</label>
            <input class="form-control" name="business_bank_name" value="<?= e((string)($profile['business_bank_name'] ?? '')) ?>">
          </div>
          <div class="col-md-3">
            <label class="form-label">Account number</label>
            <input class="form-control" name="business_bank_account" value="<?= e((string)($profile['business_bank_account'] ?? '')) ?>">
          </div>
          <div class="col-md-3">
            <label class="form-label">IFSC</label>
            <input class="form-control" name="business_bank_ifsc" maxlength="20" style="text-transform:uppercase;" value="<?= e((string)($profile['business_bank_ifsc'] ?? '')) ?>">
          </div>
        </div>

        <h2 class="h6 text-uppercase text-muted small fw-semibold mb-3">
          Pickup warehouse
          <span class="text-muted text-lowercase fw-normal">— where the courier picks orders up from</span>
        </h2>
        <div class="row g-3 mb-4">
          <div class="col-md-6">
            <label class="form-label">Warehouse name</label>
            <input class="form-control" name="delhivery_warehouse_name" value="<?= e((string)($profile['delhivery_warehouse_name'] ?? '')) ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label">Contact phone</label>
            <input class="form-control" name="delhivery_warehouse_phone" value="<?= e((string)($profile['delhivery_warehouse_phone'] ?? '')) ?>">
          </div>
          <div class="col-12">
            <label class="form-label">Address</label>
            <textarea class="form-control" name="delhivery_warehouse_address" rows="2"><?= e((string)($profile['delhivery_warehouse_address'] ?? '')) ?></textarea>
          </div>
          <div class="col-md-5">
            <label class="form-label">City</label>
            <input class="form-control" name="delhivery_warehouse_city" value="<?= e((string)($profile['delhivery_warehouse_city'] ?? '')) ?>">
          </div>
          <div class="col-md-4">
            <label class="form-label">State</label>
            <input class="form-control" name="delhivery_warehouse_state" value="<?= e((string)($profile['delhivery_warehouse_state'] ?? '')) ?>">
          </div>
          <div class="col-md-3">
            <label class="form-label">Pincode</label>
            <input class="form-control" name="delhivery_warehouse_pincode" value="<?= e((string)($profile['delhivery_warehouse_pincode'] ?? '')) ?>">
          </div>
        </div>

        <div class="d-flex justify-content-end">
          <button class="btn btn-mm" type="submit">Save</button>
        </div>
      </form>
    </div>

    <script>if (window.lucide) window.lucide.createIcons();</script>
    <?php
};

require __DIR__ . '/../../views/layout.php';
