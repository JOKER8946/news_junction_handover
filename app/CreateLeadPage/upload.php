<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image'])) {
    $uploadDir = 'uploads/NW_images/';  // Folder to store images
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true); // Create folder if not exists
    }

    $file = $_FILES['image'];
    $fileName = time() . '_' . basename($file['name']); // Unique file name
    $filePath = $uploadDir . $fileName;

    // Check if the file is an image
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    if (in_array($file['type'], $allowedTypes)) {
        if (move_uploaded_file($file['tmp_name'], $filePath)) {
            echo $filePath; // Return file path
        } else {
            echo "Error uploading the file.";
        }
    } else {
        echo "Invalid file type.";
    }
} else {
    echo "No image uploaded.";
}
?>
