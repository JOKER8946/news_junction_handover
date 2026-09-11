<?
include '../inc/php/db_config.php';
include '../inc/php/validate.logged.php';
include '../inc/php/function.php';
// Get the search query from the request (if it's an AJAX request)
$searchQuery = isset($_GET['query']) ? $_GET['query'] : '';

$userResults = [];
$postResults = [];
$channelResults = [];

if (!empty($searchQuery)) {
    // SEARCH USERS
    $sqlUser = "SELECT id, full_name, profile_pic FROM user WHERE full_name LIKE ? OR email LIKE ? LIMIT 10";
    $stmtUser = $creamdb->prepare($sqlUser);
    $searchQueryLike =  $searchQuery . '%';
    $stmtUser->bind_param('ss', $searchQueryLike, $searchQueryLike);
    $stmtUser->execute();
    $resultUser = $stmtUser->get_result();

    while ($row = $resultUser->fetch_assoc()) {
        $profileUrl = isset($row['profile_pic']) ? "https://newsjunction.net/data/profilePic/" . $row['profile_pic'] : "https://newsjunction.net/data/profilePic/default.png";
        $userResults[] = [
            'type' => 'user',
            'full_name' => $row['full_name'],
            'img' => $profileUrl,
            'user_id' => $row['id'],
            'url' => "/profile.php?userId=" . $row['id']
        ];
    }

    // SEARCH POSTS
    $sqlPost = "SELECT id, chat, userId FROM reader_stream WHERE chat LIKE ?  LIMIT 10";
    $stmtPost = $readerdb->prepare($sqlPost);
    $searchQueryPost = '%' . $searchQuery . '%';
    $stmtPost->bind_param('s', $searchQueryPost);
    $stmtPost->execute();
    $resultPost = $stmtPost->get_result();

    while ($row = $resultPost->fetch_assoc()) {
        $postResults[] = [
            'type' => 'post',
            'title' => $row['chat'],
            'user_id' => $row['userId'],
            'url' => "/post-details.php?id=" . $row['id']
        ];
    }

    // SEARCH CHANNELS
    $sqlChannel = "SELECT id, name, created_by, profilePic FROM channels WHERE name LIKE ? LIMIT 10";
    $stmtChannel = $readerdb->prepare($sqlChannel);
    $stmtChannel->bind_param('s', $searchQueryLike);
    $stmtChannel->execute();
    $resultChannel = $stmtChannel->get_result();

    while ($row = $resultChannel->fetch_assoc()) {
        $profileUrl = isset($row['profilePic']) ? "https://newsjunction.net/data/channelPic/" . $row['profilePic'] : "https://newsjunction.net/data/profilePic/default.png";
        $channelResults[] = [
            'type' => 'channel',
            'img' => $profileUrl,
            'channel_name' => $row['name'],
            'user_id' => $row['created_by'],
            'url' => "/channel.php?channelId=" . $row['id'] . "&channelName=" . $row['name']
        ];
    }
}

// Combine all results
$response = [
    'users' => $userResults,
    'posts' => $postResults,
    'channels' => $channelResults
];

// Return JSON response for AJAX requests
if (isset($_GET['query'])) {
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}
