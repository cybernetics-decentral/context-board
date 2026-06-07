# Agile Implementation Plan — Test-Driven Development

## Context Board Application

**Version:** 1.0
**Date:** 2026-06-07
**Methodology:** Scrum, 6 sprints × 1 week
**Testing Approach:** Test-Driven Development (Red-Green-Refactor)
**Based on:** [SRS.md](SRS.md) v1.0 / [SDD.md](SDD.md) v1.0

---

## Table of Contents

1. [Overview](#1-overview)
   1.1 [TDD Philosophy](#11-tdd-philosophy)
   1.2 [Testing Pyramid](#12-testing-pyramid)
   1.3 [Sprint Cadence](#13-sprint-cadence)
2. [Requirements Traceability](#2-requirements-traceability)
3. [Sprint 0 — Project Scaffold & Test Infrastructure](#3-sprint-0--project-scaffold--test-infrastructure)
4. [Sprint 1 — Core Data Layer](#4-sprint-1--core-data-layer)
5. [Sprint 2 — Read-Only Board & Thread Display](#5-sprint-2--read-only-board--thread-display)
6. [Sprint 3 — Anonymous Posting & Reply System](#6-sprint-3--anonymous-posting--reply-system)
7. [Sprint 4 — Thread Ranking, Bump Algorithm & Rate Limiting](#7-sprint-4--thread-ranking-bump-algorithm--rate-limiting)
8. [Sprint 5 — Admin Authentication & Panel](#8-sprint-5--admin-authentication--panel)
9. [Sprint 6 — Security Hardening, Deployment & Polish](#9-sprint-6--security-hardening-deployment--polish)
10. [Definition of Done](#10-definition-of-done)
11. [Risk Register](#11-risk-register)

---

## 1. Overview

### 1.1 TDD Philosophy

Every feature in this plan follows the **Red-Green-Refactor** cycle:

```
  ┌──────────────────────────────────────────┐
  │          TDD Cycle per User Story         │
  │                                           │
  │  1. RED    — Write a failing test         │
  │              (unit or integration)         │
  │                   │                       │
  │                   ▼                       │
  │  2. GREEN  — Write the minimum code       │
  │              to make the test pass         │
  │                   │                       │
  │                   ▼                       │
  │  3. REFACTOR — Improve code structure     │
  │                without changing behavior   │
  │                   │                       │
  │                   ▼                       │
  │  4. REPEAT  — Next test case              │
  └──────────────────────────────────────────┘
```

**Enforcement rules:**

- **No production code without a failing test first.**
- **No new test without a clear SRS requirement (FR-xxx) or SDD component reference.**
- Unit tests target `src/*.php` classes with mocked dependencies.
- Integration tests target full HTTP request-to-response flows against a temporary `data/` directory.
- All tests run in CI before merge.

### 1.2 Testing Pyramid

```
           ┌──────┐
           │  E2E │  ← Security scans, manual exploratory
           ├──────┤
           │ INT  │  ← Full request→response flows (Section 10.2 of SDD)
           ├──────┤
           │ UNIT │  ← Every class method, every edge case
           └──────┘
```

**Target coverage:**

| Layer | Coverage Target | Framework |
|---|---|---|
| Unit | ≥ 90% line coverage | PHPUnit 10.x |
| Integration | All FR-xxx scenarios | PHPUnit + `setUp()`/`tearDown()` temp dir |
| Security | All NFR-S01–NFR-S11 | phpcs, manual pen-test |

### 1.3 Sprint Cadence

| Sprint | Duration | Focus | Story Points | FRs Covered |
|---|---|---|---|---|
| **Sprint 0** | 3 days | Scaffold, config, test harness | 3 | — |
| **Sprint 1** | 1 week | FlatfileStore, Validator, Helpers, IpLogger | 8 | — |
| **Sprint 2** | 1 week | Router, BoardController, Templates, CSS | 13 | FR-003, FR-004, FR-007, FR-007a, FR-012, FR-024 |
| **Sprint 3** | 1 week | PostController, CSRF, reply tree, auto-refresh | 13 | FR-001, FR-002, FR-006, FR-007, FR-007b, FR-025, FR-026 |
| **Sprint 4** | 1 week | Bump algorithm, thread ranking, rate limiting, pagination | 8 | FR-005, FR-008, FR-009, FR-010 |
| **Sprint 5** | 1 week | AuthController, AdminController, cascading delete | 13 | FR-011, FR-013–FR-023 |
| **Sprint 6** | 1 week | Security hardening, deployment configs, polish, docs | 5 | NFR-S01–NFR-S11, NFR-R01–R04 |

---

## 2. Requirements Traceability

The implementation plan maps every Functional Requirement to at least one user story, one unit test, and one integration test.

| FR | Requirement | Sprint | User Story | Unit Tests | Integration Tests |
|---|---|---|---|---|---|
| FR-001 | Submit new thread | Sprint 3 | US-3.1 | `PostControllerTest`, `ValidatorTest` | `PostControllerIntegrationTest` |
| FR-002 | Submit reply (nested) | Sprint 3 | US-3.2 | `PostControllerTest`, `ValidatorTest` | `PostControllerIntegrationTest` |
| FR-003 | Display thread index | Sprint 2 | US-2.2 | `BoardControllerTest` | `BoardControllerIntegrationTest` |
| FR-004 | Display threaded view | Sprint 2 | US-2.3 | `BoardControllerTest` | `BoardControllerIntegrationTest` |
| FR-005 | Thread auto-deletion | Sprint 4 | US-4.2 | `FlatfileStoreTest`, `PostControllerTest` | — |
| FR-006 | Reply count tracking | Sprint 3 | US-3.2 | `PostControllerTest` | `PostControllerIntegrationTest` |
| FR-007 | Sequential post numbers | Sprint 2 | US-2.3 | `HelpersTest` | `BoardControllerIntegrationTest` |
| FR-007a | CSS indentation | Sprint 2 | US-2.4 | — (visual) | `BoardControllerIntegrationTest` (assert class) |
| FR-007b | Parent-child validation | Sprint 3 | US-3.2 | `ValidatorTest` | `PostControllerIntegrationTest` |
| FR-008 | Bump score computation | Sprint 4 | US-4.1 | `HelpersTest` (algorithm unit) | `PostControllerIntegrationTest` |
| FR-009 | Board sort by bump | Sprint 4 | US-4.1 | `BoardControllerTest` | `BoardControllerIntegrationTest` |
| FR-010 | IP capture & store | Sprint 4 | US-4.3 | `ValidatorTest`, `IpLoggerTest` | `PostControllerIntegrationTest` |
| FR-011 | Admin IP visibility | Sprint 5 | US-5.4 | `AdminControllerTest` | `AdminControllerIntegrationTest` |
| FR-012 | Board index display | Sprint 2 | US-2.1 | `BoardControllerTest` | `BoardControllerIntegrationTest` |
| FR-013 | Admin create board | Sprint 5 | US-5.2 | `AdminControllerTest` | `AdminControllerIntegrationTest` |
| FR-014 | Admin rename board | Sprint 5 | US-5.2 | `AdminControllerTest` | `AdminControllerIntegrationTest` |
| FR-015 | Admin delete board | Sprint 5 | US-5.2 | `AdminControllerTest`, `FlatfileStoreTest` | `AdminControllerIntegrationTest` |
| FR-016 | Admin dashboard | Sprint 5 | US-5.1 | `AdminControllerTest` | `AdminControllerIntegrationTest` |
| FR-017 | Admin delete thread | Sprint 5 | US-5.3 | `AdminControllerTest` | `AdminControllerIntegrationTest` |
| FR-018 | Admin delete reply | Sprint 5 | US-5.3 | `AdminControllerTest` | `AdminControllerIntegrationTest` |
| FR-018a | Cascading reply delete | Sprint 5 | US-5.3 | `AdminControllerTest` (algorithm unit) | `AdminControllerIntegrationTest` |
| FR-019 | Admin thread view w/ IPs | Sprint 5 | US-5.4 | `AdminControllerTest` | `AdminControllerIntegrationTest` |
| FR-020 | Admin login | Sprint 5 | US-5.1 | `AuthControllerTest`, `SecurityTest` | `AuthControllerIntegrationTest` |
| FR-021 | Password hashing/setup | Sprint 5 | US-5.1 | `SecurityTest` | `AuthControllerIntegrationTest` |
| FR-022 | Admin logout | Sprint 5 | US-5.1 | `AuthControllerTest` | `AuthControllerIntegrationTest` |
| FR-023 | Session timeout | Sprint 5 | US-5.1 | `SecurityTest` | `AuthControllerIntegrationTest` |
| FR-024 | Page auto-refresh | Sprint 3 | US-3.3 | `TemplateTest` | `BoardControllerIntegrationTest` (assert meta tag) |
| FR-025 | Dedicated reply page | Sprint 3 | US-3.3 | `PostControllerTest` | `PostControllerIntegrationTest` (assert no meta) |
| FR-026 | Reply link w/ parent_id | Sprint 3 | US-3.2 | `TemplateTest` | `BoardControllerIntegrationTest` |

---

## 3. Sprint 0 — Project Scaffold & Test Infrastructure

**Duration:** 3 days
**Story Points:** 3
**Goal:** Bootable application skeleton, PHPUnit harness, CI pipeline.

### User Stories

#### US-0.1: Directory Scaffold

> **As a** developer
> **I want** the project directory structure from SDD Section 2.3 to exist with proper `.htaccess` rules
> **So that** development can begin with a known layout.

**TDD approach:** Not applicable (scaffold only). Verified by `ls` + file existence checks.

**Acceptance criteria:**
- [ ] `public/index.php` returns HTTP 200 with placeholder content
- [ ] `public/.htaccess` routes all non-file requests to `index.php`
- [ ] `src/`, `templates/`, `data/` directories exist with correct permissions
- [ ] `data/` is outside document root (not accessible via HTTP)
- [ ] `composer.json` declares `phpunit/phpunit:^10.0` as dev-dependency only

#### US-0.2: Configuration File

> **As a** developer
> **I want** [`src/config.php`](src/config.php) to return a well-defined configuration array per SDD Section 3.3
> **So that** all components reference a single source of truth.

**Unit tests (`ConfigTest`):**

| Test ID | Test Case | Expected |
|---|---|---|
| UT-0.2.1 | `config.php` returns an array | `assertIsArray()` |
| UT-0.2.2 | Required keys exist | `data_dir`, `boards_dir`, `ip_logs_dir`, `app_log_dir`, `tmp_dir`, `template_dir` |
| UT-0.2.3 | `max_message_length` is positive integer | `assertGreaterThan(0)` |
| UT-0.2.4 | `rate_limit_window` is positive integer | `assertGreaterThan(0)` |
| UT-0.2.5 | `password_algo` is a valid constant | `PASSWORD_ARGON2ID` or `PASSWORD_BCRYPT` |

#### US-0.3: PHPUnit Bootstrap & CI

> **As a** developer
> **I want** PHPUnit configured with a proper bootstrap that sets up autoloading and a temporary data directory
> **So that** all tests run in an isolated, repeatable environment.

**Test harness (`tests/bootstrap.php`):**

- Defines `ROOT_DIR` constant
- Requires all `src/*.php` files
- Creates `/tmp/context-board-test/` in `setUp()` of base `TestCase`
- Deletes `/tmp/context-board-test/` in `tearDown()`
- Overrides config `data_dir` to point to temp directory

**Acceptance criteria:**
- [ ] `phpunit.xml` configured with test suite directories
- [ ] `./vendor/bin/phpunit` runs successfully (0 tests, green)
- [ ] `TestCase::setUp()` creates temp dir; `TestCase::tearDown()` cleans it
- [ ] CI workflow file exists (`.github/workflows/test.yml` or equivalent)

### Sprint 0 Deliverables

```
context-board/
├── .github/workflows/test.yml
├── composer.json
├── phpunit.xml
├── tests/
│   ├── bootstrap.php
│   ├── TestCase.php
│   └── Unit/
│       └── ConfigTest.php
├── public/
│   ├── index.php          ← placeholder "OK"
│   ├── .htaccess
│   └── css/
│       └── style.css      ← empty
├── src/
│   └── config.php
├── templates/
│   └── .gitkeep
└── data/
    └── .gitkeep
```

---

## 4. Sprint 1 — Core Data Layer

**Duration:** 1 week
**Story Points:** 8
**Goal:** All data-access and utility components unit-tested and functional. No HTTP layer yet.

**Components under test:** `FlatfileStore`, `Validator`, `Helpers`, `IpLogger`, `Security`, `Template`

### User Stories

#### US-1.1: FlatfileStore — JSON Read/Write/Lock

> **As a** developer
> **I want** [`FlatfileStore`](src/FlatfileStore.php) to provide safe read/write/delete operations for JSON files with advisory locking
> **So that** controllers can persist data without direct filesystem manipulation.
>
> **SDD Reference:** [Section 3.8](SDD.md#38-flatfile-store-srcflatfilestorephp)

**Unit tests (`FlatfileStoreTest`):**

| Test ID | Method | Input | Expected |
|---|---|---|---|
| UT-1.1.1 | `readJson()` on non-existent file | Path with no file | Returns `[]` |
| UT-1.1.2 | `writeJson()` then `readJson()` | `['foo' => 'bar']` | Returns `['foo' => 'bar']` |
| UT-1.1.3 | `writeJson()` with `JSON_PRETTY_PRINT` | Complex array | File contains pretty-printed JSON |
| UT-1.1.4 | `writeJson()` is atomic (temp-then-rename) | Write, kill process mid-write | Target file uncorrupted or absent |
| UT-1.1.5 | `exists()` on existing file | Path that exists | `true` |
| UT-1.1.6 | `exists()` on missing file | Non-existent path | `false` |
| UT-1.1.7 | `delete()` removes file | Existing file path | `exists()` returns `false` |
| UT-1.1.8 | `deleteDirectory()` recursively removes | Dir with files | Directory gone |
| UT-1.1.9 | `createDirectory()` makes dirs | Nested path | Both parent and child exist |
| UT-1.1.10 | `readJson()` on corrupt JSON | `{invalid` | Returns `[]`, logs warning |
| UT-1.1.11 | Lock contention (2 processes) | Simultaneous write | One succeeds, other waits then succeeds |
| UT-1.1.12 | `listDirectory()` returns files | Dir with 3 files | Returns array of 3 filenames |
| UT-1.1.13 | `readRaw()` and `writeRaw()` | Raw string | Round-trips correctly |
| UT-1.1.14 | Write with Unicode/emoji | `"Hello 🌍"` | `JSON_UNESCAPED_UNICODE` preserves emoji |

**Red phase (write first, watch it fail):**
```php
// tests/Unit/FlatfileStoreTest.php
public function testReadJsonOnNonExistentFileReturnsEmptyArray(): void
{
    $store = new FlatfileStore($this->tempDir);
    $result = $store->readJson('nonexistent.json');
    $this->assertSame([], $result);
}
```

**Green phase (implement):**
```php
// src/FlatfileStore.php
public function readJson(string $relativePath): array
{
    if (!$this->exists($relativePath)) {
        return [];
    }
    $raw = file_get_contents($this->resolvePath($relativePath));
    $data = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        // log warning, return empty
        return [];
    }
    return $data;
}
```

**Refactor phase:**
- Extract path resolution into private `resolvePath()`
- Extract JSON decode error handling into private `safeJsonDecode()`

#### US-1.2: Validator — Input Sanitization & Validation

> **As a** developer
> **I want** [`Validator`](src/Validator.php) to provide static methods for all input validation and sanitization
> **So that** every controller uses the same validation rules.
>
> **SDD Reference:** [Section 3.10](SDD.md#310-validator-srcvalidatorphp)

**Unit tests (`ValidatorTest`):**

| Test ID | Method | Input | Expected |
|---|---|---|---|
| UT-1.2.1 | `sanitizeMessage()` strips NULL bytes | `"hello\0world"` | `"helloworld"` |
| UT-1.2.2 | `sanitizeMessage()` normalizes line endings | `"a\r\nb"` | `"a\nb"` |
| UT-1.2.3 | `sanitizeMessage()` trims whitespace | `"  hi  "` | `"hi"` |
| UT-1.2.4 | `sanitizeMessage()` truncates to 10000 | 12000-char string | 10000 chars |
| UT-1.2.5 | `sanitizeSubject()` trims and truncates | 300-char string | 200 chars |
| UT-1.2.6 | `isValidBoardId('general')` | Valid ID | `true` |
| UT-1.2.7 | `isValidBoardId('gen eral')` | Space | `false` |
| UT-1.2.8 | `isValidBoardId('-invalid')` | Starts with hyphen | `false` |
| UT-1.2.9 | `isValidBoardId('invalid-')` | Ends with hyphen | `false` |
| UT-1.2.10 | `isValidBoardId('')` | Empty | `false` |
| UT-1.2.11 | `isValidBoardId()` 33-char string | Too long | `false` |
| UT-1.2.12 | `isValidBoardId('a_b-c')` | Valid mixed | `true` |
| UT-1.2.13 | `isValidThreadId()` valid | `"1717700123.456789.ab12cd34"` | `true` |
| UT-1.2.14 | `isValidThreadId()` invalid hex | `"123.456.ghij"` | `false` |
| UT-1.2.15 | `isValidThreadId()` missing segment | `"123.456"` | `false` |
| UT-1.2.16 | `isValidPostId()` same as thread | Same pattern | Same results |
| UT-1.2.17 | `isValidIp('192.168.1.1')` | IPv4 | `true` |
| UT-1.2.18 | `isValidIp('2001:db8::1')` | IPv6 | `true` |
| UT-1.2.19 | `isValidIp('not.an.ip')` | Invalid | `false` |
| UT-1.2.20 | `validateMessageLength('')` | Empty | `"A message is required."` |
| UT-1.2.21 | `validateMessageLength()` 1 char | Valid | `null` (no error) |
| UT-1.2.22 | `validateMessageLength()` 10001 chars | Too long | Error string |
| UT-1.2.23 | `sanitizeMessage()` preserves emoji | `"🌟 test"` | `"🌟 test"` |
| UT-1.2.24 | `sanitizeMessage()` preserves UTF-8 | `"café résumé"` | `"café résumé"` |

#### US-1.3: Helpers — Formatting & Utilities

> **As a** developer
> **I want** [`Helpers`](src/Helpers.php) to provide static formatting utilities
> **So that** templates and controllers format data consistently.
>
> **SDD Reference:** [Section 3.13](SDD.md#313-helpers-srchelpersphp)

**Unit tests (`HelpersTest`):**

| Test ID | Method | Input | Expected |
|---|---|---|---|
| UT-1.3.1 | `relativeTime()` now | `time()` | `"just now"` or `"less than a minute ago"` |
| UT-1.3.2 | `relativeTime()` 5 min ago | `time() - 300` | Contains `"5 minutes ago"` |
| UT-1.3.3 | `relativeTime()` 2 hours ago | `time() - 7200` | Contains `"2 hours ago"` |
| UT-1.3.4 | `relativeTime()` 3 days ago | `time() - 259200` | Contains `"3 days ago"` |
| UT-1.3.5 | `absoluteTime()` format | Unix timestamp | `"2026-06-07 14:15:23"` |
| UT-1.3.6 | `excerpt()` short text | `"Hello"` | `"Hello"` (unchanged) |
| UT-1.3.7 | `excerpt()` long text | 500 chars | 150 chars + `"…"` |
| UT-1.3.8 | `excerpt()` breaks at word | `"word1 word2 word3..."` | Truncates at word boundary |
| UT-1.3.9 | `excerpt()` replaces newlines | `"line1\nline2"` | `"line1 line2"` (collapsed spaces) |
| UT-1.3.10 | `generateId()` format | (none) | Matches `/^\d+\.\d+\.[a-f0-9]+$/` |
| UT-1.3.11 | `generateId()` uniqueness | 1000 calls | All unique |

#### US-1.4: IpLogger — Append-Only IP Logging

> **As a** developer
> **I want** [`IpLogger`](src/IpLogger.php) to append one JSON line per post action to a daily log file
> **So that** IP-to-post mappings are recorded for moderation.
>
> **SDD Reference:** [Section 3.9](SDD.md#39-ip-logger-srciploggerphp)

**Unit tests (`IpLoggerTest`):**

| Test ID | Input | Expected |
|---|---|---|
| UT-1.4.1 | `log()` with all fields | File contains one JSON line with correct fields |
| UT-1.4.2 | Two calls to `log()` | Two lines in file |
| UT-1.4.3 | Log file name is UTC date | `ip_logs/2026-06-07.log` |
| UT-1.4.4 | action = `'new_thread'` | JSON `"action":"new_thread"` |
| UT-1.4.5 | action = `'reply'` | JSON `"action":"reply"` |
| UT-1.4.6 | IPv4 address stored | `"ip":"192.168.1.100"` |
| UT-1.4.7 | IPv6 address stored | `"ip":"2001:db8::1"` |

#### US-1.5: Security — CSRF, Escaping, Password Hashing

> **As a** developer
> **I want** [`Security`](src/Security.php) to provide CSRF token generation/validation, HTML escaping, and password operations
> **So that** security primitives are centralized and consistently applied.
>
> **SDD Reference:** [Section 3.11](SDD.md#311-security-srcsecurityphp)

**Unit tests (`SecurityTest`):**

| Test ID | Method | Input | Expected |
|---|---|---|---|
| UT-1.5.1 | `generateCsrfToken()` | (none) | 64-char hex string (32 bytes → 64 hex) |
| UT-1.5.2 | `validateCsrfToken()` match | Same token | `true` |
| UT-1.5.3 | `validateCsrfToken()` mismatch | Different token | `false` |
| UT-1.5.4 | `escapeHtml('<script>')` | Script tag | `"<script>"` |
| UT-1.5.5 | `escapeHtml('"quoted"')` | Double quotes | `""quoted""` |
| UT-1.5.6 | `escapeHtml("it's")` | Single quote | `"it&#039;s"` |
| UT-1.5.7 | `escapeHtml('&')` | Already entity | Not double-encoded |
| UT-1.5.8 | `escapeAttribute('" onmouseover="xss')` | Attribute injection | Properly escaped |
| UT-1.5.9 | `hashPassword('test1234')` | 8-char password | Returns string starting with `$argon2id$` or `$2y$` |
| UT-1.5.10 | `verifyPassword('test1234', hash)` | Correct | `true` |
| UT-1.5.11 | `verifyPassword('wrong', hash)` | Wrong | `false` |
| UT-1.5.12 | `getCsrfTokenField()` | (none) | Returns `<input type="hidden" name="csrf_token" value="...">` |
| UT-1.5.13 | `sendSecurityHeaders()` sets CSP | Call method | `x-content-security-policy` header set |
| UT-1.5.14 | `checkSessionTimeout()` expired | 3700s old | Session cleared, returns false |
| UT-1.5.15 | `checkSessionTimeout()` valid | 100s old | Session extended, returns true |

#### US-1.6: Template — Layout Rendering

> **As a** developer
> **I want** [`Template`](src/Template.php) to render PHP templates with layout inheritance via output buffering
> **So that** controllers can delegate HTML generation.
>
> **SDD Reference:** [Section 3.12](SDD.md#312-template-engine-srctemplatephp)

**Unit tests (`TemplateTest`):**

| Test ID | Input | Expected |
|---|---|---|
| UT-1.6.1 | `render('simple', ['name' => 'Zoo'])` | Output contains `"Zoo"` |
| UT-1.6.2 | Template sets `$layout` | Output wrapped in layout |
| UT-1.6.3 | Template with auto-refresh data | `<meta http-equiv="refresh" content="30">` present |
| UT-1.6.4 | Template without auto-refresh | No `<meta http-equiv="refresh">` present |
| UT-1.6.5 | `$pageTitle` in data | `<title>My Title</title>` present |
| UT-1.6.6 | Non-existent template | Throws `RuntimeException` |

### Sprint 1 Deliverables

- [ ] `FlatfileStore` fully implemented with all 14 unit tests passing
- [ ] `Validator` fully implemented with all 24 unit tests passing
- [ ] `Helpers` fully implemented with all 11 unit tests passing
- [ ] `IpLogger` fully implemented with all 7 unit tests passing
- [ ] `Security` fully implemented with all 15 unit tests passing
- [ ] `Template` fully implemented with all 6 unit tests passing
- [ ] All 77 unit tests green, ≥ 90% line coverage

---

## 5. Sprint 2 — Read-Only Board & Thread Display

**Duration:** 1 week
**Story Points:** 13
**Goal:** Users can browse boards and view threads (read-only). Zero write functionality.

**Components:** `Router`, `BoardController`, `templates/*`, `public/css/style.css`

### User Stories

#### US-2.1: Home Page — Board Index

> **As an** anonymous user
> **I want** to see a list of all sub-boards on the home page
> **So that** I can choose which board to browse.
>
> **SRS:** [FR-012](SRS.md#fr-012-board-index-display)
> **SDD:** [Section 3.4](SDD.md#34-board-controller-srcboardcontrollerphp)

**Red phase — Integration test first:**

```php
// tests/Integration/BoardControllerIntegrationTest.php
public function testHomePageShowsBoardList(): void
{
    // Arrange: create boards.json with 2 boards
    $this->store->writeJson('boards.json', [
        ['board_id' => 'general', 'name' => 'General', 'description' => 'Chat',
         'sort_order' => 1, 'max_threads' => 100, 'created_at' => time()],
        ['board_id' => 'tech', 'name' => 'Tech', 'description' => 'Tech talk',
         'sort_order' => 2, 'max_threads' => 100, 'created_at' => time()],
    ]);

    // Act
    $response = $this->get('/');

    // Assert
    $this->assertSame(200, $response->getStatusCode());
    $this->assertStringContainsString('General', $response->getBody());
    $this->assertStringContainsString('Tech', $response->getBody());
    $this->assertStringContainsString('/boards/general', $response->getBody());
    $this->assertStringContainsString('/boards/tech', $response->getBody());
}
```

**Unit tests (`BoardControllerTest`):**

| Test ID | Method | Scenario | Expected |
|---|---|---|---|
| UT-2.1.1 | `index()` | 0 boards | Renders with empty list |
| UT-2.1.2 | `index()` | 3 boards | Renders all 3, sorted by `sort_order` |
| UT-2.1.3 | `index()` | boards out of order | Sorted ascending by `sort_order` |

**Unit tests (`RouterTest`):**

| Test ID | Route | Expected |
|---|---|---|
| UT-2.2.1 | `GET /` | Dispatches to `BoardController::index()` |
| UT-2.2.2 | `GET /nonexistent` | 404 response |

#### US-2.2: Board Thread List with Auto-Refresh

> **As an** anonymous user
> **I want** to see a paginated list of threads when I visit a board
> **So that** I can browse discussions and see activity.
>
> **SRS:** [FR-003](SRS.md#fr-003-display-thread-index-for-a-board), [FR-024](SRS.md#fr-024-page-auto-refresh-for-board-index-and-thread-view)
> **SDD:** [Section 3.4](SDD.md#34-board-controller-srcboardcontrollerphp), `showBoard()`

**Integration tests:**

| Test ID | Scenario | Expected |
|---|---|---|
| IT-2.2.1 | `GET /boards/general` with 0 threads | 200, empty thread list message |
| IT-2.2.2 | `GET /boards/general` with 5 threads | 200, all 5 listed |
| IT-2.2.3 | `GET /boards/nonexistent` | 404 "Board not found." |
| IT-2.2.4 | `GET /boards/general?page=1` | First 20 threads |
| IT-2.2.5 | `GET /boards/general?page=2` | Threads 21–40 |
| IT-2.2.6 | Auto-refresh meta tag | `<meta http-equiv="refresh" content="30">` present |
| IT-2.2.7 | Thread excerpt displayed | 150-char excerpt visible |
| IT-2.2.8 | Thread subject displayed | Subject or "No Subject" visible |
| IT-2.2.9 | Reply count displayed | `reply_count` visible per thread |
| IT-2.2.10 | `GET /boards/general?page=999` | Redirects to last valid page |

**Unit tests:**

| Test ID | Method | Scenario | Expected |
|---|---|---|---|
| UT-2.3.1 | `showBoard()` | Board not found | Throws `NotFoundException` (404) |
| UT-2.3.2 | `showBoard()` | 25 threads | Pagination: 2 pages, first has 20 |
| UT-2.3.3 | `showBoard()` | Page 1 from query string | Reads `$_GET['page']` |

#### US-2.3: Thread View — Nested Reply Tree

> **As an** anonymous user
> **I want** to view a thread with OP and all replies displayed in a nested tree with "[Reply]" links
> **So that** I can follow the discussion hierarchy.
>
> **SRS:** [FR-004](SRS.md#fr-004-display-a-single-thread-threadednested-view), [FR-007](SRS.md#fr-007-sequential-post-numbers-depth-first-traversal), [FR-007a](SRS.md#fr-007a-nested-reply-indentation-via-css), [FR-026](SRS.md#fr-026-reply-link-with-pre-selected-parent)
> **SDD:** [Section 3.4](SDD.md#34-board-controller-srcboardcontrollerphp), `showThread()`, [Section 6.2](SDD.md#62-reply-tree-construction-flat-to-tree), [Section 6.3](SDD.md#63-depth-first-post-numbering)

**Integration tests:**

| Test ID | Scenario | Expected |
|---|---|---|
| IT-2.3.1 | `GET /boards/general/thread/{valid_id}` | 200, OP visible |
| IT-2.3.2 | Thread with 3 replies (flat) | All replies rendered |
| IT-2.3.3 | Thread with nested replies (depth 2) | `.reply-depth-0` and `.reply-depth-1` CSS classes present |
| IT-2.3.4 | OP is post #1 | `#1` displayed on OP |
| IT-2.3.5 | Depth-first numbering | Replies numbered in traversal order |
| IT-2.3.6 | "[Reply]" link on OP | Link to `/reply` without `parent_id` |
| IT-2.3.7 | "[Reply]" link on reply | Link to `/reply?parent_id={post_id}` |
| IT-2.3.8 | "Post a Reply" button at bottom | Link to `/reply` page |
| IT-2.3.9 | Auto-refresh meta tag | `<meta http-equiv="refresh" content="30">` present |
| IT-2.3.10 | `GET .../thread/{nonexistent}` | 404 "Thread not found." |
| IT-2.3.11 | Messages preserve newlines | `white-space: pre-wrap` or `nl2br` applied |
| IT-2.3.12 | Indentation maxes at 10 levels | `.reply-depth-10` for depth ≥ 10 |

**Unit tests for algorithms:**

| Test ID | Algorithm | Input | Expected |
|---|---|---|---|
| UT-2.4.1 | `buildReplyTree()` | Empty replies | Empty tree |
| UT-2.4.2 | `buildReplyTree()` | 1 top-level reply | Tree with 1 node, depth 0 |
| UT-2.4.3 | `buildReplyTree()` | 1 nested reply | Tree depth 1 under parent |
| UT-2.4.4 | `buildReplyTree()` | 3 siblings sorted | Chronological order |
| UT-2.4.5 | `assignPostNumbers()` | OP + 2 replies | OP=1, reply1=2, reply2=3 |
| UT-2.4.6 | `assignPostNumbers()` | OP + nested | Depth-first: OP=1, top=2, nested=3, next-top=4 |

#### US-2.4: CSS Styling

> **As an** anonymous user
> **I want** the board to look clean and readable with indented replies
> **So that** I can easily follow discussion threads.
>
> **SRS:** UR-001, UR-009
> **SDD:** [Section 5.4](SDD.md#54-css-class-naming-conventions)

**Visual regression tests (manual + automated):**

| Test ID | Check | Tool |
|---|---|---|
| VT-2.4.1 | `.reply-depth-{n}` classes apply increasing margin | CSS assertion: `margin-left` grows by 20px/level |
| VT-2.4.2 | `.post:target` highlights anchored post | CSS pseudo-class present |
| VT-2.4.3 | No `display:none` critical content | Accessibility check |
| VT-2.4.4 | Responsive layout | Viewport 320px–1920px readable |
| VT-2.4.5 | `<label>` elements on all inputs | HTML validation |

### Sprint 2 Deliverables

- [ ] `Router` implemented with all routes from SDD Section 5.1 registered
- [ ] `BoardController::index()`, `showBoard()`, `showThread()`, `newThreadForm()` implemented
- [ ] All templates: `layout.php`, `board_index.php`, `thread_list.php`, `thread_view.php`
- [ ] `public/css/style.css` with indentation classes, responsive layout
- [ ] Reply tree construction algorithm (`buildReplyTree`) implemented
- [ ] Depth-first post numbering (`assignPostNumbers`) implemented
- [ ] All 30+ integration/unit tests green
- [ ] Board index → Board → Thread view navigation works end-to-end

---

## 6. Sprint 3 — Anonymous Posting & Reply System

**Duration:** 1 week
**Story Points:** 13
**Goal:** Anonymous users can create threads and post replies (top-level and nested). CSRF protection active.

**Components:** `PostController`, CSRF integration, `reply_form.php` template

### User Stories

#### US-3.1: Create New Thread

> **As an** anonymous user
> **I want** to fill out a form and create a new thread in a board
> **So that** I can start a discussion.
>
> **SRS:** [FR-001](SRS.md#fr-001-submit-a-new-thread)
> **SDD:** [Section 3.5](SDD.md#35-post-controller-srcpostcontrollerphp), `createThread()`

**Red phase — Integration test:**

```php
public function testCreateThreadWithValidData(): void
{
    $this->setupBoard('general');
    $csrfToken = $this->getCsrfToken();

    $response = $this->post('/boards/general/new', [
        'subject'    => 'Test Thread',
        'message'    => 'This is a test message.',
        'csrf_token' => $csrfToken,
    ]);

    $this->assertSame(303, $response->getStatusCode());
    $this->assertStringContainsString('/boards/general/thread/', $response->getHeaderLine('Location'));

    // Verify thread file exists
    $threads = $this->store->readJson('boards/general/threads.json');
    $this->assertCount(1, $threads);

    // Verify thread JSON was created
    $threadId = $threads[0]['thread_id'];
    $thread = $this->store->readJson("boards/general/threads/{$threadId}.json");
    $this->assertSame('Test Thread', $thread['subject']);
    $this->assertSame(0, $thread['reply_count']);
    $this->assertSame(0, $thread['bump_score']);
}
```

**Integration tests:**

| Test ID | Scenario | Expected |
|---|---|---|
| IT-3.1.1 | `POST /boards/{id}/new` valid data | 303 redirect to thread view |
| IT-3.1.2 | Thread file created with correct structure | `op`, `replies: []`, metadata fields present |
| IT-3.1.3 | `threads.json` index updated | New entry with `bump_score=0`, `bump_recency=created_at` |
| IT-3.1.4 | Empty subject → `"No Subject"` | Thread created with default subject |
| IT-3.1.5 | IP logged to daily file | `ip_logs/{date}.log` has one entry with `action: "new_thread"` |
| IT-3.1.6 | `POST` without message | 400 "A message is required." |
| IT-3.1.7 | `POST /boards/nonexistent/new` | 400 "Board not found." |
| IT-3.1.8 | Message exceeds 10000 chars | Truncated to 10000, thread created |
| IT-3.1.9 | Subject exceeds 200 chars | Truncated to 200 |
| IT-3.1.10 | Missing CSRF token | 403 "Invalid security token." |
| IT-3.1.11 | Wrong CSRF token | 403 "Invalid security token." |

#### US-3.2: Reply to Thread (Top-Level & Nested)

> **As an** anonymous user
> **I want** to reply to a thread or to another reply
> **So that** I can participate in discussions.
>
> **SRS:** [FR-002](SRS.md#fr-002-submit-a-reply-top-level-or-nested), [FR-006](SRS.md#fr-006-reply-count-tracking), [FR-007b](SRS.md#fr-007b-parent-child-relationship-validation), [FR-025](SRS.md#fr-025-dedicated-reply-form-page-no-auto-refresh), [FR-026](SRS.md#fr-026-reply-link-with-pre-selected-parent)
> **SDD:** [Section 3.5](SDD.md#35-post-controller-srcpostcontrollerphp), `createReply()`, `replyForm()`

**Integration tests:**

| Test ID | Scenario | Expected |
|---|---|---|
| IT-3.2.1 | `GET .../reply` renders form | 200, form with message textarea, no auto-refresh meta |
| IT-3.2.2 | `GET .../reply?parent_id={id}` | Hidden `parent_id` field pre-filled |
| IT-3.2.3 | `POST` top-level reply (`parent_id` empty) | 303 redirect, reply appended to `replies[]`, `parent_id=null` |
| IT-3.2.4 | `POST` nested reply (valid `parent_id`) | 303 redirect, reply appended with correct `parent_id` |
| IT-3.2.5 | `reply_count` incremented | Thread file + threads.json both updated |
| IT-3.2.6 | `last_modified` updated | Timestamp reflects reply time |
| IT-3.2.7 | `POST` with invalid `parent_id` | 400 "Invalid parent post." |
| IT-3.2.8 | `POST` without message | 400 "A message is required." |
| IT-3.2.9 | `GET .../reply` — NO auto-refresh | No `<meta http-equiv="refresh">` tag |
| IT-3.2.10 | Redirect includes anchor `#post-{new_id}` | Location header contains `#post-` |
| IT-3.2.11 | `POST` to non-existent thread | 400 "Thread not found." |
| IT-3.2.12 | Reply with emoji | Emoji preserved in thread JSON |
| IT-3.2.13 | IP logged with `action: "reply"` | IP log entry verified |

**Unit tests for reply validation:**

| Test ID | Method | Scenario | Expected |
|---|---|---|---|
| UT-3.2.1 | `PostController::createReply()` | Valid top-level reply | Returns redirect response |
| UT-3.2.2 | `PostController::createReply()` | Parent post not in thread | Throws `InvalidParentException` |
| UT-3.2.3 | `PostController::createReply()` | Empty message after sanitize | Throws `BadRequestException` |
| UT-3.2.4 | `PostController::createReply()` | Thread file over 512KB | Throws `PayloadTooLargeException` (413) |

#### US-3.3: Reply Form Isolation & Auto-Refresh

> **As an** anonymous user
> **I want** the reply form to be on a separate page that does NOT auto-refresh
> **So that** I can type without interruption.
>
> **SRS:** [FR-024](SRS.md#fr-024-page-auto-refresh-for-board-index-and-thread-view), [FR-025](SRS.md#fr-025-dedicated-reply-form-page-no-auto-refresh)
> **SDD:** [Section 3.5](SDD.md#35-post-controller-srcpostcontrollerphp), `replyForm()`

**Integration tests:**

| Test ID | Page | Auto-Refresh? | Verified How |
|---|---|---|---|
| IT-3.3.1 | `GET /boards/{id}` | **Yes** | `<meta http-equiv="refresh" content="30">` present |
| IT-3.3.2 | `GET /boards/{id}/thread/{id}` | **Yes** | `<meta http-equiv="refresh" content="30">` present |
| IT-3.3.3 | `GET /boards/{id}/thread/{id}/reply` | **No** | No refresh meta tag |
| IT-3.3.4 | `GET /boards/{id}/new` | **No** | No refresh meta tag |
| IT-3.3.5 | `GET /` | **No** | No refresh meta tag |

### Sprint 3 Deliverables

- [ ] `PostController::createThread()` implemented
- [ ] `PostController::createReply()` implemented
- [ ] `PostController::replyForm()` implemented
- [ ] `new_thread_form.php` template
- [ ] `reply_form.php` template
- [ ] CSRF token validation on all POST routes
- [ ] IP logging on all post actions
- [ ] Auto-refresh correctly toggled per page type
- [ ] All 30+ integration/unit tests green
- [ ] Full anonymous posting flow works end-to-end

---

## 7. Sprint 4 — Thread Ranking, Bump Algorithm & Rate Limiting

**Duration:** 1 week
**Story Points:** 8
**Goal:** Threads are correctly ranked by sub-thread bump score. Rate limiting prevents spam. Thread auto-deletion works.

### User Stories

#### US-4.1: Bump Score & Thread Ranking

> **As an** anonymous user
> **I want** threads with active sub-thread discussions to appear at the top
> **So that** I can find the most engaging conversations.
>
> **SRS:** [FR-008](SRS.md#fr-008-compute-bump-score-from-sub-thread-branches), [FR-009](SRS.md#fr-009-board-index-sorted-by-bump-score-then-recency)
> **SDD:** [Section 6.1](SDD.md#61-bump-score-computation), [Section 6.5](SDD.md#65-thread-ranking-sort)

**Red phase — Algorithm unit test:**

```php
public function testComputeBumpScoreWithTwoBranches(): void
{
    $replies = [
        // Branch A: 3 replies (R1 + R2 + R3)
        ['post_id' => 'r1', 'parent_id' => null, 'timestamp' => 100],
        ['post_id' => 'r2', 'parent_id' => 'r1', 'timestamp' => 200],
        ['post_id' => 'r3', 'parent_id' => 'r2', 'timestamp' => 300],
        // Branch B: 2 replies (R4 + R5)
        ['post_id' => 'r4', 'parent_id' => null, 'timestamp' => 150],
        ['post_id' => 'r5', 'parent_id' => 'r4', 'timestamp' => 250],
    ];

    [$bumpScore, $bumpRecency] = computeBumpScore($replies);

    $this->assertSame(3, $bumpScore);    // Branch A has 3
    $this->assertSame(300, $bumpRecency); // Most recent in Branch A
}
```

**Algorithm unit tests:**

| Test ID | Scenario | Expected bump_score | Expected bump_recency |
|---|---|---|---|
| UT-4.1.1 | No replies | 0 | 0 (caller sets to `created_at`) |
| UT-4.1.2 | 1 top-level reply | 1 | reply timestamp |
| UT-4.1.3 | 1 top-level + 1 nested | 2 | nested timestamp |
| UT-4.1.4 | Two branches: 3 vs 2 | 3 | winning branch max timestamp |
| UT-4.1.5 | Two branches: tied at 3 | 3 | max timestamp across both |
| UT-4.1.6 | Deep nesting (depth 10) | 10 | deepest timestamp |
| UT-4.1.7 | 100 replies across 5 branches | Largest branch count | Branch max timestamp |

**Thread ranking integration tests:**

| Test ID | Thread Setup | Sort Order |
|---|---|---|
| IT-4.1.1 | Thread A: score=5, Thread B: score=3 | A before B |
| IT-4.1.2 | Both score=3, A: recency=500, B: recency=300 | A before B |
| IT-4.1.3 | Thread A: score=0, Thread B: score=1 | B before A |
| IT-4.1.4 | After new reply in Thread B, score changes | B moves above A if score now higher |

#### US-4.2: Thread Auto-Deletion

> **As an** administrator
> **I want** old threads auto-deleted when the board thread limit is exceeded
> **So that** storage is managed automatically.
>
> **SRS:** [FR-005](SRS.md#fr-005-thread-auto-deletion-optional--configurable-limit)

**Integration tests:**

| Test ID | Setup | Expected |
|---|---|---|
| IT-4.2.1 | max_threads=3, create 4th thread | Oldest/lowest-score thread deleted |
| IT-4.2.2 | max_threads=0 (unlimited) | No deletion, 100 threads OK |
| IT-4.2.3 | Tie: 2 threads with same score, different recency | Older recency deleted |
| IT-4.2.4 | Thread JSON file removed from disk | `threads/{id}.json` deleted |
| IT-4.2.5 | Thread removed from index | `threads.json` entry removed |

#### US-4.3: IP Address Recording

> **As a** system
> **I want** poster IPs to be recorded with each post
> **So that** the admin can moderate content.
>
> **SRS:** [FR-010](SRS.md#fr-010-capture-and-store-poster-ip-address)
> **SDD:** [Section 3.9](SDD.md#39-ip-logger-srciploggerphp)

**Integration tests:**

| Test ID | Scenario | Expected |
|---|---|---|
| IT-4.3.1 | New thread — IP in thread JSON `op.ip` | IP matches `$_SERVER['REMOTE_ADDR']` |
| IT-4.3.2 | New reply — IP in reply object `ip` | IP recorded |
| IT-4.3.3 | Invalid IP → `"0.0.0.0"` | Falls back to marker IP |
| IT-4.3.4 | IPv6 address recorded correctly | Full IPv6 string stored |
| IT-4.3.5 | IP log entry created | Correct date file, correct JSON fields |

#### US-4.4: Rate Limiting

> **As a** system
> **I want** to limit posting to 5 posts per 60 seconds per IP
> **So that** spam and abuse are prevented.
>
> **SRS:** NFR-S08
> **SDD:** [Section 7.5](SDD.md#75-rate-limiting)

**Integration tests:**

| Test ID | Scenario | Expected |
|---|---|---|
| IT-4.4.1 | 5 posts in 60s (same IP) | All 5 succeed |
| IT-4.4.2 | 6th post within 60s | 429 "You are posting too quickly." |
| IT-4.4.3 | Wait 60s, post again | Succeeds (window expired) |
| IT-4.4.4 | 5 posts from IP-A, 1 from IP-B | IP-B succeeds (different IP) |
| IT-4.4.5 | Rate limit file cleaned up after 1h | Old rate file deleted |

#### US-4.5: Pagination

> **As an** anonymous user
> **I want** thread listings paginated with 20 threads per page
> **So that** I can navigate large boards efficiently.
>
> **SRS:** [FR-003](SRS.md#fr-003-display-thread-index-for-a-board) step 4

**Integration tests:**

| Test ID | Scenario | Expected |
|---|---|---|
| IT-4.5.1 | 25 threads | Page 1: 20, Page 2: 5 |
| IT-4.5.2 | 20 threads | Page 1: 20, no page 2 link |
| IT-4.5.3 | 0 threads | "No threads yet" message |
| IT-4.5.4 | page=1 query param | Shows first page |
| IT-4.5.5 | page=999 (beyond range) | Redirects to last page |

### Sprint 4 Deliverables

- [ ] `computeBumpScore()` algorithm implemented and tested
- [ ] `sortThreadsByRank()` implemented
- [ ] Thread auto-deletion on `max_threads` exceeded
- [ ] IP recording in all post flows
- [ ] Rate limiting middleware/check implemented
- [ ] Pagination with page navigation
- [ ] All 25+ tests green

---

## 8. Sprint 5 — Admin Authentication & Panel

**Duration:** 1 week
**Story Points:** 13
**Goal:** Complete admin system: authentication, dashboard, board CRUD, content moderation with cascading delete.

**Components:** `AuthController`, `AdminController`, admin templates, session management

### User Stories

#### US-5.1: Admin Authentication (Login, Logout, Session, Setup)

> **As an** administrator
> **I want** to log in with a username and password, have a secure session, and be able to log out
> **So that** only I can access the admin panel.
>
> **SRS:** [FR-020](SRS.md#fr-020-admin-login), [FR-021](SRS.md#fr-021-admin-password-hashing-initial-setup), [FR-022](SRS.md#fr-022-admin-logout), [FR-023](SRS.md#fr-023-session-timeout)
> **SDD:** [Section 3.7](SDD.md#37-auth-controller-srcauthcontrollerphp), [Section 7.1](SDD.md#71-authentication-flow), [Section 7.6](SDD.md#76-session-management)

**Red phase — Integration test:**

```php
public function testAdminLoginSuccess(): void
{
    // Arrange: create admin.json with known credentials
    $passwordHash = password_hash('admin123', PASSWORD_ARGON2ID);
    $this->store->writeJson('admin.json', [
        'username'            => 'admin',
        'password_hash'       => $passwordHash,
        'created_at'          => time(),
        'last_password_change' => time(),
    ]);

    // Act
    $response = $this->post('/admin/login', [
        'username'   => 'admin',
        'password'   => 'admin123',
        'csrf_token' => $this->getCsrfToken(),
    ]);

    // Assert
    $this->assertSame(303, $response->getStatusCode());
    $this->assertStringContainsString('/admin', $response->getHeaderLine('Location'));
    $this->assertArrayHasKey('PHPSESSID', $this->getCookies($response));
}
```

**Integration tests:**

| Test ID | Scenario | Expected |
|---|---|---|
| IT-5.1.1 | `POST /admin/login` correct credentials | 303 → `/admin`, session created |
| IT-5.1.2 | `POST /admin/login` wrong password | 401 "Invalid username or password." |
| IT-5.1.3 | `POST /admin/login` wrong username | 401 (same generic message) |
| IT-5.1.4 | Failed login → audit log entry | `admin_audit.log` has `result: "failure"` |
| IT-5.1.5 | Successful login → audit log entry | `admin_audit.log` has `result: "success"` |
| IT-5.1.6 | Session regenerated on login | `session_regenerate_id()` called |
| IT-5.1.7 | `GET /admin` without session | 302 redirect to `/admin/login` |
| IT-5.1.8 | `GET /admin/logout` | Session destroyed, 303 → `/` |
| IT-5.1.9 | Session timeout (1h+) | Redirect to login with "Session expired" |
| IT-5.1.10 | `GET /setup` when `admin.json` missing | 200, setup form displayed |
| IT-5.1.11 | `POST /setup` create admin | `admin.json` created, `/setup` disabled |
| IT-5.1.12 | `GET /setup` when `admin.json` exists | 404 or redirect |
| IT-5.1.13 | CSRF token required on login | 403 without token |
| IT-5.1.14 | Password min length 8 | Rejected if shorter |
| IT-5.1.15 | Password change: wrong current | 400 "Current password is incorrect." |
| IT-5.1.16 | Password change: valid | `password_hash` updated in `admin.json` |

#### US-5.2: Board CRUD

> **As an** administrator
> **I want** to create, rename, and delete sub-boards
> **So that** I can organize the discussion categories.
>
> **SRS:** [FR-013](SRS.md#fr-013-admin-create-sub-board), [FR-014](SRS.md#fr-014-admin-rename-sub-board), [FR-015](SRS.md#fr-015-admin-delete-sub-board)

**Integration tests:**

| Test ID | Scenario | Expected |
|---|---|---|
| IT-5.2.1 | `POST /admin/boards/create` valid | Board created, directory + `threads.json` exist |
| IT-5.2.2 | Duplicate `board_id` | 400 "Board already exists." |
| IT-5.2.3 | Invalid `board_id` (special chars) | 400 validation error |
| IT-5.2.4 | `POST /admin/boards/{id}/rename` | Name/description updated in `boards.json` |
| IT-5.2.5 | `POST /admin/boards/{id}/delete` without confirm | 400 "You must check the confirmation box." |
| IT-5.2.6 | `POST /admin/boards/{id}/delete` with confirm | Board directory deleted, `boards.json` updated |
| IT-5.2.7 | All board CRUD requires auth | 302 redirect if unauthenticated |

#### US-5.3: Content Moderation (Thread & Reply Deletion, Cascading)

> **As an** administrator
> **I want** to delete entire threads or individual replies (with cascading deletion of nested replies)
> **So that** I can remove inappropriate content.
>
> **SRS:** [FR-017](SRS.md#fr-017-admin-delete-thread), [FR-018](SRS.md#fr-018-admin-delete-individual-reply), [FR-018a](SRS.md#fr-018a-cascading-deletion-of-nested-replies)
> **SDD:** [Section 6.4](SDD.md#64-cascading-reply-deletion)

**Red phase — Cascading delete unit test:**

```php
public function testCascadeDeleteRemovesAllDescendants(): void
{
    $replies = [
        ['post_id' => 'r1', 'parent_id' => null, 'message' => 'keep'],
        ['post_id' => 'r2', 'parent_id' => 'r1', 'message' => 'delete-parent'],
        ['post_id' => 'r3', 'parent_id' => 'r2', 'message' => 'delete-child'],
        ['post_id' => 'r4', 'parent_id' => 'r2', 'message' => 'delete-child-2'],
        ['post_id' => 'r5', 'parent_id' => null, 'message' => 'keep-2'],
    ];

    $result = cascadeDeleteReplies($replies, 'r2');

    $remainingIds = array_column($result['replies'], 'post_id');
    $this->assertContains('r1', $remainingIds);
    $this->assertContains('r5', $remainingIds);
    $this->assertNotContains('r2', $remainingIds);
    $this->assertNotContains('r3', $remainingIds);
    $this->assertNotContains('r4', $remainingIds);
    $this->assertSame(3, $result['deleted_count']);
}
```

**Integration tests:**

| Test ID | Scenario | Expected |
|---|---|---|
| IT-5.3.1 | Delete thread | Thread file deleted, index entry removed |
| IT-5.3.2 | Delete top-level reply (no children) | 1 reply removed, `reply_count` decremented |
| IT-5.3.3 | Delete reply with 3 nested descendants | All 4 removed, `reply_count` decremented by 4 |
| IT-5.3.4 | After cascading delete, bump_score recomputed | Score reflects remaining branches |
| IT-5.3.5 | Delete confirmation required | 400 without `confirm=1` |
| IT-5.3.6 | Delete non-existent reply | 400 "Reply not found." |

#### US-5.4: Admin Dashboard & IP Visibility

> **As an** administrator
> **I want** a dashboard with board statistics and to see IPs next to posts
> **So that** I can monitor activity and identify posters if needed.
>
> **SRS:** [FR-016](SRS.md#fr-016-admin-dashboard), [FR-011](SRS.md#fr-011-admin-ip-address-visibility), [FR-019](SRS.md#fr-019-admin-view-thread-with-ip-addresses)
> **SDD:** [Section 3.6](SDD.md#36-admin-controller-srcadmincontrollerphp), `dashboard()`, `moderateThread()`

**Integration tests:**

| Test ID | Scenario | Expected |
|---|---|---|
| IT-5.4.1 | `GET /admin` | Dashboard with board names, thread counts, reply counts |
| IT-5.4.2 | Dashboard "Last Activity" | Shows most recent timestamp per board |
| IT-5.4.3 | `GET /admin/boards/{id}/thread/{id}` as admin | IP addresses visible (`.post-ip` class) |
| IT-5.4.4 | Same URL as anonymous | No IP addresses displayed |
| IT-5.4.5 | `GET /admin/boards/{id}` | Thread list with delete buttons |

### Sprint 5 Deliverables

- [ ] `AuthController` with all 6 methods implemented
- [ ] `AdminController` with all 9 methods implemented
- [ ] 6 admin templates (login, dashboard, board_manage, board_moderate, thread_moderate, password_change)
- [ ] Cascading delete algorithm with unit tests
- [ ] Session security: httponly, samesite, regenerate on login, timeout
- [ ] Admin audit logging for login attempts
- [ ] Initial setup flow (`/setup`) for first-run
- [ ] All 50+ integration/unit tests green

---

## 9. Sprint 6 — Security Hardening, Deployment & Polish

**Duration:** 1 week
**Story Points:** 5
**Goal:** Production-ready application. All NFRs verified. Deployment guides tested.

### User Stories

#### US-6.1: Security Hardening

> **As a** system administrator
> **I want** the application to pass a security audit
> **So that** it is safe to deploy on the public internet.

**Security tests (from SDD Section 10.3):**

| Test ID | Attack Vector | Test | Expected |
|---|---|---|---|
| ST-6.1.1 | XSS | `<script>alert('xss')</script>` in message | Rendered as escaped text |
| ST-6.1.2 | Path traversal | `board_id=../../etc/passwd` | 404 (pattern mismatch) |
| ST-6.1.3 | NULL byte | `message\0with NULL` | NULL stripped |
| ST-6.1.4 | Direct JSON access | `GET /data/boards.json` | 403/404 (outside document root) |
| ST-6.1.5 | SQL injection | `' OR '1'='1` in message | No effect (no database) |
| ST-6.1.6 | Session fixation | Phpssid cookie before login | Session ID changes after login |
| ST-6.1.7 | Massive payload | 1 MB message | Truncated to 10000 chars |
| ST-6.1.8 | Concurrent writes | 10 simultaneous replies | No corruption (file locking verified) |
| ST-6.1.9 | CSP header | Check response headers | `Content-Security-Policy: ... script-src 'none'` |
| ST-6.1.10 | No JavaScript served | Grep all responses | No `<script>` tags served |
| ST-6.1.11 | X-Content-Type-Options | Response header | `nosniff` |
| ST-6.1.12 | X-Frame-Options | Response header | `DENY` |

#### US-6.2: Deployment Configuration

> **As a** system administrator
> **I want** clear deployment instructions and server configuration files
> **So that** I can deploy the application to a production server.
>
> **SDD:** [Section 9](SDD.md#9-deployment-design)

**Acceptance criteria:**

| Test ID | Verification |
|---|---|
| DP-6.2.1 | Apache `.htaccess` routes correctly on Apache 2.4 |
| DP-6.2.2 | Nginx config works on Nginx 1.24 |
| DP-6.2.3 | `README.md` has step-by-step setup instructions |
| DP-6.2.4 | `data/` permissions: `0750` dirs, `0640` files |
| DP-6.2.5 | Application runs on PHP 8.1, 8.2, and 8.3 |
| DP-6.2.6 | Fresh clone → `/setup` → create admin → board functional |

#### US-6.3: Error Handling & Graceful Degradation

> **As a** user
> **I want** clear error pages when something goes wrong
> **So that** I understand what happened without seeing technical details.
>
> **SDD:** [Section 8](SDD.md#8-error-handling-design)

**Integration tests:**

| Test ID | Scenario | HTTP Status | User Message |
|---|---|---|---|
| IT-6.3.1 | Corrupt thread JSON | 500 | "An internal error occurred." (no stack trace) |
| IT-6.3.2 | File write permission denied | 500 | Generic error (no path leaked) |
| IT-6.3.3 | Board not found | 404 | "Board not found." |
| IT-6.3.4 | Thread not found | 404 | "Thread not found." |
| IT-6.3.5 | Rate limited | 429 | "You are posting too quickly." |
| IT-6.3.6 | Thread file too large | 413 | "This thread has reached the maximum number of replies." |
| IT-6.3.7 | Debug mode = true | 500 | Stack trace visible |
| IT-6.3.8 | Debug mode = false (production) | 500 | Generic message only |

#### US-6.4: Polish

> **As a** user
> **I want** a polished experience with consistent navigation, readable formatting, and responsive layout.

**Acceptance criteria:**

- [ ] Breadcrumb navigation on all pages
- [ ] Relative timestamps accurate and readable
- [ ] CSS responsive at 320px–1920px viewport widths
- [ ] All form inputs have `<label>` elements
- [ ] No broken links in any template
- [ ] `README.md` complete with project description, setup, and configuration reference

### Sprint 6 Deliverables

- [ ] All 12 security tests passing
- [ ] Apache & Nginx config files tested
- [ ] `README.md` complete
- [ ] Error templates: `400.php`, `403.php`, `404.php`, `500.php`
- [ ] Debug mode toggle verified
- [ ] Final CSS polish
- [ ] Application log rotation verified
- [ ] Full test suite: **all 200+ tests green, ≥ 90% line coverage**

---

## 10. Definition of Done

A user story is **Done** only when:

| Criterion | Verification |
|---|---|
| **Tests written first** | Unit and/or integration test committed before production code |
| **All tests pass** | `./vendor/bin/phpunit` exits with 0 |
| **Code coverage** | ≥ 90% for `src/` directory |
| **SRS traceability** | Story maps to ≥ 1 FR-xxx requirement |
| **SDD compliance** | Implementation matches SDD component design |
| **No JavaScript** | Zero `<script>` tags, zero `.js` files |
| **Code review** | Peer-reviewed (or self-reviewed with checklist) |
| **Documentation** | PHPDoc on all public methods, complex algorithms commented |

**Sprint-level Definition of Done:**

- [ ] All story acceptance criteria met
- [ ] Full regression test suite passes (all previous sprints' tests still green)
- [ ] Sprint demo to stakeholder (manual walkthrough of new features)
- [ ] Sprint retrospective completed
- [ ] `SRS.md` and `SDD.md` updated if any design decisions changed

---

## 11. Risk Register

| ID | Risk | Probability | Impact | Mitigation |
|---|---|---|---|---|
| R-01 | `flock()` not available on NFS/network filesystem | Low | High | Document requirement for local disk. Detect and warn in setup. |
| R-02 | JSON file corruption under high concurrency | Medium | High | Atomic write-via-temp-file pattern. Extensive concurrency tests (ST-6.1.8). |
| R-03 | PHP 8.1 EOL before project completion | Low | Medium | Code compatible with 8.1–8.3. Test matrix covers all versions. |
| R-04 | Thread file exceeds 512KB with many small replies | Medium | Low | Limit enforced before write. Clear 413 message. Admin can delete old threads. |
| R-05 | Admin password lost (no recovery mechanism) | Low | High | Document manual recovery: delete `admin.json`, re-run `/setup`. |
| R-06 | Rate limit file accumulation over time | Low | Low | Files older than 1 hour auto-deleted on read. Periodic cleanup. |
| R-07 | CSS-only UI limitations (no JS for enhanced UX) | Medium | Low | Accepted constraint (C-004). Progressive enhancement not applicable. |
| R-08 | Test data directory collision between parallel CI runs | Low | Medium | Use unique temp dir per run: `/tmp/context-board-test/{uuid}/`. |

---

## Appendix A: Sprint Ceremony Schedule

| Ceremony | Frequency | Duration | Participants |
|---|---|---|---|
| **Sprint Planning** | Per sprint | 2 hours | Dev team |
| **Daily Standup** | Daily | 15 minutes | Dev team |
| **Sprint Review / Demo** | End of sprint | 1 hour | Dev team + stakeholder |
| **Sprint Retrospective** | End of sprint | 45 minutes | Dev team |
| **Backlog Refinement** | Mid-sprint | 1 hour | Dev team |

## Appendix B: Test Naming Convention

```
tests/
├── Unit/
│   ├── FlatfileStoreTest.php
│   ├── ValidatorTest.php
│   ├── HelpersTest.php
│   ├── IpLoggerTest.php
│   ├── SecurityTest.php
│   ├── TemplateTest.php
│   ├── RouterTest.php
│   ├── BoardControllerTest.php
│   ├── PostControllerTest.php
│   ├── AdminControllerTest.php
│   └── AuthControllerTest.php
├── Integration/
│   ├── BoardControllerIntegrationTest.php
│   ├── PostControllerIntegrationTest.php
│   ├── AdminControllerIntegrationTest.php
│   ├── AuthControllerIntegrationTest.php
│   └── SecurityIntegrationTest.php
└── bootstrap.php
```

## Appendix C: CI Pipeline Stages

```yaml
# .github/workflows/test.yml
name: Test Suite
on: [push, pull_request]
jobs:
  test:
    strategy:
      matrix:
        php: ['8.1', '8.2', '8.3']
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ matrix.php }}
      - run: composer install
      - run: ./vendor/bin/phpunit --coverage-text
      - run: ./vendor/bin/phpunit --coverage-clover=coverage.xml
      - uses: codecov/codecov-action@v3
```

---

*End of Agile TDD Implementation Plan*

---

© 2026 Abhishek Kumar <mr.kumar.abhishek@outlook.in> — Licensed under [CC BY-SA 4.0](LICENSE.md)
