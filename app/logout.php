<?php
session_start();
include 'inc/php/db_config.php';


// Check if user is logged in and session variables are set
if (isset($_SESSION['userEmail'])) {
    // $email = $_SESSION['userEmail'];
    $user_id = $_SESSION['userId']; // Assuming you store the user ID in the session

    // // Update the logout timestamp in the 'user' table
    // $stmt = $creamdb->prepare("UPDATE user SET logout = NOW() WHERE email = ?");
    // $stmt->bind_param('s', $email);
    // $stmt->execute();

    // Now update the end time in the 'user_sessions' table to mark the session as ended
    $session_id = session_id();  // Get the session ID
    // echo "Session ID: " . session_id();

    $stmt2 = $creamdb->prepare("UPDATE session_log SET endTime = NOW() WHERE sessionId = ? AND userId = ?");
    $stmt2->bind_param('si', $session_id, $user_id);
    $stmt2->execute();
}

// Delete the cookie if it exists
if (isset($_COOKIE['knobly_user_data'])) {
    setcookie('knobly_user_data', '', time() - 3600, "/");
}

// Destroy the session
session_destroy();

// Redirect to the index page after logout
header("Location: index.php");
exit();
?>
