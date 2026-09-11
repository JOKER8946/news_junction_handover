<?php
require_once '../inc/php/db_config.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
function check_cream_subscription($userId)
{
    global $creamdb;
    $sql = "SELECT plan, plan_type FROM cream_subscription WHERE userId = ? AND NOW() BETWEEN start_date AND end_date";
    if ($stmt = $creamdb->prepare($sql)) {
        $stmt->bind_param("i", $userId);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $_SESSION['userPlan'] = $row['plan'];
            } else {
                $_SESSION['userPlan'] = "Free";
            }
        }
    }
}

global $creamdb;

// Check if Google data and form data exist
if (!isset($_SESSION['google_data']) || !isset($_POST['userPhone']) || !isset($_POST['countryCode'])) {
    echo "error";
    exit;
}

$google_data = $_SESSION['google_data'];
$phone_no = $_POST['userPhone'];
$country_code = $_POST['countryCode'];

// Validate phone number and country code
if (!preg_match('/^[0-9]{10,15}$/', $phone_no)) {
    die('Invalid phone number');
}
if (!preg_match('/^\+[0-9]{1,3}$/', $country_code)) {
    die('Invalid country code');
}

// Check if email already exists
$sql_check = "SELECT id FROM user WHERE email = ?";
if ($stmt_check = $creamdb->prepare($sql_check)) {
    $stmt_check->bind_param("s", $google_data['email']);
    $stmt_check->execute();
    $stmt_check->store_result();

    if ($stmt_check->num_rows > 0) {
        echo "email_exists";
        exit;
    }
    $stmt_check->close();
} else {
    die('Error checking existing email');
}

// Insert new user
$sql = "INSERT INTO user (full_name, email, acc_create_type, google_id, phone_no, country_code, is_activated, date_created, date_modified, num_visits) 
        VALUES (?, ?, 'google', ?, ?, ?, 1, NOW(), NOW(), 1)";
if ($stmt = $creamdb->prepare($sql)) {
    $stmt->bind_param(
        "sssss",
        $google_data['full_name'],
        $google_data['email'],
        $google_data['google_id'],
        $phone_no,
        $country_code
    );
    if ($stmt->execute()) {
        $user_id = $creamdb->insert_id;

        // Log login activity
        $ip = getenv('HTTP_CLIENT_IP') ?: getenv('HTTP_X_FORWARDED_FOR') ?:
            getenv('HTTP_X_FORWARDED') ?: getenv('HTTP_FORWARDED_FOR') ?:
            getenv('HTTP_FORWARDED') ?: getenv('REMOTE_ADDR');
        $sql = "INSERT INTO user_login (user_id, ip, date_login) VALUES (?, ?, NOW())";
        if ($stmt = $creamdb->prepare($sql)) {
            $stmt->bind_param("is", $user_id, $ip);
            $stmt->execute();
        }

        // Set session variables
        $_SESSION['user_logged_in'] = true;
        $_SESSION['userId'] = $user_id;
        check_cream_subscription($user_id);
        $_SESSION['userName'] = $google_data['full_name'];
        $_SESSION['userEmail'] = $google_data['email'];
        $_SESSION['userSubdomain'] = null;

        // Set cookie
        $userData = [
            'userId' => $user_id,
            'userEmail' => $google_data['email'],
            'userPlan' => $_SESSION['userPlan'],
            'userName' => $google_data['full_name'],
            'userSubdomain' => null
        ];
        $cookie_value = json_encode($userData);
        $cookie_expiration = time() + 2629744;
        setcookie('knobly_user_data', $cookie_value, $cookie_expiration, "/");

        unset($_SESSION['google_data']);
        echo "success";
    } else {
        die('Error creating account');
    }
    $stmt->close();
} else {
    die('Error preparing statement');
}
