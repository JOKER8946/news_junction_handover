<?php
date_default_timezone_set('Asia/Kolkata');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['manual_post'])) {
    // Function to remove emojis and other non-alphanumeric characters
    function remove_emojis_and_symbols($text) {
        // Remove emojis
        $text = preg_replace('/[\x{1F600}-\x{1F64F}\x{1F300}-\x{1F5FF}\x{1F680}-\x{1F6FF}\x{1F1E0}-\x{1F1FF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}\x{2300}-\x{23FF}\x{2B50}\x{2B06}\x{2934}\x{2935}\x{3030}\x{303D}\x{3297}\x{3299}\x{FE0F}\x{2122}\x{2139}\x{2194}-\x{2199}\x{21A9}-\x{21AA}\x{2B05}\x{2B06}\x{2B07}\x{2B1B}\x{2B1C}\x{2B50}\x{2B55}\x{231A}\x{231B}\x{2328}\x{23CF}\x{23E9}-\x{23F3}\x{23F8}-\x{23FA}\x{24C2}\x{25AA}\x{25AB}\x{25B6}\x{25C0}\x{25FB}-\x{25FE}\x{2600}-\x{2604}\x{260E}\x{2611}\x{2614}\x{2615}\x{2618}\x{261D}\x{2620}\x{2622}\x{2623}\x{2626}\x{262A}\x{262E}\x{262F}\x{2638}-\x{263A}\x{2640}\x{2642}\x{2648}-\x{2653}\x{2660}\x{2663}\x{2665}\x{2666}\x{2668}\x{267B}\x{267F}\x{2692}-\x{2697}\x{2699}\x{269B}\x{269C}\x{26A0}\x{26A1}\x{26AA}\x{26AB}\x{26B0}\x{26B1}\x{26BD}\x{26BE}\x{26C4}\x{26C5}\x{26C8}\x{26CE}\x{26CF}\x{26D1}\x{26D3}\x{26D4}\x{26E9}\x{26EA}\x{26F0}-\x{26F5}\x{26F7}-\x{26FA}\x{26FD}\x{2705}\x{2708}-\x{270D}\x{270F}\x{2712}\x{2714}\x{2716}\x{271D}\x{2721}\x{2728}\x{2733}\x{2734}\x{2747}\x{274C}\x{274E}\x{2753}\x{2754}\x{2755}\x{2757}\x{275F}\x{2763}\x{2764}\x{2795}-\x{2797}\x{27A1}\x{27B0}\x{27BF}\x{2934}\x{2935}\x{2B05}-\x{2B07}\x{2B1B}\x{2B1C}\x{2B50}\x{2B55}\x{3030}\x{303D}\x{3297}\x{3299}\x{1F000}-\x{1F02F}\x{1F0A0}-\x{1F0FF}\x{1F100}-\x{1F6FF}\x{1F700}-\x{1F77F}\x{1F780}-\x{1F7FF}\x{1F800}-\x{1F8FF}\x{1F900}-\x{1F9FF}\x{1FA00}-\x{1FA6F}\x{1FA70}-\x{1FAFF}\x{200D}]/u', '', $text);
        // Remove other common symbols that might be considered "icons" or unwanted characters
        $text = preg_replace('/[^\p{L}\p{N}\s]/u', '', $text);
        return $text;
    }

    $caption = strip_tags($_POST['caption'] ?? '');
    $caption = remove_emojis_and_symbols($caption);
    $hashtag = strip_tags($_POST['hashtag'] ?? '');
    $hashtag = remove_emojis_and_symbols($hashtag);

    $new_post = [
        'type' => 'manual',
        'topic' => $_POST['topic'] ?? 'manual',
        'caption' => $caption,
        'hashtag' => $hashtag,
        'image' => '',
        'image_desc' => '',
        'detail_caption' => '',
        'bot_name' => $_POST['user'] ?? 'Manual Post',
        'avatar' => 'user_images/' . ($_POST['user'] ?? 'Ananya') . '.jpg',
        'feed_source' => 'Manual',
        'created_at' => date('Y-m-d H:i:s')
    ];

    if (isset($_FILES['media']) && $_FILES['media']['error'] == 0) {
        $upload_dir = 'user_images/';
        $upload_file = $upload_dir . basename($_FILES['media']['name']);
        if (move_uploaded_file($_FILES['media']['tmp_name'], $upload_file)) {
            $new_post['image'] = $upload_file;
        }
    }

    $posts_file = 'posts.json';
    $posts = [];
    if (file_exists($posts_file)) {
        $posts = json_decode(file_get_contents($posts_file), true) ?: [];
    }
    array_unshift($posts, $new_post);
    file_put_contents($posts_file, json_encode($posts, JSON_PRETTY_PRINT));

    // Redirect to avoid form resubmission
    header("Location: index.php");
    exit();
}
?>