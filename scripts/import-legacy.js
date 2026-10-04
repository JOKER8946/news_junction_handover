// Stage every legacy row in PostgreSQL, then promote supported news entities.
// No source SQL is executed. Original files remain untouched.
const path = require('node:path');
const crypto = require('node:crypto');
const sanitize = require('sanitize-html');
const { timestamp, plain } = require('./legacy-values');
const { pool } = require('../server/database');
const { readDump } = require('./mysql-dump');
const migrate = require('./migrate');
async function archive(filename) {
  const source = path.basename(filename),
    db = await pool.connect();
  let batch = [],
    count = 0;
  const occurrences = new Map();
  async function flush() {
    if (!batch.length) return;
    await db.query(
      'INSERT INTO legacy_records(source,table_name,record_key,data) SELECT source,table_name,record_key,data FROM jsonb_to_recordset($1::jsonb) AS r(source text,table_name text,record_key text,data jsonb) ON CONFLICT(source,table_name,record_key) DO UPDATE SET data=EXCLUDED.data',
      [JSON.stringify(batch)],
    );
    batch = [];
  }
  try {
    await db.query('BEGIN');
    for await (const { table, data } of readDump(filename)) {
      const key =
        data.id ||
        data.magazine_id ||
        crypto.createHash('sha256').update(JSON.stringify(data)).digest('hex');
      const identity = table + ':' + key,
        n = (occurrences.get(identity) || 0) + 1;
      occurrences.set(identity, n);
      batch.push({
        source,
        table_name: table,
        record_key: String(key) + (n > 1 ? ':' + n : ''),
        data,
      });
      count++;
      if (batch.length >= 300) await flush();
    }
    await flush();
    await db.query('COMMIT');
    console.log(`${source}: ${count} rows archived.`);
    return count;
  } catch (e) {
    await db.query('ROLLBACK');
    throw e;
  } finally {
    db.release();
  }
}
const str = (v) => (v == null ? '' : String(v));
function media(v) {
  v = str(v);
  if (!v) return '';
  if (v.startsWith('https://')) return v;
  if (v.startsWith('http://')) return v.replace('http://', 'https://');
  return '/legacy-media/' + v.replace(/^\//, '');
}
async function promote(source = 'nj_cream.sql.gz', readerSource = 'nj_reader.sql.gz') {
  const db = await pool.connect();
  let summary = { users: 0, articles: 0, channels: 0, resources: 0, complaints: 0 };
  const records = async (table, src = source) =>
    (
      await db.query(
        'SELECT record_key,data FROM legacy_records WHERE source=$1 AND table_name=$2 ORDER BY record_key',
        [src, table],
      )
    ).rows;
  const users = new Map(),
    articles = new Map(),
    channels = new Map(),
    cats = new Map(),
    posts = new Map(),
    sources = new Map(),
    seenUrls = new Set();
  try {
    await db.query('BEGIN');
    await db.query('SELECT pg_advisory_xact_lock(74639201)');
    if (
      (await db.query('SELECT id FROM users LIMIT 1')).rowCount ||
      (await db.query('SELECT id FROM articles LIMIT 1')).rowCount
    )
      throw Error(
        'Promotion requires a fresh database with no users/articles. Archive mode is safe for existing databases.',
      );
    await db.query("INSERT INTO categories(name) VALUES('Community') ON CONFLICT DO NOTHING");
    const fallback = (await db.query("SELECT id FROM categories WHERE name='Community'")).rows[0]
      .id;
    for (const { record_key, data: d } of await records('reader_category', readerSource)) {
      if (!d.category || d.category === 'All News') continue;
      const c = (
        await db.query(
          'INSERT INTO categories(name) VALUES($1) ON CONFLICT(name) DO UPDATE SET name=EXCLUDED.name RETURNING id',
          [d.category],
        )
      ).rows[0];
      cats.set(record_key, c.id);
    }
    for (const { data: d } of await records('rss_feeds_url', readerSource))
      sources.set(str(d.rss_id), d);
    for (const { record_key, data: d } of await records('user')) {
      if (!d.email || !d.email.includes('@') || (d.is_deleted && d.is_deleted !== '0')) continue;
      // Keep legacy hashes intact. Only bcrypt hashes can sign in until reset-password.js is run.
      const u = (
        await db.query(
          'INSERT INTO users(email,password,full_name,role,bio,phone) VALUES($1,$2,$3,$4,$5,$6) ON CONFLICT(lower(email)) DO UPDATE SET email=EXCLUDED.email RETURNING id',
          [
            d.email.toLowerCase().trim(),
            d.password || crypto.randomBytes(48).toString('hex'),
            d.full_name || 'Reader',
            ['admin', 'reporter'].includes(d.role) ? d.role : 'reader',
            d.bio || '',
            d.phone_no || '',
          ],
        )
      ).rows[0];
      users.set(record_key, u.id);
      summary.users++;
    }
    for (const { record_key, data: d } of await records('user_collection')) {
      if (!d.title) continue;
      const a = (
        await db.query(
          'INSERT INTO articles(title,description,content,image,category_id,user_id,author_name,status,published_at) VALUES($1,$2,$3,$4,$5,$6,$7,$8,$9) RETURNING id',
          [
            plain(d.title),
            plain(sanitize(d.description || '', { allowedTags: [], allowedAttributes: {} })).slice(
              0,
              600,
            ),
            sanitize(d.description || ''),
            media(d.cover_img),
            fallback,
            users.get(str(d.user_id)) || null,
            d.author || 'News Junction',
            d.is_archive === '1' ? 'draft' : 'published',
            timestamp(d.date_published || d.date_added),
          ],
        )
      ).rows[0];
      articles.set('collection:' + record_key, a.id);
      summary.articles++;
    }
    // Cursor pagination avoids loading the entire RSS archive into process memory.
    let last = '';
    while (true) {
      const rows = (
        await db.query(
          "SELECT record_key,data FROM legacy_records WHERE source=$1 AND table_name='rss_feeds_articles' AND record_key>$2 ORDER BY record_key LIMIT 1000",
          [readerSource, last],
        )
      ).rows;
      if (!rows.length) break;
      last = rows.at(-1).record_key;
      const valid = rows.filter((r) => r.data.title);
      if (!valid.length) continue;
      const ids = (
        await db.query(
          "SELECT nextval(pg_get_serial_sequence('articles','id')) AS id FROM generate_series(1,$1)",
          [valid.length],
        )
      ).rows;
      const batch = valid.map(({ record_key, data: d }, i) => {
        articles.set('rss:' + record_key, ids[i].id);
        const feed = sources.get(str(d.feed_id)) || {};
        const source_url = /^https?:\/\//.test(d.url || '') && !seenUrls.has(d.url) ? d.url : null;
        if (source_url) seenUrls.add(source_url);
        return {
          id: ids[i].id,
          title: plain(d.title),
          source_url,
          publisher: feed.rss_publisher || 'News Junction',
          description: plain(
            sanitize(d.description || '', { allowedTags: [], allowedAttributes: {} }),
          ).slice(0, 600),
          content: sanitize(d.content || d.description || ''),
          image: media(d.image),
          category_id: cats.get(str(d.category)) || cats.get(str(feed.rss_category)) || fallback,
          author_name: plain(d.author) || feed.rss_publisher || 'News Junction',
          published_at: timestamp(d.date),
        };
      });
      await db.query(
        'INSERT INTO articles(id,title,description,content,image,category_id,author_name,published_at,source_url,publisher) SELECT id,title,description,content,image,category_id,author_name,published_at,source_url,publisher FROM jsonb_to_recordset($1::jsonb) AS r(id bigint,title text,description text,content text,image text,category_id integer,author_name text,published_at timestamptz,source_url text,publisher text)',
        [JSON.stringify(batch)],
      );
      summary.articles += batch.length;
      if (summary.articles % 10000 < 1000) console.log('Stories promoted: ' + summary.articles);
    }
    for (const { record_key, data: d } of await records('channels', readerSource)) {
      if (d.deleted_at && d.deleted_at !== '0000-00-00 00:00:00') continue;
      const c = (
        await db.query(
          'INSERT INTO channels(name,bio,image,user_id) VALUES($1,$2,$3,$4) RETURNING id',
          [
            d.name || 'Community channel',
            d.bio || '',
            media(d.profilePic),
            users.get(str(d.created_by)) || null,
          ],
        )
      ).rows[0];
      channels.set(record_key, c.id);
      summary.channels++;
    }
    for (const { record_key, data: d } of await records('reader_stream', readerSource)) {
      const u = users.get(str(d.userId));
      if (!u || d.deleteFlag === '1' || (d.visibility && d.visibility !== 'public') || !d.chat)
        continue;
      const p = (
        await db.query(
          'INSERT INTO community_posts(user_id,body,image,pincode,created_at) VALUES($1,$2,$3,$4,$5) RETURNING id',
          [
            u,
            sanitize(d.chat, { allowedTags: [], allowedAttributes: {} }),
            media(d.mediaPath),
            d.pincode || '',
            timestamp(d.postedOn),
          ],
        )
      ).rows[0];
      posts.set(record_key, p.id);
    }
    for (const { data: d } of await records('channel_content', readerSource)) {
      const c = channels.get(str(d.channel_id)),
        p = posts.get(str(d.post_id));
      if (c && p)
        await db.query(
          'INSERT INTO channel_posts(channel_id,user_id,body,created_at) SELECT $1,user_id,body,created_at FROM community_posts WHERE id=$2',
          [c, p],
        );
    }
    for (const { data: d } of await records('reader_stream_follow', readerSource)) {
      const u = users.get(str(d.follower_id)),
        f = users.get(str(d.following_id));
      if (u && f && u !== f)
        await db.query(
          'INSERT INTO user_follows(follower_id,following_id) VALUES($1,$2) ON CONFLICT DO NOTHING',
          [u, f],
        );
    }
    for (const { data: d } of await records('reader_stream_like', readerSource)) {
      const u = users.get(str(d.userId)),
        p = posts.get(str(d.streamId));
      if (u && p)
        await db.query(
          'INSERT INTO community_likes(user_id,post_id) VALUES($1,$2) ON CONFLICT DO NOTHING',
          [u, p],
        );
    }
    for (const { data: d } of await records('reader_collection', readerSource)) {
      const u = users.get(str(d.user_id)),
        a = articles.get('rss:' + d.feed_id);
      if (u && a)
        await db.query(
          'INSERT INTO bookmarks(user_id,article_id) VALUES($1,$2) ON CONFLICT DO NOTHING',
          [u, a],
        );
    }
    for (const { data: d } of await records('reader_thumbs_up', readerSource)) {
      const u = users.get(str(d.userId)),
        a = articles.get('rss:' + d.articleId);
      if (u && a)
        await db.query(
          'INSERT INTO likes(user_id,article_id) VALUES($1,$2) ON CONFLICT DO NOTHING',
          [u, a],
        );
    }
    for (const { data: d } of await records('reader_comments', readerSource)) {
      const u = users.get(str(d.userId)),
        a = articles.get('rss:' + d.feedId);
      if (u && a && !d.deleted_on)
        await db.query(
          'INSERT INTO comments(user_id,article_id,body,created_at) VALUES($1,$2,$3,$4)',
          [u, a, d.comment || '', timestamp(d.posted_on)],
        );
    }
    for (const { data: d } of await records('complaints')) {
      const u = users.get(str(d.user_id));
      if (!u) continue;
      await db.query(
        'INSERT INTO complaints(user_id,title,description,location,pincode,status,response,created_at) VALUES($1,$2,$3,$4,$5,$6,$7,$8)',
        [
          u,
          d.title || 'Civic report',
          d.description || '',
          d.department_name || '',
          d.pincode || '',
          /resolved|closed/i.test(d.status)
            ? 'Resolved'
            : /progress|review/i.test(d.status)
              ? 'In Review'
              : 'Submitted',
          d.admin_response || '',
          timestamp(d.created_at),
        ],
      );
      summary.complaints++;
    }
    for (const [table, kind] of [
      ['magazine', 'magazines'],
      ['ads', 'ads'],
    ])
      for (const { data: d } of await records(table, readerSource)) {
        await db.query('INSERT INTO resources(kind,title,data,user_id) VALUES($1,$2,$3,$4)', [
          kind,
          d.title || 'Untitled',
          JSON.stringify({
            description: d.description || '',
            url: media(d.magazine_url || d.ad_link),
            image: media(d.images || d.image_url),
          }),
          users.get(str(d.user_id || d.created_by)) || null,
        ]);
        summary.resources++;
      }
    for (const { data: d } of await records('rss_feeds_url', readerSource)) {
      const url = d.rss_url || d.url || d.feed_url;
      if (!url || !/^https?:\/\//.test(url)) continue;
      await db.query(
        'INSERT INTO feed_sources(url,publisher,category_id) VALUES($1,$2,$3) ON CONFLICT(url) DO NOTHING',
        [url, d.rss_publisher || d.publisher || '', cats.get(str(d.rss_category)) || fallback],
      );
    }
    await db.query('COMMIT');
    console.log('Promoted:', summary);
    return summary;
  } catch (e) {
    await db.query('ROLLBACK');
    throw e;
  } finally {
    db.release();
  }
}
async function main() {
  await migrate();
  const args = process.argv.slice(2),
    files = args.filter((x) => !x.startsWith('--'));
  if (!files.length && !args.includes('--promote'))
    throw Error(
      'Usage: npm run db:import -- database/nj_cream.sql.gz database/nj_reader.sql.gz [--promote]',
    );
  for (const file of files) await archive(path.resolve(file));
  if (args.includes('--promote'))
    await promote(process.env.LEGACY_ACCOUNT_SOURCE, process.env.LEGACY_READER_SOURCE);
}
if (require.main === module)
  main()
    .catch((e) => {
      console.error(e.message);
      process.exitCode = 1;
    })
    .finally(() => pool.end());
module.exports = { archive, promote };
