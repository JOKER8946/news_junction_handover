require('dotenv').config();
const { Pool } = require('pg');
const pool = new Pool({
  connectionString:
    process.env.DATABASE_URL ||
    'postgresql://newsjunction:newsjunction@127.0.0.1:5432/newsjunction',
  connectionTimeoutMillis: 15000,
  max: 10,
});
pool.on('error', (err) => console.error('PostgreSQL connection error:', err.message));
module.exports = { pool, query: (text, params) => pool.query(text, params) };
