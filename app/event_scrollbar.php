<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="inc/css/social.css">

    <title>Scrolling Text with Profile</title>
    <style>
        .tubecards {
            display: flex;
            width: 100%;
            justify-content: space-around;
            background-color: transparent;
            gap: 10px;
            overflow-x: hidden;

        }

        #cards-container {

            display: flex;
            gap: 4px;
            overflow-x: hidden;
        }

        .custom-scroll-container {
            display: flex;
            align-items: center;
            background-color: var(--bg-card);
            color: var(--text-primary);
            border-radius: 50px;
            padding: 15px 10px 15px 60px;
            min-width: 300px;
            position: relative;
            overflow: hidden;
            animation: slide-animation 10s linear infinite;
            /* Ensure text is clipped when over profile */
        }

        .custom-scroll-text {
            white-space: nowrap;
            font-size: 18px;
            font-weight: bold;
            animation: scroll-animation 17s linear infinite;
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
            /* margin-left: 33px; */
            /* To leave space for the profile image */
            /* overflow: hidden; */
            /* Hide text overflow where it overlaps with profile */
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
    <style>
        /* Default styling for large screens */
        .custom-scroll-container {
            display: block;
            margin: 10px;
        }

        /* Responsive layout for small screens */
        @media (max-width: 768px) {
            .custom-scroll-container {
                display: inline-block;
                width: 23%;
                /* Show 4 items side by side with a bit of margin */
                margin: 1%;
            }

        }

        /* Responsive layout for larger screens */
        @media (min-width: 769px) {
            .custom-scroll-container {
                display: block;
                width: 100%;
                /* Show 1 item per row */
                margin: 10px 0;
            }

            .main_screen {
                width: 840px;
            }

            .custom-scroll-container {
                display: block;
                width: 190px;
                margin: 10px 0;
            }

            #cards-container {

                display: flex;
                gap: 10px;
                overflow-x: hidden;
            }
        }
    </style>
</head>

<body>
    <div class="tubecards">
        <div class="main_screen">
            <!-- This loop will generate 4 divs for small screens -->
            <div id="cards-container"></div>
        </div>
    </div>

    <script>
        // JavaScript to dynamically generate 4 cards
        const container = document.getElementById('cards-container');
        for (let i = 0; i < 3; i++) {
            const card = document.createElement('div');
            card.classList.add('custom-scroll-container');
            card.setAttribute('onclick', "window.location.href='https://docs.google.com/forms/d/e/1FAIpQLSdmsbW2OtKumyb3a47G_lDQqq0N8vyokWSOLVqY2Wkn0oCedQ/viewform'");

            const cardContent = `
                <div class="custom-scroll-text-container">
                    <div class="custom-scroll-text">Upload Your Details for ‘News Junction Vipra Information Directory – 2026’</div>
                </div>
            `;
            card.innerHTML = cardContent;
            container.appendChild(card);
        }
    </script>
</body>


</html>