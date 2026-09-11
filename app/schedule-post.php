<?php
// schedule-post.php
header('Content-Type: application/json');

// Get JSON data from POST request
$data = json_decode(file_get_contents('php://input'), true);

// Validate input
if (!isset($data['content']) || !isset($data['visibility']) || !isset($data['scheduledTime'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required data.']);
    exit();
}

// MySQLi database connection
$servername = "localhost";
$dbname = "reader";
$username = "YOUR_DB_USER";
$password = "YOUR_DB_PASSWORD";

$conn = new mysqli($servername, $username, $password, $dbname);

// Check for connection errors
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Convert ISO 8601 to MySQL DATETIME format (YYYY-MM-DD HH:MM:SS)
$scheduledTime = new DateTime($data['scheduledTime']);
$scheduledTimeFormatted = $scheduledTime->format('Y-m-d H:i:s'); // Convert to MySQL datetime format

// Insert the scheduled post into the database
$content = $conn->real_escape_string($data['content']);
$visibility = $conn->real_escape_string($data['visibility']);

$query = "INSERT INTO schedule_posts (content, visibility, scheduled_time) VALUES ('$content', '$visibility', '$scheduledTimeFormatted')";

if ($conn->query($query) === TRUE) {
    echo json_encode(['status' => 'success', 'message' => 'Post scheduled successfully.']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Error scheduling post: ' . $conn->error]);
}

$conn->close();
?>
