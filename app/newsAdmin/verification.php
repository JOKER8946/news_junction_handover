<?php
include '../inc/php/validate.logged.php';

// Assuming the email or phone number is stored in the session
if (!isset($_SESSION['userEmail'])) {
    die("No email or phone number in session.");
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body {
            font-family: sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-color: #f4f4f4;
            margin: 0;
            /* Remove default body margin */
        }

        .container {
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: 400px;
            /* Adjust as needed */
        }

        h1 {
            text-align: center;
            margin-bottom: 20px;
            color: #333;
            /* Darker heading color */
        }

        #otp-form,
        #verify-otp-form {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            color: #555;
            /* Slightly darker label color */
        }

        input[type="text"] {
            width: calc(100% - 12px);
            /* Account for padding */
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            margin-bottom: 10px;
            box-sizing: border-box;
            /* Include padding in width */
        }

        button {
            background-color: #007bff;
            /* Blue button */
            color: #fff;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            /* Smooth hover effect */
        }

        button:hover {
            background-color: #0056b3;
            /* Darker blue on hover */
        }

        #message {
            margin-top: 10px;
            color: #d9534f;
            /* Red for error messages */
        }

        .success {
            color: #5cb85c;
            /* Green for success messages */
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>Admin Login</h1>

        <div id="otp-form">
            <p>Sending OTP to mail: <?= $gUserEmail ?></p>
            <button id="send-otp-btn">Send OTP</button>
        </div>

        <div id="verify-otp-form" style="display:none;">
            <h2>Enter OTP</h2>
            <label for="otp">Enter OTP:</label>
            <input type="text" name="otp" id="otp" required>
            <button id="verify-otp-btn">Verify OTP</button>
        </div>

        <div id="message"></div>
    </div>
    <script>
        // When Send OTP button is clicked
        $('#send-otp-btn').click(function() {
            const $button = $(this);
            $button.prop("disabled", true).text("Sending...");

            // Send the OTP request via AJAX
            $.ajax({
                url: 'send_otp.php',
                type: 'POST',
                success: function(response) {
                    if (response.success) {
                        $('#message').html("OTP sent successfully!");
                        $('#otp-form').hide();
                        $('#verify-otp-form').show();
                    } else {
                        $('#message').html(response.error);
                    }
                }
            });
        });

        // When Verify OTP button is clicked
        $('#verify-otp-btn').click(function() {
            var otp = $('#otp').val();

            if (otp === "") {
                $('#message').html("Please enter the OTP.");
                return;
            }

            // Verify the OTP via AJAX
            $.ajax({
                url: 'verify_otp.php',
                type: 'POST',
                data: {
                    otp: otp
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert("verified successfully");
                        window.location.href = "dashboard.php?filter=week";
                    } else {
                        $('#message').html(response.error);
                    }
                },
                error: function(xhr, status, error) {
                    console.error("AJAX error: " + status + " - " + error);
                }
            });
        });
    </script>

</body>

</html>