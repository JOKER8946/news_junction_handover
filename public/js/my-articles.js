document.addEventListener('DOMContentLoaded', () => {
  const tbody = document.getElementById('articlesTableBody');
  const tabPublished = document.getElementById('tabPublished');
  const tabScheduled = document.getElementById('tabScheduled');

  let currentTab = 'published';
  let articles = [];

  async function loadArticles() {
    tbody.innerHTML = `<tr><td colspan="8" style="text-align: center; padding: 30px; color: var(--color-text-muted);">Loading articles...</td></tr>`;
    try {
      if (currentTab === 'published') {
        articles = await API.articles.getMy();
      } else {
        articles = await API.articles.getScheduled();
      }
      renderArticles();
    } catch (err) {
      tbody.innerHTML = `<tr><td colspan="8" style="text-align: center; padding: 30px; color: red;">Failed to load articles: ${err.message}</td></tr>`;
    }
  }

  function renderArticles() {
    if (!articles || articles.length === 0) {
      tbody.innerHTML = `<tr><td colspan="8" style="text-align: center; padding: 30px; color: var(--color-text-muted);">No articles found in this queue.</td></tr>`;
      return;
    }

    tbody.innerHTML = articles.map(art => `
      <tr>
        <td style="font-weight: 600; max-width: 320px;">
          <a href="/article.html?id=${art.id}" style="color: var(--color-dark); hover: text-decoration: underline;">
            ${art.title}
          </a>
        </td>
        <td><span class="badge" style="background: #f1f5f9; color: #475569;">${art.category_name || 'General'}</span></td>
        <td>${art.district || 'Karnataka'}</td>
        <td style="font-weight: 700;">${(art.views || 0).toLocaleString()}</td>
        <td style="color: var(--color-primary); font-weight: 600;">❤️ ${art.likes || 0}</td>
        <td style="font-size: 0.85rem; color: var(--color-text-muted);">${art.scheduled_for || art.date || 'Recent'}</td>
        <td>
          <span class="badge ${art.status === 'scheduled' ? 'badge-scheduled' : 'badge-published'}">
            ${art.status || 'published'}
          </span>
        </td>
        <td>
          <div style="display: flex; gap: 8px;">
            <a href="/article.html?id=${art.id}" class="btn-secondary" style="padding: 4px 10px; font-size: 0.8rem;">View</a>
            <button onclick="deleteArticle(${art.id})" class="btn-secondary" style="padding: 4px 10px; font-size: 0.8rem; color: red;">Delete</button>
          </div>
        </td>
      </tr>
    `).join('');
  }

  window.deleteArticle = async (id) => {
    if (!confirm('Are you sure you want to delete this story?')) return;
    try {
      await API.articles.delete(id);
      articles = articles.filter(a => a.id !== id);
      renderArticles();
    } catch (err) {
      alert('Failed to delete: ' + err.message);
    }
  };

  tabPublished.addEventListener('click', () => {
    currentTab = 'published';
    tabPublished.className = 'btn-primary';
    tabScheduled.className = 'btn-secondary';
    loadArticles();
  });

  tabScheduled.addEventListener('click', () => {
    currentTab = 'scheduled';
    tabScheduled.className = 'btn-primary';
    tabPublished.className = 'btn-secondary';
    loadArticles();
  });

  loadArticles();
});
