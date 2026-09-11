<?
// Cream: My Account

session_start();
require_once '../inc/config.php';

$act = '';
if (!empty($_POST)) $act = isset($_POST["act"]) ? $_POST["act"] : '';


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
  $sql = "SELECT id,title,url,read_more_email FROM user_collection WHERE id=$leadCollectionId";
  $result = mysqli_query($db, $sql);
  $numRows = mysqli_num_rows($result);
  if ($numRows > 0) {
   $row = mysqli_fetch_assoc($result);
   $collectionId = $row['id'];
   $collectionTitle = $row['title'];
   $collectionLink = 'https://www.newsjunction.net' . '/view/' . $collectionId . '/' . createArticleURL($collectionTitle);
   $readEmail = $row['read_more_email'];
   if ($readEmail <> '') {
    $tmpHTML = "";
    $tmpHTML .= "<html>";
    $tmpHTML .= "<body>";
    $tmpHTML .= "<div style=\"font-family:Arial;font-size:12px;\">\r\n";
    $tmpHTML .= "Dear News Junction user,<br><br>\r\n";
    $tmpHTML .= "The following lead details have been submitted:<br><br>\r\n";
    $tmpHTML .= "URL: $collectionLink<br>\r\n";
    $tmpHTML .= "Name: $leadName<br>\r\n";
    $tmpHTML .= "Company: $leadCompany<br>\r\n";
    $tmpHTML .= "Email: $leadEmail<br>\r\n";
    $tmpHTML .= "Mobile: $leadMobile<br><br>\r\n";
    $tmpHTML .= "Warm Regards,<br>\r\n";
    $tmpHTML .= "News Junction<br>\r\n";
    $tmpHTML .= "</body>";
    $tmpHTML .= "</html>";
    sendEmail('News Junction User', $readEmail, '', 'Lead details from News Junction', $tmpHTML);
   }
  }
  echo 'OK';
 }
}
