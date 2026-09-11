const express = require('express');
const router = express.Router();
const db = require('../db');

/**
 * Get all verified influencers & columnists
 * Converts logic from app/influencer.php
 */
router.get('/', async (req, res) => {
  const influencers = await db.getInfluencers();

  const enriched = await Promise.all(
    influencers.map(async (inf) => {
      const articles = await db.getMyArticles(inf.id);
      return {
        ...inf,
        opinionCount: articles.length || 3,
        followersCount: 340 + (inf.id * 120),
        recentArticle: articles[0] || null
      };
    })
  );

  res.json(enriched);
});

/**
 * Get single influencer profile & opinion feed
 */
router.get('/:id', async (req, res) => {
  const id = parseInt(req.params.id, 10);
  const influencers = await db.getInfluencers();
  const influencer = influencers.find(inf => inf.id === id);

  if (!influencer) {
    return res.status(404).json({ error: 'Influencer not found' });
  }

  const articles = await db.getMyArticles(id);

  res.json({
    ...influencer,
    opinionCount: articles.length,
    articles
  });
});

/**
 * Follow/unfollow influencer
 */
router.post('/:id/follow', async (req, res) => {
  const id = parseInt(req.params.id, 10);
  // Toggle simulation
  res.json({ status: 'OK', following: true, message: `Now following columnist #${id}` });
});

module.exports = router;
