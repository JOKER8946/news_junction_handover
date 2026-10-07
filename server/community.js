const express = require('express');
const { z } = require('zod');
const prisma = require('./prisma');
const router = express.Router();
const wrap = (fn) => (req, res, next) => Promise.resolve(fn(req, res)).catch(next);
const fail = (status, message) => Object.assign(Error(message), { status });
const auth = (req, res, next) =>
  req.user ? next() : next(fail(401, 'Please sign in to join the conversation.'));

function postToJSON(p) {
  const { user, _count, likes: lk, saves: sv, ...scalar } = p;
  return {
    ...scalar,
    full_name: user?.full_name,
    likes: _count?.likes ?? 0,
    liked: lk ? lk.length > 0 : false,
    saved: sv ? sv.length > 0 : false,
    followed: user?.followers ? user.followers.length > 0 : false,
  };
}

router.get(
  '/',
  wrap(async (req, res) => {
    const page = Math.max(1, Math.min(10000, parseInt(req.query.page) || 1));
    const mode = ['following', 'saved'].includes(req.query.mode) ? req.query.mode : 'all';
    const pin = String(req.query.pincode || '').slice(0, 6);
    const userId = req.user?.id ?? null;

    const where = {
      AND: [
        ...(pin ? [{ pincode: pin }] : []),
        ...(mode === 'following'
          ? [{ user: { followers: { some: { follower_id: userId } } } }]
          : []),
        ...(mode === 'saved' ? [{ saves: { some: { user_id: userId } } }] : []),
      ],
    };

    const include = {
      user: {
        select: {
          full_name: true,
          ...(userId
            ? { followers: { where: { follower_id: userId }, take: 1, select: { follower_id: true } } }
            : {}),
        },
      },
      _count: { select: { likes: true } },
    };
    if (userId) {
      include.likes = { where: { user_id: userId }, take: 1, select: { user_id: true } };
      include.saves = { where: { user_id: userId }, take: 1, select: { user_id: true } };
    }

    const posts = await prisma.communityPost.findMany({
      where,
      include,
      orderBy: { created_at: 'desc' },
      take: 21,
      skip: (page - 1) * 20,
    });

    res.json({ posts: posts.slice(0, 20).map(postToJSON), hasMore: posts.length > 20 });
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

    const post = await prisma.communityPost.create({
      data: { user_id: req.user.id, body: d.body, pincode: d.pincode, image: d.image },
    });
    res.status(201).json(post);
  }),
);

router.delete(
  '/:id',
  auth,
  wrap(async (req, res) => {
    const postId = BigInt(req.params.id);
    const where =
      req.user.role === 'admin'
        ? { id: postId }
        : { id: postId, user_id: req.user.id };

    const deleted = await prisma.communityPost.deleteMany({ where });
    if (!deleted.count) throw fail(404, 'Post not found or not owned by you.');
    res.json({ ok: true });
  }),
);

for (const [action, model] of [
  ['like', 'communityLike'],
  ['save', 'communitySave'],
])
  router.put(
    `/:id/${action}`,
    auth,
    wrap(async (req, res) => {
      const { active } = z.object({ active: z.boolean() }).parse(req.body);
      const postId = BigInt(req.params.id);
      const key = { user_id: req.user.id, post_id: postId };

      if (active) {
        await prisma[model].upsert({ where: { user_id_post_id: key }, create: key, update: {} });
      } else {
        await prisma[model].deleteMany({ where: key });
      }
      res.json({ active });
    }),
  );

router.put(
  '/people/:id/follow',
  auth,
  wrap(async (req, res) => {
    const { active } = z.object({ active: z.boolean() }).parse(req.body);
    const followingId = BigInt(req.params.id);
    if (req.user.id === followingId) throw fail(400, 'You cannot follow yourself.');
    const key = { follower_id: req.user.id, following_id: followingId };

    if (active) {
      await prisma.userFollow.upsert({
        where: { follower_id_following_id: key },
        create: key,
        update: {},
      });
    } else {
      await prisma.userFollow.deleteMany({ where: key });
    }
    res.json({ active });
  }),
);

router.get(
  '/:id/comments',
  wrap(async (req, res) => {
    const comments = await prisma.communityComment.findMany({
      where: { post_id: BigInt(req.params.id) },
      include: { user: { select: { full_name: true } } },
      orderBy: { created_at: 'asc' },
    });
    res.json(
      comments.map((c) => {
        const { user, ...rest } = c;
        return { ...rest, full_name: user?.full_name };
      }),
    );
  }),
);

router.post(
  '/:id/comments',
  auth,
  wrap(async (req, res) => {
    const body = z.string().trim().min(1).max(3000).parse(req.body.body);
    const comment = await prisma.communityComment.create({
      data: { post_id: BigInt(req.params.id), user_id: req.user.id, body },
    });
    res.status(201).json(comment);
  }),
);

module.exports = router;
