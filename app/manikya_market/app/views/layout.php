<?php

$basePath = app_base_path($config ?? []);
?><!doctype html>
<html lang="en">
<head>
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-RED4LFQ774"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());
      gtag('config', 'G-RED4LFQ774');
    </script>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="facebook-domain-verification" content="bmm4lxa6lyiv385e6fk4i71f7g9428" />
    <title><?= e($title ?? 'Manikya Market') ?></title>
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= e($basePath) ?>/assets/css/app.css" rel="stylesheet">

    <!-- Meta Pixel Code -->
    <script>
    !function(f,b,e,v,n,t,s)
    {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
    n.callMethod.apply(n,arguments):n.queue.push(arguments)};
    if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
    n.queue=[];t=b.createElement(e);t.async=!0;
    t.src=v;s=b.getElementsByTagName(e)[0];
    s.parentNode.insertBefore(t,s)}(window, document,'script',
    'https://connect.facebook.net/en_US/fbevents.js');
    fbq('init', '854581434339349');
    fbq('track', 'PageView');
    </script>
    <noscript><img height="1" width="1" style="display:none"
    src="https://www.facebook.com/tr?id=854581434339349&ev=PageView&noscript=1"
    /></noscript>
    <!-- End Meta Pixel Code -->
</head>
<?php
$_role_attr = function_exists('auth_role') ? auth_role() : null;
$_body_attrs = '';
if ($_role_attr === 'merchant')    { $_body_attrs .= ' data-km-admin="1"'; }
if ($_role_attr === 'super_admin') { $_body_attrs .= ' data-sa-admin="1"'; }
?>
<body class="bg-light"<?= $_body_attrs ?>>
<?php require __DIR__ . '/partials/nav.php'; ?>
<?php require __DIR__ . '/partials/categories-bar.php'; ?>

<?php
// Sidebar for merchant links. Visible for merchant role only.
$role = function_exists('auth_role') ? auth_role() : null;
?>
<?php if ($role === 'merchant'): ?>
  <aside id="mmSidebar" class="mm-sidebar modern">
    <div class="mm-sidebar-top">
      
      <div class="mm-search mt-3">
        <input id="mmSidebarSearch" type="search" class="form-control form-control-sm" placeholder="Search" aria-label="Search">
      </div>
    </div>

    <nav class="mm-nav">
      <ul>
        <li class="mm-section">Overview</li>
        <li class="has-sub">
          <a href="?p=merchant/dashboard" class="mm-nav-link"><i data-lucide="layout-dashboard"></i><span class="mm-label">Dashboard</span></a>
        </li>

        <li class="mm-section">Catalog</li>
        <li class="has-sub">
          <a href="?p=merchant/products" class="mm-nav-link"><i data-lucide="package"></i><span class="mm-label">Products</span><i data-lucide="chevron-down" class="chev"></i></a>
          <ul class="sub-menu">
            <li><a href="?p=merchant/products" class="mm-sub-link">All Products</a></li>
            <li><a href="?p=merchant/product-add" class="mm-sub-link">Add Product</a></li>
          </ul>
        </li>
        <li class="has-sub">
          <a href="?p=merchant/inventory" class="mm-nav-link"><i data-lucide="warehouse"></i><span class="mm-label">Inventory</span></a>
        </li>

        <li class="mm-section">Operations</li>
        <li class="has-sub">
          <a href="?p=merchant/orders" class="mm-nav-link"><i data-lucide="shopping-bag"></i><span class="mm-label">Orders</span></a>
        </li>
        <li class="has-sub">
          <a href="?p=merchant/tickets" class="mm-nav-link"><i data-lucide="help-circle"></i><span class="mm-label">Support Tickets</span></a>
        </li>
        <li class="has-sub">
          <a href="?p=merchant/wallet" class="mm-nav-link"><i data-lucide="wallet"></i><span class="mm-label">Earnings &amp; Wallet</span></a>
        </li>
        <li class="has-sub">
          <a href="?p=merchant/payments-ledger" class="mm-nav-link"><i data-lucide="receipt-indian-rupee"></i><span class="mm-label">Payment Ledger</span></a>
        </li>
        <li class="has-sub">
          <a href="?p=merchant/completed-orders" class="mm-nav-link"><i data-lucide="check-circle"></i><span class="mm-label">Completed Orders</span></a>
        </li>
        <li class="has-sub">
          <a href="?p=merchant/returns" class="mm-nav-link"><i data-lucide="undo-2"></i><span class="mm-label">Returns</span></a>
        </li>

        <li class="mm-section">Analytics</li>
        <li class="has-sub">
          <a href="?p=merchant/reports" class="mm-nav-link"><i data-lucide="bar-chart-2"></i><span class="mm-label">Reports &amp; Analysis</span></a>
        </li>
      </ul>
    </nav>

    <div class="mm-sidebar-bottom">
      <ul>
        <li class="has-sub">
          <a href="?p=merchant/settings" class="mm-nav-link"><i data-lucide="settings"></i><span class="mm-label">Settings</span><i data-lucide="chevron-down" class="chev"></i></a>
          <ul class="sub-menu">
            <li><a href="?p=merchant/settings" class="mm-sub-link">My Business</a></li>
            <li><a href="?p=merchant/integrations" class="mm-sub-link">Integrations status</a></li>
          </ul>
        </li>
      </ul>

      <div class="mm-profile mt-3">
        <?php $userName = function_exists('auth_user_name') ? auth_user_name() : null; ?>
        <div class="d-flex align-items-center gap-2">
          <div class="avatar"><?= strtoupper(substr((string)($userName ?? 'U'),0,1)) ?></div>
          <div class="profile-meta">
            <div class="name"><?= e($userName ?? 'User') ?></div>
            <div class="role small text-muted"><?= e((function_exists('auth_role') ? auth_role() : null) ?? '') ?></div>
          </div>
        </div>
      </div>
    </div>
  </aside>
  <main id="mmMain" class="main-with-sidebar modern">
    <div class="container">
      <?php
        $_pendingBanner = function_exists('merchant_pending_banner') ? merchant_pending_banner($db) : null;
        if ($_pendingBanner !== null):
      ?>
        <div class="alert alert-warning d-flex align-items-start gap-2 mb-3" role="alert">
          <i data-lucide="alert-triangle" style="flex-shrink:0; margin-top:2px;"></i>
          <div><strong>Account pending.</strong> <?= e($_pendingBanner) ?></div>
        </div>
      <?php endif; ?>
      <?php ($content ?? function () {})(); ?>
    </div>
  </main>
<?php elseif ($role === 'super_admin'): ?>
  <?php require __DIR__ . '/partials/super-admin-nav.php'; ?>
  <main class="sa-main">
    <?php ($content ?? function () {})(); ?>
  </main>
<?php else: ?>
  <main>
    <?php ($content ?? function () {})(); ?>
  </main>
<?php endif; ?>

<?php
// ────────────────────────────────────────────────
// Show footer ONLY on home page
$currentPage = (string)($_GET['p'] ?? 'home');
if ($currentPage === 'home') {
    require __DIR__ . '/partials/footer.php';
}
// ────────────────────────────────────────────────
?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/lucide@latest"></script>
<script>
  if (window.lucide) window.lucide.createIcons();
</script>
<script>
// Sidebar toggle logic (unchanged)
(function () {
    const toggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('mmSidebar');
    const main = document.getElementById('mmMain');
    if (!toggle || !sidebar || !main) return;

    const KEY_COLLAPSED = 'mm_sidebar_collapsed';
    const KEY_HIDDEN = 'mm_sidebar_hidden';
    // restore persisted states
    const collapsed = localStorage.getItem(KEY_COLLAPSED) === '1';
    const hidden = localStorage.getItem(KEY_HIDDEN) === '1';
    if (collapsed) sidebar.classList.add('collapsed');
    if (hidden) {
      sidebar.classList.add('hidden');
      sidebar.setAttribute('aria-hidden', 'true');
      main.classList.add('hidden');
      document.documentElement.classList.add('mm-sidebar-hidden');
      sidebar.addEventListener('transitionend', function _hideOnLoad(e) {
        if (e.propertyName === 'transform' && sidebar.classList.contains('hidden')) {
          sidebar.style.display = 'none';
        }
        sidebar.removeEventListener('transitionend', _hideOnLoad);
      });
    }

    const overlay = document.createElement('div');
    overlay.id = 'mmSidebarOverlay';
    overlay.className = 'mm-sidebar-overlay';
    document.body.appendChild(overlay);

    function closeMobile() {
      sidebar.classList.remove('open');
      overlay.classList.remove('show');
      document.body.classList.remove('mm-noscroll');
    }

    function openMobile() {
      sidebar.classList.add('open');
      overlay.classList.add('show');
      document.body.classList.add('mm-noscroll');
    }

    toggle.addEventListener('click', function () {
        try {
          const isMobile = window.innerWidth <= 768;
          if (isMobile) {
            if (sidebar.classList.contains('open')) {
              closeMobile();
            } else {
              openMobile();
            }
            return;
          }

          const currentlyHidden = (window.getComputedStyle(sidebar).display === 'none') || sidebar.classList.contains('hidden');
          if (currentlyHidden) {
            sidebar.style.display = '';
            sidebar.classList.remove('hidden');
            sidebar.removeAttribute('aria-hidden');
            main.classList.remove('hidden');
            main.style.marginLeft = '';
            document.documentElement.classList.remove('mm-sidebar-hidden');
            localStorage.setItem(KEY_HIDDEN, '0');
          } else {
            sidebar.classList.add('hidden');
            sidebar.setAttribute('aria-hidden', 'true');
            sidebar.style.display = 'none';
            main.classList.add('hidden');
            main.style.marginLeft = '0px';
            document.documentElement.classList.add('mm-sidebar-hidden');
            localStorage.setItem(KEY_HIDDEN, '1');
          }
        } catch (e) {
          console.error('Sidebar toggle error', e);
        }
    });

    overlay.addEventListener('click', function () { closeMobile(); });

    // Sidebar search/filter (unchanged)
    const searchInput = document.getElementById('mmSidebarSearch');
    if (searchInput) {
      let timer = null;
      searchInput.addEventListener('input', function (ev) {
        clearTimeout(timer);
        timer = setTimeout(function () {
          const q = (searchInput.value || '').trim().toLowerCase();
          const items = sidebar.querySelectorAll('.mm-nav > ul > li');
          items.forEach(li => {
            const labels = Array.from(li.querySelectorAll('a')).map(a => a.textContent.trim().toLowerCase()).join(' ');
            if (!q) {
              li.style.display = '';
              li.classList.remove('open');
              return;
            }
            if (labels.indexOf(q) !== -1) {
              li.style.display = '';
              if (li.classList.contains('has-sub')) li.classList.add('open');
            } else {
              li.style.display = 'none';
              li.classList.remove('open');
            }
          });
        }, 120);
      });
    }

    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeMobile(); });

    // Tooltip for collapsed sidebar (unchanged)
    let tooltipEl = null;
    function showTooltip(text, rect) {
      if (!tooltipEl) {
        tooltipEl = document.createElement('div');
        tooltipEl.className = 'mm-tooltip';
        document.body.appendChild(tooltipEl);
      }
      tooltipEl.textContent = text;
      const top = rect.top + (rect.height/2) - 18;
      const left = rect.right + 8;
      tooltipEl.style.top = Math.max(8, top) + 'px';
      tooltipEl.style.left = left + 'px';
      requestAnimationFrame(()=> tooltipEl.classList.add('show'));
    }
    function hideTooltip() {
      if (!tooltipEl) return;
      tooltipEl.classList.remove('show');
    }

    sidebar.querySelectorAll('.mm-nav a').forEach(a => {
      a.addEventListener('mouseenter', (ev)=>{
        if (!sidebar.classList.contains('collapsed')) return;
        const label = a.querySelector('.mm-label');
        const rect = a.getBoundingClientRect();
        const text = label ? label.textContent.trim() : (a.getAttribute('title') || a.textContent.trim());
        showTooltip(text, rect);
      });
      a.addEventListener('mouseleave', hideTooltip);
      a.addEventListener('focus', (ev)=>{
        if (!sidebar.classList.contains('collapsed')) return;
        const label = a.querySelector('.mm-label');
        const rect = a.getBoundingClientRect();
        const text = label ? label.textContent.trim() : (a.getAttribute('title') || a.textContent.trim());
        showTooltip(text, rect);
      });
      a.addEventListener('blur', hideTooltip);
    });

    // ── Active-link + submenu logic ────────────────────────────────
    // Only the link matching the current ?p=... is highlighted, and only
    // the submenu containing that link is expanded by default.
    (function () {
      const params = new URLSearchParams(window.location.search);
      const currentP = (params.get('p') || '').replace(/\\/g, '/').toLowerCase();

      // Mark the matching nav link active.
      let activeLink = null;
      sidebar.querySelectorAll('.mm-nav a, .mm-sidebar-bottom a').forEach(a => {
        const href = a.getAttribute('href') || '';
        const m = href.match(/[?&]p=([^&]+)/);
        if (!m) return;
        const linkP = decodeURIComponent(m[1]).replace(/\\/g, '/').toLowerCase();
        if (linkP === currentP) {
          a.classList.add('active');
          activeLink = a;
        }
      });

      // Open only the submenu containing the active link.
      if (activeLink) {
        const parentLi = activeLink.closest('.has-sub');
        if (parentLi) parentLi.classList.add('open');
        // If the active link is itself a sub-link (under a .has-sub parent),
        // also mark the parent .mm-nav-link so the section reads as current.
        const wrappingHasSub = activeLink.closest('li.has-sub');
        if (wrappingHasSub) {
          wrappingHasSub.classList.add('open');
          const parentNav = wrappingHasSub.querySelector(':scope > .mm-nav-link');
          if (parentNav) parentNav.classList.add('active');
        }
      }
    })();

    // Click handler on .has-sub parent: if it has its own page (direct href
    // to a real route), navigate to it; otherwise just toggle the submenu.
    sidebar.querySelectorAll('.has-sub > .mm-nav-link').forEach(link => {
      link.addEventListener('click', function (ev) {
        const li = this.closest('.has-sub');
        if (!li || !li.querySelector('.sub-menu')) return;
        if (sidebar.classList.contains('collapsed')) return;
        // If the parent has a real href (not "#"), let the click navigate.
        // The submenu still opens via CSS :has() / the active-link logic on
        // the next page. Only intercept when there's no destination.
        const href = this.getAttribute('href') || '';
        if (href && href !== '#' && href.indexOf('?p=') !== -1) {
          // Allow navigation, but also toggle so users can collapse without
          // leaving the page (chevron click pattern). We only toggle when the
          // user clicks the chevron icon specifically.
          if (ev.target && ev.target.closest && ev.target.closest('.chev')) {
            li.classList.toggle('open');
            ev.preventDefault();
            ev.stopPropagation();
          }
          return;
        }
        li.classList.toggle('open');
        ev.preventDefault();
        ev.stopPropagation();
      });
    });
  })();
</script>

<!-- CSRF auto-injector: every POST form gets the current session token
     stamped in just before submit. Eliminates the need to add the field
     by hand to every form in the codebase. -->
<script>
(function() {
  var tokenMeta = document.querySelector('meta[name="csrf-token"]');
  if (!tokenMeta) return;
  var token = tokenMeta.getAttribute('content') || '';
  document.addEventListener('submit', function(ev) {
    var f = ev.target;
    if (!f || f.tagName !== 'FORM') return;
    var method = (f.method || 'get').toLowerCase();
    if (method !== 'post') return;
    if (f.querySelector('input[name="_csrf"]')) return;
    var i = document.createElement('input');
    i.type = 'hidden'; i.name = '_csrf'; i.value = token;
    f.appendChild(i);
  }, true);
})();
</script>

<?php
// Re-derive the role rather than reusing $role from the sidebar block above:
// the page's $content() has run in between, and this must not depend on what
// any page left in scope.
$_alert_role = function_exists('auth_role') ? auth_role() : null;
?>
<?php if (in_array($_alert_role, ['merchant', 'super_admin'], true)): ?>
<!-- ── Order popup alerts (merchant: new orders · super admin: status changes) ── -->
<div class="modal fade" id="mmAlertModal" tabindex="-1" aria-hidden="true" aria-labelledby="mmAlertTitle">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-warning-subtle border-0">
        <h5 class="modal-title d-flex align-items-center gap-2" id="mmAlertTitle">
          <i data-lucide="bell-ring" class="mm-icon"></i>
          <span id="mmAlertTitleText">Alert</span>
        </h5>
      </div>
      <div class="modal-body">
        <p class="mb-0" id="mmAlertBody"></p>
        <p class="text-muted small mb-0 mt-2 d-none" id="mmAlertMore"></p>
      </div>
      <div class="modal-footer border-0 pt-0">
        <a href="#" class="btn btn-outline-secondary btn-sm d-none" id="mmAlertLink">View order</a>
        <button type="button" class="btn btn-mm btn-sm" id="mmAlertOk">OK</button>
      </div>
    </div>
  </div>
</div>
<script>
/**
 * Polls ?p=api/alerts and shows one popup (with a beep) per unseen alert.
 * Dismissing acknowledges it server-side so it never fires twice, on any device.
 */
(function () {
  'use strict';

  var POLL_MS  = 20000;
  var ENDPOINT = window.location.pathname + '?p=api/alerts';
  var metaTag  = document.querySelector('meta[name="csrf-token"]');
  var CSRF     = metaTag ? metaTag.getAttribute('content') : '';

  var modalEl = document.getElementById('mmAlertModal');
  if (!modalEl || !window.bootstrap) return;

  var modal    = new bootstrap.Modal(modalEl, { backdrop: 'static', keyboard: false });
  var titleEl  = document.getElementById('mmAlertTitleText');
  var bodyEl   = document.getElementById('mmAlertBody');
  var moreEl   = document.getElementById('mmAlertMore');
  var linkEl   = document.getElementById('mmAlertLink');
  var okBtn    = document.getElementById('mmAlertOk');

  /* ── sound ─────────────────────────────────────────────────────────────
   * Browsers refuse to start audio until the user has interacted with the
   * page, and every navigation resets that. So the context is resumed on the
   * first gesture; before that the popup still appears, just silently.
   * A generated beep avoids shipping an audio asset.
   */
  var actx = null;
  function audio() {
    if (actx) return actx;
    var Ctor = window.AudioContext || window.webkitAudioContext;
    if (!Ctor) return null;
    try { actx = new Ctor(); } catch (e) { return null; }
    return actx;
  }
  function unlock() {
    var a = audio();
    if (a && a.state === 'suspended') { a.resume().catch(function () {}); }
  }
  ['click', 'keydown', 'touchstart'].forEach(function (ev) {
    document.addEventListener(ev, unlock, { passive: true });
  });

  function beep(pulses) {
    var a = audio();
    if (!a || a.state !== 'running') return;   // still locked — popup carries it
    var t0 = a.currentTime;
    for (var i = 0; i < pulses; i++) {
      var osc  = a.createOscillator();
      var gain = a.createGain();
      var at   = t0 + i * 0.30;
      osc.type = 'sine';
      osc.frequency.setValueAtTime(880, at);
      // Ramp the gain — starting/stopping at full volume clicks audibly.
      gain.gain.setValueAtTime(0.0001, at);
      gain.gain.exponentialRampToValueAtTime(0.3, at + 0.02);
      gain.gain.exponentialRampToValueAtTime(0.0001, at + 0.24);
      osc.connect(gain); gain.connect(a.destination);
      osc.start(at); osc.stop(at + 0.26);
    }
  }

  /* ── queue ─────────────────────────────────────────────────────────────
   * One popup at a time. `queued` guards against the same alert being shown
   * twice if a poll laps a still-open modal.
   */
  var queue = [], queued = {}, showing = false, current = null;

  function enqueue(list) {
    list.forEach(function (a) {
      if (queued[a.id]) return;
      queued[a.id] = true;
      queue.push(a);
    });
    pump();
  }

  function pump() {
    if (showing || !queue.length) return;
    current = queue.shift();
    showing = true;
    titleEl.textContent = current.title;
    bodyEl.textContent  = current.body;
    if (queue.length) {
      moreEl.textContent = '+' + queue.length + ' more alert' + (queue.length > 1 ? 's' : '');
      moreEl.classList.remove('d-none');
    } else {
      moreEl.classList.add('d-none');
    }
    if (current.link) {
      linkEl.href = current.link;
      linkEl.classList.remove('d-none');
    } else {
      linkEl.classList.add('d-none');
    }
    if (window.lucide) window.lucide.createIcons();
    modal.show();
    beep(3);
  }

  function ack(id, cb) {
    var body = new URLSearchParams();
    body.append('_csrf', CSRF);
    body.append('ids[]', id);
    fetch(ENDPOINT, {
      method: 'POST',
      body: body,
      credentials: 'same-origin',
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    }).catch(function () {}).then(function () { if (cb) cb(); });
  }

  function dismiss(then) {
    var id = current ? current.id : null;
    modal.hide();
    showing = false;
    current = null;
    if (id) { ack(id, then); } else if (then) { then(); }
    setTimeout(pump, 400);   // let the hide animation finish
  }

  okBtn.addEventListener('click', function () { dismiss(); });

  // Acknowledge before navigating, so the alert doesn't re-fire on the target page.
  linkEl.addEventListener('click', function (ev) {
    if (!current || !current.link) return;
    ev.preventDefault();
    var href = current.link;
    dismiss(function () { window.location.href = href; });
  });

  function poll() {
    fetch(ENDPOINT, {
      credentials: 'same-origin',
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
      .then(function (r) { return r.ok ? r.json() : null; })
      .then(function (j) { if (j && j.ok && j.alerts && j.alerts.length) enqueue(j.alerts); })
      .catch(function () {});   // offline / session gone — just try again next tick
  }

  poll();
  setInterval(poll, POLL_MS);   // keep polling in background tabs: that's when it matters most
})();
</script>
<?php endif; ?>
</body>
</html>