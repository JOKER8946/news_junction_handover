<?
require_once '../inc/config.php';
require_once '../inc/validate.logged.php';

// Assuming you have already established a connection to the database in $db

// Check if 'feedId' is set in the POST request
if (isset($_POST['feedId'])) {
    $feedId = (int)$_POST['feedId']; // Cast to int for safety


    // Prepare the SQL statement
    $sql = "UPDATE user_collection SET esamudaay = 1 WHERE id = ? AND user_id = ?";
    $stmt = $db->prepare($sql);

    if ($stmt) {
        // Bind the parameters
        $stmt->bind_param("ii", $feedId, $gUserId);

        // Execute the statement
        if ($stmt->execute()) {
            echo "Record updated successfully.";
        } else {
            echo "Error updating record: " . $stmt->error;
        }

        // Close the statement
        $stmt->close();
    } else {
        echo "Error preparing statement: " . $db->error;
    }
} else {
    echo "feedId and user_id must be provided.";
}

// Close the database connection if it's not needed anymore
$db->close();