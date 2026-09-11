<?
function linkedin_login_consent($credentials, $redirectUri)
{
    // Ensure 'app_id', 'scope', and 'redirect_uri' are provided in $credentials
    if (!isset($credentials['client_id'], $credentials['scope'])) {
        throw new InvalidArgumentException('Missing required credentials.');
    }

    // Build the login URL
    $loginUrl = 'https://www.linkedin.com/oauth/v2/authorization?' . http_build_query([
        'client_id' => $credentials['client_id'],
        'redirect_uri' => $redirectUri,
        'scope' => $credentials['scope'],
        'response_type' => 'code'
    ]);

    return $loginUrl;
}

function linkedin_generate_access_token($credentials, $code, $redirectUri)
{
    // Ensure 'client_id' and 'client_secret' are provided in $credentials
    if (!isset($credentials['client_id'], $credentials['client_secret'])) {
        throw new InvalidArgumentException('Missing required credentials.');
    }

    // Exchange the authorization code for an access token
    $clientId = $credentials['client_id'];
    $clientSecret = $credentials['client_secret'];
    $tokenUrl = 'https://www.linkedin.com/oauth/v2/accessToken';

    // Prepare the parameters for the request
    $params = [
        'grant_type' => 'authorization_code',
        'code' => $code,
        'redirect_uri' => $redirectUri,
        'client_id' => $clientId,
        'client_secret' => $clientSecret,
    ];

    // Initialize cURL
    $ch = curl_init();
    if ($ch === false) {
        throw new Exception('Failed to initialize cURL session.');
    }

    // Set cURL options
    curl_setopt($ch, CURLOPT_URL, $tokenUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // Execute cURL request
    $response = curl_exec($ch);
    curl_close($ch);

    // Decode the response
    $tokenData = json_decode($response, true);

    // Check for access token in the response
    if (isset($tokenData['access_token'])) {
        return $tokenData['access_token'];
    } else {
        throw new Exception('Error obtaining access token: ' . json_encode($tokenData));
    }
}

function postToLinkedIn($accessToken, $title, $url, $text, $description)
{
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

    echo "<pre>";
    echo $response; // Output the response from LinkedIn
    echo "</pre>";
}

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
?>
