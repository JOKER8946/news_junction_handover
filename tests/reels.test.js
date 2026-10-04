const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs/promises');
const path = require('node:path');
const app = require('../server/app');
const { pool } = require('../server/database');
test('reel permissions, file validation, drafts, streaming, likes and deletion', async () => {
  const server = app.listen(0);
  await new Promise((r) => server.once('listening', r));
  const base = 'http://127.0.0.1:' + server.address().port;
  let userId, reelId, filename;
  function client() {
    let cookie = '',
      csrf = '';
    return async (url, method = 'GET', body) => {
      const r = await fetch(base + url, {
        method,
        headers: {
          Cookie: cookie,
          'X-CSRF-Token': csrf,
          ...(body instanceof FormData ? {} : { 'Content-Type': 'application/json' }),
        },
        body: body instanceof FormData ? body : body ? JSON.stringify(body) : undefined,
      });
      if (r.headers.get('set-cookie')) cookie = r.headers.get('set-cookie').split(';')[0];
      const data = await r.json();
      if (data.csrf) csrf = data.csrf;
      return { status: r.status, data };
    };
  }
  const owner = client(),
    guest = client();
  const form = (bytes, published = 'false') => {
    const f = new FormData();
    f.append('video', new Blob([bytes], { type: 'video/mp4' }), 'test.mp4');
    f.append('title', 'Reel verification');
    f.append('caption', 'A caption');
    f.append('published', published);
    return f;
  };
  try {
    await owner('/api/auth/session');
    await guest('/api/auth/session');
    const registered = await owner('/api/auth/register', 'POST', {
      full_name: 'Reel test',
      email: 'reel-' + Date.now() + '@example.test',
      password: 'Reel-test-password-2026',
    });
    userId = registered.data.user.id;
    assert.equal((await owner('/api/reels', 'POST', form('fake'))).status, 403);
    assert.equal((await guest('/api/reels?manage=true')).status, 403);
    await pool.query("UPDATE users SET role='admin' WHERE id=$1", [userId]);
    assert.equal((await owner('/api/reels', 'POST', form('not a video'))).status, 400);
    const bytes = await fs.readFile(path.join(__dirname, '../public/media/reel1.mp4'));
    const created = await owner('/api/reels', 'POST', form(bytes));
    assert.equal(created.status, 201);
    reelId = created.data.id;
    filename = (await pool.query('SELECT filename FROM reels WHERE id=$1', [reelId])).rows[0]
      .filename;
    assert.equal((await guest('/api/reels?id=' + reelId)).data.reels.length, 0);
    assert.equal((await fetch(base + '/api/reels/' + reelId + '/video')).status, 404);
    assert.equal((await fetch(base + '/media/uploads/.reels/' + filename)).status, 404);
    assert.equal(
      (
        await owner('/api/reels/' + reelId, 'PUT', {
          title: 'Published reel',
          caption: 'Updated',
          published: true,
        })
      ).status,
      200,
    );
    assert.equal((await guest('/api/reels?id=' + reelId)).data.reels[0].title, 'Published reel');
    const video = await fetch(base + '/api/reels/' + reelId + '/video', {
      headers: { Range: 'bytes=0-31' },
    });
    assert.equal(video.status, 206);
    assert.equal((await video.arrayBuffer()).byteLength, 32);
    assert.equal(
      (await guest('/api/reels/' + reelId + '/like', 'PUT', { active: true })).status,
      401,
    );
    await owner('/api/reels/' + reelId + '/like', 'PUT', { active: true });
    await owner('/api/reels/' + reelId + '/like', 'PUT', { active: true });
    assert.equal((await owner('/api/reels?id=' + reelId)).data.reels[0].likes, 1);
    assert.equal((await owner('/api/reels/' + reelId, 'DELETE')).status, 200);
    reelId = null;
    await assert.rejects(
      fs.access(path.join(__dirname, '../public/media/uploads/.reels', filename)),
    );
  } finally {
    if (reelId) await pool.query('DELETE FROM reels WHERE id=$1', [reelId]);
    if (filename)
      await fs
        .unlink(path.join(__dirname, '../public/media/uploads/.reels', filename))
        .catch(() => {});
    if (userId) {
      await pool.query("DELETE FROM session WHERE sess->>'userId'=$1", [String(userId)]);
      await pool.query('DELETE FROM users WHERE id=$1', [userId]);
    }
    await new Promise((r) => server.close(r));
    await pool.end();
  }
});
