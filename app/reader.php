<?php
require_once './assets/php/validate.logged.php';
include './assets/php/function.php';
include './inc/config.php';
include './assets/php/db_config.php';


function display_my_feeds($conn, $userId)
{
    // Prepare the query
    $stmt = $conn->prepare("SELECT rfa.id, rfu.rss_publisher, rfa.url, rfa.title, rfa.description, rfa.image, rfa.date 
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
        <!-- <div class="category">
            <h2 class="category-title">My Collection</h2>
            <div class="scroll-row">
                <?
                // Output data of each row
                while ($row = $result->fetch_assoc()) {
                    $feedId = stripslashes($row['id']);
                    $title = htmlspecialchars(strip_tags(stripslashes($row['title'])));
                    $description = htmlspecialchars(strip_tags(stripslashes($row['description'])));
                    // $date = htmlspecialchars(strip_tags(stripslashes($row['date'])));
                    $image = is_null($row['image']) || $row['image'] === '' ? 'inc/img/toi.png' : $row['image'];
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
        </div> -->
    <?
    }
    // Close the prepared statement and the database connection
    $stmt->close();
}
function build_image_url($type, $data)
{
    return "https://newsjunction.net/data/" . $type . "/" . $data;
}

function display_opinions($db)
{
    $stmt = $db->prepare("SELECT id, full_name, profile_pic FROM user WHERE is_influencer=1");
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
    ?>
        <div class="category">
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

    // Fetch the user's channel details
    $stmt = $readerdb->prepare("
        SELECT id, name, profilePic 
        FROM channels
    ");
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) { ?>
        <div class="category">
            <h2 class="category-title">User Channels</h2>
            <div class="scroll-row">
                <?php while ($row = $result->fetch_assoc()) { ?>
                    <div class="card item-card">
                        <img src="<?= build_image_url('channelPic', $row['profilePic']) ?>" class="card-img-top">
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($row['name']) ?></h5>
                            <a href="channel.php?channelId=<?= $row['id'] ?>&channelName=<?= $row['name'] ?>" class="btn btn-primary">Visit Channel</a>
                        </div>
                    </div>
                <?php } ?>
            </div>
        </div>
    <?php
    } else {
        echo "<p>No channel found.</p>";
    }
}

function my_channel($user_id)
{
    global $readerdb;

    // Fetch the user's channel details
    $stmt = $readerdb->prepare("SELECT id, name, profilePic 
    FROM channels
    WHERE created_by = $user_id;
    ");
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) { ?>
        <div class="category">
            <!-- <h2 class="category-title">My Channels</h2> -->
            <div class="scroll-row">
                <?php while ($row = $result->fetch_assoc()) { ?>
                    <div class="card item-card">
                        <img src="<?= build_image_url('channelPic', $row['profilePic']) ?>" class="card-img-top">
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($row['name']) ?></h5>
                            <a href="channel.php?channelId=<?= $row['id'] ?>&channelName=<?= $row['name'] ?>" class="btn btn-primary">Visit Channel</a>
                        </div>
                    </div>
                <?php } ?>
            </div>
        </div>
    <?php
    } 
}


// Function to fetch mixed articles from multiple RSS feeds
function display_mixed_featured_articles($readerdb, $rss_ids, $total_limit = 50)
{
    if (empty($rss_ids) || !is_array($rss_ids)) {
        echo '<p>Error: No RSS IDs provided.</p>';
        return;
    }

    // Create a comma-separated string of RSS IDs for the query
    $ids_string = implode(',', $rss_ids);

    // Prepare the query to fetch articles from multiple feeds, ordered by date
    $query = "SELECT rfa.id, rfu.rss_publisher, rfa.url, rfa.title, rfa.description, 
                     rfa.image AS articleImage, rfu.rss_image AS rssImage, rfa.date, rfu.rss_id
              FROM rss_feeds_articles rfa 
              INNER JOIN rss_feeds_url rfu ON rfa.feed_id = rfu.rss_id 
              WHERE rfu.rss_id IN ($ids_string) 
              ORDER BY rfa.date DESC LIMIT ?";

    $stmt = $readerdb->prepare($query);
    $stmt->bind_param("i", $total_limit);
    $stmt->execute();
    $result = $stmt->get_result();
    ?>

    <div class="container mt-4">
        <!-- <h1 class="text-center text-white mb-4">Featured News</h1> -->
        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 " id="mixed-news-container">
            <?php
            if ($result->num_rows > 0) {
                // Output data of each row
                while ($row = $result->fetch_assoc()) {
                    $feedId = stripslashes($row['id']);
                    $title = htmlspecialchars(strip_tags(stripslashes($row['title'])));
                    $description = htmlspecialchars(strip_tags(stripslashes($row['description'])));
                    $image = is_null($row['articleImage']) || $row['articleImage'] === '' ?
                        (is_null($row['rssImage']) || $row['rssImage'] === '' ? '' : ($row['rssImage'])) :
                        $row['articleImage'];
                    $date = htmlspecialchars(strip_tags(stripslashes($row['date'])));
                    $rss_id = $row['rss_id'];
                    $publisher = htmlspecialchars($row['rss_publisher']);

                    // Apply special image handling for certain sources
                    if ($rss_id == 37) { // The Guardian
                        $image = guardianImg($image);
                    }
            ?>
                    <div class=" mb-4" style="box-shadow: 0 0 5px rgba(0, 0, 0, 0.1); border-radius: 10px; display:flex; gap:4px; ">
                        <div class="card news-item"
                            data-id="<?= htmlspecialchars($feedId) ?>"
                            data-title="<?= $title ?>"
                            data-description="<?= $description ?>"
                            data-image="<?= htmlspecialchars($image) ?>"
                            data-url="<?= htmlspecialchars($row['url']) ?>"
                            data-date="<?= $date ?>">
                            <img src="<?= htmlspecialchars($image) ?>" class="card-img-top" alt="News Image">
                            <div class="card-body">
                                <h5 class="card-title"><?= $title ?></h5>
                                <p class="card-text"><small class="text-muted">Source: <?= $publisher ?></small></p>
                                <p class="card-text"><small class="text-muted">Date: <?= $date ?></small></p>
                            </div>
                        </div>
                    </div>
                <?php
                }
            } else {
                ?>
                <p class="text-white">No news available at the moment.</p>
            <?php
            }
            ?>
        </div>
    </div>
<?php
    // Close the prepared statement
    $stmt->close();
}


function fetch_all_articles($conn)
{
    // Prepare the query without category filtering
    $stmt = $conn->prepare("SELECT rfa.id, rfu.rss_publisher, rfa.url, rfa.title, rfa.description, rfa.image, rfa.date 
                   FROM rss_feeds_articles rfa 
                   INNER JOIN rss_feeds_url rfu ON rfa.feed_id = rfu.rss_id 
                   ORDER BY rfa.date DESC
                   LIMIT 50"); // Limiting to prevent overwhelming results

    $stmt->execute();
    $result = $stmt->get_result(); ?>
    <!-- <h1 class="text-center mb-4 text-white">Featured Topics</h1> -->
    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">
        <? if ($result->num_rows > 0) {
            // Output data of each row
            while ($row = $result->fetch_assoc()) {
                $feedId = stripslashes($row['id']);
                $title = htmlspecialchars(strip_tags(stripslashes($row['title'])));
                $description = htmlspecialchars(strip_tags(stripslashes($row['description'])));
                $image = is_null($row['image']) || $row['image'] === '' ? 'inc/img/toi.png' : $row['image'];
                $date = htmlspecialchars(strip_tags(stripslashes($row['date'])));
                $publisher = htmlspecialchars(stripslashes($row['rss_publisher']));
        ?>
                <div class="col feature-topic-item" style="box-shadow: 0 0 5px rgba(0, 0, 0, 0.1); border-radius: 10px; gap:4px">
                    <div class="card h-100 feature-topic-card"
                        data-id="<?= htmlspecialchars($feedId) ?>"
                        data-title="<?= $title ?>"
                        data-description="<?= $description ?>"
                        data-image="<?= htmlspecialchars($image) ?>"
                        data-url="<?= htmlspecialchars($row['url']) ?>"
                        data-date="<?= $date ?>"
                        data-publisher="<?= $publisher ?>">
                        <div class="card-img-container" style="height: 180px; overflow: hidden;">
                            <img src="<?= htmlspecialchars($image) ?>" class="card-img-top" alt="News Image" style="object-fit: cover; height: 100%; width: 100%;">
                        </div>
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title"><?= $title ?></h5>
                            <div class="mt-auto">
                                <p class="card-text mb-1 text-muted"><small><?= $publisher ?></small></p>
                                <p class="card-text"><small class="text-muted"><?= $date ?></small></p>
                            </div>
                        </div>
                    </div>
                </div>
            <?php }
        } else {
            ?>
            <div class="col-12">
                <p>No news available at the moment.</p>
            </div>
        <?
        }
        ?>
    </div>

    <!-- Feature Topics Modal -->
    <div class="modal fade" id="featureTopicsModal" tabindex="-1" aria-labelledby="featureTopicsModalLabel" aria-hidden="true" style="top:100px">
        <div class="modal-dialog" style="margin-bottom: 110px;">
            <div class="modal-content">
                <div class="modal-header w-full flex justify-between items-center gap-4">
                    <h5 class="model-title" id="featureTopicsModalLabel"></h5>
                    <button type="button" class="btn-close w-full w-[1/5] flex items-center justify-center color-red" data-bs-dismiss="modal" aria-label="Close" style="background-color: #a3a2a2;"></button>
                </div>
                <div class="modal-body">
                    <img src="" id="ft-modal-image" class="img-fluid mb-3" alt="News Image">
                    <p id="ft-modal-description"></p>
                    <p class="datewithtime"><strong>Date: </strong><span id="ft-modal-date"></span></p>
                    <p class="publisherwithname"><strong>Publisher: </strong><span id="ft-modal-publisher"></span></p>
                    <div class="morewithlike d-flex justify-content-between">
                        <a href="" id="ft-modal-url" class="btn btn-primary">Read More</a>
                        <div id="ft-dataModal" data-id="" data-title="" data-description="" data-url=""></div>
                        <div class="data col-12 col-md-6 text-md-right pl-0 mt-2 mt-md-0 d-flex ">
                            <button class="btn p-2 reader-button ft-pause-button" style="display:none;">
                                <i class="fas fa-pause"></i>
                            </button>
                            <button class="btn p-2 reader-button ft-resume-button" style="display:none;">
                                <i class="fas fa-play"></i>
                            </button>
                            <button class="btn p-2 reader-button ft-stop-button" style="display:none;">
                                <i class="fas fa-stop"></i>
                            </button>
                            <button class="btn reader-button ft-likeButton">
                                <i id="ft-thumbsUp" class="fa-regular fa-thumbs-up" style="padding-right: 4px; padding-top: 2px;"></i>
                                <div id="ft-likeCount"></div>
                            </button>
                            <button class="btn p-2 reader-button icon-container" style="margin-bottom: 0px;">
                                <i class="far fa-bookmark" id="ft-bookmarkIcon"></i>
                            </button>
                            <button class="btn p-2 reader-button ft-copyButton">
                                <i class="fa-solid fa-arrow-up-from-bracket"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?
    // Close the prepared statement
    $stmt->close();
}
?>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>News Junction</title>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">

    <!-- Bootstrap CSS -->
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="assets/js/script.js">
    <link rel="stylesheet" href="assets/css/styles.css">

    <!-- Custom CSS -->
    <style>
        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            color: #e50914 !important;
        }

        .navbar-nav .nav-link {
            color: #bbb !important;
            transition: color 0.3s;
        }

        .navbar-nav .nav-link:hover {
            color: #fff !important;
        }

        .navbar {
            background-color: #141414;
            padding: 8px;
        }


        /* Container Padding */
        @media screen and (max-width: 768px) {
            .container-fluid {
                padding: 0px 0 !important;
                max-width: 1400px;
                margin: 0 auto;

            }

        }


        .category {
            margin-bottom: 2rem;
        }

        .category-title {
            margin-bottom: 2rem;
            font-size: 1.75rem;
            font-weight: 700;
            text-align: center;
        }

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
            flex-wrap: wrap;
            overflow-x: auto;
            gap: 10px;
            padding: 15px 70px;
        }

        .item-card {
            flex: 0 0 auto;
            max-width: 200px;
            min-width: 200px !important;
            margin-right: 0.5rem;
            background-color: #1c1c1c !important;
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

        .card-body {
            background-color: #fff;
        }

        .card-title {
            color: #141414 !important;
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
            left: 50%;
            margin-left: -60px;
            opacity: 0;
            transition: opacity 0.3s;
        }

        .tooltip:hover .tooltiptext {
            visibility: visible;
            opacity: 1;
        }

        .action-card {
            height: 220px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            text-align: center;
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

        p {
            color: black;
        }



        .modal-body {
            padding: 20px;
        }



        .morewithlike button::after {
            border-color: pink;
            border: none;
            outline: none;

        }

        .modal-title {
            max-width: 88%;
        }

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

            .scroll-row {
                display: flex;
                flex-wrap: wrap;
                justify-content: center;
                overflow-x: auto;
                gap: 8px;
                padding-bottom: 10px;
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
    </style>
    <style>
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
    </style>


    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/5.3.0/js/bootstrap.min.js"></script>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->

    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
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


            // Add click handler for news-item cards
            $(document).on('click', '.news-item', function() {
                const feedId = $(this).data('id');
                const title = $(this).data('title');
                const description = $(this).data('description');
                const image = $(this).data('image');
                const url = $(this).data('url');
                const date = $(this).data('date');

                // Populate modal with data
                $('#dataModal').attr('data-id', feedId);
                $('#dataModal').attr('data-title', title);
                $('#newsModalLabel').text(title);
                $('#modal-description').text(description);
                $('#modal-image').attr('src', image);
                $('#modal-url').attr('href', url);
                $('#modal-date').text(date);

                // Check if the article is in collection
                checkCollection(feedId, userId);
                loadLike(feedId, userId);
                likeCount(feedId, userId);

                // Show the modal
                $('#newsModal').modal('show');
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

    <style>
        .sideWithMainContainer {
            display: flex;
            flex-direction: row;
            gap: 10px;
            overflow-x: hidden;

        }

        .sideMaincontent {
            height: 85vh;
            overflow-y: scroll;
            padding: 0px 0px;
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

    <style>
        /* Tab styling */
        .tabs-container {
            width: 100%;
            position: sticky;
            top: 0;
            z-index: 100;
            background-color: #fff;
            margin-bottom: 20px;
            padding: 10px 0;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .nav-tabs {
            display: flex;
            flex-wrap: nowrap;
            overflow-x: auto;
            padding: 0 10px;
            gap: 8px;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: none;
            border-bottom: none !important;
            /* Firefox */
        }

        .nav-tabs::-webkit-scrollbar {
            display: none;
            /* Safari and Chrome */
        }

        .tab-button {
            flex: 0 0 auto;
            padding: 8px 15px;
            background-color: rgb(231, 228, 228);
            color: #141414;
            border: none;
            border-radius: 20px;
            font-size: 14px;
            cursor: pointer;
            white-space: nowrap;
            transition: all 0.3s ease;
        }

        .tab-button.active {
            background-color: #db5919;
            /* Netflix Red */
            color: black;
            font-weight: 500;
        }

        .tab-button:hover:not(.active) {
            background-color: #333;
            color: white;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .tabs-container {
                padding: 8px 0;
            }

            .tab-button {
                padding: 6px 12px;
                font-size: 12px;
            }
        }

        /* Style for read button to match the tab design */
        .read-more.btn {
            background-color: #e50914;
            border-color: #e50914;
            transition: all 0.3s ease;
        }

        .read-more.btn:hover {
            background-color: #f40612;
            border-color: #f40612;
            transform: translateY(-2px);
        }

        .modal-content {
            background-color: white !important;
            color: black !important;
            border: none;
            margin-bottom: 20px;
            transition: transform 0.3s;
        }
    </style>

</head>

<body>
    <!-- Navbar -->
    <? include 'assets/php/social_navbar.php' ?>

    <!-- Keep the modal for viewing articles -->
    <div class="modal fade" id="newsModal" tabindex="-1" aria-labelledby="newsModalLabel" aria-hidden="true" style="top:100px">
        <div class="modal-dialog" style="margin-bottom: 110px;">
            <div class="modal-content">
                <div class="modal-header w-full flex justify-between items-center gap-4">
                    <h5 class="model-title" id="newsModalLabel"></h5>
                    <button type="button" class="btn-close w-full w-[1/5] flex items-center justify-center color-red" data-bs-dismiss="modal" aria-label="Close" style="background-color: #a3a2a2;"></button>
                </div>
                <div class="modal-body">
                    <img src="" id="modal-image" class="img-fluid mb-3" alt="News Image">
                    <p id="modal-description"></p>
                    <p class="datewithtime"><strong>Date: </strong><span id="modal-date"></span></p>
                    <div class="morewithlike d-flex justify-content-between">
                        <a href="" id="modal-url" class="btn btn-primary">Read More</a>
                        <div id="dataModal" data-id="" data-title="" data-description="" data-url=""></div>
                        <div class="data col-12 col-md-6 text-md-right pl-0 mt-2 mt-md-0 d-flex ">
                            <button class="btn p-2 reader-button pause-button" style="display:none;">
                                <i class="fas fa-pause"></i>
                            </button>
                            <button class="btn p-2 reader-button resume-button" style="display:none;">
                                <i class="fas fa-play"></i>
                            </button>
                            <button class="btn p-2 reader-button stop-button" style="display:none;">
                                <i class="fas fa-stop"></i>
                            </button>
                            <button class="btn reader-button likeButton">
                                <i id="thumbsUp" class="fa-regular fa-thumbs-up" style="padding-right: 4px; padding-top: 2px;"></i>
                                <div id="likeCount"></div>
                            </button>
                            <button class="btn p-2 reader-button icon-container" style="margin-bottom: 0px;">
                                <i class="far fa-bookmark" id="bookmarkIcon"></i>
                            </button>
                            <button class="btn p-2 reader-button copyButton">
                                <i class="fa-solid fa-arrow-up-from-bracket"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="sideWithMainContainer">
        <div class="col-md-2">
            <? include 'assets/php/sidebar.php' ?>
        </div>
        <div class="container-fluid  col-sm-12 col-md-10 sideMaincontent" style="padding-bottom: 70px;">
            <!-- Add this code right after your navbar and before the content sections -->

            <div class="tabs-container">
                <div class="nav-tabs">
                    <button class="tab-button active" data-category="cream-curated">Cream Curated </button>
                    <button class="tab-button" data-category="featured-channels">Featured Channels</button>
                    <button class="tab-button" data-category="user-channels">User Channels</button>
                    <button class="tab-button" data-category="featured-topics">Featured Topics</button>
                    <button class="tab-button" data-category="opinion">Opinion</button>
                    <button class="tab-button" data-category="my-channels">My Channels</button>
                </div>
            </div>



            <!-- Add this script at the end of your file, before the closing body tag -->
            <script>
                $(document).ready(function() {
                    // Initially hide all sections except the one for cream-curated
                    $(".category").each(function(index) {
                        if (index !== 3) { // Index 3 is currently the cream-curated content
                            $(this).hide();
                        }
                    });

                    // Add data-category attributes to each category section (keep the same order as your HTML)
                    $(".category").eq(0).attr("data-category", "featured-channels");
                    $(".category").eq(1).attr("data-category", "user-channels");
                    $(".category").eq(2).attr("data-category", "featured-topics");
                    $(".category").eq(3).attr("data-category", "cream-curated");
                    $(".category").eq(4).attr("data-category", "opinion");
                    $(".category").eq(5).attr("data-category", "my-channels");

                    // Initially show cream-curated content and hide all others
                    $(".category").hide();
                    $('[data-category="cream-curated"]').show();

                    // Handle tab clicks
                    $(".tab-button").on("click", function() {
                        // Remove active class from all tabs
                        $(".tab-button").removeClass("active");

                        // Add active class to clicked tab
                        $(this).addClass("active");

                        // Get the category to show
                        var categoryToShow = $(this).data("category");

                        // Hide all categories
                        $(".category").hide();

                        // Show selected category
                        $('[data-category="' + categoryToShow + '"]').show();

                        // Scroll to the top of the content area
                        $(".sideMaincontent").scrollTop(0);
                    });
                });
            </script>

            <div class="category container mt-4">
                <!-- <h1 class="text-center text-white mb-4">Featured Channels</h1> -->

                <?php
                $featured_rss_ids = [9, 38, 18, 111, 19, 3];

                // Display mixed articles from all featured channels
                display_mixed_featured_articles($readerdb, $featured_rss_ids, 30);
                ?>


            </div>
            <? display_my_channel($gUserId) ?>

            <div class="category">
                <!-- <h2 class="category-title">Featured Topics</h2> -->
                <?php
                // $featured_rss_ids = [9, 38, 18, 111, 19, 3];

                // Display mixed articles from all featured channels
                fetch_all_articles($readerdb, $featured_rss_ids, 30);
                ?>
                <!-- Modal -->

            </div>
            <div class="category">
                <h2 class="category-title">
                    <!-- Cream Curated Feeds
                    <i class="fas fa-info-circle info-icon" title="View Your Curated Feeds Using Knobly"></i> -->
                </h2>
                <div class="scroll-row ">

                    <?php
                    include 'assets/php/db_connect.php';

                    $rss_id = 9;
                    // Function to limit title to 10 words
                    function limitWords($text, $wordLimit)
                    {
                        $words = explode(' ', $text);
                        return implode(' ', array_slice($words, 0, $wordLimit));
                    }

                    // Query to fetch news data
                    $stmt = $conn->prepare("SELECT rfa.id, rfu.rss_publisher, rfa.url, rfa.title, rfa.description, rfa.image, rfa.date 
                        FROM rss_feeds_articles rfa 
                        INNER JOIN rss_feeds_url rfu ON rfa.feed_id = rfu.rss_id 
                        WHERE rfu.rss_id = ? 
                        ORDER BY rfa.date DESC LIMIT 50");

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

                            // $date = htmlspecialchars(strip_tags(stripslashes($row['date'])));
                            $image = is_null($row['image']) || $row['image'] === '' ? 'inc/img/toi.png' : $row['image'];
                            $date = htmlspecialchars(strip_tags(stripslashes($row['date'])));
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
                    <?
                        }
                    } else {
                        echo '<p>No articles found.</p>';
                    }

                    // Close the prepared statement and the database connection
                    $stmt->close();
                    ?>
                </div>
            </div>


            <? display_opinions($db) ?>

            <? my_channel($gUserId) ?>
            

            <div class="category" data-category="my-channels">
                <? //my_channel($gUserId) ?>
                <a href="add_channel.php" class="btn btn-secondary mt-2">
                    Add Channel
                </a>
            </div>

            <?php display_my_feeds($conn, $gUserId) ?>
        </div>
    </div>


    <script>
        // Add this to your existing document ready function
        $(document).ready(function() {
            // Initialize the feature topics modal
            const featureTopicsModal = new bootstrap.Modal(document.getElementById('featureTopicsModal'));

            // Click handler for featured topics cards
            $(document).on('click', '.feature-topic-card', function() {
                const feedId = $(this).data('id');
                const title = $(this).data('title');
                const description = $(this).data('description');
                const image = $(this).data('image');
                const url = $(this).data('url');
                const date = $(this).data('date');
                const publisher = $(this).data('publisher');

                // Populate modal with data
                $('#ft-dataModal').attr('data-id', feedId);
                $('#ft-dataModal').attr('data-title', title);
                $('#featureTopicsModalLabel').text(title);
                $('#ft-modal-description').text(description);
                $('#ft-modal-image').attr('src', image);
                $('#ft-modal-url').attr('href', url);
                $('#ft-modal-date').text(date);
                $('#ft-modal-publisher').text(publisher);

                // Check if the article is in collection
                ft_checkCollection(feedId, userId);
                ft_loadLike(feedId, userId);
                ft_likeCount(feedId, userId);

                // Show the modal
                featureTopicsModal.show();
            });

            // Feature Topics Like button handler
            $('.ft-likeButton').on('click', function() {
                var feed_id = $('#ft-dataModal').attr('data-id');
                ft_toggleLike(feed_id, userId);
            });

            // Feature Topics Copy button handler
            $('.ft-copyButton').on('click', function() {
                var feed_url = $('#ft-modal-url').attr('href');
                ft_copyToClipboard(feed_url);
            });

            // Feature Topics Bookmark handler
            $('#ft-bookmarkIcon').on('click', function() {
                var feed_id = $('#ft-dataModal').attr('data-id');

                // Check the current state of the icon
                if ($(this).hasClass('far fa-bookmark')) {
                    ft_addToCollection(feed_id, userId);
                } else {
                    ft_removeFromCollection(feed_id, userId);
                }
            });

            // Play button click handler for feature topics
            $('.ft-play-button').on('click', function() {
                var title = $('#ft-dataModal').data('title');
                var desc = $('#ft-dataModal').data('description');
                ft_playAudio(title, desc, $(this));
            });
        });

        // Feature Topics - Function to check collection status
        function ft_checkCollection(feedId, userId) {
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
                                $('#ft-bookmarkIcon').removeClass('far fa-bookmark').addClass('fas fa-bookmark');
                            } else {
                                $('#ft-bookmarkIcon').removeClass('fas fa-bookmark').addClass('far fa-bookmark');
                            }
                        } else {
                            console.error('Failed to check collection: ' + response.message);
                        }
                    } catch (e) {
                        console.error('Parsing error:', e);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX request failed: ' + error);
                }
            });
        }

        // Feature Topics - Function to load like status
        function ft_loadLike(feedId, userId) {
            $.ajax({
                url: 'inc/handler.php',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({
                    request: 'loadLike',
                    userId: userId,
                    feedId: feedId
                }),
                success: function(response) {
                    if (response.status === "success") {
                        if (response.response === true) {
                            $('.ft-likeButton #ft-thumbsUp').removeClass('fa-regular').addClass('fa-solid');
                        } else {
                            $('.ft-likeButton #ft-thumbsUp').removeClass('fa-solid').addClass('fa-regular');
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

        // Feature Topics - Function to get like count
        function ft_likeCount(feedId, userId) {
            $.ajax({
                url: 'inc/handler.php',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({
                    request: 'likeCount',
                    userId: userId,
                    feedId: feedId
                }),
                success: function(response) {
                    if (response.status === "success") {
                        $('.ft-likeButton #ft-likeCount').html(response.count || '');
                    } else {
                        console.error("Error message:", response.message);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error("Error:", textStatus, errorThrown);
                }
            });
        }

        // Feature Topics - Function to toggle like
        function ft_toggleLike(feedId, userId) {
            var thumbsUpIcon = $('.ft-likeButton #ft-thumbsUp');
            var isLiked = thumbsUpIcon.hasClass('fa-solid');
            var requestType = isLiked ? 'unlike' : 'like';

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
                        if (requestType === 'like') {
                            thumbsUpIcon.removeClass('fa-regular').addClass('fa-solid');
                        } else {
                            thumbsUpIcon.removeClass('fa-solid').addClass('fa-regular');
                        }
                        ft_likeCount(feedId, userId);
                    } else {
                        console.error("Error message:", response.message);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error("Error:", textStatus, errorThrown);
                }
            });
        }

        // Feature Topics - Function to copy URL to clipboard
        function ft_copyToClipboard(feedUrl) {
            navigator.clipboard.writeText(feedUrl).then(function() {
                alert('Feed URL copied to clipboard: ' + feedUrl);
            }).catch(function(error) {
                console.error('Error copying text: ', error);
            });
        }

        // Feature Topics - Function to add to collection
        function ft_addToCollection(feedId, userId) {
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
                        $('#ft-bookmarkIcon').removeClass('far fa-bookmark').addClass('fas fa-bookmark');
                    } else {
                        console.error('Failed to add to collection: ' + response.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX request failed: ' + error);
                }
            });
        }

        // Feature Topics - Function to remove from collection
        function ft_removeFromCollection(feedId, userId) {
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
                        $('#ft-bookmarkIcon').removeClass('fas fa-bookmark').addClass('far fa-bookmark');
                    } else {
                        console.error('Failed to remove from collection: ' + response.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX request failed: ' + error);
                }
            });
        }

        // Feature Topics - Function to play audio
        function ft_playAudio(title, desc, button) {
            var audio = new Audio();

            if (isPlaying) {
                console.log("Audio is already playing. Cannot start another instance.");
                return;
            }

            console.log("Starting audio playback...");

            isPlaying = true;

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

                    button.hide();
                    button.siblings('.ft-pause-button').show();
                    button.siblings('.ft-stop-button').show();

                    button.siblings('.ft-pause-button').on('click', function() {
                        audio.pause();
                        $(this).hide();
                        $(this).siblings('.ft-resume-button').show();
                    });

                    button.siblings('.ft-resume-button').on('click', function() {
                        audio.play();
                        $(this).hide();
                        $(this).siblings('.ft-pause-button').show();
                    });

                    button.siblings('.ft-stop-button').on('click', function() {
                        audio.pause();
                        audio.currentTime = 0;
                        button.siblings('.ft-resume-button').hide();
                        button.siblings('.ft-stop-button').hide();
                        button.siblings('.ft-pause-button').hide();
                        button.show();
                        isPlaying = false;
                    });

                    audio.onended = function() {
                        isPlaying = false;
                        button.show();
                        button.siblings('.ft-pause-button').hide();
                        button.siblings('.ft-resume-button').hide();
                        button.siblings('.ft-stop-button').hide();
                    };
                },
                error: function(xhr, status, error) {
                    console.error("Error playing audio:", error);
                }
            });
        }
    </script>


    <? include 'assets/php/bottom_navbar.php' ?>
    <? include 'assets/php/footer.php' ?>
</body>

</html>