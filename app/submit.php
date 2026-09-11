<?php
include 'db.php';

$name = $_POST['name'];
$email = $_POST['email'];
$industry = $_POST['industry'];
$product = $_POST['product'];
$goals = $_POST['goals'];
$challenges = $_POST['challenges'];
$value = $_POST['value'];
$target_audience = $_POST['target_audience'];
$assets = $_POST['assets'];
$social_media = $_POST['social_media'];

$sql = "INSERT INTO users (name, email, industry, product, goals, challenges, value, target_audience, assets, social_media)
        VALUES ('$name', '$email', '$industry', '$product', '$goals', '$challenges', '$value', '$target_audience', '$assets', '$social_media')";
$conn->query($sql);
$conn->close();
header("Location: gemini.php?email=" . urlencode($email));
exit();
?>