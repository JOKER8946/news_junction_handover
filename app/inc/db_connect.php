<?php
$host = 'localhost';
$username = 'YOUR_DB_USER';
$password = 'YOUR_DB_PASSWORD';
$database = 'nj_reader';

$conn = new mysqli($host, $username, $password, $database);


if($conn->connect_error){
    die("Connection failed: " . $mysqli->connect_error);
}
?>
