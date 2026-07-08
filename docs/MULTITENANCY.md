# Multi-Tenancy Design — Phase 1 (Foundation)

Turning the single-board app into a SaaS where many companies each run their own board on a subdomain (`acme.yourjobboard.com`), managed by themselves, activated manually by the platform owner.

**Decisions (locked):** subdomain-per-company routing · manual activation (no payment integration yet) · Phase 1 = foundation only (tenant model + data isolation + company self-service accounts + a platform super-admin). Branding, billing, and custom domains are later phases.

---

## 1. Roles

- **Super-admin (you / platform owner):** lives on the root domain (`yourjobboard.com/admin`). Manages tenants — approves signups, activates/suspends, sees everything.
- **Company admin (tenant):** lives on their subdomain (`acme.yourjobboard.com/admin`). Manages *only their own* jobs, categories, applicants. Cannot see other tenants.
- **Visitor / applicant:** browses one company's board at its subdomain. No account.

## 2. Data model

New table:
```
tenants
  id, name, subdomain (unique), status ENUM('pending','active','suspended'),
  created_at, activated_at
```

Add `tenant_id` (FK → tenants, ON DELETE CASCADE) to every content table: **jobs, categories, applicants, activity_log**. Every content query filters by it.

`admins` gains:
- `tenant_id` (nullable — NULL means a platform super-admin; set means a company admin for that tenant)
- `role` ENUM('super_admin','company_admin')
- uniqueness of `username`/`email` becomes **per-tenant** (two companies can each have an "admin").

**Migration for existing data:** create one tenant (e.g. `subdomain='kastana'`, active), assign all existing jobs/categories/applicants/activity_log/admins to it, so nothing breaks. Ships as `config/migration_tenancy.sql` + folded into `database.sql`.

## 3. Tenant resolution (per request)

In `config.php`, after session start, resolve the current tenant from the `Host` header:
- Strip the base domain → take the leftmost label as the subdomain.
- **No subdomain / `www` / the bare root domain →** the *platform* context (marketing home, signup, super-admin). No tenant.
- **A subdomain →** look it up in `tenants`. Not found or not `active` → show a themed "board not found / not active" page (404) and stop.
- Store it once: `current_tenant()` returns the row (or null on the platform domain), `current_tenant_id()` returns the id.

## 4. Data isolation — the safety model

This is the part that must not leak. The strategy, in layers:
1. **A single choke point.** Content reads/writes go through helpers that *always* inject `tenant_id = ?` — e.g. `tenant_jobs_query()` builders — rather than hand-writing `WHERE tenant_id` in 12 files and hoping none is forgotten.
2. **Writes stamp the tenant automatically** from `current_tenant_id()`, never from user input.
3. **Every admin action re-checks ownership**: `... WHERE id = ? AND tenant_id = ?`, so a company admin can't approve/delete another tenant's job by guessing an id.
4. **Super-admin is the only role that can cross tenants**, and only in the platform area.
5. A short **manual test matrix** (below) run before merge: log in as Company A, try to reach Company B's job/applicant/edit URLs by id → must 404.

## 5. Auth changes

- Login resolves against `(tenant_id, username)`: on `acme.` it authenticates company admins for that tenant; on the root domain it authenticates super-admins.
- `require_login()` stays, plus `require_super_admin()` for the platform area.
- Sessions already regenerate on login; we add the tenant id into the session and verify it matches the request's subdomain (prevents a session from one tenant being replayed on another).

## 6. Company self-service + manual activation

- **Signup** (root domain): company picks a name + desired subdomain + admin credentials → creates a `tenants` row as `pending` and its first `company_admin`. Subdomain validated (allow-list charset, uniqueness, reserved words like `www`/`admin`/`api`).
- **Activation:** super-admin dashboard lists pending tenants → "Activate" flips status to `active` (this is the "you got paid" step). Suspend/reactivate too.
- Until active, the subdomain shows a "coming soon / awaiting activation" page.

## 7. Local development (XAMPP + subdomains)

Subdomains need a tiny bit of local setup (documented for buyers too):
- Browsers resolve `*.localhost` to 127.0.0.1 automatically, so `acme.localhost` works with **no hosts-file editing** in Chrome/Edge.
- Apache: one virtual host with `ServerAlias *.localhost` (or a `.test` domain) pointing at the project so any subdomain is served. The tenant code reads whatever `Host` sends, so it's environment-agnostic.
- We'll include the sample vhost snippet in the docs.

## 8. Explicitly deferred (later phases)

- Per-tenant branding (name/logo/colors from the DB — the white-label config hooks we already built become the fallback).
- Subscription/billing integration (Phase 1 is manual activation).
- Custom domains (`careers.acme.com`).
- Plan limits (max jobs, etc.).

## 9. Build order for Phase 1

1. Schema + migration (tenants table, `tenant_id` columns, admin role/tenant, backfill a default tenant).
2. Tenant resolution in `config.php` + `current_tenant()` / `current_tenant_id()` helpers.
3. Scope every existing content query (public board, job detail, submit, saved, and all admin pages) to the current tenant, with ownership re-checks on admin actions.
4. Auth: tenant-scoped login + `require_super_admin()`.
5. Signup flow + super-admin tenant list with activate/suspend.
6. Run the isolation test matrix; document the local vhost setup.

## 10. Risks

- **Cross-tenant leakage** is the headline risk — mitigated by the single-choke-point helpers + the pre-merge test matrix.
- **The `edit-job.php` column-count footgun** (already noted in CLAUDE.md) now also has to carry `tenant_id` — the INSERT/UPDATE lists get updated carefully.
- Subdomain routing means the app really wants to live at a domain root (a vhost), not the `/kastana-jobs` subpath — a deployment note, not a code problem.
