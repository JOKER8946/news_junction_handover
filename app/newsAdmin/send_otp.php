<?php
// sendOTP.php
header('Content-Type: application/json');
include 'mail.php';

session_start(); // Start session to store OTP for validation

// Function to send OTP via Email
function sendEmailOTP($email, $otp)
{
    // Create the HTML message
    $emailMessage = "
    <html>
    <head>
        <title>One Time Password (OTP) for Knobly Cream Admin</title>
    </head>
    <body>
        <p>Your One Time Password (OTP) for Knobly Cream Admin is:</p>
        <p><pre><strong>$otp</strong></pre></p>
        <br>
        <p>Regards,<br>Team Knobly Cream</p>
    </body>
    </html>
    ";
    $emailSubject = 'One Time Password (OTP) for Knobly Cream';

    sendEmail('Prashanth', $email, '', $emailSubject, $emailMessage);
    return "Email sent to $email with OTP: $otp";
}

$email = "YOUR_ADMIN_EMAIL";   

// Validate input
if (!$email ) {
    $response = array(
        'success' => false,
        'message' => 'Invalid input. Mobile and type are required.'
    );
    echo json_encode($response);
    exit;
}


// Generate a random 6-digit OTP
$otp = rand(1000, 9999);
$_SESSION['otp'] = $otp;

$sendResult = sendEmailOTP($email, $otp); // Assuming mobile holds email if type is email

// Prepare the response
$response = array(
    'success' => true,
    'message' => 'OTP generated and sent successfully.',
);

echo json_encode($response);
