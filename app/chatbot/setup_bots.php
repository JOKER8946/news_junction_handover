<?php
/**
 * One-time setup script to create bot user accounts in nj_cream.user
 * Run this once, then copy the output user IDs into chatbot/index.php $users array.
 */
header('Content-Type: text/html; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../inc/php/db_config.php';

$bots = [
    ['full_name' => 'Ananya',   'email' => 'ananya.bot@newsjunction.net'],
    ['full_name' => 'Soumya',   'email' => 'soumya.bot@newsjunction.net'],
    ['full_name' => 'Jenny',    'email' => 'jenny.bot@newsjunction.net'],
    ['full_name' => 'Harry',    'email' => 'harry.bot@newsjunction.net'],
    ['full_name' => 'Einstein', 'email' => 'einstein.bot@newsjunction.net'],
];

$dummyPassword = 'bot'; // Short placeholder - bot accounts don't log in

echo "<h2>=== Bot User Setup ===</h2>";

$stmt = $creamdb->prepare(
    "INSERT INTO user (full_name, email, password, is_activated, num_visits, date_created)
     VALUES (?, ?, ?, 1, 1, NOW())"
);

if (!$stmt) {
    echo "<p style='color:red;'>Prepare failed: " . htmlspecialchars($creamdb->error) . "</p>";
    exit;
}

foreach ($bots as $bot) {
    $check = $creamdb->prepare("SELECT id FROM user WHERE email = ?");
    $check->bind_param('s', $bot['email']);
    $check->execute();
    $result = $check->get_result();

    if ($row = $result->fetch_assoc()) {
        echo "<p><strong>{$bot['full_name']}</strong>: already exists with userId = <code>{$row['id']}</code></p>";
    } else {
        $stmt->bind_param('sss', $bot['full_name'], $bot['email'], $dummyPassword);
        if ($stmt->execute()) {
            $newId = $creamdb->insert_id;
            echo "<p style='color:green;'><strong>{$bot['full_name']}</strong>: created with userId = <code>{$newId}</code></p>";
        } else {
            echo "<p style='color:red;'><strong>{$bot['full_name']}</strong>: ERROR - " . htmlspecialchars($stmt->error) . "</p>";
        }
    }
    $check->close();
}

$stmt->close();

echo "<hr><p>Done. The bot userIds are auto-loaded in chatbot/index.php at runtime.</p>";
