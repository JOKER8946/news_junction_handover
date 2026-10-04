let csrf;
let sessionRequest;
export async function uploadReel(form, onProgress) {
  if (!csrf) await session();
  return new Promise((resolve, reject) => {
    const xhr = new XMLHttpRequest();
    xhr.open('POST', '/api/reels');
    xhr.setRequestHeader('X-CSRF-Token', csrf);
    xhr.upload.onprogress = (e) => {
      if (e.lengthComputable) onProgress(Math.round((e.loaded / e.total) * 100));
    };
    xhr.onerror = () => reject(Error('Upload failed. Check your connection and try again.'));
    xhr.onload = () => {
      try {
        const data = JSON.parse(xhr.responseText);
        xhr.status >= 200 && xhr.status < 300
          ? resolve(data)
          : reject(Error(data.error || 'Upload failed.'));
      } catch {
        reject(Error('Upload failed. Please try again.'));
      }
    };
    xhr.send(form);
  });
}
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
