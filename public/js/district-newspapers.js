document.addEventListener('DOMContentLoaded', () => {
  const container = document.getElementById('newspapersContainer');
  const districtTabs = document.getElementById('districtTabs');
  const modal = document.getElementById('addPaperModal');
  const btnOpen = document.getElementById('btnOpenAddPaper');
  const btnHeaderAdd = document.getElementById('btnHeaderAddPaper');
  const btnClose = document.getElementById('btnClosePaperModal');
  const btnCancel = document.getElementById('btnCancelPaper');
  const form = document.getElementById('addPaperForm');

  let currentDistrict = 'all';

  // Check URL query param e.g. ?district=Udupi
  const urlParams = new URLSearchParams(window.location.search);
  const qDist = urlParams.get('district');
  if (qDist) {
    currentDistrict = qDist;
    const tabMatch = Array.from(districtTabs.children).find(b => b.dataset.district.toLowerCase() === qDist.toLowerCase());
    if (tabMatch) {
      document.querySelectorAll('.district-tab').forEach(t => t.classList.remove('active'));
      tabMatch.classList.add('active');
    }
  }

  async function loadNewspapers() {
    container.innerHTML = '<p style="grid-column: 1/-1; text-align: center; color: var(--color-text-muted);">Loading district editions...</p>';
    try {
      const papers = await API.newspapers.list(currentDistrict);
      renderNewspapers(papers);
    } catch (err) {
      container.innerHTML = `<p style="grid-column: 1/-1; text-align: center; color: red;">Failed to load newspapers: ${err.message}</p>`;
    }
  }

  function renderNewspapers(papers) {
    if (!papers || papers.length === 0) {
      container.innerHTML = `
        <div style="grid-column: 1/-1; text-align: center; padding: 40px; background: #fff; border-radius: 8px; border: 1px solid var(--color-border);">
          <p style="color: var(--color-text-muted); font-size: 1rem;">No newspaper editions uploaded for <strong>${currentDistrict}</strong> yet.</p>
        </div>
      `;
      return;
    }

    container.innerHTML = papers.map(p => `
      <div class="newspaper-card">
        <img src="${p.thumbnail || '/images/belagavi.png'}" alt="${p.title}" onerror="this.src='/images/belagavi.png';" />
        <div class="newspaper-content">
          <span class="badge" style="background: #e0f2fe; color: #0369a1; align-self: flex-start; margin-bottom: 8px;">
            📍 ${p.district}
          </span>
          <h3 class="newspaper-title">${p.title}</h3>
          <p class="newspaper-meta">
            ${p.publisher || 'Regional Publisher'} • ${p.edition_date || 'Today'} • 📑 ${p.pages || 8} Pages
          </p>
          <div style="display: flex; gap: 8px; margin-top: auto;">
            <a href="${p.pdf_url || '#'}" target="_blank" class="btn-primary" style="flex-grow: 1; text-align: center; justify-content: center; font-size: 0.85rem;">
              📖 Read E-Paper
            </a>
            <button onclick="sharePaper('${p.title}')" class="btn-secondary" style="font-size: 0.85rem;">
              📤 Share
            </button>
          </div>
        </div>
      </div>
    `).join('');
  }

  window.sharePaper = (title) => {
    if (navigator.share) {
      navigator.share({ title: title, url: window.location.href });
    } else {
      navigator.clipboard.writeText(window.location.href);
      alert(`Link copied to clipboard: ${title}`);
    }
  };

  // Tab clicks
  districtTabs.addEventListener('click', (e) => {
    if (e.target.classList.contains('district-tab')) {
      document.querySelectorAll('.district-tab').forEach(t => t.classList.remove('active'));
      e.target.classList.add('active');
      currentDistrict = e.target.dataset.district;
      loadNewspapers();
    }
  });

  // Modal handlers
  const openModal = () => modal && modal.classList.add('active');
  const closeModal = () => modal && modal.classList.remove('active');

  if (btnOpen) btnOpen.addEventListener('click', openModal);
  if (btnHeaderAdd) btnHeaderAdd.addEventListener('click', openModal);
  if (btnClose) btnClose.addEventListener('click', closeModal);
  if (btnCancel) btnCancel.addEventListener('click', closeModal);

  if (form) {
    // Default today's date in picker
    const dateInput = document.getElementById('paperDate');
    if (dateInput) dateInput.value = new Date().toISOString().slice(0, 10);

    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      const payload = {
        title: document.getElementById('paperTitle').value.trim(),
        district: document.getElementById('paperDistrict').value,
        publisher: document.getElementById('paperPublisher').value.trim(),
        edition_date: document.getElementById('paperDate').value,
        pages: parseInt(document.getElementById('paperPages').value, 10) || 8,
        thumbnail: document.getElementById('paperThumb').value.trim() || '/images/belagavi.png',
        pdf_url: document.getElementById('paperPdf').value.trim() || '#'
      };

      try {
        await API.newspapers.submit(payload);
        closeModal();
        form.reset();
        loadNewspapers();
      } catch (err) {
        alert('Failed to upload newspaper: ' + err.message);
      }
    });
  }

  loadNewspapers();
});
