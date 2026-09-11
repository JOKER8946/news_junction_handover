<?php
// Cream: Community Feeds

require_once '../inc/validate.logged.php';
require_once '../inc/config.php';
$act = '';
if (!empty($_POST)) $act = isset($_POST["act"]) ? $_POST["act"] : '';

// Function to truncate the description to a certain number of words
function truncateDescription($description, $limit = 25)
{
    $words = explode(' ', $description);
    if (count($words) > $limit) {
        return implode(' ', array_slice($words, 0, $limit)) . '...';
    } else {
        return $description;
    }
}