<?php
include '../php/validate.logged.php';

ini_set('display_startup_errors', 1);

require 'vendor/autoload.php'; // Include Razorpay PHP SDK
use Razorpay\Api\Api;

include '../php/db_config.php';

// Collect payment data from POST request
$razorpayOrderId = $_POST['order_id'];
$razorpayPaymentId = $_POST['payment_id'];
$razorpaySignature = $_POST['signature'];
$email = $_POST['email'] ?? $gUserEmail;

if (!$email || !$razorpayOrderId || !$razorpayPaymentId || !$razorpaySignature) {
    echo json_encode(['error' => 'Required fields are missing']);
    exit;
}

// Attributes to verify signature
$attributes = [
    'razorpay_order_id' => $razorpayOrderId,
    'razorpay_payment_id' => $razorpayPaymentId,
    'razorpay_signature' => $razorpaySignature
];

$paymentData = [
    'orderId' => $razorpayOrderId,
    'paymentId' => $razorpayPaymentId,
    'rzpSignature' => $razorpaySignature,
    'userEmail' => $email,
    'userName' => $_POST['name'] ?? null,
    'userMobile' =>  $_POST['mobile'] ?? null,
    'billingCountry' => $_POST['billing_country'] ?? null,
    'billingStreet' => $_POST['billing_street'] ?? null,
    'billingCity' => $_POST['billing_city'] ?? null,
    'billingState' => $_POST['billing_state'] ?? null,
    'billingZip' => $_POST['billing_zip'] ?? null,
    'billingPhone' => $_POST['billing_phone'] ?? null,
    'paymentMethod' => $_POST['payment_method'] ?? 'razorpay',
    'amount' => $_POST['amount'] ?? null,
    'plan' => $_POST['plan'] ?? null
];


$response = verify_payment($attributes, $paymentData);
if ($response['status']) {
    if (in_array($paymentData['plan'], array('Annual Subscription', 'Monthly Subscription'))) {
        $innerResponse = capture_cream_subscription($plan);
        if ($innerResponse['status']) {
?>
            <script>
                alert("Payment is successful");
            </script>
        <? header('Location: payment_success.php?payment=success');
        } else {
        ?>
            <?= $innerResponse['error_message'] ?>
        <?
        }
    } else {
        ?>
        <script>
            alert("Payment is successful");
        </script>
    <? header('Location: payment_success.php?payment=success');
    }
} else {
    ?>
    <?= $response['error_message'] ?>
<?

}
// Close the database connection
$creamdb->close();

function verify_payment($attributes, $paymentData)
{
    $response = ['status' => false];
    global $creamdb, $gUserId;
    try {
        $api = new Api('YOUR_RAZORPAY_KEY_ID', 'YOUR_RAZORPAY_KEY_SECRET');
        $api->utility->verifyPaymentSignature($attributes);

        $sql = "INSERT INTO razorpay 
    (userId, order_id, payment_id, signature, status, recipient, email, mobile, billing_country, billing_street, billing_city, billing_state, billing_zip, billing_phone, payment_method, amount, plan, created_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $creamdb->prepare($sql);

        // If preparation fails, print the error
        if ($stmt === false) {
            throw new Exception('MySQL prepare error: ' . $creamdb->error);
        }

        $payment_status = 'successful';
        $timestamp = date('Y-m-d H:i:s');

        // Bind the parameters
        $stmt->bind_param(
            "isssssssssssssssss",
            $gUserId,
            $paymentData['orderId'],
            $paymentData['paymentId'],
            $paymentData['rzpSignature'],
            $payment_status,
            $paymentData['userName'],
            $paymentData['userEmail'],
            $paymentData['userMobile'],
            $paymentData['billingCountry'],
            $paymentData['billingStreet'],
            $paymentData['billingCity'],
            $paymentData['billingState'],
            $paymentData['billingZip'],
            $paymentData['billingPhone'],
            $paymentData['paymentMethod'],
            $paymentData['amount'],
            $paymentData['plan'],
            $timestamp
        );

        // Execute the statement
        if ($stmt->execute()) {
            $response['status'] = true;
            return $response;
        } else {
            throw new Exception("Payment successful but failed to capture. Error: " . $stmt->error);
        }
    } catch (Exception $e) {

        $response['status'] = false;
        $response['error_message'] = $e->getMessage();
        return $response;
    }
}

function capture_cream_subscription($plan)
{
    global $creamdb, $gUserId;
    $response = ['status' => false];
    try {
        if ($plan == "Annual Subscription") {
            $currentTime = time();
            $expireTime = $currentTime + (60 * 60 * 24 * 365);
        } else if ($plan == "Monthly Subscription") {
            $currentTime = time();
            $expireTime = $currentTime + (60 * 60 * 24 * 30);
        } else {
            throw new Exception("Plan Error: Wrong Plan Detected. Please contact the Knobly Team YOUR_SUPPORT_EMAIL");
        }
        $currentDate = date("Y-m-d H:i:s", $currentTime); // current time in 'Y-m-d H:i:s' format
        $expireDate = date("Y-m-d H:i:s", $expireTime); // expire time in 'Y-m-d H:i:s' format

        $stmt = $creamdb->prepare("INSERT INTO cream_subscription(userId, plan, start_date, end_date) VALUES(?, ?, ?, ?)");
        $stmt->bind_param("isss", $gUserId, $plan, $currentDate, $expireDate);
        if ($stmt->execute()) {
            $_SESSION['userPlan'] = 1;
            $response['status'] = true;
        } else {
            throw new Exception("Capture Error: " . $stmt->error);
        }
        return $response;
    } catch (Exception $e) {
        $response['status'] = false;
        $response['error_message'] = $e->getMessage();
        return $response;
    }
}
?>