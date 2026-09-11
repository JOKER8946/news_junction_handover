document.addEventListener('DOMContentLoaded', () => {
  const isSingleChannel = window.location.pathname.includes('channel.html') && !window.location.pathname.includes('channels.html');

  if (isSingleChannel) {
    initSingleChannel();
  } else {
    initChannelsDirectory();
  }

  // ================= CHANNELS DIRECTORY LOGIC =================
  async function initChannelsDirectory() {
    const container = document.getElementById('channelsContainer');
    const searchInput = document.getElementById('channelSearch');
    const filterFeatured = document.getElementById('filterFeatured');
    const modal = document.getElementById('createChannelModal');
    const btnOpen = document.getElementById('btnOpenCreateChannel');
    const btnHeaderCreate = document.getElementById('btnHeaderCreateChannel');
    const btnClose = document.getElementById('btnCloseChannelModal');
    const btnCancel = document.getElementById('btnCancelChannel');
    const form = document.getElementById('createChannelForm');

    let allChannels = [];

    async function loadChannels() {
      container.innerHTML = '<p style="grid-column: 1/-1; text-align: center; color: var(--color-text-muted);">Loading channels...</p>';
      try {
        const featured = filterFeatured && filterFeatured.checked ? 'Y' : '';
        const q = searchInput ? searchInput.value.trim() : '';
        allChannels = await API.channels.list({ featured, q });
        renderChannels(allChannels);
      } catch (err) {
        container.innerHTML = `<p style="grid-column: 1/-1; text-align: center; color: red;">Failed to load channels: ${err.message}</p>`;
      }
    }

    function renderChannels(channels) {
      if (!channels || channels.length === 0) {
        container.innerHTML = '<p style="grid-column: 1/-1; text-align: center; color: var(--color-text-muted);">No channels match your criteria.</p>';
        return;
      }

      container.innerHTML = channels.map(c => `
        <div class="channel-card">
          <div class="channel-card-banner"></div>
          <div class="channel-card-body">
            <img src="${c.profilePic || '/images/district.png'}" alt="${c.name}" class="channel-card-avatar" onerror="this.src='/images/district.png';" />
            <h3 class="channel-card-name">
              <a href="/channel.html?id=${c.id}" style="color: var(--color-dark);">${c.name}</a>
              ${c.featured_channel === 'Y' ? '<span class="badge badge-featured" style="margin-left: 6px; font-size: 0.65rem;">Featured</span>' : ''}
            </h3>
            <p class="channel-card-subscribers">👥 ${(c.subscribers || 0).toLocaleString()} subscribers</p>
            <p class="channel-card-bio">${c.bio || 'Verified news channel covering community and state affairs.'}</p>
            <div style="display: flex; gap: 10px; margin-top: auto;">
              <a href="/channel.html?id=${c.id}" class="btn-secondary" style="flex-grow: 1; text-align: center; justify-content: center; font-size: 0.88rem;">Open Channel</a>
              <button onclick="toggleFollowChannel(${c.id}, this)" class="btn-primary" style="font-size: 0.88rem; background: ${c.isFollowed ? '#10b981' : 'var(--color-primary)'};">
                ${c.isFollowed ? '✓ Following' : '+ Follow'}
              </button>
            </div>
          </div>
        </div>
      `).join('');
    }

    window.toggleFollowChannel = async (channelId, btn) => {
      try {
        const res = await API.channels.toggleFollow(channelId);
        btn.textContent = res.following ? '✓ Following' : '+ Follow';
        btn.style.background = res.following ? '#10b981' : 'var(--color-primary)';
      } catch (err) {
        alert('Action failed: ' + err.message);
      }
    };

    if (searchInput) searchInput.addEventListener('input', loadChannels);
    if (filterFeatured) filterFeatured.addEventListener('change', loadChannels);

    // Modal toggles
    const openModal = () => modal && modal.classList.add('active');
    const closeModal = () => modal && modal.classList.remove('active');

    if (btnOpen) btnOpen.addEventListener('click', openModal);
    if (btnHeaderCreate) btnHeaderCreate.addEventListener('click', openModal);
    if (btnClose) btnClose.addEventListener('click', closeModal);
    if (btnCancel) btnCancel.addEventListener('click', closeModal);

    if (form) {
      form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const payload = {
          name: document.getElementById('channelName').value.trim(),
          bio: document.getElementById('channelBio').value.trim(),
          profilePic: document.getElementById('channelAvatar').value.trim() || 'images/district.png',
          visibility: document.getElementById('channelVisibility').value
        };

        try {
          await API.channels.create(payload);
          closeModal();
          form.reset();
          loadChannels();
        } catch (err) {
          alert('Failed to create channel: ' + err.message);
        }
      });
    }

    loadChannels();
  }

  // ================= SINGLE CHANNEL VIEW LOGIC =================
  async function initSingleChannel() {
    const params = new URLSearchParams(window.location.search);
    const channelId = params.get('id') || params.get('channelId') || 1;

    const channelTitle = document.getElementById('channelTitle');
    const channelSubscribers = document.getElementById('channelSubscribers');
    const channelBio = document.getElementById('channelBio');
    const channelPic = document.getElementById('channelPic');
    const btnFollow = document.getElementById('btnFollowChannel');
    const formPost = document.getElementById('channelPostForm');
    const streamContainer = document.getElementById('postsStreamContainer');

    async function loadChannelDetails() {
      try {
        const channel = await API.channels.getById(channelId);
        channelTitle.textContent = channel.name;
        channelSubscribers.textContent = `👥 ${(channel.subscribers || 0).toLocaleString()} subscribers`;
        channelBio.textContent = channel.bio || 'Verified news channel.';
        if (channel.profilePic) channelPic.src = channel.profilePic.startsWith('http') || channel.profilePic.startsWith('/') ? channel.profilePic : `/${channel.profilePic}`;

        btnFollow.textContent = channel.isFollowed ? '✓ Following' : '+ Follow Channel';
        btnFollow.style.background = channel.isFollowed ? '#10b981' : 'var(--color-primary)';

        renderStreamPosts(channel.posts || []);
      } catch (err) {
        channelTitle.textContent = 'Channel Not Found';
        streamContainer.innerHTML = `<p style="color: red; text-align: center;">Error loading channel: ${err.message}</p>`;
      }
    }

    function renderStreamPosts(posts) {
      if (!posts || posts.length === 0) {
        streamContainer.innerHTML = `
          <div class="magazine-box" style="text-align: center; color: var(--color-text-muted); padding: 40px;">
            No updates posted to this channel stream yet. Be the first to share!
          </div>
        `;
        return;
      }

      streamContainer.innerHTML = posts.map(p => `
        <div class="magazine-box" style="margin-bottom: 20px;">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
            <div style="display: flex; align-items: center; gap: 10px;">
              <div style="width: 36px; height: 36px; border-radius: 50%; background: var(--color-primary); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700;">
                ${(p.user_name || 'U').charAt(0)}
              </div>
              <div>
                <strong style="font-size: 0.95rem;">${p.user_name || 'Correspondent'}</strong>
                <span style="font-size: 0.78rem; color: var(--color-text-muted); display: block;">${p.postedOn || 'Recent'}</span>
              </div>
            </div>
            <span class="badge badge-resolved" style="font-size: 0.7rem;">Verified Post</span>
          </div>

          <p style="font-size: 1rem; line-height: 1.6; color: var(--color-text-dark); margin-bottom: ${p.mediaPath ? '14px' : '0'};">
            ${p.chat}
          </p>

          ${p.mediaPath ? `
            <img src="${p.mediaPath.startsWith('http') || p.mediaPath.startsWith('/') ? p.mediaPath : `/${p.mediaPath}`}" 
                 alt="Attachment" 
                 style="width: 100%; max-height: 350px; object-fit: cover; border-radius: 8px; margin-bottom: 12px;" 
                 onerror="this.style.display='none'" />
          ` : ''}

          <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 14px; pt: 12px; border-top: 1px solid #f1f5f9; font-size: 0.88rem; color: var(--color-text-muted);">
            <button onclick="likeStreamPost(this)" style="background: none; border: none; cursor: pointer; color: var(--color-primary); font-weight: 600;">
              ❤️ ${p.likes || 0} Likes
            </button>
            <span>News Junction Verified Desk</span>
          </div>
        </div>
      `).join('');
    }

    window.likeStreamPost = (btn) => {
      const current = parseInt(btn.textContent.replace(/[^0-9]/g, '') || 0, 10);
      btn.textContent = `❤️ ${current + 1} Likes`;
      btn.disabled = true;
    };

    btnFollow.addEventListener('click', async () => {
      try {
        const res = await API.channels.toggleFollow(channelId);
        btnFollow.textContent = res.following ? '✓ Following' : '+ Follow Channel';
        btnFollow.style.background = res.following ? '#10b981' : 'var(--color-primary)';
        channelSubscribers.textContent = `👥 ${(res.subscribers || 0).toLocaleString()} subscribers`;
      } catch (err) {
        alert('Action failed: ' + err.message);
      }
    });

    if (formPost) {
      formPost.addEventListener('submit', async (e) => {
        e.preventDefault();
        const chat = document.getElementById('postChat').value.trim();
        const mediaPath = document.getElementById('postMedia').value.trim();

        if (!chat) return;

        try {
          await API.channels.addPost(channelId, { chat, mediaPath });
          document.getElementById('postChat').value = '';
          document.getElementById('postMedia').value = '';
          loadChannelDetails();
        } catch (err) {
          alert('Failed to post to stream: ' + err.message);
        }
      });
    }

    loadChannelDetails();
  }
});
