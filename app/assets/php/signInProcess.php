<?
// Cream: My Account
ini_set('display_startup_errors', 1);

session_start();
require_once 'inc/config.php';
require_once 'assets/php/function.php';

function createArticleURL($title)
{
    if ($title <> '') {
        $title = str_replace(' ', '-', $title);
        $title = str_replace('%', '', $title);
        $title = str_replace("'", "", $title);
        return $title;
    } else {
        return '';
    }
}

$act = '';
if (!empty($_POST)) $act = isset($_POST["act"]) ? $_POST["act"] : '';


// Check if already exists on account creation
if ($act == 'chkExist') {
    $signEmail = isset($_POST['signEmail']) ? $_POST['signEmail'] : '';
    $sql = "SELECT id FROM user WHERE email='$signEmail'";
    $result = mysqli_query($db, $sql);
    $numRows = mysqli_num_rows($result);
    if ($numRows == 0) {
        echo 'OK';
    }
}


// Check if User exists
if ($act == 'chkExistUser') {
    $chkEmail = isset($_POST['chkEmail']) ? $_POST['chkEmail'] : '';
    $sql = "SELECT id FROM user WHERE email='$chkEmail'";
    $result = mysqli_query($db, $sql);
    $numRows = mysqli_num_rows($result);
    if ($numRows > 0) {
        $row = mysqli_fetch_assoc($result);
        echo $row['id'];
    }
}


// Reset Password
if ($act == 'resetPassword') {
    $editId = isset($_POST['editId']) ? $_POST['editId'] : '';
    $resetEmail = isset($_POST['email']) ? $_POST['email'] : '';
    $sql = "SELECT full_name FROM user WHERE email='$resetEmail'";
    $result = mysqli_query($db, $sql);
    $numRows = mysqli_num_rows($result);
    echo $numRows . "<br>";
    if ($numRows > 0) {
        $resetURL = "139.59.84.140/reset.html?token=" . simpleEncDec($resetEmail);
        $row = mysqli_fetch_assoc($result);
        $userName = $row['full_name'];
        $tmpHTML = "";
        $tmpHTML .= "<html>";
        $tmpHTML .= "<body>";
        $tmpHTML .= "<div style=\"font-family:Arial;font-size:12px;\">\r\n";
        $tmpHTML .= "Dear $userName,<br><br>\r\n";
        $tmpHTML .= "You recently requested to reset your password. Please click on the following link to reset your password:<br>\r\n";
        $tmpHTML .= "<a href=\"$resetURL\">$resetURL</a><br><br>\r\n";
        $tmpHTML .= "Please note that the above link will be active only for 30 minutes.<br><br>\r\n";
        $tmpHTML .= "Warm Regards,<br>\r\n";
        $tmpHTML .= "Knobly Cream<br>\r\n";
        $tmpHTML .= "</body>";
        $tmpHTML .= "</html>";
        sendEmail($userName, $resetEmail, '', 'Knobly Cream', $tmpHTML);
        echo 'OK';
    }
}


// Reset Password Confirm
if ($act == 'resetPasswordConfirm') {
    $loginToken = isset($_POST['loginToken']) ? $_POST['loginToken'] : '';
    $loginPwd = isset($_POST['loginPwd']) ? $_POST['loginPwd'] : '';
    if ($loginToken <> '' && $loginPwd <> '') {
        $loginTokenA = simpleEncDec($loginToken, 'd');
        $sql = "SELECT id,full_name FROM user WHERE email='$loginToken'";
        $result = mysqli_query($db, $sql);
        $numRows = mysqli_num_rows($result);
        if ($numRows > 0) {
            $row = mysqli_fetch_assoc($result);
            $userId = $row['id'];
            $userName = $row['full_name'];
            $hashedPwd = password_hash($loginPwd, PASSWORD_DEFAULT);
            $sql = "UPDATE user SET password='$hashedPwd' WHERE id=$userId";
            $result = mysqli_query($db, $sql);
            echo "Dear $userName: Your Password has been reset!<br>";
        } else {
            echo 'Password could not be reset!';
        }
    }
}
if ($act == 'createAccount') {
    $signFullName = isset($_POST['signFullName']) ? $_POST['signFullName'] : '';
    $signEmail = isset($_POST['signEmail']) ? $_POST['signEmail'] : '';
    $signPwd = isset($_POST['signPwd1']) ? $_POST['signPwd1'] : '';
    $signCompany = isset($_POST['signCompany']) ? $_POST['signCompany'] : '';
    $signWebsite = isset($_POST['signWebsite']) ? $_POST['signWebsite'] : '';
    $signBusinessType = isset($_POST['signBusinessType']) ? $_POST['signBusinessType'] : '';


    if ($signFullName != '' && $signEmail != '' && $signPwd != '') {
	$hashedPwd = password_hash($signPwd, PASSWORD_DEFAULT);
        // Prepare the insert statement to avoid SQL injection
        $stmt = $db->prepare("INSERT INTO user(full_name, company, email, password, website, num_visits, date_created) 
                              VALUES (?, ?, ?, ?, ?, 1, NOW())");
        $stmt->bind_param("sssss", $signFullName, $signCompany, $signEmail, $hashedPwd, $signWebsite);

        if ($stmt->execute()) {
            $userId = $stmt->insert_id;

            // Get user IP
            $ip = getenv('HTTP_CLIENT_IP') ?: getenv('HTTP_X_FORWARDED_FOR') ?: getenv('HTTP_X_FORWARDED') ?: getenv('HTTP_FORWARDED_FOR') ?: getenv('HTTP_FORWARDED') ?: getenv('REMOTE_ADDR');

            // Insert login data securely
            $stmtLogin = $db->prepare("INSERT INTO user_login(user_id, ip, date_login) VALUES (?, ?, NOW())");
            $stmtLogin->bind_param("is", $userId, $ip);

            if ($stmtLogin->execute()) {
                $_SESSION['userId'] = $userId;
                $_SESSION['userName'] = $signFullName;
                $_SESSION['userEmail'] = $signEmail;

                // Send activation email
                $activateURL = "http://www.newsjunction.net/activate.php?token=" . simpleEncDec($signEmail);
                $tmpHTML = "<html><body>";
                $tmpHTML .= "<div style=\"font-family:Arial;font-size:12px;\">\r\n";
                $tmpHTML .= "Dear $signFullName,<br><br>\r\n";
                $tmpHTML .= "You recently created an account on Knobly Cream. Please click on the following link to activate your account:<br>\r\n";
                $tmpHTML .= "<a href=\"$activateURL\">$activateURL</a><br><br>\r\n";
                $tmpHTML .= "Warm Regards,<br>\r\nKnobly Cream<br>\r\n</div>";
                $tmpHTML .= "</body></html>";

                sendEmail($signFullName, $signEmail, '', 'Knobly Cream: Activate your Account!', $tmpHTML);

                // Return user ID as part of a JSON success response
                echo json_encode([
                    'status' => 'OK',
                    'userId' => $userId
                ]);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error inserting user login data: ' . $stmtLogin->error
                ]);
            }

            $stmtLogin->close();
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Error inserting user data: ' . $stmt->error
            ]);
        }

        $stmt->close();
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Missing required fields.'
        ]);
    }
}


if ($act == 'createUdupiAccount') {
    $signFullName = isset($_POST['signFullName']) ? $_POST['signFullName'] : '';
    $signEmail = isset($_POST['signEmail']) ? $_POST['signEmail'] : '';
    $signPwd = isset($_POST['signPwd1']) ? $_POST['signPwd1'] : '';
    $signCompany = isset($_POST['signCompany']) ? $_POST['signCompany'] : '';
    $signWebsite = isset($_POST['signWebsite']) ? $_POST['signWebsite'] : '';
    $signBusinessType = isset($_POST['signBusinessType']) ? $_POST['signBusinessType'] : '';
    $pincode = isset($_POST['pincode']) ? $_POST['pincode'] : '';

    // if ($signFullName != '' && $signEmail != '' && $signPwd != '' && $signBusinessType != '') {
    if ($signFullName != '' && $signEmail != '' && $signPwd != '') {
        $sql = "INSERT INTO user(full_name, company, email, password, website,  num_visits, pincode,date_created) 
        VALUES('$signFullName', '$signCompany', '$signEmail', '$signPwd', '$signWebsite', 1,$pincode, Now())";
        if (mysqli_query($db, $sql)) {
            // If the insert is successful, get the last inserted user ID
            $userId = mysqli_insert_id($db);

            // Insert user login data
            $ip = getenv('HTTP_CLIENT_IP') ?: getenv('HTTP_X_FORWARDED_FOR') ?: getenv('HTTP_X_FORWARDED') ?: getenv('HTTP_FORWARDED_FOR') ?: getenv('HTTP_FORWARDED') ?: getenv('REMOTE_ADDR');
            $sqlLogin = "INSERT INTO user_login(user_id, ip, date_login) VALUES($userId, '$ip', Now())";

            if (mysqli_query($db, $sqlLogin)) {
                // If login data insertion is successful
                // echo "User registration and login data inserted successfully!";
                $_SESSION['userId'] = $userId;
                $_SESSION['userName'] = $signFullName;
                $_SESSION['userEmail'] = $signEmail;
                $activateURL = "http://www.newsjunction.net/activate.php?token=" . simpleEncDec($signEmail);
                $tmpHTML = "";
                $tmpHTML .= "<html>";
                $tmpHTML .= "<body>";
                $tmpHTML .= "<div style=\"font-family:Arial;font-size:12px;\">\r\n";
                $tmpHTML .= "Dear $signFullName,<br><br>\r\n";
                $tmpHTML .= "You recently created an account on Knobly Cream. Please click on the following link to activate your account:<br>\r\n";
                $tmpHTML .= "<a href=\"$activateURL\">$activateURL</a><br><br>\r\n";
                $tmpHTML .= "Warm Regards,<br>\r\n";
                $tmpHTML .= "Knobly Cream<br>\r\n";
                $tmpHTML .= "</body>";
                $tmpHTML .= "</html>";
                sendEmail($signFullName, $signEmail, '', 'Knobly Cream: Activate your Account!', $tmpHTML);
                echo 'OK';
            } else {
                // If login data insertion fails
                echo "Error inserting user login data: " . mysqli_error($db);
            }
        } else {
            // If user data insertion fails
            echo "Error inserting user data: " . mysqli_error($db);
        }
    }
}



// Get Business Type
if ($act == 'getBusinessType') {
    $returnArr = [];
    $sql = "SELECT id,category FROM category ORDER BY category";
    $result = mysqli_query($db, $sql);
    $numRows = mysqli_num_rows($result);
    if ($numRows > 0) {
        for ($i = 0; $i < $numRows; $i++) {
            $row = mysqli_fetch_array($result);
            array_push($returnArr, array($row['id'], $row['category']));
        }
    }
    echo json_encode($returnArr);
}


// Show Forgot Password
if ($act == 'showForgotPassword') {
?>
    <div class="popup">
        <div class="widget">
            <div class="card" style="margin-bottom: 10px;">
                <div class="card-header bg-dark">
                    <h5 class="mb-0 text-light">Forgot Password</h5>
                </div>
            </div>A
            <div id="widget_B" style="">
                <div class="form-group">
                    <label for="forgotLogin">Enter your Login</label>
                    <input type="text" class="form-control" id="forgotLogin" name="forgotLogin" maxlength="100" />
                    <small class="form-text text-muted">Please enter the email with which you signed up</small>
                </div>
            </div>
            <div id="widget_F">
                <div>
                    <button type="submit" class="btn btn-primary" onclick="return chkResetPassword()">Reset Password</button>
                    <div id="panelStatus" class="float-right text-sm" style="margin-top:5px" align="right"></div>
                </div>
            </div>
        </div>
    </div>
<?
}


// Create Lead
if ($act == 'createLead') {
    $leadName = isset($_POST['leadName']) ? $_POST['leadName'] : '';
    $leadCompany = isset($_POST['leadCompany']) ? $_POST['leadCompany'] : '';
    $leadEmail = isset($_POST['leadEmail']) ? $_POST['leadEmail'] : '';
    $leadMobile = isset($_POST['leadMobile']) ? $_POST['leadMobile'] : '';
    $leadCollectionId = isset($_POST['leadCollectionId']) ? $_POST['leadCollectionId'] : '';
    if ($leadCollectionId != '' && $leadName != '') {
        $ip = getenv('HTTP_CLIENT_IP') ?: getenv('HTTP_X_FORWARDED_FOR') ?: getenv('HTTP_X_FORWARDED') ?: getenv('HTTP_FORWARDED_FOR') ?: getenv('HTTP_FORWARDED') ?: getenv('REMOTE_ADDR');
        $sql = "INSERT INTO user_collection_lead(article_id,ip,full_name,company,email,mobile,date_created) VALUES($leadCollectionId,'$ip','$leadName','$leadCompany','$leadEmail','$leadMobile',Now())";
        mysqli_query($db, $sql);
        $sql = "SELECT id,title,url,read_more_email FROM user_collection WHERE id=$leadCollectionId";
        $result = mysqli_query($db, $sql);
        $numRows = mysqli_num_rows($result);
        if ($numRows > 0) {
            $row = mysqli_fetch_assoc($result);
            $collectionId = $row['id'];
            $collectionTitle = $row['title'];
            $collectionLink = 'https://www.newsjunction.net' . '/view/' . $collectionId . '/' . createArticleURL($collectionTitle);
            $readEmail = $row['read_more_email'];
            if ($readEmail <> '') {
                $tmpHTML = "";
                $tmpHTML .= "<html>";
                $tmpHTML .= "<body>";A
                $tmpHTML .= "<div style=\"font-family:Arial;font-size:12px;\">\r\n";
                $tmpHTML .= "Dear Knobly Cream user,<br><br>\r\n";
                $tmpHTML .= "The following lead details have been submitted:<br><br>\r\n";
                $tmpHTML .= "URL: $collectionLink<br>\r\n";
                $tmpHTML .= "Name: $leadName<br>\r\n";
                $tmpHTML .= "Company: $leadCompany<br>\r\n";
                $tmpHTML .= "Email: $leadEmail<br>\r\n";
                $tmpHTML .= "Mobile: $leadMobile<br><br>\r\n";
                $tmpHTML .= "Warm Regards,<br>\r\n";
                $tmpHTML .= "Knobly Cream<br>\r\n";
                $tmpHTML .= "</body>";
                $tmpHTML .= "</html>";
                sendEmail('Knobly Cream User', $readEmail, '', 'Lead details from Knobly Cream', $tmpHTML);
            }
        }
        echo 'OK';
    }
}


// Show Go Pro
if ($act == 'showGoPro') {
?>
    <div class="popup" style="width:420px">
        <div class="widget">
            <div class="card">
                <div class="card-header bg-dark">
                    <h5 class="mb-0 text-light">Go Pro Today</h5>
                </div>
            </div>
            <div style="padding:15px 25px">
                Send in your details by <a href="https://www.newsjunction.net/more.php?id=2655">clicking here</a>. We will get in touch with you and help you with Pro set ups and payment options.
            </div>
        </div>
    </div>
<?
}
