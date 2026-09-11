<?
function facebook_login_consent($credentials, $redirectUri)
{
    // Ensure 'app_id', 'scope', and 'redirect_uri' are provided in $credentials
    if (!isset($credentials['app_id'], $credentials['scope'])) {
        throw new InvalidArgumentException('Missing required credentials.');
    }

    // Build the login URL
    $loginUrl = 'https://www.facebook.com/v20.0/dialog/oauth?' . http_build_query([
        'client_id' => $credentials['app_id'],
        'redirect_uri' => $redirectUri,
        'scope' => $credentials['scope'],
        'response_type' => 'code'
    ]);

    return $loginUrl;
}

function facebook_generate_short_live_code($db, $credentials)
{
    $redirectUri = 'https://www.newsjunction.net/social_media/facebook/facebook_setup_handler.php';

    // Validate credentials
    if (!isset($credentials['app_id'], $credentials['appSecret'])) {
        throw new InvalidArgumentException('Missing required credentials: app_id and appSecret must be provided.');
    }

    // Check for 'code' in the query parameters
    if (empty($_GET['code'])) {
        throw new InvalidArgumentException('Authorization code is missing.');
    }

    // Prepare the token exchange URL
    $tokenUrl = 'https://graph.facebook.com/v20.0/oauth/access_token?' . http_build_query([
        'client_id' => $credentials['app_id'],
        'redirect_uri' => $redirectUri,
        'client_secret' => $credentials['appSecret'],
        'code' => $_GET['code']
    ]);

    // Initialize cURL
    $ch = curl_init($tokenUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // Execute the cURL request and handle errors
    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        $errorMessage = curl_error($ch);
        curl_close($ch);
        throw new RuntimeException("cURL error: $errorMessage");
    }

    curl_close($ch);
    $responseData = json_decode($response, true);

    echo "<pre>";
    print_r($responseData);
    echo "</pre>";

    // Check for access token in the response
    if (isset($responseData['access_token'])) {
        return facebook_save_short_live_code($db, $responseData['access_token']);
    } else {
        $errorDetail = isset($responseData['error']['message']) ? $responseData['error']['message'] : 'Unknown error';
        throw new RuntimeException("Error obtaining short-lived token: $errorDetail");
    }
}

function facebook_save_short_live_code($db, $accessToken)
{
    global $gUserId;

    // Prepare the SQL statement using INSERT ... ON DUPLICATE KEY UPDATE
    $sql = "INSERT INTO fb_short_code (userId, access_token) VALUES (?, ?)
            ON DUPLICATE KEY UPDATE access_token = ?";

    // Prepare the statement
    $stmt = mysqli_prepare($db, $sql);
    if (!$stmt) {
        throw new RuntimeException('Error preparing statement: ' . mysqli_error($db));
    }

    // Bind parameters: userId (int), accessToken (string), accessToken (string) for update
    mysqli_stmt_bind_param($stmt, "iss", $gUserId, $accessToken, $accessToken);

    // Execute the statement
    if (mysqli_stmt_execute($stmt)) {
        return $accessToken; // Return the saved access token
    } else {
        throw new RuntimeException('Error executing statement: ' . mysqli_stmt_error($stmt));
    }
}

function facebook_generate_page_tokens($db, $shortLivedToken)
{
    // Get Page Access Token Using User Access Token
    $pageTokenUrl = 'https://graph.facebook.com/v20.0/me/accounts?' . http_build_query([
        'access_token' => $shortLivedToken
    ]);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $pageTokenUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        curl_close($ch);
        throw new RuntimeException('cURL error: ' . curl_error($ch));
    }

    curl_close($ch);

    $data = json_decode($response, true);

    // echo "<pre>";
    // print_r($data);
    // echo "</pre>";

    // Check for errors in the response
    if (isset($data['error'])) {
        throw new RuntimeException('Error: ' . $data['error']['message']);
    }

    // Save the page tokens to the database
    if(!empty($data['data'])){
        facebook_save_page_tokens($db, $data['data']); // Pass the database connection and page tokens
    } else {
        return null;
    }
    

    return $data['data']; // Returns an array of pages and their respective access tokens
}

function facebook_save_page_tokens($db, $pageTokens)
{
    global $gUserId; // Ensure $gUserId is available in the context

    // Fetch existing page tokens from the database
    $sql = "SELECT pages_id, pages_name ,pages_token FROM fb_pages WHERE userId = ?";
    $stmt = mysqli_prepare($db, $sql);

    if (!$stmt) {
        throw new RuntimeException('Error preparing statement: ' . mysqli_error($db));
    }

    mysqli_stmt_bind_param($stmt, "i", $gUserId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    // Store existing tokens in an array for quick lookup
    $existingPages = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $existingPages[] = $row['pages_id'];
    }

    // Check and save new page tokens
    foreach ($pageTokens as $pageToken) {
        if (!in_array($pageToken['id'], $existingPages)) {
            facebook_insert_page_token($db, $gUserId, $pageToken['id'], $pageToken['name'], $pageToken['access_token']);
        } else {
            facebook_update_page_token($db, $gUserId, $pageToken['id'], $pageToken['access_token']);
        }
    }
}

function facebook_insert_page_token($db, $userId, $pagesId, $pageName, $accessToken)
{
    $sql = "INSERT INTO fb_pages (userId, pages_id, pages_name, pages_token) VALUES (?, ?, ?, ?)";
    $stmt = mysqli_prepare($db, $sql);

    if (!$stmt) {
        throw new RuntimeException('Error preparing statement: ' . mysqli_error($db));
    }

    mysqli_stmt_bind_param($stmt, "isss", $userId, $pagesId, $pageName, $accessToken);

    if (!mysqli_stmt_execute($stmt)) {
        throw new RuntimeException('Error inserting record: ' . mysqli_stmt_error($stmt));
    }
}

function facebook_update_page_token($db, $userId, $pagesId, $accessToken)
{
    // Step 1: Prepare the SELECT statement
    $sql = "SELECT pages_token FROM fb_pages WHERE pages_id = ? AND userId = ?";
    $stmt = mysqli_prepare($db, $sql);
    if (!$stmt) {
        throw new RuntimeException('Error preparing statement: ' . mysqli_error($db));
    }

    // Step 2: Bind the parameters and execute the statement
    mysqli_stmt_bind_param($stmt, "is", $pagesId, $userId);
    mysqli_stmt_execute($stmt);

    // Step 3: Get the result
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);

    // Step 4: Check if the access token matches
    if ($accessToken !== $row['pages_token']) {
        // Step 5: Prepare the UPDATE statement
        $sql = "UPDATE fb_pages SET pages_token = ? WHERE pages_id = ?";
        $updateStmt = mysqli_prepare($db, $sql);
        if (!$updateStmt) {
            throw new RuntimeException('Error preparing update statement: ' . mysqli_error($db));
        }

        // Step 6: Bind the parameters for the update
        mysqli_stmt_bind_param($updateStmt, "si", $accessToken, $pagesId);

        // Step 7: Execute the update
        if (!mysqli_stmt_execute($updateStmt)) {
            throw new RuntimeException('Error executing update statement: ' . mysqli_error($db));
        }

        // Close the update statement
        mysqli_stmt_close($updateStmt);
    }

    // Close the select statement
    mysqli_stmt_close($stmt);
}

function facebook_generate_long_live_token($db, $pageId, $pageToken)
{
    // Load credentials from the JSON file
    $credentials = json_decode(file_get_contents('credential.json'), true);

    if (!$credentials || !isset($credentials['app_id'], $credentials['appSecret'])) {
        throw new RuntimeException('Invalid or missing credentials in credential.json');
    }

    // Exchange the short-lived page token for a long-lived token
    $longLivedTokenUrl = 'https://graph.facebook.com/v20.0/oauth/access_token?' . http_build_query([
        'grant_type' => 'fb_exchange_token',
        'client_id' => $credentials['app_id'],
        'client_secret' => $credentials['appSecret'],
        'fb_exchange_token' => $pageToken,
    ]);


    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $longLivedTokenUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        curl_close($ch);
        throw new RuntimeException('cURL error: ' . curl_error($ch));
    }

    curl_close($ch);
    $longLivedResponseData = json_decode($response, true);

    echo "<pre>";
    print_r($longLivedResponseData);
    echo "</pre>";

    if (isset($longLivedResponseData['access_token'])) {
        $longLivedToken = $longLivedResponseData['access_token'];
        echo 'Long-Lived Access Token: ' . $longLivedToken . '<br>';

        // Check if the long-lived token already exists in the database
        if (!facebook_check_long_live_token($db, $pageId, $longLivedToken)) {
            echo "Saving<br>";
            facebook_save_long_live_token($db, $pageId, $longLivedToken);
        } else {
            echo "Updating<br>";
            facebook_update_long_live_token($db, $pageId, $longLivedToken);
        }
        return $longLivedToken;
    } else {
        throw new RuntimeException('Error exchanging short-lived token for long-lived token: ' . (isset($longLivedResponseData['error']['message']) ? $longLivedResponseData['error']['message'] : 'Unknown error'));
    }
}

function facebook_check_long_live_token($db, $pageId, $longLivedToken)
{
    $sql = "SELECT * FROM fb_long_lived_token WHERE pageId = ? AND token = ?";
    $stmt = mysqli_prepare($db, $sql);

    if (!$stmt) {
        throw new RuntimeException('Error preparing statement: ' . mysqli_error($db));
    }

    mysqli_stmt_bind_param($stmt, "ss", $pageId, $longLivedToken);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    // Check if the token exists in the database
    return mysqli_num_rows($result) > 0;
}

function facebook_save_long_live_token($db, $pageId, $longLivedToken)
{
    global $gUserId; // Ensure $gUserId is available in the context

    try {
        $sql = "INSERT INTO fb_long_lived_token (userId, pageId, token) VALUES (?, ?, ?)";
        $stmt = mysqli_prepare($db, $sql);

        if (!$stmt) {
            throw new RuntimeException('Error preparing statement: ' . mysqli_error($db));
        }

        mysqli_stmt_bind_param($stmt, "iis", $gUserId, $pageId, $longLivedToken);

        if (!mysqli_stmt_execute($stmt)) {
            throw new RuntimeException('Error inserting record: ' . mysqli_stmt_error($stmt));
        }

        echo "<br>Long lived access token successfully saved..<br>";
    } catch (RuntimeException $e) {
        echo "Failed to save long-lived access token: " . $e->getMessage();
    }
}

function facebook_update_long_live_token($db, $pageId, $longLivedToken)
{
    global $gUserId; // Ensure $gUserId is available in the context

    // Correct the SQL query to use UPDATE syntax
    $sql = "UPDATE fb_long_lived_token SET token = ? WHERE userId = ? AND pageId = ?";
    $stmt = mysqli_prepare($db, $sql);

    if (!$stmt) {
        throw new RuntimeException('Error preparing statement: ' . mysqli_error($db));
    }

    mysqli_stmt_bind_param($stmt, "sis", $longLivedToken, $gUserId, $pageId);

    if (!mysqli_stmt_execute($stmt)) {
        throw new RuntimeException('Error updating record: ' . mysqli_stmt_error($stmt));
    }

    mysqli_stmt_close($stmt); // Close the statement

    echo "<br>Long lived access token successfully updated..<br>";
}

function facebook_access_test($accessToken)
{
    $url = 'https://graph.facebook.com/v20.0/me?fields=id,name&access_token=' . urlencode($accessToken);

    // Initialize cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // Execute the request
    $response = curl_exec($ch);

    // Check if there was an error with cURL
    if (curl_errno($ch)) {
        curl_close($ch);
        throw new RuntimeException('cURL error: ' . curl_error($ch));
    }

    curl_close($ch);

    // Decode the response
    $user = json_decode($response, true);

    echo "<pre>";
    print_r($user);
    echo "</pre>";

    // Check if there was an error in the API response
    if (isset($user['error'])) {
        throw new RuntimeException('Error: ' . $user['error']['message']);
    }

    // Return the user data as an array
    return [
        'id' => $user['id'],
        'name' => $user['name']
    ];
}

function facebook_post_to_page($db, $message, $link)
{
    // Load credentials from the JSON file
    $credentials = json_decode(file_get_contents('credential.json'), true);

    if (!$credentials || !isset($credentials['longLivedToken'])) {
        throw new RuntimeException('Invalid or missing credentials in credential.json');
    }

    $pageAccessToken = $credentials['longLivedToken']; // This should be the page access token
    $postUrl = 'https://graph.facebook.com/v20.0/me/feed';

    // Data to send
    $postData = [
        'link' => $link,
        'message' => $message,
        'access_token' => $pageAccessToken,
    ];

    // Initialize cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $postUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // Execute request
    $response = curl_exec($ch);

    // Check if there was an error with cURL
    if (curl_errno($ch)) {
        curl_close($ch);
        throw new RuntimeException('cURL error: ' . curl_error($ch));
    }

    curl_close($ch);

    // Decode the response
    $responseData = json_decode($response, true);

    echo "<pre>";
    print_r($responseData);
    echo "</pre>";

    // Check if there was an error in the API response
    if (isset($responseData['error'])) {
        throw new RuntimeException('Error: ' . $responseData['error']['message']);
    }

    // Return success response
    return [
        'success' => true,
        'message' => 'Message posted successfully!',
        'response' => $responseData
    ];
}

function facebook_schedule_post_to_page($message, $link, $scheduled_time)
{
    // Load credentials from the JSON file
    $credentials = json_decode(file_get_contents('credential.json'), true);

    if (!$credentials || !isset($credentials['longLivedToken'])) {
        throw new RuntimeException('Invalid or missing credentials in credential.json');
    }

    $pageAccessToken = $credentials['longLivedToken']; // This should be the page access token
    $postUrl = 'https://graph.facebook.com/v20.0/me/feed';

    // Data to send
    $postData = [
        'link' => $link,
        'message' => $message,
        'access_token' => $pageAccessToken,
        'published' => false, // Set to false for scheduled posts
        'scheduled_publish_time' => $scheduled_time // Unix timestamp for scheduling
    ];

    // Initialize cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $postUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // Execute request
    $response = curl_exec($ch);

    // Check if there was an error with cURL
    if (curl_errno($ch)) {
        curl_close($ch);
        throw new RuntimeException('cURL error: ' . curl_error($ch));
    }

    curl_close($ch);

    // Decode the response
    $responseData = json_decode($response, true);

    echo "<pre>";
    print_r($responseData);
    echo "</pre>";

    // Check if there was an error in the API response
    if (isset($responseData['error'])) {
        throw new RuntimeException('Error: ' . $responseData['error']['message']);
    }

    // Return success response
    return [
        'success' => true,
        'message' => 'Post scheduled successfully!',
        'response' => $responseData
    ];
}

function facebook_fetch_page_token($db, $gUserId, $pageId)
{
    // Prepare the SQL statement
    $sql = "SELECT pages_token FROM fb_pages WHERE pages_id = ? AND userId = ?";
    $stmt = mysqli_prepare($db, $sql);

    if (!$stmt) {
        throw new RuntimeException('Error preparing statement: ' . mysqli_error($db));
    }

    // Bind the parameters
    mysqli_stmt_bind_param($stmt, "ii", $pageId, $gUserId);

    // Execute the statement
    mysqli_stmt_execute($stmt);

    if (!mysqli_stmt_execute($stmt)) {
        throw new RuntimeException('Error inserting record: ' . mysqli_stmt_error($stmt));
    }

    // Get the result
    $result = mysqli_stmt_get_result($stmt);

    // Fetch the token
    if ($row = mysqli_fetch_assoc($result)) {
        $pageToken = $row['pages_token'];
    } else {
        $pageToken = null; // No token found
    }

    // Close the statement
    mysqli_stmt_close($stmt);

    return $pageToken;
}

function facebook_fetch_user_pages($db, $gUserId)
{
    // Prepare the SQL statement
    $sql = "SELECT pages_id, pages_name, pages_token FROM fb_pages WHERE userId = ?";
    $stmt = mysqli_prepare($db, $sql);

    if (!$stmt) {
        throw new RuntimeException('Error preparing statement: ' . mysqli_error($db));
    }

    // Bind the userId parameter
    mysqli_stmt_bind_param($stmt, "i", $gUserId);

    // Execute the statement
    mysqli_stmt_execute($stmt);

    // Bind result variables
    mysqli_stmt_bind_result($stmt, $pagesId, $pagesName, $pagesToken);

    // Fetch all results
    $pages = [];
    while (mysqli_stmt_fetch($stmt)) {
        $pages[] = [
            'pages_id' => $pagesId,
            'pages_name' => $pagesName,
            'pages_token' => $pagesToken
        ];
    }

    // Close the statement
    mysqli_stmt_close($stmt);

    // Check if any pages were found
    if (empty($pages)) {
        return null;
    }

    return $pages; // Return an array of pages
}

function facebook_login_status($db, $gUserId) 
{
    // Prepare the SQL statement to prevent SQL injection
    $stmt = $db->prepare("SELECT COUNT(*) AS count FROM fb_short_code WHERE userId = ?");
    
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