<?php
include '../php/validate.logged.php';

ini_set('display_startup_errors', 1);


require 'vendor/autoload.php'; // Include Razorpay PHP SDK
use Razorpay\Api\Api;

$api = new Api('YOUR_RAZORPAY_KEY_ID', 'YOUR_RAZORPAY_KEY_SECRET');

include '../php/db_config.php';

// Collect payment data from POST request
$razorpayOrderId = $_POST['order_id'];
$razorpayPaymentId = $_POST['payment_id'];
$razorpaySignature = $_POST['signature'];
$name = $_POST['name'] ?? null;
$email = $_POST['email'] ?? null;
$mobile = $_POST['mobile'] ?? null;
$billing_country = $_POST['billing_country'] ?? null;
$billing_street = $_POST['billing_street'] ?? null;
$billing_city = $_POST['billing_city'] ?? null;
$billing_state = $_POST['billing_state'] ?? null;
$billing_zip = $_POST['billing_zip'] ?? null;
$billing_phone = $_POST['billing_phone'] ?? null;
$payment_method = $_POST['payment_method'] ?? 'razorpay';
$amount = $_POST['amount'] ?? null;
$plan = $_POST['plan'] ?? null;

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

try {
    // Verify payment signature
    $api->utility->verifyPaymentSignature($attributes);

    $status = 'successful';
    $created_at = date('Y-m-d H:i:s'); 

    // Prepare the SQL query
    $sql = "INSERT INTO razorpay 
    (userId, order_id, payment_id, signature, status, recipient, email, mobile, billing_country, billing_street, billing_city, billing_state, billing_zip, billing_phone, payment_method, amount, plan, created_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $creamdb->prepare($sql);

    // If preparation fails, print the error
    if ($stmt === false) {
        die('MySQL prepare error: ' . $creamdb->error);
    }

    // Bind the parameters
    $stmt->bind_param(
        "isssssssssssssssss",
        $gUserId,
        $razorpayOrderId,
        $razorpayPaymentId,
        $razorpaySignature,
        $status,
        $name,
        $email,
        $mobile,
        $billing_country,
        $billing_street,
        $billing_city,
        $billing_state,
        $billing_zip,
        $billing_phone,
        $payment_method,
        $amount,
        $plan,
        $created_at
    );

    // Execute the statement
    if ($stmt->execute()) {
?>
        <script>
            alert("Payment is successful");
        </script>
<?php
    header('Location: payment_success.php');
    } else {
        echo "Payment successful but failed to capture. Error: " . $stmt->error;
    }
} catch (\Exception $e) {
    // If verification fails, handle the error
    echo "Payment verification failed: " . $e->getMessage();
}

// Close the database connection
$creamdb->close();
?>