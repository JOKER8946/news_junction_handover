<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scrolling Text with Profile</title>
    <style>
        .tubecards {
            display: flex;
            width: 100%;
            justify-content: space-around;
            background-color: transparent;
            padding: 10px 10px;
            gap: 10px;
            overflow-x: hidden ;
        
        }

        .custom-scroll-container {
            display: flex;
            align-items: center;
            background-color: rgb(31, 123, 139);
            color: white;
            border-radius: 50px;
            padding: 15px 10px 15px 60px;
            min-width: 300px;
            position: relative;
            overflow: hidden;
            animation: slide-animation 10s linear infinite;
            /* Ensure text is clipped when over profile */
        }

        .custom-profile {
            width: 80px;
            height: 40px;
            border-radius: 20PX;
            border: 1px solid white;
            margin-right: 10px;
            position: absolute;
            /* Position the profile over the text */
            left: 10px;
            /* Adjust as needed */
            z-index: 2;
            /* Ensures profile is on top */
        }

        .custom-scroll-text-container {
            display: flex;
            align-items: center;
            width: 100%;
            padding-left: 50px;
            /* To leave space for the profile image */
            overflow: hidden;
            /* Hide text overflow where it overlaps with profile */
        }

        .custom-scroll-text {
            white-space: nowrap;
            font-size: 18px;
            font-weight: bold;
            color: white;
            animation: scroll-animation 8s linear infinite;
        }

        @keyframes scroll-animation {
            from {
                transform: translateX(100%);
            }

            to {
                transform: translateX(-100%);
            }
        }

        @keyframes slide-animation {
            from {
                transform: translateX(0);
            }

            to {
                transform: translateX(-100%);
            }
        }
    </style>
</head>

<body>
<div class="tubecards">
    <div class="custom-scroll-container" onclick="window.location.href='https://emergestartupfest.com/'">
        <div class="custom-scroll-text-container">
            <img src="assets/img/emerge-logo.jpg" class="custom-profile" alt="Profile Picture">
            <div class="custom-scroll-text">EMERGE 2025</div>
        </div>
    </div>
    <div class="custom-scroll-container" onclick="window.location.href='https://emergestartupfest.com/'">
        <div class="custom-scroll-text-container">
            <img src="assets/img/emerge-logo.jpg" class="custom-profile" alt="Profile Picture">
            <div class="custom-scroll-text">EMERGE 2025</div>
        </div>
    </div>
    <div class="custom-scroll-container" onclick="window.location.href='https://emergestartupfest.com/'">
        <div class="custom-scroll-text-container">
            <img src="assets/img/emerge-logo.jpg" class="custom-profile" alt="Profile Picture">
            <div class="custom-scroll-text">EMERGE 2025</div>
        </div>
    </div>
    <div class="custom-scroll-container" onclick="window.location.href='https://emergestartupfest.com/'">
        <div class="custom-scroll-text-container">
            <img src="assets/img/emerge-logo.jpg" class="custom-profile" alt="Profile Picture">
            <div class="custom-scroll-text">EMERGE 2025</div>
        </div>
    </div>
</div>

</body>

</html>
