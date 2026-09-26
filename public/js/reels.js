/**
 * News Junction - Instagram-Style Reels & Shorts Component
 * Vertical scroll-snap feed with IntersectionObserver playback & interactive controls
 */

(function () {
  'use strict';

  // Curated vertical video dataset
  const REELS_DATA = [
    {
      id: 1,
      title: 'ಕರಾವಳಿ ಎಕ್ಸ್‌ಪ್ರೆಸ್‌ವೇ ಕಾಮಗಾರಿ: ಸ್ಥಳ ಪರಿಶೀಲನೆ ಮತ್ತು ಪ್ರಗತಿ ವರದಿ',
      englishTitle: 'Coastal Expressway Project: Ground Inspection & Progress',
      category: 'udupi',
      categoryLabel: 'Coastal Desk',
      district: 'Udupi',
      author: 'Udupi Coastal Desk',
      handle: '@udupi_live',
      avatar: '/images/udupi.png',
      time: '12m ago',
      videoSrc: '/videos/reel1.mp4',
      fallbackSrc: '/assets/img/createVideo.mp4',
      likes: 3420,
      comments: 284,
      shares: 950,
      views: '45.2K',
      audio: 'News Junction Live Audio - Brahmavar Bureau',
      description: 'NH-66 widening and flyover construction near Brahmavar enters final phase. District commissioner inspects key bottleneck junctions to ensure timely completion before monsoon.'
    },
    {
      id: 2,
      title: 'ಬೆಂಗಳೂರು ನಮ್ಮ ಮೆಟ್ರೋ ಹಳದಿ ಮಾರ್ಗ: ಟ್ರಯಲ್ ರನ್ ಯಶಸ್ವಿ',
      englishTitle: 'Bengaluru Namma Metro Yellow Line: Successful Trial Run',
      category: 'bengaluru',
      categoryLabel: 'Bengaluru',
      district: 'Bengaluru',
      author: 'Urban Transport Desk',
      handle: '@metro_updates',
      avatar: '/grfx/images/bengaluru.png',
      time: '45m ago',
      videoSrc: '/videos/reel2.mp4',
      fallbackSrc: '/assets/img/social_video.mp4',
      likes: 8190,
      comments: 512,
      shares: 2400,
      views: '112.5K',
      audio: 'BMRCL Ground Audio - Electronics City Station',
      description: 'Driverless train sets conducted high-speed stability testing between Bommasandra and Central Silk Board station ahead of commercial opening scheduled next quarter.'
    },
    {
      id: 3,
      title: 'ಮೈಸೂರು ದಸರಾ ಸಿದ್ಧತೆ: ಅರಮನೆ ಆವರಣದಲ್ಲಿ ಗಜಪಡೆ ತಾಲೀಮು',
      englishTitle: 'Mysuru Dasara Preparations: Elephant Squad Rehearsal',
      category: 'mysuru',
      categoryLabel: 'Mysuru',
      district: 'Mysuru',
      author: 'Mysuru Heritage Bureau',
      handle: '@mysuru_bulletin',
      avatar: '/images/mysore.png',
      time: '2h ago',
      videoSrc: '/videos/reel3.mp4',
      fallbackSrc: '/data/covers/375-1780054751.mp4',
      likes: 12400,
      comments: 890,
      shares: 4100,
      views: '180K',
      audio: 'Heritage Bell Chimes - Mysuru Palace Desks',
      description: 'Captain Abhimanyu leads the jumbo squad in daily weight-bearing training through the Raja Marga towards Bannimantap grounds under veterinary supervision.'
    },
    {
      id: 4,
      title: 'ಬೆಳಗಾವಿ ಸುವರ್ಣ ಸೌಧ: ರೈತರ ಬೆಳೆ ವಿಮೆ ಪರಿಹಾರ ನೇರ ವರ್ಗಾವಣೆ',
      englishTitle: 'Belagavi Suvarna Soudha: Farmer Crop Insurance Review',
      category: 'civic',
      categoryLabel: 'Civic & Agriculture',
      district: 'Belagavi',
      author: 'North Karnataka Bureau',
      handle: '@belagavi_live',
      avatar: '/images/belagavi.png',
      time: '3h ago',
      videoSrc: '/videos/reel4.mp4',
      fallbackSrc: '/data/covers/375-1780054841.mp4',
      likes: 4560,
      comments: 340,
      shares: 1200,
      views: '62K',
      audio: 'Ground Report Audio - Belagavi Vidhana Sabha',
      description: 'Cabinet sub-committee expedites Kharif drought and excessive rainfall compensation transfers directly to 4.2 lakh registered farmer bank accounts.'
    },
    {
      id: 5,
      title: 'ಹಾಸನ ಹೆದ್ದಾರಿ ಸುರಕ್ಷತೆ: ಕಡಿದಾದ ತಿರುವುಗಳಲ್ಲಿ ಸ್ವಯಂಚಾಲಿತ ಸೆನ್ಸಾರ್',
      englishTitle: 'Hassan Highway Safety: Advanced Warning Sensors',
      category: 'breaking',
      categoryLabel: 'Breaking News',
      district: 'Hassan',
      author: 'Hassan Express Desk',
      handle: '@hassan_express',
      avatar: '/images/hassan.png',
      time: '4h ago',
      videoSrc: '/videos/reel5.mp4',
      fallbackSrc: '/assets/img/createVideo.mp4',
      likes: 2890,
      comments: 198,
      shares: 670,
      views: '38K',
      audio: 'NH Safety Warning Sensor System',
      description: 'Shiradi Ghat section receives automated weather and landslide warning sensors connected to district disaster cell for real-time commuter safety.'
    },
    {
      id: 6,
      title: 'ಡಿಜಿಟಲ್ ಪತ್ರಿಕೋದ್ಯಮ ಕ್ರಾಂತಿ: ಪ್ರತಿಯೊಬ್ಬ ನಾಗರಿಕನೂ ವರದಿಗಾರ',
      englishTitle: 'Digital Journalism Revolution: Citizen Reporting Spotlight',
      category: 'breaking',
      categoryLabel: 'Citizen Grievances',
      district: 'Karnataka',
      author: 'News Junction Special',
      handle: '@newsjunction_network',
      avatar: '/images/verified.png',
      time: '6h ago',
      videoSrc: '/assets/img/createVideo.mp4',
      fallbackSrc: '/assets/img/social_video.mp4',
      likes: 9800,
      comments: 720,
      shares: 3300,
      views: '145K',
      audio: 'News Junction Signature Theme Audio',
      description: 'Verified grassroots reporting network connects 30+ districts to provide fact-checked updates, investigative stories, and citizen complaint resolutions.'
    }
  ];

  let currentFilter = 'all';
  let filteredReels = [...REELS_DATA];
  let activeIndex = 0;
  let isGlobalMuted = true;
  let intersectionObserver = null;

  // DOM Elements cache
  let scrollContainer = null;
  let paginationContainer = null;
  let metaSidebar = null;
  let muteIcon = null;
  let toastEl = null;

  // Initialize
  document.addEventListener('DOMContentLoaded', () => {
    initNavHeightSync();
    initReelsComponent();
  });

  /**
   * Sync sticky navigation height with CSS variable
   */
  function initNavHeightSync() {
    function updateHeight() {
      const nav = document.getElementById('mainNav');
      if (nav) {
        const h = nav.offsetHeight;
        document.documentElement.style.setProperty('--nav-height', `${h}px`);
      }
    }
    updateHeight();
    window.addEventListener('resize', updateHeight);
    window.addEventListener('orientationchange', updateHeight);
  }

  /**
   * Initialize Reels Component
   */
  function initReelsComponent() {
    scrollContainer = document.getElementById('reelsScrollContainer');
    if (!scrollContainer) return;

    paginationContainer = document.getElementById('reelsPagination');
    metaSidebar = document.getElementById('reelsMetaSidebar');
    muteIcon = document.getElementById('reelsMuteIcon');

    // Create toast notification element
    createToastElement();

    // Render reels cards
    renderReels();

    // Setup events
    setupHeaderControls();
    setupFilterChips();
    setupKeyboardNavigation();
    setupFullscreenModal();

    // Observe active reel on scroll snap
    setupIntersectionObserver();
  }

  /**
   * Toast notification helper
   */
  function createToastElement() {
    if (document.getElementById('reelsToast')) return;
    toastEl = document.createElement('div');
    toastEl.id = 'reelsToast';
    toastEl.className = 'reels-toast';
    document.body.appendChild(toastEl);
  }

  function showToast(message) {
    if (!toastEl) return;
    toastEl.textContent = message;
    toastEl.classList.add('show');
    clearTimeout(toastEl._timer);
    toastEl._timer = setTimeout(() => {
      toastEl.classList.remove('show');
    }, 2800);
  }

  /**
   * Render Reels based on active filter
   */
  function renderReels() {
    if (!scrollContainer) return;

    // Filter data
    filteredReels = currentFilter === 'all'
      ? REELS_DATA
      : REELS_DATA.filter(r => r.category === currentFilter);

    if (filteredReels.length === 0) {
      filteredReels = REELS_DATA;
    }

    // Build Cards HTML
    scrollContainer.innerHTML = filteredReels.map((reel, index) => `
      <div class="reel-card" data-index="${index}" data-id="${reel.id}">
        <!-- Top Video Progress Bar -->
        <div class="reel-progress-track">
          <div class="reel-progress-fill" id="progressFill-${index}"></div>
        </div>

        <!-- Video Element (Full Viewport Fill) -->
        <video 
          class="reel-video" 
          id="reelVideo-${index}"
          src="${reel.videoSrc}"
          loop
          playsinline
          preload="metadata"
          ${isGlobalMuted ? 'muted' : ''}
        >
          <source src="${reel.videoSrc}" type="video/mp4">
          <source src="${reel.fallbackSrc}" type="video/mp4">
          Your browser does not support the video tag.
        </video>

        <!-- Tap Play/Pause Flash Overlay -->
        <div class="reel-play-indicator" id="playIndicator-${index}">
          ▶
        </div>

        <!-- Top Header Overlay -->
        <div class="reel-top-bar">
          <span class="reel-badge-tag">
            <span style="display:inline-block; width:8px; height:8px; border-radius:50%; background:#ef4444; animation: pulse 1.5s infinite;"></span>
            ${reel.categoryLabel}
          </span>
          <button class="reel-audio-btn" data-action="toggle-audio" title="Mute/Unmute Audio">
            ${isGlobalMuted ? '🔇' : '🔊'}
          </button>
        </div>

        <!-- Right Action Bar (Instagram / TikTok Style) -->
        <div class="reel-actions-bar">
          <div class="reel-action-item">
            <button class="reel-action-btn" data-action="like" data-id="${reel.id}" title="Like this story">
              ❤️
            </button>
            <span class="reel-action-count" id="likeCount-${reel.id}">${formatNumber(reel.likes)}</span>
          </div>

          <div class="reel-action-item">
            <button class="reel-action-btn" data-action="comment" data-id="${reel.id}" title="Discussion">
              💬
            </button>
            <span class="reel-action-count">${formatNumber(reel.comments)}</span>
          </div>

          <div class="reel-action-item">
            <button class="reel-action-btn" data-action="share" data-id="${reel.id}" title="Share Reel">
              ↗️
            </button>
            <span class="reel-action-count">${formatNumber(reel.shares)}</span>
          </div>

          <div class="reel-action-item">
            <button class="reel-action-btn" data-action="save" data-id="${reel.id}" title="Bookmark Story">
              🔖
            </button>
            <span class="reel-action-count">Save</span>
          </div>
        </div>

        <!-- Bottom Story Information Overlay -->
        <div class="reel-bottom-bar">
          <div class="reel-author-row">
            <img class="reel-author-avatar" src="${reel.avatar}" alt="${reel.author}" onerror="this.src='/images/verified.png'" />
            <span class="reel-author-name">${reel.handle}</span>
            <span class="reel-follow-chip" data-action="follow">+ Follow</span>
          </div>

          <h3 class="reel-caption-title">${reel.title}</h3>

          <div class="reel-audio-track">
            <span>🎵</span>
            <span>${reel.audio}</span>
          </div>
        </div>
      </div>
    `).join('');

    // Build Pagination Dots
    if (paginationContainer) {
      paginationContainer.innerHTML = filteredReels.map((_, i) => `
        <div class="reels-dot ${i === 0 ? 'active' : ''}" data-index="${i}"></div>
      `).join('');

      paginationContainer.querySelectorAll('.reels-dot').forEach(dot => {
        dot.addEventListener('click', () => {
          const idx = parseInt(dot.getAttribute('data-index'), 10);
          scrollToReel(idx);
        });
      });
    }

    // Attach card event listeners
    attachCardListeners();

    // Reconnect intersection observer
    setupIntersectionObserver();

    // Update active metadata sidebar
    updateMetaSidebar(0);
  }

  /**
   * Attach Listeners on Reel Cards (Click to play/pause, like, share, audio)
   */
  function attachCardListeners() {
    const cards = scrollContainer.querySelectorAll('.reel-card');

    cards.forEach((card, idx) => {
      const video = card.querySelector('.reel-video');
      const progressFill = card.querySelector('.reel-progress-fill');
      const playIndicator = card.querySelector('.reel-play-indicator');

      // Click video to toggle Play/Pause
      video.addEventListener('click', (e) => {
        e.stopPropagation();
        togglePlayPause(video, playIndicator);
      });

      // Update progress bar
      video.addEventListener('timeupdate', () => {
        if (video.duration && progressFill) {
          const pct = (video.currentTime / video.duration) * 100;
          progressFill.style.width = `${pct}%`;
        }
      });

      // Handle Action buttons inside card
      card.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-action]');
        if (!btn) return;
        e.stopPropagation();

        const action = btn.getAttribute('data-action');
        const reel = filteredReels[idx];

        if (action === 'toggle-audio') {
          toggleGlobalMute();
        } else if (action === 'like') {
          btn.classList.toggle('liked');
          const countEl = card.querySelector(`#likeCount-${reel.id}`);
          if (btn.classList.contains('liked')) {
            reel.likes += 1;
            btn.style.transform = 'scale(1.25)';
            setTimeout(() => btn.style.transform = '', 200);
            showToast('❤️ Added to Liked Stories!');
          } else {
            reel.likes -= 1;
          }
          if (countEl) countEl.textContent = formatNumber(reel.likes);
        } else if (action === 'share') {
          handleShareReel(reel);
        } else if (action === 'save') {
          btn.classList.toggle('liked');
          showToast(btn.classList.contains('liked') ? '🔖 Story saved to bookmarks!' : 'Removed from bookmarks');
        } else if (action === 'follow') {
          if (btn.textContent.includes('Follow')) {
            btn.textContent = '✓ Following';
            btn.style.background = 'var(--color-primary)';
            showToast(`Now following ${reel.handle}!`);
          } else {
            btn.textContent = '+ Follow';
            btn.style.background = '';
          }
        }
      });
    });
  }

  /**
   * Toggle Video Play/Pause with visual indicator
   */
  function togglePlayPause(video, indicator) {
    if (video.paused) {
      video.play().then(() => {
        if (indicator) {
          indicator.textContent = '▶';
          indicator.classList.add('show');
          setTimeout(() => indicator.classList.remove('show'), 400);
        }
      }).catch(err => {
        console.warn('Autoplay prevented:', err);
      });
    } else {
      video.pause();
      if (indicator) {
        indicator.textContent = '⏸';
        indicator.classList.add('show');
        setTimeout(() => indicator.classList.remove('show'), 400);
      }
    }
  }

  /**
   * IntersectionObserver: Detects which reel is snapped into view
   * Autoplays snapped reel and pauses out-of-view reels
   */
  function setupIntersectionObserver() {
    if (intersectionObserver) {
      intersectionObserver.disconnect();
    }

    const options = {
      root: scrollContainer,
      threshold: 0.65 // Trigger when reel card is 65% in view
    };

    intersectionObserver = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        const card = entry.target;
        const index = parseInt(card.getAttribute('data-index'), 10);
        const video = card.querySelector('.reel-video');

        if (entry.isIntersecting) {
          activeIndex = index;
          updatePagination(index);
          updateMetaSidebar(index);

          // Play active video
          if (video) {
            video.muted = isGlobalMuted;
            const playPromise = video.play();
            if (playPromise !== undefined) {
              playPromise.catch(() => {
                // Browsers may block unmuted autoplay; ensure muted
                video.muted = true;
                video.play().catch(e => console.warn('Playback error:', e));
              });
            }
          }
        } else {
          // Pause when scrolled out of view
          if (video && !video.paused) {
            video.pause();
          }
        }
      });
    }, options);

    const cards = scrollContainer.querySelectorAll('.reel-card');
    cards.forEach(card => intersectionObserver.observe(card));
  }

  /**
   * Scroll Programmatically to Reel Index
   */
  function scrollToReel(index) {
    if (index < 0 || index >= filteredReels.length) return;
    const cardHeight = scrollContainer.clientHeight;
    scrollContainer.scrollTo({
      top: index * cardHeight,
      behavior: 'smooth'
    });
  }

  /**
   * Update active pagination indicator
   */
  function updatePagination(index) {
    if (!paginationContainer) return;
    const dots = paginationContainer.querySelectorAll('.reels-dot');
    dots.forEach((dot, i) => {
      dot.classList.toggle('active', i === index);
    });
  }

  /**
   * Update Desktop Meta Sidebar
   */
  function updateMetaSidebar(index) {
    if (!metaSidebar) return;
    const reel = filteredReels[index];
    if (!reel) return;

    metaSidebar.innerHTML = `
      <div>
        <span class="reels-meta-badge">📍 ${reel.district} &bull; ${reel.categoryLabel}</span>
        <h3 class="reels-meta-title" style="margin-top: 6px;">${reel.title}</h3>
        <p style="font-size:0.85rem; color:#94a3b8; margin-top:4px;">${reel.englishTitle}</p>
      </div>

      <p class="reels-meta-desc">${reel.description}</p>

      <div class="reels-meta-stats-row">
        <div>
          <div class="meta-stat-val">${reel.views}</div>
          <div class="meta-stat-lbl">Views</div>
        </div>
        <div>
          <div class="meta-stat-val" id="sideLikesVal">${formatNumber(reel.likes)}</div>
          <div class="meta-stat-lbl">Likes</div>
        </div>
        <div>
          <div class="meta-stat-val">${formatNumber(reel.shares)}</div>
          <div class="meta-stat-lbl">Shares</div>
        </div>
      </div>

      <div style="background: rgba(15, 23, 42, 0.4); border-radius: 8px; padding: 12px; border: 1px solid rgba(255,255,255,0.06);">
        <div style="font-size:0.82rem; color:#94a3b8;">Reported By</div>
        <div style="font-weight:700; color:#ffffff; font-size:0.95rem; margin-top:2px;">${reel.author}</div>
        <div style="font-size:0.78rem; color:#f59e0b; margin-top:2px;">Verified Field Correspondent &bull; 100% Fact-checked</div>
      </div>

      <div class="reels-comments-preview">
        <div class="comments-title">Live Citizen Reactions (<span id="sidebarCommentCount">${reel.comments}</span>)</div>
        <div id="sidebarCommentsList">
          <div class="comment-mini">
            <strong>Ramesh_Udupi:</strong> Good to see ground progress on this stretch!
          </div>
          <div class="comment-mini">
            <strong>CivicVoice:</strong> Transparent reporting, keep it up News Junction!
          </div>
          <div class="comment-mini">
            <strong>Pradeep_K:</strong> Please follow up next week on drainage works.
          </div>
        </div>

        <form id="reelCommentForm" style="display:flex; gap:8px; margin-top:12px;">
          <input type="text" id="reelCommentInput" placeholder="Add a public comment..." style="flex:1; background:rgba(15,23,42,0.85); border:1px solid rgba(255,255,255,0.2); border-radius:20px; padding:8px 14px; color:#ffffff; font-size:0.85rem; outline:none;" required />
          <button type="submit" style="background:var(--color-primary); color:#ffffff; border:none; border-radius:20px; padding:0 14px; font-weight:600; cursor:pointer; font-size:0.82rem; transition:var(--transition);">Post</button>
        </form>
      </div>
    `;

    // Interactive comment submission
    const commentForm = metaSidebar.querySelector('#reelCommentForm');
    if (commentForm) {
      commentForm.addEventListener('submit', (e) => {
        e.preventDefault();
        const input = metaSidebar.querySelector('#reelCommentInput');
        const text = input ? input.value.trim() : '';
        if (!text) return;

        const commentsList = metaSidebar.querySelector('#sidebarCommentsList');
        if (commentsList) {
          const newComment = document.createElement('div');
          newComment.className = 'comment-mini';
          newComment.innerHTML = `<strong>You:</strong> ${escapeHtml(text)}`;
          commentsList.prepend(newComment);
        }

        reel.comments += 1;
        const countSpan = metaSidebar.querySelector('#sidebarCommentCount');
        if (countSpan) countSpan.textContent = reel.comments;

        const card = scrollContainer.querySelector(`.reel-card[data-index="${index}"]`);
        if (card) {
          const cardCommentCount = card.querySelector('[data-action="comment"] + .reel-action-count');
          if (cardCommentCount) cardCommentCount.textContent = formatNumber(reel.comments);
        }

        if (input) input.value = '';
        showToast('💬 Comment posted to this story!');
      });
    }
  }

  function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
  }

  /**
   * Header Controls (Prev, Next, Sound Toggle)
   */
  function setupHeaderControls() {
    const prevBtn = document.getElementById('reelsPrevBtn');
    const nextBtn = document.getElementById('reelsNextBtn');
    const globalMuteBtn = document.getElementById('reelsGlobalMuteBtn');

    if (prevBtn) {
      prevBtn.addEventListener('click', () => {
        scrollToReel(activeIndex - 1);
      });
    }

    if (nextBtn) {
      nextBtn.addEventListener('click', () => {
        scrollToReel(activeIndex + 1);
      });
    }

    if (globalMuteBtn) {
      globalMuteBtn.addEventListener('click', toggleGlobalMute);
    }
  }

  /**
   * Toggle Global Sound
   */
  function toggleGlobalMute() {
    isGlobalMuted = !isGlobalMuted;

    // Update mute icons
    if (muteIcon) {
      muteIcon.textContent = isGlobalMuted ? '🔇' : '🔊';
    }

    const audioBtns = scrollContainer.querySelectorAll('[data-action="toggle-audio"]');
    audioBtns.forEach(btn => {
      btn.textContent = isGlobalMuted ? '🔇' : '🔊';
    });

    // Update all video elements
    const videos = scrollContainer.querySelectorAll('.reel-video');
    videos.forEach(v => {
      v.muted = isGlobalMuted;
    });

    showToast(isGlobalMuted ? '🔇 Audio muted' : '🔊 Audio enabled');
  }

  /**
   * Filter Chips (Categories & Districts)
   */
  function setupFilterChips() {
    const chips = document.querySelectorAll('.reels-filter-chip');
    chips.forEach(chip => {
      chip.addEventListener('click', () => {
        chips.forEach(c => c.classList.remove('active'));
        chip.classList.add('active');
        currentFilter = chip.getAttribute('data-filter') || 'all';
        activeIndex = 0;
        renderReels();
        scrollContainer.scrollTop = 0;
        showToast(`Filtered: ${chip.textContent.trim()}`);
      });
    });
  }

  /**
   * Keyboard Arrow Navigation (Up / Down)
   */
  function setupKeyboardNavigation() {
    window.addEventListener('keydown', (e) => {
      const reelsSection = document.getElementById('reelsSection');
      if (!reelsSection) return;

      const rect = reelsSection.getBoundingClientRect();
      const inView = rect.top < window.innerHeight && rect.bottom > 0;

      // Only handle arrows if reels section is on screen
      if (!inView) return;

      if (e.key === 'ArrowDown') {
        e.preventDefault();
        scrollToReel(activeIndex + 1);
      } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        scrollToReel(activeIndex - 1);
      }
    });
  }

  /**
   * Fullscreen Modal Viewer
   */
  function setupFullscreenModal() {
    const fsBtn = document.getElementById('reelsFullscreenBtn');
    if (!fsBtn) return;

    // Build modal markup if not present
    let modal = document.getElementById('reelsFullscreenModal');
    if (!modal) {
      modal = document.createElement('div');
      modal.id = 'reelsFullscreenModal';
      modal.className = 'reels-fullscreen-modal';
      modal.innerHTML = `
        <button class="reels-fullscreen-close" id="closeFsModalBtn" title="Close Viewer">&times;</button>
        <div class="reels-modal-inner" id="reelsModalInner"></div>
      `;
      document.body.appendChild(modal);
    }

    const closeBtn = document.getElementById('closeFsModalBtn');
    const modalInner = document.getElementById('reelsModalInner');

    function openModal() {
      const activeReel = filteredReels[activeIndex] || filteredReels[0];
      modalInner.innerHTML = `
        <div class="reel-card" style="border-radius:20px; box-shadow:0 25px 60px rgba(0,0,0,0.8);">
          <video class="reel-video" id="fsVideo" src="${activeReel.videoSrc}" autoplay loop playsinline ${isGlobalMuted ? 'muted' : ''}>
            <source src="${activeReel.videoSrc}" type="video/mp4">
          </video>
          <div class="reel-top-bar">
            <span class="reel-badge-tag">🔴 FULLSCREEN &bull; ${activeReel.categoryLabel}</span>
          </div>
          <div class="reel-bottom-bar">
            <div class="reel-author-row">
              <span class="reel-author-name">${activeReel.author}</span>
            </div>
            <h3 class="reel-caption-title">${activeReel.title}</h3>
            <p style="font-size:0.85rem; color:#cbd5e1; margin-top:4px;">${activeReel.description}</p>
          </div>
        </div>
      `;
      modal.classList.add('active');
      document.body.style.overflow = 'hidden';

      const fsVideo = document.getElementById('fsVideo');
      if (fsVideo) fsVideo.play().catch(() => {});
    }

    function closeModal() {
      const fsVideo = document.getElementById('fsVideo');
      if (fsVideo) fsVideo.pause();
      modal.classList.remove('active');
      document.body.style.overflow = '';
    }

    fsBtn.addEventListener('click', openModal);
    closeBtn.addEventListener('click', closeModal);

    modal.addEventListener('click', (e) => {
      if (e.target === modal) closeModal();
    });

    window.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && modal.classList.contains('active')) {
        closeModal();
      }
    });
  }

  /**
   * Share Reel Action
   */
  function handleShareReel(reel) {
    const shareUrl = `${window.location.origin}/#reelsSection?id=${reel.id}`;
    if (navigator.share) {
      navigator.share({
        title: reel.title,
        text: `${reel.title} - Watch live on News Junction`,
        url: shareUrl
      }).catch(() => {});
    } else if (navigator.clipboard) {
      navigator.clipboard.writeText(shareUrl).then(() => {
        showToast('🔗 Story link copied to clipboard!');
      }).catch(() => {
        showToast('Sharing not supported on this browser.');
      });
    } else {
      showToast('Share link: ' + shareUrl);
    }
  }

  /**
   * Helper: Number formatting (e.g. 1.2K)
   */
  function formatNumber(num) {
    if (!num) return '0';
    if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M';
    if (num >= 1000) return (num / 1000).toFixed(1) + 'K';
    return num.toString();
  }

})();
