const crypto = require('crypto');
const bcrypt = require('bcryptjs');

const SECRET_KEY = 'knoblyCream@2020';
const SECRET_IV = 'my_simple_secret_iv';

/**
 * Exact replica of PHP's simpleEncDec() function
 * Used for tokens, password reset links, and user session tokens.
 */
function simpleEncDec(string, action = 'e') {
  if (string === null || string === undefined || string === '') return '';
  const keyStr = crypto.createHash('sha256').update(SECRET_KEY).digest('hex').slice(0, 32);
  const ivStr = crypto.createHash('sha256').update(SECRET_IV).digest('hex').slice(0, 16);
  const key = Buffer.from(keyStr, 'utf8');
  const iv = Buffer.from(ivStr, 'utf8');

  try {
    if (action === 'e') {
      const cipher = crypto.createCipheriv('aes-256-cbc', key, iv);
      let encrypted = cipher.update(String(string), 'utf8', 'base64');
      encrypted += cipher.final('base64');
      return Buffer.from(encrypted, 'utf8').toString('base64');
    } else if (action === 'd') {
      let innerBase64;
      try {
        innerBase64 = Buffer.from(String(string), 'base64').toString('utf8');
      } catch {
        innerBase64 = String(string);
      }
      const decipher = crypto.createDecipheriv('aes-256-cbc', key, iv);
      let decrypted = decipher.update(innerBase64, 'base64', 'utf8');
      decrypted += decipher.final('utf8');
      return decrypted;
    }
  } catch (err) {
    console.error('simpleEncDec error:', err.message);
    return '';
  }
  return '';
}

/**
 * Compatible password verifier matching app/inc/php/nj_password.php
 * Supports bcrypt hashes ($2y$, $2b$, $2a$) as well as legacy plaintext.
 */
function verifyPassword(input, stored) {
  if (typeof input !== 'string' || !input) return false;
  if (typeof stored !== 'string' || !stored) return false;

  // Check if bcrypt hash
  if (/^\$2[ayb]\$/.test(stored)) {
    const compatHash = stored.replace(/^\$2y\$/, '$2a$');
    return bcrypt.compareSync(input, compatHash);
  }

  // Legacy plaintext match
  return input === stored;
}

/**
 * Generate password hash using bcrypt
 */
function hashPassword(plain) {
  return bcrypt.hashSync(plain, 10);
}

module.exports = {
  simpleEncDec,
  verifyPassword,
  hashPassword
};
