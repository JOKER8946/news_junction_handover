<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve the POST data
    $plan = isset($_POST['plan']) ? $_POST['plan'] : 'Not specified';
    $price = isset($_POST['price']) ? $_POST['price'] : 'Not specified';
    $billing_cycle = isset($_POST['billing_cycle']) ? $_POST['billing_cycle'] : 'Not specified';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plan Details</title>
    <!-- <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f7f7f7;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        h1 {
            color: #333;
        }
        p {
            font-size: 18px;
            color: #555;
        }
    </style> -->
</head>
<body>

    <div class="container">
        <h1>Your Plan Details</h1>
        <p><strong>Selected Plan:</strong> <?php echo htmlspecialchars($plan); ?></p>
        <p><strong>Price:</strong> ₹<?php echo htmlspecialchars($price); ?></p>
        <p><strong>Billing Cycle:</strong> <?php echo htmlspecialchars($billing_cycle); ?></p>
    </div>

</body>
</html>
