<?
// Cream: Logout

require_once '../inc/validate.logged.php';
require_once '../inc/config.php';

$sql = "SELECT MAX(id) AS recordId FROM user_login WHERE user_id=$gUserId";
$result = mysqli_query($db, $sql);
$row = mysqli_fetch_assoc($result);
$recordId = $row['recordId'];
$sql = "UPDATE user_login SET date_logout=Now() WHERE id=$recordId";
mysqli_query($db, $sql);

session_destroy();
header('Location: ../');