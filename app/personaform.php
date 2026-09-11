<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CreamNow - Market Plan Insights</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #e6f7ff;
            /* Light blue background */
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .container {
            background-color: #ffffff;
            padding: 50px;
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
            width: 600px;
            /* Increased width */
            max-width: 95%;
            text-align: left;
            /* Align form elements to the left */
        }

        h2 {
            text-align: center;
            color: #2e6da4;
            /* Blue heading color */
            margin-bottom: 40px;
            font-size: 28px;
            /* Increased font size */
        }

        .form-group {
            margin-bottom: 25px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
        }

        input[type="text"],
        input[type="email"],
        textarea {
            width: calc(100% - 22px);
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            box-sizing: border-box;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }

        textarea {
            height: 150px;
            resize: vertical;
        }

        input[type="text"]:focus,
        input[type="email"]:focus,
        textarea:focus {
            border-color: #4d90fe;
            outline: none;
            box-shadow: 0 0 8px rgba(77, 144, 254, 0.5);
        }

        button {
            background-color: #4d90fe;
            color: white;
            padding: 14px 25px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 18px;
            width: 100%;
            transition: background-color 0.3s ease;
        }

        button:hover {
            background-color: #357ae8;
        }

        /* Improved clarity for form fields */
        input::placeholder,
        textarea::placeholder {
            color: #aaa;
            font-style: italic;
        }
    </style>
</head>

<body>
    <div class="container">
        <h2>CreamNow - Market Plan Insights</h2>
        <form action="persona_submit.php" method="POST">
            <div class="form-group">
                <label for="name">Your Name:</label>
                <input type="text" name="name" id="name" placeholder="Enter your full name" required>
            </div>
            <div class="form-group">
                <label for="email">Your Email:</label>
                <input type="email" name="email" id="email" placeholder="Enter your email address" required>
            </div>
            <div class="form-group">
                <label for="industry">Industry Sector:</label>
                <select name="industry" id="industry" required>
                    <option value="">Select Industry</option>
                    <option value="SaaS">SaaS</option>
                    <option value="Retail">Retail</option>
                    <option value="Technology">Technology</option>
                    <option value="Finance">Finance</option>
                    <option value="Healthcare">Healthcare</ <option value="Manufacturing">Manufacturing</option>
                    <option value="Education">Education</option>
                    <option value="Construction">Construction</option>
                    <option value="Transportation">Transportation</option>
                    <option value="Energy">Energy</option>
                    <option value="Agriculture">Agriculture</option>
                    <option value="Media">Media</option>
                    <option value="Telecommunications">Telecommunications</option>
                    <option value="Real Estate">Real Estate</option>
                    <option value="Hospitality">Hospitality</option>
                    <option value="Pharmaceuticals">Pharmaceuticals</option>
                    <option value="Automotive">Automotive</option>
                    <option value="Aerospace">Aerospace</option>
                    <option value="Chemical">Chemical</option>
                    <option value="Mining">Mining</option>
                    <option value="Insurance">Insurance</option>
                    <option value="Consulting">Consulting</option>
                    <option value="Logistics">Logistics</option>
                    <option value="Utilities">Utilities</option>
                    <option value="Textiles">Textiles</option>
                    <option value="Other">Other</option>
                </select>
            </div>
            <div class="form-group">
                <label for="product">Product Name:</label>
                <input type="text" name="product" id="product" placeholder="Enter your product or service name"
                    required>
            </div>
            <div class="form-group">
                <label for="goals">Market Goals:</label>
                <input type="text" name="goals" id="goals"
                    placeholder="e.g., Increase market share, expand customer base" required>
            </div>
            <div class="form-group">
                <label for="challenges">Challenges:</label>
                <textarea name="challenges" id="challenges" placeholder="List current market challenges"
                    required></textarea>
            </div>
            <div class="form-group">
                <label for="value">Differentiating Value:</label>
                <input type="text" name="value" id="value" placeholder="What makes your product unique?" required>
            </div>
            <div class="form-group">
                <label for="conversation">Primary target audience</label>
                <textarea name="conversation" id="conversation" placeholder="Describe ideal target audience"
                    required></textarea>
            </div>
            <div class="form-group">
                <label for="assets">Existing Assets:</label>
                <input type="text" name="assets" id="assets"
                    placeholder="e.g., Website, social media presence, partnerships" required>
            </div>
            <div class="form-group">
                <label for="social_media">Social Media Channels:</label>
                <input type="text" name="social_media" id="social_media" placeholder="List used social media platforms"
                    required>
            </div>
            <button type="submit">Generate Market Plan</button>
        </form>
    </div>
</body>

</html>