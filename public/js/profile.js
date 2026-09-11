/**
 * News Junction - Profile & Dashboard Logic
 */

let currentUser = null;

document.addEventListener('DOMContentLoaded', async () => {
  initTabs();
  await loadProfile();
  initForm();
});

function initTabs() {
  document.querySelectorAll('.auth-tab').forEach(tab => {
    tab.addEventListener('click', () => {
      document.querySelectorAll('.auth-tab').forEach(t => t.classList.remove('active'));
      document.querySelectorAll('.auth-form-panel').forEach(f => f.style.display = 'none');

      tab.classList.add('active');
      const target = tab.getAttribute('data-target');
      const targetPanel = document.getElementById(target);
      if (targetPanel) targetPanel.style.display = 'block';
    });
  });
}

function showAlert(message, isError = false) {
  const alertEl = document.getElementById('profileAlert');
  alertEl.className = `alert-message ${isError ? 'alert-error' : 'alert-success'}`;
  alertEl.textContent = message;
  alertEl.style.display = 'block';
  alertEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

async function loadProfile() {
  try {
    const user = await API.profile.get();
    currentUser = user;

    // Header display
    const initial = (user.fullName || 'U').charAt(0).toUpperCase();
    document.getElementById('avatarDisplay').textContent = initial;
    document.getElementById('profileName').textContent = user.fullName;
    document.getElementById('planBadge').textContent = user.plan || 'Free';
    document.getElementById('profileEmail').textContent = `📧 ${user.email} | 📍 ${user.pincode || 'Karnataka'}`;
    document.getElementById('profileBio').textContent = user.bio || (user.company ? `Associated with ${user.company}` : 'Verified News Junction Reader');

    // Stats
    document.getElementById('statSaved').textContent = user.stats.savedCount || 0;
    document.getElementById('statVisits').textContent = user.stats.visits || 1;
    document.getElementById('statRole').textContent = (user.role || 'Reader').toUpperCase();
    document.getElementById('settingsPlan').textContent = `${user.plan || 'Free'} Tier`;

    // Populate Edit Form
    document.getElementById('editFullName').value = user.fullName || '';
    document.getElementById('editEmail').value = user.email || '';
    document.getElementById('editCountryCode').value = user.countryCode || '+91';
    document.getElementById('editPhone').value = user.phone || '';
    document.getElementById('editCompany').value = user.company || '';
    document.getElementById('editPincode').value = user.pincode || '';
    document.getElementById('editWebsite').value = user.website || '';
    document.getElementById('editBio').value = user.bio || '';

    // Render Saved Articles
    renderSavedArticles(user.savedArticles || []);
  } catch (err) {
    console.warn('Redirecting unauthenticated visitor to login:', err);
    window.location.assign('/sign-in.html');
  }
}

function renderSavedArticles(articles) {
  const grid = document.getElementById('savedGrid');
  const emptyMsg = document.getElementById('noSavedMsg');

  if (!articles || articles.length === 0) {
    grid.innerHTML = '';
    emptyMsg.style.display = 'block';
    return;
  }

  emptyMsg.style.display = 'none';
  grid.innerHTML = articles.map(a => `
    <article class="article-card">
      <div class="card-media">
        <img src="${a.image || '/images/district.png'}" alt="${a.title}" />
        <span class="card-badge">${a.category_name || 'News'}</span>
      </div>
      <div class="card-body">
        <div class="card-meta">
          <span>📍 ${a.district || 'Karnataka'}</span>
          <span>🕒 ${new Date(a.date).toLocaleDateString()}</span>
        </div>
        <h3 class="card-title">${a.title}</h3>
        <p class="card-excerpt">${a.description ? a.description.slice(0, 110) + '...' : ''}</p>
        <div class="card-footer">
          <a href="/article.html?id=${a.id}" class="btn-read">Read Story</a>
          <button class="icon-btn btn-remove-bookmark" data-id="${a.id}" title="Remove from saved">
            ❌ Remove
          </button>
        </div>
      </div>
    </article>
  `).join('');

  grid.querySelectorAll('.btn-remove-bookmark').forEach(btn => {
    btn.addEventListener('click', async () => {
      const id = btn.getAttribute('data-id');
      try {
        await API.reader.toggleBookmark(id);
        await loadProfile();
      } catch (e) {
        console.error(e);
      }
    });
  });
}

function initForm() {
  const form = document.getElementById('editProfileForm');
  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = document.getElementById('saveProfileBtn');
    btn.disabled = true;
    btn.textContent = 'Saving Changes...';

    const updateData = {
      userName: document.getElementById('editFullName').value.trim(),
      userEmail: document.getElementById('editEmail').value.trim(),
      countryCode: document.getElementById('editCountryCode').value,
      userPhone: document.getElementById('editPhone').value.trim(),
      userCompany: document.getElementById('editCompany').value.trim(),
      pincode: document.getElementById('editPincode').value.trim(),
      userWebsite: document.getElementById('editWebsite').value.trim(),
      userBio: document.getElementById('editBio').value.trim()
    };

    try {
      const res = await API.profile.update(updateData);
      showAlert(res.message || 'Profile updated successfully!', false);
      await loadProfile();
    } catch (err) {
      showAlert(err.message || 'Could not update profile.', true);
    } finally {
      btn.disabled = false;
      btn.textContent = 'Save Changes';
    }
  });
}
