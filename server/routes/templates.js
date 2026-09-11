const express = require('express');
const router = express.Router();
const db = require('../db');

/**
 * Get all landing page templates
 * Converts app/CreateLeadPage/savedPages.php
 */
router.get('/', async (req, res) => {
  const templates = await db.getTemplates();
  res.json(templates);
});

/**
 * Get template by ID
 * Converts app/CreateLeadPage/load_template.php
 */
router.get('/:id', async (req, res) => {
  const id = parseInt(req.params.id, 10);
  const template = await db.getTemplateById(id);
  if (!template) {
    return res.status(404).json({ error: 'Template not found' });
  }
  res.json(template);
});

/**
 * Save new lead capture template
 * Converts app/CreateLeadPage/save-template.php
 */
router.post('/save', async (req, res) => {
  const { name, heroTitle, heroSubtitle, ctaText, primaryColor, theme, fields } = req.body;

  if (!heroTitle) {
    return res.status(400).json({ error: 'Hero title is required' });
  }

  const saved = await db.saveTemplate({
    name: name || 'Custom Lead Landing Page',
    heroTitle,
    heroSubtitle: heroSubtitle || '',
    ctaText: ctaText || 'Sign Up',
    primaryColor: primaryColor || '#b00000',
    theme: theme || 'light',
    fields: fields || ['name', 'email', 'mobile']
  });

  res.status(201).json({ status: 'OK', template: saved });
});

/**
 * Generate standalone export HTML for lead page
 */
router.post('/export', (req, res) => {
  const { heroTitle, heroSubtitle, ctaText, primaryColor, theme } = req.body;

  const html = `<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>${heroTitle || 'News Junction'}</title>
  <style>
    body { margin: 0; font-family: 'Segoe UI', system-ui, sans-serif; background: ${theme === 'dark' ? '#121212' : '#f8fafc'}; color: ${theme === 'dark' ? '#ffffff' : '#1e293b'}; display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 20px; box-sizing: border-box; }
    .card { background: ${theme === 'dark' ? '#1e1e1e' : '#ffffff'}; max-width: 520px; width: 100%; border-radius: 16px; padding: 36px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); text-align: center; }
    h1 { color: ${primaryColor || '#b00000'}; font-size: 1.85rem; margin-bottom: 12px; }
    p { color: #64748b; font-size: 1.05rem; line-height: 1.5; margin-bottom: 24px; }
    .form-group { margin-bottom: 16px; text-align: left; }
    label { font-size: 0.9rem; font-weight: 600; margin-bottom: 6px; display: block; }
    input { width: 100%; padding: 12px 14px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 1rem; box-sizing: border-box; }
    button { width: 100%; padding: 14px; background: ${primaryColor || '#b00000'}; color: #fff; font-weight: 700; border: none; border-radius: 8px; font-size: 1.05rem; cursor: pointer; margin-top: 10px; transition: opacity 0.2s; }
    button:hover { opacity: 0.9; }
  </style>
</head>
<body>
  <div class="card">
    <h1>${heroTitle || 'Join Our Community'}</h1>
    <p>${heroSubtitle || 'Stay updated with local and regional breaking stories.'}</p>
    <form onsubmit="event.preventDefault(); alert('Lead captured successfully!');">
      <div class="form-group">
        <label>Full Name</label>
        <input type="text" required placeholder="Your full name" />
      </div>
      <div class="form-group">
        <label>Email Address</label>
        <input type="email" required placeholder="you@domain.com" />
      </div>
      <div class="form-group">
        <label>Mobile Number</label>
        <input type="tel" placeholder="+91 9876543210" />
      </div>
      <button type="submit">${ctaText || 'Submit'}</button>
    </form>
  </div>
</body>
</html>`;

  res.setHeader('Content-Type', 'text/html');
  res.send(html);
});

module.exports = router;
