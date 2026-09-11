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

        // Same News Junction Gmail account that inc/config.php's sendEmail uses.
        // (Previously this file pointed at YOUR_SMTP_FROM_EMAIL whose app password
        // had expired — that's why Delete Account showed "Could not send mail".)
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

        //   $mail->addBCC('chiranjeev@gmail.com');
        $mail->isHTML(true);
        $mail->Subject = $emailSubject;
        $mail->Body    = $emailBody;
        $mail->send();
        return ['status' => 'success', 'message' => 'Mail sent successfully'];
    } catch (Exception $e) {
        return ['status' => 'error', 'message' => $mail->ErrorInfo];
    }
}


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