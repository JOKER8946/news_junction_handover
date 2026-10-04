// Run through your scheduler (for example every 15 minutes). Sources are operator-managed.
const Parser = require('rss-parser');
const sanitize = require('sanitize-html');
const { pool } = require('../server/database');
const parser = new Parser({
  customFields: {
    item: [
      ['media:content', 'media'],
      ['media:thumbnail', 'thumbnail'],
    ],
  },
});
async function sync(ids = null) {
  const sources = (
    await pool.query(
      'SELECT * FROM feed_sources WHERE active AND ($1::bigint[] IS NULL OR id=ANY($1)) ORDER BY id',
      [ids],
    )
  ).rows;
  if (!sources.length) {
    console.log('No RSS sources configured in feed_sources.');
    return;
  }
  for (const source of sources) {
    try {
      const response = await fetch(source.url, {
        signal: AbortSignal.timeout(20000),
        headers: { 'User-Agent': 'NewsJunction/2.0 RSS Reader' },
      });
      if (!response.ok) throw Error('HTTP ' + response.status);
      const reader = response.body.getReader();
      let bytes = 0,
        chunks = [];
      while (true) {
        const { done, value } = await reader.read();
        if (done) break;
        bytes += value.length;
        if (bytes > 5 * 1024 * 1024) {
          await reader.cancel();
          throw Error('Feed exceeds 5 MB');
        }
        chunks.push(value);
      }
      const feed = await parser.parseString(Buffer.concat(chunks).toString('utf8'));
      let inserted = 0;
      for (const item of feed.items.slice(0, 100)) {
        if (!item.title || !item.link || !/^https?:\/\//.test(item.link)) continue;
        const image = item.enclosure?.type?.startsWith('image/')
          ? item.enclosure.url
          : item.media?.$?.url || item.thumbnail?.$?.url || '';
        const content = item['content:encoded'] || item.content || item.contentSnippet || '';
        const when = new Date(item.isoDate || item.pubDate);
        const r = await pool.query(
          'INSERT INTO articles(title,description,content,image,category_id,author_name,publisher,published_at,source_url) VALUES($1,$2,$3,$4,$5,$6,$7,$8,$9) ON CONFLICT(source_url) WHERE source_url IS NOT NULL DO NOTHING',
          [
            item.title,
            sanitize(content, { allowedTags: [], allowedAttributes: {} }).slice(0, 600),
            sanitize(content),
            /^https?:\/\//.test(image) ? image : '',
            source.category_id,
            item.creator || source.publisher || feed.title || 'News Junction',
            source.publisher || feed.title || 'News Junction',
            Number.isFinite(when.getTime()) ? when : new Date(),
            item.link,
          ],
        );
        inserted += r.rowCount;
      }
      await pool.query('UPDATE feed_sources SET last_synced_at=now(),last_error=NULL WHERE id=$1', [
        source.id,
      ]);
      console.log(`Feed ${source.id}: ${inserted} new stories.`);
    } catch (e) {
      await pool.query('UPDATE feed_sources SET last_error=$1 WHERE id=$2', [e.message, source.id]);
      console.error(`Feed ${source.id}: ${e.message}`);
      process.exitCode = 1;
    }
  }
}
if (require.main === module)
  sync()
    .catch((e) => {
      console.error(e.message);
      process.exitCode = 1;
    })
    .finally(() => pool.end());
module.exports = sync;
