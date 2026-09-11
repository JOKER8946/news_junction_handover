<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trending News</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/js/scripts.js">
    <style>
        .card {
            background-color: #1c1c1c;
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
        .morewithlike i{
            color: #a3a2a2;
            border: none;
            padding: 0;
        }
        .morewithlike button::after {
            border-color: pink; /* Ensure no outline when focused */
            border: none ;
            outline: none;

        }
        .modal-title{
            max-width: 88%;
        }
    </style>
</head>

<body>
    <!-- Navbar -->
    <?php
    include 'navbar.php'
    ?>



    <!-- 
        <div class="container mt-5">
        <div class="card" style="width: 18rem;">
            <img src="https://via.placeholder.com/800x600" class="card-img-top" alt="Placeholder image">
            <div class="card-body">
                <h5 class="card-title">Politics: Election Results Are In</h5>
                <p class="card-text">The latest updates from the recent elections.</p>
            </div>
        </div>
    </div> -->



    <div class="container mt-4">
        <h1 class="text-center mb-4">Top Trending News</h1>
        <div class="row" id="news-container">
            <?php
            include 'db_connect.php';

            $rss_id = 83;

            // Query to fetch news data
            // $query = "SELECT title, description, image_url FROM news LIMIT 6"; // Assuming your table is named 'news'
            $query = "SELECT rfa.url, rfa.title, rfa.description, rfa.image, rfa.date FROM rss_feeds_articles rfa INNER JOIN rss_feeds_url rfu ON rfa.feed_id = rfu.rss_id WHERE rfu.rss_id = $rss_id ";
            // $query = "SELECT rfa.rss_url, rfa.rss_title, rfa.rss_description, rfa.rss_image FROM rss_feeds_articles rfa  INNER JOIN rss_feeds_url rfu ON rfa.id  = rfu.rss_id  where rfa.rss_id = $rss_id";
            $result = $conn->query($query);

            if ($result->num_rows > 0) {
                // Output data of each row
                while ($row = $result->fetch_assoc()) {
                    echo '
                <div class="col-md-4">
                    <div class="card mb-4">
                        <img src="' . $row['image'] . '" class="card-img-top" alt="News Image">
                        <div class="card-body">
                            <h5 class="card-title">' . $row['title'] . '</h5>
                            
                            <div class="morewithlike d-flex justify-content-between">
                                <a href="' . htmlspecialchars($row['url']) . '" class="btn btn-primary">Read More</a>
                            <div class="data col-12 col-md-6 text-md-right pl-0 mt-2 mt-md-0 d-flex ">
                              <button class="btn  p-2 reader-button play-button" data-title="ICICI+Securities+slides+over+3%25+as+NCLT+approves+delisting+plans%3B+details" data-description="ICICI+Securities+share+price+dipped+as+much+as+3.42+per+cent+to+Rs+837.65+per+share+on+the+BSE+in+Thursday%27s+early+morning+trade">
                                   <i class="fas fa-volume-up"></i>
                              </button>
                              <button class="btn  p-2 reader-button pause-button" style="display:none;">
                                   <i class="fas fa-pause"></i>
                              </button>
                              <button class="btn  p-2 reader-button resume-button" style="display:none;">
                                   <i class="fas fa-play"></i>
                              </button>
                              <button class="btn p-2 reader-button stop-button" style="display:none;">
                                   <i class="fas fa-stop"></i>
                              </button>
                              <button class="btn  p-2 reader-button likeButton">
                                   <i id="thumbsUp" class="fa-regular fa-thumbs-up" style="padding-right: 4px; padding-top: 2px;"></i>
                                   <div id="likeCount"></div>
                              </button>
                              <button class="btn p-2 reader-button comments" >
                                   <i class="fa-regular fa-comments"></i>
                              </button>
                              <button class="btn p-2  reader-button icon-container" style="margin-bottom: 0px;">
                                   <i class="far fa-bookmark" id="bookmarkIcon"></i>
                              </button>
                              <button class="btn p-2 reader-button" >
                                   <i class="fa-solid fa-arrow-up-from-bracket"></i>
                              </button>
                         </div>

                            </div>
                        </div>

                        
                    </div>
                </div>';
                }
            } else {
                echo '<p>No news available at the moment.</p>';
            }

            // Close the database connection
            $conn->close();
            ?>
        </div>
    </div>





    <!-- Footer -->
    <div class="footer">
        <p>&copy; 2024 MyMagazine. All rights reserved.</p>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    
</body>

</html>