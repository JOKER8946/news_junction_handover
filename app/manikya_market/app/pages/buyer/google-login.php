<?php

declare(strict_types=1);

$config = google_oauth_get_config($db);
if (!$config) {
    flash_set('error', 'Google sign-in is not configured. Please contact the merchant.');
    redirect_to('buyer/login');
}

$state = bin2hex(random_bytes(16));
$_SESSION['google_oauth_state'] = $state;

$url = google_oauth_authorize_url($config['client_id'], $state);
header('Location: ' . $url);
exit;
