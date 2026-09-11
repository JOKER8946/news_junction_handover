<?php
date_default_timezone_set('Asia/Kolkata');
header('Content-Type: application/json');

// Get the JSON input
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

$bot_status_file = 'bot_status.json';

if ($action === 'pause') {
    // Pause the bot
    $status_data = [
        'status' => 'paused',
        'paused_at' => date('Y-m-d H:i:s')
    ];
    file_put_contents($bot_status_file, json_encode($status_data, JSON_PRETTY_PRINT));
    
    echo json_encode([
        'success' => true,
        'message' => 'Bot paused successfully',
        'status' => 'paused'
    ]);
    
} elseif ($action === 'resume') {
    // Resume the bot
    $status_data = [
        'status' => 'running',
        'resumed_at' => date('Y-m-d H:i:s')
    ];
    file_put_contents($bot_status_file, json_encode($status_data, JSON_PRETTY_PRINT));
    
    echo json_encode([
        'success' => true,
        'message' => 'Bot resumed successfully',
        'status' => 'running'
    ]);
    
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid action'
    ]);
}
?> 