const bcrypt = require('bcryptjs');
const fs = require('node:fs');
const path = require('node:path');
const { pool } = require('../server/database');
const migrate = require('./migrate');
async function seed() {
  await migrate();
  if (
    !process.env.SEED_ADMIN_EMAIL ||
    !process.env.SEED_ADMIN_PASSWORD ||
    process.env.SEED_ADMIN_PASSWORD.length < 10
  )
    throw Error('Set SEED_ADMIN_EMAIL and a SEED_ADMIN_PASSWORD of at least 10 characters.');
  const db = await pool.connect();
  try {
    await db.query('BEGIN');
    const data = require('../database/demo-content.json');
    for (const c of data.categories)
      await db.query('INSERT INTO categories(id,name) VALUES($1,$2) ON CONFLICT DO NOTHING', [
        c.id,
        c.category,
      ]);
    const admin = (
      await db.query(
        "INSERT INTO users(email,password,full_name,role) VALUES($1,$2,'News Junction Editor','admin') ON CONFLICT(lower(email)) DO UPDATE SET email=EXCLUDED.email RETURNING id",
        [
          process.env.SEED_ADMIN_EMAIL.toLowerCase(),
          await bcrypt.hash(process.env.SEED_ADMIN_PASSWORD, 12),
        ],
      )
    ).rows[0];
    if (!(await db.query('SELECT id FROM articles LIMIT 1')).rowCount) {
      for (const a of data.articles) {
        const image = fs.existsSync(path.join(__dirname, `../public/media/story-${a.id}.jpg`))
          ? `/media/story-${a.id}.jpg`
          : a.image;
        await db.query(
          'INSERT INTO articles(id,title,description,content,image,category_id,user_id,author_name,publisher,district,published_at) VALUES($1,$2,$3,$4,$5,$6,$7,$8,$9,$10,$11)',
          [
            a.id,
            a.title,
            a.description,
            a.content,
            image,
            a.category_id,
            admin.id,
            a.author_name,
            a.publisher,
            a.district,
            a.date,
          ],
        );
      }
      for (const c of data.channels)
        await db.query('INSERT INTO channels(id,name,bio,user_id) VALUES($1,$2,$3,$4)', [
          c.id,
          c.name,
          c.bio || '',
          admin.id,
        ]);
      for (const p of data.posts)
        await db.query('INSERT INTO channel_posts(channel_id,user_id,body) VALUES($1,$2,$3)', [
          p.channel_id,
          admin.id,
          p.chat,
        ]);
    }
    for (const a of data.articles)
      if (fs.existsSync(path.join(__dirname, `../public/media/story-${a.id}.jpg`)))
        await db.query('UPDATE articles SET image=$1 WHERE id=$2 AND title=$3', [
          `/media/story-${a.id}.jpg`,
          a.id,
          a.title,
        ]);
    for (const table of ['users', 'articles', 'channels', 'categories'])
      await db.query(
        `SELECT setval(pg_get_serial_sequence('${table}','id'), GREATEST(COALESCE((SELECT max(id) FROM ${table}),0),1), EXISTS(SELECT 1 FROM ${table}))`,
      );
    await db.query('COMMIT');
    console.log(
      'Local example content seeded. Editor credentials are in your untracked .env. This is sample content, not a live news import.',
    );
  } catch (e) {
    await db.query('ROLLBACK');
    throw e;
  } finally {
    db.release();
  }
}
if (require.main === module)
  seed()
    .catch((e) => {
      console.error(e.message);
      process.exitCode = 1;
    })
    .finally(() => pool.end());
module.exports = seed;
