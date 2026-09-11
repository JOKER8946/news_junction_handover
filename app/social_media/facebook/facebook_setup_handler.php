<?php
ini_set('display_startup_errors', 1);

include 'fb_function.php';
include '../db_connect.php';
include '../../inc/validate.logged.php';

$credentials = json_decode(file_get_contents('fb_credentials.json'), true);
$appId = $credentials['app_id'];
$appSecret = $credentials['appSecret'];

if (isset($_GET['code'])) {
    $shortLivedToken = facebook_generate_short_live_code($db, $credentials);
    echo 'Short-Lived Access Token: ' . $shortLivedToken . '<br>';
    $pageTokens = facebook_generate_page_tokens($db, $shortLivedToken);
    // echo 'Page Token: <pre>' ;
    // print_r($pageTokens);
    // echo  '</pre><br>';

    // Display pages for selection
    if ($pageTokens) {
        header("Location: facebook_setup.php"); // Use Location header for redirection
        exit(); // Always exit after a header redirect
    } else {
        echo "No Pages Found";
        header("Location: facebook_setup.php?log=npf"); // Use Location header
        exit(); // Always exit after a header redirect
    }
} else {
    echo 'No code parameter found.';
    header("Location: facebook_setup.php"); // Use Location header
    exit(); // Always exit after a header redirect
}

?>