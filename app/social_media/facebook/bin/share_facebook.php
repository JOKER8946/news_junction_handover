<?php
ini_set('display_startup_errors', 1);

include 'function.php';
include 'db_connect.php';
include '../inc/validate.logged.php';


// Facebook App Credentials
$credentials = json_decode(file_get_contents('credential.json'), true);
$appId = $credentials['app_id'];
$appSecret = $credentials['appSecret'];
$redirectUri = 'https://newsjunction.net/testing/share_facebook.php'; // Ensure this URL matches the one registered with Facebook

if (isset($_POST['page_token']) && $_POST['page_id']) {
    // Handle form submission to select a page
    $longLivedToken = facebook_generate_long_live_token($db, $_POST['page_id'], $_POST['page_token']);

    echo "<a href='https://newsjunction.net/testing/accessTest.php'> Test the permission</a><br>";
    echo "<a href='https://newsjunction.net/testing/postTest.php?pageId=".$_POST['page_id']."'> Test Sending the Post to Facebook</a>";
} // Check if there's a code parameter in the query string
else if (isset($_GET['code'])) {
    $shortLivedToken = facebook_generate_short_live_code($db, $credentials);
    echo 'Short-Lived Access Token: ' . $shortLivedToken . '<br>';
    $pageTokens = facebook_generate_page_tokens($db, $shortLivedToken);
    // Display pages for selection
    echo "<form method='POST' action=''>";
    echo "<h3>Select a page:</h3>";
    foreach ($pageTokens as $index => $page) {
        echo "<input type='radio' name='page_token' value='" . htmlspecialchars($page['access_token']) . "' id='page" . ($index + 1) . "'>";
        echo "<label for='page" . ($index + 1) . "'>" . htmlspecialchars($page['name']) . " (Page ID: " . htmlspecialchars($page['id']) . ")</label>";
        
        // Add a hidden input for the page ID
        echo "<input type='hidden' name='page_id' value='" . htmlspecialchars($page['id']) . "'>";
        echo "<br>";
    }
    echo "<input type='submit' value='Select Page'>";
    echo "</form>";
} else {
    echo 'No code parameter found.';
}