<?
// Cream: My Account

session_start();
require_once '../inc/config.php';

$act = '';
if (!empty($_POST)) $act = isset($_POST["act"]) ? $_POST["act"] : '';


// Check if already exists on account creation
if ($act == 'chkExist') {
 $signEmail = isset($_POST['signEmail']) ? $_POST['signEmail'] : '';
 $sql = "SELECT id FROM user WHERE email='$signEmail'";
 $result = mysqli_query($db, $sql);
 $numRows = mysqli_num_rows($result);
 if ($numRows == 0) {
  echo 'OK';
 }
}


// Check if User exists
if ($act == 'chkExistUser') {
 $chkEmail = isset($_POST['chkEmail']) ? $_POST['chkEmail'] : '';
 $sql = "SELECT id FROM user WHERE email='$chkEmail'";
 $result = mysqli_query($db, $sql);
 $numRows = mysqli_num_rows($result);
 if ($numRows > 0) {
  $row = mysqli_fetch_assoc($result);
  echo $row['id'];
 }
}


// Reset Password
if ($act == 'resetPassword') {
 $editId = isset($_POST['editId']) ? $_POST['editId'] : '';
 $resetEmail = isset($_POST['email']) ? $_POST['email'] : '';
 $sql = "SELECT full_name FROM user WHERE email='$resetEmail'";
 $result = mysqli_query($db, $sql);
 $numRows = mysqli_num_rows($result);
 if ($numRows > 0) {
  $resetURL = "https://www.newsjunction.net/reset.html?token=" . simpleEncDec($resetEmail);
  $row = mysqli_fetch_assoc($result);
  $userName = $row['full_name'];
  $tmpHTML = "";
  $tmpHTML .= "<html>";
  $tmpHTML .= "<body>";
  $tmpHTML .= "<div style=\"font-family:Arial;font-size:12px;\">\r\n";
  $tmpHTML .= "Dear $userName,<br><br>\r\n";
  $tmpHTML .= "You recently requested to reset your password. Please click on the following link to reset your password:<br>\r\n";
  $tmpHTML .= "<a href=\"$resetURL\">$resetURL</a><br><br>\r\n";
  $tmpHTML .= "Please note that the above link will be active only for 30 minutes.<br><br>\r\n";
  $tmpHTML .= "Warm Regards,<br>\r\n";
  $tmpHTML .= "News Junction<br>\r\n";
  $tmpHTML .= "</body>";
  $tmpHTML .= "</html>";
  sendEmail($userName, $resetEmail, '', 'News Junction', $tmpHTML);
 }
 echo 'OK';
}


// Reset Password Confirm
if ($act == 'resetPasswordConfirm') {
 $loginToken = isset($_POST['loginToken']) ? $_POST['loginToken'] : '';
 $loginPwd = isset($_POST['loginPwd']) ? $_POST['loginPwd'] : '';
 if ($loginToken <> '' && $loginPwd <> '') {
  $loginToken = simpleEncDec($loginToken, 'd');
  $sql = "SELECT id,full_name FROM user WHERE email='$loginToken'";
  $result = mysqli_query($db, $sql);
  $numRows = mysqli_num_rows($result);
  if ($numRows > 0) {
   $row = mysqli_fetch_assoc($result);
   $userId = $row['id'];
   $userName = $row['full_name'];
   $sql = "UPDATE user SET password='$loginPwd' WHERE id=$userId";
   $result = mysqli_query($db, $sql);
   echo "Dear $userName: Your Password has been reset!<br>";
  } else {
   echo 'Password could not be reset!';
  }
 }
}


// Create Account
if ($act == 'createAccount') {
 $signFullName = isset($_POST['signFullName']) ? $_POST['signFullName'] : '';
 $signEmail = isset($_POST['signEmail']) ? $_POST['signEmail'] : '';
 $signPwd = isset($_POST['signPwd1']) ? $_POST['signPwd1'] : '';
 $signCompany = isset($_POST['signCompany']) ? $_POST['signCompany'] : '';
 $signWebsite = isset($_POST['signWebsite']) ? $_POST['signWebsite'] : '';
 $signBusinessType = isset($_POST['signBusinessType']) ? $_POST['signBusinessType'] : '';
 $captcha = isset($_POST['h-captcha-response']) ? $_POST['h-captcha-response'] : '';
 if ($captcha == '') die();
 $data = array(
  'secret' => "0x18cD9b63A86e1d4DC9Ae33a36344bFa4f68F3344",
  'response' => $captcha
 );
 $verify = curl_init();
 curl_setopt($verify, CURLOPT_URL, "https://hcaptcha.com/siteverify");
 curl_setopt($verify, CURLOPT_POST, true);
 curl_setopt($verify, CURLOPT_POSTFIELDS, http_build_query($data));
 curl_setopt($verify, CURLOPT_RETURNTRANSFER, true);
 $response = curl_exec($verify);
 $responseData = json_decode($response);
 if ($responseData->success) {
  if ($signFullName != '' && $signEmail != '' && $signPwd != '' && $signBusinessType != '') {
   $sql = "INSERT INTO user(full_name,company,email,password,website,category_id,num_visits,date_created) VALUES('$signFullName','$signCompany','$signEmail','$signPwd','$signWebsite','$signBusinessType',1,Now())";
   mysqli_query($db, $sql);
   $ip = getenv('HTTP_CLIENT_IP')?:getenv('HTTP_X_FORWARDED_FOR')?:getenv('HTTP_X_FORWARDED')?:getenv('HTTP_FORWARDED_FOR')?:getenv('HTTP_FORWARDED')?:getenv('REMOTE_ADDR');
   $userId = mysqli_insert_id($db);
   $sql = "INSERT INTO user_login(user_id,ip,date_login) VALUES($userId,'$ip',Now())";
   mysqli_query($db, $sql);
   $_SESSION['userId'] = $userId;
   $_SESSION['userName'] = $signFullName;
   $_SESSION['userEmail'] = $signEmail;
   $activateURL = "http://www.newsjunction.net/activate.php?token=" . simpleEncDec($signEmail);
   $tmpHTML = "";
   $tmpHTML .= "<html>";
   $tmpHTML .= "<body>";
   $tmpHTML .= "<div style=\"font-family:Arial;font-size:12px;\">\r\n";
   $tmpHTML .= "Dear $signFullName,<br><br>\r\n";
   $tmpHTML .= "You recently created an account on News Junction. Please click on the following link to activate your account:<br>\r\n";
   $tmpHTML .= "<a href=\"$activateURL\">$activateURL</a><br><br>\r\n";
   $tmpHTML .= "Warm Regards,<br>\r\n";
   $tmpHTML .= "News Junction<br>\r\n";
   $tmpHTML .= "</body>";
   $tmpHTML .= "</html>";
   sendEmail($signFullName, $signEmail, '', 'News Junction: Activate your Account!', $tmpHTML);
   echo 'OK';
  }
 }
}


// Get Business Type
if ($act == 'getBusinessType') {
 $returnArr = [];
 $sql = "SELECT id,category FROM category ORDER BY category";
 $result = mysqli_query($db, $sql);
 $numRows = mysqli_num_rows($result);
 if ($numRows > 0) {
  for ($i=0;$i<$numRows;$i++) {
   $row = mysqli_fetch_array($result);
   array_push($returnArr, array($row['id'], $row['category']));
  }
 }
 echo json_encode($returnArr);
}


// Show Forgot Password
if ($act == 'showForgotPassword') {
?>
<div class="popup" style="width:420px">
<div class="widget">
 <div class="card">
  <div class="card-header bg-dark"><h5 class="mb-0 text-light">Forgot Password</h5></div>
 </div>
 <div id="widget_B" style="padding:15px 25px">
  <div class="form-group">
   <label for="forgotLogin">Enter your Login</label>
   <input type="text" class="form-control" id="forgotLogin" name="forgotLogin" maxlength="100" />
   <small class="form-text text-muted">Please enter the email with which you signed up</small>
  </div>
 </div>
 <div id="widget_F" style="border-top:1px solid #ebedf2;padding:20px 10px;">
  <div class="col">
   <button type="submit" class="btn btn-primary" onclick="return chkResetPassword()">Reset Password</button>
   <div id="panelStatus" class="float-right text-sm" style="margin-top:5px" align="right"></div>
  </div>
 </div>
</div>
</div>
<?
}


// Create Lead
if ($act == 'createLead') {
 $leadName = isset($_POST['leadName']) ? $_POST['leadName'] : '';
 $leadCompany = isset($_POST['leadCompany']) ? $_POST['leadCompany'] : '';
 $leadEmail = isset($_POST['leadEmail']) ? $_POST['leadEmail'] : '';
 $leadMobile = isset($_POST['leadMobile']) ? $_POST['leadMobile'] : '';
 $leadCollectionId = isset($_POST['leadCollectionId']) ? $_POST['leadCollectionId'] : '';
 if ($leadCollectionId != '' && $leadName != '') {
  $ip = getenv('HTTP_CLIENT_IP')?:getenv('HTTP_X_FORWARDED_FOR')?:getenv('HTTP_X_FORWARDED')?:getenv('HTTP_FORWARDED_FOR')?:getenv('HTTP_FORWARDED')?:getenv('REMOTE_ADDR');
  $sql = "INSERT INTO user_collection_lead(article_id,ip,full_name,company,email,mobile,date_created) VALUES($leadCollectionId,'$ip','$leadName','$leadCompany','$leadEmail','$leadMobile',Now())";
  mysqli_query($db, $sql);
  echo 'OK';
 }
}


// Show Go Pro
if ($act == 'showGoPro') {
?>
<div class="popup" style="width:420px">
<div class="widget">
 <div class="card">
  <div class="card-header bg-dark"><h5 class="mb-0 text-light">Go Pro Today</h5></div>
 </div>
 <div style="padding:15px 25px">
  Send in your details by <a href="https://www.newsjunction.net/more.php?id=2655">clicking here</a>. We will get in touch with you and help you with Pro set ups and payment options.
 </div>
</div>
</div>
<?
}