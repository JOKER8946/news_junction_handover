require('dotenv').config();
const { test, expect } = require('@playwright/test');
test('community publishing, replies and image upload validation', async ({ page }) => {
  await page.goto('/sign-in');
  await page.getByLabel('Email address', { exact: true }).fill(process.env.SEED_ADMIN_EMAIL);
  await page.getByLabel('Password', { exact: true }).fill(process.env.SEED_ADMIN_PASSWORD);
  await page.getByRole('button', { name: 'Log in', exact: true }).last().click();
  await expect(page).toHaveURL(/community/);
  await page.goto('/community');
  await page.getByRole('button', { name: 'Create post' }).click();
  await page
    .getByLabel('What’s happening in your neighbourhood?')
    .fill('Browser community verification');
  await page.getByRole('button', { name: 'Post', exact: true }).click();
  await expect(page.locator('.social-post').first()).toContainText(
    'Browser community verification',
  );
  const post = page.locator('.social-post').first();
  await post.getByRole('button', { name: 'Discuss post' }).click();
  await post.getByLabel('Join the conversation').fill('A browser-tested reply');
  await post.getByRole('button', { name: 'Post reply' }).click();
  await expect(post.locator('.comment')).toContainText('A browser-tested reply');
  page.on('dialog', (d) => d.accept());
  await post.getByRole('button', { name: 'Delete post' }).click();
  await expect(page.getByText('Browser community verification', { exact: true })).toHaveCount(0);
  await page.goto('/create');
  const rejected = page.waitForResponse((r) => r.url().endsWith('/api/uploads'), {
    timeout: 45000,
  });
  await page.locator('input[type=file]').setInputFiles({
    name: 'fake.jpg',
    mimeType: 'image/jpeg',
    buffer: Buffer.from('This is not an image file.'),
  });
  expect((await rejected).status()).toBe(400);
  await expect(page.getByRole('alert')).toContainText('Only JPEG, PNG, and WebP');
  const accepted = page.waitForResponse((r) => r.url().endsWith('/api/uploads'), {
    timeout: 45000,
  });
  await page.locator('input[type=file]').setInputFiles('public/media/story-101.jpg');
  const response = await accepted;
  expect(response.status()).toBe(201);
  const { url } = await response.json();
  await expect(page.getByLabel('Cover image', { exact: true })).toHaveValue(url);
  expect(
    await page.getByLabel('Cover image', { exact: true }).evaluate((el) => el.checkValidity()),
  ).toBe(true);
  expect(url).toMatch(/^\/media\/uploads\/[a-f0-9-]+\.jpg$/);
  await require('node:fs/promises').unlink(require('node:path').join(process.cwd(), 'public', url));
});
