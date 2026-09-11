<?php
ini_set('display_startup_errors', 1);

include 'linkedin_function.php';
include '../db_connect.php';


if (isset($_GET['code'])) {
    // Load credentials from JSON file
    $credentials = json_decode(file_get_contents('linkedin_credentials.json'), true);
    
    // Check if credentials are loaded correctly
    if ($credentials === null) {
        throw new Exception('Failed to load credentials from credential.json.');
    }
    $code = $_GET['code'];

    // Attempt to obtain the access token
    try {
        $accessToken = linkedin_generate_access_token($db, $credentials, $code, 'https://newsjunction.net/social_media/linkedin/linkedinPost.php');

        $title = 'Knobly Cream';
        $url = 'https://newsjunction.net';
        $text = 'Welcome to Knobly Cream';
        $description = 'Create | Reach | Measure';

        // Use the access token to post to LinkedIn
        postToLinkedIn($accessToken, $title, $url, $text, $description);
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
        exit;
    }
} else {
    echo "No authorization code provided.";
    exit;
}