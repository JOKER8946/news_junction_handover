<?
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
$gLogStatus = false;

function check_login_status(&$gLogStatus)
{
    if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in']) {
        $gLogStatus = true;
    } elseif (isset($_COOKIE['knobly_user_data'])) {
        $gLogStatus = true;
    } else {
        $gLogStatus = false;
    }
}

check_login_status($gLogStatus);
