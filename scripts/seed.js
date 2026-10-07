const bcrypt = require('bcryptjs');
const fs = require('node:fs');
const path = require('node:path');
const prisma = require('../server/prisma');
const { pool } = require('../server/database');
const migrate = require('./migrate');

async function seed() {
  await migrate();

  if (
    !process.env.SEED_ADMIN_EMAIL ||
    !process.env.SEED_ADMIN_PASSWORD ||
    process.env.SEED_ADMIN_PASSWORD.length < 10
  )
    throw Error('Set SEED_ADMIN_EMAIL and a SEED_ADMIN_PASSWORD of at least 10 characters.');

  const data = require('../database/demo-content.json');

  await prisma.$transaction(async (tx) => {
    // Seed categories
    for (const c of data.categories) {
      await tx.category.upsert({
        where: { id: c.id },
        create: { id: c.id, name: c.category },
        update: {},
      });
    }

    // Upsert admin user
    const admin = await tx.user.upsert({
      where: { email: process.env.SEED_ADMIN_EMAIL.toLowerCase() },
      create: {
        email: process.env.SEED_ADMIN_EMAIL.toLowerCase(),
        password: await bcrypt.hash(process.env.SEED_ADMIN_PASSWORD, 12),
        full_name: 'News Junction Editor',
        role: 'admin',
      },
      update: {},
    });

    // Seed articles if none exist
    const existingArticle = await tx.article.findFirst({ select: { id: true } });
    if (!existingArticle) {
      for (const a of data.articles) {
        const image = fs.existsSync(path.join(__dirname, `../public/media/story-${a.id}.jpg`))
          ? `/media/story-${a.id}.jpg`
          : a.image;
        await tx.article.create({
          data: {
            id: BigInt(a.id),
            title: a.title,
            description: a.description,
            content: a.content,
            image,
            category_id: a.category_id,
            user_id: admin.id,
            author_name: a.author_name,
            publisher: a.publisher,
            district: a.district,
            published_at: new Date(a.date),
          },
        });
      }

      for (const c of data.channels) {
        await tx.channel.create({
          data: { id: BigInt(c.id), name: c.name, bio: c.bio || '', user_id: admin.id },
        });
      }

      for (const p of data.posts) {
        await tx.channelPost.create({
          data: { channel_id: BigInt(p.channel_id), user_id: admin.id, body: p.chat },
        });
      }
    }

    // Update article images from local files
    for (const a of data.articles) {
      if (fs.existsSync(path.join(__dirname, `../public/media/story-${a.id}.jpg`))) {
        await tx.article.updateMany({
          where: { id: BigInt(a.id), title: a.title },
          data: { image: `/media/story-${a.id}.jpg` },
        });
      }
    }
  });

  // Reset sequences using raw pool (Prisma doesn't support setval)
  const db = await pool.connect();
  try {
    for (const table of ['users', 'articles', 'channels', 'categories']) {
      await db.query(
        `SELECT setval(pg_get_serial_sequence('${table}','id'), GREATEST(COALESCE((SELECT max(id) FROM ${table}),0),1), EXISTS(SELECT 1 FROM ${table}))`,
      );
    }
  } finally {
    db.release();
  }

  console.log(
    'Local example content seeded. Editor credentials are in your untracked .env. This is sample content, not a live news import.',
  );
}

if (require.main === module)
  seed()
    .catch((e) => {
      console.error(e.message);
      process.exitCode = 1;
    })
    .finally(async () => {
      await prisma.$disconnect();
      await pool.end();
    });

module.exports = seed;
