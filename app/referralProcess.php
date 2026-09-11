<?php
require_once 'assets/php/db_config.php';

// Get input JSON
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['newUserId']) || !isset($input['inviteCode'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

$newUserId = $input['newUserId'];
$inviteCode = $input['inviteCode'];

// 1. Check if referral code exists
$stmt = $creamdb->prepare('SELECT id FROM referrer_info WHERE referralCode = ?');
$stmt->bind_param('s', $inviteCode);
$stmt->execute();
$result = $stmt->get_result();
$referrer = $result->fetch_assoc();

if (!$referrer) {
    echo json_encode(['success' => false, 'message' => 'Invalid referral code']);
    exit;
}

$referrerId = $referrer['id'];
$stmt->close();

// 2. Insert referral relationship
$stmt = $creamdb->prepare('INSERT INTO referral_info (referrer_id, referred_id) VALUES (?, ?)');
$stmt->bind_param('ii', $referrerId, $newUserId);
$stmt->execute();
$stmt->close();

echo json_encode(['success' => true, 'message' => 'Referral processed successfully']);
?>
