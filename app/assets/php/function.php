<?

function return_cream_subscription($userId)
{
    global $creamdb;
    $sql = "SELECT plan, plan_type FROM cream_subscription WHERE userId = ? AND NOW() BETWEEN start_date AND end_date";
    if ($stmt = $creamdb->prepare($sql)) {
        $stmt->bind_param("i", $userId);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                return $row['plan'];
            } else {
                return "Free";
            }
        }
    }
}

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

function viewProfilePic($creamdb, $userId)
{
    $sql = "SELECT profile_pic FROM user WHERE id = ?";
    $link = "";
    $path = "data/profilePic/";

    if ($stmt = $creamdb->prepare($sql)) {
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
        return "Error preparing statement: " . $creamdb->error;
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

function checkUserSave($readerdb, $postId, $userId)
{
    $saveCheck = 0;
    $sql = "SELECT COUNT(*) AS saveCheck FROM stream_saved WHERE post_id = ? AND user_id = ?";
    $stmt = $readerdb->prepare($sql);
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

function checkUserLike($readerdb, $postId, $userId)
{
    $likeCount = 0;
    $sql = "SELECT COUNT(*) AS likeCount FROM reader_stream_like WHERE streamId = ? AND userId = ?";
    $stmt = $readerdb->prepare($sql);
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

function getReplyCount($readerdb, $postId)
{
    $replyCount = 0;
    $sql = "SELECT COUNT(*) AS replyCount FROM reader_stream WHERE referenceId = ? and deleteFlag=0";
    $stmt = $readerdb->prepare($sql);
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

function getLikeCount($readerdb, $postId)
{
    $likeCount = 0;
    $sql = "SELECT COUNT(*) AS likeCount FROM reader_stream_like WHERE streamId = ?";
    $stmt = $readerdb->prepare($sql);
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

function getViewCount($readerdb, $postId)
{
    $viewCount = 0;
    $sql = "SELECT COUNT(*) AS viewCount FROM stream_analytics WHERE streamId = ?";
    $stmt = $readerdb->prepare($sql);
    $stmt->bind_param("i", $postId);
    $stmt->execute();
    $stmt->bind_result($viewCount);
    $stmt->fetch();
    $stmt->close();
    if ($viewCount === 0) {
        return null;
    } else {
        return ($viewCount * 10) + rand(0, 9);
    }
}

function checkFollow($readerdb, $userId, $accId)
{
    $followCount = 0;
    $sql = "SELECT COUNT(*) AS follow FROM reader_stream_follow WHERE follower_id = ? AND following_id = ?";
    $stmt = $readerdb->prepare($sql);
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

function showUserName($creamdb, $userId)
{
    $sql = "SELECT full_name FROM user WHERE id = ?";
    $fullName = "";

    if ($stmt = $creamdb->prepare($sql)) {
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
        return "Error preparing statement: " . $creamdb->error;
    }
}

function showProfileBio($creamdb, $userId)
{
    $sql = "SELECT bio FROM user WHERE id = ?";
    $bio = "";
    if ($stmt = $creamdb->prepare($sql)) {
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
        return "Error preparing statement: " . $creamdb->error;
    }
}

function fetch_publisher_title($readerdb, $rss_id)
{
    $stmt = $readerdb->prepare("SELECT rss_publisher FROM rss_feeds_url WHERE rss_id = ?");
    $stmt->bind_param("i", $rss_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['rss_publisher'];
}

function fetch_featured_topic_name($readerdb, $ft_id)
{
    $stmt = $readerdb->prepare("SELECT category FROM reader_category WHERE id = ?");
    $stmt->bind_param("i", $ft_id);
    $stmt->execute();
    $result = $stmt->get_result();

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
    global $gUserId, $readerdb, $creamdb;
    $maxLength = 320;
    $isTruncated = isset($chat) ? (strlen($chat) > $maxLength) : false;
    // $isTruncated = mb_strlen($chat) > $maxLength;
    $truncatedContent = $isTruncated ? substr($chat, 0, $maxLength) . '...' : $chat;
?>
    <div class="post-card" id="post_<?= $id ?>" data-id="<?= $id ?>">
        <div class="post-header">
            <div class="user-avatar">
                <a href="profile.php?userId=<?= $userId ?>">
                    <img src="/<?= viewProfilePic($creamdb, $userId) ?>" alt="User Avatar" class="user-avatar" onerror="this.onerror=null; this.src='/data/profilePic/default.png';">
                </a>
            </div>
            <div class="post-info">
                <div class="post-author">
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
                <button class="follow-button" data-id="<?= $userId ?>">
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
                            <img src="/<?= htmlspecialchars($media); ?>" alt="Post media"
 onclick="openModal('image', '<?= htmlspecialchars($media); ?>',0,<?=$id?>)" class="post-image">
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
                                                                               <video controls onclick="openModal('video', '<?= htmlspecialchars($media); ?>',<?= $displayedMedia ?>,<?=$id?>)">
                                            <source src="/<?= htmlspecialchars($mediaItem); ?>" type="video/<?= strpos($mediaItem, 'mp4') !== false ? 'mp4' : 'quicktime'; ?>">
                                            Your browser does not support the video tag.
                                        </video>
                                    <?php } else { ?>
                                        <img src="/<?= htmlspecialchars($mediaItem); ?>" alt="Post media"
                                                                                      onclick="openModal('image', '<?= htmlspecialchars($media); ?>',<?= $displayedMedia ?>,<?=$id?>)">
                                    <?php } ?>
                                </div>
                            <?php
                                // $displayedMedia++;
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

            <div class="action-button likeButton" id="likeButton_<?= $id ?>" data-id="<?= $id ?>">
                <i class="<?= checkUserLike($readerdb, $id, $gUserId) ?>"></i>
                <span class="likeCount"><?= getLikeCount($readerdb, $id) ?></span>
            </div>

            <!-- Comment Button -->
            <a style="text-decoration: none;" href="post-details.php?id=<?= $id ?>" class="action-button">
                <i class="far fa-comment"></i>
                <span><?= getReplyCount($readerdb, $id) ?></span>
            </a>

            <!-- Bookmark Button -->
            <div class="action-button saveButton" id="saveButton_<?= $id ?>" data-id="<?= $id ?>">
                <i class="<?= checkUserSave($readerdb, $id, $gUserId) ?>"></i>
                <!-- <span>Bookmark</span> -->
            </div>

            <!-- Channels Button -->
            <!-- The Action Button with TV icon -->
            <div class="action-button channelButton" onclick="fetchChannelData(<?= $id ?>)">
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
            <div class="action-button shareNow" id="shareButton_<?= $id ?>" data-id="<?= $id ?>">
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


// function checkUserChannel($readerdb, $postId, $userId)
// {
//     $channelCheck = 0;
//     // Query the my_channel table to check if the post is added to the user's channel
//     $sql = "SELECT COUNT(*) AS channelCheck FROM channel_content cc INNER JOIN channels c ON c.id = cc.channel_id WHERE cc.post_id = ? AND c.created_by = ?";
//     $stmt = $readerdb->prepare($sql);
//     $stmt->bind_param("ii", $postId, $userId);
//     $stmt->execute();
//     $stmt->bind_result($channelCheck);
//     $stmt->fetch();
//     $stmt->close();

//     // Return the appropriate icon class based on whether the post is in the user's channel
//     if ($channelCheck === 0) {
//         return "fa-regular fa-plus-circle"; // Icon when not added to the channel
//     } else {
//         return "fa-solid fa-check-circle"; // Icon when added to the channel
//     }
// }


function display_channel_names()
{
    global $readerdb, $gUserId;

    // Query to fetch channels created by the user
    $sql = "SELECT name FROM channels WHERE created_by = ?";
    $stmt = $readerdb->prepare($sql);

    if ($stmt) {
        $stmt->bind_param('i', $gUserId);
        if ($stmt->execute()) {
            $result = $stmt->get_result(); // Get the result set
            if ($result->num_rows > 0) {
                // Loop through the results and display channel names
                while ($row = $result->fetch_assoc()) {
                    echo htmlspecialchars($row['name']) . "<br>";
                }
            } else {
                echo "No channels found.";
            }
        } else {
            echo "Error executing query: " . $stmt->error;
        }
        $stmt->close();
    } else {
        echo "Error preparing statement: " . $readerdb->error;
    }
}

?>