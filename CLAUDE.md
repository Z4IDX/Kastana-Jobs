# CLAUDE.md — Kastana Jobs

Project context for Claude Code. Read this before making changes.

## What this is
A curated **job board** built in plain PHP + MySQL to run on **XAMPP** (Apache + MariaDB). No framework, no build step, no Composer/npm. Edit PHP/CSS/JS directly and test in the browser.

- **Employers** register an account (`register.php` / `login.php`), then create and edit their **own** postings from `employer/dashboard.php`. Every submit/edit enters the admin review queue (`status='pending'`).
- **Admins** (platform staff) log in at `admin/login.php` to review, edit, approve, reject, feature, expire, or delete **all** postings, and manage categories.
- **Visitors** browse approved postings without any account, and **apply directly by phone or email** — the site is not an intermediary (there is no on-site application form).
- **Notifications**: when a posting is approved/rejected, the employer sees an on-site popup on their dashboard (no email transport exists).
- The site is **Arabic-first**: Arabic is the default language, the whole app (public, employer, admin) is translated and RTL, with an English toggle. Postings hold optional `*_ar` fields per language.
- **Single site** — the earlier multi-tenant/subdomain system was removed. One board, no `tenants`.
- Postings can carry an uploaded **company image/logo** (auto-thumbnailed).

Brand: "Kastana Mena". Palette is blue / yellow / grey (see CSS tokens below).

## Stack & requirements
- PHP 8.0+ with `pdo_mysql`, `mbstring`, `fileinfo` (all default in XAMPP), and `gd` for thumbnail
  generation (uncomment `extension=gd` in php.ini if missing — thumbnails degrade gracefully without it).
- MySQL / MariaDB.
- Apache with `mod_rewrite` + `mod_headers` (default in XAMPP).
- Frontend: vanilla CSS (custom properties) + a little vanilla JS. Google Fonts via CDN.

## Run / setup
1. Folder lives in `htdocs/kastana-jobs`.
2. Start Apache + MySQL in XAMPP.
3. Import `config/database.sql` (Navicat or phpMyAdmin) — creates DB `kastana_jobs`, tables, seed data, and a default admin.
4. Open `http://localhost/kastana-jobs/`. Admin: `http://localhost/kastana-jobs/admin/login.php`.
5. Default admin login: **`admin` / `ChangeMe!2025`** (change via admin → Account).
6. Coming from the multi-tenant version? Run `config/migration_single_site.sql` once (drops tenants/tenant_id/applicants, adds `employers`, `jobs.employer_id` + `company_phone`), then `config/migration_notifications.sql`. Older base migrations (arabic/uploads/expiry/thumbnails/activity_log) still apply to pre-those installs.
7. `uploads/` must be writable (automatic on Windows/XAMPP; `chmod 775 uploads` on macOS/Linux).

## File map
```
index.php            Public board: hero, server-side search + category/job-type filter + sort + pagination
job.php              Single job detail (bilingual; apply by Call (tel:) / Email (mailto) / apply_url; copy-link)
register.php         Employer sign-up (open); creates an active account and signs in
login.php            Employer sign-in (rate-limited, constant-time verify)
employer/dashboard.php   Employer's own postings + statuses + approval popup notifications
employer/post-job.php    Employer create/edit their own posting (any edit -> 'pending'); logout.php
save.php             POST-only toggle for the saved-jobs cookie (CSRF, whitelisted return redirect)
saved.php            Visitor's bookmarked jobs (reads the kastana_saved cookie)
.htaccess            Security: no dir listing, block sensitive files/dotfiles, headers
README.md            End-user setup guide
CLAUDE.md            This file

config/
  config.php         DB creds, APP settings, session cookie flags, security headers,
                     db() PDO singleton, constants (BASE_URL, USE_HTTPS, login limits, MAX_UPLOAD_BYTES).
                     Requires functions.php, i18n.php, upload.php.
  database.sql       Full schema + seed. Starts with `SET NAMES utf8mb4` (keep it!).
  migration_single_site.sql  Multi-tenant -> single-site: drops tenants/tenant_id/applicants, adds
                     employers + jobs.employer_id + company_phone, simplifies admins.
  migration_notifications.sql  Creates the notifications table (employer approval alerts).
  migration_account_verification.sql  Adds 'pending' to employers.status (admin-approved sign-ups).
  migration_settings.sql  Creates the settings table + seeds moderation_mode.
  migration_*.sql    Older base migrations (arabic/uploads/expiry/thumbnails/activity_log) for pre-those installs.
  .htaccess          Deny-all (folder not web-accessible).

includes/
  functions.php      e(), url(), redirect(), input(), slugify(), CSRF (csrf_field/require_csrf),
                     flash_set/flash_get, auth (is_logged_in/require_login/login_admin/logout_admin/
                     current_admin_id), login rate-limiting, format_salary(), time_ago() (localized),
                     render_text().
  i18n.php           translations() [en/ar arrays], current_lang(), is_rtl(), dir_attr(),
                     t(), th() (markup-safe), lang_switch_url(), job_type_label(),
                     job_field($job,'base'), cat_name($name,$name_ar).
  upload.php         save_uploaded_image('field') -> ['path'=>?, 'thumb_path'=>?, 'error'=>?];
                     make_thumbnail() (GD, max 160px); delete_uploaded_image($rel).
  job-card.php       Shared job-card partial (used by index.php and saved.php; includes save-star form).
  header.php         Public <head> (sets <html lang dir>, fonts, favicon, logo, nav incl Saved, language toggle).
  footer.php         Public footer + main.js.
  .htaccess          Deny-all.

admin/
  login.php          Rate-limited login (bcrypt verify, dummy-hash timing, CSRF).
  logout.php
  dashboard.php      List/filter by status; POST actions: approve, reject, unpublish, feature, delete
                     (logged to activity_log). approve/reject notify the owning employer. Thumbnails, AR/Expired badges.
  edit-job.php       Create/edit any posting. Bilingual fields + company_phone + image upload/thumbnail.
                     Sets status + featured + expires_at. Logs create/edit to activity_log.
  activity-log.php   Paginated admin action history.
  categories.php     Category list + delete (job counts shown; FK sets jobs.category_id NULL).
  category-form.php  Create/edit a category (auto slug via slugify + collision suffix).
  account.php        Change admin password.
  employers.php      Employer-account verification: approve pending sign-ups, suspend/reactivate (logged).
  includes/admin-header.php   Calls require_login(); admin <head> + top bar (Categories/Activity log links).
  includes/admin-footer.php   + admin.js.

assets/
  css/style.css      Public design system: :root tokens, glass header, hero, glass job cards,
                     effects (floating orbs, shimmer headline, hover sheen), RTL rules, logo,
                     file input, uploaded-image styles.
  css/admin.css      Admin styles: tokens, top bar, buttons, stat cards, tabs, job rows (+thumb),
                     forms, login, img-review, file input.
  js/main.js         Scroll reveal (IntersectionObserver), sort auto-submit, copy-link button.
  js/admin.js        Confirm-before-delete on forms with [data-confirm].
  img/logo.png       Site logo (header + footer). Replace this file to change the logo.
  img/favicon.svg    Brand-colored tab icon.

uploads/             User-uploaded images. .htaccess disables code execution here. index.html placeholder.
```

## Database (`kastana_jobs`, utf8mb4)
- **admins** (platform staff): id, username (uniq), email (uniq), password_hash (bcrypt), last_login, created_at.
- **employers** (self-registered posters, kept separate from admins on purpose): id, company_name, email (uniq), password_hash (bcrypt), phone, website, status (**pending/active/suspended** — new sign-ups start `pending` and can log in but cannot post until an admin approves them in `admin/employers.php`), last_login, created_at.
- **categories**: id, name, name_ar, slug (uniq), created_at.
- **jobs**: id, employer_id (FK→employers, NULL on delete = admin-created), title, title_ar, slug, company_name, company_email,
  company_phone (shown so applicants can call), company_website,
  location, location_ar, job_type (ENUM: Full-time, Part-time, Contract, Internship, Remote, Temporary),
  category_id (FK→categories, NULL on delete), salary_min, salary_max, salary_currency,
  description, description_ar, requirements, requirements_ar, how_to_apply, how_to_apply_ar,
  apply_url, image_path, thumbnail_path, status (ENUM: pending, approved, rejected), is_featured,
  expires_at (NULL = never expires), created_at, updated_at, approved_at, approved_by (FK→admins).
- **login_attempts**: id, ip_address, username, success, attempted_at (shared by admin + employer logins).
- **activity_log**: id, admin_id (FK→admins, NULL on delete), job_id (FK→jobs, NULL on delete),
  action, details (snapshot label, survives job deletion), created_at.
- **notifications**: id, employer_id (FK→employers, CASCADE), job_id (FK→jobs, NULL on delete), type (approved/rejected), title (snapshot), is_read, created_at. Written by notify_employer(); shown/cleared on employer/dashboard.php via unread_notifications()/mark_notifications_read().
- **settings**: k (PK), v. Key/value store. Holds `moderation_mode` (**both/companies/jobs**) — set from the toggle on `admin/dashboard.php` via `moderation_mode()`/`get_setting()`/`set_setting()`. Controls whether new employer accounts and/or new jobs require admin approval before going live (companies→jobs auto-publish once the account is approved; jobs→accounts auto-activate but each posting is reviewed; both→current default).

Seed: 8 bilingual categories, 1 admin, 2 approved bilingual sample jobs.

## Conventions (follow these)
- Every entry page begins with `require_once .../config/config.php` (starts session, sends security headers, exposes `db()`, loads helpers).
- **DB access**: always `db()->prepare(...)->execute([...])` with bound params. Never interpolate user input into SQL.
- **Escaping**: wrap every echoed value in `e()`. Use `t('key')` for UI strings; `th('key')` for translated strings that contain HTML (echoed unescaped — only used for our own trusted markup).
- **Bilingual content**: read job fields with `job_field($job,'title')` (returns `*_ar` when RTL and present, else base). Categories via `cat_name($row['name'],$row['name_ar'])`. Job types via `job_type_label($type)`.
- **Forms**: include `csrf_field()`; every POST handler calls `require_csrf()` first. File forms need `enctype="multipart/form-data"`.
- **Auth**: guard admin pages with `require_login()`. Use `current_admin_id()`.
- **Flash messages**: `flash_set('success'|'error'|'info', $msg)` then render `flash_get()`.
- **Redirects**: `redirect('relative/path')` (resolved against BASE_URL by `url()`).
- **Images**: `save_uploaded_image('image')`; on replace/remove/delete call `delete_uploaded_image($oldPath)`.

## CSS tokens & fonts
Tokens live in `:root` of both stylesheets. **Note the historical names**: `--chestnut` and `--honey` now hold the brand blue and yellow (renamed palette, kept variable names to avoid churn).
- `--chestnut: #1D5C9D` (brand blue — primary buttons, links, hero, card spine)
- `--chestnut-deep: #164A80` (darker blue — hero/admin-bar backgrounds)
- `--honey: #EAA62C` (brand yellow — accents, highlights, badges)
- `--ink: #57575A` (brand grey — body text)
- `--brand-orange: #F26E2C`, `--brand-green: #5A9E43`
- Admin status colors: green=approved, amber=pending, red/orange=rejected.
Fonts: Fraunces (display), Plus Jakarta Sans (body), Space Mono (labels); Cairo + Tajawal load when RTL.

## Security model (preserve when editing)
- PDO prepared statements, `EMULATE_PREPARES=false`.
- Passwords: `password_hash` bcrypt cost 12; `password_needs_rehash` on login; constant-time dummy-hash to avoid user enumeration; lockout after `LOGIN_MAX_ATTEMPTS` (5) within `LOGIN_WINDOW_MIN` (15) via `login_attempts`.
- CSRF token (`hash_equals`) on all forms → 419 on failure.
- Output escaping everywhere; CSP + `X-Frame-Options`/`X-Content-Type-Options`/`Referrer-Policy` set in `config.php` and `.htaccess`. CSP allows Google Fonts and same-origin only.
- Session: HttpOnly + SameSite=Strict cookies, `secure` when `USE_HTTPS`, `session_regenerate_id(true)` on login, 60-min idle timeout.
- Honeypot field `website_url` on the public submit form.
- Uploads: validated by `getimagesize()` **and** `finfo` (allow-list jpg/png/webp/gif), 2 MB cap, random filenames, `uploads/.htaccess` disables PHP execution, path-traversal guard in `delete_uploaded_image()`.
- `config/` and `includes/` are deny-all; directory listing off; sensitive extensions blocked at the root `.htaccess`.

## Config knobs (`config/config.php`)
- `DB_HOST=127.0.0.1`, `DB_NAME=kastana_jobs`, `DB_USER=root`, `DB_PASS=''` (XAMPP defaults).
- `BASE_URL='/kastana-jobs'` — must match the folder name. If renamed, also update the `ErrorDocument` paths in the root `.htaccess`.
- `USE_HTTPS=false` — set `true` in production (enables secure cookies + HSTS).
- `LOGIN_MAX_ATTEMPTS`, `LOGIN_WINDOW_MIN`, `SESSION_TIMEOUT_MIN`, `MAX_UPLOAD_BYTES` (2 MB).

## Validation rules
- Public submit: title 3–150 chars, company name ≥2, valid email, valid URLs (website/apply if provided), location ≥2, description ≥40, how_to_apply ≥10, salary_min ≤ salary_max.
- Admin edit: same but description ≥20 and how_to_apply ≥5.
- `job_type` and `status` must match their enum whitelists; `category_id` must exist.

## Gotchas
- **`SET NAMES utf8mb4`** at the top of the SQL files is required — without it, Arabic seed data imports as mojibake on some clients.
- **Job INSERT/UPDATE** statements have exactly matched column/placeholder/value counts, in two places: `admin/edit-job.php` (admin, INSERT = 28 columns incl `company_phone`, `image_path`, `thumbnail_path`, `expires_at`) and `employer/post-job.php` (employer, INSERT = 24 columns incl `employer_id` + `company_phone`, no status/expiry which stay defaulted). If you add a jobs column, update the CREATE TABLE, `migration_single_site.sql`, and all three parts (columns/placeholders/values) of **both** statements.
- The public form accepts optional `*_ar` fields (title/location/description/requirements/how_to_apply); admins can still add or edit Arabic later in the editor.
- Admin UI strings are intentionally English; don't wrap them in `t()` unless the goal is to make the admin bilingual too.
- No build step: don't add bundlers. Keep everything runnable by copying to `htdocs`.
- `display_errors` is off (errors go to the PHP log). Turn on temporarily in `config.php` when debugging locally.

## How to test locally
Copy to `htdocs/kastana-jobs`, import `config/database.sql`, browse the site. For a quick server without XAMPP: `php -S 127.0.0.1:8080 -t <parent-of-project>` and open `http://127.0.0.1:8080/kastana-jobs/index.php` (requires a MySQL/MariaDB reachable at the configured host).

## Design & tooling (Claude Code)
- **Design system**: `design-system/MASTER.md` is the source of truth for UI decisions (palette tokens, fonts, RTL
  discipline, accessibility/touch rules, job-board-specific guidance). Read it before building or changing UI.
  Page-specific overrides go in `design-system/pages/<page>.md` and take precedence over MASTER for that page.
  Generated by the **ui-ux-pro-max** skill; its live search engine (`scripts/search.py`) needs Python 3, which is
  **not installed** here — the persisted MASTER.md was authored from the skill's Quick Reference instead.
- **21st MCP server** (`21st` → `https://21st.dev/api/mcp`, HTTP, `x-api-key` header): registered in local config
  (`~/.claude.json`, scoped to this project). It's a **React/Tailwind** component generator/registry, so it does
  **not** fit this project's stack directly — no build step, plain PHP + vanilla CSS. Treat its output as design
  reference to hand-port into our CSS tokens, not code to paste in. Loads on next `claude` start, not mid-session.

## Possible next tasks (backlog, not yet built)
- Email notifications (admin on new submission; company on approve/reject). Needs a mail strategy first —
  XAMPP/Windows has no working MTA by default; either configure php.ini SMTP or vendor a small SMTP class.
- Optional bilingual admin UI.
- Applicant statuses (new/reviewed/shortlisted) and CSV export.
- Resume upload on the apply form (needs a document-upload validator separate from the image one).
