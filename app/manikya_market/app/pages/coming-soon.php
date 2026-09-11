<?php

declare(strict_types=1);

http_response_code(200);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Manikya Market — Coming Soon</title>
  <link rel="icon" type="image/x-icon" href="/grfx/img/nj_logo.png">
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: 'Segoe UI', Roboto, sans-serif;
      min-height: 100vh;
      background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
      color: #fff;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 24px;
      overflow: hidden;
      position: relative;
    }
    /* Decorative blurred shapes */
    body::before, body::after {
      content: '';
      position: absolute;
      border-radius: 50%;
      filter: blur(80px);
      opacity: 0.4;
      z-index: 0;
    }
    body::before {
      width: 480px; height: 480px;
      background: #ff6b35;
      top: -120px; left: -120px;
    }
    body::after {
      width: 380px; height: 380px;
      background: #f7931e;
      bottom: -100px; right: -100px;
    }
    .card {
      position: relative;
      z-index: 1;
      background: rgba(255,255,255,0.06);
      backdrop-filter: blur(14px);
      -webkit-backdrop-filter: blur(14px);
      border: 1px solid rgba(255,255,255,0.12);
      border-radius: 24px;
      padding: 48px 40px;
      max-width: 540px;
      width: 100%;
      text-align: center;
      box-shadow: 0 20px 60px rgba(0,0,0,0.4);
    }
    .badge {
      display: inline-block;
      background: linear-gradient(135deg, #ff6b35 0%, #f7931e 100%);
      color: #fff;
      padding: 6px 16px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 700;
      letter-spacing: 1.5px;
      text-transform: uppercase;
      margin-bottom: 24px;
      box-shadow: 0 4px 14px rgba(247,147,30,0.4);
    }
    h1 {
      font-size: 42px;
      font-weight: 800;
      margin-bottom: 14px;
      letter-spacing: -1px;
      background: linear-gradient(135deg, #fff 0%, #f7931e 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }
    .tagline {
      font-size: 18px;
      opacity: 0.9;
      margin-bottom: 28px;
      line-height: 1.5;
    }
    .description {
      font-size: 15px;
      opacity: 0.7;
      line-height: 1.7;
      margin-bottom: 32px;
    }
    .icon-row {
      display: flex;
      justify-content: center;
      gap: 16px;
      margin-bottom: 32px;
      flex-wrap: wrap;
    }
    .icon-box {
      background: rgba(255,255,255,0.08);
      border: 1px solid rgba(255,255,255,0.1);
      padding: 14px 18px;
      border-radius: 12px;
      font-size: 13px;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .icon-box span { font-size: 22px; }
    .back-link {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: rgba(255,255,255,0.1);
      color: #fff;
      padding: 12px 28px;
      border-radius: 30px;
      text-decoration: none;
      font-weight: 600;
      font-size: 14px;
      border: 1px solid rgba(255,255,255,0.15);
      transition: all 0.25s ease;
    }
    .back-link:hover {
      background: rgba(255,255,255,0.18);
      transform: translateY(-2px);
    }
    @media (max-width: 540px) {
      .card { padding: 36px 24px; }
      h1 { font-size: 32px; }
      .tagline { font-size: 16px; }
    }
  </style>
</head>
<body>
  <div class="card">
    <div class="badge">Coming Soon</div>
    <h1>Manikya Market</h1>
    <p class="tagline">A new way to shop is on its way</p>
    <p class="description">
      We're putting the final touches on something special. A trusted marketplace
      connecting Karnataka's sellers with buyers across India — launching soon.
    </p>
    <div class="icon-row">
      <div class="icon-box"><span>🛍️</span> Curated sellers</div>
      <div class="icon-box"><span>🚚</span> Fast delivery</div>
      <div class="icon-box"><span>💳</span> Secure payments</div>
    </div>
    <a href="/stream.php" class="back-link">← Back to News Junction</a>
  </div>
</body>
</html>
