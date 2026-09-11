<?php
require_once '../inc/php/function.php';
?>
<style>
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
        background-color: #141414;
    }


    /* CSS for the navbar */
    .navbar {
        position: sticky;
        /* Makes the navbar sticky */
        top: 0;
        /* Sticks to the top */
        z-index: 10000;
        /* Ensures it stays on top of other content */
        transition: all 0.3s ease;
    }

    .navbar.scrolled {
        padding: 10px 0;
        /* Adjust the padding for a smaller navbar */
        background-color: #000;
        /* Change background color if needed */
    }

    /* 
        .navbar img {
            height: 60px;
            transition: height 0.3s ease;
        } */

    .navbar.scrolled img {
        height: 20px;
        /* Smaller logo height on scroll */
    }

    /* Mobile styles */
    @media (max-width: 768px) {
        .navbar {
            padding: 8px;
            /* Initial padding for mobile */
        }

        .navbar.scrolled {
            padding: 10px 0;
            /* Smaller padding when scrolled */
        }

    }

    .nav-item button {

        border: none !important;
        /* border-radius: 100% !important; */
        background-color: transparent;
        outline: none !important;
    }

    .nav-item button span {
        /* display: flex; */
        /* position: relative; */
        /* right: 5px; */
        align-items: center;
        justify-items: center;
        /* border-radius: 100%; */
        background-color: transparent !important;
    }

    /* button:focus {
            outline: 1px dotted;
            outline: 5px auto -webkit-focus-ring-color;
        } */

    #theme-icon img {
        fill: white;
        /* Change the color to white */
    }

    /* .dropdown-menu.show {
         display: block;
         right: 50px;
         position: absolute;
         padding: 10px;
         display: block;
     } */

    #toggle-mode {
        text-decoration: none;
        /* Prevent underline or other hover styles */
        cursor: pointer;
        /* Optional: Make it look like a clickable button */
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
        /* Ensure text color doesn't change on hover */
        background-color: transparent;
        /* Prevent any background color change on hover */
    }

    .dropdown-item:focus,
    .dropdown-item:hover {
        color: var(--bs-dropdown-link-hover-color);
        background-color: none !important;
    }

    body.light-mode #toggle-mode #mode-text {
        color: #141414;
        /* Ensure the text color remains consistent */
    }

    body.dark-mode #toggle-mode #mode-text {
        color: white;
        /* Ensure the text color remains consistent */
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

    /* Mobile size adjustment */
    /* @media (max-width: 768px) {
        svg {
            width: 18px;
            height: 18px;
        }
    } */



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

<? require_once '../inc/php/db_config.php' ?>

<nav class="navbar navbar-expand-lg">
    <div class="container d-flex col-md-12 ">
        <!-- <a class="navbar-brand" href="/stream.php">
             <img src="/grfx/img/logo.black.png" height="60px" alt="Logo">
         </a> -->
        <a class="navbar-brand" href="/stream.php">
            <img src="/grfx/img/logo.black.png"
                srcset="/grfx/img/logo.black.png 1200w, /grfx/img/logo.black-large.png 1200w"
                sizes="(max-width: 600px), (min-width: 601px)" alt="Logo" class="navbar-logo">
        </a>

        <div class="end-navbar">
            <div class="nav-item dropdown " style="display:flex;">


                <!-- <button id="toggle-mode" class="toggle-button">
                     <span id="theme-icon">&#9728;</span>
                 </button> -->



                <a class="nav-link dropdown-toggle" href="#" id="settingsDropdown" role="button" aria-expanded="false">
                    <span id="settings-text">
                        <div class="avatar">
                            <img src="<?= viewProfilePic($creamdb, $gUserId) ?>" alt="Default Image"
                                onerror="this.onerror=null; this.src='/data/profilePic/default.png';">
                        </div>

                    </span>
                </a>


                <ul class="dropdown-menu dropdown-menu-end flex flex-col justify-center "
                    aria-labelledby="settingsDropdown">
                    <li>
                        <a class="dropdown-item " href="/account.php">
                            <svg xmlns="http://www.w3.org/2000/svg" width="100%" height="100%" viewBox="0 0 24 24">
                                <path fill="currentColor"
                                    d="M12 4a4 4 0 0 1 4 4a4 4 0 0 1-4 4a4 4 0 0 1-4-4a4 4 0 0 1 4-4m0 2a2 2 0 0 0-2 2a2 2 0 0 0 2 2a2 2 0 0 0 2-2a2 2 0 0 0-2-2m0 7c2.67 0 8 1.33 8 4v3H4v-3c0-2.67 5.33-4 8-4m0 1.9c-2.97 0-6.1 1.46-6.1 2.1v1.1h12.2V17c0-.64-3.13-2.1-6.1-2.1" />
                            </svg>
                            <span class="px-1"><?php echo htmlspecialchars($gUserName); ?></span>
                        </a>
                    </li>
                    <!-- <li><a class="dropdown-item" href="">Account</a></li> -->
                    <!-- <li><a class="dropdown-item" href="kannada/dashboard.php">ಕನ್ನಡ</a></li> -->
                    <li>
                        <a class="dropdown-item" href="/my_collection.php">
                            <!-- <i class="fas fa-newspaper"></i> -->
                            <svg xmlns="http://www.w3.org/2000/svg" width="100%" height="100%" viewBox="0 0 24 24">
                                <path fill="currentColor" d="M19 3.25H6.75a2.43 2.43 0 0 0-2.5 2.35V18a2.85 2.85 0 0 0 2.94 2.75H19a.76.76 0 0 0 .75-.75V4a.76.76 0 0 0-.75-.75m-.75 16H7.19A1.35 1.35 0 0 1 5.75 18a1.35 1.35 0 0 1 1.44-1.25h11.06Zm0-4H7.19a3 3 0 0 0-1.44.37V5.6a.94.94 0 0 1 1-.85h11.5Z" />
                                <path fill="currentColor" d="M8.75 8.75h6.5a.75.75 0 0 0 0-1.5h-6.5a.75.75 0 0 0 0 1.5m0 3.5h6.5a.75.75 0 0 0 0-1.5h-6.5a.75.75 0 0 0 0 1.5" />
                            </svg>
                            <span class="px-1"> My Collection</span>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="/saved.php">
                            <!-- <i class="fas fa-newspaper"></i> -->
                            <i class="fa-regular fa-bookmark"></i>
                            <span class="px-1">Bookmarks</span>
                        </a>
                    </li>
                    <li class="notDisInMob"><a class="dropdown-item " href="/stream.php">
                            <svg xmlns="http://www.w3.org/2000/svg" width="100%" height="100%" viewBox="0 0 24 24">
                                <g fill="none" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.141 5A9.97 9.97 0 0 1 22 12a9.97 9.97 0 0 1-2.922 7.064M5 19.142A9.97 9.97 0 0 1 2 12a9.97 9.97 0 0 1 2.936-7.078m11.349 3.122C17.345 9.059 18 10.449 18 11.982c0 1.552-.67 2.957-1.753 3.974M7.8 16C6.69 14.979 6 13.556 6 11.982C6 10.427 6.673 9.018 7.762 8" />
                                    <path d="M13.656 10.451C14.552 11.11 15 11.438 15 12s-.448.891-1.344 1.549a13 13 0 0 1-.718.495a12 12 0 0 1-.653.38c-.894.49-1.34.735-1.741.464s-.437-.838-.51-1.971c-.02-.321-.034-.635-.034-.917s.013-.596.034-.917c.072-1.133.109-1.7.51-1.97c.4-.272.847-.027 1.74.462c.233.127.457.256.654.381c.226.143.471.314.718.495Z" />
                                </g>
                            </svg>
                            <span class="px-1">Social</span>
                        </a></li>
                    <li class="notDisInMob"><a class="dropdown-item notDisInMob" href="/dashboard.php">
                            <svg xmlns="http://www.w3.org/2000/svg" width="100%" height="100%" viewBox="0 0 24 24">
                                <path fill="currentColor"
                                    d="M14 9.9V8.2q.825-.35 1.688-.525T17.5 7.5q.65 0 1.275.1T20 7.85v1.6q-.6-.225-1.213-.337T17.5 9q-.95 0-1.825.238T14 9.9m0 5.5v-1.7q.825-.35 1.688-.525T17.5 13q.65 0 1.275.1t1.225.25v1.6q-.6-.225-1.213-.338T17.5 14.5q-.95 0-1.825.225T14 15.4m0-2.75v-1.7q.825-.35 1.688-.525t1.812-.175q.65 0 1.275.1T20 10.6v1.6q-.6-.225-1.213-.338T17.5 11.75q-.95 0-1.825.238T14 12.65M6.5 16q1.175 0 2.288.263T11 17.05V7.2q-1.025-.6-2.175-.9T6.5 6q-.9 0-1.788.175T3 6.7v9.9q.875-.3 1.738-.45T6.5 16m6.5 1.05q1.1-.525 2.213-.787T17.5 16q.9 0 1.763.15T21 16.6V6.7q-.825-.35-1.713-.525T17.5 6q-1.175 0-2.325.3T13 7.2zM12 20q-1.2-.95-2.6-1.475T6.5 18q-1.05 0-2.062.275T2.5 19.05q-.525.275-1.012-.025T1 18.15V6.1q0-.275.138-.525T1.55 5.2q1.15-.6 2.4-.9T6.5 4q1.45 0 2.838.375T12 5.5q1.275-.75 2.663-1.125T17.5 4q1.3 0 2.55.3t2.4.9q.275.125.413.375T23 6.1v12.05q0 .575-.487.875t-1.013.025q-.925-.5-1.937-.775T17.5 18q-1.5 0-2.9.525T12 20m-5-8.35" />
                            </svg>
                            <span class="px-1">Central</span></a></li>
                    <!-- <li><a class="dropdown-item" href="cream_dashboard.php">Creator</a></li> -->
                    <li class="notDisInMob"> <a class="dropdown-item notDisInMob" href="/cream_dashboard.php">
                            <svg xmlns="http://www.w3.org/2000/svg" width="100%" height="100%" viewBox="0 0 48 48">
                                <g fill="currentColor">
                                    <path
                                        d="M12.5 6c-1.112 4.017-2.543 5.39-6.5 6.5c3.957 1.11 5.388 2.483 6.5 6.5c1.112-4.017 2.543-5.39 6.5-6.5c-3.957-1.11-5.388-2.483-6.5-6.5m0 17c-1.112 4.017-2.543 5.39-6.5 6.5c3.957 1.11 5.388 2.483 6.5 6.5c1.112-4.017 2.543-5.39 6.5-6.5c-3.957-1.11-5.388-2.483-6.5-6.5M23 12.5c3.957-1.11 5.388-2.483 6.5-6.5c1.112 4.017 2.543 5.39 6.5 6.5c-3.957 1.11-5.388 2.483-6.5 6.5c-1.112-4.017-2.543-5.39-6.5-6.5" />
                                    <path fill-rule="evenodd"
                                        d="m35.8 41.456l-.23-.23l-.014-.013l-18.142-18.142a2 2 0 0 1 0-2.828l2.829-2.829a2 2 0 0 1 2.828 0L41.456 35.8a2 2 0 0 1 0 2.828l-2.828 2.829a2 2 0 0 1-2.829 0M22.615 25.444l-3.787-3.787l2.828-2.829l3.788 3.788z"
                                        clip-rule="evenodd" />
                                </g>
                            </svg>
                            <span class="px-1">Creator</span>
                        </a>
                    </li>


                    <!-- <li><a class="dropdown-item" href="./analytics.php">Analytics</a></li> -->
                    <button id="toggle-mode" class="toggle-button dropdown-item">
                        <span id="theme-icon">
                            <!-- Icon will be inserted here dynamically via JavaScript -->
                        </span>
                        <span class="px-1" id="mode-text"> Dark </span> <!-- Text that will change based on mode -->
                    </button>
                    <li>
                        <a class="dropdown-item" href="/settings.php">
                            <svg xmlns="http://www.w3.org/2000/svg" width="100%" height="100%" viewBox="0 0 24 24">
                                <g fill="none" fill-rule="evenodd">
                                    <path d="m12.593 23.258l-.011.002l-.071.035l-.02.004l-.014-.004l-.071-.035q-.016-.005-.024.005l-.004.01l-.017.428l.005.02l.01.013l.104.074l.015.004l.012-.004l.104-.074l.012-.016l.004-.017l-.017-.427q-.004-.016-.017-.018m.265-.113l-.013.002l-.185.093l-.01.01l-.003.011l.018.43l.005.012l.008.007l.201.093q.019.005.029-.008l.004-.014l-.034-.614q-.005-.018-.02-.022m-.715.002a.02.02 0 0 0-.027.006l-.006.014l-.034.614q.001.018.017.024l.015-.002l.201-.093l.01-.008l.004-.011l.017-.43l-.003-.012l-.01-.01z" />
                                    <path fill="currentColor" d="M14.41 2.293a10 10 0 0 1 2.75 1.14a1 1 0 0 1 .47 1.019c-.113.689.059 1.216.38 1.538c.322.322.85.493 1.538.38a1 1 0 0 1 1.02.47a10 10 0 0 1 1.14 2.75a1 1 0 0 1-.388 1.054c-.568.407-.82.9-.82 1.356s.252.95.82 1.357a1 1 0 0 1 .388 1.053a10 10 0 0 1-1.14 2.75a1 1 0 0 1-1.02.47c-.689-.113-1.215.059-1.537.38c-.322.322-.494.85-.38 1.538a1 1 0 0 1-.47 1.02a10 10 0 0 1-2.752 1.14a1 1 0 0 1-1.053-.388c-.406-.568-.9-.82-1.356-.82c-.455 0-.95.252-1.356.82a1 1 0 0 1-1.053.388a10 10 0 0 1-2.752-1.14a1 1 0 0 1-.47-1.02c.114-.688-.057-1.215-.38-1.537c-.321-.322-.848-.494-1.537-.38a1 1 0 0 1-1.019-.47a10 10 0 0 1-1.14-2.752a1 1 0 0 1 .388-1.053c.567-.406.82-.9.82-1.356c0-.455-.253-.95-.82-1.356a1 1 0 0 1-.388-1.053a10 10 0 0 1 1.14-2.751a1 1 0 0 1 1.019-.47c.689.113 1.216-.058 1.538-.38s.493-.85.38-1.538a1 1 0 0 1 .47-1.019a10 10 0 0 1 2.75-1.14a1 1 0 0 1 1.054.388c.407.567.9.82 1.356.82s.95-.253 1.357-.82a1 1 0 0 1 1.053-.388m.102 2.11C13.855 5.06 12.992 5.5 12 5.5s-1.855-.439-2.512-1.098a8 8 0 0 0-1.084.45c.001.93-.299 1.85-1 2.552c-.701.701-1.622 1.001-2.552 1q-.264.523-.45 1.084C5.061 10.145 5.5 11.008 5.5 12s-.439 1.855-1.098 2.512q.187.562.45 1.085c.93-.002 1.85.298 2.552 1c.701.7 1.001 1.621 1 2.551q.522.264 1.084.45c.657-.659 1.52-1.098 2.512-1.098s1.855.44 2.512 1.098a8 8 0 0 0 1.085-.45c-.002-.93.298-1.85 1-2.552c.7-.701 1.621-1 2.551-1q.264-.522.45-1.084c-.659-.657-1.098-1.52-1.098-2.512s.44-1.855 1.098-2.512a8 8 0 0 0-.45-1.084c-.93.001-1.85-.299-2.552-1c-.701-.701-1-1.622-1-2.552a8 8 0 0 0-1.084-.45ZM12 8a4 4 0 1 1 0 8a4 4 0 0 1 0-8m0 2a2 2 0 1 0 0 4a2 2 0 0 0 0-4" />
                                </g>
                            </svg>
                            <span class="px-1">Settings</span>
                        </a>
                    </li>

                    <? if ($gUserId == 319) { ?>
                        <li>
                            <a class="dropdown-item" href="/videotest.php">
                                <svg xmlns="http://www.w3.org/2000/svg" width="100%" height="100%" viewBox="0 0 24 24">
                                    <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 6.5C4.159 8.148 3 10.334 3 13a9 9 0 1 0 18 0c0-2.666-1.159-4.852-3-6.5M12 2v9m0-9c-.7 0-2.008 1.994-2.5 2.5M12 2c.7 0 2.008 1.994 2.5 2.5" color="currentColor" />
                                </svg>
                                <span class="px-1">Video Test</span>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="/streamtest.php">
                                <svg xmlns="http://www.w3.org/2000/svg" width="100%" height="100%" viewBox="0 0 24 24">
                                    <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 6.5C4.159 8.148 3 10.334 3 13a9 9 0 1 0 18 0c0-2.666-1.159-4.852-3-6.5M12 2v9m0-9c-.7 0-2.008 1.994-2.5 2.5M12 2c.7 0 2.008 1.994 2.5 2.5" color="currentColor" />
                                </svg>
                                <span class="px-1">Video Testing</span>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="/cookie.php">
                                <svg xmlns="http://www.w3.org/2000/svg" width="100%" height="100%" viewBox="0 0 24 24">
                                    <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 6.5C4.159 8.148 3 10.334 3 13a9 9 0 1 0 18 0c0-2.666-1.159-4.852-3-6.5M12 2v9m0-9c-.7 0-2.008 1.994-2.5 2.5M12 2c.7 0 2.008 1.994 2.5 2.5" color="currentColor" />
                                </svg>
                                <span class="px-1">check cookie</span>
                            </a>
                        </li>
                    <? } ?>

                    <li>
                        <a class="dropdown-item" href="/logout.php">
                            <svg xmlns="http://www.w3.org/2000/svg" width="100%" height="100%" viewBox="0 0 24 24">
                                <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 6.5C4.159 8.148 3 10.334 3 13a9 9 0 1 0 18 0c0-2.666-1.159-4.852-3-6.5M12 2v9m0-9c-.7 0-2.008 1.994-2.5 2.5M12 2c.7 0 2.008 1.994 2.5 2.5" color="currentColor" />
                            </svg>
                            <span class="px-1">Logout</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</nav>


<!-- Bootstrap JS and dependencies -->
<!-- <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
 <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
 <script src="https://stackpath.bootstrapcdn.com/bootstrap/5.3.0/js/bootstrap.min.js"></script> -->




<!-- Custom script for dropdown and screen resizing -->
<script>
    $(document).ready(function() {
        const isMobile = () => $(window).width() <= 768;

        // Handle dropdown toggle
        $('.dropdown-toggle').on('click', function(e) {
            e.preventDefault();
            const $dropdownMenu = $(this).next('.dropdown-menu');

            // Toggle visibility
            $dropdownMenu.toggle();

            // Close other open dropdowns
            $('.dropdown-menu').not($dropdownMenu).hide();
        });

        // Close dropdown when clicking outside
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.nav-item.dropdown').length) {
                $('.dropdown-menu').hide();
            }
        });

        // Adjust on window resize
        $(window).resize(function() {
            if (!isMobile()) {
                $('.dropdown-menu').hide(); // Ensure dropdowns are hidden on resize
            }
        });
    });
</script>




<script>
    $(function() {
        if (localStorage.getItem('mode') === 'light') {
            $('body').addClass('light-mode').removeClass('dark-mode');
            $('#theme-icon').html('<img src="/grfx/img/moon.png" width="20px" height="20px" alt="Light Mode">');
            $('#mode-text').text('Dark ');
        } else {
            $('body').addClass('dark-mode').removeClass('light-mode');
            $('#theme-icon').html('<img src="/grfx/img/sun.png" width="20px" height="20px" alt="Dark Mode">');
            $('#mode-text').text('Light ');
        }

        $('#toggle-mode').click(function() {
            $('body').toggleClass('dark-mode light-mode');

            if ($('body').hasClass('light-mode')) {
                localStorage.setItem('mode', 'light');
                $('#theme-icon').html('<img src="/grfx/img/moon.png" width="20px" height="20px" alt="Light Mode">');
                $('#mode-text').text('Dark ');
            } else {
                localStorage.setItem('mode', 'dark');
                $('#theme-icon').html('<img src="/grfx/img/sun.png" width="20px" height="20px" alt="Dark Mode">');
                $('#mode-text').text('Light ');
            }
        });
    });
</script>

<script>
    let lastScrollTop = 0; // Store last scroll position
    const navbar = document.querySelector('.navbar'); // Get the navbar element

    // Function to check if it's mobile view (you can adjust the breakpoint)
    function isMobile() {
        return window.innerWidth <= 768;
    }
    window.addEventListener('scroll', function() {
        if (isMobile()) { // Only apply the effect on mobile
            let currentScroll = window.scrollY;

            if (currentScroll > lastScrollTop) {
                navbar.style.transform = 'translateY(-100%)';
            } else {
                navbar.style.transform = 'translateY(0)';
            }

            lastScrollTop = currentScroll <= 0 ? 0 : currentScroll;
        }
    });

    // You can also check if window is resized and reapply the logic
    window.addEventListener('resize', function() {
        if (!isMobile()) {
            // Reset navbar position when the screen size goes above mobile view
            navbar.style.transform = 'translateY(0)';
        }
    });
</script>