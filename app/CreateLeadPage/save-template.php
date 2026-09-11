<?php
include "../inc/php/validate.logged.php";
include "db_gallery.php";

header('Content-Type: application/json');

try {
    // Enable error reporting for debugging

    // Define base directory for images and templates
    $baseDir = __DIR__ . '/uploads/';

    // Ensure directory exists with proper permissions
    if (!file_exists($baseDir)) {
        if (!mkdir($baseDir, 0755, true)) {
            throw new Exception("Failed to create directory: " . $baseDir);
        }
    }

    // Validate required inputs
    if (!isset($_POST['template']) || !isset($_POST['templateFileName'])) {
        throw new Exception("Required fields missing: template content or filename");
    }

    $templateContent = $_POST['template'];
    $email = $_POST['templateEmail'];
    $templateFileName = basename($_POST['templateFileName']); // Sanitize filename

    // Validate template filename
    $templateFileName = str_replace(' ', '_', $templateFileName);
    if (!preg_match('/^[\w\-\.]+$/', $templateFileName)) {
        throw new Exception("Invalid template filename");
    }

    if (!preg_match('/^[\w\-\.]+$/', $templateFileName)) {
        throw new Exception("Invalid template filename");
    }

    // Create a unique file name for each template (using timestamp or a random string)
    $uniqueTemplateFileName = time() . '-' . $templateFileName;
    $newTemplatePath = $baseDir . $uniqueTemplateFileName;
    $newTemplateUrl = 'uploads/' . $uniqueTemplateFileName;

    // Save the template file with error checking
    if (file_put_contents($newTemplatePath, $templateContent) === false) {
        throw new Exception("Failed to save template file: " . error_get_last()['message']);
    }

    $user_id = $gUserId;

    // Prepare the database insert statement
    $stmt = $conn->prepare("INSERT INTO user_landing_page (user_id, url,title,action_email) VALUES (?, ?,?,?)");

    if (!$stmt) {
        throw new Exception("Database prepare failed: " . $conn->error);
    }

    // Bind parameters and execute
    $stmt->bind_param("isss", $user_id, $uniqueTemplateFileName, $templateFileName, $email);

    if (!$stmt->execute()) {
        throw new Exception("Failed to save template to database: " . $stmt->error);
    }

    // Close the statement
    $stmt->close();

    // Return success response
    echo json_encode([
        'success' => true,
        'templatePath' => $newTemplateUrl,
        'message' => 'Template saved successfully'
    ]);
} catch (Exception $e) {
    error_log("Template save error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
