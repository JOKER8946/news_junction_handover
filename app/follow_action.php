<?php
include './inc/db_connect.php';
include './inc/config.php';
// Enable error reporting for troubleshooting

header('Content-Type: application/json'); // Return JSON response

// Get POST data (json format)
$input = json_decode(file_get_contents('php://input'), true);

// Check if the request data is available
if (!$input) {
    echo json_encode(["status" => "error", "message" => "No data received"]);
    exit;
}

$request = $input['request'] ?? null;
$followerId = $input['followerId'] ?? null;
$followingId = $input['followingId'] ?? null;

if ($request && $followerId && $followingId) {
    if ($request === 'follow') {
        // Insert follower-following relationship
        $stmt = $conn->prepare("INSERT INTO reader_stream_follow (follower_id, following_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $followerId, $followingId);
    } elseif ($request === 'unfollow') {
        // Delete follower-following relationship
        $stmt = $conn->prepare("DELETE FROM reader_stream_follow WHERE follower_id = ? AND following_id = ?");
        $stmt->bind_param("ii", $followerId, $followingId);
    } else {
        echo json_encode(["status" => "error", "message" => "Invalid request type"]);
        exit;
    }

    // Execute and send response
    if ($stmt->execute()) {
        // Return success message
        echo json_encode(["status" => "success"]);
    } else {
        // Database error
        echo json_encode(["status" => "error", "message" => "Database error: " . $stmt->error]);
    }

    $stmt->close();
} else {
    echo json_encode(["status" => "error", "message" => "Missing parameters (request, followerId, or followingId)"]);
}

$conn->close();
?>
