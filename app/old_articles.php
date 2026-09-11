<?
require_once './inc/php/validate.logged.php';
require_once 'inc/config.php';
// require_once 'inc/function.php';
include './inc/php/db_config.php';
include './inc/php/function.php';


$act = isset($_POST["act"]) ? $_POST["act"] : '';
$actAfter = isset($_POST["actAfter"]) ? $_POST["actAfter"] : '';

if ($act == '') { ?>

    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>My Collections | News Junction</title>

        <!-- Google Fonts -->
        <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">

        <!-- Bootstrap CSS -->
        <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">

        <!-- Font Awesome -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.0/font/bootstrap-icons.min.css"
            rel="stylesheet">

        <!-- Magnific Popup CSS (only if you need it) -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/magnific-popup.js/1.1.0/magnific-popup.min.css">

        <link rel="stylesheet" href="inc/css/stream.css">
        <link rel="stylesheet" href="inc/css/social.css">
        <link rel="icon" type="image/x-icon" href="grfx/img/logo.ico">

        <!-- jQuery (always load jQuery first) -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>

        <!-- Popper.js (Bootstrap 4 requires Popper.js) -->
        <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>

        <!-- Bootstrap JS (needed for Bootstrap 4) -->
        <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

        <script src="https://cdn.tiny.cloud/1/720y3p95h0psi2n78gt09nbiyiqixqixlrezcaaelxhh34sd/tinymce/7/tinymce.min.js"
            referrerpolicy="origin"></script>

        <!-- Your Custom JavaScript -->
        <script src="inc/js/common.js"></script>
        <script src="inc/js/stream.js"></script>

        <style>
            #panelContent {
                /* padding-right: 8px; */
                height: calc(100vh - 140px);
                overflow: auto;
            }

            .main-content {
                padding-bottom: 0px;
            }

            .tox-promotion {
                display: none !important;
            }

            .breadcrumb {
                display: -ms-flexbox;
                display: flex;
                -ms-flex-wrap: wrap;
                flex-wrap: wrap;
                padding: .75rem 0rem;
                margin-bottom: 1rem;
                list-style: none;
                background-color: transparent !important;
                border-radius: .25rem;
            }

            .badge-warning {
                background-color: #818182;
                border-radius: 10px;
            }

            .breadcrumb {
                color: var(--text-primary);
            }

            .card {
                background-color: #00000000;
            }

            .card-body {
                background-color: var(--bg-card);
                ;
                color: #ffffff87 !important;
                border-radius: 12px;
                -ms-flex: 1 1 auto;
                flex: 1 1 auto;
                min-height: 1px;
                padding: 1.25rem;
                padding-bottom: 5px;
                padding-top: 5px;
            }


            .buttonCreamShare {
                background-color: #6c757d;
            }

            #eSamudaayShare {
                border: none;
                background-color: #6c757d;
            }

            .card-body a {
                color: var(--text-primary);
            }

            .nav-tabs {
                display: flex;
                flex-direction: row;
                flex-wrap: nowrap;
                /* Align items in a row */
                overflow-x: auto;
                /* Enable horizontal scrolling when content overflows */
                padding-bottom: 10px;
                /* Optional: add some spacing at the bottom */
                margin-bottom: 0;
                /* Optional: remove margin at the bottom */
            }

            .nav-tabs::-webkit-scrollbar {
                height: 8px;
            }

            .nav-tabs::-webkit-scrollbar-thumb {
                background-color: #888;
                border-radius: 4px;
            }

            .nav-tabs::-webkit-scrollbar-thumb:hover {
                background-color: #555;
            }

            .hyperlink img {
                border-radius: 10px;
                margin-top: 10px;
                max-width: 30%;
                margin-bottom: 10px;
            }

            @media screen and (min-width:768px) {
                .modal-content {
                    width: 800px !important;
                }

            }

            /* Optional: Add a subtle shadow effect to indicate scrollable content */
            .nav-tabs::after {
                content: '';
                /* Empty content */
                position: absolute;
                top: 0;
                right: 0;
                width: 20px;
                /* Shadow width */
                height: 100%;
                /* background: linear-gradient(to right, rgba(0, 0, 0, 0.1), rgba(0, 0, 0, 0)); */
                /* Fade shadow */
                pointer-events: none;
                /* Prevent interference with clicking */
            }

            /* Individual list item style */
            .nav-item {
                flex-shrink: 0;
                /* Prevent items from shrinking */
            }

            /* Optional: Add styles for the active state */
            .nav-link.active {
                font-weight: bold;
                /* Make active item bold or any other style */
            }

            .openModalButton {
                height: 32px;
                user-select: none;
                cursor: pointer;
                padding-bottom: 4px;
                width: 24px;
            }


            @media screen and (max-width:600px) {
                .text-center {
                    text-align: left !important;
                }

            }

            @media (min-width: 576px) {
                .modal-dialog {
                    max-width: 100%;
                    margin: 1.75rem auto;
                }
            }

            .modal-content,
            .close,
            .form-control,
            .nav-link.active {
                background-color: var(--bg-card);
                color: var(--text-primary);
            }

            a {
                color: var(--text-primary);

            }

            .btn {
                background-color: var(--primary);
            }

            .sideMaincontent {
                width: 70%;
                height: 85vh;
                overflow-y: scroll;
                padding: 30px 20px;
            }

            .first_left_container {
                height: 85vh !important;
            }

            @media (min-width: 768px) {
                .myCollectionSide {
                    padding: 0px !important;
                }

            }

            @media (max-width: 768px) {
                .myCollectionSide {
                    display: none !important;
                }

                .sideMaincontent {
                    width: 100%;
                    height: 85vh;
                    overflow-y: scroll;
                    padding: 0px 0px;
                }

            }

            @media screen and (min-width:1024px) {
                .sideMaincontent {
                    width: 90%;
                    height: 85vh;
                    overflow-y: scroll;
                    padding: 30px 20px;
                }

            }

            .modal {
                position: fixed;
                left: 0;
                z-index: 1050;
                display: none;
                width: 100%;
                height: 100%;
                overflow: hidden;
                outline: 0;
            }
        </style>
    </head>

    <body>
        <div>
            <?php include 'inc/php/social_navbar.php' ?>
            <? include 'inc/php/social_sidebar.php' ?>
            <div class="search-main-content main-content">

                <div id="panelContent">
                    <ol class="breadcrumb my-3">
                        <li class="breadcrumb-item w-100" style="justify-content: space-between;">
                            <div class="text-left ">
                                <h4 class="m-0">Articles</h4>
                            </div>
                        </li>
                    </ol>

                    <div class="tab-content my-3">
                        <div class="tab-pane fade<? if ($actAfter == '' || $actAfter == 'itemActive') { ?> show active<? } ?>"
                            id="itemActive" role="tabpanel">
                            <div class="panelFeeds" style="max-height:calc(100vh - 245px)">
                                <?
                                $sql = "SELECT id,title,url FROM user_collection WHERE  share_user_id IS NULL AND is_archive IS NULL ORDER BY id DESC";
                                $result = mysqli_query($creamdb, $sql);
                                $numRows = mysqli_num_rows($result);
                                if ($numRows == 0) {
                                    echo '<div class="p-2">You do not have any items in your collections!</div>';
                                } else {
                                    while ($row = mysqli_fetch_assoc($result)) {
                                        $collectionId = $row['id'];
                                        $collectionTitle = $row['title'];
                                        $collectionURL = $row['url'];
                                        $collectionPublisher = substr($collectionURL, strpos($collectionURL, ".") + 1);
                                        $collectionPublisher = ucfirst(strtok($collectionPublisher, '.'));
                                        if ($collectionPublisher == '')
                                            $collectionPublisher = 'News Junction';
                                        $collectionLink = '/view.php?id=' . $collectionId;
                                        if ($gUserSubdomain <> '') {
                                            $collectionLinkFull = 'https://newsjunction.net' . $collectionLink;
                                        } else {
                                            $collectionLinkFull = 'https://newsjunction.net' . $collectionLink;
                                        }
                                        ?>
                                        <div class="card p-0 mb-3  border-0">
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="col-12 col-md-10">
                                                        <h4 class="mb-0"><a href="<?= $collectionLinkFull ?>"
                                                                target="_blank"><?= $collectionTitle ?></a></h4>
                                                        <div style="color:#f26522" class="mb-3">Publisher:
                                                            <?= $collectionPublisher ?>
                                                        </div>
                                                    </div>
                                                </div>

                                            </div>
                                        </div>
                                    <?
                                    }
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <? include 'inc/php/footer.php' ?>
    </body>

    </html>

<? } ?>