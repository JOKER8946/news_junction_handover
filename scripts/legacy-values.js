const { decodeHTML } = require('entities');
function timestamp(value) {
  if (!value || /^0000-/.test(String(value))) return new Date(0).toISOString();
  let raw = value;
  if (/^\d{10}$/.test(String(raw))) raw = Number(raw) * 1000;
  else if (/^\d{13}$/.test(String(raw))) raw = Number(raw);
  if (typeof raw === 'string' && /^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/.test(raw))
    raw = raw.replace(' ', 'T') + '+05:30';
  const date = new Date(raw);
  return Number.isFinite(date.getTime()) ? date.toISOString() : new Date(0).toISOString();
}
function plain(value) {
  return decodeHTML(String(value || ''))
    .replace(/\\'/g, "'")
    .replace(/\\\"/g, '"');
}
module.exports = { timestamp, plain };
