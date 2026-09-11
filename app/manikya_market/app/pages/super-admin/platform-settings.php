<?php

declare(strict_types=1);

auth_require_role('super_admin');

$title = 'Platform Settings';

if (request_method() === 'POST' && isset($_POST['settings_save'])) {
    $commissionPct = (float)post_string('commission_pct');
    if ($commissionPct < 0)   $commissionPct = 0;
    if ($commissionPct > 100) $commissionPct = 100;

    $rzpKeyId     = trim(post_string('razorpay_key_id'));
    $rzpKeySecret = trim(post_string('razorpay_key_secret'));

    // Ekart Logistics credentials — the actual courier integration. The legacy
    // delhivery_api_token / delhivery_mode columns are no longer surfaced in
    // the form, but kept in the DB to preserve historical data.
    $ekartClientId   = trim(post_string('ekart_client_id'));
    $ekartApiToken   = trim(post_string('ekart_api_token'));   // legacy single-token field (unused in v2 auth)
    $ekartUsername   = trim(post_string('ekart_username'));
    $ekartPassword   = post_string('ekart_password');          // sent as plaintext; stored as-is — required by Ekart v2 auth
    $ekartBaseUrl    = trim(post_string('ekart_base_url'));
    $ekartEnv        = post_string('ekart_environment') === 'production' ? 'production' : 'sandbox';

    // If the password field is submitted empty, KEEP the existing one so we
    // don't accidentally wipe it on a Save that didn't touch the password.
    if ($ekartPassword === '') {
        $existing = db_fetch_one($db, 'SELECT ekart_password FROM platform_settings WHERE id = 1');
        $ekartPassword = (string)($existing['ekart_password'] ?? '');
    }

    // Warehouse fields keep their delhivery_* column names for now (rename is a
    // separate migration). They serve as the platform-level pickup point.
    $whName            = trim(post_string('delhivery_warehouse_name'));
    $whAddress         = trim(post_string('delhivery_warehouse_address'));
    $whCity            = trim(post_string('delhivery_warehouse_city'));
    $whState           = trim(post_string('delhivery_warehouse_state'));
    $whPincode         = trim(post_string('delhivery_warehouse_pincode'));
    $whPhone           = trim(post_string('delhivery_warehouse_phone'));

    $smtpHost     = trim(post_string('smtp_host'));
    $smtpPort     = (int)post_string('smtp_port');
    $smtpUsername = trim(post_string('smtp_username'));
    $smtpPassword = trim(post_string('smtp_password'));
    $smtpFromEmail= trim(post_string('smtp_from_email'));
    $smtpFromName = trim(post_string('smtp_from_name'));

    $bankName     = trim(post_string('bank_name'));
    $bankAccount  = trim(post_string('bank_account'));
    $bankIfsc     = trim(post_string('bank_ifsc'));

    db_exec($db,
        'UPDATE platform_settings SET
           commission_pct = :pct,
           razorpay_key_id = :rzid, razorpay_key_secret = :rzsec,
           ekart_client_id = :ecid, ekart_api_token = :etok,
           ekart_username = :eusr, ekart_password = :epwd,
           ekart_base_url = :eburl, ekart_environment = :eenv,
           delhivery_warehouse_name = :whn, delhivery_warehouse_address = :wha,
           delhivery_warehouse_city = :whc, delhivery_warehouse_state = :whst,
           delhivery_warehouse_pincode = :whp, delhivery_warehouse_phone = :whph,
           smtp_host = :sh, smtp_port = :sp,
           smtp_username = :su, smtp_password = :spw,
           smtp_from_email = :sfe, smtp_from_name = :sfn,
           bank_name = :bn, bank_account = :ba, bank_ifsc = :bi
         WHERE id = 1',
        [
            'pct'   => $commissionPct,
            'rzid'  => $rzpKeyId   ?: null,
            'rzsec' => $rzpKeySecret ?: null,
            'ecid'  => $ekartClientId ?: null,
            'etok'  => $ekartApiToken ?: null,
            'eusr'  => $ekartUsername ?: null,
            'epwd'  => $ekartPassword ?: null,
            'eburl' => $ekartBaseUrl  ?: null,
            'eenv'  => $ekartEnv,
            'whn'   => $whName     ?: null,
            'wha'   => $whAddress  ?: null,
            'whc'   => $whCity     ?: null,
            'whst'  => $whState    ?: null,
            'whp'   => $whPincode  ?: null,
            'whph'  => $whPhone    ?: null,
            'sh'    => $smtpHost   ?: null,
            'sp'    => $smtpPort   ?: null,
            'su'    => $smtpUsername ?: null,
            'spw'   => $smtpPassword ?: null,
            'sfe'   => $smtpFromEmail ?: null,
            'sfn'   => $smtpFromName  ?: null,
            'bn'    => $bankName   ?: null,
            'ba'    => $bankAccount?: null,
            'bi'    => $bankIfsc   ?: null,
        ]
    );
    flash_set('success', 'Platform settings saved.');
    redirect_to('super-admin/platform-settings');
}

$settings = platform_settings_get($db) ?: [];

// Invoice-issuer info is stored on the super-admin's own merchant_profile row.
// Promotion Updates uses the same row for hero/promo fields. Here we just
// expose the company-identity fields used by the tax invoice.
$adminId = function_exists('platform_admin_user_id') ? platform_admin_user_id($db) : 0;
$invoiceCompany = [];
if ($adminId > 0) {
    $invoiceCompany = db_fetch_one($db, 'SELECT * FROM merchant_profile WHERE merchant_user_id = :id LIMIT 1', ['id' => $adminId]) ?: [];
}

// ─── News Junction → Google OAuth credentials ──────────────────────────────
// Stored in nj_cream.platform_settings (cross-DB; same MySQL server). The
// News Junction sign-in flow (process/google-callback.php) reads from here
// before falling back to the config file at inc/data/google_oauth.php.
// The table is created lazily so this works on a fresh install too.
$njOauthEnsure = function (PDO $db) {
    try {
        $db->exec(
            "CREATE TABLE IF NOT EXISTS nj_cream.platform_settings (
                setting_key   VARCHAR(100) NOT NULL PRIMARY KEY,
                setting_value TEXT NULL,
                updated_at    DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )"
        );
    } catch (Throwable $t) { /* ignore — caller will surface failure */ }
};

if (request_method() === 'POST' && isset($_POST['nj_oauth_save'])) {
    $njOauthEnsure($db);
    $cid = trim((string)($_POST['nj_google_client_id'] ?? ''));
    $sec = trim((string)($_POST['nj_google_client_secret'] ?? ''));

    // Light sanity check.
    $cidOk = $cid === '' || (bool)preg_match('/\.apps\.googleusercontent\.com$/', $cid);
    if (!$cidOk) {
        flash_set('error', 'Client ID must end with .apps.googleusercontent.com');
        redirect_to('super-admin/platform-settings');
    }

    try {
        if ($cid !== '') {
            $db->prepare(
                "INSERT INTO nj_cream.platform_settings (setting_key, setting_value)
                 VALUES ('google_client_id', :v)
                 ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)"
            )->execute(['v' => $cid]);
        }
        // Empty secret means "keep what's saved" — same UX pattern as Ekart pwd.
        if ($sec !== '') {
            $db->prepare(
                "INSERT INTO nj_cream.platform_settings (setting_key, setting_value)
                 VALUES ('google_client_secret', :v)
                 ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)"
            )->execute(['v' => $sec]);
        }
        flash_set('success', 'News Junction Google credentials saved.');
    } catch (Throwable $t) {
        flash_set('error', 'Could not save Google credentials: ' . $t->getMessage());
    }
    redirect_to('super-admin/platform-settings');
}

// Read current values for the form. Empty strings if not set.
$njOauthCid = '';
$njOauthSec = '';
try {
    $njOauthEnsure($db);
    $stmt = $db->prepare("SELECT setting_key, setting_value FROM nj_cream.platform_settings WHERE setting_key IN ('google_client_id', 'google_client_secret')");
    $stmt->execute();
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        if ($r['setting_key'] === 'google_client_id')     $njOauthCid = (string)$r['setting_value'];
        if ($r['setting_key'] === 'google_client_secret') $njOauthSec = (string)$r['setting_value'];
    }
} catch (Throwable $t) { /* ignore */ }

$njOauthMaskedSec = '';
if ($njOauthSec !== '') {
    $L = strlen($njOauthSec);
    $njOauthMaskedSec = $L <= 12 ? str_repeat('•', $L)
                                 : substr($njOauthSec, 0, 8) . str_repeat('•', max(4, $L - 12)) . substr($njOauthSec, -4);
}

if (request_method() === 'POST' && isset($_POST['invoice_save'])) {
    if ($adminId <= 0) {
        flash_set('error', 'No super-admin user found.');
        redirect_to('super-admin/platform-settings');
    }
    // Ensure the row exists.
    if (!$invoiceCompany) {
        db_exec($db, 'INSERT INTO merchant_profile (merchant_user_id, created_at) VALUES (:id, NOW())', ['id' => $adminId]);
    }
    db_exec($db,
        'UPDATE merchant_profile SET
            business_name = :bn,
            business_address = :ba,
            business_state = :bs,
            business_pincode = :bp,
            business_pan = :pan,
            business_gstin = :gst,
            business_cin = :cin
         WHERE merchant_user_id = :id',
        [
            'bn'  => post_string('inv_business_name')    ?: null,
            'ba'  => post_string('inv_business_address') ?: null,
            'bs'  => post_string('inv_business_state')   ?: null,
            'bp'  => post_string('inv_business_pincode') ?: null,
            'pan' => post_string('inv_business_pan')     ?: null,
            'gst' => post_string('inv_business_gstin')   ?: null,
            'cin' => post_string('inv_business_cin')     ?: null,
            'id'  => $adminId,
        ]
    );
    flash_set('success', 'Invoice issuer details saved.');
    redirect_to('super-admin/platform-settings');
}

$content = function () use ($settings, $invoiceCompany, $njOauthCid, $njOauthMaskedSec) {
    require __DIR__ . '/../../views/partials/super-admin-nav.php';
    $success = flash_get('success');
    ?>
    <div class="container" style="max-width: 920px;">
      <h1 class="h4 mb-3">Platform Settings</h1>
      <p class="text-muted small">
        Configure the keys the entire marketplace uses for payments, shipping, and email.
        Once set, buyer payments land in <em>your</em> Razorpay, shipments go through <em>your</em>
        Ekart Logistics account, and notifications go out from <em>your</em> SMTP.
      </p>

      <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>

      <form method="post" class="bg-white border rounded-3 p-4">
        <input type="hidden" name="settings_save" value="1">

        <!-- Commission -->
        <h2 class="h6 text-uppercase text-muted small fw-semibold mb-3">Commission</h2>
        <div class="mb-4">
          <label class="form-label">Platform commission (%)</label>
          <input class="form-control" type="number" step="0.01" min="0" max="100"
                 name="commission_pct" value="<?= e((string)($settings['commission_pct'] ?? 10)) ?>" required>
          <div class="form-text">Applied on every order's <code>total_amount</code>. Seller earns <code>total − commission</code>.</div>
        </div>

        <!-- Razorpay -->
        <h2 class="h6 text-uppercase text-muted small fw-semibold mb-3">
          Razorpay
          <?php if (!empty($settings['razorpay_key_id'])): ?>
            <span class="badge bg-success ms-1">Connected</span>
          <?php else: ?>
            <span class="badge bg-warning ms-1">Not configured</span>
          <?php endif; ?>
        </h2>
        <p class="text-muted small mb-2">All buyer payments route through this account. Money lands in <em>your</em> bank linked to Razorpay; you settle sellers via Payouts.</p>
        <div class="row g-3 mb-4">
          <div class="col-md-6">
            <label class="form-label">Key ID</label>
            <input class="form-control" name="razorpay_key_id" placeholder="rzp_test_... or rzp_live_..."
                   value="<?= e((string)($settings['razorpay_key_id'] ?? '')) ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label">Key Secret</label>
            <input class="form-control" type="password" name="razorpay_key_secret"
                   value="<?= e((string)($settings['razorpay_key_secret'] ?? '')) ?>">
          </div>
        </div>

        <!-- Ekart Logistics -->
        <?php
          $ekHasAll = !empty($settings['ekart_client_id']) && !empty($settings['ekart_username']) && !empty($settings['ekart_password']);
        ?>
        <h2 class="h6 text-uppercase text-muted small fw-semibold mb-3">
          Ekart Logistics (shipping)
          <?php if ($ekHasAll): ?>
            <span class="badge bg-success ms-1">Connected</span>
          <?php elseif (!empty($settings['ekart_client_id'])): ?>
            <span class="badge bg-warning ms-1">Partial — need username + password</span>
          <?php else: ?>
            <span class="badge bg-warning ms-1">Not configured</span>
          <?php endif; ?>
        </h2>
        <p class="text-muted small mb-2">
          Ekart Logistics v2 auth uses <strong>Client ID + Username + Password</strong>
          (a Bearer token is fetched automatically and cached for ~24 h).
          Until all three are present, shipping runs in <em>stub mode</em> and waybills
          are stored as <code>EKART_PENDING_&lt;order_no&gt;</code>.
        </p>
        <div class="row g-3 mb-3">
          <div class="col-md-6">
            <label class="form-label">Client ID</label>
            <input class="form-control" name="ekart_client_id"
                   value="<?= e((string)($settings['ekart_client_id'] ?? '')) ?>"
                   placeholder="From Ekart onboarding email">
          </div>
          <div class="col-md-6">
            <label class="form-label">Username</label>
            <input class="form-control" name="ekart_username"
                   value="<?= e((string)($settings['ekart_username'] ?? '')) ?>"
                   placeholder="From Ekart onboarding email">
          </div>
          <div class="col-md-8">
            <label class="form-label">Password</label>
            <input class="form-control" type="password" name="ekart_password"
                   value=""
                   placeholder="<?= !empty($settings['ekart_password']) ? '••• kept (leave blank to keep, type new value to change)' : 'From Ekart onboarding email' ?>">
          </div>
          <div class="col-md-4">
            <label class="form-label">Environment</label>
            <select class="form-select" name="ekart_environment">
              <option value="sandbox"    <?= (($settings['ekart_environment'] ?? '') === 'sandbox')    ? 'selected' : '' ?>>Sandbox / UAT</option>
              <option value="production" <?= (($settings['ekart_environment'] ?? '') === 'production') ? 'selected' : '' ?>>Production (live)</option>
            </select>
          </div>
          <div class="col-12">
            <label class="form-label">Base URL <small class="text-muted">(blank = https://app.elite.ekartlogistics.in)</small></label>
            <input class="form-control" name="ekart_base_url"
                   value="<?= e((string)($settings['ekart_base_url'] ?? '')) ?>"
                   placeholder="https://app.elite.ekartlogistics.in">
          </div>
          <input type="hidden" name="ekart_api_token" value="<?= e((string)($settings['ekart_api_token'] ?? '')) ?>">
        </div>
        <p class="text-muted small mb-2">Default pickup warehouse (used as the from-address for shipments):</p>
        <div class="row g-3 mb-4">
          <div class="col-md-6">
            <label class="form-label">Warehouse name</label>
            <input class="form-control" name="delhivery_warehouse_name" value="<?= e((string)($settings['delhivery_warehouse_name'] ?? '')) ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label">Phone</label>
            <input class="form-control" name="delhivery_warehouse_phone" value="<?= e((string)($settings['delhivery_warehouse_phone'] ?? '')) ?>">
          </div>
          <div class="col-12">
            <label class="form-label">Address</label>
            <textarea class="form-control" name="delhivery_warehouse_address" rows="2"><?= e((string)($settings['delhivery_warehouse_address'] ?? '')) ?></textarea>
          </div>
          <div class="col-md-5">
            <label class="form-label">City</label>
            <input class="form-control" name="delhivery_warehouse_city" value="<?= e((string)($settings['delhivery_warehouse_city'] ?? '')) ?>">
          </div>
          <div class="col-md-4">
            <label class="form-label">State</label>
            <input class="form-control" name="delhivery_warehouse_state" value="<?= e((string)($settings['delhivery_warehouse_state'] ?? '')) ?>">
          </div>
          <div class="col-md-3">
            <label class="form-label">Pincode</label>
            <input class="form-control" name="delhivery_warehouse_pincode" value="<?= e((string)($settings['delhivery_warehouse_pincode'] ?? '')) ?>">
          </div>
        </div>

        <!-- SMTP -->
        <h2 class="h6 text-uppercase text-muted small fw-semibold mb-3">
          SMTP (transactional email)
          <?php if (!empty($settings['smtp_username']) && !empty($settings['smtp_password'])): ?>
            <span class="badge bg-success ms-1">Connected</span>
          <?php else: ?>
            <span class="badge bg-warning ms-1">Not configured</span>
          <?php endif; ?>
        </h2>
        <p class="text-muted small mb-2">Buyer order confirmations and shipment notifications go out from this mailbox.</p>
        <div class="row g-3 mb-4">
          <div class="col-md-8">
            <label class="form-label">Host</label>
            <input class="form-control" name="smtp_host" placeholder="smtp.gmail.com"
                   value="<?= e((string)($settings['smtp_host'] ?? 'smtp.gmail.com')) ?>">
          </div>
          <div class="col-md-4">
            <label class="form-label">Port</label>
            <input class="form-control" type="number" name="smtp_port" value="<?= e((string)($settings['smtp_port'] ?? 587)) ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label">Username</label>
            <input class="form-control" name="smtp_username" value="<?= e((string)($settings['smtp_username'] ?? '')) ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label">Password</label>
            <input class="form-control" type="password" name="smtp_password"
                   value="<?= e((string)($settings['smtp_password'] ?? '')) ?>">
            <div class="form-text">For Gmail, use an App Password.</div>
          </div>
          <div class="col-md-6">
            <label class="form-label">From email</label>
            <input class="form-control" type="email" name="smtp_from_email" value="<?= e((string)($settings['smtp_from_email'] ?? '')) ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label">From name</label>
            <input class="form-control" name="smtp_from_name" value="<?= e((string)($settings['smtp_from_name'] ?? 'Manikya Market')) ?>">
          </div>
        </div>

        <!-- Bank -->
        <h2 class="h6 text-uppercase text-muted small fw-semibold mb-3">Platform bank account</h2>
        <p class="text-muted small mb-2">Where buyer payments accumulate before payouts to sellers.</p>
        <div class="row g-3 mb-4">
          <div class="col-md-6">
            <label class="form-label">Bank name</label>
            <input class="form-control" name="bank_name" value="<?= e((string)($settings['bank_name'] ?? '')) ?>">
          </div>
          <div class="col-md-3">
            <label class="form-label">Account</label>
            <input class="form-control" name="bank_account" value="<?= e((string)($settings['bank_account'] ?? '')) ?>">
          </div>
          <div class="col-md-3">
            <label class="form-label">IFSC</label>
            <input class="form-control" name="bank_ifsc" value="<?= e((string)($settings['bank_ifsc'] ?? '')) ?>">
          </div>
        </div>

        <button class="btn btn-mm" type="submit">Save settings</button>
      </form>

      <!-- ─── News Junction · Google OAuth credentials ─── -->
      <form method="post" class="bg-white border rounded-3 p-4 mt-4" autocomplete="off">
        <input type="hidden" name="nj_oauth_save" value="1">
        <h2 class="h6 text-uppercase text-muted small fw-semibold mb-3">News Junction · Google Sign-In</h2>
        <p class="text-muted small mb-3">
          These credentials power the <strong>Continue with Google</strong> button on
          <code>newsjunction.net/sign-in.php</code>. Rotate them here whenever the
          Google Cloud project changes — no code edit needed. Stored in
          <code>nj_cream.platform_settings</code>; the sign-in flow reads from here
          first and falls back to <code>inc/data/google_oauth.php</code>.
        </p>
        <div class="row g-3 mb-3">
          <div class="col-md-12">
            <label class="form-label">Client ID</label>
            <input class="form-control" name="nj_google_client_id"
                   value="<?= e($njOauthCid) ?>"
                   placeholder="123456789-abc.apps.googleusercontent.com">
            <div class="form-text">From Google Cloud Console → Credentials → OAuth client. Must end with <code>.apps.googleusercontent.com</code>.</div>
          </div>
          <div class="col-md-12">
            <label class="form-label">Client Secret</label>
            <input type="password" class="form-control" name="nj_google_client_secret"
                   placeholder="<?= $njOauthMaskedSec !== '' ? 'Currently: ' . e($njOauthMaskedSec) . ' — leave blank to keep' : 'GOCSPX-xxxxxxxxxxxxxxxxxxxxxxxx' ?>">
            <div class="form-text">
              <?php if ($njOauthMaskedSec !== ''): ?>
                A secret is already saved. Leave this field blank to keep it; type a new value to replace.
              <?php else: ?>
                Starts with <code>GOCSPX-</code>. Only needed if you later use server-side OAuth flows (offline access, Calendar, Drive). The sign-in flow itself doesn't need the secret.
              <?php endif; ?>
            </div>
          </div>
        </div>
        <button class="btn btn-mm" type="submit">Save Google credentials</button>
      </form>

      <!-- ─── Invoice issuer details (used as the "Sold By" block on every tax invoice) ─── -->
      <form method="post" class="bg-white border rounded-3 p-4 mt-4">
        <input type="hidden" name="invoice_save" value="1">
        <h2 class="h6 text-uppercase text-muted small fw-semibold mb-3">Invoice Issuer (Sold By)</h2>
        <p class="text-muted small mb-3">
          These fields appear in the "Sold By" header of every tax invoice issued through this platform.
          The PAN, GSTIN and CIN are printed below the company name as required for B2B GST filings.
        </p>
        <div class="row g-3 mb-3">
          <div class="col-md-6">
            <label class="form-label">Company name</label>
            <input class="form-control" name="inv_business_name"
                   value="<?= e((string)($invoiceCompany['business_name'] ?? '')) ?>"
                   placeholder="Manikya Money Service Private Limited">
          </div>
          <div class="col-md-6">
            <label class="form-label">Address</label>
            <textarea class="form-control" name="inv_business_address" rows="1"
                      placeholder="Building, street, locality"><?= e((string)($invoiceCompany['business_address'] ?? '')) ?></textarea>
          </div>
          <div class="col-md-4">
            <label class="form-label">State</label>
            <input class="form-control" name="inv_business_state"
                   value="<?= e((string)($invoiceCompany['business_state'] ?? '')) ?>"
                   placeholder="Karnataka">
          </div>
          <div class="col-md-2">
            <label class="form-label">Pincode</label>
            <input class="form-control" name="inv_business_pincode"
                   value="<?= e((string)($invoiceCompany['business_pincode'] ?? '')) ?>"
                   placeholder="560001">
          </div>
          <div class="col-md-2">
            <label class="form-label">PAN</label>
            <input class="form-control text-uppercase" name="inv_business_pan"
                   value="<?= e((string)($invoiceCompany['business_pan'] ?? '')) ?>"
                   placeholder="AAACX1234A">
          </div>
          <div class="col-md-4">
            <label class="form-label">GSTIN</label>
            <input class="form-control text-uppercase" name="inv_business_gstin"
                   value="<?= e((string)($invoiceCompany['business_gstin'] ?? '')) ?>"
                   placeholder="29AAACX1234A1ZA">
          </div>
          <div class="col-md-6">
            <label class="form-label">CIN</label>
            <input class="form-control text-uppercase" name="inv_business_cin"
                   value="<?= e((string)($invoiceCompany['business_cin'] ?? '')) ?>"
                   placeholder="U12345KA2024PTC123456">
          </div>
          <div class="col-md-6 d-flex align-items-end">
            <small class="text-muted">Logo is the one already used in the navbar / footer (uploaded via Promotion Updates).</small>
          </div>
        </div>
        <button class="btn btn-mm" type="submit">Save invoice issuer details</button>
      </form>
    </div>
    <?php
};

require __DIR__ . '/../../views/layout.php';
