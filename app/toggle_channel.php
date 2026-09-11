<?php
// Include the database connection
include 'assets/php/db_connect.php';

// Get the data from the POST request (assuming JSON format)
$data = json_decode(file_get_contents('php://input'), true);

// Ensure the data is valid
if (isset($data['postId']) && isset($data['userId'])) {
    $postId = (int)$data['postId'];
    $userId = (int)$data['userId'];

    // Prepare the response array
    $response = ['success' => false, 'action' => ''];

    // Function to toggle the post in the channel
    function toggleChannel($conn, $postId, $userId)
    {
        // Check if the post is already in the user's channel
        $sql = "SELECT COUNT(*) AS channelCheck FROM my_channel WHERE post_id = ? AND user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $postId, $userId);
        $stmt->execute();
        $stmt->bind_result($channelCheck);
        $stmt->fetch();
        $stmt->close();

        // If the post is not in the channel, add it
        if ($channelCheck === 0) {
            // Insert the post into the my_channel table
            $sql = "INSERT INTO my_channel (post_id, user_id) VALUES (?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $postId, $userId);
            $stmt->execute();
            $stmt->close();

            return 'added';
        } else {
            // If the post is in the channel, remove it
            $sql = "DELETE FROM my_channel WHERE post_id = ? AND user_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $postId, $userId);
            $stmt->execute();
            $stmt->close();

            return 'removed';
        }
    }

    // Perform the action and get the result
    $action = toggleChannel($conn, $postId, $userId);

    // Set the response data
    if ($action === 'added') {
        $response['success'] = true;
        $response['action'] = 'added';
    } elseif ($action === 'removed') {
        $response['success'] = true;
        $response['action'] = 'removed';
    }
    
    // Return the response as JSON
    echo json_encode($response);
} else {
    // If invalid data is provided, return an error response
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
}
?>
