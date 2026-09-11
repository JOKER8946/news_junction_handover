<?php
$articles = [];
if (isset($readerdb) && $readerdb instanceof mysqli) {
    $stmt = $readerdb->prepare(
        "SELECT rfa.id, rfa.url, rfa.title, rfa.date,
                rfa.image AS articleImage, rfu.rss_image AS rssImage
         FROM rss_feeds_articles rfa
         INNER JOIN rss_feeds_url rfu ON rfa.feed_id = rfu.rss_id
         WHERE rfu.rss_id = ?
         ORDER BY rfa.date DESC LIMIT 100"
    );
    if ($stmt) {
        $rssId = 9;
        $stmt->bind_param("i", $rssId);
        $stmt->execute();
        $rs = $stmt->get_result();
        while ($row = $rs->fetch_assoc()) {
            $image = (empty($row['articleImage']))
                ? (empty($row['rssImage']) ? '' : $row['rssImage'])
                : $row['articleImage'];
            $articles[] = [
                'id'    => stripslashes($row['id']),
                'title' => htmlspecialchars(strip_tags(stripslashes($row['title']))),
                'image' => $image,
                'url'   => $row['url'],
                'date'  => $row['date'],
            ];
        }
        $stmt->close();
    }
}
?>
<style>
    .search-container {
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
        box-shadow: var(--card-shadow);
        border-radius: 7px;
        padding: 10px;
        position: absolute;
        z-index: 10;
        max-height: 70vh;
        margin-top: 10px;
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

    .search-bar {
        width: 100%;
        display: flex;
        justify-content: space-between;
        align-items: center;
        /* Center items vertically */
        background-color: var(--bg-card);
        border-radius: 7px;
        padding: 1px 3px;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
    }

    .search-bar input {
        border: none;
        background: transparent;
        outline: none;
        width: 70%;
        /* Adjusted to make space for clear button */
        font-size: 16px;
        padding: 8px;
        border-radius: 20px;
    }

    .search-bar button {
        border: none;
        outline: none;
        color: var(--text-primary);
        font-size: 16px;
        border-radius: 50%;
        cursor: pointer;
        display: flex;
        justify-content: center;
        align-items: center;
        background-color: var(--bg-card);
    }

    .search-bar button:hover {
        background-color: #007acb;
    }

    #clearButton {
        display: none;
        /* Hidden by default */
        margin-right: 5px;
        /* Space between clear and search buttons */
    }
</style>


<!-- Right sidebar -->
<div class="right-sidebar">
    <div class="search-container">
        <div class="search-bar">
            <input type="text" placeholder="Search..." id="searchInput">
            <button id="clearButton" style="display: none;">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24">
                    <path fill="currentColor" d="M12 2C6.47 2 2 6.47 2 12s4.47 10 10 10 10-4.47 10-10S17.53 2 12 2zm4.707 14.707a1 1 0 0 1-1.414 0L12 13.414l-3.293 3.293a1 1 0 0 1-1.414-1.414L10.586 12 7.293 8.707a1 1 0 0 1 1.414-1.414L12 10.586l3.293-3.293a1 1 0 0 1 1.414 1.414L13.414 12l3.293 3.293a1 1 0 0 1 0 1.414z" />
                </svg>
            </button>
            <button id="searchButton">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
                    <path fill="currentColor" d="M10 4a6 6 0 1 0 0 12a6 6 0 0 0 0-12m-8 6a8 8 0 1 1 14.32 4.906l5.387 5.387a1 1 0 0 1-1.414 1.414l-5.387-5.387A8 8 0 0 1 2 10" />
                </svg>
            </button>
        </div>
        <div id="suggestionsList"></div>
    </div>

    <div class="sidebar-card news-card">
        <div class="sidebar-title">All News</div>
        <div class="news-container">
            <?php if (!empty($articles)): ?>
                <?php foreach (array_slice($articles, 0, 7) as $article): ?>

                    <a href="<?= htmlspecialchars($article['url']); ?>" target="_blank" class="news-link">
                        <div class="news-item">
                            <img src="<?php echo htmlspecialchars($article['image']); ?>" alt="News thumbnail" class="news-thumbnail">
                            <div class="news-content">
                                <div class="news-headline"><?php echo htmlspecialchars($article['title']); ?></div>
                                <!-- <div class="news-source">Source • <?= htmlspecialchars(strip_tags(stripslashes($article['date']))) ?></div> -->
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="news-item">Failed to load news. Please try again later.</div>
            <?php endif; ?>
        </div>
        <a href="/dashboard.php" class="news-footer">See all news</a>
    </div>

    <!-- <div class="sidebar-card">
        <div class="sidebar-title">Who to follow</div>
        <div class="friend-item">
            <div class="user-avatar">D</div>
            <div class="friend-info">
                <div class="friend-name">David Chen</div>
                <div class="post-meta">@designdavid</div>
            </div>
        </div>
        <div class="friend-item">
            <div class="user-avatar">L</div>
            <div class="friend-info">
                <div class="friend-name">Lisa Morgan</div>
                <div class="post-meta">@lisacreates</div>
            </div>
        </div>
        <div class="friend-item">
            <div class="user-avatar">K</div>
            <div class="friend-info">
                <div class="friend-name">Kevin Patel</div>
                <div class="post-meta">@kevindev</div>
            </div>
        </div>
    </div> -->
</div>

<script>
    $(document).ready(function() {
        const $searchInput = $('#searchInput');
        const $suggestionsList = $('#suggestionsList');
        const $clearButton = $('#clearButton');

        // Show/hide clear button based on input value
        $searchInput.on('input', function() {
            const searchQuery = $searchInput.val().trim().toLowerCase();

            // Show clear button if input is not empty
            $clearButton.css('display', searchQuery ? 'flex' : 'none');

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

                        if (data.posts.length > 3) {
                            displayedPosts += `
                            <div class="suggestion-item more-options">
                                <a href="javascript:void(0);" class="showMore" data-type="posts">More</a>
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

                        if (data.channels.length > 3) {
                            displayedChannels += `
                            <div class="suggestion-item more-options">
                                <a href="javascript:void(0);" class="showMore" data-type="channels">More</a>
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
                        let type = $(this).data('type');
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

        // Clear button click event
        $clearButton.on('click', function() {
            $searchInput.val(''); 
            $suggestionsList.hide(); 
            $clearButton.hide(); 
        });
    });
</script>