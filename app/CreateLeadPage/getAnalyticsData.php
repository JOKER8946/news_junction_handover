<?php
// Include your database connection here
include "../inc/php/db_config.php";

header('Content-Type: application/json');

// Get the page ID from the AJAX request
$pageId = $_POST['id'] ?? null;

// Make sure the page ID is valid
if (!$pageId) {
    echo json_encode(['success' => false, 'message' => 'Invalid page ID']);
    exit;
}

// Query the database to fetch the analytics data for the given page ID
$sql = "SELECT name, email, mobile, company, created_at, designation,query, state, city FROM user_landing_page_leads WHERE page_id = ?";
$stmt = $creamdb->prepare($sql);
$stmt->bind_param("i", $pageId);  // Bind the page ID to the query
$stmt->execute();
$result = $stmt->get_result();

// Check if data was found
if ($result->num_rows > 0) {
    $data = [];
    // Fetch all rows
    while ($row = $result->fetch_assoc()) {
        $data[] = [
            'name' => $row['name'],
            'email' => $row['email'],
            'mobile' => $row['mobile'],
            'company' => $row['company'],
            'created_at' => $row['created_at'],
            'designation' => $row['designation'],
            'query' => $row['query'],
            'state' => $row['state'],
            'city' => $row['city']
        ];
    }
    echo json_encode(['success' => true, 'data' => $data]);
} else {
    echo json_encode(['success' => false, 'message' => 'No data found for this page']);
}

$stmt->close();
$creamdb->close();
?>
