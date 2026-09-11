<?

  $servername = "localhost";
  $dbname = "cream";
  $username = "YOUR_DB_USER";
  $passwod = "Creamx@2025#";
  $conn = new mysqli($servername, $dbname, $username, $password);
  if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
echo "Connected successfully";
?>