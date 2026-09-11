<?php

declare(strict_types=1);

$config = google_oauth_get_config($db);
if (!$config) {
    flash_set('error', 'Google sign-in is not configured.');
    redirect_to('buyer/login');
}

$state = (string)($_GET['state'] ?? '');
$savedState = (string)($_SESSION['google_oauth_state'] ?? '');
unset($_SESSION['google_oauth_state']);

if ($state === '' || $savedState === '' || !hash_equals($savedState, $state)) {
    flash_set('error', 'Invalid sign-in attempt. Please try again.');
    redirect_to('buyer/login');
}

if (isset($_GET['error'])) {
    flash_set('error', 'Google sign-in was cancelled.');
    redirect_to('buyer/login');
}

$code = (string)($_GET['code'] ?? '');
if ($code === '') {
    flash_set('error', 'Missing authorization code.');
    redirect_to('buyer/login');
}

$token = google_oauth_exchange_code($code, $config['client_id'], $config['client_secret']);
if (!$token) {
    flash_set('error', 'Failed to verify Google sign-in. Please try again.');
    redirect_to('buyer/login');
}

$profile = google_oauth_get_userinfo((string)$token['access_token']);
if (!$profile) {
    flash_set('error', 'Could not fetch your Google profile. Please try again.');
    redirect_to('buyer/login');
}

$email = trim(mb_strtolower((string)($profile['email'] ?? '')));
$emailVerified = !empty($profile['email_verified']);
$fullName = trim((string)($profile['name'] ?? ''));
if ($email === '' || !$emailVerified) {
    flash_set('error', 'Your Google email must be verified to sign in.');
    redirect_to('buyer/login');
}

// Look up existing user
$user = db_fetch_one($db, "SELECT id, role, status FROM users WHERE email = :email LIMIT 1", ['email' => $email]);

if ($user) {
    if ((string)$user['role'] !== 'buyer') {
        flash_set('error', 'This email is registered under a different account type. Please use email/password login.');
        redirect_to('buyer/login');
    }
    if ((string)$user['status'] !== 'active') {
        flash_set('error', 'Your account is not active. Please contact support.');
        redirect_to('buyer/login');
    }
    if (session_status() === PHP_SESSION_ACTIVE) { session_regenerate_id(true); }
    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['role'] = 'buyer';
    flash_set('success', 'Signed in with Google.');
    redirect_to('home');
}

// Create new buyer account
$randomPassword = bin2hex(random_bytes(16));
$hash = password_hash($randomPassword, PASSWORD_DEFAULT);

db_exec($db, "INSERT INTO users (role, full_name, phone, email, password_hash, status, created_at) VALUES ('buyer', :full_name, :phone, :email, :hash, 'active', NOW())", [
    'full_name' => $fullName !== '' ? $fullName : 'Customer',
    'phone' => '',
    'email' => $email,
    'hash' => $hash,
]);

$newUserId = (int)$db->lastInsertId();
referral_generate_code($db, $newUserId);

if (session_status() === PHP_SESSION_ACTIVE) { session_regenerate_id(true); }
$_SESSION['user_id'] = $newUserId;
$_SESSION['role'] = 'buyer';

flash_set('success', 'Welcome! Your account has been created with Google.');
redirect_to('home');
