-- database/schema.sql
-- ACCESS — User Authentication
-- PostgreSQL schema definition

CREATE TABLE IF NOT EXISTS users (
    id            SERIAL PRIMARY KEY,
    username      VARCHAR(100) NOT NULL UNIQUE,
    password_hash TEXT         NOT NULL,
    created_at    TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Index for fast username lookups during login
CREATE INDEX IF NOT EXISTS idx_users_username ON users (username);
