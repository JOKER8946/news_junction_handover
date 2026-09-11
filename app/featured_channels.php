<?php
require_once './inc/php/db_config.php';
require_once './inc/php/validate.logged.php';
require_once './inc/php/function.php';


$rss_id = isset($_GET['rss_id']) ? intval($_GET['rss_id']) : null;
$feedId = isset($_GET['feed_id']) ? intval($_GET['feed_id']) : null;
function guardianImg($image)
{
    $position = strpos($image, '.jpg');
    // Remove any existing query parameters by splitting the URL at the '?' and keeping the base part
    $baseUrl = substr($image, 0, $position + 4);

    // Append the new query parameters
    $modimg = $baseUrl . '?width=620&dpr=1&s=none';

    return $modimg;
}

function fetch_articles($readerdb, $rss_id)
{
    if ($rss_id === null) {
        echo '<p>Error: RSS ID not provided.</p>';
        exit; // Stop execution if rss_id is not provided
    }

    // Prepare the query
    $stmt = $readerdb->prepare("SELECT rfa.id, rfu.rss_publisher, rfa.url, rfa.title, rfa.description, rfa.image , rfu.rss_image , rfa.date
                            FROM rss_feeds_articles rfa 
                            INNER JOIN rss_feeds_url rfu ON rfa.feed_id = rfu.rss_id 
                            WHERE rfu.rss_id = ? 
                            ORDER BY rfa.date DESC limit 100 ");

    $stmt->bind_param("i", $rss_id);
    $stmt->execute();
    $result = $stmt->get_result();
?>
    <h1 class="text-center mb-4 text-white"><?= fetch_publisher_title($readerdb, $rss_id) ?></h1>
    <? if ($result->num_rows > 0) {
        // Output data of each row
        while ($row = $result->fetch_assoc()) {
            $feedId = stripslashes($row['id']);
            $title = htmlspecialchars(strip_tags(stripslashes($row['title'])));
            $description = htmlspecialchars(strip_tags(stripslashes($row['description'])));
            $date = htmlspecialchars(strip_tags(stripslashes($row['date'])));

            // Determine image source
            $image = is_null($row['image']) || $row['image'] === ''
                ? (is_null($row['rss_image']) || $row['rss_image'] === '' ? '' : $row['rss_image'])
                : $row['image'];

            // Skip card if image is empty or invalid
            if (empty($image)) {
                continue;
            }

            // Validate image URL
            $imageInfo = @getimagesize($image);
            if ($imageInfo === false) {
                continue;
            }
    ?>
            <div class="col-md-4">
                <div class="card mb-4 news-item"
                    data-id="<?= htmlspecialchars($feedId) ?>"
                    data-title="<?= $title ?>"
                    data-description="<?= $description ?>"
                    data-image="<?= htmlspecialchars($image) ?>"
                    data-url="<?= htmlspecialchars($row['url']) ?>"
                    data-date="<?= $date ?>">
                    <img src="<?= htmlspecialchars($image) ?>" class="card-img-top" alt="News Image">
                    <div class="card-body">
                        <h5 class="card-title"><?= $title ?></h5>
                        <p class="card-text"><strong>Date: </strong><?= $date ?></p>
                    </div>
                </div>
            </div>
        <?php
        }
    } else {
        ?>
        <p>No news available at the moment.</p>
<?
    }
    // Close the prepared statement and the database connection
    $stmt->close();
    $readerdb->close();
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Featured Channels</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="inc/css/social.css">
    <link rel="stylesheet" href="inc/css/reader.css">
    <link rel="icon" type="image/x-icon" href="../grfx/img/logo.ico">

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        .card-body {
            background-color: var(--bg-card);
        }

        .btn {
            color: var(--text-primary) !important;

        }

        .modal-content {
            position: relative;
            display: flex;
            flex-direction: column;
            width: 100%;
            color: var(--text-primary);
            pointer-events: auto;
            background-color: var(--bg-main);
            background-clip: padding-box;
            border: var();
            outline: 0;
        }

        .text-white {
            color: var(--text-primary) !important;

        }

        .btn-primary {
            background-color: var(--primary);
            color: #fff;
            border: none;
        }

        .news-item {
            padding: 0px;
            background-color: var(--bg-main);
        }
    </style>

</head>

<body>

    <? include 'inc/php/social_navbar.php' ?>
    <div class="reader-main-content main-content">
        <? include 'inc/php/social_sidebar.php' ?>
        <div class="row" id="news-container">
            <? fetch_articles($readerdb, $rss_id) ?>
        </div>
        <!-- Modal -->
        <div class="modal fade" id="newsModal" tabindex="-1" aria-labelledby="newsModalLabel" aria-hidden="true" style=" top:70px">
            <div class="modal-dialog" style="margin-bottom: 110px;">
                <div class="modal-content">
                    <div class="modal-header w-full flex justify-between items-center gap-4">
                        <h5 class="model-title " id="newsModalLabel"></h5>
                        <button type="button" class="btn-close w-full w-[1/5] flex items-center justify-center color-red" data-bs-dismiss="modal" aria-label="Close" style="background-color: #a3a2a2;"></button>
                    </div>
                    <div class="modal-body">
                        <img src="" id="modal-image" class="img-fluid mb-3" alt="News Image">
                        <p id="modal-description"></p>
                        <p class="datewithtime"><strong>Date: </strong><span id="modal-date"></span></p>
                        <div class="morewithlike d-flex justify-content-between">
                            <a href="" id="modal-url" target="_blank" class="btn btn-primary">Read More</a>
                            <div id="dataModal" data-id="" data-title="" data-description="" data-url=""></div>
                            <div class="data col-12 col-md-6 text-md-right pl-0 mt-2 mt-md-0 d-flex ">
                                <!-- <button class="btn p-2 reader-button play-button">
                                    <i class="fas fa-volume-up"></i>
                                </button> -->
                                <button class="btn  p-2 reader-button pause-button" style="display:none;">
                                    <i class="fas fa-pause"></i>
                                </button>
                                <button class="btn  p-2 reader-button resume-button" style="display:none;">
                                    <i class="fas fa-play"></i>
                                </button>
                                <button class="btn p-2 reader-button stop-button" style="display:none;">
                                    <i class="fas fa-stop"></i>
                                </button>
                                <button class="btn reader-button likeButton">
                                    <i id="thumbsUp" class="fa-regular fa-thumbs-up" style="padding-right: 4px; padding-top: 2px;"></i>
                                    <div id="likeCount"></div>
                                </button>
                                <!-- <button class="btn p-2 reader-button comments" onclick="toggleComment()">
                                <i class="fa-regular fa-comments"></i>
                            </button> -->
                                <button class="btn p-2  reader-button icon-container" style="margin-bottom: 0px;">
                                    <i class="far fa-bookmark" id="bookmarkIcon"></i>
                                </button>
                                <!-- <button class="btn p-2 reader-button" onclick="toggleShare()">
                                <i class="fa-solid fa-arrow-up-from-bracket"></i>
                            </button> -->
                                <button class="btn p-2 reader-button copyButton">
                                    <i class="fa-solid fa-arrow-up-from-bracket"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <? include "inc/php/footer.php"; ?>

    <script>
        const userId = <?= $gUserId ?>;
        $(document).ready(function() {
            $('.news-item').on('click', function() {
                var feed_id = $(this).data('id');
                var title = $(this).data('title');
                var description = $(this).data('description');
                var image = $(this).data('image');
                var date = $(this).data('date');
                var url = $(this).data('url');

                $('#dataModal').attr('data-id', feed_id);
                $('#dataModal').attr('data-title', title);
                $('#newsModalLabel').text(title);
                $('#modal-description').text(description);
                $('#modal-image').attr('src', image);
                $('#modal-date').text(date);
                $('#modal-url').attr('href', url);

                // loadLike(feed_id, userId);
                // likeCount(feed_id, userId);
                checkCollection(feed_id, userId)

                var modal = new bootstrap.Modal($('#newsModal')[0]);


                modal.show();
            });


            $('.likeButton').on('click', function() {
                var feed_id = $('#dataModal').attr('data-id');
                toggleLike(feed_id, userId);
            });

            $('.copyButton').on('click', function() {
                var feed_url = $('#modal-url').attr('href');

                // var feed_url = $('.news-item').data('url');
                copyToClipboard(feed_url); // Call the correct function with the correct variable
                // copyToClipboards(feed_url);
            });

            $('#bookmarkIcon').on('click', function() {
                var feed_id = $('#dataModal').attr('data-id'); // Ensure this targets the correct item
                console.log(feed_id);

                // Check the current state of the icon
                if ($(this).hasClass('far fa-bookmark')) {
                    addToCollection(feed_id, userId);
                } else {
                    removeFromCollection(feed_id, userId);
                }
            });
        });

        function loadLike(feedId, userId) {
            $.ajax({
                url: 'inc/handler.php',
                type: 'POST',
                contentType: 'application/json', // Specify that you're sending JSON
                data: JSON.stringify({
                    request: 'loadLike',
                    userId: userId,
                    feedId: feedId
                }),
                success: function(response) {
                    if (response.status === "success") {
                        console.log("Response:", response.response); // Access 'response' instead of 'data'
                        if (response.response === true) {
                            $('.likeButton #thumbsUp').removeClass('fa-regular').addClass('fa-solid'); // Change to solid thumbs up
                        } else {
                            $('.likeButton #thumbsUp').removeClass('fa-solid').addClass('fa-regular'); // Change to regular thumbs up
                        }
                    } else {
                        console.error("Error message:", response.message);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error("Error:", textStatus, errorThrown);
                }
            });
        }

        function likeCount(feedId, userId) {
            $.ajax({
                url: 'inc/handler.php',
                type: 'POST',
                contentType: 'application/json', // Specify that you're sending JSON
                data: JSON.stringify({
                    request: 'likeCount',
                    userId: userId,
                    feedId: feedId
                }),
                success: function(response) {
                    if (response.status === "success") {
                        console.log("Response:", response); // Access 'response' instead of 'data'
                        $('.likeButton #likeCount').html(response.count || ''); // Change to solid thumbs up

                    } else {
                        console.error("Error message:", response.message);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error("Error:", textStatus, errorThrown);
                }
            });
        }

        function toggleLike(feedId, userId) {
            var thumbsUpIcon = $('.likeButton #thumbsUp');
            var isLiked = thumbsUpIcon.hasClass('fa-solid');

            // Determine the request type based on the current like status
            var requestType = isLiked ? 'unlike' : 'like';

            // Make the AJAX call to like/unlike the post
            $.ajax({
                url: 'inc/handler.php',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({
                    request: requestType,
                    userId: userId,
                    feedId: feedId
                }),
                success: function(response) {
                    if (response.status === "success") {
                        // Toggle the icon based on the like status
                        if (requestType === 'like') {
                            thumbsUpIcon.removeClass('fa-regular').addClass('fa-solid');
                        } else {
                            thumbsUpIcon.removeClass('fa-solid').addClass('fa-regular');
                        }
                        likeCount(feedId, userId);
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

    <script>
        function copyToClipboard(note) {
            // Append the custom text to the note
            var textToCopy = note + '\n\nshared via newsjunction.net';

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
    </script>

    <script>
        function checkCollection(feedId, userId) {
            $.ajax({
                url: 'inc/handler.php',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({
                    request: 'checkColl',
                    userId: userId,
                    feedId: feedId
                }),
                success: function(response) {
                    try {
                        if (response.status === 'success') {
                            if (response.count > 0) {
                                // console.log(response.count);
                                $('#bookmarkIcon').removeClass('far fa-bookmark').addClass('fas fa-bookmark');
                            } else {
                                $('#bookmarkIcon').removeClass('fas fa-bookmark').addClass('far fa-bookmark');
                            }
                        } else {
                            console.error('Failed to check collection: ' + response.message);
                        }
                    } catch (e) {
                        console.error('Parsing error:', e);
                        // alert('An error occurred while processing your request.'); // User-friendly error message
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX request failed: ' + error);
                    alert('Failed to check collection. Please check your connection and try again.'); // User-friendly error message
                }
            });
        }

        function addToCollection(feedId, userId) {
            $.ajax({
                url: 'inc/handler.php',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({
                    request: 'addColl',
                    userId: userId,
                    feedId: feedId
                }),
                success: function(response) {
                    if (response.status === 'success') {
                        console.log('Added to collection');
                        $('#bookmarkIcon').removeClass('far fa-bookmark').addClass('fas fa-bookmark');
                    } else {
                        console.error('Failed to add to collection: ' + response.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX request failed: ' + error);
                }
            });
        }

        function removeFromCollection(feedId, userId) {
            $.ajax({
                url: 'inc/handler.php',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({
                    request: 'removeColl',
                    userId: userId,
                    feedId: feedId
                }),
                success: function(response) {
                    if (response.status === 'success') {
                        console.log('Removed from collection');
                        $('#bookmarkIcon').removeClass('fas fa-bookmark').addClass('far fa-bookmark');
                    } else {
                        console.error('Failed to remove from collection: ' + response.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX request failed: ' + error);
                }
            });
        }
    </script>

    <script>
        var isPlaying = false;

        $(document).ready(function() {

            // Play button click handler
            $('.play-button').on('click', function() {
                var title = $('#dataModal').data('title');
                var desc = $('#dataModal').data('description');
                playAudio(title, desc, $(this));
            });

            // Function to play audio
            function playAudio(title, desc, button) {
                var audio = new Audio();

                if (isPlaying) {
                    console.log("Audio is already playing. Cannot start another instance.");
                    return;
                }

                console.log("Starting audio playback...");

                isPlaying = true; // Set flag to true to indicate audio playback has started

                // Ajax call to get audio file URL
                $.ajax({
                    url: 'texttovoice/processvoice.php',
                    type: 'POST',
                    data: {
                        title: title,
                        description: desc
                    },
                    success: function(response) {
                        audio.src = response;
                        audio.play();

                        // Update button visibility
                        button.hide();
                        button.siblings('.pause-button').show();
                        button.siblings('.stop-button').show();

                        // Pause button click handler
                        button.siblings('.pause-button').on('click', function() {
                            audio.pause();
                            $(this).hide();
                            $(this).siblings('.resume-button').show();
                        });

                        // Resume button click handler
                        button.siblings('.resume-button').on('click', function() {
                            audio.play();
                            $(this).hide();
                            $(this).siblings('.pause-button').show();
                        });

                        button.siblings('.stop-button').on('click', function() {
                            audio.pause(); // Pause the audio (assuming this stops playback)
                            audio.currentTime = 0; // Reset audio playback to the beginning
                            button.siblings('.resume-button').hide(); // Hide resume button if shown
                            button.siblings('.stop-button').hide();
                            button.siblings('.pause-button').hide();
                            button.show();
                            isPlaying = false;
                        });

                        // Reset when audio ends
                        audio.onended = function() {
                            isPlaying = false;
                            button.show();
                            button.siblings('.pause-button').hide();
                            button.siblings('.resume-button').hide();
                            button.siblings('.stop-button').hide();
                        };
                    },
                    error: function(xhr, status, error) {
                        console.error("Error playing audio:", error);
                    }
                });
            }
        });


        function broadcast(link) {

            if (isPlaying) {
                console.log("Audio is already playing. Cannot start another instance.");
                return;
            }

            console.log("Starting audio playback...");

            isPlaying = true; // Set flag to true to indicate audio playback has started

            var braudio = new Audio();
            console.log(link);
            braudio.src = link;
            braudio.play();

            $('.broadcast-pause').show();
            $('.broadcast-stop').show();

            $('.broadcast-pause').on('click', function() {
                braudio.pause();
                $('.broadcast-pause').hide();
                $('.broadcast-resume').show();
            });

            $('.broadcast-resume').on('click', function() {
                braudio.play();
                $('.broadcast-resume').hide();
                $('.broadcast-pause').show();
            });

            $('.broadcast-stop').on('click', function() {
                braudio.pause();
                braudio.currentTime = 0;
                $('.broadcast-pause').hide();
                $('.broadcast-resume').hide();
                $('.broadcast-stop').hide();
                isPlaying = false;

            });


            braudio.onended = function() {
                isPlaying = false;
                $('.broadcast-pause').hide();
                $('.broadcast-resume').hide();
                $('.broadcast-stop').hide();
            };
        }
    </script>
</body>

</html>