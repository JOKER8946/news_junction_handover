<?php
include './inc/php/db_config.php';
include './inc/config.php';
include './inc/validate.logged.php';

header('Content-Type: application/json');

// Check if the image data was sent
if (isset($_POST['image_state'])) {
    // Get the base64 image data
    $imageData = $_POST['image_state'];

    // Remove the "data:image/png;base64," part of the string
    $imageData = str_replace('data:image/png;base64,', '', $imageData);

    // Decode the base64 string into binary data
    $imageData = base64_decode($imageData);

    // Define the path to save the image
    $imageDirectory = 'data/profilePic/'; // Make sure this directory is writable
    if (!file_exists($imageDirectory)) {
        // Create the directory if it doesn't exist
        mkdir($imageDirectory, 0777, true);
    }

    $imageName = $gUserId . '-' . time() . '.png';

    // Define the full path to save the image
    $imagePath = $imageDirectory . $imageName;

    // Fetch the old profile picture path from the database
    $stmt = $creamdb->prepare("SELECT profile_pic FROM user WHERE id = ?");
    $stmt->bind_param('i', $gUserId); // 'i' means integer
    $stmt->execute();
    $stmt->bind_result($oldImageName);
    $stmt->fetch();
    $stmt->close();

    $oldImagePath = $imageDirectory . $oldImageName;

    // If there is an old profile picture, delete it from the server
    if ($oldImagePath && file_exists($oldImagePath)) {
        unlink($oldImagePath); // Delete the old image from the server
    }

    // Save the new image to the server
    if (file_put_contents($imagePath, $imageData)) {
        // Image saved successfully

        // Prepare the SQL query to update the user's profile picture in the database
        $stmt = $creamdb->prepare("UPDATE user SET profile_pic = ? WHERE id = ?");
        $stmt->bind_param('si', $imageName, $gUserId); // 'si' means string and integer

        // Execute the query to update the database
        if ($stmt->execute()) {
            // Successfully updated the database

            // Return the success response with the new image path
            echo json_encode(['status' => 'success', 'message' => 'Profile updated successfully!', 'image_path' => $imagePath]);
        } else {
            // Error updating the database
            echo json_encode(['status' => 'error', 'message' => 'Failed to update profile in the database.']);
        }

        // Close the statement
        $stmt->close();
    } else {
        // Error saving image to the server
        echo json_encode(['status' => 'error', 'message' => 'Failed to save the image.']);
    }

    // Close the creamdb connection
    $creamdb->close();
} else {
    // If no image data was sent
    echo json_encode(['status' => 'error', 'message' => 'No image data received.']);
}
