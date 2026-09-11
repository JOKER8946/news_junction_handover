<?

function convertLink($data)
{
    // Regular expression pattern to match URLs (HTTP, HTTPS, FTP)
    $url_pattern = '/(?:https?|ftp):\/\/(?:[a-zA-Z0-9-]+\.)?(?:[a-zA-Z0-9-]+\.[a-zA-Z]{2,})(?:\/[^\s]*)?/';

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

function bgenerate_stream_card($id, $userId, $chat, $postedOn, $editedOn, $media,  $metaData)
{
    global $gUserId, $conn, $db;
    $maxLength = 220;
    $isTruncated = strlen($chat) > $maxLength;
    $truncatedContent = $isTruncated ? substr($chat, 0, $maxLength) . '...' : $chat;
?>
    <div class="mid_container all_post_container">
        <div class="post" style="display:flex">
            <div class="con" style=" justify-content: space-between; align-items: center;">
                <div class="post-header d-flex align-items-center">
                    <div class="avatar me-2">
                        <a href="profile.php?userId=<?= $userId ?>">
                            <img src="<?= viewProfilePic($db, $userId) ?>" alt="Default Image" onerror="this.onerror=null; this.src='inc/img/default.png';">
                        </a>
                    </div>
                </div>
            </div>
            <div class="postWithMainContainer" style="width: 82%;">
                <div class="usernameWithfollow">
                    <div class="namewithfollow" style="display: flex; justify-content: space-between;">
                        <div class="username-date mb-2">
                            <!-- Assuming showUserName fetches the username from the database using the userId -->
                            <a href="profile.php?userId=<?= $userId ?>">
                                <h5 class="mb-0" style="line-height: 14px;"><?= showUserName($db, $userId); ?></h5>
                            </a>
                            <span class="text-muted" style="font-size: 12px;"><?= formatToIST($postedOn); ?></span>
                            <?php if ($editedOn) { ?>
                                <small class="text-muted">(Edited)</small>
                            <?php } ?>

                        </div>
                        <div>
                            <? if ($gUserId != $userId) { ?>
                                <button class="followButton" data-id="<?= $userId ?>">
                                    <?= checkFollow($conn, $gUserId, $userId) ? 'Following' : 'Follow' ?>
                                </button>
                            <? } ?>
                        </div>
                    </div>
                </div>
                <?
                if (isset($metaData)) {
                    $metaData = json_decode($metaData, true);
                    if (isset($metaData['youtubeLink']) && ($metaData['youtubeLink'] != '')) {
                ?>
                        <div class="ytprew">
                            <?= $metaData['youtubeLink']; ?>
                        </div>

                    <?
                    } else {
                    ?>
                        <div class="linkDisplay" style="width: 100%; height:auto;">
                            <div class="hyperlink mb-1" style="padding: 5px;background-color:#dedede;border-radius: 5px;">
                                <img src="<?= $metaData['metaImage'] ?>"
                                    alt="Card image">


                                <div style="padding: 0px; flex-grow: 1;">
                                    <b>
                                        <h3 id="linkHeading" style="font-size: 16px; margin: 0 0 5px; color: #333;"><?= $metaData['metaTitle'] ?></h3>
                                    </b>
                                    <p id="linkDesc"
                                        style="margin: 0 0 10px; font-size: 14px; color: #555; line-height: 1.4;">
                                        <?= $metaData['metaDesc'] ?>
                                    </p>
                                    <a id="linkUrl" href="<?= $metaData['metaUrl'] ?>"
                                        style="font-size: 13px; color: #007bff; text-decoration: none;">
                                        <?= $metaData['metaDomain'] ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                <?
                    }
                } ?>


                <a href="post-details.php?id=<?= $id ?>" style="text-decoration: none;">
                    <div class="post-content">
                        <!-- Display truncated content by default -->
                        <p id="postContent_<?= $id ?>" style='margin:0px;'>
                            <?= htmlspecialchars($truncatedContent); ?>
                        </p>
                    </div>
                </a>
                <!-- Full content hidden initially -->
                <?php if ($isTruncated): ?>
                    <a href="post-details.php?id=<?= $id ?>" style="text-decoration: none;">
                        <p id="fullContent_<?= $id ?>" style="display: none; margin:0px;"><?= htmlspecialchars($chat); ?></p>
                    </a>
                    <div>
                        <button class="btn btn-link readMoreBtn" data-id="<?= $id ?>" onclick="toggleReadMore(<?= $id ?>);">Read More</button>
                    </div>
                <?php endif; ?>

                <!-- Image/Video Display Section -->
                <?php if ($media): ?>
                    <?php if (strpos($media, 'mp4') !== false): ?>
                        <video controls onclick="openModal('video', '<?= htmlspecialchars($media); ?>')">
                            <source src="<?= htmlspecialchars($media); ?>" type="video/mp4">
                            Your browser does not support the video tag.
                        </video>
                    <?php else: ?>
                        <img src="<?= htmlspecialchars($media); ?>" alt="Post media"
                            onclick="openModal('image', '<?= htmlspecialchars($media); ?>')" style="cursor: pointer;">
                    <?php endif; ?>
                <?php endif; ?>

                <!-- Modal -->
                <div id="mediaModal" class="modal fade" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <button style="background-color:#333; position:absolute; right:20px;" type="button" class="btn-close zoomButton" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body text-center" style="position: relative;">
                                <div id="modalContent" style="max-width: auto; max-height: auto; transition: transform 0.3s ease;">

                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
            <? if ($gUserId == $userId) { ?>
                <div class="menu-container">
                    <div alt="menu" width="100%" height="100%" id="menu-icon" <?= $id ?> onclick="toggleDropcardMenu(<?= $id ?>)">⋮</div>

                    <!-- Dropcard Menu -->
                    <div id="dropcardMenu_<?= $id ?>" class="dropcardMenu">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                            fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                            onclick='editPost(<?= $id ?>, <?= json_encode($chat) ?>)'
                            style="cursor: pointer;">
                            <path width="24" height="24" d="m5 16l-1 4l4-1L19.586 7.414a2 2 0 0 0 0-2.828l-.172-.172a2 2 0 0 0-2.828 0zM15 6l3 3m-5 11h8" />
                        </svg>
                        <!-- <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 72 72"><path fill="#fff" d="M51.76 17H20.153v37.65c0 4.06 3.29 5.62 7.35 5.62H44.41c4.06 0 7.35-1.56 7.35-5.62zM31 16v-4h10v4"/><path fill="#9b9b9a" d="M51 37v20.621L48.3 60H33z"/><path fill="#fff" d="M17 16h38v4H17z"/><path fill="none" stroke="#000" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="2" d="M31 16v-4h10v4m10 9v31a4 4 0 0 1-4 4H25a4 4 0 0 1-4-4V25m-4-9h38v4H17zm24 12.25V55M31 28.25V55"/></svg> -->
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                            fill="currentColor" onclick="deletePost(<?= $id ?>)" style="cursor: pointer;">
                            <path width="24" height="24" d="M18 19a3 3 0 0 1-3 3H8a3 3 0 0 1-3-3V7H4V4h4.5l1-1h4l1 1H19v3h-1zM6 7v12a2 2 0 0 0 2 2h7a2 2 0 0 0 2-2V7zm12-1V5h-4l-1-1h-3L9 5H5v1zM8 9h1v10H8zm6 0h1v10h-1z" />
                        </svg>
                    </div>

                </div>
            <? } ?>

            <a href="post-details.php?id=<?= $id ?>" style="text-decoration: none;">
                <!-- <div class="post-content mt-2">
                            <p><?= htmlspecialchars($chat); ?></p>
                            <?php if ($media): ?>
                                <?php if (strpos($media, 'mp4') !== false): ?>
                                    <video controls>
                                        <source src="<?= htmlspecialchars($media); ?>" type="video/mp4">
                                        Your browser does not support the video tag.
                                    </video>
                                <?php else: ?>
                                    <img src="<?= htmlspecialchars($media); ?>" alt="Post media">
                                <?php endif; ?>
                            <?php endif; ?>
                        </div> -->
            </a>

            <div class="actions d-flex justify-content-end ">
                <!-- Like Button -->
                <button class="btn reader-button likeButton flex" data-id='<?= $id ?>'>
                    <i class="<?= checkUserLike($conn, $id, $gUserId) ?>" style="padding-right: 4px; padding-top: 2px;"></i>
                    <div class="likeCount likedUsers" data-id="<?= $id ?>">
                        <?= getLikeCount($conn, $id) ?>
                    </div>
                </button>
                <!-- voice recording  -->
                <!-- <button class="btn btn-link">
                            <i class="fas fa-microphone"></i> Record
                        </button> -->
                <!-- Voice Recording Button and Preview -->
                <!-- <div class="mt-3">
                            <button type="button" id="recordBtn" class="btn btn-link">Record</button>
                            <button type="button" id="stopBtn" class="btn btn-link" disabled>Stop</button>
                            <div id="audioPreview" class="mt-3"></div>
                        </div> -->
                <!-- <input type="hidden" name="audioData" id="audioData"> -->

                <!-- Comment Button -->
                <a style="position: relative;" href="post-details.php?id=<?= $id ?>">
                    <button class="btn reader-button flex btn-link"><i class="fa-regular fa-comments" style="padding-right: 4px; padding-top: 2px;"></i>
                        <div class="replyCount">
                            <?= getReplyCount($conn, $id) ?>
                        </div>
                    </button>
                </a>
            </div>
        </div>
    </div>
<?
}

function generate_stream_card($id, $userId, $chat, $postedOn, $editedOn, $media,  $metaData)
{
    global $gUserId, $conn, $db;
    $maxLength = 220;
    $isTruncated = strlen($chat) > $maxLength;
    $truncatedContent = $isTruncated ? substr($chat, 0, $maxLength) . '...' : $chat;

?>
    <div class="mid_container all_post_container">
        <div class="post" style="display:flex">
            <div class="con" style="justify-content: space-between; align-items: center;">
                <div class="post-header d-flex align-items-center">
                    <div class="avatar me-2">
                        <a href="profile.php?userId=<?= $userId ?>">
                            <img src="<?= viewProfilePic($db, $userId) ?>" alt="Default Image" onerror="this.onerror=null; this.src='inc/img/default.png';">
                        </a>
                    </div>
                </div>
            </div>
            <div class="postWithMainContainer" style="width: 82%;" onclick="window.location.href='post-details.php?id=<?= $id ?>'">
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
                                <button class="followButton" data-id="<?= $userId ?>">
                                    <?= checkFollow($conn, $gUserId, $userId) ? 'Following' : 'Follow' ?>
                                </button>
                            <? } ?>
                        </div>
                    </div>
                </div>
                <?
                if (isset($metaData)) {
                    $metaData = json_decode($metaData, true);
                    if (isset($metaData['youtubeLink']) && ($metaData['youtubeLink'] != '')) {
                ?>
                        <div class="ytprew"><?= $metaData['youtubeLink']; ?></div>
                    <? } else { ?>
                        <div class="linkDisplay" style="width: 100%; height:auto;">
                            <div class="hyperlink mb-1" style="padding: 5px; background-color:#dedede; border-radius: 5px;">
                                <img src="<?= $metaData['metaImage'] ?>" alt="Card image">
                                <div style="padding: 0px; flex-grow: 1;">
                                    <b>
                                        <h3 id="linkHeading" style="font-size: 16px; margin: 0 0 5px; color: #333;"><?= $metaData['metaTitle'] ?></h3>
                                    </b>
                                    <p id="linkDesc" style="margin: 0 0 10px; font-size: 14px; color: #555; line-height: 1.4;">
                                        <?= $metaData['metaDesc'] ?>
                                    </p>
                                    <a id="linkUrl" href="<?= $metaData['metaUrl'] ?>" style="font-size: 13px; color: #007bff; text-decoration: none;">
                                        <?= $metaData['metaDomain'] ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                <? }
                } ?>

                <div class="post-content">
                    <!-- Display truncated content by default -->
                    <p id="postContent_<?= $id ?>" style='margin:0px;'>
                        <?= convertLink(htmlspecialchars($truncatedContent)); ?>
                    </p>
                    <!-- Full content hidden initially -->
                    <? if ($isTruncated) { ?>
                        <a href="post-details.php?id=<?= $id ?>" style="text-decoration: none;">
                            <p id="fullContent_<?= $id ?>" style="display: none; margin:0px;"><?= convertLink(htmlspecialchars($chat)); ?></p>
                        </a>
                        <div>
                            <button class="btn btn-link readMoreBtn" data-id="<?= $id ?>" onclick="toggleReadMore(<?= $id ?>)">Read More</button>
                        </div>
                    <? } ?>

                    <!-- Image/Video Display Section -->
                    <? if ($media) { ?>
                        <? if (strpos($media, 'mp4') !== false) { ?>
                            <video controls onclick="openModal('video', '<?= htmlspecialchars($media); ?>')">
                                <source src="<?= htmlspecialchars($media); ?>" type="video/mp4">
                                Your browser does not support the video tag.
                            </video>
                        <? } else { ?>
                            <img src="<?= htmlspecialchars($media); ?>" alt="Post media"
                                onclick="openModal('image', '<?= htmlspecialchars($media); ?>')" style="cursor: pointer;">
                        <? } ?>
                    <? } ?>
                </div>

                <div class="actions d-flex justify-content-end ">
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
                </div>
            </div>

            <? if ($gUserId == $userId) { ?>
                <div class="menu-container">
                    <div alt="menu" width="100%" height="100%" id="menu-icon" <?= $id ?> onclick="toggleDropcardMenu(<?= $id ?>)">⋮</div>

                    <!-- Dropcard Menu -->
                    <div id="dropcardMenu_<?= $id ?>" class="dropcardMenu">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                            fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                            onclick='editPost(<?= $id ?>, <?= json_encode($chat) ?>)'
                            style="cursor: pointer;">
                            <path width="24" height="24" d="m5 16l-1 4l4-1L19.586 7.414a2 2 0 0 0 0-2.828l-.172-.172a2 2 0 0 0-2.828 0zM15 6l3 3m-5 11h8" />
                        </svg>
                        <!-- <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 72 72"><path fill="#fff" d="M51.76 17H20.153v37.65c0 4.06 3.29 5.62 7.35 5.62H44.41c4.06 0 7.35-1.56 7.35-5.62zM31 16v-4h10v4"/><path fill="#9b9b9a" d="M51 37v20.621L48.3 60H33z"/><path fill="#fff" d="M17 16h38v4H17z"/><path fill="none" stroke="#000" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="2" d="M31 16v-4h10v4m10 9v31a4 4 0 0 1-4 4H25a4 4 0 0 1-4-4V25m-4-9h38v4H17zm24 12.25V55M31 28.25V55"/></svg> -->
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                            fill="currentColor" onclick="deletePost(<?= $id ?>)" style="cursor: pointer;">
                            <path width="24" height="24" d="M18 19a3 3 0 0 1-3 3H8a3 3 0 0 1-3-3V7H4V4h4.5l1-1h4l1 1H19v3h-1zM6 7v12a2 2 0 0 0 2 2h7a2 2 0 0 0 2-2V7zm12-1V5h-4l-1-1h-3L9 5H5v1zM8 9h1v10H8zm6 0h1v10h-1z" />
                        </svg>
                    </div>
                </div>
            <? } ?>

        </div>
    </div>
<?
}

?>