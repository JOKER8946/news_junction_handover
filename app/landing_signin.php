
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>News Junction</title>


    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.1/aos.css">
    <link rel="stylesheet" href="assets/css/cream.css">

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.1/aos.js"></script>
    <script>
        $(document).ready(function () {
            // Initialize AOS after DOM is loaded
            AOS.init();

            const images = [
                'inc/img/home_screen1.jpg',
                'inc/img/home_screen2.jpg',
                'inc/img/home_screen3.jpg',
                'inc/img/home1.webp',
                'inc/img/home2.webp',
                'inc/img/home3.webp'
            ];

            // Get the current index from localStorage, or start at 0
            let currentIndex = parseInt(localStorage.getItem('bgIndex')) || 0;

            function changeBackground() {
                // Set the current background image
                $('.index-background').css('background-image', `url(${images[currentIndex]})`);

                // Update the index for the next load
                currentIndex = (currentIndex + 1) % images.length; // Loop back to the start

                // Save the current index in localStorage
                localStorage.setItem('bgIndex', currentIndex);
            }

            changeBackground(); // Set initial background
            setInterval(changeBackground, 20000000); // Change image every 2 seconds (Note: You might want to adjust this interval time)

            // Check for the presence of the 'knobly_user_data' cookie
            if (checkCookie('knobly_user_data')) {
                window.location.href = '/stream.php';
            } else {
                console.log('No Cookies found');
            }

            // Function to check for the presence of the 'knobly_user_data' cookie
            function checkCookie(cookieName) {
                var cookies = document.cookie;
                return cookies.split(';').some(function (cookie) {
                    return $.trim(cookie).startsWith(cookieName + '=');
                });
            }
        });
    </script>
    <link href="assets/css/main.css" rel="stylesheet">
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/vendor/aos/aos.css" rel="stylesheet">
    <link href="assets/vendor/glightbox/css/glightbox.min.css" rel="stylesheet">
    <link href="assets/vendor/swiper/swiper-bundle.min.css" rel="stylesheet">
</head>
<style>
    .about-images{
        display: flex;
        align-items: center;
    }
</style>

<body style="background-color:#000000;">
    <!-- Hero Section -->
    <section id="hero" class="hero section" style="background-color:#000000;">

        <div class="container text-center">
            <section id="about" class="about section" style="background-color:#000000; margin-top:-200px;">

                <div class="container">

                    <div class="row gy-4">

                        <div class="col-lg-5 about-images" data-aos="fade-up" data-aos-delay="200">
                            <div class="tab-pane fade active show" id="features-tab-1" align="center">
                                <img vlign="middle" src="inc/img/knobly_logo.png" alt="" class="img-fluid">
                            </div>

                        </div>

                        <div class="col-lg-7 content" data-aos="fade-up" data-aos-delay="100">
                            <h1 data-aos="fade-up" style="color:#FFFFFF;">Join</h1>
                            <div class="buttons">
                                <a href="#" class="button"><img src="assets/services/google.png"/> Sign up with Google</a>
                                <a href="#" class="button"><img src="assets/services/apple.png"/> Sign up with Apple</a>
                                <div align="center" style="color:#FFFFFF;">or</div>
                                <a href="#" class="button blue">Create account</a>
                                <div style="color:#ffffff; font-size:10px; margin-top:-5px;">By signing up, you agree to
                                    the <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a>, including
                                    <a href="#">Cookie Use</a>.</div>
                                <p style="color:#FFFFFF; margin-top:30px;">Already have an account?</p>
                                <a href="#" class=" signin">Create account</a>
                            </div>

                        </div>

                    </div>

                </div>
            </section>

        </div>

    </section><!-- /Hero Section -->


    <?php include 'assets/php/indexFooter.php' ?>
</body>

</html>
