<?php
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

if ($act == 'chkExist') {
    $signEmail = $_POST['signEmail'] ?? '';
    $sql = "SELECT id FROM user WHERE email='$signEmail'";
    $result = mysqli_query($db, $sql);
    echo (mysqli_num_rows($result) == 0) ? 'OK' : '';
}

if ($act == 'chkExistUser') {
    $chkEmail = $_POST['chkEmail'] ?? '';
    $sql = "SELECT id FROM user WHERE email='$chkEmail'";
    $result = mysqli_query($db, $sql);
    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        echo $row['id'];
    }
}

if ($act == 'resetPassword') {
    $resetEmail = $_POST['email'] ?? '';
    $sql = "SELECT full_name FROM user WHERE email='$resetEmail'";
    $result = mysqli_query($db, $sql);
    if (mysqli_num_rows($result) > 0) {
        $resetURL = "https://newsjunction.net/reset.html?token=" . simpleEncDec($resetEmail);
        $row = mysqli_fetch_assoc($result);
        $userName = $row['full_name'];
        $tmpHTML = "<html><body><div style=\"font-family:Arial;font-size:12px;\">\r\n";
        $tmpHTML .= "Dear $userName,<br><br>\r\n";
        $tmpHTML .= "Reset your password by clicking the link below:<br>\r\n";
        $tmpHTML .= "<a href=\"$resetURL\">$resetURL</a><br><br>\r\n";
        $tmpHTML .= "Link is active for 30 minutes.<br><br>Warm Regards,<br>News Junction</div></body></html>";
        sendEmail($userName, $resetEmail, '', 'News Junction', $tmpHTML);
        echo 'OK';
    }
}

if ($act == 'resetPasswordConfirm') {
    $loginToken = $_POST['loginToken'] ?? '';
    $loginPwd = $_POST['loginPwd'] ?? '';
    if ($loginToken && $loginPwd) {
        $loginToken = simpleEncDec($loginToken, 'd');
        $sql = "SELECT id,full_name FROM user WHERE email='$loginToken'";
        $result = mysqli_query($db, $sql);
        if (mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            $sql = "UPDATE user SET password='$loginPwd' WHERE id=" . $row['id'];
            mysqli_query($db, $sql);
            echo "Dear {$row['full_name']}: Your Password has been reset!<br>";
        } else echo 'Password could not be reset!';
    }
}

if ($act == 'updateProfile') {
    global $gUserId;
    $userName = $_POST['userName'] ?? '';
    $userEmail = $_POST['userEmail'] ?? '';
    $userPhone = $_POST['userPhone'] ?? '';
    $countryCode = $_POST['countryCode'] ?? ''; // ✅ New
    $userCompany = $_POST['userCompany'] ?? '';
    $userCategoryId = (int)($_POST['userCategoryId'] ?? 0);
    $userWebsite = $_POST['userWebsite'] ?? '';
    $userBio = $_POST['userBio'] ?? '';

    if ($userName && $userEmail && $userPhone) {
        // ✅ Basic validation
        if (!preg_match('/^\+?[0-9\s\-\(\)]{7,20}$/', $userPhone)) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid phone number format.']);
            exit;
        }

        if (!preg_match('/^\+\d{1,4}$/', $countryCode)) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid country code.']);
            exit;
        }

        // ✅ Sanitize inputs
        $userName = mysqli_real_escape_string($db, $userName);
        $userEmail = mysqli_real_escape_string($db, $userEmail);
        $userPhone = mysqli_real_escape_string($db, $userPhone);
        $countryCode = mysqli_real_escape_string($db, $countryCode);
        $userCompany = mysqli_real_escape_string($db, $userCompany);
        $userWebsite = mysqli_real_escape_string($db, $userWebsite);
        $userBio = mysqli_real_escape_string($db, $userBio);

        // ✅ Update query
        $sql = "UPDATE user 
                SET full_name='$userName',
                    email='$userEmail',
                    phone_no='$userPhone',
                    country_code='$countryCode',  -- ✅ must exist in DB
                    company='$userCompany',
                    category_id=$userCategoryId,
                    website='$userWebsite',
                    bio='$userBio',
                    date_modified=NOW()
                WHERE id=$gUserId";

        echo mysqli_query($db, $sql)
            ? json_encode(['status' => 'OK', 'message' => 'Profile updated successfully.'])
            : json_encode(['status' => 'error', 'message' => 'Database error: ' . mysqli_error($db)]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Name, Email and Phone number are required.']);
    }
}

if ($act == 'createAccount') {
    $signFullName = $_POST['signFullName'] ?? '';
    $signEmail = $_POST['signEmail'] ?? '';
    $userPhone = $_POST['userPhone'] ?? '';
    $countryCode = $_POST['countryCode'] ?? ''; // ✅ NEW
    $pincode = $_POST['pincode'] ?? ''; // ✅ NEW
    $signPwd = $_POST['signPwd1'] ?? '';
    $signCompany = $_POST['signCompany'] ?? '';
    $signWebsite = $_POST['signWebsite'] ?? '';
    $signBusinessType = $_POST['signBusinessType'] ?? '';

    if ($signFullName != '' && $signEmail != '' && $signPwd != '') {

        // ✅ Validate country code
        if (!preg_match('/^\+\d{1,4}$/', $countryCode)) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid country code.']);
            exit;
        }

        // ✅ Validate phone format (digits, hyphens, etc.)
        if (!preg_match('/^[0-9\s\-\(\)]{7,20}$/', $userPhone)) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid phone number.']);
            exit;
        }

        // ✅ Validate pincode
        if (!$pincode || empty($pincode)) {
            echo json_encode(['status' => 'error', 'message' => 'Pincode is required.']);
            exit;
        }

        // ✅ Prepare insert query — add `country_code` and `pincode` to your `user` table first!
        $stmt = $db->prepare("INSERT INTO user(full_name, company, email, phone_no, country_code, pincode, password, website, num_visits, date_created) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())");

        $stmt->bind_param("ssssssss", $signFullName, $signCompany, $signEmail, $userPhone, $countryCode, $pincode, $signPwd, $signWebsite);

        if ($stmt->execute()) {
            $userId = $stmt->insert_id;

            $ip = getenv('HTTP_CLIENT_IP') ?: getenv('HTTP_X_FORWARDED_FOR') ?: getenv('HTTP_X_FORWARDED') ?: getenv('HTTP_FORWARDED_FOR') ?: getenv('HTTP_FORWARDED') ?: getenv('REMOTE_ADDR');

            $stmtLogin = $db->prepare("INSERT INTO user_login(user_id, ip, date_login) VALUES (?, ?, NOW())");
            $stmtLogin->bind_param("is", $userId, $ip);

            if ($stmtLogin->execute()) {
                $_SESSION['userId'] = $userId;
                $_SESSION['userName'] = $signFullName;
                $_SESSION['userEmail'] = $signEmail;

                $activateURL = "https://newsjunction.net/activate.php?token=" . simpleEncDec($signEmail);
                $tmpHTML = "<html><body>";
                $tmpHTML .= "<div style=\"font-family:Arial;font-size:12px;\">\r\n";
                $tmpHTML .= "Dear $signFullName,<br><br>\r\n";
                $tmpHTML .= "You recently created an account on News Junction. Please click on the following link to activate your account:<br>\r\n";
                $tmpHTML .= "<a href=\"$activateURL\">$activateURL</a><br><br>\r\n";
                $tmpHTML .= "Warm Regards,<br>\r\nNews Junction<br>\r\n</div>";
                $tmpHTML .= "</body></html>";

                sendEmail($signFullName, $signEmail, '', 'News Junction: Activate your Account!', $tmpHTML);

                echo json_encode(['status' => 'OK', 'userId' => $userId]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Error inserting login data: ' . $stmtLogin->error]);
            }

            $stmtLogin->close();
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error inserting user data: ' . $stmt->error]);
        }

        $stmt->close();
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Missing required fields.']);
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
                $_SESSION['userId'] = $userId;
                $_SESSION['userName'] = $signFullName;
                $_SESSION['userEmail'] = $signEmail;
                $activateURL = "https://newsjunction.net/activate.php?token=" . simpleEncDec($signEmail);
                $tmpHTML = "";
                $tmpHTML .= "<html>";
                $tmpHTML .= "<body>";
                $tmpHTML .= "<div style=\"font-family:Arial;font-size:12px;\">\r\n";
                $tmpHTML .= "Dear $signFullName,<br><br>\r\n";
                $tmpHTML .= "You recently created an account on News Junction. Please click on the following link to activate your account:<br>\r\n";
                $tmpHTML .= "<a href=\"$activateURL\">$activateURL</a><br><br>\r\n";
                $tmpHTML .= "Warm Regards,<br>\r\n";
                $tmpHTML .= "News Junction<br>\r\n";
                $tmpHTML .= "</body>";
                $tmpHTML .= "</html>";
                sendEmail($signFullName, $signEmail, '', 'News Junction: Activate your Account!', $tmpHTML);
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


if ($act == 'getBusinessType') {
    $returnArr = [];
    $result = mysqli_query($db, "SELECT id,category FROM category ORDER BY category");
    while ($row = mysqli_fetch_array($result)) {
        $returnArr[] = [$row['id'], $row['category']];
    }
    echo json_encode($returnArr);
}

if ($act == 'showForgotPassword') {
?>
    <div class="popup">
        <div class="widget">
            <div class="card" style="margin-bottom: 10px;">
                <div class="card-header bg-dark">
                    <h5 class="mb-0 text-light">Forgot Password</h5>
                </div>
            </div>
            <div id="widget_B">
                <div class="form-group">
                    <label for="forgotLogin">Enter your Login</label>
                    <input type="text" class="form-control" id="forgotLogin" name="forgotLogin" maxlength="100" />
                    <small class="form-text text-muted">Please enter the email with which you signed up</small>
                </div>
            </div>
            <div id="widget_F">
                <button type="submit" class="btn btn-primary" onclick="return chkResetPassword()">Reset Password</button>
                <div id="panelStatus" class="float-right text-sm" style="margin-top:5px" align="right"></div>
            </div>
        </div>
    </div>
<?php
}

if ($act == 'createLead') {
    $leadName = $_POST['leadName'] ?? '';
    $leadCompany = $_POST['leadCompany'] ?? '';
    $leadEmail = $_POST['leadEmail'] ?? '';
    $leadMobile = $_POST['leadMobile'] ?? '';
    $leadCollectionId = $_POST['leadCollectionId'] ?? '';
    if ($leadCollectionId && $leadName) {
        $ip = $_SERVER['REMOTE_ADDR'];
        mysqli_query($db, "INSERT INTO user_collection_lead(article_id,ip,full_name,company,email,mobile,date_created) 
            VALUES($leadCollectionId,'$ip','$leadName','$leadCompany','$leadEmail','$leadMobile',Now())");

        $result = mysqli_query($db, "SELECT id,title,url,read_more_email FROM user_collection WHERE id=$leadCollectionId");
        if ($row = mysqli_fetch_assoc($result)) {
            $collectionLink = 'https://newsjunction.net/view/' . $row['id'] . '/' . createArticleURL($row['title']);
            $readEmail = $row['read_more_email'];
            if ($readEmail) {
                $tmpHTML = "<html><body><div style=\"font-family:Arial;font-size:12px;\">\r\n";
                $tmpHTML .= "Dear News Junction user,<br><br>Lead submitted:<br><br>\r\n";
                $tmpHTML .= "URL: $collectionLink<br>Name: $leadName<br>Company: $leadCompany<br>Email: $leadEmail<br>Mobile: $leadMobile<br><br>Warm Regards,<br>News Junction</div></body></html>";
                sendEmail('News Junction User', $readEmail, '', 'Lead details from News Junction', $tmpHTML);
            }
        }
        echo 'OK';
    }
}

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
                Send in your details by <a href="https://newsjunction.net/more.php?id=2655">clicking here</a>. We will get in touch with you and help you with Pro set ups and payment options.
            </div>
        </div>
    </div>
<?php
}
?>