<?
ini_set('display_startup_errors', 1);

include 'function.php';
include 'db_connect.php';

$gUserId = 999;
$pageId = $_GET['pageId'];

echo "<pre>";
print_r($_POST);
echo "</pre>";

// Long-Lived Access Token
// $longLivedToken = 'EAAGvb6fjh0YBO32eFQzgo93fTwfvZAJRs5iMCSbLDKB9cZCRmS29Gz1wiVUjrMoevJHkozCo02icx6WhxYrGYA9pktT3mVrUsKnfAMLZCHhQn5UhVYSC925PZA3dm2d8MiVb5DiHpXF04XmgUuwvFAmDZBPMhv5ZBIvtqs7IOrohHK5AWtIT2ozGBmZC2QEfC7y2DcAJwZDZD';

// Facebook App Credentials
$credentials = json_decode(file_get_contents('credential.json'), true);
// $pageAccessToken = $credentials['longLivedToken']; // This should be the page access token

$pageAccessToken = facebook_fetch_page_token($db, $gUserId, $pageId);

// URL to post to the page
$postUrl = 'https://graph.facebook.com/v16.0/me/feed';

// Check if form data is posted
if (isset($_POST['message']) && isset($_POST['action'])) {
    $message = $_POST['message'];

    if (isset($_POST['link'])) {
        $link = $_POST['link'];
    } else {
        $link = '';
    }

    $scheduleTimestamp = null;

    if ($_POST['action'] == 'post_now') {
        $response = facebook_post_to_page($message, $link);
        echo $response['message'];
    } else if ($_POST['action'] == 'schedule_post') {

        $scheduleDate = $_POST['scheduleDate'];
        $scheduleTime = $_POST['scheduleTime'];

        // Get the submitted date and time
        // Combine date and time into a single string
        $dateTimeString = $scheduleDate . ' ' . $scheduleTime;

        // Create a DateTime object in IST
        $istTimezone = new DateTimeZone('Asia/Kolkata');
        $localDateTime = new DateTime($dateTimeString, $istTimezone);

        // Get the Unix timestamp
        $scheduleTimestamp = $localDateTime->getTimestamp();
        echo $scheduleTimestamp . "<br>";

        $response = facebook_schedule_post_to_page($message, $link, $scheduleTimestamp);

        echo $response['message'];
    }
} else {
    // Display the form for posting a message
?>
    <!-- <form action="" method="POST">
        <textarea name="message" placeholder="Write your message here..." required></textarea>
        <textarea name="link" placeholder="Enter the link to be shared..."></textarea>
        <button type="submit">Post</button>
    </form> -->

    <form id="postForm" action="" method="POST">
        <textarea name="message" placeholder="Write your message here..." required></textarea>
        <textarea name="link" placeholder="Enter the link to be shared..."></textarea>

        <div>
            <input type="radio" id="postNow" name="action" value="post_now" checked onclick="this.form.scheduleDate.value=''; this.form.scheduleTime.value='';" />
            <label for="postNow">POST NOW</label>

            <input type="radio" id="schedulePost" name="action" value="schedule_post" onclick="this.form.scheduleDate.value=''; this.form.scheduleTime.value='';" />
            <label for="schedulePost">SCHEDULE POST</label>
        </div>

        <div id="scheduleOptions">
            <label for="scheduleDate">Select date:</label>
            <input type="date" id="scheduleDate" name="scheduleDate">

            <label for="scheduleTime">Select time:</label>
            <input type="time" id="scheduleTime" name="scheduleTime">
        </div>

        <button type="submit">Submit</button>
    </form>
<?php
}
?>