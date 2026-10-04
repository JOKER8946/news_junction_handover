require('dotenv').config();
const app = require('./server/app');
const { pool } = require('./server/database');
async function start() {
  await pool.query('SELECT 1 FROM users LIMIT 1');
  const server = app.listen(process.env.PORT || 3000, () =>
    console.log('News Junction ready at http://localhost:' + (process.env.PORT || 3000)),
  );
  for (const signal of ['SIGINT', 'SIGTERM'])
    process.on(signal, () =>
      server.close(async () => {
        await pool.end();
        process.exit(0);
      }),
    );
}
start().catch((err) => {
  console.error('Startup failed. Check DATABASE_URL and run npm run db:migrate.', err.message);
  process.exit(1);
});
