// Parse mysqldump INSERT literals without executing legacy SQL or requiring MySQL.
const fs = require('node:fs');
const zlib = require('node:zlib');
const readline = require('node:readline');
function* tuples(input) {
  let row = [],
    value = '',
    quoted = false,
    inString = false,
    escape = false,
    depth = 0;
  const finish = () => {
    const v = value.trim();
    row.push(quoted ? value : v.toUpperCase() === 'NULL' ? null : v);
    value = '';
    quoted = false;
  };
  for (let i = 0; i < input.length; i++) {
    const c = input[i];
    if (inString) {
      if (escape) {
        value += { 0: '\u0000', n: '\n', r: '\r', t: '\t', b: '\b', Z: '\x1a' }[c] ?? c;
        escape = false;
      } else if (c === '\\') escape = true;
      else if (c === "'") {
        if (input[i + 1] === "'") {
          value += "'";
          i++;
        } else inString = false;
      } else value += c;
      continue;
    }
    if (c === "'") {
      inString = true;
      quoted = true;
      continue;
    }
    if (c === '(') {
      if (depth) throw Error('Unsupported expression in SQL dump.');
      depth = 1;
      row = [];
      value = '';
      quoted = false;
      continue;
    }
    if (!depth) continue;
    if (c === ',') {
      finish();
      continue;
    }
    if (c === ')') {
      finish();
      yield row;
      depth = 0;
      continue;
    }
    if (!quoted || !/\s/.test(c)) value += c;
  }
  if (inString || depth) throw Error('Unterminated INSERT tuple in SQL dump.');
}
async function* readDump(filename) {
  const input = fs.createReadStream(filename);
  const stream = filename.endsWith('.gz') ? input.pipe(zlib.createGunzip()) : input;
  const lines = readline.createInterface({ input: stream, crlfDelay: Infinity });
  const columns = new Map();
  let current = null;
  for await (const line of lines) {
    const create = line.match(/^CREATE TABLE(?: IF NOT EXISTS)? `([^`]+)`/);
    if (create) {
      current = create[1];
      columns.set(current, []);
      continue;
    }
    if (current) {
      const col = line.match(/^\s+`([^`]+)`\s/);
      if (col) columns.get(current).push(col[1]);
      if (line.startsWith(')')) current = null;
      continue;
    }
    const insert = line.match(/^INSERT INTO `([^`]+)`(?:\s*\(([^)]+)\))?\s+VALUES\s*/);
    if (!insert) continue;
    const table = insert[1],
      keys = insert[2]
        ? Array.from(insert[2].matchAll(/`([^`]+)`/g), (m) => m[1])
        : columns.get(table);
    if (!keys?.length) throw Error('No columns found for ' + table);
    let index = 0;
    for (const row of tuples(line.slice(insert[0].length))) {
      if (row.length !== keys.length)
        throw Error(`Column count mismatch in ${table}: ${row.length} instead of ${keys.length}`);
      const data = Object.fromEntries(
        keys.map((key, i) => [
          key,
          typeof row[i] === 'string' ? row[i].replaceAll('\u0000', '') : row[i],
        ]),
      );
      yield { table, data, index: index++ };
    }
  }
}
module.exports = { tuples, readDump };
