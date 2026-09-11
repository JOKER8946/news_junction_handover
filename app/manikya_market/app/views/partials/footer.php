<?php
// footer partial
$footerBase = '';
if (isset($GLOBALS['config']) && is_array($GLOBALS['config']) && function_exists('app_base_path')) {
    $footerBase = app_base_path($GLOBALS['config']);
}
?>
<style>
  /* Coordinated palette — matches the navy/gold hero banner.
     Replaces Amazon's #FF9900 with refined international tones:
       - Accent gold (#E8A23B)   → top border, link hover, focal hover
       - Accent gold dark (#C77D2A) → social hover backdrop
       - Sky-link (#7FB3FF)      → bottom-strip link hover (softer than gold)
       - Muted text (#B7BDC4)    → body copy
  */
  .mm-footer {
    background: #232F3E;
    color: #DDD;
    margin-top: 32px;
    border-top: 3px solid;
    border-image: linear-gradient(90deg, #E8A23B 0%, #C77D2A 50%, #E8A23B 100%) 1;
  }
  .mm-footer .mm-footer-main { padding: 36px 0 28px; }
  .mm-footer h6 {
    color: #FFFFFF;
    font-weight: 600;
    font-size: .92rem;
    letter-spacing: .01em;
    margin-bottom: 14px;
  }
  .mm-footer p, .mm-footer li, .mm-footer .mm-foot-text { color: #B7BDC4; }
  .mm-footer a {
    color: #DDD;
    text-decoration: none;
    transition: color .15s;
  }
  .mm-footer a:hover { color: #E8A23B; text-decoration: underline; }
  .mm-footer .mm-social a {
    width: 32px; height: 32px;
    background: rgba(255,255,255,.06);
    border: 1px solid rgba(255,255,255,.12);
    border-radius: 50%;
    display: inline-flex; align-items: center; justify-content: center;
    transition: background .15s, border-color .15s, color .15s;
  }
  .mm-footer .mm-social a:hover {
    background: rgba(232, 162, 59, .14);
    border-color: #E8A23B;
    color: #E8A23B;
  }
  .mm-footer .mm-social a:hover i { color: #E8A23B; }
  .mm-footer-logo { display: inline-block; }
  .mm-footer-bottom {
    background: #131A22;
    color: #95A0AC;
    padding: 16px 0;
    font-size: .82rem;
  }
  .mm-footer-bottom a { color: #95A0AC; }
  .mm-footer-bottom a:hover { color: #7FB3FF; }
  .mm-footer-bottom .mm-foot-links li { position: relative; padding-right: 14px; }
  .mm-footer-bottom .mm-foot-links li:not(:last-child)::after {
    content: '·'; position: absolute; right: 4px; top: 0; color: #4A5360;
  }
</style>
<footer class="mm-footer">
  <div class="mm-footer-main">
    <div class="container">
      <div class="row g-4 align-items-start">
        <!-- About Section -->
        <div class="col-md-4 col-lg-3">
          <h6>About</h6>
          <p class="small mb-0">A Tech-enabled marketplace bridging authentic Indian village craftsmanship with global consumers. We handle everything for rural sellers so they can focus on their craft.</p>
        </div>

        <!-- Quick Links -->
        <div class="col-6 col-md-2 col-lg-2">
          <h6>Quick Links</h6>
          <ul class="list-unstyled small mb-0">
            <li class="mb-1"><a href="?p=home">Home</a></li>
            <li class="mb-1"><a href="?p=products">Products</a></li>
          </ul>
        </div>

        <!-- Contact & Social -->
        <div class="col-6 col-md-3 col-lg-4">
          <h6>Contact Us</h6>
          <p class="small mb-2 d-flex align-items-center gap-2">
            <i data-lucide="mail" class="mm-icon"></i>
            <span>manikyaservicespvtltd@gmail.com</span>
          </p>
          <p class="small mb-3 d-flex align-items-center gap-2">
            <i data-lucide="phone" class="mm-icon"></i>
            <span>+91 7411742999 / 7411647999</span>
          </p>
          <div class="d-flex gap-2 mm-social">
            <a href="#" title="Facebook"><i data-lucide="facebook" style="width:14px;height:14px;"></i></a>
            <a href="#" title="Twitter"><i data-lucide="twitter" style="width:14px;height:14px;"></i></a>
            <a href="#" title="Instagram"><i data-lucide="instagram" style="width:14px;height:14px;"></i></a>
          </div>
        </div>

        <!-- Footer Logo -->
        <div class="col-md-3 col-lg-3 d-flex align-items-center justify-content-md-end justify-content-center">
          <span class="mm-footer-logo">
            <img src="<?= e($footerBase) ?>/assets/img/footer-logo2.png" alt="Manikya Money Service Private Limited" style="max-width: 130px; width: 100%; height: auto; display: block;">
          </span>
        </div>
      </div>
    </div>
  </div>

  <!-- Bottom strip (Amazon-style darker band) -->
  <div class="mm-footer-bottom">
    <div class="container">
      <div class="row align-items-center g-2">
        <div class="col-md-6">
          <p class="mb-0">&copy; 2026 Manikya Money Service Private Limited. All rights reserved.</p>
        </div>
        <div class="col-md-6 text-md-end">
          <ul class="list-unstyled d-inline-flex mm-foot-links mb-0">
            <li><a href="#">Privacy Policy</a></li>
            <li><a href="#">Terms of Service</a></li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</footer>
