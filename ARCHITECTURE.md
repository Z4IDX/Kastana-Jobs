# Architecture & Design Decisions — Kastana Jobs

This document explains *how* the project is built and *why* the notable decisions were made. It's meant for another developer reviewing the codebase, not for end users (see [README.md](README.md) for setup).

---

## 1. Overview

Kastana Jobs is a curated, bilingual (English/Arabic) job board. Three roles interact with it:

- **Companies** submit postings through a public form — no account required. Submissions land as `pending`.
- **Admins** log in to review, edit, approve/reject, feature, expire, or delete postings, and to manage categories and view applicants.
- **Visitors** browse approved postings, filter/search/sort, save roles, and apply — also with no account.

The hard constraint that shaped everything: **it had to run on a stock XAMPP install with no build step** — no framework, no Composer, no npm. That constraint is a feature, not a limitation: the whole app is readable PHP you can drop into `htdocs` and run.

---

## 2. Architecture at a glance

```
Request
  → <page>.php  (require config/config.php first — always)
      → config.php   starts the session, sends security headers,
                     exposes db() (PDO singleton), loads the helper layer
      → helpers      functions.php (escaping, CSRF, auth, formatting),
                     i18n.php (translations + language/RTL), upload.php (images)
      → PDO prepared statement → MySQL/MariaDB
      → render: includes/header.php + page markup + includes/footer.php
```

There is **no router and no MVC layer** — each URL maps to a PHP file, which is the simplest thing that works for a site this size and keeps the mental model flat. Shared behavior lives in a thin helper layer rather than a framework:

- **`e()` / `t()` / `th()`** — output escaping and translation, applied to *everything* echoed.
- **`db()`** — a lazily-created PDO singleton with `EMULATE_PREPARES = false` and exceptions on.
- **`job_field($job, 'title')`** — returns the Arabic variant when the page is RTL and it exists, else the base field. This is how bilingual content stays a one-liner at every call site.
- **CSRF / auth / flash / rate-limit helpers** so every page follows the same patterns.

Conventions are documented in [CLAUDE.md](CLAUDE.md) so the codebase stays consistent as it grows.

---

## 3. Key decisions & trade-offs

**No framework / no build step.** Chosen for the deployment target (XAMPP) and to work directly with the fundamentals a framework usually hides — sessions, prepared statements, CSRF, headers. Trade-off: no ORM, no routing, no dependency management. Mitigated with a disciplined helper layer and documented conventions.

**Server-side search / filter / sort / pagination.** The board originally filtered client-side in JavaScript over every rendered card. That doesn't scale and breaks once listings grow, so it was moved fully server-side via query-string parameters (`?category=&q=&sort=&page=`), which also makes filtered views linkable and shareable. A subtle correctness note: because `EMULATE_PREPARES` is off, `LIMIT`/`OFFSET` must be bound with `PDO::PARAM_INT` — passing them as strings breaks on real prepared statements.

**Saved jobs via cookie, not accounts.** Visitors bookmark roles without signing up. The saved set is a capped, `HttpOnly`, `SameSite=Lax` cookie of job IDs. Trade-off: it doesn't persist across devices — but it avoids building an entire auth system for visitors, which is the right scope for a curated board.

**Bilingual content model.** Rather than a separate translations table, each job row carries optional `*_ar` columns (`title_ar`, `description_ar`, …). Reads go through `job_field()`, which falls back to the base language when a translation is missing. Simple, and it keeps a posting's two languages in one row.

**Email intentionally descoped.** Notifications (admin on new submission, company on approve/reject) are the biggest missing feature — but XAMPP has no working mail transport by default, so rather than ship something that silently fails, the decision was to leave it out and document it as future work with an explicit "pick an SMTP strategy first" note.

---

## 4. Security model

Security was designed in from the start rather than bolted on. Every item below is enforced in code:

| Threat | Mitigation |
|---|---|
| SQL injection | PDO prepared statements everywhere; `EMULATE_PREPARES=false`; user input never concatenated into SQL |
| XSS | All output escaped via `e()` (`htmlspecialchars`); CSP restricts scripts to `'self'` (no inline handlers anywhere) |
| CSRF | Per-session token on every form, verified with `hash_equals()`; 419 on mismatch |
| Password theft | bcrypt (cost 12) with `password_needs_rehash` on login |
| User enumeration | A constant-time **dummy hash** is verified even when the username doesn't exist, so login timing doesn't leak which usernames are valid |
| Brute force | Login locks an IP after 5 failed attempts in 15 minutes, tracked in `login_attempts` |
| Session attacks | `HttpOnly` + `SameSite=Strict` cookies, `session_regenerate_id(true)` on login, 60-min idle timeout |
| Malicious uploads | Validated by real content (`getimagesize` **and** `finfo`), 2 MB cap, random filenames, `uploads/` set to never execute PHP, path-traversal guard on delete |
| Spam submissions | Hidden honeypot field on the public form |
| Open redirect | The saved-jobs return URL is regex-whitelisted to same-site relative paths |

The dummy-hash detail is the one worth calling out in a review: it closes a timing side-channel most login forms leave open.

---

## 5. Data model & integrity

Schema lives in [config/database.sql](config/database.sql) (the canonical file). Notable choices:

- **Foreign keys with deliberate delete behavior.** `applicants.job_id` uses `ON DELETE CASCADE` (an application only means something attached to a live job). `activity_log.job_id` and `.admin_id` use `ON DELETE SET NULL` — deleting a job or admin must **not** erase the audit trail.
- **Audit log survives deletion.** Because `activity_log.job_id` goes null when a job is deleted, each entry also stores a `"title — company"` snapshot in `details`, so history stays legible after the referenced job is gone. Deletes are logged *before* the row is removed so the FK is still satisfiable at insert time.
- **Migrations discipline.** Every schema change ships as both an edit to `database.sql` (fresh installs) and a standalone `migration_*.sql` (existing installs), following the established naming pattern.
- **A known footgun, documented.** `admin/edit-job.php` has hand-written `INSERT`/`UPDATE` column lists that must stay exactly in sync with the table. This is called out explicitly in CLAUDE.md so the next change to the `jobs` table doesn't silently break the bound-parameter count.

---

## 6. Testing & verification

Changes were verified end-to-end against a running XAMPP stack, not just eyeballed:

- Migrations applied to the live DB and confirmed with `DESCRIBE`.
- Full public flow driven with real CSRF tokens: form submission **with image upload**, thumbnail generation (large image → resized `_thumb`, small image → no thumb + original reused), applicant submission, honeypot rejection, invalid-input rejection.
- Full admin flow: login guard, approve/feature/unfeature actions, category CRUD, and confirmation that each action wrote a correct `activity_log` entry (including UTF-8 integrity of the snapshot label).
- Expiry checked both ways: an expired job disappears from the board and 404s on its detail page, then reappears when the date is cleared.

---

## 7. Known limitations & future work

Ordered by impact-for-effort:

1. **Google for Jobs structured data** (`JobPosting` JSON-LD on the detail page) — table stakes for job-board discoverability; low effort and fits the no-build architecture. The highest-ROI next step.
2. **Email** — transactional notifications and job alerts / saved searches. Blocked on choosing a mail transport (SMTP library vs configured `mail()`), since XAMPP ships none.
3. **Employer self-service** — companies currently submit "blind"; they can't edit a posting or view applicants without an admin. This is the biggest functional gap and needs a new authenticated role.
4. **Job-seeker accounts** — would make saved jobs/alerts persistent across devices and add an application history. Optional for a curated board.
5. **Applicant pipeline** — the `applicants` table is currently a flat list; statuses (new/reviewed/shortlisted), notes, and CSV export would make it a real hiring tool.
6. **Repo hygiene** — stale duplicate SQL files (`database.sql` and `migration_arabic.sql` at the repo root, and `config/kastana_jobs.sql`) predate the canonical `config/database.sql`. They're excluded from version control via `.gitignore` but still sit on disk; delete them once you're sure nothing local depends on them.
