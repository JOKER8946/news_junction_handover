// Local provisioning: preserve the owner URL privately, grant only application DML, and switch runtime credentials.
require('dotenv').config();
const {Pool}=require('pg');
const fs=require('node:fs/promises');
const crypto=require('node:crypto');
(async()=>{
 const ownerUrl=process.env.MIGRATION_DATABASE_URL||process.env.DATABASE_URL;
 if(!ownerUrl)throw Error('Set a database owner URL first.');
 const pool=new Pool({connectionString:ownerUrl});
 try {
  const exists=(await pool.query("SELECT 1 FROM pg_roles WHERE rolname='newsjunction_runtime'")).rowCount;
  if(!exists)await pool.query('CREATE ROLE newsjunction_runtime LOGIN NOSUPERUSER NOCREATEDB NOCREATEROLE NOREPLICATION NOBYPASSRLS');
  const password=crypto.randomBytes(32).toString('hex');
  // Generated hexadecimal is safe in DDL; SQL identifiers are static and never user controlled.
  await pool.query("ALTER ROLE newsjunction_runtime PASSWORD '"+password+"'");
  const source=await fs.readFile('database/runtime-role.sql','utf8');
  await pool.query(source.slice(source.indexOf('ALTER ROLE newsjunction_runtime SET')));
  const url=new URL(ownerUrl);url.username='newsjunction_runtime';url.password=password;
  let env=await fs.readFile('.env','utf8');
  if(!/^MIGRATION_DATABASE_URL=/m.test(env))env+='\nMIGRATION_DATABASE_URL='+ownerUrl+'\n';
  env=env.replace(/^DATABASE_URL=.*$/m,'DATABASE_URL='+url.toString());
  await fs.writeFile('.env',env,{mode:0o600});
  console.log('Restricted runtime role configured. Owner connection retained only in the private local environment.');
 } finally {await pool.end();}
})().catch(e=>{console.error('Runtime provisioning failed:',e.code||e.name);process.exitCode=1;});
