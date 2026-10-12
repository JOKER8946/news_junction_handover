const crypto = require('node:crypto');
const { rateLimit } = require('express-rate-limit');
const { query } = require('./database');

function configuration(env = process.env) {
  const production = env.NODE_ENV === 'production';
  const origin = env.APP_ORIGIN || 'http://localhost:3000';
  const url = new URL(origin);
  if (url.origin !== origin || url.username || url.password) throw Error('APP_ORIGIN must be an origin without a path or credentials.');
  if (production) {
    if (!env.SESSION_SECRET || env.SESSION_SECRET.length < 48 || /replace|example|changeme/i.test(env.SESSION_SECRET)) throw Error('Production requires a random SESSION_SECRET of at least 48 characters.');
    if (url.protocol !== 'https:') throw Error('Production APP_ORIGIN must use HTTPS.');
    if (env.COOKIE_SECURE !== 'true') throw Error('Production requires COOKIE_SECURE=true.');
    if (!env.DATABASE_URL) throw Error('Production requires DATABASE_URL.');
    const db = new URL(env.DATABASE_URL);
    if(!['db','localhost','127.0.0.1'].includes(db.hostname) && db.searchParams.get('sslmode')!=='verify-full')throw Error('Remote production databases require sslmode=verify-full.');
    if (!db.password || /^(newsjunction|postgres|password|changeme)$/i.test(decodeURIComponent(db.password))) throw Error('Production requires a non-default database password.');
    if (env.DEBUG || env.NODE_TLS_REJECT_UNAUTHORIZED === '0') throw Error('Debug logging and disabled TLS verification are forbidden in production.');
  }
  return { production, origin, secure: production || env.COOKIE_SECURE === 'true' };
}

// Shared, atomic counters survive restarts and are shared by all app instances.
class PgRateStore {
  constructor(scope) { this.prefix = scope; }
  init(options) { this.windowMs = options.windowMs; }
  async increment(key) {
    const bucket = this.prefix + ':' + crypto.createHash('sha256').update(key).digest('hex');
    const result = await query(`INSERT INTO security_rate_limits(bucket,hits,expires_at)
      VALUES($1,1,clock_timestamp()+$2*interval '1 millisecond')
      ON CONFLICT(bucket) DO UPDATE SET
       hits=CASE WHEN security_rate_limits.expires_at<=clock_timestamp() THEN 1 ELSE security_rate_limits.hits+1 END,
       expires_at=CASE WHEN security_rate_limits.expires_at<=clock_timestamp() THEN clock_timestamp()+$2*interval '1 millisecond' ELSE security_rate_limits.expires_at END
      RETURNING hits,expires_at`, [bucket,this.windowMs]);
    return {totalHits:result.rows[0].hits,resetTime:result.rows[0].expires_at};
  }
  async decrement(key) {
    await query('UPDATE security_rate_limits SET hits=GREATEST(0,hits-1) WHERE bucket=$1',[this.prefix+':'+crypto.createHash('sha256').update(key).digest('hex')]);
  }
  async resetKey(key) { await query('DELETE FROM security_rate_limits WHERE bucket=$1',[this.prefix+':'+crypto.createHash('sha256').update(key).digest('hex')]); }
}
function limiter(scope, limit, windowMs, extra={}) {
  return rateLimit({windowMs,limit,store:new PgRateStore(scope),standardHeaders:'draft-8',legacyHeaders:false,
    message:{error:'Too many requests. Please try again later.'},...extra});
}
function safeUrl(value) {
  if (!value) return true;
  if (typeof value !== 'string' || /[\x00-\x20\\]/.test(value)) return false;
  if (/^\/media\//.test(value)) return !value.includes('..');
  try { const url=new URL(value); return url.protocol === 'https:' && !url.username && !url.password; } catch { return false; }
}
function redactSecrets(value) {
  return value.replace(/(?:AKIA[0-9A-Z]{16}|AIza[0-9A-Za-z_-]{35}|gh[pousr]_[0-9A-Za-z]{30,}|sk_(?:live|test)_[0-9A-Za-z]{20,}|sk-proj-[0-9A-Za-z_-]{30,}|xox[baprs]-[0-9A-Za-z-]{20,})/g,'[REDACTED]')
    .replace(/-----BEGIN (?:RSA |EC |OPENSSH )?PRIVATE KEY-----[\s\S]*?-----END (?:RSA |EC |OPENSSH )?PRIVATE KEY-----/g,'[REDACTED PRIVATE KEY]');
}
module.exports={configuration,limiter,safeUrl,PgRateStore,redactSecrets};
