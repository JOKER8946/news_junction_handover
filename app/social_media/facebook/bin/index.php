<?php
ini_set('display_startup_errors', 1);

include 'function.php';

$credentials = json_decode(file_get_contents('credential.json'), true);

$loginUrl = facebook_login_consent($credentials, 'https://newsjunction.net/testing/share_facebook.php');

echo "<b>Login Url: " . htmlspecialchars($loginUrl) . "</b><br>";

echo 'Login URL: <a href="' . htmlspecialchars($loginUrl) . '">Login with Facebook</a>';

?>