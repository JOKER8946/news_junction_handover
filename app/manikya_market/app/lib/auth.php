<?php

declare(strict_types=1);

function auth_user_id(): ?int
{
    $id = $_SESSION['user_id'] ?? null;
    if ($id === null) {
        return null;
    }

    return (int)$id;
}

function auth_role(): ?string
{
    $role = $_SESSION['role'] ?? null;
    if (!is_string($role) || $role === '') {
        return null;
    }

    return $role;
}

function auth_require_role(string $role): void
{
    if (auth_user_id() === null || auth_role() !== $role) {
        // Redirect to the appropriate login page based on role
        switch ($role) {
            case 'merchant':
                $login = 'merchant/login';
                break;
            case 'logistics':
                $login = 'logistics/login';
                break;
            case 'super_admin':
                $login = 'super-admin/login';
                break;
            default:
                $login = 'buyer/login';
                break;
        }
        header('Location: ?p=' . $login);
        exit;
    }
}

/**
 * Returns the merchant_profile.status for the given merchant user id,
 * or null if no profile exists yet.
 */
function merchant_profile_status(PDO $db, int $userId): ?string
{
    $row = db_fetch_one($db, 'SELECT status FROM merchant_profile WHERE merchant_user_id = :uid LIMIT 1', [
        'uid' => $userId,
    ]);
    if (!$row) {
        return null;
    }
    return (string)$row['status'];
}

/**
 * Guard for actions that require an approved merchant (e.g. listing products).
 * Flashes a message and redirects to the merchant dashboard if not approved.
 */
function require_approved_merchant(PDO $db): void
{
    auth_require_role('merchant');
    $status = merchant_profile_status($db, (int)auth_user_id());
    if ($status !== 'approved') {
        flash_set('error', $status === 'disabled'
            ? 'Your merchant account has been disabled.'
            : 'Your application is pending super-admin approval. You cannot list products yet.');
        header('Location: ?p=merchant/dashboard');
        exit;
    }
}

/**
 * Status banner string to render at the top of merchant pages when the
 * merchant isn't approved. Returns null if approved.
 */
function merchant_pending_banner(PDO $db): ?string
{
    if (auth_role() !== 'merchant') return null;
    $status = merchant_profile_status($db, (int)auth_user_id());
    if ($status === 'approved') return null;
    if ($status === 'disabled') {
        return 'Your seller account has been disabled. Contact platform support to reactivate.';
    }
    return 'Your seller application is awaiting platform approval. You can view the dashboard, but creating products / shipping / receiving orders is locked until approval.';
}

/**
 * Best-effort brute-force protection on login.
 *
 * Tracks recent failed attempts per (email + ip) pair in a small table. After
 * 5 failures within 15 minutes, returns the seconds-until-allowed; the caller
 * blocks the login attempt with a clear message. Successful login clears the
 * counter.
 */
function auth_login_throttle_check(PDO $db, string $email, string $ip): int
{
    static $tablesReady = false;
    if (!$tablesReady) {
        try {
            $db->exec("CREATE TABLE IF NOT EXISTS auth_login_failures (
                id INT AUTO_INCREMENT PRIMARY KEY,
                email VARCHAR(190) NOT NULL,
                ip VARCHAR(45) NOT NULL,
                attempted_at DATETIME NOT NULL,
                INDEX idx_alf_lookup (email, ip, attempted_at)
            )");
            $tablesReady = true;
        } catch (Throwable $t) { /* table creation best-effort */ }
    }
    try {
        // Window: 15 minutes. Threshold: 5 failures.
        $row = db_fetch_one($db,
            "SELECT COUNT(*) AS c, MAX(attempted_at) AS last FROM auth_login_failures
              WHERE email = :e AND ip = :i AND attempted_at > (NOW() - INTERVAL 15 MINUTE)",
            ['e' => $email, 'i' => $ip]
        );
        $count = (int)($row['c'] ?? 0);
        if ($count >= 5) {
            // Block for the remainder of the 15-minute window.
            return 60 * 15;
        }
    } catch (Throwable $t) { /* ignore */ }
    return 0;
}

function auth_login_record_failure(PDO $db, string $email, string $ip): void
{
    try {
        db_exec($db,
            'INSERT INTO auth_login_failures (email, ip, attempted_at) VALUES (:e, :i, NOW())',
            ['e' => $email, 'i' => $ip]
        );
        // Prune old rows opportunistically (~5% chance).
        if (random_int(1, 20) === 1) {
            $db->exec("DELETE FROM auth_login_failures WHERE attempted_at < (NOW() - INTERVAL 1 DAY)");
        }
    } catch (Throwable $t) { /* ignore */ }
}

function auth_login_record_success(PDO $db, string $email, string $ip): void
{
    try {
        db_exec($db, 'DELETE FROM auth_login_failures WHERE email = :e AND ip = :i', ['e' => $email, 'i' => $ip]);
    } catch (Throwable $t) { /* ignore */ }
}

function auth_login(PDO $db, string $email, string $password, string $role): ?array
{
    $email = trim(mb_strtolower($email));
    $ip = (string)($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');

    // Throttle: if too many recent failures from this email+IP, refuse early.
    if (auth_login_throttle_check($db, $email, $ip) > 0) {
        flash_set('error', 'Too many failed login attempts. Try again in a few minutes.');
        return null;
    }

    $user = db_fetch_one($db, 'SELECT id, role, password_hash, full_name, phone FROM users WHERE email = :email AND role = :role AND status = \'active\' LIMIT 1', [
        'email' => $email,
        'role' => $role,
    ]);

    if (!$user) {
        auth_login_record_failure($db, $email, $ip);
        return null;
    }

    if (!password_verify($password, (string)$user['password_hash'])) {
        auth_login_record_failure($db, $email, $ip);
        return null;
    }

    // Regenerate the session ID on every successful auth to prevent fixation:
    // any cookie value an attacker pre-set is invalidated, and the post-auth
    // session has a fresh ID bound only to the now-authenticated user.
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }

    auth_login_record_success($db, $email, $ip);

    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['role'] = (string)$user['role'];

    return $user;
}

function auth_logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool)$params['secure'], (bool)$params['httponly']);
    }
    session_destroy();
}

/**
 * Ensure password_resets table exists (idempotent).
 */
function auth_ensure_resets_table(PDO $db): void
{
    static $checked = false;
    if ($checked) return;
    $checked = true;
    try {
        $db->exec("CREATE TABLE IF NOT EXISTS password_resets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            email VARCHAR(190) NOT NULL,
            token VARCHAR(128) NOT NULL UNIQUE,
            created_at DATETIME NOT NULL,
            expires_at DATETIME NOT NULL,
            used_at DATETIME NULL,
            INDEX idx_pwreset_user (user_id),
            INDEX idx_pwreset_email (email)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } catch (Throwable $t) {
        // ignore — caller will handle insert failure
    }
}

/**
 * Create a password reset token for a user
 */
function auth_create_reset_token(PDO $db, int $userId, string $email): ?string
{
    auth_ensure_resets_table($db);

    // Create a secure token
    $token = bin2hex(random_bytes(32));
    $expiresAt = date('Y-m-d H:i:s', time() + 3600); // 1 hour expiry

    try {
        db_exec($db, '
            INSERT INTO password_resets (user_id, email, token, created_at, expires_at)
            VALUES (:user_id, :email, :token, NOW(), :expires_at)
        ', [
            'user_id' => $userId,
            'email' => $email,
            'token' => $token,
            'expires_at' => $expiresAt,
        ]);
        return $token;
    } catch (Throwable $t) {
        error_log('auth_create_reset_token failed: ' . $t->getMessage());
        return null;
    }
}

/**
 * Verify and get reset token details
 */
function auth_verify_reset_token(PDO $db, string $token): ?array
{
    $reset = db_fetch_one($db, '
        SELECT id, user_id, email, created_at, expires_at, used_at 
        FROM password_resets 
        WHERE token = :token 
        LIMIT 1
    ', ['token' => $token]);
    
    if (!$reset) {
        return null;
    }
    
    // Check if already used
    if (!empty($reset['used_at'])) {
        return null;
    }
    
    // Check if expired
    if (strtotime((string)$reset['expires_at']) < time()) {
        return null;
    }
    
    return $reset;
}

/**
 * Reset user password using token
 */
function auth_reset_password(PDO $db, string $token, string $newPassword): bool
{
    $reset = auth_verify_reset_token($db, $token);
    if (!$reset) {
        return false;
    }
    
    try {
        $passwordHash = password_hash($newPassword, PASSWORD_BCRYPT);
        
        // Update user password
        db_exec($db, 'UPDATE users SET password_hash = :hash WHERE id = :id', [
            'hash' => $passwordHash,
            'id' => (int)$reset['user_id'],
        ]);
        
        // Mark token as used
        db_exec($db, 'UPDATE password_resets SET used_at = NOW() WHERE id = :id', [
            'id' => (int)$reset['id'],
        ]);
        
        return true;
    } catch (Throwable $t) {
        return false;
    }
}
