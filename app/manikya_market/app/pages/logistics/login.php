<?php

declare(strict_types=1);

$title = 'Logistics Login';

if (request_method() === 'POST') {
    $email = post_string('email');
    $password = post_string('password');

    $user = auth_login($db, $email, $password, 'logistics');
    if ($user) {
        redirect_to('logistics/dashboard');
    }

    flash_set('error', 'Invalid login');
    redirect_to('logistics/login');
}

$content = function () {
    $error = flash_get('error');
    ?>
    <div style="min-height: 100vh; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
      <div class="container" style="max-width: 400px;">
        <div class="bg-white rounded-4 p-4 shadow-lg">
          <div class="text-center mb-4">
            <div style="font-size: 3rem; margin-bottom: 12px;">🚚</div>
            <h1 class="h5 mb-2">Logistics Login</h1>
            <p class="text-muted small">Manage shipments and deliveries</p>
          </div>

          <?php if ($error): ?>
            <div class="alert alert-danger small"><?= e($error) ?></div>
          <?php endif; ?>

          <form method="POST" class="row g-3">
            <div class="col-12">
              <label class="form-label">Email</label>
              <input type="email" class="form-control" name="email" required autofocus>
            </div>
            <div class="col-12">
              <label class="form-label">Password</label>
              <input type="password" class="form-control" name="password" required>
            </div>
            <div class="col-12">
              <button type="submit" class="btn btn-primary w-100">Login</button>
            </div>
            <div class="col-12 text-center">
              <a href="?p=home" class="text-decoration-none small">Back to Home</a>
            </div>
          </form>
        </div>
      </div>
    </div>
    <?php
};

require __DIR__ . '/../../views/layout.php';
?>
