<style>
    /* Basic reset for margin and padding */
    html,
    body {
        margin: 0;
        padding: 0;
        height: 100%;
        font-family: 'Arial', sans-serif;
        display: flex;
        flex-direction: column;
    }

    /* Flexbox layout for main content and footer */
    .main-content {
        flex: 1;
    }

    /* Footer styling */
    footer {
        position: fixed;
        bottom: 0;
        left: 0;
        width: 100%;
        padding: 20px 0;
        text-align: center;
        font-size: 0.875rem;
        background-color:white !important;
    }





    footer .text-muted {
        font-size: 0.875rem;
    }

    footer .container-fluid {
        max-width: 1140px;
        margin: 0 auto;
        padding: 0 15px;
    }

    /* Hide footer on mobile screens (screen width 768px or smaller) */
    @media (max-width: 768px) {
        footer {
            display: none;
        }
    }
</style>
</head>

<!-- Footer -->
<footer>
    <div class="container-fluid">
        <div>&copy; <?= date('Y') ?>, Knobly Consulting</div>
    </div>
</footer>