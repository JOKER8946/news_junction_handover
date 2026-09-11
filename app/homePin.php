<?php
// Database connection
require_once './assets/php/db_connect.php';
require_once './assets/php/config.php';
require_once './assets/php/db_config.php';
require_once './assets/php/validate.logged.php';
require_once './assets/php/function.php';

function checkPincode()
{
    global $creamdb, $gUserId;

    $sql = "SELECT pincode FROM user WHERE id = ? AND pincode IS NULL";
    $stmt = $creamdb->prepare($sql);
    $stmt->bind_param("i", $gUserId);
    $stmt->execute();
    $stmt->store_result();

    // If no rows are found, it means pincode is NULL or doesn't exist
    if ($stmt->num_rows == 0) {
        return true;
    } else {
        // Return false if pincode is found
        return false;
    }
}


// Handle the AJAX request for data
if (isset($_GET['page'])) {
    $page = (int)$_GET['page'];  // Get page number
    $size = (int)$_GET['size'];  // Get number of items per page
    $offset = ($page - 1) * $size;

    // Query to get the latest records, ordered by postedOn or ID in descending order
    // $sql = "SELECT * FROM reader_stream WHERE deleteFlag = 0 AND referenceId IS NULL ORDER BY postedOn DESC LIMIT $size OFFSET $offset";

    $sql = "SELECT rs.* FROM reader.reader_stream rs
    JOIN cream.user u ON u.id = rs.userId 
    WHERE (rs.visibility = 'public' 
        OR rs.userId IN (
            SELECT following_id FROM reader.reader_stream_follow WHERE follower_id = $gUserId
            UNION SELECT $gUserId
        )
    )  
    AND rs.deleteFlag = 0
    AND rs.referenceId IS NULL
    AND rs.id NOT IN (SELECT streamId FROM report_stream WHERE userId = $gUserId)
    AND rs.userId NOT IN (SELECT blockedUserId FROM cream.block_acc WHERE userId = $gUserId)
    AND rs.userId NOT IN (SELECT userId FROM cream.block_acc WHERE blockedUserId = $gUserId)
    AND u.pincode IS NOT NULL
    ORDER BY rs.postedOn DESC
    LIMIT $size OFFSET $offset;";

    $result = $readerdb->query($sql);

    // Initialize the data container
    $htmlOutput = '';

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $htmlOutput .= captureStream($row['id'], $row['userId'], $row['chat'], $row['postedOn'], $row['editedOn'], $row['mediaPath'],  $row['metadata']);
        }
    }
    // Check if the current batch is the last one (i.e., less than the requested size)
    $last = (count(explode('</div>', $htmlOutput)) - 1 < $size) ? true : false;

    // Return the HTML and the last page status as JSON response
    echo json_encode(['html' => $htmlOutput, 'last' => $last]);

    // Close connection
    $readerdb->close();
    exit;
}

function get_ip_loc()
{
    $ip = getenv('HTTP_CLIENT_IP') ?: getenv('HTTP_X_FORWARDED_FOR') ?: getenv('HTTP_X_FORWARDED') ?: getenv('HTTP_FORWARDED_FOR') ?: getenv('HTTP_FORWARDED') ?: getenv('REMOTE_ADDR');
    $response = unserialize(file_get_contents('http://www.geoplugin.net/php.gp?ip=' . $ip));
    if ($response === false) {
        $visitCity = '';
        $visitCountry = '';
    } else {
        $visitCity = $response['geoplugin_city'];
        $visitCountry = $response['geoplugin_countryName'];
    }
    return array(
        "ip" => $ip,
        "city" => $visitCity,
        "country" => $visitCountry
    );
}

if (isset($_POST['streamId'])) {
    $iploc = get_ip_loc();

    // SQL query using INSERT WHERE NOT EXISTS to prevent duplicate insertion
    $sql = "INSERT INTO stream_analytics (streamId, userId, ip, city, country)
        SELECT ?, ?, ?, ?, ? FROM DUAL
        WHERE NOT EXISTS (
            SELECT 1 FROM stream_analytics WHERE streamId = ? AND userId = ?
        )";

    // Prepare the statement
    $stmt = $readerdb->prepare($sql);

    // Bind the parameters
    $stmt->bind_param("iisssii", $_POST['streamId'], $gUserId, $iploc['ip'], $iploc['city'], $iploc['country'], $_POST['streamId'], $gUserId);

    // Execute the query
    $result = $stmt->execute();

    if ($result) {
        // Check if the insertion was successful
        if ($stmt->affected_rows > 0) {
            echo json_encode(['status' => "success", "message" => "Data inserted successfully"]);
        } else {
            echo json_encode(['status' => "success", "message" => "Combination of streamId and userId already exists."]);
        }
    } else {
        echo json_encode(['status' => "error", "message" => $stmt->error]);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>News Junction</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.0/font/bootstrap-icons.min.css" rel="stylesheet">

    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/stream.css">

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
            /* padding: 10px; */
            border: 0.2px solid #ccc;
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

        .postEditDelete {
            display: flex;
            flex-direction: column;
            gap: 20px;
            width: fit-content !important;
        }

        @media screen and (max-width:884px) {
            .navaigation_main {
                display: none;
            }

        }
    </style>

    <style>
        #modalContent img {
            display: block;
            margin: 0 auto;
        }

        #modalContent video {
            display: block;
            margin: 0 auto;
        }
    </style>
    <style>
        /* Slider container */
        .slider-container {
            position: relative;
            width: 100%;
            max-width: 600px;
            /* Adjust width as needed */
            margin: 0 auto;
            /* Center the slider */
            overflow: hidden;
        }

        /* The actual slider slides */
        .slider-slides {
            display: flex;
            transition: transform 0.3s ease;
        }

        /* Each slide (media item) */
        .slider-slides img,
        .slider-slides video {
            width: 100%;
            object-fit: contain;
            /* Ensure images/videos scale correctly */
        }

        /* Navigation buttons */
        .prev,
        .next {
            position: absolute;
            top: 50%;
            z-index: 10;
            font-size: 18px;
            color: white;
            background-color: rgba(0, 0, 0, 0.5);
            border: none;
            padding: 16px;
            cursor: pointer;
            transform: translateY(-50%);
        }

        .prev {
            left: 0;
        }

        .next {
            right: 0;
        }

        /* Hover effect on the navigation buttons */
        .prev:hover,
        .next:hover {
            background-color: rgba(0, 0, 0, 0.8);
        }

        /* Style for the loading spinner */
        .loading-spinner {
            z-index: 100;
            display: inline-block;
            position: fixed;
            bottom: 50px;
            width: 50px;
            height: 50px;
            text-align: center;
            font-size: 18px;
            color: #db5919;
            font-weight: bold;
        }

        .loading-spinner::after {
            content: "";
            position: absolute;
            top: 50%;
            left: 50%;
            width: 40px;
            height: 40px;
            margin-top: -20px;
            margin-left: -20px;
            border: 5px solid #ccc;
            border-top: 5px solid #3498db;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        /* Animation for the spinning effect */
        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        .mediawithvisibility {
            display: flex;
            justify-content: center;
            gap: 4px;
        }

        .model {
            width: 700px !important;
        }
    </style>
    <style>
        /* Media container */
        .media-container {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            /* Default 3 columns */
            gap: 10px;
            width: 100%;
        }

        /* Stream media styling */
        .stream-media {
            position: relative;
        }

        .stream-media img,
        .stream-media video {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
        }

        /* Specific styles for more block */
        .stream-media.more {
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: #f0f0f0;
            border: 1px solid #ddd;
            color: #777;
            font-size: 16px;
        }

        .stream-media.more .more-count {
            font-weight: bold;
        }

        /* Custom grid layouts based on the number of items */
        <?php if ($mediaCount == 2) { ?>.media-container {
            grid-template-columns: repeat(2, 1fr);
            /* 2 items in half-half layout */
        }

        <?php } elseif ($mediaCount == 3) { ?>.media-container {
            grid-template-columns: 1fr 2fr;
            /* 1:2 ratio layout */
        }

        <?php } elseif ($mediaCount == 4) { ?>.media-container {
            grid-template-columns: repeat(4, 1fr);
            /* 4 items in 1/4 layout */
        }

        <?php } elseif ($mediaCount == 1) { ?>.media-container {
            grid-template-columns: 1fr;
            /* Single item takes full width */
        }

        <?php } ?>
    </style>
    <style>
        .form-group {
            margin-bottom: 20px;
            font-family: Arial, sans-serif;
        }

        .form-group label {
            font-size: 14px;
            font-weight: 300;
            color: #333;
            margin-bottom: 4px;
            display: block;
        }

        .form-control {
            width: 100%;
            padding: 4px 10px;
            border-radius: 4px;
            border: 1px solid #ccc;
            font-size: 14px;
            background-color: #f9f9f9;
            transition: border-color 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: #db5919;
            box-shadow: none;
        }

        .form-control option {
            padding: 8px;
            font-size: 14px;
            background-color: #fff;
            color: #333;
        }
    </style>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Bootstrap JS and Bootstrap Icons -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script src="assets/js/stream.js"></script>
    <script>
        const userId = <?= $gUserId ?>;
        let tempUrl = '';
        let letUrl = true;
        let dotInterval;
        var myModal;
        let page = 1; // Start with the first page
        const pageSize = 20; // Number of items per page
        let isLoading = false; // Flag to prevent multiple AJAX calls at once
        let isLastPage = false; // Flag to check if the last page is reached
        function shareNow(postId) {
            // console.log(postId);
            var link = "https://newsjunction.net/streamPush.php?id=" + postId;
            copyToClipboard(link);
        }

        function copyToClipboard(note) {
            // Append the custom text to the note
            var textToCopy = note;

            // Try using the Clipboard API first
            if (navigator.clipboard) {
                navigator.clipboard.writeText(textToCopy).then(function() {
                    alert('Note copied to clipboard: ' + textToCopy);
                }).catch(function(error) {
                    console.error('Clipboard API error: ', error);
                    fallbackCopy(textToCopy);
                });
            } else {
                console.error('Clipboard API is not available');
                fallbackCopy(textToCopy);
            }

            // Fallback method using a temporary textarea element
            function fallbackCopy(textToCopy) {
                // Create a temporary textarea element using jQuery
                var $tempTextArea = $('<textarea>');

                // Set the value of the textarea to the text we want to copy
                $tempTextArea.val(textToCopy).appendTo('body');

                // Focus the textarea and select the content using jQuery
                $tempTextArea.focus().select();
                $tempTextArea[0].setSelectionRange(0, textToCopy.length); // For mobile devices

                // Try executing the copy command
                try {
                    var successful = document.execCommand('copy');
                    if (successful) {
                        alert('Note copied to clipboard: ' + textToCopy);
                    } else {
                        alert('Failed to copy note.');
                    }
                } catch (err) {
                    console.error('Error copying text: ', err);
                    alert('Failed to copy note.');
                } finally {
                    // Remove the temporary textarea from the document
                    $tempTextArea.remove();
                }
            }

        }

        $(document).on('click', '.shareNow', function() {
            shareNow($(this).data('id'));
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
        function uploadPost() {
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

        function generateVideoThumbnail($ele) {
            try {
                // Fetch the video URL from the source element inside the .post div
                var videoUrl = $ele.find('video source').attr('src');

                if (!videoUrl) {
                    return false; // If no video URL, return false early
                }

                var $video = $('<video>').attr('controls', true).css({
                    maxWidth: '100%',
                    maxHeight: '70vh' // Set max height for the preview
                });

                // Create a hidden video element to extract the first frame
                var videoElement = document.createElement('video');
                videoElement.src = videoUrl;

                // Create a canvas to draw the first frame
                var $thumbnailCanvas = $('<canvas>')[0];
                var thumbnailContext = $thumbnailCanvas.getContext('2d');

                // Wait for the video to load and then set it to the first frame
                $(videoElement).on('loadeddata', function() {
                    videoElement.currentTime = 0; // Set video to the first frame
                });

                // Once the video seeks to the first frame, capture the thumbnail
                $(videoElement).on('seeked', function() {
                    // Set canvas size to match video dimensions
                    $thumbnailCanvas.width = videoElement.videoWidth;
                    $thumbnailCanvas.height = videoElement.videoHeight;

                    // Draw the current frame (first frame) onto the canvas
                    thumbnailContext.drawImage(videoElement, 0, 0, videoElement.videoWidth, videoElement.videoHeight);

                    // Convert the canvas to a data URL and set it as the video poster
                    var dataUrl = $thumbnailCanvas.toDataURL();
                    $video.attr('poster', dataUrl); // Set the first frame as the thumbnail
                });

                // Keep the existing content of the div intact
                var existingVideo = $ele.find('video');

                // Replace the video inside the .post div with the new one that has a thumbnail
                existingVideo.replaceWith($video);

                // Set the video source and load it
                $video.attr('src', videoUrl);
                $video[0].load();
                // $video[0].play(); // Optionally, you can play the video if needed

                return true; // Return true if the function completes successfully
            } catch (error) {
                console.error("Error generating video thumbnail:", error);
                return false; // Return false if an error occurs
            }
        }

        function handleLazyLoad() {
            $('.post-content').each(function() {
                if ($(this).data('thumbnail') === false && $(this).find('video').length > 0) {
                    generateVideoThumbnail($(this));
                    $(this).data('thumbnail', true);
                }
            });
        }

        function isElementInView($el) {
            var windowTop = $(window).scrollTop();
            var windowBottom = windowTop + $(window).height();
            var elementTop = $el.offset().top;
            var elementBottom = elementTop + $el.height();

            // Check if element is in the viewport
            return elementBottom >= windowTop && elementTop <= windowBottom;
        }

        function storeAnalytics($streamId) {
            $.ajax({
                url: '', // The same file since we handle both front-end and back-end here
                type: 'POST',
                data: {
                    streamId: $streamId
                },
                success: function(response) {
                    const data = JSON.parse(response);
                    if (data.status == "error") {
                        alert('Please Check the Internet Connection.');
                    }
                },
                error: function() {
                    alert('Please Check the Internet Connection.');
                }
            });
        }

        function streamAnalytics() {
            $('.post').each(function() {
                var $ele = $(this); // Current .post element
                if (isElementInView($ele) && !$ele.hasClass('logged')) {
                    storeAnalytics($ele.attr('data-id'));
                    $ele.addClass('logged'); // Mark it as logged
                }
            });
        }

        // Function to play or pause the video based on its visibility
        function handleVideoVisibility(videoElement, $postElement) {
            if (isElementInView($postElement)) {
                // Video is in the viewport, play it if it's not already playing
                // if (videoElement.paused) {
                //     videoElement.play().catch(function(error) {});
                // }
            } else {
                // Video is out of the viewport, pause it if it's playing
                if (!videoElement.paused) {
                    videoElement.pause();
                }
            }
        }

        // Function to check all videos within .post elements
        function checkVideoVisibility() {
            $('.post').each(function() {
                var video = $(this).find('video')[0]; // Get the video element
                if (video) {
                    handleVideoVisibility(video, $(this)); // Call the function to handle play/pause
                }
            });
        }

        // Function to load data from the server
        function loadData() {
            if (isLoading || isLastPage) return; // If data is already being loaded or the last page is reached, return early

            isLoading = true; // Set flag to true to prevent further calls

            $('.loading-spinner').show(); // Show the loading indicator

            $.ajax({
                url: '', // The same file since we handle both front-end and back-end here
                type: 'GET',
                data: {
                    page: page,
                    size: pageSize
                },
                success: function(response) {
                    const data = JSON.parse(response);
                    const html = data.html;

                    if (html.length > 0) {
                        // Append the HTML content directly to the container (this loads latest data below)
                        // $('#data-container').append(html);
                        $('.first_right_container').append(html);

                        // Check if the last page is reached
                        isLastPage = data.last;

                        page++; // Increment the page number for the next request
                    }

                    $('.loading-spinner').hide(); // Hide the loading indicator
                    isLoading = false; // Reset the flag after data is loaded
                },
                error: function() {
                    $('.loading-spinner').hide();
                    isLoading = false; // Reset the flag in case of error
                    alert('Error loading data');
                }
            });
        }

        function blockAccount(userId) {
            // Ask the user for confirmation before proceeding
            if (confirm("Are you sure you want to block this account?")) {
                $.ajax({
                    url: 'assets/php/blockAccount.php', // The PHP script to handle the blocking
                    method: 'POST',
                    data: {
                        act: 'block',
                        userId: userId
                    },
                    success: function(response) {
                        // Check if the block operation was successful
                        if (response.status === 'success') {
                            alert("Blocked the account");
                            window.location.reload(); // Reload the page to reflect the changes
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

        // Function to handle the report submission with AJAX
        function report_stream(userId, streamId, reason) {
            // Prepare the data for the server using FormData
            const formData = new FormData();
            formData.append('userId', userId);
            formData.append('streamId', streamId);
            formData.append('reason', reason);

            // Perform the AJAX request with jQuery
            $.ajax({
                url: '/assets/php/report_stream.php',
                type: 'POST',
                data: formData,
                dataType: 'json',
                processData: false,
                contentType: false,
                success: function(data) {
                    console.log(data);
                    if (data.status === 'success') {
                        alert(data.message);
                        window.location.reload(); // Reload the page after successful submission
                    } else {
                        alert('Error: ' + data.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error:', error);
                    alert('An error occurred while trying to report the post. Please try again later.');
                }
            });
        }

        $(function() {
            // Initialize the modal when the document is ready
            reportModal = new bootstrap.Modal($("#reportModal")[0]);

            // Initialize the modal when the document is ready
            myModal = new bootstrap.Modal($("#uploadModal")[0]);

            // Event listener for showing the modal (for example, when the plus button is clicked)
            $("#plusButton").on("click", function() {
                myModal.show(); // Show the modal
            });
        });

        $(document).ready(function() {
            // Initialize the height on page load
            var $textarea = $('#contentTextarea');
            if ($textarea.length) {
                adjustTextareaHeight($textarea[0]);
            }

            $(document).on('click', '.followButton', function() {
                const targetUserId = $(this).data('id'); // ID of the user to follow/unfollow
                toggleFollow(this, userId, targetUserId); // Pass `this` (the button element) as the first parameter
            });

            $('#saveModalEditButton').on('click', function() {
                saveEditedContent();
            });

            $(document).on('click', '.saveButton', function(e) {
                var id = $(this).data('id');
                toggleSave(this, id);
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

            // $(window).on('scroll', checkVideoVisibility);

            $(window).on('scroll', function() {
                streamAnalytics();
                checkVideoVisibility();

                // Check if the user has scrolled near the bottom of the page
                if ($(window).scrollTop() + $(window).height() >= $(document).height() - 100) {
                    loadData(); // Load more data when scrolled near the bottom
                }
            });

            $('.post-content').attr('tabindex', '0');

            $(document).on('mouseenter', '.post-content', function() {
                if ($(this).data('thumbnail') === false && $(this).find('video').length > 0) {
                    generateVideoThumbnail($(this));
                    $(this).data('thumbnail', true);
                }
            });

            $(document).on('ajaxComplete', function() {
                handleLazyLoad();
                // Also check on page load in case elements are already in view
                $(window).trigger('scroll');
            });

            $("#reportReasonSelect").on("change", function() {
                const otherReasonContainer = $("#otherReasonContainer");
                if ($(this).val() === "Others") {
                    otherReasonContainer.show();
                } else {
                    otherReasonContainer.hide();
                }
            });

            // Initial data load
            loadData();
        });

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

        $(document).on('click', '.reportThisPost', function() {
            var reportId = $(this).data('id');
            reportModal.show();
            $('#reportStreamId').val(reportId);

        });

        $(document).on('click', '.blockThisPost', function() {
            var blockId = $(this).data('userid');
            // reportModal.show();
            // $('#reportStreamId').val(reportId);
            blockAccount(blockId);

        });

        // jQuery code to handle the 'Submit' button click
        $(document).on('click', '#submitReportButton', function() {
            // Get the selected reason from the dropdown
            const reason = $('#reportReasonSelect').val();
            const streamId = $('#reportStreamId').val();
            const otherReason = $('#otherReasonTextarea').val().trim(); // Get the value from the textarea

            // console.log(reason);
            // console.log(streamId);
            // console.log(otherReason);

            // Ensure a reason is selected
            if (!reason) {
                alert('Please select a reason.');
                return;
            }

            // If "Others" is selected, ensure the textarea is filled
            let finalReason = reason;
            if (reason === 'Others') {
                if (!otherReason) {
                    alert('Please provide a reason.');
                    return;
                }
                finalReason = otherReason; // Use the value from the textarea as the reason
            }

            // Call the report_stream function with the selected data
            report_stream(userId, streamId, finalReason);
        });

        // Attach the `oninput` event for dynamic resizing
        $(document).on('input', '#contentTextarea', function() {
            adjustTextareaHeight(this);
        });
    </script>

    <!-- <script>
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
        $(document).on('click', '.repostButton', function() {
            console.log("Working")
            var postId = $(this).data('id'); // Get the post ID

            $.ajax({
                url: 'repost.php', // The PHP script to handle the repost
                method: 'POST',
                data: {
                    id: postId
                },
                success: function(response) {
                    // Update the repost count after a successful repost
                    $('.repostCount[data-id="' + postId + '"]').text(response.repost_count);
                },
                error: function() {
                    alert('There was an error while reposting.');
                }
            });
        });
    </script> -->

</head>

<body>
    <? include 'assets/php/navbar.php' ?>
    <div class="container" style="margin-top: 6px; padding:0;">
        <!-- Left Section -->
        <div class="streamLeftbar">
            <div class="first_left_container" style="display: flex; flex-direction:column">
                <div class="all_section flex" style="justify-content: space-around;">
                    <button class=" btn-light" onclick="window.location.href='./stream.php'">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
                            <path fill="currentColor" fill-rule="evenodd" d="M5.467 4.392a.75.75 0 0 1-.001 1.06A9.22 9.22 0 0 0 2.75 12a9.22 9.22 0 0 0 2.775 6.606a.75.75 0 1 1-1.05 1.071A10.72 10.72 0 0 1 1.25 12c0-2.972 1.207-5.664 3.156-7.609a.75.75 0 0 1 1.06.001m13.15.072a.75.75 0 0 1 1.06.011A10.72 10.72 0 0 1 22.75 12c0 2.964-1.2 5.65-3.141 7.594a.75.75 0 1 1-1.062-1.06A9.22 9.22 0 0 0 21.25 12a9.22 9.22 0 0 0-2.644-6.475a.75.75 0 0 1 .01-1.06M8.308 7.488a.75.75 0 0 1-.035 1.06c-.949.888-1.524 2.102-1.524 3.434c0 1.348.589 2.575 1.558 3.466a.75.75 0 1 1-1.016 1.104c-1.252-1.151-2.042-2.77-2.042-4.57c0-1.779.771-3.38 2-4.53a.75.75 0 0 1 1.06.036m7.434.038a.75.75 0 0 1 1.06-.024c1.197 1.145 1.947 2.727 1.947 4.48c0 1.775-.767 3.373-1.99 4.521a.75.75 0 1 1-1.027-1.093c.945-.887 1.517-2.1 1.517-3.428c0-1.313-.559-2.512-1.484-3.396a.75.75 0 0 1-.023-1.06m-3.15 1.362l.052.03a13 13 0 0 1 .694.404c.245.155.505.337.761.525l.046.033c.408.3.79.58 1.06.864c.314.328.544.727.544 1.256c0 .53-.23.928-.543 1.257c-.27.283-.653.563-1.061.863a18 18 0 0 1-.807.558c-.215.136-.453.273-.694.405l-.053.029c-.4.22-.79.432-1.132.543c-.409.132-.882.161-1.336-.146c-.428-.289-.604-.717-.692-1.125c-.08-.373-.11-.845-.143-1.367l-.004-.052c-.021-.33-.035-.662-.035-.965s.014-.634.035-.965l.004-.052c.033-.522.063-.994.143-1.367c.088-.408.264-.836.692-1.125c.454-.307.927-.278 1.336-.146c.342.11.732.324 1.132.543m-1.642.87a1 1 0 0 0-.052.174c-.054.25-.079.608-.117 1.2c-.02.31-.032.608-.032.868s.012.558.032.869c.038.59.063.95.117 1.199a1 1 0 0 0 .052.174l.048-.014c.19-.062.451-.201.926-.46a12 12 0 0 0 .613-.358c.205-.13.436-.29.675-.466c.47-.345.74-.547.908-.723c.13-.135.13-.184.129-.217v-.008c0-.033 0-.082-.129-.217c-.167-.175-.438-.378-.909-.723a12 12 0 0 0-.674-.466c-.18-.114-.39-.235-.613-.357c-.475-.26-.736-.4-.926-.46z" clip-rule="evenodd" />
                        </svg> <!-- Sun icon for dark mode -->
                        <p>All Posts</p>
                    </button>
                    <button class=" btn-light" onclick="window.location.href='./follow_dash.php'">
                        <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24">
                            <g fill="none">
                                <path d="m12.593 23.258l-.011.002l-.071.035l-.02.004l-.014-.004l-.071-.035q-.016-.005-.024.005l-.004.01l-.017.428l.005.02l.01.013l.104.074l.015.004l.012-.004l.104-.074l.012-.016l.004-.017l-.017-.427q-.004-.016-.017-.018m.265-.113l-.013.002l-.185.093l-.01.01l-.003.011l.018.43l.005.012l.008.007l.201.093q.019.005.029-.008l.004-.014l-.034-.614q-.005-.018-.02-.022m-.715.002a.02.02 0 0 0-.027.006l-.006.014l-.034.614q.001.018.017.024l.015-.002l.201-.093l.01-.008l.004-.011l.017-.43l-.003-.012l-.01-.01z" />
                                <path fill="currentColor" d="M16 14a5 5 0 0 1 5 5v1a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-1a5 5 0 0 1 5-5zm5.414-4.919a1 1 0 0 1 1.498 1.32l-.084.095L20 13.324a1 1 0 0 1-1.32.083l-.094-.083l-1.414-1.414a1 1 0 0 1 1.32-1.498l.094.084l.707.707zM12 2a5 5 0 1 1 0 10a5 5 0 0 1 0-10" />
                            </g>
                        </svg> <!-- Sun icon for dark mode -->
                        <p>Following</p>
                    </button>
                    <? if (checkPincode()) { ?>
                        <button class=" btn-light" onclick="window.location.href='homePin.php'">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 16 16">
                                <rect width="16" height="16" fill="none" />
                                <g fill="none" stroke="currentColor">
                                    <path d="M8 14.5C10.5 11 12.5 8 12.5 6a4.5 4.5 0 1 0-9 0c0 2 2 5 4.5 8.5Z" />
                                    <path d="M10 6a2 2 0 1 1-4 0a2 2 0 0 1 4 0Z" />
                                </g>
                            </svg><!-- Sun icon for dark mode -->
                            <p style="text-decoration: underline;">Home Pin</p>
                        </button>
                    <? } ?>

                </div>
            </div>
            <div class="first_left_container navaigation_main ">
                <div class="navigationToProduct" style="display: flex; flex-direction:column;gap: 10px;">
                    <a class="dropdown-item" href="/stream.php">
                        <svg xmlns="http://www.w3.org/2000/svg" width="100%" height="100%" viewBox="0 0 24 24">
                            <g fill="none" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.141 5A9.97 9.97 0 0 1 22 12a9.97 9.97 0 0 1-2.922 7.064M5 19.142A9.97 9.97 0 0 1 2 12a9.97 9.97 0 0 1 2.936-7.078m11.349 3.122C17.345 9.059 18 10.449 18 11.982c0 1.552-.67 2.957-1.753 3.974M7.8 16C6.69 14.979 6 13.556 6 11.982C6 10.427 6.673 9.018 7.762 8" />
                                <path d="M13.656 10.451C14.552 11.11 15 11.438 15 12s-.448.891-1.344 1.549a13 13 0 0 1-.718.495a12 12 0 0 1-.653.38c-.894.49-1.34.735-1.741.464s-.437-.838-.51-1.971c-.02-.321-.034-.635-.034-.917s.013-.596.034-.917c.072-1.133.109-1.7.51-1.97c.4-.272.847-.027 1.74.462c.233.127.457.256.654.381c.226.143.471.314.718.495Z" />
                            </g>
                        </svg>
                        <span class="px-1">Social</span>
                    </a>
                    <a class="dropdown-item" href="/dashboard.php">
                        <svg xmlns="http://www.w3.org/2000/svg" width="100%" height="100%" viewBox="0 0 24 24">
                            <path fill="currentColor"
                                d="M14 9.9V8.2q.825-.35 1.688-.525T17.5 7.5q.65 0 1.275.1T20 7.85v1.6q-.6-.225-1.213-.337T17.5 9q-.95 0-1.825.238T14 9.9m0 5.5v-1.7q.825-.35 1.688-.525T17.5 13q.65 0 1.275.1t1.225.25v1.6q-.6-.225-1.213-.338T17.5 14.5q-.95 0-1.825.225T14 15.4m0-2.75v-1.7q.825-.35 1.688-.525t1.812-.175q.65 0 1.275.1T20 10.6v1.6q-.6-.225-1.213-.338T17.5 11.75q-.95 0-1.825.238T14 12.65M6.5 16q1.175 0 2.288.263T11 17.05V7.2q-1.025-.6-2.175-.9T6.5 6q-.9 0-1.788.175T3 6.7v9.9q.875-.3 1.738-.45T6.5 16m6.5 1.05q1.1-.525 2.213-.787T17.5 16q.9 0 1.763.15T21 16.6V6.7q-.825-.35-1.713-.525T17.5 6q-1.175 0-2.325.3T13 7.2zM12 20q-1.2-.95-2.6-1.475T6.5 18q-1.05 0-2.062.275T2.5 19.05q-.525.275-1.012-.025T1 18.15V6.1q0-.275.138-.525T1.55 5.2q1.15-.6 2.4-.9T6.5 4q1.45 0 2.838.375T12 5.5q1.275-.75 2.663-1.125T17.5 4q1.3 0 2.55.3t2.4.9q.275.125.413.375T23 6.1v12.05q0 .575-.487.875t-1.013.025q-.925-.5-1.937-.775T17.5 18q-1.5 0-2.9.525T12 20m-5-8.35" />
                        </svg>
                        <span class="px-1">Central</span>
                    </a>
                    <!-- <li><a class="dropdown-item" href="cream_dashboard.php">Creator</a></li> -->
                    <a class="dropdown-item" href="/cream_dashboard.php">
                        <svg xmlns="http://www.w3.org/2000/svg" width="100%" height="100%" viewBox="0 0 48 48">
                            <g fill="currentColor">
                                <path
                                    d="M12.5 6c-1.112 4.017-2.543 5.39-6.5 6.5c3.957 1.11 5.388 2.483 6.5 6.5c1.112-4.017 2.543-5.39 6.5-6.5c-3.957-1.11-5.388-2.483-6.5-6.5m0 17c-1.112 4.017-2.543 5.39-6.5 6.5c3.957 1.11 5.388 2.483 6.5 6.5c1.112-4.017 2.543-5.39 6.5-6.5c-3.957-1.11-5.388-2.483-6.5-6.5M23 12.5c3.957-1.11 5.388-2.483 6.5-6.5c1.112 4.017 2.543 5.39 6.5 6.5c-3.957 1.11-5.388 2.483-6.5 6.5c-1.112-4.017-2.543-5.39-6.5-6.5" />
                                <path fill-rule="evenodd"
                                    d="m35.8 41.456l-.23-.23l-.014-.013l-18.142-18.142a2 2 0 0 1 0-2.828l2.829-2.829a2 2 0 0 1 2.828 0L41.456 35.8a2 2 0 0 1 0 2.828l-2.828 2.829a2 2 0 0 1-2.829 0M22.615 25.444l-3.787-3.787l2.828-2.829l3.788 3.788z"
                                    clip-rule="evenodd" />
                            </g>
                        </svg>
                        <span class="px-1">Creator</span>
                    </a>
                </div>
            </div>

        </div>


        <div class="first_right_container">
        </div>
        <div class="loading-spinner"></div>

        <button id="plusButton" class="btn btn-primary ">
            <i class="fa fa-plus"></i>
        </button>
    </div>

    <!-- Edit Post Modal -->
    <div class="modal fade" id="editPostModal" tabindex="-1" aria-labelledby="editPostModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editPostModalLabel">Edit Post</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Textarea for editing content -->
                    <textarea id="modalContentTextarea" class="form-control" rows="5" style="overflow-y: auto; max-height: 200px;"></textarea>
                </div>
                <div class="modal-footer">
                    <!-- <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button> -->
                    <button type="button" id="saveModalEditButton" class="btn btn-primary">Save changes</button>
                </div>
            </div>
        </div>
    </div>


    <!-- Report Modal -->
    <div class="modal fade" id="reportModal" tabindex="-1" aria-labelledby="reportModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="reportModalLabel">Why are you reporting this post?</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Dropdown for selecting reasons -->
                    <form id="reportReasonsForm">
                        <div class="mb-3">
                            <label for="reportReasonSelect" class="form-label">Select a reason:</label>
                            <select class="form-select" id="reportReasonSelect">
                                <option value="" selected disabled>Choose a reason</option>
                                <option value="Nudity or sexual activity">Nudity or sexual activity</option>
                                <option value="Bullying or harassment">Bullying or harassment</option>
                                <option value="Suicide, self-injury or eating disorders">Suicide, self-injury or eating disorders</option>
                                <option value="Violence, hate or exploitation">Violence, hate or exploitation</option>
                                <option value="Selling or promoting restricted items">Selling or promoting restricted items</option>
                                <option value="Scam, fraud or impersonation">Scam, fraud or impersonation</option>
                                <option value="I just don't like it">I just don't like it</option>
                                <option value="Others">Others</option>
                            </select>
                        </div>
                        <!-- Hidden textarea initially -->
                        <div class="mb-3" id="otherReasonContainer" style="display: none;">
                            <label for="otherReasonTextarea" class="form-label">Please specify:</label>
                            <textarea class="form-control" id="otherReasonTextarea" rows="3" placeholder="Describe your reason here"></textarea>
                        </div>
                        <input type="hidden" id="reportStreamId" value="">
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" id="submitReportButton" class="btn btn-primary">Submit</button>
                </div>
            </div>
        </div>
    </div>

    <!-- likedUsers Modal -->
    <div class="modal fade" id="likedUsersModal" tabindex="-1" aria-labelledby="likedUsersModalLabel" aria-hidden="true">
        <div class="modal-dialog" style="width:fit-content">
            <div class="modal-content" style="margin-left: 50px;">
                <div class="modal-header">
                    <h5 class="modal-title" id="likedUsersModalLabel">People who liked this post</h5>
                </div>
                <div class="modal-body">
                    <ul id="likedUsersList" class="list-group">
                        <!-- Usernames will be dynamically inserted here -->
                    </ul>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Upload Modal -->
    <div class="modal fade" id="uploadModal" tabindex="-1" aria-labelledby="uploadModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="uploadModalLabel">Share</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="background-color:#555"></button>
                </div>
                <div class="modal-body">
                    <!-- Your upload form goes here -->
                    <div class="upload-section">
                        <div class="d-flex align-items-start">
                            <div class="w-100">
                                <div id="loadingIndicator" style="display: none; font-size: 14px; color: gray;">Generating<span id="dots">...</span></div>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <textarea id="contentTextarea" class="form-control mb-2" placeholder="What would you like to share?"
                                        style="flex: 1; overflow: hidden; resize: none; min-height:180px;" oninput="adjustTextareaHeight(this)"></textarea>
                                </div>

                                <div class="d-flex justify-content-between align-items-center" style="padding:10px 0">
                                    <div class="mediawithvisibility">
                                        <input type="file" id="fileInput" accept="image/*,video/*" class="d-none" onchange="previewMedia();" multiple>
                                        <button type="button" class="btn btn-link text-decoration-none text-light bg-none" onclick="document.getElementById('fileInput').click();">
                                            <i class="fa-solid fa-photo-film"></i>
                                        </button>

                                        <div class="form-group ">
                                            <label for="visibilitySelect">Who can see this post?</label>
                                            <select id="visibilitySelect" name="visibility" class="form-control">

                                                <option value="public">Public</option>
                                                <option value="private">People I follow</option>
                                                <!-- <option value="">People I follow</option> -->

                                            </select>
                                        </div>
                                    </div>
                                    <div class="d-flex">
                                        <button type="button" onclick="fetchGenAIContent()" style="text-decoration: none; border:none; border-radius:5px">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
                                                <g fill="none" fill-rule="evenodd">
                                                    <path d="m12.594 23.258l-.012.002l-.071.035l-.02.004l-.014-.004l-.071-.036q-.016-.004-.024.006l-.004.01l-.017.428l.005.02l.01.013l.104.074l.015.004l.012-.004l.104-.074l.012-.016l.004-.017l-.017-.427q-.004-.016-.016-.018m.264-.113l-.014.002l-.184.093l-.01.01l-.003.011l.018.43l.005.012l.008.008l.201.092q.019.005.029-.008l.004-.014l-.034-.614q-.005-.019-.02-.022m-.715.002a.02.02 0 0 0-.027.006l-.006.014l-.034.614q.001.018.017.024l.015-.002l.201-.093l.01-.008l.003-.011l.018-.43l-.003-.012l-.01-.01z" />
                                                    <path fill="currentColor" d="M19 19a1 1 0 0 1 .117 1.993L19 21h-7a1 1 0 0 1-.117-1.993L12 19zm.631-14.632a2.5 2.5 0 0 1 0 3.536L8.735 18.8a1.5 1.5 0 0 1-.44.305l-3.804 1.729c-.842.383-1.708-.484-1.325-1.326l1.73-3.804a1.5 1.5 0 0 1 .304-.44L16.096 4.368a2.5 2.5 0 0 1 3.535 0m-2.12 1.414L6.677 16.614l-.589 1.297l1.296-.59L18.217 6.49a.5.5 0 1 0-.707-.707M6 1a1 1 0 0 1 .946.677l.13.378a3 3 0 0 0 1.869 1.87l.378.129a1 1 0 0 1 0 1.892l-.378.13a3 3 0 0 0-1.87 1.869l-.129.378a1 1 0 0 1-1.892 0l-.13-.378a3 3 0 0 0-1.869-1.87l-.378-.129a1 1 0 0 1 0-1.892l.378-.13a3 3 0 0 0 1.87-1.869l.129-.378A1 1 0 0 1 6 1m0 3.196A5 5 0 0 1 5.196 5q.448.355.804.804q.355-.448.804-.804A5 5 0 0 1 6 4.196" />
                                                </g>
                                            </svg>
                                        </button>
                                        <button class="btn btn-upload ms-2" onclick="uploadPost()">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
                                                <path fill="none" stroke="currentColor" d="m6.998 10.247l.435.76c.277.485.415.727.415.993s-.138.508-.415.992l-.435.761c-1.238 2.167-1.857 3.25-1.375 3.788c.483.537 1.627.037 3.913-.963l6.276-2.746c1.795-.785 2.693-1.178 2.693-1.832s-.898-1.047-2.693-1.832L9.536 7.422c-2.286-1-3.43-1.5-3.913-.963s.137 1.62 1.375 3.788Z" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                <div id="mediaSlider" class="slider-container">
                                    <div id="mediaSlides" class="slider-slides">
                                        <!-- Dynamic media elements will be appended here (images or videos) -->
                                    </div>
                                    <button class="prev slideBtn" onclick="moveSlide(-1)">&#10094;</button>
                                    <button class="next slideBtn" onclick="moveSlide(1)">&#10095;</button>
                                </div>
                                <div id="linkPreview" class="mt-3" style="display: none; width: 100%; height:auto;">
                                    <div class="hyperlink" style="padding: 10px;">
                                        <img src="" alt="Card image">
                                        <div style="padding: 0px; flex-grow: 1;">
                                            <h3 id="linkHeading" style="font-size: 16px; margin: 0 0 5px; color: #333;"></h3>
                                            <p id="linkDesc" style="margin: 0 0 10px; font-size: 14px; color: #555; line-height: 1.4;"></p>
                                            <a id="linkUrl" href="" style="font-size: 13px; color: #007bff; text-decoration: none;"></a>
                                        </div>
                                    </div>
                                </div>

                                <div id="ytPreview" class="mt-3 ytprew"></div>
                                <div id="loadingIcon" class="text-center" style="display:none;">
                                    <div class="spinner-border" role="status">
                                        <span class="sr-only">Loading...</span>
                                    </div>
                                </div>

                                <div>
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
                </div>
            </div>
        </div>
    </div>

    <!-- Media Modal -->
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
    <? include 'assets/php/footer.php' ?>
    <? include 'assets/php/bottom_navbar.php' ?>
</body>

</html>