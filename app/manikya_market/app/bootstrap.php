<?php

declare(strict_types=1);

// PHP errors were being written nowhere: error_log is unset in php.ini, and
// nothing from this app ever reached the Apache vhost log — so fatals showed
// as a bare HTTP 500 with no trace. Send them to a file we control instead.
// display_errors stays off: buyers must never see internals.
ini_set('log_errors', '1');
ini_set('display_errors', '0');
ini_set('error_log', __DIR__ . '/logs/php-error.log');

session_start();

mb_internal_encoding('UTF-8');
date_default_timezone_set('Asia/Kolkata');

$configPath = __DIR__ . '/config.php';
if (!is_file($configPath)) {
    header('Location: /setup.php');
    exit;
}

$config = require $configPath;

// Make config available globally for helpers (redirects, base path)
$GLOBALS['config'] = $config;

require __DIR__ . '/lib/db.php';
require __DIR__ . '/lib/auth.php';
require __DIR__ . '/lib/cart.php';
require __DIR__ . '/lib/inventory.php';
require __DIR__ . '/lib/helpers.php';
require __DIR__ . '/lib/referral.php';
// Ekart Logistics is the active courier. The library exposes ekart_* functions
// and back-compat shims for legacy delhivery_* names so any call site we haven't
// migrated yet still works. The old delhivery.php is no longer loaded.
require __DIR__ . '/lib/ekart.php';
require __DIR__ . '/lib/google_oauth.php';
require __DIR__ . '/lib/razorpay.php';
require __DIR__ . '/lib/notify.php';
require __DIR__ . '/lib/wishlist.php';
require __DIR__ . '/lib/coupons.php';
require __DIR__ . '/lib/alerts.php';

$basePath = app_base_path($config);
$setupUrl = ($basePath !== '' ? $basePath : '/') . '/setup.php';

try {
    $db = db_connect($config);
} catch (Throwable $t) {
    header('Location: ' . $setupUrl . '?error=' . rawurlencode('Database not found or connection failed. Please run setup.'));
    exit;
}
