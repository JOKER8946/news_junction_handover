<?php
header('Content-Type: application/json');

include '../../inc/validate.logged.php';
include '../db_connect.php';
// include 'linkedin_function.php';

$data = json_decode(file_get_contents('php://input'), true);

class AttributeException extends Exception {}
class PostException extends Exception {}
class TokenException extends Exception {}

function getUserId($accessToken)
{
    $url = 'https://api.linkedin.com/v2/userinfo'; // LinkedIn API endpoint to get user profile

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $accessToken",
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    curl_close($ch);

    // Check for errors in the response
    if ($response === false) {
        throw new Exception("Error fetching user ID: " . curl_error($ch));
    }

    $userData = json_decode($response, true);

    // Check if we received the expected data
    if (isset($userData['sub'])) {
        return $userData['sub']; // Extract the user's ID
    } else {
        throw new Exception("User ID not found in response: " . $response);
    }
}

function linkedin_fetch_tokens($db, $userId)
{
    $accessToken = '';
    $stmt = $db->prepare("SELECT access_token FROM linkedin_tokens WHERE userId = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->bind_result($accessToken);
    if ($stmt->fetch()) {
        return $accessToken;
    } else {
        return null; // Return null if no token is found
    }
    // Close the statement
    $stmt->close();
}

function linkedin_store_posts($db, $userId, $id)
{
    $stmt = $db->prepare("INSERT INTO linkedin_posts(userId, urn) VALUES (?, ?)");
    if ($stmt === false) {
        die('Prepare error: ' . htmlspecialchars($db->error));
    }
    $stmt->bind_param("is", $userId, $id);
    if ($stmt->execute()) {
        return $db->insert_id;
    } else {
        die('Execute error: ' . htmlspecialchars($stmt->error));
    }
    $stmt->close();
}

function postToLinkedIn($accessToken, $title, $url, $text, $description)
{
    global $db, $gUserId;
    $postUrl = 'https://api.linkedin.com/v2/ugcPosts';
    $personId = getUserId($accessToken); // Get the person's ID using the access token

    $data = [
        "author" => "urn:li:person:$personId",
        "lifecycleState" => "PUBLISHED",
        "specificContent" => [
            "com.linkedin.ugc.ShareContent" => [
                "shareCommentary" => [
                    "text" => $text
                ],
                "shareMediaCategory" => "ARTICLE",
                "media" => array([
                    "status" => "READY",
                    "description" => [
                        "text" => $description
                    ],
                    "originalUrl" => $url,
                    "title" => [
                        "text" => $title
                    ]
                ])
            ]
        ],
        "visibility" => [
            "com.linkedin.ugc.MemberNetworkVisibility" => "PUBLIC"
        ]
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $postUrl);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $accessToken",
        "Content-Type: application/json"
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    curl_close($ch);

    $resultData = [];

    // Decode the response
    $responseData = json_decode($response, true);

    if (isset($responseData['id'])) {
        $resultData = array("status" => "success");
        $resultData['id'] = $responseData['id'];
        linkedin_store_posts($db, $gUserId, $responseData['id']);
        echo json_encode($resultData);
    } else {
        echo json_encode(["status" => "error", "error_message" => $response]);
    }
}

$accessToken = linkedin_fetch_tokens($db, $gUserId);
postToLinkedIn($accessToken, $data['title'], $data['url'], $data['text'], $data['description']);
