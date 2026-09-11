<?php
// process/save_file.php

header('Content-Type: application/json');

$response = array('success' => false, 'message' => '');

try {
    // Get the JSON data
    $jsonData = file_get_contents('php://input');
    $data = json_decode($jsonData, true);

    if (!isset($data['content']) || !isset($data['filename'])) {
        throw new Exception('Missing required data');
    }

    // Validate filename
    if (!preg_match('/^[0-9]+\-[a-z0-9\-]+\.html$/', $data['filename'])) {
        throw new Exception('Invalid filename format');
    }

    // Set the directory path
    $directory = '../pages/';

    // Create directory if it doesn't exist
    if (!file_exists($directory)) {
        mkdir($directory, 0755, true);
    }

    // Full file path
    $filepath = $directory . $data['filename'];

    // Save the file
    if (file_put_contents($filepath, $data['content']) === false) {
        throw new Exception('Failed to save file');
    }

    $response['success'] = true;
    $response['message'] = 'File saved successfully';

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);