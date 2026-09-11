<?
// News Junction: Check user login

session_start();
require_once '../inc/config.php';

if (!empty($_POST)) {
 $login = isset($_POST['email']) ? $_POST['email'] : '';
 $pwd = isset($_POST['pwd']) ? $_POST['pwd'] : '';
 if ($login != '' && $pwd != '') {
  $sql = "SELECT id,full_name,subdomain,plan,is_activated FROM user WHERE email='$login' AND password='$pwd'";
  $result = @mysqli_query($db, $sql);
  $numRows = mysqli_num_rows($result);
  if ($numRows > 0) {
   $row = mysqli_fetch_assoc($result);
   $isActivated = $row['is_activated'];
   if ($isActivated == 1) {
    $sql = "UPDATE user SET num_visits=num_visits+1 WHERE email='$login' AND password='$pwd'";
    mysqli_query($db, $sql);
    $ip = getenv('HTTP_CLIENT_IP')?:getenv('HTTP_X_FORWARDED_FOR')?:getenv('HTTP_X_FORWARDED')?:getenv('HTTP_FORWARDED_FOR')?:getenv('HTTP_FORWARDED')?:getenv('REMOTE_ADDR');
    $sql = "INSERT INTO user_login(user_id,ip,date_login) VALUES(" . $row['id'] . ",'$ip',Now())";
    mysqli_query($db, $sql);
    $_SESSION['userId'] = $row['id'];
    $_SESSION['userPlan'] = $row['plan'];
    $_SESSION['userName'] = $row['full_name'];
    $_SESSION['userEmail'] = $login;
    $_SESSION['userSubdomain'] = $row['subdomain'];
    echo 'OK|' . simpleEncDec($row['id'], 'e');
   } else {
    echo 'notActivated';
   }
  }
 }
}