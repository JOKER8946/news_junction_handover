<?
//	Current Science: Check if logged in
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (isset($_COOKIE['knobly_user_data'])) {
    // Retrieve and decode the user data from the cookie
    $userData = json_decode($_COOKIE['knobly_user_data'], true);
    if ($userData) {
        $gUserId = $userData['userId'];
        $gUserPlan = $userData['userPlan'];
        $gUserName = $userData['userName'];
        $gUserEmail = $userData['userEmail'];
        $gUserSubdomain = $userData['userSubdomain'];
    } else {
?>
        <script>
            window.location.assign('/sign-in.php');
        </script>
    <?
        exit();
    }
} else if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in']) {
    $gUserId = $_SESSION['userId'];
    $gUserPlan = $_SESSION['userPlan'];
    $gUserName = $_SESSION['userName'];
    $gUserEmail = $_SESSION['userEmail'];
    $gUserSubdomain = $_SESSION['userSubdomain'];
} else {
    ?>
    <script>
        window.location.assign('/sign-in.php');
    </script>
<?
    exit();
}
