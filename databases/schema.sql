-- MySQL Unified Setup Schema for APKS Web Application
-- Database: db4apks_webapp
-- Notes: Foreign keys removed; referential integrity enforced at application layer.
--        Engine changed to MyISAM for simplicity.
--        Indexes added on frequently-queried columns.
--        Seeded with starting user: nimda (password: nimda123, role: admin).

CREATE DATABASE IF NOT EXISTS `db4apks_webapp` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `db4apks_webapp`;

-- Drop tables if they exist to support fresh clean install
DROP TABLE IF EXISTS `tbl4users_user_roles`;
DROP TABLE IF EXISTS `tbl4users_roles`;
DROP TABLE IF EXISTS `tbl4users_permissions`;
DROP TABLE IF EXISTS `tbl4users_oauth_consents`;
DROP TABLE IF EXISTS `tbl4users_oauth_tokens`;
DROP TABLE IF EXISTS `tbl4users_oauth_codes`;
DROP TABLE IF EXISTS `tbl4users_oauth_clients`;
DROP TABLE IF EXISTS `tbl4users_users`;

-- 1. Users Table
CREATE TABLE `tbl4users_users` (
    `id`                    INT AUTO_INCREMENT PRIMARY KEY,
    `uuid`                  CHAR(36)     UNIQUE,
    `username`              VARCHAR(50)  NOT NULL UNIQUE,
    `password_hash`         VARCHAR(255) NOT NULL,
    `application`           VARCHAR(100) NOT NULL,
    `email_verified`        TINYINT(1)   DEFAULT 0,
    `status`                VARCHAR(50)  DEFAULT 'active',
    `failed_login_attempts` INT          DEFAULT 0,
    `created_at`            TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. OAuth Clients Table
CREATE TABLE `tbl4users_oauth_clients` (
    `client_id`             VARCHAR(80)   NOT NULL PRIMARY KEY,
    `client_secret`         VARCHAR(80)   NOT NULL,
    `name`                  VARCHAR(100)  NOT NULL,
    `redirect_uri`          VARCHAR(2000) NOT NULL,
    `allowed_redirect_uris` JSON          NULL,
    `allowed_grant_types`   JSON          NULL,
    `allowed_scopes`        JSON          NULL,
    `scope`                 VARCHAR(255)  NOT NULL DEFAULT 'profile',
    `first_party`           TINYINT(1)    NOT NULL DEFAULT 0,
    `created_at`            TIMESTAMP     DEFAULT CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. OAuth Authorization Codes Table (no foreign keys)
CREATE TABLE `tbl4users_oauth_codes` (
    `code`                  VARCHAR(80)   NOT NULL PRIMARY KEY,
    `client_id`             VARCHAR(80)   NOT NULL,
    `redirect_uri`          VARCHAR(2000) NOT NULL,
    `username`              VARCHAR(50)   NOT NULL,
    `scope`                 VARCHAR(255)  NOT NULL,
    `state`                 VARCHAR(255)  NULL,
    `code_challenge`        VARCHAR(255)  NULL,
    `code_challenge_method` VARCHAR(50)   NULL,
    `expires_at`            INT UNSIGNED  NOT NULL,
    `created_at`            TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_codes_client_id`  (`client_id`),
    INDEX `idx_codes_expires_at` (`expires_at`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. OAuth Access Tokens Table (no foreign keys)
CREATE TABLE `tbl4users_oauth_tokens` (
    `access_token`             VARCHAR(120)  NOT NULL PRIMARY KEY,
    `client_id`                VARCHAR(80)   NOT NULL,
    `username`                 VARCHAR(50)   NOT NULL,
    `scope`                    VARCHAR(255)  NOT NULL,
    `refresh_token`            VARCHAR(120)  NULL,
    `refresh_token_expires_at` INT UNSIGNED  NULL,
    `is_revoked`               TINYINT(1)    DEFAULT 0,
    `expires_at`               INT UNSIGNED  NOT NULL,
    `created_at`               TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_tokens_client_id`  (`client_id`),
    INDEX `idx_tokens_username`   (`username`),
    INDEX `idx_tokens_expires_at` (`expires_at`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. OAuth Consents Table (no foreign keys)
CREATE TABLE `tbl4users_oauth_consents` (
    `id`             INT AUTO_INCREMENT PRIMARY KEY,
    `user_id`        INT           NOT NULL,
    `client_id`      VARCHAR(80)   NOT NULL,
    `scopes_granted` JSON          NOT NULL,
    `granted_at`     TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_consents_user_id`   (`user_id`),
    INDEX `idx_consents_client_id` (`client_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Roles Table
CREATE TABLE `tbl4users_roles` (
    `id`             INT AUTO_INCREMENT PRIMARY KEY,
    `role_name`      VARCHAR(50)   NOT NULL UNIQUE,
    `created_at`     TIMESTAMP     DEFAULT CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. User Roles Table
CREATE TABLE `tbl4users_user_roles` (
    `username`       VARCHAR(50)   NOT NULL,
    `role_name`      VARCHAR(50)   NOT NULL,
    `created_at`     TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`username`, `role_name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Permissions Table
CREATE TABLE `tbl4users_permissions` (
    `id`             INT AUTO_INCREMENT PRIMARY KEY,
    `permission_name` VARCHAR(100)  NOT NULL UNIQUE,
    `created_at`     TIMESTAMP     DEFAULT CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- SEED DATA
-- ============================================================

-- Seed Default Users
-- nimda (password: nimda123)
-- user (password: password)
INSERT INTO `tbl4users_users` (`id`, `uuid`, `username`, `password_hash`, `application`, `email_verified`, `status`) VALUES
(1, '0cb8a960-bcd4-4002-8613-43cbc071829b', 'nimda', '$2y$10$htLWBV.JZAK.Ia80bzUQBO.WrMeASw0h9xxOGmWeGOaPeMH6SZ12e', 'default_app', 1, 'active'),
(2, '2eabf525-aa60-4d5a-a6ff-5c325cc219aa', 'user', '$2y$10$ctrNHJg/sLVcW1ZaiT8beO1THk5EFUBl1OegEtoa2ZzHpI.BIdmxe', 'default_app', 0, 'active');

-- Seed Default Roles
INSERT INTO `tbl4users_roles` (`id`, `role_name`) VALUES
(1, 'admin'),
(2, 'editor'),
(3, 'viewer');

-- Seed User Roles Mapping (nimda has admin role)
INSERT INTO `tbl4users_user_roles` (`username`, `role_name`) VALUES
('nimda', 'admin');

-- Seed Default OAuth Clients
INSERT INTO `tbl4users_oauth_clients` (`client_id`, `client_secret`, `name`, `redirect_uri`, `allowed_redirect_uris`, `allowed_grant_types`, `allowed_scopes`, `scope`, `first_party`) VALUES
(
    'demo-client', 
    'demo-secret', 
    'Demo Application', 
    'http://localhost:8000/oauth-callback-demo.php', 
    '["http://localhost:8000/oauth-callback-demo.php"]', 
    '["authorization_code"]', 
    '["profile"]', 
    'profile', 
    0
),
(
    'apks-users-client', 
    'apks-users-secret', 
    'APKS Users Portal (First-Party)', 
    'http://localhost:8000/index.php?page=user&action=oauth-callback', 
    '["http://localhost:8000/index.php?page=user&action=oauth-callback"]', 
    '["authorization_code"]', 
    '["profile"]', 
    'profile', 
    1
);
