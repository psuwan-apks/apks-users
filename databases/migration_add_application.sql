-- Migration: Add `application` column to `tbl4users_users`
-- This script adds the column with a default value for existing rows.

ALTER TABLE `tbl4users_users`
    ADD COLUMN `application` VARCHAR(100) NOT NULL DEFAULT 'default_app' AFTER `password_hash`;

-- Optionally, you can remove the default after populating existing rows:
-- ALTER TABLE `tbl4users_users` ALTER COLUMN `application` DROP DEFAULT;
