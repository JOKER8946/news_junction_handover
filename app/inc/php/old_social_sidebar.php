<!-- Sidebar -->
<div class="sidebar">
    <!-- Primary Navigation -->
    <div class="socail-section" style="gap: 10px;border-bottom: 0.2px solid #cbcbcb;">
        <p style="margin: 0rem;font-weight:700; margin-left: 10px;">Social</p>
        <a href="/stream.php" style="text-decoration: none; color: inherit;">
            <div class="menu-item">
                <div class="menu-icon">
                    <i class="fas fa-home"></i>
                </div>
                <div class="menu-text">Social</div>
            </div>
        </a>
        <a href="/pincode.php" style="text-decoration: none; color: inherit;">
            <div class="menu-item">
                <div class="menu-icon">
                    <i class="fa-solid fa-map-pin"></i>
                </div>
                <div class="menu-text">Pincode</div>
            </div>
        </a>
        <a href="/saved.php" style="text-decoration: none; color: inherit;">
            <div class="menu-item">
                <div class="menu-icon">
                    <i class="fas fa-bookmark"></i>
                </div>
                <div class="menu-text">Bookmarks</div>
            </div>
        </a>
        <a href="/follow_dash.php" style="text-decoration: none; color: inherit;">
            <div class="menu-item">
                <div class="menu-icon">
                    <i class="fas fa-user-friends"></i>
                </div>
                <div class="menu-text">Following</div>
            </div>
        </a>

    </div>
    <div class="socail-section" style="gap: 10px;border-bottom: 0.2px solid #cbcbcb;">
        <p style="margin: 0rem;font-weight:700;margin-left: 10px;">Reader</p>
        <a href="/dashboard.php" style="text-decoration: none; color: inherit;">
            <div class="menu-item">
                <div class="menu-icon">
                    <i class="fas fa-book-reader"></i>
                </div>
                <div class="menu-text">Reader</div>
            </div>
        </a>
        <!-- Featured Channels -->
        <a href="/dashboard.php#featuredchannels" style="text-decoration: none; color: inherit;">
            <div class="menu-item">
                <div class="menu-icon">
                    <i class="fas fa-star"></i>
                </div>
                <div class="menu-text">Featured Channels</div>
            </div>
        </a>

        <!-- Featured Topics -->
        <a href="/dashboard.php#featuredtopics" style="text-decoration: none; color: inherit;">
            <div class="menu-item">
                <div class="menu-icon">
                    <i class="fas fa-tags"></i>
                </div>
                <div class="menu-text">Featured Topics</div>
            </div>
        </a>

        <!-- Cream Curated Feeds -->
        <a href="/dashboard.php#curatedfeeds" style="text-decoration: none; color: inherit;">
            <div class="menu-item">
                <div class="menu-icon">
                    <i class="fas fa-layer-group"></i>
                </div>
                <div class="menu-text">Curated Feeds</div>
            </div>
        </a>



    </div>

    <?php
    //  if ($gUserPlan == 1) { 
     if (true) { 
        ?>
        <div class="Creator-section" style="border-bottom: 0.2px solid #cbcbcb;">

            <p style="margin: 0rem;font-weight:700;margin-left: 10px;">Creator</p>

            <a href="/my_collection.php" style="text-decoration: none; color: inherit;">
                <div class="menu-item">
                    <div class="menu-icon">
                        <i class="fas fa-layer-group"></i>
                    </div>
                    <div class="menu-text">My Collection</div>
                </div>
            </a>

            <a href="/create.php" style="text-decoration: none; color: inherit;">
                <div class="menu-item">
                    <div class="menu-icon">
                        <i class="fas fa-edit"></i>
                    </div>
                    <div class="menu-text">Reporter</div>
                </div>
            </a>


            <a href="/analytics.php" style="text-decoration: none; color: inherit;">
                <div class="menu-item">
                    <div class="menu-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="menu-text">Analytics</div>
                </div>
            </a>

        </div>
    <?
    }
    ?>
    <!-- 
            <a href="/request_article.php" style="text-decoration: none; color: inherit;">
                <div class="menu-item">
                    <div class="menu-icon">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <div class="menu-text">GenAI Creator</div>
                </div>
            </a>
    
    

            <a href="/genai/genai.php" style="text-decoration: none; color: inherit;">
                <div class="menu-item">
                    <div class="menu-icon">
                        <i class="fas fa-robot"></i>
                    </div>
                    <div class="menu-text">Research</div>
                </div>
            </a>
     -->
    <!-- 
        <?php if ($gUserPlan == 0) { ?>
        <? } else { ?>
            <a href="/newsletter.php" style="text-decoration: none; color: inherit;">
                <div class="menu-item">
                    <div class="menu-icon">
                        <i class="fas fa-newspaper"></i>
                    </div>
                    <div class="menu-text">Newsletter</div>
                </div>
            </a>
        <? } ?>
     -->
    <!-- 
        <?php if ($gUserPlan == 0) { ?>
        <? } else { ?>
            <a href="/Xpress/newCompaign.php" style="text-decoration: none; color: inherit;">
                <div class="menu-item">
                    <div class="menu-icon">
                        <i class="fas fa-envelope-open-text"></i>
                    </div>
                    <div class="menu-text">Mailer Campaign</div>
                </div>
            </a>
        <? } ?>
     -->
    <!-- <a href="/CreateLeadPage/savedPages.php" style="text-decoration: none; color: inherit;">
        <div class="menu-item">
            <div class="menu-icon">
                <i class="fas fa-file-alt"></i>
            </div>
            <div class="menu-text">Landing Pages</div>
        </div>
    </a> -->

    <a href="/account.php" style="text-decoration: none; color: inherit;">
        <div class="menu-item">
            <div class="menu-icon">
                <i class="fas fa-user-circle"></i>
            </div>
            <div class="menu-text">My Account</div>
        </div>
    </a>
    <a href="/my_complaints.php" style="text-decoration: none; color: inherit;">
        <div class="menu-item">
            <div class="menu-icon">
                <i class="fas fa-hands-helping"></i>
            </div>
            <div class="menu-text">My Complaints</div>
        </div>
    </a>

    <?php if ($gUserId == 21) { ?>
        <a href="/admin_complaints.php" style="text-decoration: none; color: inherit;">
            <div class="menu-item">
                <div class="menu-icon">
                    <i class="fas fa-hands-helping"></i>
                </div>
                <div class="menu-text">Admin Complaints</div>
            </div>
        </a>
    <? } ?>
    <!-- <a href="/premium.php" style="text-decoration: none; color: inherit;">
        <div class="menu-item">
            <div class="menu-icon">
                <i class="fas fa-crown"></i>
            </div>
            <div class="menu-text">Premium</div>
        </div>
    </a> -->

    <a href="/settings.php" style="text-decoration: none; color: inherit;">
        <div class="menu-item">
            <div class="menu-icon">
                <i class="fas fa-cog"></i>
            </div>
            <div class="menu-text">Settings</div>
        </div>
    </a>


    <a href="/search_bar.php" style="text-decoration: none; color: inherit;">
        <div class="menu-item">
            <div class="menu-icon">
                <i class="fas fa-search"></i>
            </div>
            <div class="menu-text">Search</div>
        </div>
    </a>
    <!-- <a href="" style="text-decoration: none; color: inherit;">
        <div class="menu-item">
            <div class="menu-icon">
                <i class="fas fa-pen-fancy"></i>
            </div>
            <div class="menu-text">Creator</div>
        </div>
    </a> -->
    <a href="/logout.php" style="text-decoration: none; color: inherit;">
        <div class="menu-item">
            <div class="menu-icon">
                <i class="fas fa-sign-out-alt"></i>
            </div>
            <div class="menu-text">Logout</div>
        </div>
    </a>
</div>
<div class="sidebar-overlay" id="sidebar-overlay"></div>

<script>
    // Mobile sidebar toggle
    const sidebarToggle = document.getElementById('sidebar-toggle');
    const sidebar = document.querySelector('.sidebar');
    const sidebarOverlay = document.getElementById('sidebar-overlay');

    sidebarToggle.addEventListener('click', function() {
        sidebar.classList.toggle('active');
        sidebarOverlay.classList.toggle('active');
    });



    sidebarOverlay.addEventListener('click', function() {
        sidebar.classList.remove('active');
        sidebarOverlay.classList.remove('active');
    });
    document.addEventListener('DOMContentLoaded', function() {
        const menuItems = document.querySelectorAll('.menu-item');

        // Remove active from all items
        menuItems.forEach(item => item.classList.remove('active'));

        // Get current URL path
        const currentPath = window.location.pathname;

        // Add active class based on href match
        menuItems.forEach(item => {
            const parentLink = item.closest('a');
            if (parentLink) {
                const href = parentLink.getAttribute('href');
                if (currentPath.includes(href)) {
                    item.classList.add('active');
                }
            }
        });
    });
</script>