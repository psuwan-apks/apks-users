-- MySQL Schema for APKS Web Application OAuth & User Management
-- Database: db4apks_webapp
-- Notes: Foreign keys removed; referential integrity enforced at application layer.
--        Engine changed to MyISAM for simplicity.
--        Indexes added on frequently-queried columns.

CREATE DATABASE IF NOT EXISTS `db4apks_webapp` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `db4apks_webapp`;

-- 1. Users Table
CREATE TABLE IF NOT EXISTS `tbl4users_users` (
    `id`            INT AUTO_INCREMENT PRIMARY KEY,
    `username`      VARCHAR(50)  NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `application`   VARCHAR(100) NOT NULL,
    `created_at`    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. OAuth Clients Table
CREATE TABLE IF NOT EXISTS `tbl4users_oauth_clients` (
    `client_id`     VARCHAR(80)   NOT NULL PRIMARY KEY,
    `client_secret` VARCHAR(80)   NOT NULL,
    `name`          VARCHAR(100)  NOT NULL,
    `redirect_uri`  VARCHAR(2000) NOT NULL,
    `scope`         VARCHAR(255)  NOT NULL DEFAULT 'profile',
    `first_party`   TINYINT(1)   NOT NULL DEFAULT 0,
    `created_at`    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. OAuth Authorization Codes Table (no foreign keys)
CREATE TABLE IF NOT EXISTS `tbl4users_oauth_codes` (
    `code`         VARCHAR(80)   NOT NULL PRIMARY KEY,
    `client_id`    VARCHAR(80)   NOT NULL,
    `redirect_uri` VARCHAR(2000) NOT NULL,
    `username`     VARCHAR(50)   NOT NULL,
    `scope`        VARCHAR(255)  NOT NULL,
    `state`        VARCHAR(255)  NULL,
    `expires_at`   INT UNSIGNED  NOT NULL,
    `created_at`   TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_codes_client_id`  (`client_id`),
    INDEX `idx_codes_expires_at` (`expires_at`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. OAuth Access Tokens Table (no foreign keys)
CREATE TABLE IF NOT EXISTS `tbl4users_oauth_tokens` (
    `access_token` VARCHAR(120)  NOT NULL PRIMARY KEY,
    `client_id`    VARCHAR(80)   NOT NULL,
    `username`     VARCHAR(50)   NOT NULL,
    `scope`        VARCHAR(255)  NOT NULL,
    `expires_at`   INT UNSIGNED  NOT NULL,
    `created_at`   TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_tokens_client_id`  (`client_id`),
    INDEX `idx_tokens_username`   (`username`),
    INDEX `idx_tokens_expires_at` (`expires_at`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
