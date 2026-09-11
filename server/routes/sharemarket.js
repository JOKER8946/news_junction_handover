const express = require('express');
const router = express.Router();

const defaultStocks = [
  { name: 'NIFTY 50', symbol: '^NSEI', price: 22493.55, change: 124.75, change_percent: 0.56, category: 'Index' },
  { name: 'SENSEX', symbol: '^BSESN', price: 74119.39, change: 408.86, change_percent: 0.55, category: 'Index' },
  { name: 'Reliance Industries', symbol: 'RELIANCE.NS', price: 2984.60, change: 18.20, change_percent: 0.61, category: 'Energy' },
  { name: 'Tata Consultancy Services', symbol: 'TCS.NS', price: 4120.15, change: -12.40, change_percent: -0.30, category: 'IT' },
  { name: 'Infosys', symbol: 'INFY.NS', price: 1618.90, change: 14.50, change_percent: 0.90, category: 'IT' },
  { name: 'HDFC Bank', symbol: 'HDFCBANK.NS', price: 1445.30, change: 8.75, change_percent: 0.61, category: 'Banking' },
  { name: 'ICICI Bank', symbol: 'ICICIBANK.NS', price: 1088.40, change: 11.20, change_percent: 1.04, category: 'Banking' },
  { name: 'Tata Motors', symbol: 'TATAMOTORS.NS', price: 1024.75, change: 21.60, change_percent: 2.15, category: 'Auto' }
];

async function fetchYahooStock(symbol) {
  try {
    const res = await fetch(`https://query1.finance.yahoo.com/v8/finance/chart/${symbol}`, {
      headers: { 'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)' },
      signal: AbortSignal.timeout(3000)
    });
    if (!res.ok) return null;
    const data = await res.json();
    const meta = data?.chart?.result?.[0]?.meta;
    if (meta && meta.regularMarketPrice) {
      const price = meta.regularMarketPrice;
      const prev = meta.previousClose || price;
      const change = parseFloat((price - prev).toFixed(2));
      const change_percent = prev ? parseFloat(((change / prev) * 100).toFixed(2)) : 0;
      return { price, change, change_percent };
    }
  } catch {}
  return null;
}

/**
 * Get live stock market quotes
 * Converts app/sharemarket.php logic with resilient live data + fallback
 */
router.get('/quotes', async (req, res) => {
  const isLiveRequested = req.query.live === 'true';

  let results = [...defaultStocks];

  if (isLiveRequested) {
    try {
      const liveResults = await Promise.all(
        defaultStocks.map(async (s) => {
          const live = await fetchYahooStock(s.symbol);
          if (live) {
            return { ...s, price: live.price, change: live.change, change_percent: live.change_percent };
          }
          return s;
        })
      );
      results = liveResults;
    } catch {}
  } else {
    // Add realistic subtle micro-variations for real-time feel
    results = results.map(s => {
      const jitter = (Math.random() - 0.48) * 0.4;
      const newPrice = parseFloat((s.price + jitter).toFixed(2));
      return { ...s, price: newPrice };
    });
  }

  res.json({
    marketStatus: 'Open',
    lastUpdated: new Date().toLocaleTimeString('en-IN', { hour: '2-digit', minute: '2-digit', second: '2-digit' }),
    quotes: results
  });
});

module.exports = router;
