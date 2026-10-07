const express = require('express');
const multer = require('multer');
const fs = require('node:fs/promises');
const os = require('node:os');
const path = require('node:path');
const crypto = require('node:crypto');
const { z } = require('zod');
const prisma = require('./prisma');
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

function reelToJSON(r) {
  const { user, _count, reel_likes: lk, ...scalar } = r;
  return {
    ...scalar,
    author_name: user?.full_name ?? null,
    video_url: `/api/reels/${r.id}/video`,
    likes: _count?.reel_likes ?? 0,
    liked: lk ? lk.length > 0 : false,
  };
}

router.get(
  '/',
  wrap(async (req, res) => {
    const page = Math.max(1, Math.min(10000, parseInt(req.query.page) || 1));
    const manage = req.query.manage === 'true';
    if (manage && req.user?.role !== 'admin') throw fail(403, 'Administrator access required.');
    const userId = req.user?.id ?? null;

    const where = {};
    if (!manage) where.published = true;
    if (req.query.id) {
      where.id = BigInt(z.string().regex(/^[0-9]+$/).parse(req.query.id));
    }

    const include = {
      user: { select: { full_name: true } },
      _count: { select: { reel_likes: true } },
    };
    if (userId) {
      include.reel_likes = { where: { user_id: userId }, take: 1, select: { user_id: true } };
    }

    const rows = await prisma.reel.findMany({
      where,
      include,
      orderBy: [{ created_at: 'desc' }, { id: 'desc' }],
      take: 13,
      skip: (page - 1) * 12,
    });

    res.json({ reels: rows.slice(0, 12).map(reelToJSON), hasMore: rows.length > 12, page });
  }),
);

router.get(
  '/:id/video',
  wrap(async (req, res) => {
    const r = await prisma.reel.findUnique({
      where: { id: BigInt(req.params.id) },
      select: { filename: true, mime: true, published: true },
    });
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

      const r = await prisma.reel.create({
        data: {
          title: d.title,
          caption: d.caption,
          published: d.published,
          filename,
          mime: type.mime,
          user_id: req.user.id,
        },
        select: { id: true },
      });

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
    try {
      const r = await prisma.reel.update({
        where: { id: BigInt(req.params.id) },
        data: { title: d.title, caption: d.caption, published: d.published },
        select: { id: true },
      });
      res.json(r);
    } catch (e) {
      if (e.code === 'P2025') throw fail(404, 'Reel not found.');
      throw e;
    }
  }),
);

router.delete(
  '/:id',
  admin,
  wrap(async (req, res) => {
    let r;
    try {
      r = await prisma.reel.delete({
        where: { id: BigInt(req.params.id) },
        select: { filename: true },
      });
    } catch (e) {
      if (e.code === 'P2025') throw fail(404, 'Reel not found.');
      throw e;
    }
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
    const reelId = BigInt(req.params.id);

    const reel = await prisma.reel.findFirst({ where: { id: reelId, published: true }, select: { id: true } });
    if (!reel) throw fail(404, 'Reel not found.');

    const key = { reel_id: reelId, user_id: req.user.id };
    if (active) {
      await prisma.reelLike.upsert({ where: { reel_id_user_id: key }, create: key, update: {} });
    } else {
      await prisma.reelLike.deleteMany({ where: key });
    }
    res.json({ ok: true });
  }),
);

module.exports = router;
