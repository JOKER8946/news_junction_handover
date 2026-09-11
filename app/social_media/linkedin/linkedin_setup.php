<?
ini_set('display_startup_errors', 1);

include '../../assets/php/validate.logged.php';
include 'linkedin_function.php';
include '../db_connect.php';
include '../../assets/php/function.php';
$credentials = json_decode(file_get_contents('linkedin_credentials.json'), true);
$liLoggedStatus = linkedin_login_status($db, $gUserId);

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
    <link rel="stylesheet" href="/assets/css/styles.css" />
    <link rel="stylesheet" href="/assets/css/genai_style.css" />
    <style>
        .col {

            margin-top: 0px !important;
            margin-left: -2px !important;
        }



        .profile-info {
            font-size: 1rem;
            /* Adjust font size for better readability */
            color: black;
            text-align: left;
            /* Center-align content */
            margin-left: -20px;
            /* Shift the card slightly to the left */
            position: relative;
            /* Ensures proper placement in the layout */
        }


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

            /* Breadcrumb Styles for Mobile */
            .breadcrumb {
                padding: 0.5rem;
                margin-top: 3rem !important;

            }

            .breadcrumb-item {
                display: flex;
                flex-direction: column;
                align-items: center;
                text-align: center;
                margin-bottom: 1rem;
            }

            .breadcrumb-item h4 {
                font-size: 1.25rem;
                margin-bottom: 0.5rem;
            }

            .breadcrumb-item .btn {
                font-size: 0.875rem;
                padding: 0.5rem 0.75rem;
                width: 100%;
                /* Buttons take full width */
                margin-bottom: 0.25rem;
                /* Spacing between buttons */
            }

            .breadcrumb-item .form-inline {
                display: flex;
                flex-direction: column;
                /* Stacks inputs and buttons */
                width: 100%;
                align-items: stretch;
                /* Stretches inputs and buttons to full width */
            }

            .breadcrumb-item .form-inline input,
            .breadcrumb-item .form-inline button {
                width: 100%;
                /* Inputs and buttons take full width */
                padding: 0.5rem;
                /* Larger touch targets for mobile */
                margin: 0.25rem 0;
                /* Spacing between input fields */
                box-sizing: border-box;
                /* Includes padding in width */
            }

            .breadcrumb-item a.btn {
                width: 100%;
                /* Links take full width */
                margin-top: 0.25rem;
                /* Spacing from other elements */
                padding: 0.5rem;
                /* Larger clickable area */
            }

            /* Main Content for Mobile */
            .main-content {
                margin: 10px;
                padding: 10px;
                margin-left: 15px;
                margin-top: -25px;
            }

            /* Profile Info Styling */
            .profile-info {
                font-size: 1rem;
                /* Adjust font size for better readability */
                color: black;
                text-align: center;
                /* Center-align content */
                margin-left: -30px;

            }

            .text-left {
                margin-left: -110px;
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
        @media (min-width: 769px) {
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

            .main-content {
                margin: 120px;

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

<body>

    <? include "../../assets/php/navbar.php" ?>

    <div class="main-content">
        <ol class="breadcrumb my-3">
            <li class="breadcrumb-item w-100 d-flex justify-content-between align-items-center flex-wrap">
                <div class="text-left mb-2 mb-md-0">
                    <h4 class="mt-1">LinkedIn</h4>
                    <a href="<?= htmlspecialchars(linkedin_login_consent($credentials, 'https://newsjunction.net/social_media/linkedin/linkedin_setup_handler.php')) ?>" class="btn btn-primary mt-2"><i class="fab fa-linkedin-in"></i> Login to LinkedIn</a>
                </div>
                <!-- <div class="d-flex align-items-center ml-auto mt-2 mt-md-0 flex-wrap justify-content-end">
                    <a href="../../dashboard.php" class="btn btn-primary" style="background-color: #007bff; border-color: #007bff;">
                        <i class="bi bi-arrow-left"></i> Back to Cream
                    </a>
                </div> -->
            </li>
        </ol>
        <? if ($liLoggedStatus == true) {
        ?>
            <? linkedin_display_profile($db, $gUserId) ?>

        <? } else {
        ?>
            <div class="col">
                You have not logged in into the LinkedIn account to use this feature.
            </div>
        <? }
        ?>
    </div>
    <? include '../../assets/php/footer.php' ?>
</body>
</html>