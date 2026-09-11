<?php
// Bridge: news_junction admin -> Manikya Market super-admin.
//
// Flow:
//   1. Require the visitor to be a logged-in news_junction admin ($gIsAdmin=true).
//   2. Look up (or auto-create) the matching row in manikya_market.users keyed
//      by the news_junction admin's email. Force role='super_admin'.
//   3. Start the manikya_market PHP session and set the keys MM's auth expects
//      ($_SESSION['user_id'], $_SESSION['role']).
//   4. Redirect to the MM super-admin dashboard.

require_once __DIR__ . '/inc/php/db_config.php';
require_once __DIR__ . '/inc/php/validate.logged.php';

// validate.logged.php already redirects to sign-in if not authenticated and
// sets $gIsAdmin via the admin override block.
if (empty($gIsAdmin)) {
    header('Location: /stream.php');
    exit;
}

$nj = $creamdb->prepare(
    "SELECT id, full_name, email FROM nj_cream.user WHERE id = ? LIMIT 1"
);
$nj->bind_param("i", $gUserId);
$nj->execute();
$njUser = $nj->get_result()->fetch_assoc();
$nj->close();

if (!$njUser || empty($njUser['email'])) {
    http_response_code(403);
    echo "Cannot bridge to Manikya Market — your news_junction account has no email on file.";
    exit;
}

$adminEmail = (string)$njUser['email'];
$adminName  = trim((string)$njUser['full_name']) ?: 'Admin';

// Connect to the manikya_market database directly. The MM app uses its own
// host/user/pass that's specific to its config — re-use the same MySQL
// instance and the same db creds we already use for news_junction.
$mmDb = new mysqli('127.0.0.1', 'YOUR_DB_USER', 'YOUR_DB_PASSWORD', 'manikya_market');
if ($mmDb->connect_error) {
    http_response_code(500);
    echo "Cannot connect to Manikya Market database.";
    exit;
}
$mmDb->set_charset('utf8mb4');

// 1. Find an existing MM user for this email.
$stmt = $mmDb->prepare("SELECT id, role FROM users WHERE email = ? LIMIT 1");
$stmt->bind_param("s", $adminEmail);
$stmt->execute();
$mmUser = $stmt->get_result()->fetch_assoc();
$stmt->close();

$mmUserId = $mmUser['id'] ?? null;

// 2a. Auto-create if missing.
if (!$mmUserId) {
    // password_hash is NOT NULL on the users table; insert a random hash that
    // can never match a real password — they never log in directly here.
    $randomHash = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
    $role       = 'super_admin';
    $status     = 'active';
    $createdAt  = gmdate('Y-m-d H:i:s');

    $stmt = $mmDb->prepare(
        "INSERT INTO users (role, full_name, phone, email, password_hash, status, created_at)
         VALUES (?, ?, NULL, ?, ?, ?, ?)"
    );
    $stmt->bind_param("ssssss", $role, $adminName, $adminEmail, $randomHash, $status, $createdAt);
    $stmt->execute();
    $mmUserId = $stmt->insert_id;
    $stmt->close();
}
// 2b. If they exist but with a non-admin role, force them to super_admin so
//     the bridge keeps working consistently.
elseif (($mmUser['role'] ?? '') !== 'super_admin') {
    $stmt = $mmDb->prepare("UPDATE users SET role = 'super_admin' WHERE id = ?");
    $stmt->bind_param("i", $mmUserId);
    $stmt->execute();
    $stmt->close();
}

$mmDb->close();

// 3. Start the MM session and set the keys MM's auth library reads.
//    MM uses the default PHP session name (PHPSESSID); we set it before
//    session_start to make sure we land in a fresh, clean MM session for
//    this admin.
if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

session_id(bin2hex(random_bytes(16)));
session_start();
$_SESSION = [];
$_SESSION['user_id'] = (int)$mmUserId;
$_SESSION['role']    = 'super_admin';
session_write_close();

// 4. Off you go.
header('Location: /manikya_market/index.php?p=super-admin/dashboard');
exit;
