<?
include './inc/php/db_config.php';
// include 'inc/config.php';
require_once 'inc/php/validate.logged.php';
include 'inc/php/function.php';
$specialIds = [370, 415];

// Cream: My Settings
$act = '';
if (!empty($_POST)) $act = isset($_POST["act"]) ? $_POST["act"] : '';

function fetchProfession($creamdb, $userId)
{
    $sql = "SELECT profession FROM user WHERE id = ?";
    $stmt = $creamdb->prepare($sql);
    if (!$stmt) {
        die("Prepare failed: " . $creamdb->error);
    }
    $stmt->bind_param("i", $userId);
    if (!$stmt->execute()) {
        die("Execute failed: " . $stmt->error);
    }
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $profession = $row['profession'];
    } else {
        $profession = null;
    }
    $stmt->close();
    return $profession;
}

$selectedProfession = fetchProfession($creamdb, $gUserId);


// Check if Subdomain exists
if ($act == 'chkExistSubdomain') {
    $chkSubdomain = isset($_POST['subdomain']) ? $_POST['subdomain'] : '';
    if ($chkSubdomain == '') {
        echo 'OK';
    } else {
        $chkSubdomain = strtolower($chkSubdomain);
        $sql = "SELECT id FROM user WHERE subdomain='$chkSubdomain' AND id<>$gUserId";
        $result = mysqli_query($creamdb, $sql);
        $numRows = mysqli_num_rows($result);
        if ($numRows == 0) {
            echo 'OK';
        }
    }
}


// Get Cream Feeds
if ($act == 'getCreamFeeds') {
    $categoryId = isset($_POST['categoryId']) ? $_POST['categoryId'] : '';
    if ($categoryId <> '') {
        $returnHTML = '';
        $sql = "SELECT * FROM feeds_rss WHERE category_id=$categoryId ORDER BY feed_url";
        $result = mysqli_query($creamdb, $sql);
        $numRows = mysqli_num_rows($result);
        if ($numRows > 0) {
            $returnHTML .= 'Select Feeds';
            for ($i = 0; $i < $numRows; $i++) {
                $row = mysqli_fetch_array($result);
                $returnHTML .= '<div class="row my-3">';
                $returnHTML .= ' <div class="col-1"><input type="checkbox" id="feedData" name="feedData" value="' . $row['id'] . '" onclick="addCreamFeed()"></div>';
                $returnHTML .= ' <div class="col">' . $row['feed_url'] . '</div>';
                $returnHTML .= '</div>';
            }
        }
        echo $returnHTML;
    }
}


// Added Cream Feed
if ($act == 'addedCreamFeed') {
    $data = isset($_POST['data']) ? $_POST['data'] : '';
    if ($data != '') {
        $arrData = json_decode($data, true);
        foreach ($arrData as &$value) {
            $feedId = $value['id'];
            $sql = "SELECT category_id,feed_url FROM feeds_rss WHERE id=$feedId";
            $result = mysqli_query($creamdb, $sql);
            $numRows = mysqli_num_rows($result);
            if ($numRows > 0) {
                $row = mysqli_fetch_array($result);
                $categoryId = $row['category_id'];
                $feedURL = $row['feed_url'];
                $sql = "SELECT id FROM user_feeds WHERE feed_url='$feedURL' AND user_id=$gUserId";
                $resultInner = mysqli_query($creamdb, $sql);
                $numRowsInner = mysqli_num_rows($resultInner);
                if ($numRowsInner == 0) {
                    $sql = "INSERT INTO user_feeds(user_id,category_id,feed_url,is_feed_cream) VALUES($gUserId,$categoryId,'$feedURL',1)";
                    mysqli_query($creamdb, $sql);
                }
            }
        }
        echo "OK";
    }
}


// Added External Feed
if ($act == 'addedExternalFeed') {
    $categoryId = isset($_POST['categoryId']) ? $_POST['categoryId'] : '';
    $feedURL = isset($_POST['feedURL']) ? $_POST['feedURL'] : '';
    if ($categoryId != '' && $feedURL != '') {
        $sql = "INSERT INTO user_feeds(user_id,category_id,feed_url,is_feed_cream) VALUES($gUserId,$categoryId,'$feedURL',0)";
        mysqli_query($creamdb, $sql);
        echo "OK";
    }
}


// Delete User Feed
if ($act == 'deleteFeed') {
    $feedId = isset($_POST['delId']) ? $_POST['delId'] : '';
    if ($feedId != '') {
        $sql = "DELETE FROM user_feeds WHERE id=$feedId AND user_id=$gUserId";
        mysqli_query($creamdb, $sql);
        echo "OK";
    }
}


// Update Subdomain
if ($act == 'editSubdomain') {
    $userSubdomain = isset($_POST['subdomain']) ? $_POST['subdomain'] : '';
    $_SESSION['userSubdomain'] = $userSubdomain;
    if ($userSubdomain == '') {
        $userSubdomain = NULL;
    } else {
        $userSubdomain = strtolower($userSubdomain);
    }
    $sql = "UPDATE user SET subdomain='$userSubdomain',date_modified=Now() WHERE id=$gUserId";
    mysqli_query($creamdb, $sql);
    echo "OK";
}


// Update View Page Settings
if ($act == 'editView') {
    $userSidePanel = isset($_POST['userSidePanel']) ? $_POST['userSidePanel'] : '';
    $userSidePanelContent = isset($_POST['userSidePanelContent']) ? $_POST['userSidePanelContent'] : '';
    $sql = "UPDATE user SET is_side_panel=$userSidePanel,side_panel_content='$userSidePanelContent',date_modified=Now() WHERE id=$gUserId";
    mysqli_query($creamdb, $sql);
    echo "OK";
}


// Update Newsletter Settings
if ($act == 'editNewsletter') {
    $userNewsletterTitle = isset($_POST['userNewsletterTitle']) ? $_POST['userNewsletterTitle'] : '';
    // $nlamount = ($_POST['userNewsletterAmount'] == '') ? 0 : $_POST['userNewsletterAmount'];
    if ($userNewsletterTitle != '') {
        $fileUpload = '';
        if (isset($_FILES['userNewsletterLogo'])) {
            $temp = $_FILES['userNewsletterLogo'];
            if (is_uploaded_file($temp['tmp_name'])) {
                $fileExt = strtolower(pathinfo($temp['name'], PATHINFO_EXTENSION));
                $fileUpload = $gUserId . '-' . time() . '.' . $fileExt;

                // Ensure that the directory exists before moving the file
                $uploadDir = 'data/logos/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true); // Create directory if it doesn't exist
                }

                if (!move_uploaded_file($temp['tmp_name'], $uploadDir . $fileUpload)) {
                    echo "Error uploading file.";
                    exit;
                }
            }
        }

        // Prepare the SQL query
        $sql = "UPDATE user SET news_title='$userNewsletterTitle', news_logo='$fileUpload',  date_modified=Now() WHERE id=$gUserId";

        // Execute the query and check for success
        if (mysqli_query($creamdb, $sql)) {
            echo "OK";
        } else {
            echo "Error updating the newsletter information: " . mysqli_error($creamdb);
        }
    } else {
        echo "Newsletter title is required.";
    }
}



// Update Send Grid Key
if ($act == 'editSendGridKey') {
    $userSendGridKey = isset($_POST['userSendGridKey']) ? $_POST['userSendGridKey'] : '';
    $userSendGridSuppressionId = isset($_POST['userSendGridSuppressionId']) ? $_POST['userSendGridSuppressionId'] : '';
    if ($userSendGridKey != '' && $userSendGridSuppressionId != '') {
        $sql = "UPDATE user SET send_grid_key='$userSendGridKey',send_grid_suppression_id='$userSendGridSuppressionId',date_modified=Now() WHERE id=$gUserId";
        mysqli_query($creamdb, $sql);
        echo "OK";
    }
}


// Show Add from Cream Feed
if ($act == 'addCreamFeed') {
?>
    <div class="popup" style="width:420px">
        <div class="widget">
            <form id="frmAdd" name="frmAdd">
                <div class="card">
                    <div class="card-header bg-dark">
                        <h5 class="mb-0 text-light">Add from Cream Feed</h5>
                    </div>
                </div>
                <div id="widget_B" style="padding:15px 25px">
                    <?
                    if ($gUserPlan == 0) {
                        $sql = "SELECT id FROM user_feeds WHERE user_id=$gUserId";
                        $result = mysqli_query($creamdb, $sql);
                        $numFeed = mysqli_num_rows($result);
                        if ($numFeed >= 5) {
                            echo '<div class="alert alert-success" role="alert">You have exceeded the number of feeds.<br>Go to My Account to upgrade.</div>';
                            die();
                        }
                    }
                    ?>
                    <div class="form-group">
                        <label for="categoryId">Category</label>
                        <select id="categoryId" name="categoryId" class="form-control" onchange="getCreamFeeds()">
                            <option value="">--[select]--
                                <?
                                $sql = "SELECT id,category FROM category ORDER BY category";
                                $result = mysqli_query($creamdb, $sql);
                                $numRows = mysqli_num_rows($result);
                                if ($numRows > 0) {
                                    for ($i = 0; $i < $numRows; $i++) {
                                        $row = mysqli_fetch_array($result);
                                        $tmpCategoryId = $row['id'];
                                        $tmpCategoryName = $row['category'];
                                        echo '   <option value="' . $tmpCategoryId . '">' . $tmpCategoryName;
                                    }
                                }
                                ?>
                            <option value="0">Others
                        </select>
                    </div>
                    <div id="panelCreamFeeds">Select Category to see Feeds!</div>
                </div>
                <div id="widget_F" style="border-top:1px solid #ebedf2;padding:20px 10px;">
                    <div class="col">
                        <button class="btn btn-primary" onclick="return chkAddCreamFeed()">Add Selected Feed(s)</button>
                        <div id="panelStatus" class="float-right text-sm" style="margin-top:5px" align="right"></div>
                    </div>
                </div>
            </form>
        </div>
    </div>
<?
}


// Show Add External Feed
if ($act == 'addExternalFeed') {
?>
    <div class="popup" style="width:420px">
        <div class="widget">
            <form id="frmAdd" name="frmAdd">
                <div class="card">
                    <div class="card-header bg-dark">
                        <h5 class="mb-0 text-light">Add External Feed</h5>
                    </div>
                </div>
                <div id="widget_B" style="padding:15px 25px">
                    <?
                    if ($gUserPlan == 0) {
                        $sql = "SELECT id FROM user_feeds WHERE user_id=$gUserId";
                        $result = mysqli_query($creamdb, $sql);
                        $numFeed = mysqli_num_rows($result);
                        if ($numFeed >= 5) {
                            echo '<div class="alert alert-success" role="alert">You have exceeded the number of feeds.<br>Go to My Account to upgrade.</div>';
                            die();
                        }
                    }
                    ?>
                    <div class="form-group">
                        <label for="categoryId">Category</label>
                        <select id="categoryId" name="categoryId" class="form-control">
                            <option value="">--[select]--
                                <?
                                $sql = "SELECT id,category FROM category ORDER BY category";
                                $result = mysqli_query($creamdb, $sql);
                                $numRows = mysqli_num_rows($result);
                                if ($numRows > 0) {
                                    for ($i = 0; $i < $numRows; $i++) {
                                        $row = mysqli_fetch_array($result);
                                        $tmpCategoryId = $row['id'];
                                        $tmpCategoryName = $row['category'];
                                        echo '   <option value="' . $tmpCategoryId . '">' . $tmpCategoryName;
                                    }
                                }
                                ?>
                            <option value="0">Others
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="feedURL">Feed URL</label>
                        <input type="text" class="form-control" id="feedURL" name="feedURL" maxlength="200" />
                        <small class="form-text text-muted">Please enter the URL with http:// or https://</small>
                    </div>
                </div>
                <div id="widget_F" style="border-top:1px solid #ebedf2;padding:20px 10px;">
                    <div class="col">
                        <button type="submit" class="btn btn-primary" onclick="return chkAddExternalFeed()">Add Feed</button>
                        <div id="panelStatus" class="float-right text-sm" style="margin-top:5px" align="right"></div>
                    </div>
                </div>
            </form>
        </div>
    </div>
<?
}
// Show Delete Feed
if ($act == 'delFeed') {
    $feedId = isset($_POST['id']) ? $_POST['id'] : '';
?>
    <div class="popup" style="width:420px">
        <div class="widget">
            <form id="frmDel" name="frmDel">
                <div class="card">
                    <div class="card-header bg-dark">
                        <h5 class="mb-0 text-light">Delete Feed</h5>
                    </div>
                </div>
                <div id="widget_B" style="padding:15px 25px">
                    You are about to delete this feed from your account!
                </div>
                <div id="widget_F" style="border-top:1px solid #ebedf2;padding:20px 10px;">
                    <div class="col">
                        <button type="submit" class="btn btn-primary" onclick="return chkDelFeed()">Confirm</button>
                    </div>
                </div>
                <input type="hidden" id="delId" name="delId" value="<?= $feedId ?>" />
                <input type="hidden" id="act" name="act" value="deleteFeed" />
            </form>
        </div>
    </div>
<?
}

if ($act == 'genaiProfession') {
    $profession = $_POST['profession'];
    $sql = "UPDATE user SET profession = ? WHERE id = ?";
    $stmt = $creamdb->prepare($sql);
    $stmt->bind_param("si", $profession, $gUserId);
    if ($stmt->execute()) {
        echo "OK";
    } else {
        echo "Error updating record: " . $stmt->error;
    }
}

if ($act == '') {
?>
    <!doctype html>
    <html lang="en">

    <head>

        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <title>News Junction</title>
        <!-- jQuery -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

        <!-- Animate.css (Latest Version) -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />

        <!-- Favicon -->
        <link rel="icon" type="image/x-icon" href="../grfx/img/logo.ico">
        <!-- Bootstrap 4.5.2 (Update to 5 if needed) -->
        <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/magnific-popup.js/1.1.0/magnific-popup.min.css">
        <link rel="stylesheet" href="inc/css/social.css">

        <!-- jQuery (v3.6.0) -->
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

        <!-- Chart.js (Latest Version) -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.4/Chart.min.js" crossorigin="anonymous"></script>

        <!-- ZOHO Widget Script -->
        <script src="https://js.zohostatic.com/books/zfwidgets/assets/js/zf-widget.js"></script>

        <!-- Custom Scripts -->
        <script src="/inc/js/new_social_scripts.js"></script>
        <script src="inc/js/common.js"></script>

        <!-- Bootstrap Bundle with Popper.js (for Bootstrap JS functionality) -->
        <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/magnific-popup.js/1.1.0/jquery.magnific-popup.min.js"></script>

        <style>
            .newsroll-dropdown {
                border: none;
                outline: none;
                position: relative;
                display: inline-block;
                /* display: flex; */
                align-items: center;
                padding-left: 8px;
                /* border: none; */
                padding-bottom: 8px;
            }

            .newsroll-dropbtn {
                border: none;
                color: rgba(255, 255, 255, 0.5);
                background-color: #212529;
            }

            .newsroll-dropbtn:focus {
                border: none;
                outline: none;

            }

            .mfp-close-btn-in .mfp-close {
                color: white;
            }

            .newsroll-dropdown-content {
                display: none;
                outline: none;
                position: relative;
                background-color: #212529;
                min-width: 160px;
                /* box-shadow: 0px 8px 16px 0px rgba(0, 0, 0, 0.2); */
                /* z-index: 1; */
            }

            .newsroll-dropdown-content a {
                color: white;
                padding: 12px 16px;
                text-decoration: none;
                display: block;
            }

            .show {
                display: block;
            }

            .tab-container {
                overflow-x: auto;
                white-space: nowrap;
                padding: 10px 0;
                border-radius: 5px;
            }

            #frmAdd {
                color: #000;
            }

            #categoryId {
                color: #000;

            }

            .nav-tabs {
                display: inline-flex;
                flex-wrap: nowrap;
            }

            .nav-btn {
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 10px 20px;
                font-size: 14px;
                font-weight: 500;
                color: #333;
                background-color: #e0e0e0;
                border: 1px solid #ccc;
                border-radius: 5px;
                margin-right: 5px;
                transition: background-color 0.3s ease, transform 0.3s ease;
            }

            .nav-btn.active {
                border-bottom: 3px solid #db5919;
                background-color: #db5919;
                color: white;
            }

            .nav-btn:hover {
                background-color: #db5919;
                color: white;
                transform: scale(1.05);
            }

            .btn-primary {
                color: #fff;
                background-color: #db5919 !important;
                border-color: #db5919 !important;
            }


            .nav-btn {
                color: #ebedf2;
                background-color: #444;
                border-color: #666;
            }

            .nav-btn.active {
                background-color: #db5919;
                color: white;
                border-bottom: 3px solid #db5919;
            }

            #deleteAccount {
                width: 100%;
                display: flex;
                justify-content: center;
            }

            .popup-content {
                padding: 20px;
                width: 100%;
                max-width: 500px;
            }

            .deleteAccountclsBtn {
                display: flex;
                justify-content: left;
                gap: 10px;
            }

            @media (min-width: 768px) {
                #widthPc {
                    width: 420px;
                }
            }

            @media (max-width: 768px) {
                .tab-container {
                    overflow-x: auto;
                    display: block;
                }

                .nav-tabs {
                    flex-wrap: nowrap;
                    -webkit-overflow-scrolling: touch;
                    scroll-behavior: smooth;
                }

                .nav-item {
                    display: inline-block;
                }
            }

            .form-control {
                padding: 0px;
                background-color: transparent;
                color: var(--text-primary);
            }

            .table {
                color: var(--text-primary);

            }

            .form-group {
                margin-left: -15px;
            }

            .form-row {
                padding-right: 680px;
                margin-left: 13px;
            }

            @media (max-width: 768px) {
                .form-check {
                    display: block;
                    margin-bottom: 10px;
                }

                .form-group {
                    margin-left: 0;
                }

                .form-row {
                    padding-right: 20px;
                    margin-left: 10px;
                }
            }


            .widget {
                overflow: auto !important;
            }

            .modal {
                position: fixed;
                top: 30%;
                left: 0;
                z-index: 1050;
                display: none;
                width: 100%;
                height: 100%;
                overflow: hidden;
                outline: 0;
            }

            .modal-body {
                padding: 2rem !important;
            }

            .breadcrumb {
                background-color: var(--bg-main);
            }

            code {
                color: #e83e8c !important;
                word-wrap: break-word;
            }

            .mfp-ajax-holder .mfp-content {
                background-color: #ccc;
                width: auto;
            }
        </style>

        <script type="text/javascript">
            $(function() {
                $(document).ready(function() {
                    // Get elements
                    const $deleteAccountLink = $('.nav-btn');
                    const $deletePopup = $('#deleteAccount');
                    const $closePopupBtn = $('#closePopupBtn');
                    const $deleteForm = $('#deleteForm');
                    const $confirmCheckbox1 = $('#confirmCheckbox1');
                    const $confirmCheckbox2 = $('#confirmCheckbox2');
                    const $confirmCheckbox3 = $('#confirmCheckbox3');
                    const $passwordInput = $('#password');
                    const $reasonSelect = $('#deleteReason');
                    const $submitBtn = $('#submitBtn');

                    // Show the popup when the Delete Account link is clicked
                    $deleteAccountLink.on('click', function(e) {
                        e.preventDefault();
                        $deletePopup.css('display', 'flex');
                    });

                    // Close the popup when the Cancel button is clicked
                    $closePopupBtn.on('click', function() {
                        $deletePopup.css('display', 'none'); // Hide the tab pane
                    });

                    // Handle form submission with AJAX
                    $deleteForm.on('submit', function(e) {
                        e.preventDefault();

                        // Check if all checkboxes are checked
                        if (!$confirmCheckbox1.is(':checked') || !$confirmCheckbox2.is(':checked') || !$confirmCheckbox3.is(':checked')) {
                            alert('Please confirm all the required checkboxes to delete your account.');
                            return;
                        }

                        // Check if a reason is selected
                        if (!$reasonSelect.val()) {
                            alert('Please select a reason for deleting your account.');
                            return;
                        }

                        // Prompt user to confirm the account deletion
                        const userConfirmed = confirm('Are you sure you want to delete your account?');

                        if (userConfirmed) {
                            const password = $passwordInput.val();
                            if (password) {
                                // Prepare the data for the server
                                const formData = new FormData();

                                formData.append('password', password);
                                formData.append('reason', $reasonSelect.val());

                                $.ajax({
                                    url: '/inc/php/delete_account.php',
                                    type: 'POST',
                                    data: formData,
                                    dataType: 'json',
                                    processData: false,
                                    contentType: false,
                                    success: function(data) {
                                        console.log(data);
                                        if (data.status === 'success') {
                                            alert(data.message);
                                            window.location.href = 'logout.php';
                                        } else {
                                            alert('Error: ' + data.message);
                                        }
                                    },
                                    error: function(xhr, status, error) {
                                        console.error('Error:', error);
                                        alert('An error occurred while trying to delete your account. Please try again later.');
                                    }
                                });
                            } else {
                                alert('Please enter your password.');
                            }
                        } else {
                            alert('Account deletion canceled.');
                        }
                    });

                    $('#buttonAddExternalFeed').magnificPopup({
                        type: 'ajax',
                        closeBtnInside: true,
                        ajax: {
                            settings: {
                                method: 'POST',
                                url: 'process/get.section.settings.php'
                            }
                        },
                        callbacks: {
                            elementParse: function(item) {
                                postData = {
                                    act: 'addExternalFeed',
                                }
                                this.st.ajax.settings.data = postData;
                            }
                        }
                    });
                    // Handle form submission (AJAX)
                    $('#externalFeedForm').submit(function(e) {
                        e.preventDefault(); // Prevent default form submission

                        // Collect form data
                        var feedURL = $('#feedURL').val();
                        var feedCategory = $('#feedCategory').val();

                        // Show loading spinner (optional)
                        $('#loadingSpinner').show();

                        // Send the data via AJAX
                        $.ajax({
                            method: 'POST',
                            url: 'process/get.section.settings.php', // Adjust URL if needed
                            data: {
                                act: 'addExternalFeed', // Action to add feed
                                feedURL: feedURL,
                                feedCategory: feedCategory
                            },
                            success: function(response) {
                                $('#loadingSpinner').hide(); // Hide loading spinner
                                if (response === 'success') {
                                    alert('Feed added successfully');
                                    location.reload(); // Reload the page to show the new feed
                                } else {
                                    alert('Error adding feed');
                                }
                            },
                            error: function() {
                                $('#loadingSpinner').hide(); // Hide loading spinner
                                alert('An error occurred. Please try again.');
                            }
                        });

                        // Close the modal after submission
                        $('#addFeedModal').modal('hide');
                    });

                    $(document).on('click', '.delRow', function() {
                        var feedId = $(this).data('id'); // Get feedId from the data-id attribute

                        // Confirm before deleting
                        if (confirm('Are you sure you want to delete this feed?')) {
                            // Show loading spinner (optional)
                            $('#loadingSpinner').show();

                            $.ajax({
                                method: 'POST',
                                url: 'process/get.section.settings.php', // Adjust URL as needed
                                data: {
                                    act: 'deleteFeed', // Action to delete feed
                                    feedId: feedId
                                },
                                success: function(response) {
                                    $('#loadingSpinner').hide(); // Hide loading spinner
                                    if (response === 'OK') {
                                        alert('Feed deleted successfully');
                                        location.reload(); // Reload the page to reflect the deletion
                                    } else {
                                        alert('Error deleting feed');
                                    }
                                },
                                error: function() {
                                    $('#loadingSpinner').hide(); // Hide loading spinner
                                    alert('An error occurred while deleting the feed.');
                                }
                            });
                        }
                    });
                });

                $('#buttonAddCreamFeed').magnificPopup({
                    type: 'ajax',
                    closeBtnInside: true,
                    ajax: {
                        settings: {
                            method: 'POST',
                            url: 'process/get.section.settings.php'
                        }
                    },
                    callbacks: {
                        elementParse: function(item) {
                            postData = {
                                act: 'addCreamFeed',
                            }
                            this.st.ajax.settings.data = postData;
                        }
                    }
                });
            });

            window.onclick = function(event) {
                if (!event.target.matches('.newsroll-dropbtn')) {
                    var dropdowns = document.getElementsByClassName("newsroll-dropdown-content");
                    for (var i = 0; i < dropdowns.length; i++) {
                        var openDropdown = dropdowns[i];
                        if (openDropdown.classList.contains('show')) {
                            openDropdown.classList.remove('show');
                        }
                    }
                }
            }
        </script>
    </head>

    <body>
        <div class="container">
            <? include 'inc/php/social_navbar.php' ?>
            <? include 'inc/php/social_sidebar.php' ?>

            <!-- <div id="layoutSidenav_content"> -->
            <main class="search-main-content main-content" style="overflow-y: auto;">
                <div>
                    <?
                    $newsTitle = '';
                    $newsLogo = '';
                    // $nlamount = null;
                    $sql = "SELECT subdomain,news_title,news_logo,nl_amount,send_grid_key,send_grid_suppression_id,is_side_panel,side_panel_content FROM user WHERE id=$gUserId";
                    $result = mysqli_query($creamdb, $sql);
                    $numRows = mysqli_num_rows($result);
                    if ($numRows > 0) {
                        $row = mysqli_fetch_assoc($result);
                        $newsTitle = $row['news_title'];
                        $newsLogo = $row['news_logo'];
                        // $nlamount = $row['nl_amount'];
                        $userSubdomain = $row['subdomain'];
                        $userSendGridKey = $row['send_grid_key'];
                        $userSendGridSuppressionId = $row['send_grid_suppression_id'];
                        $userSidePanel = $row['is_side_panel'];
                        $userSidePanelContent = $row['side_panel_content'];
                    }
                    ?>
                    <ol class="breadcrumb my-3">
                        <li class="breadcrumb-item">
                            <h4 class="m-0">My Settings</h4>
                        </li>
                    </ol>
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="tab-container ">
                                <ul class="nav nav-tabs mb-4">
                                    <!-- <li class="nav-item"><a class="nav-btn active" data-toggle="tab" href="#rss" role="tab" onclick="$('#panelStatus').html('')">My RSS Feeds</a></li> -->
                                    <li class="nav-item"><a class="nav-btn active" data-toggle="tab" href="#view" role="tab" onclick="$('#panelStatus').html('')">View Page Settings</a></li>
                                    <!-- <li class="nav-item"><a class="nav-btn" data-toggle="tab" href="#newsletter" role="tab" onclick="$('#panelStatus').html('')">Newsletter Settings</a></li> -->
                                    <!-- <li class="nav-item"><a class="nav-btn" data-toggle="tab" href="#sendgrid" role="tab" onclick="$('#panelStatus').html('')">SendGrid Settings</a></li> -->
                                    <!-- <li class="nav-item"><a class="nav-btn" data-toggle="tab" href="#genai" role="tab" onclick="$('#panelStatus').html('')">GenAI Settings</a></li> -->
                                    <!-- <li class="nav-item"><a class="nav-btn" data-toggle="tab" href="#subdomain" role="tab" onclick="$('#panelStatus').html('')">Setup Subdomain</a></li> -->
                                    <!-- <li class="nav-item"><a class="nav-btn" data-toggle="tab" href="#social" role="tab" onclick="$('#panelStatus').html('')">Social Media</a></li> -->
                                    <!-- <li class="nav-item"><a class="nav-btn" data-toggle="tab" href="#api" role="tab" onclick="$('#panelStatus').html('')">Generate api key</a></li> -->
                                    <li class="nav-item"><a class="nav-btn" data-toggle="tab" href="#deleteAccount" role="tab" onclick="$('#panelStatus').html('')">Delete Account</a></li>
                                </ul>
                            </div>

                            <div class="tab-content ">
                                <div class="tab-pane fade" style="margin-top: 15px;" id="rss" role="tabpanel">
                                    <!-- Button to trigger modal -->
                                    <button type="button" id="buttonAddExternalFeed" class="btn btn-secondary mb-2 float-right">
                                        <i class="fas fa-plus"></i> Add External Feed
                                    </button>
                                    <button type="button" id="buttonAddCreamFeed" class="btn btn-secondary mb-2 mr-2 float-right">
                                        <i class="fas fa-plus"></i> Add from Cream Feeds
                                    </button>
                                    <br clear="all" />

                                    <?php
                                    $numFeed = 1;
                                    $sql = "SELECT A.*, B.category FROM user_feeds A LEFT JOIN category B ON A.category_id = B.id WHERE A.user_id = $gUserId ORDER BY category";
                                    $result = mysqli_query($creamdb, $sql);
                                    $numRows = mysqli_num_rows($result);
                                    if ($numRows > 0) {
                                    ?>
                                        <div class="table-responsive">
                                            <table class="table table-striped">
                                                <thead>
                                                    <tr>
                                                        <th scope="col">#</th>
                                                        <th scope="col">Category</th>
                                                        <th scope="col">Feed</th>
                                                        <th scope="col"></th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php
                                                    while ($row = mysqli_fetch_assoc($result)) {
                                                        $feedId = $row['id'];
                                                        $feedURL = $row['feed_url'];
                                                        $feedCategory = $row['category'];
                                                        if ($feedCategory == '') $feedCategory = 'Others';
                                                    ?>
                                                        <tr>
                                                            <th scope="row"><?= $numFeed ?></th>
                                                            <td><?= $feedCategory ?></td>
                                                            <td><?= $feedURL ?></td>
                                                            <td>
                                                                <!-- Delete button with feedId as data attribute -->
                                                                <a href="#" title="Delete Feed">
                                                                    <i data-id="<?= $feedId ?>" class="delRow far fa-trash-alt fa-lg text-muted"></i>
                                                                </a>
                                                            </td>
                                                        </tr>
                                                    <?php
                                                        $numFeed += 1;
                                                    }
                                                    ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php
                                    } else {
                                    ?>
                                        You have not added any feeds!
                                    <?php
                                    }
                                    ?>

                                    <!-- Modal for Adding External Feed -->
                                    <div id="addFeedModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                        <div class="modal-dialog" role="document">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="exampleModalLabel">Add External Feed</h5>
                                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                        <span aria-hidden="true">&times;</span>
                                                    </button>
                                                </div>
                                                <div class="modal-body">
                                                    <!-- External Feed Form -->
                                                    <form id="externalFeedForm">
                                                        <div class="form-group">
                                                            <label for="feedURL">Feed URL</label>
                                                            <input type="url" class="form-control" id="feedURL" name="feedURL" placeholder="Enter feed URL" required>
                                                        </div>
                                                        <div class="form-group">
                                                            <label for="feedCategory">Category</label>
                                                            <input type="text" class="form-control" id="feedCategory" name="feedCategory" placeholder="Enter category" required>
                                                        </div>
                                                        <button type="submit" class="btn btn-primary">Add Feed</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="tab-pane fade show active" style="margin-top: 15px;" id="view" role="tabpanel">
                                    <div class="form-group row">
                                        <label for="userSidePanel" class="col-sm-2 col-form-label">Show Side Panel</label>
                                        <div class="col-sm-12 col-md-6">
                                            <div class="form-check form-check-inline ml-0 ml-sm-2 w-50">
                                                <input class="form-check-input" type="radio" id="userSidePanelYes" name="userSidePanel" value="1"
                                                    onclick="$('#sidePanelContent').show();">
                                                <label class="form-check-label" for="userSidePanelYes" style="padding: 5px 10px;">Yes</label>
                                            </div>
                                            <div class="form-check form-check-inline ml-0 ml-sm-2 w-50">
                                                <input class="form-check-input" type="radio" id="userSidePanelNo" name="userSidePanel" value="0"
                                                    checked onclick="$('#sidePanelContent').hide();">
                                                <label class="form-check-label" for="userSidePanelNo" style="padding: 5px 10px;">No</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group row" id="sidePanelContent" style="display: none;">
                                        <label for="userSidePanelContent" class="col-sm-2 col-form-label">Side Panel Content</label>
                                        <div class="col-sm-12 col-md-3">
                                            <textarea id="userSidePanelContent" name="userSidePanelContent"><?= $userSidePanelContent ?></textarea>
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <label class="col-sm-2 col-form-label"></label>
                                        <div class="col-sm-12 col-md-6">
                                            <button class="btn btn-primary" onclick="return chkUpdateView()">Update</button>
                                        </div>
                                    </div>
                                </div>
                                <div class="tab-pane fade" id="newsletter" style="margin-top: 15px;" role="tabpanel">
                                    <div class="form-group row">
                                        <label for="userNewsletterTitle" class="col-sm-2 col-form-label">Newsletter Title</label>
                                        <div class="col-sm-12 col-md-6">
                                            <input type="text" class="form-control" id="userNewsletterTitle" name="userNewsletterTitle" value="<?= $newsTitle ?>" maxlength="100" />
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <label for="userNewsletterLogo" class="col-sm-2 col-form-label">Newsletter Logo</label>
                                        <div class="col-sm-12 col-md-3">
                                            <input type="file" class="form-control-file" id="userNewsletterLogo" name="userNewsletterLogo" accept="image/*" />
                                        </div>
                                        <? if ($newsLogo <> '') { ?>
                                            <div class="col-sm-12 col-md-3 mt-3 mt-md-0">
                                                <a href="../data/logos/<?= $newsLogo ?>" target="_blank"><img src="../data/logos/<?= $newsLogo ?>" width="100" style="min-width: 200px;" /></a>
                                            </div>
                                        <? } ?>
                                    </div>

                                    <div class="form-group row">
                                        <label class="col-sm-2 col-form-label"></label>
                                        <div class="col-sm-12 col-md-6">
                                            <button class="btn btn-primary" onclick="return chkUpdateNewsletter()">Update</button>
                                        </div>
                                    </div>
                                </div>
                                <div class="tab-pane fade" id="sendgrid" role="tabpanel">
                                    <?
                                    if ($gUserPlan == 0) {
                                        echo '<div class="alert alert-success" role="alert">This feature is only available in <b>Pro</b> plan! Go to My Account to upgrade.</div>';
                                    } else {
                                    ?>
                                        <div class="form-group row">
                                            <label for="userSendGridKey" class="col-sm-2 col-form-label">Authorization Key</label>
                                            <div class="col-sm-12 col-md-6">
                                                <input type="text" class="form-control" id="userSendGridKey" name="userSendGridKey" value="<?= $userSendGridKey ?>" maxlength="100" />
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="userSendGridSuppressionId" class="col-sm-2 col-form-label">Suppression Group ID</label>
                                            <div class="col-sm-12 col-md-6">
                                                <input type="text" class="form-control" id="userSendGridSuppressionId" name="userSendGridSuppressionId" value="<?= $userSendGridSuppressionId ?>" maxlength="10" />
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label class="col-sm-2 col-form-label"></label>
                                            <div class="col-sm-12 col-md-6">
                                                <button class="btn btn-primary" onclick="return chkUpdateSendGrid()">Update</button>
                                            </div>
                                        </div>
                                    <? } ?>
                                </div>
                                <div class="tab-pane fade" id="subdomain" style="margin-top: 15px;" role="tabpanel">
                                    <?
                                    if ($gUserPlan == 0) {
                                        echo '<div class="alert alert-success" role="alert">This feature is only available in <b>Pro</b> plan! Go to My Account to upgrade.</div>';
                                    } else {
                                    ?>
                                        <div class="form-group row">
                                            <label for="userSubdomain" class="col-sm-2 col-form-label">Subdomain</label>
                                            <div class="col-sm-12 col-md-3">
                                                <input type="text" class="form-control" id="userSubdomain" name="userSubdomain" maxlength="50" value="<?= $userSubdomain ?>" />
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label class="col-sm-2 col-form-label"></label>
                                            <div class="col-sm-12 col-md-3">
                                                <button class="btn btn-primary" onclick="return chkUpdateSubdomain()">Update Subdomain</button>
                                            </div>
                                        </div>
                                    <? } ?>
                                </div>
                                <div class="tab-pane fade" id="genai" style="margin-top: 15px;" role="tabpanel">
                                    <? if ($gUserPlan == 0) { ?>
                                        <div class="alert alert-success" role="alert">This feature is only available in <b>Pro</b> plan! Go to My Account to upgrade.</div>
                                    <? } else {
                                        $professions = [
                                            '' => '--Select a profession--',
                                            'doctor' => 'Doctor',
                                            'engineer' => 'Engineer',
                                            'teacher' => 'Teacher',
                                            'artist' => 'Artist',
                                            'nurse' => 'Nurse',
                                            'scientist' => 'Scientist',
                                            'lawyer' => 'Lawyer',
                                            'chef' => 'Chef',
                                            'musician' => 'Musician',
                                            'developer' => 'Software Developer',
                                            'architect' => 'Architect',
                                            'writer' => 'Writer',
                                            'journalist' => 'Journalist',
                                            'photographer' => 'Photographer',
                                            'therapist' => 'Therapist',
                                            'pilot' => 'Pilot',
                                            'mechanic' => 'Mechanic',
                                            'entrepreneur' => 'Entrepreneur',
                                            'accountant' => 'Accountant',
                                            'ca' => 'Chartered Accountant (CA)',
                                            'pharmacist' => 'Pharmacist',
                                            'social_worker' => 'Social Worker',
                                            'dentist' => 'Dentist'
                                        ];


                                    ?>
                                        <label for="profession">Choose a profession:</label>
                                        <select id="profession" name="profession" class="form-control">
                                            <?
                                            foreach ($professions as $value => $label) {
                                                $selected = ($value === $selectedProfession) ? ' selected' : '';
                                                echo "<option value=\"$value\"$selected>$label</option>";
                                            }
                                            ?>
                                        </select>

                                        <button id='genaiProfession' class="btn btn-primary mt-3" onclick="fetchProfessionData()">Submit</button>
                                        <div id="response" class="mt-3"></div>
                                    <? } ?>
                                </div>

                                <div class="tab-pane fade" id="social" style="margin-top: 15px;" role="tabpanel">
                                    <div class="form-group row">
                                        <label class=" col-form-label">Facebook</label>
                                        <div class="col-sm-12 col-md-3">
                                            <a href="social_media/facebook/facebook_setup.php"><button class="btn btn-primary" onclick="">Setup Facebook</button></a>
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <label class=" col-form-label">LinkedIn</label>
                                        <div class="col-sm-12 col-md-3">
                                            <a href="social_media/linkedin/linkedin_setup.php"><button class="btn btn-primary" onclick="">Setup LinkedIn</button></a>
                                        </div>
                                    </div>
                                </div>

                                <div class="tab-pane fade" style="margin-top: 15px;" id="api" role="tabpanel">
                                    <div class="form-group row">
                                        <label class="col-sm-2 col-form-label">Generate Token</label>
                                        <div class="col-sm-12 col-md-3">
                                            <button class="btn btn-primary" id="generateTokenBtn">Generate Api Key & Secret Token</button>
                                        </div>
                                    </div>

                                    <!-- Table to display generated tokens -->
                                    <div class="row">
                                        <div class="col-sm-12">
                                            <table class="table table-bordered" id="tokenTable">
                                                <thead>
                                                    <tr>
                                                        <th>API Key </th>
                                                        <th>Secret Token</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <!-- Tokens will be displayed here -->
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                    <!-- Instructions for using the API -->
                                    <div class="mt-4">
                                        <h4>Instructions for Fetching Data via API</h4>
                                        <p>To retrieve data through the API, you will need to use your unique <strong>API Key</strong> and <strong>Secret Token</strong>. Please follow the steps below:</p>

                                        <ol>
                                            <li>
                                                Copy the URL template provided below:
                                                <p>
                                                    <code>
                                                        https://newsjunction.net/creamapi/fetch_data.php?api_key=<strong>API_KEY</strong>&secret_key=<strong>SECRET_TOKEN</strong>
                                                    </code>
                                                </p>
                                            </li>
                                            <li>Replace <strong>API_KEY</strong> with your actual API key.</li>
                                            <li>Replace <strong>SECRET_TOKEN</strong> with your actual Secret token.</li>
                                            <li>Open the updated URL in your browser or use it in your API client (like Postman) to fetch the data.</li>
                                        </ol>

                                        <h5>Optional Parameters for Filtering Data</h5>
                                        <p>In addition to the required API and Secret keys, the following optional parameters can be included in your request to filter and paginate the data:</p>
                                        <ul>
                                            <li><strong>limit</strong>: Specifies the number of records to retrieve. Default is 10. Example: <code>&limit=20</code></li>
                                            <li><strong>offset</strong>: Specifies the starting point of records (useful for pagination). Default is 0. Example: <code>&offset=10</code></li>
                                            <li><strong>sort_order</strong>: Defines the sorting order of the data by date. Use <code>asc</code> for ascending or <code>desc</code> for descending. Default is <code>asc</code>. Example: <code>&sort_order=desc</code></li>
                                        </ul>

                                        <p>Example URL with filtering parameters:</p>
                                        <p>
                                            <code>
                                                https://newsjunction.net/creamapi/fetch_data.php?api_key=YOUR_API_KEY&secret_key=YOUR_SECRET_KEY&limit=20&offset=10&sort_order=desc
                                            </code>
                                        </p>

                                        <h5>Count API Call</h5>
                                        <p>To retrieve only the count of records (instead of the full dataset), use the following URL:</p>
                                        <p>
                                            <code>
                                                https://newsjunction.net/creamapi/fetch_data.php?api_key=YOUR_API_KEY&secret_key=YOUR_SECRET_KEY&action=count
                                            </code>
                                        </p>
                                        <p>This will return a JSON response containing the total number of records available.</p>

                                        <h5>Example Response for Count API</h5>
                                        <p>A successful response from the Count API will look like this:</p>
                                        <pre><code>
{
    "status": "success",
    "count": 150
}
    </code></pre>

                                        <h5>Example Response for Data Fetching</h5>
                                        <p>When using the fetch API (with filtering options or without), the response will contain the data in JSON format, as shown below:</p>
                                        <pre><code>
{
    "status": "success",
    "data": [
        {
            "title": "Sample Title",
            "url": "https://example.com",
            "description": "Sample description of the data",
            "date_added": "2024-10-20"
        },
        ...
    ]
}
    </code></pre>
                                    </div>


                                </div>


                                <script>
                                    document.getElementById('generateTokenBtn').addEventListener('click', function() {
                                        fetch('creamapi/generate_token.php')
                                            .then(response => response.json())
                                            .then(data => {
                                                // Check for errors
                                                if (data.error) {
                                                    console.error('Error:', data.error);
                                                    alert(data.error); // Show error to user
                                                    return;
                                                }

                                                // Create a new row for the generated token and access key
                                                const tokenTableBody = document.getElementById('tokenTable').querySelector('tbody');
                                                const newRow = document.createElement('tr');

                                                // Create cells for API token and Access key
                                                const tokenCell = document.createElement('td');
                                                tokenCell.textContent = data.api_token; // Set the token text

                                                const accessKeyCell = document.createElement('td');
                                                accessKeyCell.textContent = data.access_key; // Set the access key text

                                                // Append cells to the new row
                                                newRow.appendChild(tokenCell);
                                                newRow.appendChild(accessKeyCell);
                                                tokenTableBody.appendChild(newRow); // Add the new row to the table
                                            })
                                            .catch(error => console.error('Error:', error));
                                    });
                                </script>


                                <div class="tab-pane fade" id="deleteAccount" role="tabpanel">
                                    <div class="popup-content">
                                        <h3 class="text-center">Are you sure you want to delete your account?</h3>
                                        <form id="deleteForm">
                                            <!-- Confirmation checkboxes -->
                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input" id="confirmCheckbox1">
                                                <label class="form-check-label" for="confirmCheckbox1">I understand that this action is permanent and cannot be undone.</label>
                                            </div>
                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input" id="confirmCheckbox2">
                                                <label class="form-check-label" for="confirmCheckbox2">I acknowledge that all data will be lost and cannot be recovered.</label>
                                            </div>
                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input" id="confirmCheckbox3">
                                                <label class="form-check-label" for="confirmCheckbox3">I confirm that I am the account holder and wish to delete my account.</label>
                                            </div>

                                            <!-- Reason dropdown -->
                                            <div class="mt-3">
                                                <label for="deleteReason" class="form-label">Reason for deleting the account:</label>
                                                <select class="form-select" id="deleteReason" required>
                                                    <option value="">Select a reason</option>
                                                    <option value="no_longer_needed">1. I no longer need the account</option>
                                                    <option value="privacy_concerns">2. Concerns about privacy</option>
                                                    <option value="too_many_emails">3. Receiving too many emails</option>
                                                    <option value="found_alternative">4. Found a better alternative</option>
                                                    <option value="poor_customer_service">5. Poor customer service</option>
                                                    <option value="high_cost">6. Too expensive</option>
                                                    <option value="account_hacked">7. My account was hacked</option>
                                                    <option value="no_features">8. Missing key features</option>
                                                    <option value="dissatisfied">9. Dissatisfied with the service</option>
                                                    <option value="other">10. Other</option>
                                                </select>
                                            </div>

                                            <!-- Password input -->
                                            <div class="mt-3">
                                                <label for="password" class="form-label">Enter your password:</label>
                                                <input type="password" class="form-control" id="password" name="password" required>
                                            </div>

                                            <!-- Submit button -->
                                            <div class="deleteAccountclsBtn">
                                                <div class="mt-4 text-center">
                                                    <button type="submit" id="submitBtn" class="btn btn-danger">Submit</button>
                                                </div>
                                                <div class="mt-4 text-center">
                                                    <button id="closePopupBtn" class="btn btn-secondary">Cancel</button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                                <div id="panelStatus" class="mt-4"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
            <!-- </div> -->
        </div>

        <? include 'inc/php/footer.php' ?>

    </body>


    </html>
<?
}
?>