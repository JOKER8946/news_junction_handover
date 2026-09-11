<?php
// Get template parameter from the request
$getUrl = isset($_GET['template']) ? $_GET['template'] : null;

// Set the correct path to the template
$templateUrl = $_SERVER["DOCUMENT_ROOT"] . "/Xpress/NLEditor/uploads/" . $getUrl;

// Check if a template was provided
if ($templateUrl) {
    // Check if the template file exists before including it
    if (file_exists($templateUrl)) {
        // Include the template (this will output the content of the template)
        include($templateUrl);
    } else {
        echo "Template file not found: " . htmlspecialchars($templateUrl);
    }
} else {
    echo "No template provided.";
}
