<?php

declare(strict_types=1);

$title = 'Seller Login';

if (request_method() === 'POST') {
    $email = post_string('email');
    $password = post_string('password');

    $user = auth_login($db, $email, $password, 'merchant');
    if ($user) {
        $profile = db_fetch_one($db, 'SELECT status FROM merchant_profile WHERE merchant_user_id = :uid LIMIT 1', [
            'uid' => (int)$user['id'],
        ]);
        $status = (string)($profile['status'] ?? 'approved');
        if ($status === 'disabled') {
            auth_logout();
            flash_set('error', 'Your seller account has been disabled. Contact support.');
            redirect_to('merchant/login');
        }
        if ($status === 'pending') {
            flash_set('info', 'Your application is still pending super-admin approval. You can sign in, but listing products is locked until approved.');
        }
        redirect_to('merchant/dashboard');
    }

    flash_set('error', 'Invalid login');
    redirect_to('merchant/login');
}

$content = function () {
    $error = flash_get('error');
    ?>
    <div class="container py-4" style="max-width: 420px;">
      <div class="bg-white border rounded-4 p-4 mm-card">
        <div class="d-flex align-items-center gap-2 mb-3">
          <i data-lucide="store" class="mm-icon"></i>
          <h1 class="h5 mb-0">Seller Login</h1>
        </div>

        <?php if ($error): ?>
          <div class="alert alert-danger"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" class="d-grid gap-3">
          <div>
            <label class="form-label">Email</label>
            <input class="form-control" type="email" name="email" required>
          </div>
          <div>
            <label class="form-label">Password</label>
            <input class="form-control" type="password" name="password" required>
          </div>
          <button class="btn btn-mm" type="submit">Login</button>
        </form>

        <div class="text-muted small mt-3">
          New seller? <a href="?p=merchant/signup">Apply to sell on Manikya Market</a>
        </div>
      </div>
    </div>
    <?php
};

require __DIR__ . '/../../views/layout.php';
