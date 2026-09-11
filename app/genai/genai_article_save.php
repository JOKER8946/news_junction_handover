<?php
// Start or resume the session

include '../inc/validate.logged.php';
include '../inc/config.php'; // Corrected the path

if (!isset($_POST['title']) || !isset($_POST['description'])) {
  die('Not a valid request...');
}

$db = new mysqli($servername, $username, $password, $dbname);
if ($db->connect_error) {
  die("Connection failed: " . $db->connect_error);
}
mysqli_query($db, "SET NAMES utf8");
mysqli_query($db, "SET time_zone = '+5:30'");

$user_id = $gUserId; // Assuming user_id comes from a form
$title = mysqli_real_escape_string($db, $_POST['title']);
$description = mysqli_real_escape_string($db, $_POST['description']);
$date_added = date("Y:m:d H:i:s");  // Use current date and time

// echo $user_id . "<br>";
// echo $title . "<br>";
// echo $description . "<br>";
// echo $date_added . "<br>";

// Set utf8mb4 character set for MySQL connection
mysqli_set_charset($db, "utf8mb4");

// Prepare the SQL statement with placeholders
$sql = "INSERT INTO user_collection (user_id, title, description, date_added) VALUES (?, ?, ?, ?)";

// Prepare the statement
$stmt = mysqli_prepare($db, $sql);

if (!$stmt) {
  die("Error preparing statement: " . mysqli_error($db));
}

// Bind values to the prepared statement
mysqli_stmt_bind_param($stmt, "ssss", $user_id, $title, $description, $date_added);


if (mysqli_stmt_execute($stmt)) {
  // Successful insertion
  $response = 'Article Saved Succesfully!';
} else {
  // Error inserting record
  $response = 'Error inserting Article: ' . mysqli_stmt_error($stmt);
}

print_r($response);

// Close the statement and connection
mysqli_stmt_close($stmt);
mysqli_close($db);