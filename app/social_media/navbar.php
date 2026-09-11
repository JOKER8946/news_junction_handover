<script src="../../inc/common.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark">
    <a class="navbar-brand" href="../../dashboard.php">
        <img src="../../grfx/logo.png" width="120"></a>
    <button class="btn btn-link btn-sm order-1 order-lg-0" id="sidebarToggle" href="#"><i class="fas fa-bars"></i></button>
    <div class="navbar-nav ml-auto">
        <div class="navbar-nav text-light" style="margin-top:6px;margin-right:20px"><?= strtok($gUserName, " "); ?></div>
        <div class="navbar-nav text-light" style="margin-top:6px;margin-right:20px"><? if ($gUserPlan == 1) { ?>Pro<? } else { ?>Free<? } ?></div>
        <!-- <div class="navbar-nav text-light cursorH" style="margin-top:10px;margin-right:10px" onclick="goSection('utils','','showNotifications')"><i class="fas fa-bell"></i></div> -->
        <ul class="navbar-nav ml-md-0">
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" id="userDropdown" href="#" role="button" data-toggle="dropdown"><i class="fas fa-user fa-fw"></i></a>
                <div class="dropdown-menu dropdown-menu-right">
                    <a class="dropdown-item" href="javascript:goSection('account')">My Account</a>
                    <a class="dropdown-item" href="javascript:goSection('settings')">My Settings</a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="../../process/logout.php" onclick="removeSignedIn()">Logout</a>
                </div>
            </li>
        </ul>
    </div>
</nav>