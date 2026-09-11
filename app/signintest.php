<!doctype html>
<html lang="en">

<head>
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-LBXBWFM961"></script>
    <script>
        window.dataLayer = window.dataLayer || [];

        function gtag() {
            dataLayer.push(arguments);
        }
        gtag('js', new Date());

        gtag('config', 'G-LBXBWFM961');
    </script>
    <title>News Junction</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.1/aos.css">
    <link rel="stylesheet" href="inc/css/cream.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css"
        integrity="sha384-9aIt2nRpC12Uk9gS9baDl411NQApFmC26EwAOH8WgZl5MYYxFfc+NcPb1dKGj7Sk" crossorigin="anonymous" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.0.0/animate.min.css" />
    <link rel="stylesheet" href="inc/css/magnific-popup.css" />
    <link rel="stylesheet" href="inc/css/styles.css" />
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"
        integrity="sha256-9/aliU8dGd2tb6OSsuzixeV4y/faTqgFtohetphbbj0=" crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.min.js"
        integrity="sha384-OgVRvuATP1z7JjHLkuOU7Xw704+h835Lr+6QL9UvYjZE3Ipu6Tp75j7Bh/kR0JKI"
        crossorigin="anonymous"></script>
    <!-- <script src="inc/js/magnific-popup.min.js"></script> -->

    <!-- Magnific Popup CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/magnific-popup@1.1.0/dist/magnific-popup.css" />

    <!-- Magnific Popup JS -->
    <script src="https://cdn.jsdelivr.net/npm/magnific-popup@1.1.0/dist/jquery.magnific-popup.min.js"></script>

    <!-- <script src="inc/js/common.js"></script> -->

    <!-- Global site tag (gtag.js) - Google Ads: 991688670 -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=AW-991688670"></script>
    <script src="https://accounts.google.com/gsi/client" async defer></script>

    <script>
        window.dataLayer = window.dataLayer || [];

        function gtag() {
            dataLayer.push(arguments);
        }
        gtag('js', new Date());
        gtag('config', 'AW-991688670');
    </script>
    <!-- Event snippet for Cream-Subscription conversion page In your html page, add the snippet and call gtag_report_conversion when someone clicks on the chosen link or button. -->
    <script>
        function gtag_report_conversion(url) {
            var callback = function() {
                if (typeof(url) != 'undefined') {
                    window.location = url;
                }
            };
            gtag('event', 'conversion', {
                'send_to': 'AW-991688670/ApZWCLnHhOQBEN7v79gD',
                'event_callback': callback
            });
            return false;
        }
    </script>

    <script type="text/javascript">
        $(function() {
            var wchTab = getParam('type');
            if (wchTab == 'login') {
                $('#tabSignup').removeClass('active');
                $('#tabLogin').addClass('active');
                $('#signup').removeClass('show').removeClass('active');
                $('#login').addClass('show').addClass('active');
            }
            getBusinessType();
        });
    </script>
    <!-- <style>
        .mfp-content {
            width: 360px !important;
            padding: 44px;
            width: unset !important;
            background-color: #121212;
        }

        .mfp-container {
            text-align: center;
            position: absolute;
            width: 100%;
            height: auto;
            display: flex;
            top: 20%;
            left: 0;
            /* top: 0; */
            padding: 0 8px;
            border-radius: 10px;
            box-sizing: border-box;
        }

        .nav-link {
            color: white !important;
            /* Inactive tabs in white */
        }

        .nav-link.active {
            color: #ff6347 !important;
            background-color: transparent;
        }

        .nav-link.active#tabSignup {
            color: #ff6347;
        }

        .nav-link.active#tabLogin {
            color: #32cd32;
        }

        .nav-link:hover {
            color: #007bff;
        }

        #btnOkAfterMessage {
            background-color: #db5919;
            border-color: #db5919;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            font-size: 16px;
            transition: background-color 0.3s;
        }

        #btnOkAfterMessage:hover {
            background-color: #28a745;
            border-color: #28a745;
        }

        .mfp-content {
            width: 360px !important;
            padding: 44px;
            width: unset !important;
            background-color: #121212;
        }

        .mfp-container {
            text-align: center;
            position: absolute;
            width: 100%;
            height: auto;
            display: flex;
            top: 20%;
            left: 0;
            /* top: 0; */
            padding: 0 8px;
            border-radius: 10px;
            box-sizing: border-box;
        }

        body {
            background-color: #f8f4e3 !important;
        }

        /* .headding {
            color: #fafafa;
        } */

        .nav-link,
        .nav-tabs .nav-link.active {
            background-color: transparent !important;
        }

        .mfp-close-btn-in .mfp-close {
            color: #fff3f3;
        }

        .mfp-content {
            max-width: 30%;
        }
    </style> -->

    <style>
        :root {
            --cream: #f8f4e3;
            --orange: #db5919;
            --white: #ffffff;
            --light-cream: #fdfaf0;
            --dark-cream: #e6e0cc;
            --dark-orange: #b44815;
            --light-orange: #f47a43;
            --text-dark: #333333;
            --gray: #f5f5f5;
        }

        /* Base Styles */
        body {
            background-color: var(--cream) !important;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .headding {
            font-size: 24px;
            color: var(--text-dark);
            margin-top: 5px;
        }

        /* Form Styles */
        .input-group-text {
            background-color: var(--white);
            border-color: var(--dark-cream);
        }

        .form-control {
            border-color: var(--dark-cream);
            transition: border-color 0.3s, box-shadow 0.3s;
        }

        .form-control:focus {
            border-color: none;
            box-shadow: none !important;
        }

        /* Button Styles */
        .btn-success {
            background-color: var(--orange);
            border-color: var(--orange);
            color: var(--white);
            padding: 10px 20px;
            border-radius: 5px;
            font-size: 16px;
            transition: background-color 0.3s, transform 0.2s;
        }

        .btn-success:hover {
            background-color: var(--dark-orange);
            border-color: var(--dark-orange);
            transform: translateY(-2px);
        }

        #btnOkAfterMessage {
            background-color: var(--orange);
            border-color: var(--orange);
            color: var(--white);
            padding: 10px 20px;
            border-radius: 5px;
            font-size: 16px;
            transition: background-color 0.3s;
        }

        #btnOkAfterMessage:hover {
            background-color: var(--orange);
            border-color: var(--orange);
        }

        /* Tab Navigation */
        .nav-tabs {
            border-bottom: 1px solid var(--dark-cream);
        }

        .nav-link {
            color: var(--text-dark) !important;
            font-weight: 500;
            border: none;
            transition: color 0.3s;
        }

        .nav-link:hover {
            color: var(--orange) !important;
        }

        .nav-link.active {
            color: var(--orange) !important;
            background-color: transparent !important;
            border-bottom: 3px solid var(--orange);
        }

        .nav-link.active#tabSignup {
            color: var(--orange) !important;
        }

        .nav-link.active#tabLogin {
            color: var(--orange) !important;
            border-bottom: 3px solid var(--orange);
        }

        /* Modal Popup Styles */
        .mfp-content {
            width: auto !important;
            max-width: 450px;
            padding: 40px;
            background-color: var(--white);
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }

        .mfp-container {
            text-align: center;
            position: fixed;
            width: 100%;
            height: 100%;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            padding-top: 10vh;
            box-sizing: border-box;
        }

        .mfp-close-btn-in .mfp-close {
            color: var(--text-dark);
        }

        /* Error Messages */
        .text-danger.animate__animated {
            font-size: 14px;
            margin-top: 5px;
        }

        /* Links */
        a {
            color: var(--orange);
            transition: color 0.3s;
        }

        a:hover {
            color: var(--dark-orange);
            text-decoration: none;
        }

        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .mfp-content {
                padding: 20px;
                width: 90% !important;
                max-width: 350px;
            }

            .headding {
                font-size: 20px;
            }

            .form-control,
            .input-group-text {
                padding: 0.5rem !important;
                height: calc(3.1rem + 1px);
            }
        }

        /* Accessibility Improvements */
        .btn:focus,
        .form-control:focus,
        .nav-link:focus {
            outline: 0px solid rgba(219, 89, 25, 0.4);
            outline-offset: 2px;
        }

        /* Toggle Password Icon Styles */
        .input-group-append span:hover {
            cursor: pointer;
            color: var(--orange);
        }

        /* Status Messages */
        #panelStatusSignUp {
            min-height: 20px;
        }

        /* Form Container Styling */
        .tab-content {
            background-color: var(--light-cream);
            border-radius: 0 0 8px 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 30px;
        }

        /* Input Group Enhancements */
        .input-group {
            margin-bottom: 15px;
        }

        .input-group-prepend .input-group-text,
        .input-group-append .input-group-text {
            background-color: var(--gray);
            border: 1px solid var(--dark-cream);
        }

        /* Animation for Submit Buttons */
        .btn-success:active {
            transform: scale(0.98);
        }

        /* Focus Styles for Better Visibility */
        .form-control:focus {
            border-color: var(--light-orange);
            box-shadow: 0 0 0 0.2rem rgba(244, 122, 67, 0.25);
        }

        .SignIn_container {
            flex: 1;
            display: flex;
            flex-direction: column;
            /* justify-content: center; */
            align-items: center;
            gap: 30px;
        }

        .google-signin-wrapper {
            width: 100%;
            display: flex;
            justify-content: center;
        }

        .google-signin-wrapper>div {
            width: 100% !important;
            display: flex;
            justify-content: center;
        }
    </style>
    <!-- Add the script to handle the login and store cookies -->
    <!-- dont touch touch this this is app code -->

    <script>
        // Get the login form
        const form = document.getElementById('login-form');

        // Add event listener for form submission
        form.addEventListener('submit', function(e) {
            e.preventDefault(); // Prevent default form submission

            // Get email and password values from the form
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;

            // Send login request to the server
            fetch('/api/login', {
                method: 'POST',
                body: new URLSearchParams({
                    email,
                    password
                }) // Send email and password to the server
            }).then(response => {
                if (response.ok) { // If login is successful
                    setTimeout(() => {
                        // Store the session cookie in localStorage
                        const cookies = document.cookie;
                        console.log("Storing cookies after login:", cookies);
                        localStorage.setItem("session_cookie", cookies);

                        // Optionally redirect to another page after login (e.g., stream.php)
                        window.location.href = '/stream.php'; // Redirect to stream.php or any other page
                    }, 2000); // Set a delay to ensure cookie is stored properly
                } else {
                    // Handle error (e.g., incorrect credentials)
                    alert('Login failed. Please check your credentials.');
                }
            });
        });
    </script>
</head>

<body style="height: 100vh;">

    <section class="my-5 SignIn_container" style="flex: 1;">
        <div class="container text-center pt-2">
            <a href="/"><img src="inc/img/logo.black.png" alt="News Junction" style="width:200px"></a><br>
            <div class="headding" style="font-size:24px">Create &bull; Reach &bull; Measure</div>
        </div>
        <div class="container text-center" style="margin-bottom: 100px;">
            <div class="col-12 offset-md-2 col-md-8 offset-lg-3 col-lg-6">

                <ul class="nav nav-tabs">
                    <li class="nav-item">
                        <a id="tabSignup" class="nav-link active" data-toggle="tab" href="#signup">
                            <h5>Sign up</h5>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a id="tabLogin" class="nav-link" data-toggle="tab" href="#login">
                            <h5>Login</h5>
                        </a>
                    </li>
                </ul>

                <div class="tab-content">

                    <div id="signup" class="tab-pane fade show active">
                        <div id="g_id_onload"
                            data-client_id="856604026767-kef0n01ho13g631j7ljf1fvdsuvfsdlo.apps.googleusercontent.com"
                            data-callback="handleCredentialResponse"
                            data-auto_prompt="false">
                        </div>

                        <div class="google-signin-wrapper">
                            <div class="g_id_signin"
                                data-type="standard"
                                data-size="large"
                                data-theme="outline"
                                data-text="sign_in_with"
                                data-shape="circle">
                            </div>
                        </div>

                        <hr style="border: none; height: 1px; background-color: #ccc; margin: 20px 0;">

                        <form id="frmSignUp">
                            <div id="panelSignUp" class="my-4">
                                <div class="row px-3">
                                    <div class="input-group mb-3">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text p-3">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24">
                                                    <path fill="currentColor" d="M12 4a4 4 0 0 1 4 4a4 4 0 0 1-4 4a4 4 0 0 1-4-4a4 4 0 0 1 4-4m0 10c4.42 0 8 1.79 8 4v2H4v-2c0-2.21 3.58-4 8-4" />
                                                </svg>
                                            </span>
                                        </div>
                                        <input type="text" id="signFullName" name="signFullName"
                                            class="form-control p-4" placeholder="Full Name" required maxlength="100" />
                                    </div>


                                    <div class="input-group mb-3">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text p-3">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24">
                                                    <path fill="currentColor" d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2m0 4l-8 5l-8-5V6l8 5l8-5z" />
                                                </svg>
                                            </span>
                                        </div>
                                        <input type="email" id="signEmail" name="signEmail" class="form-control p-4"
                                            placeholder="Email Address" onkeyup="chkEmailSignUp()" required maxlength="100" />
                                    </div>
                                    <div class="input-group mb-3">
                                        <!-- Icon prepend -->
                                        <div class="input-group-prepend">
                                            <span class="input-group-text p-3">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24">
                                                    <path fill="currentColor" d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24c1.12.37 2.33.57 3.57.57c.55 0 1 .45 1 1V20c0 .55-.45 1-1 1c-9.39 0-17-7.61-17-17c0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1c0 1.25.2 2.45.57 3.57c.11.35.03.74-.25 1.02z" />
                                                </svg>
                                            </span>
                                        </div>

                                        <!-- Country code dropdown -->
                                        <div class="input-group-prepend">
                                            <select class="form-control " style="height: calc(3.1rem + 2px);width:100px;" id="countryCode" name="countryCode" required>
                                                <option value="+91" <?= ($countryCode == '+91') ? 'selected' : '' ?>>🇮🇳 +91 (India)</option>
                                                <option value="+1" <?= ($countryCode == '+1') ? 'selected' : '' ?>>🇺🇸 +1 (USA)</option>
                                                <option value="+44" <?= ($countryCode == '+44') ? 'selected' : '' ?>>🇬🇧 +44 (UK)</option>
                                                <option value="+61" <?= ($countryCode == '+61') ? 'selected' : '' ?>>🇦🇺 +61 (Australia)</option>
                                                <option value="+81" <?= ($countryCode == '+81') ? 'selected' : '' ?>>🇯🇵 +81 (Japan)</option>
                                                <option value="+49" <?= ($countryCode == '+49') ? 'selected' : '' ?>>🇩🇪 +49 (Germany)</option>
                                                <option value="+33" <?= ($countryCode == '+33') ? 'selected' : '' ?>>🇫🇷 +33 (France)</option>
                                                <option value="+971" <?= ($countryCode == '+971') ? 'selected' : '' ?>>🇦🇪 +971 (UAE)</option>
                                                <option value="+63" <?= ($countryCode == '+63') ? 'selected' : '' ?>>🇵🇭 +63 (Philippines)</option>
                                                <option value="+234" <?= ($countryCode == '+234') ? 'selected' : '' ?>>🇳🇬 +234 (Nigeria)</option>
                                            </select>
                                        </div>

                                        <!-- Phone number input -->
                                        <input type="text" id="userPhone" name="userPhone"
                                            class="form-control p-4" placeholder="Phone No" required maxlength="15" />
                                    </div>

                                    <div class="input-group mb-3">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text p-3">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24">
                                                    <path fill="currentColor" d="M18 10H9V7c0-1.654 1.346-3 3-3s3 1.346 3 3h2c0-2.757-2.243-5-5-5S7 4.243 7 7v3H6a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8a2 2 0 0 0-2-2m-7.939 5.499A2.002 2.002 0 0 1 14 16a1.99 1.99 0 0 1-1 1.723V20h-2v-2.277a1.99 1.99 0 0 1-.939-2.224" />
                                                </svg>
                                            </span>
                                        </div>
                                        <input type="password" id="signPwd1" name="signPwd1" class="form-control p-4" placeholder="Password" required maxlength="20" />
                                        <div class="input-group-append">
                                            <span class="input-group-text p-3" id="togglePasswordVisibility">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24">
                                                    <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m3.282 21.782l4.278-4.278M21.782 3.282L17.673 7.39m-3.363 3.363a2.64 2.64 0 0 0-1.063-1.063a2.625 2.625 0 1 0-2.494 4.62m3.557-3.557l-3.557 3.557m3.557-3.557l3.363-3.363m-6.92 6.92L7.56 17.504M17.673 7.39c-.38-.319-.791-.621-1.232-.894C15.2 5.726 13.717 5.19 12 5.19c-4.956 0-7.948 4.459-8.91 6.16c-.11.196-.165.293-.197.446a1.2 1.2 0 0 0 0 .408c.032.152.088.25.198.445c.51.903 1.593 2.582 3.237 3.96c.38.319.791.621 1.232.895m12.18-7.925c.528.694.919 1.328 1.17 1.773c.11.194.165.292.197.444c.023.112.023.296 0 .408c-.032.152-.087.25-.197.444c-.96 1.702-3.95 6.162-8.91 6.162q-.714-.002-1.374-.117" />
                                                </svg>
                                            </span>
                                        </div>
                                    </div>

                                    <div class="input-group mb-3">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text p-3">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24">
                                                    <path fill="currentColor" d="M18 10H9V7c0-1.654 1.346-3 3-3s3 1.346 3 3h2c0-2.757-2.243-5-5-5S7 4.243 7 7v3H6a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8a2 2 0 0 0-2-2m-7.939 5.499A2.002 2.002 0 0 1 14 16a1.99 1.99 0 0 1-1 1.723V20h-2v-2.277a1.99 1.99 0 0 1-.939-2.224" />
                                                </svg>
                                            </span>
                                        </div>
                                        <input type="password" id="signPwd2" name="signPwd2" class="form-control p-4"
                                            placeholder="Confirm Password" required maxlength="20" />
                                        <div class="input-group-append">
                                            <span class="input-group-text p-3" id="togglePassword">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24">
                                                    <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m3.282 21.782l4.278-4.278M21.782 3.282L17.673 7.39m-3.363 3.363a2.64 2.64 0 0 0-1.063-1.063a2.625 2.625 0 1 0-2.494 4.62m3.557-3.557l-3.557 3.557m3.557-3.557l3.363-3.363m-6.92 6.92L7.56 17.504M17.673 7.39c-.38-.319-.791-.621-1.232-.894C15.2 5.726 13.717 5.19 12 5.19c-4.956 0-7.948 4.459-8.91 6.16c-.11.196-.165.293-.197.446a1.2 1.2 0 0 0 0 .408c.032.152.088.25.198.445c.51.903 1.593 2.582 3.237 3.96c.38.319.791.621 1.232.895m12.18-7.925c.528.694.919 1.328 1.17 1.773c.11.194.165.292.197.444c.023.112.023.296 0 .408c-.032.152-.087.25-.197.444c-.96 1.702-3.95 6.162-8.91 6.162q-.714-.002-1.374-.117" />
                                                </svg>
                                            </span>
                                        </div>
                                    </div>
                                    <?
                                    $userAgent = $_SERVER['HTTP_USER_AGENT'];
                                    // Check if the request comes from an Android WebView
                                    if (strpos($userAgent, 'wv') !== false || strpos($userAgent, 'WebView') !== false || (isset($_SERVER['HTTP_X_APP_SOURCE']) && $_SERVER['HTTP_X_APP_SOURCE'] == 'AndroidWebView')) { ?>
                                        <div class="form-check mb-2 ml-1">
                                            <input type="checkbox" class="form-check-input" id="hasInviteCode" onclick="toggleInviteCodeField()">
                                            <label class="form-check-label" for="hasInviteCode">I have an invitation code</label>
                                        </div>
                                    <? } ?>

                                    <!-- Replace the current inviteCodeGroup div with this hidden input -->
                                    <input type="hidden" id="inviteCode" name="inviteCode" />

                                </div>
                                <div class="row">
                                    <div class="col-12 text-left">
                                        <input type="hidden" id="act" name="act" value="createAccount" />
                                        <!-- Keep the original onclick handler -->
                                        <button class="btn btn-success btn-md px-4" onclick="return chkSignUp()">Sign
                                            up</button>
                                        <div id="panelStatusSignUp" class="float-right text-sm" style="margin-top:5px"
                                            align="right"></div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>



                    <div id="login" class="tab-pane fade">

                        <div id="g_id_onload"
                            data-client_id="856604026767-kef0n01ho13g631j7ljf1fvdsuvfsdlo.apps.googleusercontent.com"
                            data-callback="handleCredentialResponse"
                            data-auto_prompt="false">
                        </div>

                        <div class="google-signin-wrapper">
                            <div class="g_id_signin"
                                data-type="standard"
                                data-size="large"
                                data-theme="outline"
                                data-text="sign_in_with"
                                data-shape="circle">
                            </div>
                        </div>

                        <hr style="border: none; height: 1px; background-color: #ccc; margin: 20px 0;">

                        <form id="frmLogin">
                            <div class="my-4">
                                <div class="row px-3">
                                    <div class="input-group mb-3">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text p-3">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24">
                                                    <path fill="currentColor" d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2m0 4l-8 5l-8-5V6l8 5l8-5z" />
                                                </svg>
                                            </span>
                                        </div>
                                        <input type="text" id="loginEmail" name="loginEmail"
                                            class="form-control p-4" placeholder="Enter Email" required maxlength="100" />
                                    </div>



                                    <div class="input-group mb-3">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text p-3">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24">
                                                    <path fill="currentColor" d="M18 10H9V7c0-1.654 1.346-3 3-3s3 1.346 3 3h2c0-2.757-2.243-5-5-5S7 4.243 7 7v3H6a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8a2 2 0 0 0-2-2m-7.939 5.499A2.002 2.002 0 0 1 14 16a1.99 1.99 0 0 1-1 1.723V20h-2v-2.277a1.99 1.99 0 0 1-.939-2.224" />
                                                </svg>
                                            </span>
                                        </div>
                                        <input type="password" id="loginPwd" name="loginPwd" class="form-control p-4" placeholder="Enter Password" required maxlength="20" />
                                        <div class="input-group-append">
                                            <span class="input-group-text p-3" onclick="togglePasswordVisibility()">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24">
                                                    <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m3.282 21.782l4.278-4.278M21.782 3.282L17.673 7.39m-3.363 3.363a2.64 2.64 0 0 0-1.063-1.063a2.625 2.625 0 1 0-2.494 4.62m3.557-3.557l-3.557 3.557m3.557-3.557l3.363-3.363m-6.92 6.92L7.56 17.504M17.673 7.39c-.38-.319-.791-.621-1.232-.894C15.2 5.726 13.717 5.19 12 5.19c-4.956 0-7.948 4.459-8.91 6.16c-.11.196-.165.293-.197.446a1.2 1.2 0 0 0 0 .408c.032.152.088.25.198.445c.51.903 1.593 2.582 3.237 3.96c.38.319.791.621 1.232.895m12.18-7.925c.528.694.919 1.328 1.17 1.773c.11.194.165.292.197.444c.023.112.023.296 0 .408c-.032.152-.087.25-.197.444c-.96 1.702-3.95 6.162-8.91 6.162q-.714-.002-1.374-.117" />
                                                </svg>
                                            </span>
                                        </div>
                                    </div>

                                </div>

                                <div class="row">
                                    <div class="col-6 text-left">
                                        <button class="btn btn-success btn-md px-4" onclick="return chkLogin()">Log in</button>
                                    </div>
                                    <div class="col-6 text-right pt-2">
                                        <a href="#" id="linkForogtpassword">Forgot password?</a>
                                    </div>
                                </div>
                            </div>
                        </form>



                    </div>

                </div>
            </div>
        </div>
    </section>
    <!-- Add this modal HTML at the bottom of the body tag, before any scripts -->
    <div class="modal fade" id="inviteCodeModal" tabindex="-1" role="dialog" aria-labelledby="inviteCodeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <!-- <div class="modal-header">
                    <h5 class="modal-title" id="inviteCodeModalLabel">Enter Invitation Code</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div> -->
                <div class="modal-body">
                    <div class="">
                        <label style="color:black;" for="modalInviteCode">Please enter your invitation code:</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24">
                                        <path fill="currentColor" d="M20 4H4c-1.11 0-2 .89-2 2v12c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2m-2 9h-3v3h-2v-3h-3v-2h3V8h2v3h3z" />
                                    </svg>
                                </span>
                            </div>
                            <input type="text" id="modalInviteCode" class="form-control" placeholder="Enter Invitation Code" maxlength="50" />
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" id="applyInviteCode">Apply Code</button>
                </div>
            </div>
        </div>
    </div>
    <?php include 'inc/php/indexFooter.php'; ?>


    <script type="text/javascript">
        var chkFilterEmail = /\w{1,}[@][\w\-]{1,}([.]([\w\-]{1,})){1,3}$/;
        $(function() {
            $('#linkForogtpassword').magnificPopup({
                type: 'ajax',
                closeBtnInside: true,
                ajax: {
                    settings: {
                        method: 'POST',
                        data: {
                            act: 'showForgotPassword'
                        },
                        url: 'process/signInProcess.php'
                    }
                }
            });
        });

        function chkLogin() {
            var signedIn = $('input[name=signedIn]:checked').val();
            var loginEmail = $('#loginEmail').val();
            var loginPwd = $('#loginPwd').val();

            //  bhuvanesh code
            var url = window.location.href;

            var urlObj = new URL(url);
            var parameters = urlObj.searchParams;

            var article_Id = parameters.get('article_Id');

            // var parameters = $.param.querystring(queryString);
            // var article_Id = parameters.article_Id !== undefined ? parameters.article_Id : null;

            if (loginEmail == '' || loginPwd == '') {
                alert('Both Login and Password are required!');
                return false;
            }
            if (loginEmail != '') {
                if (!(chkFilterEmail.test(loginEmail))) {
                    alert('Your Email is not valid!');
                    return false;
                }
            }
            $.ajax({
                    url: 'process/logInCheck.php',
                    method: 'POST',
                    data: {
                        permanentLogin: signedIn,
                        email: loginEmail,
                        pwd: loginPwd
                    },
                })
                .done(function(res) {
                    if (res == 'notActivated') {
                        alert('ERROR: Account is not activated!');
                    } else if (res != '') {
                        if (res.substring(0, 2) == 'OK') {

                            // bhuvanesh code
                            if (article_Id != null) {
                                window.location = '/article.php?article_id=' + article_Id;
                            } else {
                                window.location = 'stream.php';
                            }

                        } else {
                            alert('ERROR: User or Password is incorrect!');
                        }
                    } else {
                        alert('ERROR: User or Password is incorrect!');
                    }
                });
            return false;
        }

        function chkSignUp() {
            var signFullName = $('#signFullName').val();
            var signEmail = $('#signEmail').val();
            var userPhone = $('#userPhone').val();
            var countryCode = $('#countryCode').val();
            var signPwd1 = $('#signPwd1').val();
            var signPwd2 = $('#signPwd2').val();
            var inviteCode = $('#inviteCode').val();
            var signBusinessType = $('#signBusinessType').val();

            if (signFullName == '') {
                $('#panelStatusSignUp').html('<div class="text-danger animate__animated animate__flash">Error: Full Name not entered!</div>');
                return false;
            }

            if (signEmail == '') {
                $('#panelStatusSignUp').html('<div class="text-danger animate__animated animate__flash">Error: Email not entered!</div>');
                return false;
            }

            if (!chkFilterEmail.test(signEmail)) {
                $('#panelStatusSignUp').html('<div class="text-danger animate__animated animate__flash">Error: Email is not valid!</div>');
                return false;
            }

            if (signPwd1 == '') {
                $('#panelStatusSignUp').html('<div class="text-danger animate__animated animate__flash">Error: Password is not entered!</div>');
                return false;
            }

            if (signPwd1 != signPwd2) {
                $('#panelStatusSignUp').html('<div class="text-danger animate__animated animate__flash">Error: Passwords do not match!</div>');
                return false;
            }

            if (signBusinessType == '') {
                $('#panelStatusSignUp').html('<div class="text-danger animate__animated animate__flash">Error: Business Type is not selected!</div>');
                return false;
            }

            // Check if email already exists
            $.ajax({
                method: 'POST',
                url: 'process/signInProcess.php',
                data: {
                    act: 'chkExist',
                    signEmail: signEmail
                }
            }).done(function(response) {
                if (response === 'OK') {
                    $('#contentLoader').show();

                    // Serialize form and add countryCode manually
                    var formData = $('#frmSignUp').serializeArray();
                    formData.push({
                        name: 'countryCode',
                        value: countryCode
                    });

                    $.ajax({
                        url: 'process/signInProcess.php',
                        method: 'POST',
                        data: formData,
                        dataType: 'json'
                    }).done(function(res) {
                        $('#contentLoader').hide();

                        if (res.status === 'OK') {
                            $('#panelSignUp').html(
                                'Please check your email for further instructions<br>on how to activate your account!<br>'
                            );
                            $('#panelSignUp').append(
                                '<button id="btnOkAfterMessage" class="btn btn-primary mt-2">OK</button>'
                            );

                            if (inviteCode != "") {
                                processReferral(res.userId, inviteCode);
                            }
                        } else {
                            $('#panelStatusSignUp').html(
                                '<div class="text-danger animate__animated animate__flash">Error: ' + (res.message || 'Account could not be created!') + '</div><br>'
                            );
                        }
                    });
                } else {
                    $('#panelStatusSignUp').html('<div class="text-danger animate__animated animate__flash">Error: This email address already exists!</div><br>');
                }
            });

            return false;
        }


        function chkEmailSignUp() {
            $('#panelStatusSignUp').html('');
            var signEmail = $('#signEmail').val();
            if (signEmail != '') {
                if (chkFilterEmail.test(signEmail)) {
                    $.ajax({
                            method: 'POST',
                            url: 'process/signInProcess.php',
                            data: {
                                act: 'chkExist',
                                signEmail: signEmail
                            }
                        })
                        .done(function(response) {
                            if (response != 'OK') {
                                $('#panelStatusSignUp').html('<div class="text-danger animate__animated animate__flash">Error: This email address already exists!</div>');
                            }
                        });
                }
            }
        }

        function getBusinessType() {
            $.ajax({
                    url: 'process/signInProcess.php',
                    method: 'POST',
                    data: {
                        act: 'getBusinessType'
                    }
                })
                .done(function(res) {
                    $("#signBusinessType").append(new Option('Select business type', ''));
                    var returnArr = JSON.parse(res);
                    $.each(returnArr, function(index, value) {
                        $("#signBusinessType").append(new Option(value[1], value[0]));
                    });
                    $("#signBusinessType").append(new Option('Other', '0'));
                });
        }

        function chkResetPassword() {
            var forgotLogin = $('#forgotLogin').val();
            if (forgotLogin == '') {
                $('#panelStatus').html('<div class="text-danger animate__animated animate__flash">Error: Login not entered!</div>');
                return false;
            }
            if (forgotLogin != '') {
                if (!(chkFilterEmail.test(forgotLogin))) {
                    $('#panelStatus').html('<div class="text-danger animate__animated animate__flash">Error: Login Email is not valid!</div>');
                    return false;
                }
            }
            $.ajax({
                    url: 'process/signInProcess.php',
                    method: 'POST',
                    data: {
                        act: 'resetPassword',
                        email: forgotLogin
                    }
                })
                .done(function(res) {
                    $('#widget_B').html('<div class="my-3">If you have an account with us, password reset instructions have been sent to your registered email address.</div>');
                    $('#widget_F').hide();
                });
            return false;
        }

        function getParam(param) {
            return new URLSearchParams(window.location.search).get(param);
        }

        function togglePasswordVisibility() {
            var $passwordField = $("#loginPwd");
            var currentType = $passwordField.attr("type");
            $passwordField.attr("type", currentType === "password" ? "text" : "password");
        }
        $(document).ready(function() {
            // Add click event listener to the toggle icons
            $('#togglePasswordVisibility, #togglePassword').on('click', function() {
                var passwordField1 = $('#signPwd1');
                var passwordField2 = $('#signPwd2');
                var svgIcon = $(this).find('svg');

                // Toggle the visibility of both password fields
                if (passwordField1.attr('type') === 'password') {
                    passwordField1.attr('type', 'text'); // Show first password
                    passwordField2.attr('type', 'text'); // Show second password
                    svgIcon.css('fill', 'currentColor'); // Modify icon when passwords are visible
                } else {
                    passwordField1.attr('type', 'password'); // Hide first password
                    passwordField2.attr('type', 'password'); // Hide second password
                    svgIcon.css('fill', 'none'); // Reset icon when passwords are hidden
                }
            });
        });

        function toggleInviteCodeField() {
            var checkbox = document.getElementById('hasInviteCode');

            if (checkbox.checked) {
                $('#inviteCodeModal').modal('show');
            } else {
                // Clear the hidden input field when unchecking
                document.getElementById('inviteCode').value = '';
            }
        }

        function processReferral(newUserId, inviteCode) {
            var referralData = {
                newUserId: newUserId,
                inviteCode: inviteCode
            };

            $.ajax({
                url: 'referralProcess.php',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify(referralData),
                success: function(response) {
                    try {
                        var parsedResponse = JSON.parse(response);

                        // Only alert the user if the referral was processed successfully
                        if (parsedResponse.success) {
                            alert("Invitation code has been successfully applied and Get your invitaion code in account tab.");
                        } else {
                            console.warn("Referral not processed:", parsedResponse.message);
                        }

                        console.log('Referral processing result:', parsedResponse);
                    } catch (e) {
                        console.error('Failed to parse response:', response);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error processing referral:', error);
                }
            });

        }
        // Replace the toggleInviteCodeField function with this code that opens the modal
        function toggleInviteCodeField() {
            var checkbox = document.getElementById('hasInviteCode');

            if (checkbox.checked) {
                $('#inviteCodeModal').modal('show');
            } else {
                // Clear the hidden input field when unchecking
                document.getElementById('inviteCode').value = '';
            }
        }

        // Add this code to the document ready section or in a script tag at the bottom
        $(document).ready(function() {
            // Handle applying the invitation code from the modal
            $('#applyInviteCode').on('click', function() {
                var codeFromModal = $('#modalInviteCode').val().trim();

                if (codeFromModal) {
                    // Update the hidden field with the code from the modal
                    $('#inviteCode').val(codeFromModal);

                    // Keep the checkbox checked
                    $('#hasInviteCode').prop('checked', true);

                    // Show a confirmation message
                    var confirmationMessage = $('<div class="text-success ml-2 mt-1 animate__animated animate__fadeIn">Invitation code applied!</div>');
                    $('#hasInviteCode').parent().append(confirmationMessage);

                    // Remove the confirmation message after a few seconds
                    setTimeout(function() {
                        confirmationMessage.remove();
                    }, 3000);

                    // Close the modal
                    $('#inviteCodeModal').modal('hide');
                } else {
                    // Show an error if the code is empty
                    alert('Please enter a valid invitation code');
                }
            });

            // When the modal is closed without applying a code, uncheck the checkbox
            $('#inviteCodeModal').on('hidden.bs.modal', function() {
                if ($('#inviteCode').val().trim() === '') {
                    $('#hasInviteCode').prop('checked', false);
                }
            });
        });
    </script>
    <script>
        function handleCredentialResponse(response) {
            const idToken = response.credential;
            fetch('process/google-callback.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'id_token=' + idToken
                })
                .then(response => response.text())
                .then(data => {
                    if (data.startsWith('OK|')) {
                        window.location.href = 'stream.php';
                    } else if (data === 'new_user') {
                        window.location.href = 'create-account.php';
                    } else {
                        alert('Login failed: ' + data);
                    }
                });
        }
    </script>
</body>

</html>