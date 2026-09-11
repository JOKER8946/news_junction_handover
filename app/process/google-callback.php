<?php
require '../vendor/autoload.php';
require_once '../inc/php/db_config.php';

use Google\Client;

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

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

global $creamdb;

// Credentials come from nj_cream.platform_settings (set via MM super-admin →
// Platform Settings → News Junction · Google Sign-In). Falls back to the
// file config if no DB override exists.
require_once __DIR__ . '/../inc/php/platform_settings.php';
$googleCfg = nj_google_oauth_config();
$client = new Google_Client();
$client->setClientId((string)$googleCfg['client_id']);
$client->setClientSecret((string)($googleCfg['client_secret'] ?? ''));
$client->setRedirectUri('https://newsjunction.net/process/google-callback.php');
$client->addScope('email');
$client->addScope('profile');

if (isset($_POST['id_token'])) {
    try {
        $payload = $client->verifyIdToken($_POST['id_token']);
        if ($payload) {
            $google_id = $payload['sub'];
            $email = $payload['email'];
            $full_name = $payload['name'];
            $profile_pic = $payload['picture'];

            // Check if user exists
            $sql = "SELECT id, full_name, subdomain, plan, is_activated FROM user WHERE google_id = ? OR email = ?";
            if ($stmt = $creamdb->prepare($sql)) {
                $stmt->bind_param("ss", $google_id, $email);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();

                if ($result->num_rows > 0) {
                    if ($row['is_activated'] == 1) {
                        // Existing user, update profile_pic and num_visits
                        $sql = "UPDATE user SET date_modified = NOW(), num_visits = num_visits + 1 WHERE id = ?";
                        if ($stmt = $creamdb->prepare($sql)) {
                            $stmt->bind_param("i",  $row['id']);
                            $stmt->execute();
                        }

                        // Log login activity
                        $ip = getenv('HTTP_CLIENT_IP') ?: getenv('HTTP_X_FORWARDED_FOR') ?:
                            getenv('HTTP_X_FORWARDED') ?: getenv('HTTP_FORWARDED_FOR') ?:
                            getenv('HTTP_FORWARDED') ?: getenv('REMOTE_ADDR');
                        $sql = "INSERT INTO user_login (user_id, ip, date_login) VALUES (?, ?, NOW())";
                        if ($stmt = $creamdb->prepare($sql)) {
                            $stmt->bind_param("is", $row['id'], $ip);
                            $stmt->execute();
                        }

                        // Set session variables
                        $_SESSION['user_logged_in'] = true;
                        $_SESSION['userId'] = $row['id'];
                        check_cream_subscription($row['id']);
                        $_SESSION['userName'] = $row['full_name'];
                        $_SESSION['userEmail'] = $email;
                        $_SESSION['userSubdomain'] = $row['subdomain'];

                        // Set cookie
                        $userData = [
                            'userId' => $row['id'],
                            'userEmail' => $email,
                            'userPlan' => $_SESSION['userPlan'],
                            'userName' => $row['full_name'],
                            'userSubdomain' => $row['subdomain']
                        ];
                        $cookie_value = json_encode($userData);
                        $cookie_expiration = time() + 2629744;
                        setcookie('knobly_user_data', $cookie_value, $cookie_expiration, "/");
                        // echo "error";
                        echo 'OK|';
                    } else {
                        echo 'notActivated';
                    }
                    $stmt->close();
                } else {
                    // New user — create directly. Google has already verified the
                    // email, so we activate immediately. No password (Google-only
                    // user; they always come back through this callback).
                    $insertSql = "INSERT INTO user (full_name, email, password, is_activated, num_visits, date_created, acc_create_type, google_id) VALUES (?, ?, '', 1, 1, NOW(), 'google', ?)";
                    if ($ins = $creamdb->prepare($insertSql)) {
                        $ins->bind_param('sss', $full_name, $email, $google_id);
                        if ($ins->execute()) {
                            $newId = $creamdb->insert_id;
                            $ins->close();

                            $_SESSION['user_logged_in'] = true;
                            $_SESSION['userId']        = $newId;
                            check_cream_subscription($newId);
                            $_SESSION['userName']      = $full_name;
                            $_SESSION['userEmail']     = $email;
                            $_SESSION['userSubdomain'] = '';

                            $userData = [
                                'userId'        => $newId,
                                'userEmail'     => $email,
                                'userPlan'      => $_SESSION['userPlan'],
                                'userName'      => $full_name,
                                'userSubdomain' => '',
                            ];
                            setcookie('knobly_user_data', json_encode($userData), time() + 2629744, "/");
                            echo 'OK|';
                        } else {
                            echo 'Error creating account: ' . $ins->error;
                        }
                    } else {
                        echo 'Error preparing insert statement';
                    }
                }
            } else {
                echo 'Error preparing statement';
            }
        } else {
            echo 'Invalid ID token';
        }
    } catch (Exception $e) {
        echo 'Error: ' . $e->getMessage();
    }
} else {
    echo 'No ID token received';
}
