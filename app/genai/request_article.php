<?
// Cream: Request Article

require_once 'inc/php/validate.logged.php';
require_once 'inc/config.php';
require_once 'genai/genai_function.php';
require_once 'inc/php/function.php';
include 'vendor/autoload.php';

$act = '';
if (!empty($_POST)) $act = isset($_POST["act"]) ? $_POST["act"] : '';

if ($act == 'generateArticle') {
    $model = "grok-beta"; //Ensure to update the GPT model when required
    $type = 'request_article';
    $system_prompt = array(
        array(
            "role" => "system",
            "content" => "Try to complete the sentence within the max_tokens."
        ),
        array(
            "role" => "system",
            "content" => "Try to complete the sentence even if the max_tokens is reached."
        ),
        array(
            "role" => "system",
            "content" => "Always use mysql compatible svg embeds to show icons or images if you are using them to pepper the response."
        ),
        array(
            "role" => "system",
            "content" => "You are a report generator where you are able to generate the article."
        ),
        array(
            "role" => "system",
            "content" => "Please respond only with the result and do not try to give any response message. Don't even give the result in the code box. Reply only with the result and this is very very very important!!"
        ),
        array(
            "role" => "system",
            "content" => "Please mention the heading of the article at the beginning as per the markdown syntax."
        ),
        array(
            "role" => "system",
            "content" => "use the primary source as the reference, crawl the given url for necessary information"
        ),
        array(
            "role" => "system",
            "content" => "Please provide the content related to the user prompt and related latest information."
        )

    );

    $articleHeadline = isset($_POST['articleHeadline']) ? $_POST['articleHeadline'] : '';
    $articleObjective = isset($_POST['articleObjective']) ? $_POST['articleObjective'] : '';
    $articleTargetGroup = isset($_POST['articleTargetGroup']) ? $_POST['articleTargetGroup'] : '';
    $articleKeywords = isset($_POST['articleKeywords']) ? $_POST['articleKeywords'] : '';
    $articleNumWords = isset($_POST['articleNumWords']) ? $_POST['articleNumWords'] : '';
    $articleNumImages = isset($_POST['articleNumImages']) ? $_POST['articleNumImages'] : '';
    $articleOutline = isset($_POST['articleOutline']) ? $_POST['articleOutline'] : '';
    $articlePrimarySource = isset($_POST['articlePrimarySource']) ? $_POST['articlePrimarySource'] : '';
    $articleSecondarySource = isset($_POST['articleSecondarySource']) ? $_POST['articleSecondarySource'] : '';
    $articleImageLink = isset($_POST['imageLink']) ? $_POST['imageLink'] : '';

    $user_prompt = array(
        array(
            "role" => "user",
            "content" => 'I wanted to create an article which will be a typical article but amazing one.'
        ),
        array(
            "role" => "user",
            "content" => 'The title of the article is "' . $articleHeadline . '".'
        ),
        array(
            "role" => "user",
            "content" => 'The main objective of the article is to ' . $articleObjective . '.'
        ),
        array(
            "role" => "user",
            "content" => 'The target group for the article are "' . $articleTargetGroup . '"'
        ),
        array(
            "role" => "user",
            "content" => 'The main keywords for the article are "' . $articleKeywords . '" and these keywords are really important.'
        ),
        array(
            "role" => "user",
            "content" => 'And try to complete within ' . $articleNumWords . ' words as I want to complete within these ' . $articleNumWords . ' words'
        )
    );

    if ($articleNumImages != '') {
        $user_prompt[] = array(
            "role" => "user",
            "content" => 'If possible try to have probably ' . $articleNumImages . ' images or graphics in this article. Give the real image links and not the sample one. Very important'
        );
    }

    if ($articleOutline != '') {
        $user_prompt[] = array(
            "role" => "user",
            "content" => 'The outline of the article should contain ' . $articleOutline
        );
    }
    if ($articlePrimarySource != '') {
        $user_prompt[] = array(
            "role" => "user",
            "content" => 'The primary source for this article can be viewed in this link "' . $articlePrimarySource . '"'
        );
    }
    if ($articleSecondarySource != '') {
        $user_prompt[] = array(
            "role" => "user",
            "content" => 'The outline of the article should contain "' . $articleSecondarySource
        );
    }

    if ($articleImageLink != '') {
        $user_prompt[] = array(
            "role" => "user",
            "content" => 'Use this above image link to put the image which are: "' . $articleImageLink . '". Use the first image link as the main article title image. '
        );
    }

    $response = processPrompt($type, $system_prompt, $user_prompt, $model);

    $articleData = extractTitleAndDescription($response);


    // $Parsedown = new Parsedown();

    // if ($articleData !== null) {
    //     $articleData['description_html'] = $Parsedown->text($articleData['description']);
    //     $articleData['title_html'] = $Parsedown->text($articleData['title']);

    //     echo "<h1>" . $articleData['title_html'] . "</h1>";
    //     echo $articleData['description_html'];
    // } else {
    //     echo "Error: Could not extract title and description.";
    // }


?>
    <style>
        #generated_response img {
            display: block;
            width: 45vw;
        }
    </style>
    <!-- <div>
        <?= htmlspecialchars($response) ?>
    </div><br><br> -->
    <div id="generated_response">
        <?= $response ?>
    </div>
    <div>
        <button class='btn btn-primary' onclick="article_save(<?= htmlspecialchars($articleData['title'], ENT_QUOTES, 'UTF-8') ?>,<?= htmlspecialchars($articleData['description'], ENT_QUOTES, 'UTF-8') ?>)">Save to Collections</button>
    </div>
    <script>
        $(document).ready(function() {
            // Get the Markdown content from the hidden div
            var markdownContent = $('#generated_response').text();

            // Convert Markdown to HTML using marked.js
            var htmlContent = convert_text(markdownContent);

            // Display the converted HTML
            $('#generated_response').html(htmlContent);
        });
    </script>

<?
}

// Create Post
if ($act == 'sendRequest') {
    $articleHeadline = isset($_POST['articleHeadline']) ? $_POST['articleHeadline'] : '';
    $articleObjective = isset($_POST['articleObjective']) ? $_POST['articleObjective'] : '';
    $articleTargetGroup = isset($_POST['articleTargetGroup']) ? $_POST['articleTargetGroup'] : '';
    $articleKeywords = isset($_POST['articleKeywords']) ? $_POST['articleKeywords'] : '';
    $articleNumWords = isset($_POST['articleNumWords']) ? $_POST['articleNumWords'] : '';
    $articleNumImages = isset($_POST['articleNumImages']) ? $_POST['articleNumImages'] : '';
    $articleOutline = isset($_POST['articleOutline']) ? $_POST['articleOutline'] : '';
    $articlePrimarySource = isset($_POST['articlePrimarySource']) ? $_POST['articlePrimarySource'] : '';
    $articleSecondarySource = isset($_POST['articleSecondarySource']) ? $_POST['articleSecondarySource'] : '';
    if ($articleHeadline != '' && $articleObjective != '') {
        $tmpHTML = "";
        $tmpHTML .= "<html>";
        $tmpHTML .= "<body>";
        $tmpHTML .= "<div style=\"font-family:Arial;font-size:12px;\">\r\n";
        $tmpHTML .= "The following has been requested from Knobly Cream:<br><br>\r\n";
        $tmpHTML .= "<b>Request by:</b><br>\r\n";
        $tmpHTML .= "$gUserName [$gUserEmail]<br><br>\r\n";
        $tmpHTML .= "<b>Headline:</b><br>\r\n";
        $tmpHTML .= "$articleHeadline<br><br>\r\n";
        $tmpHTML .= "<b>Objective:</b><br>\r\n";
        $tmpHTML .= "$articleObjective <br><br>\r\n";
        $tmpHTML .= "<b>Target Group:</b><br>\r\n";
        $tmpHTML .= "$articleTargetGroup <br><br>\r\n";
        $tmpHTML .= "<b>Keywords:</b><br>\r\n";
        $tmpHTML .= "$articleKeywords<br><br>\r\n";
        $tmpHTML .= "<b>Number of words:</b><br>\r\n";
        $tmpHTML .= "$articleNumWords<br><br>\r\n";
        $tmpHTML .= "<b>Number of Pictures/Graphics/etc.:</b><br>\r\n";
        $tmpHTML .= "$articleNumImages<br><br>\r\n";
        $tmpHTML .= "<b>Outline:</b><br>\r\n";
        $tmpHTML .= "$articleOutline<br><br>\r\n";
        $tmpHTML .= "<b>Primary Source:</b><br>\r\n";
        $tmpHTML .= "$articlePrimarySource<br><br>\r\n";
        $tmpHTML .= "<b>Secondary Source:</b><br>\r\n";
        $tmpHTML .= "$articleSecondarySource<br><br>\r\n";
        $tmpHTML .= "Warm Regards,<br>\r\n";
        $tmpHTML .= "Knobly Cream<br>\r\n";
        $tmpHTML .= "</body>";
        $tmpHTML .= "</html>";
        sendEmail('Site Administrator', 'YOUR_ADMIN_EMAIL', '', 'Knobly Cream: Request Article', $tmpHTML);
        echo 'Thank you for your submission!<br>We will get back to you at the earliest.';
    }
}




// Default
if ($act == '') {
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

        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <!-- Font Awesome CSS -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css" integrity="sha384-9aIt2nRpC12Uk9gS9baDl411NQApFmC26EwAOH8WgZl5MYYxFfc+NcPb1dKGj7Sk" crossorigin="anonymous" />
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.0.0/animate.min.css" />
        <!-- <link rel="stylesheet" href="inc/style.css" /> -->
        <link rel="stylesheet" href="inc/css/styles.css" />
        <link rel="stylesheet" href="inc/genai_style.css" />
        <link rel="icon" type="image/x-icon" href="/img/logo.ico">
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.min.js" integrity="sha384-OgVRvuATP1z7JjHLkuOU7Xw704+h835Lr+6QL9UvYjZE3Ipu6Tp75j7Bh/kR0JKI" crossorigin="anonymous"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.8.0/Chart.min.js" crossorigin="anonymous"></script>
        <script src="https://cdn.tiny.cloud/1/u5oz235qw5jiqww4udm90ocp9zg2rncblqo2ch0ym3twjgyt/tinymce/5/tinymce.min.js" referrerpolicy="origin"></script>
        <script src="https://js.zohostatic.com/books/zfwidgets/assets/js/zf-widget.js"></script>
        <script src="/inc/old/common.js"></script>
        <script src="/inc/js/genai_func.js"></script>
        <style>
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

            .container-fluid,
            .container-lg,
            .container-md,
            .container-sm,
            .container-xl {
                width: 75%;
                padding-right: 0px;
                padding-left: 0px;
                margin-bottom: 60px;
                margin-right: auto;
                margin-left: auto;
            }

            .breadcrumb {
                background-color: transparent !important;
            }

            .m-0 {
                color: e9ecef;
            }

            .form-control {
                background-color: #e9ecef;
            }

            .btn-primary {
                background-color: #db5919 !important;
                border-color: #db5919 !important;
            }

            .btn-primary:focus {
                background-color: #db5919 !important;
                border-color: #db5919 !important;
                box-shadow: none !important;
            }



            /* Container Padding */
            @media screen and (max-width: 768px) {
                .container-fluid {
                    padding: 0px 0 !important;
                    max-width: 1400px;
                    margin: 0 auto;

                }

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
                padding: 30px 0;
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
        </style>
    </head>

    <body class="sb-nav-fixed">

        <?php include 'inc/php/social_navbar.php' ?>

        <div class="sideWithMainContainer">
            <div class="col-md-2">
                <? include 'inc/php/sidebar.php' ?>
            </div>
            <main class="col-sm-12 col-md-10 sideMaincontent">
                <div id="panelContent" class="container-fluid">
                    <ol class="breadcrumb my-3">
                        <li class="breadcrumb-item">
                            <h4 class="m-0">Request Article</h4>
                        </li>
                    </ol>
                    <div id="panelRequestArticleHeader" class="row mb-4 p-2">
                        <div class="col">
                            Fill up the form to help our writers understand your requirement. Charges are applicable.<br>
                            Fields marked with <span class="txtRed">*</span> are mandatory.<br>
                        </div>
                    </div>
                    <div class="row mb-4 p-2">
                        <div id="panelRequestArticle" class="col">
                            <form id="frmArticle">
                                <div class="form-row">
                                    <div class="form-group col-12 col-md-6">
                                        <label for="postTitle">Headline<sup class="txtRed"><big>*</big></sup></label>
                                        <input type="text" class="form-control px-2 py-4" id="articleHeadline" name="articleHeadline" maxlength="100" />
                                    </div>
                                    <div class="form-group col-12 col-md-6">
                                        <label for="postTitle">Objective<sup class="txtRed"><big>*</big></sup></label>
                                        <input type="text" class="form-control px-2 py-4" id="articleObjective" name="articleObjective" maxlength="100" />
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-12 col-md-6">
                                        <label for="postTitle">Target Group<sup class="txtRed"><big>*</big></sup></label>
                                        <input type="text" class="form-control px-2 py-4" id="articleTargetGroup" name="articleTargetGroup" maxlength="100" />
                                    </div>
                                    <div class="form-group col-12 col-md-6">
                                        <label for="postTitle">Keywords<sup class="txtRed"><big>*</big></sup></label>
                                        <input type="text" class="form-control px-2 py-4" id="articleKeywords" name="articleKeywords" maxlength="100" />
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-12 col-md-6">
                                        <label for="postTitle">Number of words<sup class="txtRed"><big>*</big></sup></label>
                                        <input type="text" class="form-control px-2 py-4" id="articleNumWords" name="articleNumWords" maxlength="100" />
                                    </div>
                                    <div class="form-group col-12 col-md-6">
                                        <label for="postTitle">Number of Pictures/Graphics/etc.</label>
                                        <!-- <label for="postTitle">Number of Pictures/Graphics/etc.<sup class="txtRed"><big>*</big></sup></label> -->
                                        <input type="text" class="form-control px-2 py-4" id="articleNumImages" name="articleNumImages" maxlength="100" />
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col">
                                        <label for="postTitle">Outline</label>
                                        <textarea class="form-control" id="articleOutline" name="articleOutline"></textarea>
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col">
                                        <label for="postTitle">Drop Image links</label>
                                        <textarea class="form-control" id="imageLink" name="imageLink"></textarea>
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group col-12 col-md-6">
                                        <label for="postTitle">Primary source</label>
                                        <textarea class="form-control" id="articlePrimarySource" name="articlePrimarySource"></textarea>
                                    </div>
                                    <div class="form-group col-12 col-md-6">
                                        <label for="postTitle">Secondary source</label>
                                        <textarea class="form-control" id="articleSecondarySource" name="articleSecondarySource"></textarea>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <? if ($gUserPlan == 1) { ?>
                                        <button type="button" id="buttonGenerate" class="btn btn-primary btn-spacing" onclick="chkGenerateArticle('generateArticle')">Generate Article</button>
                                    <? } else { ?>
                                        <div class="alert alert-success" role="alert">The <b>GenAI Creator</b> is only available for <b>Pro</b> users!Click on Upgrade to Upgrade your account-<a href="premium.php">Upgrade</a> .</div>
                                    <? } ?>
                                    <div class="float-left ml-4 pt-2">
                                        <div id="panelStatusRequestArticle"></div>
                                    </div>
                                </div>
                                <input type="hidden" id="actionType" name="act" value="" />


                            </form>
                        </div>
                    </div>
                </div>
            </main>

        </div>

    </body>
    <? include 'inc/php/bottom_navbar.php' ?>
    <? include 'inc/php/footer.php' ?>


    <script>
        function chkGenerateArticle(action) {
            $('#actionType').val(action);
            $('#buttonGenerate').text("Generating...");

            // Get form values and trim whitespace
            var articleHeadline = $('#articleHeadline').val().trim();
            var articleObjective = $('#articleObjective').val().trim();
            var articleTargetGroup = $('#articleTargetGroup').val().trim();
            var articleKeywords = $('#articleKeywords').val().trim();
            var articleNumWords = $('#articleNumWords').val().trim();
            var articleNumImages = $('#articleNumImages').val().trim();

            // Check for empty fields and collect errors
            var errors = [];
            if (!articleHeadline) errors.push('Headline not entered!');
            if (!articleObjective) errors.push('Objective not entered!');
            if (!articleTargetGroup) errors.push('Target Group not entered!');
            if (!articleKeywords) errors.push('Keywords not entered!');
            if (!articleNumWords) errors.push('Number of words not entered!');
            // if (!articleNumImages) errors.push('Number of Pictures/Graphics/etc. not entered!');

            // Display errors if any
            if (errors.length > 0) {
                $('#panelStatusRequestArticle').html(
                    '<div class="text-danger animate__animated animate__flash">Error: ' + errors.join(' ') + '</div>'
                );
                return false;
            }

            $('#contentLoader').show();

            // Perform AJAX request
            $.ajax({
                    url: 'request_article.php',
                    method: 'POST',
                    data: serializeFormExcludingField('#frmArticle', 'sendRequest')
                })
                .done(function(res) {
                    $('#contentLoader').hide();
                    $('#panelRequestArticleHeader').hide();
                    $('#panelRequestArticle').html(res);
                })
                .fail(function() {
                    $('#contentLoader').hide();
                    $('#panelStatusRequestArticle').html('<div class="text-danger animate__animated animate__flash">Error: Failed to process request.</div>');
                });

            return false; // Prevent default form submission
        }



        function serializeFormExcludingField(formSelector, excludeName) {
            var form = $(formSelector);
            var serializedArray = form.serializeArray();
            var filteredArray = serializedArray.filter(function(item) {
                return item.name !== excludeName;
            });

            // Convert the filtered array back to a query string
            return $.param(filteredArray);
        }
    </script>

    </html>

<? } ?>