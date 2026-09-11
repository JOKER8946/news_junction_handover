<!doctype html>
<?
include './inc/php/db_config.php';
include './inc/php/validate.logged.php';
include './inc/php/function.php';
include 'inc/function.php';


$location = json_decode(find_ipgeo_location(), true);

$res = trim($location['City'] . ($location['City'] && $location['Country'] ? ", " : "") . $location['Country']);

$act = '';
if (!empty($_POST)) $act = isset($_POST["act"]) ? $_POST["act"] : '';

// Show Blast Details
if ($act == 'showBlastDetails') {
    $blastId = isset($_POST['id']) ? $_POST['id'] : '';
    $sql = "SELECT send_grid_key FROM user WHERE id=$gUserId";
    $result = mysqli_query($creamdb, $sql);
    $row = mysqli_fetch_assoc($result);
    $userSendGridKey = $row['send_grid_key'];
    $apiURL = "https://api.sendgrid.com/v3/marketing/stats/singlesends/$blastId?group_by=ab_variation&aggregated_by=total";
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $apiURL,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER => array(
            "authorization: Bearer $userSendGridKey"
        ),
    ));
    $response = curl_exec($curl);
    curl_close($curl);
    $arrStats = json_decode($response, true);
    $arrStatsClicks = $arrStats['results'][0]['stats']['clicks'];
    $arrStatsUniqueClicks = $arrStats['results'][0]['stats']['unique_clicks'];
    $arrStatsDelivered = $arrStats['results'][0]['stats']['delivered'];
    $arrStatsInvalidEmails = $arrStats['results'][0]['stats']['invalid_emails'];
    $arrStatsOpens = $arrStats['results'][0]['stats']['opens'];
    $arrStatsUniqueOpens = $arrStats['results'][0]['stats']['unique_opens'];
    $arrStatsUnsubscribes = $arrStats['results'][0]['stats']['unsubscribes'];
?>
    <div class="popup" style="max-width:600px">
        <div class="widget">
            <div class="card">
                <div class="card-header bg-dark">
                    <h5 class="mb-0 text-light">Newsletter Details</h5>
                    <span class="close">&times;</span>
                </div>
            </div>
            <div id="widget_B" style="padding:15px 25px; max-height:calc(100vh - 200px);overflow-y:auto;">
                <table class="table table-striped">
                    <tr>
                        <td>Delivered</td>
                        <td><?= $arrStatsDelivered ?></td>
                    </tr>
                    <tr>
                        <td>Unique Opens</td>
                        <td><?= $arrStatsUniqueOpens ?></td>
                    </tr>
                    <tr>
                        <td>Unique Clicks</td>
                        <td><?= $arrStatsUniqueClicks ?></td>
                    </tr>
                    <tr>
                        <td>Unsubscribes</td>
                        <td><?= $arrStatsUnsubscribes ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
<?
}


// Show Article Details
if ($act == 'showArticleDetails') {
    $postId = isset($_POST['id']) ? $_POST['id'] : '';
?>
    <div class="popup" style="max-width:600px">
        <div class="widget">
            <div class="card">
                <div class="card-header bg-dark">
                    <h5 class="mb-0 text-light">Article Details</h5>
                    <span class="close">&times;</span>

                </div>
            </div>
            <div id="widget_B" style="padding:15px 25px; max-height:calc(100vh - 200px);overflow-y:auto;">
                <?
                if ($gUserPlan == 0) {
                    echo '<div class="alert alert-success" role="alert">This feature is only available in <b>Pro</b> plan! Go to My Account to upgrade.</div>';
                } else {
                ?>
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Country</th>
                                <th class="text-center">Total Views</th>
                                <th>View Breakdown</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?
                            $sql = "SELECT count(article_id) AS totalVisits,visit_country FROM metrics WHERE article_id=$postId GROUP BY visit_country ORDER BY visit_country";
                            $result = mysqli_query($creamdb, $sql);
                            while ($row = mysqli_fetch_assoc($result)) {
                                $visitCountry = $row['visit_country'];
                                $visitCountryCount = $row['totalVisits'];
                            ?>
                                <tr>
                                    <td><?= $visitCountry ?></td>
                                    <td align="center"><?= $visitCountryCount ?></td>
                                    <td>
                                        <?
                                        $sql = "SELECT count(article_id) AS totalVisits,visit_city FROM metrics WHERE article_id=$postId AND visit_country='$visitCountry' GROUP BY visit_city ORDER BY visit_city";
                                        $resultInner = mysqli_query($creamdb, $sql);
                                        while ($rowInner = mysqli_fetch_assoc($resultInner)) {
                                            $visitCity = $rowInner['visit_city'];
                                            $visitCityCount = $rowInner['totalVisits'];
                                            if ($visitCity == '') $visitCity = 'Unknown';
                                        ?>
                                            <?= $visitCity ?> - <?= $visitCityCount ?><br>
                                        <?
                                        }
                                        ?>
                                    </td>
                                </tr>
                            <?
                            }
                            ?>
                        </tbody>
                    </table>
                <?
                }
                ?>
            </div>
        </div>
    </div>
<?
}

// Show Article Leads
if ($act == 'showArticleLeads') {
    $postId = isset($_POST['id']) ? $_POST['id'] : '';
?>
    <div class="popup" style="max-width:900px">
        <div class="widget">
            <div class="card">
                <div class="card-header bg-dark">
                    <h5 class="mb-0 text-light">Article Lead Details</h5>
                    <span class="close">&times;</span>

                </div>
            </div>
            <div id="widget_B" style="padding:15px 25px; max-height:calc(100vh - 200px);overflow-y:auto;">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th width="20">#</th>
                            <th>Name</th>
                            <th>Company</th>
                            <th>Email</th>
                            <th>Mobile</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?
                        $sql = "SELECT * FROM user_collection_lead WHERE article_id=$postId ORDER BY date_created DESC";
                        $result = mysqli_query($creamdb, $sql);
                        $i = 1;
                        while ($row = mysqli_fetch_assoc($result)) {
                            $leadName = $row['full_name'];
                            $leadCompany = $row['company'];
                            $leadEmail = $row['email'];
                            $leadMobile = $row['mobile'];
                            $leadDate = $row['date_created'];
                            $leadDate = date('M d, Y', strtotime($leadDate));
                        ?>
                            <tr>
                                <td><?= $i ?>.</td>
                                <td><?= $leadName ?></td>
                                <td><?= $leadCompany ?></td>
                                <td><?= $leadEmail ?></td>
                                <td><?= $leadMobile ?></td>
                                <td><?= $leadDate ?></td>
                            </tr>
                        <?
                            $i += 1;
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?
}
// Cream: Analytics

if ($act == '') {
?>
    <html lang="en">

    <head>

        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Analytics | Knobly Reader</title>

        <!-- jQuery -->
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

        <!-- Magnific Popup -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/magnific-popup.js/1.1.0/jquery.magnific-popup.min.js"></script>

        <!-- Bootstrap, Font Awesome, etc. -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css" integrity="sha384-9aIt2nRpC12Uk9gS9baDl411NQApFmC26EwAOH8WgZl5MYYxFfc+NcPb1dKGj7Sk" crossorigin="anonymous">
        <link rel="stylesheet" href="inc/magnific-popup.css" />
        <link rel="icon" type="image/x-icon" href="../grfx/img/logo.ico">

        <!-- Custom CSS -->
        <link rel="stylesheet" href="inc/css/social.css">

        <!-- Scripts -->
        <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.min.js" integrity="sha384-OgVRvuATP1z7JjHLkuOU7Xw704+h835Lr+6QL9UvYjZE3Ipu6Tp75j7Bh/kR0JKI" crossorigin="anonymous"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.8.0/Chart.min.js" crossorigin="anonymous"></script>
        <script src="inc/common.js"></script>
        <script src="inc/genai_func.js"></script>

        <style>
            tbody td {
                color: var(--text-primary);
                text-decoration: none;
                background-color: transparent;
            }


            thead th {
                color: var(--text-primary);
                text-decoration: none;
                background-color: transparent;
            }



            a {
                color: var(--text-primary);
                text-decoration: none;
                background-color: transparent;
            }

            .newsroll-dropdown {
                border: none;
                outline: none;
                position: relative;
                display: inline-block;
                /* display: flex; */
                align-items: center;
                padding-left: 8px;
                /* border: none; */
                padding-bottom: 8px;
            }

            .newsroll-dropbtn {
                border: none;
                color: rgba(255, 255, 255, 0.5);
                background-color: #212529;
            }

            .newsroll-dropbtn:focus {
                border: none;
                outline: none;

            }

            .newsroll-dropdown-content {
                display: none;
                outline: none;
                position: relative;
                background-color: #212529;
                min-width: 160px;
                /* box-shadow: 0px 8px 16px 0px rgba(0, 0, 0, 0.2); */
                /* z-index: 1; */
            }

            .newsroll-dropdown-content a {
                color: white;
                padding: 12px 16px;
                text-decoration: none;
                display: block;
            }

            .show {
                display: block;
            }


            .breadcrumb {
                display: -ms-flexbox;
                display: flex;
                -ms-flex-wrap: wrap;
                flex-wrap: wrap;
                padding: .75rem 1rem;
                margin-bottom: 1rem;
                list-style: none;
                background-color: transparent !important;
                border-radius: .25rem;
            }

            @media screen and (min-width:768px) {

                .container-fluid,
                .container-lg,
                .container-md,
                .container-sm,
                .container-xl {
                    width: 85% !important;
                    padding-right: 15px;
                    padding-left: 15px;
                    margin-right: auto;
                    margin-left: auto;
                }

            }

            #panelContent {
                margin-bottom: 60px;
            }

            /* Fullscreen blurred background */
            .blur-background {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                /* Dark overlay */
                backdrop-filter: blur(8px);
                /* Blur effect */
                z-index: 998;
            }

            /* Centered popup card */
            .popup {
                position: fixed;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                background: #fff;
                border-radius: 10px;
                box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
                /* Shadow for a card-like look */
                max-width: 600px;
                width: 90%;
                /* Responsive width */
                z-index: 999;
                overflow: hidden;
            }

            /* Card styling */
            .card-header {
                background-color: #343a40;
                /* Dark header */
                color: #fff;
                padding: 15px;
                font-size: 18px;
                border-top-left-radius: 10px;
                border-top-right-radius: 10px;
                justify-content: space-between;
                display: flex;
            }


            .card {
                margin: 0;
                padding: 0;
                border: none;
            }

            /* Content area styling */
            #widget_B {
                padding: 15px 20px;
                max-height: calc(100vh - 200px);
                overflow-y: auto;
                font-size: 14px;
                background: #f8f9fa;
                /* Light background */

            }

            /* Ensure tables are scrollable on smaller devices */
            .tab-content {
                width: 100%;
                overflow-x: auto;
            }

            .table-responsive {
                width: 100%;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                /* Smooth scrolling on mobile */
            }

            .table {
                width: 100%;
                min-width: 600px;
                /* Optional: Forces horizontal scroll for smaller screens */
                margin-bottom: 1rem;
            }

            /* Table styling */
            table.table {
                margin: 0;
                width: 100%;
                border-collapse: collapse;
            }

            /* table.table th,
            table.table td {
                padding: 10px;
                border: 1px solid #dee2e6;
                text-align: left;
                vertical-align: top;
                color: black;
            } */

            table.table th {
                background-color: var(--bg-main);
                font-weight: bold;
            }

            .close {
                float: right;
                font-size: 1.5rem;
                font-weight: 700;
                line-height: 1;
                text-shadow: 0 1px 0 #000;
                opacity: .5;
                color: #fff !important;
                cursor: pointer;
            }

            .close:hover {
                color: #fff !important;
            }

            @media screen and (max-width:540px) {
                .tab-content {
                    overflow-x: auto;
                }

            }



            /* Container Padding */
            @media screen and (max-width: 768px) {
                .container-fluid {
                    padding: 40px 0 !important;
                    max-width: 1400px;
                    margin: 0 auto;

                }

            }
        </style>

    </head>


    <body>

        <div class="">
            <? include 'inc/php/social_navbar.php' ?>
            <? include 'inc/php/social_sidebar.php' ?>
            <main class="search-main-content main-content">

                <div id="panelContent" class="container-fluid">
                    <ol class="breadcrumb my-3">
                        <li class="breadcrumb-item">
                            <h4 class="m-0">Month-wise Analytics</h4>
                        </li>
                    </ol>

                    <!-- Include Chart.js -->
                    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

                    <?php
                    $sql = "SELECT 
DATE_FORMAT(A.date_visited, '%Y-%m') AS month,
COUNT(A.article_id) AS totalVisits
FROM 
metrics A
INNER JOIN 
user_collection B 
ON A.article_id = B.id 
AND B.user_id = $gUserId
WHERE 
A.date_visited >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)
GROUP BY 
DATE_FORMAT(A.date_visited, '%Y-%m')
ORDER BY 
month ASC;";

                    $result = $creamdb->query($sql);

                    $labels = [];
                    $data = [];

                    while ($row = $result->fetch_assoc()) {
                        $labels[] = $row['month'];
                        $data[] = $row['totalVisits'];
                    }
                    ?>

                    <!-- Canvas for Chart -->
                    <canvas id="visitsChart"></canvas>

                    <script>
                        // PHP data to JavaScript
                        const labels = <?php echo json_encode($labels); ?>;
                        const data = <?php echo json_encode($data, JSON_NUMERIC_CHECK); ?>; // Ensures numeric values are correctly parsed

                        // Chart.js Line Graph
                        const ctx = document.getElementById('visitsChart').getContext('2d');
                        new Chart(ctx, {
                            type: 'line',
                            data: {
                                labels: labels, // X-axis: Months
                                datasets: [{
                                    label: 'Total Visits',
                                    data: data, // Y-axis: No. of Visits
                                    borderColor: 'blue',
                                    borderWidth: 2,
                                    fill: false,
                                    tension: 0.4 // Smooth curve effect
                                }]
                            },
                            options: {
                                responsive: true,
                                scales: {
                                    x: {
                                        title: {
                                            display: true,
                                            text: 'Month'
                                        }
                                    },
                                    y: {
                                        title: {
                                            display: true,
                                            text: 'Total Visits'
                                        },
                                        beginAtZero: true
                                    }
                                }
                            }
                        });
                    </script>

                    <ol class="breadcrumb my-3">
                        <li class="breadcrumb-item">
                            <h4 class="m-0">Analytics</h4>
                        </li>
                    </ol>
                    <div class="row mb-4">
                        <div class="col">
                            <ul class="nav nav-tabs mb-4">
                                <li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#articles" role="tab">Top Visited Articles</a></li>
                                <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#lead" role="tab">Article Leads</a></li>
                                <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#newsletter" role="tab">Sent Newsletters</a></li>
                            </ul>
                            <div class="tab-content my-3">
                                <div class="tab-pane fade show active" id="articles" role="tabpanel">
                                    <?
                                    $sql = "SELECT B.id,B.title,count(A.article_id) AS totalVisits FROM metrics A INNER JOIN user_collection B ON (A.article_id=B.id AND B.user_id=$gUserId) GROUP BY B.url,B.id ORDER BY totalVisits DESC";
                                    $result = mysqli_query($creamdb, $sql);
                                    $numRows = mysqli_num_rows($result);
                                    if ($numRows == 0) {
                                        echo '<div class="px-3">No articles to show!</div>';
                                    } else {
                                    ?>
                                        <div class="table-responsive">
                                            <table class="table table-striped">
                                                <thead>
                                                    <tr>
                                                        <th width="20">#</th>
                                                        <th>Title</th>
                                                        <th>Views</th>
                                                        <th></th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?
                                                    $i = 1;
                                                    while ($row = mysqli_fetch_assoc($result)) {
                                                        $topArticleId = $row['id'];
                                                        $topArticleTitle = $row['title'];
                                                        $topArticleVisit = $row['totalVisits'];
                                                    ?>
                                                        <tr>
                                                            <td><?= $i ?>.</td>
                                                            <td><a href="https://newsjunction.net/view/<?= $topArticleId ?>/<?= createArticleURL($topArticleTitle) ?>" target="_blank"><?= $topArticleTitle ?></a></td>
                                                            <td><?= $topArticleVisit ?></td>
                                                            <td align="right"><a href="#" title="View Details"><i data-id="<?= $topArticleId ?>" class="viewRowA far fa-chart-bar fa-lg text-muted"></i></a></td>
                                                        </tr>
                                                    <?
                                                        $i += 1;
                                                    }
                                                    ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?
                                    }
                                    ?>
                                </div>
                                <div class="tab-pane fade" id="lead" role="tabpanel">
                                    <?
                                    if ($gUserPlan == 0) {
                                        echo '<div class="alert alert-success" role="alert">This feature is only available in <b>Pro</b> plan! Go to My Account to upgrade.</div>';
                                    } else {
                                        $sql = "SELECT B.id,B.title,count(A.article_id) AS totalLeads FROM user_collection_lead A INNER JOIN user_collection B ON (A.article_id=B.id AND B.user_id=$gUserId) GROUP BY B.url,B.id ORDER BY totalLeads DESC LIMIT 25";
                                        $result = mysqli_query($creamdb, $sql);
                                        $numRows = mysqli_num_rows($result);
                                        if ($numRows == 0) {
                                            echo '<div class="px-3">No article leads to show!</div>';
                                        } else {
                                    ?>
                                            <table class="table table-striped">
                                                <thead>
                                                    <tr>
                                                        <th width="20">#</th>
                                                        <th>Title</th>
                                                        <th>Leads</th>
                                                        <th></th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?
                                                    $i = 1;
                                                    while ($row = mysqli_fetch_assoc($result)) {
                                                        $topArticleId = $row['id'];
                                                        $topArticleTitle = $row['title'];
                                                        $topArticleLead = $row['totalLeads'];
                                                    ?>
                                                        <tr>
                                                            <td><?= $i ?>.</td>
                                                            <td><a href="https://newsjunction.net/view/<?= $topArticleId ?>/<?= createArticleURL($topArticleTitle) ?>" target="_blank"><?= $topArticleTitle ?></a></td>
                                                            <td><?= $topArticleLead ?></td>
                                                            <td align="right"><a href="#" title="View Details"><i data-id="<?= $topArticleId ?>" class="viewRowL far fa-chart-bar fa-lg text-muted"></i></a></td>
                                                        </tr>
                                                    <?
                                                        $i += 1;
                                                    }
                                                    ?>
                                                </tbody>
                                            </table>
                                    <?
                                        }
                                    }
                                    ?>
                                </div>
                                <div class="tab-pane fade" id="newsletter" role="tabpanel">
                                    <?
                                    if ($gUserPlan == 0) {
                                        echo '<div class="alert alert-success" role="alert">This feature is only available in <b>Pro</b> plan! Go to My Account to upgrade.</div>';
                                    } else {
                                        $sql = "SELECT A.newsletter_id,A.blast_provider,A.blast_subject,A.blast_id,A.date_created FROM user_blast A INNER JOIN user_newsletter B ON A.newsletter_id=B.id AND B.user_id=$gUserId";
                                        $result = mysqli_query($creamdb, $sql);
                                        $numRows = mysqli_num_rows($result);
                                        if ($numRows == 0) {
                                            echo '<div class="px-3">No newsletters sent!</div>';
                                        } else {
                                    ?>
                                            <table class="table table-striped">
                                                <thead>
                                                    <tr>
                                                        <th width="20">#</th>
                                                        <th>Provider</th>
                                                        <th>Subject</th>
                                                        <th>Date Sent</th>
                                                        <th></th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?
                                                    $i = 1;
                                                    while ($row = mysqli_fetch_assoc($result)) {
                                                        $newsletterId = $row['newsletter_id'];
                                                        $blastId = $row['blast_id'];
                                                        $blastProvider = $row['blast_provider'];
                                                        $blastSubject = $row['blast_subject'];
                                                        $blastDate = $row['date_created'];
                                                        $blastDate = date('M d, Y', strtotime($blastDate));
                                                    ?>
                                                        <tr>
                                                            <td><?= $i ?>.</td>
                                                            <td><?= $blastProvider ?></td>
                                                            <td><a href="newsletter.php?id=<?= $newsletterId ?>" target="_blank"><?= $blastSubject ?></a></td>
                                                            <td><?= $blastDate ?></td>
                                                            <td align="right"><a href="#" title="View Details"><i data-id="<?= $blastId ?>" class="viewRowN far fa-chart-bar fa-lg text-muted"></i></a></td>
                                                        </tr>
                                                    <?
                                                        $i += 1;
                                                    }
                                                    ?>
                                                </tbody>
                                            </table>
                                    <?
                                        }
                                    }
                                    ?>
                                </div>

                            </div>
                        </div>
                    </div>

                    <div id="analyticsModal" class="modal">

                        <div class="modal-content">
                            <!-- AJAX content will be injected here -->
                        </div>

                    </div>


                    <script type="text/javascript">
                        $(function() {
                            // Function to open the modal and load content via AJAX
                            function openModal(content) {
                                var modal = $('#analyticsModal'); // Assuming you have a modal with the ID 'analyticsModal'
                                modal.find('.modal-content').html(content); // Place the content in the modal
                                modal.show(); // Show the modal
                            }

                            // Function to close the modal
                            function closeModal() {
                                $('#analyticsModal').hide(); // Hide the modal
                            }

                            // Handler for 'viewRowA' elements
                            $('.viewRowA').on('click', function() {
                                var postData = {
                                    act: 'showArticleDetails',
                                    id: $(this).data('id') // Get the ID from the data attribute
                                };

                                $.ajax({
                                    type: 'POST',
                                    url: '',
                                    data: postData,
                                    success: function(response) {
                                        openModal(response); // Open the modal with the AJAX response content
                                    },
                                    error: function() {
                                        alert('Error loading content');
                                    }
                                });
                            });

                            // Handler for 'viewRowL' elements
                            $('.viewRowL').on('click', function() {
                                var postData = {
                                    act: 'showArticleLeads',
                                    id: $(this).data('id') // Get the ID from the data attribute
                                };

                                $.ajax({
                                    type: 'POST',
                                    url: '',
                                    data: postData,
                                    success: function(response) {
                                        openModal(response); // Open the modal with the AJAX response content
                                    },
                                    error: function() {
                                        alert('Error loading content');
                                    }
                                });
                            });

                            // Handler for 'viewRowN' elements
                            $('.viewRowN').on('click', function() {
                                var postData = {
                                    act: 'showBlastDetails',
                                    id: $(this).data('id') // Get the ID from the data attribute
                                };

                                $.ajax({
                                    type: 'POST',
                                    url: '',
                                    data: postData,
                                    success: function(response) {
                                        openModal(response); // Open the modal with the AJAX response content
                                    },
                                    error: function() {
                                        alert('Error loading content');
                                    }
                                });
                            });

                            // Close modal when clicking outside the modal content (optional)
                            $(document).on('click', function(e) {
                                if ($(e.target).closest('.modal-content').length === 0) {
                                    closeModal();
                                }
                            });

                            // Close modal when clicking the close button inside modal (optional)
                            $('#analyticsModal .close').on('click', function() {
                                console.log('Closing modal...');
                                closeModal();
                            });


                            $(document).on('click', '#analyticsModal .close', function() {
                                console.log('Modal close button clicked.');
                                closeModal();
                            });

                        });
                    </script>
                </div>
            </main>
            <? include 'inc/php/footer.php' ?>
        </div>

    </body>
    <script>
        function newsrollToggleDropdown() {
            var dropdownContent = document.getElementById("newsrollDropdown");
            dropdownContent.classList.toggle("show");
        }

        // Close the dropdown if the user clicks outside of it
        window.onclick = function(event) {
            if (!event.target.matches('.newsroll-dropbtn')) {
                var dropdowns = document.getElementsByClassName("newsroll-dropdown-content");
                for (var i = 0; i < dropdowns.length; i++) {
                    var openDropdown = dropdowns[i];
                    if (openDropdown.classList.contains('show')) {
                        openDropdown.classList.remove('show');
                    }
                }
            }
        }
    </script>

    </html>
<?
}
?>