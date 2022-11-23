-- -*- mode: sql; sql-product: sqlite; -*-
BEGIN;
ALTER TABLE user ADD stealth INTEGER NOT NULL DEFAULT FALSE;
END;
