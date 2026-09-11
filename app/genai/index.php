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

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
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

        .container-fluid {
            /* padding: 7rem 2%; */
        }

        /* Container Padding */
        @media screen and (max-width: 768px) {
            .container-fluid {
                padding: 0px 0 !important;
                max-width: 1400px;
                margin: 0 auto;

            }

            .sidebar .sidebar-content {
                background-color: white;
                display: flex;
                flex-direction: column;
                gap: 20px;
                padding: 20px;
                color: black !important;
            }

        }

        .go-back-bar {
            margin-top: 100px;
        }

        .sideWithMainContainer {
            display: flex;
            flex-direction: row;
            gap: 10px;
            overflow-x: hidden;

        }

        .container-fluid,
        .container,
        .generated-content {
            padding: 0px !important;
        }

        .sideMaincontent {
            height: 85vh;
            overflow-y: scroll;
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

        .active-tab {
            text-decoration: underline;
            color: #f26522 !important;
        }

        /* Main layout container */
        #layoutSidenav {
            display: flex;
            flex-direction: column;
            height: 85vh;
            position: relative;
        }

        /* Content area - scrollable */
        .content-area {
            flex: 1;
            overflow-y: auto;
            padding: 20px 0;
            margin-bottom: 20px;
        }

        /* Fixed input area at bottom with all controls */
        .input-area-fixed {
            position: fixed;
            bottom: 45px;
            padding: 10px 50px 25px 10px;
            width: 83%;
            z-index: 100;
        }

        /* Input area styling */
        .input-control-wrapper {
            background-color: white;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
            overflow: hidden;
        }

        /* Button bar within input area */
        .button-bar {
            display: flex;
            padding: 8px 12px;
            border-bottom: 1px solid #f0f0f0;
            background-color: #fafafa;
        }

        .button-container {
            display: flex;
            gap: 8px;
        }

        /* Textarea styling */
        #working_headline {
            border: none;
            padding: 12px 15px;
            resize: none;
            width: 100%;
            min-height: 50px;
            font-size: 16px;
            transition: border-color 0.3s;
        }

        #working_headline:focus {
            outline: none;
        }

        /* Bottom action bar */
        .action-bar {
            display: flex;
            justify-content: space-between;
            padding: 8px 12px;
            border-top: 1px solid #f0f0f0;
            background-color: #fafafa;
        }

        /* Button styling */
        .reportButton {
            padding: 6px 12px;
            border-radius: 4px;
            background-color: transparent;
            border: 1px solid transparent;
            color: #555;
            font-weight: 500;
            transition: background-color 0.2s;
            display: flex;
            align-items: center;
            gap: 4px;
            cursor: pointer;
        }

        .reportButton:hover {
            background-color: #f0f0f0;
        }

        .reportButton.active-tab {
            background-color: #f0f0f0;
            border-color: #e0e0e0;
        }

        .generated-text {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
        }

        /* Action buttons */
        .btn-primary {
            background-color: #f58020;
            border-color: #f58020;
        }

        .btn-primary:hover {
            background-color: #e57010;
            border-color: #e57010;
        }

        .right-controls {
            display: flex;
            gap: 10px;
        }

        /* Mobile adjustments */
        @media (max-width: 768px) {

            .input-area-fixed {
                padding: 10px 10px 25px 10px;
                width: 100%;
                left: 0;
            }

            #layoutSidenav {
                /* padding-bottom: 200px; */
            }
        }

        /* Toggle button styling */
        .input-toggle-btn {
            position: absolute;
            top: -30px;
            right: 50%;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background-color: #f58020;
            color: white;
            border: none;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
            cursor: pointer;
            z-index: 101;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .input-toggle-btn:hover {
            background-color: #e57010;
            transform: scale(1.05);
        }

        /* Styling for collapsed input area */
        .input-area-fixed.collapsed {
            height: 30px;
            padding: 0;
            background: transparent;
        }

        /* Additional padding for content area when input is expanded */
        .content-area {
            /* padding-bottom: 160px; */
            transition: padding-bottom 0.3s ease;
        }

        /* td,
        tr {
            border: 1px solid black;
        } */

        /* Make the input area responsive */
        @media (max-width: 768px) {
            .input-toggle-btn {
                right: 45%;
            }
        }

        @media (max-width: 425px) {
            .reportButton {
                padding: 0px 12px;
            }

            .btn {
                padding: 0px 3px;
            }

            h5 {
                font-size: 15px;
                padding: 0px;
            }
        }

        .generated-content {
            /* max-height: 225px; */
            height: auto;
            overflow-y: auto;
            transition: max-height 0.3s ease;
        }

        .generated-content.expanded {
            /* max-height: 400px; */
        }

        .content-area {
            transition: padding-bottom 0.3s ease;
        }

        /* Make the input area responsive */
        @media (max-width: 768px) {
            .content-area.expanded-content {
                /* padding-bottom: 80px; */
            }

            .generated-content.expanded {
                /* max-height: 350px; */
            }
        }
    </style>
</head>

<body class="sb-nav-fixed">

    <?php include '../inc/php/social_navbar.php' ?>

    <div class="sideWithMainContainer">
        <div class="col-md-2">
            <? include '../inc/php/sidebar.php' ?>
        </div>

        <div id="layoutSidenav" class="col-sm-12 col-md-10 sideMaincontent">
            <main>
                <div id="panelContent" class="container-fluid">

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
                        <div class="" style="padding-bottom: 4rem;">

                            <h4 class="m-0">Reasearch</h4>

                            <div class="generated-content border bg-[#333] p-3 mt-3" style="display: none;">
                                <div class="" style="display: flex; justify-content:space-between">
                                    <h5>Generated Content:</h5>

                                    <div class="button-content" style="display: flex; justify-content:right">
                                        <button onclick="copyText()" class=" btn btn-warning " style="border: none; background:transparent">
                                            <span>
                                                <i class="far fa-copy fa-lg fa-fw"></i>
                                            </span>
                                        </button>
                                        <input class="float-right btn btn-primary" id="saveButton" type="button" value="Save">
                                    </div>
                                </div>
                                <div class="content-show">

                                    <div class="generated-text">

                                    </div>

                                </div>
                            </div>

                            <? if ($gUserPlan == 0) {
                            ?>
                                <div class="alert alert-success" role="alert">The <b>Market Research</b> is only available for <b>Pro</b> users! Click on Upgrade to Upgrade your account-<a href="../premium.php">Upgrade</a> .</div>
                            <?
                            } else {
                            ?>
                                <div class="dummy" style="font-size: large;">
                                    Welcome to the Market Research Tool! Begin by entering your working headline and generating insightful reports, code, or tables. This is a placeholder text, and the generated content will appear here once you press the "Generate" button.
                                </div>
                                <div class="button-bar">
                                    <div class="button-container">
                                        <button id="reportButton1" class="reportButton" value="#reporter" title="Convert the text that follows as a newspaper report">
                                            <i class="fas fa-newspaper dropdown-icon"></i> Report
                                        </button>
                                        <button id="reportButton2" class="reportButton" value="#code" title="Respond with the amazing coding solution.">
                                            <i class="fas fa-code dropdown-icon"></i> Code
                                        </button>
                                        <button id="reportButton3" class="reportButton" value="#table" title="Respond with the a detailed Table.">
                                            <i class="fa-solid fa-table"></i> Table
                                        </button>
                                        <button id="reportButton4" class="reportButton" value="#computation" title="Respond with a computational output.">
                                            <i class="fa-solid fa-plus-minus"></i> Computation
                                        </button>
                                    </div>
                                </div>
                                <div class="" style="margin-top: 1rem; margin-bottom: 1rem">
                                    <textarea name="working_headline" placeholder="Enter your working headline..." id="working_headline" class="form-control" rows="2"></textarea>
                                </div>

                                <div class="" style="display: flex; justify-content:space-between">
                                    <div class="">
                                        <button id="reset-btn" name="reset" class="float-right btn btn-secondary  classReset"><i class="fas fa-trash-alt"></i> Reset</i></button>
                                        <button id="clear-btn" name="clear" class="btn btn-secondary" style="margin: 0px 10px;"><i class="fas fa-eraser"></i> Clear</button>
                                        <!-- <a id="whatsapp-share-link" href="https://web.whatsapp.com/">
                                            <i class="fab fa-2x fa-whatsapp-square" style="margin-left: 5px;position:relative;top:5px;color:#f58020;"></i>
                                        </a> -->
                                    </div>

                                    <div class="" style="display: flex; justify-content:end; gap:10px">
                                        <div class="loadingIndicator" style="display: none;">
                                            <div class="loader"></div>
                                        </div>
                                        <button id="submit-btn" <? echo ($gUserPlan) ? "" : "disabled"; ?> class="btn btn-primary">Generate</button>
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
                                // var workingHeadline = $('#working_headline').val();
                                var workingHeadline = prompt("Enter the Title");
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
                                        decodedData = formatMarkdown(decodedData);
                                        $('.generated-text').append(decodedData);
                                        $('.generated-text').append('<br><br>');
                                        $(".generated-content").show();
                                        $(".dummy").hide();
                                        $('.loadingIndicator').hide();
                                    },
                                    error: function() {
                                        console.error('AJAX request failed');
                                        alert('Error: Unable to fetch data.');
                                        $('.loadingIndicator').hide();
                                    }
                                });
                            });

                            function formatMarkdown(text) {
                                // Convert markdown code blocks (```language\ncode\n```) to styled <pre><code> boxes
                                text = text.replace(/```(\w+)?\n([\s\S]+?)\n```/g, function(match, language, code) {
                                    const langClass = language ? `language-${language.toLowerCase()}` : '';
                                    return `<pre style="background: #f4f4f4; border: 1px solid #ddd; border-radius: 4px; padding: 15px; overflow-x: auto; font-family: 'Courier New', monospace; margin-bottom: 20px;"><code class="${langClass}">${code.trim()}</code></pre>`;
                                });

                                // Convert markdown tables to HTML tables
                                text = text.replace(/\|(.+?)\|[\r\n-]+([\s\S]+?)(\n\n|$)/g, function(match, header, body) {
                                    let headers = header.split('|').map(cell => cell.trim()).filter(cell => cell);
                                    let headerRow = '<tr>' + headers.map(h => `<th style="padding: 8px; text-align: left;">${h}</th>`).join('') + '</tr>';

                                    let rows = body.split('\n').filter(row => row.trim() && !row.match(/^---/));
                                    let bodyRows = rows.map(row => {
                                        let cells = row.split('|').map(cell => cell.trim()).filter(cell => cell);
                                        return '<tr>' + cells.map(c => `<td style="padding: 8px;">${c}</td>`).join('') + '</tr>';
                                    }).join('');

                                    return `<table border="1" style="border-collapse: collapse; width: 100%; margin-bottom: 20px; font-family: Arial, sans-serif;">
                    <thead>${headerRow}</thead>
                    <tbody>${bodyRows}</tbody>
                </table>`;
                                });

                                // Convert markdown bold (**text**) to <strong>text</strong>
                                text = text.replace(/\*\*(.*?)\*\*/g, "<strong>$1</strong>");

                                // Convert markdown italic (*text*) to <em>text</em>
                                text = text.replace(/\*(.*?)\*/g, "<em>$1</em>");

                                // Convert markdown unordered lists (* item) into <ul> and <li>
                                text = text.replace(/^\*\s+(.+)$/gm, "<ul><li>$1</li></ul>");
                                text = text.replace(/<\/ul>\s*<ul>/g, "");

                                // Convert markdown links to actual <a> tags
                                text = text.replace(/\[([^\]]+)\]\(([^)]+)\)/g, '<a href="$2" target="_blank" style="color: #1a73e8; text-decoration: none;">$1</a>');

                                // Convert markdown images to <img> tags
                                text = text.replace(/!\[([^\]]+)\]\(([^)]+)\)/g, `<img src="$2" alt="$1" style="max-width: 100%; height: auto; margin: 15px 0; display: block;"/>`);

                                // Convert markdown line breaks to <br> tags (only for intentional breaks)
                                text = text.replace(/\n(?!\s*(<table|<ul|<li|<pre))/g, "<br>");

                                return `<div style="line-height: 1.6; font-family: Arial, sans-serif;">${text}</div>`;
                            }


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
    $(document).ready(function() {
        // Restructure the layout
        var $layoutContent = $('#layoutSidenav > main > .container-fluid');

        // Create content area
        $layoutContent.wrapInner('<div class="content-area"></div>');

        // Create fixed input area
        var $inputArea = $('<div class="input-area-fixed"></div>');
        var $inputControlWrapper = $('<div class="input-control-wrapper"></div>');

        // Move button bar to input area
        $('.button-bar').appendTo($inputControlWrapper);

        // Create textarea container
        var $textareaContainer = $('<div class="textarea-container"></div>');
        $('#working_headline').appendTo($textareaContainer);
        $textareaContainer.appendTo($inputControlWrapper);

        // Create action bar for submit buttons
        var $actionBar = $('<div class="action-bar"></div>');

        // Create left controls
        var $leftControls = $('<div class="left-controls"></div>');
        $('#reset-btn').appendTo($leftControls);
        $('#clear-btn').appendTo($leftControls);
        $leftControls.appendTo($actionBar);

        // Create right controls
        var $rightControls = $('<div class="right-controls"></div>');
        $('.loadingIndicator').appendTo($rightControls);
        $('#submit-btn').appendTo($rightControls);
        $rightControls.appendTo($actionBar);

        // Append everything to the page
        $actionBar.appendTo($inputControlWrapper);
        $inputControlWrapper.appendTo($inputArea);
        $inputArea.appendTo('#layoutSidenav');

        // Function to set active button
        $('.reportButton').click(function() {
            $('.reportButton').removeClass('active-tab');
            $(this).addClass('active-tab');
        });

        // Set initial active button
        $('#reportButton1').addClass('active-tab');

        // Adjust scroll to show new content when generated
        $('#submit-btn').click(function() {
            setTimeout(function() {
                if ($('.generated-content').is(':visible')) {
                    $('.content-area').animate({
                        scrollTop: 0
                    }, 500);
                }
            }, 500);
        });

        // Auto-resize textarea
        $('#working_headline').on('input', function() {
            this.style.height = 'auto';
        });
    });

    $(document).ready(function() {
        // Create the toggle button for the input area
        var $toggleButton = $('<button class="input-toggle-btn" title="Toggle input area"><i class="fas fa-chevron-down"></i></button>');

        // Add the button to the layout
        $('.input-area-fixed').prepend($toggleButton);

        // Initialize a variable to track if the input area is expanded
        var inputAreaExpanded = true;

        // Add click handler for the toggle button
        $toggleButton.on('click', function() {
            if (inputAreaExpanded) {
                // Collapse the input area, showing only the toggle button
                $('.input-control-wrapper').slideUp(300);
                $(this).find('i').removeClass('fa-chevron-down').addClass('fa-chevron-up');
                $('.input-area-fixed').addClass('collapsed');

                // Increase the visible area for generated content
                $('.content-area').addClass('expanded-content');

                // Increase the max-height of generated-content
                $('.generated-content').addClass('expanded');
            } else {
                // Expand the input area
                $('.input-control-wrapper').slideDown(300);
                $(this).find('i').removeClass('fa-chevron-up').addClass('fa-chevron-down');
                $('.input-area-fixed').removeClass('collapsed');

                // Return the content area to normal size
                $('.content-area').removeClass('expanded-content');

                // Return the generated-content to original max-height
                $('.generated-content').removeClass('expanded');
            }

            // Toggle the state
            inputAreaExpanded = !inputAreaExpanded;
        });
    });
</script>

</html>