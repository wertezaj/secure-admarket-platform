# AdMarket — Secure OOH Advertising Platform

A peer-to-peer marketplace for renting physical billboard and outdoor advertising space (OOH). 

I built this application from scratch in vanilla PHP 8 and MariaDB without using high-level frameworks (like Laravel or Symfony). My primary focus was implementing defensive programming patterns, secure authentication, safe file handling, and proper access control directly at the code and database level.

---

## Why I Built It This Way

Modern frameworks handle security abstractions automatically. While great for production speed, relying solely on them makes it easy to overlook how vulnerabilities actually work under the hood. 

By building this without a framework, I wanted to manually handle critical web application risks—such as timing attacks, IDOR, malicious file uploads, and session hijacking—and understand the exact mitigations needed to prevent them.

---

## Security Features & Implementations

### 1. Authentication & Anti-Enumeration
* **Constant-Time Verification (Preventing User Enumeration):** In typical login endpoints, attempting to log in with a non-existent email returns faster than with a valid email because `password_verify` (Bcrypt) isn't executed. To stop attackers from enumerating valid registered accounts via response-time differences, the login controller runs `password_verify()` against a fixed dummy hash whenever an email isn't found.
* **Bcrypt 72-Byte Boundary Handling:** Bcrypt silently truncates passwords after 72 bytes. To prevent truncation collisions or CPU-exhaustion denial of service with oversized strings, input length is strictly enforced between 8 and 72 bytes before hashing.
* **Brute-Force Lockout:** Tracks consecutive failed attempts in the database. If a user fails 5 times, the account is locked for 15 minutes (`lockout_until = DATE_ADD(NOW(), INTERVAL 15 MINUTE)`).
* **Hashed Email Verification (OTP):** 6-digit confirmation codes are generated using cryptographically secure random numbers (`random_int`) and stored only as Bcrypt hashes in the database with a 15-minute expiration and a 5-attempt limit.

### 2. Password Reset (Selector + Validator Model)
Storing plaintext tokens in a database or querying tokens directly by hash creates security risks. If the database leaks or timing attacks are possible on database lookups, tokens can be compromised.
* **Split Token Architecture:** The reset link contains two values: a `selector` and a `validator`.
  * The **selector** is a random 16-character hex string used as a database lookup key.
  * The **validator** is a 32-byte secure random string sent only in the email. The database stores only its Bcrypt hash.
* **Result:** Even with full read-only access to a database dump, an attacker cannot generate valid reset links because the raw validator was never stored.
* **Timing Jitter:** For non-existent accounts requesting a password reset, random delays (`usleep(500000, 900000)`) are introduced so response times match the time taken by email generation and hashing.

### 3. Image Upload Pipeline & Sanitization
Allowing users to upload images for billboard listings introduces severe risks (Web Shells, ImageTragick exploits, SVG-based Stored XSS, or storage exhaustion).
* **Magic Bytes Inspection:** The application ignores the client-provided `Content-Type` header and extension. It inspects the actual binary header using PHP's `finfo_file` against an explicit whitelist (`JPEG`, `PNG`, `WEBP`).
* **No Vector Graphics (SVG):** SVGs are strictly blocked to eliminate embedded JavaScript and XML-based XSS vectors.
* **Pixel Flood / Decompression Bomb Guard:** Before processing, dimensions are checked via `getimagesize()`. Files exceeding $4096 \times 4096$ pixels are rejected before allocating memory in the GD library.
* **Re-encoding to WebP:** Every uploaded image is loaded into memory, resized if wider than 1920px, and re-encoded from scratch into a `.webp` file with a randomized name (`bin2hex(random_bytes(16))`). This strips all EXIF metadata, IPTC data, and potential PHP polyglot payloads embedded in the image.
* **Path Traversal Protection on Deletion:** When old images are cleaned up, file paths are validated using `realpath()` and verified with `str_starts_with()` to guarantee that deletions can never touch files outside the `uploads/` directory.

### 4. Access Control & Authorization (IDOR Prevention)
* **Contextual SQL Ownership:** Listing edits and deletions don't rely only on client-provided IDs. The current user ID from the session is bound directly into the query:
  ```sql
  UPDATE markers SET name = ?, description = ? WHERE id = ? AND user_id = ?
  ```
* **Image Manipulation Checks:** When updating an existing listing, the user cannot inject arbitrary image paths from other listings. The backend strictly cross-checks requested paths against existing database ownership.
* **Role-Based Access Control (RBAC):** Three privilege levels exist: Standard User (0), Moderator/Admin (1), and SuperAdmin (2). Administrative actions include safeguards preventing lower admins from modifying or banning SuperAdmin accounts.

### 5. Session Management & Browser Security
* **Session Hardening:** Configured prior to `session_start()`:
  * `session.use_strict_mode = 1` (rejects uninitialized IDs).
  * `session.use_only_cookies = 1`.
  * Cookie flags: `HttpOnly`, `SameSite=Lax`, and `Secure` when HTTPS is detected.
  * Automatic session destruction after 30 minutes of inactivity.
  * `session_regenerate_id(true)` called upon every authentication state change (login, verification).
* **Live Revocation:** Active sessions verify the user's status in the database on requests. If an administrator bans an account, the user is logged out immediately rather than waiting for the session cookie to expire.
* **CSRF Mitigation:** State-changing requests (`POST`) require a 256-bit random CSRF token validated using constant-time string comparison (`hash_equals`).
* **Security Headers:** Enforces Content-Security-Policy (CSP), Strict-Transport-Security (HSTS), `X-Frame-Options: SAMEORIGIN`, and `X-Content-Type-Options: nosniff`.

---

## Tech Stack

* **Backend:** PHP 8.1+ (OOP, Native MySQLi with Prepared Statements)
* **Database:** MariaDB / MySQL (InnoDB, UTF-8 MB4)
* **Libraries:** PHPMailer (SMTPS/TLS), PHP GD (image conversion), vlucas/phpdotenv
* **Frontend:** Vanilla JS, jQuery, Leaflet.js (OpenStreetMap), SortableJS

---

## Local Setup

### Requirements
* PHP 8.1 or higher with extensions: `mysqli`, `gd`, `fileinfo`, `mbstring`, `openssl`
* MariaDB / MySQL
* Composer

### Steps

1. **Clone the repository:**
   ```bash
   git clone https://github.com/your-username/secure-admarket-platform.git
   cd secure-admarket-platform
   ```

2. **Install dependencies:**
   ```bash
   composer install
   ```

3. **Configure environment:**
   Create a `.env` file in the project root:
   ```env
   APP_ENV=development
   APP_URL=http://localhost:8000

   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_NAME=reklama_local
   DB_USER=your_user
   DB_PASSWORD=your_password

   SMTP_HOST=sandbox.smtp.mailtrap.io
   SMTP_PORT=2525
   SMTP_USER=your_smtp_user
   SMTP_PASSWORD=your_smtp_password
   SMTP_FROM=security@admarket.local
   SMTP_FROM_NAME="AdMarket"
   ```

4. **Import database:**
   ```bash
   mysql -u your_user -p reklama_local < struktura_bazy.sql
   ```

5. **Ensure upload directory exists:**
   ```bash
   mkdir -p uploads assets/img
   chmod 755 uploads assets/img
   ```

6. **Start local dev server:**
   ```bash
   php -S 127.0.0.1:8000
   ```

---

## License & Intellectual Property

Copyright (c) 2026. All Rights Reserved.  
This source code is made publicly visible strictly for educational review and portfolio evaluation purposes. Unauthorized copying, modification, or commercial use is prohibited without prior written permission.