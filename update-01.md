# Database Schema Update Roadmap: APKS OAuth System

This document outlines the necessary schema enhancements for the APKS OAuth system to achieve full OIDC compliance, robust security, and seamless user experience.

## 1. OAuth Token & Code Metadata (Security & Compliance)
To support secure token lifecycles and RFC 7009 (Token Revocation), update the existing token and code tables:

* **`tbl4users_oauth_tokens`**
    * `refresh_token` (VARCHAR/TEXT): Stores the long-lived token for silent renewals.
    * `refresh_token_expires_at` (DATETIME): Expiration timestamp for the refresh token.
    * `is_revoked` (BOOLEAN): Flag to track manual token invalidation.
* **`tbl4users_oauth_codes`**
    * `code_challenge` (VARCHAR): Hashed secret string for PKCE support.
    * `code_challenge_method` (VARCHAR): Method used (e.g., S256).

## 2. Identity Claims & User Management
Update the `tbl4users_users` table to conform to OIDC standards and improve account security.

* **`tbl4users_users`**
    * `uuid` (CHAR(36)): Immutable identifier used as the `sub` claim.
    * `email_verified` (BOOLEAN): Status of email verification.
    * `status` (VARCHAR): Account state (e.g., active, suspended, banned).
    * `failed_login_attempts` (INT): Counter for security monitoring and lockouts.

## 3. Granular Client Management
To prevent vulnerabilities such as open redirector exploits and ensure least-privilege access, enhance the `tbl4users_oauth_clients` table.

* **`tbl4users_oauth_clients`**
    * `allowed_redirect_uris` (JSON): A strict list of authorized callback URLs.
    * `allowed_grant_types` (JSON): Restricted list of permitted flows (e.g., `authorization_code`, `client_credentials`).
    * `allowed_scopes` (JSON): Scopes explicitly permitted for this client.

## 4. User Consent Tracking (New Table)
Implement the `tbl4users_oauth_consents` table to enable "remember me" functionality for application permissions.

| Column Name | Type | Description |
| :--- | :--- | :--- |
| `user_id` | INT/UUID | FK to `tbl4users_users` |
| `client_id` | INT/UUID | FK to `tbl4users_oauth_clients` |
| `scopes_granted` | JSON | List of approved scopes |
| `granted_at` | TIMESTAMP | Creation date of consent |

---

## Summary Architecture Map

| Table Name | Key Additions |
| :--- | :--- |
| `tbl4users_users` | `uuid`, `email_verified`, `status`, `failed_login_attempts` |
| `tbl4users_oauth_clients` | `allowed_redirect_uris`, `allowed_grant_types`, `allowed_scopes` |
| `tbl4users_oauth_codes` | `code_challenge`, `code_challenge_method` |
| `tbl4users_oauth_tokens` | `refresh_token`, `refresh_token_expires_at`, `is_revoked` |
| `tbl4users_oauth_consents` | `user_id`, `client_id`, `scopes`, `created_at` |
