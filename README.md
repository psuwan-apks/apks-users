# APKS Web Application

APKS is a modern, lightweight, custom-built PHP web application leveraging a home-grown Model-View-Controller (MVC) pattern. It features an interactive layout, localized dual-language support (English/Thai), a dynamic drag-and-drop navigation menu manager, an integrated Schedule-X calendar system, secure session-based authentication, and Thai-compatible PDF generation via FPDF.

---

## 🛠️ Technology Stack

- **Backend Core**: PHP 7.4+
- **Database Wrapper**: PDO-based MySQL utility handler
- **Frontend Framework**: Bootstrap 5.3.8 & Vanilla CSS
- **Icons**: FontAwesome (Pro/Thin variant icons)
- **Interactive Scripts**: jQuery 4.0.0, Popper.js, and SortableJS
- **Calendar Engine**: Schedule-X Calendar v4.6.0 (Preact & core signals bundle)
- **PDF Generation**: FPDF v1.9 with custom Thai font maps

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
│   │   ├── fpdf19/             # FPDF core library
│   │   ├── functions.php       # General helper utilities (UUIDs, event logging)
│   │   ├── functions-datetime.php # Gregorian (AD) <=> Buddhist (BE) converters
│   │   ├── functions-lang.php  # Translation dictionary manager
│   │   └── functions-mysql.php # MySQL PDO wrappers & insert query builder
│   ├── menu/                   # Menu definition schema
│   │   └── sidebar.json        # Active sidebar layout configuration
│   ├── model/                  # Data models & business logic
│   │   ├── calendar.php        # Calendar event processing
│   │   ├── guest.php           # Guest actions routing
│   │   ├── user.php            # Authentication & registration logic
│   │   └── users.json          # Credentials store file (hashed)
│   └── view/                   # Layout & page templates
│       ├── user/               # User account templates (login/register)
│       ├── 404.php             # Page not found error layout
│       ├── calendar.php        # Calendar view page (Schedule-X integration)
│       ├── layout.php          # Main responsive dashboard layout wrapper
│       ├── menu-footer.php     # Footer bar wrapper
│       ├── menu-navbar.php     # Top navbar wrapper
│       ├── menu-sidebar.php    # Dynamic sidebar component wrapper
│       └── page-dashboard.php  # Main dashboard view content
└── public_html/                # Web Server Document Root (Publicly Accessible)
    ├── assets/                 # Frontend libraries, stylesheets, & assets
    ├── ex-genpdf.php           # FPDF Thai script PDF generation example
    ├── index.php               # Front controller & request router
    ├── logout.php              # Sign-out logic and session destruction
    ├── menu-manager.php        # Visual Sidebar Menu Manager (Developer tool)
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

### 5. Session Authentication
- Login and registration scripts securely validate input and save accounts locally inside `app/model/users.json`.
- Passwords are securely encrypted using standard PHP `password_hash` with `PASSWORD_DEFAULT` and checked using `password_verify`.

### 6. Thai PDF Generation
- Integrates FPDF with the Thai National font **THSarabunNew**.
- Translates character mappings into CP874 encoding (`iconv('UTF-8', 'cp874', ...)`) so that Thai scripts, vowel marks, and tone marks render with correct layout geometry.
- An example implementation is provided in `public_html/ex-genpdf.php`.

### 7. Core Helpers
- **`apksDATEBE` (`app/lib/functions-datetime.php`)**: Translates date strings between Western Gregorian calendar (AD) and Thai Buddhist Era calendar (BE). Formats long and short Thai month names.
- **`db_connected` (`app/lib/functions-mysql.php`)**: Establishes PDO database connectors using strict exception modes and real prepared statements. Includes helper queries like `insertRecord`.
- **General Utilities**: Includes random token generation, custom RFC-4122 v4 UUID binary converters, and JSON-formatted file event logging.

---

## 🚀 Setup & Installation

### Prerequisites
- PHP 7.4 or higher
- Apache, Nginx, or PHP Built-in Server
- Write permissions enabled on folders:
  - `app/logs/` (for logging events)
  - `app/menu/` (for updating sidebar configurations)
  - `app/model/` (for seeding and saving local users)

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
- **Web App**: `http://localhost:8000/`
- **Sidebar Menu Manager (Developer tool)**: `http://localhost:8000/menu-manager.php`
- **PDF Generation Example**: `http://localhost:8000/ex-genpdf.php`
