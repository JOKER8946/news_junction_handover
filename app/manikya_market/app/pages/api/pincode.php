<?php

declare(strict_types=1);

header('Content-Type: application/json');

$pin = preg_replace('/\D/', '', (string)($_GET['pin'] ?? ''));
if (strlen((string)$pin) !== 6) {
    echo json_encode(['ok' => false, 'error' => 'Pincode must be 6 digits']);
    exit;
}

$result = ['ok' => false, 'pincode' => $pin, 'city' => '', 'state' => '', 'serviceable' => null, 'error' => null];

// 1. Look up city/state via the public India pincode API.
$ch = curl_init('https://api.postalpincode.in/pincode/' . urlencode($pin));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
$resp = curl_exec($ch);
$http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http >= 200 && $http < 300 && is_string($resp)) {
    $data = json_decode($resp, true);
    $entry = is_array($data) && isset($data[0]) ? $data[0] : null;
    if ($entry && ($entry['Status'] ?? '') === 'Success' && !empty($entry['PostOffice'][0])) {
        $po = $entry['PostOffice'][0];
        $result['ok'] = true;
        $result['city'] = (string)($po['District'] ?? '');
        $result['state'] = (string)($po['State'] ?? '');
    } else {
        $result['error'] = 'Invalid pincode';
    }
} else {
    $result['error'] = 'Pincode lookup unavailable';
}

// 2. Ask Delhivery whether we deliver here (best-effort; failures don't break the response).
try {
    $config = delhivery_get_config($db);
    if ($config && !empty($config['warehouse_pincode'])) {
        $check = delhivery_check_pincode($db, $config['warehouse_pincode'], (string)$pin);
        if ($check['error'] === null) {
            $result['serviceable'] = (bool)$check['serviceable'];
        }
    }
} catch (Throwable $t) {
    // ignore — serviceability is informational
}

echo json_encode($result);
