-- Runs once, on first cluster initialisation, as the superuser.

-- citext powers case-insensitive idempotency keys and webhook endpoint URLs;
-- pgcrypto backs gen_random_uuid() for database-side identifier defaults if needed.
CREATE EXTENSION IF NOT EXISTS "citext";
CREATE EXTENSION IF NOT EXISTS "pgcrypto";
CREATE EXTENSION IF NOT EXISTS "btree_gist";

-- Dedicated test database so `APP_ENV=test` never touches development data.
SELECT 'CREATE DATABASE app_test OWNER app'
WHERE NOT EXISTS (SELECT FROM pg_database WHERE datname = 'app_test')\gexec

\connect app_test
CREATE EXTENSION IF NOT EXISTS "citext";
CREATE EXTENSION IF NOT EXISTS "pgcrypto";
CREATE EXTENSION IF NOT EXISTS "btree_gist";

\connect app
-- Money is stored as BIGINT minor units; make accidental float math loud.
ALTER DATABASE app SET default_transaction_isolation TO 'read committed';
ALTER DATABASE app_test SET default_transaction_isolation TO 'read committed';
