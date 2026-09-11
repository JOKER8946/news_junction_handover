<?php
// Function to check if the user has already liked the post
function hasUserLiked($conn, $userId, $streamId) {
    $query = "SELECT 1 FROM reader_stream_like WHERE userId = ? AND streamId = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $userId, $streamId);
    $stmt->execute();
    $stmt->store_result();

    return $stmt->num_rows > 0;  // Return true if the user has already liked, else false
}

// Function to insert a like (like the post)
function insertLike($conn, $userId, $streamId) {
    $query = "INSERT INTO reader_stream_like (userId, streamId) VALUES (?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $userId, $streamId);
    return $stmt->execute();
}

// Function to delete a like (unlike the post)
function deleteLike($conn, $userId, $streamId) {
    $query = "DELETE FROM reader_stream_like WHERE userId = ? AND streamId = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $userId, $streamId);
    return $stmt->execute();
}

// Function to get the like count for a stream
function getLikeCount($conn, $streamId) {
    $query = "SELECT COUNT(*) AS like_count FROM reader_stream_like WHERE streamId = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $streamId);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    // return ($data['like_count']* 10)+ rand(0, 9);
    return $data['like_count'];
}



// Include your database connection (assumed to be set up in db.php)
include('db_connect.php');


// Get the raw POST data
$data = json_decode(file_get_contents('php://input'), true);

// Check if data is valid
if (isset($data['request'], $data['userId'], $data['feedId'])) {
    $request = $data['request'];
    $userId = $data['userId'];
    $streamId = $data['feedId'];  // FeedId corresponds to streamId

    // Handle like or unlike action
    if ($request === 'like') {
        // Check if the user has already liked this post
        if (!hasUserLiked($conn, $userId, $streamId)) {
            // User has not liked yet, so insert a new like
            if (insertLike($conn, $userId, $streamId)) {
                // After inserting, get the new like count
                $likeCount = getLikeCount($conn, $streamId);
                $response = ['status' => 'success', 'likeCount' => $likeCount];
            } else {
                $response = ['status' => 'error', 'message' => 'Unable to like post'];
            }
        } else {
            $response = ['status' => 'error', 'message' => 'You already liked this post'];
        }
    } elseif ($request === 'unlike') {
        // User has liked this post, so remove the like
        if (deleteLike($conn, $userId, $streamId)) {
            // After deleting, get the updated like count
            $likeCount = getLikeCount($conn, $streamId);
            // If there are no likes, return null (or 0 depending on your preference)
            $response = ['status' => 'success', 'likeCount' => $likeCount > 0 ? $likeCount : null];
        } else {
            $response = ['status' => 'error', 'message' => 'Unable to unlike post'];
        }
    } else {
        // Invalid request type
        $response = ['status' => 'error', 'message' => 'Invalid request'];
    }
} else {
    $response = ['status' => 'error', 'message' => 'Invalid data'];
}

// Set the response content type to JSON and return the response
header('Content-Type: application/json');
echo json_encode($response);
?>

