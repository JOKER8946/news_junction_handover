document.addEventListener('DOMContentLoaded', () => {
  const updatedTime = document.getElementById('marketUpdatedTime');
  const indicesContainer = document.getElementById('indicesContainer');
  const stocksContainer = document.getElementById('stocksContainer');
  const autoRefreshToggle = document.getElementById('autoRefreshToggle');
  const btnRefresh = document.getElementById('btnManualRefresh');

  let refreshTimer = null;

  async function loadMarketData(live = false) {
    try {
      const data = await API.sharemarket.getQuotes(live);
      updatedTime.textContent = data.lastUpdated || new Date().toLocaleTimeString();
      renderMarketData(data.quotes || []);
    } catch (err) {
      console.error('Share market load error:', err);
    }
  }

  function renderMarketData(quotes) {
    const indices = quotes.filter(q => q.category === 'Index');
    const equities = quotes.filter(q => q.category !== 'Index');

    // Render Indices
    indicesContainer.innerHTML = indices.map(idx => {
      const isPos = idx.change >= 0;
      return `
        <div class="stat-card" style="border-top: 4px solid ${isPos ? '#16a34a' : '#dc2626'};">
          <div style="flex-grow: 1;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
              <span style="font-size: 0.85rem; font-weight: 700; color: var(--color-text-muted); text-transform: uppercase;">${idx.name}</span>
              <span style="font-size: 0.8rem; font-weight: 600; color: ${isPos ? '#16a34a' : '#dc2626'};">
                ${isPos ? '▲' : '▼'} ${idx.change_percent}%
              </span>
            </div>
            <h2 style="font-size: 2rem; font-weight: 800; margin: 4px 0;">${idx.price.toLocaleString('en-IN', { minimumFractionDigits: 2 })}</h2>
            <p style="font-size: 0.9rem; font-weight: 600; color: ${isPos ? '#16a34a' : '#dc2626'};">
              ${isPos ? '+' : ''}${idx.change} pts
            </p>
          </div>
        </div>
      `;
    }).join('');

    // Render Equities
    stocksContainer.innerHTML = equities.map(stock => {
      const isPos = stock.change >= 0;
      return `
        <div class="stock-card">
          <div class="stock-header">
            <div>
              <div class="stock-name">${stock.name}</div>
              <div class="stock-symbol">${stock.symbol} • ${stock.category}</div>
            </div>
            <span class="badge" style="background: ${isPos ? '#dcfce7' : '#fee2e2'}; color: ${isPos ? '#15803d' : '#b91c1c'}; font-size: 0.72rem;">
              ${stock.category}
            </span>
          </div>
          <div>
            <div class="stock-price">₹${stock.price.toLocaleString('en-IN', { minimumFractionDigits: 2 })}</div>
            <div class="stock-change ${isPos ? 'positive' : 'negative'}">
              <span>${isPos ? '▲ +' : '▼ '}${stock.change}</span>
              <span>(${isPos ? '+' : ''}${stock.change_percent}%)</span>
            </div>
          </div>
        </div>
      `;
    }).join('');
  }

  function startAutoRefresh() {
    if (refreshTimer) clearInterval(refreshTimer);
    if (autoRefreshToggle.checked) {
      refreshTimer = setInterval(() => loadMarketData(false), 30000);
    }
  }

  autoRefreshToggle.addEventListener('change', () => {
    startAutoRefresh();
  });

  btnRefresh.addEventListener('click', () => {
    loadMarketData(true);
  });

  loadMarketData(true);
  startAutoRefresh();
});
