<?php
require_once './inc/php/db_config.php';
require_once './inc/php/validate.logged.php';
require_once './inc/php/function.php';

$user_feed_id = isset($_GET['user_feed_id']) ? intval($_GET['user_feed_id']) : null;
$feedId = isset($_GET['feed_id']) ? intval($_GET['feed_id']) : null;

function guardianImg($image)
{
    $position = strpos($image, '.jpg');
    if ($position === false) return $image;

    // Remove any existing query parameters by splitting the URL at the '?' and keeping the base part
    $baseUrl = substr($image, 0, $position + 4);
    // Append the new query parameters
    $modimg = $baseUrl . '?width=620&dpr=1&s=none';
    return $modimg;
}

function get_user_feed_info($creamdb, $user_feed_id)
{
    $stmt = $creamdb->prepare("SELECT feed_url FROM user_feeds WHERE id = ?");
    $stmt->bind_param("i", $user_feed_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $stmt->close();
        return $row['feed_url'];
    }

    $stmt->close();
    return null;
}

function parse_rss_feed($feed_url)
{
    // Enable user error handling for libxml
    libxml_use_internal_errors(true);

    // Create a context with options for handling different feed types
    $context = stream_context_create([
        'http' => [
            'timeout' => 30,
            'user_agent' => 'Mozilla/5.0 (compatible; RSS Reader/1.0)',
            'header' => "Accept: application/rss+xml, application/atom+xml, application/xml, text/xml"
        ]
    ]);

    // Load the RSS feed
    $rss_content = @file_get_contents($feed_url, false, $context);

    if ($rss_content === false) {
        return false;
    }

    // Try to parse as XML
    $xml = @simplexml_load_string($rss_content, 'SimpleXMLElement', LIBXML_NOCDATA);

    if ($xml === false) {
        return false;
    }

    $articles = [];

    // Check if it's RSS 2.0 or RSS 1.0
    if (isset($xml->channel->item)) {
        $feed_title = (string)$xml->channel->title;
        $feed_image = isset($xml->channel->image->url) ? (string)$xml->channel->image->url : '';

        foreach ($xml->channel->item as $item) {
            $article = [
                'title' => (string)$item->title,
                'description' => (string)$item->description,
                'url' => (string)$item->link,
                'date' => isset($item->pubDate) ? date('Y-m-d H:i:s', strtotime((string)$item->pubDate)) : date('Y-m-d H:i:s'),
                'image' => '',
                'feed_title' => $feed_title,
                'feed_image' => $feed_image
            ];

            // Try to extract image from description or content
            $description = (string)$item->description;
            if (isset($item->children('content', true)->encoded)) {
                $description = (string)$item->children('content', true)->encoded;
            }

            // Look for images in the content
            if (preg_match('/<img[^>]+src=["\']([^"\']+)["\'][^>]*>/i', $description, $matches)) {
                $article['image'] = $matches[1];
            }

            // Check for media:thumbnail or enclosure
            if (isset($item->children('media', true)->thumbnail)) {
                $article['image'] = (string)$item->children('media', true)->thumbnail->attributes()->url;
            } elseif (isset($item->enclosure) && strpos((string)$item->enclosure->attributes()->type, 'image') !== false) {
                $article['image'] = (string)$item->enclosure->attributes()->url;
            }

            $articles[] = $article;
        }
    }
    // Check if it's Atom feed
    elseif (isset($xml->entry)) {
        $feed_title = (string)$xml->title;
        $feed_image = '';

        foreach ($xml->entry as $entry) {
            $article = [
                'title' => (string)$entry->title,
                'description' => isset($entry->summary) ? (string)$entry->summary : (string)$entry->content,
                'url' => '',
                'date' => isset($entry->published) ? date('Y-m-d H:i:s', strtotime((string)$entry->published)) : date('Y-m-d H:i:s'),
                'image' => '',
                'feed_title' => $feed_title,
                'feed_image' => $feed_image
            ];

            // Get the link
            if (isset($entry->link)) {
                if (is_object($entry->link) && isset($entry->link->attributes()->href)) {
                    $article['url'] = (string)$entry->link->attributes()->href;
                } else {
                    $article['url'] = (string)$entry->link;
                }
            }

            // Try to extract image from content
            $content = isset($entry->content) ? (string)$entry->content : (string)$entry->summary;
            if (preg_match('/<img[^>]+src=["\']([^"\']+)["\'][^>]*>/i', $content, $matches)) {
                $article['image'] = $matches[1];
            }

            $articles[] = $article;
        }
    }

    return $articles;
}

function fetch_articles($creamdb, $user_feed_id)
{
    if ($user_feed_id === null) {
        echo '<p>Error: User Feed ID not provided.</p>';
        exit;
    }

    // Get the feed URL from user_feeds table
    $feed_url = get_user_feed_info($creamdb, $user_feed_id);

    if (!$feed_url) {
        echo '<p>Error: Feed URL not found.</p>';
        exit;
    }

    // Parse the RSS feed
    $articles = parse_rss_feed($feed_url);

    if ($articles === false) {
        echo '<p>Error: Could not fetch or parse the RSS feed.</p>';
        exit;
    }

    if (empty($articles)) {
        echo '<p>No articles found in the RSS feed.</p>';
        exit;
    }

    // Display the feed title
    $feed_title = !empty($articles[0]['feed_title']) ? $articles[0]['feed_title'] : 'RSS Feed';
?>
    <h1 class="text-center mb-4 text-white"><?= htmlspecialchars($feed_title) ?></h1>
    <?php

    $article_count = 0;
    foreach ($articles as $index => $article) {
        if ($article_count >= 100) break; // Limit to 100 articles

        $feedId = $index + 1; // Use index as feed ID for modal
        $title = htmlspecialchars(strip_tags($article['title']));
        $description = htmlspecialchars(strip_tags($article['description']));
        $date = htmlspecialchars($article['date']);
        $url = htmlspecialchars($article['url']);

        // Determine image source
        $image = !empty($article['image']) ? $article['image'] : $article['feed_image'];

        // Default placeholder image if no image is available
        $defaultImage = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzAwIiBoZWlnaHQ9IjIwMCIgdmlld0JveD0iMCAwIDMwMCAyMDAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxyZWN0IHdpZHRoPSIzMDAiIGhlaWdodD0iMjAwIiBmaWxsPSIjZjNmNGY2Ii8+CjxwYXRoIGQ9Ik05MCA5MEM5MCA4NS4wMjk0IDk0LjAyOTQgODEgOTkgODFIMjAxQzIwNS45NzEgODEgMjEwIDg1LjAyOTQgMjEwIDkwVjExMEMyMTAgMTE0Ljk3MSAyMDUuOTcxIDExOSAyMDEgMTE5SDk5Qzk0LjAyOTQgMTE5IDkwIDExNC45NzEgOTAgMTEwVjkwWiIgZmlsbD0iI2U1ZTdlYiIvPgo8cGF0aCBkPSJNMTIwIDEwMEMxMjAgMTAyLjc2MSAxMTcuNzYxIDEwNSAxMTUgMTA1QzExMi4yMzkgMTA1IDExMCAxMDIuNzYxIDExMCAxMDBDMTEwIDk3LjIzODYgMTEyLjIzOSA5NSAxMTUgOTVDMTE3Ljc2MSA5NSAxMjAgOTcuMjM4NiAxMjAgMTAwWiIgZmlsbD0iI2M5Y2NkMSIvPgo8cGF0aCBkPSJNMTMwIDEwNUwxNDAgOTVMMTcwIDEwNUwxODAgOTVMMTkwIDEwNVYxMTBIMTMwVjEwNVoiIGZpbGw9IiNjOWNjZDEiLz4KPHRleHQgeD0iMTUwIiB5PSIxNDAiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGZpbGw9IiM5Y2EzYWYiIGZvbnQtZmFtaWx5PSJBcmlhbCwgc2Fucy1zZXJpZiIgZm9udC1zaXplPSIxNCIgZm9udC13ZWlnaHQ9IjUwMCI+Tm8gSW1hZ2UgQXZhaWxhYmxlPC90ZXh0Pgo8L3N2Zz4K';

        // If no image is available, use default placeholder
        if (empty($image)) {
            $image = $defaultImage;
        }

        // Validate image URL only if it's not the default placeholder
        if ($image !== $defaultImage && !filter_var($image, FILTER_VALIDATE_URL)) {
            $image = $defaultImage;
        }

        $article_count++;
    ?>
        <div class="col-md-4">
            <div class="card mb-4 news-item"
                data-id="<?= htmlspecialchars($feedId) ?>"
                data-title="<?= $title ?>"
                data-description="<?= $description ?>"
                data-image="<?= htmlspecialchars($image) ?>"
                data-url="<?= $url ?>"
                data-date="<?= $date ?>">
                <div class="card-img-container">
                    <img src="<?= htmlspecialchars($image) ?>" class="card-img-top" alt="News Image" onerror="this.src='<?= $defaultImage ?>'; this.onerror=null;">
                </div>
                <div class="card-body">
                    <h5 class="card-title"><?= $title ?></h5>
                    <p class="card-text"><?= strlen($description) > 100 ? substr($description, 0, 100) . '...' : $description ?></p>
                    <p class="card-text"><strong>Date: </strong><?= $date ?></p>
                </div>
            </div>
        </div>
<?php
    }

    if ($article_count == 0) {
        echo '<p>No articles found in the RSS feed.</p>';
    }

    // Close the database connection
    $creamdb->close();
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RSS Feed Reader</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="inc/css/social.css">
    <link rel="stylesheet" href="inc/css/reader.css">
    <link rel="icon" type="image/x-icon" href="../grfx/img/logo.ico">

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        .card-body {
            background-color: var(--bg-card);
        }

        .btn {
            color: var(--text-primary) !important;
        }

        .modal-content {
            position: relative;
            display: flex;
            flex-direction: column;
            width: 100%;
            color: var(--text-primary);
            pointer-events: auto;
            background-color: var(--bg-main);
            background-clip: padding-box;
            border: var();
            outline: 0;
        }

        .text-white {
            color: var(--text-primary) !important;
        }

        .btn-primary {
            background-color: var(--primary);
            color: #fff;
            border: none;
        }

        .news-item {
            padding: 0px;
            background-color: var(--bg-main);
        }

        .error-message {
            color: #dc3545;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            padding: 10px;
            border-radius: 4px;
            margin: 10px 0;
        }

        .card-img-container {
            position: relative;
            height: 200px;
            overflow: hidden;
        }

        .card-img-top {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .card-title {
            font-size: 1.1rem;
            font-weight: 600;
            line-height: 1.3;
            margin-bottom: 0.5rem;
        }

        .card-text {
            font-size: 0.9rem;
            line-height: 1.4;
            margin-bottom: 0.5rem;
        }

        .news-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            transition: all 0.3s ease;
        }

        .news-item {
            cursor: pointer;
            transition: all 0.3s ease;
        }
    </style>
</head>

<body>
    <?php include 'inc/php/social_navbar.php' ?>
    <div class="reader-main-content main-content">
        <?php include 'inc/php/social_sidebar.php' ?>
        <div class="row" id="news-container">
            <?php fetch_articles($creamdb, $user_feed_id) ?>
        </div>
        <!-- Modal -->
        <div class="modal fade" id="newsModal" tabindex="-1" aria-labelledby="newsModalLabel" aria-hidden="true" style=" top:70px">
            <div class="modal-dialog modal-lg" style="margin-bottom: 110px;">
                <div class="modal-content">
                    <div class="modal-header w-full flex justify-between items-center gap-4">
                        <h5 class="model-title " id="newsModalLabel"></h5>
                        <button type="button" class="btn-close w-full w-[1/5] flex items-center justify-center color-red" data-bs-dismiss="modal" aria-label="Close" style="background-color: #a3a2a2;"></button>
                    </div>
                    <div class="modal-body">
                        <div class="modal-img-container mb-3">
                            <img src="" id="modal-image" class="img-fluid" alt="News Image" style="max-height: 300px; width: 100%; object-fit: cover;">
                        </div>
                        <p id="modal-description"></p>
                        <p class="datewithtime"><strong>Date: </strong><span id="modal-date"></span></p>
                        <div class="morewithlike d-flex justify-content-between">
                            <a href="" id="modal-url" class="btn btn-primary" target="_blank">Read More</a>
                            <div id="dataModal" data-id="" data-title="" data-description="" data-url=""></div>
                            <div class="data col-12 col-md-6 text-md-right pl-0 mt-2 mt-md-0 d-flex ">
                                <button class="btn reader-button likeButton">
                                    <i id="thumbsUp" class="fa-regular fa-thumbs-up" style="padding-right: 4px; padding-top: 2px;"></i>
                                    <div id="likeCount"></div>
                                </button>
                                <button class="btn p-2  reader-button icon-container" style="margin-bottom: 0px;">
                                    <i class="far fa-bookmark" id="bookmarkIcon"></i>
                                </button>
                                <button class="btn p-2 reader-button copyButton">
                                    <i class="fa-solid fa-arrow-up-from-bracket"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include "inc/php/footer.php"; ?>

    <script>
        const userId = <?= $gUserId ?>;
        $(document).ready(function() {
            $('.news-item').on('click', function() {
                var feed_id = $(this).data('id');
                var title = $(this).data('title');
                var description = $(this).data('description');
                var image = $(this).data('image');
                var date = $(this).data('date');
                var url = $(this).data('url');

                $('#dataModal').attr('data-id', feed_id);
                $('#dataModal').attr('data-title', title);
                $('#newsModalLabel').text(title);
                $('#modal-description').text(description);
                $('#modal-image').attr('src', image);
                $('#modal-date').text(date);
                $('#modal-url').attr('href', url);

                // Note: Like and bookmark functionality will need to be adapted for RSS feeds
                // checkCollection(feed_id, userId)

                var modal = new bootstrap.Modal($('#newsModal')[0]);
                modal.show();
            });

            $('.copyButton').on('click', function() {
                var feed_url = $('#modal-url').attr('href');
                copyToClipboard(feed_url);
            });

            // Note: Like and bookmark functionality commented out as it requires database integration
            /*
            $('.likeButton').on('click', function() {
                var feed_id = $('#dataModal').attr('data-id');
                toggleLike(feed_id, userId);
            });

            $('#bookmarkIcon').on('click', function() {
                var feed_id = $('#dataModal').attr('data-id');
                if ($(this).hasClass('far fa-bookmark')) {
                    addToCollection(feed_id, userId);
                } else {
                    removeFromCollection(feed_id, userId);
                }
            });
            */
        });

        function copyToClipboard(note) {
            var textToCopy = note + '\n\nshared via newsjunction.net';

            if (navigator.clipboard) {
                navigator.clipboard.writeText(textToCopy).then(function() {
                    alert('Link copied to clipboard!');
                }).catch(function(error) {
                    console.error('Clipboard API error: ', error);
                    fallbackCopy(textToCopy);
                });
            } else {
                fallbackCopy(textToCopy);
            }

            function fallbackCopy(textToCopy) {
                var $tempTextArea = $('<textarea>');
                $tempTextArea.val(textToCopy).appendTo('body');
                $tempTextArea.focus().select();
                $tempTextArea[0].setSelectionRange(0, textToCopy.length);

                try {
                    var successful = document.execCommand('copy');
                    if (successful) {
                        alert('Link copied to clipboard!');
                    } else {
                        alert('Failed to copy link.');
                    }
                } catch (err) {
                    console.error('Error copying text: ', err);
                    alert('Failed to copy link.');
                } finally {
                    $tempTextArea.remove();
                }
            }
        }
    </script>
</body>

</html>