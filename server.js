const express = require('express');
const path = require('path');
const cors = require('cors');
const cookieParser = require('cookie-parser');
const session = require('express-session');
require('dotenv').config();

const authRoutes = require('./server/routes/auth');
const readerRoutes = require('./server/routes/reader');
const articleRoutes = require('./server/routes/articles');
const profileRoutes = require('./server/routes/profile');
const adsRoutes = require('./server/routes/ads');
const complaintRoutes = require('./server/routes/complaints');
const channelRoutes = require('./server/routes/channels');
const newspaperRoutes = require('./server/routes/newspapers');
const magazineRoutes = require('./server/routes/magazine');
const collectionRoutes = require('./server/routes/collections');
const analyticsRoutes = require('./server/routes/analytics');
const sharemarketRoutes = require('./server/routes/sharemarket');
const influencerRoutes = require('./server/routes/influencers');
const templateRoutes = require('./server/routes/templates');
const adminRoutes = require('./server/routes/admin');

const app = express();
const PORT = process.env.PORT || 3000;

// Security & Parsing Middlewares
app.use(cors({ origin: true, credentials: true }));
app.use(express.json({ limit: '10mb' }));
app.use(express.urlencoded({ extended: true, limit: '10mb' }));
app.use(cookieParser());

app.use(session({
  secret: 'news-junction-session-secret-2026',
  resave: false,
  saveUninitialized: false,
  cookie: {
    maxAge: 30 * 24 * 60 * 60 * 1000,
    httpOnly: false
  }
}));

// Backward compatibility redirects for existing PHP routes
app.get('/index.php', (req, res) => res.redirect('/'));
app.get('/sign-in.php', (req, res) => res.redirect('/sign-in.html' + (req.url.includes('?') ? req.url.slice(req.url.indexOf('?')) : '')));
app.get(['/reader.php', '/dashboard.php'], (req, res) => res.redirect('/reader.html'));
app.get('/view.php', (req, res) => {
  const id = req.query.id || '';
  res.redirect(id ? `/article.html?id=${id}` : '/reader.html');
});
app.get(['/profile.php', '/account.php', '/settings.php'], (req, res) => res.redirect('/profile.html'));
app.get(['/complaint_form.php', '/my_complaints.php', '/complaint_detail.php'], (req, res) => res.redirect('/complaints.html'));
app.get('/create.php', (req, res) => res.redirect('/create.html'));
app.get(['/articles.php', '/myfeeds.php', '/scheduled_posts.php'], (req, res) => res.redirect('/my-articles.html'));
app.get('/channel.php', (req, res) => {
  const id = req.query.channelId || req.query.id || '';
  res.redirect(id ? `/channel.html?id=${id}` : '/channels.html');
});
app.get(['/featured_channels.php', '/add_channel.php'], (req, res) => res.redirect('/channels.html'));
app.get(['/district_newspapers.php', '/add_newspaper.php'], (req, res) => {
  const dist = req.query.district || '';
  res.redirect(dist ? `/district-newspapers.html?district=${encodeURIComponent(dist)}` : '/district-newspapers.html');
});
app.get(['/magazine.php', '/newsletter.php'], (req, res) => res.redirect('/magazine.html'));
app.get(['/collections.php', '/my_collection.php', '/saved.php'], (req, res) => res.redirect('/collections.html'));
app.get(['/analytics.php', '/userActivity.php', '/cream_dashboard.php'], (req, res) => res.redirect('/analytics.html'));
app.get('/sharemarket.php', (req, res) => res.redirect('/sharemarket.html'));
app.get('/influencer.php', (req, res) => res.redirect('/influencers.html'));
app.get(['/CreateLeadPage', '/CreateLeadPage/index.php'], (req, res) => res.redirect('/lead-builder.html'));
app.get(['/admin_ads.php', '/ads.php', '/admin_complaints.php', '/admin_complaint_detail.php', '/admin', '/admin/index.php'], (req, res) => res.redirect('/admin.html'));
app.get('/logout.php', (req, res) => res.redirect('/api/auth/logout'));

// Legacy PHP POST dispatch compatibility
app.post(['/signInProcess.php', '/process/signInProcess.php'], (req, res, next) => {
  const act = req.body.act || '';
  if (act === 'chkExist') return req.url = '/check-exist', authRoutes(req, res, next);
  if (act === 'createAccount' || act === 'createUdupiAccount') return req.url = '/register', authRoutes(req, res, next);
  if (act === 'resetPassword') return req.url = '/forgot-password', authRoutes(req, res, next);
  if (act === 'resetPasswordConfirm') return req.url = '/reset-password-confirm', authRoutes(req, res, next);
  if (act === 'getBusinessType') return req.url = '/categories', readerRoutes(req, res, next);
  if (act === 'createLead') return req.url = `/${req.body.leadCollectionId}/lead`, articleRoutes(req, res, next);
  if (act === 'createPost') return req.url = '/create', articleRoutes(req, res, next);
  if (act === 'updateProfile') return req.url = '/', profileRoutes(req, res, next);
  return res.send('OK');
});

app.post(['/process/logInCheck.php', '/loggedInCheck.php'], (req, res, next) => {
  req.url = '/login';
  return authRoutes(req, res, next);
});

app.get('/process/ad_click_handler.php', (req, res) => {
  const id = req.query.ad_id || 0;
  res.redirect(`/api/ads/click/${id}`);
});

// Mount API routes
app.use('/api/auth', authRoutes);
app.use('/api/reader', readerRoutes);
app.use('/api/articles', articleRoutes);
app.use('/api/profile', profileRoutes);
app.use('/api/ads', adsRoutes);
app.use('/api/complaints', complaintRoutes);
app.use('/api/channels', channelRoutes);
app.use('/api/newspapers', newspaperRoutes);
app.use('/api/magazine', magazineRoutes);
app.use('/api/collections', collectionRoutes);
app.use('/api/analytics', analyticsRoutes);
app.use('/api/sharemarket', sharemarketRoutes);
app.use('/api/influencers', influencerRoutes);
app.use('/api/templates', templateRoutes);
app.use('/api/admin', adminRoutes);

// Static assets from original PHP directories (images, logos, fonts, data)
app.use('/grfx', express.static(path.join(__dirname, 'app/grfx')));
app.use('/images', express.static(path.join(__dirname, 'app/images')));
app.use('/data', express.static(path.join(__dirname, 'app/data')));
app.use('/assets', express.static(path.join(__dirname, 'app/assets')));
app.use('/inc', express.static(path.join(__dirname, 'app/inc')));

// Serve converted HTML, CSS, JS frontend
app.use(express.static(path.join(__dirname, 'public')));

// Fallback route for single page article views e.g. /view/:id
app.get('/view/:id*', (req, res) => {
  const id = req.params.id;
  res.redirect(`/article.html?id=${id}`);
});

// 404 handler
app.use((req, res) => {
  res.status(404).sendFile(path.join(__dirname, 'public/404.html'), (err) => {
    if (err) res.status(404).send('Page not found');
  });
});

app.listen(PORT, () => {
  console.log(`====================================================`);
  console.log(`  News Junction Node.js Server is Running!         `);
  console.log(`  Local URL: http://localhost:${PORT}               `);
  console.log(`====================================================`);
});

module.exports = app;
