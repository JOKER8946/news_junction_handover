const express = require('express');
const router = express.Router();
const db = require('../db');
const path = require('path');
const multer = require('multer');

// Configure multer for profile picture uploads
const storage = multer.diskStorage({
  destination: function (req, file, cb) {
    cb(null, path.join(__dirname, '../../app/data/profilePic'));
  },
  filename: function (req, file, cb) {
    const ext = path.extname(file.originalname);
    cb(null, 'avatar_' + Date.now() + ext);
  }
});
const upload = multer({ storage: storage, limits: { fileSize: 5 * 1024 * 1024 } });

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
 * Get profile
 */
router.get('/:id?', async (req, res) => {
  let targetId = req.params.id ? parseInt(req.params.id, 10) : getUserId(req);

  if (!targetId) {
    return res.status(401).json({ error: 'User ID is required' });
  }

  const user = await db.findUserById(targetId);
  if (!user) {
    return res.status(404).json({ error: 'User not found' });
  }

  const savedArticles = await db.getUserCollections(targetId);

  res.json({
    id: user.id,
    fullName: user.full_name,
    email: user.email,
    phone: user.phone_no,
    countryCode: user.country_code,
    pincode: user.pincode,
    company: user.company,
    website: user.website,
    bio: user.bio,
    profilePic: user.profile_pic || 'default.png',
    subdomain: user.subdomain,
    plan: user.plan,
    role: user.role,
    stats: {
      savedCount: savedArticles.length,
      visits: user.num_visits || 1
    },
    savedArticles
  });
});

/**
 * Update Profile
 * Matches act=updateProfile from app/signInProcess.php
 */
router.put('/', async (req, res) => {
  const currentUserId = getUserId(req);
  if (!currentUserId) {
    return res.status(401).json({ status: 'error', message: 'You must be logged in to update your profile.' });
  }

  const { userName, userEmail, userPhone, countryCode, userCompany, userWebsite, userBio, pincode } = req.body;

  if (!userName || !userEmail) {
    return res.status(400).json({ status: 'error', message: 'Name and Email are required.' });
  }

  try {
    const updated = await db.updateUser(currentUserId, {
      fullName: userName,
      email: userEmail,
      phone: userPhone,
      countryCode: countryCode || '+91',
      company: userCompany,
      website: userWebsite,
      bio: userBio,
      pincode: pincode
    });

    // Update cookie and session
    if (req.session) {
      req.session.userName = userName;
      req.session.userEmail = userEmail;
    }

    res.json({ status: 'OK', message: 'Profile updated successfully.', user: updated });
  } catch (err) {
    res.status(500).json({ status: 'error', message: err.message });
  }
});

/**
 * Upload Avatar
 */
router.post('/upload-avatar', (req, res) => {
  const currentUserId = getUserId(req);
  if (!currentUserId) {
    return res.status(401).json({ status: 'error', message: 'Unauthorized' });
  }

  upload.single('avatar')(req, res, async function (err) {
    if (err) {
      return res.status(400).json({ status: 'error', message: err.message });
    }
    if (!req.file) {
      return res.status(400).json({ status: 'error', message: 'No file uploaded' });
    }

    const filename = req.file.filename;
    // Update profile pic in database
    await db.updateUser(currentUserId, { profile_pic: filename });

    return res.json({
      status: 'OK',
      message: 'Avatar uploaded successfully',
      avatarUrl: '/data/profilePic/' + filename
    });
  });
});

module.exports = router;
