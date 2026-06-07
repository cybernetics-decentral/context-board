# Context Board — Admin Usage Guide

This guide covers all administrative operations for the Context Board application.  
The admin panel is accessible at [`/admin`](/admin) and requires no JavaScript — all destructive actions are confirmed via HTML checkboxes.

---

## Table of Contents

1. [Initial Setup](#1-initial-setup)
2. [Login / Logout](#2-login--logout)
3. [Dashboard](#3-dashboard)
4. [Managing Boards](#4-managing-boards)
5. [Moderating Threads](#5-moderating-threads)
6. [Moderating Replies](#6-moderating-replies)
7. [Changing Password](#7-changing-password)
8. [Security Notes](#8-security-notes)

---

## 1. Initial Setup

The setup process creates the first (and only) admin account. It is available **only** when no admin account exists yet — specifically, when the file `data/admin.json` is absent.

### Step-by-step

1. Navigate to **`/setup`** in your browser.
2. Fill in the form:
   - **Username** — any string (required).
   - **Password** — must be at least **8 characters**.
   - **Confirm Password** — must match the password field exactly.
3. Click **Submit**.

If successful, you are redirected to [`/admin/login`](#2-login--logout). The `data/admin.json` file is created with your credentials hashed using Argon2id (or bcrypt as a fallback).

> **Note:** Once `admin.json` exists, visiting `/setup` returns a **404 Not Found** — the setup route is permanently disabled.

---

## 2. Login / Logout

### Login

1. Go to **`/admin/login`**.
2. Enter the **username** and **password** you chose during setup.
3. Click **Login**.

**Behaviour:**

- On success: redirected to the [Dashboard](#3-dashboard) (`/admin`).
- On failure: redirected back to `/admin/login?error=1` with the generic message **"Invalid username or password."** — no distinction is made between wrong username and wrong password, to prevent user enumeration.
- Every login attempt (success or failure) is written to the **audit log** at `data/admin_audit.log` with timestamp, IP address, username, and result.

### Session Timeout

Admin sessions expire after **1 hour** of inactivity. Upon expiration, the session is destroyed and you are redirected to `/admin/login?expired=1` with the message **"Session expired. Please log in again."**

Activity on any admin page resets the timeout clock.

### Logout

Click the **Logout** link visible in the navigation bar of every admin page, or browse directly to **`/admin/logout`**. The session is fully destroyed (including the session cookie), and you are redirected to the public homepage `/`.

---

## 3. Dashboard

The Dashboard at **`/admin`** provides an overview of every board in the system.

| Column          | Description                                                       |
|-----------------|-------------------------------------------------------------------|
| **Board**       | The board's display name, linked to the [thread moderation](#5-moderating-threads) view for that board. |
| **Threads**     | Total number of threads in the board.                             |
| **Replies**     | Total number of replies across all threads in the board.          |
| **Last Activity** | Human-readable relative time of the most recent post (e.g. "5 minutes ago") or **Never** if the board is empty. |

If no boards exist yet, a prompt with a link to [Manage Boards](#4-managing-boards) is displayed.

### Navigation

From the Dashboard you can reach:

- [`/admin/boards`](#4-managing-boards) — Manage Boards
- [`/admin/password`](#7-changing-password) — Change Password
- [`/admin/logout`](#2-login--logout) — Logout
- [`/`](#) — View Site (public homepage)

---

## 4. Managing Boards

Board management is at **`/admin/boards`**.

### Creating a Board

Use the **"Create New Board"** form at the top of the page.

| Field           | Requirement                                                                    |
|-----------------|--------------------------------------------------------------------------------|
| **Board ID**    | Required. URL slug for the board. Must match: `[a-zA-Z0-9][a-zA-Z0-9_-]{0,30}[a-zA-Z0-9]` — i.e. **1–32 characters**, only alphanumeric (`a-z`, `A-Z`, `0-9`), hyphens (`-`), and underscores (`_`). Must **start and end** with an alphanumeric character. Examples: `general`, `tech-news`, `dev_log`. |
| **Display Name** | Optional. If left blank, the Board ID is used as the display name. Max 100 characters. |
| **Description**  | Optional. A short description of the board's purpose. Max 500 characters.      |

Click **Create Board**. If successful, you are redirected back to `/admin/boards` and the new board appears in the list. The board's data directory and empty thread index are created automatically.

> **Error cases:** If the board ID is invalid or already exists, an error message is returned (HTTP 400).

### Renaming a Board

Each board row in the **"Existing Boards"** table has a **Rename** form:

1. Edit the **name** field inline.
2. Click **Rename**.

The board's display name and/or description is updated immediately. The Board ID (URL slug) cannot be changed — renaming affects only the displayed metadata.

### Deleting a Board

Each board row has a **Delete** form with a confirmation checkbox.

1. **Check the "Confirm" checkbox.**
2. Click **Delete**.

The entire board directory (including all threads and replies) is removed from disk, and the board is removed from the index. This action is **irreversible**. If the confirmation checkbox is not ticked, the request is rejected with an HTTP 400 error.

---

## 5. Moderating Threads

### Viewing Threads in a Board

From the [Dashboard](#3-dashboard), click a board name, or navigate to **`/admin/boards/{board_id}`**.  
This page lists all threads in the selected board, showing each thread's subject and metadata.

### Viewing a Thread for Moderation

Click into a specific thread at **`/admin/boards/{board_id}/thread/{thread_id}`**.

This page displays:

- The **OP (original post)** with post number, timestamp, and the poster's IP address.
- All **replies** nested in a threaded tree, each with post number, timestamp, IP address, and message content.
- A **Delete Reply** form under each reply (see [Section 6](#6-moderating-replies)).

### Deleting an Entire Thread

At the top or bottom of the thread moderation page, a **Delete Thread** form is provided.

1. **Check the "Confirm" checkbox.**
2. Click **Delete Thread**.

The thread file (`data/boards/{board_id}/threads/{thread_id}.json`) is deleted, and the thread is removed from the board's thread index. This action is **irreversible** and removes the OP and all replies within that thread. Without the confirmation checkbox, the request is rejected.

---

## 6. Moderating Replies

### IP Address Visibility

In the admin thread view, every post (OP and replies) shows the poster's **IP address** in the post metadata line:

```
#1  2 minutes ago  IP: 192.168.1.42
```

IP addresses are styled for readability in the admin interface. On public-facing pages, IP addresses are **never** displayed — only the admin can see them.

### Deleting a Single Reply

Each reply in the thread view has a **Delete Reply** form:

1. **Check the "Confirm" checkbox** (labelled "Confirm delete (cascades to nested replies)").
2. Click **Delete Reply**.

### Cascading Deletion

When you delete a reply that has nested child replies, **all descendant replies are also deleted**. This is called cascading deletion and works as follows:

- The system collects the target post ID and then performs a **breadth-first traversal** to find all direct and indirect children.
- Every post in the resulting descendant set is removed from the thread.
- The thread's `reply_count` and `bump_score`/`bump_recency` metadata are recalculated automatically.
- The board's thread index is updated to reflect the new reply count.

This ensures that deleting a parent post does not leave orphaned child replies in the thread.

> **Note:** If the confirmation checkbox is not ticked, the deletion is rejected with an HTTP 400 error and no changes are made.

---

## 7. Changing Password

The password change page is at **`/admin/password`**.

### Step-by-step

1. Navigate to **`/admin/password`** (requires active admin session).
2. Fill in the form:
   - **Current Password** — your existing password (required for verification).
   - **New Password** — must be at least **8 characters**.
   - **Confirm New Password** — must match the New Password field exactly.
3. Click **Change Password**.

### Behaviour

- If the current password is **incorrect**: HTTP 400 with message "Current password is incorrect."
- If the new password is **shorter than 8 characters**: HTTP 400 with message "Password must be at least 8 characters."
- If the two new password fields **do not match**: HTTP 400 with message "Passwords do not match."
- On success: `data/admin.json` is updated with the new password hash and a `last_password_change` timestamp. You are redirected to the [Dashboard](#3-dashboard).

Passwords are hashed using **Argon2id** (with memory cost 65536, time cost 4) or **bcrypt** (cost 12) as a fallback on systems that do not support Argon2id.

---

## 8. Security Notes

### Session Security

- Admin sessions use **HttpOnly** cookies (JavaScript cannot read them).
- Cookies are set with **SameSite=Strict** to prevent cross-site request forgery from external sites.
- If the site is served over HTTPS, session cookies are also marked **Secure**.
- Session IDs are regenerated upon successful login (`session_regenerate_id(true)`) to prevent session fixation.
- Session strict mode is enabled (`session.use_strict_mode=1`).

### CSRF Protection

All admin forms that perform state-changing operations (login, logout, create/rename/delete board, delete thread, delete reply, change password) include a hidden **CSRF token** field generated via [`Security::getCsrfTokenField()`](src/Security.php:21). The token is:

- A 64-character hex string (32 random bytes).
- Stored in `$_SESSION['csrf_token']` and validated on submission using constant-time comparison (`hash_equals`).
- If the token is missing or invalid, the server returns a **403 Forbidden** with the message "Invalid security token."

### Password Hashing

All passwords are hashed before storage using PHP's `password_hash()`:

| Algorithm    | Conditions                                                    |
|--------------|---------------------------------------------------------------|
| **Argon2id** | Used when the `PASSWORD_ARGON2ID` constant is defined (PHP 7.3+). Memory cost: 65536, Time cost: 4. |
| **bcrypt**   | Fallback when Argon2id is unavailable. Cost factor: 12.        |

Password verification uses `password_verify()`, which is timing-attack-safe.

### Security Headers

All admin responses include:

- `Content-Security-Policy: default-src 'self'; script-src 'none'; style-src 'self' 'unsafe-inline'`
- `X-Content-Type-Options: nosniff`
- `X-Frame-Options: DENY`
- `Referrer-Policy: same-origin`

### Auto-Refresh

The public board and thread pages include an automatic refresh mechanism (every 30 seconds) so users see new posts without manual reloading. **Admin pages do not auto-refresh** — this prevents accidental loss of form state or unintended duplicate submissions during moderation.

---

## Quick Reference — Admin URL Map

| URL                                                         | Method | Purpose                           |
|-------------------------------------------------------------|--------|-----------------------------------|
| `/setup`                                                    | GET    | Initial setup form                |
| `/setup`                                                    | POST   | Create admin account              |
| `/admin/login`                                              | GET    | Login form                        |
| `/admin/login`                                              | POST   | Submit login credentials          |
| `/admin/logout`                                             | GET    | Destroy session and logout        |
| `/admin`                                                    | GET    | Dashboard                         |
| `/admin/boards`                                             | GET    | Board management (list/create)    |
| `/admin/boards/create`                                      | POST   | Create a new board                |
| `/admin/boards/{board_id}/rename`                           | POST   | Rename a board                    |
| `/admin/boards/{board_id}/delete`                           | POST   | Delete a board                    |
| `/admin/boards/{board_id}`                                  | GET    | List threads in a board (moderation) |
| `/admin/boards/{board_id}/thread/{thread_id}`               | GET    | View thread with replies & IPs    |
| `/admin/boards/{board_id}/thread/{thread_id}/delete`        | POST   | Delete an entire thread           |
| `/admin/boards/{board_id}/thread/{thread_id}/reply/{post_id}/delete` | POST | Delete a reply (cascading) |
| `/admin/password`                                           | GET    | Password change form              |
| `/admin/password`                                           | POST   | Submit password change            |

---

© 2026 Abhishek Kumar <mr.kumar.abhishek@outlook.in> — Licensed under [CC BY-SA 4.0](../docs/LICENSE.md)
