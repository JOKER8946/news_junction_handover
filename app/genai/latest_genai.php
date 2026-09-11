<!doctype html>

<?
include '../inc/php/validate.logged.php';
include '../inc/php/function.php';
include '../inc/config.php';
include '../inc/function.php';

?>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GenAI | Knobly Cream: Create &bull; Reach &bull; Measure</title>

    <!-- Core dependencies -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css" integrity="sha384-9aIt2nRpC12Uk9gS9baDl411NQApFmC26EwAOH8WgZl5MYYxFfc+NcPb1dKGj7Sk" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.0.0/animate.min.css">
    <link rel="stylesheet" href="../inc/css/styles.css">
    <link rel="stylesheet" href="../inc/css/genai_style.css">
    <link rel="icon" type="image/x-icon" href="/img/logo.ico">

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.min.js" integrity="sha384-OgVRvuATP1z7JjHLkuOU7Xw704+h835Lr+6QL9UvYjZE3Ipu6Tp75j7Bh/kR0JKI" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.8.0/Chart.min.js" crossorigin="anonymous"></script>
    <script src="https://cdn.tiny.cloud/1/u5oz235qw5jiqww4udm90ocp9zg2rncblqo2ch0ym3twjgyt/tinymce/5/tinymce.min.js" referrerpolicy="origin"></script>
    <script src="../inc/genai_func.js"></script>

    <style>
        :root {
            --primary-color: #f58020;
            --primary-hover: #e57010;
            --secondary-color: #333;
            --light-gray: #f9f9f9;
            --border-color: #e0e0e0;
            --shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            --border-radius: 12px;
            --font-main: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }

        body {
            font-family: var(--font-main);
            background-color: #f9fafb;
            overflow-x: hidden;
        }

        /* Main container layout */
        .sideWithMainContainer {
            display: flex;
            flex-direction: row;
            gap: 10px;
            overflow-x: hidden;
        }

        .sideMaincontent {
            height: calc(100vh - 70px);
            overflow-y: scroll;
            scroll-behavior: smooth;
            position: relative;
            padding-bottom: 24px;
        }

        /* Layout adjustments */
        #layoutSidenav {
            display: flex;
            flex-direction: column;
            height: calc(100vh - 90px);
            position: relative;
        }

        /* Content area */
        .content-area {
            flex: 1;
            overflow-y: auto;
            padding: 20px 5% 0px;
            margin-bottom: 20px;
            position: relative;
        }

        /* Heading */
        .ai-heading {
            color: var(--primary-color);
            margin: 20px 0 30px;
            font-size: 2.2rem;
            font-weight: 800;
            letter-spacing: -0.025em;
            text-align: center;
            animation: fadeIn 0.5s ease-in-out;
        }

        /* Logo enhancement */
        .ai-heading i {
            margin-right: 10px;
        }

        .ai-subheading {
            text-align: center;
            color: var(--secondary-color);
            margin-bottom: 30px;
            opacity: 0.7;
            font-size: 1.1rem;
        }

        /* Loading spinner */
        .loader {
            border: 4px solid rgba(0, 0, 0, 0.1);
            border-left-color: var(--primary-color);
            border-radius: 50%;
            width: 32px;
            height: 32px;
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

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Generated content container */
        .generated-content {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            padding: 20px;
            margin: 0 auto 0px;
            max-width: 100%;
            max-height: 500px;
            /* min-height: 200px; */
            overflow-y: unset !important;
            transition: all 0.3s ease;
            animation: slideUp 0.4s ease-out;
            border: 1px solid rgba(0, 0, 0, 0.08);
            position: relative;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .generated-content .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--border-color);
            margin-bottom: 15px;
        }

        .generated-content h5 {
            font-weight: 600;
            color: var(--secondary-color);
            margin: 0;
        }

        .button-content {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn-icon {
            background: transparent;
            border: none;
            color: #888;
            font-size: 1rem;
            padding: 5px 10px;
            border-radius: 4px;
            transition: all 0.2s ease;
        }

        .btn-icon:hover {
            background: #f0f0f0;
            color: #333;
        }

        #saveButton {
            font-weight: 500;
            font-size: 0.9rem;
            padding: 5px 15px;
            border-radius: 6px;
        }

        .content-show {
            display: flex;
            justify-content: center;
        }

        .generated-text {
            width: 100%;
            padding: 10px;
            line-height: 1.6;
            font-size: 1rem;
            color: #444;
            max-height: 40vh;
            overflow-y: auto;
        }

        /* Input area styling */
        .input-area-fixed {
            width: 90%;
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
            transition: all 0.3s ease;
            position: relative;
        }


        .input-control-wrapper {
            background-color: white;
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--shadow);
            border: 1px solid rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
        }

        .textarea-container {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            background-color: white;
            border-bottom: 1px solid #f0f0f0;
        }

        #working_headline {
            border: none;
            padding: 12px 15px;
            resize: none;
            width: 100%;
            min-height: 50px;
            font-size: 16px;
            border-radius: 8px;
            background-color: #f9f9f9;
            transition: all 0.2s ease;
        }

        #working_headline:focus {
            outline: none;
            background-color: #f0f0f0;
            box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        #submit-btn {
            background-color: var(--primary-color);
            border: none;
            color: white;
            padding: 10px;
            border-radius: 50%;
            width: 44px;
            height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }

        #submit-btn:hover {
            background-color: var(--primary-hover);
            transform: scale(1.05);
        }

        #submit-btn svg {
            width: 20px;
            height: 20px;
        }

        /* Button bar */
        .button-bar {
            display: flex;
            padding: 12px 15px;
            background-color: #fafafa;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: none;
        }

        .button-bar::-webkit-scrollbar {
            height: 0px;
        }

        .button-container {
            display: flex;
            gap: 10px;
            flex-wrap: nowrap;
        }

        /* Button styling */
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .reportButton {
            padding: 6px 15px;
            border-radius: 20px;
            background-color: white;
            border: 1px solid #ddd;
            color: #555;
            font-weight: 500;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            font-size: 0.9rem;
            white-space: nowrap;
        }

        .reportButton:hover {
            background-color: #f9f9f9;
            border-color: #ccc;
            transform: translateY(-1px);
        }

        .reportButton.active-tab {
            background-color: rgba(245, 128, 32, 0.1);
            border-color: var(--primary-color);
            color: var(--primary-color);
            box-shadow: 0 2px 4px rgba(245, 128, 32, 0.1);
        }

        .reportButton i {
            font-size: 0.9rem;
        }

        /* Recommendations section */
        .recommendations-container {
            margin-top: 15px;
            padding: 15px;
            border-radius: 8px;
            background-color: #f9f9f9;
            border: 1px solid #e8e8e8;
        }

        .recommendations-container h5 {
            font-size: 0.95rem;
            color: #666;
            margin-bottom: 12px;
            font-weight: 600;
        }

        .recommendation-item {
            padding: 12px 15px;
            margin-bottom: 8px;
            background-color: white;
            border-radius: 8px;
            border: 1px solid #e8e8e8;
            cursor: pointer;
            transition: all 0.25s ease;
            font-size: 0.95rem;
            color: #444;
        }

        .recommendation-item:hover {
            background-color: #f0f0f0;
            transform: translateY(-2px);
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.1);
            border-color: #ddd;
        }

        .recommendation-item:last-child {
            margin-bottom: 0;
        }

        /* Action buttons */
        .clear-reset-buttons {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        .btn-action {
            padding: 8px 15px;
            border-radius: 6px;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.2s;
        }

        .btn-clear {
            background-color: #f0f0f0;
            border: 1px solid #ddd;
            color: #555;
        }

        .btn-clear:hover {
            background-color: #e0e0e0;
        }

        .btn-reset {
            background-color: #f8d7c4;
            border: 1px solid #f1b89e;
            color: #c94f17;
        }

        .btn-reset:hover {
            background-color: #f6c8ae;
        }

        /* Toggle button */
        .input-toggle-btn {
            position: absolute;
            top: -15px;
            left: 50%;
            transform: translateX(-50%);
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background-color: #fff;
            color: #555;
            border: 1px solid #ddd;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            cursor: pointer;
            z-index: 102;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }

        .input-toggle-btn:hover {
            background-color: #f0f0f0;
        }

        /* Mobile optimizations */
        @media (max-width: 768px) {
            .col-md-2 {
                display: none !important;
            }

            .sideMaincontent {
                width: 100% !important;
                flex: 0 0 100%;
                max-width: 100%;
            }

            .content-area {
                padding: 15px 10px 60px;
            }

            .input-area-fixed {
                width: 100%;
                padding: 0px;
            }

            .input-area-fixed.fixed {
                width: 100%;
                bottom: 0px;
                padding: 0;
            }

            .generated-content {
                padding: 15px;
                max-width: 95%;
                margin-bottom: 30px;
            }

            .ai-heading {
                font-size: 1.8rem;
                margin: 10px 0 20px;
            }

            .button-container {
                gap: 5px;
            }

            .reportButton {
                padding: 5px 12px;
                font-size: 0.8rem;
            }

            @media screen and (max-width: 500px) {
                .button-container {
                    position: relative;
                    overflow-x: scroll !important;
                    overflow-y: hidden;
                    display: flex;
                    justify-content: flex-start;
                    border-bottom: none;
                }
            }

            #working_headline {
                padding: 10px;
                font-size: 15px;
            }

            /* .generated-text {
                max-height: 50vh;
            } */

            .button-content {
                gap: 5px;
            }

            #saveButton {
                padding: 4px 10px;
                font-size: 0.8rem;
            }

            .textarea-container {
                padding: 8px 10px;
            }
        }

        @media (max-width: 480px) {
            .reportButton {
                padding: 4px 10px;
                font-size: 0.75rem;
            }

            .ai-heading {
                font-size: 1.5rem;
            }

            .recommendations-container h5 {
                font-size: 0.9rem;
            }

            .recommendation-item {
                padding: 3px 6px;
                font-size: 0.70rem !important;
                padding: 4px 6px;
                margin-bottom: 0px;
                background-color: transparent !important;
                border-radius: 8px;
                border: none !important;
                cursor: pointer;
                transition: all 0.25s ease;
                font-size: 0.95rem;
                color: #444;
            }

            .generated-content .header h5 {
                font-size: 1rem;
            }

            .btn-icon {
                padding: 3px 6px;
            }

            #submit-btn {
                width: 38px;
                height: 38px;
            }
        }

        /* Enhanced design for code blocks */
        pre {
            background: #f8f9fa;
            border: 1px solid #eaeaea;
            border-radius: 8px;
            padding: 15px;
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
            overflow-x: auto;
            position: relative;
        }

        pre:before {
            content: "";
            height: 10px;
            width: 10px;
            background: #ff5f56;
            position: absolute;
            top: 10px;
            left: 10px;
            border-radius: 50%;
            box-shadow: 18px 0 0 0 #ffbd2e, 36px 0 0 0 #27c93f;
        }

        pre code {
            padding-top: 15px;
            display: block;
        }

        /* Tables styling */
        table {
            border-collapse: collapse;
            width: 100%;
            margin: 20px 0;
            font-size: 0.9rem;
            box-shadow: 0 2px 3px rgba(0, 0, 0, 0.05);
            border-radius: 8px;
            overflow: hidden;
        }

        table th {
            background-color: #f5f5f5;
            color: #333;
            font-weight: 600;
            text-align: left;
            padding: 12px;
        }

        table td {
            padding: 10px 12px;
            border-top: 1px solid #eee;
            color: #555;
        }

        table tr:nth-child(even) {
            background-color: #fafafa;
        }

        table tr:hover {
            background-color: #f1f1f1;
        }
    </style>
    <style>
        /* Default styles for desktop */
        .button-container .reportButton {
            display: inline-block;
        }

        .mobile-dropdown {
            display: none;
        }

        /* Mobile view (screen width <= 768px) */
        @media (max-width: 768px) {
            .button-container .reportButton {
                display: none;
            }

            .mobile-dropdown {
                display: block;
                width: 100%;
                padding: 10px;
                font-size: 16px;
                border: 1px solid #ccc;
                border-radius: 4px;
                background-color: #fff;
                cursor: pointer;
            }
        }
    </style>



</head>

<body class="sb-nav-fixed">
    <!-- PHP includes for navbar - this would be included in your actual page -->
    <?php include '../inc/php/social_navbar.php' ?>

    <div class="sideWithMainContainer">
        <div class="col-md-2">
            <!-- Sidebar would be included here -->
            <? include '../inc/php/sidebar.php' ?>
        </div>

        <div id="layoutSidenav" class="col-sm-12 col-md-10 sideMaincontent">
            <main>
                <div id="panelContent" class="">
                    <div id="GenAI-Container">
                        <div class="content-area">
                            <!-- AI Header -->
                            <h2 class="dummy ai-heading"><i class="fas fa-robot"></i>Ask Deeplit AI</h2>
                            <p class="dummy ai-subheading">Your intelligent assistant for creative and analytical tasks</p>

                            <!-- Generated Content Area -->
                            <div class="generated-content" style="display: none;">
                                <div class="header">
                                    <h5>Deeplit Generated Content</h5>
                                    <div class="button-content">
                                        <button onclick="copyText()" class="btn-icon" title="Copy to clipboard">
                                            <i class="far fa-copy"></i>
                                        </button>
                                        <button id="saveButton" class="btn btn-primary">Save</button>
                                    </div>
                                </div>
                                <div class="content-show">
                                    <div class="generated-text"></div>
                                </div>
                            </div>

                            <!-- Input Area -->
                            <div class="input-area-fixed">
                                <div class="input-control-wrapper">
                                    <!-- Toggle Button -->
                                    <!-- <button class="input-toggle-btn" title="Toggle input area">
                                        <i class="fas fa-chevron-up"></i>
                                    </button> -->

                                    <!-- Textarea Container -->
                                    <div class="textarea-container">
                                        <textarea name="working_headline" placeholder="Ask anything..." id="working_headline" class="form-control" rows="2"></textarea>
                                        <button id="submit-btn" class="btn" title="Send message">
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
                                            <!-- Desktop Buttons -->
                                            <button id="reportButton2" class="reportButton" value="#live" title="live">
                                                <i class="fas fa-globe"></i> Current Affairs
                                            </button>
                                            <button id="reportButton1" class="reportButton active-tab" value="#reporter" title="Convert the text that follows as a newspaper report">
                                                <i class="fas fa-newspaper"></i> Report
                                            </button>

                                            <button id="reportButton3" class="reportButton" value="#code" title="Respond with the amazing coding solution.">
                                                <i class="fas fa-code"></i> Code
                                            </button>
                                            <button id="reportButton4" class="reportButton" value="#table" title="Respond with a detailed Table.">
                                                <i class="fa-solid fa-table"></i> Table
                                            </button>
                                            <button id="reportButton5" class="reportButton" value="#computation" title="Respond with a computational output.">
                                                <i class="fa-solid fa-calculator"></i> Computation
                                            </button>
                                            <!-- Mobile Dropdown -->
                                            <select id="mobile-report-dropdown" class="mobile-dropdown">
                                                <option value="#reporter">Report</option>
                                                <option value="#live">Current Affairs</option>
                                                <option value="#code">Code</option>
                                                <option value="#table">Table</option>
                                                <option value="#computation">Computation</option>
                                            </select>
                                        </div>
                                        <div>
                                            <button id="clear-btn" class="btn btn-action btn-clear">
                                                <i class="fas fa-eraser"></i>
                                            </button>
                                            <button id="reset-btn" class="btn btn-action btn-reset">
                                                <i class="fas fa-redo"></i>
                                            </button>
                                        </div>
                                    </div>



                                    <script>
                                        document.addEventListener('DOMContentLoaded', () => {
                                            const buttons = document.querySelectorAll('.reportButton');
                                            const dropdown = document.querySelector('#mobile-report-dropdown');
                                            const textarea = document.querySelector('#working_headline');

                                            // Handle button clicks (desktop)
                                            buttons.forEach(button => {
                                                button.addEventListener('click', () => {
                                                    buttons.forEach(btn => btn.classList.remove('active-tab'));
                                                    button.classList.add('active-tab');
                                                    // Optionally, update textarea or perform other actions based on button.value
                                                    console.log(`Selected: ${button.value}`);
                                                });
                                            });

                                            // Handle dropdown change (mobile)
                                            dropdown.addEventListener('change', () => {
                                                const selectedValue = dropdown.value;
                                                // Simulate button click behavior
                                                buttons.forEach(btn => {
                                                    btn.classList.remove('active-tab');
                                                    if (btn.value === selectedValue) {
                                                        btn.classList.add('active-tab');
                                                    }
                                                });
                                                console.log(`Selected: ${selectedValue}`);
                                                // Optionally, update textarea or perform other actions based on selectedValue
                                            });

                                            // Handle submit button
                                            document.querySelector('#submit-btn').addEventListener('click', () => {
                                                const selectedValue = dropdown.value || document.querySelector('.reportButton.active-tab')?.value;
                                                const text = textarea.value;
                                                console.log(`Submitted: ${selectedValue} - ${text}`);
                                                // Add your submit logic here
                                            });

                                            // Handle clear button
                                            document.querySelector('#clear-btn').addEventListener('click', () => {
                                                textarea.value = '';
                                            });

                                            // Handle reset button
                                            document.querySelector('#reset-btn').addEventListener('click', () => {
                                                textarea.value = '';
                                                buttons.forEach(btn => btn.classList.remove('active-tab'));
                                                document.querySelector('#reportButton1').classList.add('active-tab');
                                                dropdown.value = '#reporter';
                                            });
                                        });
                                    </script>

                                    <!-- Action Buttons -->


                                    <!-- Recommendations Section -->
                                    <div class="recommendations-container">
                                        <h5><i class="fas fa-lightbulb"></i> Try these prompts</h5>
                                        <div class="recommendation-item" data-prompt="Suggest social media marketing strategies for small businesses">
                                            Social media marketing strategies for small businesses
                                        </div>
                                        <div class="recommendation-item" data-prompt="Tell me a joke">
                                            Tell me a joke
                                        </div>
                                        <div class="recommendation-item" data-prompt="Provide tips for effective content marketing">
                                            Tips for effective content marketing
                                        </div>
                                        <div class="recommendation-item" data-prompt="Create a weekly content calendar for a fitness blog">
                                            Create a weekly content calendar for a fitness blog
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <script>
                        $(document).ready(function() {
                            // Active button tracking
                            function setActiveButton(buttonId) {
                                $('.reportButton').removeClass('active-tab');
                                $('#' + buttonId).addClass('active-tab');
                            }

                            // Function to make the input area fixed
                            function makeInputFixed() {
                                $('.input-area-fixed').addClass('fixed');
                                // Hide recommendations when input is fixed
                                $('.recommendations-container').hide();
                                $('.action-buttons').show();
                                // Show toggle button
                                $('.input-toggle-btn').show();
                            }

                            // Function to return to relative position
                            function makeInputRelative() {
                                $('.input-area-fixed').removeClass('fixed');
                                // Show recommendations
                                $('.recommendations-container').show();
                                $('.action-buttons').hide();
                                // Hide toggle button
                                $('.input-toggle-btn').hide();

                                // Clear and reset
                                $('.generated-text').html('');
                                $('.generated-content').hide();
                                $('.ai-heading').show();
                                $('.ai-subheading').show();
                            }

                            // Initially hide toggle button
                            $('.input-toggle-btn').hide();

                            // Replace <br> tags with newlines for saving
                            function replaceBrWithNewline(text) {
                                return text.replace(/<br\s*\/?>/gi, '\n');
                            }

                            // Format markdown for better display
                            function formatMarkdown(text) {
                                // Convert markdown code blocks to styled code boxes
                                text = text.replace(/```(\w+)?\n([\s\S]+?)\n```/g, function(match, language, code) {
                                    const langClass = language ? `language-${language.toLowerCase()}` : '';
                                    return `<pre><code class="${langClass}">${code.trim()}</code></pre>`;
                                });

                                // Convert markdown tables to HTML tables
                                text = text.replace(/\|(.+?)\|[\r\n-]+([\s\S]+?)(\n\n|$)/g, function(match, header, body) {
                                    let headers = header.split('|').map(cell => cell.trim()).filter(cell => cell);
                                    let headerRow = '<tr>' + headers.map(h => `<th>${h}</th>`).join('') + '</tr>';

                                    let rows = body.split('\n').filter(row => row.trim() && !row.match(/^---/));
                                    let bodyRows = rows.map(row => {
                                        let cells = row.split('|').map(cell => cell.trim()).filter(cell => cell);
                                        return '<tr>' + cells.map(c => `<td>${c}</td>`).join('') + '</tr>';
                                    }).join('');

                                    return `<table border="1">
                                        <thead>${headerRow}</thead>
                                        <tbody>${bodyRows}</tbody>
                                    </table>`;
                                });

                                // Convert markdown bold and italic
                                text = text.replace(/\*\*(.*?)\*\*/g, "<strong>$1</strong>");
                                text = text.replace(/\*(.*?)\*/g, "<em>$1</em>");

                                // Convert markdown unordered lists
                                text = text.replace(/^\*\s+(.+)$/gm, "<ul><li>$1</li></ul>");
                                text = text.replace(/<\/ul>\s*<ul>/g, "");

                                // Convert markdown links
                                text = text.replace(/\[([^\]]+)\]\(([^)]+)\)/g, '<a href="$2" target="_blank">$1</a>');

                                // Convert markdown images
                                text = text.replace(/!\[([^\]]+)\]\(([^)]+)\)/g, `<img src="$2" alt="$1" style="max-width: 100%"/>`);

                                // Convert markdown line breaks
                                text = text.replace(/\n(?!\s*(<table|<ul|<li|<pre))/g, "<br>");

                                return text;
                            }

                            // Initialize avatar value
                            var avatar = '#reporter';

                            // Add click event listeners to mode buttons
                            $('.reportButton').click(function() {
                                avatar = $(this).val();
                                setActiveButton(this.id);
                            });

                            // Submit button handler
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
                                            $(".generated-content").show();
                                            $(".dummy").hide();
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


                            // Toggle button handler
                            $('.input-toggle-btn').click(function() {
                                if ($('.input-area-fixed').hasClass('fixed')) {
                                    makeInputRelative();
                                    $(this).html('<i class="fas fa-chevron-up"></i>');
                                } else {
                                    makeInputFixed();
                                    $(this).html('<i class="fas fa-chevron-down"></i>');
                                }
                            });

                            // Copy text functionality
                            window.copyText = function() {
                                const text = replaceBrWithNewline($('.generated-text').text());
                                navigator.clipboard.writeText(text).then(function() {
                                    alert('Content copied to clipboard!');
                                }).catch(function(err) {
                                    console.error('Failed to copy text: ', err);
                                    // Fallback method for browsers that don't support clipboard API
                                    const textarea = document.createElement('textarea');
                                    textarea.value = text;
                                    document.body.appendChild(textarea);
                                    textarea.select();
                                    document.execCommand('copy');
                                    document.body.removeChild(textarea);
                                    alert('Content copied to clipboard!');
                                });
                            };

                            // Save button handler
                            $('#saveButton').click(function() {
                                const content = replaceBrWithNewline($('.generated-text').html());
                                const blob = new Blob([content], {
                                    type: 'text/html'
                                });
                                const url = URL.createObjectURL(blob);
                                const a = document.createElement('a');
                                a.href = url;
                                a.download = 'deeplit_content_' + new Date().toISOString().slice(0, 10) + '.html';
                                document.body.appendChild(a);
                                a.click();
                                document.body.removeChild(a);
                                URL.revokeObjectURL(url);
                            });

                            // Clear button handler
                            $('#clear-btn').click(function() {
                                $('#working_headline').val('');
                            });

                            // Reset button handler
                            $('#reset-btn').click(function() {
                                $('#working_headline').val('');
                                $('.generated-text').html('');
                                $('.generated-content').hide();
                                $('.ai-heading').show();
                                $('.ai-subheading').show();
                            });

                            // Recommendation items click handler
                            $('.recommendation-item').click(function() {
                                const prompt = $(this).data('prompt');
                                $('#working_headline').val(prompt);
                            });

                            // Handle textarea Enter key press
                            $('#working_headline').keypress(function(e) {
                                if (e.which == 13 && !e.shiftKey) {
                                    e.preventDefault();
                                    $('#submit-btn').click();
                                }
                            });

                            // Initialize input area position
                            makeInputRelative();

                            // Add textarea auto-resize functionality
                            $('#working_headline').on('input', function() {
                                this.style.height = 'auto';
                                this.style.height = (this.scrollHeight) + 'px';
                            });
                        });
                    </script>
                </div>
            </main>
        </div>
    </div>

    <!-- Footer would be included here -->
    <? include '../inc/php/bottom_navbar.php' ?>
    <? include '../inc/php/footer.php' ?>

</body>

</html>