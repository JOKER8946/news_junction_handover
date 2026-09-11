<?

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
        $gUserPincode = $userData['userPincode'];
        // if ($userData['userPlan'] == "Pro") {
        //     $gUserPlan = 1;
        // } else {
        //     $gUserPlan = 1;
        // }
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
    $gUserPincode = $_SESSION['userPincode'];
    // if ($_SESSION['userPlan'] == "Pro" || $_SESSION['userPlan'] == "Trial") {
    //     $gUserPlan = 1;
    // } else {
    //     $gUserPlan = 1;
    // }
} else {
    ?>
    <script>
        window.location.assign('/sign-in.php');
    </script>
<?
    exit();
}

// Admin override: users with role='admin' bypass all plan-based paywalls.
if (isset($gUserId)) {
    if (!isset($creamdb)) {
        require_once __DIR__ . '/db_config.php';
    }
    if (isset($creamdb)) {
        $stmt = $creamdb->prepare("SELECT role FROM nj_cream.user WHERE id = ? LIMIT 1");
        if ($stmt) {
            $uid = (int)$gUserId;
            $stmt->bind_param("i", $uid);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res && $row = $res->fetch_assoc()) {
                if ($row['role'] === 'admin') {
                    $gUserPlan = 1;
                    $gIsAdmin = true;
                }
            }
            $stmt->close();
        }
    }
}
if (!isset($gIsAdmin)) { $gIsAdmin = false; }
