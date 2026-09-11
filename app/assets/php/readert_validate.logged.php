<?
//	Current Science: Check if logged in

session_start();

if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in']) {
    $gUserId = $_SESSION['id'];
    $gUserName = $_SESSION['user_name'];
    $gUserEmail = $_SESSION['user_email'];
    //  $gUserPlan = $_SESSION['userPlan'];
    // $gUserSubdomain = $_SESSION['userSubdomain'];
} else {
?>
    <script>
        window.location.assign('/new_reader/sign-in.php');
    </script>
<?
    exit();
}
