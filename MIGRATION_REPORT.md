# Migration verification

Verified locally on 4 October 2026 using PostgreSQL 17, Node.js 24, and installed Google Chrome.

## Original data

All seven provided compressed SQL backups were parsed into PostgreSQL without executing their MySQL SQL statements. Original backup files were left untouched.

| Result                                      |   Count |
| ------------------------------------------- | ------: |
| Original rows preserved in `legacy_records` | 301,878 |
| Accounts promoted                           |     142 |
| Authored and syndicated stories promoted    | 284,512 |
| Community posts promoted                    |   1,207 |
| Channels promoted                           |      10 |
| Magazine/advertisement resources promoted   |      18 |
| Civic reports promoted                      |       4 |
| RSS definitions promoted                    |      85 |

The supplied user's account and password were verified against the migrated bcrypt hash. No plaintext credential was added to source control. An additional local editor account was created for verification.

The local application uses the migrated database. The separate sample database remains available. The hosted website was inspected using the supplied login; its database, content, hosting, and DNS were not modified.

## Application checks

- Production React build passed.
- Seventeen Node API/parser/RSS checks passed, covering persistence, permissions, CSRF, sessions, scheduling, interactions, community posts, and RSS deduplication/sanitization.
- Three Chrome browser workflows passed: desktop news/publishing; mobile layout/navigation; and community posts/replies plus rejected and accepted image uploads.
- PostgreSQL import completed transactionally; malformed input and legacy date handling have dedicated tests.
- The multi-stage Node Docker image built successfully with no PHP runtime.
- A temporary production container returned HTTP 200 for both the homepage and database health endpoint, read all 284,512 migrated stories, and successfully wrote to its upload volume.
- Dependency installation reported zero known vulnerabilities.
- Source scan found no remaining PHP or PHTML files outside ignored dependencies.

## Scope and deployment

The implemented news, reader, creator, channel, and community flows are described in `README.md`. Legacy data for the separate marketplace, email providers, payments, OAuth, and social-network integrations is archived; those provider integrations are not active in this rebuild.

Production cutover still requires access to the hosting environment, database provisioning, media transfer, HTTPS/reverse-proxy configuration, and DNS. The provided website account does not grant that infrastructure access. No production deployment is claimed.

## UI correction ? 5 October 2026

Restored the original red/blue public landing page, full News Junction logo, Kannada feature cards, orange split-screen authentication, and Social/Reader sidebar layout from the supplied references. Removed the editorial homepage and marketing slogans. Login opens Social, and Reader has its own route. The separate marketplace navigation links to the existing marketplace.

The production build passed. Four browser workflows were verified across the UI correction runs: landing/sign-up navigation; desktop search, reading, bookmarks and article editing; community posting, replies and image uploads; and mobile layout/menu/login. The final mobile check passed after fixing the menu backdrop. Screenshots are in `tmp/restored-*.png`. No production deployment was performed.

## Reels feature ? 5 October 2026

Added PostgreSQL-backed reels and likes, access-checked MP4/WebM uploads (100 MB maximum), private drafts, byte-range video streaming, admin editing/deletion, and an on-site vertical player. The production build and all 18 API/parser/RSS checks passed. A Chrome workflow verified admin upload, mobile autoplay/play-pause/mute, draft visibility, and deletion. Its screenshot is `tmp/reels-mobile.png`. Test uploads were removed.
