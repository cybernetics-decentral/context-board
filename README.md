# Context Board

A lightweight, anonymous, JavaScript-free context board application built with vanilla PHP 8.x and flatfile JSON storage. No frameworks, no database, no JavaScript — just PHP, HTML, and CSS.

[![Test Suite](https://github.com/context-board/context-board/actions/workflows/test.yml/badge.svg)](https://github.com/Mr-Kumar-Abhishek/context-board/actions/workflows/test.yml)
![PHP](https://img.shields.io/badge/PHP-%3E%3D8.1-777bb4?logo=php)
![License](https://img.shields.io/badge/license-MIT-green)
![No JS](https://img.shields.io/badge/JavaScript-none!-red)

---

## Features

- **Anonymous posting** — no registration required; anyone can create threads and replies
- **Nested replies** — sub-thread support with configurable indentation (up to 10 levels)
- **Sub-boards / categories** — organize discussions into separate topic boards
- **Thread ranking** — sub-thread branch bump system keeps active discussions visible
- **Admin panel** — manage boards, moderate threads and replies, change password
- **Secure authentication** — Argon2id password hashing, CSRF protection, CSP headers
- **Rate limiting** — IP-based rate limiting to prevent spam (configurable per IP)
- **IP logging** — IPv4 and IPv6 address recording for all posts
- **Auto-refresh** — configurable meta-refresh for board and thread pages (30s default)
- **Zero JavaScript** — the entire frontend is built with HTML5 and CSS3 only
- **Flatfile storage** — JSON-based file storage; no database server required
- **No frameworks** — pure vanilla PHP; zero external runtime dependencies

## Requirements

- **PHP** 8.1 or later
- **Web server** — Apache with `mod_rewrite` (recommended) or PHP's built-in development server
- **Filesystem** — write permissions on the `data/` directory
- **No database** — all data is stored as flat JSON files under `data/boards/`

## Quick Start

### 1. Clone the repository

```bash
git clone https://github.com/context-board/context-board.git
cd context-board
```

### 2. Install dev dependencies (optional, for testing)

```bash
composer install
```

### 3. Configure your web server

**Apache (recommended):** Point the document root to `public/`. The included `.htaccess` handles URL rewriting.

**PHP built-in server (development only):**

```bash
php -S localhost:8080 -t public/
```

### 4. Set up the admin account

Navigate to `/setup` in your browser and create the first admin account. This route is only available when no admin account exists (i.e., `data/admin.json` is absent).

### 5. Configure the application

Edit [`src/config.php`](src/config.php) to adjust board settings, rate limits, password policy, and debug mode to suit your environment.

### 6. Start posting

Visit the root URL to see the board index, create threads, and post replies — all anonymously, no login required.

## Project Structure

```
.
├── public/                  # Web root — all HTTP requests go through index.php
│   ├── .htaccess            # Apache URL rewriting rules
│   ├── index.php            # Front controller (single entry point)
│   └── css/
│       └── style.css        # Application stylesheet
├── src/                     # Application source code
│   ├── config.php           # Centralized configuration
│   ├── Router.php           # URL router with path parameter support
│   ├── Template.php         # Simple PHP template renderer
│   ├── FlatfileStore.php    # JSON flatfile I/O abstraction
│   ├── Security.php         # CSRF tokens, CSP headers, input sanitization
│   ├── Validator.php        # Input validation rules
│   ├── IpLogger.php         # IP address logging (IPv4 & IPv6)
│   ├── Helpers.php          # Utility functions
│   ├── BoardController.php  # Board listing and thread display
│   ├── PostController.php   # Thread and reply creation
│   ├── AdminController.php  # Admin dashboard and moderation
│   └── AuthController.php   # Authentication and session management
├── templates/               # PHP templates (view layer)
│   ├── layout.php           # Shared HTML layout
│   ├── board_index.php      # Board listing page
│   ├── thread_list.php      # Thread list within a board
│   ├── thread_view.php      # Single thread with nested replies
│   ├── new_thread_form.php  # New thread creation form
│   ├── reply_form.php       # Reply form
│   ├── errors/              # HTTP error pages (400, 403, 404, 500)
│   └── admin/               # Admin panel templates
├── data/                    # Flatfile storage (create writable subdirectories)
│   ├── boards/              # Board and thread JSON files
│   ├── logs/                # Application logs
│   ├── ip_logs/             # IP address logs
│   └── tmp/                 # Temporary files
├── tests/                   # PHPUnit test suite
│   ├── Unit/                # Unit tests
│   ├── Integration/         # Integration tests
│   ├── TestCase.php         # Base test case class
│   └── bootstrap.php        # Test bootstrap
├── docs/                    # Project documentation (CC BY-SA 4.0)
│   ├── SRS.md               # Software Requirements Specification
│   ├── SDD.md               # Software Design Document
│   └── TDD_AGILE_PLAN.md    # Agile TDD implementation plan
├── wiki/                    # Wiki documentation (CC BY-SA 4.0)
│   └── Admin-Guide.md       # Admin usage guide
├── composer.json            # Composer configuration (dev dependencies only)
├── phpunit.xml              # PHPUnit configuration
├── LICENSE                  # MIT License (source code)
└── README.md
```

## Configuration

All application settings are centralized in [`src/config.php`](src/config.php). Key options:

| Setting | Default | Description |
|---------|---------|-------------|
| `default_max_threads` | 100 | Maximum threads per board |
| `threads_per_page` | 20 | Threads displayed per page |
| `max_message_length` | 10000 | Maximum characters per post |
| `max_subject_length` | 200 | Maximum characters per thread subject |
| `rate_limit_max_posts` | 5 | Max posts within the rate limit window |
| `rate_limit_window` | 60 | Rate limit window in seconds |
| `session_timeout` | 3600 | Admin session timeout in seconds |
| `auto_refresh_seconds` | 30 | Page auto-refresh interval |
| `password_algo` | `PASSWORD_ARGON2ID` | Password hashing algorithm |
| `debug` | `true` | Enable debug error display |

Set `debug` to `false` in production to suppress detailed error output.

## Testing

The test suite uses PHPUnit. Both unit and integration tests are included.

```bash
# Run all tests
composer test

# Run with coverage summary
composer test-coverage

# Run a specific test suite
./vendor/bin/phpunit --testsuite Unit
./vendor/bin/phpunit --testsuite Integration
```

CI runs automatically via GitHub Actions on every push and pull request across **PHP 8.1, 8.2, and 8.3**.

## Administration

| Route | Description |
|-------|-------------|
| `/setup` | Initial admin account creation (one-time) |
| `/admin/login` | Admin login |
| `/admin` | Admin dashboard |
| `/admin/boards` | Manage boards (create, rename, delete) |
| `/admin/boards/{id}` | Moderate a board's threads |
| `/admin/boards/{id}/thread/{id}` | Moderate a thread and its replies |
| `/admin/password` | Change admin password |

See [`wiki/Admin-Guide.md`](wiki/Admin-Guide.md) for a complete walkthrough of all admin operations.

## Design Philosophy

- **Simplicity first** — flat JSON files instead of a database; vanilla PHP instead of a framework
- **No JavaScript** — all interactivity is achieved through standard HTML forms and CSS; the application is fully functional with JS disabled
- **Anonymous by default** — no user accounts, no tracking, no cookies (except the admin session)
- **Defense in depth** — CSRF tokens on all state-changing operations, Content-Security-Policy headers, Argon2id password hashing, rate limiting, and strict session cookies

## Documentation

Full technical documentation is maintained in the [`docs/`](docs/) and [`wiki/`](wiki/) directories:

- **[Software Requirements Specification](docs/SRS.md)** — complete functional and non-functional requirements with traceable IDs (FR-XXX)
- **[Software Design Document](docs/SDD.md)** — architecture, data model, component design, and API specification
- **[Agile TDD Implementation Plan](docs/TDD_AGILE_PLAN.md)** — test-driven development plan with sprint breakdown
- **[Admin Usage Guide](wiki/Admin-Guide.md)** — step-by-step instructions for all admin operations

## License

This project uses a dual-license structure:

- **Source code** — [MIT License](LICENSE) © 2026 Abhishek Kumar
- **Documentation** (`docs/` and `wiki/`) — [CC BY-SA 4.0](docs/LICENSE.md) © 2026 Abhishek Kumar

## Author

**Abhishek Kumar** — [mr.kumar.abhishek@outlook.in](mailto:mr.kumar.abhishek@outlook.in)
