const express = require('express');
const router = express.Router();
const db = require('../db');

/**
 * List all news publishers
 * Converts fetch_all_publishers from app/magazine.php
 */
router.get('/publishers', async (req, res) => {
  const publishers = await db.getPublishers();
  res.json(publishers);
});

/**
 * Generate compiled digital magazine
 * Converts logic from app/magazine.php & created_magazine.html
 */
router.post('/generate', async (req, res) => {
  const { publishers = [], topics = [], categories = [], title = 'Digital News Edition', limit = 12 } = req.body;

  const activeCategories = topics.length > 0 ? topics : categories;
  const articles = await db.generateMagazine({
    publishers,
    categories: activeCategories,
    limit: parseInt(limit, 10)
  });

  res.json({
    title,
    generatedAt: new Date().toISOString(),
    totalArticles: articles.length,
    articles
  });
});

/**
 * Newsletter single send / blast simulation
 * Converts logic from app/analytics.php blast details & newsletter.php
 */
router.post('/blast', async (req, res) => {
  const { subject, recipientGroup, articleIds } = req.body;

  if (!subject) {
    return res.status(400).json({ error: 'Subject is required' });
  }

  const blastStats = {
    blastId: 'BLAST-' + Date.now().toString(36).toUpperCase(),
    subject,
    recipientGroup: recipientGroup || 'All Subscribers',
    delivered: 12450,
    uniqueOpens: 10458,
    openRate: '84%',
    uniqueClicks: 3820,
    clickRate: '30.6%',
    unsubscribes: 12,
    dateSent: new Date().toISOString().slice(0, 19).replace('T', ' ')
  };

  res.json({ status: 'OK', stats: blastStats });
});

module.exports = router;
