const express = require('express');
const router = express.Router();
const db = require('../db');

/**
 * Platform analytics overview
 * Converts metrics from app/analytics.php & cream_dashboard.php
 */
router.get('/overview', async (req, res) => {
  const stats = await db.getAnalyticsOverview();
  res.json(stats);
});

/**
 * Top articles by readership
 */
router.get('/top-articles', async (req, res) => {
  const limit = parseInt(req.query.limit || '10', 10);
  const articles = await db.getTopArticles(limit);
  res.json(articles);
});

/**
 * Leads captured via article gates
 * Converts logic from app/analytics.php & signInProcess.php lead capture
 */
router.get('/leads', async (req, res) => {
  const leads = await db.getLeads();
  res.json(leads);
});

/**
 * Newsletter performance stats
 */
router.get('/newsletter-stats', async (req, res) => {
  res.json({
    activeSubscribers: 14850,
    averageOpenRate: '82.4%',
    clickThroughRate: '28.9%',
    recentBlasts: [
      { id: 'BLAST-104', title: 'Karnataka Infrastructure & Rail Special', delivered: 14200, opens: 11928, clicks: 4210, date: '2026-03-09' },
      { id: 'BLAST-103', title: 'Coastal Tourism & Udupi Maritime Digest', delivered: 13950, opens: 11439, clicks: 3950, date: '2026-03-06' },
      { id: 'BLAST-102', title: 'Agri-Tech Expo 2026 Innovations', delivered: 13600, opens: 11016, clicks: 3880, date: '2026-03-03' }
    ]
  });
});

module.exports = router;
