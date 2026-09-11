<?
$servername = "localhost";
$dbname = "nj_cream";
$username = "YOUR_DB_USER";
$password = "YOUR_DB_PASSWORD";

$db = new mysqli($servername, $username, $password, $dbname);
if ($db->connect_error) {
   die("Connection failed: " . $db->connect_error);
}
mysqli_query($db, "SET NAMES utf8");
mysqli_query($db, "SET time_zone = '+5:30'");

?>