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
  return null;
}

/**
 * Get news feeds with filtering and search
 */
router.get('/feeds', async (req, res) => {
  const categoryId = req.query.category || req.query.categoryId || 0;
  const district = req.query.district || '';
  const search = req.query.search || req.query.q || '';
  const limit = parseInt(req.query.limit || '20', 10);
  const page = parseInt(req.query.page || '1', 10);
  const offset = (page - 1) * limit;

  const currentUserId = getUserId(req);

  try {
    const result = await db.getArticles({
      categoryId,
      district,
      search,
      limit,
      offset
    });

    // Check bookmarks and likes for current user if logged in
    const articlesWithStatus = await Promise.all(
      result.articles.map(async (a) => {
        let isBookmarked = false;
        let isLiked = false;
        if (currentUserId) {
          isBookmarked = await db.isArticleBookmarked(currentUserId, a.id);
          isLiked = await db.isArticleLiked(currentUserId, a.id);
        }
        return {
          ...a,
          isBookmarked,
          isLiked
        };
      })
    );

    return res.json({
      total: result.total,
      page,
      limit,
      articles: articlesWithStatus
    });
  } catch (err) {
    console.error('Error fetching feeds:', err.message);
    return res.status(500).json({ error: 'Failed to fetch feeds' });
  }
});

/**
 * Get categories
 */
router.get('/categories', async (req, res) => {
  const categories = await db.getCategories();
  res.json(categories);
});

/**
 * Get channels
 */
router.get('/channels', async (req, res) => {
  const channels = await db.getChannels();
  res.json(channels);
});

/**
 * Get influencers / opinions
 */
router.get('/opinions', async (req, res) => {
  const opinions = await db.getInfluencers();
  res.json(opinions);
});

/**
 * Get user saved articles
 */
router.get('/saved', async (req, res) => {
  const userId = getUserId(req);
  if (!userId) {
    return res.status(401).json({ error: 'Unauthorized' });
  }

  const saved = await db.getUserCollections(userId);
  res.json(saved);
});

/**
 * Toggle bookmark
 */
router.post('/bookmark', async (req, res) => {
  const userId = getUserId(req);
  if (!userId) {
    return res.status(401).json({ error: 'Please sign in to save articles' });
  }

  const articleId = req.body.articleId || req.body.feedId;
  if (!articleId) {
    return res.status(400).json({ error: 'articleId is required' });
  }

  const result = await db.toggleBookmark(userId, articleId);
  res.json(result);
});

/**
 * Toggle like
 */
router.post('/like', async (req, res) => {
  const userId = getUserId(req) || 1; // Allow guest like fallback
  const articleId = req.body.articleId || req.body.streamId;

  if (!articleId) {
    return res.status(400).json({ error: 'articleId is required' });
  }

  const result = await db.toggleLike(userId, articleId);
  res.json(result);
});

module.exports = router;
