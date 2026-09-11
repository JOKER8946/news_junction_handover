/**
 * News Junction - Homepage logic
 */

document.addEventListener('DOMContentLoaded', async () => {
  initNavbar();
  initChat();
  checkAuth();
  loadAds();
  loadHeadlines();
});

// Sticky Navbar scroll listener
function initNavbar() {
  const nav = document.getElementById('mainNav');
  window.addEventListener('scroll', () => {
    if (window.scrollY > 40) {
      nav.classList.add('stuck');
    } else {
      nav.classList.remove('stuck');
    }
  });
}

// Session check
async function checkAuth() {
  const container = document.getElementById('navAuthContainer');
  try {
    const res = await API.auth.checkSession();
    if (res.authenticated && res.user) {
      const initial = (res.user.fullName || 'U').charAt(0).toUpperCase();
      container.innerHTML = `
        <a href="/profile.html" class="nav-user-badge">
          <div class="nav-user-avatar">${initial}</div>
          <span>${res.user.fullName}</span>
        </a>
        <a href="/api/auth/logout" class="btn-nav-login" style="padding: 5px 12px; font-size: 0.85rem;">Logout</a>
      `;
    }
  } catch (err) {
    console.warn('Auth check skipped:', err.message);
  }
}

// Load Ads
async function loadAds() {
  try {
    const topAds = await API.ads.get('top');
    const topContainer = document.getElementById('topAdBanner');
    if (topAds && topAds.length > 0) {
      const ad = topAds[0];
      topContainer.innerHTML = `
        <a href="/api/ads/click/${ad.id}" class="ad-card" target="_blank" rel="noopener">
          <span class="ad-badge">Partner Ad</span>
          <img src="${ad.image}" alt="${ad.title}" onerror="this.src='/images/1(1).png'" />
          <div class="ad-content">
            <h4>${ad.title}</h4>
            <p>${ad.description}</p>
          </div>
        </a>
      `;
    }

    const feedAds = await API.ads.get('feed');
    const feedContainer = document.getElementById('feedAdBanner');
    if (feedAds && feedAds.length > 0) {
      const ad = feedAds[feedAds.length > 1 ? 1 : 0];
      feedContainer.innerHTML = `
        <a href="/api/ads/click/${ad.id}" class="ad-card" target="_blank" rel="noopener">
          <span class="ad-badge">Sponsored Highlight</span>
          <img src="${ad.image}" alt="${ad.title}" onerror="this.src='/images/ethical.png'" />
          <div class="ad-content">
            <h4>${ad.title}</h4>
            <p>${ad.description}</p>
          </div>
        </a>
      `;
    }
  } catch (err) {
    console.warn('Could not load ads:', err);
  }
}

// Load Top Headlines
async function loadHeadlines() {
  const container = document.getElementById('featuredArticlesGrid');
  try {
    const data = await API.reader.getFeeds({ limit: 3 });
    if (!data.articles || data.articles.length === 0) {
      container.innerHTML = '<p>No headlines available right now.</p>';
      return;
    }

    container.innerHTML = data.articles.map(article => `
      <article class="article-card">
        <div class="card-media">
          <img src="${article.image || '/images/district.png'}" alt="${article.title}" loading="lazy" />
          <span class="card-badge">${article.category_name || 'News'}</span>
        </div>
        <div class="card-body">
          <div class="card-meta">
            <span>📍 ${article.district || 'Karnataka'}</span>
            <span>🕒 ${new Date(article.date).toLocaleDateString()}</span>
          </div>
          <h3 class="card-title">${article.title}</h3>
          <p class="card-excerpt">${article.description.slice(0, 110)}...</p>
          <div class="card-footer">
            <span style="font-size: 0.8rem; color: #64748b; font-weight: 500;">Source: ${article.publisher}</span>
            <a href="/article.html?id=${article.id}" class="btn-read">Full Story &rarr;</a>
          </div>
        </div>
      </article>
    `).join('');
  } catch (err) {
    console.error('Error loading headlines:', err);
    container.innerHTML = '<p>Headlines could not be loaded at this time.</p>';
  }
}

// Chat widget logic
function initChat() {
  const trigger = document.getElementById('chatTriggerBtn');
  const box = document.getElementById('chatBox');
  const closeBtn = document.getElementById('closeChatBtn');
  const form = document.getElementById('chatForm');
  const input = document.getElementById('chatInput');
  const messages = document.getElementById('chatMessages');

  trigger.addEventListener('click', () => {
    box.classList.toggle('active');
    if (box.classList.contains('active')) input.focus();
  });

  closeBtn.addEventListener('click', () => {
    box.classList.remove('active');
  });

  form.addEventListener('submit', (e) => {
    e.preventDefault();
    const text = input.value.trim();
    if (!text) return;

    // User message
    const userBubble = document.createElement('div');
    userBubble.className = 'chat-bubble chat-bubble-user';
    userBubble.textContent = text;
    messages.appendChild(userBubble);
    input.value = '';
    messages.scrollTop = messages.scrollHeight;

    // Bot response after slight delay
    setTimeout(() => {
      const botBubble = document.createElement('div');
      botBubble.className = 'chat-bubble chat-bubble-bot';

      const lower = text.toLowerCase();
      if (lower.includes('district') || lower.includes('udupi') || lower.includes('bengaluru')) {
        botBubble.textContent = 'News Junction has 30+ district desks covering local civic, development, and administrative news across Karnataka. You can filter by district on the News Feed page!';
      } else if (lower.includes('complaint') || lower.includes('grievance') || lower.includes('report')) {
        botBubble.innerHTML = 'You can report civic issues or citizen news on our <a href="/complaints.html" style="color:#b00000;text-decoration:underline;">Citizen Grievances</a> page.';
      } else if (lower.includes('login') || lower.includes('sign in') || lower.includes('account')) {
        botBubble.innerHTML = 'Access your personalized news preferences by <a href="/sign-in.html" style="color:#b00000;text-decoration:underline;">Signing in here</a>.';
      } else {
        botBubble.textContent = 'Thank you for reaching out. News Junction provides 24/7 verified grassroots reporting across Karnataka. Head to the News Feed to explore the latest updates!';
      }

      messages.appendChild(botBubble);
      messages.scrollTop = messages.scrollHeight;
    }, 500);
  });
}
