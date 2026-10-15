# News Junction

A React 19 application with an Express API, Prisma ORM, and PostgreSQL storage. No PHP runtime or MySQL driver is used. The previous PHP source and the replaced static HTML/JavaScript application have been removed. The original database backups remain unchanged.

The UI follows the supplied reference PDFs and the original live site: red/blue public landing page, original logo, orange split-screen login, and a white Social/Reader sidebar with the original navigation labels. The React, Express, Prisma ORM, and PostgreSQL implementation remains in place.

## Run locally

Requirements: Node.js 22.12+ (Node 24 recommended), npm, and PostgreSQL 17. Docker Compose can supply PostgreSQL.

```sh
npm ci
# On a fresh checkout, copy .env.example to .env and choose your own secrets.
docker compose up -d db
npm run db:migrate
npm run db:seed
npm run build
npm start
```

Open **http://localhost:3000**. On Windows with restricted PowerShell script execution, use `npm.cmd` instead of `npm`.

### Prisma ORM

This application uses Prisma Client (`@prisma/client`) to query the PostgreSQL database. The Prisma schema is located at `prisma/schema.prisma`. 

* The `npm ci` step automatically runs `npm run prisma:generate` (via `postinstall`) to generate the local Prisma Client.
* **Important:** A raw `pg` database pool (`server/database.js`) is retained alongside Prisma. It is strictly used for the `connect-pg-simple` session store and for the legacy data import script (`npm run db:import`), which requires efficient `jsonb_to_recordset` queries that Prisma does not support well.
* Prisma represents Postgres `bigint` columns as JavaScript `BigInt` objects. The application includes a `BigInt.prototype.toJSON` polyfill in `server/prisma.js` so these values serialize safely in Express JSON responses.
* You can browse the database interactively by running `npm run prisma:studio`.

In this working directory, `.env` now selects `newsjunction_migration_check`, which contains the converted original backups. Your supplied existing account authenticates with its preserved bcrypt hash. A separate local editor is configured in the untracked `.env`. The earlier six-story sample database is still available as `newsjunction`; neither database is the live hosted database.

`db:seed` creates a local editor from `SEED_ADMIN_EMAIL` and `SEED_ADMIN_PASSWORD`. It adds six explicitly example stories only when the articles table is empty. These examples came from the previous Node prototype; they are not a live news feed. Do not seed a production database with example content. The untracked `.env` created during this migration has a random local editor password; the live website password is not included in source or example configuration.

For development, run `npm run dev` and open **http://localhost:5173**. Vite proxies `/api`, `/media`, and `/legacy-media` to Express on port 3000.

## Implemented workflows

- News search, category and district filters, pagination, story details, original-source links, saved stories, likes and comments.
- Account registration/sign-in/sign-out, PostgreSQL-backed sessions, profile updates and password changes.
- Any registered user can submit and edit their own articles. Non-admin submissions and edits stay pending until an administrator approves them; users can track pending, rejected and published articles in **My Articles**. Administrators can also publish directly. Scheduled stories become public at their publication time without requiring a worker.
- Floating WhatsApp contact button across the site, linked to News Junction at +91 74117 42999.
- Citizen Connect: public neighbourhood posts, PIN-code filtering, following people, saved posts, likes and replies.
- Channels, follows, owner-authored updates, newspaper and magazine resource directories, and community voice profiles.
- Private civic reports, administrator status changes and responses.
- Author analytics, shareable lead pages, lead capture and newsletter signup storage.
- Administrator roles and advertisement management.
- Existing local videos, informational pages and market-exchange links.
- Operator-run RSS refresh with duplicate URL protection: `npm run feeds:sync`.

Reader accounts can publish community posts and interact with news. Only reporters and administrators can publish news stories. Administrators manage account roles; users cannot grant themselves privileges. The new site does not accept the old client-editable identity cookie.

## Database migration

The original `.sql.gz` files are MySQL dumps, not PostgreSQL scripts. Do not run them directly against PostgreSQL.

The Node importer parses SQL values without executing the source SQL. It first preserves **every row** in the private `legacy_records` table, including unconverted integration data. Repeated archival imports update the same source records, and duplicate legacy rows are retained separately. Each file is imported in a transaction. *(Note: The import script continues to use raw `pg` queries rather than Prisma to take advantage of bulk `jsonb_to_recordset` operations for performance).*

```sh
# Point DATABASE_URL to a new PostgreSQL database before starting.
npm run db:migrate
npm run db:import -- database/cream.sql.gz database/reader.sql.gz database/nj_gallery.sql.gz database/nj_mailer.sql.gz database/manikya_market.sql.gz database/nj_cream.sql.gz database/nj_reader.sql.gz
npm run db:import -- --promote
```

Promotion requires empty application `users` and `articles` tables and runs in one transaction. It preserves account ownership relationships while assigning new PostgreSQL IDs. `nj_cream.sql.gz` supplies accounts/authored articles, and `nj_reader.sql.gz` supplies syndicated articles and social data. Override those filenames with `LEGACY_ACCOUNT_SOURCE` and `LEGACY_READER_SOURCE` when testing alternate dumps.

Promotion covers accounts, categories, authored and RSS articles, source URLs, publishers, channels, channel updates, public community posts, user follows, community likes, article bookmarks/likes/comments, civic reports, magazines, advertisements and RSS feed sources. Missing/deleted users and private/deleted posts are not exposed publicly; their original records remain archived. Invalid/missing dates are kept out of the latest-news order instead of being assigned today's date. Legacy timestamps without a zone are interpreted as India time.

The supplied backups were checked in an isolated database, separate from the initial sample preview. The archived data includes records for other applications and third-party integrations; archival does not make those integrations active in the new news application.

### Existing passwords

Bcrypt hashes remain usable, including the `$2y$` prefix. Other legacy password formats are preserved in the archive but do not authenticate through insecure plaintext or reversible-password fallbacks. An operator can reset an imported account using environment variables:

```sh
# Set RESET_EMAIL and RESET_PASSWORD in your shell, then:
node scripts/reset-password.js
```

The reset script hashes the replacement and invalidates all sessions for that account. It does not print the password. Production email-based account recovery and Google OAuth require their provider configuration and are not enabled.

### Existing media

Relative legacy image/video/PDF paths resolve through `/legacy-media/`, which serves only allowed media extensions from `LEGACY_MEDIA_ROOT` (defaults to `app/`). HTML, JavaScript, configuration files and backups are not served by that route. Keep the original media directories when moving to a new host. External publisher images remain external; a local illustrated fallback appears when one fails.

New uploads are checked by file signature, limited to JPEG/PNG/WebP and 8 MB, and saved under random filenames in `public/media/uploads`. Back up this directory together with PostgreSQL. Docker uses a persistent uploads volume and a read-only mount for the legacy media tree.

## RSS and external services

Feed definitions live in `feed_sources`; imported feeds keep their publisher/category mappings. Only configure trusted feeds that you have permission to display. Schedule `npm run feeds:sync` using your host's scheduler. Each feed records its last successful refresh or failure, and response size/time limits apply. The application never invents live stock prices; market links open the exchanges.

Newsletter signups and leads are stored in PostgreSQL. Email delivery, payment processing, Google sign-in, push notifications, social-network publishing and the separate Manikya marketplace are not wired to providers in this rebuild. Their old records are retained for follow-up migration; no old PHP service is run. Advertisement links are managed in the administration screen.

## Verification

```sh
npm test
npm run build
npm run test:e2e
```

API tests run against `DATABASE_URL`, create uniquely named accounts and records, and clean up their own data. Use a development database. They cover PostgreSQL persistence, CSRF/origin checks, forged-cookie rejection, role/ownership enforcement, publishing and scheduling, idempotent bookmarks/likes, comments, civic-report privacy, channels, lead capture, community posts/follows/replies, and session invalidation. Parser tests cover Unicode, escapes, NULLs, invalid SQL tuples and legacy dates.

Browser tests use installed Google Chrome, the local editor credentials from `.env`, and the production build. They cover desktop/mobile navigation, search, reading, login, saving, draft publishing/deletion, community posting/replies and upload validation. Screenshots are written to `tmp/`. To use Playwright's Chromium instead, install it with `npx playwright install chromium` and remove `channel: 'chrome'` from `playwright.config.js`.

`npm run format` formats the maintained JavaScript/React source. The SQL dumps and historical assets are excluded.

## Production deployment

This repository includes a multi-stage Docker build and Compose configuration. The Node container serves the built React application and the API on port 3000.

1. Set a private PostgreSQL connection and a random `SESSION_SECRET` (at least 32 characters). Use a strong `POSTGRES_PASSWORD` for Compose.
2. Set `APP_ORIGIN` to the exact public HTTPS origin, `COOKIE_SECURE=true`, and `TRUST_PROXY=1` only when running behind one trusted reverse proxy.
3. Import and verify a fresh production database. Bring across the legacy media directories and uploaded images.
4. Build with `npm run build`, migrate with `npm run db:migrate`, and start with `npm start`; or use `docker compose --profile production up -d --build`.
5. Configure the host's reverse proxy, HTTPS, database/upload backups and RSS schedule. Check `/api/health` and smoke-test sign-in, publishing and media before changing DNS.

Do not use the example database password or a placeholder session secret on a public server. Database connection failures stop startup; there is no in-memory fallback.

**The existing newsjunction.net deployment has not been replaced.** Website sign-in credentials do not provide hosting/SSH/DNS access. The repository, local PostgreSQL migration, build and deployment files are ready for hosting access to be connected.

## Layout

- `src/`: React pages, shared components, API client and responsive styling.
- `server/`: Express API, Prisma singleton (`prisma.js`), PostgreSQL access, and community endpoints.
- `prisma/`: Prisma schema mapping the PostgreSQL tables.
- `database/schema.sql`: PostgreSQL schema and indexes (applied by `db:migrate`).
- `scripts/`: schema migration, demo seeding, SQL-dump import, password recovery and RSS refresh.
- `public/media/`: bundled visual assets and video; uploads are ignored by Git.
- `app/`: retained legacy non-PHP files/media, never used as an application runtime.
- `tests/`: API, parser and Playwright browser tests.
- `reference_images/`: the supplied design-reference PDFs.

## Reels

Open `/reels` from the sidebar to watch published reels in a vertical, scroll-snap player. Videos play muted while visible, pause off screen, and support play/pause, sound, likes, and direct sharing links. Visitors can watch; liking requires login.

Admins can open **Administration > Reels** (`/admin?tab=reels`) to upload a video, add a title and caption, publish immediately or save a draft, edit visibility/details, and delete reels. MP4 and WebM files up to 100 MB are accepted; vertical 9:16 is recommended. Videos are served as uploaded, without transcoding; use browser-compatible codecs (such as H.264 MP4).

Run `npm run db:migrate` when updating an existing installation. Reel metadata and likes are in PostgreSQL. Video files are stored under `public/media/uploads/.reels`, within the existing Docker uploads volume. They are served through an access-checked API (with byte-range support), not the public static route; drafts are admin-only. Include both the database and uploads volume in backups. Configure any production reverse proxy to allow 100 MB uploads plus multipart overhead.

## Security audit ? 12 October 2026

This audit covers the checked-out React/Express/PostgreSQL application, reachable local Git history, local backup files, environment settings, and deployment configuration. It is not a penetration-test certification. The live PHP host is a separate deployment; local changes do not update that host.

### Findings requiring operator action

- **Rotate/revoke potentially exposed provider credentials.** The redacted history scan found provider-token patterns in deleted `app/genai/genai_function.php`, `app/genai/zgenai_function.php`, and `app/texttovoice/processvoice.php`, plus a possible credential assignment in `app/test.php`. Six provider-token matches also occur in the `user_collection` data of the private `database/nj_cream.sql.gz` backup. These are potential credentials, not verified active keys; their values were not printed or used. Rotation must happen in the owning provider accounts. Removing current files does not revoke historical keys.
- **Repository history and remote LFS remain sensitive.** Seven compressed database backups and the marketplace dump have been removed from the Git index and ignored, with local copies preserved. Previously pushed commits, LFS objects, forks, and caches remain accessible until an administrator coordinates history/LFS cleanup. No shared history was rewritten or force-pushed. Keep the repository private until this is resolved.
- **Provider billing cap is not configured.** No active paid API integration was found in this Node application, and no billing-account access was available. A dollar cap cannot be enforced by an unused environment variable. Set a hard provider/project spending limit and usage alerts in each provider account; rotate legacy keys first. The implemented 2 GiB/month upload allowance and request limits control application resources, not hosting, bandwidth, or third-party invoices.
- **Live HTTPS cutover is pending.** `Caddyfile` and `compose.production.yaml` configure automatic HTTPS after the domain points to the deployment server and ports 80/443 are available. No certificate issuance, DNS change, or live deployment is claimed. Local development remains HTTP on localhost.
- **Windows file ACLs require administrator attention.** `.env` and backup files inherit access for ordinary local Users/Authenticated Users. Attempts to remove broad access were denied by Windows even outside the sandbox. Restrict these files to the owning user, administrators, SYSTEM, and explicitly authorized tooling using an administrator session. Git ignore rules and HTTP access restrictions do not replace filesystem permissions.

### Checks and protections

| Area | Result / implemented protection |
| --- | --- |
| API keys and environment variables | No provider-key patterns found in active `src`, `server`, or built client files. No public-prefix environment variables were configured. `.env` is ignored and not served; its local session secret is 96 characters. Secrets are excluded from Docker build context. Known provider-token/private-key formats are redacted from JSON responses as defense in depth. Heuristic scans can miss unknown formats. |
| Git history | 25,234 reachable text blobs and seven local compressed backups scanned without printing values; 440 binary blobs skipped. Git contained LFS pointers, so available working-copy backups were scanned separately. Vendor test/example key fixtures are not evidence of live credentials. `scripts/security-scan.py` writes a redacted report to ignored `tmp/security-history-scan.json`. |
| Authentication | Passwords are bcrypt hashed; login errors are generic. Registration always creates a reader. Session IDs and CSRF tokens rotate on login. Password changes and operator resets invalidate other/all sessions. New passwords are limited to 72 UTF-8 bytes to prevent silent bcrypt truncation. |
| Admin and user permissions | Admin API endpoints, article review, reel management, and direct `/admin` page requests require server-side authorization. Article edits verify ownership; non-admin submissions and edits stay hidden pending administrator approval. Channel/resource mutations verify role and ownership. Complaints and leads are scoped to the owner/admin. Role changes revoke the affected user's sessions. Client navigation visibility is not the security boundary. |
| Input validation / SQL injection | Zod validates payload types, lengths, roles and states. Numeric route identifiers are checked before database conversion. Application queries use Prisma parameters or PostgreSQL placeholders; fixed SQL fragments are not built from user input. Invalid JSON/IDs return generic 400 responses. |
| XSS | Article HTML is sanitized on write and detail read; other text is escaped by React. URL inputs reject script/data schemes, embedded credentials, control characters, and backslashes. CSP disallows inline scripts, plugins, framing and base-tag injection. Inline styles remain allowed for existing UI styling; no user-provided CSS is accepted. Legacy SVG files are no longer publicly served. |
| Database rules | Foreign keys, uniqueness constraints, allowed roles/statuses and ownership checks were reviewed. `database/runtime-role.sql` grants a non-owner runtime role only application DML and sequence access, excluding archived legacy records/feed administration. Production refuses superuser, role-creation, database-creation, RLS-bypass, or table-owner connections. Row-level security is not enabled: this is a server-only database with authorization in Express, not a browser-accessible database. |
| Rate limits | PostgreSQL counters are shared across restarts/instances: API 600/minute per IP; writes 120/15 minutes; sensitive actions 30/15 minutes; login 20 failed attempts/15 minutes per normalized email; reel uploads 20/hour per IP. Successful login attempts are removed from the account counter. Expired counters are cleaned hourly by the server. Database failures do not silently bypass limits. |
| Uploads and resource allowance | Authenticated users can upload article cover images; reels require admin access. File signatures, sizes, multipart parts and formats are checked. Images are decoded and re-encoded with metadata removed, limited to 25 million pixels and 8 MB. Reels allow MP4/WebM up to 100 MB, with UUID filenames, temporary-file cleanup, and admin-only draft streaming. Videos are not transcoded or malware-scanned; decoder/antivirus isolation remains an optional production addition. |
| Upload allowance | `MAX_UPLOAD_BYTES_PER_MONTH=2147483648` sets a shared 2 GiB/month allowance; `0` disables new uploads. Maximum file size is reserved atomically before receiving a file; unused reservation is settled after the response. A crashed worker can leave a conservative reservation. Existing files are not removed; storage and bandwidth accumulate and need host-level quotas/monitoring. |
| CSRF / CORS | Every API mutation, including login and multipart uploads, requires a session-bound CSRF token. Foreign mutation origins are rejected. CORS (interpreting ?cause settings? as CORS) is intentionally same-origin: no wildcard origin or cross-origin credential grant. Local development has a short explicit origin allowlist. |
| HTTPS, headers and cookies | Production requires an HTTPS origin and Secure cookies, redirects safe HTTP requests, rejects insecure mutations, and sends HSTS. Cookies use HttpOnly, SameSite=Lax and a bounded lifetime. Helmet sets CSP and MIME protections; Permissions-Policy disables camera/microphone/geolocation. API responses are private/no-store and X-Powered-By is disabled. |
| Proxy and database transport | Only trust one proxy behind the loopback-bound app port; keep direct app access private. Caddy is the public entry point. Remote production database URLs require `sslmode=verify-full`; plaintext database connections are limited by configuration to the local/private Docker database hosts. |
| Debug / production | Production rejects DEBUG, disabled TLS verification, placeholder/short session secrets, insecure cookies, HTTP origins and default database passwords. Prisma error logging is disabled and HTTP 500s return generic messages. Server request/header timeouts are bounded. The production container runs as node, drops capabilities, disallows privilege elevation, uses a read-only root and bounded temporary storage/CPU/memory. |
| Dependency supply chain | Prisma CLI/client versions are aligned and the vulnerable `deepmerge-ts` dependency is overridden to its patched 8.x line. Dependency audit/build/test outcomes are recorded below after verification. |
| SSRF and feed ingestion | RSS downloads allow public HTTPS on port 443, reject private/reserved IPs and redirects, pin the DNS-checked IPv4 address, and limit time/size. Each run handles at most 100 sources and 100 entries per feed. No arbitrary URL-fetch endpoint is exposed. Feed operators may need to replace old HTTP/redirecting URLs with direct HTTPS endpoints. |

### Production setup

1. Keep `.env`, backups and any migration-owner credentials out of Git and container images. Set a unique production session secret and database password; do not deploy the demo admin account/password.
2. Run `npm run db:migrate` using a separate owner connection. Operator CLI commands accept `MIGRATION_DATABASE_URL`; the web application uses only `DATABASE_URL`. Apply `database/runtime-role.sql` with psql as the owner and supply `runtime_password` privately. For this local workspace, `node scripts/provision-runtime.js` provisions the restricted role and preserves the owner URL in ignored `.env`.
3. Set `SITE_HOST`, `PRODUCTION_APP_ORIGIN=https://your-domain`, and `PRODUCTION_DATABASE_URL` for the restricted runtime role. The production container deliberately does **not** run migrations at startup. Provision the schema before launching it. Use a provider-trusted CA for external PostgreSQL TLS.
4. Deploy with `docker compose -f compose.yaml -f compose.production.yaml --profile production up -d --build`. App access stays bound to loopback; Caddy publishes 80/443. Keep PostgreSQL bound to loopback/private networking, restrict host firewalls, back up the DB and uploads, and verify restore procedures.
5. Set provider-side hard spending caps and host storage/bandwidth quotas, configure alerts, review audit logs, and complete key rotation/history cleanup before public rollout. Backups need encrypted storage and retention/access controls; this audit does not encrypt or destroy the existing originals.

Verification commands: `npm test`, `npm run build`, `npm audit`, and `python scripts/security-scan.py` (Python 3). Security tests cover production fail-closed configuration, URLs/private network rejection, shared limiter behavior, private endpoints, headers, CSRF/CORS, malformed requests, and secret-file access; existing tests cover ownership, SQL/XSS payload handling, sessions and uploads.
