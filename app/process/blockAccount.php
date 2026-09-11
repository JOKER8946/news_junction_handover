<?
include '../inc/php/validate.logged.php';
include '../inc/php/db_config.php';
header('Content-Type: application/json');

function block_account($userId)
{
    global $creamdb, $gUserId;

    try {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $stmt = $creamdb->prepare("INSERT INTO block_acc(userId, blockedUserId) VALUES (?, ?)");
        $stmt->bind_param("ii", $gUserId, $userId);
        if ($stmt->execute()) {

            echo json_encode(['status' => 'success', 'message' => 'You Have Blocked the Account..']);
        } else {
            throw new mysqli_sql_exception("Unknown :(");
        }
    } catch (mysqli_sql_exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Could not block the account. Reason: ' . $e->getMessage()]);
    } finally {
        if ($stmt) {
            $stmt->close();
        }
    }
}

function unblock_account($userId)
{
    global $creamdb, $gUserId;

    try {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $stmt = $creamdb->prepare("DELETE FROM block_acc WHERE userId = ? AND blockedUserId = ?");
        $stmt->bind_param("ii", $gUserId, $userId);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'You Have Unblocked the Account..']);
        } else {
            throw new mysqli_sql_exception("Unknown :(");
        }
    } catch (mysqli_sql_exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Could not unblock the account. Reason: ' . $e->getMessage()]);
    } finally {
        if ($stmt) {
            $stmt->close();
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // echo $_POST['streamId']."<br>".$_POST['reason']."<br>";
    if (!isset($_POST['userId']) || !isset($_POST['act'])) {
        echo json_encode(['status' => 'error', 'message' => "Missing required inputs"]);
        exit;
    } else {
        if ($_POST['act'] == "block") {
            block_account($_POST['userId']);
        } elseif ($_POST['act'] == "unblock") {
            unblock_account($_POST['userId']);
        }
    }
    exit;
} else {
    echo json_encode(['status' => 'error', 'message' => "Unknown Request"]);
    exit;
}
