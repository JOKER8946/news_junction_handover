<?php

declare(strict_types=1);

$title = 'Forgot Password';

$error = '';
$success = '';

require __DIR__ . '/../../../vendor/autoload.php';;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (request_method() === 'POST') {
    $email = trim(mb_strtolower(post_string('email')));

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address';
    } else {
        $user = db_fetch_one($db, 'SELECT id, full_name FROM users WHERE email = :email AND role = :role AND status = :status LIMIT 1', [
            'email' => $email,
            'role' => 'buyer',
            'status' => 'active',
        ]);

        if (!$user) {
            // Anti-enumeration: don't reveal that the email isn't registered
            $success = 'If an account exists with this email, a reset link has been sent.';
        } else {
            $token = auth_create_reset_token($db, (int)$user['id'], $email);

            if (!$token) {
                $error = 'Failed to create reset token. Please try again.';
            } else {
                $resetUrl = app_site_url() . '/index.php?p=buyer/reset-password&token=' . urlencode($token);
                $smtp = smtp_get_config($db);

                if (!$smtp) {
                    error_log('SMTP not configured. Reset URL for ' . $email . ': ' . $resetUrl);
                    $error = 'Email service is not yet configured. Please contact support to reset your password.';
                } else {
                    $mail = new PHPMailer(true);
                    try {
                        $mail->isSMTP();
                        $mail->Host       = $smtp['host'];
                        $mail->SMTPAuth   = true;
                        $mail->Username   = $smtp['username'];
                        $mail->Password   = $smtp['password'];
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                        $mail->Port       = $smtp['port'];

                        $mail->setFrom($smtp['from_email'], $smtp['from_name']);
                        $mail->addAddress($email, $user['full_name'] ?? 'Customer');

                        $mail->isHTML(true);
                        $mail->Subject = 'Reset your King Mango password';
                        $mail->Body    = "
                            <h2>Hello " . e($user['full_name'] ?? 'there') . ",</h2>
                            <p>We received a request to reset your password for your King Mango account.</p>
                            <p style='text-align:center; margin:30px 0;'>
                                <a href='$resetUrl' style='background:#F39200; color:white; padding:14px 32px; text-decoration:none; border-radius:6px; font-size:16px; font-weight:bold; display:inline-block;'>
                                    Reset Password
                                </a>
                            </p>
                            <p>This link will expire in 60 minutes for security reasons.</p>
                            <p>If you did not request this password reset, please ignore this email.</p>
                            <p>Thank you,<br><strong>King Mango Team</strong></p>
                        ";
                        $mail->AltBody = "Hello,\n\nReset your password here: $resetUrl\n\nThis link expires in 60 minutes.\n\nIf this wasn't you, please ignore this email.";

                        $mail->send();
                        $success = 'A password reset link has been sent to your email.<br>Please check your inbox (and spam/junk folder).';
                    } catch (Exception $e) {
                        error_log("PHPMailer Error: " . $mail->ErrorInfo);
                        $error = 'Failed to send the reset email. Please try again later or contact support.';
                    }
                }
            }
        }
    }
}

$content = function () use ($error, $success) {
?>
  <style>
    .km-auth {
      --ink: #111827; --muted: #6B7280; --line: #E5E7EB; --line-strong: #D1D5DB;
      --bg-soft: #F9FAFB; --primary: #F39200; --primary-dark: #C97500;
      --danger: #DC2626; --success: #15803D;
      color: var(--ink);
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
      min-height: calc(100vh - 80px);
      display: flex; align-items: center; justify-content: center;
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
    .km-foot a:hover { color: var(--primary); text-decoration: underline; }
    @media (max-width: 480px) { .km-auth-card { padding: 26px 20px; border-radius: 12px; } .km-auth h1 { font-size: 1.4rem; } }
  </style>

  <div class="km-auth">
    <div class="km-auth-card">
      <h1>Reset password</h1>
      <div class="km-auth-sub">Enter your email and we'll send you a link to reset it.</div>

      <?php if ($error): ?>
        <div class="km-alert km-alert-danger"><?= $error ?></div>
      <?php endif; ?>
      <?php if ($success): ?>
        <div class="km-alert km-alert-success"><?= $success ?></div>
        <a class="km-btn km-btn-outline" href="?p=buyer/login">Back to sign in</a>
      <?php else: ?>
        <form method="post">
          <div class="km-field">
            <label class="km-label" for="km-email">Email address</label>
            <input class="km-input" id="km-email" type="email" name="email" required autofocus autocomplete="email">
          </div>
          <button class="km-btn km-btn-primary" type="submit">Send Reset Link</button>
        </form>
        <div class="km-foot">
          <a href="?p=buyer/login">Back to sign in</a>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <script>if (window.lucide) window.lucide.createIcons();</script>
<?php
};

require __DIR__ . '/../../views/layout.php';
