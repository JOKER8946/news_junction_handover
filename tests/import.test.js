const { test } = require('node:test');
const assert = require('node:assert/strict');
const { tuples } = require('../scripts/mysql-dump');
test('MySQL literals preserve Unicode, commas, escaped quotes, backslashes and NULL', () => {
  const values = Array.from(
    tuples(
      "(1,'ಕನ್ನಡ, news','it\\'s fine',NULL,'NULL','a\\nb','C:\\\\files'),(2,'double '' quote','x',0,'',1,2);",
    ),
  );
  assert.deepEqual(values[0], ['1', 'ಕನ್ನಡ, news', "it's fine", null, 'NULL', 'a\nb', 'C:\\files']);
  assert.deepEqual(values[1], ['2', "double ' quote", 'x', '0', '', '1', '2']);
});
test('malformed SQL literals fail rather than silently losing data', () => {
  assert.throws(() => Array.from(tuples("(1,'unterminated")), /Unterminated/);
});
test('legacy dates do not turn missing or invalid timestamps into fresh news', () => {
  const { timestamp, plain } = require('../scripts/legacy-values');
  assert.equal(timestamp('0000-00-00 00:00:00'), '1970-01-01T00:00:00.000Z');
  assert.equal(timestamp('invalid'), '1970-01-01T00:00:00.000Z');
  assert.equal(timestamp('2026-04-21 00:50:58'), '2026-04-20T19:20:58.000Z');
  assert.equal(timestamp('1700000000'), '2023-11-14T22:13:20.000Z');
  assert.equal(plain("India\\'s &amp; Karnataka"), "India's & Karnataka");
});
