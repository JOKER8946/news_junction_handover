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
    $(document).ready(function() {
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
        return cookies.split(';').some(function(cookie) {
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
  .index-story {
    position: absolute;
    bottom: 0px;
    background-color: white;
    width: 100%;
  }

  @media screen and (max-width:720px) {
    .index-story {
      position: none;
      bottom: 1px;
      background-color: white;
      width: 100%;
    }

  }
</style>

<body>
  <div class="index-hero" data-aos="fade-in">
    <div class="index-background"></div>
    <div class="index-hero-content">
      <img src="inc/img/logo.black.png" alt="Readers Logo" class="index-logo">
      <h1 style="font-size: 44px;font-weight:600;">Welcome to</h1>
      <h2 style="font-size: 40px; font-weight:400; color:antiquewhite;">Voice of India, <br>News Junction of the World</h2>
      <p>Be Part of the Conversation</p>
      <a href="sign-in.php?type=login"><button class="cta mb-2">Start</button></a>
      <a href="https://play.google.com/store/apps/details?id=com.knobly.cream&pcampaignid=web_share&pli=1">
        <img src="./inc/img/playstore.png" alt="" width="auto" height="56" style="border-radius: 4px;">
      </a>


    </div>

  </div>

  <div class="index-story" data-aos="fade-up">
    <h2>Our Story</h2>
    <p>Cream is founded with the aim of giving a voice to everyone out there and enabling them to share their
      version of the story with the world.!</p>
  </div>

  <!-- About Section -->
  <section id="about" class="about section">

    <div class="container">

      <div class="row gy-4">

        <div class="col-lg-6 content" data-aos="fade-up" data-aos-delay="100">
          <p class="who-we-are">Who We Are</p>
          <h3>News Junction: The Future of Social Engagement</h3>
          <p class="fst-italic">
            In a world inundated with social platforms, News Junction rises as a unified and purpose-driven space for meaningful connections and impactful content. Here's why News Junction stands out as a platform that redefines social media:
          </p>
          <ul>
            <li><i class="bi bi-check-circle"></i> <span>Voice of India, News Junction of the World: Amplify diverse voices from every corner of India while staying attuned to global conversations, creating a perfect balance of hyperlocal and global perspectives.</span></li>
            <li><i class="bi bi-check-circle"></i> <span>Build Hyperlocal Communities: Foster tight-knit, location-based communities that thrive on shared interests, making every interaction relevant and impactful.</span></li>
            <li><i class="bi bi-check-circle"></i> <span>Create, Curate, Reach, Measure-All in One: From ideation to distribution and performance tracking, News Junction empowers users with a single platform to seamlessly manage their content and achieve their goals.</span></li>
            It's not just another platform; it's one platform to rule them all-unifying creativity, connection, and community in unprecedented ways.
          </ul>
          <a href="#" class="read-more"><span>Read More</span><i class="bi bi-arrow-right"></i></a>
        </div>

        <div class="col-lg-6 about-images" data-aos="fade-up" data-aos-delay="200">
          <div class="tab-pane fade active show" id="features-tab-1">
            <img src="inc/img/tabs-1.jpg" alt="" class="img-fluid">
          </div>

        </div>

      </div>

    </div>
  </section><!-- /About Section -->

  <section id="about" class="about section">

    <div class="container">

      <div class="row gy-4">

        <div class="col-lg-6 about-images" data-aos="fade-up" data-aos-delay="200">
          <div class="tab-pane fade active show" id="features-tab-1">
            <img src="inc/img/tabs-2.jpg" alt="" class="img-fluid">
          </div>

        </div>

        <div class="col-lg-6 content" data-aos="fade-up" data-aos-delay="100">
          <p class="who-we-are">How it works</p>
          <h3>News Junction: Redefining Connections Across Audiences and Goals</h3>
          <p class="fst-italic">
            News Junction seamlessly blends the best of B2C, B2B, and P2P worlds, making it the ultimate platform for brands, businesses, and individuals. Whether you're looking to grow your audience, amplify your brand, or connect with like-minded people, News Junction is built to cater to every need:
          </p>
          <ul>
            <li><i class="bi bi-check-circle"></i> <span>A Marketing Powerhouse: Use News Junction as a comprehensive tool to craft, publish, and measure marketing campaigns that resonate with your audience.</span></li>
            <li><i class="bi bi-check-circle"></i> <span>Brand Amplification: Position yourself or your business in the spotlight through News Junction's versatile brand amplification capabilities.</span></li>
            <li><i class="bi bi-check-circle"></i> <span>Three Modes of Engagement:</span></li>
            <ul>
              <li>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Social Mode: Build and engage with vibrant, hyperlocal or global communities.</li>
              <li>Content Mode: Read, watch, and listen to curated content tailored to your interests.</li>
              <li>Creator Mode: Leverage AI-enhanced tools to create, curate, and measure your content's impact.</li>
            </ul>
          </ul>
          Ready to Transform the Way You Connect? Join News Junction Today and Experience the Future of Engagement!
          <br>
          <br>
          <a href="#" class="read-more"><span>Read More</span><i class="bi bi-arrow-right"></i></a>
        </div>

      </div>

    </div>
  </section>

  <!-- Pricing Section -->
  <section id="pricing" class="pricing section">

    <!-- Section Title -->
    <div class="container section-title" data-aos="fade-up">
      <h2>Pricing</h2>
      <p>Go Pro Today</p>
    </div><!-- End Section Title -->


    <div class="container">

      <div class="row gy-4">

        <div class="col-lg-4" data-aos="zoom-in" data-aos-delay="100">
          <div class="pricing-item">
            <h3>Free</h3>
            <p class="description">Starter</p>
            <h4><sup>$</sup>0<span> / month</span></h4>
            <a href="#" class="cta-btn">Sign Up For Free Trial</a>
            <ul>
              <li><i class="bi bi-check"></i> <span>Cream Curated RSS Feeds</span></li>
              <li><i class="bi bi-check"></i> <span>Your Own RSS Feeds<br>
                  Max of 5 Feeds</span></li>
              <li><i class="bi bi-check"></i> <span>Social Share</span></li>
              <li class="na"><i class="bi bi-x"></i>Social Share Scheduling</span></li>
              <li><i class="bi bi-check"></i> <span>Create Blog</span></li>
              <li class="na"><i class="bi bi-x"></i>Create Landing Page with CTA button</span></li>
              <li class="na"><i class="bi bi-x"></i>Capture Lead from Landing Page</span></li>
              <li><i class="bi bi-check"></i> <span>Newsletter<br>
                  Restricted to 1 a month</span></li>
              <li class="na"><i class="bi bi-x"></i>Newsletter Sending From within Cream</span></li>
              <li><i class="bi bi-check"></i> <span>Analytics</span></li>
              <li class="na"><i class="bi bi-x"></i>Analytics Drilldown to Location</span></li>
              <li class="na"><i class="bi bi-x"></i>Subdomain xyz.newsjunction.net</span></li>
              <li class="na"><i class="bi bi-x"></i>Mapping knobly view pages to your domain</span></li>

            </ul>
          </div>
        </div><!-- End Pricing Item -->

        <div class="col-lg-4" data-aos="zoom-in" data-aos-delay="200">
          <div class="pricing-item featured">
            <p class="popular">Popular</p>
            <h3>Pro</h3>
            <p class="description">INR 2000/month OR INR 18,000 Paid Annually</p>
            <h4><sup>$</sup>23.32<span> / month</span></h4>
            <a href="#" class="cta-btn">Sign Up and Go Pro</a>
            <ul>
              <li><i class="bi bi-check"></i> <span>Cream Curated RSS Feeds</span></li>
              <li><i class="bi bi-check"></i> <span>Your Own RSS Feeds<br>
                  Unlimited</span>
              </li>
              <li><i class="bi bi-check"></i> <span>Social Share</span></li>
              <li><i class="bi bi-check"></i> <span>Social Share Scheduling</span></li>
              <li><i class="bi bi-check"></i> <span>Create Blog</span></li>
              <li><i class="bi bi-check"></i> <span>Create Landing Page with CTA button</span></li>
              <li><i class="bi bi-check"></i> <span>Capture Lead from Landing Page</span></li>
              <li><i class="bi bi-check"></i> <span>Newsletter<br>
                  Unlimited</span>
              </li>
              <li><i class="bi bi-check"></i> <span>Newsletter Sending From within Cream</span></li>
              <li><i class="bi bi-check"></i> <span>Analytics</span></li>
              <li><i class="bi bi-check"></i> <span>Analytics Drilldown to Location</span></li>
              <li><i class="bi bi-check"></i> <span>Subdomain xyz.newsjunction.net</span></li>
              <li><i class="bi bi-check"></i> <span>Mapping knobly view pages to your domain</span></li>
            </ul>
          </div>
        </div><!-- End Pricing Item -->

        <div class="col-lg-4" data-aos="zoom-in" data-aos-delay="300">
          <div class="pricing-item">
            <h3>Developer Plan</h3>
            <p class="description">Ullam mollitia quasi nobis soluta in voluptatum et sint palora dex strater</p>
            <h4><sup>$</sup>49<span> / month</span></h4>
            <a href="#" class="cta-btn">Start a free trial</a>
            <p class="text-center small">No credit card required</p>
            <ul>
              <li><i class="bi bi-check"></i> <span>Quam adipiscing vitae proin</span></li>
              <li><i class="bi bi-check"></i> <span>Nec feugiat nisl pretium</span></li>
              <li><i class="bi bi-check"></i> <span>Nulla at volutpat diam uteera</span></li>
              <li><i class="bi bi-check"></i> <span>Pharetra massa massa ultricies</span></li>
              <li><i class="bi bi-check"></i> <span>Massa ultricies mi quis hendrerit</span></li>
              <li><i class="bi bi-check"></i> <span>Voluptate id voluptas qui sed aperiam rerum</span></li>
              <li><i class="bi bi-check"></i> <span>Iure nihil dolores recusandae odit voluptatibus</span></li>
            </ul>
          </div>
        </div><!-- End Pricing Item -->

      </div>

    </div>

  </section><!-- /Pricing Section -->

  <!-- Services Section -->
  <section id="services" class="services section light-background">

    <!-- Section Title -->
    <div class="container section-title" data-aos="fade-up">
      <h2>Do more with less</h2>
      <p>Features optimized for you</p>
    </div><!-- End Section Title -->

    <div class="container">

      <div class="row g-5">

        <div class="col-lg-6" data-aos="fade-up" data-aos-delay="100">
          <div class="service-item item-cyan position-relative">
            <i class="bi icon"> <img src="assets/services/1.png"></i>
            <div>
              <h3>Create</h3>
              <p>Create original content using our create feature or curate from your feeds and write a commentary, whatever suits you.</p>
              <a href="#" class="read-more stretched-link">Learn More <i class="bi bi-arrow-right"></i></a>
            </div>
          </div>
        </div><!-- End Service Item -->

        <div class="col-lg-6" data-aos="fade-up" data-aos-delay="200">
          <div class="service-item item-orange position-relative">
            <i class="bi bi-broadcast icon"></i>
            <div>
              <h3>Curate</h3>
              <p>Aggregate your fav reading list via RSS/Atom feeds. Search from our repo of feeds and add.</p>
              <a href="#" class="read-more stretched-link">Learn More <i class="bi bi-arrow-right"></i></a>
            </div>
          </div>
        </div><!-- End Service Item -->

        <div class="col-lg-6" data-aos="fade-up" data-aos-delay="300">
          <div class="service-item item-teal position-relative">
            <i class="bi bi-easel icon"></i>
            <div>
              <h3>Measure</h3>
              <p>Measure the response of your audience right here.</p>
              <a href="#" class="read-more stretched-link">Learn More <i class="bi bi-arrow-right"></i></a>
            </div>
          </div>
        </div><!-- End Service Item -->

        <div class="col-lg-6" data-aos="fade-up" data-aos-delay="400">
          <div class="service-item item-red position-relative">
            <i class="bi bi-bounding-box-circles icon"></i>
            <div>
              <h3>Share on Social Media</h3>
              <p>Share it with your friends by email or social media.</p>
              <a href="#" class="read-more stretched-link">Learn More <i class="bi bi-arrow-right"></i></a>
            </div>
          </div>
        </div><!-- End Service Item -->

        <div class="col-lg-6" data-aos="fade-up" data-aos-delay="500">
          <div class="service-item item-indigo position-relative">
            <i class="bi bi-calendar4-week icon"></i>
            <div>
              <h3>Newsletter</h3>
              <p>Create newsletters and download a complete html with inline styling. Send to your list using Thunderbird, Outlook or SaaS platforms like Mailchimp, Sendgrid, Mailgun, etc.</p>
              <a href="#" class="read-more stretched-link">Learn More <i class="bi bi-arrow-right"></i></a>
            </div>
          </div>
        </div><!-- End Service Item -->

        <div class="col-lg-6" data-aos="fade-up" data-aos-delay="600">
          <div class="service-item item-pink position-relative">
            <i class="bi bi-chat-square-text icon"></i>
            <div>
              <h3>Settings</h3>
              <p>Customize your Cream platform, your newsletter look and feel.</p>
              <a href="#" class="read-more stretched-link">Learn More <i class="bi bi-arrow-right"></i></a>
            </div>
          </div>
        </div><!-- End Service Item -->

      </div>

    </div>

  </section><!-- /Services Section -->

  <!-- <footer class="index-footer fixed-bottom">
    <div class="container-fluid">
      <div class="d-flex justify-content-between">
        <div>
          &copy; Knobly Consulting
        </div>
        <div>
          <a href="about.html">About us</a> &nbsp;
          <a href="usage.html">Usage Policy</a> &nbsp;
          <a href="refund.html">Refund Policy</a> &nbsp;
          <a href="privacy.html">Privacy Policy</a> &nbsp;
          <a href="contact.html">Contact us</a>
        </div>
      </div>
    </div>
  </footer> -->
  <?php include './assets/php/indexFooter.php' ?>
</body>

</html>