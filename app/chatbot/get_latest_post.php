<?php
date_default_timezone_set('Asia/Kolkata');

// Function to fetch a random RSS item, adapted from index.php
function fetch_random_rss_item($feeds) {
    // Shuffle the feeds to ensure randomness
    shuffle($feeds);
    $random_feed_url = $feeds[0];
    
    // Add error logging
    $log_file = 'rss_errors.log';
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - Attempting to fetch: $random_feed_url\n", FILE_APPEND);
    
    // Set up context with timeout
    $context = stream_context_create([
        'http' => [
            'timeout' => 10, // 10 second timeout
            'user_agent' => 'Mozilla/5.0 (compatible; RSSBot/1.0)'
        ]
    ]);
    
    $xml_string = @file_get_contents($random_feed_url, false, $context);
    if ($xml_string === false) {
        file_put_contents($log_file, date('Y-m-d H:i:s') . " - Failed to fetch content from: $random_feed_url\n", FILE_APPEND);
        return null;
    }
    
    if (strpos($xml_string, '<?xml') === false && strpos($xml_string, '<rss') === false && strpos($xml_string, '<feed') === false) {
        file_put_contents($log_file, date('Y-m-d H:i:s') . " - Response is not valid XML/RSS from: $random_feed_url\n", FILE_APPEND);
        return null;
    }
    
    libxml_use_internal_errors(true);
    $xml = simplexml_load_string($xml_string);
    $xml_errors = libxml_get_errors();
    libxml_clear_errors();
    
    if ($xml === false || !empty($xml_errors)) {
        file_put_contents($log_file, date('Y-m-d H:i:s') . " - Failed to parse XML from: $random_feed_url\n", FILE_APPEND);
        foreach ($xml_errors as $error) {
            file_put_contents($log_file, "  XML Error: " . $error->message . "\n", FILE_APPEND);
        }
        return null;
    }
    
    if (!isset($xml->channel->item) || empty($xml->channel->item)) {
        file_put_contents($log_file, date('Y-m-d H:i:s') . " - No items found in RSS feed: $random_feed_url\n", FILE_APPEND);
        return null;
    }
    
    $itemsList = [];
    foreach ($xml->channel->item as $it) {
        $itemsList[] = $it;
    }
    if (empty($itemsList)) {
        file_put_contents($log_file, date('Y-m-d H:i:s') . " - Empty items array from: $random_feed_url\n", FILE_APPEND);
        return null;
    }
    
    // Get the latest item, which is usually the first one
    $latest_item = $itemsList[0];
    
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - Successfully fetched latest item from: $random_feed_url\n", FILE_APPEND);
    
    return ['item' => $latest_item, 'feed_url' => $random_feed_url];
}

// User feeds from index.php
$user_feeds = [
    'Ananya' => [
        'https://timesofindia.indiatimes.com/rssfeedstopstories.cms',
        'https://timesofindia.indiatimes.com/rssfeedmostread.cms',
        'https://timesofindia.indiatimes.com/rssfeeds/296589292.cms',
        'https://feeds.bbci.co.uk/news/world/asia/india/rss.xml',
        'https://www.indianewsnetwork.com/rss.en.all.xml',
        'https://www.oneindia.com/rss/news-fb.xml'
    ],
    'Soumya' => [
        'https://www.dnaindia.com/rss.xml',
        'https://www.thehindu.com/news/national/feeder/default.aspx',
        'https://www.hindustantimes.com/rss/topnews/rssfeed.xml',
        'https://timesofindia.indiatimes.com/rssfeedstopstories.cms',
        'https://timesofindia.indiatimes.com/rssfeedmostread.cms',
        'https://timesofindia.indiatimes.com/rssfeeds/296589292.cms'
    ],
    'Jenny' => [
        'https://feeds.bbci.co.uk/news/world/asia/india/rss.xml',
        'https://www.indianewsnetwork.com/rss.en.all.xml',
        'https://www.oneindia.com/rss/news-fb.xml',
        'https://www.dnaindia.com/rss.xml',
        'https://www.thehindu.com/news/national/feeder/default.aspx',
        'https://www.hindustantimes.com/rss/topnews/rssfeed.xml'
    ],
    'Harry' => [
        'https://timesofindia.indiatimes.com/rssfeedstopstories.cms',
        'https://timesofindia.indiatimes.com/rssfeedmostread.cms',
        'https://timesofindia.indiatimes.com/rssfeeds/296589292.cms',
        'https://feeds.bbci.co.uk/news/world/asia/india/rss.xml',
        'https://www.indianewsnetwork.com/rss.en.all.xml',
        'https://www.oneindia.com/rss/news-fb.xml'
    ],
    'Einstein' => [
        'https://www.dnaindia.com/rss.xml',
        'https://www.thehindu.com/news/national/feeder/default.aspx',
        'https://www.hindustantimes.com/rss/topnews/rssfeed.xml',
        'https://timesofindia.indiatimes.com/rssfeedstopstories.cms',
        'https://timesofindia.indiatimes.com/rssfeedmostread.cms',
        'https://timesofindia.indiatimes.com/rssfeeds/296589292.cms'
    ]
];

// Combine all feeds into a single array
$all_feeds = array_merge(...array_values($user_feeds));
// Remove duplicate feeds
$all_feeds = array_unique($all_feeds);

$result = null;
$max_attempts = 5; // Try up to 5 different feeds

for ($attempt = 0; $attempt < $max_attempts && $result === null; $attempt++) {
    $result = fetch_random_rss_item($all_feeds);
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Latest Post from Random RSS Feed</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 20px; }
        .post-container { max-width: 800px; margin: 0 auto; padding: 20px; background: #fff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #333; }
        .post-title { font-size: 1.5em; font-weight: bold; margin-top: 0; }
        .post-link { color: #007bff; text-decoration: none; }
        .post-link:hover { text-decoration: underline; }
        .post-description { margin-top: 10px; color: #555; }
        .error { color: #dc3545; }
        .feed-source { font-size: 0.9em; color: #6c757d; }
    </style>
</head>
<body>
    <div class="post-container">
        <h1>Latest Post from a Random RSS Feed</h1>
        <?php if ($result && $result['item']): ?>
            <?php $item = $result['item']; ?>
            <p class="feed-source">From: <?= htmlspecialchars($result['feed_url']) ?></p>
            <h2 class="post-title"><?= htmlspecialchars($item->title) ?></h2>
            <p><a class="post-link" href="<?= htmlspecialchars($item->link) ?>" target="_blank">Read full story</a></p>
            <div class="post-description">
                <?= htmlspecialchars($item->description) ?>
            </div>
        <?php else: ?>
            <p class="error">Could not fetch a post. Please check the rss_errors.log for details.</p>
        <?php endif; ?>
    </div>
</body>
</html>
</html>