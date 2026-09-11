<?php
// Initialize cURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://newsjunction.net/api/articles.php?rss_id=9');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

// Execute cURL request
$response = curl_exec($ch);
$articles = [];

if ($response !== false) {
    $articles = json_decode($response, true);
    if (!is_array($articles)) {
        $articles = [];
    }
} else {
    // Log error if needed
    error_log('cURL error: ' . curl_error($ch));
}

curl_close($ch);
?>
<!-- Right sidebar -->
<div class="right-sidebar">
    <div class="sidebar-card news-card">
        <div class="sidebar-title">All News</div>
        <div class="news-container">
            <?php if (!empty($articles)): ?>
                <?php foreach (array_slice($articles, 0, 7) as $article): ?>
                    <a href="<?= htmlspecialchars($article['url']); ?>" target="_blank" class="news-link">
                        <div class="news-item">
                            <img src="<?php echo htmlspecialchars($article['image']); ?>" alt="News thumbnail" class="news-thumbnail">
                            <div class="news-content">
                                <div class="news-headline"><?php echo htmlspecialchars($article['title']); ?></div>
                                <div class="news-source">Source • <?php echo date('Y-m-d'); ?></div>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="news-item">Failed to load news. Please try again later.</div>
            <?php endif; ?>
        </div>
        <a href="../social/dashboard.php" class="news-footer">See all news</a>
    </div>

    <!-- <div class="sidebar-card">
        <div class="sidebar-title">Who to follow</div>
        <div class="friend-item">
            <div class="user-avatar">D</div>
            <div class="friend-info">
                <div class="friend-name">David Chen</div>
                <div class="post-meta">@designdavid</div>
            </div>
        </div>
        <div class="friend-item">
            <div class="user-avatar">L</div>
            <div class="friend-info">
                <div class="friend-name">Lisa Morgan</div>
                <div class="post-meta">@lisacreates</div>
            </div>
        </div>
        <div class="friend-item">
            <div class="user-avatar">K</div>
            <div class="friend-info">
                <div class="friend-name">Kevin Patel</div>
                <div class="post-meta">@kevindev</div>
            </div>
        </div>
    </div> -->
</div>