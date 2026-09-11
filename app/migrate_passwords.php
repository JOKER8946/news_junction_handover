<?php
/**
 * ONE-TIME MIGRATION: convert plaintext passwords in nj_cream.user to bcrypt.
 *
 * WHY
 *   The `user` table historically stored passwords as plaintext and login
 *   compared them directly in SQL. This script re-hashes every legacy row with
 *   password_hash(). Nobody has to change their password: the login code
 *   (process/logInCheck.php + inc/php/nj_password.php) verifies with
 *   password_verify(), so the same password keeps working.
 *
 * HOW TO RUN  (command line only - it refuses to run over the web)
 *
 *     php migrate_passwords.php --dry-run     # show what would change
 *     php migrate_passwords.php --commit      # actually write
 *
 * SAFETY
 *   - Refuses to run via a web request.
 *   - Dry-run by default; --commit is required to write anything.
 *   - Takes a backup of id+password into user_password_backup_<date> first.
 *   - Skips rows that are already hashed, so it is safe to run twice.
 *
 * AFTER RUNNING
 *   Verify a couple of logins, then DELETE this file. It has no purpose
 *   afterwards and the backup table holds plaintext.
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die("This migration may only be run from the command line.\n");
}

require_once __DIR__ . '/inc/php/nj_password.php';

$opts   = getopt('', ['dry-run', 'commit']);
$commit = isset($opts['commit']);

if (!$commit && !isset($opts['dry-run'])) {
    die("Refusing to guess. Pass --dry-run or --commit.\n");
}

// ---- connect -------------------------------------------------------------
$envFile = __DIR__ . '/inc/data/.env';
$host = 'localhost'; $port = 3306; $user = null; $pass = null;

if (is_readable($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (strpos($line, '#') === 0 || strpos($line, '=') === false) continue;
        list($k, $v) = explode('=', $line, 2);
        $k = trim($k); $v = trim($v);
        if ($k === 'DB_HOST')     $host = $v;
        if ($k === 'DB_PORT')     $port = (int) $v;
        if ($k === 'DB_USERNAME') $user = $v;
        if ($k === 'DB_PASSWORD') $pass = $v;
    }
}
if ($user === null) {
    die("Could not read DB credentials from $envFile\n");
}

$db = new mysqli($host, $user, $pass, 'nj_cream', $port ?: 3306);
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error . "\n");
}
$db->set_charset('utf8mb4');

echo $commit ? "MODE: COMMIT (will write)\n" : "MODE: DRY RUN (no writes)\n";
echo "DB:   $user@$host/nj_cream\n\n";

// ---- survey --------------------------------------------------------------
$rows = [];
$res = $db->query("SELECT id, email, password FROM `user`");
while ($r = $res->fetch_assoc()) { $rows[] = $r; }
$res->free();

$legacy = $already = $empty = 0;
foreach ($rows as $r) {
    $p = (string) $r['password'];
    if ($p === '')                        { $empty++;   continue; }
    if (nj_password_is_hashed($p))        { $already++; continue; }
    $legacy++;
}

printf("total rows       : %d\n", count($rows));
printf("already bcrypt   : %d  (skipped)\n", $already);
printf("empty / NULL     : %d  (skipped)\n", $empty);
printf("legacy plaintext : %d  <- to migrate\n\n", $legacy);

if ($legacy === 0) {
    echo "Nothing to do.\n";
    exit(0);
}

if (!$commit) {
    echo "Dry run only. Re-run with --commit to apply.\n";
    exit(0);
}

// ---- widen the column ----------------------------------------------------
// nj_cream.user.password is historically varchar(20). bcrypt output is 60
// chars. Without STRICT sql_mode the database truncates silently, producing a
// hash that can never verify and locking the user out. Widen before hashing.
$res = $db->query("
    SELECT character_maximum_length AS len, is_nullable
    FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'user' AND column_name = 'password'");
$col = $res->fetch_assoc();
$curLen = (int) $col['len'];
$nullable = ($col['is_nullable'] === 'YES') ? 'NULL' : 'NOT NULL';

printf("user.password is varchar(%d)\n", $curLen);

if ($curLen < 255) {
    echo "widening to varchar(255)... ";
    if (!$db->query("ALTER TABLE `user` MODIFY `password` VARCHAR(255) $nullable")) {
        die("FAILED: " . $db->error . "\nAborting - hashing into a narrow column would lock users out.\n");
    }
    echo "done\n";
} else {
    echo "already wide enough\n";
}
echo "\n";

// ---- backup --------------------------------------------------------------
$bak = 'user_password_backup_' . date('Ymd_His');
if (!$db->query("CREATE TABLE `$bak` AS SELECT id, email, password FROM `user`")) {
    die("Backup table failed, aborting: " . $db->error . "\n");
}
echo "backup table created: $bak\n";

// ---- migrate -------------------------------------------------------------
$stmt = $db->prepare("UPDATE `user` SET password=? WHERE id=?");
if (!$stmt) { die("prepare failed: " . $db->error . "\n"); }

$done = 0; $failed = 0;
foreach ($rows as $r) {
    $p = (string) $r['password'];
    if ($p === '' || nj_password_is_hashed($p)) continue;

    $hash = nj_password_hash($p);

    // sanity: the new hash must verify against the original plaintext
    if (!password_verify($p, $hash)) {
        echo "  !! hash self-check FAILED for id={$r['id']}, skipping\n";
        $failed++;
        continue;
    }

    $stmt->bind_param('si', $hash, $r['id']);
    if ($stmt->execute() && $stmt->affected_rows >= 0) {
        $done++;
    } else {
        echo "  !! update failed for id={$r['id']}: " . $stmt->error . "\n";
        $failed++;
    }
}
$stmt->close();

printf("\nmigrated: %d   failed: %d\n", $done, $failed);

// ---- verify --------------------------------------------------------------
$res = $db->query("SELECT COUNT(*) AS c FROM `user` WHERE password <> '' AND password NOT LIKE '$2y$%'");
$left = (int) $res->fetch_assoc()['c'];
printf("non-bcrypt rows remaining: %d\n", $left);

echo $left === 0
    ? "\nOK - every password is now hashed.\n"
    : "\nWARNING - some rows are still not hashed. Investigate before shipping.\n";

echo "\nRollback if needed:\n";
echo "  UPDATE `user` u JOIN `$bak` b ON b.id=u.id SET u.password=b.password;\n";
echo "\nWhen you are satisfied, DROP TABLE `$bak`; and delete this script.\n";
