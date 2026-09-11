<?
require_once('inc/php/db_config.php');
require_once('inc/php/function.php');

if (isset($_GET['id'])) {
    $postId = $_GET['id'];
} else {
    die("Invalid post ID.");
}



function show_nav_bar()
{
    global $creamdb;

?>
    <div class="navbar">
        <div class="logo" id="sidebar-toggle">
            <img src="/grfx/img/nj_logo.png" alt="Chirper Logo">
        </div>

        <div class="nav-actions login-button">
            <div class="theme-toggle" id="theme-toggle">
                <i class="fas fa-moon"></i>
            </div>

            <div class="nav-icon login-button" style="display:flex;">
                <a class="nav-link" href="account.php">
                    <span id="settings-text">
                        <div class="avatar">
                            <img src="https://newsjunction.net/data/profilePic/default.png" alt="Default Image"
                                onerror="this.onerror=null; this.src='/data/profilePic/default.png';">
                        </div>
                    </span>
                </a>
            </div>
        </div>
    </div>
<?
}

function main_post_card($id, $userId, $chat, $postedOn, $editedOn, $media,  $metaData)
{
    global $gUserId, $readerdb, $creamdb;
    $maxLength = 320;
    $isTruncated = isset($chat) ? (strlen($chat) > $maxLength) : false;
    // $isTruncated = mb_strlen($chat) > $maxLength;
    $truncatedContent = $isTruncated ? substr($chat, 0, $maxLength) . '...' : $chat; ?>

    <div class="post-card " data-id="<?= $id ?>">
        <div class="post-header">
            <div class="user-avatar login-button ">
                <a href="profile.php?userId=<?= $userId ?>">
                    <img src="<?= viewProfilePic($creamdb, $userId) ?>" alt="User Avatar" class="user-avatar" onerror="this.onerror=null; this.src='inc/img/default.png';">
                </a>
            </div>
            <div class="post-info">
                <div class="post-author login-button ">
                    <a href="profile.php?userId=<?= $userId ?>">
                        <?= showUserName($creamdb, $userId); ?>
                    </a>
                </div>
                <div class="post-meta">
                    <?= formatToIST($postedOn); ?>
                    <? if ($editedOn) { ?> (Edited) <? } ?>
                </div>
            </div>
            <? if ($gUserId != $userId) { ?>
                <button class="follow-button login-button" data-id="<?= $userId ?>">
                    <i class="fas fa-user-plus"></i> <?= checkFollow($readerdb, $gUserId, $userId) ? 'Following' : 'Follow' ?>
                </button>
            <? } ?>

            <div class="post-menu" onclick="toggleDropcardMenu(<?= $id ?>)">
                <i class="fas fa-ellipsis-h"></i>
            </div>
        </div>

        <div class="post-content">
            <!-- <div class="post-text">
                <? if ($truncatedContent != null && !preg_match('/(?:https?|ftp):\/\/(?:[a-zA-Z0-9-]+\.)?(?:[a-zA-Z0-9-]+\.[a-zA-Z]{2,})(?:\/[^\s\)]*)?/', $truncatedContent)) { ?>
                    <a href="post-details.php?id=<?= $id ?>" style="text-decoration: none;">
                        <?= convertLink(htmlspecialchars($truncatedContent)); ?>
                    </a>

                    <?php if ($isTruncated) { ?>
                        <div id="fullContent_<?= $id ?>" style="display: none;">
                            <a href="post-details.php?id=<?= $id ?>" style="text-decoration: none;">
                                <?= convertLink(htmlspecialchars($chat)); ?>
                            </a>
                        </div>
                        <div>
                            <button class="btn-link" data-id="<?= $id ?>" onclick="toggleReadMore(<?= $id ?>)">Read More</button>
                        </div>
                    <?php } ?>
                <? } ?>
            </div> -->
            <div class="post-text">
                <? if ($truncatedContent != null && !preg_match('/(?:https?|ftp):\/\/(?:[a-zA-Z0-9-]+\.)?(?:[a-zA-Z0-9-]+\.[a-zA-Z]{2,})(?:\/[^\s\)]*)?/', $truncatedContent)) { ?>
                    <div id="postContent_<?= $id ?>" class="truncated-content">
                        <a href="post-details.php?id=<?= $id ?>" style="text-decoration: none;">
                            <?= convertLink(htmlspecialchars($truncatedContent)); ?>
                        </a>
                    </div>

                    <?php if ($isTruncated) { ?>
                        <div id="fullContent_<?= $id ?>" class="full-content" style="display: none;">
                            <a href="post-details.php?id=<?= $id ?>" style="text-decoration: none;">
                                <?= convertLink(htmlspecialchars($chat)); ?>
                            </a>
                        </div>
                        <div class="read-more-container">
                            <button class="read-more-btn" data-id="<?= $id ?>" onclick="toggleReadMore(<?= $id ?>)">Read More</button>
                        </div>
                    <?php } ?>
                <? } ?>
            </div>

            <?php if ($media) {
                $mediaArray = explode(',', $media);
                $mediaCount = count($mediaArray);
                if ($mediaCount < 2) { ?>
                    <div class="post-image">
                        <?php if (strpos($media, 'mp4') !== false || strpos($media, 'mov') !== false) { ?>
                            <video controls onclick="openModal('video', '<?= htmlspecialchars($media); ?>',0)">
                                <source src="<?= htmlspecialchars($media); ?>" type="video/<?= strpos($media, 'mp4') !== false ? 'mp4' : 'quicktime'; ?>">
                                Your browser does not support the video tag.
                            </video>
                        <?php } else { ?>
                            <img src="<?= htmlspecialchars($media); ?>" alt="Post media"
                                onclick="openModal('image', '<?= htmlspecialchars($media); ?>',0)" class="post-image">
                        <?php } ?>
                    </div>
                <?php } else if ($mediaCount > 1) { ?>
                    <div class="media-gallery">
                        <?php
                        $displayedMedia = 0;
                        foreach ($mediaArray as $mediaItem) {
                            $mediaItem = trim($mediaItem);
                            if ($displayedMedia < 4) { ?>
                                <div class="gallery-item">
                                    <?php if (strpos($mediaItem, 'mp4') !== false || strpos($mediaItem, 'mov') !== false) { ?>
                                        <video controls onclick="openModal('video', '<?= htmlspecialchars($media); ?>',<?= $displayedMedia ?>)">
                                            <source src="<?= htmlspecialchars($mediaItem); ?>" type="video/<?= strpos($mediaItem, 'mp4') !== false ? 'mp4' : 'quicktime'; ?>">
                                            Your browser does not support the video tag.
                                        </video>
                                    <?php } else { ?>
                                        <img src="<?= htmlspecialchars($mediaItem); ?>" alt="Post media"
                                            onclick="openModal('image', '<?= htmlspecialchars($media); ?>',<?= $displayedMedia ?>)">
                                    <?php } ?>
                                </div>
                            <?php
                                $displayedMedia++;
                            }
                        }

                        if (($mediaCount > 4) && ($mediaCount != 4)) { ?>
                            <div class="gallery-item more-media">
                                <span class="more-count">+<?= $mediaCount - 3; ?> more</span>
                            </div>
                        <?php } ?>
                    </div>
                <?php } ?>
            <?php } ?>

            <? if (isset($metaData)) {
                $metaData = json_decode($metaData, true);
                if (isset($metaData['youtubeLink']) && ($metaData['youtubeLink'] != '')) { ?>
                    <div class="embedded-content"><?= $metaData['youtubeLink']; ?></div>
                <? } else if (!empty($metaData['metaTitle']) || !empty($metaData['metaDesc']) || !empty($metaData['metaImage'])) { ?>
                    <div class="link-preview">
                        <?php if (!empty($metaData['metaImage'])) { ?>
                            <img src="<?= htmlspecialchars($metaData['metaImage'], ENT_QUOTES, 'UTF-8') ?>" alt="Link preview" class="preview-image post-image">
                        <?php } ?>
                        <div class="preview-content">
                            <?php if (($metaData['metaTitle']) != "No Title Found") { ?>
                                <h3 class="preview-title"><?= htmlspecialchars($metaData['metaTitle'], ENT_QUOTES, 'UTF-8') ?></h3>
                            <?php } ?>

                            <?php if (($metaData['metaDesc']) != "No Description Found") { ?>
                                <p class="preview-description"><?= htmlspecialchars($metaData['metaDesc'], ENT_QUOTES, 'UTF-8') ?></p>
                            <?php } ?>

                            <?php if (!empty($metaData['metaUrl'])) { ?>
                                <a href="<?= htmlspecialchars($metaData['metaUrl'], ENT_QUOTES, 'UTF-8') ?>" class="preview-link">
                                    <?= !empty($metaData['metaDomain']) ? htmlspecialchars($metaData['metaDomain'], ENT_QUOTES, 'UTF-8') : htmlspecialchars($metaData['metaUrl'], ENT_QUOTES, 'UTF-8') ?>
                                </a>
                            <?php } ?>
                        </div>
                    </div>
                <?php } ?>
            <?php } ?>
        </div>

        <!-- <div class="post-stats">
            <div><?= getLikeCount($readerdb, $id) ?> likes</div>
            <div><?= getReplyCount($readerdb, $id) ?> comments</div>
        </div> -->

        <div class="post-actions-bar">
            <!-- Like Button -->

            <div class="action-button login-button likeButton" id="likeButton_<?= $id ?>" data-id="<?= $id ?>">
                <i class="<?= checkUserLike($readerdb, $id, $gUserId) ?>"></i>
                <span class="likeCount"><?= getLikeCount($readerdb, $id) ?></span>
            </div>

            <!-- Comment Button -->
            <a style="text-decoration: none;" href="post-details.php?id=<?= $id ?>" class="action-button login-button">
                <i class="far fa-comment"></i>
                <span><?= getReplyCount($readerdb, $id) ?></span>
            </a>

            <!-- Bookmark Button -->
            <div class="action-button login-button saveButton" id="saveButton_<?= $id ?>" data-id="<?= $id ?>">
                <i class="<?= checkUserSave($readerdb, $id, $gUserId) ?>"></i>
                <!-- <span>Bookmark</span> -->
            </div>

            <!-- Channels Button -->
            <!-- The Action Button with TV icon -->
            <div class="action-button login-button" onclick="fetchChannelData(<?= $id ?>)">
                <i class="fas fa-tv"></i>
                <div id="channelList_<?= $id ?>" class="channel-dropdown" style="display:none"></div>
            </div>

            <!-- Channel Cards Modal System -->
            <div class="channel-overlay" id="channelOverlay_<?= $id ?>" onclick="hideChannelCards(<?= $id ?>)"></div>
            <div class="channel-cards-wrapper" id="channelCardsWrapper_<?= $id ?>">
                <div class="channel-cards-header">
                    <h3>Your Channels</h3>
                    <button class="close-btn" onclick="hideChannelCards(<?= $id ?>)">×</button>
                </div>
                <div class="channel-cards-content" id="channelCardsContent_<?= $id ?>">
                    <!-- Channel cards will be populated here -->
                </div>
            </div>

            <!-- Share Button -->
            <div class="action-button login-button shareNow" id="shareButton_<?= $id ?>" data-id="<?= $id ?>">
                <i class="far fa-share-square"></i>
            </div>
        </div>

        <!-- Dropdown Menu -->

        <div id="dropcardMenu_<?= $id ?>" class="card-dropdown-menu" style="display:none">
            <div class="card-menu-container">
                <? if ($gUserId == $userId) { ?>
                    <div class="card-menu-item edit-post editPostModal" onclick="editPost(<?= $id ?>)">
                        <i class="fas fa-edit"></i>
                        <p>Edit this Post</p>
                    </div>

                    <div class="card-menu-item delete-post" onclick="deletePost(<?= $id ?>)">
                        <i class="fas fa-trash"></i>
                        <p>Delete this Post</p>
                    </div>
                <? } else { ?>
                    <div class="card-menu-item report-post" onclick="reportPost(<?= $id ?>)" data-id=<?= $id ?>>
                        <i class="fas fa-flag"></i>
                        <p>Report this Post</p>
                    </div>
                    <div class="card-menu-item block-user" onclick="blockAccount(<?= $userId ?>)" data-userid="<?= $userId ?>">
                        <i class="fas fa-user-slash"></i>
                        <p>Block this Account</p>
                    </div>
                <? } ?>
            </div>
        </div>
    </div>
    <?

}


function show_main_post($postId)
{
    global $readerdb;
    $sql = "SELECT * FROM reader_stream WHERE id= $postId";
    $result = $readerdb->query($sql);

    if ($result->num_rows > 0) {
        // Fetch the first (and only) row
        $row = $result->fetch_assoc();
        // Call the captureStream function with the data
        main_post_card($row['id'], $row['userId'], $row['chat'], $row['postedOn'], $row['editedOn'], $row['mediaPath'], $row['metadata']);
    } else {
        echo "No results found";
    }
}

function show_reply_content($postId)
{
    global $readerdb, $creamdb;
    // SQL query to get posts ordered by the most recent
    $sql = "SELECT * FROM reader_stream WHERE referenceId = $postId and deleteFlag=0";
    $result = $readerdb->query($sql);

    if ($result->num_rows > 0) {

    ?>
        <div class="mid_container all_post_container">
            <div class="header d-flex justify-content-between align-items-center ">
                <h1 class="fs-5 fw-bold text-white">Replies</h1>
            </div>
            <? while ($row = $result->fetch_assoc()) { ?>
                <div class="post" style="gap: 10px; position: relative;">
                    <div class="post-header d-flex align-items-center" style="justify-content: space-between;">
                        <div class="d-flex">
                            <div class="avatar me-3">
                                <img src="<?= viewProfilePic($creamdb, $row['userId']) ?>" alt="Default Image" onerror="this.onerror=null; this.src='inc/img/arvind.png';">
                            </div>
                            <div class="username-date">
                                <!-- Assuming showUserName fetches the username from the database using the userId -->
                                <h5 class="mb-0"><?= showUserName($creamdb, $row['userId']); ?></h5>
                                <span class="text-muted"><?= formatToIST($row['postedOn']); ?></span>
                                <?php if ($row['editedOn']) { ?>
                                    <small class="text-muted">(Edited)</small>
                                <?php } ?>
                            </div>
                        </div>
                    </div>

                    <div class="post-content mt-3">
                        <p><?= htmlspecialchars($row['chat']); ?></p>
                        <?php if ($row['mediaPath']): ?>
                            <?php if (strpos($row['mediaPath'], 'mp4') !== false): ?>
                                <video controls>
                                    <source src="<?= htmlspecialchars($row['mediaPath']); ?>" type="video/mp4">
                                    Your browser does not support the video tag.
                                </video>
                            <?php else: ?>
                                <img src="<?= htmlspecialchars($row['mediaPath']); ?>" alt="Post media">
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                    <div class="actions d-flex justify-content-end mt-2">
                        <!-- Like Button (Needs AJAX to dynamically update like count) -->
                        <button class="btn reader-button likeButton flex" data-id='<?= $row['id'] ?>'>
                            <i class="fa-regular fa-thumbs-up" style="padding-right: 4px; padding-top: 2px;"></i>
                            <div class="likeCount likedUsers" data-id="<?= $row['id'] ?>">
                                <?= getLikeCount($readerdb, $row['id']) ?>
                            </div>
                        </button>


                        <button class="btn reader-button flex btn-link"><i class="fa-regular fa-comments" style="padding-right: 4px; padding-top: 2px;"></i>
                            <div class="replyCount">
                                <?= getReplyCount($readerdb, $row['id']) ?>
                            </div>
                        </button>

                    </div>
                </div>
            <?
            }
            ?>
        </div>
    <? } else { ?>
        Be the first one to reply
<?
    }
}

function fetch_title($postId)
{
    global $readerdb;
    $sql = "SELECT chat FROM reader_stream WHERE id= $postId";
    $result = $readerdb->query($sql);

    if ($result->num_rows > 0) {
        // Fetch the first (and only) row
        $row = $result->fetch_assoc();
        if ($row['chat'] != null) {
            return substr($row['chat'], 0, 50) . "...";
        } else {
            return "News Junction";
        }
    } else {
        return "News Junction";
    }
}

function fetch_meta_image($postId)
{
    global $readerdb;
    $sql = "SELECT mediaPath FROM reader_stream WHERE id= $postId";
    $result = $readerdb->query($sql);
    if ($result->num_rows > 0) {
        // Fetch the first (and only) row
        $row = $result->fetch_assoc();
        if ($row['mediaPath'] != null) {
            $image = explode(",", $row['mediaPath']);
            if (count($image) >= 1) {
                return "https://newsjunction.net/" . $image[0];
            } else {
                return "https://newsjunction.net/inc/img/logo.black.png";
            }
        } else {
            return "https://newsjunction.net/inc/img/logo.black.png";
        }
    } else {
        return "https://newsjunction.net/inc/img/logo.black.png";
    }
}


$collectionLink = "https://newsjunction.net/streamPush.php?id=" . $postId;
$collectionTitle = fetch_title($postId);
$serverName = $_SERVER['SERVER_NAME'];
$collectionImgCover = fetch_meta_image($postId);
$newsLogo = fetch_meta_image($postId);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $collectionTitle ?></title>


    <!-- Facebook Meta Tags -->
    <meta property="og:url" content="<?= $collectionLink ?>" />
    <meta property="og:type" content="website" />
    <meta property="og:title" content="<?= $collectionTitle ?>" />
    <meta property="og:description" content="<?= $collectionTitle ?>" />
    <? if ($collectionImgCover <> '') { ?>
        <meta property="og:image" content="<?= $collectionImgCover ?>" />
        <meta property="og:image:secure-url" itemprop="image" content="<?= $collectionImgCover ?>" />
    <? } else { ?>
        <meta property="og:image" content="<?= $newsLogo ?>" />
        <meta property="og:image:secure-url" itemprop="image" content="https://<?= $serverName ?>/data/logos/<?= $newsLogo ?>" />
    <? } ?>

    <!-- Twitter Meta Tags -->
    <meta property="twitter:url" content="<?= $collectionLink ?>" />
    <meta name="twitter:card" content="summary" />
    <meta name="twitter:title" content="<?= $companyName ?>" />
    <meta name="twitter:description" content="" />
    <? if ($collectionImgCover <> '') { ?>
        <meta name="twitter:image" content="<?= $collectionImgCover ?>" />
    <? } else { ?>
        <meta name="twitter:image" content="<?= $newsLogo ?>" />
    <? } ?>


    <!-- Add your stylesheets here, e.g., Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.0/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/stream.css">
    <link rel="stylesheet" href="assets/css/social.css">
    <link rel="icon" type="image/x-icon" href="../grfx/img/logo.ico">

    <!-- Bootstrap JS and Bootstrap Icons -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>


    <script src="assets/js/stream.js"></script>
    <!-- open model script -->
    <script>
        function openModal(type, path, count) {
            let str = path;
            let mediaPaths = str.split(",");

            event.stopPropagation();

            const modalContent = $('#modalContent');
            modalContent.empty(); // Clear existing content

            if (type === 'video') {
                const video = $('<video></video>').attr('controls', true).css({
                    'max-width': '100%',
                    'max-height': '100%'
                }).attr('src', mediaPaths[count]);
                modalContent.append(video);
            } else if (type === 'image') {
                const imgContainer = $('<div></div>').css({
                    'position': 'relative',
                    'overflow': 'auto', // Allow scrolling when zoomed in
                    'width': '100%',
                    'height': '100%',
                    'display': 'flex',
                    'justify-content': 'center', // Center the image horizontally
                    'align-items': 'center' // Center the image vertically
                });

                const img = $('<img></img>').attr('src', mediaPaths[count]).css({
                    'max-width': '100%',
                    'max-height': '100%',
                    'cursor': 'zoom-in', // Cursor will indicate zooming
                    'transition': 'transform 0.3s ease' // Smooth transition for scaling
                }).attr('id', 'zoomableImage');

                imgContainer.append(img);
                modalContent.append(imgContainer);
            }

            // Initialize modal
            const modal = new bootstrap.Modal($('#mediaModal')[0]);
            modal.show();

        }
    </script>
    <!-- likedusers -->
    <script>
        $(document).ready(function() {
            $('#saveModalEditButton').on('click', function() {
                saveEditedContent();
            });

            $('.login-button').on('click', function() {
                alert("Login to newsjunction.net to view amazing features!!");
                window.location.href = 'https://newsjunction.net';
            })
        });
        $(document).on('click', '.likedUsers', function() {
            const postId = $(this).data('id'); // Get post ID from the button's data attribute
            $.ajax({
                url: 'fetch_liked_users.php',
                type: 'POST',
                data: {
                    postId: postId
                },
                success: function(response) {
                    console.log(response); // Debugging: Log the response
                    const users = JSON.parse(response);

                    if (users.error) {
                        alert(users.error);
                    } else if (users.message) {
                        $('#likedUsersList').html('<li class="list-group-item text-center">' + users.message + '</li>');
                    } else {
                        const userList = users.map(user => `<li class="list-group-item">${user}</li>`).join('');
                        $('#likedUsersList').html(userList);
                    }

                    const likedUsersModal = new bootstrap.Modal(document.getElementById('likedUsersModal'));
                    likedUsersModal.show();
                },
                error: function() {
                    alert('Error fetching likes.');
                }
            });
        });
    </script>

    <script>
        const userId = <?= $gUserId ?>;
        $(document).ready(function() {
            // Handle like button clicks
            $(document).on('click', '.likeButton', function(e) {

            });
        });

        function toggleLike(button, feedId, userId) {
            var thumbsUpIcon = $(button).find('i'); // The <i> tag with the class indicating the like status
            var likeCountElement = $(button).find('.likeCount'); // The div where the like count is displayed

            var isLiked = thumbsUpIcon.hasClass('fa-solid');
            var requestType = isLiked ? 'unlike' : 'like';

            $.ajax({
                url: '/assets/php/handler.php',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({
                    request: requestType,
                    userId: userId,
                    feedId: feedId
                }),
                success: function(response) {
                    if (response.status === "success") {
                        if (requestType === 'like') {
                            thumbsUpIcon.removeClass('fa-regular').addClass('fa-solid');
                        } else {
                            thumbsUpIcon.removeClass('fa-solid').addClass('fa-regular');
                        }

                        var updatedLikeCount = response.likeCount === null ? '' : response.likeCount;
                        likeCountElement.text(updatedLikeCount);
                    } else {
                        console.error("Error message:", response.message);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error("Error:", textStatus, errorThrown);
                }
            });
        }
    </script>

    <style>
        .dropcardMenu {
            width: fit-content;
            right: 7px;
        }

        .editYourPost {
            padding-bottom: 20px;
        }

        .ytprew iframe {
            width: 100% !important;
        }

        .first_right_container {
            margin-bottom: 65px;
        }
    </style>

</head>

<body>
    <?php include 'assets/php/models.php' ?>
    <div class="container">
        <? show_nav_bar() ?>
        <? include 'assets/php/social_sidebar.php' ?>
        <div class="main-content">
            <div class="posts-feed">
                <? show_main_post($postId) ?>
                <div class="mid_container" onclick='window.location.href="https://newsjunction.net"'>
                    Login to give a reply
                </div>
                <? show_reply_content($postId) ?>

                <?php include 'assets/php/right_news_module.php' ?>
            </div>

        </div>

        <? include "footer.php"; ?>
    </div>

</body>

</html>