<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

<style>
    .go-back-btn {
        background-color: #ff7f00;
        /* Vibrant Orange */
        color: white;
        cursor: pointer;
        border: none;
        border-radius: 40px;
        /* Soft pill shape */
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 10px 16px;
        /* Smaller but still comfortable */
        font-size: 14px;
        font-weight: 600;
        transition: all 0.3s ease-in-out;
        box-shadow: 0px 4px 8px rgba(255, 127, 0, 0.2);
        /* Soft orange glow */
        outline: none;
        gap: 8px;
    }

    .go-back-btn i {
        font-size: 16px;
        transition: transform 0.3s ease-in-out;
    }

    .go-back-btn:hover {
        background-color: #007f2e;
        /* Deep Green */
        box-shadow: 0px 6px 12px rgba(0, 127, 46, 0.3);
        /* Soft green glow */
        transform: translateY(-1px);
    }

    .go-back-btn:active {
        transform: scale(0.95);
    }

    /* Light & Dark Mode Support */
    body.dark-mode .go-back-btn {
        background-color: #ff7f00;
        /* Keep the same orange */
        color: white;
    }

    body.dark-mode .go-back-btn:hover {
        background-color: #007f2e;
        /* Green */
    }

    /* Mobile Adjustments */
    @media screen and (max-width: 540px) {
        .go-back-btn {
            font-size: 13px;
            padding: 8px 14px;
        }

        .go-back-btn i {
            font-size: 14px;
        }
    }

    .go-back-bar {
        margin-top: 100px;
    }
</style>

<!-- Back Button -->
<div class="go-back-bar">
    <button class="go-back-btn" onclick="history.back();">
        <i class="fas fa-arrow-left"></i> Go Back
    </button>
</div>

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