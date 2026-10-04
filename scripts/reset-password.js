// Operator-only account recovery. Never print or accept passwords as CLI arguments.
const bcrypt = require('bcryptjs');
const { pool } = require('../server/database');
(async () => {
  const email = process.env.RESET_EMAIL,
    password = process.env.RESET_PASSWORD;
  if (!email || !password || password.length < 10)
    throw Error('Set RESET_EMAIL and RESET_PASSWORD (10+ characters) in your environment.');
  const r = await pool.query('UPDATE users SET password=$1 WHERE lower(email)=$2 RETURNING id', [
    await bcrypt.hash(password, 12),
    email.toLowerCase(),
  ]);
  if (!r.rowCount) throw Error('Account not found.');
  await pool.query("DELETE FROM session WHERE sess->>'userId'=$1", [String(r.rows[0].id)]);
  console.log('Password updated; existing sessions invalidated.');
})()
  .catch((e) => {
    console.error(e.message);
    process.exitCode = 1;
  })
  .finally(() => pool.end());
