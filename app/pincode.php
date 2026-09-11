<?php
require_once 'inc/php/db_config.php';
require_once 'inc/php/validate.logged.php';
require_once 'inc/php/function.php';

// Handle the AJAX request for data
if (isset($_GET['page'])) {
    $page = (int) $_GET['page'];  // Get page number
    $size = (int) $_GET['size'];  // Get number of items per page
    $offset = ($page - 1) * $size;

    // Query to get the latest records, ordered by postedOn or ID in descending order
    // $sql = "SELECT * FROM reader_stream WHERE deleteFlag = 0 AND referenceId IS NULL ORDER BY postedOn DESC LIMIT $size OFFSET $offset";

    $sql = "
   SELECT rs.* 
    FROM nj_reader.reader_stream rs
    WHERE (rs.visibility = 'public' 
            OR rs.userId IN (
                SELECT following_id FROM nj_reader.reader_stream_follow WHERE follower_id = $gUserId
                UNION SELECT $gUserId
            )
        ) 
        AND rs.deleteFlag = 0
        AND rs.referenceId IS NULL
        AND rs.id NOT IN (SELECT streamId FROM nj_reader.report_stream WHERE userId = $gUserId)
        AND rs.userId NOT IN (SELECT blockedUserId FROM nj_cream.block_acc WHERE userId = $gUserId)
        AND rs.userId NOT IN (SELECT userId FROM nj_cream.block_acc WHERE blockedUserId = $gUserId)
        AND rs.pincode = $gUserPincode
        ORDER BY rs.postedOn DESC
    LIMIT $size OFFSET $offset";

    $result = $readerdb->query($sql);

    // Initialize the data container
    $htmlOutput = '';
    $cardCount = 0;

    // Fetch news articles once
    $newsArticles = fetchNewsArticles();
    $totalNewsArticles = count($newsArticles);
    $newsSliderCount = 0;

    // Define advertisement positions (randomly)
    // $adPositions = generateRandomAdPositions($size);

    // Fetch ad images
    // $adImages = fetchAdImages();
    // $totalAdImages = count($adImages);
    // $adCount = 0;

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            // Add regular stream card
            $htmlOutput .= captureStream($row['id'], $row['userId'], $row['chat'], $row['postedOn'], $row['editedOn'], $row['mediaPath'], $row['metadata']);
            $cardCount++;

            // Check if we need to display an ad at this position
            // if (in_array($cardCount, $adPositions) && $totalAdImages > 0) {
            //     $adIndex = $adCount % $totalAdImages;
            //     $htmlOutput .= generateAdCard($adImages[$adIndex]);
            //     $adCount++;
            // }

            // if ($cardCount % 5 === 0) {
            //     $newsStartIndex = ($newsSliderCount * 5) % $totalNewsArticles;
            //     $currentNewsArticles = array_slice($newsArticles, $newsStartIndex, 5);

            //     if (count($currentNewsArticles) < 5 && $totalNewsArticles >= 5) {
            //         $remaining = 5 - count($currentNewsArticles);
            //         $currentNewsArticles = array_merge($currentNewsArticles, array_slice($newsArticles, 0, $remaining));
            //     }

            //     $htmlOutput .= generateNewsCardSlider($currentNewsArticles);
            //     $newsSliderCount++;
            // }

            if ($cardCount % 5 === 0) {
                if ($totalNewsArticles > 0) {
                    // Calculate which set of news articles to show
                    $newsStartIndex = ($newsSliderCount * 5) % $totalNewsArticles;
                    $currentNewsArticles = array_slice($newsArticles, $newsStartIndex, 5);

                    // If we don't have 5 news articles left, wrap around to the beginning
                    if (count($currentNewsArticles) < 5 && $totalNewsArticles >= 5) {
                        $remaining = 5 - count($currentNewsArticles);
                        $currentNewsArticles = array_merge(
                            $currentNewsArticles,
                            array_slice($newsArticles, 0, $remaining)
                        );
                    }

                    $htmlOutput .= generateNewsCardSlider($currentNewsArticles);
                    $newsSliderCount++;
                } else {
                    // Optional: handle case where no news articles are available
                    // e.g. show a placeholder card or skip
                    $htmlOutput .= "<div class='no-news'>No news available</div>";
                }
            }
        }
    } else {
        $htmlOutput = "<div class='no-posts'>No posts found for this pincode</div>";
    }

    // Check if the current batch is the last one
    $last = (count(explode('</div>', $htmlOutput)) - 1 < $size) ? true : false;

    // Return the HTML and the last page status as JSON response
    echo json_encode(['html' => $htmlOutput, 'last' => $last]);

    // Close connection
    $readerdb->close();
    exit;
}

function postPin($id)
{

    global $gUserId, $readerdb;
    $sql = "
    SELECT rs.* 
    FROM reader.reader_stream rs
    WHERE (rs.visibility = 'public' 
            OR rs.userId IN (
                SELECT following_id FROM reader_stream_follow WHERE follower_id = $gUserId
                UNION SELECT $gUserId
            )
        ) 
        AND rs.deleteFlag = 0
        AND rs.referenceId IS NULL
        AND rs.id NOT IN (SELECT streamId FROM report_stream WHERE userId = $gUserId)
        AND rs.userId NOT IN (SELECT blockedUserId FROM cream.block_acc WHERE userId = $gUserId)
        AND rs.userId NOT IN (SELECT userId FROM cream.block_acc WHERE blockedUserId = $gUserId)
        AND id = $id ";

    $result = $readerdb->query($sql);

    $htmlOutput = '';

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $htmlOutput .= captureStream($row['id'], $row['userId'], $row['chat'], $row['postedOn'], $row['editedOn'], $row['mediaPath'], $row['metadata']);
        }
    }
    // Close connection
    $readerdb->close();
    echo $htmlOutput;
    exit;
}
/**
 * Generate random positions for ad placements
 * @param int $maxPosition Maximum position to consider
 * @return array Array of positions where ads should appear
 */
function generateRandomAdPositions($maxPosition)
{
    $positions = [];

    // Start with fixed positions like 3, 7, 12
    $positions = [3, 7, 12];

    // Add more random positions if needed
    $additionalPositions = 2; // Number of additional random ads

    for ($i = 0; $i < $additionalPositions; $i++) {
        $pos = mt_rand(15, $maxPosition);

        // Ensure we don't have duplicate positions
        while (in_array($pos, $positions) || $pos % 5 === 0) { // Avoid positions where news sliders appear
            $pos = mt_rand(15, $maxPosition);
        }

        $positions[] = $pos;
    }

    sort($positions);
    return $positions;
}

/**
 * Fetch advertisement images
 * @return array Array of ad image URLs and links
 */
function fetchAdImages()
{
    // In a real application, you would fetch these from a database or API
    // This is just a placeholder example
    return [
        [
            'image' => 'assets/img/adv2.png',
            'link' => 'https://newsjunction.net/promo/1'
        ],
        [
            'image' => 'assets/img/adv1.png',
            'link' => 'https://newsjunction.net/promo/2'
        ],
        [
            'image' => 'assets/img/adv2.png',
            'link' => 'https://newsjunction.net/promo/3'
        ],
        [
            'image' => 'assets/img/adv1.png',
            'link' => 'https://newsjunction.net/promo/4'
        ],
        [
            'image' => 'assets/img/adv2.png',
            'link' => 'https://newsjunction.net/promo/5'
        ]
    ];
}

/**
 * Generate an ad card
 * @param array $adData Ad image and link data
 * @return string HTML for the ad card
 */
function generateAdCard($adData)
{
    $adId = 'ad-' . uniqid();

    $html = '<div class="ads-card" id="' . $adId . '">';
    $html .= '<a  target="_blank" class="ads-card-link">';
    $html .= '<img src="' . $adData['image'] . '" alt="Advertisement" class="ads-card-image">';
    $html .= '</a>';
    $html .= '</div>';

    return $html;
}

/**
 * Fetch news articles from the API
 * @return array News articles data
 */
function fetchNewsArticles()
{
    $apiUrl = 'https://newsjunction.net/api/articles.php?rss_id=9';
    $jsonResponse = file_get_contents($apiUrl);

    if ($jsonResponse === false) {
        return []; // Return empty array if API call fails
    }

    $newsData = json_decode($jsonResponse, true);
    return $newsData ?: []; // Return parsed data or empty array if decode fails
}

/**
 * Generate a horizontal news card slider
 * @param array $newsItems Array of news articles
 * @return string HTML for the news card slider
 */
// function generateNewsCardSlider($newsItems)
// {
//     if (empty($newsItems)) {
//         return ''; // Return empty string if no news items
//     }

//     // Create unique ID for this news slider instance
//     $sliderId = 'news-slider-' . uniqid();

//     // Create the news slider container
//     $html = '<div class="newStream news-slider-container" id="' . $sliderId . '">';
//     $html .= '<h3 class="newStream-title">Latest News</h3>';
//     $html .= '<div class="news-slider-scroll">';

//     // Add each news card
//     foreach ($newsItems as $item) {
//         $html .= '<div class="news-cards" data-id="' . $item['id'] . '">';
//         $html .= '<a href="' . $item['url'] . '" target="_blank" class="news-cards-link">';
//         $html .= '<div class="news-cards-image"><img src="' . $item['image'] . '" alt="' . htmlspecialchars($item['title']) . '"></div>';
//         $html .= '<div class="news-cards-title">' . htmlspecialchars($item['title']) . '</div>';
//         $html .= '<div class="news-cards-description">' . htmlspecialchars(substr($item['description'], 0, 100)) . '...</div>';
//         $html .= '</a>';
//         $html .= '</div>';
//     }

//     $html .= '</div>';
//     $html .= '</div>';

//     return $html;
// }
/**
 * Generate a horizontal news card slider
 * @param array $newsItems Array of news articles
 * @return string HTML for the news card slider
 */
function generateNewsCardSlider($newsItems)
{
    if (empty($newsItems)) {
        return ''; // Return empty string if no news items
    }

    // Create unique ID for this news slider instance
    $sliderId = 'news-slider-' . uniqid();

    // Create the news slider container
    $html = '<div class="newStream news-slider-container" id="' . $sliderId . '">';
    $html .= '<h3 class="newStream-title">Latest News</h3>';
    $html .= '<div class="news-slider-scroll">';

    // Add each news card
    foreach ($newsItems as $item) {
        $html .= '<div class="news-cards" data-id="' . $item['id'] . '">';
        // $html .= '<a href="' . $item['url'] . '" target="_blank" class="news-cards-link">';
        $html .= '<a href="dashboard.php" target="_blank" class="news-cards-link">';
        $html .= '<div class="news-cards-image"><img src="' . $item['image'] . '" alt="' . htmlspecialchars($item['title']) . '"></div>';
        $html .= '<div class="news-cards-title">' . htmlspecialchars($item['title']) . '</div>';
        $html .= '<div class="news-cards-description">' . htmlspecialchars(substr($item['description'], 0, 100)) . '...</div>';
        $html .= '</a>';
        // Add dashboard button
        $html .= '<div class="news-cards-action">';
        $html .= '<a href="dashboard.php" class="dashboard-btn">Go to Reader</a>';
        $html .= '</div>';
        $html .= '</div>';
    }

    $html .= '</div>';
    $html .= '</div>';

    return $html;
}




function get_ip_loc()
{
    $ip = getenv('HTTP_CLIENT_IP') ?: getenv('HTTP_X_FORWARDED_FOR') ?: getenv('HTTP_X_FORWARDED') ?: getenv('HTTP_FORWARDED_FOR') ?: getenv('HTTP_FORWARDED') ?: getenv('REMOTE_ADDR');
    $response = unserialize(file_get_contents('http://www.geoplugin.net/php.gp?ip=' . $ip));
    if ($response === false) {
        $visitCity = '';
        $visitCountry = '';
    } else {
        $visitCity = $response['geoplugin_city'];
        $visitCountry = $response['geoplugin_countryName'];
    }
    return array(
        "ip" => $ip,
        "city" => $visitCity,
        "country" => $visitCountry
    );
}

if (isset($_POST['streamId'])) {
    $iploc = get_ip_loc();

    // SQL query using INSERT WHERE NOT EXISTS to prevent duplicate insertion
    $sql = "INSERT INTO stream_analytics (streamId, userId, ip, city, country)
        SELECT ?, ?, ?, ?, ? FROM DUAL
        WHERE NOT EXISTS (
            SELECT 1 FROM stream_analytics WHERE streamId = ? AND userId = ?
        )";

    // Prepare the statement
    $stmt = $readerdb->prepare($sql);

    // Bind the parameters
    $stmt->bind_param("iisssii", $_POST['streamId'], $gUserId, $iploc['ip'], $iploc['city'], $iploc['country'], $_POST['streamId'], $gUserId);

    // Execute the query
    $result = $stmt->execute();

    if ($result) {
        // Check if the insertion was successful
        if ($stmt->affected_rows > 0) {
            echo json_encode(['status' => "success", "message" => "Data inserted successfully"]);
        } else {
            echo json_encode(['status' => "success", "message" => "Combination of streamId and userId already exists."]);
        }
    } else {
        echo json_encode(['status' => "error", "message" => $stmt->error]);
    }
    exit;
}

function check_cream_subscription($userId)
{
    global $creamdb;
    $sql = "SELECT plan, plan_type FROM cream_subscription WHERE userId = ? AND NOW() BETWEEN start_date AND end_date";
    if ($stmt = $creamdb->prepare($sql)) {
        $stmt->bind_param("i", $userId);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                if ($row['plan'] = !'Pro') {
                    echo "<script type='text/javascript'>
                        alert('Your plan has ended, Please login!')
                        window.location.href = 'logout.php';
                    </script>";
                }
            } else {
                echo "<script type='text/javascript'>
                    alert('Your plan has ended, Please login!')
                    window.location.href = 'logout.php';
                </script>";
            }
        }
    }
}

// Function to check if the user exists in the session_log and add session if it doesn't exist
function checkAndAddUserSession($userId, $creamdb)
{
    $sessionId = session_id();
    // SQL query to check if the user has an active session in the session_log table
    $session_sql = "SELECT COUNT(*) AS session_count 
                    FROM session_log 
                    WHERE userId = ? 
                    AND sessionId = ?
                    AND endTime IS NULL
                    AND startTime >= CURDATE() - INTERVAL 30 DAY";

    try {
        // Prepare the query
        $stmt = $creamdb->prepare($session_sql);
        $stmt->bind_param('is', $userId, $sessionId); // Bind the userId parameter to the query
        $stmt->execute();

        // Get the result
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        // If session_count is 0, the user does not have an active session
        if ($row['session_count'] == 0) {
            // Insert a new session entry if the session does not exist
            createNewSession($userId, $creamdb);
        }
    } catch (mysqli_sql_exception $e) {
        echo "Error checking session: " . $e->getMessage();
    }
}

// Function to create a new session log for the user
function createNewSession($userId, $creamdb)
{
    // Get the current session ID
    $sessionId = session_id(); // Assuming the session ID is already started (session_start() is called earlier)

    // Insert the new session into the session_log table
    $insert_sql = "INSERT INTO session_log (userId, sessionId, startTime) VALUES (?, ?, NOW())";

    try {
        // Prepare the query
        $stmt = $creamdb->prepare($insert_sql);
        $stmt->bind_param('is', $userId, $sessionId); // Bind userId and sessionId
        $stmt->execute();
    } catch (mysqli_sql_exception $e) {
        echo "Error inserting new session: " . $e->getMessage();
    }
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>News Junction</title>

    <link rel="stylesheet" href="inc/css/social.css">
    <link rel="sheet" href="style.css">
    <link rel="icon" type="image/x-icon" href="grfx/img/logo.ico">
    <!-- Make sure you have this or similar in your <head> -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.0/font/bootstrap-icons.min.css"
        rel="stylesheet">

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/js/all.min.js"></script> -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="inc/js/new_social_script.js?v=20260528"></script>

    <style>
        /* News Slider Styles - optimized for grid layout with PC scroll fix */
        .newStream.news-slider-container {
            width: 100%;
            margin: 20px 0;
            position: relative;
            overflow: hidden;
            background: var(--bg-card);
            border-radius: 12px;
            padding: 20px 0 15px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            /* Ensure it fits within the grid column */
            max-width: 100%;
            box-sizing: border-box;
        }

        .newStream-title {
            padding: 0 20px 15px;
            margin: 0;
            font-size: 18px;
            font-weight: 600;
        }

        .newStream .news-slider-scroll {
            display: flex;
            overflow-x: auto;
            overflow-y: hidden;
            /* Prevent vertical scrolling */
            scroll-behavior: smooth;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: thin;
            /* Show thin scrollbar on Firefox for PC */
            scrollbar-color: #ccc transparent;
            /* Custom scrollbar colors for Firefox */
            padding: 0 15px 15px;
            gap: 12px;
            user-select: none;
            /* Fixed width calculation for proper scrolling */
            width: 100%;
            margin: 0;
            /* Ensure cards don't wrap */
            flex-wrap: nowrap;
        }

        /* Custom scrollbar for WebKit browsers (Chrome, Safari, Edge) */
        .newStream .news-slider-scroll::-webkit-scrollbar {
            height: 6px;
            /* Show scrollbar on PC */
            background: transparent;
        }

        .newStream .news-slider-scroll::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.05);
            border-radius: 3px;
        }

        .newStream .news-slider-scroll::-webkit-scrollbar-thumb {
            background: rgba(0, 0, 0, 0.2);
            border-radius: 3px;
            transition: background 0.3s ease;
        }

        .newStream .news-slider-scroll::-webkit-scrollbar-thumb:hover {
            background: rgba(0, 0, 0, 0.4);
        }

        .newStream .news-cards {
            min-width: 240px;
            max-width: 240px;
            flex: 0 0 240px;
            /* Prevent shrinking */
            background: var(--bg-card);
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            position: relative;
            /* Ensure card maintains its size */
            box-sizing: border-box;
        }

        .newStream .news-cards:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .newStream .news-cards-link {
            text-decoration: none;
            color: inherit;
            display: block;
            outline: none;
        }

        .newStream .news-cards-image {
            height: 140px;
            overflow: hidden;
            width: 100%;
        }

        .newStream .news-cards-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .newStream .news-cards:hover .news-cards-image img {
            transform: scale(1.05);
        }

        .newStream .news-cards-title {
            margin: 12px 15px 8px;
            font-weight: 600;
            font-size: 16px;
            line-height: 1.3;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .newStream .news-cards-description {
            padding: 0 15px 15px;
            font-size: 14px;
            color: #666;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .news-cards-action {
            margin-top: 10px;
            text-align: left;
            padding: 0 15px 15px;
        }

        .dashboard-btn {
            display: inline-block;
            padding: 8px 15px;
            background-color: #db5919;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-weight: bold;
            font-size: 13px;
            transition: background-color 0.3s, transform 0.2s;
        }

        .dashboard-btn:hover {
            background-color: #c44f16;
            transform: translateY(-1px);
        }

        /* Fade effect to indicate more content */
        .newStream.news-slider-container::after {
            content: '';
            position: absolute;
            right: 0;
            top: 20px;
            bottom: 15px;
            width: 30px;
            /* background: linear-gradient(to right, rgba(249, 249, 249, 0), rgba(249, 249, 249, 1)); */
            pointer-events: none;
            z-index: 2;
        }

        /* Desktop-specific improvements */
        @media (min-width: 1024px) {
            .newStream .news-slider-scroll {
                /* Show scrollbar on hover for better UX on desktop */
                scrollbar-width: thin;
            }

            .newStream .news-slider-scroll::-webkit-scrollbar {
                height: 8px;
            }

            /* Add mouse wheel scroll support */
            .newStream .news-slider-scroll {
                scroll-snap-type: x mandatory;
            }

            .newStream .news-cards {
                scroll-snap-align: start;
            }
        }

        /* Responsive design for main-content grid layout */
        @media (max-width: 1200px) {
            .newStream.news-slider-container {
                margin: 15px 0;
                padding: 15px 0 10px;
                /* Ensure container doesn't exceed grid column width */
                min-width: 0;
                /* Allow shrinking */
            }

            .newStream .news-slider-scroll {
                /* Adjust for narrower grid column (350px-600px) */
                padding: 0 10px 15px;
            }

            .newStream .news-cards {
                min-width: 200px;
                /* Smaller cards for constrained width */
                max-width: 200px;
                flex: 0 0 200px;
            }

            .newStream .news-cards-image {
                height: 120px;
            }

            .newStream-title {
                padding: 0 15px 15px;
                font-size: 17px;
            }
        }

        /* When grid collapses to single column (992px breakpoint) */
        @media (max-width: 992px) {
            .newStream.news-slider-container {
                margin: 15px 0;
                padding: 15px 0 10px;
                /* Full width in single column layout */
                min-width: 0;
            }

            .newStream-title {
                padding: 0 15px 10px;
                font-size: 16px;
            }

            .newStream .news-slider-scroll {
                padding: 0 15px 10px;
                gap: 10px;
                /* Hide scrollbar on tablets and mobile */
                scrollbar-width: none;
            }

            .newStream .news-slider-scroll::-webkit-scrollbar {
                display: none;
            }

            .newStream .news-cards {
                min-width: 220px;
                /* Larger cards in single column */
                max-width: 220px;
                flex: 0 0 220px;
            }

            .newStream .news-cards-image {
                height: 130px;
            }

            .newStream .news-cards-title {
                font-size: 15px;
                padding: 10px 12px 6px;
            }

            .newStream .news-cards-description {
                font-size: 13px;
                padding: 0 12px 10px;
            }

            .news-cards-action {
                padding: 0 12px 10px;
            }
        }

        /* Mobile screens - when sidebar collapses */
        @media (max-width: 768px) {
            .newStream.news-slider-container {
                margin: 10px 0;
                padding: 12px 0 8px;
                border-radius: 8px;
            }

            .newStream-title {
                padding: 0 12px 8px;
                font-size: 15px;
            }

            .newStream .news-slider-scroll {
                padding: 0 12px 8px;
                gap: 8px;
                scrollbar-width: none;
            }

            .newStream .news-slider-scroll::-webkit-scrollbar {
                display: none;
            }

            .newStream .news-cards {
                min-width: 180px;
                max-width: 180px;
                flex: 0 0 180px;
            }

            .newStream .news-cards-image {
                height: 110px;
            }

            .newStream .news-cards-title {
                margin: 0px;
                font-size: 14px;
                padding: 8px 10px 2px;
                -webkit-line-clamp: 2;
            }

            .newStream .news-cards-description {
                font-size: 12px;
                padding: 0 10px 4px;
                -webkit-line-clamp: 2;
            }

            .news-cards-action {
                padding: 0 10px 8px;
                margin-top: 5px;
            }

            .dashboard-btn {
                padding: 5px 8px;
                font-size: 11px;
            }

            .newStream.news-slider-container::after {
                width: 15px;
                top: 12px;
                bottom: 8px;
            }
        }

        /* Very small screens */
        @media (max-width: 480px) {
            .newStream .news-cards {
                min-width: 160px;
                max-width: 160px;
                flex: 0 0 160px;
            }

            .newStream .news-cards-image {
                height: 100px;
            }

            .newStream .news-cards-title {
                font-size: 13px;
                padding: 6px 8px 2px;
            }

            .newStream .news-cards-description {
                font-size: 11px;
                padding: 0 8px 2px;
            }

            .news-cards-action {
                padding: 0 8px 6px;
            }

            .dashboard-btn {
                padding: 4px 6px;
                font-size: 10px;
            }
        }

        /* Ad Card Styles */
        .ads-card {
            width: 100%;
            margin: 15px 0;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            background-color: #fff;
            position: relative;
        }

        .ads-card:before {
            content: "Ad";
            position: absolute;
            top: 5px;
            right: 5px;
            background-color: rgba(0, 0, 0, 0.5);
            color: #fff;
            font-size: 10px;
            padding: 2px 5px;
            border-radius: 3px;
            z-index: 10;
        }

        .ads-card-link {
            display: block;
            text-decoration: none;
            color: inherit;
        }

        .ads-card-image {
            width: 100%;
            height: auto;
            display: block;
            transition: transform 0.3s ease;
        }

        .ads-card:hover .ads-card-image {
            transform: scale(1.03);
        }

        @media (max-width: 768px) {
            .ads-card {
                margin: 10px 0;
            }
        }
    </style>
    <script>
        const userId = <?= $gUserId ?>;
        let tempUrl = '';
        let letUrl = true;
        let dotInterval;
        var myModal;
        let page = 1; // Start with the first page
        const pageSize = 20; // Number of items per page
        let isLoading = false; // Flag to prevent multiple AJAX calls at once
        let isLastPage = false; // Flag to check if the last page is reached

        function removeMeta() {
            $('#linkPreview').hide();
            $('#hiddenTitle').val('');
            $('#hiddenDesc').val('');
            $('#hiddenUrl').val('');
            $('#hiddenDomain').val('');
            $('#hiddenImage').val('');
        }

        function removeYT() {
            $('ytPreview').html('');
            $('#ytPreview').hide();
        }

        function generateVideoThumbnail($ele) {
            try {
                // Fetch the video URL from the source element inside the .post div
                var videoUrl = $ele.find('video source').attr('src');

                if (!videoUrl) {
                    return false; // If no video URL, return false early
                }

                var $video = $('<video>').attr('controls', true).css({
                    maxWidth: '100%',
                    maxHeight: '70vh' // Set max height for the preview
                });

                // Create a hidden video element to extract the first frame
                var videoElement = document.createElement('video');
                videoElement.src = videoUrl;

                // Create a canvas to draw the first frame
                var $thumbnailCanvas = $('<canvas>')[0];
                var thumbnailContext = $thumbnailCanvas.getContext('2d');

                // Wait for the video to load and then set it to the first frame
                $(videoElement).on('loadeddata', function() {
                    videoElement.currentTime = 0; // Set video to the first frame
                });

                // Once the video seeks to the first frame, capture the thumbnail
                $(videoElement).on('seeked', function() {
                    // Set canvas size to match video dimensions
                    $thumbnailCanvas.width = videoElement.videoWidth;
                    $thumbnailCanvas.height = videoElement.videoHeight;

                    // Draw the current frame (first frame) onto the canvas
                    thumbnailContext.drawImage(videoElement, 0, 0, videoElement.videoWidth, videoElement.videoHeight);

                    // Convert the canvas to a data URL and set it as the video poster
                    var dataUrl = $thumbnailCanvas.toDataURL();
                    $video.attr('poster', dataUrl); // Set the first frame as the thumbnail
                });

                // Keep the existing content of the div intact
                var existingVideo = $ele.find('video');

                // Replace the video inside the .post div with the new one that has a thumbnail
                existingVideo.replaceWith($video);

                // Set the video source and load it
                $video.attr('src', videoUrl);
                $video[0].load();
                // $video[0].play(); // Optionally, you can play the video if needed

                return true; // Return true if the function completes successfully
            } catch (error) {
                console.error("Error generating video thumbnail:", error);
                return false; // Return false if an error occurs
            }
        }

        function handleLazyLoad() {
            $('.post-content').each(function() {
                if ($(this).data('thumbnail') === false && $(this).find('video').length > 0) {
                    generateVideoThumbnail($(this));
                    $(this).data('thumbnail', true);
                }
            });
        }

        function isElementInView($el) {
            var windowTop = $(window).scrollTop();
            var windowBottom = windowTop + $(window).height();
            var elementTop = $el.offset().top;
            var elementBottom = elementTop + $el.height();
            // Check if element is in the viewport
            return elementBottom >= windowTop && elementTop <= windowBottom;
        }

        function storeAnalytics($streamId) {
            $.ajax({
                url: '',
                type: 'POST',
                data: {
                    streamId: $streamId
                },
                success: function(response) {
                    const data = JSON.parse(response);
                    if (data.status == "error") {
                        alert('Please Check the Internet Connection.');
                    }
                },
                error: function() {
                    alert('Please Check the Internet Connection.');
                }
            });
        }

        function streamAnalytics() {
            $('.post-card').each(function() {
                var $ele = $(this); // Current .post element
                if (isElementInView($ele) && !$ele.hasClass('logged')) {
                    storeAnalytics($ele.attr('data-id'));
                    $ele.addClass('logged'); // Mark it as logged
                }
            });
        }

        // Function to play or pause the video based on its visibility
        function handleVideoVisibility(videoElement, $postElement) {
            if (isElementInView($postElement)) {
                // Video is in the viewport, play it if it's not already playing
                // if (videoElement.paused) {
                //     videoElement.play().catch(function(error) {});
                // }
            } else {
                // Video is out of the viewport, pause it if it's playing
                if (!videoElement.paused) {
                    videoElement.pause();
                }
            }
        }

        // Function to check all videos within .post elements
        function checkVideoVisibility() {
            $('.post').each(function() {
                var video = $(this).find('video')[0]; // Get the video element
                if (video) {
                    handleVideoVisibility(video, $(this)); // Call the function to handle play/pause
                }
            });
        }

        // Function to load data from the server
        function loadData() {
            if (isLoading || isLastPage) return; // If data is already being loaded or the last page is reached, return early

            isLoading = true; // Set flag to true to prevent further calls

            $('.loading-spinner').show(); // Show the loading indicator

            $.ajax({
                url: '', // The same file since we handle both front-end and back-end here
                type: 'GET',
                data: {
                    page: page,
                    size: pageSize
                },
                success: function(response) {
                    const data = JSON.parse(response);
                    const html = data.html;

                    if (html.length > 0) {
                        // Append the HTML content directly to the container (this loads latest data below)
                        // $('#data-container').append(html);
                        $('.first_right_container').append(html);

                        // Check if the last page is reached
                        isLastPage = data.last;

                        page++; // Increment the page number for the next request
                    }

                    $('.loading-spinner').hide(); // Hide the loading indicator
                    isLoading = false; // Reset the flag after data is loaded
                },
                error: function(xhr, status, error) {
                    $('.loading-spinner').hide();
                    isLoading = false;
                    console.error('AJAX Error:', status, error, xhr.responseText);
                    alert('Error loading data: ' + error + ' - Response: ' + xhr.responseText);
                }
            });
        }




        $(function() {
            // Initialize the modal when the document is ready
            reportModal = new bootstrap.Modal($("#reportModal")[0]);

            // Initialize the modal when the document is ready
            myModal = new bootstrap.Modal($("#uploadModal")[0]);

            // Event listener for showing the modal (for example, when the plus button is clicked)
            $("#plusButton").on("click", function() {
                myModal.show(); // Show the modal
            });
        });

        $(document).ready(function() {
            // Initialize the height on page load
            var $textarea = $('#contentTextarea');
            if ($textarea.length) {
                adjustTextareaHeight($textarea[0]);
            }



            $('#saveModalEditButton').on('click', function() {
                saveEditedContent();
            });


            // likedUsers
            $(document).on('click', '.likedUsers', function() {
                const postId = $(this).data('id'); // Get post ID from the button's data attribute

                $.ajax({
                    url: 'fetch_liked_users.php',
                    type: 'POST',
                    data: {
                        postId: postId
                    },
                    success: function(response) {
                        const users = JSON.parse(response);

                        if (users.error) {
                            alert(users.error);
                        } else if (users.message) {
                            $('#likedUsersList').html('<li class="list-group-item text-center">' + users.message + '</li>');
                        } else {
                            const userList = users.map(user => `<li class="list-group-item">${user}</li>`).join('');
                            $('#likedUsersList').html(userList);
                        }

                        const likedUsersModal = new bootstrap.Modal(document.getElementById('likedUsersModal'));
                        likedUsersModal.show();
                    },
                    error: function() {
                        alert('Error fetching likes.');
                    }
                });
            });

            // Handle like button clicks
            $(document).on('click', '.likeButton', function(e) {
                event.stopPropagation();
                // If the clicked element has the `likedUsers` class, do not toggle like/unlike                
                if ($(e.target).hasClass('likedUsers')) {
                    return; // Prevent triggering the like toggle
                }

                // Proceed with like toggle functionality
                var feedId = $(this).data('id');
                toggleLike(this, feedId, userId);
            });

            $('#contentTextarea').on('input', function() {
                var content = $('#contentTextarea').val().trim();

                // Updated regular expression to match full URLs, including subdomains and paths
                // var url_pattern = /(?:https?|ftp):\/\/(?:[a-zA-Z0-9-]+\.)?(?:[a-zA-Z0-9-]+\.[a-zA-Z]{2,})(?:\/[^\s]*)?/g;
                var url_pattern = /(?:https?|ftp):\/\/(?:[a-zA-Z0-9-]+\.)?(?:[a-zA-Z0-9-]+\.[a-zA-Z]{2,})(?:\/[^\s\)]*)?/g;

                var youtube_pattern = /(?:v=|youtu\.be\/|embed\/)([a-zA-Z0-9_-]+)/;

                // Attempt to match all URLs within the content
                var matchedUrls = content.match(url_pattern);
                var youtubeUrl = content.match(youtube_pattern);

                if (youtubeUrl) {
                    youtubeUrl.forEach(function(url) {
                        // console.log('Matched URL:', url); // Log each matched URL
                    });
                    if (tempUrl != youtubeUrl[0]) {
                        letUrl = true;
                        tempUrl = youtubeUrl[0];
                        removeMeta();
                        // Show the loading animation
                        $('#loadingIcon').show();
                        // $('#postedContent').html(''); // Clear any previous metadata
                        // Send the first matched URL to the server for metadata fetching
                        $.ajax({
                            url: 'link.php', // Submit to the same page
                            type: 'POST',
                            data: {
                                ytUrl: youtubeUrl[0]
                            }, // Send the first matched URL
                            success: function(response) {
                                // Hide the loading animation
                                $('#loadingIcon').hide();
                                if (response.iframe) {
                                    $('#ytPreview').html(response.iframe);
                                    $('#hiddenYTLink').val(response.iframe || '');
                                    $('#hiddenTitle').val(response.title || '');
                                    $('#ytPreview').show();
                                } else {
                                    return;
                                }
                            },
                            error: function() {
                                // Hide the loading animation on error
                                $('#loadingIcon').hide();
                                // console.log("Error fetching Youtube Link");
                            }
                        });
                    }
                } else if (matchedUrls) {
                    // Log all matched URLs to the console (for testing purposes)
                    matchedUrls.forEach(function(url) {
                        // console.log('Matched URL:', url); // Log each matched URL
                    });
                    if (tempUrl != matchedUrls[0]) {
                        letUrl = true;
                        tempUrl = matchedUrls[0];
                        removeYT();
                        // Show the loading animation
                        $('#loadingIcon').show();
                        // $('#postedContent').html(''); // Clear any previous metadata
                        // Send the first matched URL to the server for metadata fetching
                        $.ajax({
                            url: 'link.php', // Submit to the same page
                            type: 'POST',
                            data: {
                                url: matchedUrls[0]
                            }, // Send the first matched URL
                            success: function(response) {
                                // Hide the loading animation
                                $('#loadingIcon').hide();
                                $('#linkPreview').show();
                                if (response.url) {
                                    $('#linkPreview #linkHeading').html(response.title || ''); // Set heading text
                                    $('#linkPreview #linkDesc').html(response.description || ''); // Set description text
                                    $('#linkPreview #linkUrl').attr('href', response.url || ''); // Set link URL
                                    $('#linkPreview #linkUrl').html(response.domain || '');
                                    $('#hiddenTitle').val(response.title || '');
                                    $('#hiddenDesc').val(response.description || '');
                                    $('#hiddenUrl').val(response.url || '');
                                    $('#hiddenDomain').val(response.domain || '');
                                    if (response.image) {
                                        $('#linkPreview img').attr('src', response.image); // Set image source
                                        $('#hiddenImage').val(response.image);
                                    }
                                } else {
                                    return;
                                }
                            },
                            error: function() {
                                // Hide the loading animation on error
                                $('#loadingIcon').hide();
                                // console.log("Error fetching the metadata");
                            }
                        });
                    }
                } else {
                    // Clear content and hide the loading animation if no valid URL is found
                    $('#loadingIcon').hide();
                }
                if (tempUrl != youtubeUrl[0]) {
                    removeYT();
                }
                if (tempUrl != matchedUrls[0]) {
                    removeMeta()
                }
                if (youtubeUrl[0] == '') {
                    removeYT();
                }
                if (matchedUrls[0] == '') {
                    removeMeta();
                }
            });

            // $(window).on('scroll', checkVideoVisibility);

            $(window).on('scroll', function() {
                streamAnalytics();
                checkVideoVisibility();

                // Check if the user has scrolled near the bottom of the page
                if ($(window).scrollTop() + $(window).height() >= $(document).height() - 100) {
                    loadData(); // Load more data when scrolled near the bottom
                }
            });

            $('.post-content').attr('tabindex', '0');

            $(document).on('mouseenter', '.post-content', function() {
                if ($(this).data('thumbnail') === false && $(this).find('video').length > 0) {
                    generateVideoThumbnail($(this));
                    $(this).data('thumbnail', true);
                }
            });

            $(document).on('ajaxComplete', function() {
                handleLazyLoad();
                // Also check on page load in case elements are already in view
                $(window).trigger('scroll');
            });

            $("#reportReasonSelect").on("change", function() {
                const otherReasonContainer = $("#otherReasonContainer");
                if ($(this).val() === "Others") {
                    otherReasonContainer.show();
                } else {
                    otherReasonContainer.hide();
                }
            });

            // Initial data load
            loadData();
        });

        // Close the dropdown menu if the user clicks anywhere outside the menu or the icon
        $(document).click(function(event) {
            var $isClickInsideMenu = $(event.target).closest('.dropcardMenu');
            var $isClickInsideIcon = $(event.target).closest('.menu-container');

            // Close the dropdown if the click is outside the menu and icon
            if ($isClickInsideMenu.length === 0 && $isClickInsideIcon.length === 0) {
                closeAllDropcardMenus();
            }
        });

        // Add event listeners to Edit and Delete buttons to close the dropdown when clicked
        $('.editBtn').click(function() {
            closeAllDropcardMenus();
        });

        $('.dropcardMenu a button').click(function() {
            closeAllDropcardMenus();
        });


        // Attach the `oninput` event for dynamic resizing
        $(document).on('input', '#contentTextarea', function() {
            adjustTextareaHeight(this);
        });


        // Updated fetchChannelData function
        function fetchChannelData(id) {
            const userId = <?= $gUserId ?>;
            const postId = id;

            // Show the modal and overlay
            const overlay = document.getElementById(`channelOverlay_${id}`);
            const wrapper = document.getElementById(`channelCardsWrapper_${id}`);
            const cardsContent = document.getElementById(`channelCardsContent_${id}`);

            // Show loading state
            overlay.style.display = 'block';
            wrapper.classList.add('active');
            cardsContent.innerHTML = '<div class="loading-indicator">Loading channels...</div>';

            // Perform AJAX request
            $.ajax({
                url: "process/handle_channel.php",
                type: "GET",
                data: {
                    user_id: userId,
                    post_id: postId
                },
                dataType: "json",
                success: function(data) {
                    // Clear the container
                    cardsContent.innerHTML = '';

                    if (data.success) {
                        if (data.channels.length === 0) {
                            cardsContent.innerHTML = '<div class="no-channels-message">No channels available</div>';
                        } else {
                            // Create channel cards
                            $.each(data.channels, function(index, channel) {
                                let card = $("<div>")
                                    .addClass("channel-card")
                                    .append(
                                        $("<div>")
                                        .addClass("channel-name")
                                        .text(channel.name)
                                    )
                                    .append(
                                        $("<button>")
                                        .addClass("add-to-channel-btn")
                                        .text("Add")
                                        .on("click", function() {
                                            addToChannel(postId, channel.id);
                                        })
                                    );

                                $(cardsContent).append(card);
                            });
                        }

                        // Add "Add Channels" button as a card
                        let addCard = $("<div>")
                            .addClass("add-channel-card")
                            .append(
                                $("<a>")
                                .addClass("add-channel-btn")
                                .attr("href", "add_channel.php?postId=" + id)
                                .text("+ Add New Channel")
                            );

                        $(cardsContent).append(addCard);
                    } else {
                        cardsContent.innerHTML = '<div class="error-message">Error: ' + data.message + '</div>';
                    }
                },
                error: function(xhr, status, error) {
                    cardsContent.innerHTML = '<div class="error-message">Failed to load channels. Please try again.</div>';
                    console.error("AJAX Error:", error);
                }
            });

            // Add a document click handler to close the modal when clicking outside
            setTimeout(function() {
                $(document).one("click", function(e) {
                    if (!$(e.target).closest('.channel-cards-wrapper, .action-button').length) {
                        hideChannelCards(id);
                    }
                });
            }, 100);
        }


        // Hide dropdown when clicking outside
        $(document).on("click", function(event) {
            if (!$(event.target).closest(".channels_list").length) {
                $(".channels_list").hide();
            }
        });

        function addToChannel(postId, channelId) {
            $.ajax({
                url: 'process/add_to_channel.php',
                type: 'POST',
                data: {
                    postId: postId,
                    channelId: channelId
                },
                success: function(response) {
                    const result = JSON.parse(response); // Parse the JSON response
                    if (result.success) {
                        alert('Post added to channel');
                        hideChannelCards(postId);
                    } else {
                        alert('Failed to add to channel: ' + result.message);
                    }
                },
                error: function(xhr, status, error) {
                    alert('Error: ' + error);
                }
            });
        }

        // Select the bookmark icon and notification elements
        $(document).ready(function() {

        });
    </script>

</head>

<body>

    <?php include 'inc/php/models.php' ?>
    <div class="media-share-modal" id="post-modal">
        <div class="media-modal-content" style="max-height: 80vh; overflow: auto;">
            <div class="media-modal-header">
                <h5 class="media-modal-title">Share</h5>
                <button type="button" class="media-close-modal" data-bs-dismiss="modal" aria-label="Close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="media-upload-section">
                <!-- Upload form section -->
                <div class="media-upload-container">
                    <div class="media-upload-wrapper">
                        <div id="loadingIndicator" class="media-loading-indicator">Generating<span id="dots">...</span>
                        </div>
                        <div class="media-textarea-container">
                            <textarea id="contentTextarea" class="media-content-textarea"
                                placeholder="What would you like to share?" style="flex: 1 1 0%;
    overflow: auto;
    resize: none;
    max-height: 180px;
    height: 50px;" oninput="adjustTextareaHeight(this)"></textarea>
                        </div>

                        <!-- Media preview area -->
                        <div id="mediaSlider" class="media-slider-container">
                            <div id="mediaSlides" class="media-slider-slides">
                                <!-- Dynamic media elements will be appended here -->
                            </div>
                            <button class="media-prev media-slideBtn" onclick="moveSlide(-1)">
                                <i class="fas fa-chevron-left"></i>
                            </button>
                            <button class="media-next media-slideBtn" onclick="moveSlide(1)">
                                <i class="fas fa-chevron-right"></i>
                            </button>
                        </div>

                        <!-- Link preview area -->
                        <div id="linkPreview" class="media-link-preview">
                            <div class="media-hyperlink-container">
                                <img class="media-link-image" src="" alt="Link preview">
                                <div class="media-link-content">
                                    <h3 id="linkHeading" class="media-link-title"></h3>
                                    <p id="linkDesc" class="media-link-description"></p>
                                    <a id="linkUrl" class="media-link-url" href=""></a>
                                </div>
                            </div>
                        </div>

                        <!-- YouTube preview area -->
                        <div id="ytPreview" class="media-youtube-preview"></div>

                        <!-- Loading indicator -->
                        <div id="loadingIcon" class="media-loading-spinner">
                            <div class="media-spinner"></div>
                        </div>

                        <!-- Hidden fields for data storage -->
                        <div class="media-hidden-fields">
                            <input type="hidden" id="hiddenTitle">
                            <input type="hidden" id="hiddenDesc">
                            <input type="hidden" id="hiddenUrl">
                            <input type="hidden" id="hiddenImage">
                            <input type="hidden" id="hiddenDomain">
                            <input type="hidden" id="hiddenYTLink">
                        </div>
                    </div>
                </div>
            </div>

            <div class="media-share-footer">
                <div class="media-privacy-selector">
                    <span class="media-privacy-label">Who can see this post?</span>
                    <div class="media-dropdown-container">
                        <button class="media-dropdown-button" id="privacy-dropdown-btn" style="gap:4px;">
                            <i class="fas fa-globe"></i>
                            <span>Public</span>
                            <i class="fas fa-caret-down"></i>
                        </button>
                        <div class="media-dropdown-content" id="privacy-dropdown-content">
                            <div class="media-dropdown-item active" style="gap:4px;">
                                <i class="fas fa-globe"></i>
                                <span>Public</span>
                            </div>
                            <div class="media-dropdown-item" style="gap:4px;">
                                <i class="fas fa-user-friends"></i>
                                <span>People I follow</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="media-share-actions">
                    <!-- File upload button -->
                    <!-- <button class="media-action-icon-btn" id="schedule-toggle-btn">
                        <i class="fas fa-calendar-alt"></i>
                    </button> -->
                    <button class="media-action-icon-btn" id="media-upload-btn">
                        <i class="fa fa-photo-film"></i>
                    </button>
                    <input type="file" id="fileInput" class="media-file-input" accept="image/*,video/*"
                        onchange="previewMedia();" multiple>

                    <!-- AI content generation button -->
                    <button class="media-action-icon-btn" id="ai-generate-btn" onclick="fetchGenAIContent()">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24">
                            <g fill="none" fill-rule="evenodd">
                                <path
                                    d="m12.594 23.258l-.012.002l-.071.035l-.02.004l-.014-.004l-.071-.036q-.016-.004-.024.006l-.004.01l-.017.428l.005.02l.01.013l.104.074l.015.004l.012-.004l.104-.074l.012-.016l.004-.017l-.017-.427q-.004-.016-.016-.018m.264-.113l-.014.002l-.184.093l-.01.01l-.003.011l.018.43l.005.012l.008.008l.201.092q.019.005.029-.008l.004-.014l-.034-.614q-.005-.019-.02-.022m-.715.002a.02.02 0 0 0-.027.006l-.006.014l-.034.614q.001.018.017.024l.015-.002l.201-.093l.01-.008l.003-.011l.018-.43l-.003-.012l-.01-.01z" />
                                <path fill="currentColor"
                                    d="M19 19a1 1 0 0 1 .117 1.993L19 21h-7a1 1 0 0 1-.117-1.993L12 19zm.631-14.632a2.5 2.5 0 0 1 0 3.536L8.735 18.8a1.5 1.5 0 0 1-.44.305l-3.804 1.729c-.842.383-1.708-.484-1.325-1.326l1.73-3.804a1.5 1.5 0 0 1 .304-.44L16.096 4.368a2.5 2.5 0 0 1 3.535 0m-2.12 1.414L6.677 16.614l-.589 1.297l1.296-.59L18.217 6.49a.5.5 0 1 0-.707-.707M6 1a1 1 0 0 1 .946.677l.13.378a3 3 0 0 0 1.869 1.87l.378.129a1 1 0 0 1 0 1.892l-.378.13a3 3 0 0 0-1.87 1.869l-.129.378a1 1 0 0 1-1.892 0l-.13-.378a3 3 0 0 0-1.869-1.87l-.378-.129a1 1 0 0 1 0-1.892l.378-.13a3 3 0 0 0 1.87-1.869l.129-.378A1 1 0 0 1 6 1m0 3.196A5 5 0 0 1 5.196 5q.448.355.804.804q.355-.448.804-.804A5 5 0 0 1 6 4.196" />
                            </g>
                        </svg>
                    </button>

                    <!-- Post submission button -->
                    <button class="media-post-button" id="submit-post" onclick="uploadPost(true)">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>

            </div>
            <div class="mb-3" id="schedule-section" style="display: none;">
                <label for="scheduleDate" class="form-label text-muted" style="margin: 10px;">Schedule Post</label>
                <div style="max-width: 220px;">
                    <input type="datetime-local" id="scheduleDate" class="form-control" />
                    <small class="text-muted" style="margin: 10px;">Format: mm/dd/yy</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Main container -->
    <div class="container">
        <? include 'inc/php/social_navbar.php' ?>
        <? include 'inc/php/social_sidebar.php' ?>
        <div class="main-content">
            <div class="event">
                <?
                // include 'event_scrollbar.php'
                ?>

                <div class="posts-feed">
                    <div class="first_right_container">
                    <h1 style="padding: 10px;">Pincode</h1>

                    </div>
                    <div class="loading-spinner"></div>
                </div>
            </div>

            <div class="fab-container">
                <button class="fab-button" id="create-post-fab">
                    <i class="fas fa-plus"></i>
                </button>
            </div>
            <?php include 'inc/php/right_news_module.php' ?>
        </div>

        <? include "inc/php/footer.php"; ?>
    </div>

    <script>
        // Toggle the modal visibility
        function toggleModal() {
            const modal = document.getElementById('post-modal');
            modal.classList.toggle('active');

            // Reset form when opening
            if (modal.classList.contains('active')) {
                resetForm();
            }
        }


        // document.getElementById('schedule-toggle-btn').addEventListener('click', function() {
        //     const scheduleSection = document.getElementById('schedule-section');
        //     scheduleSection.style.display = scheduleSection.style.display === 'none' ? 'block' : 'none';
        // });


        // Reset the form fields
        function resetForm() {
            document.getElementById('contentTextarea').value = '';
            document.getElementById('mediaSlider').style.display = 'none';
            document.getElementById('mediaSlides').innerHTML = '';
            document.getElementById('linkPreview').style.display = 'none';
            document.getElementById('ytPreview').style.display = 'none';
            document.getElementById('loadingIndicator').style.display = 'none';
            document.getElementById('loadingIcon').style.display = 'none';

            // Reset hidden fields
            document.getElementById('hiddenTitle').value = '';
            document.getElementById('hiddenDesc').value = '';
            document.getElementById('hiddenUrl').value = '';
            document.getElementById('hiddenImage').value = '';
            document.getElementById('hiddenDomain').value = '';
            document.getElementById('hiddenYTLink').value = '';
        }

        // Adjust textarea height based on content
        function adjustTextareaHeight(textarea) {
            textarea.style.height = 'auto';
            textarea.style.height = textarea.scrollHeight + 'px';
        }

        // Toggle dropdown visibility
        function toggleDropdown() {
            const dropdown = document.getElementById('privacy-dropdown-content');
            dropdown.classList.toggle('active');
        }

        // Handle dropdown item selection
        function selectPrivacyOption(option) {
            const dropdown = document.getElementById('privacy-dropdown-content');
            const dropdownBtn = document.getElementById('privacy-dropdown-btn');

            // Update dropdown button text and icon
            const icon = option === 'public' ? 'fa-globe' : 'fa-user-friends';
            const text = option === 'public' ? 'Public' : 'People I follow';

            dropdownBtn.innerHTML = `
    <i class="fas ${icon}"></i>
    <span>${text}</span>
    <i class="fas fa-caret-down"></i>
    `;

            // Update active class
            const items = dropdown.querySelectorAll('.media-dropdown-item');
            items.forEach(item => {
                item.classList.remove('active');
                if (item.querySelector('span').textContent === text) {
                    item.classList.add('active');
                }
            });

            // Close dropdown
            dropdown.classList.remove('active');
        }

        // Handle media file preview
        function previewMedia() {
            const fileInput = document.getElementById('fileInput');
            const mediaSlider = document.getElementById('mediaSlider');
            const mediaSlides = document.getElementById('mediaSlides');

            if (fileInput.files.length > 0) {
                mediaSlider.style.display = 'block';
                mediaSlides.innerHTML = '';

                Array.from(fileInput.files).forEach(file => {
                    const reader = new FileReader();

                    reader.onload = function(e) {
                        const slideDiv = document.createElement('div');
                        slideDiv.className = 'slide';

                        if (file.type.startsWith('image/')) {
                            const img = document.createElement('img');
                            img.src = e.target.result;
                            img.alt = 'Uploaded image';
                            slideDiv.appendChild(img);
                        } else if (file.type.startsWith('video/')) {
                            const video = document.createElement('video');
                            video.src = e.target.result;
                            video.controls = true;
                            slideDiv.appendChild(video);
                        }

                        mediaSlides.appendChild(slideDiv);
                    };

                    reader.readAsDataURL(file);
                });
            } else {
                mediaSlider.style.display = 'none';
            }
        }

        // Handle slider navigation
        let slideIndex = 0;

        function moveSlide(direction) {
            const slides = document.querySelectorAll('.slide');

            if (slides.length === 0) return;

            slideIndex += direction;

            if (slideIndex >= slides.length) {
                slideIndex = 0;
            } else if (slideIndex < 0) {
                slideIndex = slides.length - 1;
            }

            const translateValue = -slideIndex * 100;
            document.getElementById('mediaSlides').style.transform = `translateX(${translateValue}%)`;
        }

        function fetchGenAIContent() {
            const loadingIndicator = document.getElementById('loadingIndicator');
            const textarea = document.getElementById('contentTextarea');

            // Check if textarea exists
            if (!textarea) {
                console.error("Textarea with id 'contentTextarea' not found.");
                return;
            }

            const textareaValue = textarea.value.trim();

            // Check if the textarea is empty
            if (!textareaValue) {
                alert("Please enter something in the textarea.");
                console.warn("Textarea is empty. AJAX request not sent.");
                return;
            }

            // Show loading indicator
            loadingIndicator.style.display = 'block';
            animateLoadingDots(); // Your existing animation function

            // Make the AJAX request using fetch API (modern replacement for $.ajax)
            fetch("genai/process_genai.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded",
                    },
                    body: new URLSearchParams({
                        working_headline: textareaValue,
                        avatar: "#post"
                    })
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! Status: ${response.status}`);
                    }
                    return response.text();
                })
                .then(data => {
                    // Populate the textarea with the response
                    textarea.value = data;
                    adjustTextareaHeight(textarea); // Adjust height after setting the response
                })
                .catch(error => {
                    console.error(`Error fetching GenAI content: ${error}`);
                })
                .finally(() => {
                    // Hide the loading indicator when complete
                    loadingIndicator.style.display = 'none';
                    // Assuming you have a function to stop the animation
                    stopAnimatingLoadingDots();
                });
        }

        // Animate loading dots for AI generation
        function animateLoadingDots() {
            const dots = document.getElementById('dots');
            let dotCount = 0;

            const interval = setInterval(() => {
                dotCount = (dotCount + 1) % 4;
                dots.textContent = '.'.repeat(dotCount);

                if (document.getElementById('loadingIndicator').style.display === 'none') {
                    clearInterval(interval);
                }
            }, 300);
        }


        // Get media files from input
        function getMediaFiles() {
            const fileInput = document.getElementById('fileInput');
            return Array.from(fileInput.files);
        }

        // Show toast notification
        function showToast(message) {
            // Create toast notification if it doesn't exist
            let toast = document.querySelector('.toast-notification');

            if (!toast) {
                toast = document.createElement('div');
                toast.className = 'toast-notification';
                document.body.appendChild(toast);
            }

            toast.textContent = message;
            toast.classList.add('show');

            // Hide toast after 3 seconds
            setTimeout(() => {
                toast.classList.remove('show');
            }, 3000);
        }

        // Initialize event listeners
        document.addEventListener('DOMContentLoaded', function() {
            // Open modal
            document.querySelector('.fab-button')?.addEventListener('click', toggleModal);

            // Close modal
            document.querySelector('.media-close-modal').addEventListener('click', toggleModal);

            // Toggle privacy dropdown
            document.getElementById('privacy-dropdown-btn').addEventListener('click', toggleDropdown);

            // Handle privacy option selection
            document.querySelectorAll('.media-dropdown-item').forEach(item => {
                item.addEventListener('click', function() {
                    const option = this.querySelector('span').textContent === 'Public' ? 'public' : 'private';
                    selectPrivacyOption(option);
                });
            });

            // Handle media upload button click
            document.getElementById('media-upload-btn').addEventListener('click', function() {
                document.getElementById('fileInput').click();
            });

            // Close modal when clicking outside
            window.addEventListener('click', function(event) {
                const modal = document.getElementById('post-modal');
                if (event.target === modal) {
                    toggleModal();
                }
            });

            // Close dropdown when clicking outside
            window.addEventListener('click', function(event) {
                const dropdown = document.getElementById('privacy-dropdown-content');
                const dropdownBtn = document.getElementById('privacy-dropdown-btn');

                if (event.target !== dropdownBtn && !dropdownBtn.contains(event.target)) {
                    dropdown.classList.remove('active');
                }
            });
        });
    </script>

</body>

</html>