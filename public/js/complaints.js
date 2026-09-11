/**
 * News Junction - Citizen Complaints & Grievance Logic
 */

document.addEventListener('DOMContentLoaded', async () => {
  checkAuth();
  await loadComplaints();
  initFilters();
  initModal();
});

async function checkAuth() {
  const container = document.getElementById('complaintsNavAuth');
  try {
    const res = await API.auth.checkSession();
    if (res.authenticated && res.user) {
      const initial = (res.user.fullName || 'U').charAt(0).toUpperCase();
      container.innerHTML = `
        <a href="/profile.html" class="nav-user-badge">
          <div class="nav-user-avatar">${initial}</div>
          <span>${res.user.fullName}</span>
        </a>
      `;
    }
  } catch {}
}

async function loadComplaints(pincode = null) {
  const grid = document.getElementById('complaintsGrid');
  const emptyMsg = document.getElementById('noComplaintsMsg');
  const countBadge = document.getElementById('complaintsCount');

  grid.innerHTML = '<div style="grid-column: 1/-1; text-align:center; padding:30px;">Loading reports...</div>';
  emptyMsg.style.display = 'none';

  try {
    const list = await API.complaints.get(pincode);
    if (!list || list.length === 0) {
      grid.innerHTML = '';
      emptyMsg.style.display = 'block';
      countBadge.textContent = '0 reports';
      return;
    }

    countBadge.textContent = `${list.length} reports logged`;

    grid.innerHTML = list.map(c => `
      <div class="feature-card" style="display:flex; flex-direction:column; justify-content:space-between;">
        <div>
          <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:10px;">
            <span class="card-badge" style="position:static; background:var(--color-navy);">Pincode ${c.pincode}</span>
            <span style="font-size:0.75rem; font-weight:700; padding:3px 8px; border-radius:4px; ${c.status === 'Resolved' ? 'background:#dcfce7; color:#166534;' : 'background:#fef3c7; color:#92400e;'}">
              ${c.status || 'Under Review'}
            </span>
          </div>
          <h3 style="font-size:1.15rem; margin-bottom:6px; color:var(--color-text-dark);">${c.title}</h3>
          <p style="font-size:0.85rem; color:var(--color-primary); font-weight:600; margin-bottom:10px;">📍 ${c.location}</p>
          <p style="font-size:0.92rem; color:var(--color-text-muted); line-height:1.5;">${c.description}</p>
        </div>

        <div style="margin-top:16px; padding-top:12px; border-top:1px solid #f1f5f9; display:flex; justify-content:space-between; font-size:0.8rem; color:#94a3b8;">
          <span>Reported by: ${c.submitted_by || 'Citizen'}</span>
          <span>${c.date ? new Date(c.date).toLocaleDateString() : 'Recent'}</span>
        </div>
      </div>
    `).join('');
  } catch (err) {
    grid.innerHTML = '<div style="grid-column: 1/-1; text-align:center; padding:30px; color:#ef4444;">Failed to load complaints.</div>';
  }
}

function initFilters() {
  const searchInput = document.getElementById('pincodeSearch');
  const filterBtn = document.getElementById('filterPincodeBtn');
  const resetBtn = document.getElementById('resetPincodeBtn');

  filterBtn.addEventListener('click', () => {
    const val = searchInput.value.trim();
    if (val) loadComplaints(val);
  });

  resetBtn.addEventListener('click', () => {
    searchInput.value = '';
    loadComplaints();
  });
}

function initModal() {
  const modal = document.getElementById('complaintModal');
  const openBtn = document.getElementById('openComplaintModalBtn');
  const closeBtn = document.getElementById('closeComplaintModalBtn');
  const form = document.getElementById('complaintForm');

  openBtn.addEventListener('click', () => modal.style.display = 'flex');
  closeBtn.addEventListener('click', () => modal.style.display = 'none');
  modal.addEventListener('click', (e) => {
    if (e.target === modal) modal.style.display = 'none';
  });

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = document.getElementById('submitCompBtn');
    btn.disabled = true;
    btn.textContent = 'Submitting Report...';

    const data = {
      pincode: document.getElementById('compPincode').value.trim(),
      location: document.getElementById('compLocation').value.trim(),
      title: document.getElementById('compTitle').value.trim(),
      description: document.getElementById('compDesc').value.trim()
    };

    try {
      await API.complaints.submit(data);
      alert('Report submitted successfully! The district desk has been notified.');
      modal.style.display = 'none';
      form.reset();
      await loadComplaints();
    } catch (err) {
      alert('Failed to submit report. Please check input fields.');
    } finally {
      btn.disabled = false;
      btn.textContent = 'Submit Report to Editors';
    }
  });
}
