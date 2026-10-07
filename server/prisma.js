// Ensure BigInt values can be serialised as JSON (Prisma returns BigInt for bigint columns).
// They stay within Number.MAX_SAFE_INTEGER for this application.
/* eslint-disable no-extend-native */
BigInt.prototype.toJSON = function () {
  return Number(this);
};

const { PrismaClient } = require('@prisma/client');

const prisma = new PrismaClient({
  log: process.env.NODE_ENV === 'development' ? ['warn', 'error'] : ['error'],
});

module.exports = prisma;
