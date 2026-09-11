<?php
/**
 * Ad helper — DB-only, no JSON fallback.
 * Uses nj_reader.ads table exclusively.
 */

function __ad_get_db()
{
    // Return a usable mysqli connection or null
    
    // First check if already in $GLOBALS
    if (isset($GLOBALS['readerdb']) && $GLOBALS['readerdb'] instanceof mysqli) {
        if (property_exists($GLOBALS['readerdb'], 'connect_errno') && $GLOBALS['readerdb']->connect_errno == 0) {
            return $GLOBALS['readerdb'];
        }
    }

    // Include db_config.php which sets $readerdb locally
    $cfg = __DIR__ . '/db_config.php';
    if (file_exists($cfg)) {
        include_once $cfg;
        
        // After include, $readerdb is a local var, so register it as global
        if (isset($readerdb) && $readerdb instanceof mysqli) {
            if (property_exists($readerdb, 'connect_errno') && $readerdb->connect_errno == 0) {
                $GLOBALS['readerdb'] = $readerdb;
                return $readerdb;
            }
        }
    }

    return null;
}

function getActiveAds($position = 'feed', $limit = 10) {
    // DB-only: no JSON fallback
    $db = __ad_get_db();
    if (!$db) {
        error_log("AdManager: DB connection failed for position=$position");
        return [];
    }

    $pos = $db->real_escape_string($position);
    $now = date('Y-m-d H:i:s');
    $sql = "SELECT * FROM nj_reader.ads 
            WHERE is_active = 1
            AND position = '$pos'
            AND (start_date IS NULL OR start_date <= '$now')
            AND (end_date IS NULL OR end_date >= '$now')
            ORDER BY created_at DESC
            LIMIT " . intval($limit);
    $res = $db->query($sql);
    
    if (!$res) {
        error_log("AdManager SQL Error: " . $db->error . " | Query: " . $sql);
        return [];
    }
    
    $ads = $res->fetch_all(MYSQLI_ASSOC);
    if (empty($ads)) {
        error_log("AdManager: No ads found for position=$position, now=$now");
    }
    return $ads;
}

function generateAdCard($ad) {
    if (!$ad) return '';

    $title = htmlspecialchars($ad['title'] ?? 'Sponsored');
    $description = htmlspecialchars($ad['description'] ?? '');
    $image = htmlspecialchars($ad['image_url'] ?? '');
    $link = !empty($ad['ad_link']) ? htmlspecialchars($ad['ad_link']) : '#';

    $html = "<div class=\"ad-card\" style=\"position:relative; margin:10px auto; max-width:300px; border-radius:6px; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,0.1); background:#fff; transition:transform 0.2s;\">";
    
    // Ad Badge at top
    $html .= "<div style=\"position:absolute; top:8px; right:8px;  padding:4px 8px; border-radius:3px; font-size:11px; font-weight:600; z-index:10;\">AD</div>";
    
    if ($image) {
        $targetAttr = (strpos($link, 'ad_click_handler.php') !== false) ? '' : ' target="_blank" rel="noopener noreferrer"';
        $html .= "<a href=\"$link\"$targetAttr><img src=\"$image\" alt=\"$title\" style=\"width:100%;height:160px;display:block;object-fit:cover;\"></a>";
    }
    
    $html .= "<div style=\"padding:10px 12px;\">";
    $targetAttr = (strpos($link, 'ad_click_handler.php') !== false) ? '' : ' target="_blank"';
    $html .= "<h4 style=\"margin:0 0 6px 0;font-size:14px;font-weight:600;color:#b00000;\"><a href=\"$link\"$targetAttr style=\"color:inherit;text-decoration:none;\">$title</a></h4>";
    if ($description) $html .= "<p style=\"margin:0 0 8px 0;color:#666;font-size:12px;line-height:1.3;\">$description</p>";
    $html .= "<a href=\"$link\"$targetAttr style=\"display:inline-block;padding:6px 12px;background:#b00000;color:#fff;border-radius:3px;text-decoration:none;font-weight:600;font-size:12px;\">Learn More</a>";
    $html .= "</div></div>";

    return $html;
}
?>
