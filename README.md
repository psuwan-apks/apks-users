# APKS Web Application

APKS is a modern, lightweight, custom-built PHP web application leveraging a home-grown Model-View-Controller (MVC) pattern. It features an interactive layout, localized dual-language support (English/Thai), a dynamic drag-and-drop navigation menu manager, an integrated Schedule-X calendar system, secure session-based authentication, and Thai-compatible PDF generation via TCPDF.

---

## 🛠️ Technology Stack

- **Backend Core**: PHP 7.4+
- **Database Wrapper**: PDO-based MySQL utility handler
- **Frontend Framework**: Bootstrap 5.3.8 & Vanilla CSS
- **Icons**: FontAwesome (Pro/Thin variant icons)
- **Interactive Scripts & Dialogs**: jQuery 4.0.0, Popper.js, SortableJS, SweetAlert2 v11 (offline custom dialogs/confirms), and Select2 v4.1.0-rc.0 (offline styled searchable select dropdowns)
- **Calendar Engine**: Schedule-X Calendar v4.6.0 (Preact & core signals bundle)
- **PDF Generation**: TCPDF with pre-compiled Thai font mappings (THSarabunPSK)

---

## 📁 Directory Structure

```text
apks-web/
├── app/                        # Main Application Code (Protected)
│   ├── config/                 # Application configuration & paths mapping
│   │   └── config.php          # Central configuration & libraries loader
│   ├── data/                   # JSON-based data stores
│   │   └── calendar-events.json
│   ├── lang/                   # Localization translation tables
│   │   ├── en.php              # English localization strings
│   │   └── th.php              # Thai localization strings
│   ├── lib/                    # Helper libraries & custom functions
│   │   ├── tcpdf/              # TCPDF core library
│   │   ├── functions.php       # General helper utilities (UUIDs, event logging)
│   │   ├── functions-datetime.php # Gregorian (AD) <=> Buddhist (BE) converters
│   │   ├── functions-lang.php  # Translation dictionary manager
│   │   └── functions-mysql.php # MySQL PDO wrappers & insert query builder
│   ├── menu/                   # Menu definition schema
│   │   └── sidebar.json        # Active sidebar layout configuration
│   ├── model/                  # Data models & business logic
│   │   ├── calendar.php        # Calendar event processing
│   │   ├── guest.php           # Guest actions routing
│   │   ├── oauth.php           # OAuth2 provider model and actions router
│   │   ├── user.php            # Authentication, registration & SSO callback logic
│   │   └── users.json          # Credentials store file (hashed)
│   └── view/                   # Layout & page templates
│       ├── oauth/              # OAuth provider templates (authorize, client dashboard)
│       ├── user/               # User account templates (SSO redirect, provider login, register)
│       ├── 404.php             # Page not found error layout
│       ├── calendar.php        # Calendar view page (Schedule-X integration)
│       ├── layout.php          # Main responsive dashboard layout wrapper
│       ├── menu-footer.php     # Footer bar wrapper
│       ├── menu-navbar.php     # Top navbar wrapper
│       ├── menu-sidebar.php    # Dynamic sidebar component wrapper
│       └── page-dashboard.php  # Main dashboard view content
└── public_html/                # Web Server Document Root (Publicly Accessible)
    ├── assets/                 # Frontend libraries, stylesheets, & assets
    ├── ex-genpdf.php           # TCPDF Thai script PDF generation example
    ├── index.php               # Front controller & request router
    ├── logout.php              # Sign-out logic and session destruction
    ├── menu-manager.php        # Visual Sidebar Menu Manager (Developer tool)
    ├── oauth-callback-demo.php # Sandbox simulation of a third-party app login flow
    ├── oauth-token.php         # Secure OAuth2 token exchange endpoint
    ├── oauth-userinfo.php      # OAuth2 authenticated user profile endpoint
    └── process.php             # Asynchronous request controller
```

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
- Accounts are verified against hashed user records stored locally inside `app/model/users.json`.

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

## 🚀 Setup & Installation

### Prerequisites
- PHP 7.4 or higher
- Apache, Nginx, or PHP Built-in Server
- Write permissions enabled on folders:
  - `app/logs/` (for logging events)
  - `app/menu/` (for updating sidebar configurations)
  - `app/model/` (for seeding and saving local users)
  - `app/data/` (for storing OAuth clients, tokens, and authorization codes)

### Setup Steps
1. **Configure Web Server**: Point your host's document root to the `public_html/` directory.
   - *Alternative (PHP Built-in Server)*: Navigate to the repository root and run:
     ```bash
     php -S localhost:8000 -t public_html
     ```
2. **Setup Directories**: Ensure the application configuration is resolved correctly in `app/config/config.php`.
3. **Database Configuration** (Optional): If MySQL queries are needed, customize connection variables in `app/lib/functions-mysql.php`:
   ```php
   const DB_HOST = 'localhost';
   const DB_USER = 'root';
   const DB_PASS = 'your_password';
   const DB_NAME = 'your_db';
   const DB_PORT = 3306; // or 8889
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

1. **Advanced User & Account Management Console**:
   - Build a visual administrative dashboard to manage user accounts, assign user roles, toggle statuses, and audit security events.
2. **Enhanced Client Management Features**:
   - Introduce toggles to configure/revoke client first-party status, manage supported redirect URIs, configure customizable client token lifetimes, and deactivate/ban accounts.
3. **Menu Manager Advanced Features**:
   - Implement import/export functionality for `sidebar.json` with visual JSON schema validation.
   - Add localization helper tools within the menu editor to automatically map translation keys directly to `app/lang/en.php` and `app/lang/th.php`.
4. **OAuth Access Token Revocation (RFC 7009)**:
   - Expose the `/oauth-revoke.php` endpoint to allow third-party applications to cleanly invalidate active access tokens.
5. **Database Maintenance & Log Viewer UI**:
   - Provide a developers-only system panel to view raw MySQL connections, check table statuses, inspect execution logs, and monitor execution bottlenecks.
