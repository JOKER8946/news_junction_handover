<?php
ini_set('display_startup_errors', 1);

$servername = "localhost";
$dbname = "nj_cream";
$username = "YOUR_DB_USER";
$password = "YOUR_DB_PASSWORD";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require dirname(__FILE__) . '/PHPMailer/Exception.php';
require dirname(__FILE__) . '/PHPMailer/PHPMailer.php';
require dirname(__FILE__) . '/PHPMailer/SMTP.php';

$db = new mysqli($servername, $username, $password, $dbname);
if ($db->connect_error) {
   die("Connection failed: " . $db->connect_error);
}
mysqli_query($db, "SET NAMES utf8");
mysqli_query($db, "SET time_zone = '+5:30'");

function simpleEncDec($string, $action = 'e')
{
   $secret_key = 'knoblyCream@2020';
   $secret_iv = 'my_simple_secret_iv';
   $output = false;
   $encrypt_method = "AES-256-CBC";
   $key = hash('sha256', $secret_key);
   $iv = substr(hash('sha256', $secret_iv), 0, 16);
   if ($action == 'e') {
      $output = base64_encode(openssl_encrypt($string, $encrypt_method, $key, 0, $iv));
   } else if ($action == 'd') {
      $output = openssl_decrypt(base64_decode($string), $encrypt_method, $key, 0, $iv);
   }
   return $output;
}

function sendEmail($toName, $toEmail, $toEmailCC, $emailSubject, $emailBody)
{
   $mail = new PHPMailer(true);
   try {
      $mail->isSMTP();

      //   $mail->setFrom('YOUR_SMTP_FROM_EMAIL_3', 'Knobly Cream');
      //   $mail->Username   = 'YOUR_AWS_SES_SMTP_USERNAME';
      //   $mail->Password   = 'YOUR_AWS_SES_SMTP_PASSWORD';
      //   $mail->Host       = 'email-smtp.ap-south-1.amazonaws.com';

      //   $mail->setFrom('YOUR_SMTP_FROM_EMAIL_2', 'Knobly Cream');
      //   $mail->Username   = 'YOUR_POSTMARK_SERVER_TOKEN';
      //   $mail->Password   = 'YOUR_POSTMARK_SERVER_TOKEN';
      //   $mail->Host       = 'smtp.postmarkapp.com';
      //   $mail->addCustomHeader('X-PM-Message-Stream', 'outbound');


      $mail->setFrom('YOUR_GMAIL_ADDRESS', 'News Junction');
      $mail->Host       = 'smtp.gmail.com';
      $mail->Username   = 'YOUR_GMAIL_ADDRESS';
      $mail->Password   = 'YOUR_GMAIL_APP_PASSWORD_2';

      $mail->Port       = 587;
      $mail->SMTPAuth   = true;
      $mail->SMTPSecure = 'tls';
      if ($toEmail != '') {
         $arrEmail = explode(',', $toEmail);
         foreach ($arrEmail as $value) {
            $mail->addAddress(trim($value));
         }
      }
      //$mail->addAddress($toEmail, $toName);
      if ($toEmailCC != '') {
         $arrCC = explode(',', $toEmailCC);
         foreach ($arrCC as $value) {
            $mail->addCC(trim($value));
         }
      }
      $mail->isHTML(true);
      $mail->Subject = $emailSubject;
      $mail->Body    = $emailBody;
      $mail->send();
   } catch (Exception $e) {
      echo 'Message could not be sent.';
      echo 'Mailer Error: ' . $mail->ErrorInfo;
   }
}



function buildNewsletter($newsId)
{
   $returnHTML = '';
   $returnMETA = '';
   $metaCoverImg = '';
   $metaTitle = '';
   $metaDesc = '';

   global $db;
   $sql = "SELECT A.*,B.company,B.news_title,B.news_logo,B.subdomain FROM user_newsletter A INNER JOIN user B ON A.user_id=B.id WHERE A.id=$newsId";
   $result = mysqli_query($db, $sql);
   $row = mysqli_fetch_assoc($result);
   $userSubdomain = $row['subdomain'];
   $companyName = $row['company'];
   $newsTitle = $row['news_title'];
   $newsLogo = $row['news_logo'];
   $newsDate = $row['date_created'];
   $newsArticles = $row['article_id'];
   $arrArticles = explode(',', $newsArticles);
   $returnHTML .= '<table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width:650px;border:1px solid #ccc;margin:30px 0;">';
   $returnHTML .= '<tr>';
   $returnHTML .= ' <td style="width:40px"></td>';
   $returnHTML .= ' <td style="padding-top:20px" align="center">';
   $returnHTML .= '  <img src="https://www.newsjunction.net/data/logos/' . $newsLogo . '" width="150" /><br>';
   $returnHTML .= '  <div style="font-size:20px;font-family:Helvetica,Arial,sans-serif;">' . $newsTitle . '</div>';
   $returnHTML .= '  <div style="font-size:13px;font-family:Helvetica,Arial,sans-serif;">' . date('M d, Y') . ' | Publisher: ' .  $companyName . '</div>';
   $returnHTML .= ' </td>';
   $returnHTML .= ' <td style="width:40px"></td>';
   $returnHTML .= '</tr>';
   $returnHTML .= '<tr>';
   $returnHTML .= ' <td></td>';
   $returnHTML .= ' <td style="padding-top:40px;padding-bottom:20px;font-family:Georgia,serif;font-size:16px;line-height:1.5em;" align="left">';
   foreach ($arrArticles as $nl) {
      if ($nl <> '') {
         $sql = "SELECT * FROM user_collection WHERE id=$nl";
         $result = mysqli_query($db, $sql);
         $numRows = mysqli_num_rows($result);
         if ($numRows > 0) {
            $row = mysqli_fetch_assoc($result);
            $artId = $row['id'];
            $artTitle = $row['title'];
            $artDesc = $row['description'];

            if ($userSubdomain <> '') {
               $artURL = 'https://www.newsjunction.net/view/' . $artId . '/' . createArticleURL($artTitle);
               $artDesc = str_replace('<img src="data/posts/', '<img src="https://www.newsjunction.net/data/posts/', $artDesc);
            } else {
               $artURL = 'https://www.newsjunction.net/view/' . $artId . '/' . createArticleURL($artTitle);
               $artDesc = str_replace('<img src="data/posts/', '<img src="https://www.newsjunction.net/data/posts/', $artDesc);
            }

            $artDesc = str_replace("\\n", "<br>", $artDesc);
            $artDesc = stripslashes($artDesc);

            $artCoverImg = $row['cover_img'];
            $artIsReadMore = $row['is_read_more'];
            $artReadMoreTxt = $row['read_more_txt'];
            if ($artReadMoreTxt == '') $artReadMoreTxt = "Read More";

            if ($metaCoverImg == '') $metaCoverImg = $artCoverImg;
            if ($metaTitle == '') $metaTitle = $artTitle;
            if ($metaDesc == '') $metaDesc = $artDesc;

            

            $returnHTML .= '  <div style="padding-bottom:40px">';
            if ($artCoverImg <> '') {
               $returnHTML .= '  <div style="padding-bottom:10px"><a href="' . $artURL . '" target="_blank"><img src="https://www.newsjunction.net/data/covers/' . $artCoverImg . '" style="max-width:650px" width="100%" /></a></div>';
            }
            $returnHTML .= '   <div style="padding-bottom:10px;font-size:14pt;"><a href="' . $artURL . '" target="_blank"><strong>' . $artTitle . '</strong></a></div>';
            $returnHTML .= '   <span class="newsletterPara" style="font-weight:400" >' . $artDesc . '</span>';
            if ($artIsReadMore <> '') {
               if ($userSubdomain <> '') {
                  $returnHTML .= '   <center><a href="https://www.newsjunction.net/more.php?id=' . $nl . '" target="_blank"><div style="display:inline-block;font-size:0.75em;margin-top:10px;padding:8px 15px;background-color:#ffc107;border-radius:5px;text-decoration:none;">' . $artReadMoreTxt . '</div></a></center>';
               } else {
                  $returnHTML .= '   <center><a href="https://www.newsjunction.net/more.php?id=' . $nl . '" target="_blank"><div style="display:inline-block;font-size:0.75em;margin-top:10px;padding:8px 15px;background-color:#ffc107;border-radius:5px;text-decoration:none;">' . $artReadMoreTxt . '</div></a></center>';
               }
            }
            $returnHTML .= '   <br clear="all">';
            $returnHTML .= '  </div>';
         }
      }
   }

   $returnHTML .= ' </td>';
   $returnHTML .= ' <td></td>';
   $returnHTML .= '</tr>';
   $returnHTML .= '<tr>';
   $returnHTML .= ' <td></td>';
   $returnHTML .= ' <td align="center">';
   $returnHTML .= '  Powered by <a href="https://www.newsjunction.net/"><img src="https://www.newsjunction.net/assets/img/logo.black.png" width="100" align="middle" style="padding-bottom:10px"></a><br><br>';
   $returnHTML .= ' </td>';
   $returnHTML .= ' <td></td>';
   $returnHTML .= '</tr>';
   $returnHTML .= '</table>';


   $collectionLink = 'https://' . $_SERVER['SERVER_NAME'] . '/newsletter.php?id=' . $newsId;
   $returnMETA .= '<meta property="og:url" content=' . $collectionLink . ' />';
   $returnMETA .= '<meta property="og:type" content="website" />';
   $returnMETA .= '<meta property="og:title" content=' . $metaTitle . ' />';
   $returnMETA .= '<meta property="og:description" content=' . htmlspecialchars($metaDesc) . ' />';
   $returnMETA .= '<meta property="og:image" content="https://' . $_SERVER['SERVER_NAME'] . '/data/covers/' . $metaCoverImg . '" />';
   $returnMETA .= '<meta property="og:image:secure-url" itemprop="image" content="https://' . $_SERVER['SERVER_NAME'] . '/data/covers/' . $metaCoverImg . '" />';

   $returnMETA .= '<meta property="twitter:url" content="' . $collectionLink . '" />';
   $returnMETA .= '<meta name="twitter:card" content="summary" />';
   $returnMETA .= '<meta name="twitter:title" content="' . $metaTitle . '" />';
   $returnMETA .= '<meta name="twitter:description" content="' . htmlspecialchars($metaDesc) . '" />';
   $returnMETA .= '<meta name="twitter:image" content="https://' . $_SERVER['SERVER_NAME'] . '/data/logos/' . $metaCoverImg . '" />';

   return ['meta_tag' => $returnMETA, 'html_data' => $returnHTML];
}
