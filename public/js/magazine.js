document.addEventListener('DOMContentLoaded', () => {
  const publishersContainer = document.getElementById('publishersContainer');
  const topicsContainer = document.getElementById('topicsContainer');
  const customTitleInput = document.getElementById('magazineCustomTitle');
  const btnGenerate = document.getElementById('btnGenerateMagazine');
  const btnPrint = document.getElementById('btnPrintMagazine');
  const magazineHeaderTitle = document.getElementById('magazineHeaderTitle');
  const magazineHeaderMeta = document.getElementById('magazineHeaderMeta');
  const magazineArticlesContainer = document.getElementById('magazineArticlesContainer');

  async function loadPublishers() {
    try {
      const publishers = await API.magazine.getPublishers();
      publishersContainer.innerHTML = publishers.map((pub, idx) => `
        <div class="checkbox-pill">
          <input type="checkbox" id="pub_${idx}" value="${pub}" ${idx < 4 ? 'checked' : ''} />
          <label for="pub_${idx}">${pub}</label>
        </div>
      `).join('');
    } catch (err) {
      publishersContainer.innerHTML = '<p style="color: red;">Failed to load publishers</p>';
    }
  }

  async function generateMagazine() {
    btnGenerate.disabled = true;
    btnGenerate.textContent = 'Compiling Edition...';
    magazineArticlesContainer.innerHTML = '<p style="grid-column: 1/-1; text-align: center; color: var(--color-text-muted);">Curating and assembling stories...</p>';

    // Selected publishers
    const selectedPublishers = Array.from(publishersContainer.querySelectorAll('input:checked')).map(i => i.value);
    // Selected topics
    const selectedTopics = Array.from(topicsContainer.querySelectorAll('input:checked')).map(i => i.value);
    const title = customTitleInput.value.trim() || 'KARNATAKA SPECIAL EDITION';

    try {
      const res = await API.magazine.generate({
        publishers: selectedPublishers,
        topics: selectedTopics,
        title: title
      });

      magazineHeaderTitle.textContent = res.title.toUpperCase();
      magazineHeaderMeta.textContent = `Curated on ${new Date().toLocaleDateString('en-IN', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })} • ${res.totalArticles} Verified Stories`;

      renderArticles(res.articles || []);
    } catch (err) {
      magazineArticlesContainer.innerHTML = `<p style="grid-column: 1/-1; text-align: center; color: red;">Error generating magazine: ${err.message}</p>`;
    } finally {
      btnGenerate.disabled = false;
      btnGenerate.textContent = '✨ Generate Custom Edition';
    }
  }

  function renderArticles(articles) {
    if (!articles || articles.length === 0) {
      magazineArticlesContainer.innerHTML = '<p style="grid-column: 1/-1; text-align: center; color: var(--color-text-muted);">No stories matched your publisher & topic selection.</p>';
      return;
    }

    magazineArticlesContainer.innerHTML = articles.map(art => `
      <article class="magazine-article">
        <span style="font-size: 0.78rem; text-transform: uppercase; font-weight: 700; color: var(--color-primary); letter-spacing: 0.5px;">
          ${art.publisher || 'Regional Desk'} • ${art.district || 'Karnataka'}
        </span>
        <h2><a href="/article.html?id=${art.id}" style="color: #0f172a; text-decoration: none;">${art.title}</a></h2>
        ${art.image ? `<img src="${art.image}" alt="${art.title}" style="width: 100%; height: 180px; object-fit: cover; border-radius: 6px; margin: 10px 0;" />` : ''}
        <p style="font-size: 0.95rem; line-height: 1.6; color: #334155; margin-bottom: 12px;">
          ${art.description || ''}
        </p>
        <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.82rem; color: var(--color-text-muted); padding-top: 8px; border-top: 1px solid #f1f5f9;">
          <span>By ${art.author_name || 'Staff Reporter'}</span>
          <a href="/article.html?id=${art.id}" style="color: var(--color-primary); font-weight: 600;">Read Full Story &rarr;</a>
        </div>
      </article>
    `).join('');
  }

  btnGenerate.addEventListener('click', generateMagazine);
  btnPrint.addEventListener('click', () => window.print());

  loadPublishers().then(() => generateMagazine());
});
