const express = require('express');
const session = require('express-session');
const PgStore = require('connect-pg-simple')(session);
const helmet = require('helmet');
const { rateLimit } = require('express-rate-limit');
const bcrypt = require('bcryptjs');
const crypto = require('node:crypto');
const path = require('node:path');
const sanitize = require('sanitize-html');
const { z } = require('zod');
const prisma = require('./prisma');
const { pool, query } = require('./database');
const app = express();
const { configuration, limiter, safeUrl } = require('./security');
const { production, origin: appOrigin, secure: secureCookie } = configuration();
app.set('json replacer', (key, value) =>
  typeof value === 'string' ? require('./security').redactSecrets(value) : value,
);
app.disable('x-powered-by');
if (process.env.TRUST_PROXY === '1') app.set('trust proxy', 1);
app.use((req, res, next) => {
  if (production && !req.secure) {
    if (!['GET', 'HEAD'].includes(req.method))
      return res.status(426).json({ error: 'HTTPS is required.' });
    return res.redirect(308, appOrigin + req.originalUrl);
  }
  res.set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
  next();
});
app.use(
  helmet({
    strictTransportSecurity: production ? { maxAge: 31536000, includeSubDomains: true } : false,
    contentSecurityPolicy: {
      directives: {
        'img-src': ["'self'", 'https:', 'data:'],
        'media-src': ["'self'", 'blob:'],
        'script-src': ["'self'"],
        'frame-ancestors': ["'none'"],
        'object-src': ["'none'"],
        'base-uri': ["'none'"],
        'form-action': ["'self'"],
        'style-src': ["'self'", "'unsafe-inline'"],
        'upgrade-insecure-requests': production ? [] : null,
      },
    },
  }),
);
app.use('/api', limiter('api', 600, 60000));
app.use(express.json({ limit: '1mb' }));
app.use(
  session({
    store: new PgStore({ pool }),
    secret: process.env.SESSION_SECRET || crypto.randomBytes(48).toString('hex'),
    resave: false,
    saveUninitialized: false,
    name: 'nj.sid',
    cookie: {
      httpOnly: true,
      sameSite: 'lax',
      secure: secureCookie,
      maxAge: 7 * 86400000,
    },
  }),
);
const wrap = (fn) => (req, res, next) => Promise.resolve(fn(req, res, next)).catch(next);
const fail = (status, message) => Object.assign(new Error(message), { status });
const publicUser = (u) =>
  u && {
    id: u.id,
    email: u.email,
    full_name: u.full_name,
    role: u.role,
    bio: u.bio,
    district: u.district,
    phone: u.phone,
  };
app.use(
  '/api',
  wrap(async (req, res, next) => {
    res.set('Cache-Control', 'private, no-store');
    if (req.session.userId)
      req.user = await prisma.user.findUnique({ where: { id: BigInt(req.session.userId) } });
    if (!['GET', 'HEAD', 'OPTIONS'].includes(req.method)) {
      const origin = req.get('origin');
      const allowed = appOrigin;
      if (
        origin &&
        origin !== allowed &&
        !(
          !production &&
          ['http://localhost:5173', 'http://127.0.0.1:5173', 'http://127.0.0.1:3000'].includes(
            origin,
          )
        )
      )
        throw fail(403, 'Untrusted request origin');
      if (!req.session.csrf || req.get('x-csrf-token') !== req.session.csrf)
        throw fail(403, 'Refresh the page and try again.');
    }
    next();
  }),
);
const auth = (req, res, next) =>
  req.user ? next() : next(fail(401, 'Please sign in to continue.'));
const admin = (req, res, next) =>
  req.user?.role === 'admin' ? next() : next(fail(403, 'Administrator access required.'));
const editor = (req, res, next) =>
  ['admin', 'reporter'].includes(req.user?.role)
    ? next()
    : next(fail(403, 'Reporter access required.'));
const limited = limiter('sensitive', 30, 15 * 60000);
app.use('/api', (req, res, next) =>
  ['GET', 'HEAD', 'OPTIONS'].includes(req.method) ? next() : writeLimit(req, res, next),
);
const writeLimit = limiter('writes', 120, 15 * 60000);
app.use('/api', (req, res, next) => {
  const segments = req.path.split('/');
  for (let i = 0; i < segments.length; i++) {
    if (
      ['articles', 'channels', 'complaints', 'lead-pages', 'reels', 'people'].includes(
        segments[i],
      ) &&
      segments[i + 1] &&
      !/^[1-9][0-9]{0,17}$/.test(segments[i + 1])
    )
      return res.status(400).json({ error: 'Invalid record identifier.' });
  }
  next();
});
const text = (max = 500) => z.string().trim().min(1).max(max);
const email = z
  .email()
  .max(254)
  .transform((v) => v.toLowerCase());
const validUrl = z.string().max(2000).refine(safeUrl, 'Use an HTTPS URL or a local media URL.');
const articleSchema = z.object({
  title: text(220),
  description: z.string().max(600).default(''),
  content: text(100000),
  image: validUrl.default(''),
  category_id: z.coerce.number().int().positive(),
  district: z.string().max(100).default(''),
  status: z.enum(['draft', 'pending', 'rejected', 'published', 'scheduled']).default('published'),
  published_at: z.iso.datetime().optional(),
});

// ── Visibility condition for Prisma queries ───────────────────────────
const visibleWhere = () => ({
  OR: [
    { status: 'published' },
    { AND: [{ status: 'scheduled' }, { published_at: { lte: new Date() } }] },
  ],
});

// ── Response mapper: flatten Prisma includes to the shape the API returns ──
function articleToJSON(a) {
  const { category, _count, bookmarks: bm, likes: lk, comments: cm, author, ...scalar } = a;
  const json = {
    ...scalar,
    source_url: safeUrl(scalar.source_url) ? scalar.source_url : '',
    category_name: category?.name ?? null,
    likes: _count?.likes ?? 0,
    bookmarked: bm ? bm.length > 0 : false,
    liked: lk ? lk.length > 0 : false,
  };
  if (cm) {
    json.comments = cm.map((c) => {
      const { user, ...rest } = c;
      return { ...rest, full_name: user?.full_name };
    });
  }
  return json;
}

// ── Build article include object for findMany / findFirst ─────────────
function articleInclude(userId) {
  const inc = {
    category: { select: { name: true } },
    _count: { select: { likes: true } },
  };
  if (userId) {
    inc.bookmarks = { where: { user_id: userId }, take: 1, select: { user_id: true } };
    inc.likes = { where: { user_id: userId }, take: 1, select: { user_id: true } };
  }
  return inc;
}

// ────────────────────────────────────────────────────────────────────────
app.get(
  '/api/health',
  wrap(async (req, res) => {
    await prisma.$queryRaw`SELECT 1`;
    res.json({ status: 'ok', database: 'postgresql' });
  }),
);
app.get(
  '/api/auth/session',
  wrap(async (req, res) => {
    req.session.csrf ||= crypto.randomBytes(24).toString('hex');
    res.json({ user: publicUser(req.user) || null, csrf: req.session.csrf });
  }),
);
async function signIn(req, user) {
  await new Promise((resolve, reject) =>
    req.session.regenerate((e) => (e ? reject(e) : resolve())),
  );
  req.session.userId = Number(user.id);
  req.session.csrf = crypto.randomBytes(24).toString('hex');
  await new Promise((resolve, reject) => req.session.save((e) => (e ? reject(e) : resolve())));
}
app.post(
  '/api/auth/register',
  limited,
  wrap(async (req, res) => {
    const d = z
      .object({
        full_name: text(100),
        email,
        password: z
          .string()
          .min(10)
          .max(72)
          .refine(
            (v) => Buffer.byteLength(v, 'utf8') <= 72,
            'Password must be at most 72 UTF-8 bytes',
          ),
      })
      .parse(req.body);
    const hash = await bcrypt.hash(d.password, 12);
    const user = await prisma.user.create({
      data: { full_name: d.full_name, email: d.email, password: hash },
    });
    await signIn(req, user);
    res.status(201).json({ user: publicUser(user), csrf: req.session.csrf });
  }),
);
app.post(
  '/api/auth/login',
  limited,
  limiter('login-account', 20, 15 * 60000, {
    skipSuccessfulRequests: true,
    keyGenerator: (req) =>
      crypto
        .createHash('sha256')
        .update(
          String(req.body?.email || '')
            .toLowerCase()
            .trim()
            .slice(0, 254),
        )
        .digest('hex'),
  }),
  wrap(async (req, res) => {
    const d = z.object({ email, password: z.string().min(1).max(128) }).parse(req.body);
    const user = await prisma.user.findFirst({
      where: { email: { equals: d.email, mode: 'insensitive' } },
    });
    const valid = await bcrypt.compare(
      d.password,
      user?.password?.replace(/^\$2y\$/, '$2b$') ||
        '$2b$12$abcdefghijklmnopqrstuuU7gVvOa9kuxQeY4fdZIXBKxhZaQlrNG',
    );
    if (!user || !valid) throw fail(401, 'Email or password is incorrect.');
    await signIn(req, user);
    res.json({ user: publicUser(user), csrf: req.session.csrf });
  }),
);
app.post(
  '/api/auth/logout',
  wrap(async (req, res) => {
    await new Promise((resolve, reject) => req.session.destroy((e) => (e ? reject(e) : resolve())));
    res.clearCookie('nj.sid', { httpOnly: true, sameSite: 'lax', secure: secureCookie });
    res.json({ ok: true });
  }),
);
app.put(
  '/api/profile',
  auth,
  wrap(async (req, res) => {
    const d = z
      .object({
        full_name: text(100),
        bio: z.string().max(1000),
        district: z.string().max(100),
        phone: z.string().max(30),
      })
      .parse(req.body);
    const u = await prisma.user.update({
      where: { id: req.user.id },
      data: { full_name: d.full_name, bio: d.bio, district: d.district, phone: d.phone },
    });
    res.json(publicUser(u));
  }),
);
app.put(
  '/api/profile/password',
  auth,
  limited,
  wrap(async (req, res) => {
    const d = z
      .object({
        current: z.string().min(1).max(128),
        password: z
          .string()
          .min(10)
          .max(72)
          .refine(
            (v) => Buffer.byteLength(v, 'utf8') <= 72,
            'Password must be at most 72 UTF-8 bytes',
          ),
      })
      .parse(req.body);
    if (!(await bcrypt.compare(d.current, req.user.password.replace(/^\$2y\$/, '$2b$'))))
      throw fail(400, 'Current password is incorrect.');
    await prisma.user.update({
      where: { id: req.user.id },
      data: { password: await bcrypt.hash(d.password, 12) },
    });
    // Session table is managed by connect-pg-simple — use raw pool query
    await query("DELETE FROM session WHERE sess->>'userId'=$1 AND sid<>$2", [
      String(req.user.id),
      req.sessionID,
    ]);
    res.json({ ok: true });
  }),
);
app.get(
  '/api/categories',
  wrap(async (req, res) => res.json(await prisma.category.findMany({ orderBy: { id: 'asc' } }))),
);
app.get(
  '/api/articles',
  wrap(async (req, res) => {
    const page = Math.max(1, Math.min(10000, parseInt(req.query.page) || 1)),
      limit = 12;
    const userId = req.user?.id ?? null;

    // Build dynamic where conditions
    const conditions = [];

    if (req.query.mine === 'true') {
      if (!req.user) throw fail(401, 'Please sign in.');
      conditions.push({ user_id: userId });
    } else {
      conditions.push(visibleWhere());
    }

    if (req.query.q) {
      const pattern = String(req.query.q).slice(0, 100);
      conditions.push({
        OR: [
          { title: { contains: pattern, mode: 'insensitive' } },
          { description: { contains: pattern, mode: 'insensitive' } },
        ],
      });
    }
    if (req.query.district) conditions.push({ district: String(req.query.district) });
    if (req.query.category) conditions.push({ category: { name: String(req.query.category) } });

    if (req.query.saved === 'true') {
      if (!req.user) throw fail(401, 'Please sign in to see saved stories.');
      conditions.push({ bookmarks: { some: { user_id: userId } } });
    }

    const result = await prisma.article.findMany({
      where: { AND: conditions },
      include: articleInclude(userId),
      orderBy: [{ published_at: 'desc' }, { id: 'desc' }],
      take: 13,
      skip: (page - 1) * limit,
    });

    res.json({
      articles: result.slice(0, limit).map(articleToJSON),
      hasMore: result.length > limit,
      page,
    });
  }),
);
app.post(
  '/api/articles',
  auth,
  wrap(async (req, res) => {
    const d = articleSchema.parse(req.body);
    const isAdmin = req.user.role === 'admin';
    const status = isAdmin ? d.status : 'pending';
    if (status === 'scheduled' && (!d.published_at || new Date(d.published_at) <= new Date()))
      throw fail(400, 'Choose a future publication time.');
    const article = await prisma.article.create({
      data: {
        title: d.title,
        description: d.description,
        content: sanitize(d.content),
        image: d.image,
        category_id: d.category_id,
        district: d.district,
        status,
        published_at: d.published_at ? new Date(d.published_at) : new Date(),
        user_id: req.user.id,
        author_name: req.user.full_name,
      },
    });
    res.status(201).json(article);
  }),
);
app.get(
  '/api/articles/:id',
  wrap(async (req, res) => {
    const userId = req.user?.id ?? null;
    const isAdmin = req.user?.role === 'admin';
    const articleId = BigInt(req.params.id);

    const whereConditions = [{ id: articleId }];
    if (!isAdmin) {
      whereConditions.push({
        OR: [
          { status: 'published' },
          { AND: [{ status: 'scheduled' }, { published_at: { lte: new Date() } }] },
          ...(userId ? [{ user_id: userId }] : []),
        ],
      });
    }

    const inc = articleInclude(userId);
    inc.comments = {
      select: {
        id: true,
        body: true,
        created_at: true,
        user: { select: { full_name: true } },
      },
      orderBy: { created_at: 'desc' },
    };

    const a = await prisma.article.findFirst({ where: { AND: whereConditions }, include: inc });
    if (!a) throw fail(404, 'Story not found.');

    await prisma.article.update({
      where: { id: a.id },
      data: { views: { increment: 1 } },
    });

    const json = articleToJSON(a);
    json.content = sanitize(json.content);
    res.json(json);
  }),
);
async function ownedArticle(req) {
  const a = await prisma.article.findUnique({ where: { id: BigInt(req.params.id) } });
  if (!a) throw fail(404, 'Story not found.');
  if (String(a.user_id) !== String(req.user.id) && req.user.role !== 'admin')
    throw fail(403, 'You can only change your own stories.');
  return a;
}
app.put(
  '/api/articles/:id',
  auth,
  wrap(async (req, res) => {
    await ownedArticle(req);
    const d = articleSchema.parse(req.body);
    const status = req.user.role === 'admin' ? d.status : 'pending';
    if (status === 'scheduled' && (!d.published_at || new Date(d.published_at) <= new Date()))
      throw fail(400, 'Choose a future publication time.');
    const updated = await prisma.article.update({
      where: { id: BigInt(req.params.id) },
      data: {
        title: d.title,
        description: d.description,
        content: sanitize(d.content),
        image: d.image,
        category_id: d.category_id,
        district: d.district,
        status,
        ...(d.published_at ? { published_at: new Date(d.published_at) } : {}),
      },
    });
    res.json(updated);
  }),
);
app.delete(
  '/api/articles/:id',
  auth,
  editor,
  wrap(async (req, res) => {
    await ownedArticle(req);
    await prisma.article.delete({ where: { id: BigInt(req.params.id) } });
    res.json({ ok: true });
  }),
);
for (const [action, model] of [
  ['bookmark', 'bookmark'],
  ['like', 'like'],
])
  app.put(
    `/api/articles/:id/${action}`,
    auth,
    wrap(async (req, res) => {
      const { active } = z.object({ active: z.boolean() }).parse(req.body);
      const articleId = BigInt(req.params.id);
      const a = await prisma.article.findFirst({
        where: { id: articleId, ...visibleWhere() },
        select: { id: true },
      });
      if (!a) throw fail(404, 'Story not found.');

      const key = { user_id: req.user.id, article_id: a.id };
      if (active) {
        await prisma[model].upsert({
          where: { user_id_article_id: key },
          create: key,
          update: {},
        });
      } else {
        await prisma[model].deleteMany({ where: key });
      }
      res.json({ active });
    }),
  );
app.post(
  '/api/articles/:id/comments',
  auth,
  wrap(async (req, res) => {
    const body = text(3000).parse(req.body.body);
    const articleId = BigInt(req.params.id);
    const a = await prisma.article.findFirst({
      where: { id: articleId, ...visibleWhere() },
      select: { id: true },
    });
    if (!a) throw fail(404, 'Story not found.');
    const comment = await prisma.comment.create({
      data: { user_id: req.user.id, article_id: a.id, body },
    });
    res.status(201).json(comment);
  }),
);
app.get(
  '/api/channels',
  wrap(async (req, res) => {
    const userId = req.user?.id ?? null;
    const channels = await prisma.channel.findMany({
      include: {
        _count: { select: { follows: true } },
        ...(userId
          ? { follows: { where: { user_id: userId }, take: 1, select: { user_id: true } } }
          : {}),
      },
      orderBy: { id: 'asc' },
    });
    res.json(
      channels.map((c) => {
        const { _count, follows: fl, ...scalar } = c;
        return {
          ...scalar,
          followers: _count.follows,
          followed: fl ? fl.length > 0 : false,
        };
      }),
    );
  }),
);
app.post(
  '/api/channels',
  auth,
  editor,
  wrap(async (req, res) => {
    const d = z.object({ name: text(120), bio: z.string().max(1000).default('') }).parse(req.body);
    const channel = await prisma.channel.create({
      data: { name: d.name, bio: d.bio, user_id: req.user.id },
    });
    res.status(201).json(channel);
  }),
);
app.put(
  '/api/channels/:id/follow',
  auth,
  wrap(async (req, res) => {
    const { active } = z.object({ active: z.boolean() }).parse(req.body);
    const channelId = BigInt(req.params.id);
    const key = { user_id: req.user.id, channel_id: channelId };
    if (active) {
      await prisma.follow.upsert({
        where: { user_id_channel_id: key },
        create: key,
        update: {},
      });
    } else {
      await prisma.follow.deleteMany({ where: key });
    }
    res.json({ active });
  }),
);
app.get(
  '/api/channels/:id',
  wrap(async (req, res) => {
    const c = await prisma.channel.findUnique({ where: { id: BigInt(req.params.id) } });
    if (!c) throw fail(404, 'Channel not found.');
    const posts = await prisma.channelPost.findMany({
      where: { channel_id: c.id },
      include: { user: { select: { full_name: true } } },
      orderBy: { created_at: 'desc' },
    });
    res.json({
      ...c,
      posts: posts.map((p) => {
        const { user, ...rest } = p;
        return { ...rest, full_name: user?.full_name };
      }),
    });
  }),
);
app.post(
  '/api/channels/:id/posts',
  auth,
  editor,
  wrap(async (req, res) => {
    const c = await prisma.channel.findUnique({ where: { id: BigInt(req.params.id) } });
    if (!c) throw fail(404, 'Channel not found.');
    if (String(c.user_id) !== String(req.user.id) && req.user.role !== 'admin')
      throw fail(403, 'Only the channel owner can post.');
    const body = text(5000).parse(req.body.body);
    const post = await prisma.channelPost.create({
      data: { channel_id: c.id, user_id: req.user.id, body },
    });
    res.status(201).json(post);
  }),
);
app.get(
  '/api/complaints',
  auth,
  wrap(async (req, res) => {
    const where = req.user.role === 'admin' ? {} : { user_id: req.user.id };
    res.json(await prisma.complaint.findMany({ where, orderBy: { created_at: 'desc' } }));
  }),
);
app.post(
  '/api/complaints',
  auth,
  wrap(async (req, res) => {
    const d = z
      .object({
        title: text(200),
        description: text(5000),
        location: text(200),
        pincode: z.string().regex(/^\d{6}$/),
      })
      .parse(req.body);
    const complaint = await prisma.complaint.create({
      data: {
        user_id: req.user.id,
        title: d.title,
        description: d.description,
        location: d.location,
        pincode: d.pincode,
      },
    });
    res.status(201).json(complaint);
  }),
);
app.put(
  '/api/complaints/:id',
  auth,
  admin,
  wrap(async (req, res) => {
    const d = z
      .object({
        status: z.enum(['Submitted', 'In Review', 'Resolved']),
        response: z.string().max(5000),
      })
      .parse(req.body);
    try {
      const complaint = await prisma.complaint.update({
        where: { id: BigInt(req.params.id) },
        data: { status: d.status, response: d.response },
      });
      res.json(complaint);
    } catch (e) {
      if (e.code === 'P2025') throw fail(404, 'Report not found.');
      throw e;
    }
  }),
);
app.get(
  '/api/resources/:kind',
  wrap(async (req, res) => {
    const kind = z
      .enum(['newspapers', 'magazines', 'influencers', 'ads', 'templates'])
      .parse(req.params.kind);
    res.json(await prisma.resource.findMany({ where: { kind }, orderBy: { created_at: 'desc' } }));
  }),
);
app.post(
  '/api/resources/:kind',
  auth,
  editor,
  wrap(async (req, res) => {
    const kind = z.enum(['newspapers', 'magazines', 'ads', 'templates']).parse(req.params.kind);
    if (kind === 'ads' && req.user.role !== 'admin')
      throw fail(403, 'Administrator access required.');
    const d = z
      .object({
        title: text(200),
        data: z.object({
          description: z.string().max(2000).default(''),
          url: validUrl.default(''),
          image: validUrl.default(''),
          district: z.string().max(100).default(''),
          cta: z.string().max(100).default('Subscribe'),
        }),
      })
      .parse(req.body);
    const resource = await prisma.resource.create({
      data: { kind, title: d.title, data: d.data, user_id: req.user.id },
    });
    res.status(201).json(resource);
  }),
);
app.delete(
  '/api/resources/:id',
  auth,
  editor,
  wrap(async (req, res) => {
    const resourceId = BigInt(req.params.id);
    const where =
      req.user.role === 'admin' ? { id: resourceId } : { id: resourceId, user_id: req.user.id };
    const deleted = await prisma.resource.deleteMany({ where });
    if (!deleted.count) throw fail(404, 'Resource not found or not owned by you.');
    res.json({ ok: true });
  }),
);
app.post(
  '/api/subscribe',
  limited,
  wrap(async (req, res) => {
    const e = email.parse(req.body.email);
    await prisma.subscriber.upsert({
      where: { email: e },
      create: { email: e },
      update: {},
    });
    res.json({ ok: true, message: 'You are on the list. Thank you for joining us.' });
  }),
);
app.get(
  '/api/lead-pages/:id',
  wrap(async (req, res) => {
    const r = await prisma.resource.findFirst({
      where: { id: BigInt(req.params.id), kind: 'templates' },
    });
    if (!r) throw fail(404, 'Page not found.');
    res.json(r);
  }),
);
app.post(
  '/api/lead-pages/:id/leads',
  limited,
  wrap(async (req, res) => {
    const d = z.object({ name: text(100), email }).parse(req.body);
    const r = await prisma.resource.findFirst({
      where: { id: BigInt(req.params.id), kind: 'templates' },
      select: { id: true },
    });
    if (!r) throw fail(404, 'Page not found.');
    await prisma.lead.create({
      data: { resource_id: r.id, name: d.name, email: d.email },
    });
    res.status(201).json({ ok: true });
  }),
);
app.get(
  '/api/analytics',
  auth,
  editor,
  wrap(async (req, res) => {
    const isAdmin = req.user.role === 'admin';
    const articleWhere = isAdmin ? {} : { user_id: req.user.id };

    const [totalCount, draftCount, viewsAgg] = await Promise.all([
      prisma.article.count({ where: articleWhere }),
      prisma.article.count({ where: { ...articleWhere, status: 'draft' } }),
      prisma.article.aggregate({ where: articleWhere, _sum: { views: true } }),
    ]);

    const stories = await prisma.article.findMany({
      where: articleWhere,
      select: { id: true, title: true, views: true, status: true },
      orderBy: { views: 'desc' },
      take: 20,
    });

    const resourceWhere = isAdmin ? {} : { user_id: req.user.id };
    const leads = await prisma.lead.findMany({
      where: { resource: resourceWhere },
      include: { resource: { select: { title: true } } },
      orderBy: { created_at: 'desc' },
    });

    res.json({
      stats: {
        stories: totalCount,
        views: viewsAgg._sum.views ?? 0,
        drafts: draftCount,
      },
      stories,
      leads: leads.map((l) => {
        const { resource, ...rest } = l;
        return { ...rest, title: resource?.title };
      }),
    });
  }),
);
app.get(
  '/api/admin/users',
  auth,
  admin,
  wrap(async (req, res) =>
    res.json(
      await prisma.user.findMany({
        select: { id: true, full_name: true, email: true, role: true, created_at: true },
        orderBy: { id: 'asc' },
        take: 500,
      }),
    ),
  ),
);
app.get(
  '/api/admin/articles',
  auth,
  admin,
  wrap(async (req, res) => {
    const status = z
      .enum(['pending', 'rejected', 'published'])
      .default('pending')
      .parse(req.query.status);
    const articles = await prisma.article.findMany({
      where: { status },
      include: {
        ...articleInclude(req.user.id),
        author: { select: { full_name: true, email: true } },
      },
      orderBy: [{ created_at: 'asc' }, { id: 'asc' }],
      take: 100,
    });
    res.json(
      articles.map((article) => ({
        ...articleToJSON(article),
        author_email: article.author?.email ?? null,
      })),
    );
  }),
);
app.put(
  '/api/admin/articles/:id/review',
  auth,
  admin,
  wrap(async (req, res) => {
    const { decision } = z.object({ decision: z.enum(['approve', 'reject']) }).parse(req.body);
    const id = BigInt(req.params.id);
    const reviewed = await prisma.article.updateMany({
      where: { id, status: 'pending' },
      data: {
        status: decision === 'approve' ? 'published' : 'rejected',
        ...(decision === 'approve' ? { published_at: new Date() } : {}),
      },
    });
    if (!reviewed.count) {
      const article = await prisma.article.findUnique({ where: { id }, select: { id: true } });
      if (!article) throw fail(404, 'Story not found.');
      throw fail(409, 'This submission has already been reviewed.');
    }
    const updated = await prisma.article.findUnique({ where: { id } });
    res.json(updated);
  }),
);
app.put(
  '/api/admin/users/:id/role',
  auth,
  admin,
  wrap(async (req, res) => {
    const role = z.enum(['reader', 'reporter', 'admin']).parse(req.body.role);
    if (String(req.user.id) === req.params.id) throw fail(400, 'You cannot change your own role.');
    try {
      const u = await prisma.user.update({
        where: { id: BigInt(req.params.id) },
        data: { role },
        select: { id: true, role: true },
      });
      await query("DELETE FROM session WHERE sess->>'userId'=$1", [req.params.id]);
      res.json(u);
    } catch (e) {
      if (e.code === 'P2025') throw fail(404, 'User not found.');
      throw e;
    }
  }),
);
app.use('/api/community', require('./community'));
app.use('/api/reels', require('./reels'));
const multer = require('multer');
const receiveImage = multer({
  storage: multer.memoryStorage(),
  limits: { fileSize: 8 * 1024 * 1024, files: 1, fields: 0, parts: 1 },
}).single('image');
app.post(
  '/api/uploads',
  auth,
  limited,
  require('./upload-budget').reserveUpload(8 * 1024 * 1024),
  receiveImage,
  wrap(async (req, res) => {
    if (!req.file) throw fail(400, 'Choose an image to upload.');
    const { fileTypeFromBuffer } = await import('file-type');
    const type = await fileTypeFromBuffer(req.file.buffer);
    if (!type || !['image/jpeg', 'image/png', 'image/webp'].includes(type.mime))
      throw fail(400, 'Only JPEG, PNG, and WebP images are supported.');
    const fs = require('node:fs/promises'),
      directory = path.join(__dirname, '../public/media/uploads');
    await fs.mkdir(directory, { recursive: true });
    let clean;
    try {
      clean = await require('sharp')(req.file.buffer, {
        limitInputPixels: 25000000,
        failOn: 'warning',
      })
        .rotate()
        .toFormat(type.ext === 'jpg' ? 'jpeg' : type.ext)
        .toBuffer();
    } catch {
      throw fail(400, 'The image is invalid or exceeds 25 million pixels.');
    }
    if (clean.length > 8 * 1024 * 1024) throw fail(400, 'The decoded image exceeds 8 MB.');
    const name = crypto.randomUUID() + '.' + type.ext;
    await fs.writeFile(path.join(directory, name), clean, { flag: 'wx' });
    req.file.size = clean.length;
    res.status(201).json({ url: '/media/uploads/' + name });
  }),
);
app.use('/api', (req, res) => res.status(404).json({ error: 'Endpoint not found.' }));
app.use(
  '/media',
  express.static(path.join(__dirname, '../public/media'), { dotfiles: 'deny', index: false }),
  (req, res) => res.status(404).end(),
);
app.use(
  '/legacy-media',
  (req, res, next) => {
    if (!/\.(jpe?g|png|gif|webp|avif|ico|mp4|webm|mp3|wav|ogg|pdf|woff2?|ttf)$/i.test(req.path))
      return res.status(404).end();
    next();
  },
  express.static(process.env.LEGACY_MEDIA_ROOT || path.join(__dirname, '../app'), {
    dotfiles: 'deny',
    index: false,
    fallthrough: false,
  }),
);
app.use((req, res, next) =>
  /^\/(?:\.|database(?:\/|$)|server(?:\/|$)|prisma(?:\/|$))/.test(req.path)
    ? res.status(404).end()
    : next(),
);
app.use(express.static(path.join(__dirname, '../dist'), { index: false }));
const aliases = {
  stream: '/community',
  dashboard: '/',
  settings: '/profile',
  account: '/profile',
  my_complaints: '/complaints',
  complaint_form: '/complaints',
  featured_channels: '/channels',
  influencer: '/influencers',
  reader: '/reader',
  index: '/',
  'sign-in': '/sign-in',
  'district-newspapers': '/newspapers',
  create: '/create',
  'my-articles': '/my-articles',
  collections: '/saved',
  magazine: '/magazines',
  sharemarket: '/markets',
  'lead-builder': '/lead-builder',
  article: '/article',
  channel: '/channels',
};
app.get(/\.(php|html)$/, (req, res) => {
  const key = path.basename(req.path).replace(/\.(php|html)$/, '');
  let target = aliases[key] || '/' + key;
  if (['article', 'channel', 'view'].includes(key) && req.query.id)
    target = (key === 'channel' ? '/channels/' : '/article/') + encodeURIComponent(req.query.id);
  res.redirect(301, target);
});
app.get(['/admin', '/admin/*'], (req, res, next) => {
  if (!req.session.userId) return res.redirect('/sign-in');
  prisma.user
    .findUnique({ where: { id: BigInt(req.session.userId) }, select: { role: true } })
    .then((u) =>
      u?.role === 'admin' ? next() : res.status(403).send('Administrator access required.'),
    )
    .catch(next);
});
app.get('*', (req, res) => res.sendFile(path.join(__dirname, '../dist/index.html')));
app.use((err, req, res, next) => {
  if (res.headersSent) return next(err);
  let status = err.status || 500;
  let message = err.message;
  if (err instanceof z.ZodError) {
    status = 400;
    message = err.issues.map((i) => `${i.path.join('.')}: ${i.message}`).join('; ');
  } else if (err.code === 'LIMIT_FILE_SIZE') {
    status = 413;
    message = 'Images must be smaller than 8 MB.';
  } else if (err.code === 'LIMIT_UNEXPECTED_FILE') {
    status = 400;
    message = 'Upload one image at a time.';
  } else if (err.code === '23505' || err.code === 'P2002') {
    status = 409;
    message = 'This record already exists.';
  } else if (['22P02', '23503', '22007'].includes(err.code) || err.code === 'P2003') {
    status = 400;
    message = 'Invalid record or reference.';
  } else if (err.code === 'P2025') {
    status = 404;
    message = 'Record not found.';
  } else if (err.type === 'entity.parse.failed') {
    status = 400;
    message = 'Invalid JSON body.';
  } else if (err.code && err.code.startsWith('LIMIT_')) {
    status = 400;
    message = 'Invalid multipart upload.';
  } else if (err instanceof SyntaxError || err.name === 'PrismaClientValidationError') {
    status = 400;
    message = 'Invalid request.';
  } else if (status >= 500) {
    console.error('Request failed:', err.code || err.name);
    message = 'The service is temporarily unavailable. Please try again.';
  }
  res.status(status).json({ error: message });
});
module.exports = app;
