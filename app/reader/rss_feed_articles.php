<?php

require_once(__DIR__ . '/../inc/simplepie/autoloader.php');
require_once(__DIR__ . '/../inc/php/db_config.php');


$sql = 'SELECT rss_id, rss_url
FROM rss_feeds_url
WHERE is_active = 1
ORDER BY rss_id;';
$result = $readerdb->query($sql);

// Check if the query was successful
if (!$result) {
    die('Query Error: ' . $readerdb->error);
}

// Loop through each RSS feed URL
while ($row = $result->fetch_assoc()) {

    $feedUrl = $row['rss_url'];
    $feedId = $row['rss_id'];
    //echo $feedUrl . "<br>" . "<hr>" ;

    // // Initialize SimplePie
    // $rss = new SimplePie\SimplePie();
    // $rss->set_feed_url($feedUrl);
    // $rss->init();
    // $rss->handle_content_type();
    //  // Disable caching
    //  $rss->enable_cache(false);


    $rss = new SimplePie();
    $rss->set_feed_url($feedUrl);
    $rss->enable_cache(false);
    $rss->init();
    $rss->handle_content_type();


    // Check for errors
    if ($rss->error()) {
        echo "Error fetching feed: " . $rss->error();
        continue;
    }

    // Loop through each item in the RSS feed
    foreach ($rss->get_items() as $item) {

        $title = $readerdb->real_escape_string(html_entity_decode($item->get_title() ?? '', ENT_QUOTES | ENT_XML1, 'UTF-8'));
        $url = $readerdb->real_escape_string($item->get_permalink() ?? '');
        $description = $readerdb->real_escape_string(html_entity_decode($item->get_description() ?? '', ENT_QUOTES | ENT_XML1, 'UTF-8'));
        $content = $readerdb->real_escape_string(html_entity_decode($item->get_content() ?? '', ENT_QUOTES | ENT_XML1, 'UTF-8'));
        $author = $readerdb->real_escape_string(html_entity_decode($item->get_author()?->get_name() ?? '', ENT_QUOTES | ENT_XML1, 'UTF-8'));

        $image = '';

        // // Handle enclosure tag
        // if ($item->get_enclosure()) {
        //     $image = $readerdb->real_escape_string($item->get_enclosure()->get_link());
        // } else {
        //     // Handle media:thumbnail tag
        //     $namespaces = $item->get_namespaces(true);
        //     if (isset($namespaces['media'])) {
        //         $media_elements = $item->get_elements_by_tag_name('media:group');
        //         if (!empty($media_elements)) {
        //             $image = $readerdb->real_escape_string($media_elements[0]->get_attribute('url'));
        //         }
        //     }
        // }
        // 
        // // Do something with the $image

        // Handle enclosure tag
        if ($item->get_enclosure()) {
            $image = $readerdb->real_escape_string($item->get_enclosure()->get_link());
            // echo "encloser; " . $image . "<br>"; 
        } else {
            // Handle media:thumbnail within media:group
            $namespaces = $item->get_namespaces(true);
            if (isset($namespaces['media'])) {
                $media_groups = $item->get_elements_by_tag_name('media:group');
                if (!empty($media_groups)) {
                    // Find media:thumbnail within media:group
                    $media_thumbnails = $media_groups[0]->get_elements_by_tag_name('media:thumbnail');
                    if (!empty($media_thumbnails)) {
                        $image = $readerdb->real_escape_string($media_thumbnails[0]->get_attribute('url'));
                        // echo "media; " . $image . "<br>"; 
                    }
                }
            }
            // else echo "not running";
        }

        //$image = $readerdb->real_escape_string($item->get_enclosure() ? $item->get_enclosure()->get_link() : '');
        $date = $readerdb->real_escape_string($item->get_date('Y-m-d H:i:s'));

        // Process categories
        $categories = $item->get_categories();
        if ($categories && is_array($categories)) {
            $categoryNames = array_map(function ($cat) {
                return htmlspecialchars($cat->get_label());
            }, $categories);
            $categoryString = implode(', ', $categoryNames);
        } else {
            $categoryString = ''; // Or you can set it to an empty string ''
        }

        // Prepare SQL to insert article into the database
        $sql = 'INSERT IGNORE INTO rss_feeds_articles (feed_id, url, title, description, content, author, image, category, date) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)';
        $stmt = $readerdb->prepare($sql);
        $stmt->bind_param('issssssis', $feedId, $url, $title, $description, $content, $author, $image, $categoryString, $date);

        // Execute the query
        if (!$stmt->execute()) {
            echo "Error inserting article: " . $stmt->error;
        }

        $stmt->close();
    }
}

// Close the database connection
$readerdb->close();

echo "Articles Updated Successfully at " . date('Y-m-d H:i:s') . "\n";
