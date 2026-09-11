<?php

declare(strict_types=1);

header('Content-Type: application/json');

$pincode = (string)($_GET['pincode'] ?? '');

if (empty($pincode) || !preg_match('/^\d{6}$/', $pincode)) {
    echo json_encode(['serviceable' => false, 'error' => 'Invalid pincode']);
    exit;
}

delhivery_ensure_schema($db);
$config = delhivery_get_config($db);

if (!$config) {
    echo json_encode(['serviceable' => true, 'error' => 'Delhivery not configured']);
    exit;
}

$result = delhivery_check_pincode($db, $config['warehouse_pincode'], $pincode);

echo json_encode([
    'serviceable' => $result['serviceable'],
    'prepaid' => $result['prepaid'] ?? false,
    'cod' => $result['cod'] ?? false,
]);
exit;
