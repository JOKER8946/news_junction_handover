<?php

declare(strict_types=1);

$title = 'Create Account';

if (request_method() === 'POST') {
    $fullName = post_string('full_name');
    $phone = post_string('phone');
    $email = post_string('email');
    $password = post_string('password');

    if ($fullName === '' || $email === '' || $password === '') {
        flash_set('error', 'Please fill all required fields');
        redirect_to('buyer/signup');
    }

    $existing = db_fetch_one($db, 'SELECT id FROM users WHERE email = :email LIMIT 1', ['email' => mb_strtolower($email)]);
    if ($existing) {
        flash_set('error', 'Email already exists');
        redirect_to('buyer/signup');
    }

    $referralCode = post_string('referral_code');
    $hash = password_hash($password, PASSWORD_DEFAULT);

    db_exec($db, 'INSERT INTO users (role, full_name, phone, email, password_hash, status, created_at) VALUES (\'buyer\', :full_name, :phone, :email, :password_hash, \'active\', NOW())', [
        'full_name' => $fullName,
        'phone' => $phone,
        'email' => mb_strtolower($email),
        'password_hash' => $hash,
    ]);

    $newUserId = (int)$db->lastInsertId();
    referral_generate_code($db, $newUserId);

    if ($referralCode !== '') {
        referral_apply_code($db, $newUserId, $referralCode);
    }

    flash_set('success', 'Account created. Please sign in.');
    redirect_to('buyer/login');
}

$refConfig = referral_get_config($db);
$googleEnabled = google_oauth_get_config($db) !== null;
$prefilledRef = (string)($_GET['ref'] ?? '');

$content = function () use ($refConfig, $googleEnabled, $prefilledRef) {
    $error = flash_get('error');
?>
  <style>
    .km-auth {
      --ink: #111827; --muted: #6B7280; --line: #E5E7EB; --line-strong: #D1D5DB;
      --bg-soft: #F9FAFB; --primary: #F39200; --primary-dark: #C97500; --danger: #DC2626;
      color: var(--ink);
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
      min-height: calc(100vh - 80px);
      display: flex; align-items: center; justify-content: center;
      padding: 32px 16px; background: var(--bg-soft);
    }
    body.bg-light:has(.km-auth) { background: var(--bg-soft) !important; }
    .km-auth-card { background:#fff; border:1px solid var(--line); border-radius: 14px; box-shadow: 0 4px 24px rgba(17,24,39,.04); width:100%; max-width: 480px; padding: 36px; }
    .km-auth h1 { font-size: 1.6rem; font-weight: 700; margin: 0 0 4px; letter-spacing: -.01em; }
    .km-auth-sub { color: var(--muted); margin-bottom: 22px; font-size: .95rem; }
    .km-field { margin-bottom: 14px; }
    .km-grid-2 { display:grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .km-label { display:block; font-size: .85rem; font-weight: 600; color: var(--ink); margin-bottom: 6px; }
    .km-label .req { color: var(--danger); }
    .km-input { width: 100%; padding: 11px 13px; border:1px solid var(--line-strong); border-radius: 8px; font-size: .95rem; background:#fff; outline: none; transition: border-color .15s, box-shadow .15s; }
    .km-input:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(243,146,0,.18); }
    .km-input-pw-wrap { position: relative; }
    .km-input-pw-wrap input { padding-right: 44px; }
    .km-pw-toggle { position: absolute; right: 6px; top: 50%; transform: translateY(-50%); background: transparent; border: 0; padding: 6px 8px; color: var(--muted); cursor: pointer; border-radius: 6px; }
    .km-pw-toggle:hover { color: var(--ink); background: var(--bg-soft); }
    .km-help { font-size: .78rem; color: var(--muted); margin-top: 4px; }
    .km-help strong { color: var(--primary-dark); }
    .km-btn { display:inline-flex; align-items:center; justify-content:center; gap: 10px; width: 100%; padding: 12px 16px; font-weight: 600; font-size: .95rem; border-radius: 8px; cursor: pointer; border: 1px solid transparent; text-decoration: none; transition: all .15s; margin-top: 6px; }
    .km-btn-primary { background: var(--primary); color:#fff; }
    .km-btn-primary:hover { background: var(--primary-dark); color:#fff; }
    .km-btn-google { background:#fff; color: var(--ink); border-color: var(--line-strong); }
    .km-btn-google:hover { background: var(--bg-soft); color: var(--ink); }
    .km-btn-google svg { width:18px; height: 18px; }
    .km-divider { display:flex; align-items:center; gap:14px; margin: 18px 0; color: var(--muted); font-size:.78rem; font-weight:600; letter-spacing: .08em; text-transform: uppercase; }
    .km-divider::before, .km-divider::after { content:''; height:1px; background: var(--line); flex:1; }
    .km-alert { padding: 10px 12px; border-radius: 8px; font-size: .88rem; margin-bottom: 14px; border:1px solid; }
    .km-alert-danger { background: #FEF2F2; color: var(--danger); border-color: #FECACA; }
    .km-foot { text-align: center; margin-top: 18px; font-size: .9rem; color: var(--muted); }
    .km-foot a { color: var(--primary-dark); font-weight: 600; text-decoration: none; }
    .km-foot a:hover { color: var(--primary); text-decoration: underline; }

    @media (max-width: 480px) {
      .km-auth-card { padding: 26px 20px; border-radius: 12px; }
      .km-auth h1 { font-size: 1.4rem; }
      .km-grid-2 { grid-template-columns: 1fr; }
    }
  </style>

  <div class="km-auth">
    <div class="km-auth-card">
      <h1>Create your account</h1>
      <div class="km-auth-sub">Sign up to start ordering premium mangoes.</div>

      <?php if ($error): ?>
        <div class="km-alert km-alert-danger"><?= e($error) ?></div>
      <?php endif; ?>

      <?php if ($googleEnabled): ?>
        <a class="km-btn km-btn-google" href="?p=buyer/google-login">
          <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <path fill="#4285F4" d="M21.35 11.1h-9.18v2.92h5.27c-.23 1.36-1.66 4-5.27 4-3.18 0-5.77-2.63-5.77-5.87S8.99 6.28 12.17 6.28c1.81 0 3.02.77 3.71 1.43l2.53-2.43C16.84 3.86 14.74 3 12.17 3 7 3 2.83 7.16 2.83 12.16S7 21.32 12.17 21.32c7.02 0 9.34-4.92 9.34-7.45 0-.5-.06-.88-.16-1.27Z"/>
            <path fill="#34A853" d="M3.84 7.4l2.4 1.76c.65-1.94 2.46-3.34 4.93-3.34 1.81 0 3.02.77 3.71 1.43l2.53-2.43C15.34 3.36 13.24 2.5 10.67 2.5 7.04 2.5 3.96 4.6 2.5 7.71l1.34-.31z"/>
            <path fill="#FBBC05" d="M12.17 21.32c2.49 0 4.59-.83 6.12-2.26l-2.95-2.27c-.81.55-1.93.92-3.17.92-2.43 0-4.49-1.57-5.23-3.78l-2.42 1.86c1.45 2.86 4.42 5.53 7.65 5.53z"/>
            <path fill="#EA4335" d="M21.35 11.1h-9.18v2.92h5.27c-.18 1.05-1.07 3.07-3.27 3.78l2.95 2.27c1.74-1.61 2.86-4 2.86-7.04 0-.5-.06-.88-.16-1.27l-1.47-.66z"/>
          </svg>
          Continue with Google
        </a>
        <div class="km-divider">Or sign up with email</div>
      <?php endif; ?>

      <form method="post" autocomplete="on">
        <div class="km-field">
          <label class="km-label" for="km-name">Full name <span class="req">*</span></label>
          <input class="km-input" id="km-name" type="text" name="full_name" required autocomplete="name">
        </div>
        <div class="km-grid-2">
          <div class="km-field">
            <label class="km-label" for="km-phone">Phone</label>
            <input class="km-input" id="km-phone" type="tel" name="phone" placeholder="+91..." autocomplete="tel">
          </div>
          <div class="km-field">
            <label class="km-label" for="km-email">Email <span class="req">*</span></label>
            <input class="km-input" id="km-email" type="email" name="email" required autocomplete="email">
          </div>
        </div>
        <div class="km-field">
          <label class="km-label" for="km-pw">Password <span class="req">*</span></label>
          <div class="km-input-pw-wrap">
            <input class="km-input" type="password" id="km-pw" name="password" required minlength="6" autocomplete="new-password">
            <button type="button" class="km-pw-toggle" data-target="km-pw" aria-label="Show password">
              <i data-lucide="eye" style="width:18px;height:18px;"></i>
            </button>
          </div>
          <div class="km-help">At least 6 characters.</div>
        </div>
        <div class="km-field">
          <label class="km-label" for="km-ref">Referral Code <span class="text-muted" style="font-weight: 500;">(optional)</span></label>
          <input class="km-input" id="km-ref" type="text" name="referral_code" maxlength="10"
            value="<?= e($prefilledRef) ?>" style="text-transform: uppercase; letter-spacing: .05em;"
            placeholder="Enter referral code">
          <div class="km-help">Have a friend's code? Get <strong><?= e(number_format($refConfig['referee_pct'], 1)) ?>% off</strong> your first order.</div>
        </div>

        <button class="km-btn km-btn-primary" type="submit">Create Account</button>
      </form>

      <div class="km-foot">
        Already have an account? <a href="?p=buyer/login">Sign in</a>
      </div>
    </div>
  </div>

  <script>
    if (window.lucide) window.lucide.createIcons();
    document.querySelectorAll('.km-pw-toggle').forEach(function(btn){
      btn.addEventListener('click', function(){
        var id = btn.getAttribute('data-target');
        var input = document.getElementById(id);
        if (!input) return;
        var isPw = input.type === 'password';
        input.type = isPw ? 'text' : 'password';
        btn.setAttribute('aria-label', isPw ? 'Hide password' : 'Show password');
        var icon = btn.querySelector('i,[data-lucide]');
        if (icon) { icon.setAttribute('data-lucide', isPw ? 'eye-off' : 'eye'); if (window.lucide) window.lucide.createIcons(); }
      });
    });
  </script>
<?php
};

require __DIR__ . '/../../views/layout.php';
