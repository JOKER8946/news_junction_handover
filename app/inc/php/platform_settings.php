<?php
/**
 * Lightweight key-value settings store, backed by nj_cream.platform_settings.
 *
 * Used for things any admin can change at runtime without touching code:
 *   - Google OAuth client_id / client_secret
 *   - (future: SMTP, SMS gateway, feature flags, etc.)
 *
 * The table is created lazily on first call so this works on fresh installs.
 */

function nj_platform_settings_ensure_table(): void
{
    global $creamdb;
    static $ready = false;
    if ($ready || !isset($creamdb)) return;
    try {
        $creamdb->query("CREATE TABLE IF NOT EXISTS platform_settings (
            setting_key   VARCHAR(100) NOT NULL PRIMARY KEY,
            setting_value TEXT NULL,
            updated_at    DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )");
        $ready = true;
    } catch (Throwable $t) { /* ignore */ }
}

function nj_platform_setting_get(string $key, ?string $default = null): ?string
{
    global $creamdb;
    nj_platform_settings_ensure_table();
    if (!isset($creamdb)) return $default;
    try {
        $stmt = $creamdb->prepare('SELECT setting_value FROM platform_settings WHERE setting_key = ? LIMIT 1');
        if (!$stmt) return $default;
        $stmt->bind_param('s', $key);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $stmt->close();
        if ($row && $row['setting_value'] !== null && $row['setting_value'] !== '') {
            return (string)$row['setting_value'];
        }
    } catch (Throwable $t) { /* ignore */ }
    return $default;
}

function nj_platform_setting_set(string $key, string $value): bool
{
    global $creamdb;
    nj_platform_settings_ensure_table();
    if (!isset($creamdb)) return false;
    try {
        $stmt = $creamdb->prepare(
            'INSERT INTO platform_settings (setting_key, setting_value) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
        );
        if (!$stmt) return false;
        $stmt->bind_param('ss', $key, $value);
        $ok = $stmt->execute();
        $stmt->close();
        return (bool)$ok;
    } catch (Throwable $t) { return false; }
}

/**
 * Return the active Google OAuth config. Prefers values stored in
 * platform_settings (set via /admin_platform_settings.php). Falls back to
 * the file at inc/data/google_oauth.php if the DB has no override.
 */
function nj_google_oauth_config(): array
{
    $fromFile = @include __DIR__ . '/../data/google_oauth.php';
    $fileCid = is_array($fromFile) ? (string)($fromFile['client_id']     ?? '') : '';
    $fileSec = is_array($fromFile) ? (string)($fromFile['client_secret'] ?? '') : '';

    return [
        'client_id'     => (string)nj_platform_setting_get('google_client_id',     $fileCid),
        'client_secret' => (string)nj_platform_setting_get('google_client_secret', $fileSec),
    ];
}
