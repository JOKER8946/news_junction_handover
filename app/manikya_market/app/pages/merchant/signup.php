<?php

declare(strict_types=1);

$title = 'Seller Sign-up';

$categories = db_fetch_all($db, 'SELECT id, name FROM categories WHERE is_active = 1 ORDER BY sort_order, name') ?: [];

if (request_method() === 'POST') {
    $fullName        = post_string('full_name');
    $phone           = post_string('phone');
    $email           = mb_strtolower(trim(post_string('email')));
    $password        = post_string('password');
    $businessName    = post_string('business_name');
    $businessAddress = post_string('business_address');
    $businessPincode = post_string('business_pincode');
    $businessState   = post_string('business_state');
    $primaryCategoryId = (int)($_POST['primary_category_id'] ?? 0);

    $required = [
        'Full name'        => $fullName,
        'Email'            => $email,
        'Password'         => $password,
        'Business name'    => $businessName,
        'Business address' => $businessAddress,
        'Pincode'          => $businessPincode,
        'State'            => $businessState,
    ];
    foreach ($required as $label => $value) {
        if (trim((string)$value) === '') {
            flash_set('error', $label . ' is required.');
            redirect_to('merchant/signup');
        }
    }

    if ($primaryCategoryId <= 0) {
        flash_set('error', 'Please pick the primary category you want to sell in.');
        redirect_to('merchant/signup');
    }
    $catCheck = db_fetch_one($db, 'SELECT id FROM categories WHERE id = :id AND is_active = 1 LIMIT 1', ['id' => $primaryCategoryId]);
    if (!$catCheck) {
        flash_set('error', 'Selected category is not available.');
        redirect_to('merchant/signup');
    }

    if (strlen($password) < 6) {
        flash_set('error', 'Password must be at least 6 characters.');
        redirect_to('merchant/signup');
    }

    $existing = db_fetch_one($db, 'SELECT id FROM users WHERE email = :email LIMIT 1', ['email' => $email]);
    if ($existing) {
        flash_set('error', 'An account with this email already exists.');
        redirect_to('merchant/signup');
    }

    $hash = password_hash($password, PASSWORD_BCRYPT);

    $db->beginTransaction();
    try {
        db_exec($db, "INSERT INTO users (role, full_name, phone, email, password_hash, status, created_at)
                      VALUES ('merchant', :full_name, :phone, :email, :password_hash, 'active', NOW())", [
            'full_name'     => $fullName,
            'phone'         => $phone,
            'email'         => $email,
            'password_hash' => $hash,
        ]);
        $newUserId = (int)$db->lastInsertId();

        db_exec($db, "INSERT INTO merchant_profile
                        (merchant_user_id, status, primary_category_id, business_name, business_address, business_state, business_pincode, created_at)
                      VALUES
                        (:uid, 'pending', :cat, :bname, :baddr, :bstate, :bpin, NOW())", [
            'uid'    => $newUserId,
            'cat'    => $primaryCategoryId,
            'bname'  => $businessName,
            'baddr'  => $businessAddress,
            'bstate' => $businessState,
            'bpin'   => $businessPincode,
        ]);

        $db->commit();
    } catch (Throwable $t) {
        $db->rollBack();
        error_log('merchant/signup failed: ' . $t->getMessage());
        flash_set('error', 'Could not create account. Please try again.');
        redirect_to('merchant/signup');
    }

    flash_set('success', 'Account created. Your application is awaiting super-admin approval — you will be able to list products once approved.');
    redirect_to('merchant/login');
}

$content = function () use ($categories) {
    $error = flash_get('error');
    ?>
    <div class="container py-4" style="max-width: 600px;">
      <div class="bg-white border rounded-4 p-4 mm-card">
        <div class="d-flex align-items-center gap-2 mb-3">
          <i data-lucide="store" class="mm-icon"></i>
          <h1 class="h5 mb-0">Become a Seller on Manikya Market</h1>
        </div>
        <p class="text-muted small mb-3">
          Fill in your details. Your application will be reviewed by a super-admin before you can start selling.
        </p>

        <?php if ($error): ?>
          <div class="alert alert-danger"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" class="d-grid gap-3">
          <h6 class="text-muted small text-uppercase mb-0 mt-2">Your account</h6>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Full name *</label>
              <input class="form-control" name="full_name" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Phone</label>
              <input class="form-control" name="phone" type="tel" placeholder="+91...">
            </div>
            <div class="col-md-6">
              <label class="form-label">Email *</label>
              <input class="form-control" name="email" type="email" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Password *</label>
              <input class="form-control" name="password" type="password" minlength="6" required>
            </div>
          </div>

          <h6 class="text-muted small text-uppercase mb-0 mt-3">Business details</h6>
          <div>
            <label class="form-label">Business name *</label>
            <input class="form-control" name="business_name" required>
          </div>
          <div>
            <label class="form-label">Primary category you sell in *</label>
            <select class="form-select" name="primary_category_id" required>
              <option value="">— Choose a category —</option>
              <?php foreach ($categories as $c): ?>
                <option value="<?= (int)$c['id'] ?>"><?= e((string)$c['name']) ?></option>
              <?php endforeach; ?>
            </select>
            <div class="form-text">You can still list products in other categories later — this is your main focus area.</div>
          </div>
          <div>
            <label class="form-label">Business address *</label>
            <textarea class="form-control" name="business_address" rows="2" required></textarea>
          </div>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">State *</label>
              <input class="form-control" name="business_state" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Pincode *</label>
              <input class="form-control" name="business_pincode" required maxlength="12">
            </div>
          </div>

          <button class="btn btn-mm mt-2" type="submit">Submit application</button>
        </form>

        <div class="text-muted small mt-3">
          Already a merchant? <a href="?p=merchant/login">Sign in</a>
        </div>
      </div>
    </div>
    <?php
};

require __DIR__ . '/../../views/layout.php';
