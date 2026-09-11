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
      gap: 12px;
    }

    .auth-buttons a {
      width: 60px;
      padding: 8px 18px;
      border-radius: 20px;
      font-size: 15px;
      font-weight: 600;
      text-decoration: none;
    }

    .btn-login {
      background: gold;
      color: #a50e0eff;
      border: 2px solid #fff;
    }

    .btn-login:hover {
      background: rgba(255, 255, 255, 0.15);
    }

    .btn-signup {
      background: gold;
      color: #8b0000;
      border: 2px solid gold;
    }

    /* ================= NAV ================= */
    nav {
      background: #0e2385;
      padding: 12px 0;
    }

    nav ul {
      list-style: none;
      display: flex;
      gap: 25px;
      margin: 0 0 0 20px;
      padding: 0;
    }

    nav ul li a {
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
        justify-content: flex-end;
      }
    }
  </style>
</head>

<body>

  <header>
    <div class="hero">

      <div class="logo">
        <img src="grfx/images/logo.png" alt="News Junction">
      </div> 

      <div class="hero-content">
        <p>
          A professional digital news platform delivering verified district,
          state, national and breaking news with journalistic integrity.
        </p>
        <div class="cta">
          <a href="#">Explore Latest News</a>
        </div>
      </div>

      <div class="auth-buttons">
        <a href="sign-in.php?type=login" class="btn-login">Login</a>
        <a href="sign-in.php?type=signup" class="btn-signup">Sign Up</a>
      </div>

    </div>
  </header>

  <nav>
    <ul>
      <li><a href="#">Home</a></li>
      <li><a href="#">District</a></li>
      <li><a href="#">State</a></li>
      <li><a href="#">National</a></li>
      <li><a href="#">Cricket</a></li>
      <li><a href="#">Bullion</a></li>
      <li><a href="#">Radio</a></li>
    </ul>
  </nav>

  <section>
    <h2>ಏಕೆ News Junction?</h2>
    <div class="features">
      <div class="feature-box">
        <h3>ಪರಿಶೀಲಿತ ವರದಿ</h3>
        <p>ಪ್ರಕಟನೆಯ ಮೊದಲು ಪ್ರತಿಯೊಂದು ಸುದ್ದಿಯೂ ಜಿಲ್ಲಾ ಸಂಪಾದಕರಿಂದ ಪರಿಶೀಲಿಸಲಾಗುತ್ತದೆ.</p>
      </div>
      <div class="feature-box">
        <h3>ಜಿಲ್ಲಾ ವರದಿ</h3>
        <p>ಪ್ರತಿ ಜಿಲ್ಲೆಯ ಸ್ಥಳೀಯ ಮಹತ್ವದ ವಿಷಯಗಳಿಗೆ ಬಲವಾದ ತಳಮಟ್ಟದ ವರದಿ.</p>
      </div>
      <div class="feature-box">
        <h3>ತಕ್ಷಣದ ಅಪ್ಡೇಟ್‌ಗಳು</h3>
        <p>ಬ್ರೇಕಿಂಗ್ ನ್ಯೂಸ್ ಅಲರ್ಟ್‌ಗಳು ಮತ್ತು ಲೈವ್ ಅಪ್ಡೇಟ್‌ಗಳು ಕ್ಷಣಾರ್ಧದಲ್ಲಿ.</p>
      </div>
      <div class="feature-box">
        <h3>ನೈತಿಕ ಪತ್ರಿಕೋದ್ಯಮ</h3>
        <p>ಜವಾಬ್ದಾರಿಯುತ, ಪಕ್ಷಪಾತರಹಿತ ಮತ್ತು ಪಾರದರ್ಶಕ ವರದಿ ಮಾನದಂಡಗಳು.</p>
      </div>
    </div>
  </section>

  <!-- ================= SLIDING NEWS ADS (PLACED ABOVE STATS) ================= -->
  <div class="news-ads">
    <div class="news-ads-track">
      <a href="#" class="news-ad">
        <img src="grfx/images/bengaluru.png" alt="">
        <div class="news-ad-content">
          <h4>Breaking: District Budget Announced</h4>
          <p>Major allocations for roads and healthcare.</p>
        </div>
      </a>
      <a href="#" class="news-ad">
        <img src="grfx/images/mysore.png" alt="">
        <div class="news-ad-content">
          <h4>Exclusive Interview</h4>
          <p>Minister speaks on upcoming elections.</p>
        </div>
      </a>
      <a href="#" class="news-ad">
        <img src="grfx/images/hubli.png" alt="">
        <div class="news-ad-content">
          <h4>Cricket Update</h4>
          <p>District team wins thrilling final.</p>
        </div>
      </a>

      <!-- duplicated for smooth loop -->
      <a href="#" class="news-ad">
        <img src="grfx/images/mandya.png" alt="">
        <div class="news-ad-content">
          <h4>Breaking: District Budget Announced</h4>
          <p>Major allocations for roads and healthcare.</p>
        </div>
      </a>
      <a href="#" class="news-ad">
        <img src="grfx/images/belagavi.png" alt="">
        <div class="news-ad-content">
          <h4>Exclusive Interview</h4>
          <p>Minister speaks on upcoming elections.</p>
        </div>
      </a>
      <a href="#" class="news-ad">
        <img src="grfx/images/Hassan.png" alt="">
        <div class="news-ad-content">
          <h4>Cricket Update</h4>
          <p>District team wins thrilling final.</p>
        </div>
      </a>
    </div>
  </div>

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
      <a href="#">About</a> |
      <a href="#">Privacy Policy</a> |
      <a href="#">Disclaimer</a> |
      <a href="#">Contact</a>
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