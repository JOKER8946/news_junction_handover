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

    <!-- Google Identity Services for "Continue with Google". The library
         renders the official button and calls window.handleGoogleCredential
         with the signed JWT after a successful sign-in. -->
    <script src="https://accounts.google.com/gsi/client" async defer></script>
    <?php
      // Load Google client_id from nj_cream.platform_settings (set via the
      // Manikya Market super-admin → Platform Settings → News Junction · Google
      // Sign-In). Falls back to the file config if the DB has no override.
      require_once __DIR__ . '/inc/php/db_config.php';
      require_once __DIR__ . '/inc/php/platform_settings.php';
      $googleCfg = nj_google_oauth_config();
    ?>
    <meta name="google-signin-client_id" content="<?= htmlspecialchars((string)($googleCfg['client_id'] ?? ''), ENT_QUOTES) ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.1/aos.css">
    <link rel="stylesheet" href="inc/css/cream.css">
    <link rel="icon" type="image/x-icon" href="../grfx/img/nj_logo.png">

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
            var ref = getParam('ref');
            if (wchTab == 'login') {
                $('#tabSignup').removeClass('active');
                $('#tabLogin').addClass('active');
                $('#signup').removeClass('show').removeClass('active');
                $('#login').addClass('show').addClass('active');
            }
            if (ref == 'reporter') {
                // Show reporter registration form, hide normal tabs
                $('#normalAuthTabs').hide();
                $('#normalTabContent').hide();
                $('#reporterSignupSection').show();
            }
            getBusinessType();
        });
    </script>
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

        /* ============================================================
           World-class redesign (appended → overrides the rules above)
           CSS-only; markup, IDs and JS are untouched.
           ============================================================ */
        body {
            height: auto !important;
            min-height: 100vh;
            background:
                radial-gradient(1100px 520px at 50% -8%, rgba(244,122,67,.20) 0%, rgba(244,122,67,0) 60%),
                radial-gradient(900px 500px at 108% 8%, rgba(219,89,25,.12) 0%, rgba(219,89,25,0) 55%),
                linear-gradient(165deg, #fffdf7 0%, #f8f1e0 100%) !important;
        }

        .SignIn_container {
            justify-content: center;
            gap: 20px;
            padding: 24px 0 40px;
        }

        /* Logo */
        img[alt="News Junction"] {
            width: 128px !important;
            filter: drop-shadow(0 8px 18px rgba(31, 26, 15, .18));
            transition: transform .3s ease;
        }
        img[alt="News Junction"]:hover { transform: translateY(-2px) scale(1.02); }

        /* Pill tab toggle */
        .nav-tabs {
            border: none;
            display: inline-flex;
            gap: 4px;
            padding: 6px;
            margin: 0 auto 20px;
            background: #ffffff;
            border-radius: 999px;
            box-shadow: 0 6px 18px rgba(31, 26, 15, .08), inset 0 0 0 1px #f1e9d7;
        }
        .nav-tabs .nav-item { margin: 0; }
        .nav-link,
        .nav-link.active#tabSignup,
        .nav-link.active#tabLogin {
            border: none !important;
            border-radius: 999px !important;
            padding: 9px 30px !important;
            color: #8a7c66 !important;
            transition: all .25s ease;
        }
        .nav-link h5 { margin: 0; font-size: .98rem; font-weight: 700; letter-spacing: .01em; }
        .nav-link:hover { color: var(--orange) !important; }
        .nav-link.active,
        .nav-link.active#tabSignup,
        .nav-link.active#tabLogin {
            background: linear-gradient(135deg, var(--orange) 0%, var(--light-orange) 100%) !important;
            color: #ffffff !important;
            box-shadow: 0 8px 18px rgba(219, 89, 25, .32);
        }

        /* Auth card */
        .tab-content {
            background: #ffffff;
            border: 1px solid #f2ead8;
            border-radius: 20px;
            padding: 34px 30px 30px;
            box-shadow: 0 24px 60px rgba(31, 26, 15, .12), 0 3px 8px rgba(31, 26, 15, .05);
            animation: njFadeUp .55s cubic-bezier(.2, .7, .2, 1) both;
        }
        @keyframes njFadeUp {
            from { opacity: 0; transform: translateY(16px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* Reporter card matches the same look */
        #reporterSignupSection > div {
            border-radius: 20px !important;
            border: 1px solid #f2ead8;
            box-shadow: 0 24px 60px rgba(31, 26, 15, .12) !important;
            padding: 30px !important;
        }

        /* Inputs */
        .input-group { margin-bottom: 16px; }
        .form-control,
        .input-group-text {
            height: calc(3.25rem + 2px);
            border-color: #eae2cf;
        }
        .form-control {
            border-radius: 12px;
            font-size: 15px;
            color: var(--text-dark);
        }
        .input-group-text { border-radius: 12px; }
        .input-group-prepend .input-group-text,
        .input-group-append .input-group-text {
            background: #faf6ec;
            border-color: #eae2cf;
            color: #c06a34;
        }
        .form-control::placeholder { color: #b3a892; opacity: 1; }
        .form-control:focus {
            border-color: var(--light-orange);
            box-shadow: 0 0 0 .22rem rgba(244, 122, 67, .18);
        }
        /* Highlight the whole group on focus for a premium feel */
        .input-group:focus-within {
            border-radius: 13px;
            box-shadow: 0 0 0 3px rgba(244, 122, 67, .16);
        }
        textarea.form-control { height: auto; border-radius: 12px; }

        /* Submit buttons — full width, gradient, prominent */
        .tab-content .btn-success,
        #reporterSignupSection .btn-success {
            display: block;
            width: 100%;
            margin-top: 6px;
            padding: 13px 22px;
            border: none;
            border-radius: 12px;
            font-weight: 700;
            font-size: 16px;
            letter-spacing: .01em;
            background: linear-gradient(135deg, var(--orange) 0%, var(--light-orange) 100%);
            box-shadow: 0 12px 24px rgba(219, 89, 25, .28);
        }
        .tab-content .btn-success:hover,
        #reporterSignupSection .btn-success:hover {
            background: linear-gradient(135deg, var(--dark-orange) 0%, var(--orange) 100%);
            transform: translateY(-2px);
            box-shadow: 0 16px 30px rgba(219, 89, 25, .36);
        }

        /* Google button + divider */
        .g_id_signin { display: inline-block; }
        .text-muted.small.mt-2 {
            position: relative;
            display: flex; align-items: center; gap: 12px;
            justify-content: center;
            color: #a99c85 !important;
            margin: 14px 0 4px !important;
            font-size: 12.5px;
            text-transform: none;
        }

        /* Footer polish */
        footer, .footer { color: #8a7c66; }

        @media (max-width: 575px) {
            .tab-content { padding: 24px 18px; border-radius: 16px; }
            .nav-link { padding: 8px 22px !important; }
            img[alt="News Junction"] { width: 108px !important; }
        }

        /* ============================================================
           Split-screen layout: branded left panel + form on the right.
           ============================================================ */
        .SignIn_container.nj-split {
            flex-direction: row;
            align-items: stretch;
            justify-content: flex-start;
            gap: 0;
            padding: 0;
            min-height: 100vh;
            width: 100%;
        }

        /* LEFT — brand panel */
        .nj-brand {
            flex: 0 0 46%;
            max-width: 46%;
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 56px;
            color: #fff;
            background:
                radial-gradient(700px 500px at 12% 12%, rgba(255,255,255,.16) 0%, rgba(255,255,255,0) 55%),
                radial-gradient(600px 600px at 100% 100%, rgba(0,0,0,.18) 0%, rgba(0,0,0,0) 60%),
                linear-gradient(150deg, var(--dark-orange) 0%, var(--orange) 52%, var(--light-orange) 100%);
        }
        /* soft decorative rings */
        .nj-brand::before,
        .nj-brand::after {
            content: "";
            position: absolute;
            border-radius: 50%;
            border: 1.5px solid rgba(255,255,255,.14);
        }
        .nj-brand::before { width: 420px; height: 420px; top: -120px; right: -120px; }
        .nj-brand::after  { width: 300px; height: 300px; bottom: -110px; left: -90px; border-color: rgba(255,255,255,.10); }

        .nj-brand-inner { position: relative; z-index: 1; max-width: 460px; }
        .nj-brand .nj-brand-logo {
            width: 150px !important;
            background: #fff;
            padding: 10px 14px;
            border-radius: 14px;
            box-shadow: 0 12px 30px rgba(0,0,0,.18);
            filter: none !important;
        }
        .nj-brand-title {
            font-size: 2.5rem;
            font-weight: 800;
            line-height: 1.12;
            margin: 28px 0 14px;
            color: #fff;
            letter-spacing: -.01em;
        }
        .nj-brand-sub {
            font-size: 1.02rem;
            line-height: 1.6;
            color: rgba(255,255,255,.92);
            margin-bottom: 26px;
        }
        .nj-brand-points { list-style: none; padding: 0; margin: 0; }
        .nj-brand-points li {
            display: flex; align-items: flex-start; gap: 12px;
            font-size: .98rem; color: rgba(255,255,255,.95);
            margin-bottom: 14px;
        }
        .nj-tick {
            flex: 0 0 24px; height: 24px; width: 24px;
            display: inline-flex; align-items: center; justify-content: center;
            background: rgba(255,255,255,.22); border-radius: 50%;
            font-size: .8rem; font-weight: 800; color: #fff;
        }

        /* RIGHT — auth panel */
        .nj-auth {
            flex: 1 1 54%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 44px 40px;
            overflow-y: auto;
        }
        .nj-auth-inner { margin-bottom: 0 !important; }
        .nj-mobile-logo { display: none; margin-bottom: 18px; }

        /* Stack to single column on tablets/phones */
        @media (max-width: 991px) {
            .SignIn_container.nj-split { flex-direction: column; min-height: 100vh; }
            .nj-brand { display: none; }
            .nj-auth { flex: 1 1 auto; padding: 28px 18px 40px; }
            .nj-mobile-logo { display: block; }
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

    <section class="SignIn_container nj-split">
        <!-- LEFT: brand panel (hidden on small screens) -->
        <aside class="nj-brand">
            <div class="nj-brand-inner">
                <a href="/"><img src="grfx/img/nj_logo.png" alt="News Junction" class="nj-brand-logo"></a>
                <h1 class="nj-brand-title">Your Voice,<br>Your Platform</h1>
                <p class="nj-brand-sub">Hyperlocal news, citizen journalism and community stories — straight from the ground, across the region.</p>
                <ul class="nj-brand-points">
                    <li><span class="nj-tick">✓</span> Real-time local coverage you can trust</li>
                    <li><span class="nj-tick">✓</span> Become a verified citizen reporter</li>
                    <li><span class="nj-tick">✓</span> Your community, your stories</li>
                </ul>
            </div>
        </aside>

        <!-- RIGHT: auth panel -->
        <div class="nj-auth">
            <div class="container text-center pt-2 nj-mobile-logo">
                <a href="/"><img src="grfx/img/nj_logo.png" alt="News Junction" style="width:120px"></a>
            </div>
            <div class="container text-center nj-auth-inner">
                <div class="col-12 col-lg-11 mx-auto px-0">

                <!-- Reporter Registration Section (hidden by default) -->
                <div id="reporterSignupSection" style="display:none;">
                    <div style="background-color: var(--light-cream); border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); padding: 20px; margin-bottom: 30px;">
                        <h5 style="color: var(--orange); margin-bottom: 5px;">Reporter Registration</h5>
                        <p style="font-size: 13px; color: #666; margin-bottom: 20px;">Register as a reporter. Your account will be reviewed and verified by our admin team.</p>
                        <form id="frmReporterSignUp">
                            <div id="panelReporterSignUp" class="my-4">
                                <div class="row px-3">
                                    <!-- Full Name -->
                                    <div class="input-group mb-3">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text p-3">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24">
                                                    <path fill="currentColor" d="M12 4a4 4 0 0 1 4 4a4 4 0 0 1-4 4a4 4 0 0 1-4-4a4 4 0 0 1 4-4m0 10c4.42 0 8 1.79 8 4v2H4v-2c0-2.21 3.58-4 8-4"/>
                                                </svg>
                                            </span>
                                        </div>
                                        <input type="text" id="reporterFullName" name="reporterFullName" class="form-control p-4" placeholder="Full Name" required maxlength="100" />
                                    </div>

                                    <!-- Email -->
                                    <div class="input-group mb-3">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text p-3">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24">
                                                    <path fill="currentColor" d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2m0 4l-8 5l-8-5V6l8 5l8-5z"/>
                                                </svg>
                                            </span>
                                        </div>
                                        <input type="email" id="reporterEmail" name="reporterEmail" class="form-control p-4" placeholder="Email Address" required maxlength="100" />
                                    </div>

                                    <!-- Phone -->
                                    <div class="input-group mb-3">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text p-3">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24">
                                                    <path fill="currentColor" d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24c1.12.37 2.33.57 3.57.57c.55 0 1 .45 1 1V20c0 .55-.45 1-1 1c-9.39 0-17-7.61-17-17c0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1c0 1.25.2 2.45.57 3.57c.11.35.03.74-.25 1.02z"/>
                                                </svg>
                                            </span>
                                        </div>
                                        <div class="input-group-prepend">
                                            <select class="form-control" style="height: calc(3.25rem + 2px);width:132px;min-width:132px;" id="reporterCountryCode" name="reporterCountryCode" required>
                                                <option value="+91" selected>+91 (India)</option>
                                                <option value="+1">+1 (USA)</option>
                                                <option value="+44">+44 (UK)</option>
                                                <option value="+61">+61 (Australia)</option>
                                                <option value="+971">+971 (UAE)</option>
                                            </select>
                                        </div>
                                        <input type="text" id="reporterPhone" name="reporterPhone" class="form-control p-4" placeholder="Phone No" required maxlength="15" />
                                    </div>

                                    <!-- Pincode -->
                                    <div class="input-group mb-3">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text p-3">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 640 640"><path d="M352 348.4C416.1 333.9 464 276.5 464 208C464 128.5 399.5 64 320 64C240.5 64 176 128.5 176 208C176 276.5 223.9 333.9 288 348.4L288 544C288 561.7 302.3 576 320 576C337.7 576 352 561.7 352 544L352 348.4zM328 160C297.1 160 272 185.1 272 216C272 229.3 261.3 240 248 240C234.7 240 224 229.3 224 216C224 158.6 270.6 112 328 112C341.3 112 352 122.7 352 136C352 149.3 341.3 160 328 160z"/></svg>
                                            </span>
                                        </div>
                                        <input type="text" id="reporterPincode" name="reporterPincode" class="form-control p-4" placeholder="Pincode" required maxlength="10" />
                                    </div>

                                    <!-- Password -->
                                    <div class="input-group mb-3">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text p-3">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24">
                                                    <path fill="currentColor" d="M18 10H9V7c0-1.654 1.346-3 3-3s3 1.346 3 3h2c0-2.757-2.243-5-5-5S7 4.243 7 7v3H6a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8a2 2 0 0 0-2-2m-7.939 5.499A2.002 2.002 0 0 1 14 16a1.99 1.99 0 0 1-1 1.723V20h-2v-2.277a1.99 1.99 0 0 1-.939-2.224"/>
                                                </svg>
                                            </span>
                                        </div>
                                        <input type="password" id="reporterPwd1" name="reporterPwd1" class="form-control p-4" placeholder="Password" required maxlength="20" />
                                    </div>

                                    <div class="input-group mb-3">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text p-3">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24">
                                                    <path fill="currentColor" d="M18 10H9V7c0-1.654 1.346-3 3-3s3 1.346 3 3h2c0-2.757-2.243-5-5-5S7 4.243 7 7v3H6a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8a2 2 0 0 0-2-2m-7.939 5.499A2.002 2.002 0 0 1 14 16a1.99 1.99 0 0 1-1 1.723V20h-2v-2.277a1.99 1.99 0 0 1-.939-2.224"/>
                                                </svg>
                                            </span>
                                        </div>
                                        <input type="password" id="reporterPwd2" name="reporterPwd2" class="form-control p-4" placeholder="Confirm Password" required maxlength="20" />
                                    </div>

                                    <!-- Reporter-specific fields -->
                                    <div class="input-group mb-3">
                                        <textarea id="reporterBio" name="reporterBio" class="form-control p-3" placeholder="Brief bio / About yourself" rows="3" maxlength="1000" style="border-color: var(--dark-cream);"></textarea>
                                    </div>

                                    <div class="input-group mb-3">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text p-3">Exp</span>
                                        </div>
                                        <input type="text" id="reporterExperience" name="reporterExperience" class="form-control p-4" placeholder="Experience (e.g., 3 years in journalism)" maxlength="255" />
                                    </div>

                                    <div class="input-group mb-3">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text p-3">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24">
                                                    <path fill="currentColor" d="M3.9 12c0-1.71 1.39-3.1 3.1-3.1h4V7H7c-2.76 0-5 2.24-5 5s2.24 5 5 5h4v-1.9H7c-1.71 0-3.1-1.39-3.1-3.1M8 13h8v-2H8zm9-6h-4v1.9h4c1.71 0 3.1 1.39 3.1 3.1s-1.39 3.1-3.1 3.1h-4V17h4c2.76 0 5-2.24 5-5s-2.24-5-5-5"/>
                                                </svg>
                                            </span>
                                        </div>
                                        <input type="url" id="reporterPortfolio" name="reporterPortfolio" class="form-control p-4" placeholder="Portfolio / Website URL (optional)" maxlength="500" />
                                    </div>

                                    <div class="input-group mb-3">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text p-3">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24">
                                                    <path fill="currentColor" d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7m0 9.5a2.5 2.5 0 0 1 0-5a2.5 2.5 0 0 1 0 5"/>
                                                </svg>
                                            </span>
                                        </div>
                                        <input type="text" id="reporterCoverage" name="reporterCoverage" class="form-control p-4" placeholder="Areas of coverage (e.g., Udupi, Mangalore)" maxlength="500" />
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-12 text-left">
                                        <button class="btn btn-success btn-md px-4" onclick="return chkReporterSignUp()">Register as Reporter</button>
                                        <div id="panelStatusReporter" class="float-right text-sm" style="margin-top:5px" align="right"></div>
                                    </div>
                                </div>
                            </div>
                        </form>
                        <div class="mt-3">
                            <a href="sign-in.php">Back to normal Sign In</a>
                        </div>
                    </div>
                </div>

                <div id="normalAuthTabs">
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
                </div>

                <div id="normalTabContent" class="tab-content">

                    <div id="signup" class="tab-pane fade show active">
                        <form id="frmSignUp">
                            <div id="panelSignUp" class="my-4">

                                <!-- Continue with Google — handled by GIS library; on success it
                                     calls window.handleGoogleCredential with the signed JWT,
                                     which we POST to /process/google-callback.php. -->
                                <div class="row px-3 mb-3">
                                  <div class="col-12 text-center">
                                    <div id="g_id_onload"
                                         data-client_id="<?= htmlspecialchars((string)($googleCfg['client_id'] ?? ''), ENT_QUOTES) ?>"
                                         data-callback="handleGoogleCredential"
                                         data-context="signup"
                                         data-ux_mode="popup"></div>
                                    <div class="g_id_signin"
                                         data-type="standard"
                                         data-shape="rectangular"
                                         data-theme="outline"
                                         data-text="continue_with"
                                         data-size="large"
                                         data-logo_alignment="left"
                                         data-width="320"></div>
                                    <div class="text-muted small mt-2">— or sign up with email —</div>
                                  </div>
                                </div>

                                <div class="row px-3">
                                    <div class="input-group mb-3">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text p-3">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em"
                                                    viewBox="0 0 24 24">
                                                    <path fill="currentColor"
                                                        d="M12 4a4 4 0 0 1 4 4a4 4 0 0 1-4 4a4 4 0 0 1-4-4a4 4 0 0 1 4-4m0 10c4.42 0 8 1.79 8 4v2H4v-2c0-2.21 3.58-4 8-4" />
                                                </svg>
                                            </span>
                                        </div>
                                        <input type="text" id="signFullName" name="signFullName"
                                            class="form-control p-4" placeholder="Full Name" required maxlength="100" />
                                    </div>


                                    <div class="input-group mb-3">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text p-3">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em"
                                                    viewBox="0 0 24 24">
                                                    <path fill="currentColor"
                                                        d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2m0 4l-8 5l-8-5V6l8 5l8-5z" />
                                                </svg>
                                            </span>
                                        </div>
                                        <input type="email" id="signEmail" name="signEmail" class="form-control p-4"
                                            placeholder="Email Address" onkeyup="chkEmailSignUp()" required
                                            maxlength="100" />
                                    </div>
                                    <div class="input-group mb-3">
                                        <!-- Icon prepend -->
                                        <div class="input-group-prepend">
                                            <span class="input-group-text p-3">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em"
                                                    viewBox="0 0 24 24">
                                                    <path fill="currentColor"
                                                        d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24c1.12.37 2.33.57 3.57.57c.55 0 1 .45 1 1V20c0 .55-.45 1-1 1c-9.39 0-17-7.61-17-17c0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1c0 1.25.2 2.45.57 3.57c.11.35.03.74-.25 1.02z" />
                                                </svg>
                                            </span>
                                        </div>

                                        <!-- Country code dropdown -->
                                        <div class="input-group-prepend">
                                            <select class="form-control "
                                                style="height: calc(3.25rem + 2px);width:132px;min-width:132px;" id="countryCode"
                                                name="countryCode" required>
                                                <option value="+91" <?= ($countryCode == '+91') ? 'selected' : '' ?>>🇮🇳
                                                    +91 (India)</option>
                                                <option value="+1" <?= ($countryCode == '+1') ? 'selected' : '' ?>>🇺🇸 +1
                                                    (USA)</option>
                                                <option value="+44" <?= ($countryCode == '+44') ? 'selected' : '' ?>>🇬🇧
                                                    +44 (UK)</option>
                                                <option value="+61" <?= ($countryCode == '+61') ? 'selected' : '' ?>>🇦🇺
                                                    +61 (Australia)</option>
                                                <option value="+81" <?= ($countryCode == '+81') ? 'selected' : '' ?>>🇯🇵
                                                    +81 (Japan)</option>
                                                <option value="+49" <?= ($countryCode == '+49') ? 'selected' : '' ?>>🇩🇪
                                                    +49 (Germany)</option>
                                                <option value="+33" <?= ($countryCode == '+33') ? 'selected' : '' ?>>🇫🇷
                                                    +33 (France)</option>
                                                <option value="+971" <?= ($countryCode == '+971') ? 'selected' : '' ?>>🇦🇪
                                                    +971 (UAE)</option>
                                                <option value="+63" <?= ($countryCode == '+63') ? 'selected' : '' ?>>🇵🇭
                                                    +63 (Philippines)</option>
                                                <option value="+234" <?= ($countryCode == '+234') ? 'selected' : '' ?>>🇳🇬
                                                    +234 (Nigeria)</option>
                                            </select>
                                        </div>

                                        <!-- Phone number input -->
                                        <input type="text" id="userPhone" name="userPhone" class="form-control p-4"
                                            placeholder="Phone No" required maxlength="15" />
                                    </div>

                                    <div class="input-group mb-3">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text p-3">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 640 640"><path d="M352 348.4C416.1 333.9 464 276.5 464 208C464 128.5 399.5 64 320 64C240.5 64 176 128.5 176 208C176 276.5 223.9 333.9 288 348.4L288 544C288 561.7 302.3 576 320 576C337.7 576 352 561.7 352 544L352 348.4zM328 160C297.1 160 272 185.1 272 216C272 229.3 261.3 240 248 240C234.7 240 224 229.3 224 216C224 158.6 270.6 112 328 112C341.3 112 352 122.7 352 136C352 149.3 341.3 160 328 160z"/></svg>
                                            </span>
                                        </div>
                                        <input type="text" id="pincode" name="pincode" class="form-control p-4"
                                            placeholder="Pincode" required
                                            maxlength="100" />
                                    </div>
                                    <!-- <div class="input-group mb-3">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text p-2 pr-3">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
                                                    <g fill="none" fill-rule="evenodd">
                                                        <path d="m12.593 23.258l-.011.002l-.071.035l-.02.004l-.014-.004l-.071-.035q-.016-.005-.024.005l-.004.01l-.017.428l.005.02l.01.013l.104.074l.015.004l.012-.004l.104-.074l.012-.016l.004-.017l-.017-.427q-.004-.016-.017-.018m.265-.113l-.013.002l-.185.093l-.01.01l-.003.011l.018.43l.005.012l.008.007l.201.093q.019.005.029-.008l.004-.014l-.034-.614q-.005-.018-.02-.022m-.715.002a.02.02 0 0 0-.027.006l-.006.014l-.034.614q.001.018.017.024l.015-.002l.201-.093l.01-.008l.004-.011l.017-.43l-.003-.012l-.01-.01z" />
                                                        <path fill="currentColor" fill-rule="nonzero" d="M6.72 16.64a1 1 0 0 1 .56 1.92c-.5.146-.86.3-1.091.44c.238.143.614.303 1.136.452C8.48 19.782 10.133 20 12 20s3.52-.218 4.675-.548c.523-.149.898-.309 1.136-.452c-.23-.14-.59-.294-1.09-.44a1 1 0 0 1 .559-1.92c.668.195 1.28.445 1.75.766c.435.299.97.82.97 1.594c0 .783-.548 1.308-.99 1.607c-.478.322-1.103.573-1.786.768C15.846 21.77 14 22 12 22s-3.846-.23-5.224-.625c-.683-.195-1.308-.446-1.786-.768c-.442-.3-.99-.824-.99-1.607c0-.774.535-1.295.97-1.594c.47-.321 1.082-.571 1.75-.766M12 2a7.5 7.5 0 0 1 7.5 7.5c0 2.568-1.4 4.656-2.85 6.14a16.4 16.4 0 0 1-1.853 1.615c-.594.446-1.952 1.282-1.952 1.282a1.71 1.71 0 0 1-1.69 0a21 21 0 0 1-1.952-1.282A16 16 0 0 1 7.35 15.64C5.9 14.156 4.5 12.068 4.5 9.5A7.5 7.5 0 0 1 12 2m0 5.5a2 2 0 1 0 0 4a2 2 0 0 0 0-4" />
                                                    </g>
                                                </svg>
                                            </span>
                                            <div id="selectedPincode" style="background-color:white; color:black; font-size: 16px; color: #333; display:flex; align-items:center; padding: 0 22px">
                                            </div>
                                        </div>
                                        <select class="form-control p-4 input-group-append" id="pincode" name="pincode" required onchange="showPincode()">
                                            <option value="" disabled selected>Select your nearest Pincode</option>
                                            <option value="574119">574119: Admar-Udupi</option>
                                            <option value="574118">574118: Alevoor-Udupi</option>
                                            <option value="576225">576225: Achladi-Udupi</option>
                                            <option value="576101">576101: Adi Udupi-Udupi</option>
                                            <option value="576226">576226: Airody-Udupi</option>
                                            <option value="576103">576103: Ambalapadi-Udupi</option>
                                            <option value="574101">574101: Ajekar-Karkala</option>
                                            <option value="576283">576283: Ajruhara-Kundapura</option>
                                            <option value="576233">576233: Alur-Kundapura</option>
                                            <option value="576227">576227: Amasebail-Kundapura</option>
                                        </select>
                                    </div>
                                    <script>
                                        function showPincode() {
                                            const pincode = document.getElementById("pincode").value;
                                            const displayDiv = document.getElementById("selectedPincode");

                                            // Display the selected pincode
                                            if (pincode) {
                                                displayDiv.innerHTML = `<strong>${pincode}</strong>`;
                                            } else {
                                                displayDiv.innerHTML = ''; 
                                            }
                                        }
                                    </script> -->

                                    <div class="input-group mb-3">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text p-3">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em"
                                                    viewBox="0 0 24 24">
                                                    <path fill="currentColor"
                                                        d="M18 10H9V7c0-1.654 1.346-3 3-3s3 1.346 3 3h2c0-2.757-2.243-5-5-5S7 4.243 7 7v3H6a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8a2 2 0 0 0-2-2m-7.939 5.499A2.002 2.002 0 0 1 14 16a1.99 1.99 0 0 1-1 1.723V20h-2v-2.277a1.99 1.99 0 0 1-.939-2.224" />
                                                </svg>
                                            </span>
                                        </div>
                                        <input type="password" id="signPwd1" name="signPwd1" class="form-control p-4"
                                            placeholder="Password" required maxlength="20" />
                                        <div class="input-group-append">
                                            <span class="input-group-text p-3" id="togglePasswordVisibility">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em"
                                                    viewBox="0 0 24 24">
                                                    <path fill="none" stroke="currentColor" stroke-linecap="round"
                                                        stroke-linejoin="round" stroke-width="1.5"
                                                        d="m3.282 21.782l4.278-4.278M21.782 3.282L17.673 7.39m-3.363 3.363a2.64 2.64 0 0 0-1.063-1.063a2.625 2.625 0 1 0-2.494 4.62m3.557-3.557l-3.557 3.557m3.557-3.557l3.363-3.363m-6.92 6.92L7.56 17.504M17.673 7.39c-.38-.319-.791-.621-1.232-.894C15.2 5.726 13.717 5.19 12 5.19c-4.956 0-7.948 4.459-8.91 6.16c-.11.196-.165.293-.197.446a1.2 1.2 0 0 0 0 .408c.032.152.088.25.198.445c.51.903 1.593 2.582 3.237 3.96c.38.319.791.621 1.232.895m12.18-7.925c.528.694.919 1.328 1.17 1.773c.11.194.165.292.197.444c.023.112.023.296 0 .408c-.032.152-.087.25-.197.444c-.96 1.702-3.95 6.162-8.91 6.162q-.714-.002-1.374-.117" />
                                                </svg>
                                            </span>
                                        </div>
                                    </div>

                                    <div class="input-group mb-3">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text p-3">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em"
                                                    viewBox="0 0 24 24">
                                                    <path fill="currentColor"
                                                        d="M18 10H9V7c0-1.654 1.346-3 3-3s3 1.346 3 3h2c0-2.757-2.243-5-5-5S7 4.243 7 7v3H6a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8a2 2 0 0 0-2-2m-7.939 5.499A2.002 2.002 0 0 1 14 16a1.99 1.99 0 0 1-1 1.723V20h-2v-2.277a1.99 1.99 0 0 1-.939-2.224" />
                                                </svg>
                                            </span>
                                        </div>
                                        <input type="password" id="signPwd2" name="signPwd2" class="form-control p-4"
                                            placeholder="Confirm Password" required maxlength="20" />
                                        <div class="input-group-append">
                                            <span class="input-group-text p-3" id="togglePassword">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em"
                                                    viewBox="0 0 24 24">
                                                    <path fill="none" stroke="currentColor" stroke-linecap="round"
                                                        stroke-linejoin="round" stroke-width="1.5"
                                                        d="m3.282 21.782l4.278-4.278M21.782 3.282L17.673 7.39m-3.363 3.363a2.64 2.64 0 0 0-1.063-1.063a2.625 2.625 0 1 0-2.494 4.62m3.557-3.557l-3.557 3.557m3.557-3.557l3.363-3.363m-6.92 6.92L7.56 17.504M17.673 7.39c-.38-.319-.791-.621-1.232-.894C15.2 5.726 13.717 5.19 12 5.19c-4.956 0-7.948 4.459-8.91 6.16c-.11.196-.165.293-.197.446a1.2 1.2 0 0 0 0 .408c.032.152.088.25.198.445c.51.903 1.593 2.582 3.237 3.96c.38.319.791.621 1.232.895m12.18-7.925c.528.694.919 1.328 1.17 1.773c.11.194.165.292.197.444c.023.112.023.296 0 .408c-.032.152-.087.25-.197.444c-.96 1.702-3.95 6.162-8.91 6.162q-.714-.002-1.374-.117" />
                                                </svg>
                                            </span>
                                        </div>
                                    </div>

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
                        <form id="frmLogin">
                            <div class="my-4">

                                <!-- Continue with Google (login mode) -->
                                <div class="row px-3 mb-3">
                                  <div class="col-12 text-center">
                                    <div class="g_id_signin"
                                         data-type="standard"
                                         data-shape="rectangular"
                                         data-theme="outline"
                                         data-text="signin_with"
                                         data-size="large"
                                         data-logo_alignment="left"
                                         data-width="320"></div>
                                    <div class="text-muted small mt-2">— or sign in with email —</div>
                                  </div>
                                </div>

                                <div class="row px-3">
                                    <div class="input-group mb-3">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text p-3">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em"
                                                    viewBox="0 0 24 24">
                                                    <path fill="currentColor"
                                                        d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2m0 4l-8 5l-8-5V6l8 5l8-5z" />
                                                </svg>
                                            </span>
                                        </div>
                                        <input type="text" id="loginEmail" name="loginEmail" class="form-control p-4"
                                            placeholder="Enter Email" required maxlength="100" />
                                    </div>



                                    <div class="input-group mb-3">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text p-3">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em"
                                                    viewBox="0 0 24 24">
                                                    <path fill="currentColor"
                                                        d="M18 10H9V7c0-1.654 1.346-3 3-3s3 1.346 3 3h2c0-2.757-2.243-5-5-5S7 4.243 7 7v3H6a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8a2 2 0 0 0-2-2m-7.939 5.499A2.002 2.002 0 0 1 14 16a1.99 1.99 0 0 1-1 1.723V20h-2v-2.277a1.99 1.99 0 0 1-.939-2.224" />
                                                </svg>
                                            </span>
                                        </div>
                                        <input type="password" id="loginPwd" name="loginPwd" class="form-control p-4"
                                            placeholder="Enter Password" required maxlength="20" />
                                        <div class="input-group-append">
                                            <span class="input-group-text p-3" onclick="togglePasswordVisibility()">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em"
                                                    viewBox="0 0 24 24">
                                                    <path fill="none" stroke="currentColor" stroke-linecap="round"
                                                        stroke-linejoin="round" stroke-width="1.5"
                                                        d="m3.282 21.782l4.278-4.278M21.782 3.282L17.673 7.39m-3.363 3.363a2.64 2.64 0 0 0-1.063-1.063a2.625 2.625 0 1 0-2.494 4.62m3.557-3.557l-3.557 3.557m3.557-3.557l3.363-3.363m-6.92 6.92L7.56 17.504M17.673 7.39c-.38-.319-.791-.621-1.232-.894C15.2 5.726 13.717 5.19 12 5.19c-4.956 0-7.948 4.459-8.91 6.16c-.11.196-.165.293-.197.446a1.2 1.2 0 0 0 0 .408c.032.152.088.25.198.445c.51.903 1.593 2.582 3.237 3.96c.38.319.791.621 1.232.895m12.18-7.925c.528.694.919 1.328 1.17 1.773c.11.194.165.292.197.444c.023.112.023.296 0 .408c-.032.152-.087.25-.197.444c-.96 1.702-3.95 6.162-8.91 6.162q-.714-.002-1.374-.117" />
                                                </svg>
                                            </span>
                                        </div>
                                    </div>

                                </div>

                                <div class="row">
                                    <div class="col-6 text-left">
                                        <button class="btn btn-success btn-md px-4" onclick="return chkLogin()">Log
                                            in</button>
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
        </div><!-- /.nj-auth -->
    </section>
    <!-- Add this modal HTML at the bottom of the body tag, before any scripts -->
    <div class="modal fade" id="inviteCodeModal" tabindex="-1" role="dialog" aria-labelledby="inviteCodeModalLabel"
        aria-hidden="true">
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
                                    <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em"
                                        viewBox="0 0 24 24">
                                        <path fill="currentColor"
                                            d="M20 4H4c-1.11 0-2 .89-2 2v12c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2m-2 9h-3v3h-2v-3h-3v-2h3V8h2v3h3z" />
                                    </svg>
                                </span>
                            </div>
                            <input type="text" id="modalInviteCode" class="form-control"
                                placeholder="Enter Invitation Code" maxlength="50" />
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
                    } else if (res == 'reporterNotVerified') {
                        alert('Your reporter account is pending admin verification. Please wait for approval.');
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
            var pincode = $('#pincode').val();
            var signPwd1 = $('#signPwd1').val();
            var signPwd2 = $('#signPwd2').val();
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
            if (userPhone == '') {
                $('#panelStatusSignUp').html('<div class="text-danger animate__animated animate__flash">Error: Phone number not entered!</div>');
                return false;
            }
            if (pincode == '') {
                $('#panelStatusSignUp').html('<div class="text-danger animate__animated animate__flash">Error: Pincode not selected!</div>');
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

        function chkReporterSignUp() {
            var fullName = $('#reporterFullName').val();
            var email = $('#reporterEmail').val();
            var phone = $('#reporterPhone').val();
            var countryCode = $('#reporterCountryCode').val();
            var pincode = $('#reporterPincode').val();
            var pwd1 = $('#reporterPwd1').val();
            var pwd2 = $('#reporterPwd2').val();
            var bio = $('#reporterBio').val();
            var experience = $('#reporterExperience').val();
            var portfolio = $('#reporterPortfolio').val();
            var coverage = $('#reporterCoverage').val();

            if (fullName == '') { $('#panelStatusReporter').html('<div class="text-danger animate__animated animate__flash">Error: Full Name not entered!</div>'); return false; }
            if (email == '') { $('#panelStatusReporter').html('<div class="text-danger animate__animated animate__flash">Error: Email not entered!</div>'); return false; }
            if (!chkFilterEmail.test(email)) { $('#panelStatusReporter').html('<div class="text-danger animate__animated animate__flash">Error: Email is not valid!</div>'); return false; }
            if (phone == '') { $('#panelStatusReporter').html('<div class="text-danger animate__animated animate__flash">Error: Phone number not entered!</div>'); return false; }
            if (pincode == '') { $('#panelStatusReporter').html('<div class="text-danger animate__animated animate__flash">Error: Pincode not entered!</div>'); return false; }
            if (pwd1 == '') { $('#panelStatusReporter').html('<div class="text-danger animate__animated animate__flash">Error: Password not entered!</div>'); return false; }
            if (pwd1 != pwd2) { $('#panelStatusReporter').html('<div class="text-danger animate__animated animate__flash">Error: Passwords do not match!</div>'); return false; }

            // Check if email already exists
            $.ajax({
                method: 'POST',
                url: 'process/signInProcess.php',
                data: { act: 'chkExist', signEmail: email }
            }).done(function(response) {
                if (response === 'OK') {
                    $.ajax({
                        url: 'process/signInProcess.php',
                        method: 'POST',
                        data: {
                            act: 'createReporterAccount',
                            reporterFullName: fullName,
                            reporterEmail: email,
                            reporterPhone: phone,
                            reporterCountryCode: countryCode,
                            reporterPincode: pincode,
                            reporterPwd1: pwd1,
                            reporterBio: bio,
                            reporterExperience: experience,
                            reporterPortfolio: portfolio,
                            reporterCoverage: coverage
                        },
                        dataType: 'json'
                    }).done(function(res) {
                        if (res.status === 'OK') {
                            $('#panelReporterSignUp').html(
                                '<div class="text-center py-4">' +
                                '<h5 style="color: var(--orange);">Registration Submitted!</h5>' +
                                '<p>Please check your email for account activation.<br>' +
                                'Your reporter account will be reviewed and verified by our admin team.<br>' +
                                'You will be notified once your account is approved.</p>' +
                                '<button class="btn btn-success mt-2" onclick="window.location=\'sign-in.php?type=login\'">OK</button>' +
                                '</div>'
                            );
                        } else {
                            $('#panelStatusReporter').html('<div class="text-danger animate__animated animate__flash">Error: ' + (res.message || 'Registration failed!') + '</div>');
                        }
                    });
                } else {
                    $('#panelStatusReporter').html('<div class="text-danger animate__animated animate__flash">Error: This email address already exists!</div>');
                }
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
    </script>

    <!-- Google Identity Services callback. GIS calls this with the signed
         JWT after the user picks a Google account. We POST the JWT to
         /process/google-callback.php which verifies it server-side, creates
         or finds the user, sets the session + cookie, and tells us where
         to redirect. -->
    <script>
        function handleGoogleCredential(response) {
            if (!response || !response.credential) {
                alert('Google sign-in failed.');
                return;
            }
            var fd = new FormData();
            fd.append('id_token', response.credential);
            fetch('/process/google-callback.php', {
                method: 'POST',
                body: fd,
                credentials: 'same-origin'
            })
            .then(function (r) { return r.text(); })
            .then(function (txt) {
                // Existing callback echoes "OK|" on success, or an error string otherwise.
                if (txt && txt.indexOf('OK|') === 0) {
                    window.location.href = '/stream.php';
                    return;
                }
                if (txt === 'notActivated') {
                    alert('Your account is not activated. Please contact support.');
                    return;
                }
                alert('Sign-in failed: ' + txt);
            })
            .catch(function (err) {
                alert('Network error during sign-in: ' + err);
            });
        }
    </script>
</body>

</html>