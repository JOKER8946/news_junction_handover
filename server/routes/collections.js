const express = require('express');
const router = express.Router();
const db = require('../db');

function getUserId(req) {
  if (req.session && req.session.userId) return req.session.userId;
  if (req.cookies && req.cookies.knobly_user_data) {
    try {
      const parsed = JSON.parse(req.cookies.knobly_user_data);
      return parsed.userId;
    } catch {}
  }
  return 3; // Fallback to demo user for testing
}

/**
 * Get user collections / reading list
 * Converts logic from app/collections.php & my_collection.php
 */
router.get('/', async (req, res) => {
  const userId = getUserId(req);
  const collections = await db.getUserCollections(userId);
  res.json(collections);
});

/**
 * Tag an article in collection
 */
router.post('/tag', async (req, res) => {
  const userId = getUserId(req);
  const { articleId, tag } = req.body;

  if (!articleId || !tag) {
    return res.status(400).json({ error: 'articleId and tag are required' });
  }

  const result = await db.tagCollection(userId, articleId, tag);
  res.json(result);
});

/**
 * Share article to eSamudaay
 * Converts logic from app/collections.php esamudaayshare()
 */
router.post('/esamudaay', async (req, res) => {
  const userId = getUserId(req);
  const { articleId } = req.body;

  if (!articleId) {
    return res.status(400).json({ error: 'articleId is required' });
  }

  const result = await db.shareToEsamudaay(userId, articleId);
  res.json(result);
});

/**
 * Remove from collections
 */
router.delete('/:id', async (req, res) => {
  const userId = getUserId(req);
  const articleId = parseInt(req.params.id, 10);

  const result = await db.toggleBookmark(userId, articleId);
  res.json({ status: 'OK', result });
});

module.exports = router;
