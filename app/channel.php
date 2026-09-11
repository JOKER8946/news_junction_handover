<?php
require_once './inc/php/db_config.php';
require_once './inc/php/validate.logged.php';
require_once './inc/php/function.php';

$channel_id = $_GET['channelId'];

function show_stream_content($channel_id)
{
    global $readerdb, $gUserId; // Ensure $gUserId is defined and accessible

    // Fetch channel details (name, profilePic, bio)
    $channel_sql = "SELECT name, profilePic, bio FROM channels WHERE id = ?";
    $channel_stmt = $readerdb->prepare($channel_sql);
    $channel_stmt->bind_param("i", $channel_id);
    $channel_stmt->execute();
    $channel_result = $channel_stmt->get_result();

    // Check if channel exists
    if ($channel_result->num_rows > 0) {
        $channel_info = $channel_result->fetch_assoc();
        $channel_name = $channel_info['name'];
        $profilePic = "data/channelPic/" . $channel_info['profilePic'];
        $bio = $channel_info['bio'];
    } else {
        echo "<p>Channel not found.</p>";
        return;
    } ?>

    <div class='channel_info'>
        <img src=<?= $profilePic ?> alt='Profile Picture' class='channel-profile-pic' onerror="this.onerror=null; this.src='/data/profilePic/default.png';">
        <div class='channel_text'>
            <h1><?= $channel_name ?></h1>
            <p><?= $bio ?></p>
        </div>
    </div>
<?
    // Fetch posts for the channel
    $sql = "SELECT cc.post_id AS id, rs.userId, rs.chat, rs.postedOn, rs.editedOn, rs.mediaPath, rs.metadata 
            FROM reader_stream rs 
            INNER JOIN channel_content cc ON rs.id = cc.post_id 
            WHERE rs.deleteFlag = 0 
              AND cc.channel_id = ? 
            ORDER BY rs.postedOn DESC";

    // Prepare and execute query for posts
    $stmt = $readerdb->prepare($sql);
    $stmt->bind_param("i", $channel_id);
    $stmt->execute();
    $result = $stmt->get_result();

    // Display posts
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            generate_stream_card($row['id'], $row['userId'], $row['chat'], $row['postedOn'], $row['editedOn'], $row['mediaPath'], $row['metadata']);
        }
    } else {
        echo "<p>No posts available.</p>";
    }
}


?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Channels</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.0/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="grfx/img/logo.ico">

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/js/all.min.js"></script> -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="inc/css/social.css">
    <script src="inc/js/new_social_script.js?v=20260528"></script>
    <style>
        .channelButton {
            display: none;
        }

        .channel_info {
            display: flex;
            align-items: center;
            gap: 15px;
            /* Space between image and text */
            padding: 10px;
            max-width: 100%;
            margin: 10px 0;
            /* Adjust spacing */
            background: none;
            /* Remove background */
            box-shadow: none;
            /* Remove box shadow */
            border-radius: 0;
            /* Remove border-radius */
        }

        .channel-profile-pic {
            width: 80px;
            /* Adjust as needed */
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #007bff;
            /* Keep a slight border */
        }

        .channel_text h1 {
            font-size: 20px;
            color: var(--text-primary);
            margin-bottom: 5px;
        }

        .channel_text p {
            font-size: 14px;
            color: #666;
            line-height: 1.4;
            margin: 0;
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

                // Updated regular expression to match full URLs, including subdomains and paths
                // var url_pattern = /(?:https?|ftp):\/\/(?:[a-zA-Z0-9-]+\.)?(?:[a-zA-Z0-9-]+\.[a-zA-Z]{2,})(?:\/[^\s]*)?/g;
                var url_pattern = /(?:https?|ftp):\/\/(?:[a-zA-Z0-9-]+\.)?(?:[a-zA-Z0-9-]+\.[a-zA-Z]{2,})(?:\/[^\s\)]*)?/g;

                var youtube_pattern = /(?:v=|youtu\.be\/|embed\/)([a-zA-Z0-9_-]+)/;

                // Attempt to match all URLs within the content
                var matchedUrls = content.match(url_pattern);
                var youtubeUrl = content.match(youtube_pattern);

                if (youtubeUrl) {
                    youtubeUrl.forEach(function(url) {
                        // console.log('Matched URL:', url); // Log each matched URL
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
                                // console.log("Error fetching Youtube Link");
                            }
                        });
                    }
                } else if (matchedUrls) {
                    // Log all matched URLs to the console (for testing purposes)
                    matchedUrls.forEach(function(url) {
                        // console.log('Matched URL:', url); // Log each matched URL
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
                                // console.log("Error fetching the metadata");
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

        // The uploadPost function can now use myModal because it's in a broader scope
        function uploadPost(channelId) {
            const loadingIcon = document.getElementById('loadingIcon');
            loadingIcon.style.display = 'block';

            // Collect the data from the inputs
            var content = $("#contentTextarea").val(); // Textarea content
            var fileInput = $("#fileInput")[0].files; // All selected files
            var hiddenTitle = $("#hiddenTitle").val();
            var hiddenDesc = $("#hiddenDesc").val();
            var hiddenUrl = $("#hiddenUrl").val();
            var hiddenImage = $("#hiddenImage").val();
            var hiddenDomain = $("#hiddenDomain").val();
            var hiddenYTLink = $("#hiddenYTLink").val();
            var visibility = $("#visibilitySelect").val();
            // Create a FormData object to send data (use FormData if sending files)
            var formData = new FormData();

            // Conditionally append fields if they have values
            if (content) formData.append("content", content);

            // Append each file to the FormData object
            if (fileInput.length > 0) {
                for (var i = 0; i < fileInput.length; i++) {
                    formData.append("media[]", fileInput[i]); // Use an array (media[]) to send multiple files
                }
            }

            if (hiddenTitle) formData.append("hiddenTitle", hiddenTitle);
            if (hiddenDesc) formData.append("hiddenDesc", hiddenDesc);
            if (hiddenUrl) formData.append("hiddenUrl", hiddenUrl);
            if (hiddenImage) formData.append("hiddenImage", hiddenImage);
            if (hiddenDomain) formData.append("hiddenDomain", hiddenDomain);
            if (hiddenYTLink) formData.append("hiddenYTLink", hiddenYTLink);
            if (visibility) formData.append("visibility", visibility);

            // Send the data to PHP via AJAX
            $.ajax({
                url: 'process_data.php', // The PHP page where you want to process the data
                type: 'POST',
                data: formData,
                processData: false, // Important for sending files
                contentType: false, // Important for sending files
                success: function(response) {
                    loadingIcon.style.display = 'none';
                    if (response.status === 'success') {
                        toggleModal();
                        var postId = response.postId;
                        addToChannel(postId, channelId)
                        showToast('Your post has been shared to the channel');
                        // Reload the page after a short delay
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    } else {
                        alert("Error: " + response.message);
                    }
                },
                error: function(xhr, status, error) {
                    $("#loadingIcon").hide();
                    alert("An error occurred: " + error);
                }
            });
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

        function addToChannel(postId, channelId) {
            $.ajax({
                url: 'process/add_to_channel.php',
                type: 'POST',
                data: {
                    postId: postId,
                    channelId: channelId
                },
                success: function(response) {
                    const result = JSON.parse(response); // Parse the JSON response

                },
                error: function(xhr, status, error) {
                    alert('Error: ' + error);
                }
            });
        }
    </script>
</head>

<body>
    <?php include 'inc/php/models.php' ?>
    <div class="media-share-modal" id="post-modal">
        <div class="media-modal-content">
            <div class="media-modal-header">
                <h5 class="media-modal-title">Share</h5>
                <button type="button" class="media-close-modal" data-bs-dismiss="modal" aria-label="Close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="media-upload-section">
                <!-- Upload form section -->
                <div class="media-upload-container">
                    <div class="media-upload-wrapper">
                        <div id="loadingIndicator" class="media-loading-indicator">Generating<span id="dots">...</span></div>
                        <div class="media-textarea-container">
                            <textarea id="contentTextarea" class="media-content-textarea"
                                placeholder="What would you like to share?"
                                oninput="adjustTextareaHeight(this)"></textarea>
                        </div>

                        <!-- Media preview area -->
                        <div id="mediaSlider" class="media-slider-container">
                            <div id="mediaSlides" class="media-slider-slides">
                                <!-- Dynamic media elements will be appended here -->
                            </div>
                            <button class="media-prev media-slideBtn" onclick="moveSlide(-1)">
                                <i class="fas fa-chevron-left"></i>
                            </button>
                            <button class="media-next media-slideBtn" onclick="moveSlide(1)">
                                <i class="fas fa-chevron-right"></i>
                            </button>
                        </div>

                        <!-- Link preview area -->
                        <div id="linkPreview" class="media-link-preview">
                            <div class="media-hyperlink-container">
                                <img class="media-link-image" src="" alt="Link preview">
                                <div class="media-link-content">
                                    <h3 id="linkHeading" class="media-link-title"></h3>
                                    <p id="linkDesc" class="media-link-description"></p>
                                    <a id="linkUrl" class="media-link-url" href=""></a>
                                </div>
                            </div>
                        </div>

                        <!-- YouTube preview area -->
                        <div id="ytPreview" class="media-youtube-preview"></div>

                        <!-- Loading indicator -->
                        <div id="loadingIcon" class="media-loading-spinner">
                            <div class="media-spinner"></div>
                        </div>

                        <!-- Hidden fields for data storage -->
                        <div class="media-hidden-fields">
                            <input type="hidden" id="hiddenTitle">
                            <input type="hidden" id="hiddenDesc">
                            <input type="hidden" id="hiddenUrl">
                            <input type="hidden" id="hiddenImage">
                            <input type="hidden" id="hiddenDomain">
                            <input type="hidden" id="hiddenYTLink">
                        </div>
                    </div>
                </div>
            </div>

            <div class="media-share-footer">
                <div class="media-privacy-selector">
                    <span class="media-privacy-label">Who can see this post?</span>
                    <div class="media-dropdown-container">
                        <button class="media-dropdown-button" id="privacy-dropdown-btn" style="gap:4px;">
                            <i class="fas fa-globe"></i>
                            <span>Public</span>
                            <i class="fas fa-caret-down"></i>
                        </button>
                        <div class="media-dropdown-content" id="privacy-dropdown-content">
                            <div class="media-dropdown-item active" style="gap:4px;">
                                <i class="fas fa-globe"></i>
                                <span>Public</span>
                            </div>
                            <div class="media-dropdown-item" style="gap:4px;">
                                <i class="fas fa-user-friends"></i>
                                <span>People I follow</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="media-share-actions">
                    <!-- File upload button -->
                    <button class="media-action-icon-btn" id="media-upload-btn">
                        <i class="fas fa-image"></i>
                    </button>
                    <input type="file" id="fileInput" class="media-file-input" accept="image/*,video/*" onchange="previewMedia();" multiple>

                    <!-- AI content generation button -->
                    <button class="media-action-icon-btn" id="ai-generate-btn" onclick="fetchGenAIContent()">
                        <i class="fas fa-magic"></i>
                    </button>

                    <!-- Post submission button -->
                    <button class="media-post-button" id="submit-post" onclick="uploadPost(<?= $channel_id ?>)">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
    <!-- Main container -->
    <div class="container">
        <? include 'inc/php/social_navbar.php' ?>
        <? include 'inc/php/social_sidebar.php' ?>
        <div class="main-content">
            <div class="posts-feed">
                <div class="first_right_container">
                    <? show_stream_content($channel_id) ?>
                </div>
                <div class="loading-spinner"></div>
            </div>


            <div class="fab-container">
                <button class="fab-button" id="create-post-fab">
                    <i class="fas fa-plus"></i>
                </button>
            </div>
            <?php include 'inc/php/right_news_module.php' ?>
        </div>

        <div id="channelModal" class="modal" style="display:none;">
            <div class="modal-content">
                <span class="close">&times;</span>
                <p id="modalChannelName"></p>
            </div>
        </div>
        <? include "inc/php/footer.php"; ?>
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

<script>
    // Toggle the modal visibility
    function toggleModal() {
        const modal = document.getElementById('post-modal');
        modal.classList.toggle('active');

        // Reset form when opening
        if (modal.classList.contains('active')) {
            resetForm();
        }
    }

    // Reset the form fields
    function resetForm() {
        document.getElementById('contentTextarea').value = '';
        document.getElementById('mediaSlider').style.display = 'none';
        document.getElementById('mediaSlides').innerHTML = '';
        document.getElementById('linkPreview').style.display = 'none';
        document.getElementById('ytPreview').style.display = 'none';
        document.getElementById('loadingIndicator').style.display = 'none';
        document.getElementById('loadingIcon').style.display = 'none';

        // Reset hidden fields
        document.getElementById('hiddenTitle').value = '';
        document.getElementById('hiddenDesc').value = '';
        document.getElementById('hiddenUrl').value = '';
        document.getElementById('hiddenImage').value = '';
        document.getElementById('hiddenDomain').value = '';
        document.getElementById('hiddenYTLink').value = '';
    }

    // Adjust textarea height based on content
    function adjustTextareaHeight(textarea) {
        textarea.style.height = 'auto';
        textarea.style.height = textarea.scrollHeight + 'px';
    }

    // Toggle dropdown visibility
    function toggleDropdown() {
        const dropdown = document.getElementById('privacy-dropdown-content');
        dropdown.classList.toggle('active');
    }

    // Handle dropdown item selection
    function selectPrivacyOption(option) {
        const dropdown = document.getElementById('privacy-dropdown-content');
        const dropdownBtn = document.getElementById('privacy-dropdown-btn');

        // Update dropdown button text and icon
        const icon = option === 'public' ? 'fa-globe' : 'fa-user-friends';
        const text = option === 'public' ? 'Public' : 'People I follow';

        dropdownBtn.innerHTML = `
        <i class="fas ${icon}"></i>
        <span>${text}</span>
        <i class="fas fa-caret-down"></i>
            `;

        // Update active class
        const items = dropdown.querySelectorAll('.media-dropdown-item');
        items.forEach(item => {
            item.classList.remove('active');
            if (item.querySelector('span').textContent === text) {
                item.classList.add('active');
            }
        });

        // Close dropdown
        dropdown.classList.remove('active');
    }

    // Handle media file preview
    function previewMedia() {
        const fileInput = document.getElementById('fileInput');
        const mediaSlider = document.getElementById('mediaSlider');
        const mediaSlides = document.getElementById('mediaSlides');

        if (fileInput.files.length > 0) {
            mediaSlider.style.display = 'block';
            mediaSlides.innerHTML = '';

            Array.from(fileInput.files).forEach(file => {
                const reader = new FileReader();

                reader.onload = function(e) {
                    const slideDiv = document.createElement('div');
                    slideDiv.className = 'slide';

                    if (file.type.startsWith('image/')) {
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.alt = 'Uploaded image';
                        slideDiv.appendChild(img);
                    } else if (file.type.startsWith('video/')) {
                        const video = document.createElement('video');
                        video.src = e.target.result;
                        video.controls = true;
                        slideDiv.appendChild(video);
                    }

                    mediaSlides.appendChild(slideDiv);
                };

                reader.readAsDataURL(file);
            });
        } else {
            mediaSlider.style.display = 'none';
        }
    }

    // Handle slider navigation
    let slideIndex = 0;

    function moveSlide(direction) {
        const slides = document.querySelectorAll('.slide');

        if (slides.length === 0) return;

        slideIndex += direction;

        if (slideIndex >= slides.length) {
            slideIndex = 0;
        } else if (slideIndex < 0) {
            slideIndex = slides.length - 1;
        }

        const translateValue = -slideIndex * 100;
        document.getElementById('mediaSlides').style.transform = `translateX(${translateValue}%)`;
    }



    function fetchGenAIContent() {
        const loadingIndicator = document.getElementById('loadingIndicator');
        const textarea = document.getElementById('contentTextarea');

        // Check if textarea exists
        if (!textarea) {
            console.error("Textarea with id 'contentTextarea' not found.");
            return;
        }

        const textareaValue = textarea.value.trim();

        // Check if the textarea is empty
        if (!textareaValue) {
            alert("Please enter something in the textarea.");
            console.warn("Textarea is empty. AJAX request not sent.");
            return;
        }

        // Show loading indicator
        loadingIndicator.style.display = 'block';
        animateLoadingDots(); // Your existing animation function

        // Make the AJAX request using fetch API (modern replacement for $.ajax)
        fetch("genai/process_genai.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded",
                },
                body: new URLSearchParams({
                    working_headline: textareaValue,
                    avatar: "#post"
                })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                return response.text();
            })
            .then(data => {
                // Populate the textarea with the response
                textarea.value = data;
                adjustTextareaHeight(textarea); // Adjust height after setting the response
            })
            .catch(error => {
                console.error(`Error fetching GenAI content: ${error}`);
            })
            .finally(() => {
                // Hide the loading indicator when complete
                loadingIndicator.style.display = 'none';
                // Assuming you have a function to stop the animation
                stopAnimatingLoadingDots();
            });
    }

    // Animate loading dots for AI generation
    function animateLoadingDots() {
        const dots = document.getElementById('dots');
        let dotCount = 0;

        const interval = setInterval(() => {
            dotCount = (dotCount + 1) % 4;
            dots.textContent = '.'.repeat(dotCount);

            if (document.getElementById('loadingIndicator').style.display === 'none') {
                clearInterval(interval);
            }
        }, 300);
    }


    // Get media files from input
    function getMediaFiles() {
        const fileInput = document.getElementById('fileInput');
        return Array.from(fileInput.files);
    }

    // Show toast notification
    function showToast(message) {
        // Create toast notification if it doesn't exist
        let toast = document.querySelector('.toast-notification');

        if (!toast) {
            toast = document.createElement('div');
            toast.className = 'toast-notification';
            document.body.appendChild(toast);
        }

        toast.textContent = message;
        toast.classList.add('show');

        // Hide toast after 3 seconds
        setTimeout(() => {
            toast.classList.remove('show');
        }, 3000);
    }

    // Initialize event listeners
    document.addEventListener('DOMContentLoaded', function() {
        // Open modal
        document.querySelector('.fab-button')?.addEventListener('click', toggleModal);

        // Close modal
        document.querySelector('.media-close-modal').addEventListener('click', toggleModal);

        // Toggle privacy dropdown
        document.getElementById('privacy-dropdown-btn').addEventListener('click', toggleDropdown);

        // Handle privacy option selection
        document.querySelectorAll('.media-dropdown-item').forEach(item => {
            item.addEventListener('click', function() {
                const option = this.querySelector('span').textContent === 'Public' ? 'public' : 'private';
                selectPrivacyOption(option);
            });
        });

        // Handle media upload button click
        document.getElementById('media-upload-btn').addEventListener('click', function() {
            document.getElementById('fileInput').click();
        });

        // Close modal when clicking outside
        window.addEventListener('click', function(event) {
            const modal = document.getElementById('post-modal');
            if (event.target === modal) {
                toggleModal();
            }
        });

        // Close dropdown when clicking outside
        window.addEventListener('click', function(event) {
            const dropdown = document.getElementById('privacy-dropdown-content');
            const dropdownBtn = document.getElementById('privacy-dropdown-btn');

            if (event.target !== dropdownBtn && !dropdownBtn.contains(event.target)) {
                dropdown.classList.remove('active');
            }
        });
    });
</script>

</body>

</html>