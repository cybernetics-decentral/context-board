# Software Design Document (SDD)

## Text Board Application

**Version:** 1.0  
**Date:** 2026-06-07  
**Document Status:** Draft  
**Based on:** [SRS.md](SRS.md) v1.0

---

## Table of Contents

1. [Introduction](#1-introduction)
  1.1 [Purpose](#11-purpose)
  1.2 [Scope](#12-scope)
  1.3 [Design Goals and Principles](#13-design-goals-and-principles)
  1.4 [References](#14-references)

2. [Architectural Design](#2-architectural-design)
  2.1 [High-Level Architecture](#21-high-level-architecture)
  2.2 [Request Lifecycle](#22-request-lifecycle)
  2.3 [Directory and File Layout](#23-directory-and-file-layout)
  2.4 [Technology Stack](#24-technology-stack)
  2.5 [Design Patterns Used](#25-design-patterns-used)

3. [Component Design](#3-component-design)
  3.1 [Front Controller (`public/index.php`)](#31-front-controller-publicindexphp)
  3.2 [Router (`src/Router.php`)](#32-router-srcrouterphp)
  3.3 [Configuration (`src/config.php`)](#33-configuration-srcconfigphp)
  3.4 [Board Controller (`src/BoardController.php`)](#34-board-controller-srcboardcontrollerphp)
  3.5 [Post Controller (`src/PostController.php`)](#35-post-controller-srcpostcontrollerphp)
  3.6 [Admin Controller (`src/AdminController.php`)](#36-admin-controller-srcadmincontrollerphp)
  3.7 [Auth Controller (`src/AuthController.php`)](#37-auth-controller-srcauthcontrollerphp)
  3.8 [Flatfile Store (`src/FlatfileStore.php`)](#38-flatfile-store-srcflatfilestorephp)
  3.9 [IP Logger (`src/IpLogger.php`)](#39-ip-logger-srciploggerphp)
  3.10 [Validator (`src/Validator.php`)](#310-validator-srcvalidatorphp)
  3.11 [Security (`src/Security.php`)](#311-security-srcsecurityphp)
  3.12 [Template Engine (`src/Template.php`)](#312-template-engine-srctemplatephp)
  3.13 [Helpers (`src/Helpers.php`)](#313-helpers-srchelpersphp)

4. [Data Design](#4-data-design)
  4.1 [Entity-Relationship Model (Logical)](#41-entity-relationship-model-logical)
  4.2 [File Organization](#42-file-organization)
  4.3 [JSON Schema Definitions](#43-json-schema-definitions)
  4.4 [Concurrency Control](#44-concurrency-control)
  4.5 [Atomic Write Strategy](#45-atomic-write-strategy)

5. [Interface Design](#5-interface-design)
  5.1 [URL Routing Table](#51-url-routing-table)
  5.2 [HTTP Request/Response Contracts](#52-http-requestresponse-contracts)
  5.3 [Page Flow Diagrams](#53-page-flow-diagrams)
  5.4 [CSS Class Naming Conventions](#54-css-class-naming-conventions)

6. [Algorithm Design](#6-algorithm-design)
  6.1 [Bump Score Computation](#61-bump-score-computation)
  6.2 [Reply Tree Construction (Flat-to-Tree)](#62-reply-tree-construction-flat-to-tree)
  6.3 [Depth-First Post Numbering](#63-depth-first-post-numbering)
  6.4 [Cascading Reply Deletion](#64-cascading-reply-deletion)
  6.5 [Thread Ranking Sort](#65-thread-ranking-sort)
  6.6 [Message Excerpt Generation](#66-message-excerpt-generation)

7. [Security Design](#7-security-design)
  7.1 [Authentication Flow](#71-authentication-flow)
  7.2 [CSRF Protection](#72-csrf-protection)
  7.3 [Input Sanitization Pipeline](#73-input-sanitization-pipeline)
  7.4 [Output Escaping](#74-output-escaping)
  7.5 [Rate Limiting](#75-rate-limiting)
  7.6 [Session Management](#76-session-management)
  7.7 [File System Security](#77-file-system-security)

8. [Error Handling Design](#8-error-handling-design)
  8.1 [Error Classification](#81-error-classification)
  8.2 [Exception Hierarchy](#82-exception-hierarchy)
  8.3 [Error Response Flow](#83-error-response-flow)
  8.4 [Logging Strategy](#84-logging-strategy)

9. [Deployment Design](#9-deployment-design)
  9.1 [Server Requirements](#91-server-requirements)
  9.2 [Initial Setup Flow](#92-initial-setup-flow)
  9.3 [Apache Configuration](#93-apache-configuration)
  9.4 [Nginx Configuration](#94-nginx-configuration)
  9.5 [File Permissions](#95-file-permissions)

10. [Testing Strategy](#10-testing-strategy)
  10.1 [Unit Testing](#101-unit-testing)
  10.2 [Integration Testing](#102-integration-testing)
  10.3 [Security Testing](#103-security-testing)
  10.4 [Test Data Management](#104-test-data-management)

---

## 1. Introduction

### 1.1 Purpose

This Software Design Document (SDD) translates the requirements defined in the [SRS.md](SRS.md) into a concrete technical design. It describes the architecture, components, interfaces, algorithms, and data structures that will be implemented to satisfy every functional and non-functional requirement. The SDD serves as the primary implementation guide for developers building the Text Board Application.

### 1.2 Scope

The design covers the complete Text Board Application:

- **Front Controller** routing all HTTP requests
- **Controller layer** handling board display, posting, admin operations, and authentication
- **Data access layer** managing flatfile JSON storage with file locking
- **Template layer** rendering HTML5+CSS3 pages (no JavaScript)
- **Security layer** providing CSRF protection, input sanitization, output escaping, rate limiting, and session management
- **Algorithms** for sub-thread bump scoring, reply tree construction, cascading deletion, and thread ranking

The design does **not** cover: database backends, JavaScript frameworks, PHP frameworks, or third-party Composer packages.

### 1.3 Design Goals and Principles

| Principle | Description |
|---|---|
| **No-Framework** | Pure PHP 8.x with no external dependencies. Every component is hand-written and self-contained. |
| **Server-Rendered** | All HTML is generated server-side. Zero JavaScript. Interactivity via HTML forms and CSS3 only. |
| **Flatfile Simplicity** | JSON files on disk replace a database. Human-readable, debuggable, zero-configuration. |
| **Separation of Concerns** | Clear boundaries between routing, business logic, data access, and presentation. |
| **Defense in Depth** | Multiple security layers: input validation → sanitization → output escaping → CSP headers. |
| **Concurrency Safety** | Advisory file locking (`flock`) with atomic write-via-temp-file pattern for all mutable data. |
| **Fail Safe** | Graceful degradation on corrupt data; meaningful HTTP status codes; no information leakage in errors. |
| **Stateless for Anonymous Users** | No sessions for posters; sessions only for admin authentication. |

### 1.4 References

| Ref # | Document | Description |
|---|---|---|
| [1] | [SRS.md](SRS.md) | Software Requirements Specification for this application. |
| [2] | IEEE Std 1016-2009 | IEEE Standard for Information Technology — Systems Design — Software Design Descriptions. |
| [3] | PHP Manual: `flock()` | https://www.php.net/manual/en/function.flock.php |
| [4] | PHP Manual: `password_hash()` | https://www.php.net/manual/en/function.password-hash.php |
| [5] | OWASP Cheat Sheet Series | https://cheatsheetseries.owasp.org/ |

---

## 2. Architectural Design

### 2.1 High-Level Architecture

The application follows a **Model-View-Controller (MVC)** pattern adapted for a flatfile backend, with a **Front Controller** dispatching all requests through a unified entry point.

```
                         +----------------------------+
                         |       Web Browser          |
                         |  (HTML5 + CSS3, No JS)     |
                         +-------------+--------------+
                                       |
                                   HTTP/HTTPS
                                       |
                         +-------------v--------------+
                         |    public/index.php        |
                         |    (Front Controller)       |
                         +-------------+--------------+
                                       |
                         +-------------v--------------+
                         |        Router.php          |
                         |   URL → Controller::method  |
                         +-------------+--------------+
                                       |
              +------------+----------+----------+------------+
              |            |                     |            |
    +---------v--+  +------v-----+  +----------v-+  +-------v------+
    | Board      |  | Post       |  | Admin      |  | Auth         |
    | Controller |  | Controller |  | Controller |  | Controller   |
    +------+-----+  +------+-----+  +------+-----+  +------+------+
           |               |                |               |
           +-------+-------+----------------+-------+
                   |                                |
         +---------v---------+          +-----------v----------+
         |   FlatfileStore   |          |     IpLogger         |
         |  (Read/Write/Lock)|          | (Append-Only Log)    |
         +---------+---------+          +-----------+----------+
                   |                                |
         +---------v---------+                      |
         |   Filesystem       |<---------------------+
         |  (data/ directory) |
         +--------------------+
```

**Layer Responsibilities:**

| Layer | Components | Responsibility |
|---|---|---|
| **Presentation** | `templates/`, `public/css/style.css` | HTML rendering, CSS styling. No logic beyond simple conditionals/loops in templates. |
| **Controller** | `BoardController`, `PostController`, `AdminController`, `AuthController` | Request validation, business logic orchestration, HTTP response construction (redirects, status codes). |
| **Data Access** | `FlatfileStore`, `IpLogger` | File I/O, JSON encoding/decoding, advisory locking, atomic writes. |
| **Infrastructure** | `Router`, `config.php`, `Security`, `Validator`, `Helpers`, `Template` | Cross-cutting concerns: routing, configuration, security primitives, input validation, utility functions. |

### 2.2 Request Lifecycle

Every HTTP request follows this sequence:

```
1. Web server receives request
       │
2. URL rewriting routes to public/index.php (if not a static file)
       │
3. index.php: load config, start session (if needed), instantiate Router
       │
4. Router: parse REQUEST_URI, match route pattern, extract parameters
       │
5. Router: instantiate the target Controller, call the mapped method
       │
6. Controller: validate inputs (Validator)
       │
7. Controller: execute business logic
       │     │
       │     ├── Read data: FlatfileStore::read() with LOCK_SH
       │     ├── Write data: FlatfileStore::write() with LOCK_EX + atomic temp
       │     └── Log IP: IpLogger::log()
       │
8. Controller: determine response type
       │     │
       │     ├── Redirect: header('Location: ...', true, 303)
       │     └── Render: load template, pass data, output HTML
       │
9. Template: render HTML with escaped output, send to browser
       │
10. Browser: display page (auto-refresh if <meta http-equiv="refresh"> present)
```

### 2.3 Directory and File Layout

```
text-board/
├── public/                              # Document root (web-accessible)
│   ├── index.php                        # Front controller — ALL requests enter here
│   ├── .htaccess                        # Apache mod_rewrite rules
│   └── css/
│       └── style.css                    # All application styles
│
├── src/                                 # PHP source (outside document root)
│   ├── config.php                       # All configuration constants/arrays
│   ├── Router.php                       # URL pattern matching and dispatch
│   ├── BoardController.php              # Board listing, thread display
│   ├── PostController.php               # Thread creation, reply submission
│   ├── AdminController.php              # Admin dashboard, moderation
│   ├── AuthController.php               # Login, logout, password management
│   ├── FlatfileStore.php                # File read/write/lock abstraction
│   ├── IpLogger.php                     # Append-only IP address logging
│   ├── Validator.php                    # Input validation and sanitization
│   ├── Security.php                     # CSRF tokens, HTML escaping, password hashing
│   ├── Template.php                     # Simple PHP template renderer
│   └── Helpers.php                      # Timestamp formatting, string utilities
│
├── templates/                           # PHP template files (HTML with inline PHP)
│   ├── layout.php                       # Base layout: <!DOCTYPE>, <head>, nav, footer
│   ├── board_index.php                  # Home page: list all sub-boards
│   ├── thread_list.php                  # Board view: paginated thread list
│   ├── new_thread_form.php              # New thread creation form
│   ├── thread_view.php                  # Thread view: OP + nested reply tree
│   ├── reply_form.php                   # Reply form page (no auto-refresh)
│   ├── admin/
│   │   ├── login.php                    # Admin login form
│   │   ├── dashboard.php                # Admin dashboard with board stats
│   │   ├── board_manage.php             # Create / rename / delete boards
│   │   ├── board_moderate.php           # Moderate threads in a board
│   │   ├── thread_moderate.php          # Moderate replies (view IPs, delete)
│   │   └── password_change.php          # Change admin password
│   └── errors/
│       ├── 400.php                      # Bad Request
│       ├── 403.php                      # Forbidden
│       ├── 404.php                      # Not Found
│       └── 500.php                      # Internal Server Error
│
├── data/                                # Flatfile data store (outside document root)
│   ├── admin.json                       # Admin credentials (username + password_hash)
│   ├── admin_audit.log                  # Admin login audit trail (append-only)
│   ├── boards.json                      # Board index: all sub-boards
│   ├── boards/
│   │   └── {board_id}/
│   │       ├── threads.json             # Thread index for this board (metadata array)
│   │       └── threads/
│   │           └── {thread_id}.json     # Individual thread: OP + replies array
│   ├── ip_logs/
│   │   └── {YYYY-MM-DD}.log             # Daily IP log (one JSON object per line)
│   ├── logs/
│   │   └── app.log                      # Application error/event log
│   └── tmp/                             # Temporary files for atomic writes
│
├── SRS.md                               # Software Requirements Specification
├── SDD.md                               # This document
└── README.md                            # Deployment and usage instructions
```

### 2.4 Technology Stack

| Component | Technology | Justification |
|---|---|---|
| **Language** | PHP 8.1+ | Required by C-001. Named arguments, match expressions, readonly properties available. |
| **Storage** | JSON flatfiles | Required by C-002/C-003. Human-readable, zero-config, native PHP support. |
| **Frontend** | HTML5 + CSS3 | Required by C-004. Zero JavaScript. All interactivity via forms and CSS pseudo-classes. |
| **Templating** | Native PHP includes | No framework. Simple `include` with output buffering for layout inheritance. |
| **Concurrency** | `flock()` advisory locking | Required by C-008. Standard Unix mechanism, works on all Linux hosts. |
| **Password Hashing** | `password_hash()` (Argon2id / Bcrypt) | Required by FR-021. PHP built-in, salt auto-managed. |
| **IP Validation** | `filter_var()` with `FILTER_VALIDATE_IP` | Required by FR-010. Built-in, supports IPv4 and IPv6. |
| **Session** | PHP native `$_SESSION` | Admin only. `session.cookie_httponly=1`, `session.cookie_samesite=Strict`. |

### 2.5 Design Patterns Used

| Pattern | Application | Benefit |
|---|---|---|
| **Front Controller** | `public/index.php` | Single entry point; centralized routing, security, and error handling. |
| **MVC** | Controllers + Templates + FlatfileStore | Separation of business logic from presentation and data access. |
| **Strategy** | `FlatfileStore` read/write locking modes | Different locking strategies for reads (LOCK_SH) vs writes (LOCK_EX). |
| **Template View** | `Template.php` + `templates/*.php` | Server-side rendering with layout composition, no logic in templates. |
| **Dependency Injection (manual)** | Controllers receive FlatfileStore, Validator, Security, etc. via constructor | Testable components without a DI container. |
| **Atomic Write** | Write-to-temp-then-rename | Prevents partial writes from corrupting data files. |
| **Repository** | `FlatfileStore` | Abstracts file I/O; controllers never touch `fopen`/`fwrite` directly. |

---

## 3. Component Design

### 3.1 Front Controller (`public/index.php`)

**Purpose:** Single entry point for all HTTP requests. Bootstraps the application, invokes routing, and handles top-level errors.

**Pseudocode:**

```
1. Define ROOT_DIR constant pointing to project root
2. Require src/config.php
3. Set error reporting based on config (debug mode vs production)
4. Set exception handler to catch all uncaught exceptions
5. If request path starts with /admin:
   - Start PHP session (session_start)
   - Configure session cookie parameters (httponly, samesite, secure)
6. Require all src/*.php files (autoloading via explicit requires)
7. Instantiate core services:
   - $store = new FlatfileStore(config['data_dir'])
   - $validator = new Validator()
   - $security = new Security()
   - $ipLogger = new IpLogger(config['ip_logs_dir'])
8. Instantiate controllers with dependencies:
   - $boardCtrl = new BoardController($store, $template)
   - $postCtrl = new PostController($store, $validator, $security, $ipLogger)
   - $adminCtrl = new AdminController($store, $validator, $security)
   - $authCtrl = new AuthController($store, $security)
9. Instantiate Router, register all routes (see Section 5.1)
10. $router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI'])
```

### 3.2 Router (`src/Router.php`)

**Purpose:** Map HTTP method + URL pattern to controller method calls, extracting path parameters.

**Design:**

- Routes are registered as `[$method, $pattern, $controller, $methodName]`
- Patterns use named placeholders: `{board_id}`, `{thread_id}`, `{post_id}`
- Placeholder values are extracted via regex and passed as method arguments
- First matching route wins; routes are evaluated in registration order
- No-match returns 404

**Route Pattern Syntax:**

| Pattern | Matches | Example |
|---|---|---|
| `/` | Exact match | Home page |
| `/boards/{board_id}` | Named segment | `/boards/general` → `$board_id = 'general'` |
| `/boards/{board_id}/thread/{thread_id}` | Two segments | `/boards/general/thread/1717700123.ab12cd34` |
| `/boards/{board_id}/thread/{thread_id}/reply/{post_id}/delete` | Three segments | Admin delete reply |

**Validation of Extracted Parameters:**

```
- board_id:  /^[a-zA-Z0-9][a-zA-Z0-9_-]{0,30}[a-zA-Z0-9]$/
- thread_id: /^[0-9]+\.[0-9]+\.[a-f0-9]+$/
- post_id:   /^[0-9]+\.[0-9]+\.[a-f0-9]+$/
```

If an extracted parameter fails its pattern, the route is skipped (not matched), allowing fallback to a 404.

**Class Interface:**

```php
class Router {
    public function addRoute(string $method, string $pattern, object $controller, string $methodName): void;
    public function dispatch(string $httpMethod, string $uri): void;
}
```

### 3.3 Configuration (`src/config.php`)

**Purpose:** Single source of truth for all configurable values. Returns a PHP array.

```php
return [
    // --- Paths ---
    'data_dir'        => __DIR__ . '/../data',
    'boards_dir'      => __DIR__ . '/../data/boards',
    'ip_logs_dir'     => __DIR__ . '/../data/ip_logs',
    'app_log_dir'     => __DIR__ . '/../data/logs',
    'tmp_dir'         => __DIR__ . '/../data/tmp',
    'template_dir'    => __DIR__ . '/../templates',

    // --- Board Defaults ---
    'default_max_threads' => 100,
    'threads_per_page'    => 20,
    'auto_refresh_seconds' => 30,

    // --- Post Limits ---
    'max_message_length'   => 10000,
    'max_subject_length'   => 200,
    'max_thread_file_size' => 524288,  // 512 KB
    'message_excerpt_length' => 150,

    // --- Rate Limiting ---
    'rate_limit_max_posts' => 5,
    'rate_limit_window'    => 60,       // seconds

    // --- Session ---
    'session_timeout'      => 3600,     // 1 hour

    // --- Password Hashing ---
    'password_algo'        => PASSWORD_ARGON2ID,
    'password_options'     => [
        'memory_cost' => 65536,
        'time_cost'   => 4,
        'threads'     => 3,
    ],
    'password_min_length'  => 8,

    // --- Security ---
    'csrf_token_length'    => 32,
    'csp_header'           => "default-src 'self'; script-src 'none'; style-src 'self' 'unsafe-inline';",

    // --- Display ---
    'timezone'             => 'UTC',
    'date_format'          => 'Y-m-d H:i:s',
    'reply_indent_px'      => 20,
    'max_indent_levels'    => 10,

    // --- Debug ---
    'debug'                => false,
];
```

### 3.4 Board Controller (`src/BoardController.php`)

**Purpose:** Handle all read-only display operations for boards and threads.

**Constructor Dependencies:** `FlatfileStore`, `Template`

**Methods:**

| Method | Route | Description |
|---|---|---|
| `index()` | `GET /` | List all sub-boards sorted by `sort_order`. |
| `showBoard(string $boardId)` | `GET /boards/{board_id}` | List threads in a board, sorted by bump_score DESC, bump_recency DESC. Paginated (20/page). Includes `<meta http-equiv="refresh" content="30">`. |
| `showThread(string $boardId, string $threadId)` | `GET /boards/{board_id}/thread/{thread_id}` | Display OP + nested reply tree with CSS indentation. Includes "[Reply]" links on each post and auto-refresh. |
| `newThreadForm(string $boardId)` | `GET /boards/{board_id}/new` | Render the new thread creation form. |

**`showBoard` Algorithm:**

```
1. Validate board_id exists in boards.json → 404 if not
2. Read data/boards/{board_id}/threads.json
3. Sort entries by bump_score DESC, then bump_recency DESC
4. Calculate pagination:
   - $totalPages = ceil(count($threads) / config['threads_per_page'])
   - $page = min(max(1, $_GET['page'] ?? 1), $totalPages)
   - $offset = ($page - 1) * config['threads_per_page']
   - $pageThreads = array_slice($sortedThreads, $offset, config['threads_per_page'])
5. For each thread, format relative timestamps using Helpers::relativeTime()
6. Render thread_list.php template with auto-refresh meta tag
```

**`showThread` Algorithm (Threaded View):**

```
1. Validate board_id and thread_id
2. Read data/boards/{board_id}/threads/{thread_id}.json
3. Build reply tree from flat replies array (see Section 6.2)
4. Assign depth-first sequential post numbers (see Section 6.3)
5. Render thread_view.php:
   - OP at top (full message)
   - Recursive reply tree with CSS class .reply-depth-{n}
   - Each post gets a "[Reply]" link to /boards/{board_id}/thread/{thread_id}/reply?parent_id={post_id}
   - Bottom: "Post a Reply" button → reply form page
   - <meta http-equiv="refresh" content="30"> in <head>
   - NO inline reply form (it is on a separate page)
```

### 3.5 Post Controller (`src/PostController.php`)

**Purpose:** Handle thread creation and reply submission (both top-level and nested).

**Constructor Dependencies:** `FlatfileStore`, `Validator`, `Security`, `IpLogger`

**Methods:**

| Method | Route | Description |
|---|---|---|
| `createThread(string $boardId)` | `POST /boards/{board_id}/new` | Validate input, create thread file, update index, redirect. |
| `replyForm(string $boardId, string $threadId)` | `GET /boards/{board_id}/thread/{thread_id}/reply` | Render reply form (no auto-refresh). Pre-fills parent_id from query param. |
| `createReply(string $boardId, string $threadId)` | `POST /boards/{board_id}/thread/{thread_id}/reply` | Validate input, validate parent_id, append reply, recompute bump_score, redirect. |

**`createReply` Detailed Flow:**

```
1. Verify HTTP method is POST → 405 if not
2. Validate board_id exists in boards.json → 400 if not
3. Validate thread_id file exists → 400 if not
4. Validate CSRF token (Security::validateCsrfToken) → 403 if invalid
5. Rate-limit check: count posts from this IP in last N seconds → 429 if exceeded
6. Extract and sanitize inputs:
   - message: Validator::sanitizeMessage($_POST['message'])
     → trim, strip NULL bytes, validate 1-10000 chars
   - parent_id: $_POST['parent_id'] ?? null
     → if non-empty, validate format /^[0-9]+\.[0-9]+\.[a-f0-9]+$/
7. If message is empty after sanitization → 400 "A message is required."
8. Extract and validate IP: filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6)
   → if invalid, use '0.0.0.0'
9. Generate post_id: microtime(true) formatted + '.' + bin2hex(random_bytes(4))
10. Construct reply object:
    {
        "post_id": "1717700500.333333.cc33dd44",
        "parent_id": null,  // or the validated parent post_id
        "message": "...",
        "ip": "192.168.1.100",
        "timestamp": 1717700500
    }
11. Lock thread file (LOCK_EX), read, decode
12. If parent_id is non-null, verify it exists in the thread (OP.post_id or any reply.post_id)
    → 400 "Invalid parent post." if not found
13. Append reply to replies[] array
14. Increment reply_count
15. Recompute bump_score and bump_recency (see Section 6.1)
16. Update last_modified = time()
17. Check thread file size → 413 if exceeds max_thread_file_size
18. Write updated thread JSON (atomic: temp file → rename)
19. Release lock
20. Lock threads.json (LOCK_EX), read, decode
21. Update thread entry: last_modified, reply_count, bump_score, bump_recency
22. Write updated threads.json (atomic)
23. Release lock
24. IpLogger::log(board_id, thread_id, post_id, ip, 'reply')
25. HTTP 303 redirect to /boards/{board_id}/thread/{thread_id}#post-{post_id}
```

### 3.6 Admin Controller (`src/AdminController.php`)

**Purpose:** Handle all admin operations requiring authentication.

**Constructor Dependencies:** `FlatfileStore`, `Validator`, `Security`

**All methods check admin authentication first.** If `$_SESSION['admin_authenticated']` is not true, redirect to `/admin/login`.

**Methods:**

| Method | Route | Description |
|---|---|---|
| `dashboard()` | `GET /admin` | Show overview: each board with thread count, reply count, last activity. |
| `manageBoards()` | `GET /admin/boards` | List all boards with edit/delete options. |
| `createBoard()` | `POST /admin/boards/create` | Create new sub-board: validate board_id uniqueness, create directory + empty threads.json, append to boards.json. |
| `renameBoard(string $boardId)` | `POST /admin/boards/{board_id}/rename` | Update board name/description in boards.json. |
| `deleteBoard(string $boardId)` | `POST /admin/boards/{board_id}/delete` | Require confirmation checkbox. Recursively delete board directory. Remove from boards.json. |
| `moderateBoard(string $boardId)` | `GET /admin/boards/{board_id}` | List threads in board with delete option per thread. |
| `moderateThread(string $boardId, string $threadId)` | `GET /admin/boards/{board_id}/thread/{thread_id}` | View thread with IP addresses displayed. Delete button per reply. |
| `deleteThread(string $boardId, string $threadId)` | `POST /admin/boards/{board_id}/thread/{thread_id}/delete` | Delete thread file and remove from index. |
| `deleteReply(string $boardId, string $threadId, string $postId)` | `POST /admin/boards/{board_id}/thread/{thread_id}/reply/{post_id}/delete` | Cascading delete of reply and all descendants (see Section 6.4). Recompute bump_score. |

**Confirmation Workflow (No JavaScript):**

Since JavaScript is prohibited, delete confirmations use a two-step form:
1. The delete form includes a hidden `confirm` field (value `0`) and a visible checkbox labeled "I confirm deletion."
2. When the checkbox is checked and form submitted, the server checks `$_POST['confirm'] === '1'`.
3. If `confirm` is `0`, return the form with an error: "You must check the confirmation box."

```
<form method="POST" action="...">
    <label>
        <input type="checkbox" name="confirm" value="1">
        I confirm I want to delete this thread and all its replies.
    </label>
    <button type="submit">Delete Thread</button>
</form>
```

### 3.7 Auth Controller (`src/AuthController.php`)

**Purpose:** Handle admin login, logout, password management, and initial setup.

**Constructor Dependencies:** `FlatfileStore`, `Security`

**Methods:**

| Method | Route | Description |
|---|---|---|
| `loginForm()` | `GET /admin/login` | Render login form. |
| `login()` | `POST /admin/login` | Validate credentials, create session, redirect to dashboard. |
| `logout()` | `GET /admin/logout` | Destroy session, redirect to home. |
| `passwordChangeForm()` | `GET /admin/password` | Render password change form (requires auth). |
| `passwordChange()` | `POST /admin/password` | Verify current password, hash new password, update admin.json. |
| `setupForm()` | `GET /setup` | Render initial admin account setup (only if admin.json does not exist). |
| `setup()` | `POST /setup` | Create admin.json with hashed password (only if admin.json does not exist). |

**`login()` Detailed Flow:**

```
1. Verify HTTP method is POST
2. Validate CSRF token
3. Read data/admin.json → if not found, redirect to /setup
4. Extract username and password from $_POST
5. Timing-safe username comparison: hash_equals($_POST['username'], stored['username'])
6. If username matches: password_verify($_POST['password'], stored['password_hash'])
7. If both match:
   a. session_regenerate_id(true)  // prevent session fixation
   b. $_SESSION['admin_authenticated'] = true
   c. $_SESSION['admin_login_time'] = time()
   d. $_SESSION['csrf_token'] = bin2hex(random_bytes(32))  // new CSRF token
   e. Redirect 303 to /admin
8. If either fails:
   a. Log attempt to admin_audit.log: {timestamp, ip, username, result: 'failure'}
   b. Return 401 with "Invalid username or password." (generic message)
```

### 3.8 Flatfile Store (`src/FlatfileStore.php`)

**Purpose:** Abstract all file I/O operations behind a clean interface. Handle JSON encoding/decoding, advisory locking, and atomic writes.

**Class Interface:**

```php
class FlatfileStore {
    public function __construct(string $dataDir);

    // Reading (LOCK_SH for mutable files, no lock for immutable)
    public function read(string $relativePath): array;
    public function readRaw(string $relativePath): string;
    public function exists(string $relativePath): bool;

    // Writing (LOCK_EX, atomic write-via-temp)
    public function write(string $relativePath, array $data): void;
    public function writeRaw(string $relativePath, string $content): void;

    // Deletion
    public function delete(string $relativePath): void;
    public function deleteDirectory(string $relativePath): void;

    // Directory operations
    public function createDirectory(string $relativePath): void;
    public function listDirectory(string $relativePath): array;

    // JSON-specific (delegates to read/write with json_encode/decode)
    public function readJson(string $relativePath): array;
    public function writeJson(string $relativePath, array $data): void;
}
```

**Atomic Write Implementation:**

```
1. Generate temp file path: data/tmp/{basename}.{microtime}.{random}.tmp
2. Open temp file with fopen($tempPath, 'w')
3. Write JSON content with fwrite()
4. fclose()
5. rename($tempPath, $targetPath)  ← atomic on same filesystem
6. On failure: unlink($tempPath) if it exists, throw exception
```

**JSON Encoding Flags:**

All `writeJson()` calls use:
```php
json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
```

**Locking Strategy:**

| Operation | Lock Type | Rationale |
|---|---|---|
| Read `boards.json` | `LOCK_SH` | May be modified concurrently by admin. |
| Read `threads.json` | `LOCK_SH` | May be modified concurrently by posters. |
| Read thread file (`*.json`) | No lock | Once written, a thread's OP is immutable for anonymous users. Replies are appended, but a stale read is acceptable for display. |
| Write any file | `LOCK_EX` | Exclusive lock to prevent interleaved writes. |
| Delete file | `LOCK_EX` on parent directory lock file | Prevents concurrent delete + write to same file. |

**Error Handling in FlatfileStore:**

| Condition | Action |
|---|---|
| File not found on read | Return empty array `[]` (for indexes) or throw `NotFoundException` (for required files) |
| `json_decode()` fails | Log error, return empty array `[]` (graceful degradation per NFR-R03) |
| `flock()` fails (timeout) | Throw `LockTimeoutException` after 5 seconds |
| `rename()` fails (atomic write) | Throw `FileWriteException` |
| Disk full | Throw `FileWriteException` with details |

### 3.9 IP Logger (`src/IpLogger.php`)

**Purpose:** Append-only logging of IP addresses associated with posts. One file per UTC day.

**Class Interface:**

```php
class IpLogger {
    public function __construct(string $ipLogsDir);
    public function log(string $boardId, string $threadId, string $postId, string $ip, string $action): void;
}
```

**Implementation:**

```
1. Determine log file: data/ip_logs/{YYYY-MM-DD}.log (current UTC date)
2. Construct JSON line: {"timestamp": ..., "board_id": "...", "thread_id": "...", "post_id": "...", "ip": "...", "action": "new_thread|reply"}
3. Open file for append: fopen($logPath, 'a')
4. Acquire LOCK_EX (append operations still need locking to prevent interleaved lines)
5. fwrite($jsonLine . "\n")
6. fclose() (releases lock)
```

### 3.10 Validator (`src/Validator.php`)

**Purpose:** Centralize all input validation and sanitization logic.

**Class Interface:**

```php
class Validator {
    // Message sanitization
    public static function sanitizeMessage(string $raw): string;
    public static function sanitizeSubject(string $raw): string;

    // ID validation
    public static function isValidBoardId(string $id): bool;
    public static function isValidThreadId(string $id): bool;
    public static function isValidPostId(string $id): bool;

    // IP validation
    public static function isValidIp(string $ip): bool;

    // Length validation
    public static function validateMessageLength(string $message): ?string;  // returns error or null
    public static function validateSubjectLength(string $subject): ?string;

    // Rate limit check
    public static function checkRateLimit(string $ip, string $rateFile, int $maxPosts, int $windowSec): bool;
}
```

**Sanitization Pipeline (`sanitizeMessage`):**

```
Input: raw string from $_POST['message']
Step 1: Strip NULL bytes: str_replace("\0", '', $raw)
Step 2: Normalize line endings: str_replace("\r\n", "\n", $result)
Step 3: Trim whitespace: trim($result)
Step 4: Truncate to max length (10000 chars): mb_substr($result, 0, 10000)
Output: sanitized string
```

**Board ID Validation Pattern:**

```
/^[a-zA-Z0-9][a-zA-Z0-9_-]{0,30}[a-zA-Z0-9]$/
- Must start and end with alphanumeric
- Interior: alphanumeric, hyphen, underscore
- Length: 1-32 characters
```

**Thread/Post ID Validation Pattern:**

```
/^[0-9]+\.[0-9]+\.[a-f0-9]+$/
- Three segments separated by dots
- First two: numeric (timestamp, microseconds)
- Third: lowercase hexadecimal (random bytes)
```

### 3.11 Security (`src/Security.php`)

**Purpose:** Provide security primitives: CSRF tokens, HTML escaping, password hashing/verification.

**Class Interface:**

```php
class Security {
    // CSRF
    public static function generateCsrfToken(): string;
    public static function getCsrfTokenField(): string;  // returns <input type="hidden" ...>
    public static function validateCsrfToken(string $token): bool;

    // Output escaping
    public static function escapeHtml(string $raw): string;
    public static function escapeAttribute(string $raw): string;

    // Password
    public static function hashPassword(string $password): string;
    public static function verifyPassword(string $password, string $hash): bool;

    // Session
    public static function requireAdminSession(): void;  // redirects to login if not authenticated
    public static function checkSessionTimeout(): void;   // destroys session if expired

    // Headers
    public static function sendSecurityHeaders(): void;
}
```

**`sendSecurityHeaders()` Implementation:**

```php
public static function sendSecurityHeaders(): void {
    header('Content-Security-Policy: ' . config['csp_header']);
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: same-origin');
    // No script-src except 'none' since JavaScript is prohibited
}
```

### 3.12 Template Engine (`src/Template.php`)

**Purpose:** Simple server-side template renderer using PHP includes with output buffering. Supports layout inheritance via a base layout template.

**Class Interface:**

```php
class Template {
    public function __construct(string $templateDir);
    public function render(string $templateName, array $data = []): string;
}
```

**`render()` Implementation:**

```
1. Extract $data array to local variables: extract($data)
2. Start output buffering: ob_start()
3. Include the template file: include $this->templateDir . '/' . $templateName . '.php'
4. If the template sets $layout variable:
   - Capture current buffer as $content: ob_get_clean()
   - Start new buffer
   - Include layout: include $this->templateDir . '/' . $layout . '.php'
   - Layout uses $content variable to inject body
5. Return final buffer: ob_get_clean()
```

**Layout Template (`layout.php`):**

```php
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Text Board' ?></title>
    <link rel="stylesheet" href="/css/style.css">
    <?php if ($autoRefresh ?? false): ?>
    <meta http-equiv="refresh" content="<?= $refreshSeconds ?? 30 ?>">
    <?php endif; ?>
</head>
<body>
    <nav class="board-nav">
        <a href="/">Home</a>
        <?= $breadcrumbs ?? '' ?>
    </nav>
    <main class="board-content">
        <?= $content ?>
    </main>
    <footer>
        <p>Text Board — No JavaScript Required</p>
    </footer>
</body>
</html>
```

### 3.13 Helpers (`src/Helpers.php`)

**Purpose:** Stateless utility functions for common formatting and data transformation tasks.

**Key Functions:**

```php
class Helpers {
    // Format Unix timestamp as relative time string
    public static function relativeTime(int $timestamp): string;
    // Example: 1717700123 → "5 minutes ago", "2 hours ago", "3 days ago"

    // Format Unix timestamp as absolute date string
    public static function absoluteTime(int $timestamp): string;
    // Example: 1717700123 → "2026-06-07 14:15:23"

    // Generate message excerpt (first N chars, newlines → spaces)
    public static function excerpt(string $message, int $length = 150): string;

    // Generate unique ID
    public static function generateId(): string;
    // Format: {unix_seconds}.{microseconds}.{8_random_hex}
}
```

---

## 4. Data Design

### 4.1 Entity-Relationship Model (Logical)

```
+-------------+          +------------------+          +------------------+
|    Board    | 1      * |     Thread       | 1      * |      Reply       |
+-------------+----------+------------------+----------+------------------+
| board_id    |          | thread_id        |          | post_id          |
| name        |          | board_id (FK)    |          | thread_id (FK)   |
| description |          | subject          |          | parent_id (FK)   |
| sort_order  |          | message_excerpt  |          | message          |
| max_threads |          | created_at       |          | ip               |
| created_at  |          | last_modified    |          | timestamp        |
+-------------+          | reply_count      |          +------------------+
                         | bump_score       |                  |
                         | bump_recency     |                  |
                         +------------------+                  |
                                | 1                            |
                                v                              |
                         +------------------+                  |
                         |       OP         |<-----------------+
                         +------------------+   (parent_id
                         | post_id          |    references OP
                         | message          |    or another
                         | ip               |    reply)
                         | timestamp        |
                         +------------------+
```

**Relationships:**

- **Board 1→* Thread**: Each board contains many threads.
- **Thread 1→1 OP**: Each thread has exactly one original post (embedded).
- **Thread 1→* Reply**: Each thread has many replies (flat array, tree via `parent_id`).
- **Reply *→1 Reply (self)**: Each reply may reference a parent reply via `parent_id`. Top-level replies have `parent_id = null` (replying to OP).

### 4.2 File Organization

```
data/
├── admin.json                         # Single admin account
├── admin_audit.log                    # Login attempt audit trail
├── boards.json                        # Array of all Board objects
├── boards/
│   └── {board_id}/
│       ├── threads.json               # Array of Thread metadata objects (index)
│       └── threads/
│           └── {thread_id}.json       # Full thread: OP + all replies
├── ip_logs/
│   └── {YYYY-MM-DD}.log              # Daily IP log (newline-delimited JSON)
├── logs/
│   └── app.log                       # Application error/event log
└── tmp/                              # Temp files for atomic writes
```

### 4.3 JSON Schema Definitions

#### 4.3.1 Board Index (`boards.json`)

```json
[
    {
        "board_id": "general",
        "name": "General Discussion",
        "description": "Talk about anything and everything.",
        "sort_order": 1,
        "max_threads": 100,
        "created_at": 1717700000
    }
]
```

| Field | Type | Constraints |
|---|---|---|
| `board_id` | string | Pattern: `/^[a-zA-Z0-9][a-zA-Z0-9_-]{0,30}[a-zA-Z0-9]$/`, unique |
| `name` | string | 1-100 chars |
| `description` | string | 0-500 chars |
| `sort_order` | integer | ≥ 0 |
| `max_threads` | integer | ≥ 0 (0 = unlimited) |
| `created_at` | integer | Unix timestamp |

#### 4.3.2 Board Thread Index (`threads.json`)

```json
[
    {
        "thread_id": "1717700123.456789.ab12cd34",
        "subject": "Welcome to the board!",
        "message_excerpt": "This is the first 150 characters...",
        "poster_ip_hash": "sha256:abc123...",
        "created_at": 1717700123,
        "last_modified": 1717750000,
        "reply_count": 5,
        "bump_score": 3,
        "bump_recency": 1717750000
    }
]
```

| Field | Type | Description |
|---|---|---|
| `thread_id` | string | Unique thread identifier. |
| `subject` | string | Thread subject or "No Subject". |
| `message_excerpt` | string | First 150 chars of OP, newlines replaced with spaces. |
| `poster_ip_hash` | string | SHA-256 hash of OP's IP (not raw IP). |
| `created_at` | integer | Thread creation timestamp. |
| `last_modified` | integer | Most recent reply timestamp (any depth). |
| `reply_count` | integer | Total reply count including nested. |
| `bump_score` | integer | Reply count of the most-active sub-thread branch. 0 if no replies. |
| `bump_recency` | integer | Most recent timestamp in the winning branch. = `created_at` if no replies. |

#### 4.3.3 Thread File (`{thread_id}.json`)

```json
{
    "thread_id": "1717700123.456789.ab12cd34",
    "board_id": "general",
    "subject": "Welcome to the board!",
    "created_at": 1717700123,
    "last_modified": 1717750000,
    "reply_count": 3,
    "bump_score": 2,
    "bump_recency": 1717750000,
    "op": {
        "post_id": "1717700123.456789.ab12cd34",
        "message": "Hello everyone! ...",
        "ip": "192.168.1.100",
        "timestamp": 1717700123
    },
    "replies": [
        {
            "post_id": "1717700400.111111.xy99zz00",
            "parent_id": null,
            "message": "Hi! Great to be here.",
            "ip": "2001:0db8:85a3:0000:0000:8a2e:0370:7334",
            "timestamp": 1717700400
        },
        {
            "post_id": "1717700500.333333.cc33dd44",
            "parent_id": "1717700400.111111.xy99zz00",
            "message": "Welcome! I agree.",
            "ip": "10.0.0.55",
            "timestamp": 1717700500
        }
    ]
}
```

**Reply Object Fields:**

| Field | Type | Description |
|---|---|---|
| `post_id` | string | Unique post identifier. |
| `parent_id` | string \| null | `post_id` of the parent (OP or another reply). `null` = top-level reply to OP. |
| `message` | string | Reply body, 1-10000 chars after sanitization. |
| `ip` | string | Raw IP address (IPv4 or IPv6). |
| `timestamp` | integer | Unix timestamp of posting. |

**Thread File Size Limit:**

- Max file size: 512 KB (`max_thread_file_size` in config)
- Checked before each reply write
- If exceeded: HTTP 413 "This thread has reached the maximum number of replies."

#### 4.3.4 Admin Credentials (`admin.json`)

```json
{
    "username": "admin",
    "password_hash": "$argon2id$v=19$m=65536,t=4,p=3$...",
    "created_at": 1717700000,
    "last_password_change": 1717700000
}
```

#### 4.3.5 IP Log (`ip_logs/{YYYY-MM-DD}.log`)

Newline-delimited JSON (NDJSON), one object per line:

```
{"timestamp":1717700123,"board_id":"general","thread_id":"1717700123.456789.ab12cd34","post_id":"1717700123.456789.ab12cd34","ip":"192.168.1.100","action":"new_thread"}
{"timestamp":1717700400,"board_id":"general","thread_id":"1717700123.456789.ab12cd34","post_id":"1717700400.111111.xy99zz00","ip":"2001:0db8:85a3:0000:0000:8a2e:0370:7334","action":"reply"}
```

### 4.4 Concurrency Control

The application uses PHP's `flock()` advisory file locking to prevent race conditions during concurrent reads and writes.

**Lock Hierarchy:**

```
data/boards/{board_id}/threads.json     ← LOCK_EX for writes, LOCK_SH for reads
data/boards/{board_id}/threads/{id}.json ← LOCK_EX for writes, no lock for reads
data/boards.json                         ← LOCK_EX for writes, LOCK_SH for reads
data/admin.json                          ← LOCK_EX for writes, LOCK_SH for reads
data/ip_logs/{date}.log                  ← LOCK_EX for appends
```

**Lock Acquisition Pattern:**

```php
$handle = fopen($filePath, $mode);  // 'r' for read, 'c+' for write
if (!flock($handle, $lockType)) {   // LOCK_SH or LOCK_EX
    fclose($handle);
    throw new LockTimeoutException("Could not acquire lock on {$filePath}");
}
// ... read or write ...
flock($handle, LOCK_UN);
fclose($handle);
```

**Deadlock Prevention:**

- Locks are always acquired in a consistent order: thread file first, then index file
- No nested locks on the same file
- All locks are released explicitly (no reliance on process termination)

### 4.5 Atomic Write Strategy

To prevent data corruption from partial writes (e.g., PHP timeout, disk full), all mutable JSON files use the **write-to-temp-then-rename** pattern:

```
1. Generate unique temp filename in data/tmp/
2. Write complete JSON content to temp file
3. fsync() the temp file (ensure it's on disk)
4. rename(tempPath, targetPath)  ← atomic on Linux (same filesystem)
5. If rename fails: cleanup temp file, throw exception
```

The `rename()` system call is atomic on Linux when source and destination are on the same filesystem. If the process crashes during step 2, the temp file is orphaned (cleaned up periodically or on next write). The target file is never left in a partially-written state.

---

## 5. Interface Design

### 5.1 URL Routing Table

| HTTP Method | URL Pattern | Controller::Method | Auth | Auto-Refresh |
|---|---|---|---|---|
| `GET` | `/` | `BoardController::index()` | No | No |
| `GET` | `/boards/{board_id}` | `BoardController::showBoard($boardId)` | No | **Yes (30s)** |
| `GET` | `/boards/{board_id}/new` | `BoardController::newThreadForm($boardId)` | No | No |
| `POST` | `/boards/{board_id}/new` | `PostController::createThread($boardId)` | No | — |
| `GET` | `/boards/{board_id}/thread/{thread_id}` | `BoardController::showThread($boardId, $threadId)` | No | **Yes (30s)** |
| `GET` | `/boards/{board_id}/thread/{thread_id}/reply` | `PostController::replyForm($boardId, $threadId)` | No | No |
| `POST` | `/boards/{board_id}/thread/{thread_id}/reply` | `PostController::createReply($boardId, $threadId)` | No | — |
| `GET` | `/admin/login` | `AuthController::loginForm()` | No | No |
| `POST` | `/admin/login` | `AuthController::login()` | No | — |
| `GET` | `/admin/logout` | `AuthController::logout()` | No | — |
| `GET` | `/admin` | `AdminController::dashboard()` | **Yes** | No |
| `GET` | `/admin/boards` | `AdminController::manageBoards()` | **Yes** | No |
| `POST` | `/admin/boards/create` | `AdminController::createBoard()` | **Yes** | — |
| `POST` | `/admin/boards/{board_id}/rename` | `AdminController::renameBoard($boardId)` | **Yes** | — |
| `POST` | `/admin/boards/{board_id}/delete` | `AdminController::deleteBoard($boardId)` | **Yes** | — |
| `GET` | `/admin/boards/{board_id}` | `AdminController::moderateBoard($boardId)` | **Yes** | No |
| `GET` | `/admin/boards/{board_id}/thread/{thread_id}` | `AdminController::moderateThread($boardId, $threadId)` | **Yes** | No |
| `POST` | `/admin/boards/{board_id}/thread/{thread_id}/delete` | `AdminController::deleteThread($boardId, $threadId)` | **Yes** | — |
| `POST` | `/admin/boards/{board_id}/thread/{thread_id}/reply/{post_id}/delete` | `AdminController::deleteReply($boardId, $threadId, $postId)` | **Yes** | — |
| `GET` | `/admin/password` | `AuthController::passwordChangeForm()` | **Yes** | No |
| `POST` | `/admin/password` | `AuthController::passwordChange()` | **Yes** | — |
| `GET` | `/setup` | `AuthController::setupForm()` | No* | No |
| `POST` | `/setup` | `AuthController::setup()` | No* | — |

\* `/setup` is only accessible when `admin.json` does not exist.

### 5.2 HTTP Request/Response Contracts

#### 5.2.1 POST /boards/{board_id}/new (Create Thread)

**Request:**
```
POST /boards/general/new HTTP/1.1
Content-Type: application/x-www-form-urlencoded

subject=Hello+World&message=This+is+a+test+post.&csrf_token=a1b2c3d4...
```

**Success Response:**
```
HTTP/1.1 303 See Other
Location: /boards/general/thread/1717700123.456789.ab12cd34
```

**Error Response (400):**
```
HTTP/1.1 400 Bad Request
Content-Type: text/html; charset=utf-8

<!-- HTML page with error message -->
<p class="error">A message is required.</p>
```

#### 5.2.2 POST /boards/{board_id}/thread/{thread_id}/reply (Create Reply)

**Request:**
```
POST /boards/general/thread/1717700123.456789.ab12cd34/reply HTTP/1.1
Content-Type: application/x-www-form-urlencoded

message=Great+post%21&parent_id=&csrf_token=a1b2c3d4...
```

(If replying to a specific reply, `parent_id` would contain that reply's `post_id`.)

**Success Response:**
```
HTTP/1.1 303 See Other
Location: /boards/general/thread/1717700123.456789.ab12cd34#post-1717700500.333333.cc33dd44
```

**Error Response (400 - Invalid Parent):**
```
HTTP/1.1 400 Bad Request
Content-Type: text/html; charset=utf-8

<!-- HTML page with error -->
<p class="error">Invalid parent post.</p>
```

#### 5.2.3 POST /admin/login

**Request:**
```
POST /admin/login HTTP/1.1
Content-Type: application/x-www-form-urlencoded

username=admin&password=secret123&csrf_token=a1b2c3d4...
```

**Success Response:**
```
HTTP/1.1 303 See Other
Location: /admin
Set-Cookie: PHPSESSID=...; HttpOnly; SameSite=Strict; Path=/
```

**Error Response (401):**
```
HTTP/1.1 401 Unauthorized
Content-Type: text/html; charset=utf-8

<!-- Login form with error -->
<p class="error">Invalid username or password.</p>
```

### 5.3 Page Flow Diagrams

#### 5.3.1 Anonymous User Posting Flow

```
  +----------+         +-------------------+         +-------------------+
  |  Board   |  click  |   Thread List     |  click  |   Thread View     |
  |  Index   | ------> |  (Auto-Refresh)   | ------> |  (Auto-Refresh)   |
  |   /      |         | /boards/{id}      |         | /boards/{id}/     |
  +----------+         +-------------------+         | thread/{id}       |
       |                      |                      +---------+---------+
       |                      |                                |
       |                [New Thread]                    [Reply] link
       |                      |                      (with parent_id)
       |                      v                                |
       |               +----------------+                      v
       |               | New Thread     |               +----------------+
       |               | Form (static)  |               | Reply Form     |
       |               | /boards/{id}/  |               | (NO refresh)   |
       |               | new            |               | .../reply      |
       |               +-------+--------+               +-------+--------+
       |                       |                                |
       |                  POST submit                      POST submit
       |                       |                                |
       |                       +----------+ +------------------+
       |                                  | |
       |                                  v v
       |                           303 Redirect back
       |                           to Thread View
       |                                  |
       +<---- (nav breadcrumb) -----------+
```

#### 5.3.2 Admin Operations Flow

```
  +----------+         +-------------------+         +-------------------+
  |  Login   |  POST   |   Dashboard       |         |  Board Management |
  |  /admin/ | ------> |   /admin          | ------> |  /admin/boards    |
  |  login   |         |                   |         |                   |
  +----------+         +-------------------+         +-------------------+
                              |                               |
                              |                               | [Create]
                              |                               | [Rename]
                              |                               | [Delete]
                              |                               |
                              v                               v
                       +----------------+            +----------------+
                       | Board Moderate |            | Thread Moderate|
                       | /admin/boards/ | -------->  | /admin/boards/ |
                       | {board_id}     |            | {id}/thread/   |
                       +----------------+            | {thread_id}    |
                              |                      +--------+-------+
                              | [Delete Thread]               |
                              v                       [Delete Reply]
                        Thread deleted               (cascading)
```

### 5.4 CSS Class Naming Conventions

Since the application relies entirely on CSS3 for styling (no JavaScript), a consistent naming convention is essential.

**Global Classes:**

| Class | Purpose |
|---|---|
| `.board-nav` | Top navigation bar / breadcrumbs |
| `.board-content` | Main content area |
| `.board-list` | Board index listing |
| `.thread-list` | Thread listing table/list |
| `.thread-view` | Thread display container |
| `.post` | Individual post container (OP or reply) |
| `.post-op` | Original post (specific styling) |
| `.post-reply` | Reply post |
| `.post-message` | Message body container |
| `.post-meta` | Timestamp, post number metadata |
| `.post-ip` | IP address display (admin only) |
| `.post-reply-link` | "[Reply]" link on each post |
| `.reply-depth-{n}` | Indentation for nesting level n (0-10) |
| `.reply-form` | Reply form container |
| `.new-thread-form` | New thread form container |
| `.pagination` | Pagination navigation |
| `.error` | Error message display |
| `.success` | Success message display |
| `.confirm-box` | Admin confirmation checkbox area |

**CSS-Only Interactive States:**

| Selector | Effect |
|---|---|
| `a:hover` | Link hover color change |
| `input:focus, textarea:focus` | Focus ring on form fields |
| `button:hover` | Button background change |
| `.post:target` | Highlight post when navigated to via anchor (`#post-{id}`) |
| `input:invalid` | Red border on invalid form fields (HTML5 validation) |
| `:checked + label` | Style change for checked confirmation checkbox |

**Reply Indentation CSS:**

```css
.reply-depth-0 { margin-left: 0; }
.reply-depth-1 { margin-left: 20px; }
.reply-depth-2 { margin-left: 40px; }
.reply-depth-3 { margin-left: 60px; }
/* ... up to depth-10 */
.reply-depth-10 { margin-left: 200px; }
```

The `$reply_indent_px` config value (default 20) controls the per-level increment. Levels beyond `$max_indent_levels` (default 10) are capped at the maximum indent.

---

## 6. Algorithm Design

### 6.1 Bump Score Computation

**Purpose:** Compute the `bump_score` and `bump_recency` for a thread based on its reply tree. Called after every reply addition or deletion.

**Input:** Array of reply objects, each with `post_id`, `parent_id`, and `timestamp`.

**Output:** `[bump_score (int), bump_recency (int)]`

**Algorithm:**

```
function computeBumpScore(array $replies): array {
    // Step 1: Build a map of parent_id → children for fast lookup
    $children = [];  // key: parent_id (or 'null' string), value: array of replies
    foreach ($replies as $reply) {
        $parentKey = $reply['parent_id'] ?? 'null';
        $children[$parentKey][] = $reply;
    }

    // Step 2: Identify top-level replies (parent_id = null)
    $topLevelReplies = $children['null'] ?? [];

    // Step 3: If no top-level replies, return zero score
    if (empty($topLevelReplies)) {
        return [0, 0];  // bump_recency will be set to created_at by caller
    }

    // Step 4: For each top-level reply, compute branch size and max timestamp
    $bestScore = 0;
    $bestRecency = 0;

    foreach ($topLevelReplies as $topReply) {
        [$branchSize, $branchRecency] = computeBranchStats(
            $topReply, $children
        );

        // Step 5: Select the branch with the highest count
        if ($branchSize > $bestScore) {
            $bestScore = $branchSize;
            $bestRecency = $branchRecency;
        } elseif ($branchSize === $bestScore) {
            // Tie: use the most recent timestamp
            $bestRecency = max($bestRecency, $branchRecency);
        }
    }

    return [$bestScore, $bestRecency];
}

function computeBranchStats(array $reply, array &$children): array {
    // Count this reply: 1
    $count = 1;
    $maxTimestamp = $reply['timestamp'];

    // Recursively process all children
    $childReplies = $children[$reply['post_id']] ?? [];
    foreach ($childReplies as $child) {
        [$childCount, $childTime] = computeBranchStats($child, $children);
        $count += $childCount;
        $maxTimestamp = max($maxTimestamp, $childTime);
    }

    return [$count, $maxTimestamp];
}
```

**Complexity:** O(R) where R is the number of replies. Each reply is visited exactly once during the recursive traversal. The children map is built in O(R) as a preprocessing step.

**Example:**

```
Replies:
  R1: parent_id=null, timestamp=100
    R2: parent_id=R1, timestamp=200
      R3: parent_id=R2, timestamp=300
  R4: parent_id=null, timestamp=150
    R5: parent_id=R4, timestamp=250

Branch 1 (R1): count=3 (R1+R2+R3), recency=300
Branch 2 (R4): count=2 (R4+R5), recency=250

Result: bump_score=3, bump_recency=300
```

### 6.2 Reply Tree Construction (Flat-to-Tree)

**Purpose:** Convert the flat `replies` array into a nested tree structure for rendering.

**Input:** Flat array of reply objects with `parent_id` fields.

**Output:** Tree structure suitable for recursive template rendering.

**Algorithm:**

```
function buildReplyTree(array $replies): array {
    // Step 1: Group replies by parent_id
    $byParent = [];
    foreach ($replies as $reply) {
        $parentKey = $reply['parent_id'] ?? 'null';
        $byParent[$parentKey][] = $reply;
    }

    // Step 2: Sort each group chronologically by timestamp
    foreach ($byParent as &$group) {
        usort($group, fn($a, $b) => $a['timestamp'] <=> $b['timestamp']);
    }

    // Step 3: Recursively build tree starting from top-level (parent_id=null)
    return buildTreeLevel('null', $byParent, 0);

    function buildTreeLevel(string $parentKey, array &$byParent, int $depth): array {
        $nodes = [];
        foreach ($byParent[$parentKey] ?? [] as $reply) {
            $reply['depth'] = $depth;
            $reply['children'] = buildTreeLevel($reply['post_id'], $byParent, $depth + 1);
            $nodes[] = $reply;
        }
        return $nodes;
    }
}
```

**Complexity:** O(R log R) where R is the number of replies, dominated by sorting each sibling group by timestamp.

**Template Rendering (Recursive):**

In `thread_view.php`, the tree is rendered using a recursive PHP function:

```php
function renderReplies(array $tree): void {
    foreach ($tree as $reply): ?>
        <div class="post post-reply reply-depth-<?= min($reply['depth'], 10) ?>"
             id="post-<?= htmlspecialchars($reply['post_id']) ?>">
            <div class="post-meta">
                <span class="post-number">#<?= $reply['post_number'] ?></span>
                <span class="post-time"><?= Helpers::relativeTime($reply['timestamp']) ?></span>
            </div>
            <div class="post-message">
                <?= nl2br(htmlspecialchars($reply['message'])) ?>
            </div>
            <a class="post-reply-link"
               href="/boards/<?= $boardId ?>/thread/<?= $threadId ?>/reply?parent_id=<?= urlencode($reply['post_id']) ?>">
               [Reply]
            </a>
        </div>
        <?php if (!empty($reply['children'])): ?>
            <?php renderReplies($reply['children']) ?>
        <?php endif;
    endforeach;
}
```

### 6.3 Depth-First Post Numbering

**Purpose:** Assign sequential numbers to posts in a thread for user reference. OP is always #1. Replies are numbered in depth-first traversal order.

**Algorithm:**

```
function assignPostNumbers(array $op, array $replyTree): array {
    $counter = 1;
    $op['post_number'] = $counter++;  // OP = #1

    numberRepliesRecursive($replyTree, $counter);

    return [$op, $replyTree];
}

function numberRepliesRecursive(array &$tree, int &$counter): void {
    foreach ($tree as &$reply) {
        $reply['post_number'] = $counter++;
        if (!empty($reply['children'])) {
            numberRepliesRecursive($reply['children'], $counter);
        }
    }
}
```

**Example Output:**

```
#1 - OP
  #2 - Reply to OP (top-level, branch A)
    #3 - Reply to #2 (nested)
      #4 - Reply to #3 (deeply nested)
  #5 - Reply to OP (top-level, branch B)
    #6 - Reply to #5 (nested)
```

### 6.4 Cascading Reply Deletion

**Purpose:** When an admin deletes a reply, all its descendants must also be deleted to maintain referential integrity of the tree.

**Algorithm:**

```
function cascadeDeleteReplies(array $replies, string $targetPostId): array {
    // Step 1: Build parent→children map
    $children = [];
    foreach ($replies as $reply) {
        $parentKey = $reply['parent_id'] ?? 'null';
        $children[$parentKey][] = $reply['post_id'];
    }

    // Step 2: Collect all descendant IDs via BFS/DFS
    $idsToDelete = [$targetPostId];
    $queue = [$targetPostId];

    while (!empty($queue)) {
        $currentId = array_shift($queue);
        foreach ($children[$currentId] ?? [] as $childId) {
            $idsToDelete[] = $childId;
            $queue[] = $childId;
        }
    }

    // Step 3: Filter replies, keeping only those NOT in the delete set
    $deleteSet = array_flip($idsToDelete);
    $remaining = array_filter($replies, fn($r) => !isset($deleteSet[$r['post_id']]));

    // Step 4: Return remaining replies and count of deleted
    return [
        'replies' => array_values($remaining),
        'deleted_count' => count($idsToDelete)
    ];
}
```

**Complexity:** O(R) where R is the number of replies. BFS traversal visits each descendant once.

**Post-Deletion Steps:**

1. Decrement `reply_count` by `deleted_count`
2. Recompute `bump_score` and `bump_recency` from remaining replies (Section 6.1)
3. Update thread JSON and `threads.json` index

### 6.5 Thread Ranking Sort

**Purpose:** Sort the thread index for display on the board page.

**Algorithm:**

```
function sortThreadsByRank(array $threads): array {
    usort($threads, function ($a, $b) {
        // Primary: bump_score descending
        $scoreCmp = $b['bump_score'] <=> $a['bump_score'];
        if ($scoreCmp !== 0) {
            return $scoreCmp;
        }
        // Secondary (tiebreaker): bump_recency descending (newest first)
        return $b['bump_recency'] <=> $a['bump_recency'];
    });
    return $threads;
}
```

**Complexity:** O(N log N) where N is the number of threads. PHP's `usort` uses quicksort.

### 6.6 Message Excerpt Generation

**Purpose:** Generate a short preview of the OP message for display in the thread listing.

**Algorithm:**

```
function generateExcerpt(string $message, int $maxLength = 150): string {
    // Step 1: Replace newlines with spaces
    $text = str_replace(["\r\n", "\r", "\n"], ' ', $message);

    // Step 2: Collapse multiple spaces
    $text = preg_replace('/\s+/', ' ', $text);

    // Step 3: Trim
    $text = trim($text);

    // Step 4: Truncate to max length, breaking at word boundary if possible
    if (mb_strlen($text) <= $maxLength) {
        return $text;
    }

    $truncated = mb_substr($text, 0, $maxLength);
    $lastSpace = mb_strrpos($truncated, ' ');

    if ($lastSpace !== false && $lastSpace > $maxLength * 0.5) {
        $truncated = mb_substr($truncated, 0, $lastSpace);
    }

    return $truncated . '…';
}
```

---

## 7. Security Design

### 7.1 Authentication Flow

```
User                Browser              Server              Filesystem
 |                     |                    |                    |
 |  Enter credentials  |                    |                    |
 |-------------------->|                    |                    |
 |                     |  POST /admin/login |                    |
 |                     |------------------->|                    |
 |                     |                    | Read admin.json    |
 |                     |                    |------------------->|
 |                     |                    |<-------------------|
 |                     |                    |                    |
 |                     |                    | hash_equals(user,  |
 |                     |                    |   stored_user)     |
 |                     |                    | password_verify(   |
 |                     |                    |   pass, hash)      |
 |                     |                    |                    |
 |                     |       [Success]    |                    |
 |                     |  303 + Set-Cookie  |                    |
 |                     |<-------------------|                    |
 |                     |                    |                    |
 |                     |       [Failure]    |                    |
 |                     |  401 + error msg   | Append audit log   |
 |                     |<-------------------|------------------->|
```

**Key Security Properties:**

- Password never stored in plaintext (only `password_hash()` output)
- Salt auto-generated and embedded in hash by `password_hash()`
- Timing-safe username comparison (`hash_equals`)
- Generic error message ("Invalid username or password") — no username enumeration
- Session ID regenerated on login (`session_regenerate_id(true)`)
- Failed attempts logged with timestamp and IP

### 7.2 CSRF Protection

All state-changing POST requests (posting, admin actions, login) are protected by CSRF tokens.

**Token Lifecycle:**

```
1. Session Start: $_SESSION['csrf_token'] = bin2hex(random_bytes(32))
2. Form Render: include hidden field <input type="hidden" name="csrf_token" value="{token}">
3. Form Submit: server compares $_POST['csrf_token'] === $_SESSION['csrf_token']
4. On Mismatch: HTTP 403, log warning, do not process the action
5. Token Rotation: new token generated on login (session_regenerate_id)
```

**Token Field Helper:**

```php
public static function getCsrfTokenField(): string {
    $token = $_SESSION['csrf_token'] ?? self::generateCsrfToken();
    $_SESSION['csrf_token'] = $token;
    return '<input type="hidden" name="csrf_token" value="' . self::escapeAttribute($token) . '">';
}
```

### 7.3 Input Sanitization Pipeline

All user input passes through a consistent sanitization pipeline before processing:

```
Raw Input ($_POST / $_GET)
    │
    ▼
1. NULL Byte Stripping: str_replace("\0", '', $input)
    │
    ▼
2. Line Ending Normalization: str_replace("\r\n", "\n", $input)
    │
    ▼
3. Whitespace Trim: trim($input)
    │
    ▼
4. Length Validation: check min/max bounds
    │
    ▼
5. Pattern Validation: regex for IDs, filter_var for IPs
    │
    ▼
Sanitized Input → Business Logic
```

### 7.4 Output Escaping

All data rendered in HTML must be escaped to prevent XSS:

| Context | Function | Example |
|---|---|---|
| HTML text content | `htmlspecialchars($str, ENT_QUOTES, 'UTF-8')` | Message body, subject, board name |
| HTML attribute values | `htmlspecialchars($str, ENT_QUOTES, 'UTF-8')` | `id="post-{id}"`, `href="?parent_id={id}"` |
| URL query parameters | `urlencode($str)` | `href="/reply?parent_id={urlencode($id)}"` |

**Template Convention:**

All PHP templates MUST use `htmlspecialchars()` (via `Security::escapeHtml()`) for any dynamic content. This is enforced by code review, not by the template engine itself (since we use raw PHP includes).

**CSS `white-space: pre-wrap` Approach:**

For message display, the application uses CSS `white-space: pre-wrap` on `.post-message` elements rather than PHP's `nl2br()`. This preserves newlines without introducing `<br>` tags. The choice between `nl2br()` and `white-space: pre-wrap` is a rendering decision — both are acceptable after HTML escaping.

### 7.5 Rate Limiting

**Purpose:** Prevent spam and denial-of-service via rapid posting from the same IP.

**Implementation:**

```
Rate limit check before any post action:

1. Determine rate file: data/tmp/ratelimit_{md5($ip)}.json
2. Read existing timestamps (array of Unix timestamps) or initialize []
3. Remove timestamps older than (now - config['rate_limit_window'])
4. Count remaining timestamps
5. If count >= config['rate_limit_max_posts']:
   → HTTP 429 "You are posting too quickly. Please wait before posting again."
6. Else:
   → Append current timestamp, write back, proceed with post
```

**Configuration:**

```php
'rate_limit_max_posts' => 5,   // Max posts per window
'rate_limit_window'    => 60,  // Window in seconds
```

**Cleanup:** Rate limit files older than 1 hour are deleted on read.

### 7.6 Session Management

**Configuration (set in `public/index.php` before `session_start()`):**

```php
ini_set('session.cookie_httponly', '1');     // Not accessible via JavaScript
ini_set('session.cookie_samesite', 'Strict'); // Not sent on cross-site requests
ini_set('session.cookie_secure', '1');        // HTTPS only (if applicable)
ini_set('session.use_strict_mode', '1');      // Only accept session IDs generated by server
```

**Session Timeout:**

```
On each admin page request:
1. If $_SESSION['admin_authenticated'] !== true → redirect to login
2. If time() - $_SESSION['admin_login_time'] > config['session_timeout']:
   → Destroy session, redirect to login with "Session expired" message
3. Else: $_SESSION['admin_login_time'] = time()  // extend session
```

### 7.7 File System Security

| Measure | Implementation |
|---|---|
| **Data outside document root** | `data/` directory is at the same level as `public/`, not inside it. Web server cannot serve `.json` files directly. |
| **Directory permissions** | `data/` and subdirectories: `0750`. Files: `0640`. Owned by web server user (e.g., `www-data`). |
| **Path traversal prevention** | All `board_id`, `thread_id`, `post_id` parameters validated against strict regex patterns. No `..` or `/` allowed in IDs. |
| **No PHP execution in data/** | `.htaccess` in `data/` (if Apache) with `php_flag engine off` or equivalent. |
| **`.htaccess` protection** | `public/.htaccess` blocks access to dot-files and sensitive paths. |
| **Admin JSON permissions** | `data/admin.json` has `0640` permissions (owner read-write, group read). |

---

## 8. Error Handling Design

### 8.1 Error Classification

| Category | HTTP Status | User Message | Logging | Example |
|---|---|---|---|---|
| **Not Found (resource)** | 404 | "Board not found." / "Thread not found." | WARNING | Invalid board_id or thread_id |
| **Bad Request (input)** | 400 | Specific message (e.g., "A message is required.") | None | Missing message, invalid parent_id |
| **Rate Limited** | 429 | "You are posting too quickly. Please wait." | NOTICE | > 5 posts in 60 seconds |
| **Payload Too Large** | 413 | "This thread has reached the maximum number of replies." | WARNING | Thread file > 512 KB |
| **Forbidden (CSRF)** | 403 | "Invalid security token. Please try again." | WARNING | CSRF token mismatch |
| **Unauthorized** | 401 | "Invalid username or password." | WARNING (audit log) | Failed admin login |
| **Internal Error** | 500 | "An internal error occurred. Please try again later." | ERROR | File write failure, corrupt JSON |

### 8.2 Exception Hierarchy

```
AppException (base)
├── NotFoundException         → 404
│   ├── BoardNotFoundException
│   └── ThreadNotFoundException
├── BadRequestException       → 400
│   ├── InvalidInputException
│   └── InvalidParentException
├── ForbiddenException        → 403
├── UnauthorizedException     → 401
├── RateLimitException        → 429
├── PayloadTooLargeException  → 413
└── InternalErrorException    → 500
    ├── FileWriteException
    ├── FileReadException
    └── LockTimeoutException
```

### 8.3 Error Response Flow

```
Exception thrown in Controller
    │
    ▼
Front Controller exception handler
    │
    ├── Determine HTTP status from exception class
    ├── Log if status >= 500 or status == 401
    │     → data/logs/app.log or data/admin_audit.log
    ├── Set HTTP response code
    ├── In debug mode: display exception details
    └── In production: render generic error template
          → templates/errors/{status}.php
```

**Error Template Data:**

```php
$errorData = [
    'status_code' => 404,
    'title' => 'Not Found',
    'message' => $isDebug ? $exception->getMessage() : 'Board not found.',
    'show_details' => $isDebug,
    'details' => $isDebug ? $exception->getTraceAsString() : '',
];
```

### 8.4 Logging Strategy

| Log File | Content | Format | Rotation |
|---|---|---|---|
| `data/logs/app.log` | Errors (500), warnings (404), notices (rate limits) | `[{timestamp}] [{level}] [{ip}] {message} {context}` | Max 1 MB, keep 3 rotated files |
| `data/admin_audit.log` | Admin login attempts (success + failure), password changes | `[{timestamp}] {ip} {username} {action} {result}` | Append-only, manual rotation |

**Log Entry Examples:**

```
[2026-06-07T14:15:23+00:00] [WARNING] [192.168.1.100] Board not found: board_id="nonexistent"
[2026-06-07T14:16:00+00:00] [ERROR] [192.168.1.100] File write failed: data/boards/general/threads.json - Permission denied
[2026-06-07T14:17:00+00:00] [NOTICE] [10.0.0.55] Rate limit exceeded: 6 posts in 60s
```

---

## 9. Deployment Design

### 9.1 Server Requirements

| Requirement | Minimum | Recommended |
|---|---|---|
| PHP | 8.1 | 8.3 |
| Web Server | Apache 2.4+ or Nginx 1.18+ | Nginx 1.24+ |
| PHP Extensions | `json`, `mbstring`, `filter`, `session` | All minimum + `fileinfo` |
| Disk Space | 100 MB | 1 GB |
| Memory | 32 MB per PHP process | 64 MB |

### 9.2 Initial Setup Flow

```
1. Clone/copy application files to server
2. Set document root to text-board/public/
3. Ensure data/ directory is writable by web server user:
   chown -R www-data:www-data data/
   chmod 750 data/
   chmod 750 data/boards/ data/ip_logs/ data/logs/ data/tmp/
4. Navigate to https://example.com/setup
5. Create admin account (username + password)
   → admin.json is created, /setup becomes inaccessible
6. Log in at /admin/login
7. Create first board via Admin Panel
8. Board is ready for public use
```

### 9.3 Apache Configuration

**.htaccess (`public/.htaccess`):**

```apache
RewriteEngine On

# Redirect trailing slashes (except root)
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.+)/$ /$1 [L,R=301]

# Route all non-file requests to index.php
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]

# Deny access to hidden files
<FilesMatch "^\.">
    Require all denied
</FilesMatch>
```

**Data Directory Protection (`data/.htaccess`):**

```apache
<IfModule mod_php.c>
    php_flag engine off
</IfModule>
Require all denied
```

### 9.4 Nginx Configuration

```nginx
server {
    listen 80;
    server_name example.com;
    root /var/www/text-board/public;
    index index.php;

    # Deny access to hidden files
    location ~ /\. {
        deny all;
    }

    # Deny direct access to data directory
    location /data/ {
        deny all;
        return 403;
    }

    # Route all requests to front controller
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP processing
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 30;
    }
}
```

### 9.5 File Permissions

| Path | Owner | Permissions | Rationale |
|---|---|---|---|
| `public/` | root | `0755` | World-readable, writable only by admin |
| `public/css/` | root | `0755` | Static assets |
| `src/` | root | `0750` | PHP source, not directly accessible |
| `templates/` | root | `0750` | Template files |
| `data/` | www-data | `0750` | Only web server needs access |
| `data/admin.json` | www-data | `0640` | Sensitive credentials |
| `data/boards/` | www-data | `0750` | Read/write needed for threads |
| `data/tmp/` | www-data | `0750` | Temp files for atomic writes |
| `data/logs/` | www-data | `0750` | Append-only logging |
| `data/ip_logs/` | www-data | `0750` | Append-only IP logging |

---

## 10. Testing Strategy

### 10.1 Unit Testing

**Scope:** Individual component methods with mocked FlatfileStore.

**Tools:** PHPUnit (the only Composer dev-dependency, used only for testing, never in production).

**Key Test Cases:**

| Component | Test Cases |
|---|---|
| `Validator` | Valid/invalid board IDs, thread IDs, post IDs; message sanitization edge cases (NULL bytes, emoji, long strings); IP validation (IPv4, IPv6, invalid); rate limit window logic |
| `Security` | CSRF token generation and validation; HTML escaping of special characters; password hashing and verification; session timeout logic |
| `Helpers` | Relative time formatting (now, minutes, hours, days); excerpt generation (short text, long text, newlines); unique ID format validation |
| `FlatfileStore` | Read non-existent file; write and read back; atomic write integrity; lock acquisition and release; JSON decode error handling |

### 10.2 Integration Testing

**Scope:** Full request-to-response flows with a temporary `data/` directory.

**Key Test Scenarios:**

| Scenario | Steps | Expected |
|---|---|---|
| Create thread | POST to `/boards/general/new` with valid data | 303 redirect, thread file exists, threads.json updated |
| Reply to thread (top-level) | POST reply with `parent_id=` | 303 redirect, reply appended, bump_score updated |
| Reply to reply (nested) | POST reply with valid `parent_id` | 303 redirect, nested reply appended, tree structure correct |
| Invalid parent_id | POST reply with non-existent `parent_id` | 400 "Invalid parent post." |
| Thread ranking | Create 3 threads with different reply patterns | Threads sorted by bump_score DESC, then recency |
| Cascading delete | Admin deletes a reply with 3 descendants | All 4 replies removed, reply_count and bump_score updated |
| Rate limiting | POST 6 times within 60s from same IP | 6th request returns 429 |
| CSRF protection | POST without CSRF token | 403 "Invalid security token." |
| Admin login | POST correct credentials | 303 redirect, session created |
| Admin login (wrong) | POST wrong password | 401, audit log entry |
| Session timeout | Wait 1 hour, try admin page | Redirect to login |

### 10.3 Security Testing

| Test | Method | Expected Result |
|---|---|---|
| XSS injection in message | `<script>alert('xss')</script>` in post body | Rendered as escaped text, not executed |
| Path traversal in board_id | `../../etc/passwd` as board_id | 404 (doesn't match board_id pattern) |
| NULL byte injection | `message\0with NULL` in POST | NULL byte stripped before processing |
| Direct .json access | `GET /data/boards.json` | 403/404 (outside document root) |
| SQL injection | `' OR '1'='1` in message | No effect (no database) |
| Session fixation | Set cookie before login, check after | Session ID changes after login |
| Massive payload | 1 MB message | Truncated to 10000 chars |
| Concurrent posting | 10 simultaneous replies to same thread | No data corruption (file locking) |

### 10.4 Test Data Management

- All tests use a temporary data directory (`/tmp/text-board-test/`) created in `setUp()` and destroyed in `tearDown()`.
- Test fixtures (boards, threads, replies) are created programmatically for each test.
- No test depends on the state left by another test.
- The production `data/` directory is never touched by tests.

---

*End of Software Design Document*

---

© 2026 Abhishek Kumar <mr.kumar.abhishek@outlook.in> — Licensed under [CC BY-SA 4.0](../LICENSE.md)
