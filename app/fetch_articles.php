<?php
include 'db_connect.php';

// Get the input data
$publishers = json_decode($_POST['publishers'], true);
$topics = json_decode($_POST['topics'], true);

// Initialize the feeds array
$feeds = [];

// Base SQL query
$sql = "
SELECT rfa.id, rfa.url, rfa.title, rfa.description, rfa.image, rfa.date 
FROM rss_feeds_articles rfa 
INNER JOIN rss_feeds_url rfu ON rfa.feed_id = rfu.rss_id 
WHERE 1=1"; // Always true, allows easy appending of conditions

$params = [];
$paramTypes = '';

// Add conditions for publishers if provided
if (!empty($publishers)) {
    $publisherPlaceholders = rtrim(str_repeat('?,', count($publishers)), ',');
    $sql .= " AND rfu.rss_publisher IN ($publisherPlaceholders)";
    $params = array_merge($params, $publishers);
    $paramTypes .= str_repeat('s', count($publishers));
}

// Add conditions for topics if provided
if (!empty($topics)) {
    $topicsPlaceholders = rtrim(str_repeat('?,', count($topics)), ',');
    $sql .= " AND rfu.rss_category IN ($topicsPlaceholders)";
    $params = array_merge($params, $topics);
    $paramTypes .= str_repeat('s', count($topics));
}

// Add ordering and limit
$sql .= " ORDER BY rfa.date DESC LIMIT 10";

// Prepare the statement
$stmt = $conn->prepare($sql);

// Bind parameters only if there are any
if ($params) {
    $stmt->bind_param($paramTypes, ...$params);
}

// Execute the query
$stmt->execute();
$result = $stmt->get_result();

// Fetch results
while ($row = $result->fetch_assoc()) {
    $feeds[] = [
        'id' => $row['id'],
        'url' => $row['url'],
        'title' => $row['title'],
        'description' => $row['description'],
        'image' => $row['image'],
        'date' => $row['date']
    ];
}   


// Return the feeds as a JSON response
header('Content-Type: application/json');
echo json_encode($feeds);