<?
ini_set('display_startup_errors', 1);

include '../../inc/validate.logged.php';
include 'fb_function.php';
include '../db_connect.php';
$credentials = json_decode(file_get_contents('fb_credentials.json'), true);
$fbLoggedStatus = facebook_login_status($db, $gUserId);

if (isset($_GET['log']) && $_GET['log'] == 'npf') {
?>
    <script>
        alert("No Pages Found for the account");
        // Remove the 'log' parameter from the URL
        const url = new URL(window.location);
        url.searchParams.delete('log');
        window.history.replaceState({}, document.title, url);
    </script>
<? } ?>

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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.0.0/animate.min.css" />
    <link rel="stylesheet" href="../../inc/style.css" />
    <link rel="stylesheet" href="../../inc/genai_style.css" />
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
    <?php include "../navbar.php" ?>
    <div id="layoutSidenav">
        <?php include '../sidebar.php' ?>
        <div id="layoutSidenav_content">
            <main>
                <div id="panelContent" class="container-fluid">
                    <div class="main-content">
                        <ol class="breadcrumb my-3">
                            <li class="breadcrumb-item w-100 d-flex justify-content-between align-items-center flex-wrap">
                                <div class="text-left mb-2 mb-md-0">
                                    <h4 class="mt-1">LinkedIn</h4>
                                    <a href="<?= htmlspecialchars(facebook_login_consent($credentials, 'https://newsjunction.net/facebook/facebook_setup_handler.php')) ?>" class="btn btn-primary mt-2"><i class="fab fa-facebook-f"></i> Login to Facebook</a>
                                </div>
                                <div class="d-flex align-items-center ml-auto mt-2 mt-md-0 flex-wrap justify-content-end">
                                    <a href="../../dashboard.php" class="btn btn-primary" style="background-color: #007bff; border-color: #007bff;">
                                        <i class="bi bi-arrow-left"></i> Back to Cream
                                    </a>
                                </div>
                            </li>
                        </ol>
                        <? if ($fbLoggedStatus == true) {
                        ?>
                            <div class="col">
                                <table class="table table-striped table-bordered">
                                    <thead styles="background-color:#e9ecef">
                                        <tr>
                                            <td colspan="3" align="center"><b>Facebook Pages</b></td>
                                        </tr>
                                        <tr>
                                            <th>Page Name</th>
                                            <th>Status</th>
                                            <th>Update Page</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $pages_data = facebook_fetch_user_pages($db, $gUserId);

                                        if ($pages_data == null) {
                                        ?>
                                            <tr>
                                                <td colspan="3">No Pages associated in the Facebook Account</td>
                                            </tr>
                                            <?
                                        } else {
                                            foreach ($pages_data as $page_data) {
                                            ?>
                                                <tr>
                                                    <td><?= $page_data['pages_name'] ?></td>
                                                    <td>Active</td>
                                                    <td><button class="btn btn-primary" onclick="updateFBPages('<?= $page_data['pages_id'] ?>', '<?= $page_data['pages_token'] ?>')">Update/Refresh</button></td>
                                                </tr>
                                        <? }
                                        } ?>
                                    </tbody>
                                </table>
                            </div>
                        <? } else {
                        ?>
                            <div class="col">
                                You have not logged in into the facebook account to use this feature.
                            </div>
                        <? }
                        ?>
                    </div>
                </div>
        </div>
        <footer class="py-2 bg-light">
            <div class="container-fluid" style="display: flex; justify-content: right;">
                <div class="text-muted text-right small">&copy; <?= date('Y') ?>, Knobly Consulting</div>
            </div>
        </footer>
        </main>
    </div>
</body>


<script>
    function updateFBPages($pageId, $pageToken) {
        $.ajax({
            url: 'update_fb_pages.php', // Replace with your API endpoint
            type: 'POST', // Use GET or POST depending on your needs
            data: {
                // Add any data you want to send with the request
                page_id: $pageId,
                page_token: $pageToken
            },
            success: function(response) {
                // Handle a successful response
                console.log('Success:', response);
            },
            error: function(xhr, status, error) {
                // Handle any errors
                console.error('Error:', status, error);
            },
            complete: function() {
                // Optional: code to run after the request completes (whether it was successful or not)
                console.log('Request completed');
            }
        });
    }
</script>

</html>