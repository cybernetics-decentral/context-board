# Software Requirements Specification (SRS)

## Text Board Application

**Version:** 1.0  
**Date:** 2026-06-07  
**Document Status:** Draft  

---

## Table of Contents

1. [Introduction](#1-introduction)  
  1.1 [Purpose](#11-purpose)  
  1.2 [Scope](#12-scope)  
  1.3 [Definitions and Acronyms](#13-definitions-and-acronyms)  
  1.4 [References](#14-references)  

2. [Overall Description](#2-overall-description)  
  2.1 [Product Perspective](#21-product-perspective)  
  2.2 [Product Functions](#22-product-functions)  
  2.3 [User Classes and Characteristics](#23-user-classes-and-characteristics)  
  2.4 [Operating Environment](#24-operating-environment)  
  2.5 [Design and Implementation Constraints](#25-design-and-implementation-constraints)  
  2.6 [Assumptions and Dependencies](#26-assumptions-and-dependencies)  

3. [System Features and Requirements](#3-system-features-and-requirements)  
  3.1 [Anonymous Message Posting](#31-anonymous-message-posting)  
  3.2 [Thread Management](#32-thread-management)  
  3.3 [Reply System](#33-reply-system)  
  3.4 [Thread Ranking (Bump System)](#34-thread-ranking-bump-system)  
  3.5 [IP Address Recording (IPv4 & IPv6)](#35-ip-address-recording-ipv4--ipv6)  
  3.6 [Sub-Board / Category System](#36-sub-board--category-system)  
  3.7 [Administrator Panel](#37-administrator-panel)  
  3.8 [Admin Authentication (Salted Password Hashing)](#38-admin-authentication-salted-password-hashing)  

4. [External Interface Requirements](#4-external-interface-requirements)  
  4.1 [User Interface (Web-Based)](#41-user-interface-web-based)  
  4.2 [Admin Interface](#42-admin-interface)  
  4.3 [File System Interface (Flatfile Structure)](#43-file-system-interface-flatfile-structure)  

5. [Non-Functional Requirements](#5-non-functional-requirements)  
  5.1 [Performance](#51-performance)  
  5.2 [Security](#52-security)  
  5.3 [Reliability](#53-reliability)  
  5.4 [Maintainability](#54-maintainability)  
  5.5 [Portability](#55-portability)  

6. [Data Requirements](#6-data-requirements)  
  6.1 [Flatfile Directory Structure](#61-flatfile-directory-structure)  
  6.2 [Data Formats](#62-data-formats)  
  6.3 [Admin Credentials Storage Format](#63-admin-credentials-storage-format)  
  6.4 [IP Address Storage Format](#64-ip-address-storage-format)  

7. [Technical Architecture Overview](#7-technical-architecture-overview)  
  7.1 [Directory Layout](#71-directory-layout)  
  7.2 [File Naming Conventions](#72-file-naming-conventions)  
  7.3 [Data Flow for Key Operations](#73-data-flow-for-key-operations)  

---

## 1. Introduction

### 1.1 Purpose

This Software Requirements Specification (SRS) defines the complete functional and non-functional requirements for the **Text Board Application** — a lightweight, anonymous, forum-like message board built with PHP 8.x and a flatfile storage backend. The document is intended to serve as the authoritative reference for developers implementing the system from scratch. Every functional requirement is assigned a unique identifier (FR-XXX) and includes explicit descriptions of inputs, processing logic, and outputs.

The target audience includes:

- **Backend developers** implementing the PHP logic and flatfile I/O.
- **Frontend developers** building the HTML/CSS user and admin interfaces.
- **System administrators** deploying and configuring the application.
- **Quality assurance** testers validating each requirement.

### 1.2 Scope

The Text Board Application is a self-contained, database-free web application providing anonymous threaded discussion. The scope encompasses:

- **Anonymous posting:** Any visitor may create a new thread or reply to an existing thread without registration.
- **Threaded replies:** All replies are grouped under their parent thread.
- **Bump-based thread ranking:** Threads are ordered by the timestamp of their most recent reply, with the most recently active thread at the top.
- **IP address recording:** The server records the poster's IP address (both IPv4 and IPv6 formats) alongside each post for moderation purposes. No other personally identifiable information (PII) is collected.
- **Sub-board categories:** The administrator may create, rename, and delete sub-boards (categories), each containing its own independent set of threads.
- **Admin authentication:** A single administrator account is protected by a salted, hashed password stored in a flatfile.
- **Admin panel:** A web interface for managing boards, viewing posts, deleting threads or replies, and (optionally) viewing IP addresses associated with posts.

**Out of scope:**

- User registration or login for non-administrative users.
- Private messaging.
- File uploads or image attachments (text-only board).
- Full-text search.
- Federation with other boards or external APIs.
- Database backends (MySQL, PostgreSQL, SQLite, etc.).

### 1.3 Definitions and Acronyms

| Term / Acronym | Definition |
|---|---|
| **Board** | A top-level category (sub-board) containing its own set of threads. Also referred to as a "sub-board." |
| **Thread** | An original post (OP) and all its associated replies. Represented as a single flatfile. |
| **OP** | Original Post — the first message that creates a new thread. |
| **Reply** | A message posted in response to an existing thread. |
| **Bump** | The action of a thread moving to the top of the board listing due to a new reply. |
| **Flatfile** | A data storage approach using plain files on disk instead of a database management system. |
| **SRS** | Software Requirements Specification. |
| **PII** | Personally Identifiable Information. |
| **PHP** | PHP: Hypertext Preprocessor — the server-side scripting language used for this project. |
| **XSS** | Cross-Site Scripting — a web security vulnerability where malicious scripts are injected into content. |
| **CSRF** | Cross-Site Request Forgery — an attack that forces a user to execute unwanted actions. |
| **IPv4** | Internet Protocol version 4 (e.g., `192.168.1.1`). |
| **IPv6** | Internet Protocol version 6 (e.g., `2001:0db8:85a3:0000:0000:8a2e:0370:7334`). |
| **Hash** | A one-way cryptographic function; specifically `password_hash()` using `PASSWORD_BCRYPT` or `PASSWORD_ARGON2ID` in PHP. |
| **Salt** | Random data appended to a password before hashing to prevent rainbow-table attacks. Managed automatically by PHP's `password_hash()`. |
| **JSON** | JavaScript Object Notation — the data-interchange format used for thread and board metadata files. |
| **Unix Timestamp** | Integer count of seconds since the Unix epoch (1970-01-01 00:00:00 UTC). |

### 1.4 References

| Ref # | Document / Standard | Description |
|---|---|---|
| [1] | IEEE Std 830-1998 | IEEE Recommended Practice for Software Requirements Specifications. |
| [2] | PHP Manual: `password_hash()` | https://www.php.net/manual/en/function.password-hash.php |
| [3] | PHP Manual: `password_verify()` | https://www.php.net/manual/en/function.password-verify.php |
| [4] | PHP Manual: `filter_var()` | https://www.php.net/manual/en/function.filter-var.php — used for IP address validation. |
| [5] | PHP Manual: `json_encode()` / `json_decode()` | https://www.php.net/manual/en/function.json-encode.php |
| [6] | PHP Manual: `flock()` | https://www.php.net/manual/en/function.flock.php — advisory file locking. |
| [7] | OWASP Cross-Site Scripting Prevention Cheat Sheet | https://cheatsheetseries.owasp.org/cheatsheets/Cross_Site_Scripting_Prevention_Cheat_Sheet.html |
| [8] | OWASP Password Storage Cheat Sheet | https://cheatsheetseries.owasp.org/cheatsheets/Password_Storage_Cheat_Sheet.html |
| [9] | RFC 791 / RFC 4291 | IPv4 and IPv6 addressing specifications. |

---

## 2. Overall Description

### 2.1 Product Perspective

The Text Board Application is a greenfield, standalone web application. It does not integrate with any existing system. It is self-contained: all data resides on the local filesystem, and all processing occurs server-side via PHP scripts. The application communicates with clients exclusively via HTTP/HTTPS, serving HTML pages generated by PHP.

```
+-------------------+       HTTP/HTTPS        +-------------------+
|                   | <---------------------> |                   |
|   Web Browser     |                         |   PHP Application |
|   (User / Admin)  |                         |   (Server)         |
|                   |                         |                   |
+-------------------+                         +---------+---------+
                                                         |
                                                         | File I/O
                                                         |
                                               +---------+---------+
                                               |   Flatfile Store  |
                                               |  (JSON files on   |
                                               |   local disk)     |
                                               +-------------------+
```

### 2.2 Product Functions

At a high level, the system provides the following functions:

1. **Board Listing:** Display all configured sub-boards with their names and descriptions.
2. **Thread Listing:** Within a board, display all threads sorted by most recent bump, with thread metadata (post count, last reply time, OP excerpt).
3. **Thread Viewing:** Display the original post and all replies in chronological order within a thread.
4. **New Thread Creation:** Accept a message from an anonymous user and create a new thread in the selected board.
5. **Reply Posting:** Accept a reply message and append it to an existing thread.
6. **IP Recording:** Transparently capture and store the poster's IP address with each post.
7. **Board Administration:** Create, rename, and delete sub-boards.
8. **Content Moderation:** Delete individual replies or entire threads.
9. **Admin Authentication:** Secure login for the single administrator account using salted password hashing.

### 2.3 User Classes and Characteristics

| User Class | Characteristics | Authentication |
|---|---|---|
| **Anonymous Poster** | Any visitor to the board. Can create new threads and reply to existing threads. Cannot edit or delete posts. No login required. | None |
| **Administrator** | A single designated user who manages the board. Can create/delete boards, delete threads/replies, and view IP addresses. | Username + salted/hashed password |

**Assumptions about Anonymous Posters:**

- They have a modern web browser with HTML form support.
- Their IP address is visible to the server (no anonymizing proxy requirement).
- They do not need persistent identity across sessions.

### 2.4 Operating Environment

| Component | Requirement |
|---|---|
| **Operating System** | Linux (any modern distribution) or any Unix-like OS with PHP support. |
| **Web Server** | Apache 2.4+ with `mod_rewrite` OR Nginx 1.18+. |
| **PHP Version** | PHP 8.1 or later (8.x series). |
| **PHP Extensions** | `json` (bundled), `mbstring` (for UTF-8 handling), `filter` (bundled). |
| **Filesystem** | Local disk with read/write permissions for the web server user (e.g., `www-data`). Must support advisory file locking (`flock`). |
| **Disk Space** | Minimal; a few megabytes for metadata. Actual usage grows with post volume. |
| **Browser Support** | Any modern browser (Chrome, Firefox, Safari, Edge) released within the last 3 years. JavaScript is not required for core functionality. |

### 2.5 Design and Implementation Constraints

| ID | Constraint | Rationale |
|---|---|---|
| C-001 | **PHP 8.x only.** The system shall use PHP 8.1 or later. | Leverages modern language features (named arguments, match expressions, readonly properties, enums) and receives active security support. |
| C-002 | **Flatfile storage only.** No relational database, NoSQL database, or embedded database (SQLite) shall be used. | Explicit project requirement. Simplifies deployment by eliminating the need for a DBMS. |
| C-003 | **JSON as the data format.** All structured data shall be stored in JSON-encoded files. | Human-readable, natively supported in PHP, easily debugged. |
| C-004 | **No client-side JavaScript required for core functionality.** The board must be fully usable with HTML forms and server-rendered pages only. | Maximizes compatibility and accessibility. JavaScript may be used for optional progressive enhancements (e.g., confirmation dialogs). |
| C-005 | **Single administrator account.** The system supports exactly one admin account. | Simplifies credential management in a flatfile architecture. |
| C-006 | **No PII beyond IP address.** The system shall not collect or store names, email addresses, or any other PII from posters. | Privacy requirement. |
| C-007 | **UTF-8 encoding throughout.** All files, HTML output, and JSON data shall use UTF-8 encoding. | Ensures consistent handling of international text. |
| C-008 | **File-based locking for concurrent access.** All writes must use `flock()` with `LOCK_EX` to prevent data corruption. | Prevents race conditions when multiple users post simultaneously. |

### 2.6 Assumptions and Dependencies

1. **Web server configuration:** It is assumed the web server is correctly configured to execute `.php` files and that the document root points to the application's `public/` directory.
2. **Write permissions:** The web server process has read and write permissions on the `data/` directory and all its contents.
3. **PHP CLI not required:** All administration is performed through the web-based admin panel; no command-line scripts are required.
4. **Single-server deployment:** The application is not designed for distributed or multi-server environments. File locking works only on a single host.
5. **Moderate traffic:** The flatfile architecture is designed for small to medium-sized communities (hundreds to low thousands of threads per board). It is not intended for high-traffic scenarios exceeding thousands of concurrent users.
6. **Administrator is trusted:** The admin has full access to the server's filesystem and IP address data. The admin is expected to handle this data responsibly.
7. **Timezone:** The server's system timezone is used for all timestamps (stored as Unix timestamps; displayed in the server's configured timezone).

---

## 3. System Features and Requirements

Each functional requirement below follows this format:

- **ID:** Unique identifier (FR-XXX).
- **Description:** What the system must do.
- **Priority:** High / Medium / Low.
- **Inputs:** Data or user actions that trigger the function.
- **Processing:** Step-by-step logic the system performs.
- **Outputs:** Observable results, data written, or responses returned.

---

### 3.1 Anonymous Message Posting

#### FR-001: Submit a New Thread

- **ID:** FR-001
- **Description:** An anonymous user shall be able to create a new thread in a selected sub-board by filling out and submitting a post form.
- **Priority:** High
- **Inputs:**
  - `board_id` (string): The identifier of the target sub-board (from the URL or hidden form field).
  - `message` (string, required, min 1 char, max 10000 chars): The body of the original post.
  - Optional `subject` (string, max 200 chars): A title for the thread. If empty, defaults to "No Subject."
  - Client IP address (automatically extracted from `$_SERVER['REMOTE_ADDR']`).
- **Processing:**
  1. Validate the HTTP method is `POST`.
  2. Retrieve and validate `board_id` against the list of existing boards (see [`boards.json`](#621-board-index-boardsjson)).
  3. If `board_id` is invalid or the board does not exist, return HTTP 400 with an error message.
  4. Sanitize `message` and `subject` inputs:
     - Strip NULL bytes.
     - Trim leading/trailing whitespace.
     - Validate `message` is not empty after trimming.
     - Truncate `message` to 10,000 characters and `subject` to 200 characters.
  5. Extract the client IP address from `$_SERVER['REMOTE_ADDR']` and validate it as a legitimate IPv4 or IPv6 address using PHP's `filter_var()` with `FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6`.
  6. Generate a unique thread ID:
     - Use the current Unix timestamp (microseconds) concatenated with a random 8-character hexadecimal string.
     - Format: `{unix_timestamp_micro}.{random_hex}`.
     - Example: `1717700000.123456.a3f2c109`.
  7. Generate a post ID for the OP: same as the thread ID.
  8. Construct the thread JSON object (see [Section 6.2.1](#621-board-index-boardsjson)).
  9. Acquire an exclusive lock (`flock` with `LOCK_EX`) on the board's thread index file (`data/boards/{board_id}/threads.json`).
  10. Read and decode the thread index, or initialize an empty array if the file does not exist.
  11. Append the new thread metadata entry to the index.
  12. Write the updated thread index back to disk.
  13. Release the lock.
  14. Write the thread JSON file to `data/boards/{board_id}/threads/{thread_id}.json`.
  15. On success, issue an HTTP 303 redirect to the thread view page.
- **Outputs:**
  - A new thread JSON file on disk.
  - An updated `threads.json` index file for the board.
  - HTTP redirect to the new thread's URL: `/boards/{board_id}/thread/{thread_id}`.

#### FR-002: Submit a Reply to an Existing Thread

- **ID:** FR-002
- **Description:** An anonymous user shall be able to reply to an existing thread.
- **Priority:** High
- **Inputs:**
  - `board_id` (string): The sub-board identifier.
  - `thread_id` (string): The thread to reply to.
  - `message` (string, required, min 1 char, max 10000 chars): The reply body.
  - Client IP address (automatically extracted).
- **Processing:**
  1. Validate HTTP method is `POST`.
  2. Validate `board_id` exists and `thread_id` corresponds to an existing thread file.
  3. Sanitize `message` (same process as FR-001, step 4).
  4. Extract and validate IP address (same as FR-001, step 5).
  5. Generate a unique post ID: `{unix_timestamp_micro}.{random_hex}` (distinct from thread ID).
  6. Construct the reply JSON object (see [Section 6.2.2](#622-thread-file-thread_idjson)).
  7. Acquire an exclusive lock on the thread file (`data/boards/{board_id}/threads/{thread_id}.json`).
  8. Read and decode the thread JSON.
  9. Append the reply object to the thread's `replies` array.
  10. Update the thread's `last_modified` timestamp and `reply_count`.
  11. Write the updated thread JSON back to disk.
  12. Release the lock.
  13. Acquire an exclusive lock on the board's `threads.json` index.
  14. Update the corresponding thread entry's `last_modified` and `reply_count` fields in the index.
  15. Write the updated index and release the lock.
  16. On success, issue an HTTP 303 redirect back to the thread view page, anchored to the new reply.
- **Outputs:**
  - Updated thread JSON file with the new reply appended.
  - Updated `threads.json` index with bumped metadata.
  - HTTP redirect to the thread view.

---

### 3.2 Thread Management

#### FR-003: Display Thread Index for a Board

- **ID:** FR-003
- **Description:** When a user navigates to a board, the system shall display a paginated list of all threads in that board, sorted by most recent bump (descending).
- **Priority:** High
- **Inputs:**
  - `board_id` (string, from URL).
  - Optional `page` (integer, default 1) for pagination.
- **Processing:**
  1. Validate `board_id` exists in `boards.json`.
  2. Read the board's `threads.json` index file.
  3. Sort the threads array by `last_modified` descending (newest first).
  4. Apply pagination: 20 threads per page.
  5. Render the thread list as HTML:
     - Each entry shows: subject (or "No Subject"), truncated message excerpt (first 150 chars), reply count, and relative time of last activity.
  6. Include pagination navigation if there are more than 20 threads.
- **Outputs:** HTML page displaying the thread index.

#### FR-004: Display a Single Thread

- **ID:** FR-004
- **Description:** Display the original post and all replies for a given thread in chronological order.
- **Priority:** High
- **Inputs:**
  - `board_id` and `thread_id` from the URL.
- **Processing:**
  1. Validate both identifiers.
  2. Read the thread JSON file.
  3. Render the OP and each reply as HTML, in order.
  4. Each post displays: message body, timestamp (formatted), and a sequential post number within the thread.
  5. Render a reply form at the bottom of the page.
- **Outputs:** HTML page displaying the full thread with reply form.

#### FR-005: Thread Auto-Deletion (Optional / Configurable Limit)

- **ID:** FR-005
- **Description:** The administrator may configure a maximum number of threads per board. When the limit is exceeded, the oldest thread (by `last_modified`) shall be automatically deleted upon creation of a new thread.
- **Priority:** Low
- **Inputs:**
  - Configurable `max_threads` per board (stored in [`boards.json`](#621-board-index-boardsjson)).
  - New thread creation trigger.
- **Processing:**
  1. After appending to `threads.json`, count the number of threads.
  2. If `count > max_threads`, identify the thread with the oldest `last_modified`.
  3. Delete that thread's JSON file from disk.
  4. Remove its entry from the `threads.json` index.
- **Outputs:** Old thread file deleted; index updated.

---

### 3.3 Reply System

This section is covered by [FR-002](#fr-002-submit-a-reply-to-an-existing-thread). Additional reply-related requirements:

#### FR-006: Reply Count Tracking

- **ID:** FR-006
- **Description:** Each thread shall maintain an accurate count of its replies.
- **Priority:** Medium
- **Inputs:** New reply submission (FR-002) or reply deletion (FR-010).
- **Processing:**
  1. On reply creation, increment `reply_count` in both the thread file and the board's `threads.json` index.
  2. On reply deletion, decrement `reply_count` in both locations.
- **Outputs:** Accurate `reply_count` values in thread files and index.

#### FR-007: Sequential Post Numbers

- **ID:** FR-007
- **Description:** Each post within a thread (including the OP) shall have a sequential number for easy reference by users.
- **Priority:** Medium
- **Inputs:** The ordered list of posts in a thread.
- **Processing:**
  1. On thread display, assign sequential numbers starting at 1 for the OP.
  2. These numbers are computed at render time, not stored persistently.
  3. Replies deleted by the admin cause gaps in numbering (by design).
- **Outputs:** Displayed post numbers (e.g., "Post #5") in the thread view.

---

### 3.4 Thread Ranking (Bump System)

#### FR-008: Bump Thread on New Reply

- **ID:** FR-008
- **Description:** When a reply is posted to a thread, that thread's `last_modified` timestamp shall be updated to the current time, causing it to appear at the top of the board's thread listing.
- **Priority:** High
- **Inputs:** Reply submission event (FR-002).
- **Processing:**
  1. As part of FR-002 processing, update the thread's `last_modified` field to the current Unix timestamp.
  2. Update the corresponding entry in the board's `threads.json` index.
- **Outputs:** The thread moves to position 1 in the sorted thread index.

#### FR-009: Board Index Sorted by Most Recent Activity

- **ID:** FR-009
- **Description:** The thread listing for each board shall always display threads in descending order of `last_modified`.
- **Priority:** High
- **Inputs:** The board's `threads.json` index file.
- **Processing:**
  1. On each request for a board's thread index, sort the entries by `last_modified` descending.
  2. Render the sorted list as HTML.
- **Outputs:** Sorted thread list.

---

### 3.5 IP Address Recording (IPv4 & IPv6)

#### FR-010: Capture and Store Poster IP Address

- **ID:** FR-010
- **Description:** The system shall record the IP address of every poster (both for new threads and replies). Both IPv4 and IPv6 formats shall be supported.
- **Priority:** High
- **Inputs:**
  - `$_SERVER['REMOTE_ADDR']` — the client IP as reported by the web server.
- **Processing:**
  1. Extract the raw IP string from `$_SERVER['REMOTE_ADDR']`.
  2. Validate using `filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6)`.
  3. If the IP is valid, store it in the post's `ip` field as a plain string.
  4. If the IP is invalid (should not occur under normal circumstances), store the string `"0.0.0.0"` (IPv4-mapped invalid marker) and log a warning.
  5. The IP is stored in two locations:
     - The thread JSON file (for OP and each reply).
     - A separate IP log file (see [Section 6.4](#64-ip-address-storage-format)).
- **Outputs:** IP address string stored in post data and IP log.

#### FR-011: Admin IP Address Visibility

- **ID:** FR-011
- **Description:** The administrator shall be able to view the IP address associated with each post when logged into the admin panel.
- **Priority:** Medium
- **Inputs:** Admin session authentication status.
- **Processing:**
  1. When rendering a thread for an authenticated admin session, include the IP address string alongside each post.
  2. When rendering for an anonymous user, omit IP addresses entirely.
- **Outputs:** IP addresses visible only to the admin.

---

### 3.6 Sub-Board / Category System

#### FR-012: Board Index Display

- **ID:** FR-012
- **Description:** The application home page shall display a list of all available sub-boards with their names and descriptions.
- **Priority:** High
- **Inputs:** `boards.json` index file.
- **Processing:**
  1. Read and decode `data/boards.json`.
  2. Sort boards by their `sort_order` field (ascending).
  3. Render each board as a clickable link with its name and description.
- **Outputs:** HTML board index page.

#### FR-013: Admin Create Sub-Board

- **ID:** FR-013
- **Description:** The administrator shall be able to create a new sub-board via the admin panel.
- **Priority:** High
- **Inputs:**
  - `board_id` (string, alphanumeric + hyphens, max 32 chars): A URL-safe slug for the board.
  - `name` (string, max 100 chars): Display name.
  - `description` (string, max 500 chars): Board description.
- **Processing:**
  1. Validate admin session.
  2. Sanitize and validate all inputs.
  3. Check that `board_id` is unique (not already in `boards.json`).
  4. Create the board's directory: `data/boards/{board_id}/`.
  5. Create the board's `threads.json` index file as an empty JSON array `[]`.
  6. Append the new board entry to `boards.json`.
  7. Write updated `boards.json`.
  8. Redirect to admin panel with success message.
- **Outputs:** New board directory, empty `threads.json`, updated `boards.json`.

#### FR-014: Admin Rename Sub-Board

- **ID:** FR-014
- **Description:** The administrator shall be able to rename an existing sub-board and/or update its description.
- **Priority:** Medium
- **Inputs:**
  - `board_id` (string): Existing board to modify.
  - `name` (string, optional): New display name.
  - `description` (string, optional): New description.
- **Processing:**
  1. Validate admin session.
  2. Verify `board_id` exists in `boards.json`.
  3. Update the board's entry in `boards.json` with new values.
  4. Write updated `boards.json`.
- **Outputs:** Updated `boards.json`.

#### FR-015: Admin Delete Sub-Board

- **ID:** FR-015
- **Description:** The administrator shall be able to delete a sub-board and all its associated threads and replies.
- **Priority:** Medium
- **Inputs:**
  - `board_id` (string): Board to delete.
  - `confirm` (boolean): Confirmation flag to prevent accidental deletion.
- **Processing:**
  1. Validate admin session.
  2. Verify `board_id` exists.
  3. Require explicit confirmation (checkbox or confirmation dialog).
  4. Recursively delete the board's directory: `data/boards/{board_id}/` and all contents.
  5. Remove the board's entry from `boards.json`.
  6. Write updated `boards.json`.
- **Outputs:** Board directory deleted; `boards.json` updated.

---

### 3.7 Administrator Panel

#### FR-016: Admin Dashboard

- **ID:** FR-016
- **Description:** After successful authentication, the administrator shall be presented with a dashboard providing an overview of all boards and their thread/reply counts.
- **Priority:** High
- **Inputs:** Valid admin session.
- **Processing:**
  1. Verify session authentication flag.
  2. Read `boards.json`.
  3. For each board, read its `threads.json` and compute total thread count and total reply count.
  4. Render a summary table.
- **Outputs:** HTML admin dashboard.

#### FR-017: Admin Delete Thread

- **ID:** FR-017
- **Description:** The administrator shall be able to delete an entire thread (OP and all replies) from a board.
- **Priority:** High
- **Inputs:**
  - `board_id` and `thread_id`.
  - Confirmation.
- **Processing:**
  1. Validate admin session and identifiers.
  2. Delete the thread JSON file: `data/boards/{board_id}/threads/{thread_id}.json`.
  3. Remove the thread's entry from `data/boards/{board_id}/threads.json`.
  4. Write updated index.
- **Outputs:** Thread deleted; index updated.

#### FR-018: Admin Delete Individual Reply

- **ID:** FR-018
- **Description:** The administrator shall be able to delete a single reply from within a thread without deleting the entire thread.
- **Priority:** Medium
- **Inputs:**
  - `board_id`, `thread_id`, and `post_id`.
  - Confirmation.
- **Processing:**
  1. Validate admin session and identifiers.
  2. Lock and read the thread JSON file.
  3. Find and remove the reply with matching `post_id` from the `replies` array.
  4. Decrement `reply_count`.
  5. Write updated thread JSON.
  6. Update the `threads.json` index entry's `reply_count`.
- **Outputs:** Reply removed; thread and index updated.

#### FR-019: Admin View Thread with IP Addresses

- **ID:** FR-019
- **Description:** When viewing a thread through the admin panel, the administrator shall see IP addresses displayed alongside each post.
- **Priority:** Medium
- **Inputs:** Admin session, `board_id`, `thread_id`.
- **Processing:**
  1. Validate admin session.
  2. Read the thread JSON file.
  3. Render the thread with an additional "IP" column showing the `ip` field for each post.
- **Outputs:** HTML thread view with IP data.

---

### 3.8 Admin Authentication (Salted Password Hashing)

#### FR-020: Admin Login

- **ID:** FR-020
- **Description:** The administrator shall be able to log in via a login form using a username and password. The password shall be verified against a salted hash stored in a flatfile.
- **Priority:** High
- **Inputs:**
  - `username` (string).
  - `password` (string).
- **Processing:**
  1. Validate HTTP method is `POST`.
  2. Read `data/admin.json`.
  3. Compare submitted `username` against the stored `username` using a timing-safe string comparison (`hash_equals`).
  4. If username matches, verify the password using PHP's `password_verify($password, $stored_hash)`.
  5. If verification succeeds:
     - Regenerate the session ID (`session_regenerate_id(true)`) to prevent session fixation.
     - Set `$_SESSION['admin_authenticated'] = true`.
     - Set `$_SESSION['admin_login_time'] = time()`.
     - Redirect to the admin dashboard.
  6. If verification fails:
     - Log the failed attempt with timestamp and IP to `data/admin_audit.log`.
     - Return a generic error message: "Invalid username or password."
     - Do not reveal whether the username or password was incorrect.
- **Outputs:**
  - On success: authenticated session, redirect to admin dashboard.
  - On failure: error message, audit log entry.

#### FR-021: Admin Password Hashing (Initial Setup)

- **ID:** FR-021
- **Description:** The system shall provide a mechanism to set or change the administrator password. The password shall be hashed using a strong, salted algorithm.
- **Priority:** High
- **Inputs:**
  - `current_password` (string, required for changes).
  - `new_password` (string, min 8 chars).
  - `new_password_confirm` (string, must match `new_password`).
- **Processing:**
  1. Validate admin session (for change) or check if `admin.json` exists (for initial setup).
  2. For initial setup (no existing `admin.json`): skip current password verification.
  3. For password change: verify `current_password` using `password_verify()`.
  4. Validate `new_password` is at least 8 characters.
  5. Validate `new_password` matches `new_password_confirm`.
  6. Hash the new password using `password_hash($new_password, PASSWORD_ARGON2ID)`.
  7. If `PASSWORD_ARGON2ID` is not available, fall back to `PASSWORD_BCRYPT` with cost factor 12.
  8. Write the updated `admin.json` containing `username` and `password_hash`.
  9. Invalidate any existing admin sessions (optional, for security).
- **Outputs:** Updated `admin.json` with new salted hash.

#### FR-022: Admin Logout

- **ID:** FR-022
- **Description:** The administrator shall be able to log out, destroying the current session.
- **Priority:** High
- **Inputs:** Logout action (GET or POST to `/admin/logout`).
- **Processing:**
  1. Clear all session variables (`$_SESSION = []`).
  2. If session cookies are used, delete the session cookie.
  3. Destroy the session (`session_destroy()`).
  4. Redirect to the board index.
- **Outputs:** Session terminated; redirect to home page.

#### FR-023: Session Timeout

- **ID:** FR-023
- **Description:** Admin sessions shall automatically expire after a period of inactivity.
- **Priority:** Medium
- **Inputs:** Session data with `admin_login_time`.
- **Processing:**
  1. On each admin page request, check `time() - $_SESSION['admin_login_time']`.
  2. If the difference exceeds 3600 seconds (1 hour), destroy the session and redirect to the login page with a "Session expired" message.
  3. Otherwise, update `$_SESSION['admin_login_time'] = time()` to extend the session.
- **Outputs:** Automatic logout after 1 hour of inactivity.

---

## 4. External Interface Requirements

### 4.1 User Interface (Web-Based)

The user-facing interface shall be a server-rendered HTML application with the following pages:

| Page | URL Pattern | Description |
|---|---|---|
| **Board Index** | `/` | Lists all sub-boards. |
| **Board View** | `/boards/{board_id}` | Lists threads in a board, paginated. |
| **Thread View** | `/boards/{board_id}/thread/{thread_id}` | Displays a full thread with reply form. |
| **New Thread Form** | `/boards/{board_id}/new` | Form to create a new thread (may be embedded in board view). |

**UI Requirements:**

- **UR-001:** All user-facing pages shall use semantic HTML5 and be readable without CSS or JavaScript.
- **UR-002:** All form inputs shall have corresponding `<label>` elements.
- **UR-003:** The reply form on the thread view shall be positioned below all existing replies.
- **UR-004:** Thread lists shall display, for each thread: subject, message excerpt (first 150 characters), reply count, and relative timestamp (e.g., "5 minutes ago").
- **UR-005:** All timestamps shall be displayed in the server's configured timezone, formatted as `YYYY-MM-DD HH:MM:SS` or as relative time.
- **UR-006:** Messages shall be rendered with newlines preserved (`nl2br` or equivalent CSS `white-space: pre-wrap`).
- **UR-007:** A consistent navigation bar or breadcrumb shall allow users to navigate back to the board index and thread list.
- **UR-008:** The UI shall not require JavaScript for core operations (posting, viewing, navigation).

### 4.2 Admin Interface

The admin interface shall be accessible at `/admin` and its sub-paths:

| Page | URL Pattern | Description |
|---|---|---|
| **Login** | `/admin/login` | Admin login form. |
| **Dashboard** | `/admin` | Overview of all boards. |
| **Board Management** | `/admin/boards` | Create, rename, delete boards. |
| **Board Moderation** | `/admin/boards/{board_id}` | View threads in a board with delete options. |
| **Thread Moderation** | `/admin/boards/{board_id}/thread/{thread_id}` | View thread with IPs, delete individual replies. |
| **Password Change** | `/admin/password` | Change admin password. |
| **Logout** | `/admin/logout` | Logout action. |

**Admin UI Requirements:**

- **AR-001:** All admin pages shall check for a valid admin session before rendering. Unauthenticated requests shall redirect to `/admin/login`.
- **AR-002:** Delete actions shall require explicit confirmation (checkbox or JavaScript `confirm()`).
- **AR-003:** The admin thread view shall display IP addresses in a distinct visual style (e.g., monospace font, subtle background) next to each post.
- **AR-004:** The admin dashboard shall display a summary table: Board Name, Thread Count, Reply Count, Last Activity.
- **AR-005:** The password change form shall require the current password, new password, and confirmation.

### 4.3 File System Interface (Flatfile Structure)

The application interacts with the file system through PHP's native file functions. The interface contract is as follows:

| Operation | PHP Functions Used | Locking |
|---|---|---|
| **Read a file** | `file_get_contents()`, `json_decode()` | Shared lock `LOCK_SH` for indexes; no lock for immutable thread reads |
| **Write/create a file** | `file_put_contents()`, `json_encode()` | Exclusive lock `LOCK_EX` |
| **Delete a file** | `unlink()` | Directory-level lock via a lock file |
| **Create a directory** | `mkdir()` with `recursive=true`, `chmod()` to `0755` | None (directories are created once) |
| **Delete a directory** | Recursive `unlink()` + `rmdir()` | Directory-level lock |
| **File locking** | `fopen()` + `flock()` + `fwrite()` + `fclose()` | `LOCK_EX` for writes, `LOCK_SH` for reads of mutable files |

All JSON files shall be written with `JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES` flags for readability and correct Unicode handling.

---

## 5. Non-Functional Requirements

### 5.1 Performance

| ID | Requirement | Metric |
|---|---|---|
| NFR-P01 | **Page load time.** The board index and thread list pages shall render in under 200ms on a standard server (excluding network latency). | Measured server-side with up to 500 threads in a board. |
| NFR-P02 | **Post submission time.** New thread and reply submissions shall complete (including file I/O) in under 500ms. | Measured end-to-end from POST to redirect. |
| NFR-P03 | **Concurrent writes.** The system shall handle at least 10 concurrent POST requests without data corruption, using file locking. | Tested with concurrent reply submissions to the same thread. |
| NFR-P04 | **Memory usage.** No single request shall consume more than 32 MB of memory. | PHP `memory_limit` set to 32M. |
| NFR-P05 | **File size limits.** No single JSON file shall exceed 512 KB. If a thread's JSON file approaches this limit, replies beyond the limit shall be rejected with an appropriate message. | Enforced in application logic before write. |

### 5.2 Security

| ID | Requirement | Details |
|---|---|---|
| NFR-S01 | **Password hashing.** Admin passwords shall be hashed using `PASSWORD_ARGON2ID` (preferred) or `PASSWORD_BCRYPT` (fallback) with a cost factor of at least 12. | PHP's `password_hash()` handles salting automatically. |
| NFR-S02 | **XSS Prevention.** All user-supplied content rendered in HTML shall be escaped using `htmlspecialchars($string, ENT_QUOTES, 'UTF-8')`. | Prevents stored and reflected XSS attacks. |
| NFR-S03 | **Input sanitization.** All input strings shall have NULL bytes stripped and be trimmed before processing. Messages shall be validated for length constraints. | Prevents poison NULL byte attacks and oversized inputs. |
| NFR-S04 | **File permissions.** The `data/` directory and its contents shall be owned by the web server user with permissions `0750` for directories and `0640` for files. The `data/` directory shall be outside the web-accessible document root. | Prevents direct HTTP access to data files. |
| NFR-S05 | **Session security.** Admin sessions shall use `session_regenerate_id(true)` upon login, `session.cookie_httponly = 1`, and `session.cookie_samesite = "Strict"`. | Mitigates session fixation, XSS-based session theft, and CSRF. |
| NFR-S06 | **CSRF protection.** All admin POST actions shall be protected by a CSRF token generated per session and validated on submission. | Prevents cross-site request forgery against admin actions. |
| NFR-S07 | **Path traversal prevention.** All `board_id` and `thread_id` parameters shall be validated against a whitelist pattern (`/^[a-zA-Z0-9_-]+$/` for board IDs; `/^[0-9]+\.[0-9]+\.[a-f0-9]+$/` for thread/post IDs). | Prevents directory traversal attacks. |
| NFR-S08 | **Rate limiting.** Posting from the same IP address shall be limited to 5 posts per 60 seconds. | Mitigates spam and denial-of-service via rapid posting. |
| NFR-S09 | **Error disclosure.** Production error messages shall not reveal file paths, stack traces, or internal logic. A generic error page shall be displayed. | Prevents information leakage. |
| NFR-S10 | **Content-Security-Policy.** The application shall serve a `Content-Security-Policy` HTTP header that restricts script sources to `'self'` and disallows inline scripts. | Defense-in-depth against XSS. |

### 5.3 Reliability

| ID | Requirement | Details |
|---|---|---|
| NFR-R01 | **Data integrity during concurrent access.** File locking (`flock`) shall ensure that concurrent writes to the same file do not result in data corruption or loss. | All write operations must acquire `LOCK_EX`. |
| NFR-R02 | **Atomic writes.** When updating a file, the system shall write to a temporary file first, then rename it over the target (`write-temp-and-rename` pattern) when possible, to prevent corruption from partial writes. | Applies to `threads.json` index files. |
| NFR-R03 | **Graceful degradation.** If a thread JSON file is corrupted (invalid JSON), the system shall skip that thread in listings and log an error, rather than crashing. | `json_decode()` error checking with `JSON_ERROR_NONE`. |
| NFR-R04 | **Backup recommendation.** The SRS recommends (but does not enforce) that the administrator periodically back up the `data/` directory. | Documented operational procedure. |

### 5.4 Maintainability

| ID | Requirement | Details |
|---|---|---|
| NFR-M01 | **Code organization.** PHP source files shall follow a clear separation of concerns: routing, business logic, data access, and presentation (templates). | See [Section 7.1](#71-directory-layout) for recommended structure. |
| NFR-M02 | **JSON readability.** All JSON data files shall be written with `JSON_PRETTY_PRINT` for human readability and debugging. | Facilitates manual inspection and recovery. |
| NFR-M03 | **Logging.** The system shall log errors and significant events (failed admin logins, file write errors) to a log file in `data/logs/`. | Rotating log file, max 1 MB. |
| NFR-M04 | **Configuration centralization.** All configurable values (board limits, rate limits, session timeout, file paths) shall be defined in a single configuration file (`config.php`). | Simplifies tuning and deployment. |
| NFR-M05 | **No external dependencies.** The application shall not require Composer packages or external libraries. Only built-in PHP functions shall be used. | Simplifies deployment and reduces supply-chain risk. |

### 5.5 Portability

| ID | Requirement | Details |
|---|---|---|
| NFR-P01 | **PHP 8.x compatibility.** The code shall be compatible with PHP 8.1, 8.2, and 8.3. | No deprecated functions from PHP 7.x. |
| NFR-P02 | **Web server portability.** The application shall work on both Apache (with `mod_rewrite`) and Nginx, using standard `.htaccess` and nginx configuration examples provided in the deployment guide. | Avoids server-specific PHP functions. |
| NFR-P03 | **Filesystem portability.** All file paths shall use forward slashes (`/`) and be constructed using relative paths from the application root. | Compatible with Linux and other Unix-like systems. |
| NFR-P04 | **No platform-specific extensions.** Only standard, bundled PHP extensions shall be used (`json`, `mbstring`, `filter`, `session`, `fileinfo`). | Ensures broad hosting compatibility. |

---

## 6. Data Requirements

### 6.1 Flatfile Directory Structure

The `data/` directory (located outside the web-accessible document root) shall have the following structure:

```
data/
├── admin.json                 # Admin credentials
├── admin_audit.log            # Admin login audit trail
├── boards.json                # Board index (list of all sub-boards)
├── boards/
│   ├── {board_id}/
│   │   ├── threads.json       # Thread index for this board
│   │   └── threads/
│   │       ├── {thread_id}.json   # Individual thread file
│   │       ├── {thread_id}.json
│   │       └── ...
│   ├── general/
│   │   ├── threads.json
│   │   └── threads/
│   │       └── ...
│   └── ...
├── ip_logs/
│   └── {YYYY-MM-DD}.log      # Daily IP address log
├── logs/
│   └── app.log               # Application error/event log
└── tmp/                       # Temporary files for atomic writes
```

### 6.2 Data Formats

#### 6.2.1 Board Index (`boards.json`)

```json
[
    {
        "board_id": "general",
        "name": "General Discussion",
        "description": "Talk about anything and everything.",
        "sort_order": 1,
        "max_threads": 100,
        "created_at": 1717700000
    },
    {
        "board_id": "technology",
        "name": "Technology",
        "description": "Discuss programming, hardware, and tech news.",
        "sort_order": 2,
        "max_threads": 100,
        "created_at": 1717700100
    }
]
```

**Field Descriptions:**

| Field | Type | Description |
|---|---|---|
| `board_id` | string | URL-safe slug, alphanumeric + hyphens, max 32 chars. Unique. |
| `name` | string | Human-readable board name, max 100 chars. |
| `description` | string | Short description, max 500 chars. |
| `sort_order` | integer | Display order (ascending). |
| `max_threads` | integer | Maximum threads before oldest is auto-deleted. 0 = unlimited. |
| `created_at` | integer | Unix timestamp of board creation. |

#### 6.2.2 Board Thread Index (`threads.json`)

Each board's `threads.json` is an array of thread metadata entries:

```json
[
    {
        "thread_id": "1717700123.456789.ab12cd34",
        "subject": "Welcome to the board!",
        "message_excerpt": "This is the first 150 characters of the original post message...",
        "poster_ip_hash": "sha256:abc123...",
        "created_at": 1717700123,
        "last_modified": 1717750000,
        "reply_count": 5
    },
    {
        "thread_id": "1717700500.123456.ef56gh78",
        "subject": "No Subject",
        "message_excerpt": "Another discussion thread...",
        "poster_ip_hash": "sha256:def456...",
        "created_at": 1717700500,
        "last_modified": 1717700500,
        "reply_count": 0
    }
]
```

**Field Descriptions:**

| Field | Type | Description |
|---|---|---|
| `thread_id` | string | Unique thread identifier. |
| `subject` | string | Thread subject (or "No Subject"). |
| `message_excerpt` | string | First 150 characters of the OP message, with newlines replaced by spaces. For display in thread listing. |
| `poster_ip_hash` | string | SHA-256 hash of the OP's IP address, for the index display. Full IP is stored only in the thread file. |
| `created_at` | integer | Unix timestamp of thread creation. |
| `last_modified` | integer | Unix timestamp of most recent reply (or creation time if no replies). Used for bump sorting. |
| `reply_count` | integer | Total number of replies. |

#### 6.2.3 Thread File (`{thread_id}.json`)

```json
{
    "thread_id": "1717700123.456789.ab12cd34",
    "board_id": "general",
    "subject": "Welcome to the board!",
    "created_at": 1717700123,
    "last_modified": 1717750000,
    "reply_count": 2,
    "op": {
        "post_id": "1717700123.456789.ab12cd34",
        "message": "Hello everyone! This is the first post on this board.\n\nFeel free to introduce yourselves.",
        "ip": "192.168.1.100",
        "timestamp": 1717700123
    },
    "replies": [
        {
            "post_id": "1717700400.111111.xy99zz00",
            "message": "Hi! Great to be here. Looking forward to discussions.",
            "ip": "2001:0db8:85a3:0000:0000:8a2e:0370:7334",
            "timestamp": 1717700400
        },
        {
            "post_id": "1717750000.222222.aa11bb22",
            "message": "Thanks for setting this up!",
            "ip": "10.0.0.55",
            "timestamp": 1717750000
        }
    ]
}
```

**Field Descriptions:**

| Field | Type | Description |
|---|---|---|
| `thread_id` | string | Thread identifier (same as OP's `post_id`). |
| `board_id` | string | Parent board slug. |
| `subject` | string | Thread subject. |
| `created_at` | integer | Creation timestamp. |
| `last_modified` | integer | Last reply timestamp. |
| `reply_count` | integer | Number of replies. |
| `op` | object | The original post. |
| `op.post_id` | string | Post identifier (equals `thread_id`). |
| `op.message` | string | The original message body (max 10000 chars). |
| `op.ip` | string | The poster's IP address (IPv4 or IPv6). |
| `op.timestamp` | integer | Unix timestamp of posting. |
| `replies` | array | Ordered list of reply objects (chronological). |
| `replies[].post_id` | string | Unique post identifier. |
| `replies[].message` | string | Reply body (max 10000 chars). |
| `replies[].ip` | string | The reply poster's IP address. |
| `replies[].timestamp` | integer | Unix timestamp of the reply. |

### 6.3 Admin Credentials Storage Format

**File:** `data/admin.json`

```json
{
    "username": "admin",
    "password_hash": "$argon2id$v=19$m=65536,t=4,p=3$c29tZXNhbHR2YWx1ZQ$fullhashvaluehere...",
    "created_at": 1717700000,
    "last_password_change": 1717700000
}
```

**Field Descriptions:**

| Field | Type | Description |
|---|---|---|
| `username` | string | Admin username (alphanumeric, case-sensitive). |
| `password_hash` | string | Full password hash as returned by `password_hash()`. Includes algorithm identifier, cost parameters, salt, and hash. |
| `created_at` | integer | Unix timestamp of admin account creation. |
| `last_password_change` | integer | Unix timestamp of most recent password change. |

**Initial Setup:** If `data/admin.json` does not exist, the application shall display a setup form (accessible once) prompting for a username and password. After setup, the file is created and the setup route is disabled.

### 6.4 IP Address Storage Format

**File:** `data/ip_logs/{YYYY-MM-DD}.log`

Each line is a JSON object representing one post action:

```json
{"timestamp":1717700123,"board_id":"general","thread_id":"1717700123.456789.ab12cd34","post_id":"1717700123.456789.ab12cd34","ip":"192.168.1.100","action":"new_thread"}
{"timestamp":1717700400,"board_id":"general","thread_id":"1717700123.456789.ab12cd34","post_id":"1717700400.111111.xy99zz00","ip":"2001:0db8:85a3:0000:0000:8a2e:0370:7334","action":"reply"}
```

**Field Descriptions:**

| Field | Type | Description |
|---|---|---|
| `timestamp` | integer | Unix timestamp of the post. |
| `board_id` | string | Board where the post was made. |
| `thread_id` | string | Thread identifier. |
| `post_id` | string | Post identifier. |
| `ip` | string | Raw IP address (IPv4 or IPv6). |
| `action` | string | Either `"new_thread"` or `"reply"`. |

**Additional Notes:**

- A new log file is created for each calendar day (UTC).
- This log provides a secondary, append-only record of IP-to-post mappings for moderation purposes.
- The IP address is also stored inline within each thread JSON file (see [Section 6.2.3](#623-thread-file-thread_idjson)).

---

## 7. Technical Architecture Overview

### 7.1 Directory Layout

The recommended application source tree:

```
text-board/
├── public/                         # Document root (web-accessible)
│   ├── index.php                   # Front controller / router
│   ├── .htaccess                   # Apache URL rewriting rules
│   ├── css/
│   │   └── style.css               # Application stylesheet
│   └── js/
│       └── admin.js                # Optional admin UI enhancements
├── src/                            # PHP source code (outside document root)
│   ├── config.php                  # Centralized configuration
│   ├── Router.php                  # Simple URL router
│   ├── BoardController.php         # Board listing and thread display
│   ├── PostController.php          # Thread creation and reply handling
│   ├── AdminController.php         # Admin dashboard and management
│   ├── AuthController.php          # Login, logout, password change
│   ├── FlatfileStore.php           # File I/O abstraction (read/write/lock)
│   ├── IpLogger.php                # IP address logging
│   ├── Validator.php               # Input validation and sanitization
│   ├── Security.php                # CSRF tokens, escaping helpers
│   ├── Template.php                # Simple template renderer
│   └── Helpers.php                 # Timestamp formatting, utility functions
├── templates/                      # HTML templates
│   ├── layout.php                  # Base layout (header, footer, nav)
│   ├── board_index.php             # Home page — list of boards
│   ├── thread_list.php             # Board view — list of threads
│   ├── thread_view.php             # Thread view — OP + replies + reply form
│   ├── admin/
│   │   ├── login.php               # Admin login form
│   │   ├── dashboard.php           # Admin dashboard
│   │   ├── board_manage.php        # Create/rename/delete boards
│   │   ├── board_moderate.php      # Moderate threads in a board
│   │   ├── thread_moderate.php     # Moderate replies in a thread
│   │   └── password_change.php     # Password change form
│   └── errors/
│       ├── 400.php                 # Bad request
│       ├── 403.php                 # Forbidden
│       ├── 404.php                 # Not found
│       └── 500.php                 # Internal server error
├── data/                           # Flatfile storage (outside document root)
│   └── (see Section 6.1 for structure)
├── SRS.md                          # This document
└── README.md                       # Deployment and usage instructions
```

**Routing Strategy:**

The `public/index.php` front controller handles all requests. URL rewriting (Apache `mod_rewrite` or Nginx `try_files`) routes all non-file requests to `index.php`. The `Router.php` class parses `$_SERVER['REQUEST_URI']` and dispatches to the appropriate controller method.

**URL Routing Table:**

| HTTP Method | URL Pattern | Controller Method |
|---|---|---|
| `GET` | `/` | `BoardController::index()` |
| `GET` | `/boards/{board_id}` | `BoardController::showBoard($board_id)` |
| `GET` | `/boards/{board_id}/thread/{thread_id}` | `BoardController::showThread($board_id, $thread_id)` |
| `POST` | `/boards/{board_id}/new` | `PostController::createThread($board_id)` |
| `POST` | `/boards/{board_id}/thread/{thread_id}/reply` | `PostController::createReply($board_id, $thread_id)` |
| `GET` | `/admin/login` | `AuthController::loginForm()` |
| `POST` | `/admin/login` | `AuthController::login()` |
| `GET` | `/admin/logout` | `AuthController::logout()` |
| `GET` | `/admin` | `AdminController::dashboard()` |
| `GET` | `/admin/boards` | `AdminController::manageBoards()` |
| `POST` | `/admin/boards/create` | `AdminController::createBoard()` |
| `POST` | `/admin/boards/{board_id}/rename` | `AdminController::renameBoard($board_id)` |
| `POST` | `/admin/boards/{board_id}/delete` | `AdminController::deleteBoard($board_id)` |
| `GET` | `/admin/boards/{board_id}` | `AdminController::moderateBoard($board_id)` |
| `GET` | `/admin/boards/{board_id}/thread/{thread_id}` | `AdminController::moderateThread($board_id, $thread_id)` |
| `POST` | `/admin/boards/{board_id}/thread/{thread_id}/delete` | `AdminController::deleteThread($board_id, $thread_id)` |
| `POST` | `/admin/boards/{board_id}/thread/{thread_id}/reply/{post_id}/delete` | `AdminController::deleteReply($board_id, $thread_id, $post_id)` |
| `GET` | `/admin/password` | `AuthController::passwordChangeForm()` |
| `POST` | `/admin/password` | `AuthController::passwordChange()` |
| `GET` | `/setup` | `AuthController::setupForm()` (only if `admin.json` does not exist) |
| `POST` | `/setup` | `AuthController::setup()` (only if `admin.json` does not exist) |

### 7.2 File Naming Conventions

| Entity | Naming Pattern | Example | Notes |
|---|---|---|---|
| **Board ID** | `/^[a-zA-Z0-9][a-zA-Z0-9_-]{0,30}[a-zA-Z0-9]$/` | `general`, `tech-news`, `board_42` | 1-32 chars, alphanumeric + hyphens/underscores, cannot start or end with special chars. |
| **Thread ID** | `{unix_sec}.{microsec}.{random_hex}` | `1717700123.456789.ab12cd34` | Generated by `microtime(true)` formatted + `bin2hex(random_bytes(4))`. |
| **Post ID** | Same pattern as Thread ID. | `1717700400.111111.xy99zz00` | OP's post_id == thread_id. All others are unique. |
| **Thread file** | `{thread_id}.json` | `1717700123.456789.ab12cd34.json` | Stored in `data/boards/{board_id}/threads/`. |
| **IP log file** | `{YYYY-MM-DD}.log` | `2026-06-07.log` | One file per UTC day. Stored in `data/ip_logs/`. |

### 7.3 Data Flow for Key Operations

#### 7.3.1 Creating a New Thread

```
User Browser                    PHP Application                    Filesystem
    |                                |                                |
    |  POST /boards/general/new      |                                |
    |  (message, subject)            |                                |
    |------------------------------->|                                |
    |                                |                                |
    |                                | 1. Validate inputs              |
    |                                | 2. Extract & validate IP        |
    |                                | 3. Generate thread_id           |
    |                                |                                |
    |                                | 4. Acquire LOCK_EX on           |
    |                                |    threads.json                 |
    |                                |------------------------------->|
    |                                |    (open + flock)               |
    |                                |                                |
    |                                | 5. Read + decode threads.json   |
    |                                |<-------------------------------|
    |                                |                                |
    |                                | 6. Append thread metadata       |
    |                                | 7. Write updated threads.json   |
    |                                |------------------------------->|
    |                                |    (file_put_contents)          |
    |                                |                                |
    |                                | 8. Release lock                 |
    |                                |                                |
    |                                | 9. Create thread JSON file      |
    |                                |------------------------------->|
    |                                |    new thread_id.json           |
    |                                |                                |
    |                                | 10. Append to IP log            |
    |                                |------------------------------->|
    |                                |    ip_logs/YYYY-MM-DD.log       |
    |                                |                                |
    |  303 Redirect                  |                                |
    |  /boards/general/thread/{id}   |                                |
    |<-------------------------------|                                |
    |                                |                                |
    |  GET /boards/general/thread/{id}|                               |
    |------------------------------->|                                |
    |                                | Read thread JSON               |
    |                                |------------------------------->|
    |                                |<-------------------------------|
    |  HTML thread view              |                                |
    |<-------------------------------|                                |
```

#### 7.3.2 Replying to a Thread

```
User Browser                    PHP Application                    Filesystem
    |                                |                                |
    |  POST .../thread/{id}/reply    |                                |
    |  (message)                     |                                |
    |------------------------------->|                                |
    |                                |                                |
    |                                | 1. Validate inputs & IP         |
    |                                | 2. Generate post_id             |
    |                                |                                |
    |                                | 3. Acquire LOCK_EX on           |
    |                                |    thread_id.json               |
    |                                |------------------------------->|
    |                                |                                |
    |                                | 4. Read + decode thread JSON    |
    |                                |<-------------------------------|
    |                                |                                |
    |                                | 5. Append reply to replies[]    |
    |                                | 6. Update last_modified,        |
    |                                |    reply_count                  |
    |                                | 7. Write updated thread JSON    |
    |                                |------------------------------->|
    |                                |                                |
    |                                | 8. Release lock                 |
    |                                |                                |
    |                                | 9. Acquire LOCK_EX on           |
    |                                |    threads.json                 |
    |                                |------------------------------->|
    |                                |                                |
    |                                | 10. Update thread metadata in   |
    |                                |     threads.json (last_modified,|
    |                                |     reply_count)                |
    |                                | 11. Write updated threads.json  |
    |                                |------------------------------->|
    |                                |                                |
    |                                | 12. Release lock                |
    |                                |                                |
    |                                | 13. Append to IP log            |
    |                                |------------------------------->|
    |                                |                                |
    |  303 Redirect to thread view   |                                |
    |  (with #post-{post_id} anchor) |                                |
    |<-------------------------------|                                |
```

#### 7.3.3 Admin Authentication Flow

```
Admin Browser                   PHP Application                    Filesystem
    |                                |                                |
    |  GET /admin/login              |                                |
    |------------------------------->|                                |
    |  HTML login form               |                                |
    |<-------------------------------|                                |
    |                                |                                |
    |  POST /admin/login             |                                |
    |  (username, password)          |                                |
    |------------------------------->|                                |
    |                                |                                |
    |                                | 1. Read admin.json             |
    |                                |------------------------------->|
    |                                |<-------------------------------|
    |                                |                                |
    |                                | 2. hash_equals(username,       |
    |                                |    stored_username)            |
    |                                | 3. password_verify(password,   |
    |                                |    stored_hash)                |
    |                                |                                |
    |                                | [If success]                   |
    |                                | 4. session_regenerate_id()     |
    |                                | 5. Set session auth flags      |
    |                                |                                |
    |  303 Redirect to /admin        |                                |
    |<-------------------------------|                                |
    |                                |                                |
    |                                | [If failure]                   |
    |                                | 6. Append to admin_audit.log   |
    |                                |------------------------------->|
    |                                | 7. Return 401 + error message  |
    |  HTML login form with error    |                                |
    |<-------------------------------|                                |
```

---

## Appendix A: Error Handling Strategy

| Scenario | HTTP Status | User-Facing Message | Log Action |
|---|---|---|---|
| Board not found | 404 | "Board not found." | Log WARNING with requested board_id. |
| Thread not found | 404 | "Thread not found." | Log WARNING with requested thread_id. |
| Invalid input (missing message) | 400 | "A message is required." | None. |
| Invalid input (message too long) | 400 | "Message exceeds maximum length of 10,000 characters." | None. |
| Rate limit exceeded | 429 | "You are posting too quickly. Please wait before posting again." | Log NOTICE with IP. |
| Thread file too large | 413 | "This thread has reached the maximum number of replies." | Log WARNING. |
| File write error (permissions) | 500 | "An internal error occurred. Please try again later." | Log ERROR with details. |
| Corrupt JSON file | 500 | "An internal error occurred." | Log ERROR with file path. |
| Unauthenticated admin access | 302 | Redirect to `/admin/login`. | None. |
| Invalid admin credentials | 401 | "Invalid username or password." | Log WARNING to `admin_audit.log`. |
| CSRF token mismatch | 403 | "Invalid security token. Please try again." | Log WARNING. |

---

## Appendix B: Configuration Reference (`config.php`)

```php
<?php
return [
    // Paths (relative to project root)
    'data_dir'       => __DIR__ . '/../data',
    'boards_dir'     => __DIR__ . '/../data/boards',
    'ip_logs_dir'    => __DIR__ . '/../data/ip_logs',
    'app_log_dir'    => __DIR__ . '/../data/logs',

    // Board defaults
    'default_max_threads' => 100,
    'threads_per_page'    => 20,

    // Post limits
    'max_message_length'  => 10000,
    'max_subject_length'  => 200,
    'max_thread_file_size' => 524288, // 512 KB

    // Rate limiting
    'rate_limit_max_posts' => 5,
    'rate_limit_window'    => 60,     // seconds

    // Session
    'session_timeout'      => 3600,   // 1 hour

    // Password hashing
    'password_algo'        => PASSWORD_ARGON2ID,
    'password_options'     => [
        'memory_cost' => 65536,
        'time_cost'   => 4,
        'threads'     => 3,
    ],

    // Timezone
    'timezone'             => 'UTC',
];
```

---

## Appendix C: Functional Requirements Traceability Matrix

| ID | Requirement | Priority | Covered by FR |
|---|---|---|---|
| REQ-01 | Anonymous posting (new thread) | High | FR-001 |
| REQ-02 | Anonymous replying | High | FR-002 |
| REQ-03 | Thread display | High | FR-003, FR-004 |
| REQ-04 | Bump-based thread ranking | High | FR-008, FR-009 |
| REQ-05 | IPv4 and IPv6 recording | High | FR-010, FR-011 |
| REQ-06 | Sub-board categories | High | FR-012, FR-013, FR-014, FR-015 |
| REQ-07 | Admin authentication (salted hash) | High | FR-020, FR-021, FR-022, FR-023 |
| REQ-08 | Admin panel | High | FR-016, FR-017, FR-018, FR-019 |
| REQ-09 | Flatfile storage (no database) | Constraint | C-002, Section 6 |
| REQ-10 | PHP 8.x | Constraint | C-001 |
| REQ-11 | IP-only recording (no PII) | Constraint | C-006 |

---

*End of Software Requirements Specification*
