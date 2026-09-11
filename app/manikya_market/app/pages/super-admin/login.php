<?php

declare(strict_types=1);

$title = 'Super Admin Login';

if (request_method() === 'POST') {
    $email = post_string('email');
    $password = post_string('password');

    $user = auth_login($db, $email, $password, 'super_admin');
    if ($user) {
        redirect_to('super-admin/dashboard');
    }

    flash_set('error', 'Invalid login');
    redirect_to('super-admin/login');
}

$content = function () {
    $error = flash_get('error');
    ?>
    <div class="container py-4" style="max-width: 420px;">
      <div class="bg-white border rounded-4 p-4 mm-card">
        <div class="d-flex align-items-center gap-2 mb-3">
          <i data-lucide="shield-check" class="mm-icon"></i>
          <h1 class="h5 mb-0">Super Admin Login</h1>
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

        <div class="text-center mt-3 pt-3" style="border-top:1px solid #eee;">
          <a href="/stream.php"
             style="display:inline-flex; align-items:center; gap:6px;
                    padding:8px 16px; background:#f39200; color:#fff;
                    border-radius:4px; text-decoration:none; font-size:14px;
                    font-weight:600;">
            <i data-lucide="arrow-left" style="width:14px;height:14px;"></i>
            Back to News Junction
          </a>
        </div>
      </div>
    </div>
    <?php
};

require __DIR__ . '/../../views/layout.php';
