document.addEventListener('DOMContentLoaded', () => {
  const statTotalViews = document.getElementById('statTotalViews');
  const statTotalLeads = document.getElementById('statTotalLeads');
  const statTotalComplaints = document.getElementById('statTotalComplaints');
  const statTotalArticles = document.getElementById('statTotalArticles');
  const topArticlesBody = document.getElementById('topArticlesBody');
  const leadsBody = document.getElementById('leadsBody');
  const newsletterStatsContainer = document.getElementById('newsletterStatsContainer');
  const btnExport = document.getElementById('btnExportLeads');
  const btnTableExport = document.getElementById('btnTableExportLeads');

  let currentLeads = [];

  async function loadAnalytics() {
    try {
      const overview = await API.analytics.getOverview();
      statTotalViews.textContent = (overview.totalViews || 0).toLocaleString();
      statTotalLeads.textContent = (overview.totalLeads || 0).toLocaleString();
      statTotalComplaints.textContent = (overview.totalComplaints || 0).toLocaleString();
      statTotalArticles.textContent = (overview.totalArticles || 0).toLocaleString();
    } catch (err) {
      console.error('Failed to load overview:', err);
    }

    try {
      const topArticles = await API.analytics.getTopArticles();
      renderTopArticles(topArticles);
    } catch (err) {
      topArticlesBody.innerHTML = `<tr><td colspan="7" style="color: red; text-align: center;">Error: ${err.message}</td></tr>`;
    }

    try {
      currentLeads = await API.analytics.getLeads();
      renderLeads(currentLeads);
    } catch (err) {
      leadsBody.innerHTML = `<tr><td colspan="6" style="color: red; text-align: center;">Error: ${err.message}</td></tr>`;
    }

    try {
      const nl = await API.analytics.getNewsletterStats();
      renderNewsletterStats(nl);
    } catch (err) {
      console.error('Newsletter stats error:', err);
    }
  }

  function renderTopArticles(articles) {
    if (!articles || articles.length === 0) {
      topArticlesBody.innerHTML = '<tr><td colspan="7" style="text-align: center;">No articles available.</td></tr>';
      return;
    }

    topArticlesBody.innerHTML = articles.map((art, idx) => `
      <tr>
        <td style="font-weight: 700; color: ${idx === 0 ? '#eab308' : idx === 1 ? '#94a3b8' : idx === 2 ? '#b45309' : '#64748b'};">
          #${idx + 1}
        </td>
        <td style="font-weight: 600; max-width: 320px;">
          <a href="/article.html?id=${art.id}" style="color: var(--color-dark);">${art.title}</a>
        </td>
        <td><span class="badge" style="background: #f1f5f9; color: #475569;">${art.district || art.category_name}</span></td>
        <td>${art.publisher || 'News Junction'}</td>
        <td style="font-weight: 700;">${(art.views || 0).toLocaleString()}</td>
        <td style="color: var(--color-primary); font-weight: 600;">❤️ ${art.likes || 0}</td>
        <td>
          <div style="background: #e2e8f0; border-radius: 10px; height: 8px; width: 100px; overflow: hidden;">
            <div style="background: var(--color-primary); height: 100%; width: ${Math.min(100, Math.round(((art.views || 0) / 2000) * 100))}%;"></div>
          </div>
        </td>
      </tr>
    `).join('');
  }

  function renderLeads(leads) {
    if (!leads || leads.length === 0) {
      leadsBody.innerHTML = '<tr><td colspan="6" style="text-align: center;">No leads captured yet. Enable "Read More" gating on articles to generate leads.</td></tr>';
      return;
    }

    leadsBody.innerHTML = leads.map(l => `
      <tr>
        <td style="font-weight: 600;">${l.lead_name}</td>
        <td><a href="mailto:${l.lead_email}" style="color: var(--color-primary);">${l.lead_email}</a></td>
        <td>${l.lead_mobile}</td>
        <td>${l.lead_company}</td>
        <td style="max-width: 240px; font-size: 0.85rem; color: var(--color-text-muted);">${l.article_title || 'Locked Article'}</td>
        <td style="font-size: 0.85rem;">${l.date_captured || 'Recent'}</td>
      </tr>
    `).join('');
  }

  function renderNewsletterStats(nl) {
    newsletterStatsContainer.innerHTML = `
      <div style="background: #f8fafc; padding: 16px; border-radius: 8px; border: 1px solid var(--color-border);">
        <p style="font-size: 0.85rem; color: var(--color-text-muted);">Active Subscribers</p>
        <h3 style="font-size: 1.5rem; font-weight: 700; color: #0f172a;">${(nl.activeSubscribers || 0).toLocaleString()}</h3>
      </div>
      <div style="background: #f0fdf4; padding: 16px; border-radius: 8px; border: 1px solid #bbf7d0;">
        <p style="font-size: 0.85rem; color: #166534;">Average Open Rate</p>
        <h3 style="font-size: 1.5rem; font-weight: 700; color: #15803d;">${nl.averageOpenRate || '80%'}</h3>
      </div>
      <div style="background: #eff6ff; padding: 16px; border-radius: 8px; border: 1px solid #bfdbfe;">
        <p style="font-size: 0.85rem; color: #1e40af;">Click-Through Rate</p>
        <h3 style="font-size: 1.5rem; font-weight: 700; color: #1d4ed8;">${nl.clickThroughRate || '28%'}</h3>
      </div>
    `;
  }

  function exportCSV() {
    if (!currentLeads || currentLeads.length === 0) {
      alert('No leads available to export.');
      return;
    }

    const headers = ['ID', 'Name', 'Email', 'Mobile', 'Company', 'Article Title', 'Date Captured'];
    const rows = currentLeads.map(l => [
      l.id,
      `"${(l.lead_name || '').replace(/"/g, '""')}"`,
      `"${(l.lead_email || '').replace(/"/g, '""')}"`,
      `"${(l.lead_mobile || '').replace(/"/g, '""')}"`,
      `"${(l.lead_company || '').replace(/"/g, '""')}"`,
      `"${(l.article_title || '').replace(/"/g, '""')}"`,
      `"${l.date_captured || ''}"`
    ]);

    const csvContent = [headers.join(','), ...rows.map(r => r.join(','))].join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.setAttribute('href', url);
    link.setAttribute('download', `news_junction_leads_${new Date().toISOString().slice(0, 10)}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  }

  if (btnExport) btnExport.addEventListener('click', exportCSV);
  if (btnTableExport) btnTableExport.addEventListener('click', exportCSV);

  loadAnalytics();
});
