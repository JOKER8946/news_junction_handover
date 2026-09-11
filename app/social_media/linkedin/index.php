<?
ini_set('display_startup_errors', 1);

include 'function.php';

$credentials = json_decode(file_get_contents('linkedin_credentials.json'), true);

if ($credentials === null) {
    throw new Exception('Failed to load credentials from credential.json.');
}

$authUrl = linkedin_login_consent($credentials, "https://newsjunction.net/social_media/linkedin/linkedinPost.php");

header("Location: $authUrl");
exit();
?>