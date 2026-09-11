<?php
/**
 * Record ad click (DB if available) then redirect to ad link
 */
require_once __DIR__ . '/../inc/php/ad_functions.php';

$ad_id = isset($_GET['ad_id']) ? intval($_GET['ad_id']) : 0;
if (!$ad_id) {
    header('Location: /');
    exit;
}

// attempt DB click tracking
$db = null;
if (function_exists('__ad_get_db')) {
    $db = __ad_get_db();
}

if ($db) {
    // increment clicks and insert analytics
    $user_id = isset($_SESSION['userId']) ? intval($_SESSION['userId']) : 'NULL';
    $ad_id_int = intval($ad_id);
    $db->query("INSERT INTO nj_reader.ad_analytics (ad_id, user_id, action) VALUES ($ad_id_int, $user_id, 'click')");
    $db->query("UPDATE nj_reader.ads SET clicks = clicks + 1 WHERE id = $ad_id_int");
    // get ad link
    $res = $db->query("SELECT ad_link FROM nj_reader.ads WHERE id = $ad_id_int LIMIT 1");
    if ($res && $row = $res->fetch_assoc()) {
        $link = $row['ad_link'] ?: '/';
        header('Location: ' . $link);
        exit;
    }

    // if DB is present but no ad found, stop here (do not fallback to JSON)
    header('Location: /');
    exit;
}

// fallback to JSON lookup
$ads = getActiveAds('feed', 50);
$found = null;
foreach ($ads as $a) {
    if (isset($a['id']) && intval($a['id']) === $ad_id) { $found = $a; break; }
}

if (!$found) {
    // try reading entire file
    $path = __DIR__ . '/../data/ads.json';
    if (file_exists($path)) {
        $raw = json_decode(file_get_contents($path), true);
        if (is_array($raw)) {
            foreach ($raw as $a) if (isset($a['id']) && intval($a['id']) === $ad_id) { $found = $a; break; }
        }
    }
}

if ($found) {
    $link = $found['ad_link'] ?? '/';
    header('Location: ' . $link);
    exit;
}

header('Location: /');
exit;
?>