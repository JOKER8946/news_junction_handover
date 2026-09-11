<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once '../inc/config.php';
require_once '../inc/php/db_config.php';
require_once '../inc/php/validate.logged.php';

header('Content-Type: application/json');

// Only admin (userId 21) can access
$adminIds = [21, 18];
if (!in_array($gUserId, $adminIds)) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$act = isset($_POST['act']) ? $_POST['act'] : '';

if ($act == 'verify') {
    $userId = intval($_POST['userId'] ?? 0);
    $notes = $_POST['notes'] ?? '';

    if ($userId <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid user ID.']);
        exit;
    }

    // Update reporter status to verified
    $stmt = $creamdb->prepare("UPDATE reporters SET status = 'verified', admin_notes = ?, date_verified = NOW() WHERE user_id = ?");
    $stmt->bind_param("si", $notes, $userId);

    if ($stmt->execute()) {
        // Send verification email to reporter
        $userStmt = $creamdb->prepare("SELECT full_name, email FROM user WHERE id = ?");
        $userStmt->bind_param("i", $userId);
        $userStmt->execute();
        $userResult = $userStmt->get_result();
        $user = $userResult->fetch_assoc();
        $userStmt->close();

        if ($user) {
            $tmpHTML = "<html><body>";
            $tmpHTML .= "<div style=\"font-family:Arial;font-size:12px;\">\r\n";
            $tmpHTML .= "Dear " . $user['full_name'] . ",<br><br>\r\n";
            $tmpHTML .= "Your reporter account on News Junction has been <strong>verified and approved</strong> by our admin team.<br><br>\r\n";
            $tmpHTML .= "You can now log in to your account and start reporting.<br><br>\r\n";
            $tmpHTML .= "<a href=\"https://newsjunction.net/sign-in.php?type=login\">Click here to login</a><br><br>\r\n";
            $tmpHTML .= "Warm Regards,<br>\r\nNews Junction Team<br>\r\n</div>";
            $tmpHTML .= "</body></html>";

            sendEmail($user['full_name'], $user['email'], '', 'News Junction: Your Reporter Account is Verified!', $tmpHTML);
        }

        echo json_encode(['status' => 'OK', 'message' => 'Reporter verified successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $stmt->error]);
    }
    $stmt->close();
}

if ($act == 'reject') {
    $userId = intval($_POST['userId'] ?? 0);
    $notes = $_POST['notes'] ?? '';

    if ($userId <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid user ID.']);
        exit;
    }

    // Update reporter status to rejected
    $stmt = $creamdb->prepare("UPDATE reporters SET status = 'rejected', admin_notes = ?, date_verified = NOW() WHERE user_id = ?");
    $stmt->bind_param("si", $notes, $userId);

    if ($stmt->execute()) {
        // Send rejection email to reporter
        $userStmt = $creamdb->prepare("SELECT full_name, email FROM user WHERE id = ?");
        $userStmt->bind_param("i", $userId);
        $userStmt->execute();
        $userResult = $userStmt->get_result();
        $user = $userResult->fetch_assoc();
        $userStmt->close();

        if ($user) {
            $tmpHTML = "<html><body>";
            $tmpHTML .= "<div style=\"font-family:Arial;font-size:12px;\">\r\n";
            $tmpHTML .= "Dear " . $user['full_name'] . ",<br><br>\r\n";
            $tmpHTML .= "We regret to inform you that your reporter registration on News Junction has not been approved at this time.<br><br>\r\n";
            if ($notes) {
                $tmpHTML .= "Reason: " . htmlspecialchars($notes) . "<br><br>\r\n";
            }
            $tmpHTML .= "If you believe this is an error, please contact our support team.<br><br>\r\n";
            $tmpHTML .= "Warm Regards,<br>\r\nNews Junction Team<br>\r\n</div>";
            $tmpHTML .= "</body></html>";

            sendEmail($user['full_name'], $user['email'], '', 'News Junction: Reporter Account Update', $tmpHTML);
        }

        echo json_encode(['status' => 'OK', 'message' => 'Reporter rejected.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $stmt->error]);
    }
    $stmt->close();
}
?>
