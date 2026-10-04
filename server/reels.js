const express = require('express');
const multer = require('multer');
const fs = require('node:fs/promises');
const os = require('node:os');
const path = require('node:path');
const crypto = require('node:crypto');
const { z } = require('zod');
const { query } = require('./database');
const router = express.Router();
const directory = path.join(__dirname, '../public/media/uploads/.reels');
const fail = (status, message) => Object.assign(new Error(message), { status });
const wrap = (fn) => (req, res, next) => Promise.resolve(fn(req, res, next)).catch(next);
const admin = (req, res, next) =>
  req.user?.role === 'admin' ? next() : next(fail(403, 'Administrator access required.'));
const fields = z.object({
  title: z.string().trim().min(1).max(150),
  caption: z.string().trim().max(2200).default(''),
  published: z.boolean(),
});
const receive = multer({
  dest: os.tmpdir(),
  limits: { fileSize: 100 * 1024 * 1024, files: 1, fields: 3, fieldSize: 10000 },
}).single('video');
const select = `SELECT r.id,r.title,r.caption,r.published,r.created_at,u.full_name AS author_name,
 '/api/reels/'||r.id||'/video' AS video_url,
 (SELECT count(*)::int FROM reel_likes l WHERE l.reel_id=r.id) AS likes,
 EXISTS(SELECT 1 FROM reel_likes l WHERE l.reel_id=r.id AND l.user_id=$1) AS liked
 FROM reels r LEFT JOIN users u ON u.id=r.user_id`;
router.get(
  '/',
  wrap(async (req, res) => {
    const page = Math.max(1, Math.min(10000, parseInt(req.query.page) || 1));
    const manage = req.query.manage === 'true';
    if (manage && req.user?.role !== 'admin') throw fail(403, 'Administrator access required.');
    const values = [req.user?.id || null];
    const focus = req.query.id
      ? (values.push(
          z
            .string()
            .regex(/^[0-9]+$/)
            .parse(req.query.id),
        ),
        ' AND r.id=$2')
      : '';
    const rows = (
      await query(
        select +
          ` WHERE ${manage ? 'true' : 'r.published'}${focus} ORDER BY r.created_at DESC,r.id DESC LIMIT 13 OFFSET ${(page - 1) * 12}`,
        values,
      )
    ).rows;
    res.json({ reels: rows.slice(0, 12), hasMore: rows.length > 12, page });
  }),
);
router.get(
  '/:id/video',
  wrap(async (req, res) => {
    const r = (
      await query('SELECT filename,mime,published FROM reels WHERE id=$1', [req.params.id])
    ).rows[0];
    if (!r || (!r.published && req.user?.role !== 'admin')) throw fail(404, 'Reel not found.');
    res.set('Cache-Control', 'private, no-store');
    res
      .type(r.mime)
      .sendFile(path.join(directory, path.basename(r.filename)), { dotfiles: 'allow' });
  }),
);
router.post(
  '/',
  admin,
  (req, res, next) =>
    receive(req, res, (err) => {
      if (err)
        return next(
          fail(
            err.code === 'LIMIT_FILE_SIZE' ? 413 : 400,
            err.code === 'LIMIT_FILE_SIZE'
              ? 'Videos must be 100 MB or smaller.'
              : 'Upload one MP4 or WebM video.',
          ),
        );
      next();
    }),
  wrap(async (req, res) => {
    let stored;
    try {
      if (!req.file) throw fail(400, 'Choose a video to upload.');
      if (!['true', 'false'].includes(req.body.published))
        throw fail(400, 'Choose draft or published.');
      const d = fields.parse({ ...req.body, published: req.body.published === 'true' });
      const { fileTypeFromFile } = await import('file-type');
      const type = await fileTypeFromFile(req.file.path);
      if (!type || !['video/mp4', 'video/webm'].includes(type.mime))
        throw fail(400, 'Only MP4 and WebM videos are supported.');
      await fs.mkdir(directory, { recursive: true });
      const filename = crypto.randomUUID() + '.' + type.ext;
      stored = path.join(directory, filename);
      await fs.copyFile(req.file.path, stored, require('node:fs').constants.COPYFILE_EXCL);
      const r = (
        await query(
          'INSERT INTO reels(title,caption,published,filename,mime,user_id) VALUES($1,$2,$3,$4,$5,$6) RETURNING id',
          [d.title, d.caption, d.published, filename, type.mime, req.user.id],
        )
      ).rows[0];
      stored = null;
      res.status(201).json(r);
    } finally {
      if (req.file) await fs.unlink(req.file.path).catch(() => {});
      if (stored) await fs.unlink(stored).catch(() => {});
    }
  }),
);
router.put(
  '/:id',
  admin,
  wrap(async (req, res) => {
    const d = fields.parse(req.body);
    const r = await query(
      'UPDATE reels SET title=$1,caption=$2,published=$3 WHERE id=$4 RETURNING id',
      [d.title, d.caption, d.published, req.params.id],
    );
    if (!r.rowCount) throw fail(404, 'Reel not found.');
    res.json(r.rows[0]);
  }),
);
router.delete(
  '/:id',
  admin,
  wrap(async (req, res) => {
    const r = (await query('DELETE FROM reels WHERE id=$1 RETURNING filename', [req.params.id]))
      .rows[0];
    if (!r) throw fail(404, 'Reel not found.');
    await fs.unlink(path.join(directory, path.basename(r.filename))).catch((e) => {
      if (e.code !== 'ENOENT') console.error('Reel file cleanup failed:', e.code);
    });
    res.json({ ok: true });
  }),
);
router.put(
  '/:id/like',
  wrap(async (req, res) => {
    if (!req.user) throw fail(401, 'Please sign in to like reels.');
    const { active } = z.object({ active: z.boolean() }).parse(req.body);
    const r = await query('SELECT id FROM reels WHERE id=$1 AND published', [req.params.id]);
    if (!r.rowCount) throw fail(404, 'Reel not found.');
    if (active)
      await query('INSERT INTO reel_likes(reel_id,user_id) VALUES($1,$2) ON CONFLICT DO NOTHING', [
        req.params.id,
        req.user.id,
      ]);
    else
      await query('DELETE FROM reel_likes WHERE reel_id=$1 AND user_id=$2', [
        req.params.id,
        req.user.id,
      ]);
    res.json({ ok: true });
  }),
);
module.exports = router;
