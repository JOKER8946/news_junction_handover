<?
include '../inc/php/validate.logged.php';
include '../inc/php/db_config.php';
include '../inc/php/function.php';

function getSavedLandingPages($userId)
{
    global $creamdb;

    $query = "SELECT id, title, url, created_at FROM user_landing_page WHERE user_id = ? AND deleteFlag='N' ORDER BY created_at DESC";
    $stmt = $creamdb->prepare($query);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    $pages = [];
    while ($row = $result->fetch_assoc()) {
        $pages[] = $row;
    }

    return $pages;
}

// Replace the <div class="marginDiv"></div> with this code
$savedPages = getSavedLandingPages($gUserId);

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Landing Page</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <!-- Animate.css (Latest Version) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
    <link rel="icon" type="image/x-icon" href="../grfx/img/logo.ico">

    <!-- Bootstrap 4.5.2 (Update to 5 if needed) -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">

    <!-- Optional: Latest Bootstrap 5.3.0 (if upgrading to Bootstrap 5) -->
    <!-- <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet"> -->

    <!-- Cropper CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../inc/css/social.css">

    <style>
        .dashboard {
            display: grid;
            gap: 20px;
        }

        .card-body a {
            background-color: transparent;
            border-color: #db5919;
        }

        .btn-primary {
            background-color: #db5919 !important;
            border-color: var(--button-bg-light);
        }

        .card {
            background-color: var(--bg-card);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            text-decoration: none;
            color: inherit;
            transition: transform 0.3s ease, box-shadow 0.3s ease, filter 0.3s ease;
            position: relative;
            display: block;
            width: 250px;
            height: auto;
        }

        .card:hover {
            transform: translateY(-8px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);
        }

        .card img {
            width: 100%;
            height: 180px;
            object-fit: cover;
            transition: opacity 0.3s ease;
        }

        .card img:hover {
            opacity: 0.8;
        }

        .card h3 {
            margin: 10px 0;
            font-size: 1.2rem;
            font-weight: bold;
            padding-left: 11px;
        }

        .card p {
            font-size: 1rem;
            color: #777;
        }

        .card .card-content {
            padding: 15px;
        }

        .card .card-content p {
            margin-top: 10px;
            color: #666;
        }

        .card .card-footer {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 10px;
            background: rgba(0, 0, 0, 0.5);
            color: white;
            font-size: 0.9rem;
            text-align: center;
        }

        .go-back-bar {
            top: 90px !important;
            left: 20px !important;
        }

        .cont {
            /* padding-top: 85px; */
        }

        .card:hover .card-footer {
            background: rgba(0, 0, 0, 0.7);
        }

        @media (max-width:768px) {
            .heading {
                font-size: x-large;
            }

            .go-back-bar {
                top: 70px !important;
                left: 10px !important;
            }
        }

        @media (max-width:425px) {
            .heading {
                font-size: larger;
            }

            .go-back-bar {
                top: 65px !important;
                left: 0px !important;
                padding: 0px 10px !important;
            }

            .dashboard {
                grid-template-columns: repeat(1, 1fr) !important;
            }

            .cont {
                padding-top: 70px;
            }

            .marginDiv {
                margin: 20vw;
            }
        }

        .marginDiv {
            margin: 10vw;
        }

        .saved-pages {
            margin-top: 2rem;
        }

        .saved-page {
            position: relative;
            width: 100%;
            transition: transform 0.3s ease-in-out;
            border: 1px solid rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }

        .saved-page:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .card-body {
            padding: 1.5rem;
        }

        .card-body h3 {
            margin: 0;
            font-size: 1.2rem;
            font-weight: 600;
        }

        .card-body p {
            font-size: 0.9rem;
            margin: 0.5rem 0;
            color: #666;
        }

        .action-buttons {
            display: flex;
            overflow: auto;
            gap: 0.8rem;
            margin-top: 1rem;
        }

        .btn-sm {
            padding: 0.4rem 0.8rem;
            font-size: 0.9rem;
        }

        .btn-sm i {
            margin-right: 0.4rem;
        }

        .no-pages {
            text-align: center;
            padding: 3rem;
            border-radius: 10px;
            border: 2px dashed #ddd;
        }

        .heading {
            margin-bottom: 1.5rem;
            font-weight: 600;
            position: relative;
            padding-bottom: 0.5rem;
        }

        .heading::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 50px;
            height: 3px;
            background: #db5919;
        }

        /* Responsive Grid */
        .dashboard {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1.5rem;
            padding: 1rem;
        }

        @media (max-width: 768px) {
            .dashboard {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 425px) {
            .dashboard {
                grid-template-columns: 1fr;
            }

            .card-body {
                padding: 1rem;
            }
        }
    </style>

    <style>
        /* Modal Styles */
        .modal {
            display: none;
            /* Modal remains hidden by default */
            position: fixed;
            z-index: 1000;
            /* Ensure it's on top */
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
            /* Semi-transparent background */
            padding: 20px;
            /* Add padding for better spacing */
            box-sizing: border-box;
            /* Include padding in width/height */
            display: flex;
            /* Use flexbox for centering */
            align-items: center;
            /* Center vertically */
            justify-content: center;
            /* Center horizontally */
        }

        .modal-content {
            /* background-color: #ffffff; */
            border-radius: 8px;
            /* Rounded corners */
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            /* Subtle shadow */
            width: 90%;
            /* Responsive width */
            max-width: 800px;
            /* Maximum width */
            overflow: hidden;
            /* Prevent content overflow */
            display: flex;
            flex-direction: column;
            /* Stack content vertically */
        }

        .modal-content h4 {
            padding: 20px;
            margin: 0;
            border-bottom: 1px solid #eee;
            font-size: 1.5rem;
            font-weight: 600;
            color: #333;
        }

        #analyticsData {
            padding: 20px;
            overflow-x: auto;
            /* Enable horizontal scrolling for tables */
        }

        #analyticsData table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
        }

        #analyticsData th,
        #analyticsData td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        #analyticsData th {
            /* background-color: #f8f8f8; */
            font-weight: 600;
        }

        #analyticsData tr:hover {
            /* background-color: #f0f0f0; */
        }

        .modal-footer {
            padding: 20px;
            text-align: right;
            border-top: 1px solid #eee;
        }

        .modal-close {
            background-color: #e53935;
            /* Modern red */
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1rem;
            transition: background-color 0.3s ease;
            /* Smooth transition */
        }

        .modal-content {
            color: black !important;
            ;
        }

        .modal-close:hover {
            background-color: #d32f2f;
        }

        /* Responsive adjustments */
        @media (max-width: 600px) {
            .modal-content {
                width: 95%;
            }

            #analyticsData th,
            #analyticsData td {
                padding: 8px 10px;
                font-size: 0.85rem;
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
    <script src="../inc/js/new_social_script.js?v=20260528"></script>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        .create-new-page {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            transition: background-color 0.3s;
        }

        .create-new-page:hover {
            background-color: #f8f9fa;
        }

        .plus-icon {
            font-size: 2rem;
            margin: 0;
        }

        .create-text {
            margin: 0;
            font-size: 0.9rem;
        }

        .analytics-btn {
            border-color: #db5919;
        }

        @media (min-width:1350px) {
            .dashboard {
                grid-template-columns: repeat(4, 1fr);
            }
        }
    </style>
</head>

<body>

    <div class="container">
        <? include '../inc/php/social_navbar.php' ?>
        <? include '../inc/php/social_sidebar.php' ?>

        <div class="search-main-content main-content">
            <h1 class="heading">Lead Pages</h1>
            <div class="dashboard saved-pages">
                <? if ($gUserPlan == 0) {
                ?>
                    <div class="alert alert-success" role="alert">Creating <b>LeadPages</b> is only available for <b>Pro</b> users! Click on Upgrade to Upgrade your account-<a href="../premium.php">Upgrade</a> .</div>
                <?
                } else { ?>
                    <div class="card saved-page">
                        <div class="card-body create-new-page" onclick="window.location.href='index.php';" style="cursor: pointer; padding: 0px;">
                            <h3 class="heading">Create New Page</h3>
                            <p class="plus-icon" style="font-size:40px; color:#db5919">+</p>
                        </div>
                    </div>
                <? } ?>
                <?
                if (count($savedPages) > 0) {

                    foreach ($savedPages as $page) {
                        $pageName = str_replace('.html', '', htmlspecialchars($page['title']));
                        echo '
        <div class="card saved-page">
            <div class="card-body">
                <h3 class="heading">' . $pageName . '</h3>
                <p class="text-muted">Created: ' . date('M d, Y', strtotime($page['created_at'])) . '</p>
                <div class="action-buttons">
                    <a href="https://www.newsjunction.net/leadpage/landingPage.php?id=' . $page['id'] . '" class="btn btn-primary btn-sm" target="_blank">
                        <i class="fas fa-external-link-alt"></i> View
                    </a>
                    <button class="btn btn-danger btn-sm delete-page" data-id="' . $page['id'] . '">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                    <button class="analytics-btn btn btn-primary btn-sm" data-id="' . $page['id'] . '">
                        <i class="fa-solid fa-circle-info"></i> Analytics
                    </button>
                </div>
            </div>
        </div>';
                    }
                } else {
                    echo '<div class="no-pages">
        <i class="fas fa-folder-open fa-3x text-muted"></i>
        <p class="mt-3">No saved Lead pages yet</p>
    </div>';
                }
                echo '</div>';
                ?>
                <div class="marginDiv"></div>
                <!-- Modal Structure -->
                <div id="analyticsModal" class="modal">
                    <div class="modal-content" style="color:black;">
                        <h4>Analytics Data</h4>
                        <div id="analyticsData"></div> <!-- This is where the table will be inserted -->
                        <div class="modal-footer">
                            <a href="javascript:void(0);" class="modal-close btn">Close</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <? include '../inc/php/footer.php' ?>

        <script>
            $('#analyticsModal').hide();
            $(document).ready(function() {

                // Listen for click event on the Analytics button
                $('.analytics-btn').click(function() {
                    var pageId = $(this).data('id'); // Get the page ID from the data attribute

                    // Send the AJAX request to fetch the analytics data
                    $.ajax({
                        url: 'getAnalyticsData.php', // The URL to send the request to
                        type: 'POST', // The request method (POST)
                        data: {
                            id: pageId
                        }, // Send the page ID as data
                        dataType: 'json', // Expecting JSON response
                        success: function(data) {
                            var html = '';
                            if (data.success) {
                                // If data is fetched successfully, display it in a table
                                html = `
                    <table>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Mobile</th>
                            <th>Message</th>
                            <th>Time</th>
                        </tr>
                    `;
                                // Loop through all the rows and generate HTML for each row
                                $.each(data.data, function(index, row) {
                                    html += `
                            <tr>
                                <td>${row.name}</td>
                                <td>${row.email}</td>
                                <td>${row.mobile}</td>
                                <td>${row.query}</td>
                                <td>${row.created_at}</td>
                            </tr>
                        `;
                                });
                                html += `</table>`;
                            } else {
                                // If no data is found, show the "No data found" message
                                html = '<p>No data found.</p>';
                            }

                            // Insert the HTML content into the modal's #analyticsData div
                            $('#analyticsData').html(html);

                            // Open the modal
                            $('#analyticsModal').fadeIn();
                        },
                        error: function(xhr, status, error) {
                            console.error("AJAX Error: " + error);
                            $('#analyticsData').html('<p>An error occurred while fetching the data.</p>');
                            $('#analyticsModal').fadeIn();
                        }
                    });
                });

                // Close the modal when clicking the close button
                $('.modal-close').click(function() {
                    $('#analyticsModal').fadeOut();
                });
            });
        </script>
        <script>
            $(document).ready(function() {
                // Delete landing page functionality
                $('.delete-page').click(function(e) {
                    e.preventDefault();
                    const pageId = $(this).data('id');

                    if (confirm('Are you sure you want to delete this landing page?')) {
                        $.ajax({
                            url: 'process/delete_landing_page.php',
                            type: 'POST',
                            data: {
                                id: pageId
                            },
                            success: function(response) {
                                console.log(response);
                                // Remove the card from the UI
                                $(e.target).closest('.card').fadeOut(300, function() {
                                    $(this).remove();

                                    // Check if there are any remaining cards
                                    if ($('.saved-page').length === 0) {
                                        $('.saved-pages').replaceWith(`
                                    <div class="no-pages">
                                        <i class="fas fa-folder-open fa-3x text-muted"></i>
                                        <p class="mt-3">No saved landing pages yet</p>
                                    </div>
                                `);
                                    }
                                });
                            },
                            error: function() {
                                alert('Error connecting to the server. Please try again.');
                            }
                        });
                    }
                });
            });
        </script>
</body>

</html>