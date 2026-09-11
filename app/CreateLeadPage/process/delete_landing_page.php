<?php
include "../../inc/php/validate.logged.php";
include "../../inc/php/db_config.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $pageId = intval($_POST['id']);
    $userId = $gUserId;

    // Verify the page belongs to the user
    $query = "UPDATE user_landing_page SET deleteFlag = 'Y' WHERE id = ? AND user_id = ?";
    $stmt = $creamdb->prepare($query);
    $stmt->bind_param("ii", $pageId, $userId);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Database error']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
}
