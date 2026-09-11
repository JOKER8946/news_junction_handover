<?php
header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

// Check if the request is a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    if (isset($input['action']) && $input['action'] === 'clear') {
        $posts_file = 'posts.json';

        if (file_exists($posts_file)) {
            // Attempt to delete the file
            if (unlink($posts_file)) {
                $response['success'] = true;
                $response['message'] = 'posts.json cleared successfully.';
            } else {
                $response['message'] = 'Failed to delete posts.json.';
            }
        } else {
            $response['success'] = true;
            $response['message'] = 'posts.json does not exist, nothing to clear.';
        }
    } else {
        $response['message'] = 'Invalid action.';
    }
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
?>