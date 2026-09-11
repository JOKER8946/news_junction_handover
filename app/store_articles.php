<?php



if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the selected article IDs from the POST request
    $magazine = $_POST['articles']; // Assuming the array is named 'articles'

    include 'db_connect.php'; // Your database connection file

    // Prepare the SQL statement
    $stmt = $conn->prepare("INSERT INTO magazine (user_id) VALUES (?)"); // Change 'url' to 'article_id'

    // Check if the statement was prepared successfully
    if ($stmt === false) {
        die("Error preparing statement: " . $conn->error);
    }

    // Loop through each ID and insert it into the Magazine table
    foreach ($magazine as $id) {
        $stmt->bind_param("i", $id); // "i" indicates the type is integer (assuming IDs are integers)
        $stmt->execute();        
    }


    // Close the statement and connection
    $stmt->close();
    $conn->close();

    // Return a success response
    echo json_encode(['status' => 'success']);
}
?>
