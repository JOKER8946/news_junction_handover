require('dotenv').config();
const app = require('./server/app');
const prisma = require('./server/prisma');
const { pool } = require('./server/database');
async function start() {
  if(process.env.NODE_ENV==='production'){
    const role=(await pool.query('SELECT rolsuper,rolcreatedb,rolcreaterole,rolbypassrls FROM pg_roles WHERE rolname=current_user')).rows[0];
    const owns=(await pool.query("SELECT count(*)::int AS n FROM pg_tables WHERE schemaname='public' AND tableowner=current_user")).rows[0].n;
    if(!role||Object.values(role).some(Boolean)||owns)throw Error('Production requires a restricted non-owner database role.');
  }
  await prisma.$queryRaw`SELECT 1 FROM users LIMIT 1`;
  const server = app.listen(process.env.PORT || 3000, () =>
    console.log('News Junction ready at http://localhost:' + (process.env.PORT || 3000)),
  );
  server.requestTimeout=120000;
  server.headersTimeout=15000;
  server.keepAliveTimeout=5000;
  server.maxRequestsPerSocket=1000;
  const cleanup=setInterval(()=>pool.query('DELETE FROM security_rate_limits WHERE expires_at < now()').catch(()=>{}),3600000);
  cleanup.unref();
  for (const signal of ['SIGINT', 'SIGTERM'])
    process.on(signal, () =>
      server.close(async () => {
        await prisma.$disconnect();
        await pool.end();
        process.exit(0);
      }),
    );
}
start().catch((err) => {
  console.error('Startup failed. Check DATABASE_URL and run npm run db:migrate.', err.code || err.name);
  process.exit(1);
});
