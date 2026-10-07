const fs = require('node:fs');
const path = require('node:path');
const prisma = require('../server/prisma');
const { pool } = require('../server/database');

async function migrate() {
  // Run the legacy SQL schema to ensure all tables, indexes, and constraints exist.
  // This is safe to run repeatedly because every statement uses IF NOT EXISTS / IF NOT EXISTS.
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

  // Verify Prisma can connect (validates DATABASE_URL and generated client)
  await prisma.$queryRaw`SELECT 1`;
  await prisma.$disconnect();
}

if (require.main === module)
  migrate()
    .then(() => console.log('PostgreSQL schema ready (SQL + Prisma validated).'))
    .catch((e) => {
      console.error(e.message);
      process.exitCode = 1;
    })
    .finally(() => pool.end());

module.exports = migrate;
