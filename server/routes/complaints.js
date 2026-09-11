const express = require('express');
const router = express.Router();
const db = require('../db');

/**
 * Get complaints by pincode or list all
 */
router.get('/', async (req, res) => {
  const pincode = req.query.pincode || null;
  const list = await db.getComplaints(pincode);
  res.json(list);
});

/**
 * Submit a complaint
 */
router.post('/', async (req, res) => {
  const { pincode, location, title, description } = req.body;

  if (!pincode || !title || !description) {
    return res.status(400).json({ error: 'Pincode, title, and description are required' });
  }

  const submittedBy = (req.session && req.session.userName) || 'Citizen';

  const newComplaint = await db.addComplaint({
    pincode,
    location: location || `Pincode ${pincode}`,
    title,
    description,
    submittedBy
  });

  res.json({ status: 'OK', complaint: newComplaint });
});

module.exports = router;
