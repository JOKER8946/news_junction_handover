document.addEventListener('DOMContentLoaded', () => {
  const container = document.getElementById('collectionsContainer');
  const countBadge = document.getElementById('savedCountBadge');

  async function loadCollections() {
    container.innerHTML = '<p style="grid-column: 1/-1; text-align: center; color: var(--color-text-muted);">Loading your saved stories...</p>';
    try {
      const items = await API.collections.list();
      renderCollections(items);
    } catch (err) {
      container.innerHTML = `<p style="grid-column: 1/-1; text-align: center; color: red;">Failed to load saved items: ${err.message}</p>`;
    }
  }

  function renderCollections(items) {
    countBadge.textContent = `${items.length} Saved Articles`;

    if (!items || items.length === 0) {
      container.innerHTML = `
        <div style="grid-column: 1/-1; text-align: center; padding: 50px 20px; background: #fff; border-radius: 12px; border: 1px solid var(--color-border);">
          <div style="font-size: 3rem; margin-bottom: 12px;">🔖</div>
          <h3 style="margin-bottom: 8px;">Your reading library is empty</h3>
          <p style="color: var(--color-text-muted); margin-bottom: 20px;">Browse the news feed and tap the bookmark icon to save stories here.</p>
          <a href="/reader.html" class="btn-primary">Explore Latest News</a>
        </div>
      `;
      return;
    }

    container.innerHTML = items.map(art => `
      <div class="magazine-box" style="display: flex; flex-direction: column; justify-content: space-between;">
        <div>
          ${art.image ? `<img src="${art.image}" alt="${art.title}" style="width: 100%; height: 160px; object-fit: cover; border-radius: 8px; margin-bottom: 14px;" />` : ''}
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
            <span class="badge" style="background: #f1f5f9; color: #475569;">${art.district || 'Karnataka'}</span>
            <span style="font-size: 0.8rem; color: var(--color-text-muted);">${art.date ? art.date.slice(0, 10) : ''}</span>
          </div>
          <h3 style="font-size: 1.15rem; font-weight: 700; line-height: 1.4; margin-bottom: 10px;">
            <a href="/article.html?id=${art.id}" style="color: var(--color-dark);">${art.title}</a>
          </h3>
          <p style="font-size: 0.9rem; color: var(--color-text-muted); line-height: 1.5; margin-bottom: 16px;">
            ${(art.description || '').slice(0, 120)}...
          </p>
        </div>

        <div>
          <!-- Tag & eSamudaay share -->
          <div style="display: flex; gap: 8px; margin-bottom: 12px;">
            <button onclick="shareToEsamudaay(${art.id}, this)" class="btn-secondary" style="flex-grow: 1; font-size: 0.8rem; padding: 6px 10px;">
              🌐 eSamudaay Share
            </button>
            <button onclick="removeFromCollections(${art.id})" class="btn-secondary" style="font-size: 0.8rem; padding: 6px 10px; color: red;">
              ✕ Remove
            </button>
          </div>
          <a href="/article.html?id=${art.id}" class="btn-primary" style="width: 100%; text-align: center; justify-content: center; font-size: 0.88rem;">
            Read Article &rarr;
          </a>
        </div>
      </div>
    `).join('');
  }

  window.shareToEsamudaay = async (articleId, btn) => {
    try {
      await API.collections.shareEsamudaay(articleId);
      btn.textContent = '✓ Shared to eSamudaay';
      btn.style.color = '#15803d';
      btn.disabled = true;
    } catch (err) {
      alert('eSamudaay sharing: ' + err.message);
    }
  };

  window.removeFromCollections = async (articleId) => {
    try {
      await API.collections.remove(articleId);
      loadCollections();
    } catch (err) {
      alert('Failed to remove: ' + err.message);
    }
  };

  loadCollections();
});
