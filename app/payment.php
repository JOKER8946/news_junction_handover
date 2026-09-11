<?php
include 'inc/php/validate.logged.php';

// Check if required POST data is present
if (!isset($_POST['amount'], $_POST['plan'])) {
    // If data is missing, return HTTP 500
    http_response_code(500);
    exit();
}

if (!in_array($_POST['amount'], [24000, 18000, 2000,2400])) {
    // If data is missing, return HTTP 500
    http_response_code(500);
    exit();
}

// If POST data is present, proceed with your normal logic
// Example: process the data
$amount = $_POST['amount'];
$plan = $_POST['plan'];
$description = "Subscription Payment";
$currency = "INR";


function formatNumber($number)
{
    // Format the number to always have 2 decimal places
    return number_format($number, 2, '.', '');
}

// $amount = 1;
$plan = 'Annual';
// $amount = formatNumber($amount);

$gst = formatNumber($amount * 9 / 100);

$total = formatNumber($amount + 2 * $gst);

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>News Junction: Payment Gateway</title>
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
            /* width: 80%; */
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

        .account-info-data {
            display: grid;
            justify-items: stretch;
        }

        .quantity {
            padding-right: 90px;
        }

        @media screen and (max-width: 768px) {
            .quantity {
                padding-right: 40px;
            }
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

        $(document).ready(function() {
            $("#payButton").on("click", function() {
                const amount = <?= $total * 100 ?>; // ₹2,360 in paise
                const email = $("#email").val();
                const mobile = $("#mobile").val();
                const description = "<?= $description ?>";
                const currency = "<?= $currency ?>";
                const plan = "<?= $plan ?>";

                if (validateForm()) {
                    $.ajax({
                        url: "inc/payment/checkout.php",
                        method: "POST",
                        contentType: "application/json",
                        data: JSON.stringify({
                            amount: amount,
                            plan_type: plan
                        }),
                        success: function(data) {
                            if (data.order_id) {
                                const options = {
                                    key: "YOUR_RAZORPAY_KEY_ID",
                                    amount: amount,
                                    currency: currency,
                                    name: "Knobly Consulting LLP",
                                    description: description,
                                    order_id: data.order_id,
                                    handler: function(response) {
                                        // Populate hidden form fields with response values
                                        $("#order_id").val(data.order_id);
                                        $("#payment_id").val(response.razorpay_payment_id);
                                        $("#signature").val(response.razorpay_signature);

                                        // Submit the form after setting hidden fields
                                        $("#paymentForm").submit();
                                    },
                                    prefill: {
                                        email: email,
                                        contact: mobile,
                                    },
                                };

                                const rzp = new Razorpay(options);
                                rzp.open();
                            } else {
                                alert("Error: " + data.error);
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error("Error:", error);
                        },
                    });
                }
            });
        });
    </script>
</head>

<body>
    <nav class="navbar" style="background-color: rgb(168, 161, 150); text-align: center">
        <div style="padding: 10px; display: flex;align-items: center;justify-content: center;">
            <img src="inc/img/logo.black.png" alt="News Junction Logo" style="max-height: 70px; margin-right: 10px" />
            <h1 style="color: #fff; font-size: 1.5em; margin: 0">
                Knobly Consulting LLP
            </h1>
        </div>
    </nav>

    <div class="container">
        <div class="form-group order-container">
            <label class="header-label">Order Summary</label>
            <div class="order-summary-container">
                <div class="header">
                    <div class="item-label">Item</div>
                    <div class="quantity-label">Quantity</div>
                    <div class="price-label">Price</div>
                </div>
                <div class="item-row">
                    <div class="item"><?= $plan ?></div>
                    <div class="quantity">1</div>
                    <div class="price">₹<?= $amount ?></div>
                </div>
                <div class="sub-total-row">
                    <div class="sub-total-label">SUBTOTAL</div>
                    <div class="sub-total-amount">₹<?= $amount ?></div>
                </div>
                <div class="sub-total-item">
                    <div class="tax-label">CGST (9%)</div>
                    <div class="tax-amount">₹<?= $gst ?></div>
                </div>
                <div class="sub-total-item">
                    <div class="tax-label">SGST (9%)</div>
                    <div class="tax-amount">₹<?= $gst ?></div>
                </div>
                <div class="total-row">
                    <div class="total-label">TOTAL</div>
                    <div class="total-amount">₹<?= $total ?></div>
                </div>
            </div>
        </div>

        <form id="paymentForm" action="inc/payment/verify_payment.php" method="POST">
            <div class="form-group account-info-container">
                <label class="header-label">Account Information</label>
                <div class="account-info-data">
                    <label for="name">Name: </label>
                    <input type="name" name="name" id="name" class="form-control" value="<?= $gUserName ?>" disabled />
                </div>
                <div class="account-info-data">
                    <label for="email">Email: </label>
                    <input type="email" name="email" id="email" class="form-control" value="<?= $gUserEmail ?>" disabled />
                </div>
                <div class="account-info-data">
                    <label for="mobile">Mobile No: </label>
                    <input type="tel" name="mobile" id="mobile" class="form-control" placeholder="Mobile*" required />
                </div>
            </div>

            <div class="form-group billing-info-container">
                <label class="header-label">Billing Address</label>
                <div class="account-info-data">
                    <label for="billing_country">Country/ Region* : </label>
                    <select name="billing_country" id="billing_country" class="select" required>
                        <option value>Country/Region*</option>
                        <option value="AL">Albania</option>
                        <option value="DZ">Algeria</option>
                        <option value="AS">American Samoa</option>
                        <option value="AD">Andorra</option>
                        <option value="AO">Angola</option>
                        <option value="AI">Anguilla</option>
                        <option value="AQ">Antarctica</option>
                        <option value="AG">Antigua and Barbuda</option>
                        <option value="AR">Argentina</option>
                        <option value="AM">Armenia</option>
                        <option value="AW">Aruba</option>
                        <option value="AU">Australia</option>
                        <option value="AT">Austria</option>
                        <option value="AZ">Azerbaijan</option>
                        <option value="BS">Bahamas</option>
                        <option value="BH">Bahrain</option>
                        <option value="BD">Bangladesh</option>
                        <option value="BB">Barbados</option>
                        <option value="BY">Belarus</option>
                        <option value="BE">Belgium</option>
                        <option value="BZ">Belize</option>
                        <option value="BJ">Benin</option>
                        <option value="BM">Bermuda</option>
                        <option value="BT">Bhutan</option>
                        <option value="BO">Bolivia</option>
                        <option value="BQ">Bonaire, Sint Eustatius and Saba</option>
                        <option value="BA">Bosnia and Herzegovina</option>
                        <option value="BW">Botswana</option>
                        <option value="BV">Bouvet Island</option>
                        <option value="BR">Brazil</option>
                        <option value="IO">British Indian Ocean Territory</option>
                        <option value="BN">Brunei Darussalam</option>
                        <option value="BG">Bulgaria</option>
                        <option value="BF">Burkina Faso</option>
                        <option value="BI">Burundi</option>
                        <option value="CV">Cabo Verde</option>
                        <option value="KH">Cambodia</option>
                        <option value="CM">Cameroon</option>
                        <option value="CA">Canada</option>
                        <option value="KY">Cayman Islands</option>
                        <option value="CF">Central African Republic</option>
                        <option value="TD">Chad</option>
                        <option value="CL">Chile</option>
                        <option value="CN">China</option>
                        <option value="CX">Christmas Island</option>
                        <option value="CC">Cocos (Keeling) Islands</option>
                        <option value="CO">Colombia</option>
                        <option value="KM">Comoros</option>
                        <option value="CG">Congo</option>
                        <option value="CD">Congo, Democratic Republic of the</option>
                        <option value="CK">Cook Islands</option>
                        <option value="CR">Costa Rica</option>
                        <option value="HR">Croatia</option>
                        <option value="CU">Cuba</option>
                        <option value="CW">Curaçao</option>
                        <option value="CY">Cyprus</option>
                        <option value="CZ">Czech Republic</option>
                        <option value="CI">Côte d'Ivoire</option>
                        <option value="DK">Denmark</option>
                        <option value="DJ">Djibouti</option>
                        <option value="DM">Dominica</option>
                        <option value="DO">Dominican Republic</option>
                        <option value="EC">Ecuador</option>
                        <option value="EG">Egypt</option>
                        <option value="SV">El Salvador</option>
                        <option value="GQ">Equatorial Guinea</option>
                        <option value="ER">Eritrea</option>
                        <option value="EE">Estonia</option>
                        <option value="SZ">Eswatini</option>
                        <option value="ET">Ethiopia</option>
                        <option value="FK">Falkland Islands (Malvinas)</option>
                        <option value="FO">Faroe Islands</option>
                        <option value="FJ">Fiji</option>
                        <option value="FI">Finland</option>
                        <option value="FR">France</option>
                        <option value="GF">French Guiana</option>
                        <option value="PF">French Polynesia</option>
                        <option value="TF">French Southern Territories</option>
                        <option value="GA">Gabon</option>
                        <option value="GM">Gambia</option>
                        <option value="GE">Georgia</option>
                        <option value="DE">Germany</option>
                        <option value="GH">Ghana</option>
                        <option value="GI">Gibraltar</option>
                        <option value="GR">Greece</option>
                        <option value="GL">Greenland</option>
                        <option value="GD">Grenada</option>
                        <option value="GP">Guadeloupe</option>
                        <option value="GU">Guam</option>
                        <option value="GT">Guatemala</option>
                        <option value="GG">Guernsey</option>
                        <option value="GN">Guinea</option>
                        <option value="GW">Guinea-Bissau</option>
                        <option value="GY">Guyana</option>
                        <option value="HT">Haiti</option>
                        <option value="HM">Heard Island and McDonald Islands</option>
                        <option value="VA">Holy See</option>
                        <option value="HN">Honduras</option>
                        <option value="HK">Hong Kong</option>
                        <option value="HU">Hungary</option>
                        <option value="IS">Iceland</option>
                        <option value="IN">India</option>
                        <option value="ID">Indonesia</option>
                        <option value="IR">Iran</option>
                        <option value="IQ">Iraq</option>
                        <option value="IE">Ireland</option>
                        <option value="IM">Isle of Man</option>
                        <option value="IL">Israel</option>
                        <option value="IT">Italy</option>
                        <option value="JM">Jamaica</option>
                        <option value="JP">Japan</option>
                        <option value="JE">Jersey</option>
                        <option value="JO">Jordan</option>
                        <option value="KZ">Kazakhstan</option>
                        <option value="KE">Kenya</option>
                        <option value="KI">Kiribati</option>
                        <option value="KP">Korea, Democratic People's Republic of</option>
                        <option value="KR">Korea, Republic of</option>
                        <option value="KW">Kuwait</option>
                        <option value="KG">Kyrgyzstan</option>
                        <option value="LA">Lao People's Democratic Republic</option>
                        <option value="LV">Latvia</option>
                        <option value="LB">Lebanon</option>
                        <option value="LS">Lesotho</option>
                        <option value="LR">Liberia</option>
                        <option value="LY">Libya</option>
                        <option value="LI">Liechtenstein</option>
                        <option value="LT">Lithuania</option>
                        <option value="LU">Luxembourg</option>
                        <option value="MO">Macao</option>
                        <option value="MG">Madagascar</option>
                        <option value="MW">Malawi</option>
                        <option value="MY">Malaysia</option>
                        <option value="MV">Maldives</option>
                        <option value="ML">Mali</option>
                        <option value="MT">Malta</option>
                        <option value="MH">Marshall Islands</option>
                        <option value="MQ">Martinique</option>
                        <option value="MR">Mauritania</option>
                        <option value="MU">Mauritius</option>
                        <option value="YT">Mayotte</option>
                        <option value="MX">Mexico</option>
                        <option value="FM">Micronesia</option>
                        <option value="MD">Moldova, Republic of</option>
                        <option value="MC">Monaco</option>
                        <option value="MN">Mongolia</option>
                        <option value="ME">Montenegro</option>
                        <option value="MS">Montserrat</option>
                        <option value="MA">Morocco</option>
                        <option value="MZ">Mozambique</option>
                        <option value="MM">Myanmar</option>
                        <option value="NA">Namibia</option>
                        <option value="NR">Nauru</option>
                        <option value="NP">Nepal</option>
                        <option value="NL">Netherlands</option>
                        <option value="NC">New Caledonia</option>
                        <option value="NZ">New Zealand</option>
                        <option value="NI">Nicaragua</option>
                        <option value="NE">Niger</option>
                        <option value="NG">Nigeria</option>
                        <option value="NU">Niue</option>
                        <option value="NF">Norfolk Island</option>
                        <option value="MK">North Macedonia</option>
                        <option value="MP">Northern Mariana Islands</option>
                        <option value="NO">Norway</option>
                        <option value="OM">Oman</option>
                        <option value="PK">Pakistan</option>
                        <option value="PW">Palau</option>
                        <option value="PS">Palestine, State of</option>
                        <option value="PA">Panama</option>
                        <option value="PG">Papua New Guinea</option>
                        <option value="PY">Paraguay</option>
                        <option value="PE">Peru</option>
                        <option value="PH">Philippines</option>
                        <option value="PN">Pitcairn</option>
                        <option value="PL">Poland</option>
                        <option value="PT">Portugal</option>
                        <option value="PR">Puerto Rico</option>
                        <option value="QA">Qatar</option>
                        <option value="RO">Romania</option>
                        <option value="RU">Russian Federation</option>
                        <option value="RW">Rwanda</option>
                        <option value="RE">Réunion</option>
                        <option value="BL">Saint Barthélemy</option>
                        <option value="SH">Saint Helena, Ascension and Tristan da Cunha</option>
                        <option value="KN">Saint Kitts and Nevis</option>
                        <option value="LC">Saint Lucia</option>
                        <option value="MF">Saint Martin (French part)</option>
                        <option value="PM">Saint Pierre and Miquelon</option>
                        <option value="VC">Saint Vincent and the Grenadines</option>
                        <option value="WS">Samoa</option>
                        <option value="SM">San Marino</option>
                        <option value="ST">Sao Tome and Principe</option>
                        <option value="SA">Saudi Arabia</option>
                        <option value="SN">Senegal</option>
                        <option value="RS">Serbia</option>
                        <option value="SC">Seychelles</option>
                        <option value="SL">Sierra Leone</option>
                        <option value="SG">Singapore</option>
                        <option value="SX">Sint Maarten (Dutch part)</option>
                        <option value="SK">Slovakia</option>
                        <option value="SI">Slovenia</option>
                        <option value="SB">Solomon Islands</option>
                        <option value="SO">Somalia</option>
                        <option value="ZA">South Africa</option>
                        <option value="GS">South Georgia and the South Sandwich Islands</option>
                        <option value="SS">South Sudan</option>
                        <option value="ES">Spain</option>
                        <option value="LK">Sri Lanka</option>
                        <option value="SD">Sudan</option>
                        <option value="SR">Suriname</option>
                        <option value="SJ">Svalbard and Jan Mayen</option>
                        <option value="SE">Sweden</option>
                        <option value="CH">Switzerland</option>
                        <option value="SY">Syrian Arab Republic</option>
                        <option value="TW">Taiwan, Province of China</option>
                        <option value="TJ">Tajikistan</option>
                        <option value="TZ">Tanzania, United Republic of</option>
                        <option value="TH">Thailand</option>
                        <option value="TL">Timor-Leste</option>
                        <option value="TG">Togo</option>
                        <option value="TK">Tokelau</option>
                        <option value="TO">Tonga</option>
                        <option value="TT">Trinidad and Tobago</option>
                        <option value="TN">Tunisia</option>
                        <option value="TR">Turkey</option>
                        <option value="TM">Turkmenistan</option>
                        <option value="TC">Turks and Caicos Islands</option>
                        <option value="TV">Tuvalu</option>
                        <option value="UG">Uganda</option>
                        <option value="UA">Ukraine</option>
                        <option value="AE">United Arab Emirates</option>
                        <option value="GB">United Kingdom</option>
                        <option value="US">United States of America</option>
                        <option value="UY">Uruguay</option>
                        <option value="UZ">Uzbekistan</option>
                        <option value="VU">Vanuatu</option>
                        <option value="VE">Venezuela (Bolivarian Republic of)</option>
                        <option value="VN">Viet Nam</option>
                        <option value="VG">Virgin Islands (British)</option>
                        <option value="VI">Virgin Islands (U.S.)</option>
                        <option value="WF">Wallis and Futuna</option>
                        <option value="EH">Western Sahara</option>
                        <option value="YE">Yemen</option>
                        <option value="ZM">Zambia</option>
                        <option value="ZW">Zimbabwe</option>
                    </select>
                </div>

                <div class="account-info-data">
                    <label for="billing_street">Street: </label>
                    <input type="text" name="billing_street" id="billing_street" class="form-control" placeholder="Street Address" required />
                </div>

                <div class="account-info-data">
                    <label for="billing_city">City: </label>
                    <input type="text" name="billing_city" id="billing_city" class="form-control" placeholder="City" required />
                </div>
                <div class="account-info-data">
                    <label for="billing_state">State/Province: </label>
                    <input type="text" name="billing_state" id="billing_state" class="form-control" placeholder="State/Province" required />
                </div>
                <div class="account-info-data">
                    <label for="billing_zip">Pin Code: </label>
                    <input type="text" name="billing_zip" id="billing_zip" class="form-control" placeholder="Zip/Postal Code" required />
                </div>
                <div class="account-info-data">
                    <label for="billing_phone">Billing Phone Number: </label>
                    <input type="tel" name="billing_phone" id="billing_phone" class="form-control" placeholder="Phone" required />
                </div>
            </div>

            <input type="hidden" name="order_id" id="order_id" />
            <input type="hidden" name="payment_id" id="payment_id" />
            <input type="hidden" name="signature" id="signature" />
            <input type="hidden" name="amount" value="<?= $total * 100 ?>" />
            <input type="hidden" name="plan" value="<?= $plan ?>" />
        </form>

        <button id="payButton" class="btn">Pay with Razorpay ! </button>
    </div>
</body>

</html>