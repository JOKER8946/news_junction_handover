<?php
require_once './assets/php/db_config.php';

// SQL query to get the last 10 chat messages
$sql = "SELECT userId,id, chat FROM reader_stream ORDER BY postedOn DESC LIMIT 10";
$result = $readerdb->query($sql);

$chats = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Get the userId and chat message
        $userId = $row['userId'];
        $postId = $row['id'];
        $chatMessage = $row['chat'];

        $profileSql = "SELECT profile_pic FROM user WHERE id = ?";
        $stmt = $creamdb->prepare($profileSql);
        $stmt->bind_param("i", $userId); // "i" means the userId is an integer
        $stmt->execute();
        $profileResult = $stmt->get_result();

        $profilePhoto = null;
        if ($profileResult->num_rows > 0) {
            $profileRow = $profileResult->fetch_assoc();
            $profilePhoto = $profileRow['profile_pic']; 
        }

        // Append the chat message and profile photo to the chats array
        $chats[] = [
            'post_id' => $postId,
            'chat_message' => $chatMessage,
            'profile_photo' => $profilePhoto // Add profile photo to the result
        ];
    }
} else {
    echo "No records found";
}

// Close the database connections
$readerdb->close();
$creamdb->close();

// Return the result as JSON
echo json_encode($chats);
?>
