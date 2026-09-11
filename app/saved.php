<?php
require_once 'inc/php/db_config.php';
require_once 'inc/php/validate.logged.php';
require_once 'inc/php/function.php';

function show_stream_content()
{
    global $readerdb, $gUserId; // Ensure $gUserId is defined and accessible

    // SQL query to get posts ordered by the most recent
    $sql = "SELECT ss.post_id AS id, rs.userId, rs.chat, rs.postedOn, rs.editedOn, rs.mediaPath, rs.metadata 
        FROM reader_stream rs 
        INNER JOIN stream_saved ss ON rs.id = ss.post_id 
        WHERE rs.deleteFlag = 0 
          AND ss.user_id = ? 
        ORDER BY rs.postedOn DESC";

    // Prepare the statement
    $stmt = $readerdb->prepare($sql);

    // Bind the parameters
    $stmt->bind_param("i", $gUserId); // Bind only the userId parameter

    // Execute the query
    $stmt->execute();

    // Get the result of the prepared statement
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            generate_stream_card($row['id'], $row['userId'], $row['chat'], $row['postedOn'], $row['editedOn'], $row['mediaPath'], $row['metadata']);
        }
    } else {
        // Optionally handle the case when no posts are found
        echo "No posts available.";
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookmarks</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.0/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="grfx/img/logo.ico">

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/js/all.min.js"></script> -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="inc/css/social.css">
    <script src="inc/js/new_social_script.js?v=20260528"></script>

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
                    if (result.success) {
                        alert('Post added to channel');
                        hideChannelCards(postId);
                    } else {
                        alert('Failed to add to channel: ' + result.message);
                    }
                },
                error: function(xhr, status, error) {
                    alert('Error: ' + error);
                }
            });
        }

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
        function uploadPost() {
            // Collect the data from the inputs
            var content = $("#contentTextarea").val(); // Textarea content
            var fileInput = $("#fileInput")[0].files[0]; // The selected file
            var hiddenTitle = $("#hiddenTitle").val();
            var hiddenDesc = $("#hiddenDesc").val();
            var hiddenUrl = $("#hiddenUrl").val();
            var hiddenImage = $("#hiddenImage").val();
            var hiddenDomain = $("#hiddenDomain").val();
            var hiddenYTLink = $("#hiddenYTLink").val();

            // Create a FormData object to send data (use FormData if sending files)
            var formData = new FormData();

            // Conditionally append fields if they have values
            if (content) formData.append("content", content);
            if (fileInput) formData.append("media", fileInput); // Attach the file if selected
            if (hiddenTitle) formData.append("hiddenTitle", hiddenTitle);
            if (hiddenDesc) formData.append("hiddenDesc", hiddenDesc);
            if (hiddenUrl) formData.append("hiddenUrl", hiddenUrl);
            if (hiddenImage) formData.append("hiddenImage", hiddenImage);
            if (hiddenDomain) formData.append("hiddenDomain", hiddenDomain);
            if (hiddenYTLink) formData.append("hiddenYTLink", hiddenYTLink);

            // Show loading spinner
            $("#loadingIcon").show();

            // Send the data to PHP via AJAX
            $.ajax({
                url: 'process_data.php', // The PHP page where you want to process the data
                type: 'POST',
                data: formData,
                processData: false, // Important for sending files
                contentType: false, // Important for sending files
                success: function(response) {
                    // Hide loading spinner after response
                    $("#loadingIcon").hide();

                    if (response.status === 'success') {
                        alert("Posted successfully!");
                        myModal.hide(); // Use myModal here to hide the modal after successful post
                        window.location.reload();
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
                    <h1 style="padding: 10px;">My Bookmarks</h1>
                    <? show_stream_content() ?>
                </div>
            </div>
            <?php include 'inc/php/right_news_module.php' ?>

        </div>
        <? include 'inc/php/footer.php' ?>

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