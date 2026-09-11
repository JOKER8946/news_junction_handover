<?php
include 'checkSession.php';
// include lightweight ad helper
include_once 'inc/php/ad_functions.php';


// load ads (file-backed)
$topAds = getActiveAds('top', 1);
$feedAds = getActiveAds('feed', 3);

// DEBUG: Log ad counts
error_log("INDEX.PHP DEBUG - topAds count: " . count($topAds) . ", feedAds count: " . count($feedAds));
if (!empty($feedAds)) {
  error_log("INDEX.PHP DEBUG - feedAds[0]: " . json_encode($feedAds[0]));
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>News Junction | Trusted Digital News Platform</title>

  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">

  <style>
    body {
      margin: 0;
      font-family: 'Poppins', sans-serif;
      background: #f7f7f7;
      color: #222;
    }

    /* ================= HEADER ================= */
    header {
      background: linear-gradient(90deg, #8b0000, #c40000);
      color: #fff;
      padding: 0 20px;
      border: 3px solid gold;
    }

    .hero {
      max-width: 1100px;
      margin: auto;
      display: flex;
      align-items: center;
      gap: 30px;
      padding: 20px;
    }

    .logo {
      margin-right: auto;
    }

    .logo img {
      width: 260px;
      border-radius: 6px;
    }

    .hero-content {
      max-width: 600px;
    }

    .hero p {
      font-size: 20px;
      line-height: 1.6;
    }

    .cta a {
      display: inline-block;
      margin-top: 15px;
      padding: 12px 25px;
      background: #fff;
      color: #b00000;
      font-weight: 600;
      border-radius: 4px;
      text-decoration: none;
    }

    /* ================= AUTH BUTTONS ================= */
    .auth-buttons {
      display: flex;
      justify-content: space-between;
    }

    .log-btns {
      display: flex;
      justify-content: center;
      align-items: center;
      gap: 12px;
    }

    .auth-buttons a {
      width: 61px;
      padding: 5px 5px;
      border-radius: 12px;
      text-decoration: none;
      height: 25px;
    }

    .btn-login {
      background: #a50e0eff;
      color: white;
      border: 2px solid #fff;
    }

    .btn-login:hover {
      background: rgba(255, 255, 255, 0.15);
    }

    .btn-signup {
      background: gold;
      color: #8b0000;
      border: 2px solid white;
    }

    .auth-buttons img {
      height: 50px;
      width: auto;
    }

    /* ================= NAV ================= */
    nav {
      background: #0e2385;
      padding: 10px 10px;

    }

    .nav ul {
      list-style: none;
      display: flex;
      gap: 25px;
      margin: 0 0 0 20px;
      padding: 0;
    }

    .nav ul li a {
      color: #fff;
      font-size: 20px;
      text-decoration: none;
    }

    /* ================= SLIDING NEWS ADS ================= */
    .news-ads {
      background: #fff;
      max-width: 1100px;
      margin: 40px auto 20px;
      padding: 15px 0;
      overflow: hidden;
      border-radius: 8px;
      box-shadow: 0 6px 18px rgba(0, 0, 0, 0.08);
    }

    .news-ads-track {
      display: flex;
      gap: 30px;
      animation: slideAds 30s linear infinite;
    }

    .news-ads:hover .news-ads-track {
      animation-play-state: paused;
    }

    .news-ad {
      flex: 0 0 auto;
      width: 280px;
      background: #f9f9f9;
      border-radius: 8px;
      overflow: hidden;
      text-decoration: none;
      color: #222;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .news-ad img {
      width: 100%;
      height: 160px;
      object-fit: cover;
    }

    .news-ad-content {
      padding: 12px;
    }

    .news-ad-content h4 {
      margin: 0 0 6px;
      color: #b00000;
      font-size: 16px;
    }

    .news-ad-content p {
      margin: 0;
      font-size: 14px;
    }

    @keyframes slideAds {
      from {
        transform: translateX(0);
      }

      to {
        transform: translateX(-50%);
      }
    }

    /* ================= SECTIONS ================= */
    section {
      max-width: 1100px;
      margin: auto;
      padding: 60px 20px;
    }

    .features {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap: 20px;
    }

    .feature-box {
      background: #fff;
      padding: 25px;
      border-radius: 6px;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
    }

    .feature-box h3 {
      color: #b00000;
      margin-top: 0;
    }

    /* ================= STATS ================= */
    .stats {
      background: #fff;
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      text-align: center;
    }

    .stat h2 {
      color: #b00000;
      font-size: 32px;
      margin: 0;
    }

    /* ================= FOOTER ================= */
    footer {
      background: #111;
      color: #ccc;
      text-align: center;
      padding: 25px;
    }

    footer a {
      color: #ccc;
      margin: 0 10px;
      font-size: 14px;
      text-decoration: none;
    }

    /* ================= CHAT ================= */
    .chat-widget {
      position: fixed;
      right: 20px;
      bottom: 20px;
      z-index: 9999;
      display: none;
    }

    .chat-toggle {
      background: #b00000;
      color: #fff;
      border-radius: 50px;
      padding: 12px 16px;
      border: none;
      cursor: pointer;
    }

    .chat-window {
      width: 320px;
      height: 420px;
      background: #fff;
      border-radius: 10px;
      display: none;
      flex-direction: column;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
      margin-bottom: 10px;
    }

    .chat-widget.open .chat-window {
      display: flex;
    }

    /* ================= MOBILE ================= */
    @media(max-width:900px) {
      .hero {
        flex-wrap: wrap;
      }

      .auth-buttons {
        width: 100%;
      }

    }

    /* ================= SUBHEADING ABOVE NAV (STICKY BEHAVIOR) ================= */
    .subheading {
      max-width: 1100px;
      transition: opacity .35s ease, height .35s ease, margin .35s ease;
    }

    .subheading img {
      width: 100%;
      object-fit: cover;
      box-shadow: 0 6px 16px rgba(0, 0, 0, 0.12);
    }

    .subheading.hidden {
      opacity: 0;
      height: 0;
      margin: 0 auto;
      overflow: hidden;
      pointer-events: none;
    }

    /* sticky and show/hide behavior for .nav */
    .nav {
      transition: transform .28s ease, box-shadow .25s ease;
    }

    nav.sticky {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      z-index: 1150;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
      /* Optional: slightly smaller padding when stuck */
      padding: 8px 15px;
    }

    /* Add padding to body when nav becomes fixed → prevents content jump */
    body.nav-fixed {
      padding-top: 80px;
      /* ← adjust to your nav height when fixed */
    }

    /* nav inner layout */
    .nav-inner {
      max-width: 1100px;
      margin: 0 auto;
      display: flex;
      align-items: center;
      gap: 18px;
      padding: 8px 18px;
    }

    .nav-left {
      flex: 0 0 auto;
    }

    .nav-logo {
      height: 71px;
      display: block;
    }

    .nav-center {
      flex: 1 1 auto;
    }

    .nav-menu {
      display: flex;
      gap: 18px;
      list-style: none;
      margin: 0;
      padding: 0;
      justify-content: center;
    }

    .nav-menu li a {
      color: #111;
      text-decoration: none;
      font-weight: 600;
    }

    .nav-right {
      flex: 0 0 auto;
    }

    /* make nav light background and keep contrast */




    /* ================ SIDE LOGO & ANIMATIONS ================ */
    .side-logo {
      position: fixed;
      left: 18px;
      top: 50%;
      transform: translateY(-50%);
      width: 110px;
      z-index: 1200;
    }

    .side-logo img {
      width: 100%;
      border-radius: 8px;
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.18);
      animation: float 6s ease-in-out infinite;
    }

    @keyframes float {
      0% {
        transform: translateY(-50%) translateY(0);
      }

      50% {
        transform: translateY(-50%) translateY(-8px);
      }

      100% {
        transform: translateY(-50%) translateY(0);
      }
    }

    /* Button hover animations */
    .cta a,
    .auth-buttons a {
      transition: transform .18s ease, box-shadow .18s ease;
    }

    .cta a:hover,
    .auth-buttons a:hover {
      transform: translateY(-3px) scale(1.02);
      box-shadow: 0 8px 20px rgba(176, 0, 0, 0.12);
    }

    /* Image / content reveal on scroll */
    .reveal {
      opacity: 0;
      transform: translateY(18px) scale(0.99);
      transition: opacity .6s ease, transform .6s ease;
    }

    .reveal.in-view {
      opacity: 1;
      transform: translateY(0) scale(1);
    }

    /* make sure side logo hides on small screens */
    @media(max-width:800px) {
      .side-logo {
        display: none;
      }
    }
  </style>
</head>

<body>
  <header>
    <div class="hero">

      <div class="logo">
        <img src="grfx/images/newsjunction.png" alt="News Junction">
      </div>

      <div class="hero-content">
        <p>
          A professional digital news platform delivering verified district,
          state, national and breaking news with journalistic integrity.
        </p>
        <div class="cta">
          <a href="sign-in.php?type=login">Explore Latest News</a>
        </div>
      </div>


    </div>
  </header>
  <nav>
    <div class="auth-buttons">
      <img src="images/newsjunction.png" alt="News Junction Logo" width="100px" height="100px" justify-content="right">

      <div class="log-btns">
        <a href="sign-in.php?type=login" class="btn-login">Login</a>
        <a href="sign-in.php?type=signup" class="btn-signup">Sign Up</a>
      </div>
    </div>
  </nav>


  <section>
    <h2>News Junction ಯಾಕೆ?</h2>
    <div class="features">
      <div class="feature-box">
        <h3>ಪರಿಶೀಲಿತ ವರದಿ</h3>
        <img class="reveal" src="images/verified.png" alt="placeholder" style="width:100%; height:auto; margin:12px 0; border-radius:6px;" />
        <p>ಪ್ರಕಟನೆಯ ಮೊದಲು ಪ್ರತಿಯೊಂದು ಸುದ್ದಿಯೂ ಜಿಲ್ಲಾ ಸಂಪಾದಕರಿಂದ ಪರಿಶೀಲಿಸಲಾಗುತ್ತದೆ.</p>
      </div>
      <div class="feature-box">
        <h3>ಜಿಲ್ಲಾ ವರದಿ</h3>
        <img src="images/district.png" alt="placeholder" style="width:100%; height:auto; margin:12px 0; border-radius:6px;" />
        <p>ಪ್ರತಿ ಜಿಲ್ಲೆಯ ಸ್ಥಳೀಯ ಮಹತ್ವದ ವಿಷಯಗಳಿಗೆ ಬಲವಾದ ತಳಮಟ್ಟದ ವರದಿ.</p>
      </div>
      <div class="feature-box">
        <h3>ತಕ್ಷಣದ ವರದಿ</h3>
        <img src="images/instatnt.png" alt="placeholder" style="width:100%; height:auto; margin:12px 0; border-radius:6px;" />
        <p>ಬ್ರೇಕಿಂಗ್ ನ್ಯೂಸ್ ಅಲರ್ಟ್‌ಗಳು ಮತ್ತು ಲೈವ್ ಅಪ್ಡೇಟ್‌ಗಳು ಕ್ಷಣಾರ್ಧದಲ್ಲಿ.</p>
      </div>
      <div class="feature-box">
        <h3>ನೈತಿಕ ವರದಿ</h3>
        <img src="images/ethical.png" alt="placeholder" style="width:100%; height:auto; margin:12px 0; border-radius:6px;" />
        <p>ಜವಾಬ್ದಾರಿಯುತ, ಪಕ್ಷಪಾತರಹಿತ ಮತ್ತು ಪಾರದರ್ಶಕ ವರದಿ ಮಾನದಂಡಗಳು.</p>
      </div>
    </div>
  </section>
    <!-- top banner ad -->
  <?php if (!empty($topAds)): ?>
    <div style="max-width:1100px;margin:18px auto;padding:0 20px;">
      <?php
      // ensure ad click handler link points to our process
      if (!empty($topAds[0]['id'])) {
        $topAds[0]['ad_link'] = 'process/ad_click_handler.php?ad_id=' . intval($topAds[0]['id']);
      }
      echo generateAdCard($topAds[0]);
      ?>
    </div>
  <?php endif; ?>
 
  <!-- ================= SLIDING NEWS ADS (PLACED ABOVE STATS) ================= -->
  <div class="news-ads">
    <div class="news-ads-track">
      <a href="#" class="news-ad">
        <img src="images/1(1).png" alt="">
        <div class="news-ad-content">
          <h4></h4>
          <p></p>
        </div>
      </a>
      <a href="#" class="news-ad">
        <img src="images/belagavi.png" alt="">
        <div class="news-ad-content">
          <h4></h4>
          <p></p>
        </div>
      </a>
      <a href="#" class="news-ad">
        <img src="images/mysore.png" alt="">
        <div class="news-ad-content">
          <h4></h4>
          <p></p>
        </div>
      </a>

      <!-- duplicated for smooth loop -->
      <a href="#" class="news-ad">
        <img src="images/hassan.png" alt="">
        <div class="news-ad-content">
          <h4></h4>
          <p></p>
        </div>
      </a>
      <a href="#" class="news-ad">
        <img src="images/udupi.png" alt="">
        <div class="news-ad-content">
          <h4></h4>
          <p></p>
        </div>
      </a>
      <a href="#" class="news-ad">
        <img src="images/bellari.png" alt="">
        <div class="news-ad-content">
          <h4></h4>
          <p></p>
        </div>
      </a>
    </div>
  </div>
 <!-- feed ads -->
  <?php if (!empty($feedAds)): ?>
    <section style="padding:30px 20px;">
      <!-- <h2 style="text-align:center;margin-bottom:18px;">Featured Partners</h2> -->
      <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:18px;max-width:1100px;margin:0 auto;padding:0 10px;">
        <?php foreach ($feedAds as $ad): ?>
          <?php
          if (!empty($ad['id'])) $ad['ad_link'] = 'process/ad_click_handler.php?ad_id=' . intval($ad['id']);
          echo generateAdCard($ad);
          ?>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>

  <section class="stats">
    <div class="stat">
      <h2>30+</h2>
      <p>District Reporters</p>
    </div>
    <div class="stat">
      <h2>24x7</h2>
      <p>News Coverage</p>
    </div>
    <div class="stat">
      <h2>100%</h2>
      <p>Verified Sources</p>
    </div>
  </section>

  <footer>
    <p>© 2026 News Junction</p>
    <p>
      <a href="about.html">About</a> |
      <a href="privacy.html">Privacy Policy</a> |
      <a href="contact.html">Contact</a>
    </p>
  </footer>

  <!-- ================= CHAT ================= -->
  <div class="chat-widget">
    <button class="chat-toggle">Chat</button>
    <div class="chat-window">
      <div class="chat-header">
        <span>Assistant</span>
        <button class="chat-toggle" style="background:none;border:none;color:#fff">✕</button>
      </div>
      <div class="chat-messages"></div>
      <div class="chat-input">
        <input placeholder="Type a message...">
        <button>Send</button>
      </div>
    </div>
  </div>

  <script>
    document.querySelectorAll('.chat-toggle').forEach(btn => {
      btn.onclick = () => document.querySelector('.chat-widget').classList.toggle('open');
    });
  </script>

</body>

</html>

<script>
  // Scroll reveal using IntersectionObserver
  document.addEventListener('DOMContentLoaded', function() {
    const obs = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('in-view');
          obs.unobserve(entry.target);
        }
      });
    }, {
      threshold: 0.15
    });
    document.querySelectorAll('.reveal').forEach(el => obs.observe(el));
  });
  window.addEventListener('scroll', function() {
    const nav = document.querySelector('nav');
    const shouldBeSticky = window.scrollY > 0; // or > nav.offsetHeight

    if (shouldBeSticky) {
      nav.classList.add('sticky');
      document.body.classList.add('nav-fixed');
    } else {
      nav.classList.remove('sticky');
      document.body.classList.remove('nav-fixed');
    }
  });
</script>

<script>
  /* Multi template: selected helpers (toggleScrolled, mobile nav, scroll-top) */
  (function() {
    'use strict';

    function toggleScrolled() {
      const selectBody = document.querySelector('body');
      const selectHeader = document.querySelector('#header');
      if (!selectHeader) return;
      if (!selectHeader.classList.contains('scroll-up-sticky') && !selectHeader.classList.contains('sticky-top') && !selectHeader.classList.contains('fixed-top')) return;
      window.scrollY > 100 ? selectBody.classList.add('scrolled') : selectBody.classList.remove('scrolled');
    }

    document.addEventListener('scroll', toggleScrolled);
    window.addEventListener('load', toggleScrolled);

    // Mobile nav toggle
    const mobileNavToggleBtn = document.querySelector('.mobile-nav-toggle');

    function mobileNavToogle() {
      document.querySelector('body').classList.toggle('mobile-nav-active');
      if (!mobileNavToggleBtn) return;
      mobileNavToggleBtn.classList.toggle('bi-list');
      mobileNavToggleBtn.classList.toggle('bi-x');
    }
    if (mobileNavToggleBtn) mobileNavToggleBtn.addEventListener('click', mobileNavToogle);

    // Hide mobile nav on same-page/hash links
    document.querySelectorAll('#navmenu a').forEach(navmenu => {
      navmenu.addEventListener('click', () => {
        if (document.querySelector('.mobile-nav-active')) {
          mobileNavToogle();
        }
      });
    });

    // Scroll top button (if present)
    const scrollTop = document.querySelector('.scroll-top');

    function toggleScrollTop() {
      if (scrollTop) {
        window.scrollY > 100 ? scrollTop.classList.add('active') : scrollTop.classList.remove('active');
      }
    }
    if (scrollTop) {
      scrollTop.addEventListener('click', (e) => {
        e.preventDefault();
        window.scrollTo({
          top: 0,
          behavior: 'smooth'
        });
      });
      window.addEventListener('load', toggleScrollTop);
      document.addEventListener('scroll', toggleScrollTop);
    }

  })();
</script>