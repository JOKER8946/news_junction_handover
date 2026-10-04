const { chromium } = require('@playwright/test');
const fs = require('node:fs/promises');
(async () => {
  await fs.mkdir('tmp', { recursive: true });
  const browser = await chromium.launch({ channel: 'chrome', headless: true });
  try {
    const page = await browser.newPage({ viewport: { width: 1440, height: 1050 } });
    await page.goto('http://127.0.0.1:3000', { waitUntil: 'domcontentloaded' });
    await page.locator('.landing-features').waitFor();
    await page.evaluate(async () => {
      document.querySelectorAll('img').forEach((image) => (image.loading = 'eager'));
      await Promise.race([
        Promise.all(Array.from(document.images).map((image) => image.decode().catch(() => {}))),
        new Promise((resolve) => setTimeout(resolve, 15000)),
      ]);
      await document.fonts.ready;
    });
    await page.screenshot({ path: 'tmp/restored-landing.png', fullPage: true });
    console.log('Desktop captured.');
    console.log(
      await page.evaluate(() => ({
        loadedImages: Array.from(document.images).filter(
          (image) => image.complete && image.naturalWidth > 0 && !image.src.includes('placeholder'),
        ).length,
        placeholders: Array.from(document.images).filter((image) =>
          image.src.includes('placeholder'),
        ).length,
      })),
    );
    await page.setViewportSize({ width: 390, height: 844 });
    await page.screenshot({ path: 'tmp/restored-landing-mobile.png', fullPage: true });
    await page.goto('http://127.0.0.1:3000/sign-in');
    await page.getByLabel('Email address', { exact: true }).waitFor();
    await page.screenshot({ path: 'tmp/restored-sign-in-mobile.png', fullPage: true });
    await page.setViewportSize({ width: 1440, height: 900 });
    await page.screenshot({ path: 'tmp/restored-sign-in.png', fullPage: true });
    await page.goto('http://127.0.0.1:3000/community');
    await page.locator('.original-ad').first().waitFor();
    await page.waitForTimeout(2500);
    await page.screenshot({ path: 'tmp/restored-social.png', fullPage: false });
    console.log('Landing, mobile, sign-in and Social captured.');
  } finally {
    await browser.close();
  }
})().catch((error) => {
  console.error(error.message);
  process.exitCode = 1;
});
