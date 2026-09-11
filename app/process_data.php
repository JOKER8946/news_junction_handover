<?php
include './inc/php/validate.logged.php';
include './inc/php/function.php';
include './inc/php/db_config.php';

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
    $media_urls = [];
    // Whitelist visibility: only treat the post as private when the client
    // explicitly sends the literal string "private". Any missing/blank/garbled
    // value (e.g. from old cached JS that fails the dropdown read) → public.
    $visibility = (isset($_POST['visibility']) && $_POST['visibility'] === 'private') ? 'private' : 'public';

    // Scheduled post handling using deleteFlag
    $deleteFlag = isset($_POST['deleteFlag']) && $_POST['deleteFlag'] !== '' ? (int)$_POST['deleteFlag'] : 0;
    $scheduledDate = null;

    // If a scheduled date is provided, convert IST to UTC
    if ($deleteFlag && isset($_POST['scheduleDate'])) {
        $inputTime = $_POST['scheduleDate'];
        try {
            $ist = new DateTimeZone('Asia/Kolkata');
            $utc = new DateTimeZone('UTC');

            $date = new DateTime($inputTime, $ist);
            $date->setTimezone($utc);
            $scheduledDate = $date->format('Y-m-d H:i:s'); // UTC formatted string
        } catch (Exception $e) {
            $response['message'] = 'Invalid date format.';
            echo json_encode($response);
            exit;
        }
    }

    // Validate visibility
    if (!in_array($visibility, ['public', 'private'])) {
        $response['message'] = 'Invalid visibility value. It must be either "public" or "private".';
        echo json_encode($response);
        exit;
    }


    // Check for file uploads
    if (isset($_FILES['media']) && is_array($_FILES['media']['name'])) {
        // Loop through all uploaded files
        for ($i = 0; $i < count($_FILES['media']['name']); $i++) {
            $file_name = basename($_FILES['media']['name'][$i]);
            $file_tmp_name = $_FILES['media']['tmp_name'][$i];
            $file_error = $_FILES['media']['error'][$i];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            // Check for upload errors
            if ($file_error === UPLOAD_ERR_OK) {
                // Define upload directory
                $upload_dir = 'uploads/';
                $timestamp = time();
                $unique_name = 'user_' . $timestamp . '_' . $i . '.' . $file_ext;

                // Allowed file extensions
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'mp4', 'avi', 'mov'];

                if (in_array($file_ext, $allowed_extensions)) {
                    $media_url = $upload_dir . $unique_name;
                    if (move_uploaded_file($file_tmp_name, $media_url)) {
                        $media_urls[] = $media_url;
                        if (count($media_urls) > 4) {
                            $response['message'] = 'You can add up to 4 images at a time.';
                            echo json_encode($response);
                            exit;
                        }
                    } else {
                        $response['message'] = 'Error: Failed to upload file ' . $file_name;
                        echo json_encode($response);
                        exit;
                    }
                } else {
                    $response['message'] = 'Error: Invalid file type for file ' . $file_name;
                    echo json_encode($response);
                    exit;
                }
            } else {
                $uploadErrMap = [
                    UPLOAD_ERR_INI_SIZE   => 'file is larger than the server limit (upload_max_filesize)',
                    UPLOAD_ERR_FORM_SIZE  => 'file is larger than the form-specified limit',
                    UPLOAD_ERR_PARTIAL    => 'file was only partially uploaded',
                    UPLOAD_ERR_NO_FILE    => 'no file was uploaded',
                    UPLOAD_ERR_NO_TMP_DIR => 'missing temp folder on server',
                    UPLOAD_ERR_CANT_WRITE => 'failed to write file to disk',
                    UPLOAD_ERR_EXTENSION  => 'a PHP extension stopped the file upload',
                ];
                $reason = $uploadErrMap[$file_error] ?? ('error code ' . $file_error);
                $response['message'] = 'Error: Upload error with file ' . $file_name . ' — ' . $reason;
                echo json_encode($response);
                exit;
            }
        }
    } else {
        $response['message'] = 'No files uploaded.';
        // echo json_encode($response);
        // exit;
    }

    // Process meta data
    $jsonMetaData = [];
    $inputFields = [
        'hiddenTitle' => 'metaTitle',
        'hiddenDesc' => 'metaDesc',
        'hiddenUrl' => 'metaUrl',
        'hiddenImage' => 'metaImage',
        'hiddenDomain' => 'metaDomain',
        'hiddenYTLink' => 'youtubeLink'
    ];
    foreach ($inputFields as $field => $attribute) {
        if (isset($_POST[$field])) {
            $jsonMetaData[$attribute] = $_POST[$field];
        }
    }

    $jsonString = !empty($jsonMetaData) ? json_encode($jsonMetaData) : null;

    // Insert post if valid
    if ($content || !empty($media_urls)) {
        $stmt = $readerdb->prepare("
            INSERT INTO reader_stream (
                userId, chat, mediaPath, metadata, visibility, deleteFlag, scheduledDate, pincode
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $media_url = implode(',', $media_urls);
        $stmt->bind_param("isssssss", $gUserId, $content, $media_url, $jsonString, $visibility, $deleteFlag, $scheduledDate, $gUserPincode);

        if ($stmt->execute()) {
            $postId = $stmt->insert_id;

            $response['status'] = 'success';
            $response['message'] = $deleteFlag ? 'Post scheduled successfully.' : 'Post submitted successfully.';
            $response['postId'] = $postId;
            $response['data'][] = ['database' => 'Post inserted into the database.'];
        } else {
            $response['message'] = 'Failed to insert post into the database.';
            $response['error'] = $stmt->error;
        }

        $stmt->close();
    } else {
        $response['message'] = "The message can't be empty.";
    }
} else {
    $response['message'] = 'Invalid request method. Please use POST.';
}

echo json_encode($response);  // Output the response as JSON
