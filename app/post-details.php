<?php
require_once './inc/php/db_config.php';
require_once './inc/php/validate.logged.php';
require_once './inc/php/function.php';

// Get the post ID from the URL
if (isset($_GET['id'])) {
    $postId = $_GET['id'];
} else {
    echo "Invalid post ID.";
    exit;
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
            <div class="user-avatar">
                <a href="profile.php?userId=<?= $userId ?>">
                    <img src="<?= viewProfilePic($creamdb, $userId) ?>" alt="User Avatar" class="user-avatar" onerror="this.onerror=null; this.src='/data/profilePic/default.png';">
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
                        <!-- <a href="post-details.php?id=<?= $id ?>" style="text-decoration: none;"> -->
                        <?= convertLink(htmlspecialchars($truncatedContent)); ?>
                        <!-- </a> -->
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
            <div class="action-button" onclick="fetchChannelData(<?= $id ?>)">
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

function show_main_post($postId)
{
    global $readerdb, $creamdb, $gUserId;
    $sql = "SELECT * FROM reader_stream WHERE id= $postId";
    $result = $readerdb->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        generate_stream_card($row['id'], $row['userId'], $row['chat'], $row['postedOn'], $row['editedOn'], $row['mediaPath'], $row['metadata']);
    } else {
        echo "No results found";
    }
}

function show_reply_content($postId)
{
    global $readerdb, $creamdb, $gUserId;
    // SQL query to get posts ordered by the most recent
    $sql = "SELECT * FROM reader_stream WHERE referenceId = $postId and deleteFlag=0";
    $result = $readerdb->query($sql);

    if ($result->num_rows > 0) {
    ?>
        <div class="mid_container all_post_container">
            <div class="header d-flex justify-content-between align-items-center">
                <h1 class="fs-5 fw-bold text-white">Replies</h1>
            </div>
            <? while ($row = $result->fetch_assoc()) { ?>
                <div class="post-card" data-id="<?= $row['id'] ?>">
                    <div class="post-header">
                        <div class="user-avatar">
                            <img src="<?= viewProfilePic($creamdb, $row['userId']) ?>" alt="User Avatar" class="user-avatar" onerror="this.onerror=null; this.src='/data/profilePic/default.png';">
                        </div>
                        <div class="post-info">
                            <div class="post-author">
                                <?= showUserName($creamdb, $row['userId']); ?>
                            </div>
                            <div class="post-meta">
                                <?= formatToIST($row['postedOn']); ?>
                                <?php if ($row['editedOn']) { ?>
                                    <span>(Edited)</span>
                                <?php } ?>
                            </div>
                        </div>
                        <? if ($gUserId == $row['userId']) { ?>
                            <div class="post-menu" onclick="toggleDropcardMenu(<?= $row['id'] ?>)">
                                <i class="fas fa-ellipsis-h"></i>
                            </div>
                        <? } ?>
                    </div>

                    <div class="post-content">
                        <div class="post-text">
                            <?= htmlspecialchars($row['chat']); ?>
                        </div>
                        <?php if ($row['mediaPath']): ?>
                            <div class="post-image">
                                <?php if (strpos($row['mediaPath'], 'mp4') !== false): ?>
                                    <video controls>
                                        <source src="<?= htmlspecialchars($row['mediaPath']); ?>" type="video/mp4">
                                        Your browser does not support the video tag.
                                    </video>
                                <?php else: ?>
                                    <img src="<?= htmlspecialchars($row['mediaPath']); ?>" alt="Post media" class="post-image">
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="post-actions-bar">
                        <!-- Like Button -->
                        <div class="action-button likeButton" data-id="<?= $row['id'] ?>">
                            <i class="<?= checkUserLike($readerdb, $row['id'], $gUserId) ?>"></i>
                            <span class="likeCount"><?= getLikeCount($readerdb, $row['id']) ?></span>
                        </div>

                        <!-- Comment Button -->
                        <a href="?id=<?= $row['id'] ?>" style="text-decoration: none;" class="action-button">
                            <i class="far fa-comment"></i>
                            <span><?= getReplyCount($readerdb, $row['id']) ?></span>
                        </a>
                    </div>

                    <!-- Dropdown Menu -->
                    <? if ($gUserId == $row['userId']) { ?>
                        <div id="dropcardMenu_<?= $row['id'] ?>" class="card-dropdown-menu" style="display:none">
                            <div class="card-menu-container">
                                <div class="card-menu-item edit-post" onclick='editPost(<?= $row['id'] ?>, <?= json_encode($row['chat']) ?>)'>
                                    <i class="fas fa-edit"></i>
                                    <p>Edit this Reply</p>
                                </div>
                                <div class="card-menu-item delete-post" onclick="deletePost(<?= $row['id'] ?>)">
                                    <i class="fas fa-trash"></i>
                                    <p>Delete this Reply</p>
                                </div>
                            </div>
                        </div>
                    <? } ?>
                </div>
            <?php } ?>
        </div>
    <?php
    } else {
    ?>
        <div class="mid_container all_post_container">
            <div class="empty-state">
                <p class="text-muted text-center">Be the first one to reply</p>
            </div>
        </div>
    <?php
    }
}

function show_reply_tab($postId)
{ ?>
    <div class="first_right_container">
        <!-- Middle Section (Main Content) -->
        <div class="mid_container">
            <div class="upload-section">
                <form action="replyPost.php" method="post" enctype="multipart/form-data">
                    <div class="post-card reply-form-card">
                        <div class="post-content">
                            <div style="padding: 10px;">
                                <textarea id="contentTextarea" class="form-control reply-textarea" name="content" rows="3" placeholder="Write your reply..."></textarea>
                            </div>

                            <div class="form-actions">
                                <!-- <div class="media-upload-section">
                                    <input type="file" name="media" id="fileInput" accept="image/*,video/*" class="d-none" onchange="previewMedia();">
                                    <button type="button" class="btn btn-media" onclick="document.getElementById('fileInput').click();">
                                        <i class="fas fa-image"></i> Media
                                    </button> -->
                            </div>
                            <input type="hidden" name="refPostId" value="<?= $postId ?>">

                            <div class="submit-section" style="display: flex;justify-content: end;">
                                <button type="submit" class="media-post-button"> <i class="fas fa-paper-plane"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Preview Section -->
                        <div id="mediaPreview" class="media-preview">
                            <!-- Placeholder for image/video preview -->
                        </div>
                    </div>
            </div>
            </form>

            <script>
                // Check for Web Speech API compatibility
                const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
                let recognition;

                if (SpeechRecognition) {
                    recognition = new SpeechRecognition();
                    recognition.lang = "en-US"; // Set the language
                    recognition.continuous = true; // Keep recording until stopped
                    recognition.interimResults = true; // Show results in real time

                    recognition.onresult = (event) => {
                        const textarea = document.getElementById("contentTextarea");
                        let transcript = '';

                        for (let i = event.resultIndex; i < event.results.length; i++) {
                            transcript += event.results[i][0].transcript;
                        }
                        textarea.value = transcript; // Update textarea with spoken text
                    };

                    recognition.onerror = (event) => {
                        console.error("Speech recognition error detected: " + event.error);
                    };
                }

                function previewMedia() {
                    const fileInput = document.getElementById('fileInput');
                    const mediaPreview = document.getElementById('mediaPreview');
                    const file = fileInput.files[0];

                    // Clear previous previews
                    mediaPreview.innerHTML = '';

                    if (file) {
                        const fileReader = new FileReader();

                        // For image files
                        if (file.type.startsWith('image')) {
                            fileReader.onload = function(e) {
                                const img = document.createElement('img');
                                img.src = e.target.result;
                                img.className = 'preview-image';
                                mediaPreview.appendChild(img);
                            };
                        }

                        // For video files
                        else if (file.type.startsWith('video')) {
                            const video = document.createElement('video');
                            video.controls = true;
                            video.className = 'preview-video';
                            mediaPreview.appendChild(video);

                            fileReader.onload = function(e) {
                                video.src = e.target.result;
                                video.load();
                            };
                        }

                        // Start reading the file
                        fileReader.readAsDataURL(file);
                    }
                }

                // Function to start/stop recording
                function startRecording() {
                    if (recognition) {
                        if (recognition.recognizing) {
                            recognition.stop(); // Stop recognition if it's already running
                        } else {
                            recognition.start(); // Start recognition
                        }
                    }
                }
            </script>
        </div>
    </div>
    </div>
<?php
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post Details</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.0/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="../grfx/img/logo.ico">

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/js/all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="inc/css/social.css">
    <script src="inc/js/new_social_script.js?v=20260528"></script>
    <!-- <script src="inc/js/stream.js"></script> -->
    <!-- likedusers -->
    <script>
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

        function fetchChannelData(id) {
            const userId = <?= $gUserId ?>;
            const postId = id;

            // Show the modal and overlay
            const overlay = document.getElementById(`channelOverlay_${id}`);
            const wrapper = document.getElementById(`channelCardsWrapper_${id}`);
            const cardsContent = document.getElementById(`channelCardsContent_${id}`);

            // Show loading state
            overlay.style.display = 'block';
            wrapper.classList.add('active');
            cardsContent.innerHTML = '<div class="loading-indicator">Loading channels...</div>';

            // Perform AJAX request
            $.ajax({
                url: "process/handle_channel.php",
                type: "GET",
                data: {
                    user_id: userId,
                    post_id: postId
                },
                dataType: "json",
                success: function(data) {
                    // Clear the container
                    cardsContent.innerHTML = '';

                    if (data.success) {
                        if (data.channels.length === 0) {
                            cardsContent.innerHTML = '<div class="no-channels-message">No channels available</div>';
                        } else {
                            // Create channel cards
                            $.each(data.channels, function(index, channel) {
                                let card = $("<div>")
                                    .addClass("channel-card")
                                    .append(
                                        $("<div>")
                                        .addClass("channel-name")
                                        .text(channel.name)
                                    )
                                    .append(
                                        $("<button>")
                                        .addClass("add-to-channel-btn")
                                        .text("Add")
                                        .on("click", function() {
                                            addToChannel(postId, channel.id);
                                        })
                                    );

                                $(cardsContent).append(card);
                            });
                        }

                        // Add "Add Channels" button as a card
                        let addCard = $("<div>")
                            .addClass("add-channel-card")
                            .append(
                                $("<a>")
                                .addClass("add-channel-btn")
                                .attr("href", "add_channel.php")
                                .text("+ Add New Channel")
                            );

                        $(cardsContent).append(addCard);
                    } else {
                        cardsContent.innerHTML = '<div class="error-message">Error: ' + data.message + '</div>';
                    }
                },
                error: function(xhr, status, error) {
                    cardsContent.innerHTML = '<div class="error-message">Failed to load channels. Please try again.</div>';
                    console.error("AJAX Error:", error);
                }
            });

            // Add a document click handler to close the modal when clicking outside
            setTimeout(function() {
                $(document).one("click", function(e) {
                    if (!$(e.target).closest('.channel-cards-wrapper, .action-button').length) {
                        hideChannelCards(id);
                    }
                });
            }, 100);
        }
    </script>

    <script>
        const userId = <?= $gUserId ?>;
        $(document).ready(function() {
            // Handle like button clicks
            $(document).on('click', '.likeButton', function(e) {
                // If the clicked element has the `likedUsers` class, do not toggle like/unlike
                if ($(e.target).hasClass('likedUsers')) {
                    return; // Prevent triggering the like toggle
                }

                // Proceed with like toggle functionality
                var feedId = $(this).data('id');
                toggleLike(this, feedId, userId);
            });
        });

        function toggleLike(button, feedId, userId) {
            var thumbsUpIcon = $(button).find('i'); // The <i> tag with the class indicating the like status
            var likeCountElement = $(button).find('.likeCount'); // The div where the like count is displayed

            var isLiked = thumbsUpIcon.hasClass('fa-solid');
            var requestType = isLiked ? 'unlike' : 'like';

            $.ajax({
                url: '/inc/php/handler.php',
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


</head>

<body>
    <?php include 'inc/php/models.php' ?>
    <div class="container">
        <? include 'inc/php/social_navbar.php' ?>
        <?php include 'inc/php/social_sidebar.php' ?>
        <div class="main-content">
            <div class="posts-feed">
                <div class="first_right_container">
                    <? show_main_post($postId) ?>
                    <? show_reply_tab($postId) ?>
                    <? show_reply_content($postId) ?>
                </div>
                <?php include 'inc/php/right_news_module.php' ?>
            </div>
        </div>
        <?php include 'inc/php/footer.php' ?>
    </div>
</body>
<script>
    // Mobile sidebar toggle
    const sidebarToggle = document.getElementById('sidebar-toggle');
    const sidebar = document.querySelector('.sidebar');
    const sidebarOverlay = document.getElementById('sidebar-overlay');

    sidebarToggle.addEventListener('click', function() {
        sidebar.classList.toggle('active');
        sidebarOverlay.classList.toggle('active');
    });



    sidebarOverlay.addEventListener('click', function() {
        sidebar.classList.remove('active');
        sidebarOverlay.classList.remove('active');
    });
</script>

</html>