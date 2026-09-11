<?php
include "../../inc/php/validate.logged.php";
include "../../inc/php/db_config.php";


// Set header before any output
header('Content-Type: application/json');

$response = array('success' => false, 'message' => '');

try {
    // Log incoming data
    error_log('POST Data: ' . print_r($_POST, true));

    // Validate request
    if (!isset($_POST['title']) || !isset($_POST['action_email']) || !isset($_POST['filename'])) {
        throw new Exception('Missing required fields');
    }

    // Sanitize inputs
    $title = $_POST['title'];
    $actionEmail = $_POST['action_email'];
    $filename = $_POST['filename'];

    // Log sanitized data
    error_log('Sanitized Data - Title: ' . $title . ', Email: ' . $actionEmail . ', Filename: ' . $filename);

    // Validate email
    if (!$actionEmail) {
        throw new Exception('Invalid email address');
    }

    // Validate filename
    if (!preg_match('/^[0-9]+\-[a-z0-9\-]+\.html$/', $filename)) {
        throw new Exception('Invalid filename format');
    }

    $userId = $gUserId;

    // Use MySQLi prepared statement
    $stmt = $creamdb->prepare("INSERT INTO user_landing_page (user_id, title, action_email, url) VALUES (?, ?, ?, ?)");
    
    // Check if the statement preparation succeeded
    if (!$stmt) {
        throw new Exception('Failed to prepare the statement: ' . $creamdb->error);
    }

    // Bind parameters to the statement
    $stmt->bind_param("isss", $userId, $title, $actionEmail, $filename);

    // Execute the query
    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = 'Page details saved successfully';
    } else {
        $response['success'] = false;
        $response['message'] = 'Failed to save page details in the database: ' . $stmt->error;
    }

    // Close the prepared statement
    $stmt->close();

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
} finally {
    // Ensure no other output has been sent
    if (!headers_sent()) {
        header('Content-Type: application/json');
    }
    echo json_encode($response);
}
