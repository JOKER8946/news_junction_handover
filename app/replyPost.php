<?php
include './inc/validate.logged.php';
require_once './inc/php/db_config.php';
// Initialize message variable
$message = '';

// Handle form submission (POST method)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the reference post ID and content from the form
    $refPostId = isset($_POST['refPostId']) ? $_POST['refPostId'] : '';
    $content = isset($_POST['content']) ? trim($_POST['content']) : '';
    $media_url = null;  // Default to null if no media is uploaded

    // Handle file upload (if media is selected)
    if (isset($_FILES['media']) && $_FILES['media']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/';  // Ensure this directory is writable
        $file_name = basename($_FILES['media']['name']);
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        // Get a unique file name (userId + timestamp)
        $timestamp = time();
        $unique_name = $gUserId . '_' . $timestamp . '.' . $file_ext;

        // Validate file type (extend as needed)
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'mp4', 'avi', 'mov'];
        if (in_array($file_ext, $allowed_extensions)) {
            $media_url = $upload_dir . $unique_name;

            // Ensure the upload directory exists and is writable
            if (!is_dir($upload_dir)) {
                $message = 'Error: Upload directory does not exist.';
            } elseif (!is_writable($upload_dir)) {
                $message = 'Error: Upload directory is not writable.';
            } else {
                // Attempt to move the uploaded file
                if (move_uploaded_file($_FILES['media']['tmp_name'], $media_url)) {
                    $message = 'File uploaded successfully.';
                } else {
                    // Handle failure to upload file
                    $message = 'Error: Failed to upload the file.';
                    $media_url = null;  // Don't insert media if upload failed
                }
            }
        } else {
            // Invalid file type
            $message = 'Error: Invalid file type. Only images and videos are allowed.';
            $media_url = null;  // Don't insert media if file type is invalid
        }
    } elseif (isset($_FILES['media']) && $_FILES['media']['error'] !== UPLOAD_ERR_NO_FILE) {
        // Handle other upload errors if media is provided but upload fails
        $upload_error = $_FILES['media']['error'];
        switch ($upload_error) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $message = 'Error: File is too large.';
                break;
            case UPLOAD_ERR_PARTIAL:
                $message = 'Error: File upload was only partially completed.';
                break;
            default:
                $message = 'Error: Unknown upload error occurred.';
        }
    }

    // Check if content is not empty and insert the post into the database
    if (!empty($content)) {
        // Prepare SQL statement for inserting the post (with or without media URL)
        $stmt = $readerdb->prepare("INSERT INTO reader_stream (referenceId, userId, chat, mediaPath) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiss", $refPostId, $gUserId, $content, $media_url);

        // Execute the statement
        if ($stmt->execute()) {
            if (empty($message)) {
                // If no upload issues, indicate success
                $message = 'Posted successfully...';
            }
        } else {
            // Handle database insertion errors
            $message = 'Error: ' . $stmt->error;
        }

        $stmt->close();
    } else {
        $message = 'Error: Content cannot be empty.';
    }

    unset($_POST);  // Reset POST data

    // Use JavaScript to alert the message and then redirect the user
    echo "<script>alert('$message'); window.location.href = 'post-details.php?id=$refPostId';</script>";
}

?>
