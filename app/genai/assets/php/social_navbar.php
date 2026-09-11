<style>
    body.light-mode .navbar {
        background-color: #f8f9f8;
        color: #333;

    }

    .navbar-brand {
        font-weight: 700;
        font-size: 1.5rem;
        color: #e50914 !important;
        /* Netflix Red */
    }

    /* Navbar links */
    .navbar-nav .nav-link {
        color: #bbb !important;
        transition: color 0.3s;
    }

    .navbar-nav .nav-link:hover {
        color: #fff !important;
    }

    .navbar {
        background-color: var(--navbar-bg-light) !important;
        ;
    }


    /* CSS for the navbar */
    .navbar {
        position: sticky;
        top: 0;
        z-index: 10000;
        transition: all 0.3s ease;
    }

    .navbar.scrolled {
        padding: 10px 0;
        /* Adjust the padding for a smaller navbar */
        background-color: #000;
        /* Change background color if needed */
    }

    .sidebar-content {
        background-color: #fff;
        color: #000 !important;
    }



    .navbar.scrolled img {
        height: 20px;
    }

    /* Mobile styles */
    @media (max-width: 768px) {
        .navbar {
            padding: 8px;
        }

        .navbar.scrolled {
            padding: 10px 0;
        }

    }

    .nav-item button {

        border: none !important;
        background-color: transparent;
        outline: none !important;
    }

    .nav-item button span {

        align-items: center;
        justify-items: center;
        background-color: transparent !important;
    }



    #theme-icon img {
        fill: white;
    }



    #toggle-mode {
        text-decoration: none;
        cursor: pointer;
    }

    .dropdown-item {
        display: block;
        width: 100%;
        padding: 0.1rem 1rem !important;
        clear: both;
        font-weight: 400;
        color: var(--bs-dropdown-link-color);
        text-align: inherit;
        text-decoration: none;
        white-space: nowrap;
        background-color: transparent;
        border: 0;
        border-radius: var(--bs-dropdown-item-border-radius, 0);
    }

    #toggle-mode,
    .dropdown-item:hover {
        color: none;
        background-color: transparent;
    }

    .dropdown-item:focus,
    .dropdown-item:hover {
        color: var(--bs-dropdown-link-hover-color);
        background-color: none !important;
    }

    body.light-mode #toggle-mode #mode-text {
        color: #141414;
    }



    .dropdown-menu .dropdown-menu-end li:hover {
        color: none;
        background-color: none;

    }



    .avatar {
        width: 40px;
        height: 40px;
        /* padding-top: 5px; */
        border-radius: 50%;
        border: 0.02px solid whitesmoke;
        overflow: hidden;
    }

    .avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .dropdown-menu .dropdown-menu-end {
        padding: 10px !important;
        display: block;
        position: fixed !important;
        top: 68% !important;
        right: 0% !important;
    }

    svg {
        width: 24px;
        height: 24px;
    }

    .fa-bookmark {
        margin-left: 6px;
        margin-right: 6px;
    }




    @media screen and (max-width:540px) {
        .dropdown-menu .dropdown-menu-end {
            padding: 10px !important;
            display: block;
            position: fixed !important;
            top: 6% !important;
            right: 10% !important;
        }

        .dropdown-item {
            display: block;
            width: 100%;
            font-size: 14px !important;
            padding: 0.1rem 0.8rem !important;
            clear: both;
            font-weight: 400;
            color: var(--bs-dropdown-link-color);
            text-align: inherit;
            text-decoration: none;
            white-space: nowrap;
            background-color: transparent;
            border: 0;
            border-radius: var(--bs-dropdown-item-border-radius, 0);
        }

        .notDisInMob {
            display: none;
        }

    }
</style>

<style>
    h5 {
        margin: 10px 0 !important;
        color: #000;
    }

    .sidebar {
        position: fixed;
        top: 0px;
        left: -250px;
        width: 250px;
        height: 100%;
        overflow-x: hidden;
        transition: 0.3s ease;
        z-index: 1000;
    }



     .sidebar {
        background-color: white;
        color: black !important;
    }


    .sidebar .sidebar-content {
        display: flex;
        flex-direction: column;
        gap: 20px;
        padding: 20px;
        color: white;
    }

    .sidebar a {
        display: block;
        /* padding: 15px; */
        padding: 0.1rem 0.8rem !important;
        text-decoration: none;
        transition: background-color 0.3s;
    }


    .sidebar a {
        color: black;
    }

    .close-sidebar-btn {
        width: fit-content;
        font-size: 30px;
        /* left: 85%; */
        position: relative;

        color: #333;
        background: none;
        border: none;
        cursor: pointer;
    }

    @media (max-width: 844px) {
        .profile-toggle {
            cursor: pointer;
            font-size: 18px;
        }

        .sidebar.open {
            left: 0;
            top: 0px;
        }
    }
</style>

<? require_once 'db_config.php' ?>

<nav class="navbar navbar-expand-lg">
    <div class="container d-flex col-lg-12 ">
        <a class="navbar-brand profile-toggle" href="javascript:void(0);" id="profile-toggle">
            <img src="/assets/img/logo.black.png"
                srcset="/assets/img/logo.black.png 1200w, /assets/img/logo.black-large.png 1200w"
                sizes="(max-width: 600px), (min-width: 601px)" alt="Logo" class="navbar-logo">
        </a>


        <!-- Sidebar (Navigation) -->
        <div class="sidebar" id="sidebar">
            <div class="sidebar-content">

                <div style="border-bottom: 0.2px solid #cbcbcb; display:flex; justify-content:space-between;">

                    <a class="dropdown-item" href="/stream.php">
                        <img src="/assets/img/logo.black.png"
                            srcset="/assets/img/logo.black.png 1200w, /assets/img/logo.black-large.png 1200w"
                            sizes="(max-width: 600px), (min-width: 601px)" alt="Logo" class="navbar-logo">
                    </a>
                    <button id="close-sidebar" class="close-sidebar-btn">×</button>

                </div>



                <div style="border-bottom: 0.2px solid #cbcbcb;">

                    <a class="dropdown-item" href="/stream.php">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 16 16">
                            <rect width="16" height="16" fill="none" />
                            <path fill="currentColor" fill-rule="evenodd" d="m8.36 1.37l6.36 5.8l-.71.71L13 6.964v6.526l-.5.5h-3l-.5-.5v-3.5H7v3.5l-.5.5h-3l-.5-.5V6.972L2 7.88l-.71-.71l6.35-5.8zM4 6.063v6.927h2v-3.5l.5-.5h3l.5.5v3.5h2V6.057L8 2.43z" clip-rule="evenodd" />
                        </svg>
                        <span class="px-1">Home</span>
                    </a>

                </div>

                <div class="socail-section" style="gap: 10px;border-bottom: 0.2px solid #cbcbcb;">
                    <h5>Social</h5>
                    <a class="dropdown-item" href="/saved.php">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
                            <path fill="currentColor" d="M6.09 21.06a1 1 0 0 1-1-1L4.94 5.4a2.26 2.26 0 0 1 2.18-2.35L16.71 3a2.27 2.27 0 0 1 2.23 2.31l.14 14.66a1 1 0 0 1-.49.87a1 1 0 0 1-1 0l-5.7-3.16l-5.29 3.23a1.2 1.2 0 0 1-.51.15m5.76-5.55a1.1 1.1 0 0 1 .5.12l4.71 2.61l-.12-12.95c0-.2-.13-.34-.21-.33l-9.6.09c-.08 0-.19.13-.19.33l.12 12.9l4.28-2.63a1.06 1.06 0 0 1 .51-.14" />
                        </svg> <span class="px-1">Bookmarks</span>
                    </a>

                    <a class=" dropdown-item" onclick="window.location.href='./follow_dash.php'" style="display: flex; gap:8px">
                        <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24">
                            <g fill="none">
                                <path d="m12.593 23.258l-.011.002l-.071.035l-.02.004l-.014-.004l-.071-.035q-.016-.005-.024.005l-.004.01l-.017.428l.005.02l.01.013l.104.074l.015.004l.012-.004l.104-.074l.012-.016l.004-.017l-.017-.427q-.004-.016-.017-.018m.265-.113l-.013.002l-.185.093l-.01.01l-.003.011l.018.43l.005.012l.008.007l.201.093q.019.005.029-.008l.004-.014l-.034-.614q-.005-.018-.02-.022m-.715.002a.02.02 0 0 0-.027.006l-.006.014l-.034.614q.001.018.017.024l.015-.002l.201-.093l.01-.008l.004-.011l.017-.43l-.003-.012l-.01-.01z" />
                                <path fill="currentColor" d="M16 14a5 5 0 0 1 5 5v1a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-1a5 5 0 0 1 5-5zm5.414-4.919a1 1 0 0 1 1.498 1.32l-.084.095L20 13.324a1 1 0 0 1-1.32.083l-.094-.083l-1.414-1.414a1 1 0 0 1 1.32-1.498l.094.084l.707.707zM12 2a5 5 0 1 1 0 10a5 5 0 0 1 0-10" />
                            </g>
                        </svg> <!-- Sun icon for dark mode -->
                        <span>Following</span>
                    </a>
                    <a class="dropdown-item" href="/dashboard.php">
                        <svg xmlns="http://www.w3.org/2000/svg" width="100%" height="100%" viewBox="0 0 24 24">
                            <path fill="currentColor"
                                d="M14 9.9V8.2q.825-.35 1.688-.525T17.5 7.5q.65 0 1.275.1T20 7.85v1.6q-.6-.225-1.213-.337T17.5 9q-.95 0-1.825.238T14 9.9m0 5.5v-1.7q.825-.35 1.688-.525T17.5 13q.65 0 1.275.1t1.225.25v1.6q-.6-.225-1.213-.338T17.5 14.5q-.95 0-1.825.225T14 15.4m0-2.75v-1.7q.825-.35 1.688-.525t1.812-.175q.65 0 1.275.1T20 10.6v1.6q-.6-.225-1.213-.338T17.5 11.75q-.95 0-1.825.238T14 12.65M6.5 16q1.175 0 2.288.263T11 17.05V7.2q-1.025-.6-2.175-.9T6.5 6q-.9 0-1.788.175T3 6.7v9.9q.875-.3 1.738-.45T6.5 16m6.5 1.05q1.1-.525 2.213-.787T17.5 16q.9 0 1.763.15T21 16.6V6.7q-.825-.35-1.713-.525T17.5 6q-1.175 0-2.325.3T13 7.2zM12 20q-1.2-.95-2.6-1.475T6.5 18q-1.05 0-2.062.275T2.5 19.05q-.525.275-1.012-.025T1 18.15V6.1q0-.275.138-.525T1.55 5.2q1.15-.6 2.4-.9T6.5 4q1.45 0 2.838.375T12 5.5q1.275-.75 2.663-1.125T17.5 4q1.3 0 2.55.3t2.4.9q.275.125.413.375T23 6.1v12.05q0 .575-.487.875t-1.013.025q-.925-.5-1.937-.775T17.5 18q-1.5 0-2.9.525T12 20m-5-8.35" />
                        </svg>
                        <span class="px-1">Reader</span>
                    </a>
                </div>

                <div class="Creator-section" style="border-bottom: 0.2px solid #cbcbcb;">
                    <h5>Creator</h5>
                    <a class="dropdown-item" href="/my_collection.php">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
                            <path fill="currentColor" d="M8 16h12V4h-2v7l-2.5-1.5L13 11V4H8zm-2 2V2h16v16zm-4 4V6h2v14h14v2zM8 4v12zm5 7l2.5-1.5L18 11l-2.5-1.5z" />
                        </svg>
                        <span class="px-1"> My Collection</span>
                    </a>
                    <a class="dropdown-item" href="/create.php">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 16 16">
                            <path fill="currentColor" d="M15.49 7.3h-1.16v6.35H1.67V3.28H8V2H1.67A1.21 1.21 0 0 0 .5 3.28v10.37a1.21 1.21 0 0 0 1.17 1.25h12.66a1.21 1.21 0 0 0 1.17-1.25z" />
                            <path fill="currentColor" d="M10.56 2.87L6.22 7.22l-.44.44l-.08.08l-1.52 3.16a1.08 1.08 0 0 0 1.45 1.45l3.14-1.53l.53-.53l.43-.43l4.34-4.36l.45-.44l.25-.25a2.18 2.18 0 0 0 0-3.08a2.17 2.17 0 0 0-1.53-.63a2.2 2.2 0 0 0-1.54.63l-.7.69l-.45.44zM5.51 11l1.18-2.43l1.25 1.26zm2-3.36l3.9-3.91l1.3 1.31L8.85 9zm5.68-5.31a.9.9 0 0 1 .65.27a.93.93 0 0 1 0 1.31l-.25.24l-1.3-1.3l.25-.25a.88.88 0 0 1 .69-.25z" />
                        </svg><span class="px-1">Editor</span>
                    </a>
                    <a class="dropdown-item" href="/genai/genai.php">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
                            <g fill="currentColor">
                                <path fill-rule="evenodd" d="M8.5 4a1 1 0 0 0-1-1h-3a1 1 0 0 0-1 1v16a1 1 0 0 0 1 1h3a1 1 0 0 0 1-1zm-3 1v14h1V5z" clip-rule="evenodd" />
                                <path d="M9.5 10.655V20a1 1 0 0 0 1 1h3a1 1 0 0 0 1-1v-6.751h-.125a5.9 5.9 0 0 1-1.875-.305V19h-1v-6.5a5.9 5.9 0 0 1-2-1.845" />
                                <path fill-rule="evenodd" d="M14.375 11.75q.063 0 .125-.002a4.35 4.35 0 0 0 2.18-.654l2.613 2.613l1.414-1.414l-2.613-2.613a4.375 4.375 0 1 0-3.72 2.07m0-2A2.37 2.37 0 0 1 12.643 9a2.375 2.375 0 1 1 1.732.75M16.5 15a1 1 0 0 0-1 1v4a1 1 0 0 0 1 1h3a1 1 0 0 0 1-1v-4a1 1 0 0 0-1-1zm1 4v-2h1v2z" clip-rule="evenodd" />
                            </g>
                        </svg> <span class="px-1">Market Research</span>
                    </a>
                    <a class="dropdown-item" href="/request_article.php">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
                            <path fill="currentColor" d="m20.713 7.128l-.246.566a.506.506 0 0 1-.934 0l-.246-.566a4.36 4.36 0 0 0-2.22-2.25l-.759-.339a.53.53 0 0 1 0-.963l.717-.319A4.37 4.37 0 0 0 19.276.931L19.53.32a.506.506 0 0 1 .942 0l.253.61a4.37 4.37 0 0 0 2.25 2.327l.718.32a.53.53 0 0 1 0 .962l-.76.338a4.36 4.36 0 0 0-2.219 2.251M9 2a8 8 0 0 1 7.934 6.965l2.25 3.539c.148.233.118.58-.225.728L17 14.07V17a2 2 0 0 1-2 2h-1.999L13 22H4v-3.694c0-1.18-.436-2.297-1.244-3.305A8 8 0 0 1 9 2m0 2a6 6 0 0 0-4.684 9.75C5.41 15.114 6 16.667 6 18.306V20h5l.002-3H15v-4.248l1.55-.664l-1.543-2.425l-.057-.442A6 6 0 0 0 9 4m10.49 12.993l1.664 1.11A10.95 10.95 0 0 0 23 12q-.001-1.025-.181-2l-1.943.5q.123.733.124 1.5a8.96 8.96 0 0 1-1.51 4.993" />
                        </svg> <span class="px-1">GenAI Creator</span>
                    </a>
                    <!-- <a class="dropdown-item" href="/CreatePage/index.php">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
                            <g fill="currentColor">
                                <path fill-rule="evenodd" d="M14 7a1 1 0 0 0-1 1v8a1 1 0 0 0 1 1h4a1 1 0 0 0 1-1V8a1 1 0 0 0-1-1zm3 2h-2v6h2z" clip-rule="evenodd" />
                                <path d="M6 7a1 1 0 0 0 0 2h4a1 1 0 1 0 0-2zm0 4a1 1 0 1 0 0 2h4a1 1 0 1 0 0-2zm-1 5a1 1 0 0 1 1-1h4a1 1 0 1 1 0 2H6a1 1 0 0 1-1-1" />
                                <path fill-rule="evenodd" d="M4 3a3 3 0 0 0-3 3v12a3 3 0 0 0 3 3h16a3 3 0 0 0 3-3V6a3 3 0 0 0-3-3zm16 2H4a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h16a1 1 0 0 0 1-1V6a1 1 0 0 0-1-1" clip-rule="evenodd" />
                            </g>
                        </svg> <span class="px-1">Landing Page</span>
                    </a> -->
                    <a class="dropdown-item" href="/newsletter.php">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
                            <g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" color="currentColor">
                                <path d="M18 15V9c0-2.828 0-4.243-.879-5.121C16.243 3 14.828 3 12 3H8c-2.828 0-4.243 0-5.121.879C2 4.757 2 6.172 2 9v6c0 2.828 0 4.243.879 5.121C3.757 21 5.172 21 8 21h12M6 8h8m-8 4h8m-8 4h4" />
                                <path d="M18 8h1c1.414 0 2.121 0 2.56.44c.44.439.44 1.146.44 2.56v8a2 2 0 1 1-4 0z" />
                            </g>
                        </svg> <span class="px-1">Newsletter</span>
                    </a>
                    <a class="dropdown-item" href="Xpress/newCompaign.php">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
                            <path fill="currentColor" d="M20 14.5v9L16.667 21H14v-4h2.667zM21 3a1 1 0 0 1 1 1v9h-2V7.237l-7.928 7.101L4 7.215V19h8v2H3a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1zm0 14a2 2 0 0 1 .15 3.994L21 21zM19.5 5H4.511l7.55 6.662z" />
                        </svg>
                        <span class="px-1">Mailer Campaign</span>
                    </a>
                    <a class="dropdown-item" href="/analytics.php">
                        <!-- <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 21 21">
                            <rect width="21" height="21" fill="none" />
                            <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" d="M3.5 3.5v12a2 2 0 0 0 2 2H17m-10.5-6v3m4-6v6m4-9v9" />
                        </svg> -->
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
                            <path fill="currentColor" d="M4 2v18h18v2H2V2zm12 4h6v6h-2V9.423l-1.579 1.575l-2.714 2.708l-.707.707l-4-4l-5 5L4.586 14L11 7.585l4 4l2.009-2.003L18.594 8H16z" />
                        </svg>
                        <span class="px-1">Analytics</span>
                    </a>

                </div>


                <a class="dropdown-item" href="/account.php">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
                        <rect width="24" height="24" fill="none" />
                        <path fill="currentColor" d="M12 4a4 4 0 0 1 4 4a4 4 0 0 1-4 4a4 4 0 0 1-4-4a4 4 0 0 1 4-4m0 2a2 2 0 0 0-2 2a2 2 0 0 0 2 2a2 2 0 0 0 2-2a2 2 0 0 0-2-2m0 7c2.67 0 8 1.33 8 4v3H4v-3c0-2.67 5.33-4 8-4m0 1.9c-2.97 0-6.1 1.46-6.1 2.1v1.1h12.2V17c0-.64-3.13-2.1-6.1-2.1" />
                    </svg>
                    <span class="px-1">My Account</span>
                </a>

                <a class="dropdown-item" href="/search_bar.php">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
                        <rect width="24" height="24" fill="none" />
                        <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10a7 7 0 1 0 14 0a7 7 0 1 0-14 0m18 11l-6-6" />
                    </svg>
                    <span class="px-1">Search</span>
                </a>

                <a class="dropdown-item" href="/premium.php">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 20 20">
                        <rect width="20" height="20" fill="none" />
                        <path fill="currentColor" d="M5.5 3a.5.5 0 0 0-.429.243l-3 5a.5.5 0 0 0 .045.577l7.5 9a.5.5 0 0 0 .768 0l7.5-9a.5.5 0 0 0 .045-.577l-3-5A.5.5 0 0 0 14.5 3zM3.383 8l2.4-4h1.979l-1.6 4zm2.775 1l2.267 5.829L3.568 9zM10 16.12L7.231 9h5.538zM7.239 8l1.6-4h2.322l1.6 4zm6.6 0l-1.6-4h1.978l2.4 4zm.003 1h2.59l-4.857 5.829z" />
                    </svg>
                    <span class="px-1">
                        <?php
                        //=
                        //($gUserPlan == 0) ? "Go Premium" : "Pro"
                        ?>
                    </span>
                    <span class="">
                        Premium
                    </span>
                </a>
                <a class="dropdown-item" href="/settings.php">
                    <svg xmlns="http://www.w3.org/2000/svg" width="100%" height="100%" viewBox="0 0 24 24">
                        <g fill="none" fill-rule="evenodd">
                            <path d="m12.593 23.258l-.011.002l-.071.035l-.02.004l-.014-.004l-.071-.035q-.016-.005-.024.005l-.004.01l-.017.428l.005.02l.01.013l.104.074l.015.004l.012-.004l.104-.074l.012-.016l.004-.017l-.017-.427q-.004-.016-.017-.018m.265-.113l-.013.002l-.185.093l-.01.01l-.003.011l.018.43l.005.012l.008.007l.201.093q.019.005.029-.008l.004-.014l-.034-.614q-.005-.018-.02-.022m-.715.002a.02.02 0 0 0-.027.006l-.006.014l-.034.614q.001.018.017.024l.015-.002l.201-.093l.01-.008l.004-.011l.017-.43l-.003-.012l-.01-.01z" />
                            <path fill="currentColor" d="M14.41 2.293a10 10 0 0 1 2.75 1.14a1 1 0 0 1 .47 1.019c-.113.689.059 1.216.38 1.538c.322.322.85.493 1.538.38a1 1 0 0 1 1.02.47a10 10 0 0 1 1.14 2.75a1 1 0 0 1-.388 1.054c-.568.407-.82.9-.82 1.356s.252.95.82 1.357a1 1 0 0 1 .388 1.053a10 10 0 0 1-1.14 2.75a1 1 0 0 1-1.02.47c-.689-.113-1.215.059-1.537.38c-.322.322-.494.85-.38 1.538a1 1 0 0 1-.47 1.02a10 10 0 0 1-2.752 1.14a1 1 0 0 1-1.053-.388c-.406-.568-.9-.82-1.356-.82c-.455 0-.95.252-1.356.82a1 1 0 0 1-1.053.388a10 10 0 0 1-2.752-1.14a1 1 0 0 1-.47-1.02c.114-.688-.057-1.215-.38-1.537c-.321-.322-.848-.494-1.537-.38a1 1 0 0 1-1.019-.47a10 10 0 0 1-1.14-2.752a1 1 0 0 1 .388-1.053c.567-.406.82-.9.82-1.356c0-.455-.253-.95-.82-1.356a1 1 0 0 1-.388-1.053a10 10 0 0 1 1.14-2.751a1 1 0 0 1 1.019-.47c.689.113 1.216-.058 1.538-.38s.493-.85.38-1.538a1 1 0 0 1 .47-1.019a10 10 0 0 1 2.75-1.14a1 1 0 0 1 1.054.388c.407.567.9.82 1.356.82s.95-.253 1.357-.82a1 1 0 0 1 1.053-.388m.102 2.11C13.855 5.06 12.992 5.5 12 5.5s-1.855-.439-2.512-1.098a8 8 0 0 0-1.084.45c.001.93-.299 1.85-1 2.552c-.701.701-1.622 1.001-2.552 1q-.264.523-.45 1.084C5.061 10.145 5.5 11.008 5.5 12s-.439 1.855-1.098 2.512q.187.562.45 1.085c.93-.002 1.85.298 2.552 1c.701.7 1.001 1.621 1 2.551q.522.264 1.084.45c.657-.659 1.52-1.098 2.512-1.098s1.855.44 2.512 1.098a8 8 0 0 0 1.085-.45c-.002-.93.298-1.85 1-2.552c.7-.701 1.621-1 2.551-1q.264-.522.45-1.084c-.659-.657-1.098-1.52-1.098-2.512s.44-1.855 1.098-2.512a8 8 0 0 0-.45-1.084c-.93.001-1.85-.299-2.552-1c-.701-.701-1-1.622-1-2.552a8 8 0 0 0-1.084-.45ZM12 8a4 4 0 1 1 0 8a4 4 0 0 1 0-8m0 2a2 2 0 1 0 0 4a2 2 0 0 0 0-4" />
                        </g>
                    </svg>
                    <span class="px-1">Settings</span>
                </a>


                <!-- <button id="toggle-mode1" class="toggle-button dropdown-item">
                    <span id="theme-icon1"></span>
                    <span class="px-1" id="mode-text1" style="margin-left: 6px;">light</span>
                </button> -->
                <a class="dropdown-item" href="/logout.php">
                    <svg xmlns="http://www.w3.org/2000/svg" width="100%" height="100%" viewBox="0 0 24 24">
                        <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 6.5C4.159 8.148 3 10.334 3 13a9 9 0 1 0 18 0c0-2.666-1.159-4.852-3-6.5M12 2v9m0-9c-.7 0-2.008 1.994-2.5 2.5M12 2c.7 0 2.008 1.994 2.5 2.5" color="currentColor" />
                    </svg>
                    <span class="px-1">Logout</span>
                </a>



            </div>
        </div>

        <div class="end-navbar" style="display: flex; ">
            <div class="proMember" style="    display: flex;justify-content: center;align-items: center;">
                <span class="px-1" style="display: flex; justify-content:center ">
                    <?= ($gUserPlan == 0) ? "Free" : "Pro" ?>
                </span>
            </div>
            <div class="nav-item dropdown " style="display:flex;">
                <a class="nav-link" href="/account.php">
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
</nav>



<script>
    $(document).ready(function() {
        const isMobile = () => $(window).width() <= 768;

        $('.dropdown-toggle').on('click', function(e) {
            e.preventDefault();
            const $dropdownMenu = $(this).next('.dropdown-menu');

            $dropdownMenu.toggle();

            $('.dropdown-menu').not($dropdownMenu).hide();
        });

        $(document).on('click', function(e) {
            if (!$(e.target).closest('.nav-item.dropdown').length) {
                $('.dropdown-menu').hide();
            }
        });

        $(window).resize(function() {
            if (!isMobile()) {
                $('.dropdown-menu').hide(); // Ensure dropdowns are hidden on resize
            }
        });
    });
</script>

<script>
    $(document).ready(function() {
        function applyMode(mode) {
            if (mode === 'light') {
                $('body').addClass('light-mode').removeClass('dark-mode');
                $('#theme-icon').html('<img src="/assets/img/moon.png" width="20px" height="20px" alt="Light Mode">');
                $('#mode-text').text('Dark');
            } else {
                $('body').addClass('dark-mode').removeClass('light-mode');
                $('#theme-icon').html('<img src="/assets/img/sun.png" width="20px" height="20px" alt="Dark Mode">');
                $('#mode-text').text('Light');
            }
        }

        // Check stored mode or set default to dark
        let mode = localStorage.getItem('mode') || 'dark';
        applyMode(mode);

        $('#toggle-mode').click(function() {
            mode = (mode === 'dark') ? 'light' : 'dark';
            localStorage.setItem('mode', mode);
            applyMode(mode);
        });
    });
</script>