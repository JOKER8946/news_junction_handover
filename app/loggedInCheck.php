<?
// News Junction: Check user login

session_start();
require_once 'inc/config.php';

if (!empty($_POST)) {
 $knoblyCreamUser = isset($_POST['knoblyCreamUser']) ? $_POST['knoblyCreamUser'] : '';
 if ($knoblyCreamUser != '') {
  $knoblyCreamUser = simpleEncDec($knoblyCreamUser, 'd');
  $sql = "SELECT id,full_name,email,is_activated FROM user WHERE id='$knoblyCreamUser'";
  $result = @mysqli_query($db, $sql);
  $numRows = mysqli_num_rows($result);
  if ($numRows > 0) {
   $row = mysqli_fetch_assoc($result);
   $isActivated = $row['is_activated'];
   if ($isActivated == 1) {
    $sql = "UPDATE user SET num_visits=num_visits+1 WHERE id='$knoblyCreamUser'";
    mysqli_query($db, $sql);
    $ip = getenv('HTTP_CLIENT_IP')?:getenv('HTTP_X_FORWARDED_FOR')?:getenv('HTTP_X_FORWARDED')?:getenv('HTTP_FORWARDED_FOR')?:getenv('HTTP_FORWARDED')?:getenv('REMOTE_ADDR');
    $sql = "INSERT INTO user_login(user_id,ip,date_login) VALUES(" . $row['id'] . ",'$ip',Now())";
    mysqli_query($db, $sql);
    $_SESSION['userId'] = $row['id'];
    $_SESSION['userName'] = $row['full_name'];
    $_SESSION['userEmail'] = $row['email'];
    echo 'OK';
   }
  }
 }
}