<?php
// Assuming you have a database connection
include 'validate.logged.php';
include 'db_config.php';
include 'mail.php';
header('Content-Type: application/json');   

// Get the data from the AJAX request
$guserid = $_POST['guserId'];
$accountId = $_POST['accountId'];
$reason = $_POST['reason'];
$reportName = $_POST['username'];

function report_account_mail($toName, $toEmail, $reason,$reportName)
{
    $emailSubject = "Content Abuse Account Report";
    $emailBody = "
        Dear $toName ,<br><br>
        &nbsp;&nbsp; &nbsp; &nbsp; You reported an account:<b>$reportName</b> with following reason:$reason.
        <br>
        We will examine this and take necessary action.<br><br>
        Thank You<br>
        Knobly Cream
    ";
    sendEmail($toName, $toEmail, '', $emailSubject, $emailBody);
}

report_account_mail($gUserName, $gUserEmail, $reason,$reportName);
// Prepare an SQL query to insert the report into the database
$sql = "INSERT INTO report_acc (userId, reportId, reason) VALUES (?, ?, ?)";

// Prepare statement
$stmt = $creamdb->prepare($sql);

// Bind parameters to the statement
$stmt->bind_param("iis", $guserid, $accountId, $reason);

// Execute the statement
if ($stmt->execute()) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "error" => "Failed to submit the report."]);
}

// Close the statement
$stmt->close();
?>
