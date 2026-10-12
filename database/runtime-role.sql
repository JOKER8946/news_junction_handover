-- Run as the database owner AFTER migrations, through psql.
-- Example: psql "$MIGRATION_DATABASE_URL" -v runtime_password="$RUNTIME_DB_PASSWORD" -f database/runtime-role.sql
-- The password must be supplied via a secret manager/private environment, never committed.
SELECT 'CREATE ROLE newsjunction_runtime LOGIN NOSUPERUSER NOCREATEDB NOCREATEROLE NOREPLICATION NOBYPASSRLS'
WHERE NOT EXISTS (SELECT 1 FROM pg_roles WHERE rolname='newsjunction_runtime') \gexec
ALTER ROLE newsjunction_runtime PASSWORD :'runtime_password';
ALTER ROLE newsjunction_runtime SET statement_timeout='15s';
ALTER ROLE newsjunction_runtime SET idle_in_transaction_session_timeout='30s';
REVOKE CREATE ON SCHEMA public FROM PUBLIC;
GRANT USAGE ON SCHEMA public TO newsjunction_runtime;
GRANT SELECT,INSERT,UPDATE,DELETE ON
 users,categories,articles,bookmarks,likes,comments,channels,follows,channel_posts,complaints,
 resources,subscribers,leads,session,community_posts,community_likes,community_saves,user_follows,
 community_comments,reels,reel_likes,security_rate_limits,upload_budgets TO newsjunction_runtime;
GRANT USAGE,SELECT ON ALL SEQUENCES IN SCHEMA public TO newsjunction_runtime;
-- Deliberately no access to legacy_records or feed_sources, no DDL, and no owner/superuser privileges.
