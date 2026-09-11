<?php

$servername = "localhost";
$username = "YOUR_DB_USER";
$password = "YOUR_DB_PASSWORD";
$dbname = "creamnow";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);






$name = $_POST['name'];
$email = $_POST['email'];
$industry = $_POST['industry'];
$product = $_POST['product'];
$goals = $_POST['goals'];
$challenges = $_POST['challenges'];
$value = $_POST['value'];
$conversation = $_POST['conversation'];
$assets = $_POST['assets'];
$social_media = $_POST['social_media'];

$sql = "INSERT INTO users (name, email, industry, product, goals, challenges, value, conversation, assets, social_media)
        VALUES ('$name', '$email', '$industry', '$product', '$goals', '$challenges', '$value', '$conversation', '$assets', '$social_media')";
$conn->query($sql);
$conn->close();
header("Location: gemini.php?email=" . urlencode($email));
exit();

?>