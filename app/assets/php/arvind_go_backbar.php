<style>
   

    .go-back-btn {
        background-color: #007bff;
        color: white;
        cursor: pointer;
        border-radius: 4px;
        display: flex;
        align-items: center;
    }

    /* .go-back-bar {
        padding: 10px 20px;
        border: none !important;
        top: 100px;
        left: 100px;
        width: 100%;
        z-index: 999;
    } */
    .go-back-bar {
        position: absolute;
        padding: 10px 20px;
        border: none !important;
        top: 110px;
        left: 130px;
        /* width: 100%; */
        z-index: 1000;
    }

    .go-back-btn {
        background-color: transparent;
        color: white;
        border: none !important;
        border-radius: 4px !important;
        /* display: flex; */
        /* align-items: center; */
    }

    @media screen and (max-width:540px) {
        .go-back-bar {
            /* background-color: #f8f9fa; */
            margin: 10px 0px;
            padding: 0px 20px;
            /* text-align: center; */
            border: none !important;
            position: relative;
            top: 10px;
            left: 1px;
            /* width: 100%; */
            z-index: 999;
        }

    }

    /* @media screen and (min-width:541px) {
        .go-back-bar {
            margin: 10px 10px;
            padding: 10px 20px;
            border: none !important;
            top: 50px;
            left: -15px;
            width: 100%;
            z-index: 999;
        }

    } */

    .go-back-bar button {
        background-color: transparent;


    }

    body.dark-mode .go-back-btn i {
        margin-right: 8px;
        color: white;

        /* Space between icon and text */
    }

    body.light-mode .go-back-btn i {
        margin-right: 8px;
        color: black;

        /* Space between icon and text */
    }

    .go-back-btn:hover {
        background-color: transparent;
    }
</style>

<div class="go-back-bar">
    <button class="go-back-btn" onclick="history.back();">
        <!-- Font Awesome 'Arrow Left' icon -->
        <i class="fas fa-arrow-left"></i>
    </button>
</div>
<script>
    // Call the function to replace the history entry on page load
    window.onload = function() {
        updateHistoryState(); // Automatically update history state
    };
    // Function to automatically replace the current history entry with dynamic data
    function updateHistoryState() {
        const currentURL = window.location.href; // Get current URL
        const currentTitle = document.title; // Get current page title

        // Create a dynamic state object (customize this as needed)
        const stateObject = {
            page: currentTitle, // Use the title as part of the state
            url: currentURL // Include the full URL in the state
        };

        // Replace the current history entry with the new state, title, and URL
        history.replaceState(stateObject, currentTitle, currentURL);
    }
</script>