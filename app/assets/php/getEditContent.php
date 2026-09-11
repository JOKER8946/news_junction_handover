<?php
header('Content-Type: application/json');
include 'db_config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['post_id'])) {

        $post_id = $_POST['post_id'];

        // Prepare the SQL statement to get the chat content
        $stmt = $readerdb->prepare("SELECT chat FROM reader_stream WHERE id = ?");
        $stmt->bind_param("i", $post_id);

        // Execute the query
        if ($stmt->execute()) {
            // Fetch the result
            $stmt->store_result();
            $stmt->bind_result($chat);
            $stmt->fetch();

            $res = [
                'chat' => $chat, // Return the chat content
            ];
        } else {
            $res = [
                'chat' => "",
            ];
        }
        $stmt->close();
        echo json_encode($res);
    }
}