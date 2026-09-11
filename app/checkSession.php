<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

/**
 * Send a logged-in visitor to the stream.
 *
 * This used to be `echo "<script>window.location.assign('/stream.php');</script>"`,
 * which made index.php answer 200 with a 55-byte body and no markup. A desktop
 * browser hides that behind a fast JS hop, but the Android WebView app painted
 * it as a blank screen — and because reaching stream.php was a *second*,
 * JS-driven navigation, any network hiccup tripped onReceivedError and threw
 * "Application Error: the connection to the server was unsuccessful".
 *
 * A real 302 removes both failure modes: no empty frame to paint, and one hop
 * instead of two. The echo path is kept only as a fallback for callers that
 * have already started output (header() would fatal there).
 */
function nj_redirect_to_stream(): void
{
    if (!headers_sent()) {
        header('Location: /stream.php', true, 302);
        exit();
    }
    // meta refresh covers JS-disabled clients; the script tag covers the rest.
    echo '<!DOCTYPE html><meta http-equiv="refresh" content="0;url=/stream.php">'
       . '<script>window.location.replace("/stream.php");</script>';
    exit();
}

// Check if a cookie exists (example: knobly_user_data)
if (isset($_COOKIE['knobly_user_data'])) {
    // Retrieve and decode the user data from the cookie
    $userData = json_decode($_COOKIE['knobly_user_data'], true);
    if ($userData) {
        $gUserId = $userData['userId'];
        // $gUserPlan = $userData['userPlan'];
        $gUserName = $userData['userName'];
        $gUserEmail = $userData['userEmail'];
        $gUserSubdomain = $userData['userSubdomain'];
        if ($userData['userPlan'] == "Pro") {
            $gUserPlan = 1;
        } else {
            $gUserPlan = 0;
        }

        // Redirect to stream.php if the cookie is found
        nj_redirect_to_stream();
    }
}
// Check if a session exists (example: user_logged_in)
else if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in']) {
    $gUserId = $_SESSION['userId'];
    // $gUserPlan = $_SESSION['userPlan'];
    $gUserName = $_SESSION['userName'];
    $gUserEmail = $_SESSION['userEmail'];
    $gUserSubdomain = $_SESSION['userSubdomain'];
    if ($_SESSION['userPlan'] == "Pro" || $_SESSION['userPlan'] == "Trial") {
        $gUserPlan = 1;
    } else {
        $gUserPlan = 0;
    }

    // Redirect to stream.php if the session is found
    nj_redirect_to_stream();
} else {
    // Destroy the session
    session_destroy();
}
