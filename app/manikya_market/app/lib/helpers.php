<?php

declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/**
 * Mask an email so the local-part is partially hidden: jo***@example.com.
 */
function mask_email(string $email): string
{
    $email = (string)$email;
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) return $email;
    [$local, $domain] = explode('@', $email, 2);
    $len = strlen($local);
    if ($len <= 2) {
        $masked = str_repeat('*', $len);
    } else {
        $masked = substr($local, 0, 2) . str_repeat('*', max(3, $len - 2));
    }
    return $masked . '@' . $domain;
}

/**
 * Mask a phone number so only the last 4 digits show: +91 ******1234.
 */
function mask_phone(string $phone): string
{
    $digits = preg_replace('/\D+/', '', $phone);
    if ($digits === '' || strlen($digits) < 4) return $phone;
    return '••• ••• ' . substr($digits, -4);
}

function app_base_path(array $config): string
{
    $base = (string)(($config['app']['base_path'] ?? ''));
    if ($base === '') {
        return '';
    }

    return rtrim($base, '/');
}

function redirect_to(string $page): void
{
    // Prefer application base path from config when available so redirects
    // always point to the app folder (eg. /Mango_Mama/index.php?p=...)
    $base = '';
    if (isset($GLOBALS['config']) && is_array($GLOBALS['config'])) {
        $base = app_base_path($GLOBALS['config']);
    }

    // Split page and query parameters
    $pageParts = explode('?', $page, 2);
    $pagePath = $pageParts[0];
    $pageQuery = isset($pageParts[1]) ? '&' . $pageParts[1] : '';

    if ($base === '') {
        // Fallback to current script name
        $script = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
        $sep = strpos($script, '?') === false ? '?' : '&';
        $url = $script . $sep . 'p=' . rawurlencode($pagePath) . $pageQuery;
    } else {
        $url = rtrim($base, '/') . '/index.php?p=' . rawurlencode($pagePath) . $pageQuery;
    }

    header('Location: ' . $url);
    exit;
}

function request_method(): string
{
    return strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
}

function post_string(string $key): string
{
    $v = $_POST[$key] ?? '';
    return is_string($v) ? trim($v) : '';
}

function post_int(string $key): int
{
    $v = $_POST[$key] ?? null;
    if (is_numeric($v)) {
        return (int)$v;
    }
    return 0;
}

function ensure_order_column(PDO $db, string $column, string $definition = 'DATETIME NULL'): void
{
    try {
        $dbNameRow = db_fetch_one($db, 'SELECT DATABASE() AS dbname');
        $dbName = (string)($dbNameRow['dbname'] ?? '');
        if ($dbName === '') return;
        $row = db_fetch_one($db, 'SELECT COUNT(*) AS c FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :t AND COLUMN_NAME = :c', [
            'db' => $dbName,
            't' => 'orders',
            'c' => $column,
        ]);
        $exists = (int)($row['c'] ?? 0) > 0;
        if ($exists) return;
        $db->exec("ALTER TABLE `orders` ADD COLUMN `{$column}` {$definition}");
    } catch (Throwable $t) {
        // ignore failures
    }
}

function ensure_table_column(PDO $db, string $table, string $column, string $definition = 'DATETIME NULL'): void
{
    try {
        $dbNameRow = db_fetch_one($db, 'SELECT DATABASE() AS dbname');
        $dbName = (string)($dbNameRow['dbname'] ?? '');
        if ($dbName === '') return;
        $row = db_fetch_one($db, 'SELECT COUNT(*) AS c FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :t AND COLUMN_NAME = :c', [
            'db' => $dbName,
            't' => $table,
            'c' => $column,
        ]);
        $exists = (int)($row['c'] ?? 0) > 0;
        if ($exists) return;
        $db->exec("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}");
    } catch (Throwable $t) {
        // ignore failures
    }
}

function product_unit_label(?string $unit): string
{
    $u = strtolower(trim((string)$unit));
    static $labels = [
        'kg'    => 'Kg',
        'gm'    => 'Gram',
        'piece' => 'Piece',
        'dozen' => 'Dozen',
        'litre' => 'Litre',
        'bunch' => 'Bunch',
    ];
    return $labels[$u] ?? 'Kg';
}

function flash_set(string $key, string $value): void
{
    $_SESSION['_flash'][$key] = $value;
}

function flash_get(string $key): ?string
{
    $value = $_SESSION['_flash'][$key] ?? null;
    unset($_SESSION['_flash'][$key]);

    return is_string($value) ? $value : null;
}

/**
 * Return the current session's CSRF token, creating one if needed.
 * The token is stable for the life of the session so multi-tab usage works.
 */
function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return (string)$_SESSION['_csrf'];
}

/**
 * Render a hidden CSRF input. Use inside every <form method="post">.
 */
function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

/**
 * Verify the submitted CSRF token. Returns true on match.
 * Constant-time comparison to avoid timing-based token discovery.
 */
function csrf_verify(): bool
{
    $expected = $_SESSION['_csrf'] ?? '';
    $got      = $_POST['_csrf'] ?? '';
    if (!is_string($expected) || !is_string($got) || $expected === '' || $got === '') {
        return false;
    }
    return hash_equals($expected, $got);
}

/**
 * Enforce CSRF on the current request. Call at the top of any handler that
 * accepts a POST. On mismatch, set a flash error and redirect back.
 */
function csrf_enforce(string $redirectPage = 'home'): void
{
    if (request_method() !== 'POST') return;
    if (!csrf_verify()) {
        flash_set('error', 'Security token expired. Please try again.');
        redirect_to($redirectPage);
    }
}

// ─────────────────────────────────────────────────────────────────────────────
//  Product media: extra gallery images + a short intro video.
//  Storage follows the existing pattern (home_banner_images): a JSON array
//  column `gallery_images` on products, plus a `video_path` VARCHAR column.
//  These are shown ONLY on the product detail page (Amazon-style gallery).
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Make sure the products table has the gallery_images + video_path columns.
 * Idempotent and cheap (runs once per request thanks to the static guard).
 */
function product_media_ensure_columns(PDO $db): void
{
    static $done = false;
    if ($done) return;
    $done = true;
    try {
        $dbName = (string)($db->query('SELECT DATABASE()')->fetchColumn() ?: '');
        if ($dbName === '') return;
        $cols = ['gallery_images' => 'TEXT NULL', 'video_path' => 'VARCHAR(255) NULL'];
        foreach ($cols as $col => $def) {
            $row = db_fetch_one($db,
                'SELECT COUNT(*) AS c FROM INFORMATION_SCHEMA.COLUMNS
                 WHERE TABLE_SCHEMA = :d AND TABLE_NAME = "products" AND COLUMN_NAME = :c',
                ['d' => $dbName, 'c' => $col]);
            if ((int)($row['c'] ?? 0) === 0) {
                $db->exec("ALTER TABLE `products` ADD COLUMN `{$col}` {$def}");
            }
        }
    } catch (Throwable $e) { /* non-fatal */ }
}

/** Decode a gallery_images JSON column into a clean list of web paths. */
function product_gallery_list(?string $json): array
{
    if ($json === null || $json === '') return [];
    $decoded = json_decode($json, true);
    if (!is_array($decoded)) return [];
    $out = [];
    foreach ($decoded as $u) {
        if (is_string($u) && $u !== '') $out[] = $u;
    }
    return $out;
}

/**
 * Handle gallery image uploads + removals from a product form.
 *   - reads $_FILES['gallery_images'] (multiple, name="gallery_images[]")
 *   - reads $_POST['remove_gallery'][] (paths to drop)
 * Returns the new gallery list (array of web paths), capped at $max.
 */
function product_gallery_save(array $existing, string $basePath, int $max = 8): array
{
    $out = array_values($existing);

    $remove = (array)($_POST['remove_gallery'] ?? []);
    if ($remove) {
        $out = array_values(array_filter($out, fn($u) => !in_array($u, $remove, true)));
    }

    $dir = __DIR__ . '/../../uploads/products';
    if (!is_dir($dir)) { @mkdir($dir, 0777, true); }
    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'bmp', 'avif', 'heic', 'heif'];

    if (isset($_FILES['gallery_images']) && is_array($_FILES['gallery_images']['name'] ?? null)) {
        $f = $_FILES['gallery_images'];
        $n = count($f['name']);
        for ($i = 0; $i < $n; $i++) {
            if ((int)($f['error'][$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) continue;
            if (count($out) >= $max) break;
            $tmp  = (string)($f['tmp_name'][$i] ?? '');
            $orig = (string)($f['name'][$i] ?? '');
            $ext  = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
            $mime = function_exists('mime_content_type') && is_file($tmp) ? (string)@mime_content_type($tmp) : '';
            if (!in_array($ext, $allowed, true) && stripos($mime, 'image/') !== 0) continue;
            if ($ext === '' && stripos($mime, 'image/') === 0) {
                $ext = preg_replace('/[^a-z0-9]/', '', strtolower(substr($mime, 6))) ?: 'img';
            }
            $name = 'pg_' . date('YmdHis') . '_' . random_int(1000, 9999) . '.' . $ext;
            if (move_uploaded_file($tmp, $dir . '/' . $name)) {
                $out[] = $basePath . '/uploads/products/' . $name;
            }
        }
    }

    if (count($out) > $max) $out = array_slice($out, 0, $max);
    return array_values($out);
}

/**
 * Handle a product video upload (field name="video") + removal
 * ($_POST['remove_video']). Enforces $maxBytes (default 5 MB) server-side.
 * Duration (20–30 s) is validated in the browser before upload.
 * Returns [pathOrNull, errorOrNull]; keeps $existing when nothing uploaded.
 */
function product_video_save(?string $existing, string $basePath, int $maxBytes = 5242880): array
{
    $existing = ($existing !== null && $existing !== '') ? $existing : null;
    if (!empty($_POST['remove_video'])) $existing = null;

    if (empty($_FILES['video']) || (int)($_FILES['video']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return [$existing, null]; // nothing uploaded — keep existing
    }
    if ((int)$_FILES['video']['error'] !== UPLOAD_ERR_OK) {
        return [$existing, 'Video upload failed (it may exceed the server upload limit).'];
    }
    $size = (int)($_FILES['video']['size'] ?? 0);
    if ($size > $maxBytes) {
        return [$existing, 'Video is too large (' . round($size / 1048576, 1) . ' MB). Maximum is 5 MB.'];
    }
    $tmp  = (string)$_FILES['video']['tmp_name'];
    $orig = (string)$_FILES['video']['name'];
    $ext  = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
    $allowed = ['mp4', 'webm', 'mov', 'm4v', 'ogg'];
    $mime = function_exists('mime_content_type') && is_file($tmp) ? (string)@mime_content_type($tmp) : '';
    if (!in_array($ext, $allowed, true) && stripos($mime, 'video/') !== 0) {
        return [$existing, 'Please upload a video file (MP4, WebM or MOV).'];
    }
    if ($ext === '') $ext = 'mp4';
    $dir = __DIR__ . '/../../uploads/products';
    if (!is_dir($dir)) { @mkdir($dir, 0777, true); }
    $name = 'pv_' . date('YmdHis') . '_' . random_int(1000, 9999) . '.' . $ext;
    if (!move_uploaded_file($tmp, $dir . '/' . $name)) {
        return [$existing, 'Could not save the uploaded video.'];
    }
    return [$basePath . '/uploads/products/' . $name, null];
}


/**
 * Normalise an SMTP password.
 *
 * Google displays App Passwords as four spaced groups ("abcd efgh ijkl mnop")
 * but the actual secret is the 16 letters with no spaces. Pasted verbatim into
 * Platform Settings, those inner spaces make Gmail answer
 * "534-5.7.9 Application-specific password required" — the same error as using
 * the wrong password entirely, which is a miserable thing to debug.
 *
 * So: strip whitespace ONLY when the value matches Google's display format
 * exactly — four groups of four letters. Matching on "16 letters once
 * de-spaced" is NOT enough: "my real passphrase" also de-spaces to 16 letters
 * and would be silently mangled. Any other secret, including a passphrase that
 * legitimately contains spaces, is left alone apart from a trim.
 */
function smtp_normalize_password(?string $raw): string
{
    $raw = trim((string)$raw);
    if (preg_match('/^[a-z]{4}\s+[a-z]{4}\s+[a-z]{4}\s+[a-z]{4}$/i', $raw)) {
        return preg_replace('/\s+/', '', $raw);
    }
    return $raw;
}
