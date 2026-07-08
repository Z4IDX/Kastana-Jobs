# Kastana Jobs

A curated, **bilingual (English/Arabic)** job board where **companies submit postings**, **admins review and approve them**, and **visitors browse without any account**. Built with plain PHP + MySQL (MariaDB) so it runs on XAMPP with no build step.

> **Reviewing the code?** See [ARCHITECTURE.md](ARCHITECTURE.md) for the design decisions, security model, and trade-offs.

## Features

- **Public board** — hero, category filters, keyword search, sort (newest/salary/title), and pagination — all server-side and shareable via the URL.
- **No-account submission** — companies post a role (optionally in both languages, with a logo) straight into a review queue.
- **Admin panel** — review/approve/reject/feature/unpublish/delete, edit postings, manage categories, set expiry dates, and view applicants.
- **Applicant capture** — job seekers apply through an on-site form; applications are listed per posting in the admin.
- **Saved jobs** — visitors bookmark roles (cookie-based, no login needed).
- **Activity log** — every admin action on a posting is recorded, and the history survives even after a posting is deleted.
- **Bilingual + RTL** — full English/Arabic UI with a per-posting Arabic content option.
- **Image handling** — validated uploads with auto-generated thumbnails.
- **Security-first** — CSRF, prepared statements, bcrypt, login rate-limiting, CSP, and more (see below).

---

## What's inside

```
kastana-jobs/
├── index.php            # Public job board (hero, filters, cards)
├── job.php              # Single job detail page
├── submit.php           # Company submission form (no login needed)
├── config/
│   ├── config.php       # ← EDIT THIS: DB credentials & settings
│   ├── database.sql     # Import this to create the database
│   └── .htaccess        # Blocks web access to this folder
├── includes/            # Shared PHP (functions, header, footer)
├── saved.php            # A visitor's bookmarked jobs
├── save.php             # Toggles the saved-jobs cookie (POST only)
├── admin/
│   ├── login.php        # Admin sign-in (rate-limited)
│   ├── dashboard.php    # Review / approve / reject / feature / delete
│   ├── edit-job.php     # Create or edit a posting
│   ├── categories.php   # Manage categories
│   ├── category-form.php# Create / edit a category
│   ├── applicants.php   # Applicants for a posting
│   ├── activity-log.php # Admin action history
│   ├── account.php      # Change your password
│   └── logout.php
├── includes/            # Shared PHP (functions, i18n, uploads, job-card, header, footer)
├── assets/              # CSS + JS
└── .htaccess            # Security rules
```

See [ARCHITECTURE.md](ARCHITECTURE.md) for how these fit together and why.

---

## Setup — the easy way (wizard)

After copying the folder into `htdocs` and starting Apache + MySQL, open:
```
http://localhost/kastana-jobs/install.php
```
The wizard checks your PHP setup, creates the database, imports the schema, sets your **brand name**, and creates your **admin account** — no editing files or importing SQL by hand. **Delete `install.php` when it's done.** (It refuses to run once configured, but deleting it is safest.) Your settings are written to `config/config.local.php`, which is git-ignored so credentials never get committed.

Prefer to do it manually? The full steps are below.

---

## Setup (manual, XAMPP)

**1. Copy the folder**
Put the whole `kastana-jobs` folder into your XAMPP `htdocs` directory:
`C:\xampp\htdocs\kastana-jobs`

**2. Start services**
Open the XAMPP Control Panel and start **Apache** and **MySQL**.

**3. Create the database**
Import the schema with **Navicat** (or phpMyAdmin):

- *Navicat:* connect to `localhost` (user `root`, empty password by default) → right-click → **Execute SQL File** → choose `config/database.sql` → Run.
- *phpMyAdmin:* go to `http://localhost/phpmyadmin` → **Import** → choose `config/database.sql` → Go.

This creates the `kastana_jobs` database, all tables, sample categories, two example jobs, and a default admin.

**4. Check your settings**
Open `config/config.php`. The defaults match a stock XAMPP install:

```php
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'kastana_jobs');
define('DB_USER', 'root');
define('DB_PASS', '');            // set this if your MySQL root has a password
define('BASE_URL', '/kastana-jobs');
```

If you renamed the folder, update `BASE_URL` here **and** the two `ErrorDocument` lines in `.htaccess`.

**5. Open the site**
`http://localhost/kastana-jobs/`

---

## Logging in

Admin panel: `http://localhost/kastana-jobs/admin/login.php`

| | |
|---|---|
| **Username** | `admin` |
| **Password** | `ChangeMe!2025` |

**Change this immediately** after your first login: top-right → **Account**.

---

## How it works

1. A company fills in **Post a job** — no account needed. The posting is saved as **pending** and is *not* shown publicly.
2. An admin signs in, sees it under **Pending**, and can **edit**, **approve**, or **reject** it. Approving publishes it instantly.
3. Visitors browse approved roles on the homepage, filter by category, search, and apply via the link or email the company provided.

Admins can also create postings directly, **feature** a role (pins it to the top), unpublish, or delete.

---

## Security built in

- **SQL injection** — every query uses PDO prepared statements; no user input is ever concatenated into SQL.
- **Passwords** — hashed with bcrypt (cost 12) via `password_hash()`; never stored in plain text.
- **CSRF** — every form carries a per-session token, verified with `hash_equals()`.
- **XSS** — all output is escaped with `htmlspecialchars()`; a Content-Security-Policy header restricts scripts to the site's own origin.
- **Brute force** — the login locks an IP after 5 failed attempts within 15 minutes.
- **Sessions** — HttpOnly + SameSite=Strict cookies, session ID regenerated on login, auto-logout after 60 minutes idle.
- **Spam** — the public form has a hidden honeypot field that silently drops bot submissions.
- **Hardening** — security headers, directory listing disabled, and the `config/` and `includes/` folders blocked from direct web access.

### Before going live (production)
- Set a real MySQL password and put it in `config.php`.
- Serve over HTTPS, then set `USE_HTTPS` to `true` in `config.php` (enables secure cookies + HSTS).
- Create a dedicated MySQL user with only the privileges this app needs, instead of `root`.
- Delete the sample jobs and change the admin password.

---

## Bilingual (English / Arabic)

The public site is fully bilingual with a language toggle in the header. Switching to Arabic flips the whole layout to RTL, swaps in Arabic fonts (Cairo + Tajawal), and translates every UI label; the choice is remembered for the session.

- **Visitors** tap **العربية / English** in the header to switch.
- **Job content** is per-language: each posting can hold an English *and* an Arabic version. On an Arabic page a job shows its Arabic text, falling back to the English text when no Arabic is provided.
- **Admins** add the Arabic version in the posting editor — scroll to the **Arabic content** section (title, location, description, requirements, how to apply). A blue **AR** badge appears on the dashboard for postings that have Arabic. The admin panel itself stays in English.

**Already imported the database before this update?** Run the one-time migration to add the Arabic columns without losing data:
- Navicat / phpMyAdmin → open and run `config/migration_arabic.sql`.

Fresh imports of `config/database.sql` already include everything. Both SQL files pin the connection to `utf8mb4`, so Arabic text imports correctly on any client.

## Job images

Each posting can carry a company logo or image.

- **Companies** attach one on the public **Post a job** form (optional).
- **Admins** can upload, replace, or remove the image in the posting editor, and see a thumbnail on the dashboard while reviewing.
- On the site the image shows on the job card and the job detail page; postings without one fall back to a lettered monogram.

Uploads are validated by real file content (not the filename): only JPG, PNG, WEBP, and GIF up to 2 MB are accepted, files are stored under `uploads/` with random names, and that folder is configured to **never execute code**. Deleting or replacing an image removes the old file automatically.

**Already imported the database before this update?** Run `config/migration_uploads.sql` once (Navicat / phpMyAdmin) to add the `image_path` column. Fresh imports of `config/database.sql` already include it.

**The `uploads/` folder must be writable by the web server.** On XAMPP for Windows this works out of the box; on macOS/Linux run `chmod 775 uploads` (or `chmod 777` if needed) inside the project.

## Changing the logo

The site logo lives at `assets/img/logo.png` and shows in the public header and footer. To use a different logo, **replace that file with your own, keeping the same name and path** — no code change needed. For the sharpest result use a PNG (transparent background) that's roughly 3–4× as wide as it is tall.

- Adjust its on-screen size in `assets/css/style.css` → `.brand-logo { height: 40px; }` (and `.brand-logo--sm` for the footer).
- The markup is in `includes/header.php` and `includes/footer.php` if you want to change alt text or swap to SVG.
- The browser-tab icon is `assets/img/favicon.svg` — replace it to change the tab icon.
- The admin panel and login screen sit on a dark background, so they use the text wordmark rather than the colour logo. If you have a white/light version of your logo, drop it in and swap the markup in `admin/includes/admin-header.php` and `admin/login.php`.

## Requirements

- PHP 8.0+ with `pdo_mysql`, `mbstring`, and `fileinfo` (all on by default in XAMPP), plus `gd` for
  thumbnail generation (uncomment `extension=gd` in `php.ini` if needed — thumbnails degrade gracefully without it).
- MySQL / MariaDB.
- Apache with `mod_rewrite` and `mod_headers` (both on by default in XAMPP).

**Upgrading an existing install?** Run the one-time migrations in `config/` that you haven't already:
`migration_arabic.sql`, `migration_uploads.sql`, `migration_expiry.sql`, `migration_thumbnails.sql`,
`migration_applicants.sql`, `migration_activity_log.sql`, `migration_tenancy.sql`, `migration_tenant_branding.sql`, `migration_tenant_settings.sql`. Fresh imports of `config/database.sql` include everything.

---

## Tech & design notes

- **No framework, no build step** — just PHP files you can read and edit.
- Design: roasted-chestnut + golden-honey palette on warm paper; Fraunces (display), Plus Jakarta Sans (body), Space Mono (labels) from Google Fonts.
- Fully responsive, keyboard-accessible focus states, and respects `prefers-reduced-motion`.
