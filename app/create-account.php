<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if Google data exists
if (!isset($_SESSION['google_data'])) {
    header('Location: sign-in.php');
    exit;
}

$google_data = $_SESSION['google_data'];
?>
<!DOCTYPE html>
<html>

<head>
    <title>News Junction</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.1/aos.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css"
        integrity="sha384-9aIt2nRpC12Uk9gS9baDl411NQApFmC26EwAOH8WgZl5MYYxFfc+NcPb1dKGj7Sk" crossorigin="anonymous" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.0.0/animate.min.css" />
    <link rel="stylesheet" href="inc/css/magnific-popup.css" />
    <link rel="stylesheet" href="inc/css/styles.css" />
    <link rel="stylesheet" href="inc/css/cream.css">
    <link rel="icon" type="image/x-icon" href="../grfx/img/logo.ico">

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"
        integrity="sha256-9/aliU8dGd2tb6OSsuzixeV4y/faTqgFtohetphbbj0=" crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.min.js"
        integrity="sha384-OgVRvuATP1z7JjHLkuOU7Xw704+h835Lr+6QL9UvYjZE3Ipu6Tp75j7Bh/kR0JKI"
        crossorigin="anonymous"></script>
    <!-- Magnific Popup CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/magnific-popup@1.1.0/dist/magnific-popup.css" />

    <!-- Magnific Popup JS -->
    <script src="https://cdn.jsdelivr.net/npm/magnific-popup@1.1.0/dist/jquery.magnific-popup.min.js"></script>

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

        body {
            background-color: #f8f4e3;
            color: var(--text-dark);
            font-family: Verdana, Geneva, Tahoma, sans-serif;
        }

        .container a img {
            margin-bottom: 10px;
        }


        .headding {
            font-size: 20px;
            font-weight: 400;
            color: var(--text-dark);
            margin-bottom: 20px;
        }

        h2 {
            color: var(--dark-orange);
            font-size: 28px;
            margin-top: 30px;
            text-align: center;
            font-weight: bold;
        }

        p {
            font-size: 16px;
            color: var(--text-dark);
            text-align: center;
            margin-bottom: 30px;
        }

        .form-group label {
            font-weight: 500;
            margin-bottom: 5px;
            color: var(--text-dark);
        }

        .input-group-text {
            background-color: var(--cream);
            border: 1px solid var(--dark-cream);
            color: var(--dark-orange);
        }

        input,
        select {
            background-color: var(--white);
            border: 1px solid var(--dark-cream);
            border-radius: 4px;
            padding: 10px;
            color: var(--text-dark);
            transition: border-color 0.3s ease;
        }

        input:focus,
        select:focus {
            border-color: var(--orange);
            outline: none;
        }

        button {
            padding: 12px 25px;
            background-color: var(--orange);
            color: var(--white);
            border: none;
            border-radius: 5px;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s ease;
            display: block;
            margin: 0 auto;
        }

        button:hover {
            background-color: var(--dark-orange);
        }

        button:disabled {
            background-color: var(--dark-cream);
            color: var(--text-dark);
            cursor: not-allowed;
        }

        .error {
            color: #cc0000;
            margin-top: 10px;
            text-align: center;
        }

        .success {
            color: #228B22;
            margin-top: 10px;
            text-align: center;
        }

        .loading {
            color: var(--text-dark);
            text-align: center;
            font-size: 14px;
            margin-top: 10px;
        }

        #responseMessage {
            margin-top: 10px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
        }

        input,
        select {
            width: 100%;
            padding: 8px;
            box-sizing: border-box;
        }

        .error {
            color: red;
            margin-top: 10px;
        }

        .success {
            color: green;
            margin-top: 10px;
        }

        button:disabled {
            background-color: #6c757d;
            cursor: not-allowed;
        }

        .loading {
            display: none;
            margin-top: 10px;
        }



        .text-center {
            justify-content: center;
        }
    </style>
</head>

<body style="height: 100vh;">
    <div class="container py-5" style="max-width: 700px;">
        <!-- Header and Welcome Message -->
        <div class="nav text-center mb-4">
            <div class="text-center mb-4">
                <a href="/">
                    <img src="inc/img/logo.black.png" alt="News Junction" style="width:200px;">
                </a>
                <div class="headding mt-2 mb-2">Create &bull; Reach &bull; Measure</div>
            </div>

            <p>
                Welcome, <?= htmlspecialchars($google_data['full_name']); ?>!<br>
                Please provide your phone number to complete account creation.
            </p>
        </div>

        <!-- Card with Form -->
        <div class="card shadow" style="background-color: var(--white); border-radius: 12px;">
            <div class="card-body">
                <form id="createAccountForm">
                    <div class="form-group">
                        <label for="userPhone">Phone Number</label>
                        <div class="input-group mb-3">
                            <!-- Phone Icon -->
                            <div class="input-group-prepend">
                                <span class="input-group-text p-3">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24">
                                        <path fill="currentColor"
                                            d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24c1.12.37 2.33.57 3.57.57c.55 0 1 .45 1 1V20c0 .55-.45 1-1 1c-9.39 0-17-7.61-17-17c0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1c0 1.25.2 2.45.57 3.57c.11.35.03.74-.25 1.02z" />
                                    </svg>
                                </span>
                            </div>

                            <!-- Country Code Dropdown -->
                            <div class="input-group-prepend">
                                <select class="form-control" style="height: calc(3.1rem + 1px);" id="countryCode" name="countryCode" required>
                                    <option value="+91" selected>🇮🇳 +91</option>
                                    <option value="+1">🇺🇸 +1</option>
                                    <option value="+44">🇬🇧 +44</option>
                                    <option value="+61">🇦🇺 +61</option>
                                    <option value="+81">🇯🇵 +81</option>
                                    <option value="+49">🇩🇪 +49</option>
                                    <option value="+33">🇫🇷 +33</option>
                                    <option value="+971">🇦🇪 +971</option>
                                    <option value="+63">🇵🇭 +63</option>
                                    <option value="+234">🇳🇬 +234</option>
                                </select>
                            </div>

                            <!-- Phone Input -->
                            <input type="text" id="userPhone" name="userPhone" class="form-control p-4"
                                placeholder="e.g., 1234567890" required pattern="[0-9]{10,15}"
                                title="Phone number must be 10-15 digits">
                        </div>
                    </div>

                    <button type="submit" id="submitBtn">Create Account</button>
                    <div id="loading" class="loading">Loading...</div>
                    <div id="responseMessage"></div>
                </form>
            </div>
        </div>
    </div>

    <?php include 'inc/php/indexFooter.php'; ?>


    <script>
        document.getElementById('createAccountForm').addEventListener('submit', function(event) {
            event.preventDefault(); // Prevent default form submission

            const submitBtn = document.getElementById('submitBtn');
            const loading = document.getElementById('loading');
            const responseMessage = document.getElementById('responseMessage');

            // Disable button and show loading
            submitBtn.disabled = true;
            loading.style.display = 'block';
            responseMessage.innerHTML = '';

            // Get form data
            const formData = new FormData(this);

            // Send AJAX request
            fetch('process/process-create-account.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(data => {
                    // Hide loading
                    loading.style.display = 'none';
                    submitBtn.disabled = false;

                    // Check response for success or error
                    if (data.includes('success')) {
                        responseMessage.innerHTML = '<div class="success">Account created successfully!...</div>';
                        setTimeout(() => {
                            window.location.href = 'stream.php';
                        }, 1500);
                    } else if (data.includes('email_exists')) {
                        responseMessage.innerHTML = '<div class="success">Email already exists, try logging in...</div>';
                        setTimeout(() => {
                            window.location.href = 'sign-in.php?type=login';
                        }, 1500);
                    } else {
                        responseMessage.innerHTML = `<div class="error">${data}</div>`;
                    }
                })
                .catch(error => {
                    // Hide loading and show error
                    loading.style.display = 'none';
                    submitBtn.disabled = false;
                    responseMessage.innerHTML = '<div class="error">An error occurred. Please try again.</div>';
                    console.error('Error:', error);
                });
        });
    </script>
</body>

</html>