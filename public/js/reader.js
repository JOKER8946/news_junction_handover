/**
 * News Junction - News Reader & Feed Engine
 */

let currentCategoryId = 1;
let currentDistrict = '';
let currentSearch = '';
let searchDebounceTimer = null;
let activeUtterance = null;
let currentPlayingId = null;

document.addEventListener('DOMContentLoaded', async () => {
  checkAuth();
  await loadCategories();
  await loadChannels();
  await loadOpinions();
  await loadArticles();
  initFilters();
  initModal();
});

// Authentication and Header State
async function checkAuth() {
  const container = document.getElementById('readerAuthContainer');
  try {
    const res = await API.auth.checkSession();
    if (res.authenticated && res.user) {
      const initial = (res.user.fullName || 'U').charAt(0).toUpperCase();
      container.innerHTML = `
        <a href="/profile.html" class="nav-user-badge">
          <div class="nav-user-avatar">${initial}</div>
          <span>${res.user.fullName}</span>
        </a>
        <a href="/api/auth/logout" class="btn-nav-login" style="padding: 4px 12px; font-size: 0.85rem;">Logout</a>
      `;
    }
  } catch (err) {
    console.warn('Auth check skipped:', err.message);
  }
}

// Load Categories Pills
async function loadCategories() {
  const container = document.getElementById('categoryPills');
  try {
    const categories = await API.reader.getCategories();
    container.innerHTML = categories.map(cat => `
      <button class="cat-pill ${cat.id === currentCategoryId ? 'active' : ''}" data-id="${cat.id}">
        ${cat.category}
      </button>
    `).join('');

    container.querySelectorAll('.cat-pill').forEach(pill => {
      pill.addEventListener('click', () => {
        container.querySelectorAll('.cat-pill').forEach(p => p.classList.remove('active'));
        pill.classList.add('active');
        currentCategoryId = parseInt(pill.getAttribute('data-id'), 10);
        loadArticles();
      });
    });
  } catch (err) {
    console.error('Error loading categories:', err);
  }
}

// Load Channels
async function loadChannels() {
  const container = document.getElementById('channelsTrack');
  try {
    const channels = await API.reader.getChannels();
    container.innerHTML = channels.map(c => `
      <div class="channel-pill-card">
        <img src="${c.profilePic || '/images/district.png'}" onerror="this.src='/images/district.png'" alt="${c.name}" />
        <div class="channel-info">
          <h4>${c.name}</h4>
          <span>${c.subscribers || 500} followers</span>
        </div>
      </div>
    `).join('');
  } catch (err) {
    console.error('Error loading channels:', err);
  }
}

// Load Opinions / Influencers
async function loadOpinions() {
  const container = document.getElementById('opinionsTrack');
  try {
    const opinions = await API.reader.getOpinions();
    container.innerHTML = opinions.map(op => `
      <div class="channel-pill-card" style="border-left: 3px solid var(--color-gold);">
        <img src="/data/profilePic/${op.profile_pic}" onerror="this.src='/grfx/images/logo.png'" alt="${op.full_name}" />
        <div class="channel-info">
          <h4>${op.full_name}</h4>
          <span>${op.title || 'Columnist'}</span>
        </div>
      </div>
    `).join('');
  } catch (err) {
    console.error('Error loading opinions:', err);
  }
}

// Load Articles with current filters
async function loadArticles() {
  const grid = document.getElementById('articlesGrid');
  const emptyMsg = document.getElementById('noArticlesMsg');
  const countBadge = document.getElementById('feedCountBadge');

  grid.innerHTML = '<div style="grid-column: 1/-1; text-align:center; padding:40px;">Loading verified stories...</div>';
  emptyMsg.style.display = 'none';

  try {
    const data = await API.reader.getFeeds({
      categoryId: currentCategoryId,
      district: currentDistrict,
      search: currentSearch
    });

    if (!data.articles || data.articles.length === 0) {
      grid.innerHTML = '';
      emptyMsg.style.display = 'block';
      countBadge.textContent = '0 stories';
      return;
    }

    countBadge.textContent = `${data.total} stories found`;

    grid.innerHTML = data.articles.map(a => `
      <article class="article-card" id="article-${a.id}">
        <div class="card-media">
          <img src="${a.image || '/images/district.png'}" alt="${a.title}" loading="lazy" />
          <span class="card-badge">${a.category_name || 'News'}</span>
        </div>
        <div class="card-body">
          <div class="card-meta">
            <span>📍 ${a.district || 'Karnataka'}</span>
            <span>🕒 ${new Date(a.date).toLocaleDateString()}</span>
          </div>
          <h3 class="card-title">${a.title}</h3>
          <p class="card-excerpt">${a.description ? a.description.slice(0, 130) + '...' : ''}</p>
          
          <div class="card-footer">
            <div style="display: flex; gap: 8px;">
              <a href="/article.html?id=${a.id}" class="btn-read">Read</a>
              <button class="btn-read btn-quick-read" data-id="${a.id}" style="background:#0e2385;">Quick View</button>
            </div>

            <div class="action-buttons">
              <!-- Audio TTS -->
              <button class="icon-btn btn-audio-play" data-id="${a.id}" data-title="${encodeURIComponent(a.title)}" data-desc="${encodeURIComponent(a.description || '')}" title="Listen to summary">
                🔊
              </button>

              <!-- Like -->
              <button class="icon-btn btn-like ${a.isLiked ? 'active' : ''}" data-id="${a.id}">
                <span>${a.isLiked ? '❤️' : '🤍'}</span>
                <span class="like-count">${a.likes || 0}</span>
              </button>

              <!-- Bookmark -->
              <button class="icon-btn btn-bookmark ${a.isBookmarked ? 'active' : ''}" data-id="${a.id}" title="Save to collection">
                <span>${a.isBookmarked ? '🔖' : '📑'}</span>
              </button>
            </div>
          </div>
        </div>
      </article>
    `).join('');

    attachCardEventListeners();
  } catch (err) {
    grid.innerHTML = '<div style="grid-column: 1/-1; text-align:center; padding:40px; color:#ef4444;">Failed to load stories. Please retry.</div>';
  }
}

// Card button handlers
function attachCardEventListeners() {
  // Quick View Modal
  document.querySelectorAll('.btn-quick-read').forEach(btn => {
    btn.addEventListener('click', async () => {
      const id = btn.getAttribute('data-id');
      await openQuickView(id);
    });
  });

  // Like button
  document.querySelectorAll('.btn-like').forEach(btn => {
    btn.addEventListener('click', async () => {
      const id = btn.getAttribute('data-id');
      try {
        const res = await API.reader.toggleLike(id);
        const countSpan = btn.querySelector('.like-count');
        const iconSpan = btn.querySelector('span:first-child');
        countSpan.textContent = res.likes;
        if (res.liked) {
          btn.classList.add('active');
          iconSpan.textContent = '❤️';
        } else {
          btn.classList.remove('active');
          iconSpan.textContent = '🤍';
        }
      } catch (err) {
        console.error('Like error:', err);
      }
    });
  });

  // Bookmark button
  document.querySelectorAll('.btn-bookmark').forEach(btn => {
    btn.addEventListener('click', async () => {
      const id = btn.getAttribute('data-id');
      try {
        const res = await API.reader.toggleBookmark(id);
        const iconSpan = btn.querySelector('span:first-child');
        if (res.bookmarked) {
          btn.classList.add('active');
          iconSpan.textContent = '🔖';
        } else {
          btn.classList.remove('active');
          iconSpan.textContent = '📑';
        }
      } catch (err) {
        if (err.message.includes('sign in')) {
          window.location.assign('/sign-in.html');
        }
      }
    });
  });

  // Audio speech synthesis
  document.querySelectorAll('.btn-audio-play').forEach(btn => {
    btn.addEventListener('click', () => {
      const id = btn.getAttribute('data-id');
      const title = decodeURIComponent(btn.getAttribute('data-title'));
      const desc = decodeURIComponent(btn.getAttribute('data-desc'));

      if ('speechSynthesis' in window) {
        if (window.speechSynthesis.speaking && currentPlayingId === id) {
          window.speechSynthesis.cancel();
          btn.textContent = '🔊';
          currentPlayingId = null;
          return;
        }

        window.speechSynthesis.cancel();
        const text = `${title}. ${desc}`;
        activeUtterance = new SpeechSynthesisUtterance(text);
        activeUtterance.rate = 1.0;
        activeUtterance.pitch = 1.0;

        activeUtterance.onend = () => {
          btn.textContent = '🔊';
          currentPlayingId = null;
        };

        btn.textContent = '⏸️';
        currentPlayingId = id;
        window.speechSynthesis.speak(activeUtterance);
      } else {
        alert('Text-to-speech is not supported in this browser.');
      }
    });
  });
}

// Quick View Modal
async function openQuickView(id) {
  const modal = document.getElementById('readModal');
  const container = document.getElementById('modalArticleContent');
  modal.style.display = 'flex';
  container.innerHTML = '<p style="text-align:center; padding:30px;">Loading article details...</p>';

  try {
    const a = await API.articles.getById(id);
    container.innerHTML = `
      <div style="margin-bottom:14px;">
        <span class="card-badge" style="position:static;">${a.category_name}</span>
        <span style="font-size:0.85rem; color:#64748b; margin-left:10px;">${new Date(a.date).toLocaleDateString()}</span>
      </div>
      <h2 style="font-size:1.6rem; color:var(--color-primary); margin-bottom:14px;">${a.title}</h2>
      ${a.image ? `<img src="${a.image}" style="width:100%; max-height:280px; object-fit:cover; border-radius:8px; margin-bottom:16px;" />` : ''}
      <div style="font-size:1rem; line-height:1.7; color:#334155; margin-bottom:20px;">
        ${a.content || `<p>${a.description}</p>`}
      </div>
      <div style="display:flex; justify-content:space-between; align-items:center; border-top:1px solid #e2e8f0; padding-top:16px;">
        <span style="font-size:0.9rem; color:#64748b;">Source: ${a.publisher}</span>
        <a href="/article.html?id=${a.id}" class="btn-read">Full Discussion & Comments &rarr;</a>
      </div>
    `;
  } catch (err) {
    container.innerHTML = '<p style="color:#ef4444;">Failed to load article preview.</p>';
  }
}

function initModal() {
  const modal = document.getElementById('readModal');
  const closeBtn = document.getElementById('closeReadModalBtn');
  closeBtn.addEventListener('click', () => {
    modal.style.display = 'none';
  });
  modal.addEventListener('click', (e) => {
    if (e.target === modal) modal.style.display = 'none';
  });
}

// Search & District input handlers
function initFilters() {
  const searchInput = document.getElementById('searchInput');
  const districtSelect = document.getElementById('districtSelect');
  const resetBtn = document.getElementById('resetFilterBtn');

  searchInput.addEventListener('input', () => {
    clearTimeout(searchDebounceTimer);
    searchDebounceTimer = setTimeout(() => {
      currentSearch = searchInput.value.trim();
      loadArticles();
    }, 300);
  });

  districtSelect.addEventListener('change', () => {
    currentDistrict = districtSelect.value;
    loadArticles();
  });

  resetBtn.addEventListener('click', () => {
    searchInput.value = '';
    districtSelect.value = '';
    currentSearch = '';
    currentDistrict = '';
    currentCategoryId = 1;
    document.querySelectorAll('.cat-pill').forEach((p, idx) => {
      p.classList.toggle('active', idx === 0);
    });
    loadArticles();
  });
}
