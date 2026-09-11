<?php
// Include database connection
include 'assets/php/db_connect.php';

function fetch_all_publishers($conn)
{
    // Fetch publishers from the database
    $sql = "SELECT DISTINCT rss_publisher FROM rss_feeds_url"; // Your query
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            if (!empty($row['rss_publisher'])) {
                // Generate a sanitized ID for the checkbox
                $publisherId = htmlspecialchars(preg_replace('/[^a-zA-Z0-9_]/', '', $row['rss_publisher']));
                $publisherName = htmlspecialchars($row['rss_publisher']);
?>
                <input type="checkbox" id="publisher<?= $publisherId ?>" value="<?= $publisherName ?>">
                <label for="publisher<?= $publisherId ?>"><?= $publisherName ?></label><br>
<?php
            }
        }
    } else {
        echo "No publishers found.";
    }
}

function fetch_all_topics($conn)
{
    $html = '';
    $sql = "SELECT id, category FROM reader_category";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $topic = htmlspecialchars($row['category']);
            $topicId = htmlspecialchars($row['id']);
            $html .= "<input type='checkbox' id='topic$topicId' value='$topicId' data-topic='$topic'> ";
            $html .= "<label for='topic$topicId'>$topic</label><br>";
        }
    } else {
        $html .= "No topics found.";
    }

    return $html;
}

?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Magazine - Choose Publishers and Topics</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/styles.css">
    <style>
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            background-color: #141414;
            color: #fff;
            font-family: Arial, sans-serif;
        }

        .header {
            text-align: center;
            margin: 30px 0;
        }

        .header h1 {
            font-size: 48px;
            font-weight: bold;
        }

        .dropdown {
            position: relative;
        }

        .dropdown-menu {
            display: none;
            position: absolute;
            z-index: 1000;
            background: #333;
            border: 1px solid #444;
            width: 100%;
            max-height: 200px;
            overflow-y: auto;
            border-radius: 5px;
        }

        .dropdown.open .dropdown-menu {
            display: block;
        }

        .dropdown-toggle {
            background-color: #fcfcfc;
            color: #141414;
            border: none;
            border-radius: 5px;
            padding: 10px;
            width: 100%;
        }

        .dropdown-toggle:hover {
            background-color: #fcfcfc;
            color: #141414;
        }

        .footer {
            text-align: center;
            padding: 20px;
            background-color: #222;
            color: #fff;
            margin-top: auto;
            /* Push the footer to the bottom */
        }

        .btn-primary {
            color: #007bff;
            background-color: #1c1c1c !important;
            border-color: #1c1c1c;
        }

        .btn-primary:hover {
            background-color: #1c1c1c;
            border: 1px solid #007bff;
        }

        .container-fluid button {
            display: flex;
            position: relative;
            bottom: -32px;
        }

        input[type="checkbox"] {
            margin-right: 10px;
            cursor: pointer;
        }

        h3 {
            padding: 10px 15px;
        }

        .hidden {
            display: none;
        }

        .dropdowns {
            background-color: #fcfcfc;
            padding: 10px;
        }

        #magazineDisplay {
            margin-top: 20px;
            background-color: #333;
            padding: 20px;
            border-radius: 5px;
        }

        #magazineContent h3 {
            color: #e50914;
        }



        /* Category Section */
        .category {
            margin-bottom: 2rem;
        }

        .category-title {
            margin-bottom: 2rem;
            font-size: 1.75rem;
            font-weight: 700;
            color: #fff;
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
            max-width: 200px;
            min-width: 200px;
            margin-right: 1rem;
            background-color: #1c1c1c;
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
            /* transform: scale(1.05);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.6); */
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
            background-color: #333;
            border: none;
            border-radius: 8px;
            transition: background-color 0.3s, transform 0.3s;
            cursor: pointer;
        }

        .action-card:hover {
            background-color: #444;
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

        .item-card {
            position: relative;
            /* Allow absolute positioning for the checkbox */
        }

        .checkbox-container {
            position: absolute;
            top: 10px;
            /* Adjust as needed */
            right: 10px;
            /* Adjust as needed */
        }

        .article-checkbox {
            width: 20px;
            /* Make the checkbox larger */
            height: 20px;
            /* Make the checkbox larger */
            cursor: pointer;
            /* Change cursor to pointer for better UX */
        }

        .item-card input[type="checkbox"] {
            margin-right: 10px;
            cursor: pointer;
            position: absolute;
        }

        #addMagazineForm {
            width: 100%;
            display: flex;
            justify-items: left;
            gap: 10px;

        }

        #addMagazineForm .form-group {
            width: 100%;
            max-width: 250px;
            display: flex;
            flex-direction: column;
            justify-items: left;
            gap: 10px;
        }


        #magazineTitle::placeholder {
            color: #fcfcfc;
            font-size: 14px;
            font-weight: 200;
            /* Change placeholder text color */
        }
        .modal-title{
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

            .nav-link {
                margin-left: 0.5rem;
                font-size: 0.9rem;
                padding: .5rem 0rem;
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
    </style>
</head>

<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg">
        <div class="container d-flex justify-content-between">
            <a class="navbar-brand" href="#">
                <img src="inc/img/logo.black.png" height="60px" alt="Logo">
            </a>
        </div>
    </nav>

    <!-- Header -->
    <div class="header">
        <h1>Create Your Own Magazine</h1>
        <p>Select by Publisher and Topics</p>
    </div>

    <div class="container flex-grow-1">
        <div class="row choice-section">
            <div class="col-md-6">
                <h3>Choose by Publisher</h3>
                <div class="dropdown" id="publisherDropdown">
                    <button class="btn dropdown-toggle" type="button" id="publisherDropdownButton" aria-expanded="false">
                        Select Publishers
                    </button>
                    <div class="dropdown-menu" aria-labelledby="publisherDropdownButton">
                        <div class="dropdowns">
                            <?php fetch_all_publishers($conn) ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <h3>Choose by Topic</h3>
                <div class="dropdown" id="topicDropdown">
                    <button class="btn dropdown-toggle" type="button" id="topicDropdownButton" aria-expanded="false">
                        Select Topics
                    </button>
                    <div class="dropdown-menu" aria-labelledby="topicDropdownButton">
                        <div class="dropdowns">
                            <?= fetch_all_topics($conn) ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <button class="btn btn-primary mt-5" id="generateMagazine">Submit</button>

        <div class="container-fluid" style="display:none;">
            <!-- Featured Channels Category -->
            <div class="category">
                <h2 class="category-title">Your Magazine</h2>
                <div class="scroll-row">
                    <!-- Channel Cards with Logos -->
                    <div class="card item-card">
                        <img src="" class="card-img-top" alt="">
                        <div class="card-body">
                            <h5 class="card-title"></h5>
                            <a href="#" class="btn btn-primary">Read</a>
                        </div>
                    </div>
                </div>
            </div>


            <!-- Add Magazine Section -->
            <div class="add-magazine mb-4">
                <h4 class="add-magazine-title">Add Your Magazine</h4>
                <form id="addMagazineForm">
                    <div class="form-group">
                        <label style="font-size: medium;" for="magazineTitle">Magazine Title:</label>
                        <input style="background-color:#222; color:#fcfcfc!important; border:#141414" type="text" id="magazineTitle" class="form-control" placeholder="Enter your magazine title" required>
                    </div>
                    <div class="form-group">
                        <label for="coverPhoto">Upload Cover Photo:</label>
                        <input style="background-color:#222; color:#fcfcfc!important; border:#141414" type="file" id="coverPhoto" class="form-control" accept="image/*">
                    </div>

                    <div class="container-fluid">
                        <button id="collectSelected" class="btn btn-primary">Process</button>
                    </div>
                </form>
            </div>
        </div>

        <div id="magazineDisplay" class="hidden">
            <h2 class="text-center">Fetched Magazine</h2>
            <div id="magazineContent" class="text-center"></div>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <p>&copy; 2024 MyMagazine. All rights reserved.</p>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script>
        function toggleDefaultCovers() {
            const coverPhotoInput = document.getElementById('coverPhoto');
            const defaultCoverOptions = document.querySelector('.default-cover-options');

            // Show default cover options if no file is selected
            if (!coverPhotoInput.files.length) {
                defaultCoverOptions.style.display = 'block';
            } else {
                defaultCoverOptions.style.display = 'none';
            }
        }

        function selectDefaultCover(cover) {
            const coverPhotoInput = document.getElementById('coverPhoto');
            coverPhotoInput.value = cover; // Set the selected cover as value
            // Optionally, update the UI to show the selected cover
        }
    </script>

    <script>
        let selectedTopics = []; // Moved to higher scope
        let selectedTopicsVal = [];
        let selectedPublishers = []; // Moved to higher scope

        function fetchedArticles(selectedPublishers, selectedTopics) {

            if (selectedPublishers.length === 0 && selectedTopics.length === 0) {
                alert('Please select at least one publisher or topic.');
                return; // Exit the function
            }

            // Implement functionality to fetch articles based on selected publishers and topics
            console.log("Fetching articles for:", selectedPublishers, selectedTopics);

            // Make AJAX request
            $.ajax({
                type: 'POST',
                url: 'fetch_articles.php',
                data: {
                    publishers: JSON.stringify(selectedPublishers),
                    topics: JSON.stringify(selectedTopics)
                },
                success: function(response) {

                    let articles = Array.isArray(response) ? response : JSON.parse(response);
                    // Attempt to parse the response if it's not already an array
                    try {
                        articles = Array.isArray(response) ? response : JSON.parse(response);
                    } catch (e) {
                        console.error('Error parsing response:', e);
                        alert('Failed to parse the articles. Please try again later.');
                        return;
                    }

                    // Check if articles are fetched
                    if (articles.length > 0) {
                        let magazineContent = articles.map(feed => `
                <div class="card item-card">
                    <input type="checkbox" class="article-checkbox" data-url="${feed.url}">
                    <img src="${feed.image}" class="card-img-top" alt="${feed.title}">
                    <div class="card-body">
                        <h5 class="card-title">${feed.title}</h5>
                        <a href="${feed.url}" class="btn btn-primary" target="_blank">Read</a>
                    </div>
                </div>
            `).join('');

                        // Insert the constructed HTML into the scroll-row
                        $('.scroll-row').html(magazineContent);

                        // Show the magazine display container
                        $('.container-fluid').show();
                    } else {
                        // If no articles are found, hide the container and show an alert
                        $('.container-fluid').hide();
                        alert('No articles found for the selected publishers and topics.');
                    }
                },
                error: function(xhr, status, error) {
                    // Enhanced error handling with more details
                    console.error('Error fetching articles:', status, error);
                    alert('An error occurred while fetching the feeds. Please try again later.');
                }
            });
        }

        $(document).ready(function() {
            // Toggle dropdown visibility
            $('.dropdown-toggle').on('click', function() {
                $(this).parent().toggleClass('open');
            });

            // Close dropdown when clicking outside
            $(document).on('click', function(event) {
                if (!$(event.target).closest('.dropdown').length) {
                    $('.dropdown').removeClass('open');
                }
            });

            // When a checkbox is changed in topic dropdown
            $('#topicDropdown input[type="checkbox"]').change(function() {
                selectedTopics = []; // Clear previous selections
                selectedTopicsVal = [];

                // Get all checked checkboxes
                $('#topicDropdown input[type="checkbox"]:checked').each(function() {
                    selectedTopics.push($(this).data('topic'));
                    selectedTopicsVal.push($(this).val());
                });

                // Update the dropdown button text
                $('#topicDropdownButton').text(selectedTopics.length > 0 ? selectedTopics.join(', ') : 'Select Topics');
            });

            // When a checkbox is changed in publisher dropdown
            $('#publisherDropdown input[type="checkbox"]').change(function() {
                selectedPublishers = []; // Clear previous selections


                // Get all checked checkboxes
                $('#publisherDropdown input[type="checkbox"]:checked').each(function() {
                    selectedPublishers.push($(this).val());
                });

                // Update the dropdown button text
                $('#publisherDropdownButton').text(selectedPublishers.length > 0 ? selectedPublishers.join(', ') : 'Select Publishers');
            });

            // Update button click event to use the fetchedArticles function
            $('#generateMagazine').on('click', function() {
                console.log("The function is in action");
                fetchedArticles(selectedPublishers, selectedTopicsVal);
            });


            $('#collectSelected').on('click', function() {
                const selectedArticles = [];
                $('.article-checkbox:checked').each(function() {
                    selectedArticles.push($(this).data('url'));
                });

                console.log('Selected articles:', selectedArticles);

                // AJAX request to store selected articles
                $.ajax({
                    url: 'store_articles.php', // Change to your server script path
                    method: 'POST',
                    data: {
                        articles: selectedArticles
                    },
                    success: function(response) {
                        console.log('Articles stored successfully:', response);
                        // Optionally, handle successful response (e.g., show a message)
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        console.error('Error storing articles:', textStatus, errorThrown);
                        // Optionally, handle error (e.g., show an error message)
                    }
                });
            });

        });
    </script>

</body>

</html>