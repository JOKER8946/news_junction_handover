<?php
// Include database connection
include('../inc/php/db_config.php');
include('../inc/php/validate.logged.php');

// Query to fetch data (example)
$sql = "SELECT id, title, description FROM user_collection where user_id = $gUserId limit 5"; // Modify this query to suit your database structure
$result = $creamdb->query($sql);

$data = array(); // Create an empty array to store fetched data

if ($result->num_rows > 0) {
    // Fetch all rows and store them in the $data array
    while($row = $result->fetch_assoc()) {
        $data[] = $row; // Add row to the data array
    }
} else {
    $data = array("message" => "No records found");
}

echo json_encode($data); // Return data as JSON

$creamdb->close(); // Close the connection
?>
