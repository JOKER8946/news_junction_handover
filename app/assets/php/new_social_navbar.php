<div class="navbar">
    <div class="logo" id="sidebar-toggle">
        <img src="/assets/img/knobly_logo.png" alt="Chirper Logo">
    </div>

    <div class="nav-actions">
        <div class="proMember" style="    display: flex;justify-content: center;align-items: center;">
            <span class="px-1" style="display: flex; justify-content:center ">
                <?= ($gUserPlan == 0) ? "Free" : "Pro" ?>
            </span>
        </div>
        <div class="theme-toggle" id="theme-toggle">
            <i class="fas fa-moon"></i>
        </div>

        <div class="nav-icon" style="display:flex;">
            <a class="nav-link" href="account.php">
                <span id="settings-text">
                    <div class="avatar">
                        <img src="<?= viewProfilePic($creamdb, $gUserId) ?>" alt="Default Image"
                            onerror="this.onerror=null; this.src='/data/profilePic/default.png';">
                    </div>
                </span>
            </a>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const themeToggle = document.getElementById('theme-toggle');
        const themeIcon = themeToggle.querySelector('i');

        // Check for saved theme preference or respect OS preference
        const savedTheme = localStorage.getItem('theme');
        const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;

        // Apply theme based on saved preference or OS preference
        if (savedTheme === 'dark' || (!savedTheme && prefersDark)) {
            document.documentElement.setAttribute('data-theme', 'dark');
            themeIcon.classList.remove('fa-moon');
            themeIcon.classList.add('fa-sun');
        }

        // Theme toggle click handler
        themeToggle.addEventListener('click', function() {
            const currentTheme = document.documentElement.getAttribute('data-theme');
            let newTheme;

            if (currentTheme === 'dark') {
                newTheme = '';
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
</script>