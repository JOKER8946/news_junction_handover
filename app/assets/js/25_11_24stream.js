function toggleReadMore(postId) {
    var $truncatedContent = $('#postContent_' + postId);
    var $fullContent = $('#fullContent_' + postId);
    var $button = $('[data-id="' + postId + '"]').first();

    if ($truncatedContent.css('display') === 'none') {
        // Show truncated content, hide full content, change button text
        $truncatedContent.show();
        $fullContent.hide();
        $button.text('Read More');
    } else {
        // Show full content, hide truncated content, change button text
        $truncatedContent.hide();
        $fullContent.show();
        $button.text('Read Less');
    }
}

function toggleFollow(button, userId, targetUserId) {
    const isFollowing = $(button).text().trim() === 'Following'; // Check if currently following
    const requestType = isFollowing ? 'unfollow' : 'follow';

    // Make the AJAX request to toggle follow/unfollow
    $.ajax({
        url: 'follow_action.php', // Adjust the URL if necessary
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({
            request: requestType,
            followerId: userId,
            followingId: targetUserId
        }),
        success: function (response) {
            console.log(response); // Log the full response to debug

            if (response.status === "success") {
                // Toggle button text based on follow/unfollow status
                $(button).text(requestType === 'follow' ? 'Following' : 'Follow');
            } else {
                console.error("Error message:", response.message || "Unknown error occurred");
            }
        },
        error: function (jqXHR, textStatus, errorThrown) {
            console.error("Error:", textStatus, errorThrown);
        }
    });
}

// Function to handle delete
function deletePost(postId) {
    if (confirm("Are you sure you want to delete this post?")) {
        $.ajax({
            url: 'edit_post.php',
            type: 'POST',
            data: {
                action: 'delete',
                post_id: postId
            },
            success: function (response) {
                const result = JSON.parse(response);
                if (result.status === "success") {
                    alert(result.message);
                    location.reload();
                } else {
                    alert(result.message);
                }
            },
            error: function () {
                alert("An error occurred while deleting the post.");
            }
        });
    }
}

// Function to edit a post
function editPost(postId, currentContent) {
    // Set the content in the modal's textarea
    const $modalTextarea = $('#modalContentTextarea');
    const $saveButton = $('#saveModalEditButton');

    // Set the content and postId in the textarea and save button data attribute
    $modalTextarea.val(currentContent);
    $saveButton.data('postId', postId);

    // Set cursor to the end of the text
    $modalTextarea.focus();
    $modalTextarea[0].setSelectionRange($modalTextarea.val().length, $modalTextarea.val().length);

    // Open the modal
    const editPostModal = new bootstrap.Modal($('#editPostModal')[0]);
    editPostModal.show();
}

// Function to save edited content from modal
function saveEditedContent() {
    const saveButton = $('#saveModalEditButton');
    const postId = saveButton.data('postId');
    const newContent = $('#modalContentTextarea').val();

    // AJAX request to save the edited content
    $.ajax({
        url: 'edit_post.php',
        type: 'POST',
        data: {
            action: 'edit',
            post_id: postId,
            content: newContent
        },
        success: function (response) {
            const result = JSON.parse(response);
            if (result.status === "success") {
                alert(result.message);
                location.reload(); // Refresh to show the updated post
            } else {
                alert(result.message);
            }
        },
        error: function () {
            alert("An error occurred while saving the post.");
        }
    });
}

// Function to close all dropdown menus
function closeAllDropcardMenus() {
    $('.dropcardMenu').each(function () {
        $(this).hide();
    });
}

// Function to preview video and image Media
// function previewMedia() {
//     var $fileInput = $('#fileInput');
//     var $mediaPreview = $('#mediaPreview');
//     var file = $fileInput[0].files[0];

//     // Clear previous previews
//     $mediaPreview.empty();

//     if (file) {
//         var fileReader = new FileReader();

//         // For image files
//         if (file.type.startsWith('image')) {
//             fileReader.onload = function(e) {
//                 var $img = $('<img>').attr('src', e.target.result);
//                 // img.style.maxWidth = '100%'; 
//                 // img.style.maxHeight = '300px'; // Set max height for the preview
//                 $mediaPreview.append($img);
//             };
//         }

//         // For video files
//         else if (file.type.startsWith('video')) {
//             var $video = $('<video>').attr('controls', true).css({
//                 maxWidth: '100%',
//                 maxHeight: '70vh' // Set max height for the preview
//             });
//             $mediaPreview.append($video);

//             fileReader.onload = function(e) {
//                 $video.attr('src', e.target.result);
//                 $video[0].load();
//                 $video[0].play();
//             };
//         }

//         // Start reading the file
//         fileReader.readAsDataURL(file);
//     }
// }
// function previewMedia() {
//     console.log("PreviewMedia Workinggggggg");
//     var $fileInput = $('#fileInput');
//     var $mediaPreview = $('#mediaPreview');
//     var file = $fileInput[0].files[0];

//     // Clear previous previews
//     $mediaPreview.empty();

//     if (file) {
//         var fileReader = new FileReader();

//         // For image files
//         if (file.type.startsWith('image')) {
//             fileReader.onload = function (e) {
//                 var $img = $('<img>').attr('src', e.target.result);
//                 console.log($img);
//                 // img.style.maxWidth = '100%'; 
//                 // img.style.maxHeight = '300px'; // Set max height for the preview
//                 $mediaPreview.append($img);
                
//             };
            
//         }

//         // For video files
//         else if (file.type.startsWith('video')) {
//             var $video = $('<video>').attr('controls', true).css({
//                 maxWidth: '100%',
//                 maxHeight: '70vh' // Set max height for the preview
//             });

//             // Create a hidden video element to extract the first frame
//             var videoElement = document.createElement('video');
//             var videoUrl = URL.createObjectURL(file);
//             videoElement.src = videoUrl;

//             // Create a canvas to draw the first frame
//             var $thumbnailCanvas = $('<canvas>')[0];
//             var thumbnailContext = $thumbnailCanvas.getContext('2d');

//             // Wait for the video to load, then extract the first frame
//             $(videoElement).on('loadeddata', function () {
//                 // Ensure the video is loaded and ready
//                 videoElement.currentTime = 0;  // Set video to the first frame
//             });

//             // Once the video seeks to the first frame, capture the thumbnail
//             $(videoElement).on('seeked', function () {
//                 // Set canvas size to match video dimensions
//                 $thumbnailCanvas.width = videoElement.videoWidth;
//                 $thumbnailCanvas.height = videoElement.videoHeight;

//                 // Draw the current frame (first frame) onto the canvas
//                 thumbnailContext.drawImage(videoElement, 0, 0, videoElement.videoWidth, videoElement.videoHeight);

//                 // Convert the canvas to a data URL and set it as the video poster
//                 var dataUrl = $thumbnailCanvas.toDataURL();
//                 $video.attr('poster', dataUrl);  // Set the first frame as the thumbnail
//             });

//             // Append the video element to the preview
//             $mediaPreview.append($video);

//             // Read the video file and load it into the video element
//             fileReader.onload = function (e) {
//                 $video.attr('src', e.target.result);
//                 $video[0].load();
//                 $video[0].play();
//             };

//             // Start reading the file
//             fileReader.readAsDataURL(file);
//             console.log("Video Previewing");
//         }
//     }
//     console.log("PreviewMedia Workinggggggg 2");
// }

function previewMedia() {
    console.log("PreviewMedia Workinggggggg");
    var $fileInput = $('#fileInput');
    var $mediaPreview = $('#mediaPreview');
    var file = $fileInput[0].files[0];

    // Clear previous previews
    $mediaPreview.empty();

    if (file) {
        var fileReader = new FileReader();

        // For image files
        if (file.type.startsWith('image')) {
            fileReader.onload = function (e) {
                var $img = $('<img>').attr('src', e.target.result).css({
                    maxWidth: '100%', // Optional: to ensure the image scales properly
                    maxHeight: '300px' // Optional: to limit the height
                });
                console.log($img);
                $mediaPreview.append($img);
            };
            fileReader.readAsDataURL(file); // Read the file as a Data URL to preview
        }

        // For video files
        else if (file.type.startsWith('video')) {
            var $video = $('<video>').attr('controls', true).css({
                maxWidth: '100%',
                maxHeight: '70vh' // Set max height for the preview
            });

            // Create a hidden video element to extract the first frame
            var videoElement = document.createElement('video');
            var videoUrl = URL.createObjectURL(file);
            videoElement.src = videoUrl;

            // Create a canvas to draw the first frame
            var $thumbnailCanvas = $('<canvas>')[0];
            var thumbnailContext = $thumbnailCanvas.getContext('2d');

            // Wait for the video to load, then extract the first frame
            $(videoElement).on('loadeddata', function () {
                // Ensure the video is loaded and ready
                videoElement.currentTime = 0;  // Set video to the first frame
            });

            // Once the video seeks to the first frame, capture the thumbnail
            $(videoElement).on('seeked', function () {
                // Set canvas size to match video dimensions
                $thumbnailCanvas.width = videoElement.videoWidth;
                $thumbnailCanvas.height = videoElement.videoHeight;

                // Draw the current frame (first frame) onto the canvas
                thumbnailContext.drawImage(videoElement, 0, 0, videoElement.videoWidth, videoElement.videoHeight);

                // Convert the canvas to a data URL and set it as the video poster
                var dataUrl = $thumbnailCanvas.toDataURL();
                $video.attr('poster', dataUrl);  // Set the first frame as the thumbnail
            });

            // Append the video element to the preview
            $mediaPreview.append($video);

            // Read the video file and load it into the video element
            fileReader.onload = function (e) {
                $video.attr('src', e.target.result);
                $video[0].load();
                $video[0].play();
            };

            // Start reading the file
            fileReader.readAsDataURL(file);
            console.log("Video Previewing");
        }
    }
    console.log("PreviewMedia Workinggggggg 2");
}



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
        success: function (response) {
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
        error: function (jqXHR, textStatus, errorThrown) {
            console.error("Error:", textStatus, errorThrown);
        }
    });
}

// Function to toggle the dropcard menu visibility
function toggleDropcardMenu(postId) {
    var $menu = $('#dropcardMenu_' + postId);
    var isVisible = $menu.css('display') === 'block';

    // Close any open dropdown menus before toggling the current one
    closeAllDropcardMenus();

    // Toggle display for the clicked menu
    $menu.css('display', isVisible ? 'none' : 'block');
}

function fetchGenAIContent() {
    const $textarea = $('#contentTextarea'); // Select the textarea element
    const $loadingIndicator = $('#loadingIndicator'); // Select the loading indicator

    if ($textarea.length === 0) {
        console.error("Textarea with id 'contentTextarea' not found.");
        return;
    }

    const textareaValue = $textarea.val().trim(); // Get the current value of the textarea and trim whitespace

    // Check if the textarea is empty
    if (!textareaValue) {
        alert("Please enter something in the textarea.");
        console.warn("Textarea is empty. AJAX request not sent.");
        return;
    }

    // Show the loading indicator
    $loadingIndicator.show();
    startBlinkingDots();

    $.ajax({
        url: 'genai/process_genai.php', // Replace with the actual endpoint
        method: 'POST',
        data: {
            working_headline: textareaValue,
            avatar: "#post"
        },
        success: function (response) {
            // Populate the textarea with the response
            $textarea.val(response);
            adjustTextareaHeight($textarea[0]); // Adjust height after setting the response
        },
        error: function (jqXHR, textStatus, errorThrown) {
            console.error(`Error fetching GenAI content: ${textStatus} - ${errorThrown}`);
        },
        complete: function () {
            // Hide the loading indicator when the AJAX call is complete
            $loadingIndicator.hide();
            stopBlinkingDots();
        }
    });
}

function adjustTextareaHeight(textarea) {
    const minHeight = 50; // Minimum height in pixels
    const maxHeight = 200; // Maximum height in pixels

    // Reset the height to auto so it shrinks if the content is deleted
    textarea.style.height = 'auto';

    // Calculate the new height
    const newHeight = textarea.scrollHeight;

    // Apply the appropriate height
    if (newHeight > maxHeight) {
        textarea.style.height = `${maxHeight}px`;
        textarea.style.overflowY = 'scroll'; // Add scrollbar if max height is reached
    } else if (newHeight < minHeight) {
        textarea.style.height = `${minHeight}px`; // Ensure the height doesn't go below 50px
        textarea.style.overflowY = 'hidden';
    } else {
        textarea.style.height = `${newHeight}px`;
        textarea.style.overflowY = 'hidden'; // Remove scrollbar if below max height
    }
}

// Blinking dots functionality
function startBlinkingDots() {
    const dots = document.getElementById('dots');
    let dotCount = 0;
    dotInterval = setInterval(() => {
        dotCount = (dotCount + 1) % 4; // Cycle through 0, 1, 2, 3
        dots.textContent = '.'.repeat(dotCount);
    }, 500); // Change dots every 500ms
}

function stopBlinkingDots() {
    clearInterval(dotInterval);
    document.getElementById('dots').textContent = '...'; // Reset to three dots
}