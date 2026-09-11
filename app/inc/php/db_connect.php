<?php
$host = '127.0.0.1';
$username = 'YOUR_DB_USER';
$password = 'YOUR_DB_PASSWORD';
$database = 'reader';

$conn = new mysqli($host, $username, $password, $database);


if($conn->connect_error){
    die("Connection failed: " . $mysqli->connect_error);
}
$conn->set_charset('utf8mb4');
?>
