<?php
include './assets/php/validate.logged.php';
include './assets/php/function.php';
include './assets/php/db_config.php';

// Set content type to JSON for AJAX response
header('Content-Type: application/json');

// Initialize the response
$response = [
    'status' => 'error',
    'message' => 'There was an issue with the request.',
    'data' => []
];

// Check if the form was submitted via POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Collect the content from the form
    $content = isset($_POST['content']) ? trim($_POST['content']) : null;
    $media_url = null;

    // Check for file upload
    if (isset($_FILES['media']) && $_FILES['media']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/';  // Directory where the files will be stored
        $file_name = basename($_FILES['media']['name']);
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        // Generate a unique file name using the user ID and timestamp
        $timestamp = time();
        $unique_name = 'user_' . $timestamp . '.' . $file_ext;

        // Allowed file extensions
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'mp4', 'avi', 'mov'];

        if (in_array($file_ext, $allowed_extensions)) {
            $media_url = $upload_dir . $unique_name;

            // Check if the upload directory exists and is writable
            if (!is_dir($upload_dir) || !is_writable($upload_dir)) {
                $response['message'] = 'Error: Upload directory is not writable.';
                echo json_encode($response);
                exit;
            }

            // Move the uploaded file
            if (move_uploaded_file($_FILES['media']['tmp_name'], $media_url)) {
                $response['data'][] = ['media_upload' => 'File uploaded successfully.'];
            } else {
                $response['message'] = 'Error: Failed to upload the file.';
                echo json_encode($response);
                exit;
            }
        } else {
            $response['message'] = 'Error: Invalid file type. Only images and videos are allowed.';
            echo json_encode($response);
            exit;
        }
    } else {
        $response['message'] = 'No file uploaded.';
    }

    // Process meta data
    $jsonMetaData = [];
    $inputFields = ['hiddenTitle' => 'metaTitle',
     'hiddenDesc'=> 'metaDesc', 
     'hiddenUrl'=> 'metaUrl', 
     'hiddenImage'=> 'metaImage', 
     'hiddenDomain'=> 'metaDomain', 
     'hiddenYTLink'=> 'youtubeLink'];
    foreach ($inputFields as $field => $attribute) {
        if (isset($_POST[$field])) {
            $jsonMetaData[$attribute] = $_POST[$field];
        }
    }

    // Encode the meta data as JSON
    $jsonString = !empty($jsonMetaData) ? json_encode($jsonMetaData) : null;

    // If either content or media is present, proceed with database insertion
    if ($content || $media_url) {
        // Assuming you have the database connection already set up
        $stmt = $readerdb->prepare("INSERT INTO reader_stream (userId, chat, mediaPath, metadata) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $gUserId, $content, $media_url, $jsonString);

        if ($stmt->execute()) {
            $response['status'] = 'success';
            $response['message'] = 'Post submitted successfully.';
            $response['data'][] = ['database' => 'Post inserted into the database.'];
        } else {
            $response['message'] = 'Error: Failed to insert post into database.';
            $response['error'] = $stmt->error;  // Capture and display the SQL error
        }

        $stmt->close();
    } else {
        $response['message'] = "The message can't be empty.";
    }
} else {
    $response['message'] = 'Invalid request method. Please use POST.';
}

echo json_encode($response);  // Output the response as JSON
?>
