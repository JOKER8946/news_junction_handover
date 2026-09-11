<?php
// Fetch news for the red ticker: top recent English from DB (all active feeds)
// + live Kannada from working RSS sources.
$articles = [];
$kannadaArticles = [];

// Helper: fetch and parse an RSS feed URL, return array of ['title'=>..., 'url'=>...]
function _scroller_fetch_rss($feedUrl, $limit = 15) {
    $items = [];
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $feedUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
    curl_setopt($ch, CURLOPT_TIMEOUT, 4);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    // Browser-like UA — some news sites (e.g. oneindia) block generic bot UAs with 403.
    curl_setopt($ch, CURLOPT_USERAGENT,
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
    $xml = curl_exec($ch);
    curl_close($ch);
    if ($xml === false || empty($xml)) return $items;

    libxml_use_internal_errors(true);
    $feed = simplexml_load_string($xml);
    if ($feed === false) return $items;

    if (isset($feed->channel->item)) {
        $count = 0;
        foreach ($feed->channel->item as $item) {
            if ($count >= $limit) break;
            $title = trim((string)$item->title);
            $url = trim((string)$item->link);
            if (!empty($title)) {
                $items[] = ['title' => $title, 'url' => $url];
                $count++;
            }
        }
    } elseif (isset($feed->entry)) {
        $count = 0;
        foreach ($feed->entry as $entry) {
            if ($count >= $limit) break;
            $title = trim((string)$entry->title);
            $url = '';
            if (isset($entry->link['href'])) {
                $url = trim((string)$entry->link['href']);
            }
            if (!empty($title)) {
                $items[] = ['title' => $title, 'url' => $url];
                $count++;
            }
        }
    }
    return $items;
}

try {
    // 1. English news — pull most recent across ALL active feeds (not just rss_id=134).
    //    Ensures the ticker stays fresh even if any one source stops publishing.
    if (isset($readerdb) && $readerdb instanceof mysqli) {
        $sql = "SELECT rfa.title, rfa.url
                FROM rss_feeds_articles rfa
                INNER JOIN rss_feeds_url rfu ON rfa.feed_id = rfu.rss_id
                WHERE rfu.is_active = 1
                  AND rfa.title <> ''
                ORDER BY rfa.date DESC
                LIMIT 30";
        $res = $readerdb->query($sql);
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $articles[] = ['title' => $row['title'], 'url' => $row['url']];
            }
        }
    }

    // 2. Kannada news — working RSS sources (tested 2026-05-28).
    $kannadaFeeds = [
        'https://vijaykarnataka.com/rssfeedsdefault.cms',
        'https://kannada.oneindia.com/rss/feeds/kannada-news-fb.xml',
        'https://kannada.oneindia.com/rss/kannada-fb.xml',
        'https://www.prajavani.net/feed',
        'https://www.publictv.in/feed/',
    ];
    // Live RSS is now fetched at most once every $scrollerTtl seconds and cached
    // to disk. Fetching all five feeds inline on every request cost 12-14s per
    // page load (publictv.in alone answers in ~13s). Desktop browsers rode that
    // out, but the Android WebView app timed out and threw "Application Error:
    // the connection to the server was unsuccessful".
    //
    // Fresh cache  -> no network at all.
    // Stale cache  -> ONE request refreshes (non-blocking lock, so no stampede);
    //                 everyone else is served the stale copy instantly.
    // No cache yet -> fetch inline once, bounded by a total time budget.
    $scrollerCacheFile = __DIR__ . '/../../data/cache/news_scroller_kannada.json';
    $scrollerTtl       = 900;  // 15 minutes
    $scrollerBudget    = 6.0;  // max seconds any single request spends fetching

    if (!is_dir(dirname($scrollerCacheFile))) {
        @mkdir(dirname($scrollerCacheFile), 0777, true);
    }

    $scrollerCached = null;
    $scrollerAge    = PHP_INT_MAX;
    if (is_file($scrollerCacheFile)) {
        $rawCache = @file_get_contents($scrollerCacheFile);
        $decoded  = ($rawCache !== false) ? json_decode($rawCache, true) : null;
        if (is_array($decoded) && isset($decoded['items']) && is_array($decoded['items'])) {
            $scrollerCached = $decoded['items'];
            $scrollerAge    = time() - (int)($decoded['time'] ?? 0);
        }
    }

    if ($scrollerCached !== null && $scrollerAge < $scrollerTtl) {
        $kannadaArticles = $scrollerCached;
    } else {
        $lockFp  = @fopen($scrollerCacheFile . '.lock', 'c');
        $gotLock = ($lockFp !== false) && flock($lockFp, LOCK_EX | LOCK_NB);

        if ($gotLock || $scrollerCached === null) {
            $startedAt = microtime(true);
            foreach ($kannadaFeeds as $knFeed) {
                if ((microtime(true) - $startedAt) > $scrollerBudget) {
                    break;  // out of budget: keep whatever we already collected
                }
                $knItems = _scroller_fetch_rss($knFeed, 6);
                if (!empty($knItems)) {
                    $kannadaArticles = array_merge($kannadaArticles, $knItems);
                }
            }
            $kannadaArticles = array_slice($kannadaArticles, 0, 20);

            // Prefer fresh results; fall back to the previous copy so a failed
            // refresh never blanks the ticker. Always stamp the cache so a total
            // failure can't make every request retry the slow feeds.
            if (empty($kannadaArticles) && $scrollerCached !== null) {
                $kannadaArticles = $scrollerCached;
            }
            @file_put_contents(
                $scrollerCacheFile,
                json_encode(['time' => time(), 'items' => $kannadaArticles]),
                LOCK_EX
            );
        } else {
            $kannadaArticles = $scrollerCached;
        }

        if ($gotLock) { flock($lockFp, LOCK_UN); }
        if ($lockFp !== false) { fclose($lockFp); }
    }
    $kannadaArticles = array_slice($kannadaArticles, 0, 20);

    // 3. Interleave English (DB) and Kannada (live RSS) so both languages flow.
    if (!empty($kannadaArticles)) {
        $merged = [];
        $eCount = count($articles);
        $kCount = count($kannadaArticles);
        $eIdx = 0;
        $kIdx = 0;
        while ($eIdx < $eCount || $kIdx < $kCount) {
            if ($eIdx < $eCount) { $merged[] = $articles[$eIdx]; $eIdx++; }
            if ($kIdx < $kCount) { $merged[] = $kannadaArticles[$kIdx]; $kIdx++; }
        }
        $articles = $merged;
    }
} catch (Exception $e) {
    $articles = [];
}
?>

<style>
    .news-scroller-container {
        position: fixed;
        top: 95px;
        left: 0;
        right: 0;
        width: 100%;
        /* background: linear-gradient(90deg, #667eea 0%, #764ba2 100%); */
        padding: 12px 0;
        z-index: 8;
        /* box-shadow: 0 -2px 8px rgba(0, 0, 0, 0.1); */
        overflow: hidden;
    }

    .news-scroller-content {
        display: flex;
        gap: 30px;
        animation: scrollNews linear infinite;
        padding: 0 20px;
        white-space: nowrap;
    }

    .news-items {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 15px;
        background:#be0c08;
        border-radius: 20px;
        cursor: pointer;
        transition: all 0.3s ease;
        flex-shrink: 0;
        max-width: 400px;
        color: white;
    }

    .news-item:hover {
        background: rgba(255, 255, 255, 0.25);
        transform: translateY(-2px);
    }

    .news-item-dot {
        width: 8px;
        height: 8px;
        background: #fff;
        border-radius: 50%;
        flex-shrink: 0;
    }

    .news-item-text {
        /* color: white; */
        font-size: 13px;
        font-weight: 500;
        overflow: hidden;
        text-overflow: ellipsis;
        line-height: 1.4;
    }

    @keyframes scrollNews {
        0% {
            transform: translateX(100%);
        }
        100% {
            transform: translateX(-100%);
        }
    }

    .news-scroller-container.hidden {
        display: none;
    }

    /* Adjust footer position when scroller is visible */
    /* body.has-news-scroller{
        top: 80px;
    } */

    @media (max-width: 768px) {
        .news-scroller-container {
            /* display: none; */
        }
    }
</style>

<?php if (!empty($articles) && count($articles) > 0): ?>
    <div class="news-scroller-container">
        <div class="news-scroller-content">
            <?php
            // Repeat articles to create continuous scroll effect
            $repeatCount = 2;
            for ($repeat = 0; $repeat < $repeatCount; $repeat++):
                foreach ($articles as $article):
                    ?>
                    <div class="news-items" onclick="window.open('<?php echo htmlspecialchars($article['url'] ?? '#'); ?>', '_blank');" title="<?php echo htmlspecialchars($article['title'] ?? ''); ?>">
                        <div class="news-item-dot"></div>
                        <span class="news-item-text"><?php echo htmlspecialchars($article['title'] ?? ''); ?></span>
                    </div>
            <?php
                endforeach;
            endfor;
            ?>
        </div>
    </div>

    <script>
        // Add class to body to adjust footer position
        document.body.classList.add('has-news-scroller');

        // Adjust scroller animation duration based on content length
        const scroller = document.querySelector('.news-scroller-content');
        if (scroller) {
            const contentWidth = scroller.scrollWidth;
            const duration = (contentWidth / 100) * 0.12; // Faster speed
            scroller.style.animationDuration = duration + 's';
        }
    </script>
<?php endif; ?>
