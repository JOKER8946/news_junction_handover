require('dotenv').config();
const { test, expect } = require('@playwright/test');
test('desktop: discover, search, read, sign in, save and publish', async ({ page }) => {
  test.setTimeout(90000);
  const errors = [];
  page.on('pageerror', (e) => errors.push(e.message));
  await page.setViewportSize({ width: 1440, height: 1050 });
  await page.goto('/reader');
  await expect(page.locator('.story-card h3').first()).toBeVisible();
  await page.evaluate(async () => {
    document.querySelectorAll('img').forEach((i) => (i.loading = 'eager'));
    await Promise.race([
      Promise.all(Array.from(document.images).map((i) => i.decode().catch(() => {}))),
      new Promise((r) => setTimeout(r, 4000)),
    ]);
    await document.fonts.ready;
  });
  await page.screenshot({ path: 'tmp/redesign-desktop.png', fullPage: true });
  await page
    .getByRole('textbox', { name: 'Search news' })
    .fill('no-results-browser-verification-983624');
  await page.getByRole('textbox', { name: 'Search news' }).press('Enter');
  await expect(page.getByRole('heading', { name: 'No stories found.' })).toBeVisible({timeout:45000});
  await page.goto('/reader');
  await expect(page.locator('.story-card').first()).toBeVisible();
  await page.locator('.story-card .story-card-body>a').first().click();
  await expect(page.locator('.article-content')).toBeVisible();
  await page.getByRole('button', { name: 'Save story', exact: true }).click();
  await expect(page).toHaveURL(/sign-in/);
  await page.getByLabel('Email address', { exact: true }).fill(process.env.SEED_ADMIN_EMAIL);
  await page.getByLabel('Password', { exact: true }).fill(process.env.SEED_ADMIN_PASSWORD);
  await page.getByRole('button', { name: 'Log in', exact: true }).last().click();
  await expect(page).toHaveURL(/community/);
  await page.goto('/reader');
  await expect(page.locator('.story-card').first()).toBeVisible();
  const save = page
    .locator('.story-card')
    .first()
    .getByRole('button', { name: /save story/i });
  if ((await save.getAttribute('aria-pressed')) === 'true') await save.click();
  await expect(save).toHaveAttribute('aria-pressed', 'false');
  await save.click();
  await page.getByRole('link', { name: 'Saved stories', exact: true }).click();
  await expect(page.locator('.story-card').first()).toBeVisible();
  await page.goto('/create');
  await page.getByLabel('Headline', { exact: true }).fill('Browser verification draft');
  await page.getByLabel('Category', { exact: true }).selectOption('3');
  await page.getByLabel(/Your story/).fill('A draft created during browser verification.');
  await page.getByRole('button', { name: 'Save draft' }).click();
  await expect(page.locator('.article-page h1')).toHaveText('Browser verification draft');
  page.on('dialog', (d) => d.accept());
  await page.getByRole('button', { name: 'Delete', exact: true }).click();
  await expect(page).toHaveURL(/my-articles/);
  expect(errors).toEqual([]);
});
test('mobile: layout fits, navigation and sign-in are usable', async ({ page }) => {
  await page.setViewportSize({ width: 390, height: 844 });
  await page.goto('/reader');
  await expect(page.locator('.story-card').first()).toBeVisible();
  expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true);
  await page.screenshot({ path: 'tmp/redesign-mobile.png', fullPage: true });
  await page.getByRole('button', { name: 'Open navigation' }).click();
  await page.getByRole('link', { name: 'Featured Channels', exact: true }).click();
  await expect(page.locator('.directory-card').first()).toBeVisible();
  await page.goto('/sign-in');
  await expect(page.getByLabel('Email address', { exact: true })).toBeVisible();
  expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true);
  await page.screenshot({ path: 'tmp/redesign-sign-in.png', fullPage: true });
});

test('reference landing and sign-up navigation', async ({ page }) => {
  await page.goto('/');
  await expect(page.locator('.landing-features article')).toHaveCount(4);
  await expect(page.getByRole('img', { name: 'News Junction', exact: true }).first()).toBeVisible();
  await page.getByRole('link', { name: 'Sign Up', exact: true }).click();
  await expect(page.getByLabel('Your name')).toBeVisible();
  await page.getByRole('button', { name: 'Login', exact: true }).click();
  await expect(page.getByLabel('Your name')).toHaveCount(0);
  await expect(page.getByRole('button', { name: 'Log in', exact: true })).toBeVisible();
  await page.setViewportSize({ width: 390, height: 844 });
  await page.goto('/');
  expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true);
  await page.getByRole('link', { name: 'Explore Latest News' }).click();
  await expect(page).toHaveURL(/reader/);
});
