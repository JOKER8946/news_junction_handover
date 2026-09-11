<?php
require '../inc/php/db_config.php'; // Ensure this file contains the database connection
require '../inc/php/validate.logged.php';

global $readerdb;

if ($_SERVER["REQUEST_METHOD"] === "GET" && isset($_GET['user_id'])) {

    // Fetch all channels created by the user
    $query = "SELECT id, name FROM channels WHERE created_by = ?";
    $stmt = $readerdb->prepare($query);
    $stmt->bind_param("i", $gUserId);
    $stmt->execute();
    $result = $stmt->get_result();

    $channels = [];
    while ($row = $result->fetch_assoc()) {
        $channels[] = [
            "id" => $row["id"],
            "name" => $row["name"]
        ];
    }

    echo json_encode(["success" => true, "channels" => $channels]);
} else {
    echo json_encode(["success" => false, "message" => "Invalid request. User ID required."]);
}

?>