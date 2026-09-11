document.addEventListener('DOMContentLoaded', () => {
  const tabAds = document.getElementById('tabAds');
  const tabComplaints = document.getElementById('tabComplaints');
  const tabUsers = document.getElementById('tabUsers');

  const sectionAds = document.getElementById('sectionAds');
  const sectionComplaints = document.getElementById('sectionComplaints');
  const sectionUsers = document.getElementById('sectionUsers');

  const adsTableBody = document.getElementById('adsTableBody');
  const complaintsTableBody = document.getElementById('complaintsTableBody');
  const usersTableBody = document.getElementById('usersTableBody');
  const filterComplaintStatus = document.getElementById('filterComplaintStatus');

  const adModal = document.getElementById('adModal');
  const btnCreateAdModal = document.getElementById('btnCreateAdModal');
  const btnCloseAdModal = document.getElementById('btnCloseAdModal');
  const btnCancelAd = document.getElementById('btnCancelAd');
  const adForm = document.getElementById('adForm');

  const complaintModal = document.getElementById('complaintModal');
  const btnCloseComplaintModal = document.getElementById('btnCloseComplaintModal');
  const btnCancelComplaint = document.getElementById('btnCancelComplaint');
  const updateComplaintForm = document.getElementById('updateComplaintForm');
  const complaintTargetId = document.getElementById('complaintTargetId');
  const modalComplaintTitle = document.getElementById('modalComplaintTitle');
  const complaintNewStatus = document.getElementById('complaintNewStatus');
  const complaintAdminNote = document.getElementById('complaintAdminNote');

  // Switch tabs
  tabAds.addEventListener('click', () => switchTab('ads'));
  tabComplaints.addEventListener('click', () => switchTab('complaints'));
  tabUsers.addEventListener('click', () => switchTab('users'));

  function switchTab(tab) {
    [tabAds, tabComplaints, tabUsers].forEach(t => t.className = 'btn-secondary');
    [sectionAds, sectionComplaints, sectionUsers].forEach(s => s.style.display = 'none');

    if (tab === 'ads') {
      tabAds.className = 'btn-primary';
      sectionAds.style.display = 'block';
      loadAds();
    } else if (tab === 'complaints') {
      tabComplaints.className = 'btn-primary';
      sectionComplaints.style.display = 'block';
      loadComplaints();
    } else if (tab === 'users') {
      tabUsers.className = 'btn-primary';
      sectionUsers.style.display = 'block';
      loadUsers();
    }
  }

  // ================= ADS LOGIC =================
  async function loadAds() {
    adsTableBody.innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 24px;">Loading ads...</td></tr>';
    try {
      const ads = await API.admin.getAds();
      renderAds(ads);
    } catch (err) {
      adsTableBody.innerHTML = `<tr><td colspan="7" style="color: red; text-align: center;">Error loading ads: ${err.message}</td></tr>`;
    }
  }

  function renderAds(ads) {
    if (!ads || ads.length === 0) {
      adsTableBody.innerHTML = '<tr><td colspan="7" style="text-align: center;">No ads registered.</td></tr>';
      return;
    }

    adsTableBody.innerHTML = ads.map(a => `
      <tr>
        <td>
          <img src="${a.image || 'images/ethical.png'}" alt="Creative" style="width: 70px; height: 45px; object-fit: cover; border-radius: 4px; background: #eee;" onerror="this.src='/images/ethical.png';" />
        </td>
        <td style="font-weight: 700;">${a.title}</td>
        <td><span class="badge" style="background: #f1f5f9; color: #475569;">${a.position}</span></td>
        <td style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
          <a href="${a.ad_link}" target="_blank" style="color: var(--color-primary);">${a.ad_link}</a>
        </td>
        <td style="font-weight: 700;">${(a.clicks || 0).toLocaleString()}</td>
        <td>
          <span class="badge ${a.is_active ? 'badge-published' : 'badge-rejected'}">
            ${a.is_active ? 'Active' : 'Paused'}
          </span>
        </td>
        <td>
          <button onclick="deleteAdCampaign(${a.id})" class="btn-secondary" style="padding: 4px 10px; font-size: 0.8rem; color: red;">
            Delete
          </button>
        </td>
      </tr>
    `).join('');
  }

  window.deleteAdCampaign = async (id) => {
    if (!confirm('Are you sure you want to delete this ad campaign?')) return;
    try {
      await API.admin.deleteAd(id);
      loadAds();
    } catch (err) {
      alert('Delete failed: ' + err.message);
    }
  };

  btnCreateAdModal.addEventListener('click', () => adModal.classList.add('active'));
  btnCloseAdModal.addEventListener('click', () => adModal.classList.remove('active'));
  btnCancelAd.addEventListener('click', () => adModal.classList.remove('active'));

  adForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const payload = {
      title: document.getElementById('adTitle').value.trim(),
      description: document.getElementById('adDesc').value.trim(),
      image: document.getElementById('adImg').value.trim() || 'images/ethical.png',
      ad_link: document.getElementById('adLink').value.trim(),
      position: document.getElementById('adPos').value,
      is_active: parseInt(document.getElementById('adActive').value, 10)
    };

    try {
      await API.admin.saveAd(payload);
      adModal.classList.remove('active');
      adForm.reset();
      loadAds();
    } catch (err) {
      alert('Failed to save ad: ' + err.message);
    }
  });

  // ================= COMPLAINTS LOGIC =================
  async function loadComplaints() {
    complaintsTableBody.innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 24px;">Loading complaints...</td></tr>';
    try {
      const status = filterComplaintStatus.value;
      const complaints = await API.admin.getComplaints({ status });
      renderComplaints(complaints);
    } catch (err) {
      complaintsTableBody.innerHTML = `<tr><td colspan="7" style="color: red; text-align: center;">Error loading complaints: ${err.message}</td></tr>`;
    }
  }

  function renderComplaints(complaints) {
    if (!complaints || complaints.length === 0) {
      complaintsTableBody.innerHTML = '<tr><td colspan="7" style="text-align: center;">No complaints in this category.</td></tr>';
      return;
    }

    complaintsTableBody.innerHTML = complaints.map(c => `
      <tr>
        <td style="font-weight: 700;">#${c.id}</td>
        <td>${c.submitted_by || 'Citizen'}</td>
        <td><strong>${c.pincode}</strong><br><span style="font-size: 0.8rem; color: var(--color-text-muted);">${c.location}</span></td>
        <td style="font-weight: 600;">${c.title}</td>
        <td style="max-width: 250px; font-size: 0.85rem; line-height: 1.4;">${c.description}</td>
        <td>
          <span class="badge badge-${c.status === 'Resolved' ? 'resolved' : c.status === 'In Review' ? 'review' : c.status === 'Rejected' ? 'rejected' : 'submitted'}">
            ${c.status}
          </span>
        </td>
        <td>
          <button onclick="openComplaintModal(${c.id}, '${c.title.replace(/'/g, "\\'")}', '${c.status}')" class="btn-primary" style="padding: 4px 10px; font-size: 0.8rem;">
            Review & Action
          </button>
        </td>
      </tr>
    `).join('');
  }

  window.openComplaintModal = (id, title, status) => {
    complaintTargetId.value = id;
    modalComplaintTitle.textContent = title;
    complaintNewStatus.value = status || 'In Review';
    complaintModal.classList.add('active');
  };

  btnCloseComplaintModal.addEventListener('click', () => complaintModal.classList.remove('active'));
  btnCancelComplaint.addEventListener('click', () => complaintModal.classList.remove('active'));

  updateComplaintForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const id = complaintTargetId.value;
    const status = complaintNewStatus.value;
    const adminResponse = complaintAdminNote.value.trim();

    try {
      await API.admin.updateComplaint(id, { status, adminResponse });
      complaintModal.classList.remove('active');
      updateComplaintForm.reset();
      loadComplaints();
    } catch (err) {
      alert('Failed to update complaint: ' + err.message);
    }
  });

  filterComplaintStatus.addEventListener('change', loadComplaints);

  // ================= USERS LOGIC =================
  async function loadUsers() {
    usersTableBody.innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 24px;">Loading users...</td></tr>';
    try {
      const users = await API.admin.getUsers();
      usersTableBody.innerHTML = users.map(u => `
        <tr>
          <td style="font-weight: 700;">#${u.id}</td>
          <td style="font-weight: 600;">${u.full_name}</td>
          <td>${u.email}</td>
          <td>${u.company || 'Citizen Journalist'}</td>
          <td>
            <span class="badge" style="background: ${u.role === 'admin' ? '#fee2e2' : u.role === 'reporter' ? '#fef3c7' : '#e0f2fe'}; color: ${u.role === 'admin' ? '#991b1b' : u.role === 'reporter' ? '#92400e' : '#075985'};">
              ${u.role}
            </span>
          </td>
          <td>${u.pincode || 'Karnataka'}</td>
          <td><strong>${u.plan || 'Free'}</strong></td>
        </tr>
      `).join('');
    } catch (err) {
      usersTableBody.innerHTML = `<tr><td colspan="7" style="color: red; text-align: center;">Error loading users: ${err.message}</td></tr>`;
    }
  }

  loadAds();
});
