<?php
// filter_posts.php

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$selected_topic = $data['topic'] ?? null;

$posts = [];
if (file_exists('posts.json')) {
    $posts = json_decode(file_get_contents('posts.json'), true) ?: [];
}

if (!$selected_topic) {
    echo json_encode($posts);
    exit;
}

$topic_feed_map = [];

$filtered_posts = array_filter($posts, function($post) use ($selected_topic, $topic_feed_map) {
    $post_feed_name = $post['feed_source'] ?? '';
    if (empty($post_feed_name)) return false;

    if (isset($topic_feed_map[$selected_topic])) {
        foreach ($topic_feed_map[$selected_topic] as $feed_url) {
            if (stripos($feed_url, $post_feed_name) !== false) {
                return true;
            }
        }
    }
    return false;
});

echo json_encode(array_values($filtered_posts));
