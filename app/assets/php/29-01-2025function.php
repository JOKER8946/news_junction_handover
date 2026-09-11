<?
function show_head_tag($title, $styles, $scripts)
{
?>

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= $title ?></title>

        <!-- Stylesheets (consolidated and updated versions) -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.1/aos.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
        <link rel="stylesheet" href="assets/css/styles.css">
        <link rel="stylesheet" href="assets/css/magnific-popup.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.0.0/animate.min.css">

        <!-- Scripts (consolidated and correct order) -->
        <script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-oPtiPLlX2OsQI6V1PYcwZcVv+5zUmfpfkKmG+MJof2A=" crossorigin="anonymous"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <script src="assets/js/magnific-popup.min.js"></script>
        <?= $styles ?>
        <?= $scripts ?>
    </head>
    <style>
        .ytprew iframe {
            width: 100% !important;
        }
    </style>

<?
}

function viewProfilePic($db, $userId)
{
    $sql = "SELECT profile_pic FROM user WHERE id = ?";
    $link = "";
    $path = "https://www.newsjunction.net/data/profilePic/";

    if ($stmt = $db->prepare($sql)) {
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stmt->bind_result($link);
        if ($stmt->fetch()) {
            if ($link == null) {
                return $path . "default.png";
            }
            return $path . $link;
        } else {
            return null;
        }

        $stmt->close();
    } else {
        return "Error preparing statement: " . $db->error;
    }
}

function formatToIST($datetime)
{
    // Create a DateTime object in UTC
    $date = new DateTime($datetime, new DateTimeZone('UTC'));

    // Set the timezone to IST (Asia/Kolkata)
    $date->setTimezone(new DateTimeZone('Asia/Kolkata'));

    // Get the day of the month
    $day = $date->format('d');

    // Add ordinal suffix to the day
    $suffix = ['st', 'nd', 'rd', 'th'];
    if ($day % 10 == 1 && $day != 11) {
        $day .= $suffix[0];
    } elseif ($day % 10 == 2 && $day != 12) {
        $day .= $suffix[1];
    } elseif ($day % 10 == 3 && $day != 13) {
        $day .= $suffix[2];
    } else {
        $day .= $suffix[3];
    }

    // Format the date in the desired format with the suffix, month abbreviation, and time
    $formattedDate = $date->format('jS M, Y') . ' ' . $date->format('g:ia'); // 1:05pm format

    return $formattedDate;
}
function checkUserSave($conn, $postId, $userId)
{
    $saveCheck = 0;
    $sql = "SELECT COUNT(*) AS saveCheck FROM stream_saved WHERE post_id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $postId, $userId);
    $stmt->execute();
    $stmt->bind_result($saveCheck);
    $stmt->fetch();
    $stmt->close();
    if ($saveCheck === 0) {
        return "fa-regular fa-bookmark";
    } else {
        return "fa-solid fa-bookmark";
    }
}
function checkUserLike($conn, $postId, $userId)
{
    $likeCount = 0;
    $sql = "SELECT COUNT(*) AS likeCount FROM reader_stream_like WHERE streamId = ? AND userId = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $postId, $userId);
    $stmt->execute();
    $stmt->bind_result($likeCount);
    $stmt->fetch();
    $stmt->close();
    if ($likeCount === 0) {
        return "fa-regular fa-thumbs-up";
    } else {
        return "fa-solid fa-thumbs-up";
    }
}

function getReplyCount($conn, $postId)
{
    $replyCount = 0;
    $sql = "SELECT COUNT(*) AS replyCount FROM reader_stream WHERE referenceId = ? and deleteFlag=0";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $postId);
    $stmt->execute();
    $stmt->bind_result($replyCount);
    $stmt->fetch();
    $stmt->close();
    if ($replyCount === 0) {
        return null;
    } else {
        return $replyCount;
    }
}

function getLikeCount($conn, $postId)
{
    $likeCount = 0;
    $sql = "SELECT COUNT(*) AS likeCount FROM reader_stream_like WHERE streamId = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $postId);
    $stmt->execute();
    $stmt->bind_result($likeCount);
    $stmt->fetch();
    $stmt->close();
    if ($likeCount === 0) {
        return null;
    } else {
        return $likeCount;
    }
}
function getViewCount($conn, $postId)
{
    $viewCount = 0;
    $sql = "SELECT COUNT(*) AS viewCount FROM stream_analytics WHERE streamId = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $postId);
    $stmt->execute();
    $stmt->bind_result($viewCount);
    $stmt->fetch();
    $stmt->close();
    if ($viewCount === 0) {
        return null;
    } else {
        return ($viewCount * 5) + rand(0, 9);
    }
}

function checkFollow($conn, $userId, $accId)
{
    $followCount = 0;
    $sql = "SELECT COUNT(*) AS follow FROM reader_stream_follow WHERE follower_id = ? AND following_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $userId, $accId);
    $stmt->execute();
    $stmt->bind_result($followCount);
    $stmt->fetch();
    $stmt->close();
    if ($followCount === 0) {
        return false;
    } else {
        return true;
    }
}

function showUserName($db, $userId)
{
    $sql = "SELECT full_name FROM user WHERE id = ?";
    $fullName = "";

    if ($stmt = $db->prepare($sql)) {
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stmt->bind_result($fullName);
        if ($stmt->fetch()) {
            return $fullName;
        } else {
            return null;
        }
        $stmt->close();
    } else {
        return "Error preparing statement: " . $db->error;
    }
}

function showProfileBio($db, $userId)
{
    $sql = "SELECT bio FROM user WHERE id = ?";
    $bio = "";

    if ($stmt = $db->prepare($sql)) {
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stmt->bind_result($bio);
        if ($stmt->fetch()) {
            return $bio;
        } else {
            return null;
        }
        $stmt->close();
    } else {
        return "Error preparing statement: " . $db->error;
    }
}

function fetch_publisher_title($conn, $rss_id)
{
    $stmt = $conn->prepare("SELECT rss_publisher FROM rss_feeds_url WHERE rss_id = ?");
    $stmt->bind_param("i", $rss_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $data = []; // Initialize an array to hold the results
    $row = $result->fetch_assoc();
    return $row['rss_publisher'];
}

function fetch_featured_topic_name($conn, $ft_id)
{
    $stmt = $conn->prepare("SELECT category FROM reader_category WHERE id = ?");
    $stmt->bind_param("i", $ft_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $data = []; // Initialize an array to hold the results
    $row = $result->fetch_assoc();
    return $row['category'];
}

function convertLink($data)
{
    // Regular expression pattern to match URLs (HTTP, HTTPS, FTP)
    // $url_pattern = '/(?:https?|ftp):\/\/(?:[a-zA-Z0-9-]+\.)?(?:[a-zA-Z0-9-]+\.[a-zA-Z]{2,})(?:\/[^\s]*)?/';
    $url_pattern = '/(?:https?|ftp):\/\/(?:[a-zA-Z0-9-]+\.)?(?:[a-zA-Z0-9-]+\.[a-zA-Z]{2,})(?:\/[^\s\)]*)?/';

    // Callback function to replace URLs with <a> tags
    $text_with_links = preg_replace_callback($url_pattern, function ($matches) {
        // Get the matched URL
        $url = $matches[0];
        // Return the <a> tag with the URL as both the href and the link text
        return "<a href=\"$url\" target=\"_blank\" onclick=\"event.stopPropagation();\" style=\"color: #007bff; text-decoration: underline;\">$url</a>";
    }, $data);

    // Output the modified string with links
    return $text_with_links;
}

function generate_stream_card($id, $userId, $chat, $postedOn, $editedOn, $media,  $metaData)
{
    global $gUserId, $conn, $db;
    $maxLength = 220;
    $isTruncated = isset($chat) ? (strlen($chat) > $maxLength) : false;
    // $isTruncated = mb_strlen($chat) > $maxLength;
    $truncatedContent = $isTruncated ? substr($chat, 0, $maxLength) . '...' : $chat;

?>
    <div class="mid_container all_post_container">
        <div class="post" style="display:flex" data-id="<?= $id ?>">
            <div class="con" style="justify-content: space-between; align-items: center;">
                <div class="post-header d-flex align-items-center">
                    <div class="avatar me-2">
                        <a href="profile.php?userId=<?= $userId ?>">
                            <img src="<?= viewProfilePic($db, $userId) ?>" alt="Default Image" onerror="this.onerror=null; this.src='assets/img/default.png';">
                        </a>
                    </div>
                </div>
            </div>
            <div class="postWithMainContainer" style="width: 82%;">
                <div class="usernameWithfollow">
                    <div class="namewithfollow" style="display: flex; justify-content: space-between;">
                        <div class="username-date mb-2">
                            <a href="profile.php?userId=<?= $userId ?>">
                                <h5 class="mb-0" style="line-height: 14px;"><?= showUserName($db, $userId); ?></h5>
                            </a>
                            <span class="text-muted" style="font-size: 12px;"><?= formatToIST($postedOn); ?></span>
                            <? if ($editedOn) { ?>
                                <small class="text-muted">(Edited)</small>
                            <? } ?>
                        </div>
                        <div>
                            <? if ($gUserId != $userId) { ?>
                                <button class="followButton" onclick="" data-id="<?= $userId ?>" style="margin-right : 15px">
                                    <?= checkFollow($conn, $gUserId, $userId) ? 'Following' : 'Follow' ?>
                                </button>
                            <? } ?>
                        </div>
                    </div>
                </div>


                <!-- <div class="post-content" onclick="window.location.href='post-details.php?id=<?= $id ?>'" tabindex="0" data-thumbnail="false"> -->
                <div class="post-content" tabindex="0" data-thumbnail="false">
                    <!-- Display truncated content by default -->
                    <a href="post-details.php?id=<?= $id ?>" style="text-decoration: none;">
                        <?
                        // Check if the content does not have a link
                        if ($truncatedContent != null && !preg_match('/(?:https?|ftp):\/\/(?:[a-zA-Z0-9-]+\.)?(?:[a-zA-Z0-9-]+\.[a-zA-Z]{2,})(?:\/[^\s\)]*)?/', $truncatedContent)) {
                        ?>
                            <p id="postContent_<?= $id ?>" style="margin: 0px;">
                                <?= convertLink(htmlspecialchars($truncatedContent)); ?>
                            </p>
                        <?
                        }
                        ?>
                    </a>
                    <!-- Full content hidden initially -->
                    <?php if ($isTruncated) { ?>
                        <a href="post-details.php?id=<?= $id ?>" style="text-decoration: none;">
                            <p id="fullContent_<?= $id ?>" style="display: none; margin:0px;"><?= convertLink(htmlspecialchars($chat)); ?></p>
                        </a>
                        <div>
                            <button class="btn btn-link readMoreBtn" data-id="<?= $id ?>" onclick="toggleReadMore(<?= $id ?>)">Read More</button>
                        </div>
                    <?php } ?>

                    <!-- Image/Video Display Section -->
                    <?php if ($media) {
                        // Split the comma-separated media string into an array
                        $mediaArray = explode(',', $media);
                        $mediaCount = count($mediaArray);
                        if ($mediaCount < 2) { ?>
                            <div class="stream-media">
                                <?php if (strpos($media, 'mp4') !== false || strpos($media, 'mov') !== false) { ?>
                                    <video controls onclick="openModal('video', '<?= htmlspecialchars($media); ?>',0)">
                                        <source src="<?= htmlspecialchars($media); ?>" type="video/<?= strpos($media, 'mp4') !== false ? 'mp4' : 'quicktime'; ?>">
                                        Your browser does not support the video tag.
                                    </video>
                                <?php } else { ?>
                                    <img src="<?= htmlspecialchars($media); ?>" alt="Post media"
                                        onclick="openModal('image', '<?= htmlspecialchars($media); ?>',0)" style="cursor: pointer;">
                                <?php } ?>
                            </div>
                        <?php } else if ($mediaCount > 1) { ?>
                            <div class="media-container">
                                <?php
                                // Display the first 3 items normally
                                $displayedMedia = 0;
                                foreach ($mediaArray as $mediaItem) {
                                    $mediaItem = trim($mediaItem);

                                    if ($displayedMedia < 4) {
                                ?>
                                        <div class="stream-media">
                                            <?php if (strpos($mediaItem, 'mp4') !== false || strpos($mediaItem, 'mov') !== false) { ?>
                                                <video controls onclick="openModal('video', '<?= htmlspecialchars($media); ?>',<?= $displayedMedia ?>)">
                                                    <source src="<?= htmlspecialchars($mediaItem); ?>" type="video/<?= strpos($mediaItem, 'mp4') !== false ? 'mp4' : 'quicktime'; ?>">
                                                    Your browser does not support the video tag.
                                                </video>
                                            <?php } else { ?>
                                                <img src="<?= htmlspecialchars($mediaItem); ?>" alt="Post media"
                                                    onclick="openModal('image', '<?= htmlspecialchars($media); ?>',<?= $displayedMedia ?>)" style="cursor: pointer;">
                                            <?php } ?>
                                        </div>
                                    <?php
                                        $displayedMedia++;
                                    }
                                }

                                // If there are more than 3 items, show a "more" indicator in the fourth block
                                if (($mediaCount > 4) && ($mediaCount != 4)) {
                                    ?>
                                    <div class="stream-media more">
                                        <span class="more-count">+<?= $mediaCount - 3; ?> more</span>
                                        <!-- Optionally, you can make this span clickable to show remaining images/videos in a modal -->
                                    </div>
                                <?php
                                }
                                ?>
                            </div>
                        <? } ?>
                    <? } ?>
                </div>

                <?
                if (isset($metaData)) {
                    $metaData = json_decode($metaData, true);
                    if (isset($metaData['youtubeLink']) && ($metaData['youtubeLink'] != '')) {
                ?>
                        <div class="ytprew"><?= $metaData['youtubeLink']; ?></div>
                    <? } else { ?>
                        

                        <div class="hyperlink mb-1">
                            <?php if (!empty($metaData['metaImage'])) { ?>
                                <img src="<?= htmlspecialchars($metaData['metaImage'], ENT_QUOTES, 'UTF-8') ?>" alt="Card image">
                            <?php } ?>

                            <div style="padding: 0px; flex-grow: 1;">
                                <?php if (!empty($metaData['metaTitle'])) { ?>
                                    <h3 id="linkHeading" style="font-size: 16px; margin: 0 0 5px;">
                                        <b><?= htmlspecialchars($metaData['metaTitle'], ENT_QUOTES, 'UTF-8') ?></b>
                                    </h3>
                                <?php }
                                if (!empty($metaData['metaDesc'])) { ?>
                                    <p id="linkDesc" style="margin: 0 0 10px; font-size: 14px; line-height: 1.4;">
                                        <?= htmlspecialchars($metaData['metaDesc'], ENT_QUOTES, 'UTF-8') ?>
                                    </p>
                                <? }

                                if (!empty($metaData['metaUrl'])) { ?>
                                    <a id="linkUrl"
                                        href="<?= htmlspecialchars($metaData['metaUrl'], ENT_QUOTES, 'UTF-8') ?>"
                                        style="font-size: 13px; color: #007bff; text-decoration: none;">
                                        <?= !empty($metaData['metaDomain'])
                                            ? htmlspecialchars($metaData['metaDomain'], ENT_QUOTES, 'UTF-8')
                                            : htmlspecialchars($metaData['metaUrl'], ENT_QUOTES, 'UTF-8') ?>
                                    </a>
                                <?php } ?>
                            </div>
                        </div>
                <? }
                } ?>

                <div class="actions d-flex  mt-2" style="justify-content: space-between;">
                    <button class="btn reader-button viewButton flex" style="cursor: default;" data-id='<?= $id ?>'>
                        <i class="fa-solid fa-chart-column" style="padding-right: 4px; padding-top: 2px;"></i>
                        <div class="viewCount" data-id="<?= $id ?>">
                            <?= getViewCount($conn, $id) ?>
                        </div>
                    </button>
                    <!-- Like Button -->
                    <button class="btn reader-button likeButton flex" data-id='<?= $id ?>'>
                        <i class="<?= checkUserLike($conn, $id, $gUserId) ?>" style="padding-right: 4px; padding-top: 2px;"></i>
                        <div class="likeCount likedUsers" data-id="<?= $id ?>">
                            <?= getLikeCount($conn, $id) ?>
                        </div>
                    </button>

                    <!-- Comment Button -->
                    <a style="position: relative;" href="post-details.php?id=<?= $id ?>">
                        <button class="btn reader-button flex btn-link"><i class="fa-regular fa-comments" style="padding-right: 4px; padding-top: 2px;"></i>
                            <div class="replyCount">
                                <?= getReplyCount($conn, $id) ?>
                            </div>
                        </button>
                    </a>
                    <button class="btn reader-button saveButton flex" data-id='<?= $id ?>'>
                        <i class="<?= checkUserSave($conn, $id, $gUserId) ?>" style="padding-right: 4px; padding-top: 2px;"></i>
                    </button>

                    <button class="btn p-1 reader-button copyButton shareNow" data-id='<?= $id ?>'>
                        <i class="fa-solid fa-arrow-up-from-bracket"></i>
                    </button>
                </div>
            </div>


            <div class="menu-container">
                <div alt="menu" width="100%" height="100%" id="menu-icon" style="padding-left: 10px; padding-right: 5px;" <?= $id ?> onclick="toggleDropcardMenu(<?= $id ?>)">⋮</div>

                <!-- Dropcard Menu -->
                <div id="dropcardMenu_<?= $id ?>" class="dropcardMenu stremDropcard">
                    <? if ($gUserId == $userId) { ?>
                        <div class="editYourPost streamPost" onclick='editPost(<?= $id ?>, <?= json_encode(htmlspecialchars($chat, ENT_QUOTES, 'UTF-8')) ?>)'>
                            <p style="width:75%; margin-bottom: 0;">Edit this Post</p>
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                style="cursor: pointer;">
                                <path width="24" height="24" d="m5 16l-1 4l4-1L19.586 7.414a2 2 0 0 0 0-2.828l-.172-.172a2 2 0 0 0-2.828 0zM15 6l3 3m-5 11h8" />
                            </svg>
                        </div>
                        <div class="deleteYourPost streamPost" onclick="deletePost(<?= $id ?>)">
                            <p style="width:75%; margin-bottom: 0;">Delete this Post</p>
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                fill="currentColor" style="cursor: pointer;">
                                <path width="24" height="24" d="M18 19a3 3 0 0 1-3 3H8a3 3 0 0 1-3-3V7H4V4h4.5l1-1h4l1 1H19v3h-1zM6 7v12a2 2 0 0 0 2 2h7a2 2 0 0 0 2-2V7zm12-1V5h-4l-1-1h-3L9 5H5v1zM8 9h1v10H8zm6 0h1v10h-1z" />
                            </svg>
                        </div>

                    <? } else { ?>
                        <div class="reportThisPost streamPost" data-id=<?= $id ?>>
                            <p style="width:75%; margin-bottom: 0;">Report this Post</p>
                            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 32 32">
                                <path fill="currentColor" d="M10 18h8v2h-8zm0-5h12v2H10zm0 10h5v2h-5z" />
                                <path fill="currentColor" d="M25 5h-3V4a2 2 0 0 0-2-2h-8a2 2 0 0 0-2 2v1H7a2 2 0 0 0-2 2v21a2 2 0 0 0 2 2h18a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2M12 4h8v4h-8Zm13 24H7V7h3v3h12V7h3Z" />
                            </svg>
                        </div>
                        <div class="blockThisPost streamPost" data-userid=<?= $userId ?>>
                            <p style="width:75%; margin-bottom: 0;">Block this Account</p>
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
                                <path fill="currentColor" d="M11.5 4a3.5 3.5 0 1 0 0 7a3.5 3.5 0 0 0 0-7M6 7.5a5.5 5.5 0 1 1 11 0a5.5 5.5 0 0 1-11 0m12 7a3.5 3.5 0 0 0-3.08 5.165l4.745-4.744A3.5 3.5 0 0 0 18 14.5m3.08 1.835l-4.745 4.744a3.5 3.5 0 0 0 4.745-4.745M12.5 18a5.5 5.5 0 1 1 11 0a5.5 5.5 0 0 1-11 0M8 16a4 4 0 0 0-4 4h7.05v2H2v-2a6 6 0 0 1 6-6h3v2z" />
                            </svg>

                        </div>
                    <? } ?>
                </div>
            </div>
        </div>
    </div>
<?
}

function captureStream($id, $userId, $chat, $postedOn, $editedOn, $media,  $metaData)
{
    ob_start();
    generate_stream_card($id, $userId, $chat, $postedOn, $editedOn, $media,  $metaData);
    return ob_get_clean();
}

function blockStatus($userId)
{
    global $creamdb, $gUserId;
    $blockCount = false;
    $sql = "SELECT COUNT(*) FROM block_acc WHERE userId = ? AND blockedUserId = ?";
    if ($stmt = $creamdb->prepare($sql)) {
        $stmt->bind_param("ii", $gUserId, $userId);
        $stmt->execute();
        $stmt->bind_result($blockCount);
        $stmt->fetch();
        $stmt->close();
        return $blockCount > 0;
    } else {
        return false;
    }
}


?>