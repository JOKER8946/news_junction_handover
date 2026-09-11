<?
include '../inc/php/db_config.php';
include '../inc/php/mail.php';


function confirm_delete($userId, $pwd)
{
    global $creamdb;
    $stmt = $creamdb->prepare("UPDATE user SET is_deleted = 1 WHERE id = ? AND password = ?");
    $stmt->bind_param("is", $userId, $pwd);
    $stmt->execute();
    if ($stmt->affected_rows > 0) {
        echo json_encode(['status' => 'success', 'message' => 'Account Deleted Successfully']);
    } else {
        throw new mysqli_sql_exception("No Following data obtained for the above credentials");
    }
}

function getAccDetails($email)
{
    global $creamdb;
    $sql = "SELECT * FROM user WHERE email = ?";
    $stmt = $creamdb->prepare($sql);
    if ($stmt === false) {
        die('MySQL prepare failed: ' . $creamdb->error);
    }
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    } else {
        return null;
    }
    $stmt->close();
}

function getDeletionStatus($userId)
{
    global $creamdb;
    $sql = "SELECT status, deleted_on FROM acc_deletion WHERE userId = ?";
    $stmt = $creamdb->prepare($sql);
    if ($stmt === false) {
        die('MySQL prepare failed: ' . $creamdb->error);
    }
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    } else {
        return null;
    }
    $stmt->close();
}


if (isset($_GET['code'])) {
    $code = $_GET['code'];
    $dCode = simpleEncDec($code, 'd');
    $user = getAccDetails($dCode);
    $deletionStatus = getDeletionStatus($user['id']);
    $status = $deletionStatus['status'];

    if (isset($status)) {
?>
        <!DOCTYPE html>
        <html lang="en">

        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Account Deletion Verification</title>
            <!-- Bootstrap CSS -->
            <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
        </head>

        <body class="bg-light">

            <div class="container mt-5">
                <!-- Account Deletion Card -->

                <? if ($status === 'deleted') {
                ?>
                    <div class="card shadow-lg">
                        <div class="card-header text-center bg-danger text-white">
                            <h4>Your Account has already been deleted</h4>
                        </div>
                        <div class="card-body">
                            <div class="text-center mb-4">
                                <img src="/inc/img/logo.black.png" alt="Company Logo" style="width: 250px; height: auto;">
                            </div>
                            <h5 class="card-title text-center">Your Account <strong><?= $user['email'] ?></strong> has already been deleted.</h5>
                            <p class="card-text text-center">
                                Your account has already deleted on <strong><?= (new DateTime($deletionStatus['deleted_on']))->format('d-m-Y') ?></strong>. You cannot cancel or revert your deletion.
                            </p>
                        </div>
                    </div>
                <? } else { ?>
                    <div class="card shadow-lg">
                        <div class="card-header text-center bg-danger text-white">
                            <h4>Account Deletion Confirmation</h4>
                        </div>
                        <div class="card-body">
                            <!-- Company Logo in Card Body -->
                            <div class="text-center mb-4">
                                <img src="/inc/img/logo.black.png" alt="Company Logo" style="width: 250px; height: auto;">
                            </div>

                            <h5 class="card-title text-center">Thank you <?= $user['full_name'] ?> for your Account Deletion Verification.</h5>
                            <p class="card-text text-center">
                                Your account will be deleted within <strong>7 working days</strong>. You can cancel your deletion during this period.
                            </p>

                            <!-- Important Notes -->
                            <div class="alert alert-warning" role="alert">
                                <strong>Important:</strong>
                                <ul>
                                    <li>Your account deletion <strong>(<?= $user['email'] ?>)</strong> will be finalized within 7 working days.</li>
                                    <li>You can cancel the deletion anytime before that period.</li>
                                    <li>Once deleted, you will not be able to recover your account.</li>
                                </ul>
                            </div>

                            <!-- Footer Note -->
                            <p class="text-center text-muted">We are sorry to see you go. Hope to have you back soon.</p>

                            <!-- Action Buttons -->
                            <div class="text-center">
                                <? if ($status == 'not_confirmed') {
                                ?>
                                    <button class="btn btn-success mb-2" onclick="confirmation('cancel')">Cancel Deletion</button>
                                    <button class="btn btn-danger mb-2" onclick="confirmation('confirm')">Confirm Deletion</button>
                                <? }
                                if ($status == 'confirmed') {
                                ?>
                                    <button class="btn btn-success mb-2" onclick="confirmation('cancel')">Cancel Deletion</button>
                                <? } ?>
                            </div>
                        </div>
                    </div>

                <? } ?>
            </div>

            <!-- Bootstrap JS and dependencies -->
            <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
            <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.2/dist/umd/popper.min.js"></script>
            <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

            <script>
                function confirmation(action) {
                    // Perform the AJAX request with jQuery
                    $.ajax({
                        url: 'confirm_deletion.php',
                        type: 'POST',
                        data: {
                            'request': action,
                            'userId': <?= $user['id'] ?>
                        },
                        dataType: 'json',
                        success: function(data) {
                            console.log(data);
                            if (data.status === 'success') {
                                alert(data.message);
                                window.location.reload(); // Reload the page after successful submission
                            } else {
                                alert('Error: ' + data.message);
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('Error:', error);
                            alert('An error occurred while trying to report the post. Please try again later.');
                        }
                    });








                }
            </script>

        </body>

        </html>


<?
    } else {
        http_response_code(500);
    }
} else {
    http_response_code(500);
}
?>