<style>
    #loader {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(255, 255, 255, 0.52);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 9999;
        transition: opacity 0.5s ease-out;
    }

    .logo {
        margin-top: 5px;
    }

    .loader-spinner {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        animation: zoom 1.5s ease-in-out infinite;
    }

    @keyframes zoom {
        0% {
            transform: scale(1);
        }

        50% {
            transform: scale(1.5);
            /* zoom in */
        }

        100% {
            transform: scale(1);
        }
    }


    .loader-text {
        margin-left: 20px;
        font-family: Arial, sans-serif;
        font-size: 18px;
        color: #333;
    }

    .cc-text {
        font-size: xx-small;
    }

    @keyframes spin {
        0% {
            transform: rotate(0deg);
        }

        100% {
            transform: rotate(360deg);
        }
    }

    /* Hide loader when page is loaded */
    .loaded #loader {
        opacity: 0;
        visibility: hidden;
    }

    /* Mobile hamburger menu - show only on screens smaller than 768px */
    @media (max-width: 988px) {
        #sidebar-toggle {
            display: block !important;
        }
    }
</style>


<!-- News Scroller -->
<?php include 'news_scroller.php'; ?>

<div id="loader">
    <img src="images/newsjunction.png" class="loader-spinner">
</div>

<div class="navbar">
    <div style="display: flex; align-items: center; gap: 10px;">
        <div class="logo">
            <img src="/grfx/img/nj_logo.png" alt="Chirper Logo">
        </div>
        <button id="sidebar-toggle" style="background: none; border: none; color: var(--text-color); font-size: 24px; cursor: pointer; padding: 5px; display: none;">
            <i class="fas fa-bars"></i>
        </button>
    </div>
    <div class="nav-actions">
        <!-- <div class="proMember" style="display: flex; justify-content: center; align-items: center;">
            <span class="px-1" style="display: flex; justify-content: center;">
                <?php if ($gUserPlan == 0) { ?>
                    <p style="margin-bottom: 0px;"></p>
                <? } else { ?>
                    <i class="fas fa-crown" title="Pro" style="color: goldenrod;"></i>
                <? } ?>
            </span>
        </div> -->

        <a href="complaint_form.php" title="Add your information" style="color:#db5919; padding-top:5px; padding-right: 5px; display:flex; justify-content:center; align-items: center; flex-direction:column;">
            <i class="fas fa-hands-helping"></i>
            <span class="cc-text">
                Citizen Connect
            </span>
        </a>
        <div class="theme-toggle" id="theme-toggle">
            <i class="fas fa-moon"></i>
        </div>

        <!-- <a href="https://forms.gle/APw5bNeu4UYK7GVeA" title="Add your information" target="_blank" style="color:#db5919; padding-top:5px; padding-right: 5px;">
            <i class="fas fa-file-alt"></i>
        </a> -->
        <div class="nav-icon" style="display:flex;">
            <a class="nav-link" href="/account.php">
                <span id="settings-text">
                    <div class="avatar">
                        <img src="/<?= viewProfilePic($creamdb, $gUserId) ?>" alt="Default Image"
                            onerror="this.onerror=null; this.src='/data/profilePic/default.png';">
                    </div>
                </span>
            </a>
        </div>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Hide loader when DOM is fully loaded
        setTimeout(function() {
            document.body.classList.add('loaded');
        }, 500); // Small delay to ensure smooth transition
    });

    // Alternative: Hide loader when everything (including images) is loaded
    window.addEventListener('load', function() {
        document.body.classList.add('loaded');
    });

    // Show loader on page unload (when navigating away)
    window.addEventListener('beforeunload', function() {
        document.getElementById('loader').style.display = 'flex';
        document.body.classList.remove('loaded');
    });

    document.addEventListener('DOMContentLoaded', function() {
        const themeToggle = document.getElementById('theme-toggle');
        const themeIcon = themeToggle.querySelector('i');

        // Check for saved theme preference - default to light if nothing saved
        const savedTheme = localStorage.getItem('theme') || 'light';

        // Apply saved theme
        if (savedTheme === 'dark') {
            document.documentElement.setAttribute('data-theme', 'dark');
            themeIcon.classList.remove('fa-moon');
            themeIcon.classList.add('fa-sun');
        } else {
            document.documentElement.setAttribute('data-theme', 'light');
            themeIcon.classList.remove('fa-sun');
            themeIcon.classList.add('fa-moon');
        }

        // Theme toggle click handler
        themeToggle.addEventListener('click', function() {
            const currentTheme = document.documentElement.getAttribute('data-theme');
            let newTheme;

            if (currentTheme === 'dark') {
                newTheme = 'light';
                themeIcon.classList.remove('fa-sun');
                themeIcon.classList.add('fa-moon');
            } else {
                newTheme = 'dark';
                themeIcon.classList.remove('fa-moon');
                themeIcon.classList.add('fa-sun');
            }

            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
        });
    });

    (function() {

        // Disable text selection
        document.addEventListener('selectstart', e => e.preventDefault());

        // Disable drag
        document.addEventListener('dragstart', e => e.preventDefault());

        // Disable keyboard shortcuts
        document.addEventListener('keydown', function(e) {

            const key = e.key.toLowerCase();

            // Ctrl / Cmd combos
            if ((e.ctrlKey || e.metaKey) && ['c', 'x', 'a', 's', 'p', 'u'].includes(key)) {
                e.preventDefault();
                return false;
            }

            // Ctrl+Shift+I / J / C (DevTools)
            if ((e.ctrlKey || e.metaKey) && e.shiftKey && ['i', 'j', 'c'].includes(key)) {
                e.preventDefault();
                return false;
            }

            // F12 (DevTools)
            if (e.key === 'F12') {
                e.preventDefault();
                return false;
            }

            // Print Screen (best effort)
            if (e.key === 'PrintScreen') {
                e.preventDefault();
                alert('Screenshots are disabled.');
                return false;
            }
            // Windows + Shift + S (Snipping Tool) — best effort
            if (e.shiftKey && e.key.toLowerCase() === 's') {
                e.preventDefault();
                return false;
            }

        });

        // Disable user selection via CSS
        document.body.style.userSelect = 'none';
        document.body.style.webkitUserSelect = 'none';
        document.body.style.msUserSelect = 'none';

    })();
</script>