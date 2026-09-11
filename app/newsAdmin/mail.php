<?
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require dirname(__FILE__) . '/PHPMailer/Exception.php';
require dirname(__FILE__) . '/PHPMailer/PHPMailer.php';
require dirname(__FILE__) . '/PHPMailer/SMTP.php';


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


      $mail->setFrom('YOUR_SMTP_FROM_EMAIL', 'News Junction');
      $mail->Host       = 'smtp.gmail.com';
      $mail->Username   = 'YOUR_SMTP_FROM_EMAIL';
      $mail->Password   = 'YOUR_GMAIL_APP_PASSWORD';

      $mail->Port       = 587;
      $mail->SMTPAuth   = true;
      $mail->SMTPSecure = 'tls';
    //   if ($toEmail != '') {
    //      $arrEmail = explode(',', $toEmail);
    //      foreach ($arrEmail as $value) {
    //         $mail->addAddress(trim($value));
    //      }
    //   }
      $mail->addAddress($toEmail, $toName);
      if ($toEmailCC != '') {
         $arrCC = explode(',', $toEmailCC);
         foreach ($arrCC as $value) {
            $mail->addCC(trim($value));
         }
      }
    //   $mail->addBCC('chiranjeev@gmail.com');
      $mail->isHTML(true);
      $mail->Subject = $emailSubject;
      $mail->Body    = $emailBody;
      $mail->send();
   } catch (Exception $e) {
      echo 'Message could not be sent.';
      echo 'Mailer Error: ' . $mail->ErrorInfo;
   }
}


?>