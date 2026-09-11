document.addEventListener('DOMContentLoaded', () => {
  const container = document.getElementById('influencersContainer');

  async function loadInfluencers() {
    container.innerHTML = '<p style="grid-column: 1/-1; text-align: center; color: var(--color-text-muted);">Loading columnists...</p>';
    try {
      const influencers = await API.influencers.list();
      renderInfluencers(influencers);
    } catch (err) {
      container.innerHTML = `<p style="grid-column: 1/-1; text-align: center; color: red;">Failed to load columnists: ${err.message}</p>`;
    }
  }

  function renderInfluencers(list) {
    if (!list || list.length === 0) {
      container.innerHTML = '<p style="grid-column: 1/-1; text-align: center;">No columnists found.</p>';
      return;
    }

    container.innerHTML = list.map(inf => `
      <div class="channel-card">
        <div class="channel-card-banner" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);"></div>
        <div class="channel-card-body">
          <div style="width: 64px; height: 64px; border-radius: 50%; border: 3px solid #fff; margin-top: -32px; background: #fee2e2; color: var(--color-primary); display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: 700; box-shadow: var(--shadow-sm);">
            ${(inf.full_name || 'C').charAt(0)}
          </div>
          <h3 class="channel-card-name" style="display: flex; align-items: center; gap: 6px;">
            ${inf.full_name}
            <span title="Verified Columnist" style="color: #0284c7; font-size: 1rem;">☑️</span>
          </h3>
          <p style="font-size: 0.82rem; font-weight: 600; color: var(--color-primary); margin-bottom: 6px;">
            ${inf.title || 'Senior Columnist'}
          </p>
          <p class="channel-card-subscribers">
            ✍️ ${inf.opinionCount || 3} Published Opinions • 👥 ${inf.followersCount || 450} Followers
          </p>
          <p class="channel-card-bio" style="font-size: 0.85rem; color: var(--color-text-muted);">
            Specialties: ${inf.topics || 'Governance & Public Policy'}
          </p>
          <div style="display: flex; gap: 10px; margin-top: auto;">
            <a href="/reader.html?search=${encodeURIComponent(inf.full_name)}" class="btn-secondary" style="flex-grow: 1; text-align: center; justify-content: center; font-size: 0.85rem;">
              Read Articles
            </a>
            <button onclick="followColumnist(${inf.id}, this)" class="btn-primary" style="font-size: 0.85rem;">
              + Follow
            </button>
          </div>
        </div>
      </div>
    `).join('');
  }

  window.followColumnist = async (id, btn) => {
    try {
      await API.influencers.toggleFollow(id);
      btn.textContent = '✓ Following';
      btn.style.background = '#10b981';
      btn.disabled = true;
    } catch (err) {
      alert('Action failed: ' + err.message);
    }
  };

  loadInfluencers();
});
