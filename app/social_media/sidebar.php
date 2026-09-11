<style>
    .sidebar {
        position: fixed;
        top: 50px;
        bottom: 0;
        left: 0;
        padding: 20px;
        background-color: #212529;
        color: white;
    }

    .category {
        font-weight: bold;
        margin-top: 20px;
        color: #adb5bd;
    }

    .templates,
    .lists,
    .mailer {
        list-style-type: none;
        padding-left: 0;
    }

    .templates li,
    .lists li,
    .mailer li {
        margin-top: 10px;
    }

    .templates li a,
    .lists li a,
    .mailer li a {
        color: #adb5bd;
        text-decoration: none;
    }

    .templates li a:hover,
    .lists li a:hover,
    .mailer li a:hover {
        color: #f8f9fa;
    }

    .back-btn button {
        background-color: #343a40;
        color: #cccccc;
        border: none;
        outline: none;
    }

    .back-btn:focus {
        border: none;
        outline: none;
    }

    .sb-sidenav-footer {
        display: flex;
        background-color: none;
    }
</style>

<div id="layoutSidenav_nav">
    <nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion">
        <div class="sb-sidenav-menu">
            <div class="nav mt-4">
                <div class="container-fluid">
                    <div class="row">
                        <div class="sidebar">
                            <!-- <div class="category">Social Media</div> -->
                            <ul class="templates">
                                <li><a href="../facebook/facebook_setup.php"><i class="fab fa-facebook-f"></i> Facebook</a></li>
                            </ul>
                            <ul class="templates">
                                <li><a href="../linkedin/linkedin_setup.php"><i class="fab fa-linkedin-in"></i> LinkedIn</a></li>
                            </ul>
                        </div>
                    </div>

                    <!-- <div class="row">
                        <div class="sidebar">
                            <div class="category">CAMPAIGNS</div>
                            <ul class="templates">
                                <li><a href="createList.php"><i class="fas fa-clipboard-list"></i> All Campaigns</a></li>
                                <li><a href="newCompaign.php"><i class="fas fa-calendar-plus"></i> New Campaign</a></li>
                            </ul>

                            <div class="category">LISTS &amp; SUBSCRIBERS</div>
                            <ul class="lists">
                                <li><a href="addList.php"><i class="fas fa-list-alt"></i> Add List</a></li>
                                <li><a href="viewList.php"><i class="fas fa-th-large"></i> View All Lists</a></li>
                                <li><a href="housekeeping.php"><i class="fas fa-briefcase"></i> Housekeeping</a></li>
                                <li><a href="blacklist.php"><i class="fas fa-user-times"></i> Blacklist</a></li>
                            </ul>

                            <div class="category">GO TO MAILER</div>
                            <ul class="mailer">
                                <li><a href="index.php"><i class="fas fa-mail-bulk"></i> Mail</a></li>
                            </ul>
                        </div>

                    </div> -->
                </div>
            </div>
        </div>
        <!-- <div class="back-btn" style="border: none; outline:none; z-index:1; margin-bottom:4px;"><a href="../dashboard.php"><button class="small" style="padding:4px 8px">Back to Cream</button></a></div> -->
        <div class="sb-sidenav-footer" style="z-index: 1;">
            <div class="small">Version: 2.1</div>
        </div>
    </nav>
</div>