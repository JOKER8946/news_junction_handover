<?php
require_once './inc/php/db_config.php';
require_once './inc/php/validate.logged.php';
require_once './inc/php/function.php';


function build_article_url($artId, $artTitle, $subdomain)
{
    $collectionLink = '/view/' . $artId . '/' . createArticleURL($artTitle);
    if (isset($subdomain)) {
        $collectionLinkFull = 'https://newsjunction.net' . $collectionLink;
    } else {
        $collectionLinkFull = 'https://newsjunction.net' . $collectionLink;
    }
    return $collectionLinkFull;
}

function createArticleURL($title)
{
    if ($title <> '') {
        $title = str_replace(' ', '-', $title);
        $title = str_replace('%', '', $title);
        $title = str_replace("'", "", $title);
        return $title;
    } else {
        return '';
    }
}

function showInfluencerBio($creamdb, $userId)
{
    $sql = "SELECT influencer_about FROM user WHERE id = ?";
    $about = "";

    if ($stmt = $creamdb->prepare($sql)) {
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stmt->bind_result($about);
        if ($stmt->fetch()) {
            return $about;
        } else {
            return null;
        }
        $stmt->close();
    } else {
        return "Error preparing statement: " . $creamdb->error;
    }
}

function showOpinionNum($creamdb, $userId)
{
    $sql = "SELECT COUNT(*) AS count FROM user_collection WHERE user_id = $userId";
    $result = $creamdb->query($sql);
    $data = $result->fetch_assoc();
    return $data['count'];
}

function check_influencer($creamdb, $userId)
{
    $sql = "SELECT is_influencer FROM user WHERE id = $userId";
    $result = $creamdb->query($sql);

    if (!$result) {
        // Handle query failure
        error_log("Database query failed: " . $creamdb->error);
        return false;
    }

    $data = $result->fetch_assoc();
    if ($data['is_influencer'] == 1) {
        return true;
    } else {
        return false;
    }
}

function build_image_url($data)
{
    return "https://newsjunction.net/data/covers/" . $data;
}

function show_influencer_articles($creamdb, $userId)
{

    if (check_influencer($creamdb, $userId)) {
        $sql = "SELECT uc.id, uc.url, uc.title, uc.description, uc.cover_img, uc.date_added, u.subdomain
        FROM user_collection uc 
        INNER JOIN user_landing ul ON ul.article_id = uc.id 
        INNER JOIN user u ON u.id = uc.user_id
        WHERE uc.user_id = $userId ORDER BY ul.id DESC";
        $result = $creamdb->query($sql);

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) { ?>
                <div class="col-md-4">
                    <div class="card mb-4 news-item card-content">
                        <div class="card-body">
                            <?php if (!empty($row['cover_img'])) { ?>
                                <img src="<?= build_image_url($row['cover_img']) ?>" alt="Cover Image" class="img-fluid">
                            <?php } ?>
                            <h5 class="card-title"><?= htmlspecialchars($row['title']) ?></h5>
                            <?php
                            // Get the description and limit it to 50 words
                            $description = strip_tags($row['description']); // Remove HTML tags
                            $description = trim($description); // Trim leading/trailing spaces
                            $description = preg_replace('/\s+/', ' ', $description); // Replace multiple spaces with a single space

                            // Split the description into words
                            $descriptionWords = explode(' ', $description);

                            // Check if there are more than 50 words
                            if (count($descriptionWords) > 50) {
                                // Limit to the first 50 words and append "..."
                                $description = implode(' ', array_slice($descriptionWords, 0, 50)) . '...';
                            } else {
                                // If there are 50 or fewer words, use the whole description
                                $description = implode(' ', $descriptionWords);
                            }
                            ?>
                            <p class="card-text"><?= $description ?></p>
                            <p class="card-text"><strong>Date Added: </strong><?= htmlspecialchars($row['date_added']) ?></p>
                            <a href="<?= build_article_url($row['id'], $row['title'], $row['subdomain']) ?>" target="_blank">Read More</a>
                        </div>
                    </div>
                </div>
            <?
            }
        } else {
            ?>
            <p style="text-align: center;">No data available.</p>
        <?
        }
    } else {
        ?>
        <p style="text-align: center;">User is not an influencer.</p>
<?
    }
}

if (isset($_GET['infId'])) {
    $userId = $_GET['infId'];
    if (!(check_influencer($creamdb, $userId))) {
        http_response_code(500);
        exit();
    }
} else {
    // Set HTTP response code to 500 (Internal Server Error)
    http_response_code(500);
    // Optionally, you can add a message or log the error
    // echo "Internal Server Error: infId is missing.";
    // Stop further script execution
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Influencer Data</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.0/font/bootstrap-icons.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <!-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"> -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet" href="inc/css/styles.css">
    <link rel="stylesheet" href="inc/js/scripts.js">
    <link rel="stylesheet" href="inc/css/social.css">
    <link rel="icon" type="image/x-icon" href="../grfx/img/logo.ico">

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <!-- Bootstrap CSS -->
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <!-- <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet"> -->


    <style>
        .card,
        .modal-content {
            background-color: #1c1c1c !important;
            color: white !important;
            border: none;
            margin-bottom: 20px;
            transition: transform 0.3s;
        }


        .card:hover {
            transform: scale(1.05);
        }

        .card img {
            max-height: 200px;
            object-fit: cover;
        }

        .modal-content {
            background-color: #1c1c1c;
            color: white;
        }


        .modal-body {
            padding: 20px;
        }

        .morewithlike i {
            color: #a3a2a2;
            border: none;
            padding: 0;
        }

        .morewithlike button::after {
            border-color: pink;
            /* Ensure no outline when focused */
            border: none;
            outline: none;

        }

        .profile_card {
            padding: 20px 20px;
            color: var(--text-primary);
            background-color: var(--bg-card);
            min-height: max-content;
            gap: 30px
        }

        .news-item {
            padding: 0px;
        }

        .card-body {
            color: var(--text-primary) !important;
            background-color: var(--bg-card);
            min-height: 275px;
            margin-top: 10px;
        }


        #news-container .text-white {
            --bs-text-opacity: 1;
        }

        .modal-content {
            background-color: var(--bg-color-light) !important;
            color: var(--bg-color-dark) !important;
            border: none;
            margin-bottom: 20px;
            transition: transform 0.3s;
        }

        .opinion_count {
            font-size: 30px;
            height: auto;
        }

        .opinion-text {
            display: flex;
            align-items: end;
            padding: 0 2px;
            margin-bottom: 0 !important;
        }
    </style>


</head>

<body>


    <? include 'inc/php/social_navbar.php' ?>
    <div class="search-main-content main-content">
        <? include 'inc/php/social_sidebar.php' ?>
        <div class=" containers">
            <div>
                <div class=" profile_card d-flex align-items-center" style="width: 100%; ">
                    <!-- Profile Image (1/4 width) -->
                    <div class="avatar me-2" style="width: 100px; height:auto">
                        <a href="profile.php?userId=<?= $userId ?>">
                            <img src="<?= viewProfilePic($creamdb, $userId) ?>" alt="Default Image" class="img-fluid rounded-circle" onerror="this.onerror=null; this.src='/data/profilePic/default.png';">
                        </a>
                    </div>

                    <!-- Profile Info (3/4 width) -->
                    <div class="opinion_nameWithBio" style="width: 75%;">
                        <h3><?= showUserName($creamdb, $userId); ?></h3>
                        <p class="card-text"><?= showInfluencerBio($creamdb, $userId) ?></p>
                    </div>
                    <div class="Total_Opinions" style="width:fit-content; display:flex; flex-direction: column; align-items: center;">
                        <span class="opinion_count" id="totalOpinionsCount"><?= showOpinionNum($creamdb, $userId) ?></span>
                        <p class="opinion-text">Opinion</p>
                    </div>

                </div>

            </div>

            <!-- <h1>Influencer Data</h1> -->
            <div class="card-container row" id="news-container">

                <? show_influencer_articles($creamdb, $userId) ?>

            </div>
        </div>

    </div>
    <? include 'inc/php/footer.php' ?>

</body>

</html>