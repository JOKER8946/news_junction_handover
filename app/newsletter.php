<?
require_once 'inc/php/validate.logged.php';
require_once 'inc/php/function.php';
require_once 'inc/php/db_config.php';
require_once 'inc/function.php';

$act = '';
if (!empty($_POST)) $act = isset($_POST["act"]) ? $_POST["act"] : '';



function fetchNewsLetterHeading($creamdb, $gUserId)
{
    $stmt = $creamdb->prepare("SELECT news_title FROM user WHERE id = ?");
    $stmt->bind_param("i", $gUserId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        return $row['news_title'];
    } else {
        return null;
    }
    $stmt->close();
}


// Send Newsletter
if ($act == 'sendNewsletter') {
    $newsId = isset($_POST['newsId']) ? $_POST['newsId'] : '';
    $sendGridSenderId = isset($_POST['sendGridSenderId']) ? $_POST['sendGridSenderId'] : '';
    $sendGridListId = isset($_POST['sendGridListId']) ? $_POST['sendGridListId'] : '';
    $sendGridSubject = isset($_POST['sendGridSubject']) ? $_POST['sendGridSubject'] : '';
    if ($newsId != '' && $sendGridSenderId != '' && $sendGridListId != '' && $sendGridSubject != '') {
        $newsletterBody = buildNewsletter($newsId)['html_data'];
        $newsletterBody = str_replace('"', '\"', $newsletterBody);
        $newsletterBody = str_replace(array("\r\n", "\n\r", "\n", "\r"), "", $newsletterBody);

        $sql = "SELECT send_grid_key,send_grid_suppression_id FROM user WHERE id=$gUserId";
        $result = mysqli_query($creamdb, $sql);
        $row = mysqli_fetch_assoc($result);
        $userSendGridKey = $row['send_grid_key'];
        $userSendGridSuppressionId = $row['send_grid_suppression_id'];
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.sendgrid.com/v3/marketing/singlesends",
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => "{\"name\":\"Sent from News Junction\",\"send_to\":{\"list_ids\":[\"$sendGridListId\"]},\"email_config\":{\"sender_id\":$sendGridSenderId,\"suppression_group_id\":$userSendGridSuppressionId,\"subject\":\"$sendGridSubject\",\"html_content\":\"$newsletterBody\"}}",
            CURLOPT_HTTPHEADER => array(
                "authorization: Bearer $userSendGridKey",
                "content-type: application/json"
            ),
        ));
        $response = curl_exec($curl);
        $err = curl_error($curl);
        if ($err) {
        } else {
            $arrResponse = json_decode($response, true);
            if (array_key_exists("id", $arrResponse)) {
                $sendId = $arrResponse['id'];
                curl_setopt_array($curl, array(
                    CURLOPT_URL => "https://api.sendgrid.com/v3/marketing/singlesends/$sendId/schedule",
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_CUSTOMREQUEST => "PUT",
                    CURLOPT_POSTFIELDS => "{\"send_at\":\"now\"}",
                    CURLOPT_HTTPHEADER => array(
                        "authorization: Bearer $userSendGridKey",
                        "content-type: application/json"
                    ),
                ));
                $response = curl_exec($curl);
                $sql = "INSERT INTO user_blast(user_id,newsletter_id,blast_provider,blast_subject,blast_id,blast_key,date_created) VALUES($gUserId,$newsId,'SendGrid','$sendGridSubject','$sendId','$userSendGridKey',Now())";
                mysqli_query($creamdb, $sql);
                echo 'OK';
            }
        }
        curl_close($curl);
    }
}


// Delete Newsletter
if ($act == 'deleteNewsletter') {
    $newsletterId = isset($_POST['delId']) ? $_POST['delId'] : '';
    if ($newsletterId != '') {
        $sql = "DELETE FROM user_newsletter WHERE id=$newsletterId AND user_id=$gUserId";
        mysqli_query($creamdb, $sql);
        echo "OK";
    }
}


// Show Delete Newsletter
if ($act == 'delNewsletter') {
    $newsletterId = isset($_POST['id']) ? $_POST['id'] : '';
?>
    <div class="widget">
        <form id="frmDel" name="frmDel">
            <div class="card">
                <div class="card-header bg-dark">
                    <h5 class="mb-0 text-light">Delete Newsletter</h5>
                </div>
            </div>
            <div id="widget_B" style="padding: 15px 25px;">
                <p>You are about to delete this Newsletter from your account!</p>
            </div>
            <div id="widget_F" style="border-top: 1px solid #ebedf2; padding: 20px 10px;">
                <div class="col">
                    <button type="button" class="btn btn-primary " onclick="return chkDelNewsletter()">Confirm</button>
                </div>
            </div>
            <!-- Hidden input fields for additional data -->
            <input type="hidden" id="delId" name="delId" value="<?= $newsletterId ?>" />
            <input type="hidden" id="act" name="act" value="deleteNewsletter" />
        </form>
    </div>
<?
}


// Default
if ($act == '') {

    if ($gUserPlan == 1) {
        $htmlSendGridListId = '';
        $htmlSendGridSenderId = '';
        $sql = "SELECT send_grid_key FROM user WHERE id=$gUserId";
        $result = mysqli_query($creamdb, $sql);
        $row = mysqli_fetch_assoc($result);
        $userSendGridKey = $row['send_grid_key'];

        if ($userSendGridKey <> '') {
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://api.sendgrid.com/v3/marketing/lists?page_size=50",
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_POSTFIELDS => "{}",
                CURLOPT_HTTPHEADER => array(
                    "authorization: Bearer $userSendGridKey",
                    "content-type: application/json"
                ),
            ));
            $response = curl_exec($curl);
            $err = curl_error($curl);
            if ($err) {
                echo "cURL Error #:" . $err;
            } else {
                $arrResponse = json_decode($response, true);
                if (array_key_exists("result", $arrResponse)) {
                    $arrList = $arrResponse['result'];
                    foreach ($arrList as &$value) {
                        $htmlSendGridListId .= "<option value='" . $value['id'] . "'>" . $value['name'] . "</option>";
                    }
                    curl_setopt_array($curl, array(
                        CURLOPT_URL => "https://api.sendgrid.com/v3/verified_senders",
                        CURLOPT_SSL_VERIFYPEER => false,
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_CUSTOMREQUEST => "GET",
                        CURLOPT_POSTFIELDS => "{}",
                        CURLOPT_HTTPHEADER => array(
                            "authorization: Bearer $userSendGridKey",
                            "content-type: application/json"
                        ),
                    ));
                    $response = curl_exec($curl);
                    $arrResponse = json_decode($response, true);
                    if (array_key_exists("results", $arrResponse)) {
                        $arrList = $arrResponse['results'];
                        foreach ($arrList as &$value) {
                            $htmlSendGridSenderId .= "<option value='" . $value['id'] . "'>" . $value['nickname'] . "</option>";
                        }
                    }
                }
            }
        }
    }
?>

    <!DOCTYPE html>
    <html lang="en">

    <head>

        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <title>Newsletter | News Junction</title>
        <!-- jQuery -->
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>


        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <!-- Font Awesome CSS -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css" integrity="sha384-9aIt2nRpC12Uk9gS9baDl411NQApFmC26EwAOH8WgZl5MYYxFfc+NcPb1dKGj7Sk" crossorigin="anonymous" />
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.0.0/animate.min.css" />
        <link rel="stylesheet" href="assets/old/style.css" />
        <link rel="stylesheet" href="inc/css/styles.css" />
        <link rel="icon" type="image/x-icon" href="/img/logo.ico">
        <link rel="stylesheet" href="inc/css/social.css">

        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.8.0/Chart.min.js" crossorigin="anonymous"></script>
        <script src="https://cdn.tiny.cloud/1/720y3p95h0psi2n78gt09nbiyiqixqixlrezcaaelxhh34sd/tinymce/7/tinymce.min.js" referrerpolicy="origin"></script>
        <!-- <script src="https://cdn.tiny.cloud/1/u5oz235qw5jiqww4udm90ocp9zg2rncblqo2ch0ym3twjgyt/tinymce/5/tinymce.min.js" referrerpolicy="origin"></script> -->
        <script src="https://js.zohostatic.com/books/zfwidgets/assets/js/zf-widget.js"></script>

        <!-- <script src="assets/old/common.js"></script> -->
        <!-- <script src="assets/old/genai_func.js"></script> -->


        <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
        <style>
            .modal {
                display: none;
                position: fixed;
                z-index: 1;
                left: 0;
                top: 0;
                width: 100%;
                height: 100%;
                overflow: auto;
                background-color: var(--bg-card);
                background-color: rgba(0, 0, 0, 0.4);
                padding-top: 60px;
            }

            .modal-content {
                background-color: var(--bg-card);
                margin: 5% auto;
                padding: 20px;
                border: 1px solid #888;
                width: 40%;
                margin-left: 35%;
            }

            @media screen and (max-width: 768px) {
                .modal-content {
                    width: 100%;
                    margin-left: 0px;
                }
            }

            .close {
                color: var(--text-primary);
                float: right;
                font-size: 28px;
                font-weight: bold;
            }

            .close:hover,
            .close:focus {
                color: var(--text-primary);
                text-decoration: none;
                cursor: pointer;
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

            style attribute {
                padding-top: 40px;
                padding-bottom: 20px;
                font-family: Georgia, serif;
                font-size: 16px;
                line-height: 1.5em;
                color: black;
            }

            @media screen and (min-width:768px) {

                .container-fluid,
                .container-lg,
                .container-md,
                .container-sm,
                .container-xl {
                    width: 75% !important;
                    padding-right: 15px;
                    padding-left: 15px;
                    margin-right: auto;
                    margin-left: auto;
                }

            }

            style attribute {
                padding-top: 40px;
                padding-bottom: 20px;
                font-family: Georgia, serif;
                font-size: 16px;
                line-height: 1.5em
            }


            @media screen and (max-width: 768px) {
                .container-fluid {

                    max-width: 1400px;
                    margin: 0 auto;

                }

            }

            .btn-link {
                color: var(--text-primary);

            }



            table {
                background-color: #ecf0f1;
                color: #333;
            }

            .float-left a {
                color: var(--text-primary);

            }
        </style>
        <script>
            function chkDelNewsletter() {
                $('#panelStatus').html('');
                $.ajax({
                        method: 'POST',
                        url: '',
                        data: $('#frmDel').serializeArray()
                    })
                    .done(function(response) {
                        if (response == 'OK') {
                            $('#widget_B').html('Newsletter has been deleted!');
                            $('#widget_F').hide();
                            location.reload();
                        } else {
                            $('#panelStatus').html('<small>Error: Could not delete Newsletter!</small>');
                        }
                    });
                return false;
            }
            $(function() {
                // Attach a click event handler to elements with the class .delRow
                $('.delRow').on('click', function() {
                    // Get the id from the data-id attribute of the clicked element
                    var postData = {
                        act: 'delNewsletter',
                        id: $(this).data('id')
                    };

                    // Perform the AJAX request
                    $.ajax({
                        method: 'POST',
                        url: '', // Specify the URL here
                        data: postData,
                        success: function(response) {
                            // Insert the response data into the modal
                            $('#modalContent').html(response);

                            // Show the modal
                            $('#delModal').css('display', 'block');

                            // Optional: You can add code to remove the row from the UI if needed
                            // For example:
                            // $(this).closest('tr').remove();
                        },
                        error: function(xhr, status, error) {
                            // Handle error response here
                            console.error('Error deleting the newsletter', status, error);
                            $('#modalContent').html('An error occurred while deleting the newsletter.');
                            $('#delModal').css('display', 'block');
                        }
                    });
                });

                // Close the modal when the user clicks on the "x"
                $('.close').on('click', function() {
                    $('#delModal').css('display', 'none');
                });

                // Close the modal if the user clicks outside the modal content
                $(window).on('click', function(event) {
                    if (event.target == $('#delModal')[0]) {
                        $('#delModal').css('display', 'none');
                    }
                });
            });
        </script>

        <script>
            // Function to fetch newsletter content and update index.php
            function fetchNewsletterContent(newsContent, campaignName) {
                try {
                    // Trim the newsletter content
                    newsContent = newsContent.trim();

                    // Store the data in localStorage
                    localStorage.setItem('newsletterContent', newsContent);
                    localStorage.setItem('campaignName', campaignName);

                    // Open the email sender interface
                    window.open('Xpress/index.php', '_blank');
                } catch (error) {
                    console.error('Error fetching newsletter content:', error);
                }
            }
        </script>


    </head>

    <body>
        <div class="container">
            <? include 'inc/php/social_navbar.php' ?>
            <? include 'inc/php/social_sidebar.php' ?>

            <main class="search-main-content main-content">
                <div id="panelContent" class="container-fluid">
                    <ol class="breadcrumb my-3">
                        <li class="breadcrumb-item">
                            <h4 class="m-0">Newsletter</h4>
                        </li>
                    </ol>
                    <?php
                    $sql = "SELECT id,date_created FROM user_newsletter WHERE user_id=$gUserId ORDER BY id DESC";
                    $result = mysqli_query($creamdb, $sql);
                    $numRows = mysqli_num_rows($result);
                    if ($numRows == 0) {
                    ?>
                        <div class="px-3">You have not created any newsletters!</div>
                    <?php
                    } else {
                    ?>
                        <div class="row mb-4">
                            <div class="col">
                                <div class="accordion" id="accordionNewletter">
                                    <?php
                                    $numNewsletter = 1;
                                    while ($row = mysqli_fetch_assoc($result)) {
                                        $newsId = $row['id'];
                                        $newsDate = date('M d, Y', strtotime($row['date_created']));
                                    ?>
                                        <!-- Bootstrap CSS should be loaded once at the top of the document -->
                                        <div class="card">
                                            <div class="card-header m-0 p-0 py-1" id="heading<?php echo $numNewsletter; ?>">
                                                <div class="float-left">
                                                    <a class="btn btn-link" type="button" data-toggle="collapse" data-target="#collapse<?php echo $numNewsletter; ?>" aria-expanded="true" aria-controls="collapse<?php echo $numNewsletter; ?>">
                                                        <?php echo $newsDate; ?>
                                                    </a>
                                                </div>
                                                <div class="float-right pt-2 pr-3">
                                                    <?php if ($gUserSubdomain <> '') { ?>
                                                        <!-- <a href="https://newsjunction.net/view_newsletter.php?id=<?php echo $newsId; ?>" title="Publish Newsletter" target="_blank"> -->
                                                        <a href="https://<?php echo $gUserSubdomain; ?>.newsjunction.net/view_newsletter.php?id=<?php echo $newsId; ?>" title="Publish Newsletter" target="_blank">
                                                            <i class="fas fa-upload fa-sm text-muted pr-2"></i>
                                                        </a>
                                                    <?php } else { ?>
                                                        <a href="view_newsletter.php?id=<?php echo $newsId; ?>" title="Publish Newsletter" target="_blank">
                                                            <i class="fas fa-upload fa-sm text-muted pr-2"></i>
                                                        </a>
                                                    <?php } ?>
                                                    <a href="#" title="Delete Newsletter">
                                                        <i data-id="<?php echo $newsId; ?>" class="delRow far fa-trash-alt fa-sm text-muted"></i>
                                                    </a>
                                                </div>
                                            </div>
                                            <div id="collapse<?php echo $numNewsletter; ?>" class="collapse<?php if ($numNewsletter == 1) { ?> show<?php } ?>" aria-labelledby="heading<?php echo $numNewsletter; ?>" data-parent="#accordionNewletter">
                                                <div id="panelNewsletter<?php echo $numNewsletter; ?>" class="card-body p-0" align="center">
                                                    <?php echo buildNewsletter($newsId)['html_data']; ?>

                                                    <?php if ($gUserPlan == 0) { ?>
                                                        <div class="alert alert-success" role="alert">
                                                            Sending of Newsletter feature is only available in <b>Pro</b> plan! Go to My Account to upgrade.
                                                        </div>
                                                    <?php } else { ?>
                                                        <button class="btn btn-primary mb-4" onclick="fetchNewsletterContent($('#panelNewsletter<?php echo $numNewsletter; ?> table')[0].outerHTML, '<?php echo fetchNewsLetterHeading($creamdb, $gUserId); ?>')">Send Email</button>
                                                    <?php } ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php
                                        $numNewsletter += 1;
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    <?php
                    }
                    ?>
                    <div id="delModal" class="modal">
                        <div class="modal-content">
                            <span class="close">&times;</span>
                            <div id="modalContent"></div>
                        </div>
                    </div>
                </div>
            </main>
        </div>

        <? include 'inc/php/footer.php' ?>


    </body>

    </html>
<? } ?>