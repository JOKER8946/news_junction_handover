    <?php
    // Include necessary files
    include 'inc/php/db_config.php';
    include 'inc/php/validate.logged.php';
    include 'inc/php/function.php';

    // Get the search query from the request (if it's an AJAX request)
    $searchQuery = isset($_GET['query']) ? $_GET['query'] : '';

    $userResults = [];
    $postResults = [];
    $channelResults = [];

    if (!empty($searchQuery)) {
        // SEARCH USERS
        $sqlUser = "SELECT id, full_name, profile_pic FROM user WHERE full_name LIKE ? OR email LIKE ? LIMIT 10";
        $stmtUser = $creamdb->prepare($sqlUser);
        $searchQueryLike =  $searchQuery . '%';
        $stmtUser->bind_param('ss', $searchQueryLike, $searchQueryLike);
        $stmtUser->execute();
        $resultUser = $stmtUser->get_result();

        while ($row = $resultUser->fetch_assoc()) {
            $profileUrl = isset($row['profile_pic']) ? "https://newsjunction.net/data/profilePic/" . $row['profile_pic'] : "https://newsjunction.net/data/profilePic/default.png";
            $userResults[] = [
                'type' => 'user',
                'full_name' => $row['full_name'],
                'img' => $profileUrl,
                'user_id' => $row['id'],
                'url' => "/profile.php?userId=" . $row['id']
            ];
        }

        // SEARCH POSTS
        $sqlPost = "SELECT id, chat, userId FROM reader_stream WHERE chat LIKE ?  LIMIT 10";
        $stmtPost = $readerdb->prepare($sqlPost);
        $searchQueryPost = '%' . $searchQuery . '%';
        $stmtPost->bind_param('s', $searchQueryPost);
        $stmtPost->execute();
        $resultPost = $stmtPost->get_result();

        while ($row = $resultPost->fetch_assoc()) {
            $postResults[] = [
                'type' => 'post',
                'title' => $row['chat'],
                'user_id' => $row['userId'],
                'url' => "/post-details.php?id=" . $row['id']
            ];
        }

        // SEARCH CHANNELS
        $sqlChannel = "SELECT id, name, created_by, profilePic FROM channels WHERE name LIKE ? LIMIT 10";
        $stmtChannel = $readerdb->prepare($sqlChannel);
        $stmtChannel->bind_param('s', $searchQueryLike);
        $stmtChannel->execute();
        $resultChannel = $stmtChannel->get_result();

        while ($row = $resultChannel->fetch_assoc()) {
            $profileUrl = isset($row['profilePic']) ? "https://newsjunction.net/data/channelPic/" . $row['profilePic'] : "https://newsjunction.net/data/profilePic/default.png";
            $channelResults[] = [
                'type' => 'channel',
                'img' => $profileUrl,
                'channel_name' => $row['name'],
                'user_id' => $row['created_by'],
                'url' => "/channel.php?channelId=" . $row['id'] . "&channelName=" . $row['name']
            ];
        }
    }

    // Combine all results
    $response = [
        'users' => $userResults,
        'posts' => $postResults,
        'channels' => $channelResults
    ];

    // Return JSON response for AJAX requests
    if (isset($_GET['query'])) {
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
    ?>

    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Search Page</title>
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <link rel="stylesheet" href="inc/css/stream.css">
        <link rel="stylesheet" href="inc/css/social.css">

        <!-- jQuery -->
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

        <!-- Magnific Popup -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/magnific-popup.js/1.1.0/jquery.magnific-popup.min.js"></script>

        <!-- Bootstrap, Font Awesome, etc. -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
        <link rel="icon" type="image/x-icon" href="../grfx/img/logo.ico">

        <!-- Scripts -->
        <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.min.js" integrity="sha384-OgVRvuATP1z7JjHLkuOU7Xw704+h835Lr+6QL9UvYjZE3Ipu6Tp75j7Bh/kR0JKI" crossorigin="anonymous"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.8.0/Chart.min.js" crossorigin="anonymous"></script>
        <!-- <script src="https://cdn.tiny.cloud/1/kz1jcdrlicpzilnm0x80vemrxz252921vwmb10kytce5n9ez/tinymce/7/tinymce.min.js" referrerpolicy="origin"></script> -->
        <script src="https://cdn.tiny.cloud/1/gg63dftxs904yq8t5rs5qyu8xo1wnzpfo1rflntk3u6ic37t/tinymce/7/tinymce.min.js" referrerpolicy="origin"></script>
        <script src="inc/js/common.js"></script>
        <script src="inc/js/genai_func.js"></script>
    </head>
    <style>
        .search-container {
            width: 80%;
            position: relative;
        }

        .search-bar {
            width: 100%;
            /* max-width: 600px; */
            display: flex;
            justify-content: space-between;
            background-color: var(--bg-card);
            border-radius: 7px;
            padding: 1px 3px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
        }

        .search-bar input {
            border: none;
            background: transparent;
            outline: none;
            width: 85%;
            font-size: 16px;
            padding: 8px;
            border-radius: 20px;
        }

        .search-bar button {
            border: none;
            border-color: none;
            outline: none;
            color: var(--text-primary);
            font-size: 16px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .search-bar button:hover {
            background-color: #007acb;
        }

        #suggestionsList {
            display: none;
            width: 100%;
            background-color: var(--bg-card);
            color: var(--text-primary);
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
            border-radius: 7px;
            padding: 10px;
            position: absolute;
            z-index: 10;
            height: 80vh;
            overflow: auto;
        }

        #searchButton {
            border: none;
            outline: none;
            background-color: var(--bg-card);
            color: var(--text-primary);
        }

        .suggestion-item {
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: left;
            padding: 10px;
            border-bottom: 0.5px solid #ddd;
        }



        .suggestion-item a {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--text-primary);
        }

        .suggestion-item img {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            margin-right: 10px;
        }

        .suggestion-item .user-info {
            display: flex;
            flex-direction: column;
        }

        .suggestion-item .full_name {
            font-weight: medium;
        }

        /* Results Section */
        .results {
            display: flex;
            flex-direction: column;
            align-items: center;
            /* width: 100%; */
            padding: 20px;
            margin-top: 20px;
        }

        .result-item {
            display: flex;
            align-items: center;
            width: 80%;
            padding: 10px;
            margin: 10px 0;
            background-color: var(--bg-card);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }

        .result-item img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            margin-right: 15px;
        }

        .result-item .user-info {
            display: flex;
            flex-direction: column;
        }

        .result-item .full_name {
            font-weight: 600;
            font-size: 16px;
            color: var(--text-primary);
        }

        .result-item .name {
            font-size: 14px;
            color: #555;
        }

        /* Loading Placeholder */
        #loadingText {
            margin-top: 50px;
            font-size: 18px;
            color: #999;
        }

        @media screen and (max-width:768px) {

            .search-container {
                width: 90%;
            }

            .sidebar .sidebar-content {
                display: flex;
                flex-direction: column;
                gap: 20px;
                padding: 20px;
            }
        }

        .bottom-navbar a {
            background-color: var(--bg-card);
            color: var(--text-primary);
        }
    </style>
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
            padding: 0px 0;
        }

        .first_left_container {
            height: 82vh !important;
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

        footer {
            z-index: 1000;
        }
    </style>

    <body>
        <div class="container">
            <? include 'inc/php/social_navbar.php' ?>
            <? include 'inc/php/social_sidebar.php' ?>

            <div class="search-main-content main-content">
                <div style="display: flex; justify-content: center; align-items: center;">
                    <div class="search-container">
                        <div class="search-bar">
                            <input type="text" placeholder="Search..." id="searchInput">
                            <button id="searchButton">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
                                    <path fill="currentColor" d="M10 4a6 6 0 1 0 0 12a6 6 0 0 0 0-12m-8 6a8 8 0 1 1 14.32 4.906l5.387 5.387a1 1 0 0 1-1.414 1.414l-5.387-5.387A8 8 0 0 1 2 10" />
                                </svg>
                            </button>
                        </div>
                        <div id="suggestionsList"></div>
                    </div>
                </div>
            </div>
        </div>

        <? include 'inc/php/footer.php' ?>

        <script>
            $(document).ready(function() {
                const $searchInput = $('#searchInput');
                const $suggestionsList = $('#suggestionsList');

                $searchInput.on('input', function() {
                    const searchQuery = $searchInput.val().trim().toLowerCase();

                    if (!searchQuery) {
                        $suggestionsList.hide();
                        return;
                    }
                    $.ajax({
                        url: 'process/processSearch.php',
                        type: 'GET',
                        data: {
                            query: searchQuery
                        },
                        success: function(data) {
                            let displayedResults = '';

                            // Handle Users
                            if (data.users.length > 0) {
                                displayedResults += '<h>Users</h>';
                                let displayedUsers = data.users.slice(0, 3).map(user => `
                    <div class="suggestion-item">
                        <a href="${user.url}">
                            <img src="${user.img}" alt="${user.full_name}">
                            <div class="user-info">
                                <div class="full_name">${user.full_name}</div>
                            </div>
                        </a>
                    </div>
                `).join('');

                                // Add "More options" button if there are more than 3 users
                                if (data.users.length > 3) {
                                    displayedUsers += `
                    <div class="suggestion-item more-options">
                        <a href="javascript:void(0);" class="showMore" data-type="users">More</a>
                    </div>
                    `;
                                }

                                displayedResults += `<div id="usersList">${displayedUsers}</div>`;
                            }

                            // Handle Posts
                            if (data.posts.length > 0) {
                                displayedResults += '<h3>Posts</h3>';
                                let displayedPosts = data.posts.slice(0, 3).map(post => `
                    <div class="suggestion-item">
                        <a href="${post.url}">
                            <div class="user-info">
                                <div class="full_name">${post.title}</div>
                            </div>
                        </a>
                    </div>
                `).join('');

                                // Add "More options" button if there are more than 3 posts
                                if (data.posts.length > 3) {
                                    displayedPosts += `
                    <div class="suggestion-item more-options">
                        <a href="javascript:void(0);" class="showMore" data-type="posts">More </a>
                    </div>
                    `;
                                }

                                displayedResults += `<div id="postsList">${displayedPosts}</div>`;
                            }

                            // Handle Channels
                            if (data.channels.length > 0) {
                                displayedResults += '<h3>Channels</h3>';
                                let displayedChannels = data.channels.slice(0, 3).map(channel => `
                    <div class="suggestion-item">
                        <a href="${channel.url}">
                            <img src="${channel.img}" alt="${channel.channel_name}">
                            <div class="user-info">
                                <div class="full_name">${channel.channel_name}</div>
                            </div>
                        </a>
                    </div>
                `).join('');

                                // Add "More options" button if there are more than 3 channels
                                if (data.channels.length > 3) {
                                    displayedChannels += `
                    <div class="suggestion-item more-options">
                        <a href="javascript:void(0);" class="showMore" data-type="channels">More </a>
                    </div>
                    `;
                                }

                                displayedResults += `<div id="channelsList">${displayedChannels}</div>`;
                            }

                            // Display the results or hide if empty
                            if (displayedResults) {
                                $suggestionsList.html(displayedResults).show();
                            } else {
                                $suggestionsList.hide();
                            }

                            // Show all results when "More options" is clicked
                            $('.showMore').on('click', function() {
                                let type = $(this).data('type'); // Get type (users, posts, channels)
                                let allResults = '';

                                if (type === 'users') {
                                    allResults = data.users.map(user => `
                        <div class="suggestion-item">
                            <a href="${user.url}">
                                <img src="${user.img}" alt="${user.full_name}">
                                <div class="user-info">
                                    <div class="full_name">${user.full_name}</div>
                                </div>
                            </a>
                        </div>
                    `).join('');
                                    $('#usersList').html(allResults);
                                }

                                if (type === 'posts') {
                                    allResults = data.posts.map(post => `
                        <div class="suggestion-item">
                            <a href="${post.url}">
                                <div class="user-info">
                                    <div class="full_name">${post.title}</div>
                                </div>
                            </a>
                        </div>
                    `).join('');
                                    $('#postsList').html(allResults);
                                }

                                if (type === 'channels') {
                                    allResults = data.channels.map(channel => `
                        <div class="suggestion-item">
                            <a href="${channel.url}">
                                <div class="user-info">
                                    <div class="full_name">${channel.channel_name}</div>
                                </div>
                            </a>
                        </div>
                    `).join('');
                                    $('#channelsList').html(allResults);
                                }
                            });
                        },
                        error: function() {
                            console.log("Error fetching results");
                        }
                    });


                });
            });
        </script>
    </body>

    </html>