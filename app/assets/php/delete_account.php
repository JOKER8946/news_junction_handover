<?
include 'validate.logged.php';
include 'db_config.php';
include 'mail.php';
header('Content-Type: application/json');

function verify_user_with_pwd($userId, $pwd)
{
    global $creamdb;
    $stmt = $creamdb->prepare("SELECT COUNT(*) AS count FROM user WHERE id = ? AND password = ?");
    $stmt->bind_param("is", $userId, $pwd);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['count'];
}
function delete_account_sql($userId, $reason)
{
    global $creamdb;
    $result = [];
    $status = 'not_confirmed';

    try {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $stmt = $creamdb->prepare("INSERT INTO acc_deletion (userId, reason, status, updates_on) VALUES (?, ?, ?, NULL)");
        $stmt->bind_param("iss", $userId, $reason, $status);
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                $result = ['status' => 'success', 'message' => 'Account Deleted Successfully'];
            } else {
                throw new mysqli_sql_exception("Unknown :(");
            }
        }
    } catch (mysqli_sql_exception $e) {
        $result = ['status' => 'error', 'message' => 'Account Could Not be Deleted. Reason: ' . $e->getMessage()];
    } finally {
        if ($stmt) {
            $stmt->close();
        }
    }
    return $result;
}

function delete_account_confirmation($toName, $toEmail)
{
    global $gUserEmail;
    $result = [];
    $deleteAccLink = "https://newsjunction.net/report/account_deletion.php?code=" . simpleEncDec($gUserEmail);
    $emailSubject = "Your Request for Account Deletion";
    $emailBody = "
        Hi $toName ,<br>
        You opted to <b>Delete Your Account</b> from Knobly Cream. Please click the link below to complete the process:
        <a href='" . $deleteAccLink . "'>Delete your account</a><br><br>
        Accounts Team<br>
        Knobly Cream
    ";

    $mailResponse = sendEmail($toName, $toEmail, '', $emailSubject, $emailBody);
    if ($mailResponse['status'] == "success") {
        $result = ['status' => 'success', 'message' => 'The deletion mail has been sent to the registered email address'];
    } else {
        $result = ['status' => 'error', 'message' => "Could not send the mail to the registered email address"];
    }
    return $result;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['password'])) {
        if (verify_user_with_pwd($gUserId, $_POST['password'])) {
            $mailresponse = delete_account_confirmation($gUserName, $gUserEmail);
            if ($mailresponse['status'] === 'success') {
                $sqlresponse = delete_account_sql($gUserId, $_POST['reason']);
                if ($sqlresponse['status'] === 'success') {
                    echo json_encode($mailresponse);
                } else {
                    echo json_encode(['status' => 'error', 'message' => $sqlresponse['message']]);
                }
            } else {
                echo json_encode(['status' => 'error', 'message' => $mailresponse['message']]);
            }
            // delete_account($gUserId, $_POST['password'], $_POST['reason']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Wrong Password Entered']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => "Unknown Request"]);
    }
    exit;
} else {
    echo json_encode(['status' => 'error', 'message' => "Unknown Request"]);
    exit;
}
