const fs = require('node:fs/promises');
const data = require('../database/demo-content.json');
(async () => {
  for (const a of data.articles) {
    try {
      const urls = {
        101: 'https://images.unsplash.com/photo-1474487548417-781cb71495f3?w=1400&auto=format&fit=crop&q=85',
        104: 'https://images.unsplash.com/photo-1509391366360-2e959784a276?w=800&auto=format&fit=crop&q=85',
      };
      const r = await fetch(urls[a.id] || a.image, { signal: AbortSignal.timeout(25000) });
      if (!r.ok) throw Error(r.status);
      await fs.writeFile(`public/media/story-${a.id}.jpg`, Buffer.from(await r.arrayBuffer()));
      console.log('Downloaded story image ' + a.id);
    } catch (e) {
      console.log('Image ' + a.id + ' unavailable: ' + e.message);
    }
  }
})();
