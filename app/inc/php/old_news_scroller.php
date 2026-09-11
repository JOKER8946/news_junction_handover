<?php
// Fetch news from RSS feed API
$articles = [];
try {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://newsjunction.net/api/articles.php?rss_id=134');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    if ($response !== false) {
        $articles = json_decode($response, true);
        if (!is_array($articles)) {
            $articles = [];
        }
    }
    curl_close($ch);
} catch (Exception $e) {
    $articles = [];
}
?>

<style>
    .news-scroller-container {
        position: fixed;
        top: 55px;
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
            const duration = (contentWidth / 100) * 0.05; // Fast speed
            scroller.style.animationDuration = duration + 's';
        }
    </script>
<?php endif; ?>
