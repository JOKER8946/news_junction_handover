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

function linkedin_generate_access_token($db, $credentials, $code, $redirectUri)
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
        linkedin_save_code($db, $tokenData);
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

    echo "<pre>";
    print_r($userData); // Output the response from LinkedIn
    echo "</pre>";

    // Check if we received the expected data
    if (isset($userData['sub'])) {
        return $userData['sub']; // Extract the user's ID
    } else {
        throw new Exception("User ID not found in response: " . $response);
    }
}

function linkedin_save_code($db, $tokenData)
{
    global $gUserId;

    // Prepare the SQL statement using INSERT ... ON DUPLICATE KEY UPDATE
    $sql = "INSERT INTO linkedin_tokens (userId, access_token, expires_in, refresh_token, rt_expires_in, id_token) 
            VALUES (?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                access_token = VALUES(access_token),
                expires_in = VALUES(expires_in),
                refresh_token = VALUES(refresh_token),
                rt_expires_in = VALUES(rt_expires_in),
                id_token = VALUES(id_token)";

    // Prepare the statement
    $stmt = mysqli_prepare($db, $sql);
    if (!$stmt) {
        throw new RuntimeException('Error preparing statement: ' . mysqli_error($db));
    }

    // Bind parameters: userId (int), access_token (string), expires_in (int), refresh_token (string), rt_expires_in (int), id_token (string)
    $access_token_expiry = convert_time($tokenData['expires_in']);
    $refresh_token_expiry = convert_time($tokenData['refresh_token_expires_in']);

    // Now bind parameters
    mysqli_stmt_bind_param(
        $stmt,
        "isssss",
        $gUserId,
        $tokenData['access_token'],
        $access_token_expiry,
        $tokenData['refresh_token'],
        $refresh_token_expiry,
        $tokenData['id_token']
    );

    // Execute the statement
    if (mysqli_stmt_execute($stmt)) {
        return $tokenData['access_token']; // Return the saved access token
    } else {
        throw new RuntimeException('Error executing statement: ' . mysqli_stmt_error($stmt));
    }
}

function convert_time($timestamp)
{
    // Get the current time in epoch format
    $current_time = time();

    // Add the current time to the provided timestamp
    $result_time = $timestamp + $current_time;

    // Return the result in MySQL TIMESTAMP format
    return date('Y-m-d H:i:s', $result_time);
}

function linkedin_login_status($db, $gUserId)
{
    // Prepare the SQL statement to prevent SQL injection
    $stmt = $db->prepare("SELECT COUNT(*) AS count FROM linkedin_profile WHERE userId = ?");

    // Bind the parameter
    $stmt->bind_param("i", $gUserId);

    // Execute the statement
    $stmt->execute();

    // Get the result
    $result = $stmt->get_result();

    // Fetch the count from the result
    $row = $result->fetch_assoc(); // Use fetch_assoc to get an associative array

    // Check the count
    if ($row['count'] > 0) {
        return true;
    } else {
        return false;
    }
}

function linkedin_update_profile($db, $accessToken)
{
    global $gUserId;
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

    // echo "<pre>";
    // print_r($userData); // Output the response from LinkedIn
    // echo "</pre>";


    $sql = "INSERT INTO linkedin_profile (userId, name, profile_urn, country, lang, given_name, family_name, picture) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                name = VALUES(name),
                profile_urn = VALUES(profile_urn),
                country = VALUES(country),
                lang = VALUES(lang),
                given_name = VALUES(given_name),
                family_name = VALUES(family_name),
                picture = VALUES(picture) ";

    // Prepare the statement
    $stmt = mysqli_prepare($db, $sql);
    if (!$stmt) {
        throw new RuntimeException('Error preparing statement: ' . mysqli_error($db));
    }

    mysqli_stmt_bind_param(
        $stmt,
        "isssssss",
        $gUserId,
        $userData['name'],
        $userData['sub'],
        $userData['locale']['country'],
        $userData['locale']['language'],
        $userData['given_name'],
        $userData['family_name'],
        $userData['picture']
    );

    // Execute the statement
    if (mysqli_stmt_execute($stmt)) {
        return true; // Return the saved access token
    } else {
        throw new RuntimeException('Error executing statement: ' . mysqli_stmt_error($stmt));
    }
}

function linkedin_display_profile($db, $userId)
{
    // Prepare the SQL statement
    $stmt = $db->prepare("SELECT * FROM linkedin_profile WHERE userId = ?");

    // Bind the parameter
    $stmt->bind_param("i", $userId); // "i" indicates that the parameter is an integer

    // Execute the statement
    if ($stmt->execute()) {
        // Get the result
        $result = $stmt->get_result();

        // Check if a profile was found
        if ($result->num_rows > 0) {
            // Fetch the profile data
            $profile = $result->fetch_assoc();
?>
            <div class="col" style="margin-top: 30px; margin-left: 20px;">
                <div class="profile-card">
                    <div class="profile-image"><img src="<?= $profile['picture'] ?>" alt=""></div>
                    <div class="profile-info">
                        <h2><?= $profile['name'] ?></h2>
                        <!-- <a href="<?= getLinkedInProfileUrl($profile['profile_urn']) ?>" class="linkedin-button">LinkedIn: Go to my profile</a><br> -->
                        <!-- <div class="info-button">
                            <span>i</span>
                            <div class="tooltip">
                                This is the information you want to show!
                            </div>
                        </div> -->
                    </div>
                </div>

            </div>
            <style>
                .profile-card {
                    display: flex;
                    align-items: center;
                    background: white;
                    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
                    border-radius: 8px;
                    overflow: hidden;
                    max-width: 600px;
                    width: 100%;
                }

                .profile-image {
                    width: 100px;
                    height: 100px;
                    background-color: #007bff;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    margin: 20px;
                    flex-shrink: 0;
                    border-radius: 50%;
                    overflow: hidden;
                    /* Ensure image is clipped to circle */
                }

                .profile-info {
                    flex-grow: 1;
                    padding: 20px;
                }

                .profile-info h2 {
                    margin: 0;
                    font-size: 22px;
                }

                .linkedin-button {
                    margin: 10px 0;
                    background-color: #0077b5;
                    color: white;
                    padding: 8px;
                    border: none;
                    border-radius: 4px;
                    cursor: pointer;
                    text-decoration: none;
                    display: inline-block;
                    font-size: 14px;
                }

                .info-button {
                    position: relative;
                    display: inline-block;
                    width: 20px;
                    height: 20px;
                    color: #4a5561;
                    border-radius: 50%;
                    text-align: center;
                    line-height: 20px;
                    cursor: pointer;
                    font-size: 10px;
                    border: 1px solid #4a5561;
                }

                .tooltip {
                    visibility: hidden;
                    width: 200px;
                    background-color: #333;
                    color: #fff;
                    text-align: center;
                    border-radius: 5px;
                    padding: 5px;
                    position: absolute;
                    z-index: 1;
                    bottom: 125%;
                    left: 50%;
                    margin-left: -60px;
                    opacity: 0;
                    transition: opacity 0.3s;
                }

                .info-button:hover .tooltip {
                    visibility: visible;
                    opacity: 1;
                }
            </style>

    <?
            // Add more fields as necessary
        } else {
            echo "No profile found for this user.";
        }
    } else {
        echo "Error executing query: " . $stmt->error;
    }

    // Close the statement
    $stmt->close();

    ?>

    <?
}

function getLinkedInProfileUrl($urn)
{

    // Construct the LinkedIn profile URL
    return "https://www.linkedin.com/in/" . $urn;
}

function linkedin_send_profile($db, $userId)
{
    // Prepare the SQL statement
    $stmt = $db->prepare("SELECT * FROM linkedin_profile WHERE userId = ?");

    // Bind the parameter
    $stmt->bind_param("i", $userId); // "i" indicates that the parameter is an integer

    // Execute the statement
    if ($stmt->execute()) {
        // Get the result
        $result = $stmt->get_result();

        // Check if a profile was found
        if ($result->num_rows > 0) {
            // Fetch the profile data
            $profile = $result->fetch_assoc();
            // Add more fields as necessary
    ?>
            <div class="col">
                <div class="profile-card">
                    <div class="profile-image"><img src="<?= $profile['picture'] ?>" alt=""></div>
                    <div class="profile-info">
                        <h2><?= $profile['name'] ?></h2>                        
                    </div>
                </div>
            </div>
            <style>
                .profile-card {
                    display: flex;
                    align-items: center;
                    background: white;
                    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
                    border-radius: 8px;
                    overflow: hidden;
                    max-width: 600px;
                    width: 100%;
                }

                .profile-image {
                    width: 100px;
                    height: 100px;
                    background-color: #007bff;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    margin: 20px;
                    flex-shrink: 0;
                    border-radius: 50%;
                    overflow: hidden;
                    /* Ensure image is clipped to circle */
                }

                .profile-info {
                    flex-grow: 1;
                    padding: 20px;
                }

                .profile-info h2 {
                    margin: 0;
                    font-size: 22px;
                }

                .linkedin-button {
                    margin: 10px 0;
                    background-color: #0077b5;
                    color: white;
                    padding: 8px;
                    border: none;
                    border-radius: 4px;
                    cursor: pointer;
                    text-decoration: none;
                    display: inline-block;
                    font-size: 14px;
                }

                .info-button {
                    position: relative;
                    display: inline-block;
                    width: 20px;
                    height: 20px;
                    color: #4a5561;
                    border-radius: 50%;
                    text-align: center;
                    line-height: 20px;
                    cursor: pointer;
                    font-size: 10px;
                    border: 1px solid #4a5561;
                }

                .tooltip {
                    visibility: hidden;
                    width: 200px;
                    background-color: #333;
                    color: #fff;
                    text-align: center;
                    border-radius: 5px;
                    padding: 5px;
                    position: absolute;
                    z-index: 1;
                    bottom: 125%;
                    left: 50%;
                    margin-left: -60px;
                    opacity: 0;
                    transition: opacity 0.3s;
                }

                .info-button:hover .tooltip {
                    visibility: visible;
                    opacity: 1;
                }
            </style>
<?

        } else {
            echo "No profile found for this user.";
        }
    } else {
        echo "Error executing query: " . $stmt->error;
    }

    // Close the statement
    $stmt->close();
}
