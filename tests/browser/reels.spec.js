require('dotenv').config();
const { test, expect } = require('@playwright/test');
test('admin uploads and edits a reel; visitors watch it in the mobile viewer', async ({
  page,
  browser,
}) => {
  await page.goto('/sign-in');
  await page.getByLabel('Email address', { exact: true }).fill(process.env.SEED_ADMIN_EMAIL);
  await page.getByLabel('Password', { exact: true }).fill(process.env.SEED_ADMIN_PASSWORD);
  await page.getByRole('button', { name: 'Log in', exact: true }).click();
  await expect(page).toHaveURL(/community/);
  await page.goto('/admin');
  await page.getByRole('navigation', {name:'Administration sections'}).getByRole('link', { name: 'Reels', exact: true }).click();
  const title = 'Browser reel ' + Date.now();
  await page.getByLabel('Video', { exact: true }).setInputFiles('public/media/reel1.mp4');
  await expect(page.locator('.reel-upload-preview')).toBeVisible();
  await page.getByLabel('Reel title').fill(title);
  await page.getByLabel('Reel caption').fill('A browser verified reel');
  await page.getByRole('button', { name: 'Upload reel', exact: true }).click();
  const row = page.locator('.admin-reel-row').filter({ hasText: title });
  await expect(row).toBeVisible({ timeout: 30000 });
  const guest = await browser.newContext({ viewport: { width: 390, height: 844 } });
  const viewer = await guest.newPage();
  try {
    await viewer.goto('/reels');
    const reel = viewer.locator('.reel-slide').filter({ hasText: title });
    await expect(reel).toBeVisible();
    await expect
      .poll(() => reel.locator('video').evaluate((v) => v.readyState))
      .toBeGreaterThanOrEqual(2);
    await expect.poll(() => reel.locator('video').evaluate((v) => v.paused)).toBe(false);
    await reel.getByRole('button', { name: 'Pause reel' }).click();
    await expect(reel.getByRole('button', { name: 'Play reel' })).toBeVisible();
    await reel.getByRole('button', { name: 'Unmute reel' }).click();
    await expect(reel.getByRole('button', { name: 'Mute reel' })).toBeVisible();
    expect(await viewer.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(
      true,
    );
    await viewer.screenshot({ path: 'tmp/reels-mobile.png' });
    await row.getByRole('button', { name: 'Edit reel' }).click();
    await page.getByLabel('Visibility').selectOption('false');
    await page.getByRole('button', { name: 'Save reel', exact: true }).click();
    await expect(row).toContainText('Draft');
    await viewer.reload();
    await expect(viewer.locator('.reel-slide').filter({ hasText: title })).toHaveCount(0);
  } finally {
    await guest.close();
    page.on('dialog', (d) => d.accept());
    await row.getByRole('button', { name: 'Delete reel' }).click();
    await expect(row).toHaveCount(0);
  }
});
