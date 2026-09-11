<? require_once('assets/php/validate.logged-status.php') ?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Premium Plans</title>
    <!-- <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/stream.css"> -->
    <style>
        /* Global Styles */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #121212;
            /* Dark background */
            color: #e1e1e1;
            /* Light text for readability */
        }

        header {
            /* background-color: #1f1f1f; */
            /* Darker shade for header */
            position: relative;
            top: 30px;
            color: white;
            padding: 26px 32px;
            text-align: center;
            position: relative;
            /* To position the close button */
        }

        .close-btn {
            position: absolute;
            top: 36px;
            left: 46px;
            background: none;
            border: none;
            color: #e1e1e1;
            /* Light text for contrast */
            font-size: 24px;
            cursor: pointer;
        }

        header .close-btn:hover {
            color: #b0b0b0;
            /* Light gray on hover */
        }

        h1 {
            font-size: 36px;
            margin: 0;
            font-weight: 600;
        }

        /* Top Text Section */
        .top-text {

            padding: 24px;
            text-align: center;
            color: #e1e1e1;
            /* font-size: 48px; */
            margin-top: 16px;
        }

        .top-text h2 {
            font-size: 64px;
            margin-top: 1px;
            margin-bottom: 18px;
            font-weight: 700;
        }

        .top-text p {
            margin-bottom: 22px;
            font-size: 22px;
        }

        /* Toggle Buttons */
        .toggle-container {
            margin-top: 24px;
            display: flex;
            justify-content: center;
            gap: 20px;
        }

        .toggle-btn {
            background-color: #333;
            color: #e1e1e1;
            border: none;
            padding: 12px 24px;
            font-size: 16px;
            cursor: pointer;
            border-radius: 30px;
            transition: background-color 0.3s ease;
        }

        .toggle-btn:hover {
            background-color: #444;
        }

        .toggle-btn.active {
            background-color: #db5919;
            /* Active state of button */
        }


        /* Plan Container */
        .plans-container {
            display: flex;
            justify-content: center;
            gap: 20px;
            padding: 50px 20px;
            flex-wrap: wrap;
        }

        .plan {
            background-color: #1f1f1f;
            /* Dark card background */
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4);
            width: 280px;
            padding: 24px;
            text-align: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .plan:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.6);
        }

        .plan h2 {
            font-size: 28px;
            font-weight: 700;
            color: #e1e1e1;
            /* Light text for headings */
            margin: 16px 0;
        }

        .plan .price {
            font-size: 32px;
            color: #e1e1e1;
            /* Light price text */
            font-weight: 600;
            margin-bottom: 16px;
        }

        .plan .duration {
            font-size: 18px;
            color: #b0b0b0;
            /* Light gray for subtle text */
            margin-bottom: 24px;
        }

        .plan ul {
            list-style: none;
            padding: 0;
            margin: 0;
            font-size: 16px;
            color: #e1e1e1;
            /* Light text for readability */
        }

        .plan ul li {
            margin-bottom: 12px;
        }

        .plan .cta {
            background-color: #333333;
            /* Dark button color */
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 30px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s ease;
            width: 100%;
            margin-top: 20px;
        }

        .plan .cta:hover {
            background-color: #444444;
            /* Darker shade on hover */
        }

        /* Features Table */
        .features-table {
            display: none;
            width: 90%;
            max-width: 1000px;
            margin: 40px auto;
            border-collapse: collapse;
            text-align: center;
        }

        .features-table th,
        .features-table td {
            padding: 16px;
            border: 1px solid #333;
            /* Darker borders */
        }

        .features-table th {
            background-color: #1f1f1f;
            /* Dark header background */
            font-weight: 600;
            color: #e1e1e1;
            /* Light text for table header */
        }

        .features-table td {
            background-color: #2c2c2c;
            /* Dark background for table rows */
            font-size: 16px;
            color: #e1e1e1;
            /* Light text for readability */
        }

        /* Footer */
        footer {
            text-align: center;
            padding: 16px;
            background-color: #1f1f1f;
            /* Dark footer */
            color: #b0b0b0;
            /* Light gray text */
            font-size: 14px;
            position: relative;
            bottom: 0;
            width: 100%;
        }
    </style>
    <style>
        .scroll-container {
            width: 100%;
            height: auto;
            margin: 30px 0;
            /* Adjust height as needed */
            overflow: hidden;
            position: relative;
        }

        .card-wrapper {
            display: flex;
            width: max-content;
            /* Adjusts based on content width */
            animation: scrollImages 30s linear infinite;
        }

        .card {
            width: 200px;
            height: auto;
            margin-right: 20px;
            background-color: #ccc;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .card img {
            width: 100%;
            height: auto;
            object-fit: cover;
            border-radius: 10px;
        }

        @keyframes scrollImages {
            0% {
                transform: translateX(0);
            }

            100% {
                transform: translateX(-50%);
                /* Moves by half the width of all the images combined */
            }
        }
    </style>
    <style>
        .plan {
            border: 1px solid #ddd;
            padding: 20px;
            max-width: 300px;
            margin: 10px;
            text-align: center;
        }

        .tier-selector {
            margin-bottom: 15px;
        }

        .features {
            text-align: left;
            margin-bottom: 15px;
        }

        .features li {
            margin-bottom: 5px;
        }

        .cta {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
        }
    </style>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
        $(document).ready(function() {
            $('#btn-compare-plan').on('click', function() {
                var table = $('.features-table');

                // Check if the table is currently hidden
                if (table.css('display') === 'none') {
                    // If hidden, set it to block (make it visible)
                    table.css('display', 'table');
                } else {
                    // If visible, set it to none (hide it)
                    table.css('display', 'none');
                }
            });
        });
    </script>
    <script>
        // This script duplicates the set of images for continuous scrolling effect
        const wrapper = document.querySelector('.card-wrapper');
        const cards = document.querySelectorAll('.card');

        // Clone the cards and append them for continuous scrolling effect
        cards.forEach(card => {
            const clone = card.cloneNode(true);
            wrapper.appendChild(clone);
        });
    </script>

</head>

<body>


    <header>
        <img src="inc/img/knobly_logo.png" alt="" width="200px">
    </header>
    <button class="close-btn" onclick="window.history.back();">X</button> <!-- Close button -->

    <!-- Top Section Text -->
    <div class="top-text">
        <h2>Upgrade to Premium</h2>
        <p>Enjoy an enhanced experience, exclusive creator tools, top-tier verification and security.</p>
        <p><strong><a style="color:#ffffff" href="sign-in.php?type=login">Sign Up</a></strong>
        </p>
    </div>

    <!-- Toggle Buttons to Switch Between Monthly and Yearly Plans -->
    <div class="toggle-container">
        <button class="toggle-btn active" id="monthly-toggle" onclick="togglePlans('monthly')">Monthly Plans</button>
        <button class="toggle-btn" id="yearly-toggle" onclick="togglePlans('yearly')">Yearly Plans</button>
    </div>

    <!-- Plan Container -->
    <div class="plans-container" id="plans-container">
        <div class="plan" data-plan="monthly">
            <h2>Free</h2>
            <div class="price">&#x20b9;0/Month</div>
            <!-- <div class="duration">Billed Monthly</div> -->
            <ul>
                <li>Access to all basic features</li>
                <li>Social Media</li>
                <li></li>
                <li></li>
                <li></li>
                <li></li>
            </ul>
        </div>

        <div class="plan" data-plan="monthly">
            <h2>Cream Xpress</h2>

            <div class="tier-selector">
                <label for="tier-select">Select Tier:</label>
                <select id="tier-select" onchange="updatePlanDetails()">
                    <option value="tier1">Tier 1</option>
                    <option value="tier2">Tier 2</option>
                    <option value="tier3">Tier 3</option>
                </select>
            </div>

            <div class="price" id="price-display">&#x20b9;1,600/Month</div>

            <ul class="features" id="features-list">
                <li>Upto 20k emails</li>
                <li>24/7 premium support</li>
                <li>Priority access to new features</li>
                <li>Omni Analytics</li>
            </ul>

            <? if ($gLogStatus) { ?>
                <form id="subscription-form" action="payment.php" method="POST" target="_blank">
                    <input type="hidden" name="plan" value="Monthly Subscription">
                    <input type="hidden" name="planName" id="plan-name" value="xpressT1">
                    <input type="hidden" name="amount" id="plan-amount" value="1600">
                    <button type="submit" class="cta">Select Plan</button>
                </form>
            <? } else { ?>
                <button class="cta" onclick="window.location.href='sign-in.php'">Sign Up Now</button>
            <? } ?>
        </div>
        <script>
            const tierDetails = {
                tier1: {
                    price: 1600,
                    planName:"xpressT1",
                    features: [
                        'Upto 20k emails',
                        '24/7 premium support',
                        'Priority access to new features',
                        'Omni Analytics'
                    ]
                },
                tier2: {
                    price: 3200,
                    planName:"xpressT2",
                    features: [
                        '20k to 100k emails',
                        '24/7 premium support',
                        'Priority access to new features',
                        'Omni Analytics'
                    ]
                },
                tier3: {
                    price: 6000,
                    planName:"xpressT3",
                    features: [
                        '100k to 250k emails',
                        '24/7 premium support',
                        'Priority access to new features',
                        'Omni Analytics'
                    ]
                }
            };

            function updatePlanDetails() {
                const tierSelect = document.getElementById('tier-select');
                const selectedTier = tierSelect.value;
                const priceDisplay = document.getElementById('price-display');
                const featuresList = document.getElementById('features-list');
                const planAmountInput = document.getElementById('plan-amount');
                const planNameInput = document.getElementById('plan-name');

                // Update price
                priceDisplay.innerHTML = `&#x20b9;${tierDetails[selectedTier].price.toLocaleString()}/Month`;

                // Update features
                featuresList.innerHTML = tierDetails[selectedTier].features
                    .map(feature => `<li>${feature}</li>`)
                    .join('');

                // Update hidden input for form submission
                planNameInput.value=tierDetails[selectedTier].planName
                planAmountInput.value = tierDetails[selectedTier].price;
            }
        </script>


        <div class="plan" data-plan="monthly">
            <h2>Cream Creator</h2>
            <div class="price">&#x20b9;2,100/Month</div>
            <!-- <div class="duration">Billed Monthly</div> -->
            <ul>
                <li>Unlimited access to all features</li>
                <li>24/7 premium support</li>
                <li>Priority access to new features</li>
                <li>Omni Analytics</li>
                <li>Omni Analytics</li>
            </ul>
            <? if ($gLogStatus) { ?>
                <form action="payment.php" method="POST" target="_blank">
                    <input type="hidden" name="plan" value="Monthly Subscription">
                    <input type="hidden" name="planName" value="creator">
                    <input type="hidden" name="amount" value="2100">
                    <button type="submit" class="cta">Select Plan</button>
                </form>
            <? } else { ?>
                <button class="cta" onclick="window.location.href='sign-in.php'">Sign Up Now</button>
            <? } ?>
        </div>
        <div class="plan" data-plan="monthly">
            <h2>Cream Pro</h2>
            <div class="price">&#x20b9;5,500/Month</div>
            <!-- <div class="duration">Billed Monthly</div> -->
            <ul>
                <li>Unlimited access to all features</li>
                <li>24/7 premium support</li>
                <li>Priority access to new features</li>
                <li>Omni Analytics</li>
            </ul>
            <? if ($gLogStatus) { ?>
                <form action="payment.php" method="POST" target="_blank">
                    <input type="hidden" name="plan" value="Monthly Subscription">
                    <input type="hidden" name="planName" value="pro">
                    <input type="hidden" name="amount" value="5500">
                    <button type="submit" class="cta">Select Plan</button>
                </form>
            <? } else { ?>
                <button class="cta" onclick="window.location.href='sign-in.php'">Sign Up Now</button>
            <? } ?>
        </div>
        <div class="plan" data-plan="monthly">
            <h2>Cream Enterprise</h2>
            <div class="price">&#x20b9;45,000/Month</div>
            <ul>
                <li>Unlimited access to all features</li>
                <li>24/7 premium support</li>
                <li>Priority access to new features</li>
                <li>Omni Analytics</li>
            </ul>
            <? if ($gLogStatus) { ?>
                <form action="payment.php" method="POST" target="_blank">
                    <input type="hidden" name="plan" value="Monthly Subscription">
                    <input type="hidden" name="amount" value="45000">
                    <a href="mailto:pulsemediafriends@gmail.com">
                        <button type="button" class="cta">Contact Support Team</button>
                    </a>
                </form>
            <? } else { ?>
                <button class="cta" onclick="window.location.href='sign-in.php'">Sign Up Now</button>
            <? } ?>
        </div>

        <!-- Yearly Plan Example -->

        <div class="plan" data-plan="yearly" style="display: none;">
            <h2>Free</h2>
            <div class="price">&#x20b9;0/Year</div>
            <!-- <div class="duration">Billed Yearly</div> -->
            <ul>
                <li>Access to all basic features</li>
                <li>Social Media</li>
                <li>Reader</li>
                <li>Explore Newsletter for 30 days</li>
                <li>Basic Analytics</li>
            </ul>
        </div>

        <div class="plan" data-plan="yearly" style="display: none;">
            <h2>Cream Social</h2>
            <div class="price">&#x20b9;6,000/Year</div>
            <!-- <div class="duration">Billed Yearly</div> -->
            <ul>
                <li>Ad free</li>
                <li>Blue tick</li>
                <li>Make Channels</li>
                <li>Exclusive long form reading</li>
            </ul>
            <? if ($gLogStatus) { ?>
                <form action="payment.php" method="POST" target="_blank">
                    <input type="hidden" name="plan" value="Annual Subscription">
                    <input type="hidden" name="amount" value="6000">
                    <button type="submit" class="cta">Select Plan</button>
                </form>
            <? } else { ?>
                <button class="cta" onclick="window.location.href='sign-in.php'">Sign Up Now</button>
            <? } ?>
        </div>

        <div class="plan" data-plan="yearly" style="display: none;">
            <h2>Cream Xpress</h2>
            <div class="price">&#x20b9;36,000/Year</div>
            <!-- <div class="duration">Billed Yearly</div> -->
            <ul>
                <li>Unlimited access to all features</li>
                <li>24/7 premium support</li>
                <li>Priority access to new features</li>
                <li>Omni Analytics</li>
            </ul>
            <? if ($gLogStatus) { ?>
                <form action="payment.php" method="POST" target="_blank">
                    <input type="hidden" name="plan" value="Annual Subscription">
                    <input type="hidden" name="amount" value="36000">
                    <button type="submit" class="cta">Select Plan</button>
                </form>
            <? } else { ?>
                <button class="cta" onclick="window.location.href='sign-in.php'">Sign Up Now</button>
            <? } ?>

        </div>
        <div class="plan" data-plan="yearly" style="display: none;">
            <h2>Cream Creator</h2>
            <div class="price">&#x20b9;24,000/Year</div>
            <!-- <div class="duration">Billed Yearly</div> -->
            <ul>
                <li>Unlimited access to all features</li>
                <li>24/7 premium support</li>
                <li>Priority access to new features</li>
                <li>Omni Analytics</li>
            </ul>
            <? if ($gLogStatus) { ?>
                <form action="payment.php" method="POST" target="_blank">
                    <input type="hidden" name="plan" value="Annual Subscription">
                    <input type="hidden" name="amount" value="24000">
                    <button type="submit" class="cta">Select Plan</button>
                </form>
            <? } else { ?>
                <button class="cta" onclick="window.location.href='sign-in.php'">Sign Up Now</button>
            <? } ?>

        </div>

        <div class="plan" data-plan="yearly" style="display: none;">
            <h2>Cream Pro</h2>
            <div class="price">&#x20b9;60,000/Year</div>
            <!-- <div class="duration">Billed Yearly</div> -->
            <ul>
                <li>Unlimited access to all features</li>
                <li>24/7 premium support</li>
                <li>Priority access to new features</li>
                <li>Omni Analytics</li>
            </ul>
            <? if ($gLogStatus) { ?>
                <form action="payment.php" method="POST" target="_blank">
                    <input type="hidden" name="plan" value="Annual Subscription">
                    <input type="hidden" name="amount" value="60000">
                    <button type="submit" class="cta">Select Plan</button>
                </form>
            <? } else { ?>
                <button class="cta" onclick="window.location.href='sign-in.php'">Sign Up Now</button>
            <? } ?>
        </div>
        <div class="plan" data-plan="yearly" style="display: none;">
            <h2>Cream Enterprise</h2>
            <div class="price">&#x20b9;5,00,000/Year</div>
            <!-- <div class="duration">Billed Yearly</div> -->
            <ul>
                <li>Unlimited access to all features</li>
                <li>24/7 premium support</li>
                <li>Priority access to new features</li>
                <li>Omni Analytics</li>
            </ul>
            <? if ($gLogStatus) { ?>
                <form action="payment.php" method="POST" target="_blank">
                    <input type="hidden" name="plan" value="Annual Subscription">
                    <input type="hidden" name="amount" value="24000">
                    <a href="mailto:pulsemediafriends@gmail.com">
                        <button type="button" class="cta">Contact Support Team</button>
                    </a>
                </form>
            <? } else { ?>
                <button class="cta" onclick="window.location.href='sign-in.php'">Sign Up Now</button>
            <? } ?>
        </div>
    </div>

    <div style="display:flex; justify-content:center; padding-bottom:30px;">
        <button class="toggle-btn" id="btn-compare-plan" style="background-color: #db5919;">Compare Plans</button>
    </div>


    <!-- Features Comparison Table -->
    <table class="features-table">
        <thead>
            <tr>
                <th>Features</th>
                <th>Free</th>
                <th>Cream Pro <br>
                    INR 2000/month OR INR 18,000 Paid Annually

                </th>
                <!-- <th>Passport</th> -->
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Cream Curated RSS Feeds </td>
                <td>✔️</td>
                <td>✔️</td>
                <!-- <td>✔️</td> -->
            </tr>
            <tr>
                <td>
                    Your own RSS Feeds
                </td>
                <td>✔️(Max=5)</td>
                <td>✔️</td>
                <!-- <td>✔️</td> -->
            </tr>
            <tr>
                <td>Access to Cream Social</td>
                <td>✔️</td>
                <td>✔️</td>
                <!-- <td>✔️</td> -->
            </tr>
            <tr>
                <td>Social Share Scheduling</td>
                <td>❌</td>
                <td>✔️</td>
                <!-- <td>✔️</td> -->
            </tr>
            <tr>
                <td>Create Blog</td>
                <td>✔️</td>
                <td>✔️</td>
                <!-- <td>✔️</td> -->
            </tr>
            <tr>
                <td>Create Landing Page with CTA button</td>
                <td>❌</td>
                <td>✔️</td>
                <!-- <td>✔️</td> -->
            </tr>

            <tr>
                <td>Capture Lead from Landing Page</td>
                <td>❌</td>
                <td>✔️</td>
                <!-- <td>✔️</td> -->
            </tr>
            <tr>
                <td>
                    Newsletter
                </td>
                <td>✔️(30 Days)</td>
                <td>✔️</td>
                <!-- <td>✔️</td> -->
            </tr>
            <tr>
                <td>Newsletter Sending From within Cream</td>
                <td>❌</td>
                <td>✔️</td>
                <!-- <td>✔️</td> -->
            </tr>
            <tr>
                <td>Analytics</td>
                <td>✔️</td>
                <td>✔️</td>
                <!-- <td>✔️</td> -->
            </tr>
            <tr>
                <td>Analytics Drilldown to Location </td>
                <td>❌</td>
                <td>✔️</td>
                <!-- <td>✔️</td> -->
            </tr>
            <tr>
                <td>Subdomain xyz.newsjunction.net</td>
                <td>❌</td>
                <td>✔️</td>
                <!-- <td>✔️</td> -->
            </tr>
            <tr>
                <td>Mapping knobly view pages to your domain</td>
                <td>❌</td>
                <td>✔️</td>
                <!-- <td>✔️</td> -->
            </tr>

        </tbody>
    </table>

    <div class="scroll-container">
        <div class="card-wrapper">
            <!-- Image cards (20 in total) -->
            <div class="card"><img src="inc/img/clients_logo/scogen.jpg    " alt="Image 1"></div>
            <div class="card"><img src="inc/img/clients_logo/kasapa.jpg" alt="Image 2"></div>
            <div class="card"><img src="inc/img/clients_logo/bizproutx.jpg" alt="Image 3"></div>
            <div class="card"><img src="inc/img/clients_logo/bizprout.jpg" alt="Image 4"></div>
            <div class="card"><img src="inc/img/clients_logo/able.jpg" alt="Image 5"></div>
            <div class="card"><img src="inc/img/clients_logo/accs.jpg" alt="Image 6"></div>
            <div class="card"><img src="inc/img/clients_logo/iitb.jpg" alt="Image 7"></div>
            <div class="card"><img src="inc/img/clients_logo/esamudaay.jpg" alt="Image 8"></div>
            <div class="card"><img src="inc/img/clients_logo/kle.jpg" alt="Image 9"></div>
            <div class="card"><img src="inc/img/clients_logo/mashini.jpg" alt="Image 10"></div>
            <div class="card"><img src="inc/img/clients_logo/bdcc.jpg" alt="Image 11"></div>
            <div class="card"><img src="inc/img/clients_logo/devigere.jpg" alt="Image 12"></div>
            <div class="card"><img src="inc/img/clients_logo/ctrl.jpg" alt="Image 13"></div>
            <div class="card"><img src="inc/img/clients_logo/aadhya.jpg" alt="Image 14"></div>
            <div class="card"><img src="inc/img/clients_logo/thermog.jpg" alt="Image 15"></div>
            <div class="card"><img src="inc/img/clients_logo/nextelement.jpg" alt="Image 16"></div>
            <div class="card"><img src="inc/img/clients_logo/assuredstate.jpg" alt="Image 17"></div>
            <!-- <div class="card"><img src="inc/img/clients_logo/" alt="Image 18"></div>
            <div class="card"><img src="inc/img/clients_logo/" alt="Image 19"></div>
            <div class="card"><img src="inc/img/clients_logo/" alt="Image 20"></div> -->
        </div>
    </div>

    <footer>
        Powered by Knobly Consulting </p>
    </footer>

    <script>
        // Toggle between monthly and yearly plans
        function togglePlans(planType) {
            // Reset active state on buttons
            document.querySelectorAll('.toggle-btn').forEach(button => {
                button.classList.remove('active');
            });
            // Set the active state to the clicked button
            document.getElementById(`${planType}-toggle`).classList.add('active');

            // Toggle visibility of plans
            const plans = document.querySelectorAll('.plan');
            plans.forEach(plan => {
                if (plan.getAttribute('data-plan') === planType) {
                    plan.style.display = 'block';
                } else {
                    plan.style.display = 'none';
                }
            });
        }

        // Default to showing monthly plans
        togglePlans('monthly');
    </script>

</body>

</html>