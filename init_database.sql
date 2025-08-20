-- SQLite database schema for auth codes
-- This will replace the text file storage

CREATE TABLE IF NOT EXISTS auth_codes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    code TEXT NOT NULL UNIQUE,
    admin_access INTEGER DEFAULT 0,  -- 0 = regular user, 1 = admin access
    created_timestamp INTEGER NOT NULL,
    UNIQUE(code)
);

-- Create index for faster lookups
CREATE INDEX IF NOT EXISTS idx_auth_codes_code ON auth_codes(code);
CREATE INDEX IF NOT EXISTS idx_auth_codes_admin_access ON auth_codes(admin_access);