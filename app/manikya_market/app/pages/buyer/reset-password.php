<?php

declare(strict_types=1);

$title = 'Reset Password';

$token = (string)($_GET['token'] ?? '');
$error = '';
$success = '';

$resetData = null;
if (!empty($token)) {
    $resetData = auth_verify_reset_token($db, $token);
    if (!$resetData) {
        $error = 'Invalid or expired reset link. Please request a new one.';
    }
}

if (request_method() === 'POST' && !empty($token) && !$error) {
    $password = post_string('password');
    $passwordConfirm = post_string('password_confirm');

    if (empty($password)) {
        $error = 'Password cannot be empty';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters';
    } elseif ($password !== $passwordConfirm) {
        $error = 'Passwords do not match';
    } else {
        if (auth_reset_password($db, $token, $password)) {
            $success = 'Password has been reset successfully!';
        } else {
            $error = 'Failed to reset password. Please try again.';
        }
    }
}

$content = function () use ($error, $success, $resetData, $token) {
?>
  <style>
    .km-auth {
      --ink: #111827; --muted: #6B7280; --line: #E5E7EB; --line-strong: #D1D5DB;
      --bg-soft: #F9FAFB; --primary: #F39200; --primary-dark: #C97500;
      --danger: #DC2626; --success: #15803D;
      color: var(--ink);
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
      min-height: calc(100vh - 80px);
      display:flex; align-items:center; justify-content:center;
      padding: 32px 16px; background: var(--bg-soft);
    }
    body.bg-light:has(.km-auth) { background: var(--bg-soft) !important; }
    .km-auth-card { background:#fff; border:1px solid var(--line); border-radius: 14px; box-shadow: 0 4px 24px rgba(17,24,39,.04); width:100%; max-width: 440px; padding: 36px; }
    .km-auth h1 { font-size: 1.6rem; font-weight: 700; margin: 0 0 4px; letter-spacing: -.01em; }
    .km-auth-sub { color: var(--muted); margin-bottom: 22px; font-size: .95rem; }
    .km-field { margin-bottom: 16px; }
    .km-label { display:block; font-size: .85rem; font-weight: 600; color: var(--ink); margin-bottom: 6px; }
    .km-input { width: 100%; padding: 11px 13px; border:1px solid var(--line-strong); border-radius: 8px; font-size: .95rem; background:#fff; outline: none; transition: border-color .15s, box-shadow .15s; }
    .km-input:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(243,146,0,.18); }
    .km-help { font-size: .78rem; color: var(--muted); margin-top: 4px; }
    .km-btn { display:inline-flex; align-items:center; justify-content:center; width: 100%; padding: 12px 16px; font-weight: 600; font-size: .95rem; border-radius: 8px; cursor: pointer; border: 1px solid transparent; text-decoration: none; transition: all .15s; }
    .km-btn-primary { background: var(--primary); color:#fff; }
    .km-btn-primary:hover { background: var(--primary-dark); color:#fff; }
    .km-btn-outline { background:#fff; color: var(--ink); border-color: var(--line-strong); margin-top: 10px; }
    .km-btn-outline:hover { background: var(--bg-soft); color: var(--ink); }
    .km-alert { padding: 10px 12px; border-radius: 8px; font-size: .88rem; margin-bottom: 14px; border:1px solid; }
    .km-alert-danger { background: #FEF2F2; color: var(--danger); border-color: #FECACA; }
    .km-alert-success { background: #ECFDF5; color: var(--success); border-color: #BBF7D0; }
    .km-foot { text-align: center; margin-top: 18px; font-size: .9rem; color: var(--muted); }
    .km-foot a { color: var(--primary-dark); font-weight: 600; text-decoration: none; }
    @media (max-width: 480px) { .km-auth-card { padding: 26px 20px; border-radius: 12px; } .km-auth h1 { font-size: 1.4rem; } }
  </style>

  <div class="km-auth">
    <div class="km-auth-card">
      <h1>Set new password</h1>
      <div class="km-auth-sub">Choose a new password to access your account.</div>

      <?php if ($error): ?>
        <div class="km-alert km-alert-danger"><?= e($error) ?></div>
        <?php if (empty($resetData)): ?>
          <a class="km-btn km-btn-outline" href="?p=buyer/forgot-password">Request New Reset Link</a>
        <?php endif; ?>
      <?php elseif ($success): ?>
        <div class="km-alert km-alert-success"><?= e($success) ?></div>
        <a class="km-btn km-btn-primary" href="?p=buyer/login">Sign In</a>
      <?php else: ?>
        <?php if (!empty($resetData)): ?>
          <form method="post">
            <input type="hidden" name="token" value="<?= e($token) ?>">
            <div class="km-field">
              <label class="km-label" for="km-pw">New password</label>
              <input class="km-input" type="password" id="km-pw" name="password" required minlength="6" autofocus autocomplete="new-password">
              <div class="km-help">At least 6 characters.</div>
            </div>
            <div class="km-field">
              <label class="km-label" for="km-pw2">Confirm password</label>
              <input class="km-input" type="password" id="km-pw2" name="password_confirm" required minlength="6" autocomplete="new-password">
            </div>
            <button class="km-btn km-btn-primary" type="submit">Reset Password</button>
          </form>
        <?php else: ?>
          <div class="km-alert km-alert-danger">Invalid or expired reset link.</div>
          <a class="km-btn km-btn-outline" href="?p=buyer/forgot-password">Request New Link</a>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>

  <script>if (window.lucide) window.lucide.createIcons();</script>
<?php
};

require __DIR__ . '/../../views/layout.php';
