<?
$servername = "localhost";
$dbname = "cream";
$username = "YOUR_DB_USER";
$password = "YOUR_DB_PASSWORD";

$db = new mysqli($servername, $username, $password, $dbname);
if ($db->connect_error) {
   die("Connection failed: " . $db->connect_error);
}
function check_cream_subscription($userId)
{
    global $db;
    $sql = "SELECT plan, plan_type FROM cream_subscription WHERE userId = ? AND NOW() BETWEEN start_date AND end_date";
    if ($stmt = $db->prepare($sql)) {
        $stmt->bind_param("i", $userId);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                return $row['plan'];
            } else {
                return "Free";
            }
        }
    }
}


if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (isset($_COOKIE['knobly_user_data'])) {
    // Retrieve and decode the user data from the cookie
    $userData = json_decode($_COOKIE['knobly_user_data'], true);
    if ($userData) {
        $gUserId = $userData['userId'];
        // $gUserPlan = $userData['userPlan'];
        $gUserName = $userData['userName'];
        $gUserEmail = $userData['userEmail'];
        $gUserSubdomain = $userData['userSubdomain'];
        if (check_cream_subscription($gUserId)=="Pro"){
            $gUserPlan = 1;
        } else {
            $gUserPlan = 0;
        }
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
    // $gUserPlan = $_SESSION['userPlan'];
    $gUserName = $_SESSION['userName'];
    $gUserEmail = $_SESSION['userEmail'];
    $gUserSubdomain = $_SESSION['userSubdomain'];
    if (check_cream_subscription($gUserId)=="Pro"){
        $gUserPlan = 1;
    } else {
        $gUserPlan = 0;
    }
} else {
    ?>
    <script>
        window.location.assign('/sign-in.php');
    </script>
<?
    exit();
}
