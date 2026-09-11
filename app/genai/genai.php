<!doctype html>
<?
include '../inc/php/db_config.php';
include '../inc/php/validate.logged.php';
include '../inc/php/function.php';
include '../inc/function.php';

?>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>GenAI | Knobly Cream: Create &bull; Reach &bull; Measure</title>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <!-- Font Awesome CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css" integrity="sha384-9aIt2nRpC12Uk9gS9baDl411NQApFmC26EwAOH8WgZl5MYYxFfc+NcPb1dKGj7Sk" crossorigin="anonymous" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.0.0/animate.min.css" />
    <link rel="icon" type="image/x-icon" href="/img/logo.ico">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../inc/css/social.css" />

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.min.js" integrity="sha384-OgVRvuATP1z7JjHLkuOU7Xw704+h835Lr+6QL9UvYjZE3Ipu6Tp75j7Bh/kR0JKI" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.8.0/Chart.min.js" crossorigin="anonymous"></script>
    <script src="https://cdn.tiny.cloud/1/u5oz235qw5jiqww4udm90ocp9zg2rncblqo2ch0ym3twjgyt/tinymce/5/tinymce.min.js" referrerpolicy="origin"></script>
    <script src="https://js.zohostatic.com/books/zfwidgets/assets/js/zf-widget.js"></script>


    <script src="../inc/genai_func.js"></script>
    <script src="../inc/php/genai_func.js"></script>
    <script src="../inc/js/new_social_script.js?v=20260528"></script>


    <style>
        .sb-nav-fixed {
            background-color: #fff !important;
        }

        .btn-warning {
            color: var(--text-primary);
        }

        .show {
            display: block;
        }

        .reportButton {
            margin: 2px;
            /* Adds spacing around each button */
        }

        @media (max-width: 768px) {

            .button-container {
                gap: 5px;
            }


        }

        /* Container Padding */
        @media screen and (max-width: 768px) {

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

        .generated-content {
            border: none;
            height: 80%;
            padding: 5px !important;
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
            padding: 0px 5px;
        }

        /* Content area - scrollable */
        .content-area {
            flex: 1;
            overflow-y: auto;
            /* padding: 20px 0; */
            margin-bottom: 20px;
        }

        .input-area-fixed {
            position: absolute;
            bottom: auto;
            padding: 50px 90px 25px 90px;
            transition: all 0.3s ease;
            width: 100%;
        }

        .input-area-fixed.fixed {
            padding: 0px;
            height: 20%;
        }

        /* Recommendations section */
        .recommendations-container {
            /* margin-top: 20px; */
            padding: 10px;
            border-radius: 8px;
            background-color: var(--cream);
            border: 1px solid var(--border-sec-color);
        }

        .recommendation-item {
            padding: 5px 15px;
            margin-bottom: 8px;
            background-color: var(--cream);
            border-radius: 4px;
            border: 1px solid var(--border-sec-color);
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .recommendation-item:hover {
            background-color: var(--cream);
            transform: translateY(-2px);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .recommendation-item:last-child {
            margin-bottom: 0;
        }

        /* Additional responsive adjustments */
        @media (max-width: 768px) {
            .recommendations-container {
                /* margin-top: 15px; */
                padding: 10px;
            }
        }

        /* Input area styling */
        .input-control-wrapper {
            width: 100%;
            background-color: var(--cream);
            border-radius: 8px;
            border: 1px solid #000;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        /* Button bar within input area */
        .button-bar {
            display: flex;
            padding: 8px 12px;
            border-bottom: 1px solid var(--border-sec-color);
            background-color: var(--cream);
        }

        .button-container {
            overflow-x: auto;
            display: flex;
            gap: 8px;
            margin-right: 10px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        /* Textarea styling */
        #working_headline {
            /* border: none; */
            padding: 12px 15px;
            resize: none;
            width: 100%;
            min-height: 50px;
            font-size: 16px;
            transition: border-color 0.3s;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
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
            padding: 1px 12px;
            border-radius: 4px;
            background-color: transparent;
            border: 1px solid var(--border-sec-color);
            color: var(--text-primary);
            font-weight: 500;
            transition: background-color 0.2s;
            display: flex;
            align-items: center;
            gap: 4px;
            cursor: pointer;
        }



        .reportButton.active-tab {
            border-color: rgb(0, 0, 0);
        }

        .generated-text {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            width: 85%;
        }

        /* Action buttons */
        .btn-primary {
            background-color: #db5919;
            border-color: #db5919;
        }

        .btn-primary:hover {
            background-color: #db5919;
            border-color: #db5919;
        }

        .right-controls {
            display: flex;
            gap: 10px;
        }

        #reset-btn,
        #clear-btn {
            padding: 0px 5px;
        }

        .content-show {
            height: 100%;
            display: flex;
            justify-content: center;
            overflow: auto;
        }

        .button-container2 {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            display: flex;
        }

        /* Mobile adjustments */
        @media (max-width: 768px) {
            .generated-text {
                width: 100%;
            }

            .input-area-fixed {
                padding: 70px 0px 25px 0px;
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
            display: none;
            align-items: center;
            justify-content: center;
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

        @media (max-width: 768px) {
            .input-toggle-btn {
                right: 45%;
            }
        }

        @media (max-width: 425px) {

            #reset-btn,
            #clear-btn {
                font-size: 15px;
            }

            .button-container {
                font-size: 15px;
                gap: 0px;
            }

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
            /* height: auto;
            overflow-y: auto; */
            transition: max-height 0.3s ease;
        }

        .content-area {
            transition: padding-bottom 0.3s ease;
        }

        .dummy {
            color: #db5919;
            font-size: 2rem;
            font-weight: 800;
            letter-spacing: -0.025em;
            text-align: center;
        }

        /* Make the input area responsive */

        #GenAI-Container {
            height: calc(100vh - 160px);
            position: relative;
        }

        @media (max-width: 768px) {
            .dummy {
                font-size: x-large;
            }
        }

        @media (max-width: 768px) {
            .main-content {
                padding-bottom: 0px;
            }
        }
    </style>
</head>

<body>

    <div class="container">
        <? include '../inc/php/social_navbar.php' ?>
        <? include '../inc/php/social_sidebar.php' ?>

        <main class="search-main-content main-content">
            <div>
                <?php
                $_SESSION['.generated-text'] = "";
                unset($_SESSION['prompt_message']);

                ?>
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
                    <div class="generated-content p-3" style="display: none;">
                        <div class="" style="display: flex; justify-content:space-between">
                            <h5>Deeplit Generated Content:</h5>

                            <div class="button-content" style="display: flex; justify-content:right">
                                <button onclick="copyText()" class="btn btn-warning" style="border: none; background:transparent">
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

                    <!-- Make feature available for all users -->
                    <div class="dummy">Ask Deeplit AI</div>

                    <div class="input-area-fixed">
                        <div class="input-control-wrapper">
                            <div class="textarea-container" style="display: flex; align-items: center; gap: 5px; margin: 5px;">
                                <textarea name="working_headline" placeholder="Ask anything...." id="working_headline" class="form-control" rows="2"></textarea>
                                <button id="submit-btn" class="btn btn-primary">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
                                        <path fill="none" stroke="currentColor" d="m6.998 10.247l.435.76c.277.485.415.727.415.993s-.138.508-.415.992l-.435.761c-1.238 2.167-1.857 3.25-1.375 3.788c.483.537 1.627.037 3.913-.963l6.276-2.746c1.795-.785 2.693-1.178 2.693-1.832s-.898-1.047-2.693-1.832L9.536 7.422c-2.286-1-3.43-1.5-3.913-.963s.137 1.62 1.375 3.788Z"></path>
                                    </svg>
                                </button>
                                <div class="loadingIndicator" style="display: none;">
                                    <div class="loader"></div>
                                </div>
                            </div>

                            <div class="button-bar">
                                <div class="button-container">
                                    <button id="reportButton2" class="reportButton" value="#live" title="live">
                                        <i class="fas fa-newspaper dropdown-icon"></i> Current Affairs
                                    </button>
                                    <button id="reportButton1" class="reportButton" value="#reporter" title="Convert the text that follows as a newspaper report">
                                        <i class="fas fa-newspaper dropdown-icon"></i> Report
                                    </button>
                                    <button id="reportButton3" class="reportButton" value="#code" title="Respond with the amazing coding solution.">
                                        <i class="fas fa-code dropdown-icon"></i> Code
                                    </button>
                                    <button id="reportButton4" class="reportButton" value="#table" title="Respond with a detailed Table.">
                                        <i class="fa-solid fa-table"></i> Table
                                    </button>
                                    <button id="reportButton5" class="reportButton" value="#computation" title="Respond with a computational output.">
                                        <i class="fa-solid fa-plus-minus"></i> Computation
                                    </button>
                                </div>
                                <div class="button-container2">
                                    <!-- <i class="fas fa-trash-alt"></i>  -->
                                    <button id="reset-btn" name="reset" class="float-right btn btn-secondary  classReset" style="margin-right:10px;">Reset</button>
                                    <!-- <i class="fas fa-eraser"></i> -->
                                    <button id="clear-btn" name="clear" class="btn btn-secondary">Clear </button>
                                </div>
                            </div>

                            <!-- Recommendations Section -->
                            <div class="recommendations-container">
                                <h5>Recommendations</h5>
                                <div class="recommendation-item" data-prompt="Suggest social media marketing strategies for small businesses">
                                    Social media marketing strategies for small businesses
                                </div>
                                <div class="recommendation-item" data-prompt="Tell me a joke">
                                    Tell me a joke.
                                </div>
                                <div class="recommendation-item" data-prompt="Provide tips for effective content marketing">
                                    Tips for effective content marketing
                                </div>
                                <div class="recommendation-item" data-prompt="Provide tips for effective content marketing">
                                    Provide tips for effective content marketing
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <script>
                    // Function to make the input area fixed
                    function makeInputFixed() {
                        $('.input-area-fixed').addClass('fixed');
                        // Hide recommendations when input is fixed
                        $('.recommendations-container').hide();
                        $('.button-container2').show();
                        $(".dummy").hide();
                        $(".generated-content").show();

                    }
                    // Add click event listeners to all buttons
                    $('.reportButton').click(function() {
                        var value = $(this).val();
                        var title = $(this).attr('title');

                        setActiveButton(this.id);
                    });
                    // Initialize by showing content for the first button
                    var initialValue = $('#reportButton1').val();
                    var initialTitle = $('#reportButton1').attr('title');

                    $('#reportButton2').addClass('active-tab');
                    // Function to generate the content
                    var avatar = '#live';

                    $(document).ready(function() {
                        $('#working_headline').on('keydown', function(event) {
                            if (event.key === 'Enter' && !event.shiftKey) {
                                event.preventDefault(); // Prevent new line
                                $('#submit-btn').click(); // Trigger button click
                            }
                        });
                        $('.button-container2').hide();

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

                            // Make input area fixed when generating content
                            makeInputFixed();

                            // Check avatar status
                            if (avatar == '#live') {
                                // Use the external API
                                $.ajax({
                                    url: 'deeplit.php',
                                    type: 'POST',
                                    contentType: 'application/json',
                                    data: JSON.stringify({
                                        keywords: headline,
                                        resultCount: 5 // Or whatever you want
                                    }),
                                    success: function(response) {
                                        let output = '';

                                        if (response.summary) {
                                            output = decodeURIComponent(response.summary);
                                        } else if (typeof response === 'string') {
                                            try {
                                                var parsed = JSON.parse(response);
                                                output = decodeURIComponent(parsed.summary || parsed.message || parsed.error || response);
                                            } catch (e) {
                                                output = response;
                                            }
                                        }

                                        output = formatMarkdown(output);
                                        $('.generated-text').append(output + '<br><br>');
                                        $('#working_headline').val('');
                                        $(".generated-content").show();
                                        $('.loadingIndicator').hide();
                                    },
                                    error: function(xhr, status, error) {
                                        console.error('External API request failed:', error);
                                        // alert('External AI API failed. Falling back to local processing.');
                                        fallbackToLocal(); // Fallback call
                                    }
                                });
                            } else {
                                fallbackToLocal();
                            }

                            function fallbackToLocal() {
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
                                        $('.generated-text').append(decodedData + '<br><br>');
                                        $('#working_headline').val('');
                                        $(".generated-content").show();
                                        $(".dummy").hide();
                                        $('.loadingIndicator').hide();
                                    },
                                    error: function() {
                                        console.error('Fallback AJAX request failed');
                                        alert('Error: Unable to fetch data locally.');
                                        $('.loadingIndicator').hide();
                                        makeInputRelative();
                                    }
                                });
                            }
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

                            return `<div style="border-radius:12px; border-left: 4px solid #db5919; padding: 20px; line-height: 1.6; font-family: Arial, sans-serif;">${text}</div>`;
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
                                        $('#working_headline').val('');
                                        $('.generated-text').html('');
                                        // $('.generated-content').hide();

                                        // Return to relative position with recommendations showing
                                        makeInputRelative();
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
    <? include '../footer.php' ?>

</body>
<script>
    $(document).ready(function() {
        // Function to make the input area relative
        function makeInputRelative() {
            $('.input-area-fixed').removeClass('fixed');
            // Show recommendations when input is relative
            $('.recommendations-container').show();
        }

        // Initially set to relative position (not fixed)
        makeInputRelative();

        // Handle recommendation clicks
        $('.recommendation-item').click(function() {
            var promptText = $(this).data('prompt');
            $('#working_headline').val(promptText);

            avatar = "#reporter";

            // Update the active button UI to show Current Affairs is selected
            $('.reportButton').removeClass('active-tab');
            $('#reportButton1').addClass('active-tab');

            makeInputFixed();
            $('#submit-btn').click();
        });


        // Create the toggle button for the input area
        var $toggleButton = $('<button class="input-toggle-btn" title="Toggle input area"><i class="fas fa-chevron-down"></i></button>');
        $('.input-area-fixed').prepend($toggleButton);


        var inputAreaExpanded = true;

        $toggleButton.on('click', function() {
            if (inputAreaExpanded) {
                // Collapse the input area
                $('.input-control-wrapper').slideUp(300);
                $(this).find('i').removeClass('fa-chevron-down').addClass('fa-chevron-up');

                // Show recommendations (if not in fixed mode)
                if (!$('.input-area-fixed').hasClass('fixed')) {
                    $('.recommendations-container').show();
                }

                // Increase content area space
                $('.content-area').addClass('expanded-content');
                $('.generated-content').addClass('expanded');
            } else {
                // Expand the input area
                $('.input-control-wrapper').slideDown(300);
                $(this).find('i').removeClass('fa-chevron-up').addClass('fa-chevron-down');

                // Return content area to normal
                $('.content-area').removeClass('expanded-content');
                $('.generated-content').removeClass('expanded');
            }

            inputAreaExpanded = !inputAreaExpanded;
        });

    });
</script>

</html>