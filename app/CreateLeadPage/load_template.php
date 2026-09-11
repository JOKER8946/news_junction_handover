<?php
// Database connection settings
include 'db_gallery.php';

// Ensure that the 'category' parameter is passed
if (isset($_GET['category'])) {
    $category = $_GET['category'];

    // Prepare SQL query to fetch the template path, image path, and template name based on the category
    $stmt = $conn->prepare("SELECT template_name, image_path, template_path FROM leadPageTemplates WHERE category = ?");
    $stmt->bind_param("s", $category); 
    $stmt->execute();

    // Get the result
    $stmt->store_result();

    // Check if templates exist for the specified category
    if ($stmt->num_rows > 0) {
        // Bind the result to variables
        $stmt->bind_result($templateName, $imagePath, $templatePath);

        // Start building the response
        $response = '';

        // Fetch and display all matching templates
        while ($stmt->fetch()) {
            // Check if the image path is a valid URL
            if (filter_var($imagePath, FILTER_VALIDATE_URL)) {
                // Build the HTML for each template dynamically
                $response .= '
                    <div class="template" data-template="' . htmlspecialchars($templateName, ENT_QUOTES, 'UTF-8') . '" data-template-path="' . htmlspecialchars($templatePath, ENT_QUOTES, 'UTF-8') . '" data-category="' . htmlspecialchars($category, ENT_QUOTES, 'UTF-8') . '">
                        <img src="' . htmlspecialchars($imagePath, ENT_QUOTES, 'UTF-8') . '" alt="' . htmlspecialchars($templateName, ENT_QUOTES, 'UTF-8') . '">
                        <span>' . htmlspecialchars($templateName, ENT_QUOTES, 'UTF-8') . '</span>
                    </div>
                ';
            } else {
                // Error if the image path is invalid
                $response .= '<p>Error: Invalid image path for template ' . htmlspecialchars($templateName, ENT_QUOTES, 'UTF-8') . '</p>';
            }
        }

        echo $response;
    } else {
        echo '<p>No templates found for this category.</p>';
    }

    $stmt->close();
} else {
    // Return an error if the 'category' parameter is not provided
    echo '<p>Error: No category specified.</p>';
}

// Close the database connection
$conn->close();
