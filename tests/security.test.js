const {test,after}=require('node:test');
const assert=require('node:assert/strict');
const express=require('express');
const {configuration,safeUrl,limiter}=require('../server/security');
const {publicAddress}=require('../server/feed-fetch');
const {pool}=require('../server/database');
after(async()=>{await require('../server/prisma').$disconnect();await pool.end();});
test('production fails closed for insecure secrets, transport, cookies and debug settings',()=>{
 const good={NODE_ENV:'production',APP_ORIGIN:'https://news.example.test',SESSION_SECRET:'a'.repeat(64),COOKIE_SECURE:'true',DATABASE_URL:'postgresql://runtime:random-long-password@db/app'};
 assert.equal(configuration(good).secure,true);
 for(const bad of [{SESSION_SECRET:'replace-with-secret'},{APP_ORIGIN:'http://news.example.test'},{COOKIE_SECURE:'false'},{DATABASE_URL:'postgresql://user:postgres@db/app'},{DEBUG:'*'},{NODE_TLS_REJECT_UNAUTHORIZED:'0'},{DATABASE_URL:'postgresql://user:long-secret@remote.example/app'}])assert.throws(()=>configuration({...good,...bad}));
});
test('URL validation and feed egress reject script URLs, credentials and private networks',()=>{
 assert.equal(require('../server/security').redactSecrets('Example '+'AIza'+'x'.repeat(35)),'Example [REDACTED]');
 for(const value of ['javascript:alert(1)','data:text/html,hello','//evil.test','https://user:pass@example.test','/media/../secret','https://example.test\\@evil.test'])assert.equal(safeUrl(value),false,value);
 assert.equal(safeUrl('https://example.test/image.png'),true);
 assert.equal(safeUrl('/media/uploads/image.png'),true);
 for(const ip of ['127.0.0.1','10.0.0.1','169.254.169.254','172.16.0.1','192.168.1.1','100.64.0.1','::1'])assert.equal(publicAddress(ip),false);
 assert.equal(publicAddress('8.8.8.8'),true);
});
test('shared rate limit cannot be bypassed by switching instances',async()=>{
 const scope='test-'+Date.now();const servers=[];
 try{
  for(let n=0;n<2;n++){const app=express();app.use(limiter(scope,2,60000));app.get('/',(req,res)=>res.json({ok:true}));const server=app.listen(0);await new Promise(r=>server.once('listening',r));servers.push(server);}
  const get=async n=>fetch('http://127.0.0.1:'+servers[n].address().port);
  assert.equal((await get(0)).status,200);assert.equal((await get(1)).status,200);const blocked=await get(0);assert.equal(blocked.status,429);assert.ok(blocked.headers.get('retry-after'));
 }finally{for(const server of servers)await new Promise(r=>server.close(r));await pool.query('DELETE FROM security_rate_limits WHERE bucket LIKE $1',[scope+':%']);}
});
test('HTTP headers, CORS, CSRF, private routes, invalid input and sensitive files',async()=>{
 const app=require('../server/app'),server=app.listen(0);await new Promise(r=>server.once('listening',r));const base='http://127.0.0.1:'+server.address().port;
 try{
  const session=await fetch(base+'/api/auth/session');assert.match(session.headers.get('set-cookie'),/HttpOnly/i);assert.match(session.headers.get('set-cookie'),/SameSite=Lax/i);assert.equal(session.headers.get('cache-control'),'private, no-store');assert.equal(session.headers.get('x-content-type-options'),'nosniff');assert.match(session.headers.get('content-security-policy'),/frame-ancestors 'none'/);assert.equal(session.headers.get('x-powered-by'),null);
  const data=await session.json(),cookie=session.headers.get('set-cookie').split(';')[0];
  for(const url of ['/api/admin/users','/api/analytics'])assert.equal((await fetch(base+url)).status,401);
  assert.equal((await fetch(base+'/admin',{redirect:'manual'})).status,302);
  assert.equal((await fetch(base+'/api/community?mode=saved')).status,401);
  assert.equal((await fetch(base+'/api/articles/1%20OR%201=1')).status,400);
  assert.equal((await fetch(base+'/api/auth/login',{method:'POST',headers:{'Content-Type':'application/json',cookie,Origin:'https://evil.example','X-CSRF-Token':data.csrf},body:'{}'})).status,403);
  assert.equal((await fetch(base+'/api/auth/login',{method:'POST',headers:{'Content-Type':'application/json',cookie},body:'{}'})).status,403);
  const cross=await fetch(base+'/api/auth/session',{headers:{Origin:'https://evil.example'}});assert.equal(cross.headers.get('access-control-allow-origin'),null);
  const malformed=await fetch(base+'/api/auth/login',{method:'POST',headers:{'Content-Type':'application/json'},body:'{bad'});assert.equal(malformed.status,400);assert.equal((await malformed.json()).error,'Invalid JSON body.');
  for(const url of ['/legacy-media/social_media/facebook/fb_credentials.json','/media/uploads/.reels/not-a-file.mp4'])assert.equal((await fetch(base+url)).status,404);
 }finally{await new Promise(r=>server.close(r));}
});
