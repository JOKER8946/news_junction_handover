document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('createArticleForm');
  const alertBox = document.getElementById('alertBox');
  const isReadMore = document.getElementById('isReadMore');
  const leadSettingsContainer = document.getElementById('leadSettingsContainer');
  const isSchedule = document.getElementById('isSchedule');
  const scheduleTimeContainer = document.getElementById('scheduleTimeContainer');
  const btnPreview = document.getElementById('btnPreview');
  const previewModal = document.getElementById('previewModal');
  const btnClosePreview = document.getElementById('btnClosePreview');

  // Toggle Lead Settings Container
  isReadMore.addEventListener('change', () => {
    leadSettingsContainer.style.display = isReadMore.checked ? 'block' : 'none';
  });

  // Toggle Schedule Time Container
  isSchedule.addEventListener('change', () => {
    scheduleTimeContainer.style.display = isSchedule.checked ? 'block' : 'none';
  });

  // Preview Logic
  btnPreview.addEventListener('click', () => {
    const title = document.getElementById('postTitle').value.trim() || 'Untitled Article';
    const body = document.getElementById('postBody').value.trim() || 'No article content entered.';
    const cover = document.getElementById('coverImage').value.trim();
    const district = document.getElementById('districtSelect').value;
    const author = document.getElementById('authorName').value.trim() || 'News Junction Author';

    document.getElementById('previewTitle').textContent = title;
    document.getElementById('previewMeta').textContent = `By ${author} • ${district} • ${new Date().toLocaleDateString()}`;
    document.getElementById('previewBody').innerHTML = body;

    const previewImg = document.getElementById('previewImg');
    if (cover) {
      previewImg.src = cover;
      previewImg.style.display = 'block';
    } else {
      previewImg.style.display = 'none';
    }

    previewModal.classList.add('active');
  });

  btnClosePreview.addEventListener('click', () => {
    previewModal.classList.remove('active');
  });

  previewModal.addEventListener('click', (e) => {
    if (e.target === previewModal) previewModal.classList.remove('active');
  });

  // Submit Logic
  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const btnPublish = document.getElementById('btnPublish');
    btnPublish.disabled = true;
    btnPublish.textContent = 'Publishing...';

    const payload = {
      title: document.getElementById('postTitle').value.trim(),
      categoryId: parseInt(document.getElementById('categorySelect').value, 10),
      district: document.getElementById('districtSelect').value,
      author: document.getElementById('authorName').value.trim(),
      tags: document.getElementById('articleTags').value.trim(),
      image: document.getElementById('coverImage').value.trim(),
      description: document.getElementById('postSummary').value.trim(),
      content: document.getElementById('postBody').value.trim(),
      isReadMore: isReadMore.checked,
      readMoreTxt: document.getElementById('readMoreTxt').value.trim(),
      isMandatoryEmail: document.getElementById('isMandatoryEmail').checked,
      isMandatoryMobile: document.getElementById('isMandatoryMobile').checked,
      isMandatoryCompany: document.getElementById('isMandatoryCompany').checked,
      readMoreResponse: document.getElementById('readMoreResponse').value.trim(),
      readMoreEmail: document.getElementById('readMoreEmail').value.trim(),
      scheduledFor: isSchedule.checked ? document.getElementById('scheduledFor').value : null
    };

    try {
      const res = await API.articles.create(payload);
      alertBox.style.display = 'block';
      alertBox.style.backgroundColor = '#dcfce7';
      alertBox.style.color = '#15803d';
      alertBox.innerHTML = `✅ Article successfully published! <a href="/article.html?id=${res.article.id}" style="text-decoration: underline; font-weight: 700;">View Published Article &rarr;</a>`;
      form.reset();
      leadSettingsContainer.style.display = 'none';
      scheduleTimeContainer.style.display = 'none';
    } catch (err) {
      alertBox.style.display = 'block';
      alertBox.style.backgroundColor = '#fee2e2';
      alertBox.style.color = '#b91c1c';
      alertBox.textContent = `❌ Failed to publish: ${err.message}`;
    } finally {
      btnPublish.disabled = false;
      btnPublish.textContent = '🚀 Publish Article';
    }
  });
});
