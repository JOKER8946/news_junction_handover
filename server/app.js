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
const { pool, query } = require('./database');
const app = express();
const production = process.env.NODE_ENV === 'production';
if (production && (!process.env.SESSION_SECRET || process.env.SESSION_SECRET.length < 32))
  throw new Error('Set SESSION_SECRET to at least 32 random characters.');
app.disable('x-powered-by');
if (process.env.TRUST_PROXY === '1') app.set('trust proxy', 1);
app.use(
  helmet({
    contentSecurityPolicy: {
      directives: {
        'img-src': ["'self'", 'https:', 'data:'],
        'script-src': ["'self'"],
        'style-src': ["'self'", "'unsafe-inline'"],
        'upgrade-insecure-requests': production ? [] : null,
      },
    },
  }),
);
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
      secure: process.env.COOKIE_SECURE === 'true',
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
    if (req.session.userId)
      req.user = (await query('SELECT * FROM users WHERE id=$1', [req.session.userId])).rows[0];
    if (!['GET', 'HEAD', 'OPTIONS'].includes(req.method)) {
      const origin = req.get('origin');
      const allowed = process.env.APP_ORIGIN || 'http://localhost:3000';
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
const limited = rateLimit({
  windowMs: 15 * 60000,
  limit: 30,
  standardHeaders: 'draft-8',
  legacyHeaders: false,
  message: { error: 'Too many attempts. Please try again later.' },
});
const text = (max = 500) => z.string().trim().min(1).max(max);
const email = z
  .email()
  .max(254)
  .transform((v) => v.toLowerCase());
const validUrl = z
  .string()
  .max(2000)
  .refine((v) => !v || /^https:\/\//.test(v) || /^\/media\//.test(v), 'Use an HTTPS URL.');
const articleSchema = z.object({
  title: text(220),
  description: z.string().max(600).default(''),
  content: text(100000),
  image: validUrl.default(''),
  category_id: z.coerce.number().int().positive(),
  district: z.string().max(100).default(''),
  status: z.enum(['draft', 'published', 'scheduled']).default('published'),
  published_at: z.iso.datetime().optional(),
});
const visible = "(a.status='published' OR (a.status='scheduled' AND a.published_at<=now()))";
const articleSelect = `SELECT a.*,c.name AS category_name,(SELECT count(*)::int FROM likes l WHERE l.article_id=a.id) AS likes,EXISTS(SELECT 1 FROM bookmarks b WHERE b.article_id=a.id AND b.user_id=$1) AS bookmarked,EXISTS(SELECT 1 FROM likes l WHERE l.article_id=a.id AND l.user_id=$1) AS liked FROM articles a LEFT JOIN categories c ON c.id=a.category_id`;
app.get(
  '/api/health',
  wrap(async (req, res) => {
    await query('SELECT 1');
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
  req.session.userId = user.id;
  req.session.csrf = crypto.randomBytes(24).toString('hex');
  await new Promise((resolve, reject) => req.session.save((e) => (e ? reject(e) : resolve())));
}
app.post(
  '/api/auth/register',
  limited,
  wrap(async (req, res) => {
    const d = z
      .object({ full_name: text(100), email, password: z.string().min(10).max(128) })
      .parse(req.body);
    const hash = await bcrypt.hash(d.password, 12);
    const user = (
      await query('INSERT INTO users(full_name,email,password) VALUES($1,$2,$3) RETURNING *', [
        d.full_name,
        d.email,
        hash,
      ])
    ).rows[0];
    await signIn(req, user);
    res.status(201).json({ user: publicUser(user), csrf: req.session.csrf });
  }),
);
app.post(
  '/api/auth/login',
  limited,
  wrap(async (req, res) => {
    const d = z.object({ email, password: text(128) }).parse(req.body);
    const user = (await query('SELECT * FROM users WHERE lower(email)=$1', [d.email])).rows[0];
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
    res.clearCookie('nj.sid');
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
    const u = (
      await query(
        'UPDATE users SET full_name=$1,bio=$2,district=$3,phone=$4 WHERE id=$5 RETURNING *',
        [d.full_name, d.bio, d.district, d.phone, req.user.id],
      )
    ).rows[0];
    res.json(publicUser(u));
  }),
);
app.put(
  '/api/profile/password',
  auth,
  limited,
  wrap(async (req, res) => {
    const d = z
      .object({ current: text(128), password: z.string().min(10).max(128) })
      .parse(req.body);
    if (!(await bcrypt.compare(d.current, req.user.password.replace(/^\$2y\$/, '$2b$'))))
      throw fail(400, 'Current password is incorrect.');
    await query('UPDATE users SET password=$1 WHERE id=$2', [
      await bcrypt.hash(d.password, 12),
      req.user.id,
    ]);
    await query("DELETE FROM session WHERE sess->>'userId'=$1 AND sid<>$2", [
      String(req.user.id),
      req.sessionID,
    ]);
    res.json({ ok: true });
  }),
);
app.get(
  '/api/categories',
  wrap(async (req, res) => res.json((await query('SELECT * FROM categories ORDER BY id')).rows)),
);
app.get(
  '/api/articles',
  wrap(async (req, res) => {
    const page = Math.max(1, Math.min(10000, parseInt(req.query.page) || 1)),
      limit = 12;
    const values = [req.user?.id || null];
    let conditions = [visible];
    for (const [field, value, expr] of [
      ['q', req.query.q, '(a.title ILIKE ? OR a.description ILIKE ?)'],
      ['district', req.query.district, 'a.district=?'],
      ['category', req.query.category, 'c.name=?'],
    ])
      if (value) {
        values.push(field === 'q' ? `%${String(value).slice(0, 100)}%` : String(value));
        conditions.push(expr.replaceAll('?', `$${values.length}`));
      }
    if (req.query.saved === 'true') {
      if (!req.user) throw fail(401, 'Please sign in to see saved stories.');
      conditions.push('EXISTS(SELECT 1 FROM bookmarks b WHERE b.article_id=a.id AND b.user_id=$1)');
    }
    if (req.query.mine === 'true') {
      if (!req.user) throw fail(401, 'Please sign in.');
      conditions[0] = 'a.user_id=$1';
    }
    const where = ' WHERE ' + conditions.join(' AND ');
    const result = await query(
      articleSelect +
        where +
        ` ORDER BY a.published_at DESC,a.id DESC LIMIT 13 OFFSET ${(page - 1) * limit}`,
      values,
    );
    res.json({ articles: result.rows.slice(0, limit), hasMore: result.rows.length > limit, page });
  }),
);
app.post(
  '/api/articles',
  auth,
  editor,
  wrap(async (req, res) => {
    const d = articleSchema.parse(req.body);
    if (d.status === 'scheduled' && (!d.published_at || new Date(d.published_at) <= new Date()))
      throw fail(400, 'Choose a future publication time.');
    const r = await query(
      'INSERT INTO articles(title,description,content,image,category_id,district,status,published_at,user_id,author_name) VALUES($1,$2,$3,$4,$5,$6,$7,$8,$9,$10) RETURNING *',
      [
        d.title,
        d.description,
        sanitize(d.content),
        d.image,
        d.category_id,
        d.district,
        d.status,
        d.published_at || new Date().toISOString(),
        req.user.id,
        req.user.full_name,
      ],
    );
    res.status(201).json(r.rows[0]);
  }),
);
app.get(
  '/api/articles/:id',
  wrap(async (req, res) => {
    const a = (
      await query(articleSelect + ` WHERE a.id=$2 AND (${visible} OR a.user_id=$1 OR $3)`, [
        req.user?.id || null,
        req.params.id,
        req.user?.role === 'admin',
      ])
    ).rows[0];
    if (!a) throw fail(404, 'Story not found.');
    await query('UPDATE articles SET views=views+1 WHERE id=$1', [a.id]);
    a.content = sanitize(a.content);
    a.comments = (
      await query(
        'SELECT c.id,c.body,c.created_at,u.full_name FROM comments c JOIN users u ON u.id=c.user_id WHERE article_id=$1 ORDER BY c.created_at DESC',
        [a.id],
      )
    ).rows;
    res.json(a);
  }),
);
async function ownedArticle(req) {
  const a = (await query('SELECT * FROM articles WHERE id=$1', [req.params.id])).rows[0];
  if (!a) throw fail(404, 'Story not found.');
  if (String(a.user_id) !== String(req.user.id) && req.user.role !== 'admin')
    throw fail(403, 'You can only change your own stories.');
  return a;
}
app.put(
  '/api/articles/:id',
  auth,
  editor,
  wrap(async (req, res) => {
    await ownedArticle(req);
    const d = articleSchema.parse(req.body);
    if (d.status === 'scheduled' && (!d.published_at || new Date(d.published_at) <= new Date()))
      throw fail(400, 'Choose a future publication time.');
    res.json(
      (
        await query(
          'UPDATE articles SET title=$1,description=$2,content=$3,image=$4,category_id=$5,district=$6,status=$7,published_at=COALESCE($8,published_at) WHERE id=$9 RETURNING *',
          [
            d.title,
            d.description,
            sanitize(d.content),
            d.image,
            d.category_id,
            d.district,
            d.status,
            d.published_at || null,
            req.params.id,
          ],
        )
      ).rows[0],
    );
  }),
);
app.delete(
  '/api/articles/:id',
  auth,
  editor,
  wrap(async (req, res) => {
    await ownedArticle(req);
    await query('DELETE FROM articles WHERE id=$1', [req.params.id]);
    res.json({ ok: true });
  }),
);
for (const [action, table] of [
  ['bookmark', 'bookmarks'],
  ['like', 'likes'],
])
  app.put(
    `/api/articles/:id/${action}`,
    auth,
    wrap(async (req, res) => {
      const { active } = z.object({ active: z.boolean() }).parse(req.body);
      const a = (
        await query(`SELECT id FROM articles a WHERE id=$1 AND ${visible}`, [req.params.id])
      ).rows[0];
      if (!a) throw fail(404, 'Story not found.');
      if (active)
        await query(
          `INSERT INTO ${table}(user_id,article_id) VALUES($1,$2) ON CONFLICT DO NOTHING`,
          [req.user.id, a.id],
        );
      else
        await query(`DELETE FROM ${table} WHERE user_id=$1 AND article_id=$2`, [req.user.id, a.id]);
      res.json({ active });
    }),
  );
app.post(
  '/api/articles/:id/comments',
  auth,
  wrap(async (req, res) => {
    const body = text(3000).parse(req.body.body);
    const a = (await query(`SELECT id FROM articles a WHERE id=$1 AND ${visible}`, [req.params.id]))
      .rows[0];
    if (!a) throw fail(404, 'Story not found.');
    res
      .status(201)
      .json(
        (
          await query(
            'INSERT INTO comments(user_id,article_id,body) VALUES($1,$2,$3) RETURNING *',
            [req.user.id, a.id, body],
          )
        ).rows[0],
      );
  }),
);
app.get(
  '/api/channels',
  wrap(async (req, res) =>
    res.json(
      (
        await query(
          'SELECT c.*,(SELECT count(*)::int FROM follows f WHERE f.channel_id=c.id) AS followers,EXISTS(SELECT 1 FROM follows f WHERE f.channel_id=c.id AND f.user_id=$1) AS followed FROM channels c ORDER BY c.id',
          [req.user?.id || null],
        )
      ).rows,
    ),
  ),
);
app.post(
  '/api/channels',
  auth,
  editor,
  wrap(async (req, res) => {
    const d = z.object({ name: text(120), bio: z.string().max(1000).default('') }).parse(req.body);
    res
      .status(201)
      .json(
        (
          await query('INSERT INTO channels(name,bio,user_id) VALUES($1,$2,$3) RETURNING *', [
            d.name,
            d.bio,
            req.user.id,
          ])
        ).rows[0],
      );
  }),
);
app.put(
  '/api/channels/:id/follow',
  auth,
  wrap(async (req, res) => {
    const { active } = z.object({ active: z.boolean() }).parse(req.body);
    if (active)
      await query('INSERT INTO follows(user_id,channel_id) VALUES($1,$2) ON CONFLICT DO NOTHING', [
        req.user.id,
        req.params.id,
      ]);
    else
      await query('DELETE FROM follows WHERE user_id=$1 AND channel_id=$2', [
        req.user.id,
        req.params.id,
      ]);
    res.json({ active });
  }),
);
app.get(
  '/api/channels/:id',
  wrap(async (req, res) => {
    const c = (await query('SELECT * FROM channels WHERE id=$1', [req.params.id])).rows[0];
    if (!c) throw fail(404, 'Channel not found.');
    c.posts = (
      await query(
        'SELECT p.*,u.full_name FROM channel_posts p LEFT JOIN users u ON u.id=p.user_id WHERE channel_id=$1 ORDER BY p.created_at DESC',
        [c.id],
      )
    ).rows;
    res.json(c);
  }),
);
app.post(
  '/api/channels/:id/posts',
  auth,
  editor,
  wrap(async (req, res) => {
    const c = (await query('SELECT * FROM channels WHERE id=$1', [req.params.id])).rows[0];
    if (!c) throw fail(404, 'Channel not found.');
    if (String(c.user_id) !== String(req.user.id) && req.user.role !== 'admin')
      throw fail(403, 'Only the channel owner can post.');
    const body = text(5000).parse(req.body.body);
    res
      .status(201)
      .json(
        (
          await query(
            'INSERT INTO channel_posts(channel_id,user_id,body) VALUES($1,$2,$3) RETURNING *',
            [c.id, req.user.id, body],
          )
        ).rows[0],
      );
  }),
);
app.get(
  '/api/complaints',
  auth,
  wrap(async (req, res) =>
    res.json(
      (
        await query('SELECT * FROM complaints WHERE user_id=$1 OR $2 ORDER BY created_at DESC', [
          req.user.id,
          req.user.role === 'admin',
        ])
      ).rows,
    ),
  ),
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
    res
      .status(201)
      .json(
        (
          await query(
            'INSERT INTO complaints(user_id,title,description,location,pincode) VALUES($1,$2,$3,$4,$5) RETURNING *',
            [req.user.id, d.title, d.description, d.location, d.pincode],
          )
        ).rows[0],
      );
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
    const r = await query('UPDATE complaints SET status=$1,response=$2 WHERE id=$3 RETURNING *', [
      d.status,
      d.response,
      req.params.id,
    ]);
    if (!r.rowCount) throw fail(404, 'Report not found.');
    res.json(r.rows[0]);
  }),
);
app.get(
  '/api/resources/:kind',
  wrap(async (req, res) => {
    const kind = z
      .enum(['newspapers', 'magazines', 'influencers', 'ads', 'templates'])
      .parse(req.params.kind);
    res.json(
      (await query('SELECT * FROM resources WHERE kind=$1 ORDER BY created_at DESC', [kind])).rows,
    );
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
    res
      .status(201)
      .json(
        (
          await query(
            'INSERT INTO resources(kind,title,data,user_id) VALUES($1,$2,$3,$4) RETURNING *',
            [kind, d.title, d.data, req.user.id],
          )
        ).rows[0],
      );
  }),
);
app.delete(
  '/api/resources/:id',
  auth,
  editor,
  wrap(async (req, res) => {
    const r = await query('DELETE FROM resources WHERE id=$1 AND (user_id=$2 OR $3) RETURNING id', [
      req.params.id,
      req.user.id,
      req.user.role === 'admin',
    ]);
    if (!r.rowCount) throw fail(404, 'Resource not found or not owned by you.');
    res.json({ ok: true });
  }),
);
app.post(
  '/api/subscribe',
  limited,
  wrap(async (req, res) => {
    const e = email.parse(req.body.email);
    await query('INSERT INTO subscribers(email) VALUES($1) ON CONFLICT DO NOTHING', [e]);
    res.json({ ok: true, message: 'You are on the list. Thank you for joining us.' });
  }),
);
app.get(
  '/api/lead-pages/:id',
  wrap(async (req, res) => {
    const r = (
      await query("SELECT * FROM resources WHERE id=$1 AND kind='templates'", [req.params.id])
    ).rows[0];
    if (!r) throw fail(404, 'Page not found.');
    res.json(r);
  }),
);
app.post(
  '/api/lead-pages/:id/leads',
  limited,
  wrap(async (req, res) => {
    const d = z.object({ name: text(100), email }).parse(req.body);
    const r = await query("SELECT id FROM resources WHERE id=$1 AND kind='templates'", [
      req.params.id,
    ]);
    if (!r.rowCount) throw fail(404, 'Page not found.');
    await query('INSERT INTO leads(resource_id,name,email) VALUES($1,$2,$3)', [
      req.params.id,
      d.name,
      d.email,
    ]);
    res.status(201).json({ ok: true });
  }),
);
app.get(
  '/api/analytics',
  auth,
  editor,
  wrap(async (req, res) => {
    const scope = [req.user.id, req.user.role === 'admin'];
    const stats = (
      await query(
        "SELECT count(*)::int AS stories,COALESCE(sum(views),0)::int AS views,count(*) FILTER(WHERE status='draft')::int AS drafts FROM articles WHERE user_id=$1 OR $2",
        scope,
      )
    ).rows[0];
    const stories = (
      await query(
        'SELECT id,title,views,status FROM articles WHERE user_id=$1 OR $2 ORDER BY views DESC LIMIT 20',
        scope,
      )
    ).rows;
    const leads = (
      await query(
        'SELECT l.*,r.title FROM leads l JOIN resources r ON r.id=l.resource_id WHERE r.user_id=$1 OR $2 ORDER BY l.created_at DESC',
        scope,
      )
    ).rows;
    res.json({ stats, stories, leads });
  }),
);
app.get(
  '/api/admin/users',
  auth,
  admin,
  wrap(async (req, res) =>
    res.json(
      (await query('SELECT id,full_name,email,role,created_at FROM users ORDER BY id LIMIT 500'))
        .rows,
    ),
  ),
);
app.put(
  '/api/admin/users/:id/role',
  auth,
  admin,
  wrap(async (req, res) => {
    const role = z.enum(['reader', 'reporter', 'admin']).parse(req.body.role);
    if (String(req.user.id) === req.params.id) throw fail(400, 'You cannot change your own role.');
    const r = await query('UPDATE users SET role=$1 WHERE id=$2 RETURNING id,role', [
      role,
      req.params.id,
    ]);
    if (!r.rowCount) throw fail(404, 'User not found.');
    res.json(r.rows[0]);
  }),
);
app.use('/api/community', require('./community'));
const multer = require('multer');
const receiveImage = multer({
  storage: multer.memoryStorage(),
  limits: { fileSize: 8 * 1024 * 1024, files: 1 },
}).single('image');
app.post(
  '/api/uploads',
  auth,
  limited,
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
    const name = crypto.randomUUID() + '.' + type.ext;
    await fs.writeFile(path.join(directory, name), req.file.buffer, { flag: 'wx' });
    res.status(201).json({ url: '/media/uploads/' + name });
  }),
);
app.use('/api', (req, res) => res.status(404).json({ error: 'Endpoint not found.' }));
app.use(
  '/media',
  express.static(path.join(__dirname, '../public/media'), { dotfiles: 'deny', index: false }),
);
app.use(
  '/legacy-media',
  (req, res, next) => {
    if (!/\.(jpe?g|png|gif|webp|avif|svg|ico|mp4|webm|mp3|wav|ogg|pdf|woff2?|ttf)$/i.test(req.path))
      return res.status(404).end();
    next();
  },
  express.static(process.env.LEGACY_MEDIA_ROOT || path.join(__dirname, '../app'), {
    dotfiles: 'deny',
    index: false,
    fallthrough: false,
  }),
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
  } else if (err.code === '23505') {
    status = 409;
    message = 'This record already exists.';
  } else if (['22P02', '23503', '22007'].includes(err.code)) {
    status = 400;
    message = 'Invalid record or reference.';
  } else if (status >= 500) {
    console.error(err);
    message = 'The service is temporarily unavailable. Please try again.';
  }
  res.status(status).json({ error: message });
});
module.exports = app;
