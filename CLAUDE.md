# CLAUDE.md — Kastana Jobs

Project context for Claude Code. Read this before making changes.

## What this is
A curated **job board** built in plain PHP + MySQL to run on **XAMPP** (Apache + MariaDB). No framework, no build step, no Composer/npm. Edit PHP/CSS/JS directly and test in the browser.

- **Companies** submit postings through a public form — no account needed.
- **Admins** log in to review, edit, approve, reject, feature, or delete postings.
- **Visitors** browse approved postings without any account.
- The public site is **bilingual (English / Arabic)** with an RTL layout; the admin panel is English-only but lets admins enter Arabic content per posting.
- Postings can carry an uploaded **company image/logo**.

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
6. Existing DB from an earlier version? Run `config/migration_arabic.sql`, `config/migration_uploads.sql`, `config/migration_expiry.sql`, `config/migration_thumbnails.sql`, `config/migration_applicants.sql`, `config/migration_activity_log.sql`, and `config/migration_tenancy.sql` once.
7. `uploads/` must be writable (automatic on Windows/XAMPP; `chmod 775 uploads` on macOS/Linux).

## File map
```
index.php            Public board: hero, server-side search/filter/sort/pagination, job cards
job.php              Single job detail (bilingual, apply button/mailto, copy-link, applicant form)
submit.php           Public submission form (validation, honeypot, image upload, optional *_ar fields) -> 'pending'
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
  migration_arabic.sql   Adds *_ar columns + Arabic seed (for existing installs).
  migration_uploads.sql  Adds jobs.image_path (for existing installs).
  migration_expiry.sql   Adds jobs.expires_at (for existing installs).
  migration_thumbnails.sql  Adds jobs.thumbnail_path (for existing installs).
  migration_applicants.sql  Creates the applicants table (for existing installs).
  migration_activity_log.sql  Creates the activity_log table (for existing installs).
  migration_tenancy.sql  Multi-tenancy Phase 1: tenants table + tenant_id on jobs/applicants/
                     activity_log + admins.role/tenant_id (for existing installs). See docs/MULTITENANCY.md.
  migration_tenant_branding.sql  Phase 2: tenants.brand_name/logo_path/primary_color (per-tenant branding).
  migration_tenant_settings.sql  Phase 2: tenants.settings JSON (customization options; read via tenant_setting()/tenant_flag()).
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
                     (all logged to activity_log). Shows thumbnails, AR/Expired badges, applicant counts.
  edit-job.php       Create/edit a posting. Bilingual fields + image upload/replace/remove (+thumbnail).
                     Sets status + featured + expires_at. Logs create/edit to activity_log.
  applicants.php     Per-job applicant list (?job_id=N).
  activity-log.php   Paginated admin action history.
  categories.php     Category list + delete (job counts shown; FK sets jobs.category_id NULL).
  category-form.php  Create/edit a category (auto slug via slugify + collision suffix).
  account.php        Change admin password.
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
- **tenants** (multi-tenancy): id, name, brand_name, logo_path, primary_color (per-tenant branding; blank = platform default), subdomain (uniq), status (pending/active/suspended), created_at, activated_at. One company = one tenant, addressed by subdomain. See docs/MULTITENANCY.md. Branding is read via brand_name()/brand_logo_url()/brand_color(); the `settings` JSON holds customization options (tagline, highlight_color, hero_title/subtext, show_stats, per_page, show_salary, enable_apply, enable_saved, footer_note) read via tenant_setting()/tenant_flag(). Companies edit all of it at admin/branding.php ("Customize").
- **admins**: id, tenant_id (FK→tenants, NULL = platform super-admin), username, email, password_hash (bcrypt), role (super_admin/company_admin), last_login, created_at. username/email unique **per tenant**.
- **categories**: id, name, name_ar, slug (uniq), created_at.
- **jobs**: id, tenant_id (FK→tenants; DEFAULT 1 is transitional until every write sets it), title, title_ar, slug, company_name, company_email, company_website,
  location, location_ar, job_type (ENUM: Full-time, Part-time, Contract, Internship, Remote, Temporary),
  category_id (FK→categories, NULL on delete), salary_min, salary_max, salary_currency,
  description, description_ar, requirements, requirements_ar, how_to_apply, how_to_apply_ar,
  apply_url, image_path, thumbnail_path, status (ENUM: pending, approved, rejected), is_featured,
  expires_at (NULL = never expires), created_at, updated_at, approved_at, approved_by (FK→admins).
- **login_attempts**: id, ip_address, username, success, attempted_at.
- **applicants**: id, tenant_id (FK→tenants), job_id (FK→jobs, CASCADE on delete), name, email, phone, cover_note, created_at.
- **activity_log**: id, tenant_id (FK→tenants), admin_id (FK→admins, NULL on delete), job_id (FK→jobs, NULL on delete),
  action, details (snapshot label, survives job deletion), created_at.

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
- **edit-job.php INSERT/UPDATE** have exactly matched column/placeholder/value counts (INSERT = 27 columns incl `image_path`, `thumbnail_path`, `expires_at`; submit.php INSERT = 23 incl the five `*_ar` columns). If you add a jobs column, update the CREATE TABLE, the relevant migration, and all three parts of each statement.
- The public form accepts optional `*_ar` fields (title/location/description/requirements/how_to_apply); admins can still add or edit Arabic later in the editor.
- Admin UI strings are intentionally English; don't wrap them in `t()` unless the goal is to make the admin bilingual too.
- No build step: don't add bundlers. Keep everything runnable by copying to `htdocs`.
- `display_errors` is off (errors go to the PHP log). Turn on temporarily in `config.php` when debugging locally.

## How to test locally
Copy to `htdocs/kastana-jobs`, import `config/database.sql`, browse the site. For a quick server without XAMPP: `php -S 127.0.0.1:8080 -t <parent-of-project>` and open `http://127.0.0.1:8080/kastana-jobs/index.php` (requires a MySQL/MariaDB reachable at the configured host).

## Possible next tasks (backlog, not yet built)
- Email notifications (admin on new submission; company on approve/reject). Needs a mail strategy first —
  XAMPP/Windows has no working MTA by default; either configure php.ini SMTP or vendor a small SMTP class.
- Optional bilingual admin UI.
- Applicant statuses (new/reviewed/shortlisted) and CSV export.
- Resume upload on the apply form (needs a document-upload validator separate from the image one).
