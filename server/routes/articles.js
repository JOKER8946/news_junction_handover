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
 * Create a new article
 * Matches act=createPost from app/create.php
 */
router.post('/create', async (req, res) => {
  const userId = getUserId(req) || 1;
  const {
    postTitle,
    title,
    postBody,
    content,
    description,
    image,
    category_id,
    categoryId,
    district,
    pincode,
    author,
    tags,
    isArchive,
    isReadMore,
    readMoreTxt,
    readMoreResponse,
    readMoreEmail,
    isMandatoryCompany,
    isMandatoryEmail,
    isMandatoryMobile,
    scheduledFor
  } = req.body;

  const finalTitle = title || postTitle;
  const finalContent = content || postBody;

  if (!finalTitle || !finalContent) {
    return res.status(400).json({ error: 'Title and content are required' });
  }

  const newArticle = await db.createArticle({
    title: finalTitle,
    content: finalContent,
    description,
    image,
    category_id: categoryId || category_id,
    district,
    pincode,
    author_name: author || (req.session && req.session.userName) || 'News Junction Author',
    user_id: userId,
    tags,
    isArchive: isArchive === '1' || isArchive === true || isArchive === 1,
    isReadMore: isReadMore === '1' || isReadMore === true || isReadMore === 1,
    readMoreTxt,
    readMoreResponse,
    readMoreEmail,
    isMandatoryCompany: isMandatoryCompany === '1' || isMandatoryCompany === true || isMandatoryCompany === 1,
    isMandatoryEmail: isMandatoryEmail === '1' || isMandatoryEmail === true || isMandatoryEmail === 1,
    isMandatoryMobile: isMandatoryMobile === '1' || isMandatoryMobile === true || isMandatoryMobile === 1,
    scheduledFor
  });

  res.status(201).json({ status: 'OK', article: newArticle });
});

/**
 * Get articles by current logged in author
 */
router.get('/author/my', async (req, res) => {
  const userId = getUserId(req) || 1;
  const articles = await db.getMyArticles(userId);
  res.json(articles);
});

/**
 * Get scheduled articles
 */
router.get('/author/scheduled', async (req, res) => {
  const userId = getUserId(req);
  const scheduled = await db.getScheduledArticles(userId);
  res.json(scheduled);
});

/**
 * Get full article details by ID
 */
router.get('/:id', async (req, res) => {
  const id = parseInt(req.params.id, 10);
  if (isNaN(id)) return res.status(400).json({ error: 'Invalid article ID' });

  const article = await db.getArticleById(id);
  if (!article) {
    return res.status(404).json({ error: 'Article not found' });
  }

  const userId = getUserId(req);
  let isBookmarked = false;
  let isLiked = false;

  if (userId) {
    isBookmarked = await db.isArticleBookmarked(userId, id);
    isLiked = await db.isArticleLiked(userId, id);
  }

  const comments = await db.getComments(id);

  res.json({
    ...article,
    isBookmarked,
    isLiked,
    comments
  });
});

/**
 * Update existing article
 */
router.put('/:id', async (req, res) => {
  const id = parseInt(req.params.id, 10);
  const updated = await db.updateArticle(id, req.body);
  if (!updated) {
    return res.status(404).json({ error: 'Article not found' });
  }
  res.json({ status: 'OK', article: updated });
});

/**
 * Delete article
 */
router.delete('/:id', async (req, res) => {
  const id = parseInt(req.params.id, 10);
  const deleted = await db.deleteArticle(id);
  if (!deleted) {
    return res.status(404).json({ error: 'Article not found' });
  }
  res.json({ status: 'OK', message: 'Article deleted successfully' });
});

/**
 * Get comments for article
 */
router.get('/:id/comments', async (req, res) => {
  const id = parseInt(req.params.id, 10);
  const comments = await db.getComments(id);
  res.json(comments);
});

/**
 * Add comment to article
 */
router.post('/:id/comments', async (req, res) => {
  const id = parseInt(req.params.id, 10);
  const { comment, userName } = req.body;

  if (!comment || !comment.trim()) {
    return res.status(400).json({ error: 'Comment text is required' });
  }

  let authorName = userName;
  if (!authorName) {
    if (req.session && req.session.userName) {
      authorName = req.session.userName;
    } else if (req.cookies && req.cookies.knobly_user_data) {
      try {
        const parsed = JSON.parse(req.cookies.knobly_user_data);
        authorName = parsed.userName;
      } catch {}
    }
  }

  const newComment = await db.addComment(id, authorName, comment.trim());
  res.json({ status: 'OK', comment: newComment });
});

/**
 * Lead generation endpoint
 * Matches act=createLead from app/signInProcess.php
 */
router.post('/:id/lead', async (req, res) => {
  const articleId = parseInt(req.params.id, 10);
  const { leadName, leadCompany, leadEmail, leadMobile } = req.body;

  if (!leadName || !leadEmail) {
    return res.status(400).json({ status: 'error', message: 'Name and email are required.' });
  }

  const article = await db.getArticleById(articleId);
  await db.recordLead({
    article_id: articleId,
    article_title: article ? article.title : `Article #${articleId}`,
    lead_name: leadName,
    lead_email: leadEmail,
    lead_company: leadCompany || 'N/A',
    lead_mobile: leadMobile || 'N/A'
  });

  res.json({ status: 'OK', message: 'Thank you! Your information has been submitted successfully.' });
});

module.exports = router;
