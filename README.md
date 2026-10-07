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
- Reporter story creation, editing, deletion, drafts, scheduled publication and verified image uploads. Scheduled stories become public at their publication time without requiring a worker.
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
