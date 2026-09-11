const express = require('express');
const router = express.Router();
const db = require('../db');

/**
 * Get active ads
 */
router.get('/', async (req, res) => {
  const position = req.query.position || 'feed';
  const ads = await db.getAds(position);
  res.json(ads);
});

/**
 * Ad click tracker and redirect
 * Matches process/ad_click_handler.php
 */
router.get('/click/:id', async (req, res) => {
  const adId = req.params.id;
  const userId = (req.session && req.session.userId) || null;

  const targetLink = await db.recordAdClick(adId, userId);
  return res.redirect(targetLink || '/');
});

module.exports = router;
