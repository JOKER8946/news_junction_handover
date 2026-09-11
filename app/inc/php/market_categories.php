<?php
// Shared helper: fetch active Manikya Market categories using MM's own config
// (so creds are correct on both local XAMPP and live server without hardcoding).
// Declare as globals so the variables are usable when this file is required
// from inside a function (otherwise they'd land in that function's local scope).
global $mkCategories, $mkBaseUrl;
$mkCategories = [];
try {
    $mkCfg = require __DIR__ . '/../../manikya_market/app/config.php';
    $mkDb = @new mysqli(
        $mkCfg['db']['host'] ?? '127.0.0.1',
        $mkCfg['db']['user'] ?? '',
        $mkCfg['db']['pass'] ?? '',
        $mkCfg['db']['name'] ?? 'manikya_market',
        (int)($mkCfg['db']['port'] ?? 3306)
    );
    if (!$mkDb->connect_error) {
        $mkDb->set_charset($mkCfg['db']['charset'] ?? 'utf8mb4');
        $res = $mkDb->query("SELECT name, slug, image_path FROM categories WHERE is_active = 1 ORDER BY sort_order ASC, id ASC");
        if ($res) {
            while ($r = $res->fetch_assoc()) {
                $mkCategories[] = $r;
            }
        }
        $mkDb->close();
    }
} catch (Throwable $t) {
    // Fail silent — the widget/strip just renders empty.
}
$mkBaseUrl = '/manikya_market/index.php';
