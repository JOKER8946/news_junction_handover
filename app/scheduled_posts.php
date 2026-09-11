<?php
require_once './inc/php/db_config.php';
require_once './inc/php/validate.logged.php';
require_once './inc/php/function.php';

if (isset($_POST['updateSchedule']) && $_POST['updateSchedule'] == 1) {

    $postId = intval($_POST['postId']);
    $scheduledDate = $_POST['scheduledDate'];

    // Validate inputs
    if (empty($postId) || empty($scheduledDate)) {
        echo 'error';
        exit;
    }

    $ist = new DateTimeZone('Asia/Kolkata');
    $utc = new DateTimeZone('UTC');

    $date = new DateTime($scheduledDate, $ist);
    $date->setTimezone($utc);

    // Convert to MySQL datetime format
    $mysqlDateTime = $date->format('Y-m-d H:i:s');

    // Update the database
    $sql = "UPDATE reader_stream SET scheduledDate = ? WHERE id = ? AND userId = ?";
    $stmt = $readerdb->prepare($sql);
    $stmt->bind_param("sii", $mysqlDateTime, $postId, $gUserId);

    if ($stmt->execute()) {
        echo 'success';
    } else {
        echo 'error';
    }

    $stmt->close();
    exit;
}

function generate_schedule_card($id, $userId, $chat, $postedOn, $editedOn, $media,  $metaData, $shFlag, $shDate)
{
    global $gUserId, $readerdb, $creamdb;
    $maxLength = 320;
    $isTruncated = isset($chat) ? (strlen($chat) > $maxLength) : false;
    // $isTruncated = mb_strlen($chat) > $maxLength;
    $truncatedContent = $isTruncated ? substr($chat, 0, $maxLength) . '...' : $chat;
    // Convert UTC datetime from database to IST for display
    $displayDate = '';
    if ($shDate) {
        $utcDateTime = new DateTime($shDate, new DateTimeZone('UTC'));
        $utcDateTime->setTimezone(new DateTimeZone('Asia/Kolkata'));
        $displayDate = $utcDateTime->format('Y-m-d\TH:i');
    }
?>
    <div class="post-card" id="post_<?= $id ?>" data-id="<?= $id ?>">
        <div class="post-header">
            <div class="user-avatar">
                <a href="profile.php?userId=<?= $userId ?>">
                    <img src="<?= viewProfilePic($creamdb, $userId) ?>" alt="User Avatar" class="user-avatar" onerror="this.onerror=null; this.src='inc/img/default.png';">
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
                            <video controls onclick="openModal('video', '<?= htmlspecialchars($media); ?>',0,<?= $id ?>)">
                                <source src="<?= htmlspecialchars($media); ?>" type="video/<?= strpos($media, 'mp4') !== false ? 'mp4' : 'quicktime'; ?>">
                                Your browser does not support the video tag.
                            </video>
                        <?php } else { ?>
                            <img src="<?= htmlspecialchars($media); ?>" alt="Post media"
                                onclick="openModal('image', '<?= htmlspecialchars($media); ?>',0,<?= $id ?>)" class="post-image">
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
                                        <video controls onclick="openModal('video', '<?= htmlspecialchars($media); ?>',<?= $displayedMedia ?>,<?= $id ?>)">
                                            <source src="<?= htmlspecialchars($mediaItem); ?>" type="video/<?= strpos($mediaItem, 'mp4') !== false ? 'mp4' : 'quicktime'; ?>">
                                            Your browser does not support the video tag.
                                        </video>
                                    <?php } else { ?>
                                        <img src="<?= htmlspecialchars($mediaItem); ?>" alt="Post media"
                                            onclick="openModal('image', '<?= htmlspecialchars($media); ?>',<?= $displayedMedia ?>,<?= $id ?>)">
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

        <div style="display: none;" class="post-actions-bar">
            <div class="action-button likeButton" id="likeButton_<?= $id ?>" data-id="<?= $id ?>">
                <i class="<?= checkUserLike($readerdb, $id, $gUserId) ?>"></i>
                <span class="likeCount"><?= getLikeCount($readerdb, $id) ?></span>
            </div>

            <a style="text-decoration: none;" href="post-details.php?id=<?= $id ?>" class="action-button">
                <i class="far fa-comment"></i>
                <span><?= getReplyCount($readerdb, $id) ?></span>
            </a>

            <div class="action-button saveButton" id="saveButton_<?= $id ?>" data-id="<?= $id ?>">
                <i class="<?= checkUserSave($readerdb, $id, $gUserId) ?>"></i>
            </div>


            <div class="action-button channelButton" onclick="fetchChannelData(<?= $id ?>)">
                <i class="fas fa-tv"></i>
                <div id="channelList_<?= $id ?>" class="channel-dropdown" style="display:none"></div>
            </div>

            <div class="channel-overlay" id="channelOverlay_<?= $id ?>" onclick="hideChannelCards(<?= $id ?>)"></div>
            <div class="channel-cards-wrapper" id="channelCardsWrapper_<?= $id ?>">
                <div class="channel-cards-header">
                    <h3>Your Channels</h3>
                    <button class="close-btn" onclick="hideChannelCards(<?= $id ?>)">×</button>
                </div>
                <div class="channel-cards-content" id="channelCardsContent_<?= $id ?>">
                </div>
            </div>

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

                    <div class="card-menu-item delete-post" onclick="deleteShPost(<?= $id ?>)">
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
        <?php if ($shFlag == 1) { ?>
            <button disabled>Posted</button>
        <?php } else { ?>
            <div id="scheduleEdit_<?= $id ?>" style="margin:5px">
                <input type="datetime-local" id="scheduledDateInput_<?= $id ?>" value="<?= date('Y-m-d\TH:i', strtotime($displayDate)) ?>" />
                <button class="btn btn-sm btn-primary ms-2" style="font-size:16px;" onclick="updateScheduleDate(<?= $id ?>)">Reschedule</button>
                <span id="scheduleMsg_<?= $id ?>" style="font-size: 12px; margin-left: 8px;"></span>
            </div>
        <?php } ?>

    </div>
<?
}

function show_stream_content()
{
    global $readerdb, $gUserId; // Ensure $gUserId is defined and accessible

    // New SQL query to select posts where scheduleFlag is NULL and scheduledDate is not NULL
    $sql = "SELECT * 
            FROM reader_stream 
            WHERE scheduledDate IS NOT NULL
            and scheduleFlag  is null
            and userId = $gUserId
            ORDER BY postedOn DESC, id DESC 
            "; // Add ORDER BY to maintain consistency

    // Prepare the statement
    $stmt = $readerdb->prepare($sql);
    $stmt->execute();

    // Get the result of the prepared statement
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            generate_schedule_card($row['id'], $row['userId'], $row['chat'], $row['postedOn'], $row['editedOn'], $row['mediaPath'], $row['metadata'], $row['scheduleFlag'], $row['scheduledDate']);
        }
    } else {
        // Optionally handle the case when no posts are found
        echo "No posts scheduled.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Scheduled Posts</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.0/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="inc/css/social.css">
    <link rel="icon" type="image/x-icon" href="../grfx/img/logo.ico">

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="inc/js/new_social_script.js?v=20260528"></script>

    <style>
        .btn-primary {
            border-radius: 4px;
            background-color: var(--primary);
            color: var(--light-gray);
            padding: 2px;
            border: none;
        }
    </style>
    <script>
        const userId = <?= $gUserId ?>;
        let tempUrl = '';
        let letUrl = true;
        let dotInterval;
        var myModal;

        $(document).ready(function() {
            // Initialize the height on page load
            var $textarea = $('#contentTextarea');
            if ($textarea.length) {
                adjustTextareaHeight($textarea[0]);
            }

            $('.followButton').on('click', function() {
                const targetUserId = $(this).data('id'); // ID of the user to follow/unfollow
                toggleFollow(this, userId, targetUserId); // Pass `this` (the button element) as the first parameter
            });

            $('#saveModalEditButton').on('click', function() {
                saveEditedContent();
            });

            // likedUsers
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

            $('#contentTextarea').on('input', function() {
                var content = $('#contentTextarea').val().trim();
                console.log(content);

                // Updated regular expression to match full URLs, including subdomains and paths
                var url_pattern = /(?:https?|ftp):\/\/(?:[a-zA-Z0-9-]+\.)?(?:[a-zA-Z0-9-]+\.[a-zA-Z]{2,})(?:\/[^\s]*)?/g;

                var youtube_pattern = /(?:v=|youtu\.be\/|embed\/)([a-zA-Z0-9_-]+)/;

                // Attempt to match all URLs within the content
                var matchedUrls = content.match(url_pattern);
                var youtubeUrl = content.match(youtube_pattern);

                if (youtubeUrl) {
                    youtubeUrl.forEach(function(url) {
                        console.log('Matched URL:', url); // Log each matched URL
                    });
                    if (tempUrl != youtubeUrl[0]) {
                        letUrl = true;
                        tempUrl = youtubeUrl[0];
                        removeMeta();
                        // Show the loading animation
                        $('#loadingIcon').show();
                        // $('#postedContent').html(''); // Clear any previous metadata
                        // Send the first matched URL to the server for metadata fetching
                        $.ajax({
                            url: 'link.php', // Submit to the same page
                            type: 'POST',
                            data: {
                                ytUrl: youtubeUrl[0]
                            }, // Send the first matched URL
                            success: function(response) {
                                // Hide the loading animation
                                $('#loadingIcon').hide();
                                if (response.iframe) {
                                    $('#ytPreview').html(response.iframe);
                                    $('#hiddenYTLink').val(response.iframe || '');
                                    $('#hiddenTitle').val(response.title || '');
                                    $('#ytPreview').show();
                                } else {
                                    return;
                                }
                            },
                            error: function() {
                                // Hide the loading animation on error
                                $('#loadingIcon').hide();
                                console.log("Error fetching Youtube Link");
                            }
                        });
                    }
                } else if (matchedUrls) {
                    // Log all matched URLs to the console (for testing purposes)
                    matchedUrls.forEach(function(url) {
                        console.log('Matched URL:', url); // Log each matched URL
                    });
                    if (tempUrl != matchedUrls[0]) {
                        letUrl = true;
                        tempUrl = matchedUrls[0];
                        removeYT();
                        // Show the loading animation
                        $('#loadingIcon').show();
                        // $('#postedContent').html(''); // Clear any previous metadata
                        // Send the first matched URL to the server for metadata fetching
                        $.ajax({
                            url: 'link.php', // Submit to the same page
                            type: 'POST',
                            data: {
                                url: matchedUrls[0]
                            }, // Send the first matched URL
                            success: function(response) {
                                // Hide the loading animation
                                $('#loadingIcon').hide();
                                $('#linkPreview').show();
                                if (response.url) {
                                    $('#linkPreview #linkHeading').html(response.title || ''); // Set heading text
                                    $('#linkPreview #linkDesc').html(response.description || ''); // Set description text
                                    $('#linkPreview #linkUrl').attr('href', response.url || ''); // Set link URL
                                    $('#linkPreview #linkUrl').html(response.domain || '');
                                    $('#hiddenTitle').val(response.title || '');
                                    $('#hiddenDesc').val(response.description || '');
                                    $('#hiddenUrl').val(response.url || '');
                                    $('#hiddenDomain').val(response.domain || '');
                                    if (response.image) {
                                        $('#linkPreview img').attr('src', response.image); // Set image source
                                        $('#hiddenImage').val(response.image);
                                    }
                                } else {
                                    return;
                                }
                            },
                            error: function() {
                                // Hide the loading animation on error
                                $('#loadingIcon').hide();
                                console.log("Error fetching the metadata");
                            }
                        });
                    }
                } else {
                    // Clear content and hide the loading animation if no valid URL is found
                    $('#loadingIcon').hide();
                }
                if (tempUrl != youtubeUrl[0]) {
                    removeYT();
                }
                if (tempUrl != matchedUrls[0]) {
                    removeMeta()
                }
                if (youtubeUrl[0] == '') {
                    removeYT();
                }
                if (matchedUrls[0] == '') {
                    removeMeta();
                }
            });
        });

        function removeMeta() {
            $('#linkPreview').hide();
            $('#hiddenTitle').val('');
            $('#hiddenDesc').val('');
            $('#hiddenUrl').val('');
            $('#hiddenDomain').val('');
            $('#hiddenImage').val('');
        }

        function removeYT() {
            $('ytPreview').html('');
            $('#ytPreview').hide();
        }

        // Close the dropdown menu if the user clicks anywhere outside the menu or the icon
        $(document).click(function(event) {
            var $isClickInsideMenu = $(event.target).closest('.dropcardMenu');
            var $isClickInsideIcon = $(event.target).closest('.menu-container');

            // Close the dropdown if the click is outside the menu and icon
            if ($isClickInsideMenu.length === 0 && $isClickInsideIcon.length === 0) {
                closeAllDropcardMenus();
            }
        });

        // Add event listeners to Edit and Delete buttons to close the dropdown when clicked
        $('.editBtn').click(function() {
            closeAllDropcardMenus();
        });

        $('.dropcardMenu a button').click(function() {
            closeAllDropcardMenus();
        });

        // Attach the `oninput` event for dynamic resizing
        $(document).on('input', '#contentTextarea', function() {
            adjustTextareaHeight(this);
        });

        $(function() {
            // Initialize the modal when the document is ready
            myModal = new bootstrap.Modal($("#uploadModal")[0]);

            // Event listener for showing the modal (for example, when the plus button is clicked)
            $("#plusButton").on("click", function() {
                myModal.show(); // Show the modal
            });
        });

        function deleteShPost(postId) {
            if (confirm("Are you sure you want to delete this post?")) {
                $.ajax({
                    url: 'inc/php/edit_post.php',
                    type: 'POST',
                    data: {
                        action: 'shDelete',
                        post_id: postId
                    },
                    success: function(response) {
                        if (response.status === "success") {
                            alert(response.message);
                            location.reload();
                        } else {
                            alert(response.message);
                        }
                    },
                    error: function() {
                        alert("An error occurred while deleting the post.");
                    }
                });
            }
        }
    </script>
    <script>
        function updateScheduleDate(postId) {
            const input = document.getElementById('scheduledDateInput_' + postId);
            const msg = document.getElementById('scheduleMsg_' + postId);
            const newDate = input.value;

            if (!newDate) {
                msg.innerText = 'Please select a valid date.';
                msg.style.color = 'red';
                return;
            }

            fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `updateSchedule=1&postId=${encodeURIComponent(postId)}&scheduledDate=${encodeURIComponent(newDate)}`
                })
                .then(res => res.text())
                .then(data => {
                    if (data.trim() === 'success') {
                        msg.innerText = 'Updated successfully!';
                        msg.style.color = 'green';
                    } else {
                        msg.innerText = 'Failed to update.';
                        msg.style.color = 'red';
                    }
                });
        }
    </script>
    <script>
        function openModal(type, path) {
            event.stopPropagation();
            const modalContent = $('#modalContent');
            modalContent.empty(); // Clear existing content

            if (type === 'video') {
                const video = $('<video></video>').attr('controls', true).css({
                    'max-width': '100%',
                    'max-height': '100%'
                }).attr('src', path);
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

                const img = $('<img></img>').attr('src', path).css({
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

            // Add zoom functionality
            if (type === 'image') {
                const zoomableImage = $('#zoomableImage');
                const imgContainer = zoomableImage.parent();
                let scaleValue = 1; // Start with the image at its original size (100%)

                // Click event to zoom in (only zoom in, no zoom out)
                zoomableImage.on('click', function() {
                    if (scaleValue < 2) { // Zoom in by 50% up to a max scale of 2 (200%)
                        scaleValue += 0.5; // Zoom in by 50%
                        zoomableImage.css('cursor', 'zoom-out'); // Change cursor to zoom-out after zooming in
                    }
                    zoomableImage.css('transform', `scale(${scaleValue})`);
                });

                // Enable scrolling to zoom in (no zoom out)
                zoomableImage.on('wheel', function(event) {
                    event.preventDefault();

                    // Zoom in when scrolling up
                    if (scaleValue < 2 && event.originalEvent.deltaY < 0) { // Zoom in if scale < 2 and scroll up
                        scaleValue += 0.1; // Zoom in by 10%
                    }

                    scaleValue = Math.min(scaleValue, 2); // Limit the zoom to 200%

                    zoomableImage.css('transform', `scale(${scaleValue})`);
                });

                // Allow dragging functionality after zooming in (scroll to move the image)
                let isDragging = false;
                let startX, startY, scrollLeft, scrollTop;

                imgContainer.on('mousedown', function(e) {
                    if (scaleValue > 1) { // Enable dragging only when zoomed in
                        isDragging = true;
                        startX = e.pageX - imgContainer.offset().left;
                        startY = e.pageY - imgContainer.offset().top;
                        scrollLeft = imgContainer.scrollLeft();
                        scrollTop = imgContainer.scrollTop();
                    }
                });

                imgContainer.on('mouseleave mouseup', function() {
                    isDragging = false;
                });

                imgContainer.on('mousemove', function(e) {
                    if (!isDragging) return;
                    e.preventDefault();
                    const moveX = e.pageX - startX;
                    const moveY = e.pageY - startY;
                    imgContainer.scrollLeft(scrollLeft - moveX);
                    imgContainer.scrollTop(scrollTop - moveY);
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
            <div class="posts-feed">
                <div class="first_right_container">
                    <h1 style="padding: 10px;">Scheduled Posts</h1>
                    <? show_stream_content() ?>
                </div>
            </div>
            <?php include 'inc/php/right_news_module.php' ?>
        </div>
        <? include "inc/php/footer.php"; ?>
    </div>


</body>

</html>