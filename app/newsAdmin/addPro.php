<?php
include 'db_connect.php';
$end_date = isset($_POST['end_date']) ? $_POST['end_date'] : '';
$userId = isset($_POST['userId']) ? $_POST['userId'] : '';
$plan = 1; // Set plan to 1 for pro users

// Update user table to set plan=1 and plan_expiry
$sql = "UPDATE user SET plan=? WHERE id=?";

$stmt = $conn->prepare($sql);

// Bind parameters and execute
$stmt->bind_param("ii", $plan, $userId); // "i" for integer and "s" for string

// Execute the statement
if ($stmt->execute()) { 
    header("Location: reporters.php?success=true");
    exit(); // Ensure the script stops execution after the redirect
} else {
    // If something goes wrong with the query, you can handle it here (e.g., show an error message)
    echo "Error: " . $stmt->error;
}

$stmt->close();
