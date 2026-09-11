<!doctype html>
<?
include '../inc/php/validate.logged.php';
include '../inc/php/function.php';
include '../inc/config.php';
include '../inc/function.php';

$specialIds = [370, 415];
?>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>GenAI | Knobly Cream: Create &bull; Reach &bull; Measure</title>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <!-- Font Awesome CSS -->

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css" integrity="sha384-9aIt2nRpC12Uk9gS9baDl411NQApFmC26EwAOH8WgZl5MYYxFfc+NcPb1dKGj7Sk" crossorigin="anonymous" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.0.0/animate.min.css" />
    <link rel="stylesheet" href="inc/fontawesome/css/all.min.css" />
    <link rel="stylesheet" href="inc/magnific-popup.css" />
    <link rel="stylesheet" href="../inc/css/styles.css" />
    <link rel="stylesheet" href="../inc/css/genai_style.css" />
    <link rel="icon" type="image/x-icon" href="/img/logo.ico">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.5.1.min.js" integrity="sha256-9/aliU8dGd2tb6OSsuzixeV4y/faTqgFtohetphbbj0=" crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.min.js" integrity="sha384-OgVRvuATP1z7JjHLkuOU7Xw704+h835Lr+6QL9UvYjZE3Ipu6Tp75j7Bh/kR0JKI" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.8.0/Chart.min.js" crossorigin="anonymous"></script>
    <script src="https://cdn.tiny.cloud/1/u5oz235qw5jiqww4udm90ocp9zg2rncblqo2ch0ym3twjgyt/tinymce/5/tinymce.min.js" referrerpolicy="origin"></script>
    <script src="https://js.zohostatic.com/books/zfwidgets/assets/js/zf-widget.js"></script>
    <script src="inc/jquery.magnific-popup.min.js"></script>
    <script src="inc/common.js"></script>
    <script src="../inc/genai_func.js"></script>
    <script type="text/javascript">
        $(function() {
            $('#panelContent').load('process/get.section.curated.php', function() {
                $('#contentLoader').hide()
            });
        });
    </script>
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

        .form-control {
            line-height: 3.5;
        }

        .show {
            display: block;
        }

        .footer {
            text-align: center;
            padding: 20px;
            background-color: black !important;
            color: #777;
            position: fixed;
            bottom: 0;
            width: 100%;
        }
    </style>
    <style>
        .navbar {
            position: relative !important;
        }

        .sidebar {
            position: fixed;
            top: 0;
            left: -250px;
            width: 250px;
            height: 100%;
            overflow-x: hidden;
            transition: 0.3s ease;
            z-index: 1000;
            background-color: white;
            color: #212529 !important;
        }



        .reportButton {
            margin: 2px;
            /* Adds spacing around each button */
        }

        @media (max-width: 768px) {

            .button-container {
                gap: 5px;
            }

            .reportButton {
                margin: 10px 0;
            }
        }

        .sidebar .sidebar-content {
            display: flex;
            flex-direction: column;
            gap: 20px;
            padding: 20px;
            color: black !important;
        }


    </style>
</head>
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

<body class="sb-nav-fixed">

    <?php include '../inc/php/social_navbar.php' ?>

    <div class="sideWithMainContainer">
        <div class="col-md-2">
            <? include '../inc/php/sidebar.php' ?>
        </div>
        <div id="layoutSidenav" class="col-sm-12 col-md-10 sideMaincontent">
            <main>
                <div id="panelContent" class="container-fluid col-md-9">

                    <?php
                    $_SESSION['.generated-text'] = "";
                    unset($_SESSION['prompt_message']);

                    ?>

                    <script src="inc/genai_func.js"></script>
                    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>

                    <style>
                        .loader {
                            border: 6px solid rgba(0, 0, 0, 0.1);
                            border-left-color: #f58020;
                            border-radius: 50%;
                            width: 35px;
                            height: 35px;
                            animation: spin 1s linear infinite;
                            margin: 0 auto;

                        }

                        @keyframes spin {
                            0% {
                                transform: rotate(0deg);
                            }

                            100% {
                                transform: rotate(360deg);
                            }
                        }
                    </style>

                    <div id="GenAI-Container">
                        <div class="container my-4" style="padding-bottom: 4rem;">
                            <ol class="breadcrumb my-3">
                                <li class="breadcrumb-item">
                                    <h4 class="m-0">GenAI Creative</h4>
                                </li>
                            </ol>
                            <div class="right">
                                <!-- Button to redirect to the try creative genai -->
                                <a href="genai.php">
                                    <button class="btn btn-primary gen3">Back to GenAI Creator</button>
                                </a>
                            </div>

                            <? if ($gUserPlan == 0) {
                            ?>
                                <div class="alert alert-success" role="alert">The <b>GenAI</b> is only available for <b>Pro</b> users! Go to My Account to upgrade.</div>
                            <?
                            } else {
                            ?>
                                <div class="button-bar">
                                    <div class="button-container">
                                        <button id="reportButton1" class="reportButton" value="#todo" title="Convert the text that follows as a todo item.">
                                            <i class="fas fa-check dropdown-icon"></i> Todo
                                        </button>
                                        <button id="reportButton2" class="reportButton" value="#joke" title="use text as a context or theme to write a joke.">
                                            <i class="fas fa-laugh dropdown-icon"></i> Joke
                                        </button>
                                        <button id="reportButton3" class="reportButton" value="#bored" title="Respond with a motivational anecdote especially from among Robin Williams, Emerson, Mark Twain, Jim Rohn, Dale Carnegie, Simon Sinek">
                                            <i class="fas fa-grin-squint-tears dropdown-icon"></i> Bored
                                        </button>
                                        <button id="reportButton4" class="reportButton" value="#advise" title="Take the following text to give me advise with anecdotes on how it was done by someone else.">
                                            <i class="fas fa-comment-alt dropdown-icon"></i> Advise
                                        </button>
                                        <button id="reportButton5" class="reportButton" value="#done" title="Congratulate me and make me feel on top of the world.">
                                            <i class="as fa-check-circle dropdown-icon"></i> Done
                                        </button>
                                        <button id="reportButton6" class="reportButton" value="#working" title="help me keep my focus on the current work.">
                                            <i class="fas fa-tools dropdown-icon"></i> Working
                                        </button>
                                        <button id="reportButton7" class="reportButton" value="#ben" title="Respond like you are Ben Franklin, picking from your writings and thoughts and motivating and guiding.">
                                            <i class="fas fa-user dropdown-icon"></i> Ben
                                        </button>
                                    </div>
                                </div>
                                <div class="" style="margin-top: 1rem; margin-bottom: 1rem">
                                    <textarea name="working_headline" placeholder="Enter your working headline..." id="working_headline" class="form-control" rows="2"></textarea>
                                </div>

                                <div class="" style="display: flex; justify-content:space-between">
                                    <div class="">
                                        <button id="submit-btn" class="btn btn-primary">Generate</button>
                                        <button id="clear-btn" name="clear" class="btn btn-secondary"><i class="fas fa-eraser"></i> Clear</button>
                                        <a id="whatsapp-share-link" href="https://web.whatsapp.com/">
                                            <i class="fab fa-2x fa-whatsapp-square" style="margin-left: 5px;position:relative;top:5px;color:#f58020;"></i>
                                        </a>
                                    </div>

                                    <div class="" style="display: flex; justify-content:end; gap:10px">
                                        <div class="loadingIndicator" style="display: none;">
                                            <div class="loader"></div>
                                        </div>
                                        <button id="reset-btn" name="reset" class="float-right btn btn-secondary  classReset" style="min-width: max-content; max-height:34px"><i class="fas fa-trash-alt"></i> Reset</i></button>
                                    </div>
                                </div>
                                <div class="generated-content border bg-[#333] p-3 mt-3" style="display: none;">
                                    <div class="content-show">
                                        <div class="" style="display: flex; justify-content:space-between">
                                            <h5>Generated Content:</h5>
                                        </div>

                                        <div class="generated-text">

                                        </div>

                                    </div>


                                    <div class="" style="display: flex; justify-content:right">
                                        <div class="button-content" style="display: flex; justify-content:right">
                                            <button onclick="copyText()" class=" btn btn-warning " style="border: none; background:transparent">
                                                <span>
                                                    <i class="far fa-copy fa-lg fa-fw"></i>
                                                </span>
                                            </button>
                                        </div>
                                        <input class="float-right btn btn-primary" id="saveButton" type="button" value="Save">
                                    </div>

                                </div>
                            <?  } ?>

                        </div>

                    </div>

                    <script>
                        // Add click event listeners to all buttons
                        $('.reportButton').click(function() {
                            var value = $(this).val();
                            var title = $(this).attr('title');

                            setActiveButton(this.id);
                        });
                        // Initialize by showing content for the first button
                        var initialValue = $('#reportButton1').val();
                        var initialTitle = $('#reportButton1').attr('title');

                        $('#reportButton1').addClass('active-tab'); // Set first button as active initially
                        // Function to generate the content
                        var avatar = '';

                        $(document).ready(function() {

                            // Check if the div with class 'code-language' is empty
                            if ($.trim($('.code-language').text()) === '') {
                                $('.code-language').hide(); // Hide the div if it's empty
                            }

                            $('#saveButton').click(function() {
                                var generatedText = $('.generated-text').html();
                                var workingHeadline = $('#working_headline').val();
                                generatedText = replaceBrWithNewline(generatedText);

                                if (!workingHeadline) {
                                    alert("Working Headline cannot be empty..");
                                    return;
                                }

                                // Call genai_save function here
                                genai_save(generatedText, workingHeadline);
                            });


                            // Function to share through whatsapp
                            $('#whatsapp-share-link').click(function(event) {
                                event.preventDefault(); // Prevent default link behavior

                                // Call the share_whatsapp function when the link is clicked
                                share_whatsapp();
                            });

                            $('#submit-btn').click(function() {
                                $('.loadingIndicator').show();
                                var headline = $('#working_headline').val();

                                $.ajax({
                                    url: 'process_genai.php',
                                    type: 'POST',
                                    data: {
                                        avatar: avatar,
                                        working_headline: headline
                                    },
                                    success: function(response) {
                                        try {
                                            var decodedData = decodeURIComponent(response);
                                        } catch {
                                            decodedData = response;
                                        }
                                        decodedData = convert_text(decodedData);
                                        $('.generated-text').append(decodedData);
                                        $('.generated-text').append('<br><br>');
                                        $(".generated-content").show();
                                        $('.loadingIndicator').hide();
                                    },
                                    error: function() {
                                        console.error('AJAX request failed');
                                        alert('Error: Unable to fetch data.');
                                        $('.loadingIndicator').hide();
                                    }
                                });
                            });

                            // Click handler for '.reportButton' elements
                            $('.reportButton').click(function() {
                                avatar = $(this).attr('value');
                            });

                            // Function for Clear button
                            $('#clear-btn').click(function() {
                                $('#working_headline').val(''); // Clear textarea
                            });

                            // Function for Reset button
                            $('#reset-btn').click(function() {
                                $.ajax({
                                    url: '/genai/process_genai.php',
                                    type: 'POST',
                                    data: {
                                        action: "reset"
                                    },
                                    success: function(response) {
                                        if (response == "Ok") {
                                            $('#working_headline').val(''); // Clear textarea
                                            $('.generated-text').html('');
                                            $('.generated-content').hide();
                                        }
                                    },
                                    error: function() {
                                        console.error('Error while sending the request');
                                        $('.loadingIndicator').hide();
                                    }
                                });
                            });
                        });
                    </script>
                </div>
            </main>
        </div>
    </div>
    <? include '../inc/php/bottom_navbar.php' ?>
    <? include '../inc/php/footer.php' ?>


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