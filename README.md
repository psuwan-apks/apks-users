# APKS User Management & OAuth Identity Provider

APKS is a modern, lightweight, custom-built PHP web application leveraging a home-grown Model-View-Controller (MVC) pattern. It acts as the central authentication authority (Single Sign-On or SSO) and standard-compliant **OAuth2 Identity Provider (IdP)** for the APKS platform ecosystem.

It features an interactive layout, localized dual-language support (English/Thai), a dynamic drag-and-drop navigation menu manager, an integrated Schedule-X calendar system, secure session-based authentication, and Thai-compatible PDF generation via TCPDF.

---

## 🛠️ Technology Stack

- **Backend Core**: PHP 7.4+
- **Database**: MySQL (MyISAM Storage Engine, strict index structures, and app-layer referential integrity)
- **Frontend Framework**: Bootstrap 5.3.8 & Vanilla CSS
- **Icons**: FontAwesome (Pro/Thin variant icons)
- **Interactive Scripts & Dialogs**: jQuery 4.0.0, Popper.js, SortableJS, SweetAlert2 v11 (offline custom dialogs/confirms), and Select2 v4.1.0-rc.0 (offline styled searchable select dropdowns)
- **Calendar Engine**: Schedule-X Calendar v4.6.0 (Preact & core signals bundle)
- **PDF Generation**: TCPDF with pre-compiled Thai font mappings (THSarabunPSK)

---

## 📁 Directory Structure

```text
app-web/                        # Parent workspace directory
├── assets/                     # ⭐ Shared frontend assets (used by all APKS projects)
│   ├── bootstrap-5.3.8/        # Bootstrap CSS & JS
│   ├── css/                    # Custom layout stylesheets (sidebar, footer)
│   ├── fonts/                  # Google Sans, FontAwesome (offline)
│   ├── images/                 # Logos, flags, favicons
│   ├── js/                     # jQuery, Popper.js, Temporal polyfill
│   ├── schedule-x-4.6.0/      # Schedule-X calendar (Preact, signals)
│   ├── select2/                # Select2 searchable dropdowns
│   └── sweetalert2/            # SweetAlert2 dialog library
│
└── apks-users/                 # ← This repository
    ├── app/                    # Main Application Code (Protected)
    │   ├── config/             # Application configuration & paths mapping
    │   │   └── config.php      # Central configuration & libraries loader
    │   ├── data/               # JSON-based data stores
    │   │   └── calendar-events.json
    │   ├── lang/               # Localization translation tables
    │   │   ├── en.php          # English localization strings
    │   │   └── th.php          # Thai localization strings
    │   ├── lib/                # Helper libraries & custom functions
    │   │   ├── tcpdf/          # TCPDF core library
    │   │   ├── functions.php   # General helper utilities (UUIDs, event logging)
    │   │   ├── functions-datetime.php # Gregorian (AD) <=> Buddhist (BE) converters
    │   │   ├── functions-lang.php     # Translation dictionary manager
    │   │   └── functions-mysql.php    # MySQL PDO wrappers & insert query builder
    │   ├── menu/               # Menu definition schema
    │   │   └── sidebar.json    # Active sidebar layout configuration
    │   ├── model/              # Data models & business logic
    │   │   ├── calendar.php    # Calendar event processing
    │   │   ├── guest.php       # Guest actions routing
    │   │   ├── oauth.php       # OAuth2 provider model & actions router
    │   │   ├── user.php        # Authentication, registration & SSO callback logic
    │   │   └── users.json      # Credentials store file (hashed)
    │   └── view/               # Layout & page templates
    │       ├── oauth/          # OAuth provider templates (authorize, client dashboard)
    │       ├── user/           # User account templates (SSO redirect, provider login, register)
    │       ├── 404.php         # Page not found error layout
    │       ├── calendar.php    # Calendar view page (Schedule-X integration)
    │       ├── layout.php      # Main responsive dashboard layout wrapper
    │       ├── menu-footer.php # Footer bar wrapper
    │       ├── menu-navbar.php # Top navbar wrapper
    │       ├── menu-sidebar.php # Dynamic sidebar component wrapper
    │       └── page-dashboard.php # Main dashboard view content
    ├── databases/              # Database schema & verification utilities
    │   ├── db4apks_webapp_backup.sql # Database backup template
    │   ├── schema.sql          # Clean MyISAM relational schema (no foreign keys)
    │   └── verify.php          # Database integrity & credential validation script
    └── public_html/            # Web Server Document Root (Publicly Accessible)
        ├── ex-genpdf.php       # TCPDF Thai script PDF generation example
        ├── index.php           # Front controller & request router
        ├── logout.php          # Sign-out logic and session destruction
        ├── menu-manager.php    # Visual Sidebar Menu Manager (Developer tool)
        ├── oauth-callback-demo.php # Sandbox simulation of a third-party app login flow
        ├── oauth-token.php     # Secure OAuth2 token exchange endpoint
        ├── oauth-userinfo.php  # OAuth2 authenticated user profile endpoint
        └── process.php         # Asynchronous request controller
```

> **Note:** The `assets/` folder lives at the `app-web/` parent level and is **shared across all APKS projects** (e.g. `apks-users`, `apks-web`). All HTML/PHP templates reference it via the relative path `../../assets/`.

---

## ✨ Core Features & Integration

### 1. Simple Custom MVC Router
Requests are routed dynamically via the front controller `public_html/index.php`.
- The router accepts query parameters: `?page=<page_id>&action=<action_id>`.
- The requested page loads its corresponding model `app/model/<page_id>.php` and outputs views into `app/view/<page_id>.php`.
- Views are seamlessly injected into the global frame wrapper `app/view/layout.php` unless `bypass_layout` is activated (e.g., when rendering standalone PDFs).

### 2. Localization & Translations
Dual-language support (English and Thai) is toggled globally.
- Session-based locale state (`$_SESSION['LANGUAGE'] = 'th' | 'en'`) determines which dictionary is loaded.
- Standard utility components read labels dynamically using key mappings defined in `app/lang/en.php` and `app/lang/th.php`.
- Asynchronous translation switches are processed instantly via `public_html/process.php?CMD2PROCESS=LANGUAGE_SET`.

### 3. Dynamic & Visual Sidebar Menu Manager
- **Dynamic Configuration**: The sidebar structure is read from `app/menu/sidebar.json`. Highlighting is computed using predefined active rules (`exact_page`, `in_pages`, `exact_page_action`, or `uri_match`).
- **Interactive Manager**: Developers can navigate to `public_html/menu-manager.php` to access a drag-and-drop sidebar hierarchy designer powered by **SortableJS**. You can add new links, create collapsible folders, nest sub-items, and edit titles, icons, or paths visually.
- **Auto-Backups**: Saving configurations in the menu manager creates a backup of the previous configuration as `app/menu/sidebar.json.bak` automatically.

### 4. Schedule-X Calendar
- The calendar view (`app/view/calendar.php`) loads the Preact-based **Schedule-X** core bundle locally.
- Supported views: Month Grid, Week, Day, and Month Agenda.
- Full UI translation is mapped for Thai localization (`th-TH`).
- Built-in theme parameters configure it to run in **Dark Mode** matching custom capsule-shaped button styles.

### 5. Session Authentication & Single Sign-On (SSO)
- The application uses a centralized **OAuth2 Single Sign-On (SSO)** architecture for authentication.
- Access is initiated at `user-login.php` (SSO Initiator), which redirects unauthenticated users to the OAuth authorize endpoint.
- Real credentials entry is handled on a separate, dedicated `provider-login.php` form.
- Direct local method calls are used for the token exchange back-channel in `oauth-callback` to prevent deadlocks when running on single-threaded environments (e.g. PHP built-in server).
- Accounts are verified against hashed user records stored locally inside the MySQL database.

### 6. OAuth2 Identity Provider (IdP)
The system acts as a standard-compliant stateless OAuth2 Identity Provider (IdP) serving internal first-party and external third-party client applications.
- **First-Party Client Auto-Approval**: The host application itself runs as a registered first-party client (`apks-users-client`). It bypasses the consent prompt and logs users in seamlessly.
- **Third-Party Client Consent Flow**: Standard client applications (such as the sandbox simulator) are presented with a consent screen prompting the user to manually Approve or Deny access.
- **End-user Management**: Authorized developers can manage client apps directly on the OAuth Client dashboard.

### 7. Thai PDF Generation
- Integrates TCPDF with the Thai National font **THSarabunPSK** (regular and bold).
- Uses native UTF-8 Unicode font handling directly without requiring CP874 encodings, displaying Thai characters, vowel marks, and tone marks with correct alignment.
- An example implementation is provided in `public_html/ex-genpdf.php`.

### 8. Core Helpers
- **`apksDATEBE` (`app/lib/functions-datetime.php`)**: Translates date strings between Western Gregorian calendar (AD) and Thai Buddhist Era calendar (BE). Formats long and short Thai month names.
- **`db_connected` (`app/lib/functions-mysql.php`)**: Establishes PDO database connectors using strict exception modes and real prepared statements. Includes helper queries like `insertRecord`.
- **General Utilities**: Includes random token generation, custom RFC-4122 v4 UUID binary converters, and JSON-formatted file event logging.

### 9. Administrative User Management Console
A comprehensive suite to manage user records:
- **Visual Console**: Access `?page=users&action=users-view` to view a responsive dual-column list of users, search, reset passwords, or delete users (restricted to `admin` by default).
- **SweetAlert2 Dialogs**: Modals and confirm windows provide fluid interactive actions and warnings for critical operations like account deletions.
- **SSO Cleanup**: Deleting users automatically purges active/orphaned OAuth codes and tokens to maintain integrity.

---

## 🗄️ Database Architecture & Schema

The system uses a **No-Foreign-Key (MyISAM-compatible) relational schema** hosted on the `db4apks_webapp` MySQL database. Referential integrity is strictly maintained by application-layer operations.

1. **User Accounts (`tbl4users_users`)**
   Stores credentials and usernames. Default seeded users are `admin` (`admin123`) and `user` (`password`).
2. **Registered OAuth Clients (`tbl4users_oauth_clients`)**
   Stores application profiles allowed to request client tokens, redirect targets, and the `first_party` bypass flag.
3. **One-Time Codes (`tbl4users_oauth_codes`)**
   Stores short-lived authorization codes (expired after 5 minutes) mapped to requests.
4. **Access Tokens (`tbl4users_oauth_tokens`)**
   Bearer access credentials mapped to client applications and users (expires in 1 hour).

---

## 🔑 Using the OAuth2 Provider

To connect an application (such as an internal tool or dashboard) to this OAuth2 provider, use the following integration details:

### 1. Register a Client
Navigate to **OAuth Clients** in the sidebar (or visit `http://localhost:8000/index.php?page=oauth&action=clients`) and create a client. You will receive:
- **Client ID**: e.g., `client_abcd1234efgh`
- **Client Secret**: e.g., `secret_5678ijkl...`

### 2. Authorization Code Flow

1. **Redirect User to Authorize**:
   Redirect the browser to:
   ```text
   GET http://localhost:8000/index.php?page=oauth&action=authorize
     &client_id={YOUR_CLIENT_ID}
     &redirect_uri={YOUR_REGISTERED_REDIRECT_URI}
     &response_type=code
     &scope=profile
     &state={RANDOM_CSRF_STATE}
   ```

2. **Handle the Callback**:
   The authorization server redirects the browser back to your `redirect_uri` with an authorization code:
   ```text
   GET {YOUR_REDIRECT_URI}?code={AUTH_CODE}&state={STATE}
   ```

3. **Exchange Code for Access Token**:
   From your application backend, make a POST request:
   ```text
   POST http://localhost:8000/oauth-token.php
   
   Headers:
     Content-Type: application/x-www-form-urlencoded
     Authorization: Basic {BASE64_ENCODED_CLIENT_ID:CLIENT_SECRET} (or send client_id and client_secret in POST body)
   
   POST Body Parameters:
     grant_type=authorization_code
     code={AUTH_CODE}
     redirect_uri={YOUR_REDIRECT_URI}
     client_id={YOUR_CLIENT_ID}
     client_secret={YOUR_CLIENT_SECRET}
   ```
   **Response**:
   ```json
   {
     "access_token": "token_abc123...",
     "token_type": "Bearer",
     "expires_in": 3600,
     "scope": "profile"
   }
   ```

4. **Retrieve User Profile Info**:
   Request the profile endpoints:
   ```text
   GET http://localhost:8000/oauth-userinfo.php
   
   Headers:
     Authorization: Bearer {ACCESS_TOKEN}
   ```
   **Response**:
   ```json
   {
     "sub": "admin",
     "username": "admin",
     "scope": "profile"
   }
   ```

---

## 📡 User Management REST API

The system exposes a secure administrative API at `/api-users.php` to enable external/connected applications to programmatically manage user credentials.

### 1. Authentication
The API utilizes **Client Credentials** authentication. The caller must authenticate using a registered OAuth Client (`client_id` and `client_secret` from `tbl4users_oauth_clients`):
- **HTTP Basic Authentication**: Provide `client_id` as the username and `client_secret` as the password.
- **Request Parameters / JSON Body**: Include `client_id` and `client_secret` parameters in the URL query string, POST request body, or raw JSON payload.

### 2. API Actions & Endpoints

#### A. List Users
- **Method**: `GET`
- **Endpoint**: `/api-users.php?action=list` (or simply `GET /api-users.php`)
- **Query Parameters**:
  - `q` (Optional): Search filter to query usernames matching a substring.
- **Response (200 OK)**:
  ```json
  {
    "status": "success",
    "users": [
      { "id": 1, "username": "admin", "created_at": "2026-06-17 04:15:50" },
      { "id": 2, "username": "user", "created_at": "2026-06-17 04:15:50" }
    ]
  }
  ```

#### B. Get User Details
- **Method**: `GET`
- **Endpoint**: `/api-users.php?action=get&username={username}` (or simply `GET /api-users.php?username={username}`)
- **Response (200 OK)**:
  ```json
  {
    "status": "success",
    "user": {
      "id": 2,
      "username": "user",
      "created_at": "2026-06-17 04:15:50"
    }
  }
  ```
- **Response (404 Not Found)**:
  ```json
  { "error": "not_found", "error_description": "User not found." }
  ```

#### C. Create User
- **Method**: `POST`
- **Endpoint**: `/api-users.php?action=create` (or simply `POST /api-users.php`)
- **Headers**: `Content-Type: application/json` or `application/x-www-form-urlencoded`
- **Request Body**:
  ```json
  {
    "username": "new_api_user",
    "password": "secure_password"
  }
  ```
- **Response (201 Created)**:
  ```json
  { "status": "success", "message": "User created successfully." }
  ```
- **Response (409 Conflict)**:
  ```json
  { "error": "conflict", "error_description": "Username already exists." }
  ```

#### D. Update User Password
- **Method**: `PUT` or `PATCH`
- **Endpoint**: `/api-users.php?action=update`
- **Request Body**:
  ```json
  {
    "username": "new_api_user",
    "password": "new_secure_password"
  }
  ```
- **Response (200 OK)**:
  ```json
  { "status": "success", "message": "Password updated successfully." }
  ```

#### E. Delete User
- **Method**: `DELETE`
- **Endpoint**: `/api-users.php?action=delete&username={username}`
- **Response (200 OK)**:
  ```json
  { "status": "success", "message": "User deleted successfully." }
  ```
  *(Note: This performs a cascading cleanup, revoking any active authorization codes and tokens associated with the deleted user).*

---

## 🚀 Setup & Installation

### Prerequisites
- PHP 7.4 or higher
- MySQL / MariaDB Server
- Write permissions enabled on folders:
  - `app/logs/` (for logging events)
  - `app/menu/` (for updating sidebar configurations)
  - `app/model/` (for seeding and saving local users)
  - `app/data/` (for storing OAuth clients, tokens, and authorization codes)

### Setup Steps
1. **Database Import**: Import the schema file `databases/schema.sql` to your MySQL instance.
2. **Database Configuration**: Customize the connection variables in `app/lib/functions-mysql.php`:
   ```php
   const DB_HOST = 'localhost';
   const DB_USER = 'root';
   const DB_PASS = 'your_password';
   const DB_NAME = 'db4apks_webapp';
   const DB_PORT = 3306;
   ```
3. **Verify Setup**: Validate the database connection, table structures, storage engine, indexes, and credentials by running the CLI validation script:
   ```bash
   php databases/verify.php
   ```
4. **Shared Assets**: Ensure the shared `assets/` folder exists at the parent workspace level (`app-web/assets/`). All views reference assets via `../../assets/`.
5. **Configure Web Server**: Point your host's document root to the `public_html/` directory.
   - *Alternative (PHP Built-in Server)*: Navigate to the repository root and run:
     ```bash
     php -S localhost:8000 -t public_html
     ```

### Access Points
- **Web App / Dashboard**: `http://localhost:8000/`
- **SSO Login Initiator**: `http://localhost:8000/index.php?page=user&action=user-login`
- **Visual Sidebar Menu Manager**: `http://localhost:8000/menu-manager.php`
- **OAuth Clients Dashboard**: `http://localhost:8000/index.php?page=oauth&action=clients`
- **OAuth Callback Demo Simulator**: `http://localhost:8000/oauth-callback-demo.php`
- **PDF Generation Example**: `http://localhost:8000/ex-genpdf.php`

---

## 🔮 Next Development Steps

To expand the capabilities of the APKS platform, consider the following next development milestones:

1. **Enhanced Client Management Features**:
   - Introduce toggles to configure/revoke client first-party status, manage supported redirect URIs, configure customizable client token lifetimes, and deactivate/ban accounts.
2. **Menu Manager Advanced Features**:
   - Implement import/export functionality for `sidebar.json` with visual JSON schema validation.
   - Add localization helper tools within the menu editor to automatically map translation keys directly to `app/lang/en.php` and `app/lang/th.php`.
3. **OAuth Access Token Revocation (RFC 7009)**:
   - Expose the `/oauth-revoke.php` endpoint to allow third-party applications to cleanly invalidate active access tokens.
4. **Database Maintenance & Log Viewer UI**:
   - Provide a developers-only system panel to view raw MySQL connections, check table statuses, inspect execution logs, and monitor execution bottlenecks.
