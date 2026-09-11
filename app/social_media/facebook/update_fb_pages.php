<?
include 'db_connect.php';
include 'fb_function.php';
include '../../inc/validate.logged.php';

if (isset($_POST['page_token']) && isset($_POST['page_id'])) {
    echo $_POST['page_id']. "<br>";
    echo $_POST['page_token']. "<br>";
    $longLivedToken = facebook_generate_long_live_token($db, $_POST['page_id'], $_POST['page_token']);
}
?>