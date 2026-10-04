const fs = require('node:fs');
const path = require('node:path');
const { pool } = require('../server/database');
async function migrate() {
  const client = await pool.connect();
  try {
    await client.query('BEGIN');
    await client.query(fs.readFileSync(path.join(__dirname, '../database/schema.sql'), 'utf8'));
    await client.query('COMMIT');
  } catch (err) {
    await client.query('ROLLBACK');
    throw err;
  } finally {
    client.release();
  }
}
if (require.main === module)
  migrate()
    .then(() => console.log('PostgreSQL schema ready.'))
    .catch((e) => {
      console.error(e.message);
      process.exitCode = 1;
    })
    .finally(() => pool.end());
module.exports = migrate;
