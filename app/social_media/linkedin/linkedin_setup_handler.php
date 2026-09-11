<?php
ini_set('display_startup_errors', 1);

include 'linkedin_function.php';
include '../db_connect.php';
include '../../assets/php/validate.logged.php';

$credentials = json_decode(file_get_contents('linkedin_credentials.json'), true);
$appId = $credentials['client_id'];
$appSecret = $credentials['client_secret'];

if (isset($_GET['code'])) {
    $code = $_GET['code'];
    try {
        $accessToken = linkedin_generate_access_token($db, $credentials, $code, 'https://newsjunction.net/social_media/linkedin/linkedin_setup_handler.php');
        if ($accessToken) {
            if (linkedin_update_profile($db, $accessToken)) {
?>
                <script>
                    alert("Account Created/Updated Successfully");
                </script>
        <?php
            };
        }
    } catch (Exception $e) {
        ?>
        <script>
            alert("<? echo "Error: " . $e->getMessage(); ?>");
        </script>
    <?php

    }
} else {
    ?>
    <script>
        alert("<? echo "No authorization code provided."; ?>")
    </script>
<?
}
header("Location: linkedin_setup.php"); // Use Location header
exit(); // Always exit after a header redirect
?>