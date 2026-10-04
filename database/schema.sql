CREATE TABLE IF NOT EXISTS users (
 id BIGSERIAL PRIMARY KEY, email TEXT NOT NULL, password TEXT NOT NULL,
 full_name TEXT NOT NULL, role TEXT NOT NULL DEFAULT 'reader' CHECK(role IN ('reader','reporter','admin')),
 bio TEXT NOT NULL DEFAULT '', district TEXT NOT NULL DEFAULT '', phone TEXT NOT NULL DEFAULT '',
 created_at TIMESTAMPTZ NOT NULL DEFAULT now()
);
CREATE UNIQUE INDEX IF NOT EXISTS users_email_unique ON users(lower(email));
CREATE TABLE IF NOT EXISTS categories (id SERIAL PRIMARY KEY, name TEXT UNIQUE NOT NULL);
CREATE TABLE IF NOT EXISTS articles (
 id BIGSERIAL PRIMARY KEY, title TEXT NOT NULL, description TEXT NOT NULL DEFAULT '', content TEXT NOT NULL,
 image TEXT NOT NULL DEFAULT '', category_id INTEGER REFERENCES categories(id),
 user_id BIGINT REFERENCES users(id) ON DELETE SET NULL, author_name TEXT NOT NULL DEFAULT 'News Junction',
 publisher TEXT NOT NULL DEFAULT 'News Junction', district TEXT NOT NULL DEFAULT '',
 status TEXT NOT NULL DEFAULT 'published' CHECK(status IN ('draft','published','scheduled')),
 published_at TIMESTAMPTZ NOT NULL DEFAULT now(), created_at TIMESTAMPTZ NOT NULL DEFAULT now(), views INTEGER NOT NULL DEFAULT 0
);
CREATE INDEX IF NOT EXISTS articles_feed_idx ON articles(status,published_at DESC);
CREATE TABLE IF NOT EXISTS bookmarks (user_id BIGINT REFERENCES users(id) ON DELETE CASCADE, article_id BIGINT REFERENCES articles(id) ON DELETE CASCADE, PRIMARY KEY(user_id,article_id));
CREATE TABLE IF NOT EXISTS likes (user_id BIGINT REFERENCES users(id) ON DELETE CASCADE, article_id BIGINT REFERENCES articles(id) ON DELETE CASCADE, PRIMARY KEY(user_id,article_id));
CREATE TABLE IF NOT EXISTS comments (id BIGSERIAL PRIMARY KEY, user_id BIGINT REFERENCES users(id) ON DELETE CASCADE, article_id BIGINT REFERENCES articles(id) ON DELETE CASCADE, body TEXT NOT NULL, created_at TIMESTAMPTZ NOT NULL DEFAULT now());
CREATE TABLE IF NOT EXISTS channels (id BIGSERIAL PRIMARY KEY, name TEXT NOT NULL, bio TEXT NOT NULL DEFAULT '', image TEXT NOT NULL DEFAULT '', user_id BIGINT REFERENCES users(id) ON DELETE SET NULL);
CREATE TABLE IF NOT EXISTS follows (user_id BIGINT REFERENCES users(id) ON DELETE CASCADE, channel_id BIGINT REFERENCES channels(id) ON DELETE CASCADE, PRIMARY KEY(user_id,channel_id));
CREATE TABLE IF NOT EXISTS channel_posts (id BIGSERIAL PRIMARY KEY, channel_id BIGINT REFERENCES channels(id) ON DELETE CASCADE, user_id BIGINT REFERENCES users(id) ON DELETE SET NULL, body TEXT NOT NULL, created_at TIMESTAMPTZ NOT NULL DEFAULT now());
CREATE TABLE IF NOT EXISTS complaints (id BIGSERIAL PRIMARY KEY, user_id BIGINT REFERENCES users(id) ON DELETE CASCADE, title TEXT NOT NULL, description TEXT NOT NULL, location TEXT NOT NULL, pincode TEXT NOT NULL, status TEXT NOT NULL DEFAULT 'Submitted' CHECK(status IN ('Submitted','In Review','Resolved')), response TEXT NOT NULL DEFAULT '', created_at TIMESTAMPTZ NOT NULL DEFAULT now());
CREATE TABLE IF NOT EXISTS resources (id BIGSERIAL PRIMARY KEY, kind TEXT NOT NULL, title TEXT NOT NULL, data JSONB NOT NULL DEFAULT '{}', user_id BIGINT REFERENCES users(id) ON DELETE SET NULL, created_at TIMESTAMPTZ NOT NULL DEFAULT now());
CREATE INDEX IF NOT EXISTS resources_kind_idx ON resources(kind);
CREATE TABLE IF NOT EXISTS subscribers (email TEXT PRIMARY KEY, created_at TIMESTAMPTZ NOT NULL DEFAULT now());
CREATE TABLE IF NOT EXISTS leads (id BIGSERIAL PRIMARY KEY, resource_id BIGINT REFERENCES resources(id) ON DELETE CASCADE, name TEXT NOT NULL, email TEXT NOT NULL, created_at TIMESTAMPTZ NOT NULL DEFAULT now());
CREATE TABLE IF NOT EXISTS session (sid VARCHAR PRIMARY KEY, sess JSON NOT NULL, expire TIMESTAMP(6) NOT NULL);
CREATE INDEX IF NOT EXISTS session_expire_idx ON session(expire);
CREATE TABLE IF NOT EXISTS legacy_records (source TEXT NOT NULL, table_name TEXT NOT NULL, record_key TEXT NOT NULL, data JSONB NOT NULL, PRIMARY KEY(source,table_name,record_key));
CREATE TABLE IF NOT EXISTS community_posts (id BIGSERIAL PRIMARY KEY, user_id BIGINT REFERENCES users(id) ON DELETE CASCADE, body TEXT NOT NULL, image TEXT NOT NULL DEFAULT '', pincode TEXT NOT NULL DEFAULT '', created_at TIMESTAMPTZ NOT NULL DEFAULT now());
CREATE TABLE IF NOT EXISTS community_likes (user_id BIGINT REFERENCES users(id) ON DELETE CASCADE, post_id BIGINT REFERENCES community_posts(id) ON DELETE CASCADE, PRIMARY KEY(user_id,post_id));
CREATE TABLE IF NOT EXISTS community_saves (user_id BIGINT REFERENCES users(id) ON DELETE CASCADE, post_id BIGINT REFERENCES community_posts(id) ON DELETE CASCADE, PRIMARY KEY(user_id,post_id));
CREATE TABLE IF NOT EXISTS user_follows (follower_id BIGINT REFERENCES users(id) ON DELETE CASCADE, following_id BIGINT REFERENCES users(id) ON DELETE CASCADE, PRIMARY KEY(follower_id,following_id), CHECK(follower_id<>following_id));
CREATE TABLE IF NOT EXISTS community_comments (id BIGSERIAL PRIMARY KEY, post_id BIGINT REFERENCES community_posts(id) ON DELETE CASCADE, user_id BIGINT REFERENCES users(id) ON DELETE CASCADE, body TEXT NOT NULL, created_at TIMESTAMPTZ NOT NULL DEFAULT now());
CREATE TABLE IF NOT EXISTS feed_sources (id BIGSERIAL PRIMARY KEY, url TEXT UNIQUE NOT NULL, publisher TEXT NOT NULL DEFAULT '', category_id INTEGER REFERENCES categories(id), active BOOLEAN NOT NULL DEFAULT true, last_synced_at TIMESTAMPTZ, last_error TEXT);
ALTER TABLE articles ADD COLUMN IF NOT EXISTS source_url TEXT;
CREATE UNIQUE INDEX IF NOT EXISTS articles_source_url_unique ON articles(source_url) WHERE source_url IS NOT NULL;
CREATE INDEX IF NOT EXISTS articles_author_idx ON articles(user_id,published_at DESC);
CREATE INDEX IF NOT EXISTS articles_public_time_idx ON articles(published_at DESC,id DESC) WHERE status IN ('published','scheduled');
CREATE INDEX IF NOT EXISTS likes_article_idx ON likes(article_id);
CREATE INDEX IF NOT EXISTS bookmarks_article_idx ON bookmarks(article_id);
CREATE INDEX IF NOT EXISTS comments_article_idx ON comments(article_id,created_at DESC);
CREATE INDEX IF NOT EXISTS channel_posts_channel_idx ON channel_posts(channel_id,created_at DESC);
CREATE INDEX IF NOT EXISTS community_posts_time_idx ON community_posts(created_at DESC,id DESC);
CREATE INDEX IF NOT EXISTS community_likes_post_idx ON community_likes(post_id);
CREATE INDEX IF NOT EXISTS community_comments_post_idx ON community_comments(post_id,created_at);
