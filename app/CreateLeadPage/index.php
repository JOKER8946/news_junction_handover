<? include '../inc/php/validate.logged.php'; ?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Template Editor</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/interact.js/1.10.17/interact.min.js"></script>
    <script src="script.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">

    <!-- JavaScript to Maintain History State -->
    <script>
        window.onload = function() {
            updateHistoryState();
        };

        function updateHistoryState() {
            const currentURL = window.location.href;
            const currentTitle = document.title;

            const stateObject = {
                page: currentTitle,
                url: currentURL
            };

            history.replaceState(stateObject, currentTitle, currentURL);
        }
    </script>
</head>

<style>
    .navbar-nav {
        display: -ms-flexbox;
        display: flex;
        flex-direction: row;
        padding-left: 0;
        margin-bottom: 0;
        list-style: none;
    }

    .navbar {
        background-color: #fafafa !important;
    }

    /* Main styles */
    :root {
        --primary-color: #db5919;
        --secondary-color: #f4d03f;
        --text-color: #333;
        --background-color: #f5f5f5;
    }

    .body {
        margin: 0;
        padding: 0;
        font-family: Arial, sans-serif;
        display: flex;
        height: 100vh;
        background-color: var(--background-color);
        flex-direction: row;
    }

    /* Sidebar styles */
    .sidebar {
        width: 250px;
        background-color: white;
        padding: 20px;
        border-right: 1px solid #ddd;
        overflow-y: auto;
        flex-shrink: 0;
    }

    .template-list,
    .elements-list {
        display: flex;
        flex-direction: column;
        gap: 10px;
        margin-bottom: 20px;
    }

    .template,
    .element {
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 4px;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .template:hover,
    .element:hover {
        background-color: #f0f0f0;
        border-color: var(--primary-color);
    }

    .template img {
        width: 100%;
        height: 120px;
        object-fit: cover;
        margin-bottom: 5px;
    }

    /* Main content styles */
    .main-content {
        flex: 1;
        display: flex;
        flex-direction: column;
        /* padding: 20px;    */
        overflow-y: auto;
    }

    .toolbar {
        background-color: white;
        padding: 10px;
        border-radius: 4px;
        margin-bottom: 20px;
        display: flex;
        gap: 10px;
        align-items: center;
    }

    .text-controls {
        display: flex;
        gap: 10px;
        align-items: center;
    }

    .canvas-area {
        flex: 1;
        background-color: white;
        border-radius: 4px;
        padding: 20px;
        overflow-y: auto;
        position: relative;
    }

    /* Element styles */
    .draggable {
        position: absolute;
        padding: 10px;
        border: 1px solid transparent;
        cursor: move;
        user-select: none;
    }

    .draggable:hover {
        border-color: var(--primary-color);
    }

    .draggable.selected {
        border: 2px solid var(--primary-color);
    }

    /* Button styles */
    button {
        padding: 8px 16px;
        background-color: var(--primary-color);
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        transition: background-color 0.3s ease;
    }

    button:hover {
        background-color: #c24c15;
    }

    /* Form controls */
    select,
    input[type="color"] {
        padding: 5px;
        border: 1px solid #ddd;
        border-radius: 4px;
    }

    /* Modal styles */
    .modal {
        display: none;
        position: fixed;
        z-index: 1;
        right: 0;
        top: 60px;
        width: 30%;
        height: 100%;
        overflow: auto;
        background-color: rgba(0, 0, 0, 0.4);
    }

    .modal-content {
        background-color: #fefefe;
        padding: 20px;
        border: 1px solid #888;
    }

    .close {
        color: #aaa;
        font-size: 28px;
        font-weight: bold;
        position: absolute;
        left: 25px;
        top: 0;
    }

    .close:hover,
    .close:focus {
        color: black;
        text-decoration: none;
        cursor: pointer;
    }

    #modalContent a {
        color: black;
    }

    /* New text element styles */
    .text-element {
        position: absolute;
        min-width: 50px;
        min-height: 20px;
        padding: 5px;
        cursor: move;
        border: 1px solid transparent;
        transition: border 0.3s ease;
    }

    .text-element:hover {
        border: 1px dashed var(--primary-color);
    }

    .text-element.selected {
        border: 1px solid var(--primary-color);
    }

    .text-element[contenteditable="true"] {
        outline: none;
        border: 1px solid var(--primary-color);
    }

    .text-controls button.active {
        background-color: var(--primary-color);
        color: white;
    }

    .text-controls select,
    .text-controls input {
        padding: 5px;
        border: 1px solid #ddd;
        border-radius: 4px;
    }

    .add-text-btn {
        margin-bottom: 20px;
        width: 100%;
        padding: 10px;
        background-color: var(--primary-color);
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        transition: background-color 0.3s ease;
    }

    .add-text-btn:hover {
        background-color: #c24c15;
    }

    #new-div-container {
        margin-top: 20px;
        height: 95vh;
        overflow-y: scroll;
    }

    .new-div {
        width: 150px;
        height: auto;
        overflow-y: scroll;
        padding: 0px;
        margin: 10px;
        border: 1px solid #000;
        background-color: #f0f0f0;
    }

    .new-div img {
        width: -webkit-fill-available;
    }
</style>

<style>
    /* Responsive design */
    @media (max-width: 1024px) {
        body {
            flex-direction: column;
        }

        .main-content {
            padding: 10px;
        }

        .canvas-area {
            min-height: 600px;
        }

        .modal {
            width: 60%;
        }
    }

    @media screen and (max-width: 768px) {
        .sidebar {
            width: 100%;
            height: 100%;
        }

        .main-content {
            padding: 10px;
        }

        .canvas-area {
            min-height: 500px;
        }

        .modal {
            width: 80%;
        }
    }


    @media (max-width: 480px) {
        body {
            flex-direction: column;
        }

        .sidebar {
            width: 100%;
            height: 100%;
        }

        .main-content {
            padding: 5px;
        }

        .canvas-area {
            min-height: 400px;
        }

        .modal {
            width: 90%;
        }
    }

    table {
        width: 100%;
        max-width: 800px !important;
        margin: 0 0 !important;
        background-color: transparent !important;
        border-collapse: collapse;
    }
</style>
<style>
    .mobile-message {
        display: none;
        text-align: center;
        padding: 40px 20px;
        background-color: #f8f8f8;
        height: 100vh;
        box-sizing: border-box;
    }

    .mobile-message h1 {
        color: #333;
        margin-bottom: 20px;
    }

    .mobile-message p {
        color: #666;
        font-size: 18px;
        line-height: 1.5;
    }

    /* Media query for screens smaller than 768px (typical tablet/mobile breakpoint) */
    @media screen and (max-width: 768px) {
        .desktop-content {
            display: none;
        }

        .mobile-message {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
    }
</style>

<body>


    <?
    // include "nleditor_navbar.php";
    ?>
    <div class="body desktop-content">
        <div class="sidebar">
            <div class="go-back-bar">
                <button class="go-back-btn" style="background-color: #c24c15;" onclick="history.back();">
                    <i class="fas fa-arrow-left"></i> Go Back
                </button>
            </div>
            <h2>Templates</h2>
            <div class="template-list">
                <div class="template" data-template="template_1" data-category="basic_layout">
                    <span>Basic Layout</span>
                </div>
                <div class="template" data-template="template_2" data-category="modern_layout">
                    <span>Modern Layout</span>
                </div>
                <!-- <div class="template" data-template="template_3" data-category="professional">
                    <span>Professional</span>
                </div> -->
                <!-- <div class="template" data-template="template_4" data-category="minimal_desing">
                    <span>Minimal Design</span>
                </div>
                <div class="template" data-template="template_5" data-category="creative_style">
                    <span>Creative Style</span>
                </div> -->
            </div>
            <button onclick="window.location.href='savedPages.php'" style="background-color: #c24c15;">Your Pages</button>

        </div>

        <div id="new-div-container"></div>

        <div class="main-content">
            <!-- <div class="text-controls">
            <button id="collectionBtn">Collection</button>
        </div> -->

            <div class="canvas-area" id="canvasArea">
                <!-- Initial state when no template is selected -->
                <div class="no-template-selected" id="noTemplateSelected">
                    <i class="fas fa-hand-point-left"></i>
                    <h2>Select a Template</h2>
                    <p>Choose a template from the sidebar to preview and customize your landing page.</p>
                </div>
            </div>

        </div>

        <div id="myModal" class="modal" style="z-index: 450;">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h2>Collection Modal</h2>
                <div id="modalContent">
                    <!-- Data will be loaded here dynamically -->
                </div>
            </div>
        </div>

    </div>
    <div class="mobile-message">
        <h1>Desktop Only</h1>
        <p>This website is currently available only on desktop devices.</p>
        <p>Please visit us using a larger screen for the full experience.</p>
        <div class="go-back-bar">
            <button class="go-back-btn" onclick="history.back();">
                <i class="fas fa-arrow-left"></i> Go Back
            </button>
        </div>
    </div>
    <script src="process.js"></script>
</body>

</html>