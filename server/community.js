const express = require('express');
const { z } = require('zod');
const { query } = require('./database');
const router = express.Router();
const wrap = (fn) => (req, res, next) => Promise.resolve(fn(req, res)).catch(next);
const fail = (status, message) => Object.assign(Error(message), { status });
const auth = (req, res, next) =>
  req.user ? next() : next(fail(401, 'Please sign in to join the conversation.'));
router.get(
  '/',
  wrap(async (req, res) => {
    const page = Math.max(1, Math.min(10000, parseInt(req.query.page) || 1));
    const mode = ['following', 'saved'].includes(req.query.mode) ? req.query.mode : 'all';
    const pin = String(req.query.pincode || '').slice(0, 6);
    const r = await query(
      `SELECT p.*,u.full_name,(SELECT count(*)::int FROM community_likes l WHERE l.post_id=p.id) AS likes,EXISTS(SELECT 1 FROM community_likes l WHERE l.user_id=$1 AND l.post_id=p.id) AS liked,EXISTS(SELECT 1 FROM community_saves s WHERE s.user_id=$1 AND s.post_id=p.id) AS saved,EXISTS(SELECT 1 FROM user_follows f WHERE f.follower_id=$1 AND f.following_id=p.user_id) AS followed FROM community_posts p JOIN users u ON u.id=p.user_id WHERE ($2='' OR p.pincode=$2) AND ($3<>'following' OR EXISTS(SELECT 1 FROM user_follows f WHERE f.follower_id=$1 AND f.following_id=p.user_id)) AND ($3<>'saved' OR EXISTS(SELECT 1 FROM community_saves s WHERE s.user_id=$1 AND s.post_id=p.id)) ORDER BY p.created_at DESC LIMIT 21 OFFSET ${(page - 1) * 20}`,
      [req.user?.id || null, pin, mode],
    );
    res.json({ posts: r.rows.slice(0, 20), hasMore: r.rows.length > 20 });
  }),
);
router.post(
  '/',
  auth,
  wrap(async (req, res) => {
    const d = z
      .object({
        body: z.string().trim().min(1).max(5000),
        pincode: z
          .string()
          .regex(/^(\d{6})?$/)
          .default(''),
        image: z
          .string()
          .max(2000)
          .refine((v) => !v || v.startsWith('https://') || v.startsWith('/media/'))
          .default(''),
      })
      .parse(req.body);
    res
      .status(201)
      .json(
        (
          await query(
            'INSERT INTO community_posts(user_id,body,pincode,image) VALUES($1,$2,$3,$4) RETURNING *',
            [req.user.id, d.body, d.pincode, d.image],
          )
        ).rows[0],
      );
  }),
);
router.delete(
  '/:id',
  auth,
  wrap(async (req, res) => {
    const r = await query(
      'DELETE FROM community_posts WHERE id=$1 AND (user_id=$2 OR $3) RETURNING id',
      [req.params.id, req.user.id, req.user.role === 'admin'],
    );
    if (!r.rowCount) throw fail(404, 'Post not found or not owned by you.');
    res.json({ ok: true });
  }),
);
for (const [action, table] of [
  ['like', 'community_likes'],
  ['save', 'community_saves'],
])
  router.put(
    `/:id/${action}`,
    auth,
    wrap(async (req, res) => {
      const { active } = z.object({ active: z.boolean() }).parse(req.body);
      if (active)
        await query(`INSERT INTO ${table}(user_id,post_id) VALUES($1,$2) ON CONFLICT DO NOTHING`, [
          req.user.id,
          req.params.id,
        ]);
      else
        await query(`DELETE FROM ${table} WHERE user_id=$1 AND post_id=$2`, [
          req.user.id,
          req.params.id,
        ]);
      res.json({ active });
    }),
  );
router.put(
  '/people/:id/follow',
  auth,
  wrap(async (req, res) => {
    const { active } = z.object({ active: z.boolean() }).parse(req.body);
    if (String(req.user.id) === req.params.id) throw fail(400, 'You cannot follow yourself.');
    if (active)
      await query(
        'INSERT INTO user_follows(follower_id,following_id) VALUES($1,$2) ON CONFLICT DO NOTHING',
        [req.user.id, req.params.id],
      );
    else
      await query('DELETE FROM user_follows WHERE follower_id=$1 AND following_id=$2', [
        req.user.id,
        req.params.id,
      ]);
    res.json({ active });
  }),
);
router.get(
  '/:id/comments',
  wrap(async (req, res) =>
    res.json(
      (
        await query(
          'SELECT c.*,u.full_name FROM community_comments c JOIN users u ON u.id=c.user_id WHERE c.post_id=$1 ORDER BY c.created_at',
          [req.params.id],
        )
      ).rows,
    ),
  ),
);
router.post(
  '/:id/comments',
  auth,
  wrap(async (req, res) => {
    const body = z.string().trim().min(1).max(3000).parse(req.body.body);
    res
      .status(201)
      .json(
        (
          await query(
            'INSERT INTO community_comments(post_id,user_id,body) VALUES($1,$2,$3) RETURNING *',
            [req.params.id, req.user.id, body],
          )
        ).rows[0],
      );
  }),
);
module.exports = router;
