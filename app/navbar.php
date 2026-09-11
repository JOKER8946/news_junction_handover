<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stylish Transparent Navbar</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }


        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            padding: 20px 30px;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(15px);
            /* border-radius: 10px; */
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: background 0.3s ease;
        }

        .navbar a {
            color: white;
            text-decoration: none;
            padding: 12px 18px;
            border-radius: 5px;
            transition: background 0.3s, color 0.3s;
        }

        .navbar a:hover {
            background:rgb(29, 65, 90);
            color: #fff;
        }

        .logo {
            font-size: 30px;
            font-weight: 600;
            color: #fff;
            letter-spacing: 1px;
        }

        .menu {
            display: flex;
            list-style: none;
        }

        .menu li {
            margin: 0 15px;
        }

        .menu-toggle {
            display: none;
            cursor: pointer;
            font-size: 30px;
            color: #fff;
        }

        @media (max-width: 768px) {
            .menu {
                display: none;
                flex-direction: column;
                width: 100%;
                position: absolute;
                top: 60px;
                left: 0;
                text-align: center;
                background: rgba(0, 0, 0, 0.8);
                border-radius: 10px;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
            }

            .menu.active {
                display: flex;
            }

            .menu li {
                padding: 12px 0;
            }

            .menu-toggle {
                display: block;
            }

            .menu li a {
                padding: 10px 15px;
                border-radius: 5px;
                color: #fff;
                transition: background 0.3s;
            }

            .menu li a:hover {
                background: #3498db;
            }
        }
    </style>
</head>

<body>

    <nav class="navbar">
        <a href="#" class="logo">Articles</a>
        <ul class="menu">
            <li><a href="">Home</a></li>
            <li><a href="">About</a></li>
            <li><a href="">Services</a></li>
            <li><a href="">Login</a></li>
        </ul>
        <span class="menu-toggle">&#9776;</span>
    </nav>

    <script>
        const menuToggle = document.querySelector('.menu-toggle');
        const menu = document.querySelector('.menu');

        menuToggle.addEventListener('click', () => {
            menu.classList.toggle('active');
        });
    </script>

</body>

</html>
