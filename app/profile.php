<?
include './inc/php/validate.logged.php';
include './inc/php/function.php';
include './inc/php/db_config.php';

if (isset($_GET['userId'])) {
    $userId = $_GET['userId'];
} else {
    $userId = $gUserId;
}

$blockStatus = blockStatus($userId);


function buildCoverImgUrl($imgName)
{
    if (!$imgName) {
        return;
    }
    return "data/covers/$imgName";
}

function show_stream_content($userId)
{
    global $readerdb, $gUserId;

    // SQL query to get posts ordered by the most recent
    $sql = "SELECT * FROM reader_stream WHERE deleteFlag = 0 AND referenceId IS NULL AND userId = $userId ORDER BY postedOn DESC ";
    $result = $readerdb->query($sql);

    if ($result->num_rows > 0) { ?>
        <div class="tab-pane fade show active" id="posts" role="tabpanel" aria-labelledby="posts-tab">
            <? while ($row = $result->fetch_assoc()) {
                generate_stream_card($row['id'], $row['userId'], $row['chat'], $row['postedOn'], $row['editedOn'], $row['mediaPath'], $row['metadata']);
            }
            ?></div><?
                } else {
                    // Optionally handle the case when no posts are found
                    echo "No posts available.";
                }
            }

            function buildViewUrl($title)
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

            $followData = getFollowCounts($userId, $readerdb);
            $followerCount = $followData['followers'];
            $followingCount = $followData['following'];

            function show_showcase($userId)
            {
                global $creamdb;

                // NOTE: Consider using prepared statements to prevent SQL injection
                $sql = "SELECT * FROM user_landing WHERE user_id = $userId ORDER BY date_created DESC LIMIT 50";
                $result = $creamdb->query($sql);

                    ?>
    <div class="tab-pane fade" id="showcase" role="tabpanel" aria-labelledby="showcase-tab">
        <?php
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $articleId = $row['article_id'];
                        // NOTE: Consider using prepared statements here too
                        $artsql = "SELECT * FROM user_collection WHERE id = $articleId";
                        $artresult = $creamdb->query($artsql);

                        if ($artresult->num_rows > 0) {
                            while ($artrow = $artresult->fetch_assoc()) {
                                $articleTitle = htmlspecialchars($artrow['title']);
                                $articleDesc = htmlspecialchars($artrow['description']);
                                $coverImg = buildCoverImgUrl($artrow['cover_img']);
                                $dateCreated = $row['date_created'];
                                $viewUrl = 'view/' . $artrow['id'] . '/' . createArticleURL($artrow['title']);
                                $modalUrl = buildViewUrl($artrow['title']);

        ?>
                        <a href="<?= $viewUrl ?>">
                            <div class="card my-3"
                                data-bs-toggle="modal"
                                data-bs-target="#newsModal"
                                data-title="<?= $articleTitle ?>"
                                data-image="<?= $coverImg ?>"
                                data-description="<?= $articleDesc ?>"
                                data-date="<?= $dateCreated ?>"
                                data-url="<?= $modalUrl ?>">
                                <? if ($coverImg) {
                                    $imageInfo = @getimagesize($coverImg);
                                    if ($imageInfo !== false) { ?>
                                        <img src="<?= $coverImg ?>" alt="<?= $articleTitle ?>">
                                <?
                                    }
                                } ?>
                                <h4><?= $articleTitle ?></h4>
                                <p><?= $dateCreated ?></p>
                            </div>
                        </a>
        <?php
                            }
                        }
                    }
                } else {
                    echo "<p>No posts available.</p>";
                }
        ?>
    </div>
<?php
            }

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Page with Modal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.0/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="../grfx/img/logo.ico">

    <link rel="stylesheet" href="inc/css/social.css">

    <style>
        .readMoreBtn {
            color: #6d6e71 !important;
            background: none;
            border: none;
            padding: 0;
            cursor: pointer;
            color: blue !important;
            font-size: x-small !important;
            display: inline !important;
        }

        .readMoreBtn:hover {
            text-decoration: underline;
        }

        .hyperlink img {
            border-radius: 10px;
            /* margin-top: 10px; */
            max-width: 100%;
            margin-bottom: 10px;
        }

        .linkDisplay .hyperlink img {
            object-fit: cover;
            border-radius: 5px;
            width: 30vw;
        }



        @media screen and (max-width:720px) {
            .linkDisplay .hyperlink img {
                object-fit: cover;
                border-radius: 5px;
                width: 100vw;
            }

        }

        .ytprew {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            margin: 20px auto;
            padding: 10px;
            /* border: 1px solid #ccc; */
            border-radius: 8px;
            background-color: #f9f9f9;
            width: auto;
            /* Ensures the div doesn't stretch too wide */
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .ytprew iframe {
            width: 100%;
            max-width: 853px;
            /* Matches YouTube's default embed width */
            height: 480px;
            border-radius: 8px;
            border: none;
        }

        .ytprew a {
            margin-top: 10px;
            color: #0073e6;
            text-decoration: none;
            font-weight: bold;
        }

        .ytprew a:hover {
            text-decoration: underline;
            color: #005bb5;
        }

        .ytprew p {
            margin: 10px 0 0;
            font-size: 14px;
            color: #555;
            text-align: center;
        }

        #ytPreview {
            display: none;
        }

        #content-section {
            display: flex;
            justify-content: center;
            width: 100%;
        }

        .tab-pane {
            width: 100%;
        }
    </style>
    <style>
        .profile-section {
            display: flex;
            align-items: center;
            padding: 20px;
        }

        .profile-pic {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background-color: #444;
            overflow: hidden;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 16px;
            margin-right: 20px;
            border: 2px solid #ffffff;
        }

        .profile-pic img {
            width: 100%;
        }

        .profile-info {
            flex-grow: 1;
        }

        .nav-section {
            /* display: flex;
            gap: 5px;
            justify-content: center; */
            padding: 10px 0;
            /* background-color: #1e1e1e; */
        }

        .nav-section-tabs {
            display: flex;

        }

        .nav-section button {
            border: none;
            background-color: transparent;
            color: #ffffff;
            font-weight: bold;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
        }

        .nav-section button:active {
            /* border: none; */
            border-bottom: #ffffff;
            background-color: transparent;
            color: #ffffff;
            font-weight: bold;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
        }

        .nav-section button:focus-visible {
            background-color: transparent;
            border-bottom: #ffffff;

        }

        .content-section {
            margin-top: 20px;
            display: flex;
            flex-direction: column;
            /* overflow-y: scroll; */
            gap: 20px;
        }

        .card {
            background-color: var(--bg-card);
            color: var(--text-primary);
            padding: 15px;
            border-radius: 10px;
            text-align: left;
            cursor: pointer;
        }

        .card img {
            width: 100%;
            height: auto;
            object-fit: cover;
            border-radius: 5px;
            margin-bottom: 10px;
        }

        @media (min-width: 768px) and (max-width: 1024px) {
            .btn {
                font-size: 16px;
                padding: 0px 5px;
            }
        }

        .follow-number {
            font-size: 20px;
            font-weight: bold;
        }

        .follow-label {
            font-size: 14px;
            color: #666;
        }

        .follow-info {
            display: flex;
            gap: 20px;
            margin-left: auto;
            margin-right: 30px;
        }

        @media (max-width: 500px) {
            .profile-header {
                gap: 10px;

            }

            .follow-info {
                flex-direction: column;
                gap: 10px;
            }

            .profile-pic {
                width: 65px;
                height: 65px;
            }
        }


        .postWithMainContainer img,
        .postWithMainContainer video {
            width: 100% !important;
        }
    </style>
    <style>
        .profile-section {
            display: flex;
            align-items: center;
            justify-content: space-between;
            /* Add space between profile info and the menu */
            position: relative;
        }

        .menu-container {
            position: relative;
            cursor: pointer;
        }

        #menu-icon {
            font-size: 20px;
            color: #333;
            /* Adjust color as needed */
        }

        .dropcardMenu {
            display: none;
            /* Initially hidden */
            position: absolute;
            /* top: 100%; */
            /* Position below the dots */
            right: 0;
            /* background-color: #fff; */
            box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
            border-radius: 4px;
            padding: 10px;
            z-index: 1000;
        }

        .dropcardMenu svg {
            display: block;
            margin: 5px 0;
            cursor: pointer;
            color: #000;
        }

        .nav-link {
            color: var(--text-primary);
        }

        .nav-link:focus,
        .nav-link:hover {
            color: var(--text-primary);
        }

        .profile-container {
            flex-direction: column;
            height: 85vh;
            overflow-y: scroll;
            padding-bottom: 55px
        }

        @media screen and (max-width:540px) {
            .profile-container {

                flex-direction: column
            }
        }


        .socialMainCont {
            display: flex;
            justify-content: center;
            gap: 20px;
        }

        @media screen and (max-width:768px) {
            .socialMainCont {
                display: flex;
                justify-content: center;
                /* height: 85vh; */
                gap: 0px;
            }

        }

        .channelButton {
            display: none;
        }
    </style>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="inc/js/new_social_script.js?v=20260528"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        const userId = <?= $gUserId ?>;

        $(document).on('click', '.blockThisPost', function() {
            var blockId = $(this).data('userid');
            // reportModal.show();
            // $('#reportStreamId').val(reportId);
            blockAccount(blockId);

        });

        // Handle like button clicks
        $(document).on('click', '.likeButton', function(e) {
            event.stopPropagation();
            // If the clicked element has the `likedUsers` class, do not toggle like/unlike                
            if ($(e.target).hasClass('likedUsers')) {
                return; // Prevent triggering the like toggle
            }

            // Proceed with like toggle functionality
            var feedId = $(this).data('id');
            toggleLike(this, feedId, userId);
        });

        $(document).on('click', '.unblockThisPost', function() {
            var blockId = $(this).data('userid');
            // reportModal.show();
            // $('#reportStreamId').val(reportId);
            unblockAccount(blockId);

        });

        function blockAccount(userId) {
            // Ask the user for confirmation before proceeding
            if (confirm("Are you sure you want to block this account?")) {
                $.ajax({
                    url: 'process/blockAccount.php', // The PHP script to handle the blocking
                    method: 'POST',
                    data: {
                        act: 'block',
                        userId: userId
                    },
                    success: function(response) {
                        // Check if the block operation was successful
                        if (response.status === 'success') {
                            alert("Blocked the account");
                            window.location.href = 'stream.php'; // Reload the page to reflect the changes
                        } else {
                            alert(response.message); // Show error message if blocking fails
                        }
                    },
                    error: function() {
                        alert('There was an error while blocking the account.');
                    }
                });
            }
        }

        function unblockAccount(userId) {
            // Ask the user for confirmation before proceeding
            if (confirm("Are you sure you want to unblock this account?")) {
                $.ajax({
                    url: 'process/blockAccount.php', // The PHP script to handle the blocking
                    method: 'POST',
                    data: {
                        act: 'unblock',
                        userId: userId
                    },
                    success: function(response) {
                        // Check if the block operation was successful
                        if (response.status === 'success') {
                            alert("Unblocked the account");
                            window.location.reload(); // Reload the page to reflect the changes
                        } else {
                            alert(response.message); // Show error message if blocking fails
                        }
                    },
                    error: function() {
                        alert('There was an error while unblocking the account.');
                    }
                });
            }
        }
    </script>
</head>

<body>
    <?php include 'inc/php/models.php' ?>

    <div class="container">
        <? include 'inc/php/social_navbar.php' ?>
        <? include 'inc/php/social_sidebar.php' ?>

        <div class="main-content">
            <div>
                <!-- Profile Section -->
                <div class="profile-section">

                    <div class="profile-pic">
                        <img src="<?= viewProfilePic($creamdb, $userId) ?>" alt="Default Image"
                            onerror="this.onerror=null; this.src='/data/profilePic/default.png';">
                    </div>
                    <div class="profile-info">
                        <h3><?= showUserName($creamdb, $userId) ?></h3>
                        <p><?= showProfileBio($creamdb, $userId) ?></p>
                    </div>
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
                    <!-- Add the 3-dot menu here -->
                    <div class="menu-container">
                        <div alt="menu" width="100%" height="100%" id="menu-icon_<?= $userId ?>" onclick="toggleDropcardMenu(<?= $userId ?>)">⋮</div>

                        <!-- Dropcard Menu -->
                        <div id="dropcardMenu_<?= $userId ?>" class="dropcardMenu" style="min-width: fit-content; background-color: white; border: 1px solid #ddd; border-radius: 8px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); padding: 8px;">
                            <!-- <div class="reportThisAcc" id="reportBtn" style="display: flex; align-items: center; gap: 8px; cursor: pointer; transition: background-color 0.3s;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
                                    <rect width="24" height="24" fill="none" />
                                    <path fill="currentColor" d="M20.92 15.62a1.2 1.2 0 0 0-.21-.33l-3-3a1 1 0 0 0-1.42 1.42l1.3 1.29H12a1 1 0 0 0 0 2h5.59l-1.3 1.29a1 1 0 0 0 0 1.42a1 1 0 0 0 1.42 0l3-3a.9.9 0 0 0 .21-.33a1 1 0 0 0 0-.76M14 20H6a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1h5v3a3 3 0 0 0 3 3h4a1 1 0 0 0 .92-.62a1 1 0 0 0-.21-1.09l-6-6a1 1 0 0 0-.28-.19h-.09l-.28-.1H6a3 3 0 0 0-3 3v14a3 3 0 0 0 3 3h8a1 1 0 0 0 0-2M13 5.41L15.59 8H14a1 1 0 0 1-1-1Z" />
                                </svg>
                                <p style="margin: 0; font-size: 14px; color: #333;">Report</p>
                            </div> -->
                            <? if (!$blockStatus) { ?>
                                <div class="blockThisPost" style="display: flex; gap:4px; cursor:pointer;" data-userid=<?= $userId ?>>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
                                        <path fill="currentColor" d="M11.5 4a3.5 3.5 0 1 0 0 7a3.5 3.5 0 0 0 0-7M6 7.5a5.5 5.5 0 1 1 11 0a5.5 5.5 0 0 1-11 0m12 7a3.5 3.5 0 0 0-3.08 5.165l4.745-4.744A3.5 3.5 0 0 0 18 14.5m3.08 1.835l-4.745 4.744a3.5 3.5 0 0 0 4.745-4.745M12.5 18a5.5 5.5 0 1 1 11 0a5.5 5.5 0 0 1-11 0M8 16a4 4 0 0 0-4 4h7.05v2H2v-2a6 6 0 0 1 6-6h3v2z" />
                                    </svg>
                                    <p style="margin: 0; font-size: 14px; color: #333;">Block</p>
                                </div>
                            <? } else { ?>
                                <div class="unblockThisPost" style="display: flex; gap:4px; cursor:pointer;" data-userid=<?= $userId ?>>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 32 32">
                                        <path fill="currentColor" d="M10 18h8v2h-8zm0-5h12v2H10zm0 10h5v2h-5z" />
                                        <path fill="currentColor" d="M25 5h-3V4a2 2 0 0 0-2-2h-8a2 2 0 0 0-2 2v1H7a2 2 0 0 0-2 2v21a2 2 0 0 0 2 2h18a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2M12 4h8v4h-8Zm13 24H7V7h3v3h12V7h3Z" />
                                    </svg>
                                    <p style="margin: 0; font-size: 14px; color: #333;">Unblock</p>
                                </div>
                            <? } ?>
                        </div>

                    </div>
                </div>

                <? if ($blockStatus) { ?>
                    You Have Blocked This Account
                <? } else { ?>
                    <!-- Navigation Section (Tabs) -->
                    <div class="nav-section">
                        <!-- Bootstrap Tab Navigation -->
                        <ul class="nav nav-tabs nav-section-tabs" id="content-tabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <a class="nav-link active" id="posts-tab" data-bs-toggle="tab" href="#posts" role="tab"
                                    aria-controls="posts" aria-selected="true">Posts</a>
                            </li>
                            <li class="nav-item" role="presentation">
                                <a class="nav-link" id="showcase-tab" data-bs-toggle="tab" href="#showcase" role="tab"
                                    aria-controls="showcase" aria-selected="false">Showcase</a>
                            </li>
                        </ul>
                    </div>

                    <div class="tab-content" id="content-section">
                        <!-- Posts Tab -->
                        <? show_stream_content($userId) ?>
                        <!-- Showcase Tab -->
                        <? show_showcase($userId) ?>
                    </div>
                <? } ?>
            </div>
            <?php include 'inc/php/right_news_module.php' ?>
        </div>
        <? include "inc/php/footer.php"; ?>

    </div>

    <script>
        // Initialize the modal when the document is ready
        $(function() {
            // Initialize the Bootstrap modal
            const reportModal = new bootstrap.Modal($("#reportModal")[0]);
            const username = "<?= showUserName($creamdb, $userId); ?>"
            // Handle clicking on the 'reportThisPost' element
            $(document).on('click', '.reportThisAcc', function() {
                // Get the ID from the button's data-id attribute (if needed)
                var reportId = $(this).data('id');
                // Show the modal
                reportModal.show();
            });

            // When the user clicks the submit button in the modal
            $("#submitReport").on("click", function() {
                var reportReason = $("#reportReason").val(); // Get the selected reason

                // Check if the user has selected a reason
                if (reportReason === "") {
                    alert("Please select a reason for reporting.");
                    return; // Prevent form submission if no reason is selected
                }

                // Send data using AJAX
                $.ajax({
                    url: "inc/php/report_account.php", // PHP script that will process the report
                    method: "POST",
                    data: {
                        guserId: <?= $gUserId ?>, // PHP variable injected into JavaScript
                        accountId: <?= $userId ?>, // PHP variable injected into JavaScript
                        reason: reportReason,
                        username: username
                    },
                    success: function(response) {
                        if (response.success === true) {
                            alert("Report submitted successfully!");
                            reportModal.hide(); // Close the modal using Bootstrap's modal method
                        } else {
                            alert("Error submitting report. Please try again.");
                        }
                    },
                    error: function() {
                        alert("There was an error with the request. Please try again.");
                    }
                });
            });
        });
    </script>
    +
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>