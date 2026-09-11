<?
error_reporting(E_ALL);
ini_set('display_errors', 1);
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once '../inc/config.php';
require_once '../inc/php/db_config.php';
require_once '../inc/php/nj_password.php';

$userId = -1;

if (!empty($_POST)) {
    $login = isset($_POST['email']) ? $_POST['email'] : '';
    $pwd = isset($_POST['pwd']) ? $_POST['pwd'] : '';
    if ($login != '' && $pwd != '') {
        login_process($login, $pwd);
    }
}

function checkAndAddUserSession($userId, $sessionId, $creamdb)
{
    $sessionId =$sessionId;
    // SQL query to check if the user has an active session in the session_log table
    $session_sql = "SELECT COUNT(*) AS session_count 
                    FROM session_log 
                    WHERE userId = ? 
                    AND sessionId = ?
                    AND endTime IS NULL
                    AND startTime >= CURDATE() - INTERVAL 30 DAY";

    try {
        // Prepare the query
        $stmt = $creamdb->prepare($session_sql);
        $stmt->bind_param('is', $userId, $sessionId); // Bind the userId parameter to the query
        $stmt->execute();

        // Get the result
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        // If session_count is 0, the user does not have an active session
        if ($row['session_count'] == 0) {
            // Insert a new session entry if the session does not exist
            createNewSession($userId, $creamdb);
        }
    } catch (mysqli_sql_exception $e) {
        echo "Error checking session: " . $e->getMessage();
    }
}

// Function to create a new session log for the user
function createNewSession($userId, $creamdb)
{
    // Get the current session ID
    $sessionId = session_id(); // Assuming the session ID is already started (session_start() is called earlier)

    // Insert the new session into the session_log table
    $insert_sql = "INSERT INTO session_log (userId, sessionId, startTime) VALUES (?, ?, NOW())";

    try {
        // Prepare the query
        $stmt = $creamdb->prepare($insert_sql);
        $stmt->bind_param('is', $userId, $sessionId); // Bind userId and sessionId
        $stmt->execute();
    } catch (mysqli_sql_exception $e) {
        echo "Error inserting new session: " . $e->getMessage();
    }
}
function check_cream_subscription($userId)
{
    global $creamdb;
    $sql = "SELECT plan from user WHERE id = ?";
    if ($stmt = $creamdb->prepare($sql)) {
        $stmt->bind_param("i", $userId);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            return $row['plan'];
        }
    }
}
// function check_cream_subscription($userId)
// {
//     global $creamdb;
//     $sql = "SELECT plan, plan_type FROM cream_subscription WHERE userId = ? AND NOW() BETWEEN start_date AND end_date";
//     if ($stmt = $creamdb->prepare($sql)) {
//         $stmt->bind_param("i", $userId);
//         if ($stmt->execute()) {
//             $result = $stmt->get_result();
//             if ($result->num_rows > 0) {
//                 $row = $result->fetch_assoc();
//                 return $row['plan'];
//             } else {
//                 return "Free";
//             }
//         }
//     }
// }


function login_process($email, $pwd)
{
    global $creamdb;
    // Look the user up by email only, then verify the password in PHP.
    // The password is no longer part of the WHERE clause, so it can be a
    // bcrypt hash rather than plaintext. nj_password_verify() still accepts
    // legacy plaintext rows, so no existing login breaks.
    $sql = "SELECT id, full_name, subdomain, plan, is_activated, pincode, password FROM user WHERE email=? AND is_deleted IS NULL";
    if ($stmt = $creamdb->prepare($sql)) {
        $stmt->bind_param("s", $email);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();

                if (!nj_password_verify($pwd, $row['password'])) {
                    // Same observable behaviour as before: on a bad password
                    // the old query simply matched no rows and echoed nothing,
                    // which the front-end reads as "User or Password is
                    // incorrect". Keep that contract exactly.
                    $stmt->close();
                    return;
                }

                // Password was correct. If it was still stored as plaintext,
                // silently replace it with a bcrypt hash now.
                nj_password_upgrade_if_needed($creamdb, $row['id'], $pwd, $row['password']);

                if ($row['is_activated'] == 1) {

                    // Check if user is a reporter and if admin-verified
                    $checkRoleSql = "SELECT role FROM user WHERE id = ?";
                    $stmtRole = $creamdb->prepare($checkRoleSql);
                    $stmtRole->bind_param("i", $row['id']);
                    $stmtRole->execute();
                    $roleResult = $stmtRole->get_result();
                    $roleRow = $roleResult->fetch_assoc();
                    $stmtRole->close();

                    if ($roleRow && $roleRow['role'] === 'reporter') {
                        $checkReporterSql = "SELECT status FROM reporters WHERE user_id = ?";
                        $stmtReporter = $creamdb->prepare($checkReporterSql);
                        $stmtReporter->bind_param("i", $row['id']);
                        $stmtReporter->execute();
                        $reporterResult = $stmtReporter->get_result();
                        $reporterRow = $reporterResult->fetch_assoc();
                        $stmtReporter->close();

                        if (!$reporterRow || $reporterRow['status'] !== 'verified') {
                            echo 'reporterNotVerified';
                            return;
                        }
                    }

                    // Increment visit count.
                    // Keyed on id, not on email+password: once the password is
                    // a bcrypt hash it can never be matched in a WHERE clause,
                    // so the old query would silently update zero rows.
                    $sql = "UPDATE user SET num_visits=num_visits+1 WHERE id=?";
                    if ($stmt1 = $creamdb->prepare($sql)) {
                        $stmt1->bind_param("i", $row['id']);
                        if ($stmt1->execute()) {
                            // Get the user's IP address
                            $ip = getenv('HTTP_CLIENT_IP') ?: getenv('HTTP_X_FORWARDED_FOR') ?: getenv('HTTP_X_FORWARDED') ?: getenv('HTTP_FORWARDED_FOR') ?: getenv('HTTP_FORWARDED') ?: getenv('REMOTE_ADDR');

                            // Insert login record into the user_login table
                            $sql = "INSERT INTO user_login(user_id, ip, date_login) VALUES(?, ?, Now())";
                            
                            if ($stmt2 = $creamdb->prepare($sql)) {
                                $stmt2->bind_param("is", $row['id'], $ip);
                                if ($stmt2->execute()) {
                                    $userPlan = check_cream_subscription($row['id']);
                                    // Set session variables
                                    $_SESSION['user_logged_in'] = true;
                                    $_SESSION['userId'] = $row['id'];
                                    $_SESSION['userPlan'] = $userPlan;
                                    $_SESSION['userName'] = $row['full_name'];
                                    $_SESSION['userEmail'] = $email;
                                    $_SESSION['userPincode'] = $row['pincode'];
                                    $_SESSION['userSubdomain'] = $row['subdomain'];

                                    $session_id = session_id();
                                    // Track session in the user_sessions table

                                    checkAndAddUserSession($row['id'],$session_id,$creamdb);
                                    // $sql = "INSERT INTO session_log (sessionId, userId, startTime) VALUES (?, ?, NOW())";
                                    // if ($stmt3 = $creamdb->prepare($sql)) {
                                        // $stmt3->bind_param("si", $session_id, $row['id']);
                                        // if ($stmt3->execute()) {
                                            // Prepare data for cookie (optional)
                                            $userData = [
                                                'userId' => $row['id'],
                                                'userEmail' => $email,
                                                'userPlan' => $userPlan,
                                                'userName' => $row['full_name'],
                                                'userPincode' => $row['pincode'],
                                                'userSubdomain' => $row['subdomain']
                                            ];

                                            // Encode the data into JSON format
                                            $cookie_value = json_encode($userData);

                                            // Set the cookie to expire in 1 day (86400 seconds)
                                            $cookie_expiration = time() + 2629744;

                                            // Set the cookie
                                            setcookie('knobly_user_data', $cookie_value, $cookie_expiration, "/");

                                            // Respond with success
                                            echo 'OK|' . simpleEncDec($row['id'], 'e');
                                        // }
                                    // }
                                }
                            }
                        }
                    }
                } else {
                    echo 'notActivated';
                }
            }
        } else {
            // Handle the error in query execution
            echo "Error executing query.";
        }

        // Close the statement
        $stmt->close();
    } else {
        // Handle error in preparing the statement
        echo "Error preparing statement.";
    }
}
