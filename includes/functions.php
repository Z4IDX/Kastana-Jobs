<?php
/**
 * Kastana Jobs — Shared helper functions
 * Security, output escaping, auth, and small utilities.
 */

/* ---------- Output escaping (XSS protection) ---------- */

/** Escape a string for safe HTML output. Use this on EVERYTHING you echo. */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Build an absolute URL from the app base. */
function url(string $path = ''): string
{
    return rtrim(BASE_URL, '/') . '/' . ltrim($path, '/');
}

/** Send a redirect and stop. */
function redirect(string $path): void
{
    header('Location: ' . url($path));
    exit;
}

/* ---------- Input helpers ---------- */

/** Read + trim a value from an input array. */
function input(array $src, string $key, string $default = ''): string
{
    return isset($src[$key]) ? trim((string) $src[$key]) : $default;
}

/** Create a URL-safe slug from a title. */
function slugify(string $text): string
{
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text) ?: $text;
    $text = strtolower(trim($text, '-'));
    $text = preg_replace('~[^-a-z0-9]+~', '', $text);
    return $text !== '' ? $text : 'job';
}

/* ---------- CSRF protection ---------- */

/** Return the current CSRF token, creating one if needed. */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** Hidden input field with the CSRF token — drop into every form. */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/** Verify a submitted CSRF token in constant time. */
function verify_csrf(?string $token): bool
{
    return !empty($token)
        && !empty($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

/** Abort the request if the CSRF token is missing/invalid. */
function require_csrf(): void
{
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        http_response_code(419);
        exit('Your session expired or the request could not be verified. Please go back and try again.');
    }
}

/* ---------- Flash messages ---------- */

function flash_set(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function flash_get(): array
{
    $flash = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flash;
}

/* ---------- Authentication ---------- */

function is_logged_in(): bool
{
    return !empty($_SESSION['admin_id']);
}

/** Guard for admin pages — redirects to login if not authenticated. */
function require_login(): void
{
    // Auto-logout after inactivity.
    if (is_logged_in()) {
        $idle = time() - ($_SESSION['last_activity'] ?? time());
        if ($idle > SESSION_TIMEOUT_MIN * 60) {
            logout_admin();
            flash_set('info', 'You were signed out after a period of inactivity.');
            redirect('admin/login.php');
        }
        $_SESSION['last_activity'] = time();
    }
    if (!is_logged_in()) {
        redirect('admin/login.php');
    }

    // Tenant-context enforcement: a company admin's session is only valid on
    // its own subdomain; a super-admin's only in the super-admin zone. This
    // stops a session from being replayed in the wrong context.
    $role = $_SESSION['admin_role'] ?? 'company_admin';
    $ok = ($role === 'super_admin')
        ? is_super_admin_zone()
        : (!is_super_admin_zone() && (int) ($_SESSION['admin_tenant_id'] ?? 0) === current_tenant_id());
    if (!$ok) {
        logout_admin();
        flash_set('error', 'Please sign in for this site.');
        redirect('admin/login.php');
    }
}

/** Guard for platform-owner-only pages. */
function require_super_admin(): void
{
    require_login();
    if (($_SESSION['admin_role'] ?? '') !== 'super_admin') {
        http_response_code(403);
        exit('Forbidden.');
    }
}

function current_admin_role(): string
{
    return (string) ($_SESSION['admin_role'] ?? 'company_admin');
}

function login_admin(array $admin): void
{
    // Prevent session fixation — new ID on privilege change.
    session_regenerate_id(true);
    $_SESSION['admin_id']        = (int) $admin['id'];
    $_SESSION['admin_username']  = $admin['username'];
    $_SESSION['admin_role']      = $admin['role'] ?? 'company_admin';
    $_SESSION['admin_tenant_id'] = isset($admin['tenant_id']) ? (int) $admin['tenant_id'] : null;
    $_SESSION['last_activity']   = time();
}

function logout_admin(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

function current_admin_id(): int
{
    return (int) ($_SESSION['admin_id'] ?? 0);
}

/* ---------- Brute-force / rate limiting for login ---------- */

function client_ip(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/** How many failed attempts from this IP inside the window. */
function recent_failed_attempts(string $ip): int
{
    $stmt = db()->prepare(
        'SELECT COUNT(*) FROM login_attempts
         WHERE ip_address = ? AND success = 0
           AND attempted_at > (NOW() - INTERVAL ? MINUTE)'
    );
    $stmt->execute([$ip, LOGIN_WINDOW_MIN]);
    return (int) $stmt->fetchColumn();
}

function record_login_attempt(string $ip, ?string $username, bool $success): void
{
    $stmt = db()->prepare(
        'INSERT INTO login_attempts (ip_address, username, success)
         VALUES (?, ?, ?)'
    );
    $stmt->execute([$ip, $username, $success ? 1 : 0]);
}

function is_locked_out(string $ip): bool
{
    return recent_failed_attempts($ip) >= LOGIN_MAX_ATTEMPTS;
}

/** Build a URL preserving current GET params, applying overrides (null value = remove key). */
function query_url(array $overrides): string
{
    $path = strtok($_SERVER['REQUEST_URI'], '?');
    $q = $_GET;
    foreach ($overrides as $k => $v) {
        if ($v === null) unset($q[$k]); else $q[$k] = $v;
    }
    return $path . '?' . http_build_query($q);
}

/* ---------- Multi-tenancy: resolve the current company from the subdomain ---------- */

/**
 * The tenant (company) this request belongs to, resolved from the Host header.
 * acme.APP_DOMAIN -> the "acme" tenant. The bare/root domain, localhost, or an
 * unknown subdomain fall back to the default tenant (id 1) for now — proper
 * platform-root + "board not found" handling arrives with the signup/platform step.
 * Returns the tenant row (always non-null once the DB has a default tenant).
 */
/**
 * Resolve the company for this request from the Host header.
 * Returns: the tenant row (a resolved subdomain, or localhost in dev);
 *          null  = the platform root domain (no company);
 *          false = a subdomain that doesn't match any tenant.
 */
function current_tenant()
{
    static $computed = false;
    static $tenant = null;
    if ($computed) return $tenant;
    $computed = true;

    $host = strtolower(preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'] ?? ''));
    $base = strtolower(APP_DOMAIN);

    if ($host === $base || $host === 'www.' . $base) {
        $tenant = null;                       // platform root
        return $tenant;
    }
    if ($host === 'localhost' || $host === '127.0.0.1') {
        $tenant = db()->query("SELECT * FROM tenants WHERE id = 1 LIMIT 1")->fetch() ?: null;
        return $tenant;                        // dev convenience: the default tenant
    }
    if (str_ends_with($host, '.' . $base)) {
        $sub = substr($host, 0, -strlen('.' . $base));
        if ($sub !== '' && $sub !== 'www') {
            $stmt = db()->prepare("SELECT * FROM tenants WHERE subdomain = ? LIMIT 1");
            $stmt->execute([$sub]);
            $tenant = $stmt->fetch() ?: false; // false = unknown subdomain
            return $tenant;
        }
    }
    $tenant = null;                            // unrecognized host → treat as platform
    return $tenant;
}

/** The current tenant's id (0 when there is no concrete tenant — platform/unknown). */
function current_tenant_id(): int
{
    $t = current_tenant();
    return is_array($t) ? (int) $t['id'] : 0;
}

/**
 * Guard for public tenant pages. On the platform root shows the platform home;
 * for an unknown or not-yet-active subdomain shows a themed page; otherwise
 * returns the active tenant row.
 */
function require_active_tenant(): array
{
    if (is_platform_context()) {
        require dirname(__DIR__) . '/platform-home.php';
        exit;
    }
    $t = current_tenant();
    if (is_array($t) && ($t['status'] ?? '') === 'active') {
        return $t;
    }
    http_response_code(404);
    $notFound = ($t === false);   // used by the view
    require dirname(__DIR__) . '/tenant-unavailable.php';
    exit;
}

/** True when the request is for the platform root domain (no company subdomain). */
function is_platform_context(): bool
{
    $host = strtolower(preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'] ?? ''));
    $base = strtolower(APP_DOMAIN);
    return $host === $base || $host === 'www.' . $base;
}

/**
 * The "super-admin zone" is where the platform owner signs in and works:
 * the root domain, plus localhost/127.0.0.1 for local development. Company
 * admins sign in on their own subdomain instead.
 */
function is_super_admin_zone(): bool
{
    $host = strtolower(preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'] ?? ''));
    return is_platform_context() || $host === 'localhost' || $host === '127.0.0.1';
}

/* ---------- Saved/bookmarked jobs (cookie-based, no visitor accounts) ---------- */

const SAVED_JOBS_COOKIE = 'kastana_saved';

/** IDs of jobs the current visitor has bookmarked (from cookie). */
function saved_job_ids(): array
{
    $raw = $_COOKIE[SAVED_JOBS_COOKIE] ?? '';
    $ids = array_filter(array_map('intval', explode(',', $raw)));
    return array_values(array_unique($ids));
}

function is_job_saved(int $jobId): bool
{
    return in_array($jobId, saved_job_ids(), true);
}

/** Toggle a job in the saved-jobs cookie. */
function toggle_saved_job(int $jobId, bool $save): void
{
    $ids = saved_job_ids();
    $ids = $save ? array_unique(array_merge($ids, [$jobId])) : array_diff($ids, [$jobId]);
    $ids = array_slice(array_values($ids), -200); // cap growth
    setcookie(SAVED_JOBS_COOKIE, implode(',', $ids), [
        'expires'  => time() + 60 * 60 * 24 * 180,
        'path'     => rtrim(BASE_URL, '/') . '/',
        'secure'   => USE_HTTPS,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

/* ---------- Admin activity log ---------- */

/** Record an admin action against a posting (job_id may be null after deletion). */
function log_activity(?int $jobId, string $action, ?string $details = null): void
{
    db()->prepare("INSERT INTO activity_log (tenant_id, admin_id, job_id, action, details) VALUES (?,?,?,?,?)")
        ->execute([current_tenant_id(), current_admin_id() ?: null, $jobId, $action, $details]);
}

/* ---------- Formatting ---------- */

/** Human-friendly salary range, or null if none set. */
function format_salary(?int $min, ?int $max, string $currency): ?string
{
    if (!$min && !$max) {
        return null;
    }
    $fmt = fn($n) => number_format((int) $n);
    if ($min && $max) {
        return "{$currency} " . $fmt($min) . ' – ' . $fmt($max);
    }
    return "{$currency} " . $fmt($min ?: $max) . ($min ? '+' : '');
}

/** "3 days ago" style relative time. */
function time_ago(string $datetime): string
{
    $ts = strtotime($datetime);
    $diff = time() - $ts;
    if ($diff < 60)      return t('tm_now');
    if ($diff < 3600)    return t('tm_min', (int) floor($diff / 60));
    if ($diff < 86400)   return t('tm_hr',  (int) floor($diff / 3600));
    if ($diff < 604800)  return t('tm_day', (int) floor($diff / 86400));
    if ($diff < 2592000) return t('tm_wk',  (int) floor($diff / 604800));
    return date('M j, Y', $ts);
}

/**
 * Render user-submitted multi-line text safely:
 * escapes HTML, then turns line breaks into paragraphs/breaks.
 */
function render_text(?string $text): string
{
    $safe = e($text);
    $safe = preg_replace("/\n{2,}/", '</p><p>', $safe);
    $safe = nl2br($safe);
    return '<p>' . $safe . '</p>';
}
