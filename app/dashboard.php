<?php
require_once './inc/php/db_config.php';
require_once './inc/php/validate.logged.php';
require_once './inc/php/function.php';


function display_my_feeds($readerdb, $userId)
{
    // Prepare the query
    $stmt = $readerdb->prepare("SELECT rfa.id, rfu.rss_publisher, rfa.url, rfa.title, rfa.description, rfa.image, rfa.date 
  FROM rss_feeds_articles rfa 
  INNER JOIN rss_feeds_url rfu ON rfa.feed_id = rfu.rss_id 
  INNER JOIN reader_collection rc ON rfa.id=rc.feed_id
  WHERE rc.user_id = ? 
  ORDER BY rfa.date DESC");

    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
?>
        <?
        // Output data of each row
        while ($row = $result->fetch_assoc()) {
            $feedId = stripslashes($row['id']);
            $title = htmlspecialchars(strip_tags(stripslashes($row['title'])));
            $description = htmlspecialchars(strip_tags(stripslashes($row['description'])));
            // $date = htmlspecialchars(strip_tags(stripslashes($row['date'])));
            $image = is_null($row['image']) || $row['image'] === '' ? 'grfx/img/toi.png' : $row['image'];
            $date = htmlspecialchars(strip_tags(stripslashes($row['date'])));
            echo '
              <div class="card item-card">
                  <img src="' . htmlspecialchars($image) . '" class="card-img-top" alt="' . $title . '">
                  <div class="card-body">
                      <h5 class="card-title">' . $title . '</h5>
                      <a href="#" 
                         class="btn btn-primary read-more" 
                         data-id=' . htmlspecialchars($row['id']) . '"
                         data-title="' . htmlspecialchars($row['title']) . '" 
                         data-description="' . $description . '" 
                         data-image="' . htmlspecialchars($image) . '" 
                         data-date="' . htmlspecialchars($date) . '"
                         data-url="' . htmlspecialchars($row['url']) . '">
              
                         Read
                      </a>
                  </div>
              </div>
          ';
        }
        ?>
        </div>
        </div>
    <?
    }
    // Close the prepared statement and the database connection
    $stmt->close();
}
function build_image_url($type, $data)
{
    return "data/" . $type . "/" . $data;
}

function display_opinions($creamdb)
{
    $stmt = $creamdb->prepare("SELECT id, full_name, profile_pic FROM user WHERE is_influencer=1");
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
    ?>
        <div class="category" id="opinion">
            <h2 class="category-title">Opinion</h2>
            <div class="scroll-row">
                <? while ($row = $result->fetch_assoc()) { ?>
                    <div class="card item-card">
                        <img src="<?= build_image_url("profilePic", $row['profile_pic']) ?>" class="card-img-top">
                        <div class="card-body">
                            <h5 class="card-title"><?= $row['full_name'] ?></h5>
                            <!-- <h5 class="card-title">Mindfulness Coach</h5> -->
                            <a href="influencer.php?infId=<?= $row['id'] ?>" class="btn btn-primary">Read</a>
                        </div>
                    </div>
                <?
                }
                ?>
            </div>
        </div>
    <?
    }
}

function display_my_featured_channel($user_id)
{
    global $readerdb;

    // Fetch the user's channel details
    $stmt = $readerdb->prepare("
        SELECT id, name, profilePic 
        FROM channels where featured_channel='Y'");
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) { ?>
        <?php while ($row = $result->fetch_assoc()) { ?>
            <div class="card item-card">
                <img src="<?= build_image_url('channelPic', $row['profilePic']) ?>" class="card-img-top">
                <div class="card-body">
                    <h5 class="card-title"><?= htmlspecialchars($row['name']) ?></h5>
                    <a href="channel.php?channelId=<?= $row['id'] ?>&channelName=<?= $row['name'] ?>" class="btn btn-primary">Visit Channel</a>
                </div>
            </div>
        <?php
        }
    }
}

function display_my_channel($user_id)
{
    global $readerdb;

    // Fetch all channels including creator ID (created_by)
    $stmt = $readerdb->prepare("SELECT c.id, c.name, c.profilePic, c.visibility, c.created_by FROM channels c");
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) { ?>
        <div class="category">
            <h2 class="category-title">User Channels</h2>
            <div class="scroll-row">
                <?php while ($row = $result->fetch_assoc()) {
                    $is_private = ($row['visibility'] === 'private');
                    $can_view = true;

                    if ($is_private) {
                        // Check if the user is following the channel's creator
                        $follow_stmt = $readerdb->prepare("SELECT 1 FROM reader_stream_follow WHERE follower_id = ? AND following_id = ?");
                        $follow_stmt->bind_param("ii", $user_id, $row['created_by']);
                        $follow_stmt->execute();
                        $follow_result = $follow_stmt->get_result();
                        $can_view = ($follow_result->num_rows > 0);
                        $follow_stmt->close();
                    }

                    if ($can_view) { ?>
                        <div class="card item-card">
                            <img src="<?= build_image_url('channelPic', $row['profilePic']) ?>" class="card-img-top">
                            <div class="card-body">
                                <h5 class="card-title"><?= htmlspecialchars($row['name']) ?></h5>
                                <a href="channel.php?channelId=<?= $row['id'] ?>&channelName=<?= urlencode($row['name']) ?>" class="btn btn-primary">Visit Channel</a>
                            </div>
                        </div>
                <?php }
                } ?>
            </div>
        </div>
    <?php
    } else {
        echo "<p>No channels found.</p>";
    }
}

function my_channel($user_id)
{
    global $readerdb;

    // Fetch the user's channels (now includes visibility)
    $stmt = $readerdb->prepare("SELECT id, name, profilePic, visibility FROM channels WHERE created_by = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) { ?>
        <div class="category">
            <h2 class="category-title">My Channels</h2>
            <div class="scroll-row">
                <?php while ($row = $result->fetch_assoc()) {
                    $channel_id = $row['id'];
                    $visibility = $row['visibility'];
                    $is_private = ($visibility === 'private');
                ?>
                    <div class="card item-card">
                        <img src="<?= build_image_url('channelPic', $row['profilePic']) ?>" class="card-img-top" alt="Channel Image" onerror="this.onerror=null;this.src='/data/channelPic/default.png';">
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($row['name']) ?></h5>

                            <a href="channel.php?channelId=<?= $channel_id ?>&channelName=<?= urlencode($row['name']) ?>" class="btn btn-primary mb-2">Visit Channel</a>

                            <div class="card-bottom">
                                <!-- Menu Button -->
                                <div class="dropdown">
                                    <button class="btn btn-secondary dropdown-toggle" type="button" id="channelMenu<?= $channel_id ?>" data-bs-toggle="dropdown" aria-expanded="false">
                                        Menu
                                    </button>

                                    <ul class="dropdown-menu" aria-labelledby="channelMenu<?= $channel_id ?>">
                                        <li>
                                            <!-- Edit Channel -->
                                            <a href="edit_channel.php?channelId=<?= $channel_id ?>" class="btn btn-primary w-100 mx-3 my-1" style="width: calc(100% - 1.5rem) !important;">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>
                                        </li>
                                        <li>
                                            <!-- Toggle Visibility -->
                                            <form action="toggle_visibility.php" method="POST" class="px-3 py-1">
                                                <input type="hidden" name="channel_id" value="<?= $channel_id ?>">
                                                <input type="hidden" name="current_visibility" value="<?= $visibility ?>">
                                                <input type="hidden" name="action" value="toggle_visibility">
                                                <button type="submit" class="btn btn-primary  w-100">
                                                    Make <?= $is_private ? 'Public' : 'Private' ?>
                                                </button>
                                            </form>
                                        </li>
                                        <li>
                                            <!-- Delete Channel -->
                                            <form action="toggle_visibility.php" method="POST" onsubmit="return confirm('Are you sure you want to delete this channel?');" class="px-3 py-1">
                                                <input type="hidden" name="channel_id" value="<?= $channel_id ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <button type="submit" class="btn btn-primary w-100" title="Delete">
                                                    <i class="fas fa-trash-alt"></i> Delete
                                                </button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            </div>



                        </div>
                    </div>
                <?php } ?>
            </div>
        </div>
<?php
    } else {
        echo "<p>No channels found.</p>";
    }
}

function my_newspapers($user_id)
{
    global $readerdb;

    // Get distinct districts that have newspapers for this user
    $stmt = $readerdb->prepare("SELECT district, COUNT(*) as paper_count FROM newspapers WHERE uploaded_by = ? GROUP BY district ORDER BY district ASC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $districtResult = $stmt->get_result();

    if ($districtResult->num_rows > 0) { ?>
        <div class="category" id="newspapers-section">
            <h2 class="category-title">My Newspapers</h2>
            <div class="scroll-row">
                <?php while ($dist = $districtResult->fetch_assoc()) { ?>
                    <a href="district_newspapers.php?district=<?= urlencode($dist['district']) ?>" style="text-decoration:none;">
                        <div class="card item-card" style="cursor:pointer;">
                            <div class="card-img-top" style="height:120px; background:linear-gradient(135deg, #667eea 0%, #764ba2 100%); display:flex; align-items:center; justify-content:center; border-radius:8px 8px 0 0;">
                                <i class="fas fa-map-marker-alt" style="font-size:40px; color:#fff;"></i>
                            </div>
                            <div class="card-body">
                                <h5 class="card-title"><?= htmlspecialchars($dist['district']) ?></h5>
                                <p class="card-para" style="font-size:11px; text-align:center; margin-bottom:5px;"><?= $dist['paper_count'] ?> newspaper<?= $dist['paper_count'] > 1 ? 's' : '' ?></p>
                                <span class="btn btn-primary">View</span>
                            </div>
                        </div>
                    </a>
                <?php } ?>
            </div>
        </div>
<?php
        $stmt->close();
    }
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>News Junction</title>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="grfx/img/logo.ico">

    <!-- Bootstrap CSS -->
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="inc/css/social.css">

    <!-- Custom CSS -->
    <style>
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

        .btn-primary {
            background-color: var(--primary);
            color: #fff;
            border: none;
        }

        /* Navbar brand */
        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            color: #e50914 !important;
            /* Netflix Red */
        }

        /* Navbar links */
        .navbar-nav .nav-link {
            color: #bbb !important;
            transition: color 0.3s;
        }

        .navbar-nav .nav-link:hover {
            color: #fff !important;
        }


        /* Container Padding */
        .container-fluid {
            /* padding: 7rem 2%; */
        }

        /* Container Padding */
        @media screen and (max-width: 768px) {
            .container-fluid {
                padding: 40px 0 !important;
                max-width: 1400px;
                margin: 0 auto;

            }

        }


        /* Category Section */
        .category {
            margin-bottom: 2rem;
        }

        .category-title {
            margin-bottom: 2rem;
            font-size: 1.75rem;
            font-weight: 700;
            /* color: #fff; */
            text-align: center;
        }

        /* Scrollable Row */
        .scroll-row {
            display: flex;
            overflow-x: auto;
            padding-bottom: 1rem;
            scroll-behavior: smooth;
        }

        .scroll-row::-webkit-scrollbar {
            height: 8px;
        }

        .scroll-row::-webkit-scrollbar-thumb {
            background-color: #555;
            border-radius: 4px;
        }

        .scroll-row::-webkit-scrollbar-track {
            background-color: #222;
        }

        .scroll-row {
            display: flex;
            flex-wrap: nowrap;
            overflow-x: auto;
            gap: 0px;
            padding-bottom: 10px;
        }

        .item-card {
            flex: 0 0 auto;
            /* Prevent flex items from shrinking */
            max-width: 200px;
            min-width: 200px !important;
            margin-right: 0.5rem;
            background-color: var(--reader-card-color);
            border: none;
            border-radius: 8px;
            transition: transform 0.3s, box-shadow 0.3s;
            cursor: pointer;
        }



        /* Card Styles */
        .item-card:last-child {
            margin-right: 0;
        }

        .item-card:hover {
            transform: scale(1.05);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.6);
            z-index: 2;
        }

        .item-card img {
            border-top-left-radius: 8px;
            border-top-right-radius: 8px;
            height: 120px;
            object-fit: cover;
        }

        .item-card .card-body {
            padding: 0.5rem 1rem;
        }

        .item-card .card-title {
            font-size: 1rem;
            margin-bottom: 0.5rem;
            color: #fff;
            text-align: center;
        }

        .card-para {
            color: #fff !important;
        }

        .item-card .btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
            border-radius: 4px;
            display: block;
            margin: 0 auto;
            width: 60%;
        }

        /* Action Buttons Section */
        .actions-section {
            margin-top: 2rem;
            margin-bottom: 2rem;
        }

        .action-card {
            background-color: #333 !important;

            border: none;
            border-radius: 8px;
            transition: background-color 0.3s, transform 0.3s;
            cursor: pointer;
        }

        .action-card:hover {
            background-color: #444 !important;
            transform: translateY(-5px);
        }

        .action-card .card-body {
            text-align: center;
            padding: 2rem 1rem;
        }

        .action-card .card-title {
            margin-bottom: 1rem;
            font-size: 1.25rem;
            font-weight: 700;
            color: #fff;
        }

        .action-card .btn {
            margin-top: 1rem;
            padding: 0.5rem 1rem;
            font-size: 1rem;
            border-radius: 4px;
        }

        .icon {
            color: white !important;
            /* White color for the icons */
            /* margin-right: 10px; */
            border: none;
        }

        .navbar-toggler:focus {
            border-color: black !important;
            border: none !important;
            outline: none;
        }



        .tooltip {
            position: relative;
            display: inline-block;
        }

        .tooltiptext {
            visibility: hidden;
            width: 120px;
            background-color: black;
            color: #fff;
            text-align: center;
            border-radius: 5px;
            padding: 5px;
            position: absolute;
            z-index: 1;
            bottom: 125%;
            /* Position above the icon */
            left: 50%;
            margin-left: -60px;
            /* Center the tooltip */
            opacity: 0;
            /* Hide tooltip initially */
            transition: opacity 0.3s;
        }

        .tooltip:hover .tooltiptext {
            visibility: visible;
            opacity: 1;
            /* Show tooltip on hover */
        }

        .btn-close {
            background-color: #ffffff;
        }

        .action-card {
            height: 220px;
            /* Set a fixed height */
            display: flex;
            /* Use flexbox for vertical centering */
            flex-direction: column;
            /* Align children vertically */
            justify-content: center;
            /* Center content vertically */
            text-align: center;
            /* Center text horizontally */
        }

        .dropdown-menu {
            position: absolute;
            top: 100%;
            left: -100px;
            z-index: 1000;
            display: none;
            float: left;
            min-width: 10rem;
            padding: .5rem 0;
            margin: .125rem 0 0;
            font-size: 1rem;
            color: #212529;
            text-align: left;
            list-style: none;
            background-color: #fff;
            background-clip: padding-box;
            border: 1px solid rgba(0, 0, 0, .15);
            border-radius: .25rem;
        }


        .modal-body {
            padding: 20px;
        }

        .morewithlike button::after {
            border-color: pink;
            /* Ensure no outline when focused */
            border: none;
            outline: none;

        }

        .modal-title {
            max-width: 88%;
        }

        /* Responsive Adjustments */
        @media (max-width: 992px) {
            .item-card {
                min-width: 180px;
            }

            .category-title {
                font-size: 1.5rem;
            }
        }

        @media (max-width: 768px) {
            .item-card {
                min-width: 160px;
            }

            .category-title {
                font-size: 1.25rem;
            }

            .action-card .card-title {
                font-size: 1.1rem;
            }

            .action-card .btn {
                font-size: 0.95rem;
            }
        }

        @media (max-width: 576px) {
            .navbar-brand {
                font-size: 1.25rem;
            }



            .item-card {
                max-width: 140px;
            }

            .category-title {
                font-size: 1rem;
            }

            .action-card .card-title {
                font-size: 1rem;
            }

            .action-card .btn {
                font-size: 0.9rem;
            }
        }

        @media (max-width: 768px) {
            .info-icon {
                display: none;
                /* Check for similar rules */
            }
        }

        .go-back-bar {
            margin-top: 100px;
        }

        /* Button styling */
        .add-channel-btn {
            background-color: #28a745;
            /* Green color */
            color: white;
            font-size: 24px;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            position: fixed;
            bottom: 169px;
            right: 20px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            transition: background-color 0.3s ease;
        }

        /* Button hover effect */
        .add-channel-btn:hover {
            background-color: #218838;
            /* Darker green on hover */
        }

        /* Button text styling */
        .add-channel-btn:after {
            content: "+";
            font-size: 36px;
        }

        @media (min-width: 1400px) {

            .container,
            .container-lg,
            .container-md,
            .container-sm,
            .container-xl,
            .container-xxl {
                max-width: 100% !important;
            }
        }

        .sideWithMainContainer {
            display: flex;
            flex-direction: row;
            gap: 10px;
            overflow-x: hidden;

        }

        .sideMaincontent {
            /* height: 85vh; */
            /* overflow-y: scroll; */
            padding: 30px 20px;
        }

        @media (min-width: 768px) {
            .col-md-2 {
                padding: 0px !important;
            }

        }

        @media (max-width: 768px) {
            .col-md-2 {
                display: none !important;
            }

        }
    </style>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/5.3.0/js/bootstrap.min.js"></script>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->

    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        var isPlaying = false;
        const userId = <?= $gUserId ?>;

        $(document).ready(function() {
            const newsModal = new bootstrap.Modal($('#newsModal'));

            $('.likeButton').on('click', function() {
                var feed_id = $('#dataModal').attr('data-id');
                toggleLike(feed_id, userId);
            });

            $('.copyButton').on('click', function() {
                var feed_url = $('#modal-url').attr('href');
                // var feed_url = $('.news-item').data('url');
                copyToClipboard(feed_url); // Call the correct function with the correct variable
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

            $('.read-more').on('click', function(event) {
                event.preventDefault(); // Prevent default anchor behavior

                const title = $(this).data('title');
                const description = $(this).data('description');
                const image = $(this).data('image');
                const url = $(this).data('url');
                const date = $(this).data('date');

                const feed_id = $(this).data('id');

                $('#dataModal').attr('data-id', feed_id);
                $('#dataModal').attr('data-title', title);

                // Populate modal with data
                $('#newsModalLabel').text(title);
                $('#modal-description').text(description);
                $('#modal-image').attr('src', image);
                $('#modal-url').attr('href', url);
                $('#modal-date').text(date);

                checkCollection(feed_id, userId);

                // Show the modal
                newsModal.show();
            });

            // Play button click handler
            $('.play-button').on('click', function() {
                var title = $('#dataModal').data('title');
                var desc = $('#dataModal').data('description');
                playAudio(title, desc, $(this));
            });
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

        function copyToClipboard(feedUrl) {
            // var feedUrl = `http://localhost/new_reader/feed/${feedId}`; // Adjust this to your actual URL structure

            navigator.clipboard.writeText(feedUrl).then(function() {
                alert('Feed URL copied to clipboard: ' + feedUrl);
            }).catch(function(error) {
                console.error('Error copying text: ', error);
            });
        }

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
                    // alert('Failed to check collection. Please check your connection and try again.'); // User-friendly error message
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

</head>

<body>

    <? include 'inc/php/social_navbar.php' ?>
    <div class="reader-main-content main-content">
        <? include 'inc/php/social_sidebar.php' ?>
        <div class="container-fluid  col-sm-12 col-md-12 sideMaincontent" style="padding-bottom: 70px;">
            <!-- Featured Channels Category -->
            <div class="category">
                <h2 class="category-title">Featured Channels</h2>
                <div class="scroll-row">
                    <? display_my_featured_channel($gUserId) ?>
                    <!-- Channel Cards with Logos -->
                    <div class="card item-card">
                        <img src="grfx/img/blr.jpg" class="card-img-top" alt="TOI Logo">
                        <div class="card-body">
                            <h5 class="card-title">Bengaluru news</h5>
                            <a href="featured_channels.php?rss_id=132" class="btn btn-primary">Read</a>
                        </div>
                    </div>
                    <div class="card item-card">
                        <img src="grfx/img/kar.jpg" class="card-img-top" alt="TOI Logo">
                        <div class="card-body">
                            <h5 class="card-title">Karnataka news</h5>
                            <a href="featured_channels.php?rss_id=134" class="btn btn-primary">Read</a>
                        </div>
                    </div>
                    <div class="card item-card">
                        <img src="grfx/img/toi.png" class="card-img-top" alt="TOI Logo">
                        <div class="card-body">
                            <h5 class="card-title">TOI</h5>
                            <a href="featured_channels.php?rss_id=9" class="btn btn-primary">Read</a>
                        </div>
                    </div>
                    <div class="card item-card">
                        <img src="grfx/img/bbc.png" class="card-img-top" alt="Deccan Herald Logo">
                        <div class="card-body">
                            <h5 class="card-title">BBC news</h5>
                            <a href="featured_channels.php?rss_id=38" class="btn btn-primary">Read</a>
                        </div>
                    </div>
                    <div class="card item-card">
                        <img src="grfx/img/the hindu.png" class="card-img-top" alt="The Hindu Logo">
                        <div class="card-body">
                            <h5 class="card-title">The Hindu</h5>
                            <a href="featured_channels.php?rss_id=18" class="btn btn-primary">Read</a>
                        </div>
                    </div>
                    <div class="card item-card">
                        <img src="grfx/img/mint.png" class="card-img-top" alt="WSJ Logo">
                        <div class="card-body">
                            <h5 class="card-title">Mint</h5>
                            <a href="featured_channels.php?rss_id=111" class="btn btn-primary">Read</a>
                        </div>
                    </div>
                    <div class="card item-card">
                        <img src="grfx/img/economist.png" class="card-img-top" alt="Economist Logo"
                            style="background-color: white;">
                        <div class="card-body">
                            <h5 class="card-title">The Economist</h5>
                            <a href="featured_channels.php?rss_id=19" class="btn btn-primary">Read</a>
                        </div>
                    </div>
                    <div class="card item-card">
                        <img src="grfx/img/bs.png" class="card-img-top" alt="Business Standard Logo">
                        <div class="card-body">
                            <h5 class="card-title">Business Standard</h5>
                            <a href="featured_channels.php?rss_id=3" class="btn btn-primary">Read</a>
                        </div>
                    </div>
                    <div class="card item-card">
                        <img src="grfx/img/theguardian.png" class="card-img-top" alt="Business Gyan Logo">
                        <div class="card-body">
                            <h5 class="card-title">The Guardian</h5>
                            <a href="featured_channels.php?rss_id=37" class="btn btn-primary">Read</a>
                        </div>
                    </div>
                </div>
            </div>


            <? display_my_channel($gUserId) ?>


            <!-- Featured Topics Category -->
            <div class="category" id=featuredtopics>
                <h2 class="category-title">Featured Topics</h2>
                <div class="scroll-row">
                    <!-- Repeat Item Cards as Needed -->
                    <div class="card item-card">
                        <img src="grfx/img/Sports.png" class="card-img-top" alt="Channel 1">
                        <div class="card-body">
                            <h5 class="card-title">Sports News</h5>
                            <a href="featured_topics.php?ft_id=21" class="btn btn-primary">Read</a>
                        </div>
                    </div>
                    <div class="card item-card">
                        <img src="grfx/img/Technology.png" class="card-img-top" alt="Channel 10">
                        <div class="card-body">
                            <h5 class="card-title">Technology</h5>
                            <a href="featured_topics.php?ft_id=13" class="btn btn-primary">Read</a>
                        </div>
                    </div>
                    <div class="card item-card">
                        <img src="grfx/img/Real Estate.png" class="card-img-top" alt="Channel 9">
                        <div class="card-body">
                            <h5 class="card-title">Real Estate</h5>
                            <a href="featured_topics.php?ft_id=10" class="btn btn-primary">Read</a>
                        </div>
                    </div>
                    <div class="card item-card">
                        <img src="grfx/img/educations.png" class="card-img-top" alt="Channel 2">
                        <div class="card-body">
                            <h5 class="card-title">Education & Learning</h5>
                            <a href="featured_topics.php?ft_id=3" class="btn btn-primary">Read</a>
                        </div>
                    </div>
                    <div class="card item-card">
                        <img src="grfx/img/stocks.png" class="card-img-top" alt="Channel 3">
                        <div class="card-body">
                            <h5 class="card-title">Trading</h5>
                            <a href="featured_topics.php?ft_id=10" class="btn btn-primary">Read</a>
                        </div>
                    </div>
                    <div class="card item-card">
                        <img src="grfx/img/Entertainment.png" class="card-img-top" alt="Channel 4">
                        <div class="card-body">
                            <h5 class="card-title">Entertainment</h5>
                            <a href="featured_topics.php?ft_id=16" class="btn btn-primary">Read</a>
                        </div>
                    </div>
                    <div class="card item-card">
                        <img src="grfx/img/Cultural Events.png" class="card-img-top" alt="Channel 5">
                        <div class="card-body">
                            <h5 class="card-title">Cultural Events</h5>
                            <a href="featured_topics.php?ft_id=12" class="btn btn-primary">Read</a>
                        </div>
                    </div>
                    <div class="card item-card">
                        <img src="grfx/img/Political Developments.png" class="card-img-top" alt="Channel 6">
                        <div class="card-body">
                            <h5 class="card-title">Political Developments</h5>
                            <a href="featured_topics.php?ft_id=18" class="btn btn-primary">Read</a>
                        </div>
                    </div>

                </div>
            </div>


            <!-- Feeds Category -->
            <div class="category" id="curatedfeeds">
                <h2 class="category-title">
                    Curated Feeds
                    <i class="fas fa-info-circle info-icon" title="View Your Curated Feeds Using Knobly"></i>
                </h2>
                <div class="scroll-row">

                    <?php
                    $rss_id = 9;
                    // Function to limit title to 10 words
                    function limitWords($text, $wordLimit)
                    {
                        $words = explode(' ', $text);
                        return implode(' ', array_slice($words, 0, $wordLimit));
                    }
                    // Query to fetch news data
                    $stmt = $readerdb->prepare("SELECT rfa.id, rfu.rss_publisher, rfa.url, rfa.title, rfa.description, rfa.image, rfa.date ,rfu.rss_image 
        FROM rss_feeds_articles rfa 
        INNER JOIN rss_feeds_url rfu ON rfa.feed_id = rfu.rss_id 
        WHERE rfu.rss_id = ? 
        ORDER BY rfa.date DESC LIMIT 25");

                    $stmt->bind_param("i", $rss_id);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    if ($result->num_rows > 0) {
                        // Output data of each row

                        while ($row = $result->fetch_assoc()) {
                            $feedId = stripslashes($row['id']);
                            $title = htmlspecialchars(strip_tags(stripslashes($row['title'])));
                            $title = limitWords($title, 2);
                            $description = htmlspecialchars(strip_tags(stripslashes($row['description'])));
                            $date = htmlspecialchars(strip_tags(stripslashes($row['date'])));

                            // Determine image source
                            $image = is_null($row['image']) || $row['image'] === ''
                                ? (is_null($row['rss_image']) || $row['rss_image'] === '' ? '' : $row['rss_image'])
                                : $row['image'];

                            // Skip card if image is invalid or empty
                            if (empty($image)) {
                                continue;
                            }

                            // Validate image URL
                            $imageInfo = @getimagesize($image);
                            if ($imageInfo === false) {
                                continue;
                            }
                    ?>
                            <div class="card item-card">
                                <img src="<?= htmlspecialchars($image) ?>" class="card-img-top" alt="<?= $title ?>">
                                <div class="card-body">
                                    <h5 class="card-title"><?= $title ?></h5>
                                    <a href="#"
                                        class="btn btn-primary read-more"
                                        data-id="<?= htmlspecialchars($row['id']) ?>"
                                        data-title="<?= htmlspecialchars($row['title']) ?>"
                                        data-description="<?= $description ?>"
                                        data-image="<?= htmlspecialchars($image) ?>"
                                        data-date="<?= htmlspecialchars($date) ?>"
                                        data-url="<?= htmlspecialchars($row['url']) ?>">
                                        Read
                                    </a>
                                </div>
                            </div>
                    <?php
                        }
                    } else {
                        echo '<p>No articles found.</p>';
                    }

                    // Close the prepared statement and the database connection
                    $stmt->close();
                    ?>
                </div>
            </div>

            <div class="modal fade" id="newsModal" tabindex="-1" aria-labelledby="newsModalLabel" aria-hidden="true">
                <div class="modal-dialog" style="margin-top:100px">
                    <div class="modal-content">
                        <div class="modal-header w-full flex justify-between items-center gap-4">
                            <h5 class="model-title " id="newsModalLabel"></h5>
                            <button type="button" class="btn-close w-full w-[1/5] flex items-center justify-center color-red" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <img src="" id="modal-image" class="img-fluid mb-3" alt="News Image">
                            <p id="modal-description"></p>
                            <p class="datewithtime"><strong>Date: </strong><span id="modal-date"></span></p>
                            <div class="morewithlike d-flex justify-content-between">
                                <a href="" target="_blank" id="modal-url" class="btn btn-primary">Read More</a>
                                <div id="dataModal" data-id="" data-title="" data-description="" data-url=""></div>
                                <div class="data col-12 col-md-6 text-md-right pl-0 mt-2 mt-md-0 d-flex ">
                                    <button class="btn reader-button likeButton">
                                        <i id="thumbsUp" class="fa-regular fa-thumbs-up" style="padding-right: 4px; padding-top: 2px;"></i>
                                        <div id="likeCount"></div>
                                    </button>
                                    </button>
                                    <button class="btn p-2  reader-button icon-container" style="margin-bottom: 0px;">
                                        <i class="far fa-bookmark" id="bookmarkIcon"></i>
                                    </button>
                                    <button class="btn p-2 reader-button  copyButton">
                                        <i class="fa-solid fa-arrow-up-from-bracket"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <? my_channel($gUserId) ?>
            <a href="add_channel.php" class="btn btn-secondary mt-2">
                Add Channel
            </a>

            <? my_newspapers($gUserId) ?>
            <a href="add_newspaper.php" class="btn btn-secondary mt-2">
                Add Newspaper
            </a>
        </div>
    </div>
    <? include "inc/php/footer.php"; ?>

</body>

</html>