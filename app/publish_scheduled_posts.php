<?php
include 'inc/php/db_config.php';

// Fetch scheduled posts whose time has come
$sql = "SELECT id, scheduledDate FROM reader_stream WHERE deleteFlag = 1 AND scheduleFlag IS NULL
 AND scheduledDate <= NOW() ";
$result = $readerdb->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $postId = $row['id'];
        $scheduledDate = $row['scheduledDate'];

        // Update post: set deleteFlag = 0 and postedOn = scheduledDate
        $update = $readerdb->prepare("UPDATE reader_stream SET deleteFlag = 0, postedOn = ?,scheduleFlag = 1 WHERE id = ?");
        $update->bind_param("si", $scheduledDate, $postId);
        $update->execute();
        $update->close();

        // Optional: Log result
        file_put_contents("log.txt", "[" . date("Y-m-d H:i:s") . "] Posted ID $postId at $scheduledDate\n", FILE_APPEND);
    }

    echo json_encode([
        'status' => 'success',
        'message' => $result->num_rows . ' scheduled post(s) published.'
    ]);
} else {
    echo json_encode([
        'status' => 'no_update',
        'message' => 'No scheduled posts ready to publish yet.'
    ]);
}
