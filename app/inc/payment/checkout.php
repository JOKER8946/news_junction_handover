<?php
header('Content-Type: application/json');
include '../php/validate.logged.php';
require 'vendor/autoload.php'; // Include Razorpay PHP SDK

use Razorpay\Api\Api;

$api = new Api('YOUR_RAZORPAY_KEY_ID', 'YOUR_RAZORPAY_KEY_SECRET');

$data = json_decode(file_get_contents("php://input"), true);
$amount = $data['amount'] ?? null;

if (!$amount) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request: Amount is required']);
    exit;
}

try {
    // Create a new order with Razorpay
    $order = $api->order->create([
        'receipt' => 'order_rcptid_11',
        'amount' => $amount, // amount in smallest currency unit (paise)
        'currency' => 'INR',
        'payment_capture' => 1 // auto capture
    ]);

    // Send the order ID and amount back as JSON
    echo json_encode(['order_id' => $order['id'], 'amount' => $amount]);
} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to create order: ' . $e->getMessage()]);
}
