<?php

declare(strict_types=1);

/**
 * Google OAuth 2.0 helpers for buyer sign-in.
 *
 * Reads credentials (client_id, client_secret) from merchant_profile.
 * Configure them in Merchant Settings.
 */

function google_oauth_ensure_columns(PDO $db): void
{
    static $done = false;
    if ($done) return;
    $done = true;

    try {
        $row = db_fetch_one($db, 'SELECT DATABASE() AS d');
        $dbName = (string)($row['d'] ?? '');
        if ($dbName === '') return;
        foreach ([
            ['google_client_id', 'VARCHAR(255) NULL'],
            ['google_client_secret', 'VARCHAR(255) NULL'],
            ['smtp_host', "VARCHAR(150) NULL DEFAULT 'smtp.gmail.com'"],
            ['smtp_port', 'INT NULL DEFAULT 587'],
            ['smtp_username', 'VARCHAR(190) NULL'],
            ['smtp_password', 'VARCHAR(190) NULL'],
            ['smtp_from_email', 'VARCHAR(190) NULL'],
            ['smtp_from_name', 'VARCHAR(150) NULL'],
        ] as $col) {
            $exists = db_fetch_one($db, 'SELECT COUNT(*) AS c FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :t AND COLUMN_NAME = :c', [
                'db' => $dbName, 't' => 'merchant_profile', 'c' => $col[0],
            ]);
            if ((int)($exists['c'] ?? 0) === 0) {
                $db->exec("ALTER TABLE merchant_profile ADD COLUMN `{$col[0]}` {$col[1]}");
            }
        }
    } catch (Throwable $t) {
        // ignore
    }
}

function smtp_get_config(PDO $db): ?array
{
    google_oauth_ensure_columns($db);
    try {
        $row = db_fetch_one($db, "
            SELECT mp.smtp_host, mp.smtp_port, mp.smtp_username, mp.smtp_password,
                   mp.smtp_from_email, mp.smtp_from_name, mp.business_name
            FROM merchant_profile mp
            JOIN users u ON u.id = mp.merchant_user_id
            WHERE u.role = 'merchant'
            ORDER BY mp.id ASC LIMIT 1
        ");
        return _smtp_row_to_config($row);
    } catch (Throwable $t) {
        return null;
    }
}

function smtp_get_config_for_merchant(PDO $db, int $merchantId): ?array
{
    if ($merchantId <= 0) return null;
    try {
        $row = db_fetch_one($db, "
            SELECT smtp_host, smtp_port, smtp_username, smtp_password,
                   smtp_from_email, smtp_from_name, business_name
            FROM merchant_profile
            WHERE merchant_user_id = :uid LIMIT 1
        ", ['uid' => $merchantId]);
        return _smtp_row_to_config($row);
    } catch (Throwable $t) {
        return null;
    }
}

function _smtp_row_to_config($row): ?array
{
    if (!$row) return null;
    $username = trim((string)($row['smtp_username'] ?? ''));
    $password = smtp_normalize_password($row['smtp_password'] ?? '');
    if ($username === '' || $password === '') return null;
    return [
        'host'       => trim((string)($row['smtp_host'] ?? 'smtp.gmail.com')) ?: 'smtp.gmail.com',
        'port'       => (int)($row['smtp_port'] ?? 587) ?: 587,
        'username'   => $username,
        'password'   => $password,
        'from_email' => trim((string)($row['smtp_from_email'] ?? '')) ?: $username,
        'from_name'  => trim((string)($row['smtp_from_name'] ?? '')) ?: trim((string)($row['business_name'] ?? '')) ?: 'Manikya Market',
    ];
}

function app_site_url(): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = (string)($_SERVER['HTTP_HOST'] ?? 'localhost');
    return $scheme . '://' . $host;
}

function google_oauth_get_config(PDO $db): ?array
{
    google_oauth_ensure_columns($db);
    try {
        $row = db_fetch_one($db, "
            SELECT mp.google_client_id, mp.google_client_secret
            FROM merchant_profile mp
            JOIN users u ON u.id = mp.merchant_user_id
            WHERE u.role = 'merchant'
            ORDER BY mp.id ASC LIMIT 1
        ");
        if (!$row) return null;
        $clientId = trim((string)($row['google_client_id'] ?? ''));
        $clientSecret = trim((string)($row['google_client_secret'] ?? ''));
        if ($clientId === '' || $clientSecret === '') return null;
        return ['client_id' => $clientId, 'client_secret' => $clientSecret];
    } catch (Throwable $t) {
        return null;
    }
}

function google_oauth_redirect_uri(): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = (string)($_SERVER['HTTP_HOST'] ?? 'localhost');
    return $scheme . '://' . $host . '/index.php?p=buyer/google-callback';
}

function google_oauth_authorize_url(string $clientId, string $state): string
{
    $params = [
        'response_type' => 'code',
        'client_id' => $clientId,
        'redirect_uri' => google_oauth_redirect_uri(),
        'scope' => 'openid email profile',
        'state' => $state,
        'access_type' => 'online',
        'prompt' => 'select_account',
    ];
    return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
}

function google_oauth_exchange_code(string $code, string $clientId, string $clientSecret): ?array
{
    $payload = http_build_query([
        'code' => $code,
        'client_id' => $clientId,
        'client_secret' => $clientSecret,
        'redirect_uri' => google_oauth_redirect_uri(),
        'grant_type' => 'authorization_code',
    ]);

    $ch = curl_init('https://oauth2.googleapis.com/token');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
        CURLOPT_TIMEOUT => 20,
    ]);
    $resp = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code !== 200 || !$resp) return null;
    $data = json_decode((string)$resp, true);
    if (!is_array($data) || empty($data['access_token'])) return null;
    return $data;
}

function google_oauth_get_userinfo(string $accessToken): ?array
{
    $ch = curl_init('https://www.googleapis.com/oauth2/v3/userinfo');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $accessToken],
        CURLOPT_TIMEOUT => 20,
    ]);
    $resp = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code !== 200 || !$resp) return null;
    $data = json_decode((string)$resp, true);
    if (!is_array($data) || empty($data['email'])) return null;
    return $data;
}
