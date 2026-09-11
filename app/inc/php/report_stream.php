<?
include 'validate.logged.php';
include 'db_config.php';
include 'mail.php';
header('Content-Type: application/json');

function report_account_mail($toName, $toEmail, $reason, $postId)
{

    $reportPostLink = "https://newsjunction.net/post-details.php?id=" . $postId;
    $emailSubject = "Content Abuse Report";
    $emailBody = "
        Dear $toName ,<br><br>
        &nbsp;&nbsp; &nbsp; &nbsp;  You reported a post with following reason:$reason.
        <br><a href='" . $reportPostLink . "'>Reported post</a>
        <br>
        We will examine this and take necessary action.<br><br>
        Thank You<br>
        Knobly Cream
    ";
    sendEmail($toName, $toEmail, '', $emailSubject, $emailBody);
}

function report_stream($userId, $streamId, $reason)
{
    global $readerdb;

    try {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $stmt = $readerdb->prepare("INSERT INTO report_stream (userId, streamId, reason) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $userId, $streamId, $reason);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'You Have Reported the Content..']);
        } else {
            throw new mysqli_sql_exception("Unknown :(");
        }
    } catch (mysqli_sql_exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Could not report the Content. Reason: ' . $e->getMessage()]);
    } finally {
        if ($stmt) {
            $stmt->close();
        }
    }
}


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // echo $_POST['streamId']."<br>".$_POST['reason']."<br>";
    if (!isset($_POST['streamId']) || !isset($_POST['reason'])) {
        echo json_encode(['status' => 'error', 'message' => "Missing required inputs"]);
        exit;
    } else {
        report_account_mail($gUserName, $gUserEmail, $_POST['reason'], $_POST['streamId']);
        report_stream($gUserId, $_POST['streamId'], $_POST['reason']);
    }
    exit;
} else {
    echo json_encode(['status' => 'error', 'message' => "Unknown Request"]);
    exit;
}
