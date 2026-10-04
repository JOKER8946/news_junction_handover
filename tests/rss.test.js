const { test } = require('node:test');
const assert = require('node:assert/strict');
const http = require('node:http');
const { pool } = require('../server/database');
const sync = require('../scripts/sync-feeds');
test('RSS ingestion sanitizes content, preserves provenance and ignores duplicates', async () => {
  const unique = 'https://example.test/rss-verification-' + Date.now();
  const server = http.createServer((req, res) => {
    res.setHeader('Content-Type', 'application/rss+xml');
    res.end(
      `<?xml version="1.0"?><rss version="2.0"><channel><title>Test publisher</title><link>https://example.test</link><description>Test feed</description><item><title>Testing &amp; context</title><link>${unique}</link><description><![CDATA[<p>Verified test body</p><script>unsafe()</script>]]></description><pubDate>Sat, 03 Oct 2026 08:00:00 GMT</pubDate></item></channel></rss>`,
    );
  });
  await new Promise((r) => server.listen(0, '127.0.0.1', r));
  let id;
  try {
    id = (
      await pool.query(
        'INSERT INTO feed_sources(url,publisher,category_id) VALUES($1,$2,3) RETURNING id',
        [`http://127.0.0.1:${server.address().port}/feed`, 'Test publisher'],
      )
    ).rows[0].id;
    await sync([id]);
    await sync([id]);
    const rows = (await pool.query('SELECT * FROM articles WHERE source_url=$1', [unique])).rows;
    assert.equal(rows.length, 1);
    assert.equal(rows[0].title, 'Testing & context');
    assert.equal(rows[0].publisher, 'Test publisher');
    assert.doesNotMatch(rows[0].content, /<script/);
    assert.ok(
      (await pool.query('SELECT last_synced_at FROM feed_sources WHERE id=$1', [id])).rows[0]
        .last_synced_at,
    );
  } finally {
    await pool.query('DELETE FROM articles WHERE source_url=$1', [unique]);
    if (id) await pool.query('DELETE FROM feed_sources WHERE id=$1', [id]);
    await new Promise((r) => server.close(r));
    await pool.end();
  }
});
