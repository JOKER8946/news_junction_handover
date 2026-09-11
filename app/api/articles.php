<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Include necessary files
include '../inc/php/db_config.php';
include '../inc/php/function.php';

// Set headers for API
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Key');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

function authenticateUser()
{
    $headers = getallheaders();
    $accessKey = null;

    // Check for API key in headers
    if (isset($headers['Authorization'])) {
        $authHeader = $headers['Authorization'];
        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $accessKey = $matches[1];
        }
    } elseif (isset($headers['X-API-Key'])) {
        $accessKey = $headers['X-API-Key'];
    } elseif (isset($_GET['accesskey'])) {
        $accessKey = $_GET['accesskey'];
    }

    if (!$accessKey) {
        echo ('error API key required');
        exit;
    }

    // Validate access key against your existing api_tokens table
    global $readerdb, $creamdb;

    // Fallback: check if token doesn't contain _DEACTIVATED
    $stmt = $creamdb->prepare("SELECT user_id, id FROM api_tokens WHERE api_token = ?");

    $stmt->bind_param("s", $accessKey);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo ('error Invalid or inactive API key');
        exit;
    }

    $token = $result->fetch_assoc();
    $stmt->close();
    return $token['user_id'];
}

// authenticateUser(); 

function fetch_articles($readerdb, $rss_id)
{
    // Error handling if RSS ID is not provided
    if ($rss_id === null) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'RSS ID not provided.']);
        exit;
    }

    // Prepare the SQL query to fetch articles from the database
    $stmt = $readerdb->prepare("SELECT rfa.id, rfu.rss_publisher, rfa.url, rfa.title, rfa.description, rfa.image AS articleImage, rfu.rss_image AS rssImage, rfa.date
                                FROM rss_feeds_articles rfa 
                                INNER JOIN rss_feeds_url rfu ON rfa.feed_id = rfu.rss_id 
                                WHERE rfu.rss_id = ? 
                                ORDER BY rfa.date DESC limit 100");
    $stmt->bind_param("i", $rss_id);
    $stmt->execute();
    $result = $stmt->get_result();

    // Initialize an array to store articles data
    $articles = [];

    // If there are articles in the database
    if ($result->num_rows > 0) {
        // Fetch data for each article
        while ($row = $result->fetch_assoc()) {
            $article = [];
            $article['id'] = stripslashes($row['id']);
            $article['title'] = htmlspecialchars(strip_tags(stripslashes($row['title'])));
            $article['description'] = htmlspecialchars(strip_tags(stripslashes($row['description'])));

            // Determine which image to use (article or RSS feed image)
            $image = is_null($row['articleImage']) || $row['articleImage'] === '' ?
                (is_null($row['rssImage']) || $row['rssImage'] === '' ? '' : $row['rssImage']) :
                $row['articleImage'];
            $article['image'] = $image;
            $article['url'] = $row['url'];

            // Push the article data to the array
            $articles[] = $article;
        }
    }

    // If no articles, send a message in the response
    if (empty($articles)) {
        $articles = ['message' => 'No news available at the moment.'];
    }

    // Return the articles as a JSON response
    header('Content-Type: application/json');
    echo json_encode($articles);

    // Close the prepared statement
    $stmt->close();
    $readerdb->close();
}
?>

<?php
// Example of how to call the function and pass the appropriate parameters
// Assume that $readerdb is your database connection and $rss_id is provided via URL or request
if (isset($_GET['rss_id'])) {
    $rss_id = $_GET['rss_id'];
    fetch_articles($readerdb, $rss_id);
} else {
    // If rss_id is not provided, output an error
    header('Content-Type: application/json');
    echo json_encode(['error' => 'RSS ID is missing.']);
}
?>
