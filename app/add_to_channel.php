<?php
// Database connection
require 'assets/php/db_config.php'; // Ensure this file contains the database connection

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get data from POST request
    $postId = $_POST['postId'];
    $channelId = $_POST['channelId'];

    // Prepare SQL query to insert data into your database
    $sql = "INSERT INTO channel_content (post_id, channel_id) VALUES (?, ?)";

    // Prepare statement
    if ($stmt = $readerdb->prepare($sql)) {
        // Bind parameters and execute
        $stmt->bind_param("ii", $postId, $channelId);
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to add to channel']);
        }
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }

    // Close connection
    $readerdb->close();
}   
?>
