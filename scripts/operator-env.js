// Only operator CLI commands may opt into the database-owner connection.
module.exports = function operatorEnvironment() {
  require('dotenv').config();
  if(process.env.MIGRATION_DATABASE_URL) process.env.DATABASE_URL=process.env.MIGRATION_DATABASE_URL;
};
