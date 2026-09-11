<?php
session_start();

include 'db_connect.php';
include '../../vendor/autoload.php';

// Include PHPMailer for sending emails
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../../vendor/PHP_Mailer/Exception.php';
require '../../vendor/PHP_Mailer/PHPMailer.php';
require '../../vendor/PHP_Mailer/SMTP.php';


function sendEmail($toName, $toEmail, $toEmailCC, $emailSubject, $emailBody)
{
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();

        // $mail->setFrom('YOUR_SMTP_FROM_EMAIL_3', 'Knobly Cream');
        // $mail->Username   = 'YOUR_AWS_SES_SMTP_USERNAME';
        // $mail->Password   = 'YOUR_AWS_SES_SMTP_PASSWORD';
        // $mail->Host       = 'email-smtp.ap-south-1.amazonaws.com';

        // $mail->setFrom('YOUR_SMTP_FROM_EMAIL_2', 'Knobly Cream');
        // $mail->Username   = 'YOUR_POSTMARK_SERVER_TOKEN';
        // $mail->Password   = 'YOUR_POSTMARK_SERVER_TOKEN';
        // $mail->Host       = 'smtp.postmarkapp.com';
        // $mail->addCustomHeader('X-PM-Message-Stream', 'outbound');


        $mail->setFrom('YOUR_SMTP_FROM_EMAIL', 'Knobly Cream');
        $mail->Host       = 'smtp.gmail.com';
        $mail->Username   = 'YOUR_SMTP_FROM_EMAIL';
        $mail->Password   = 'YOUR_GMAIL_APP_PASSWORD';

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
        return true;
    } catch (Exception $e) {
        echo 'Message could not be sent.';
        echo 'Mailer Error: ' . $mail->ErrorInfo;
    }
}



if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sign_up'])) {
    $full_name = $_POST['full_name'];
    $email = $_POST['new_email'];
    $mobile_number = $_POST['mobile_number'];
    $password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        echo "Passwords do not match.";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $verification_code = bin2hex(random_bytes(16));

        // Check if email already exists
        $stmt = $conn->prepare("SELECT email FROM user WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            echo "Email already registered.";
        } else {
            $stmt = $conn->prepare("INSERT INTO user (full_name, email, mobile_number, password, verification_code) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param('sssss', $full_name, $email, $mobile_number, $hashed_password, $verification_code);

            if ($stmt->execute()) {
                $emailSubject = 'Email Verification';

                $emailData = json_decode(file_get_contents('../data/email_data.json'), true);
                $emailBody = "Click the link to verify your email: <a href='" . $emailData['verification_link']. "'>Verify Email</a>";



                if (sendEmail($full_name, $email, '', $emailSubject, $emailBody)) {
                    echo "User registered successfully. Please check your email for verification.";
                }
            } else {
                echo "Error: " . $stmt->error;
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sign_in'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, full_name, email, password, is_verified FROM user WHERE email = ?");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            if ($row['is_verified']) {
                $_SESSION['user_logged_in'] = true;
                $_SESSION['user_email'] = $row['email'];
                $_SESSION['user_name'] = $row['full_name'];
                $_SESSION['id'] = $row['id'];

                // Update last login timestamp
                $stmt = $conn->prepare("UPDATE user SET last_login = NOW() WHERE email = ?");
                $stmt->bind_param('s', $email);
                $stmt->execute();

                header("Location: /new_reader/dashboard.php");
                // header("Location: /dashboard.php");
                exit();
            } else {
                echo "Please verify your email address.";
            }
        } else {
            echo "Invalid email or password.";
        }
    } else {
        echo "Invalid email or password.";
    }
}
