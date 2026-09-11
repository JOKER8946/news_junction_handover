const express = require('express');
const router = express.Router();
const db = require('../db');

/**
 * Admin dashboard overview stats
 * Converts app/admin/index.php
 */
router.get('/stats', async (req, res) => {
  const stats = await db.getAnalyticsOverview();
  const allAds = await db.getAllAds();
  const allUsers = await db.getAllUsers();

  res.json({
    ...stats,
    totalAds: allAds.length,
    activeAds: allAds.filter(a => a.is_active).length,
    totalAdClicks: allAds.reduce((acc, a) => acc + (a.clicks || 0), 0),
    totalUsersCount: allUsers.length
  });
});

/**
 * Get all ads for management
 * Converts app/admin_ads.php & app/ads.php
 */
router.get('/ads', async (req, res) => {
  const ads = await db.getAllAds();
  res.json(ads);
});

/**
 * Create or update ad campaign
 */
router.post('/ads', async (req, res) => {
  const { id, title, description, image, ad_link, position, is_active } = req.body;

  if (!title) {
    return res.status(400).json({ error: 'Ad title is required' });
  }

  const savedAd = await db.createOrUpdateAd({
    id,
    title,
    description,
    image,
    ad_link,
    position: position || 'feed',
    is_active: is_active !== undefined ? is_active : 1
  });

  res.status(200).json({ status: 'OK', ad: savedAd });
});

/**
 * Delete ad
 */
router.delete('/ads/:id', async (req, res) => {
  const id = parseInt(req.params.id, 10);
  const deleted = await db.deleteAd(id);
  if (!deleted) {
    return res.status(404).json({ error: 'Ad not found' });
  }
  res.json({ status: 'OK', message: 'Ad deleted successfully' });
});

/**
 * Triage complaints
 * Converts app/admin_complaints.php
 */
router.get('/complaints', async (req, res) => {
  const { pincode, status } = req.query;
  let complaints = await db.getComplaints(pincode);
  if (status && status !== 'all') {
    complaints = complaints.filter(c => c.status.toLowerCase() === status.toLowerCase());
  }
  res.json(complaints);
});

/**
 * Update complaint status & note
 * Converts app/admin_complaint_detail.php
 */
router.put('/complaints/:id', async (req, res) => {
  const id = parseInt(req.params.id, 10);
  const { status, adminResponse } = req.body;

  const updated = await db.updateComplaintStatus(id, { status, adminResponse });
  if (!updated) {
    return res.status(404).json({ error: 'Complaint not found' });
  }

  res.json({ status: 'OK', complaint: updated });
});

/**
 * List all users
 */
router.get('/users', async (req, res) => {
  const users = await db.getAllUsers();
  res.json(users);
});

module.exports = router;
