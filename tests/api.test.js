const { test, before, after } = require('node:test');
const assert = require('node:assert/strict');
const app = require('../server/app');
const { pool } = require('../server/database');
let server, base, articleId, readerArticleId, rejectedArticleId, channelId, templateId;
const ids = [];
const suffix = Date.now();
function agent() {
  let cookie = '',
    csrf = '';
  return async (path, method = 'GET', body, override = {}) => {
    const r = await fetch(base + '/api' + path, {
      method,
      headers: {
        'Content-Type': 'application/json',
        Cookie: cookie,
        'X-CSRF-Token': csrf,
        ...override,
      },
      body: body ? JSON.stringify(body) : undefined,
    });
    const c = r.headers.get('set-cookie');
    if (c) cookie = c.split(';')[0];
    const data = await r.json();
    if (data.csrf) csrf = data.csrf;
    return { status: r.status, data, headers: r.headers };
  };
}
const owner = agent(),
  other = agent(),
  adminCall = agent(),
  guest = agent();
before(async () => {
  server = app.listen(0);
  await new Promise((r) => server.once('listening', r));
  base = 'http://127.0.0.1:' + server.address().port;
});
after(async () => {
  if (articleId) await pool.query('DELETE FROM articles WHERE id=$1', [articleId]);
  if (readerArticleId) await pool.query('DELETE FROM articles WHERE id=$1', [readerArticleId]);
  if (rejectedArticleId) await pool.query('DELETE FROM articles WHERE id=$1', [rejectedArticleId]);
  if (channelId) await pool.query('DELETE FROM channels WHERE id=$1', [channelId]);
  if (templateId) await pool.query('DELETE FROM resources WHERE id=$1', [templateId]);
  for (const id of ids) {
    await pool.query("DELETE FROM session WHERE sess->>'userId'=$1", [String(id)]);
    await pool.query('DELETE FROM users WHERE id=$1', [id]);
  }
  await new Promise((r) => server.close(r));
  await require('../server/prisma').$disconnect();
  await pool.end();
});
test('complete PostgreSQL workflow and permission boundaries', async (t) => {
  await t.test('anonymous requests require CSRF and never trust forged user cookies', async () => {
    const r = await guest(
      '/articles',
      'POST',
      { title: 'forged' },
      { Cookie: 'knobly_user_data={"userId":1}' },
    );
    assert.equal(r.status, 403);
    await guest('/auth/session');
    assert.equal((await guest('/articles', 'POST', {})).status, 401);
  });
  await t.test('register stores bcrypt and rotates secure session', async () => {
    for (const [call, n] of [
      [owner, 'owner'],
      [other, 'other'],
      [adminCall, 'admin'],
    ]) {
      await call('/auth/session');
      const r = await call('/auth/register', 'POST', {
        email: `${n}-${suffix}@example.test`,
        full_name: n,
        password: 'Integration-secret-2026',
      });
      assert.equal(r.status, 201);
      assert.equal(r.data.user.role, 'reader');
      assert.equal(r.data.user.password, undefined);
      assert.match(r.headers.get('set-cookie'), /HttpOnly/);
      assert.match(r.headers.get('set-cookie'), /SameSite=Lax/);
      ids.push(r.data.user.id);
    }
    await pool.query("UPDATE users SET role='admin' WHERE id=$1", [ids[2]]);
    const row = (await pool.query('SELECT password FROM users WHERE id=$1', [ids[0]])).rows[0];
    assert.match(row.password, /^\$2[aby]\$/);
  });
  await t.test('readers can submit but cannot publish or approve their own article', async () => {
    assert.equal((await owner('/articles', 'POST', {})).status, 400);
    assert.equal(
      (await owner('/admin/users/' + ids[0] + '/role', 'PUT', { role: 'admin' })).status,
      403,
    );
    const submission = await owner('/articles', 'POST', {
      title: `Reader submission ${suffix}`,
      description: 'Waiting for review',
      content: 'Reader submitted story',
      category_id: 3,
      status: 'published',
    });
    assert.equal(submission.status, 201);
    readerArticleId = submission.data.id;
    assert.equal(submission.data.status, 'pending');
    assert.equal((await guest('/articles/' + readerArticleId)).status, 404);
    assert.equal(
      (
        await owner('/admin/articles/' + readerArticleId + '/review', 'PUT', {
          decision: 'approve',
        })
      ).status,
      403,
    );
    assert.equal(
      (await adminCall('/admin/articles')).data.some((a) => a.id === readerArticleId),
      true,
    );
    assert.equal(
      (
        await adminCall('/admin/articles/' + readerArticleId + '/review', 'PUT', {
          decision: 'approve',
        })
      ).status,
      200,
    );
    assert.equal((await guest('/articles/' + readerArticleId)).status, 200);
    assert.equal(
      (
        await adminCall('/admin/articles/' + readerArticleId + '/review', 'PUT', {
          decision: 'approve',
        })
      ).status,
      409,
    );
    const rejected = await other('/articles', 'POST', {
      title: `Rejected submission ${suffix}`,
      content: 'This story will be rejected',
      category_id: 3,
    });
    assert.equal(rejected.status, 201);
    rejectedArticleId = rejected.data.id;
    assert.equal(
      (
        await adminCall('/admin/articles/' + rejectedArticleId + '/review', 'PUT', {
          decision: 'reject',
        })
      ).status,
      200,
    );
    assert.equal((await guest('/articles/' + rejectedArticleId)).status, 404);
    assert.equal((await other('/articles/' + rejectedArticleId)).data.status, 'rejected');
    await pool.query("UPDATE users SET role='reporter' WHERE id=$1", [ids[0]]);
  });
  await t.test('publishing is persistent and sanitizes markup', async () => {
    const r = await owner('/articles', 'POST', {
      title: `Integration story ${suffix}`,
      description: 'Database persistence',
      content: '<p>Safe content</p><script>alert(1)</script><img src=x onerror=alert(1)>',
      category_id: 3,
      district: 'Udupi',
      status: 'published',
    });
    assert.equal(r.status, 201);
    articleId = r.data.id;
    assert.equal(r.data.status, 'pending');
    assert.equal((await guest('/articles/' + articleId)).status, 404);
    assert.equal(
      (await adminCall('/admin/articles/' + articleId + '/review', 'PUT', { decision: 'approve' }))
        .status,
      200,
    );
    const a = (await guest('/articles/' + articleId)).data;
    assert.equal(a.title, `Integration story ${suffix}`);
    assert.doesNotMatch(a.content, /<script|onerror/);
    assert.equal(
      (await pool.query('SELECT title FROM articles WHERE id=$1', [articleId])).rows[0].title,
      a.title,
    );
    const edited = await owner('/articles/' + articleId, 'PUT', {
      title: `Edited integration story ${suffix}`,
      description: 'An edit must be reviewed again',
      content: 'Updated safe story',
      category_id: 3,
      status: 'published',
    });
    assert.equal(edited.status, 200);
    assert.equal(edited.data.status, 'pending');
    assert.equal((await guest('/articles/' + articleId)).status, 404);
    assert.equal(
      (
        await adminCall('/admin/articles/' + articleId + '/review', 'PUT', {
          decision: 'approve',
        })
      ).status,
      200,
    );
  });
  await t.test('ownership, foreign origins and invalid input are rejected', async () => {
    assert.equal((await other('/articles/' + articleId, 'DELETE')).status, 403);
    assert.equal(
      (await owner('/articles', 'POST', {}, { Origin: 'https://untrusted.example' })).status,
      403,
    );
    assert.equal((await guest('/articles/invalid')).status, 400);
    assert.equal((await owner('/articles', 'POST', { title: 'Bad' })).status, 400);
  });
  await t.test('bookmark and like PUTs are idempotent and stored in PostgreSQL', async () => {
    for (let i = 0; i < 2; i++) {
      assert.equal(
        (await other('/articles/' + articleId + '/bookmark', 'PUT', { active: true })).status,
        200,
      );
      await other('/articles/' + articleId + '/like', 'PUT', { active: true });
    }
    const a = (await other('/articles/' + articleId)).data;
    assert.equal(a.bookmarked, true);
    assert.equal(a.likes, 1);
    assert.equal(
      (
        await pool.query('SELECT count(*)::int AS n FROM bookmarks WHERE article_id=$1', [
          articleId,
        ])
      ).rows[0].n,
      1,
    );
    await other('/articles/' + articleId + '/bookmark', 'PUT', { active: false });
    assert.equal((await other('/articles/' + articleId)).data.bookmarked, false);
  });
  await t.test('comments use authenticated identity and complaints stay private', async () => {
    const c = await other('/articles/' + articleId + '/comments', 'POST', {
      body: 'A reader perspective',
      user_name: 'forged editor',
    });
    assert.equal(c.status, 201);
    const a = (await guest('/articles/' + articleId)).data;
    assert.equal(a.comments[0].full_name, 'other');
    const r = await other('/complaints', 'POST', {
      title: 'Street lighting',
      description: 'The lamp needs attention',
      location: 'Udupi',
      pincode: '576101',
    });
    assert.equal(r.status, 201);
    assert.equal(
      (await owner('/complaints')).data.some((c) => c.id === r.data.id),
      false,
    );
  });
  await t.test('channel creation, follow and publishing enforce ownership', async () => {
    const c = await owner('/channels', 'POST', { name: 'Integration channel', bio: 'Test' });
    channelId = c.data.id;
    assert.equal(c.status, 201);
    assert.equal(
      (await other('/channels/' + channelId + '/follow', 'PUT', { active: true })).status,
      200,
    );
    assert.equal(
      (await other('/channels/' + channelId + '/posts', 'POST', { body: 'Unauthorized' })).status,
      403,
    );
    assert.equal(
      (await owner('/channels/' + channelId + '/posts', 'POST', { body: 'Local update' })).status,
      201,
    );
    assert.equal((await guest('/channels/' + channelId)).data.posts[0].body, 'Local update');
  });
  await t.test('lead pages persist signups and expose them only to their owner', async () => {
    const r = await owner('/resources/templates', 'POST', {
      title: 'Integration lead page',
      data: { description: 'Join us', cta: 'Join' },
    });
    assert.equal(r.status, 201);
    templateId = r.data.id;
    assert.equal(
      (
        await guest('/lead-pages/' + templateId + '/leads', 'POST', {
          name: 'Test person',
          email: 'test@example.test',
        })
      ).status,
      201,
    );
    assert.equal(
      (await owner('/analytics')).data.leads.some((l) => l.resource_id === templateId),
      true,
    );
    assert.equal((await other('/analytics')).status, 403);
  });
  await t.test('scheduled and draft stories are private until published', async () => {
    const body = {
      title: 'Scheduled test',
      content: 'Future story',
      category_id: 3,
      status: 'scheduled',
      published_at: new Date(Date.now() + 86400000).toISOString(),
    };
    assert.equal((await adminCall('/articles/' + articleId, 'PUT', body)).status, 200);
    assert.equal((await guest('/articles/' + articleId)).status, 404);
    assert.equal((await owner('/articles/' + articleId)).status, 200);
    assert.equal(
      (await other('/articles/' + articleId + '/bookmark', 'PUT', { active: true })).status,
      404,
    );
  });
  await t.test(
    'community posts, follows, saves and replies persist with ownership checks',
    async () => {
      const p = await other('/community', 'POST', {
        body: 'A neighbourhood update',
        pincode: '576101',
      });
      assert.equal(p.status, 201);
      const id = p.data.id;
      assert.equal((await owner('/community/' + id, 'DELETE')).status, 404);
      await owner('/community/' + id + '/like', 'PUT', { active: true });
      await owner('/community/' + id + '/save', 'PUT', { active: true });
      await owner('/community/people/' + ids[1] + '/follow', 'PUT', { active: true });
      const feed = (await owner('/community?mode=following&pincode=576101')).data;
      assert.equal(
        feed.posts.some((p) => p.id === id && p.liked && p.saved),
        true,
      );
      await owner('/community/' + id + '/comments', 'POST', { body: 'A helpful reply' });
      assert.equal((await guest('/community/' + id + '/comments')).data[0].full_name, 'owner');
      assert.equal((await other('/community/' + id, 'DELETE')).status, 200);
    },
  );
  await t.test('logout invalidates the session', async () => {
    assert.equal((await other('/auth/logout', 'POST')).status, 200);
    assert.equal((await other('/auth/session')).data.user, null);
  });
});
