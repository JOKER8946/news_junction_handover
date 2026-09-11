<?
include "validate.logged.php";
include "db_config.php";

    // if($reqType)
    // $query = "INSERT INTO stream_saved (user_id, post_id) VALUES (?, ?)";
    // $stmt = $readerdb->prepare($query);
    // $stmt->bind_param('ii', $gUserId, $postId);
    // if ($stmt->execute()) {
    //     echo json_encode(['success' => true]);
    // } else {
    //     echo json_encode(['success' => false, 'error' => 'Failed to save post.']);
    // }

function deleteSave($conn, $userId, $streamId) {
    $query = "DELETE FROM stream_saved WHERE user_id = ? AND post_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $userId, $streamId);
    return $stmt->execute();
}

function hasUserSaved($conn, $userId, $streamId) {
    $query = "SELECT 1 FROM stream_saved WHERE user_id = ? AND post_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $userId, $streamId);
    $stmt->execute();
    $stmt->store_result();

    return $stmt->num_rows > 0;  // Return true if the user has already liked, else false
}
function insertSave($conn, $userId, $streamId) {
    $query = "INSERT INTO stream_saved (user_id, post_id) VALUES (?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $userId, $streamId);
    return $stmt->execute();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $postId = $_POST['id'];
    $request=$_POST['request'];
if ($request === 'save') {
    // Check if the user has already liked this post
    if (!hasUserSaved($readerdb, $gUserId, $postId)) {
        // User has not liked yet, so insert a new like
        if (insertSave($readerdb, $gUserId, $postId)) {
            $response = ['status' => 'success'];
        } else {
            $response = ['status' => 'error', 'message' => 'Unable to save post'];
        }
    } else {
        $response = ['status' => 'error', 'message' => 'You already saved this post'];
    }
} elseif ($request === 'unsave') {
    // User has liked this post, so remove the like
    if (deleteSave($readerdb, $gUserId, $postId)) {
        $response = ['status' => 'success'];
    } else {
        $response = ['status' => 'error', 'message' => 'Unable to unlike post'];
    }
} else {
    // Invalid request type
    $response = ['status' => 'error', 'message' => 'Invalid request'];
}
}
echo json_encode($response);
?>