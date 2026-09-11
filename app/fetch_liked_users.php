<?php
include './inc/db_connect.php';
include './inc/config.php';
include './inc/validate.logged.php';
include './assets/php/function.php';

if (isset($_POST['postId'])) {
    $postId = intval($_POST['postId']);
    
    // Step 1: Get userIds from Database A (likes table)
    $sqlA = "SELECT userId FROM reader_stream_like WHERE streamId = ?";
    if ($stmtA = $conn->prepare($sqlA)) {
        $stmtA->bind_param("i", $postId);
        $stmtA->execute();
        $resultA = $stmtA->get_result();
        
        $userIds = [];
        while ($row = $resultA->fetch_assoc()) {
            $userIds[] = $row['userId'];
        }
        $stmtA->close();
    } else {
        echo json_encode(['error' => 'Query preparation failed for Database A.']);
        exit;
    }

    if (empty($userIds)) {
        echo json_encode(['message' => 'No likes found for this post.']);
        exit;
    }

    // Step 2: Fetch usernames from Database B (users table)
    $usernames = [];
    $userIdsStr = implode(",", $userIds); // Convert user IDs into a comma-separated string
    $sqlB = "SELECT full_name FROM user WHERE id IN ($userIdsStr)";

    $resultB = $db->query($sqlB);
    if ($resultB) {
        while ($row = $resultB->fetch_assoc()) {
            $usernames[] = $row['full_name'];
        }
        echo json_encode($usernames); // Return usernames as JSON
    } else {
        echo json_encode(['error' => 'Failed to fetch usernames from Database B.']);
    }
} else {
    echo json_encode(['error' => 'Post ID not provided.']);
}
?>
