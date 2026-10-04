let csrf;
let sessionRequest;
export async function session() {
  sessionRequest ||= fetch('/api/auth/session')
    .then(async (r) => {
      const d = await r.json();
      if (!r.ok) throw Error(d.error);
      csrf = d.csrf;
      return d;
    })
    .finally(() => {
      sessionRequest = null;
    });
  return sessionRequest;
}
export async function api(url, options = {}) {
  if (options.method && options.method !== 'GET' && !csrf) await session();
  const r = await fetch('/api' + url, {
    ...options,
    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf || '', ...options.headers },
    body: options.body ? JSON.stringify(options.body) : undefined,
  });
  const d = await r.json();
  if (!r.ok) throw Object.assign(Error(d.error || 'Something went wrong.'), { status: r.status });
  if (d.csrf) csrf = d.csrf;
  return d;
}
export async function upload(file) {
  if (!csrf) await session();
  const form = new FormData();
  form.append('image', file);
  const r = await fetch('/api/uploads', {
    method: 'POST',
    headers: { 'X-CSRF-Token': csrf },
    body: form,
  });
  const d = await r.json();
  if (!r.ok) throw Error(d.error || 'Upload failed.');
  return d.url;
}
