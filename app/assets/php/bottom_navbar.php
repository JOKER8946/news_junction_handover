<?php

?>


<link rel="stylesheet" href="/assets/css/styles.css">

<style>
    body {
        display: flex;
        flex-direction: column;
        min-height: 100vh;
        background-color: #f4f4f4;
    }



    /* Bottom Navbar Styles (Default - Hidden on desktop) */
    .bottom-navbar {
        position: fixed;
        bottom: 0;
        left: 0;
        width: 100%;

        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        display: flex;
        justify-content: space-around;
        align-items: center;
        height: 60px;
        z-index: 10;
        display: none;
    }


     .bottom-navbar {
        background-color: white;
        color: black;
    }


     .bottom-navbar a {
        color: #444;
    }

    .bottom-navbar a {
        text-decoration: none;
        text-align: center;
        display: flex;
        justify-content: center;
        align-items: center;
        font-size: 24px;
        height: 100%;
        width: 100%;
    }

    .bottom-navbar a:hover {
        color: #555;
    }

    .active {
        color: #ff4500;
    }

    @media (max-width: 768px) {
        .bottom-navbar {
            display: flex;
        }
    }
</style>

<body>
    <div class="bottom-navbar">
        <a href="../../stream.php" class="active">
            <div class="bottom-navbar-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
                    <g fill="none" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.141 5A9.97 9.97 0 0 1 22 12a9.97 9.97 0 0 1-2.922 7.064M5 19.142A9.97 9.97 0 0 1 2 12a9.97 9.97 0 0 1 2.936-7.078m11.349 3.122C17.345 9.059 18 10.449 18 11.982c0 1.552-.67 2.957-1.753 3.974M7.8 16C6.69 14.979 6 13.556 6 11.982C6 10.427 6.673 9.018 7.762 8" />
                        <path d="M13.656 10.451C14.552 11.11 15 11.438 15 12s-.448.891-1.344 1.549a13 13 0 0 1-.718.495a12 12 0 0 1-.653.38c-.894.49-1.34.735-1.741.464s-.437-.838-.51-1.971c-.02-.321-.034-.635-.034-.917s.013-.596.034-.917c.072-1.133.109-1.7.51-1.97c.4-.272.847-.027 1.74.462c.233.127.457.256.654.381c.226.143.471.314.718.495Z" />
                    </g>
                </svg>
            </div>
        </a>
        <a href="../../dashboard.php">
            <div class="bottom-navbar-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
                    <path fill="currentColor" d="M14 9.9V8.2q.825-.35 1.688-.525T17.5 7.5q.65 0 1.275.1T20 7.85v1.6q-.6-.225-1.213-.337T17.5 9q-.95 0-1.825.238T14 9.9m0 5.5v-1.7q.825-.35 1.688-.525T17.5 13q.65 0 1.275.1t1.225.25v1.6q-.6-.225-1.213-.338T17.5 14.5q-.95 0-1.825.225T14 15.4m0-2.75v-1.7q.825-.35 1.688-.525t1.812-.175q.65 0 1.275.1T20 10.6v1.6q-.6-.225-1.213-.338T17.5 11.75q-.95 0-1.825.238T14 12.65M6.5 16q1.175 0 2.288.263T11 17.05V7.2q-1.025-.6-2.175-.9T6.5 6q-.9 0-1.788.175T3 6.7v9.9q.875-.3 1.738-.45T6.5 16m6.5 1.05q1.1-.525 2.213-.787T17.5 16q.9 0 1.763.15T21 16.6V6.7q-.825-.35-1.713-.525T17.5 6q-1.175 0-2.325.3T13 7.2zM12 20q-1.2-.95-2.6-1.475T6.5 18q-1.05 0-2.062.275T2.5 19.05q-.525.275-1.012-.025T1 18.15V6.1q0-.275.138-.525T1.55 5.2q1.15-.6 2.4-.9T6.5 4q1.45 0 2.838.375T12 5.5q1.275-.75 2.663-1.125T17.5 4q1.3 0 2.55.3t2.4.9q.275.125.413.375T23 6.1v12.05q0 .575-.487.875t-1.013.025q-.925-.5-1.937-.775T17.5 18q-1.5 0-2.9.525T12 20m-5-8.35" />
                </svg>
            </div>
        </a>
        <!-- <a href="../../cream_dashboard.php">
            <div class="bottom-navbar-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 48 48">
                    <g fill="currentColor">
                        <path d="M12.5 6c-1.112 4.017-2.543 5.39-6.5 6.5c3.957 1.11 5.388 2.483 6.5 6.5c1.112-4.017 2.543-5.39 6.5-6.5c-3.957-1.11-5.388-2.483-6.5-6.5m0 17c-1.112 4.017-2.543 5.39-6.5 6.5c3.957 1.11 5.388 2.483 6.5 6.5c1.112-4.017 2.543-5.39 6.5-6.5c-3.957-1.11-5.388-2.483-6.5-6.5M23 12.5c3.957-1.11 5.388-2.483 6.5-6.5c1.112 4.017 2.543 5.39 6.5 6.5c-3.957 1.11-5.388 2.483-6.5 6.5c-1.112-4.017-2.543-5.39-6.5-6.5" />
                        <path fill-rule="evenodd" d="m35.8 41.456l-.23-.23l-.014-.013l-18.142-18.142a2 2 0 0 1 0-2.828l2.829-2.829a2 2 0 0 1 2.828 0L41.456 35.8a2 2 0 0 1 0 2.828l-2.828 2.829a2 2 0 0 1-2.829 0M22.615 25.444l-3.787-3.787l2.828-2.829l3.788 3.788z"
                            clip-rule="evenodd" />
                    </g>
                </svg>
            </div>
        </a> -->
        <a href="/search_bar.php">
            <div class="bottom-navbar-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
                    <path fill="currentColor" d="M10 4a6 6 0 1 0 0 12a6 6 0 0 0 0-12m-8 6a8 8 0 1 1 14.32 4.906l5.387 5.387a1 1 0 0 1-1.414 1.414l-5.387-5.387A8 8 0 0 1 2 10" />
                </svg>
            </div>
        </a>
        <a href="/manikya_market/" title="Manikya Market">
            <div class="bottom-navbar-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
                    <path fill="currentColor" d="M17 18a2 2 0 0 1 2 2a2 2 0 0 1-2 2a2 2 0 0 1-2-2c0-1.11.89-2 2-2M1 2h3.27l.94 2H20a1 1 0 0 1 1 1c0 .17-.05.34-.12.5l-3.58 6.47c-.34.61-1 1.03-1.75 1.03H8.1l-.9 1.63l-.03.12a.25.25 0 0 0 .25.25H19v2H7a2 2 0 0 1-2-2c0-.35.09-.68.24-.96l1.36-2.45L3 4H1zm6 16a2 2 0 0 1 2 2a2 2 0 0 1-2 2a2 2 0 0 1-2-2c0-1.11.89-2 2-2" />
                </svg>
            </div>
        </a>
        <a href="../../my_collection.php">
            <div class="bottom-navbar-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
                    <path fill="currentColor" d="M19 3.25H6.75a2.43 2.43 0 0 0-2.5 2.35V18a2.85 2.85 0 0 0 2.94 2.75H19a.76.76 0 0 0 .75-.75V4a.76.76 0 0 0-.75-.75m-.75 16H7.19A1.35 1.35 0 0 1 5.75 18a1.35 1.35 0 0 1 1.44-1.25h11.06Zm0-4H7.19a3 3 0 0 0-1.44.37V5.6a.94.94 0 0 1 1-.85h11.5Z" />
                    <path fill="currentColor" d="M8.75 8.75h6.5a.75.75 0 0 0 0-1.5h-6.5a.75.75 0 0 0 0 1.5m0 3.5h6.5a.75.75 0 0 0 0-1.5h-6.5a.75.75 0 0 0 0 1.5" />
                </svg>
            </div>
        </a>

    </div>

</body>