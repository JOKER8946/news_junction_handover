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
 * Get district newspapers list
 * Converts logic from app/district_newspapers.php
 */
router.get('/', async (req, res) => {
  const { district } = req.query;
  const newspapers = await db.getNewspapers(district);
  res.json(newspapers);
});

/**
 * Submit / upload new newspaper edition
 * Converts logic from app/add_newspaper.php
 */
router.post('/', async (req, res) => {
  const userId = getUserId(req) || 1;
  const { title, district, publisher, edition_date, pages, thumbnail, pdf_url } = req.body;

  if (!title || !district) {
    return res.status(400).json({ error: 'Title and district are required' });
  }

  const newPaper = await db.addNewspaper({
    title,
    district,
    publisher: publisher || 'Regional Desk',
    edition_date: edition_date || new Date().toISOString().slice(0, 10),
    pages: pages || 8,
    thumbnail: thumbnail || 'images/belagavi.png',
    pdf_url: pdf_url || '#',
    uploaded_by: userId
  });

  res.status(201).json({ status: 'OK', newspaper: newPaper });
});

module.exports = router;
