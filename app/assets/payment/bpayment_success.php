<?
include '../php/validate.logged.php';
include '../php/db_config.php';
?>

<?php
// Check if the HTTP_REFERER is from verify_payment.php
if (
    isset($_SERVER['HTTP_REFERER']) &&
    (strpos($_SERVER['HTTP_REFERER'], 'verify_payment.php') !== false) ||
    (strpos($_SERVER['HTTP_REFERER'], 'payment.php') !== false)
) {
?>

    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Payment Successfull</title>
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
        <style>
            body {
                font-family: "Roboto", sans-serif;
                background-color: #f7f7f7;
                margin: 0;
                padding: 20px;
            }

            .navbar {
                background-color: #222;
                color: #fff;
                padding: 10px 0;
                text-align: center;
            }

            .navbar-brand {
                display: flex;
                align-items: center;
                justify-content: center;
            }

            */ .navbar-brand img {
                max-height: 30px;
                margin-right: 10px;
            }

            .navbar-brand h1 {
                font-size: 1.5em;
                margin: 0;
            }

            .container {
                max-width: 500px;
                margin: auto;
                background-color: #fff;
                padding: 20px;
                border-radius: 10px;
                box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            }

            .header-label {
                font-size: 1.5em;
                font-weight: bold;
                margin-bottom: 20px;
                color: #333;
            }

            .form-group {
                margin-bottom: 25px;
            }

            .order-summary-container,
            .form-group {
                padding: 20px;
                border: 1px solid #e0e0e0;
                border-radius: 5px;
                background-color: #fafafa;
            }

            .order-summary-container .header,
            .order-summary-container .item-row,
            .order-summary-container .sub-total-row,
            .order-summary-container .sub-total-item,
            .order-summary-container .total-row {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 10px 0;
            }

            .order-summary-container .item-row,
            .order-summary-container .sub-total-row,
            .order-summary-container .sub-total-item,
            .order-summary-container .total-row {
                border-top: 1px solid #e0e0e0;
            }

            .order-summary-container .total-row {
                font-size: 1.2em;
                font-weight: bold;
                color: #333;
            }

            .form-control {
                width: 100%;
                padding: 10px;
                border: 1px solid #e0e0e0;
                border-radius: 5px;
                margin-bottom: 10px;
            }

            .form-container {
                max-width: 600px;
                /* Adjust as needed */
                margin: auto;
                padding: 20px;
                background-color: #fff;
                border-radius: 10px;
                box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            }

            .btn {
                display: inline-block;
                padding: 10px 20px;
                font-size: 1em;
                color: #fff;
                background-color: #007bff;
                border: none;
                border-radius: 5px;
                text-align: center;
                text-decoration: none;
                margin-top: 10px;
                cursor: pointer;
                transition: background-color 0.3s ease;
            }

            .btn:hover {
                background-color: #0056b3;
            }

            .select {
                display: block;
                width: 100%;
                padding: 10px;
                margin-top: 10px;
                margin-bottom: 10px;
                font-size: 1em;
                line-height: 1.5;
                color: #495057;
                background-color: #fff;
                border: 1px solid #ced4da;
                border-radius: 5px;
                transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
            }

            .payment-method {
                display: flex;
                align-items: center;
                margin: 10px 0;
            }

            .payment-method img {
                margin-right: 10px;
            }
        </style>

        <script>
            function validateForm() {
                // Get form fields using jQuery
                const email = $("#email").val();
                const mobile = $("#mobile").val();
                const billingCountry = $("#billing_country").val();
                const billingStreet = $("#billing_street").val();
                const billingCity = $("#billing_city").val();
                const billingState = $("#billing_state").val();
                const billingZip = $("#billing_zip").val();
                const billingPhone = $("#billing_phone").val();

                console.log(email);
                console.log(mobile);
                console.log(billingCountry);
                console.log(billingStreet);
                console.log(billingCity);
                console.log(billingState);
                console.log(billingZip);
                console.log(billingPhone);

                // Check if any required field is empty
                if (
                    !email ||
                    !mobile ||
                    !billingCountry ||
                    !billingStreet ||
                    !billingCity ||
                    !billingState ||
                    !billingZip ||
                    !billingPhone
                ) {
                    alert("Please fill in all required fields before proceeding.");
                    return false; // Prevent form submission
                }

                return true; // Allow form submission
            }
        </script>
    </head>

    <body>
        <nav
            class="navbar"
            style="background-color: rgb(168, 161, 150); text-align: center">
            <div
                style="
          padding: 10px;
          display: flex;
          align-items: center;
          justify-content: center;
        ">
                <img
                    src="/assets/img/logo.black.png"
                    alt="Knobly Cream Logo"
                    style="max-height: 70px; margin-right: 10px" />
                <h1 style="color: #fff; font-size: 1.5em; margin: 0">
                    Knobly Consulting LLP
                </h1>
            </div>
        </nav>
        <?

        // Prepare the SQL statement to set the deleteFlag and the deletedOn timestamp
        $stmt = $creamdb->prepare("UPDATE user SET plan = 1 WHERE id = ?");
        $stmt->bind_param("i", $gUserId);  // "si" means string, integer

        if ($stmt->execute()) {
        ?>
            <div class="container">
                <div class="form-group order-container">
                    <label class="header-label">Payment Successful</label>
                    <p>
                        Your Payment was successful and now you have become a Pro Member!!!
                    </p>
                </div>
                <button class="btn" onclick="window.location.href='/stream.php'">Back to Cream</button>
            </div>


        <?
        $_SESSION['userPlan'] = 1;
        } else {
        ?>

            <div class="container">
                <div class="form-group order-container">
                    <label class="header-label">Sorry for the Inconvenience</label>
                    <p>
                        There was an error while updating your pro version. Please contact <a href="mailto:YOUR_SUPPORT_EMAIL">Knobly Support</a>
                    </p>
                </div>
                <button class="btn" onclick="window.location.href='/stream.php'">Back to Cream</button>
            </div>
        <?
        }
        $stmt->close();
        ?>

    </body>

    </html>

<?
} else {
    // If the request is not from verify_payment.php, throw an HTTP 500 error.
    header('HTTP/1.1 500 Internal Server Error');
    exit();
}
?>