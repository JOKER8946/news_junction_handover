<style>
    footer {
        position: fixed;
        bottom: 0;
        left: 0;
        width: 100%;
        padding: 20px 0;
        text-align: center;
        font-size: 0.875rem;
        background-color: var(--cream);
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

<!-- Footer -->
<footer>
    <div class="container-fluid">
        <div>Powered by Knobly Consulting</div>
    </div>
</footer>

<div class="mobile-footer">
    <div class="footer-item ">
        <a href="/stream.php">
            <i class="fas fa-home"></i>
        </a>
    </div>
    <div class="footer-item">
        <a href="/search_bar.php">
            <i class="fas fa-search"></i>
        </a>
    </div>
    <div class="footer-item">
        <a href="/dashboard.php">
            <i class="fas fa-book-reader"></i>
        </a>
    </div>
    <div class="footer-item">
        <a href="/manikya_market/" title="Manikya Market">
            <i class="fas fa-shopping-bag"></i>
        </a>
    </div>
    <div class="footer-item">
        <a href="/my_collection.php">
            <i class="fas fa-layer-group"></i>
        </a>
    </div>

</div>