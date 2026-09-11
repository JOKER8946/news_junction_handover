<!doctype html>
<?php

error_reporting(E_ALL & ~E_WARNING);
ini_set('display_errors', '0');  // Hide errors from the browser

include 'inc/config.php';
include_once 'inc/php/ad_functions.php';
// include 'conversations/functions.php';

$chat = [];

function createArticleURL($title)
{
  if ($title <> '') {
    $title = str_replace(' ', '-', $title);
    $title = str_replace('%', '', $title);
    $title = str_replace("'", "", $title);
    return $title;
  } else {
    return '';
  }
}


$id = isset($_GET["id"]) ? $_GET["id"] : '';
if ($id <> '') {
  $sql = "SELECT * FROM user_collection WHERE id=$id";
  $result = mysqli_query($db, $sql);
  $row = $result->fetch_assoc();
  $numRows = mysqli_num_rows($result);
  if ($numRows == 0) die();

  // $serverName = "newsjunction.net";
  $serverName = $_SERVER['SERVER_NAME'];
  $collectionUserId = $row['user_id'];
  $sql = "SELECT subdomain,is_side_panel,side_panel_content FROM user WHERE id=$collectionUserId";
  $resultInner = mysqli_query($db, $sql);
  $rowInner = mysqli_fetch_assoc($resultInner);
  $userSubdomain = $rowInner['subdomain'];
  $userSidePanel = $rowInner['is_side_panel'];
  $userSidePanelContent = $rowInner['side_panel_content'];
  $userSidePanelContent = str_replace('<img src="data/', '<img src="https://' . $serverName . '/data/', $userSidePanelContent);



  // $chat = fetch_messages($conn1, $id);
  // $chat = fetch_messages($db, $id);



  $ip = getenv('HTTP_CLIENT_IP') ?: getenv('HTTP_X_FORWARDED_FOR') ?: getenv('HTTP_X_FORWARDED') ?: getenv('HTTP_FORWARDED_FOR') ?: getenv('HTTP_FORWARDED') ?: getenv('REMOTE_ADDR');
  $response = unserialize(file_get_contents('http://www.geoplugin.net/php.gp?ip=' . $ip));

  if ($response === false) {
    $visitCity = '';
    $visitCountry = '';
  } else {
    $visitCity = $response['geoplugin_city'];
    $visitCountry = $response['geoplugin_countryName'];
  }
  $sql = "INSERT INTO metrics(article_id,ip,visit_city,visit_country,date_visited,category) VALUES($id,'$ip','$visitCity','$visitCountry',Now(), 'Anonymous')";
  mysqli_query($db, $sql);

  $collectionURL = $row['url'];
  if ($collectionURL <> '') {
    header("Location: " . $collectionURL);
    die();
  }

  $collectionTitle = $row['title'];
  $collectionDesc = $row['description'];

  $collectionDesc = preg_replace('/<img[^>]+src="data\/([^"]+)"/', '<img src="https://' . $serverName . '/data/$1"', $collectionDesc);
  // $collectionDesc = str_replace('<img src="data/', '<img src="https://' . $serverName . '/data/', $collectionDesc);
  $collectionDesc = str_replace('\n', '<br>', $collectionDesc);
  $collectionDesc = str_replace('\\', '', $collectionDesc);
  $collectionShareUserId = $row['share_user_id'];
  $collectionPublisher = substr($collectionURL, strpos($collectionURL, ".") + 1);
  $collectionPublisher = ucfirst(strtok($collectionPublisher, '.'));
  if ($collectionPublisher == '') $collectionPublisher = 'Cream';
  $collectionDate = date('d M, Y', strtotime($row['date_added']));
  $collectionLink = 'https://' . $serverName . '/view/' . $id . '/' . createArticleURL($collectionTitle);
  $collectionImgCover = $row['cover_img'];
  $collectionIsReadMore = $row['is_read_more'];
  $collectionReadMoreTxt = $row['read_more_txt'];
  if ($collectionReadMoreTxt == '') $collectionReadMoreTxt = "Read More";

  if ($collectionShareUserId <> '') {
    $sql = "SELECT full_name,company,website,news_title,news_logo FROM user WHERE id=$collectionShareUserId";
  } else {
    $sql = "SELECT full_name,company,website,news_title,news_logo FROM user WHERE id=$collectionUserId";
  }
  $result = mysqli_query($db, $sql);
  $row = mysqli_fetch_assoc($result);
  $user = $row['full_name'];
  $companyName = $row['company'];
  $website = strtolower($row['website']);
  $newsTitle = $row['news_title'];
  $newsLogo = $row['news_logo'];
  if (strpos($website, 'http') === false) $website = "http://$website";

  // Fetch active ads for the view page
  $feedAds = getActiveAds('feed', 20);
  $topAds = getActiveAds('top', 5);
  $verticalAds = getActiveAds('vertical', 10);
  $horizontalAds = getActiveAds('horizontal', 10);

  // Filter by page: only show ads with page='all' or page='view'
  $pageFilter = function($ad) {
    return empty($ad['page']) || $ad['page'] === 'all' || $ad['page'] === 'view';
  };

  $viewAds = array_values(array_filter(array_merge($feedAds, $topAds), $pageFilter));
  $viewVerticalAds = array_values(array_filter($verticalAds, $pageFilter));
  $viewHorizontalAds = array_values(array_filter($horizontalAds, $pageFilter));

  // Fetch sticky bottom video ads
  $stickyVideoAds = getActiveVideoAds('sticky_bottom', 5);
  $stickyVideoAdsView = array_values(array_filter($stickyVideoAds, $pageFilter));

  // Build JSON for JavaScript
  $adMapper = function($ad) {
    return [
      'id' => intval($ad['id']),
      'title' => $ad['title'] ?? '',
      'description' => $ad['description'] ?? '',
      'image_url' => $ad['image_url'] ?? '',
      'ad_link' => $ad['ad_link'] ?? '#',
    ];
  };
  $videoAdMapper = function($ad) {
    return [
      'id' => intval($ad['id']),
      'title' => $ad['title'] ?? '',
      'description' => $ad['description'] ?? '',
      'video_url' => $ad['video_url'] ?? '',
      'image_url' => $ad['image_url'] ?? '',
      'ad_link' => $ad['ad_link'] ?? '#',
    ];
  };
  $adsJson = json_encode(array_map($adMapper, $viewAds)) ?: '[]';
  $verticalAdsJson = json_encode(array_map($adMapper, $viewVerticalAds)) ?: '[]';
  $horizontalAdsJson = json_encode(array_map($adMapper, $viewHorizontalAds)) ?: '[]';
  $stickyVideoAdsJson = json_encode(array_map($videoAdMapper, $stickyVideoAdsView)) ?: '[]';
?>
  <html lang="en">

  <head>
    <title><?= $collectionTitle ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />

    <!-- Facebook Meta Tags -->
    <meta property="og:url" content="<?= $collectionLink ?>" />
    <meta property="og:type" content="website" />
    <meta property="og:title" content="<?= $collectionTitle ?>" />
    <meta property="og:description" content="<?= $collectionTitle ?>" />
    <? if ($collectionImgCover <> '') { ?>
      <meta property="og:image" content="https://<?= $serverName ?>/data/covers/<?= $collectionImgCover ?>" />
      <meta property="og:image:secure-url" itemprop="image" content="https://<?= $serverName ?>/data/covers/<?= $collectionImgCover ?>" />
    <? } else { ?>
      <meta property="og:image" content="https://<?= $serverName ?>/data/logos/<?= $newsLogo ?>" />
      <meta property="og:image:secure-url" itemprop="image" content="https://<?= $serverName ?>/data/logos/<?= $newsLogo ?>" />
    <? } ?>

    <!-- Twitter Meta Tags -->
    <meta property="twitter:url" content="<?= $collectionLink ?>" />
    <meta name="twitter:card" content="summary" />
    <meta name="twitter:title" content="<?= $companyName ?>" />
    <meta name="twitter:description" content="" />
    <? if ($collectionImgCover <> '') { ?>
      <meta name="twitter:image" content="https://<?= $serverName ?>/data/covers/<?= $collectionImgCover ?>" />
    <? } else { ?>
      <meta name="twitter:image" content="https://<?= $serverName ?>/data/logos/<?= $newsLogo ?>" />
    <? } ?>

    <link rel="shortcut icon" href="https://<?= $serverName ?>/data/logos/<?= $newsLogo ?>" type="image/x-icon" />
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css" integrity="sha384-9aIt2nRpC12Uk9gS9baDl411NQApFmC26EwAOH8WgZl5MYYxFfc+NcPb1dKGj7Sk" crossorigin="anonymous" />
    <link rel="stylesheet" href="inc/fontawesome/css/all.min.css" />
    <link rel="stylesheet" href="/inc/style.css" />
    <link rel="icon" type="image/x-icon" href="/img/logo.ico">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js" integrity="sha256-9/aliU8dGd2tb6OSsuzixeV4y/faTqgFtohetphbbj0=" crossorigin="anonymous"></script>

    <script>
      // Disable text selection (copy)
      document.addEventListener('selectstart', function(e) {
        e.preventDefault();
        return false;
      });

      // Disable keyboard shortcuts (Ctrl+C, Ctrl+X, Ctrl+A, Print Screen)
      document.addEventListener('keydown', function(e) {
        // Ctrl+C, Ctrl+X, Ctrl+A, Ctrl+S
        if ((e.ctrlKey || e.metaKey) && (e.key === 'c' || e.key === 'x' || e.key === 'a' || e.key === 's')) {
          e.preventDefault();
          return false;
        }
        // Ctrl+Shift+I (Developer Tools)
        if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key === 'i' && e.key === 's') {
          e.preventDefault();
          return false;
        }
        // F12 (Developer Tools)
        if (e.key === 'F12') {
          e.preventDefault();
          return false;
        }
      });

      // Disable drag and drop
      document.addEventListener('dragstart', function(e) {
        e.preventDefault();
        return false;
      });

      // Disable user select via CSS
      document.addEventListener('DOMContentLoaded', function() {
        document.body.style.userSelect = 'none';
        document.body.style.webkitUserSelect = 'none';
        document.body.style.msUserSelect = 'none';
      });
    </script>
    <style>
      body {
        font-size: 16px;

      }

      .panelContent img {
        width: auto;
        height: auto;
        max-width: 100%;
      }

      /* Mid-Content Ad Banner */
      .mid-content-ad {
        margin: 25px 0;
        padding: 0;
        text-align: center;
        overflow: hidden;
        border-top: 1px solid #e0e0e0;
        border-bottom: 1px solid #e0e0e0;
        background: linear-gradient(135deg, #f8f9fa 0%, #fff 100%);
        border-radius: 8px;
        position: relative;
      }

      .mid-content-ad .ad-label {
        font-size: 10px;
        color: #999;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-bottom: 5px;
        display: block;
      }

      .mid-content-ad .ad-slider {
        display: flex;
        transition: transform 0.6s ease-in-out;
        width: 100%;
      }

      .mid-content-ad .ad-slide {
        min-width: 100%;
        box-sizing: border-box;
        padding: 15px;
      }

      .mid-content-ad .ad-slide a {
        text-decoration: none;
        display: block;
      }

      .mid-content-ad .ad-slide .ad-banner {
        width: 100%;
        max-width: 728px;
        margin: 0 auto;
        border-radius: 6px;
        overflow: hidden;
        position: relative;
      }

      .mid-content-ad .ad-slide .ad-banner img {
        width: 100%;
        height: auto;
        max-height: 200px;
        object-fit: cover;
        display: block;
        border-radius: 6px;
      }

      .mid-content-ad .ad-slide .ad-banner .ad-text-overlay {
        padding: 8px 12px;
        background: rgba(0,0,0,0.03);
      }

      .mid-content-ad .ad-slide .ad-banner .ad-text-overlay h4 {
        margin: 0 0 2px 0;
        font-size: 14px;
        font-weight: 600;
        color: #333;
      }

      .mid-content-ad .ad-slide .ad-banner .ad-text-overlay p {
        margin: 0;
        font-size: 12px;
        color: #666;
      }

      /* Post-Content Scrolling Ads */
      .post-content-ads {
        margin: 30px 0;
        padding: 15px 0;
        background: #f8f9fa;
        border-radius: 8px;
        overflow: hidden;
        position: relative;
      }

      .post-content-ads .ad-label {
        font-size: 10px;
        color: #999;
        text-transform: uppercase;
        letter-spacing: 1px;
        text-align: center;
        margin-bottom: 10px;
        display: block;
      }

      .post-content-ads .marquee-wrapper {
        overflow: hidden;
        width: 100%;
        position: relative;
      }

      .post-content-ads .marquee-track {
        display: flex;
        gap: 20px;
        animation: marqueeScroll 20s linear infinite;
        width: max-content;
      }

      .post-content-ads .marquee-track:hover {
        animation-play-state: paused;
      }

      .post-content-ads .marquee-item {
        flex-shrink: 0;
        width: 280px;
        border-radius: 8px;
        cursor: pointer;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        overflow: hidden;
        text-decoration: none;
        background: #fff;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        display: block;
      }

      .post-content-ads .marquee-item:hover {
        transform: scale(1.05);
        box-shadow: 0 8px 25px rgba(0,0,0,0.2);
      }

      .post-content-ads .marquee-item img {
        width: 100%;
        height: 150px;
        object-fit: cover;
        display: block;
      }

      .post-content-ads .marquee-item .ad-card-info {
        padding: 8px 10px;
      }

      .post-content-ads .marquee-item .ad-card-info h4 {
        margin: 0 0 3px 0;
        font-size: 13px;
        font-weight: 600;
        color: #333;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
      }

      .post-content-ads .marquee-item .ad-card-info p {
        margin: 0;
        font-size: 11px;
        color: #888;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
      }

      @keyframes marqueeScroll {
        0% { transform: translateX(0); }
        100% { transform: translateX(-33.333%); }
      }

      /* Bottom sliding ad bar */
      .bottom-ad-bar {
        margin: 20px 0;
        text-align: center;
        overflow: hidden;
        border-radius: 8px;
        position: relative;
        background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
        padding: 10px 0;
      }

      .bottom-ad-bar .ad-label {
        font-size: 10px;
        color: #666;
        text-transform: uppercase;
        letter-spacing: 1px;
        display: block;
        margin-bottom: 5px;
      }

      .bottom-ad-bar .slide-container {
        position: relative;
        height: 70px;
        overflow: hidden;
      }

      .bottom-ad-bar .slide-item {
        position: absolute;
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-weight: bold;
        font-size: 16px;
        opacity: 0;
        transform: translateX(100%);
        transition: all 0.8s ease-in-out;
      }

      .bottom-ad-bar .slide-item.active {
        opacity: 1;
        transform: translateX(0);
      }

      .bottom-ad-bar .slide-item.exit {
        opacity: 0;
        transform: translateX(-100%);
      }

      .bottom-ad-bar .slide-item a {
        color: #fff;
        text-decoration: none;
      }

      .bottom-ad-bar .slide-item a:hover {
        text-decoration: underline;
      }

      /* Center Content Ad - Featured Ad Display */
      .center-content-ad {
        margin: 40px auto;
        padding: 20px;
        text-align: center;
        max-width: 100%;
        background: linear-gradient(135deg, #f5f5f5 0%, #e8e8e8 100%);
        border-radius: 12px;
        border: 2px solid #ddd;
        position: relative;
        overflow: hidden;
      }

      .center-content-ad::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, #ff6b6b, #ff8e53, #ffd93d, #6bcf7f, #4d96ff);
        animation: gradientShift 3s ease infinite;
      }

      .center-content-ad .ad-label {
        font-size: 11px;
        color: #666;
        text-transform: uppercase;
        letter-spacing: 2px;
        font-weight: bold;
        margin-bottom: 15px;
        display: block;
      }

      .center-content-ad .featured-ad-container {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 15px;
      }

      .center-content-ad .featured-ad-item {
        width: 100%;
        max-width: 600px;
        background: #fff;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 4px 15px rgba(0,0,0,0.15);
        transition: transform 0.4s ease, box-shadow 0.4s ease;
        animation: slideInAd 0.6s ease-out;
      }

      .center-content-ad .featured-ad-item:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.25);
      }

      .center-content-ad .featured-ad-item a {
        text-decoration: none;
        display: block;
      }

      .center-content-ad .featured-ad-item img {
        width: 100%;
        height: 250px;
        object-fit: cover;
        display: block;
        transition: transform 0.5s ease;
      }

      .center-content-ad .featured-ad-item:hover img {
        transform: scale(1.05);
      }

      .center-content-ad .featured-ad-info {
        padding: 15px;
        background: #fff;
      }

      .center-content-ad .featured-ad-info h3 {
        margin: 0 0 8px 0;
        font-size: 18px;
        font-weight: 700;
        color: #1a1a1a;
      }

      .center-content-ad .featured-ad-info p {
        margin: 0;
        font-size: 13px;
        color: #666;
        line-height: 1.4;
      }

      @keyframes slideInAd {
        from {
          opacity: 0;
          transform: translateY(20px);
        }
        to {
          opacity: 1;
          transform: translateY(0);
        }
      }

      @keyframes gradientShift {
        0%, 100% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
      }

      /* Vertical Ad - TOI Style Sticky Sidebar Towers */
      .vertical-ad-left,
      .vertical-ad-right {
        position: fixed;
        top: 0;
        width: 160px;
        height: 100vh;
        z-index: 998;
        display: flex;
        flex-direction: column;
        gap: 0;
        overflow: hidden;
      }

      .vertical-ad-left {
        left: 0;
      }

      .vertical-ad-right {
        right: 0;
      }

      .vertical-ad-tower {
        width: 100%;
        height: 100%;
        position: relative;
      }

      .vertical-ad-tower a {
        text-decoration: none;
        display: block;
        width: 100%;
        height: 100%;
      }

      .vertical-ad-tower img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
      }

      .vertical-ad-tower .v-ad-info {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        padding: 10px;
        background: linear-gradient(transparent, rgba(0,0,0,0.7));
        color: #fff;
      }

      .vertical-ad-tower .v-ad-info h4 {
        margin: 0;
        font-size: 13px;
        font-weight: 600;
        color: #fff;
        text-shadow: 0 1px 3px rgba(0,0,0,0.5);
      }

      .vertical-ad-tower .v-ad-label {
        font-size: 9px;
        color: rgba(255,255,255,0.7);
        text-transform: uppercase;
        letter-spacing: 1px;
        position: absolute;
        top: 5px;
        right: 5px;
        background: rgba(0,0,0,0.5);
        padding: 2px 6px;
        border-radius: 3px;
      }

      /* Push main content inward when vertical ads are showing */
      body.has-vertical-ads .container {
        max-width: 900px;
        margin-left: 170px;
        margin-right: 170px;
      }

      /* Wide screens: show larger tower ads */
      @media (min-width: 1400px) {
        .vertical-ad-left,
        .vertical-ad-right {
          width: 200px;
        }
        body.has-vertical-ads .container {
          margin-left: 210px;
          margin-right: 210px;
        }
      }

      /* Horizontal Ad - Full Width Banner */
      .horizontal-ad-container {
        margin: 25px 0;
        padding: 0;
        overflow: hidden;
        border-radius: 10px;
        background: linear-gradient(135deg, #fafafa 0%, #f0f0f0 100%);
        border: 1px solid #e5e5e5;
        position: relative;
      }

      .horizontal-ad-container .ad-label {
        font-size: 10px;
        color: #999;
        text-transform: uppercase;
        letter-spacing: 1px;
        text-align: center;
        display: block;
        padding: 6px 0 0;
      }

      .horizontal-ad-slider {
        display: flex;
        transition: transform 0.6s ease-in-out;
        width: 100%;
      }

      .horizontal-ad-slide {
        min-width: 100%;
        box-sizing: border-box;
      }

      .horizontal-ad-slide a {
        text-decoration: none;
        display: flex;
        align-items: center;
        padding: 12px 20px;
        gap: 20px;
      }

      .horizontal-ad-slide .h-ad-img {
        width: 200px;
        height: 120px;
        object-fit: cover;
        border-radius: 8px;
        flex-shrink: 0;
      }

      .horizontal-ad-slide .h-ad-content {
        flex: 1;
      }

      .horizontal-ad-slide .h-ad-content h4 {
        margin: 0 0 6px 0;
        font-size: 16px;
        font-weight: 700;
        color: #222;
      }

      .horizontal-ad-slide .h-ad-content p {
        margin: 0;
        font-size: 13px;
        color: #666;
        line-height: 1.4;
      }

      .horizontal-ad-slide .h-ad-cta {
        display: inline-block;
        margin-top: 10px;
        padding: 6px 16px;
        background: #667eea;
        color: #fff;
        border-radius: 5px;
        font-size: 12px;
        font-weight: 600;
      }

      .horizontal-ad-dots {
        text-align: center;
        padding: 8px 0;
      }

      .horizontal-ad-dots .dot {
        display: inline-block;
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: #ccc;
        margin: 0 3px;
        cursor: pointer;
        transition: background 0.3s;
      }

      .horizontal-ad-dots .dot.active {
        background: #667eea;
      }

      /* ===== Sticky Bottom Video Ad (DailyHunt style) ===== */
      .sticky-video-ad {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        z-index: 9999;
        background: #000;
        transform: translateY(100%);
        transition: transform 0.4s ease-in-out;
        box-shadow: 0 -4px 20px rgba(0,0,0,0.3);
      }

      .sticky-video-ad.visible {
        transform: translateY(0);
      }

      .sticky-video-ad-inner {
        display: flex;
        align-items: stretch;
        max-width: 100%;
        position: relative;
      }

      .sticky-video-player {
        width: 320px;
        height: 180px;
        flex-shrink: 0;
        position: relative;
        background: #000;
        cursor: pointer;
      }

      .sticky-video-player video {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
      }

      .sticky-video-player .video-play-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(0,0,0,0.3);
        opacity: 0;
        transition: opacity 0.2s;
        pointer-events: none;
      }

      .sticky-video-player:hover .video-play-overlay {
        opacity: 1;
      }

      .sticky-video-player .video-play-overlay i {
        color: #fff;
        font-size: 36px;
        text-shadow: 0 2px 8px rgba(0,0,0,0.5);
      }

      .sticky-video-info {
        flex: 1;
        padding: 12px 16px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
        min-width: 0;
      }

      .sticky-video-info .ad-badge {
        display: inline-block;
        background: rgba(255,255,255,0.15);
        color: #aaa;
        font-size: 9px;
        font-weight: 700;
        letter-spacing: 1.5px;
        text-transform: uppercase;
        padding: 2px 8px;
        border-radius: 3px;
        margin-bottom: 6px;
        width: fit-content;
      }

      .sticky-video-info h4 {
        margin: 0 0 4px 0;
        font-size: 14px;
        font-weight: 600;
        color: #fff;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
      }

      .sticky-video-info p {
        margin: 0 0 8px 0;
        font-size: 12px;
        color: #999;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
      }

      .sticky-video-info .sticky-video-cta {
        display: inline-block;
        padding: 6px 18px;
        background: #ff4444;
        color: #fff;
        border-radius: 4px;
        text-decoration: none;
        font-size: 12px;
        font-weight: 600;
        width: fit-content;
        transition: background 0.2s;
      }

      .sticky-video-info .sticky-video-cta:hover {
        background: #e03030;
      }

      .sticky-video-close {
        position: absolute;
        top: 6px;
        right: 8px;
        width: 28px;
        height: 28px;
        background: rgba(0,0,0,0.6);
        border: none;
        border-radius: 50%;
        color: #fff;
        font-size: 16px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 10;
        transition: background 0.2s;
      }

      .sticky-video-close:hover {
        background: rgba(255,0,0,0.7);
      }

      .sticky-video-progress {
        position: absolute;
        bottom: 0;
        left: 0;
        height: 3px;
        background: #ff4444;
        transition: width 0.3s linear;
        z-index: 5;
      }

      .sticky-video-mute-btn {
        position: absolute;
        bottom: 8px;
        left: 8px;
        width: 28px;
        height: 28px;
        background: rgba(0,0,0,0.6);
        border: none;
        border-radius: 50%;
        color: #fff;
        font-size: 13px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 10;
      }

      .sticky-video-mute-btn:hover {
        background: rgba(0,0,0,0.8);
      }

      @media (max-width: 600px) {
        .sticky-video-ad-inner {
          flex-direction: column;
        }
        .sticky-video-player {
          width: 100%;
          height: 140px;
        }
        .sticky-video-info {
          padding: 8px 12px;
        }
        .sticky-video-info h4 {
          font-size: 13px;
        }
      }

      /* Hide vertical tower ads on screens smaller than 1200px */
      @media (max-width: 1200px) {
        .vertical-ad-left,
        .vertical-ad-right {
          display: none !important;
        }
        body.has-vertical-ads .container {
          margin-left: auto;
          margin-right: auto;
        }
      }

      @media (max-width: 768px) {
        .mid-content-ad .ad-slide .ad-banner {
          height: 70px;
          font-size: 14px;
        }
        .post-content-ads .marquee-item {
          width: 220px;
          height: 120px;
          font-size: 13px;
        }
        .center-content-ad .featured-ad-item img {
          height: 180px;
        }
        .horizontal-ad-slide a {
          flex-direction: column;
          padding: 10px;
          gap: 10px;
        }
        .horizontal-ad-slide .h-ad-img {
          width: 100%;
          height: 150px;
        }
      }
    </style>
    <style>
      .conversations {
        max-height: 300px;
        overflow-y: scroll;
      }

      .comments-section {
        margin-top: 40px;
        margin-bottom: 80px;
        margin-left: 90px;
        margin-right: 90px;

      }

      .comment-form {
        margin-bottom: 20px;
      }

      .comment-form textarea {
        width: 100%;
        height: 100px;
        margin-bottom: 10px;
      }

      .comment {
        width: fit-content;
        margin-bottom: 20px;
        padding: 10px 20px;
        /* border: 1px solid #ccc; */
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);
        border-radius: 5px;
      }

      .comment .author {
        font-weight: bold;
      }

      .conversation-form {
        display: flex;
        width: 100%;
        column-gap: 8px;
        justify-content: left;
        margin: 10px 0px;
        margin-bottom: 20px;
      }

      .comment-text {
        border-radius: 6px;
        padding: 5px 10px;
        width: 80%;
        height: 40px;
      }

      .conversation-btn {
        border-radius: 5px;

      }

      .author span {
        padding-left: 10px;
      }

      .login-redirect {
        display: flex;
        justify-content: center;
        max-width: 100%;


      }

      .login-redirect span {
        border: 0.2px solid black;
        padding: 8px 16px;
        border-radius: 10px;
        margin: 10px 20px;
        width: 600px;
        display: flex;
        justify-content: center;
      }

      .login-redirect a:hover {
        text-decoration: none;
        color: #000;
      }

      .login-redirect span:hover {
        background-color: #d9d9e18f;
      }
    </style>
  </head>

  <body>
    <div class="container">

      <? if ($newsLogo <> '' && $user <> '' && $companyName <> '') { ?>
        <header class="blog-header py-3 border-bottom">
          <div class="row flex-nowrap justify-content-between align-items-center">
            <div class="col-8 pt-1">
              <? if ($newsLogo <> '') { ?>
                <a class="text-muted" href="<?= $website ?>"><img class="mx-auto" width="256px" src="https://<?= $serverName ?>/data/logos/<?= $newsLogo ?>" /></a>
              <? } ?>
              <h6 class="mt-1" style="color:#9e9e9e"><?= $user ?> | <?= $companyName ?></h6>
            </div>
            <div class="col-4 text-right">
            </div>
          </div>
        </header>
      <? } ?>

      <div class="row mt-3 pb-5">
        <div class="panelContent col-12 col-md pb-3">
          <h1><?= $collectionTitle ?></h1>
          <h6 style="color:#9e9e9e"><?= $collectionDate ?></h6>
          <? if ($collectionImgCover <> '') { ?>
            <div class="my-2"><img src="https://<?= $serverName ?>/data/covers/<?= $collectionImgCover ?>" /></div>
          <? } ?>
          <div id="article-content-wrapper"><?= $collectionDesc ?></div>

          <!-- Horizontal Ad Banner -->
          <div class="horizontal-ad-container" id="horizontalAdContainer" style="display:none;">
            <span class="ad-label">Sponsored</span>
            <div class="horizontal-ad-slider" id="horizontalAdSlider">
              <!-- Horizontal ads injected by JS -->
            </div>
            <div class="horizontal-ad-dots" id="horizontalAdDots"></div>
          </div>

          <!-- Center Content Ad - Featured Ad Section -->
          <div class="center-content-ad" id="centerContentAd" style="display:none;">
            <span class="ad-label">Featured Offer</span>
            <div class="featured-ad-container" id="featuredAdContainer">
              <!-- Featured ads will be injected by JS -->
            </div>
          </div>

          <? if ($collectionIsReadMore <> '') { ?>
            <center>
              <a href="https://<?= $serverName ?>/more.php?id=<?= $id ?>" target="_blank">
                <div class="btn btn-warning py-2"><?= $collectionReadMoreTxt ?></div>
              </a>
            </center>
            <br>
          <? } ?>
          <? if ($newsLogo <> '' && $user <> '' && $companyName <> '') { ?>
            <p class="small" style="color:#189eb5;">Publisher: <?= $user ?> | <?= $companyName ?></p>
          <? } ?>

          <!-- Post-Content Scrolling Ads -->
          <div class="post-content-ads">
            <span class="ad-label">Sponsored</span>
            <div class="marquee-wrapper">
              <div class="marquee-track" id="marqueeTrack">
                <!-- Ads will be injected by JS -->
              </div>
            </div>
          </div>

          <!-- Bottom Sliding Ad Bar -->
          <div class="bottom-ad-bar">
            <span class="ad-label">Advertisement</span>
            <div class="slide-container" id="bottomAdSlider">
              <!-- Slides will be injected by JS -->
            </div>
          </div>

        </div>

        <? if ($userSidePanel == 1) { ?>
          <div style="width:300px;"><?= $userSidePanelContent ?></div>
        <? } ?>

      </div>

    </div>


    <!-- <div class="comments-section" style="padding:0 15px;">
      <h3>Comments<span style="font-size: 12px; font-style: italic; color: grey; margin-left: 10px;"><? //noOfComments($db, $id) 
                                                                                                      ?> Comments</span></h3>
      <div class="conversations">
        <div id="comments">
          <?php
          // display_comments($chat);
          ?>
        </div>
      </div> -->

    <div class="login-redirect">
      <span><a href="/signup.html?type=login&article_Id=<?= $id ?>">Login to Give your comment</a></span>
    </div>


    </div>

    <!-- Vertical Ads - Fixed Left & Right Towers -->
    <div class="vertical-ad-left" id="verticalAdLeft" style="display:none;">
      <!-- Left tower ad injected by JS -->
    </div>
    <div class="vertical-ad-right" id="verticalAdRight" style="display:none;">
      <!-- Right tower ad injected by JS -->
    </div>

    <div class="container fixed-bottom pb-3" id="njFooter" style="background-color:#fff">
      <div class="row no-gutters" style="border-top:1px solid #eb5e31">
        <div class="col mt-2 text-right">
          <small>Powered by</small> <a href="https://www.newsjunction.net/" target="_blank"><img src="/inc/img/logo.black.png" width="100" /></a>
        </div>
      </div>
    </div>

    <!-- Sticky Bottom Video Ad -->
    <div class="sticky-video-ad" id="stickyVideoAd">
      <div class="sticky-video-progress" id="stickyVideoProgress"></div>
      <div class="sticky-video-ad-inner">
        <button class="sticky-video-close" id="stickyVideoClose" title="Close ad">&times;</button>
        <div class="sticky-video-player" id="stickyVideoPlayerWrap">
          <video id="stickyVideoEl" muted playsinline preload="metadata"></video>
          <div class="video-play-overlay"><i class="fas fa-play"></i></div>
          <button class="sticky-video-mute-btn" id="stickyVideoMuteBtn" title="Toggle sound">
            <i class="fas fa-volume-mute" id="stickyMuteIcon"></i>
          </button>
        </div>
        <div class="sticky-video-info">
          <span class="ad-badge">Sponsored</span>
          <h4 id="stickyVideoTitle"></h4>
          <p id="stickyVideoDesc"></p>
          <a href="#" id="stickyVideoCta" class="sticky-video-cta" target="_blank">Learn More</a>
        </div>
      </div>
    </div>

  </body>



  <script>
    function sendMessageAndRefresh(articleId, user_id) {
      // Place your sendMessage functionality here
      sendMessage(articleId, userName);

      // Refresh the page
      location.reload();
    }

    function sendMessage(article_id, user_name) {

      var messageText = $('#comment-text1').val().trim();
      var now = new Date(); // Get current date and time in user's local time zone

      var posted_on = now.getFullYear() + '-' +
        ('0' + (now.getMonth() + 1)).slice(-2) + '-' +
        ('0' + now.getDate()).slice(-2) + ' ' +
        ('0' + now.getHours()).slice(-2) + ':' +
        ('0' + now.getMinutes()).slice(-2) + ':' +
        ('0' + now.getSeconds()).slice(-2); // Format as 'YYYY-MM-DD HH:MM:SS'

      if (messageText === '') {
        return;
      }

      $.ajax({
        url: 'conversations/save_conversations.php',
        method: 'POST',
        data: {
          article_id: article_id,
          user_id: user_id,
          message: messageText,
          posted_on: posted_on
        },
        success: function(response) {
          // Handle the response
          alert('Message sent successfully!');
          console.log(response); // Log response to console
          location.reload(); // Reload the page after successful message post
        },
        error: function(xhr, status, error) {
          // Handle errors
          console.error(xhr.responseText);
        }
      });
    }



    function saveComments(comments) {
      localStorage.setItem('comments', JSON.stringify(comments));
    }

    function addComment() {
      const text = $('#comment-text').val();
      // const author = $('#comment-author').val();
      const author = '<?php echo $user_name; ?>';


      if (!text || !author) {
        alert('Please enter your name and comment.');
        return;
      }

      const comments = getComments();
      comments.push({
        text: text,
        author: author,
        date: new Date().toLocaleString()
      });
      saveComments(comments);

      $('#comment-text').val('');
      $('#comment-author').val('');
      renderComments();
    }

    function renderComments() {
      const comments = getComments();
      const commentsDiv = $('#comments');

      comments.forEach(comment => {
        const commentDiv = $('<div></div>').addClass('comment');
        commentDiv.html(`
            <div class="author">${comment.author} <span style="font-size: 0.8em; color: #555;">${comment.date}</span></div>
            <div class="text">${comment.text}</div>
            `);
        commentsDiv.append(commentDiv);
      });
    }

    $(document).ready(renderComments);

    // ===================== AD SYSTEM =====================

    // Real ads from database
    var adData = <?= $adsJson ?>;
    var verticalAdData = <?= $verticalAdsJson ?>;
    var horizontalAdData = <?= $horizontalAdsJson ?>;

    // Debug: check in browser console (F12 > Console)
    console.log('DEBUG ads - feed/top:', adData.length, adData);
    console.log('DEBUG ads - vertical:', verticalAdData.length, verticalAdData);
    console.log('DEBUG ads - horizontal:', horizontalAdData.length, horizontalAdData);

    // Shuffle ads randomly
    function shuffleArray(arr) {
      for (var i = arr.length - 1; i > 0; i--) {
        var j = Math.floor(Math.random() * (i + 1));
        var tmp = arr[i]; arr[i] = arr[j]; arr[j] = tmp;
      }
      return arr;
    }

    // Get click-tracked URL for an ad
    function adClickUrl(ad) {
      return '/process/ad_click_handler.php?ad_id=' + ad.id;
    }

    // Track impression for an ad
    function trackImpression(adId) {
      $.get('/process/ad_click_handler.php', { ad_id: adId, track: 'impression' });
    }

    $(document).ready(function() {
      // Render feed/top ads
      if (adData && adData.length > 0) {
        shuffleArray(adData);
        injectCenterContentAd();
        injectMidContentAd();
        buildMarqueeAds();
        buildBottomSlider();
      } else {
        $('.post-content-ads, .bottom-ad-bar, #centerContentAd').hide();
      }

      // Render vertical ads
      if (verticalAdData && verticalAdData.length > 0) {
        shuffleArray(verticalAdData);
        buildVerticalAds();
      }

      // Render horizontal ads
      if (horizontalAdData && horizontalAdData.length > 0) {
        shuffleArray(horizontalAdData);
        buildHorizontalAds();
      }
    });

    // === Center Content Featured Ad ===
    function injectCenterContentAd() {
      var container = document.getElementById('featuredAdContainer');
      if (!container || adData.length === 0) return;

      // Use first 2 ads for center featured display
      var centerAds = adData.slice(0, Math.min(2, adData.length));
      var html = '';
      
      for (var i = 0; i < centerAds.length; i++) {
        var ad = centerAds[i];
        html += '<div class="featured-ad-item">';
        html += '<a href="' + adClickUrl(ad) + '">';
        if (ad.image_url) {
          html += '<img src="/' + ad.image_url + '" alt="' + ad.title + '" />';
        }
        html += '<div class="featured-ad-info">';
        html += '<h3>' + ad.title + '</h3>';
        if (ad.description) html += '<p>' + ad.description + '</p>';
        html += '</div>';
        html += '</a></div>';
      }
      
      container.innerHTML = html;
      document.getElementById('centerContentAd').style.display = 'block';
    }

    // === Mid-Content Ad ===
    function injectMidContentAd() {
      var wrapper = document.getElementById('article-content-wrapper');
      if (!wrapper || adData.length === 0) return;

      var html = wrapper.innerHTML;
      // Split content roughly in half by finding a tag break near the middle
      var midPoint = Math.floor(html.length / 2);
      var breakIndex = html.indexOf('</p>', midPoint);
      if (breakIndex === -1) breakIndex = html.indexOf('<br>', midPoint);
      if (breakIndex === -1) breakIndex = html.indexOf('. ', midPoint);
      if (breakIndex === -1) breakIndex = midPoint;

      // Adjust breakIndex to end after the closing tag
      if (html.substr(breakIndex, 4) === '</p>') breakIndex += 4;
      else if (html.substr(breakIndex, 4) === '<br>') breakIndex += 4;
      else breakIndex += 2;

      // Pick up to 3 ads for rotation
      var midAds = adData.slice(0, Math.min(3, adData.length));
      var adHTML = '<div class="mid-content-ad"><span class="ad-label">Advertisement</span>';
      adHTML += '<div class="ad-slider" id="midAdSlider">';
      for (var i = 0; i < midAds.length; i++) {
        var ad = midAds[i];
        adHTML += '<div class="ad-slide">';
        adHTML += '<a href="' + adClickUrl(ad) + '">';
        adHTML += '<div class="ad-banner">';
        if (ad.image_url) {
          adHTML += '<img src="/' + ad.image_url + '" alt="' + ad.title + '">';
        }
        adHTML += '<div class="ad-text-overlay"><h4>' + ad.title + '</h4>';
        if (ad.description) adHTML += '<p>' + ad.description + '</p>';
        adHTML += '</div>';
        adHTML += '</div></a></div>';
      }
      adHTML += '</div></div>';

      wrapper.innerHTML = html.substring(0, breakIndex) + adHTML + html.substring(breakIndex);

      // Auto-rotate mid-content ad
      if (midAds.length > 1) {
        var currentMidSlide = 0;
        var totalMidSlides = midAds.length;
        setInterval(function() {
          currentMidSlide = (currentMidSlide + 1) % totalMidSlides;
          var slider = document.getElementById('midAdSlider');
          if (slider) {
            slider.style.transform = 'translateX(-' + (currentMidSlide * 100) + '%)';
          }
        }, 4000);
      }
    }

    // === Marquee Scrolling Ads ===
    function buildMarqueeAds() {
      var track = document.getElementById('marqueeTrack');
      if (!track || adData.length === 0) return;

      var items = '';
      // Duplicate ads for seamless infinite scroll
      for (var round = 0; round < 3; round++) {
        for (var i = 0; i < adData.length; i++) {
          var ad = adData[i];
          items += '<a href="' + adClickUrl(ad) + '" class="marquee-item" onclick="trackImpression(' + ad.id + ')">';
          if (ad.image_url) {
            items += '<img src="/' + ad.image_url + '" alt="' + ad.title + '">';
          }
          items += '<div class="ad-card-info"><h4>' + ad.title + '</h4>';
          if (ad.description) items += '<p>' + ad.description + '</p>';
          items += '</div></a>';
        }
      }
      track.innerHTML = items;

      // Smooth continuous scrolling animation
      track.style.animation = 'marqueeScroll ' + (adData.length * 6) + 's linear infinite';
    }

    // === Bottom Sliding Ads ===
    function buildBottomSlider() {
      var container = document.getElementById('bottomAdSlider');
      if (!container || adData.length === 0) return;

      var bottomAds = adData.slice(0, Math.min(5, adData.length));
      var slides = '';
      for (var i = 0; i < bottomAds.length; i++) {
        var ad = bottomAds[i];
        var activeClass = (i === 0) ? ' active' : '';
        slides += '<div class="slide-item' + activeClass + '">';
        slides += '<a href="' + adClickUrl(ad) + '" onclick="trackImpression(' + ad.id + ')">' + ad.title;
        if (ad.description) slides += ' &mdash; ' + ad.description;
        slides += '</a></div>';
      }
      container.innerHTML = slides;

      if (bottomAds.length > 1) {
        var currentSlide = 0;
        var totalSlides = bottomAds.length;
        setInterval(function() {
          var allSlides = container.querySelectorAll('.slide-item');
          if (!allSlides.length) return;

          allSlides[currentSlide].classList.remove('active');
          allSlides[currentSlide].classList.add('exit');

          setTimeout(function() {
            for (var s = 0; s < allSlides.length; s++) {
              allSlides[s].classList.remove('exit');
            }
          }, 800);

          currentSlide = (currentSlide + 1) % totalSlides;
          allSlides[currentSlide].classList.add('active');
        }, 4000);
      }
    }

    // === Vertical Ads (Fixed Left & Right Towers - TOI Style) ===
    function buildVerticalAds() {
      var leftContainer = document.getElementById('verticalAdLeft');
      var rightContainer = document.getElementById('verticalAdRight');
      if ((!leftContainer && !rightContainer) || verticalAdData.length === 0) return;

      function buildTower(ad) {
        var html = '<div class="vertical-ad-tower">';
        html += '<span class="v-ad-label">AD</span>';
        html += '<a href="' + adClickUrl(ad) + '" onclick="trackImpression(' + ad.id + ')">';
        if (ad.image_url) {
          html += '<img src="/' + ad.image_url + '" alt="' + ad.title + '">';
        }
        html += '<div class="v-ad-info"><h4>' + ad.title + '</h4></div>';
        html += '</a></div>';
        return html;
      }

      // First ad goes to left, second to right, if only 1 ad show on both sides
      if (verticalAdData.length >= 2) {
        if (leftContainer) {
          leftContainer.innerHTML = buildTower(verticalAdData[0]);
          leftContainer.style.display = 'block';
        }
        if (rightContainer) {
          rightContainer.innerHTML = buildTower(verticalAdData[1]);
          rightContainer.style.display = 'block';
        }
      } else {
        // Single ad — show same on both sides
        var ad = verticalAdData[0];
        if (leftContainer) {
          leftContainer.innerHTML = buildTower(ad);
          leftContainer.style.display = 'block';
        }
        if (rightContainer) {
          rightContainer.innerHTML = buildTower(ad);
          rightContainer.style.display = 'block';
        }
      }

      // Push content inward
      document.body.classList.add('has-vertical-ads');

      // Auto-rotate if more than 2 ads
      if (verticalAdData.length > 2) {
        var leftIdx = 0;
        var rightIdx = 1;
        setInterval(function() {
          leftIdx = (leftIdx + 2) % verticalAdData.length;
          rightIdx = (rightIdx + 2) % verticalAdData.length;
          if (leftContainer) leftContainer.innerHTML = buildTower(verticalAdData[leftIdx]);
          if (rightContainer) rightContainer.innerHTML = buildTower(verticalAdData[rightIdx % verticalAdData.length]);
        }, 6000);
      }
    }

    // === Horizontal Ads (Full-Width Banner Slider) ===
    function buildHorizontalAds() {
      var slider = document.getElementById('horizontalAdSlider');
      var dotsContainer = document.getElementById('horizontalAdDots');
      var wrapper = document.getElementById('horizontalAdContainer');
      if (!slider || !wrapper || horizontalAdData.length === 0) return;

      var hAds = horizontalAdData.slice(0, Math.min(5, horizontalAdData.length));
      var slidesHTML = '';
      var dotsHTML = '';

      for (var i = 0; i < hAds.length; i++) {
        var ad = hAds[i];
        slidesHTML += '<div class="horizontal-ad-slide">';
        slidesHTML += '<a href="' + adClickUrl(ad) + '" onclick="trackImpression(' + ad.id + ')">';
        if (ad.image_url) {
          slidesHTML += '<img class="h-ad-img" src="/' + ad.image_url + '" alt="' + ad.title + '">';
        }
        slidesHTML += '<div class="h-ad-content">';
        slidesHTML += '<h4>' + ad.title + '</h4>';
        if (ad.description) slidesHTML += '<p>' + ad.description + '</p>';
        slidesHTML += '<span class="h-ad-cta">Learn More</span>';
        slidesHTML += '</div></a></div>';

        dotsHTML += '<span class="dot' + (i === 0 ? ' active' : '') + '" data-index="' + i + '"></span>';
      }

      slider.innerHTML = slidesHTML;
      if (dotsContainer) dotsContainer.innerHTML = dotsHTML;
      wrapper.style.display = 'block';

      // Auto-rotate horizontal ads
      if (hAds.length > 1) {
        var currentH = 0;
        var totalH = hAds.length;

        function goToSlide(index) {
          currentH = index;
          slider.style.transform = 'translateX(-' + (currentH * 100) + '%)';
          if (dotsContainer) {
            var allDots = dotsContainer.querySelectorAll('.dot');
            for (var d = 0; d < allDots.length; d++) {
              allDots[d].classList.toggle('active', d === currentH);
            }
          }
        }

        // Dot click navigation
        if (dotsContainer) {
          dotsContainer.addEventListener('click', function(e) {
            if (e.target.classList.contains('dot')) {
              goToSlide(parseInt(e.target.getAttribute('data-index')));
            }
          });
        }

        setInterval(function() {
          goToSlide((currentH + 1) % totalH);
        }, 5000);
      }
    }

    // ===== STICKY BOTTOM VIDEO AD (DailyHunt Style) =====
    var stickyVideoData = <?= $stickyVideoAdsJson ?>;
    var stickyVideoClosed = false;
    var stickyVideoCurrentIndex = 0;

    $(document).ready(function() {
      if (stickyVideoData && stickyVideoData.length > 0) {
        initStickyVideoAd();
      }
    });

    function initStickyVideoAd() {
      var container = document.getElementById('stickyVideoAd');
      var videoEl = document.getElementById('stickyVideoEl');
      var closeBtn = document.getElementById('stickyVideoClose');
      var muteBtn = document.getElementById('stickyVideoMuteBtn');
      var muteIcon = document.getElementById('stickyMuteIcon');
      var progressBar = document.getElementById('stickyVideoProgress');
      var footer = document.getElementById('njFooter');

      if (!container || !videoEl || stickyVideoData.length === 0) return;

      // Hide the footer when sticky video ad is shown
      if (footer) footer.style.display = 'none';

      // Load the first video ad
      loadStickyVideoAd(stickyVideoData[0]);

      // Show the sticky ad after user scrolls down 300px
      var hasShown = false;
      window.addEventListener('scroll', function() {
        if (stickyVideoClosed) return;
        var scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        if (scrollTop > 300 && !hasShown) {
          hasShown = true;
          container.classList.add('visible');
          videoEl.play().catch(function() {});
        }
      });

      // Close button
      closeBtn.addEventListener('click', function() {
        container.classList.remove('visible');
        videoEl.pause();
        stickyVideoClosed = true;
        // Show footer back
        if (footer) footer.style.display = '';
      });

      // Toggle mute/unmute
      muteBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        videoEl.muted = !videoEl.muted;
        muteIcon.className = videoEl.muted ? 'fas fa-volume-mute' : 'fas fa-volume-up';
      });

      // Click on video player area to play/pause
      document.getElementById('stickyVideoPlayerWrap').addEventListener('click', function() {
        if (videoEl.paused) {
          videoEl.play().catch(function() {});
        } else {
          videoEl.pause();
        }
      });

      // Update progress bar
      videoEl.addEventListener('timeupdate', function() {
        if (videoEl.duration) {
          var pct = (videoEl.currentTime / videoEl.duration) * 100;
          progressBar.style.width = pct + '%';
        }
      });

      // When video ends, load the next one (rotate through ads)
      videoEl.addEventListener('ended', function() {
        if (stickyVideoData.length > 1) {
          stickyVideoCurrentIndex = (stickyVideoCurrentIndex + 1) % stickyVideoData.length;
          loadStickyVideoAd(stickyVideoData[stickyVideoCurrentIndex]);
          videoEl.play().catch(function() {});
        } else {
          // Single video: loop it
          videoEl.currentTime = 0;
          videoEl.play().catch(function() {});
        }
      });
    }

    function loadStickyVideoAd(ad) {
      var videoEl = document.getElementById('stickyVideoEl');
      var titleEl = document.getElementById('stickyVideoTitle');
      var descEl = document.getElementById('stickyVideoDesc');
      var ctaEl = document.getElementById('stickyVideoCta');
      var progressBar = document.getElementById('stickyVideoProgress');

      videoEl.src = '/' + ad.video_url;
      videoEl.load();
      titleEl.textContent = ad.title;
      descEl.textContent = ad.description || '';
      ctaEl.href = adClickUrl(ad);
      progressBar.style.width = '0%';

      // Track impression
      trackImpression(ad.id);
    }
  </script>

  </html>
<? } ?>