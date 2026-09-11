<?
include '../inc/validate.logged.php';
include '../inc/config.php';
include 'db.php';

$search = '';
if (isset($_GET['s']) && !empty($_GET['s'])) {
    $search = mysqli_real_escape_string($conn, $_GET['s']);
}

$campaignsSql = "SELECT campaigns.id, campaigns.campaign_name, campaigns.action, list.list_name, campaigns.sent_date 
                 FROM campaigns 
                 JOIN list  ON campaigns.list_id = list.id WHERE list.user_id = $gUserId";

if ($search) {
    $campaignsSql .= " AND campaigns.campaign_name LIKE '%$search%'";
}

$campaignsResult = mysqli_query($conn, $campaignsSql);


function fetch_unique_clicks($conn, $campaignId)
{
    $campaignsSql = "SELECT COUNT(*) AS count FROM messages m INNER JOIN email_clicks ec ON ec.message_id = m.message_id WHERE m.campaign_id = ?";
    $stmt = $conn->prepare($campaignsSql);
    $stmt->bind_param("i", $campaignId);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    $stmt->close();
    return $data['count'];
}

function fetch_unique_opens($conn, $campaignId)
{
    $campaignsSql = "SELECT COUNT(*) AS count FROM messages m INNER JOIN email_opens eo ON eo.message_id = m.message_id WHERE m.campaign_id = ?";
    $stmt = $conn->prepare($campaignsSql);
    $stmt->bind_param("i", $campaignId);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    $stmt->close();
    return $data['count'];
}

function fetch_bounces($conn, $campaignId)
{
    $campaignsSql = "SELECT COUNT(*) AS count FROM bounces b INNER JOIN messages m ON m.message_id = b.message_id WHERE m.campaign_id = ?";
    $stmt = $conn->prepare($campaignsSql);
    $stmt->bind_param("i", $campaignId);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    $stmt->close();
    return $data['count'];
}

function fetch_deliveries($conn, $campaignId)
{
    $campaignsSql = "SELECT COUNT(*) AS count FROM deliveries d INNER JOIN messages m ON m.message_id = d.message_id WHERE m.campaign_id = ?";
    $stmt = $conn->prepare($campaignsSql);
    $stmt->bind_param("i", $campaignId);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    $stmt->close();
    return $data['count'];
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Knobly Cream: Create &bull; Reach &bull; Measure</title>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <!-- Font Awesome CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.0.0/animate.min.css" />
    <link rel="stylesheet" href="../inc/style.css" />
    <link rel="stylesheet" href="../inc/genai_style.css" />
    <style>
        /* Mobile Responsive Styles */
        /* General Styling */
        .breadcrumb {
            padding: 0.75rem 1rem;
        }

        .breadcrumb-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .breadcrumb-item .text-left {
            display: block;
        }

        .breadcrumb-item form input[type="text"] {
            max-width: 100%;
        }

        /* Mobile (up to 375px) */
        @media (max-width: 375px) {
            .breadcrumb-item h4 {
                font-size: 1.25rem;
            }

            .breadcrumb-item .btn {
                font-size: 0.875rem;
                padding: 0.5rem 0.75rem;
            }

            .breadcrumb-item .form-inline {
                display: block;
                width: 100%;
            }

            .breadcrumb-item .form-inline input,
            .breadcrumb-item .form-inline button {
                width: 100%;
                margin: 0.25rem 0;
            }

            .breadcrumb-item a.btn {
                width: 100%;
                margin-top: 0.25rem;
            }
        }

        /* Tablet (375px to 768px) */
        @media (min-width: 376px) and (max-width: 768px) {
            .breadcrumb-item h4 {
                font-size: 1.5rem;
            }

            .breadcrumb-item .btn {
                font-size: 1rem;
            }

            .breadcrumb-item .form-inline {
                display: block;
                width: 100%;
            }

            .breadcrumb-item .form-inline input {
                width: 80%;
            }

            .breadcrumb-item .form-inline button {
                width: 20%;
            }

            .breadcrumb-item a.btn {
                width: 100%;
                margin-top: 0.5rem;
            }
        }

        /* Laptops (768px to 1024px) */
        @media (min-width: 769px) and (max-width: 1024px) {
            .breadcrumb-item h4 {
                font-size: 1.75rem;
            }

            .breadcrumb-item .form-inline input {
                max-width: 250px;
            }

            .breadcrumb-item a.btn {
                width: auto;
                margin-top: 0;
            }

            .breadcrumb-item .btn {
                padding: 0.75rem 1.25rem;
            }
        }

        /* Desktop (1024px and above) */
        @media (min-width: 1025px) {
            .breadcrumb-item h4 {
                font-size: 2rem;
            }

            .breadcrumb-item .form-inline input {
                max-width: 300px;
            }

            .breadcrumb-item a.btn {
                width: auto;
            }
        }
    </style>
</head>

<body class="sb-nav-fixed">
    <?php include "navbar.php"  ?>
    <div id="layoutSidenav">
        <?php include 'sidebar.php'; ?>
        <div id="layoutSidenav_content">
            <main>
                <div id="panelContent" class="container-fluid">
                    <div class="main-content">
                        <ol class="breadcrumb my-3">
                            <li class="breadcrumb-item w-100 d-flex justify-content-between align-items-center flex-wrap">
                                <div class="text-left mb-2 mb-md-0">
                                    <h4 class="mt-1">All campaigns</h4>
                                    <a href="newCompaign.php" class="btn btn-primary mt-2"><i class="fas fa-plus"></i> Create &amp; send new campaign</a>
                                </div>
                                <div class="d-flex align-items-center ml-auto mt-2 mt-md-0 flex-wrap justify-content-end">
                                    <form class="form-inline d-flex flex-nowrap mr-2 mb-2 mb-md-0" action="" method="GET">
                                        <input type="hidden" name="i" value="16">
                                        <input type="text" class="form-control mr-2" name="s" placeholder="Search campaigns..." value="<?= htmlspecialchars($search) ?>" style="max-width: 200px;">
                                        <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Search</button>
                                    </form>
                                    <a href="../dashboard.php" class="btn btn-primary" style="background-color: #007bff; border-color: #007bff;">
                                        <i class="bi bi-arrow-left"></i> Back to Cream
                                    </a>
                                </div>
                            </li>
                        </ol>

                        <div class="row mt-3">
                            <div class="col">
                                <table class="table table-striped table-bordered">
                                    <thead  styles="background-color:#e9ecef">
                                        <tr>
                                            <th>Campaign Name</th>
                                            <th>Action Type</th>
                                            <th>Recipients</th>
                                            <th>Sent</th>
                                            <th>Delivered Mails</th>
                                            <th>Bounced Mails</th>
                                            <th>Unique Opens</th>
                                            <th>Unique Clicks</th>
                                        </tr>
                                    </thead>
                                    <thead>
                                        <tr>
                                            <th>Campaign Name</th>
                                            <th>Action Type</th>
                                            <th>Recipients</th>
                                            <th>Sent</th>
                                            <th>Delivered Mails</th>
                                            <th>Bounced Mails</th>
                                            <th>Unique Opens</th>
                                            <th>Unique Clicks</th>
                                        </tr>
                                    </thead>
                                    <thead>
                                        <tr>
                                            <th>Campaign Name</th>
                                            <th>Action Type</th>
                                            <th>Recipients</th>
                                            <th>Sent</th>
                                            <th>Delivered Mails</th>
                                            <th>Bounced Mails</th>
                                            <th>Unique Opens</th>
                                            <th>Unique Clicks</th>
                                        </tr>
                                    </thead>
                                    <thead>
                                        <tr>
                                            <th>Campaign Name</th>
                                            <th>Action Type</th>
                                            <th>Recipients</th>
                                            <th>Sent</th>
                                            <th>Delivered Mails</th>
                                            <th>Bounced Mails</th>
                                            <th>Unique Opens</th>
                                            <th>Unique Clicks</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        if ($campaignsResult && mysqli_num_rows($campaignsResult) > 0) {
                                            while ($campaign = mysqli_fetch_assoc($campaignsResult)) {
                                                echo "<tr>";
                                                echo "<td><a href='viewCampaign.php?id=" . $campaign['id'] . "'>" . htmlspecialchars($campaign['campaign_name']) . "</a></td>";
                                                echo "<td>" . htmlspecialchars($campaign['action']) . "</td>";
                                                echo "<td>" . htmlspecialchars($campaign['list_name']) . "</td>";
                                                echo "<td>" . htmlspecialchars($campaign['sent_date']) . "</td>";
                                                echo "<td>" . fetch_deliveries($conn, htmlspecialchars($campaign['id'])) . "</td>";
                                                echo "<td>" . fetch_bounces($conn, htmlspecialchars($campaign['id'])) . "</td>";
                                                echo "<td>" . fetch_unique_opens($conn, htmlspecialchars($campaign['id'])) . "</td>";
                                                echo "<td>" . fetch_unique_clicks($conn, htmlspecialchars($campaign['id'])) . "</td>";
                                                echo "</tr>";
                                            }
                                        } else {
                                            echo "<tr><td colspan='8'>No matching campaigns found.</td></tr>";
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <footer class="py-2 bg-light">
                <div class="container-fluid" style="display: flex; justify-content: right;">
                    <div class="text-muted text-right small">&copy; <?= date('Y') ?>, Knobly Consulting</div>
                    <!-- <button class="btn btn-sm btn-toggle-mode float-right" onclick="toggleMode()">Toggle Dark Mode</button> -->
                </div>
            </footer>
            </main>
            
        </div>
    </div>



</body>

</html>