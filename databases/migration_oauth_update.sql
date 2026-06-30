-- Migration: APKS OAuth System Updates
-- Based on update-01.md roadmap

-- 1. Users Table Updates
ALTER TABLE `tbl4users_users`
    ADD COLUMN `uuid` CHAR(36) UNIQUE AFTER `id`,
    ADD COLUMN `email_verified` TINYINT(1) DEFAULT 0 AFTER `application`,
    ADD COLUMN `status` VARCHAR(50) DEFAULT 'active' AFTER `email_verified`,
    ADD COLUMN `failed_login_attempts` INT DEFAULT 0 AFTER `status`;

-- 2. OAuth Clients Table Updates
ALTER TABLE `tbl4users_oauth_clients`
    ADD COLUMN `allowed_redirect_uris` JSON NULL AFTER `redirect_uri`,
    ADD COLUMN `allowed_grant_types` JSON NULL AFTER `allowed_redirect_uris`,
    ADD COLUMN `allowed_scopes` JSON NULL AFTER `allowed_grant_types`;

-- 3. OAuth Authorization Codes Table Updates
ALTER TABLE `tbl4users_oauth_codes`
    ADD COLUMN `code_challenge` VARCHAR(255) NULL AFTER `state`,
    ADD COLUMN `code_challenge_method` VARCHAR(50) NULL AFTER `code_challenge`;

-- 4. OAuth Access Tokens Table Updates
ALTER TABLE `tbl4users_oauth_tokens`
    ADD COLUMN `refresh_token` VARCHAR(120) NULL AFTER `scope`,
    ADD COLUMN `refresh_token_expires_at` INT UNSIGNED NULL AFTER `refresh_token`,
    ADD COLUMN `is_revoked` TINYINT(1) DEFAULT 0 AFTER `refresh_token_expires_at`;

-- 5. Create OAuth Consents Table
CREATE TABLE IF NOT EXISTS `tbl4users_oauth_consents` (
    `id`             INT AUTO_INCREMENT PRIMARY KEY,
    `user_id`        INT           NOT NULL,
    `client_id`      VARCHAR(80)   NOT NULL,
    `scopes_granted` JSON          NOT NULL,
    `granted_at`     TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_consents_user_id`   (`user_id`),
    INDEX `idx_consents_client_id` (`client_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
