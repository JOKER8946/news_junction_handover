<!doctype html>
<?
include 'inc/config.php';
?>
<html lang="en">

<head>
   <title>News Junction</title>
   <meta charset="utf-8">
   <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
   <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css" integrity="sha384-9aIt2nRpC12Uk9gS9baDl411NQApFmC26EwAOH8WgZl5MYYxFfc+NcPb1dKGj7Sk" crossorigin="anonymous" />
   <link rel="stylesheet" href="inc/fontawesome/css/all.min.css" />
   <link rel="stylesheet" href="inc/style.css" />
   <script src="https://code.jquery.com/jquery-3.5.1.min.js" integrity="sha256-9/aliU8dGd2tb6OSsuzixeV4y/faTqgFtohetphbbj0=" crossorigin="anonymous"></script>
   <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.min.js" integrity="sha384-OgVRvuATP1z7JjHLkuOU7Xw704+h835Lr+6QL9UvYjZE3Ipu6Tp75j7Bh/kR0JKI" crossorigin="anonymous"></script>
   <script src="inc/common.js"></script>
</head>

<body>

   <header>
      <div class="container text-center pt-2">
         <a href="/"><img src="grfx/img/nj_logo.png?v=20260624" alt="News Junction" style="width:200px"></a><br>
      </div>
   </header>

   <section class="my-5">
      <div class="container text-center">
         <div class="col-12 offset-md-2 col-md-8 offset-lg-3 col-lg-6">
            <?php
            // The activate.php endpoint handles two different email flows:
            //   1. ?token=...                       -> signup activation
            //   2. ?action=delete&code=...          -> account deletion confirmation
            $action = trim(isset($_GET["action"]) ? $_GET["action"] : '');
            $code   = trim(isset($_GET["code"])   ? $_GET["code"]   : '');

            if ($action === 'delete' && $code !== '') {
                // ── Account deletion confirmation ──
                $email = simpleEncDec($code, 'd');
                echo '<h5>Account Deletion</h5><div class="my-4">';
                if ($email === false || $email === '') {
                    echo 'This deletion link is invalid or has expired.<br><a href="/">Continue to News Junction</a><br>';
                } else {
                    $emailSafe = mysqli_real_escape_string($db, $email);
                    $row = mysqli_fetch_assoc(mysqli_query($db, "SELECT id, full_name, is_deleted FROM user WHERE email='$emailSafe' LIMIT 1"));
                    if (!$row) {
                        echo 'No matching account found.<br><a href="/">Continue to News Junction</a><br>';
                    } elseif ((int)$row['is_deleted'] === 1) {
                        echo 'Your account has already been deleted.<br><a href="/">Continue to News Junction</a><br>';
                    } else {
                        $uid = (int)$row['id'];
                        // Soft-delete the user, mark the deletion record confirmed.
                        mysqli_query($db, "UPDATE user SET is_deleted = 1, date_modified = NOW() WHERE id = $uid");
                        mysqli_query($db, "UPDATE acc_deletion SET status = 'confirmed', updates_on = NOW() WHERE userId = $uid ORDER BY id DESC LIMIT 1");
                        // Clear any active session/cookie so they're logged out.
                        if (session_status() === PHP_SESSION_ACTIVE) {
                            $_SESSION = [];
                            session_destroy();
                        }
                        setcookie('knobly_user_data', '', time() - 3600, '/');
                        echo 'Your News Junction account has been <strong>deleted</strong>.<br>';
                        echo 'We\'re sorry to see you go.<br><br>';
                        echo '<a href="/">Return to News Junction homepage</a><br>';
                    }
                }
                echo '</div>';
            } else {
                // ── Signup activation (original flow) ──
                echo '<h5>Account Activation</h5><div id="panelReset" class="my-4">';
                $token = trim(isset($_GET["token"]) ? $_GET["token"] : '');
                if ($token != '') {
                    $isActivated = '';
                    $token = simpleEncDec($token, 'd');
                    $tokenSafe = mysqli_real_escape_string($db, (string)$token);
                    $sql = "SELECT is_activated,full_name FROM user WHERE email='$tokenSafe'";
                    $result = mysqli_query($db, $sql);
                    $numRows = mysqli_num_rows($result);
                    if ($numRows > 0) {
                        $row = mysqli_fetch_array($result);
                        $isActivated = $row['is_activated'];
                        $toName = $row['full_name'];
                    }
                    if ($isActivated == 1) {
                        echo 'Your account is already activated!<br><a href="/">Continue to News Junction</a><br>';
                    } else {
                        mysqli_query($db, "UPDATE user SET is_activated=1 WHERE email='$tokenSafe'");
                        $emailSubject = "Welcome to News Junction Your Account is Now Activated!";
                        $emailBody = "Hello $toName,
                            <br><br>
                            We're excited to let you know that your News Junction account has been successfully <strong>activated</strong>!
                            <br><br>
                            You can now log in and start exploring the tools.
                            <br><br>
                            <a href='https://newsjunction.net/'>Click here to log in and get started</a>
                            <br><br>
                            Thankyou,
                            <br>
                            <img src='https://newsjunction.net/grfx/img/nj_logo.png?v=20260624' alt='News Junction Logo' style='margin-top:10px; width:100px;'>
                            <br><br>
                            <hr>
                            <small>This is an automated message. Please do not reply directly to this email.</small>";
                        sendEmail('', $token, '', $emailSubject, $emailBody);
                        echo 'Your account is now activated!<br><a href="/">Continue to News Junction</a><br>';
                    }
                } else {
                    echo 'You reached here in an error!<br><a href="/">Continue to News Junction</a><br>';
                }
                echo '</div>';
            }
            ?>
         </div>
      </div>
   </section>

   <footer class="footerHome">
      <div class="container">
         <div class="row py-2 no-gutters">
            <div class="col-6">
               Powered by Knobly Consulting
            </div>
            <div class="col-6 text-right">
               <a href="usage.html">Usage Policy</a> &nbsp; <a href="privacy.html">Privacy Policy</a>
            </div>
         </div>
      </div>
   </footer>

</body>

</html>