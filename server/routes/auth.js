const express = require('express');
const router = express.Router();
const db = require('../db');
const { simpleEncDec, verifyPassword } = require('../crypto');

// Middleware to get client IP
function getClientIp(req) {
  return req.headers['x-forwarded-for'] || req.socket.remoteAddress || '127.0.0.1';
}

/**
 * Check if email exists
 * Matches act=chkExist
 */
router.post('/check-exist', async (req, res) => {
  const email = req.body.signEmail || req.body.email || '';
  if (!email) return res.status(400).send('Email is required');

  const user = await db.findUserByEmail(email);
  if (!user) {
    return res.send('OK'); // Matching PHP response: 'OK' when available
  }
  return res.send('');
});

/**
 * User Login
 * Matches login_process from app/process/logInCheck.php
 */
router.post('/login', async (req, res) => {
  const email = (req.body.email || req.body.login || '').trim();
  const pwd = (req.body.pwd || req.body.password || '').trim();

  if (!email || !pwd) {
    return res.status(400).json({ status: 'error', message: 'Email and password are required.' });
  }

  const user = await db.findUserByEmail(email);
  if (!user) {
    return res.status(401).json({ status: 'error', message: 'Invalid credentials.' });
  }

  // Verify password (supports bcrypt and plaintext)
  const isValid = verifyPassword(pwd, user.password);
  if (!isValid) {
    return res.status(401).json({ status: 'error', message: 'Invalid credentials.' });
  }

  if (user.is_activated !== 1) {
    return res.status(403).json({ status: 'notActivated', message: 'Account is not activated.' });
  }

  if (user.role === 'reporter' && user.status && user.status !== 'verified') {
    return res.status(403).json({ status: 'reporterNotVerified', message: 'Reporter account pending verification.' });
  }

  // Record login activity
  const ip = getClientIp(req);
  const sessionId = req.sessionID || 'sess_' + Date.now();
  await db.recordLogin(user.id, ip, sessionId);

  // Set session
  req.session.user_logged_in = true;
  req.session.userId = user.id;
  req.session.userPlan = user.plan || 'Free';
  req.session.userName = user.full_name;
  req.session.userEmail = user.email;
  req.session.userPincode = user.pincode;
  req.session.userSubdomain = user.subdomain;
  req.session.role = user.role || 'user';

  // Set knobly_user_data cookie matching PHP
  const cookieData = {
    userId: user.id,
    userEmail: user.email,
    userPlan: user.plan || 'Free',
    userName: user.full_name,
    userPincode: user.pincode,
    userSubdomain: user.subdomain
  };

  res.cookie('knobly_user_data', JSON.stringify(cookieData), {
    maxAge: 30 * 24 * 60 * 60 * 1000,
    httpOnly: false,
    path: '/'
  });

  const encryptedId = simpleEncDec(user.id, 'e');

  // Return both JSON and PHP compatible header/content if requested
  if (req.headers.accept && req.headers.accept.includes('application/json')) {
    return res.json({
      status: 'OK',
      token: encryptedId,
      user: cookieData
    });
  }

  return res.send(`OK|${encryptedId}`);
});

/**
 * Create Account
 * Matches act=createAccount from app/signInProcess.php
 */
router.post('/register', async (req, res) => {
  const fullName = req.body.signFullName || req.body.fullName || '';
  const email = req.body.signEmail || req.body.email || '';
  const phone = req.body.userPhone || req.body.phone || '';
  const countryCode = req.body.countryCode || '+91';
  const pincode = req.body.pincode || '';
  const password = req.body.signPwd1 || req.body.password || '';
  const company = req.body.signCompany || req.body.company || '';
  const website = req.body.signWebsite || req.body.website || '';

  if (!fullName || !email || !password) {
    return res.status(400).json({ status: 'error', message: 'Full name, email, and password are required.' });
  }

  // Email format validation
  if (!/\S+@\S+\.\S+/.test(email)) {
    return res.status(400).json({ status: 'error', message: 'Invalid email address.' });
  }

  // Check if email already taken
  const existing = await db.findUserByEmail(email);
  if (existing) {
    return res.status(400).json({ status: 'error', message: 'This email address is already registered.' });
  }

  try {
    const newUser = await db.createUser({
      fullName,
      email,
      phone,
      countryCode,
      pincode,
      password,
      company,
      website
    });

    const ip = getClientIp(req);
    await db.recordLogin(newUser.id, ip, req.sessionID);

    req.session.user_logged_in = true;
    req.session.userId = newUser.id;
    req.session.userName = newUser.full_name;
    req.session.userEmail = newUser.email;
    req.session.userPlan = 'Free';
    req.session.userPincode = newUser.pincode;

    const cookieData = {
      userId: newUser.id,
      userEmail: newUser.email,
      userPlan: 'Free',
      userName: newUser.full_name,
      userPincode: newUser.pincode,
      userSubdomain: ''
    };

    res.cookie('knobly_user_data', JSON.stringify(cookieData), {
      maxAge: 30 * 24 * 60 * 60 * 1000,
      path: '/'
    });

    return res.json({ status: 'OK', userId: newUser.id });
  } catch (err) {
    return res.status(500).json({ status: 'error', message: err.message });
  }
});

/**
 * Forgot Password Request
 * Matches act=resetPassword from app/signInProcess.php
 */
router.post('/forgot-password', async (req, res) => {
  const email = req.body.email || req.body.forgotLogin || '';
  if (!email) {
    return res.status(400).json({ status: 'error', message: 'Email is required.' });
  }

  const user = await db.findUserByEmail(email);
  if (user) {
    const token = simpleEncDec(email, 'e');
    const resetUrl = `/sign-in.html?action=reset&token=${encodeURIComponent(token)}`;
    console.log(`[AUTH] Password reset requested for ${email}. Token URL: ${resetUrl}`);
  }

  // Always return OK for security and contract consistency
  return res.json({
    status: 'OK',
    message: 'If an account exists with this email, reset instructions have been dispatched.'
  });
});

/**
 * Reset Password Confirm
 * Matches act=resetPasswordConfirm from app/signInProcess.php
 */
router.post('/reset-password-confirm', async (req, res) => {
  const token = req.body.loginToken || req.body.token || '';
  const newPwd = req.body.loginPwd || req.body.password || '';

  if (!token || !newPwd) {
    return res.status(400).json({ status: 'error', message: 'Token and new password are required.' });
  }

  const decryptedEmail = simpleEncDec(token, 'd');
  if (!decryptedEmail) {
    return res.status(400).json({ status: 'error', message: 'Invalid or expired reset token.' });
  }

  const user = await db.findUserByEmail(decryptedEmail);
  if (!user) {
    return res.status(404).json({ status: 'error', message: 'User not found.' });
  }

  const ok = await db.updatePassword(decryptedEmail, newPwd);
  if (ok) {
    return res.json({ status: 'OK', message: `Dear ${user.full_name}: Your password has been successfully reset!` });
  }
  return res.status(500).json({ status: 'error', message: 'Password could not be reset.' });
});

/**
 * Current Session / Auth state
 */
router.get('/me', async (req, res) => {
  let userId = req.session && req.session.userId;

  // Fallback to cookie if session empty
  if (!userId && req.cookies && req.cookies.knobly_user_data) {
    try {
      const parsed = JSON.parse(req.cookies.knobly_user_data);
      userId = parsed.userId;
    } catch {}
  }

  if (!userId) {
    return res.json({ authenticated: false, user: null });
  }

  const user = await db.findUserById(userId);
  if (!user) {
    return res.json({ authenticated: false, user: null });
  }

  return res.json({
    authenticated: true,
    user: {
      id: user.id,
      fullName: user.full_name,
      email: user.email,
      phone: user.phone_no,
      countryCode: user.country_code,
      pincode: user.pincode,
      company: user.company,
      website: user.website,
      bio: user.bio,
      profilePic: user.profile_pic,
      subdomain: user.subdomain,
      plan: user.plan,
      role: user.role
    }
  });
});

/**
 * Logout
 */
router.all('/logout', (req, res) => {
  req.session.destroy(() => {
    res.clearCookie('knobly_user_data');
    res.clearCookie('connect.sid');
    if (req.xhr || (req.headers.accept && req.headers.accept.includes('application/json'))) {
      return res.json({ status: 'OK' });
    }
    return res.redirect('/index.html');
  });
});

module.exports = router;
