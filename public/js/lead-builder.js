document.addEventListener('DOMContentLoaded', () => {
  const inputTitle = document.getElementById('inputHeroTitle');
  const inputSubtitle = document.getElementById('inputHeroSubtitle');
  const inputCta = document.getElementById('inputCtaText');
  const inputColor = document.getElementById('inputPrimaryColor');
  const inputTheme = document.getElementById('inputTheme');
  const fieldName = document.getElementById('fieldName');
  const fieldPhone = document.getElementById('fieldPhone');
  const fieldCompany = document.getElementById('fieldCompany');

  const liveTitle = document.getElementById('liveTitle');
  const liveSubtitle = document.getElementById('liveSubtitle');
  const liveCta = document.getElementById('liveCtaButton');
  const previewCard = document.getElementById('previewCard');
  const groupName = document.getElementById('groupName');
  const groupPhone = document.getElementById('groupPhone');
  const groupCompany = document.getElementById('groupCompany');

  const savedSelect = document.getElementById('savedTemplatesSelect');
  const btnSave = document.getElementById('btnSaveTemplate');
  const btnExport = document.getElementById('btnExportHtml');

  function updateLivePreview() {
    liveTitle.textContent = inputTitle.value.trim() || 'Join Our Community';
    liveSubtitle.textContent = inputSubtitle.value.trim() || 'Stay connected with verified journalism.';
    liveCta.textContent = inputCta.value.trim() || 'Submit';

    const color = inputColor.value;
    liveTitle.style.color = color;
    liveCta.style.backgroundColor = color;

    // Field visibility
    groupName.style.display = fieldName.checked ? 'block' : 'none';
    groupPhone.style.display = fieldPhone.checked ? 'block' : 'none';
    groupCompany.style.display = fieldCompany.checked ? 'block' : 'none';

    // Theme
    if (inputTheme.value === 'dark') {
      previewCard.style.backgroundColor = '#1e293b';
      previewCard.style.color = '#ffffff';
      liveSubtitle.style.color = '#94a3b8';
    } else {
      previewCard.style.backgroundColor = '#ffffff';
      previewCard.style.color = '#1e293b';
      liveSubtitle.style.color = '#64748b';
    }
  }

  [inputTitle, inputSubtitle, inputCta, inputColor, inputTheme, fieldName, fieldPhone, fieldCompany].forEach(el => {
    el.addEventListener('input', updateLivePreview);
    el.addEventListener('change', updateLivePreview);
  });

  async function loadTemplatesList() {
    try {
      const templates = await API.templates.list();
      savedSelect.innerHTML = '<option value="">-- Load Saved Template --</option>' + templates.map(t => `
        <option value="${t.id}">${t.name}</option>
      `).join('');
    } catch (err) {
      console.error('Failed to load templates:', err);
    }
  }

  savedSelect.addEventListener('change', async () => {
    const id = savedSelect.value;
    if (!id) return;
    try {
      const t = await API.templates.getById(id);
      inputTitle.value = t.heroTitle || '';
      inputSubtitle.value = t.heroSubtitle || '';
      inputCta.value = t.ctaText || 'Submit';
      inputColor.value = t.primaryColor || '#b00000';
      inputTheme.value = t.theme || 'light';
      updateLivePreview();
    } catch (err) {
      alert('Error loading template: ' + err.message);
    }
  });

  btnSave.addEventListener('click', async () => {
    const name = prompt('Enter a name for this template:', inputTitle.value.slice(0, 30));
    if (!name) return;

    try {
      await API.templates.save({
        name,
        heroTitle: inputTitle.value,
        heroSubtitle: inputSubtitle.value,
        ctaText: inputCta.value,
        primaryColor: inputColor.value,
        theme: inputTheme.value
      });
      alert('Template saved successfully!');
      loadTemplatesList();
    } catch (err) {
      alert('Failed to save template: ' + err.message);
    }
  });

  btnExport.addEventListener('click', async () => {
    try {
      const res = await fetch('/api/templates/export', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          heroTitle: inputTitle.value,
          heroSubtitle: inputSubtitle.value,
          ctaText: inputCta.value,
          primaryColor: inputColor.value,
          theme: inputTheme.value
        })
      });
      const htmlText = await res.text();
      const blob = new Blob([htmlText], { type: 'text/html;charset=utf-8;' });
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = `landing_page_${Date.now()}.html`;
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
    } catch (err) {
      alert('Export failed: ' + err.message);
    }
  });

  updateLivePreview();
  loadTemplatesList();
});
