<?php
require_once '../inc/config.php';
require_once '../inc/simplepie/autoloader.php';
include 'processvoice.php';


$sql1 = "SELECT feed_url FROM feeds_cream LIMIT 10";
$result1 = mysqli_query($db, $sql1);
while ($row = mysqli_fetch_assoc($result1)) {
    $arrays[] = $row['feed_url'];
}
$feeds = new SimplePie();

$feeds->set_feed_url($arrays);
$feeds->set_cache_location('../inc/simplepie/library/SimplePie/Cache');
$feeds->init();
$feeds->handle_content_type();

$headlines = '';  // Initialize an empty string to store headlines
$count = 1;  // Initialize a counter

foreach ($feeds->get_items() as $item) {
    if ($count > 10) {
        break;  // Exit the loop once 10 items have been added
    }
    
    $feedTitle = $item->get_title();
    $headlines .= "\n".$feedTitle;  // Append the title and newline character
    
    $count++;  // Increment the counter
}

texttovoice($gUserId, $timestamp, $headlines);


?>