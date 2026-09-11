<?php
include './inc/php/db_config.php';
include './inc/php/validate.logged.php';
include './inc/php/function.php';

function getPlanEndInDays($endDate)
{
    $currentDate = new DateTime();
    $endDateObj = new DateTime($endDate);
    $diff = $currentDate->diff($endDateObj);
    $daysLeft = $diff->days;
    if ($currentDate > $endDateObj) {
        return "The plan has already ended.";
    }
    return $daysLeft;
}

function getIdByUserId($userId, $creamdb)
{
    $stmt = $creamdb->prepare("SELECT referrer_id FROM referral_info WHERE referred_id = ?");
    $stmt->bind_param("s", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        return $row['referrer_id'];
    }
    return null;
}

function getFollowCounts($userId, $readerdb)
{
    $sql = "
        SELECT 
            (SELECT COUNT(*) FROM reader_stream_follow WHERE following_id = ?) AS followers,
            (SELECT COUNT(*) FROM reader_stream_follow WHERE follower_id = ?) AS following
    ";

    $stmt = $readerdb->prepare($sql);
    $stmt->bind_param("ss", $userId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        return [
            'followers' => $row['followers'],
            'following' => $row['following']
        ];
    }

    return ['followers' => 0, 'following' => 0];
}

$followData = getFollowCounts($gUserId, $readerdb);
$followerCount = $followData['followers'];
$followingCount = $followData['following'];

function show_pro_details()
{
    global $creamdb, $gUserId;
    $end_time_left = '';
    $sql = "SELECT * FROM cream_subscription WHERE userId = ? ORDER BY end_date DESC";
    if ($stmt = $creamdb->prepare($sql)) {
        $stmt->bind_param("i", $gUserId);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
?>
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Plan</th>
                            <th>Started On</th>
                            <th>Ends On</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()) {
                            $end_time_left = ($end_time_left == '') ? $row['end_date'] : $end_time_left;
                        ?>
                            <tr>
                                <td><?= $row['plan'] ?></td>
                                <td><?= date('d M, Y h:i a', strtotime($row['start_date'])) ?></td>
                                <td><?= date('d M, Y h:i a', strtotime($row['end_date'])) ?></td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
                <p class="text-muted"><strong>Your Plan Ends in:</strong> <?= getPlanEndInDays($end_time_left) ?> Days</p>
<?php
            }
        }
    }
}

$specialIds = [370, 415];
$act = '';
if (!empty($_POST)) {
    $act = isset($_POST["act"]) ? $_POST["act"] : '';
}

if ($act == 'chkExist') {
    $chkEmail = isset($_POST['email']) ? $_POST['email'] : '';
    $sql = "SELECT id FROM user WHERE email='$chkEmail' AND id<>$gUserId";
    $result = mysqli_query($creamdb, $sql);
    $numRows = mysqli_num_rows($result);
    if ($numRows == 0) {
        echo 'OK';
    }
}

if ($act == 'updateProfile') {
    $userName = isset($_POST['userName']) ? $_POST['userName'] : '';
    $userEmail = isset($_POST['userEmail']) ? $_POST['userEmail'] : '';
    $userPincode = isset($_POST['userPincode']) ? $_POST['userPincode'] : '';
    $userCompany = isset($_POST['userCompany']) ? $_POST['userCompany'] : '';
    $userCategoryId = isset($_POST['userCategoryId']) ? $_POST['userCategoryId'] : 0;
    $userWebsite = isset($_POST['userWebsite']) ? $_POST['userWebsite'] : '';
    $userBio = isset($_POST['userBio']) ? $_POST['userBio'] : '';
    $countryCode = isset($_POST['countryCode']) ? trim($_POST['countryCode']) : '';
    $localPhone = isset($_POST['userPhone']) ? trim($_POST['userPhone']) : '';
    $localPhone = ltrim(str_replace([' ', '-'], '', $localPhone), '0');


    if (!preg_match('/^\d{7,15}$/', $localPhone)) {
        echo "Invalid local phone number.";
        exit;
    }
    if (!preg_match('/^\+\d{1,4}$/', $countryCode)) {
        echo "Invalid country code.";
        exit;
    }

    if ($userName != '' && $userEmail != '') {
        $sql = "UPDATE user 
                SET full_name = '$userName',
                    email = '$userEmail',
                    pincode = '$userPincode',
                    phone_no = '$localPhone',
                    country_code = '$countryCode',
                    company = '$userCompany',
                    category_id = $userCategoryId,
                    website = '$userWebsite',
                    bio = '$userBio',
                    date_modified = NOW() 
                WHERE id = $gUserId";
        mysqli_query($creamdb, $sql);
        
        // Update session with new pincode
        $_SESSION['userPincode'] = $userPincode;
        
        // Update cookie if it exists
        if (isset($_COOKIE['knobly_user_data'])) {
            $userData = json_decode($_COOKIE['knobly_user_data'], true);
            if ($userData) {
                $userData['userPincode'] = $userPincode;
                setcookie('knobly_user_data', json_encode($userData), time() + (86400 * 365), '/');
            }
        }
        
        echo "OK";
    }
}

if ($act == 'updatePass') {
    $userPassCurrent = isset($_POST['userPassCurrent']) ? $_POST['userPassCurrent'] : '';
    $userPassNew = isset($_POST['userPassNew']) ? $_POST['userPassNew'] : '';
    if ($userPassCurrent != '' && $userPassNew != '') {
        $sql = "SELECT id FROM user WHERE id=$gUserId AND password='$userPassCurrent'";
        $result = mysqli_query($creamdb, $sql);
        $numRows = mysqli_num_rows($result);
        if ($numRows == 0) {
            echo "IncorrectPassword";
            die();
        }
        $sql = "UPDATE user SET password='$userPassNew',date_modified=NOW() WHERE id=$gUserId";
        mysqli_query($creamdb, $sql);
        echo "OK";
    }
}

$referralCode = getIdByUserId($gUserId, $creamdb);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>News Junction: My Profile</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
    <link rel="icon" type="image/x-icon" href="../grfx/img/logo.ico">
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css" rel="stylesheet">
    <link rel="stylesheet" href="inc/css/social.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
    <script src="https://js.zohostatic.com/books/zfwidgets/assets/js/zf-widget.js"></script>
    <script src="inc/js/common.js"></script>

    <style>
        .containers {
            max-width: 900px;
            margin: 20px auto;
        }

        .profile-header {
            gap: 30px;
            display: flex;
            align-items: center;
            padding: 20px;
            background-color: var(--bg-card);
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .profile-pic {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 1px solid var(--text-primary);
            margin-right: 20px;
        }


        .profile-info {
            flex-grow: 1;
        }

        .profile-info h2 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }

        .profile-info p {
            margin: 5px 0;
            color: #666;
        }

        .btn-primary {
            background-color: #db5919;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            color: #fff;
            transition: background-color 0.3s ease;
        }

        .btn-primary:hover {
            background-color: #c04e14;
        }

        .btn-secondary {
            background-color: #6c757d;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            color: #fff;
        }

        .nav-tabs {
            border-bottom: 2px solid #6c757d;
            margin-bottom: 20px;
        }

        .form-control:disabled,
        .form-control[readonly] {
            background-color: var(--bg-card);
            opacity: 1;
        }

        .nav-tabs .nav-item .nav-btn {
            padding: 10px 10px;
            font-size: 16px;
            font-weight: 500;
            color: var(--text-primary);
            background-color: transparent;
            border: none;
            border-bottom: 3px solid transparent;
            transition: all 0.3s ease;
        }


        .card {
            background-color: var(--bg-card);
            border-radius: 10px;
            box-shadow: var(--card-shadow);
            padding: 20px;
            margin-bottom: 20px;
        }

        .form-group label {
            font-weight: 500;
            color: var(--text-primary);
        }

        .form-control {
            border-radius: 5px;
            border: 1px solid var(--border-color);
            padding: 10px;
        }

        .modal-content {
            background-color: var(--bg-card);
            border-radius: 10px;
        }

        .modal-header,
        .modal-footer {
            border: none;
        }

        .modal-title {
            color: var(--text-primary);
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            background-color: var(--bg-card);
        }

        .table th {
            background-color: var(--bg-card);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
            padding: 12px;
        }

        .table td {
            color: var(--text-primary);
            padding: 12px;
            border: 1px solid var(--border-color);
        }

        .zf-widget-root-id {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }

        .zf-widget-root-id .card {
            width: calc(50% - 10px);
        }

        .upgrade-button {
            width: 100%;
            background-color: #db5919;
            color: #fff;
            border: none;
            padding: 10px;
            border-radius: 5px;
            font-size: 16px;
        }

        .upgrade-button:hover {
            background-color: #c04e14;
        }

        .follow-info {
            display: flex;
            gap: 20px;
            margin-left: auto;
        }


        @media (max-width: 500px) {
            .profile-header {
                gap: 10px;

            }

            .follow-info {
                flex-direction: column;
                gap: 10px;
            }
        }

        @media (max-width: 768px) {
            .profile-header {
                align-items: flex-start;
            }

            .profile-pic {
                margin-bottom: 20px;
                width: 85px;
                height: 85px;
            }

            .zf-widget-root-id .card {
                width: 100%;
            }

            .nav-tabs {
                display: flex;
                white-space: nowrap;
            }

            .nav-btn {
                font-size: 14px;
                padding: 8px 15px;
            }
        }


        .edit-icon {
            position: absolute;
            bottom: 5px;
            right: 5px;
            color: var(--text-primary);
            padding: 6px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 14px;
        }

        .follow-number {
            font-size: 20px;
            font-weight: bold;
        }

        .follow-label {
            font-size: 14px;
            color: #666;
        }


        .nav-btn.active {
            color: #db5919 !important;
        }
    </style>

    <script>
        $(document).ready(function() {
            let cropper;

            $("#fileInput").on("change", function(event) {
                const file = event.target.files[0];
                if (file) {
                    $("#imageContainer").show();
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        $("#imagePreview").attr("src", e.target.result);
                        const $image = $("#imagePreview")[0];
                        if (cropper) {
                            cropper.destroy();
                        }
                        cropper = new Cropper($image, {
                            aspectRatio: 1,
                            viewMode: 2,
                            autoCropArea: 0.8,
                            movable: true,
                            zoomable: true,
                            rotatable: true,
                            scalable: true,
                            cropBoxResizable: true,
                            minContainerWidth: 300,
                            minContainerHeight: 300,
                            minZoom: 0.5,
                            maxZoom: 3,
                            ready: function() {
                                const cropBoxData = cropper.getCropBoxData();
                                cropper.setCropBoxData({
                                    left: cropBoxData.left,
                                    top: cropBoxData.top,
                                    width: 300,
                                    height: 300
                                });
                            },
                            cropBoxMinWidth: 50,
                            cropBoxMinHeight: 50,
                            cropBoxMaxWidth: 300,
                            cropBoxMaxHeight: 300
                        });
                    };
                    reader.readAsDataURL(file);
                }
            });

            $("#saveProfileBtn").on("click", function() {
                if (cropper) {
                    const $button = $(this);
                    const originalText = $button.text();
                    $button.prop("disabled", true).text("Processing...");
                    const canvas = cropper.getCroppedCanvas();
                    const imageData = canvas.toDataURL("image/png");
                    $("#uploadForm").append(`<input type="hidden" name="image_state" value="${imageData}" />`);
                    const formData = new FormData($("#uploadForm")[0]);

                    $.ajax({
                        url: "save_profile.php",
                        type: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            if (response.status === 'success') {
                                alert(response.message);
                                $("#profileImagePreview").attr("src", response.image_path);
                                $('#editProfileModal').modal('hide');
                                window.location.reload();
                            } else {
                                alert(response.message);
                            }
                        },
                        error: function() {
                            alert("An error occurred while saving your profile.");
                        },
                        complete: function() {
                            $button.prop("disabled", false).text(originalText);
                        }
                    });
                }
            });

            function chkUpdateProfile() {
                $("#panelStatus").html("");
                var userName = $("#userName").val();
                var userEmail = $("#userEmail").val();
                var userPincode = $("#userPincode").val();
                var userPhone = $("#userPhone").val();
                var countryCode = $("#countryCode").val();
                var userCompany = $("#userCompany").val();
                var userCategoryId = $("#userCategoryId").val();
                var userWebsite = $("#userWebsite").val();
                var userBio = $("#userBio").val();

                if (userName == "") {
                    $("#panelStatus").html('<div class="alert alert-danger animate__animated animate__flash" role="alert">Error: Full Name not entered!</div>');
                    return false;
                }
                if (userEmail == "") {
                    $("#panelStatus").html('<div class="alert alert-danger animate__animated animate__flash" role="alert">Error: Email not entered!</div>');
                    return false;
                }

                $.ajax({
                    method: "POST",
                    url: "process/get.section.account.php",
                    data: {
                        act: "chkExist",
                        email: userEmail
                    },
                }).done(function(response) {
                    if (response == "OK") {
                        $.ajax({
                            method: "POST",
                            url: "process/get.section.account.php",
                            data: {
                                act: "updateProfile",
                                userName: userName,
                                userEmail: userEmail,
                                userPincode: userPincode,
                                userPhone: userPhone,
                                countryCode: countryCode,
                                userCompany: userCompany,
                                userCategoryId: userCategoryId,
                                userWebsite: userWebsite,
                                userBio: userBio,
                            },
                        }).done(function(response) {
                            if (response == "OK") {
                                $("#panelStatus").html('<div class="alert alert-primary" role="alert">Profile has been updated!</div>');
                            } else {
                                $("#panelStatus").html('<div class="alert alert-danger animate__animated animate__flash" role="alert">Error: Profile could not be updated!</div>');
                            }
                        });
                    } else {
                        $("#panelStatus").html('<div class="alert alert-danger animate__animated animate__flash" role="alert">Error: This email is already being used!</div>');
                    }
                });
                return false;
            }

            function chkUpdatePassword() {
                $("#panelStatus").html("");
                var userPassCurrent = $("#userPassCurrent").val();
                var userPassNew1 = $("#userPassNew1").val();
                var userPassNew2 = $("#userPassNew2").val();

                if (userPassCurrent == "" || userPassNew1 == "" || userPassNew2 == "") {
                    $("#panelStatus").html('<div class="alert alert-danger animate__animated animate__flash" role="alert">Error: All password fields are required!</div>');
                    return false;
                }
                if (userPassNew1 !== userPassNew2) {
                    $("#panelStatus").html('<div class="alert alert-danger animate__animated animate__flash" role="alert">Error: New passwords do not match!</div>');
                    return false;
                }

                $.ajax({
                    method: "POST",
                    url: "process/get.section.account.php",
                    data: {
                        act: "updatePass",
                        userPassCurrent: userPassCurrent,
                        userPassNew: userPassNew1
                    },
                }).done(function(response) {
                    if (response == "OK") {
                        $("#panelStatus").html('<div class="alert alert-primary" role="alert">Password has been updated!</div>');
                    } else if (response == "IncorrectPassword") {
                        $("#panelStatus").html('<div class="alert alert-danger animate__animated animate__flash" role="alert">Error: Current password is incorrect!</div>');
                    } else {
                        $("#panelStatus").html('<div class="alert alert-danger animate__animated animate__flash" role="alert">Error: Password could not be updated!</div>');
                    }
                });
                return false;
            }

            window.chkUpdateProfile = chkUpdateProfile;
            window.chkUpdatePassword = chkUpdatePassword;
        });
    </script>
</head>

<body>
    <!-- Edit Profile Modal -->
    <div class="modal fade" id="editProfileModal" tabindex="-1" role="dialog" aria-labelledby="editProfileModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editProfileModalLabel">Edit Profile Picture</h5>
                    <button type="button" class="btn-close" data-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="uploadForm" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="fileInput" class="form-label">Upload Profile Picture</label>
                            <input type="file" name="profile_pic" accept="image/*" id="fileInput" class="form-control" required />
                        </div>
                        <div id="imageContainer" style="display:none;">
                            <img id="imagePreview" src="" alt="Image Preview" style="max-width: 100%;" />
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveProfileBtn">Save Changes</button>
                </div>
            </div>
        </div>
    </div>

    <div class="containers">
        <?php include 'inc/php/social_navbar.php'; ?>
        <?php include 'inc/php/social_sidebar.php'; ?>

        <main class="search-main-content main-content">
            <div class="container-fluid  col-sm-12 col-md-12 sideMaincontent">

                <?php
                $sql = "SELECT * FROM user WHERE id = $gUserId";
                $result = mysqli_query($creamdb, $sql);
                $numRows = mysqli_num_rows($result);

                if ($numRows > 0) {
                    $row = mysqli_fetch_assoc($result);
                    $userName = $row['full_name'];
                    $userCompany = $row['company'];
                    $userEmail = $row['email'];
                    $userPincode = $row['pincode'];
                    $userPhone = $row['phone_no'];
                    $userWebsite = $row['website'];
                    $userCategoryId = $row['category_id'];
                    $userBio = $row['bio'];
                    $countryCode = $row['country_code'];

                    $landingPageURL = "https://newsjunction.net/showcase.php?id=".$gUserId;
                ?>
                    <div class="profile-header">
                        <!-- Profile Image with Edit Icon -->
                        <div style="position: relative; display: inline-block;">
                            <div class="edit-icon" data-toggle="modal" data-target="#editProfileModal">
                                <i class="fas fa-pencil-alt"></i>
                            </div>
                            <img id="profilePic" src="<?= viewProfilePic($creamdb, $gUserId) ?>" alt="Profile Picture" class="profile-pic" onerror="this.onerror=null; this.src='/data/profilePic/default.png';">
                        </div>

                        <!-- Profile Info -->
                        <div class="profile-info">
                            <h2><?= htmlspecialchars($userName) ?></h2>
                            <p><?= htmlspecialchars($userCompany)?></p>
                            <p><a href="<?= $landingPageURL ?>" target="_blank">Showcase Page</a></p>
                        </div>

                        <!-- Follow Info -->
                        <div class="follow-info">
                            <div class="follow-block" style="text-align: center;">
                                <div class="follow-number"><?= $followerCount ?></div>
                                <div class="follow-label">Followers</div>
                            </div>
                            <div class="follow-block" style="text-align: center;">
                                <div class="follow-number"><?= $followingCount ?></div>
                                <div class="follow-label">Following</div>
                            </div>
                        </div>
                    </div>



                    <ul class="nav nav-tabs">
                        <li class="nav-item"><a class="nav-btn active" data-toggle="tab" href="#profile" role="tab" onclick="$('#panelStatus').html('')">Profile</a></li>
                        <li class="nav-item"><a class="nav-btn" data-toggle="tab" href="#password" role="tab" onclick="$('#panelStatus').html('')">Password</a></li>
                        
                        <!-- <li class="nav-item"><a class="nav-btn" data-toggle="tab" href="#plan" role="tab" onclick="$('#panelStatus').html('')">Plan</a></li> -->
                       
                        <li class="nav-item"><a class="nav-btn" href="profile.php?userId=<?= $gUserId ?>">Posts</a></li>

                    </ul>

                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="profile" role="tabpanel">
                            <div class="card">
                                <h3>Profile Details</h3>
                                <div class="form-group">
                                    <label for="userName">Full Name</label>
                                    <input type="text" class="form-control" id="userName" name="userName" value="<?= htmlspecialchars($userName) ?>" maxlength="100" />
                                </div>
                                <div class="form-group">
                                    <label for="userEmail">Email</label>
                                    <input type="email" class="form-control" id="userEmail" name="userEmail" value="<?= htmlspecialchars($userEmail) ?>" maxlength="100" readonly />
                                </div>
                                <div class="form-group">
                                    <label for="userPincode">Pincode</label>
                                    <input type="text" class="form-control" id="userPincode" name="userPincode" value="<?= htmlspecialchars($userPincode) ?>" maxlength="100"/>
                                </div>
                                <div class="form-group">
                                    <label for="userPhone">Phone Number</label>
                                    <div class="row">
                                        <div class="col-4">
                                            <select class="form-control" id="countryCode" name="countryCode" style="padding: 0px;" required>
                                                <option value="+91" <?= ($countryCode == '+91') ? 'selected' : '' ?>>🇮🇳 +91 (Ind)</option>
                                                <option value="+1" <?= ($countryCode == '+1') ? 'selected' : '' ?>>🇺🇸 +1 (USA)</option>
                                                <option value="+44" <?= ($countryCode == '+44') ? 'selected' : '' ?>>🇬🇧 +44 (UK)</option>
                                                <option value="+61" <?= ($countryCode == '+61') ? 'selected' : '' ?>>🇦🇺 +61 (Aus)</option>
                                                <option value="+81" <?= ($countryCode == '+81') ? 'selected' : '' ?>>🇯🇵 +81 (Jap)</option>
                                                <option value="+49" <?= ($countryCode == '+49') ? 'selected' : '' ?>>🇩🇪 +49 (Ger)</option>
                                                <option value="+33" <?= ($countryCode == '+33') ? 'selected' : '' ?>>🇫🇷 +33 (Frc)</option>
                                                <option value="+971" <?= ($countryCode == '+971') ? 'selected' : '' ?>>🇦🇪 +971 (UAE)</option>
                                                <option value="+63" <?= ($countryCode == '+63') ? 'selected' : '' ?>>🇵🇭 +63 (Philippines)</option>
                                                <option value="+234" <?= ($countryCode == '+234') ? 'selected' : '' ?>>🇳🇬 +234 (Nigeria)</option>
                                            </select>
                                        </div>
                                        <div class="col-8">
                                            <input type="tel" class="form-control" id="userPhone" name="userPhone" value="<?= htmlspecialchars($userPhone) ?>" maxlength="15" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="userCompany">Company</label>
                                    <input type="text" class="form-control" id="userCompany" name="userCompany" value="<?= htmlspecialchars($userCompany) ?>" maxlength="100" />
                                </div>
                                <div class="form-group">
                                    <label for="userBio">Bio</label>
                                    <textarea class="form-control" id="userBio" name="userBio" maxlength="100" rows="4"><?= htmlspecialchars($userBio) ?></textarea>
                                </div>
                                <button class="btn btn-primary" onclick="return chkUpdateProfile()">Update Profile</button>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="password" role="tabpanel">
                            <div class="card">
                                <h3>Change Password</h3>
                                <div class="form-group">
                                    <label for="userPassCurrent">Current Password</label>
                                    <input type="password" class="form-control" id="userPassCurrent" name="userPassCurrent" maxlength="20" />
                                </div>
                                <div class="form-group">
                                    <label for="userPassNew1">New Password</label>
                                    <input type="password" class="form-control" id="userPassNew1" name="userPassNew1" maxlength="20" />
                                </div>
                                <div class="form-group">
                                    <label for="userPassNew2">Retype New Password</label>
                                    <input type="password" class="form-control" id="userPassNew2" name="userPassNew2" maxlength="20" />
                                </div>
                                <button class="btn btn-primary" onclick="return chkUpdatePassword()">Update Password</button>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="plan" role="tabpanel">
                            <div class="card">
                                <h3>Subscription Plan</h3>
                                <?php if ($gUserPlan == 1) { ?>
                                    <p>You are on a <strong>Pro</strong> plan.</p>
                                    <?php show_pro_details(); ?>
                                <?php } else { ?>
                                    <p>You are on a <strong>Free</strong> plan. Upgrade to access more features.</p>
                                    <div class="zf-widget-root-id">
                                        <div class="card">
                                            <div class="card-content">
                                                <h2 class="plan-title">Monthly</h2>
                                                <p class="plan-price">₹2400</p>
                                                <p class="plan-subtext">Billed Monthly</p>
                                                <p class="plan-description">
                                                    <span class="checkmark">✔</span> Access to complete Pro features
                                                </p>
                                                <button onclick="window.location.href='premium.php'" class="upgrade-button">Upgrade to Pro Plan</button>
                                            </div>
                                        </div>
                                        <div class="card">
                                            <div class="card-content">
                                                <h2 class="plan-title">Annual</h2>
                                                <p class="plan-price">₹24000</p>
                                                <p class="plan-subtext">Billed Annually</p>
                                                <p class="plan-description">
                                                    <span class="checkmark">✔</span> Save with annual billing
                                                </p>
                                                <button onclick="window.location.href='premium.php'" class="upgrade-button">Upgrade to Pro Plan</button>
                                            </div>
                                        </div>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="invite" role="tabpanel">
                            <div class="card">
                                <h3>Invite Code</h3>
                                <?php
                                $inviteCodes = [
                                    1 => 'KCPH1000',
                                    2 => 'KCPB1000',
                                    3 => 'KCAK1000',
                                    4 => 'KCAD1000',
                                    5 => 'KCRD1000',
                                    6 => 'KCSJ1000',
                                    7 => 'KCSG1000',
                                    8 => 'KCMA1000',
                                    9 => 'KCHP1000',
                                    10 => 'KCSR1000'
                                ];
                                if (isset($inviteCodes[$referralCode])) {
                                    echo "<p>Your invite code: <strong>{$inviteCodes[$referralCode]}</strong></p>";
                                }
                                ?>
                            </div>
                        </div>
                        <div id="panelStatus" class="mt-4"></div>
                    </div>
                <?php } ?>
            </div>
            <?php include 'inc/php/footer.php'; ?>

        </main>
    </div>
</body>

</html>