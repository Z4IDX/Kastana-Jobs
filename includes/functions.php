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

/* ---------- Authentication: platform admins (staff) ---------- */

function is_logged_in(): bool
{
    return !empty($_SESSION['admin_id']);
}

/** Guard for admin pages — redirects to login if not authenticated. */
function require_login(): void
{
    if (is_logged_in()) {
        $idle = time() - ($_SESSION['last_activity'] ?? time());
        if ($idle > SESSION_TIMEOUT_MIN * 60) {
            logout_session();
            flash_set('info', t('ad_idle_logout'));
            redirect('admin/login.php');
        }
        $_SESSION['last_activity'] = time();
    }
    if (!is_logged_in()) {
        redirect('admin/login.php');
    }
}

function login_admin(array $admin): void
{
    session_regenerate_id(true);           // prevent session fixation
    $_SESSION = [];                        // never mix an admin + employer session
    $_SESSION['admin_id']       = (int) $admin['id'];
    $_SESSION['admin_username'] = $admin['username'];
    $_SESSION['last_activity']  = time();
}

function current_admin_id(): int
{
    return (int) ($_SESSION['admin_id'] ?? 0);
}

/* ---------- Authentication: employers (self-service posters) ---------- */

function is_employer_logged_in(): bool
{
    return !empty($_SESSION['employer_id']);
}

/** Guard for employer pages — redirects to the employer login. */
function require_employer(): void
{
    if (is_employer_logged_in()) {
        $idle = time() - ($_SESSION['last_activity'] ?? time());
        if ($idle > SESSION_TIMEOUT_MIN * 60) {
            logout_session();
            flash_set('info', t('ad_idle_logout'));
            redirect('login.php');
        }
        $_SESSION['last_activity'] = time();
    }
    if (!is_employer_logged_in()) {
        redirect('login.php');
    }
}

function login_employer(array $employer): void
{
    session_regenerate_id(true);
    $_SESSION = [];                        // never mix an employer + admin session
    $_SESSION['employer_id']   = (int) $employer['id'];
    $_SESSION['employer_name'] = $employer['company_name'];
    $_SESSION['last_activity'] = time();
}

function current_employer_id(): int
{
    return (int) ($_SESSION['employer_id'] ?? 0);
}

/* ---------- Shared logout ---------- */

/** Destroy the whole session (used by both admin and employer logout). */
function logout_session(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

/** Back-compat alias. */
function logout_admin(): void { logout_session(); }

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

/* ---------- Employer notifications (on-site, no email) ---------- */

/** Queue an on-site notification for an employer about one of their postings. */
function notify_employer(int $employerId, ?int $jobId, string $type, ?string $title): void
{
    if ($employerId <= 0) return;
    db()->prepare("INSERT INTO notifications (employer_id, job_id, type, title) VALUES (?,?,?,?)")
        ->execute([$employerId, $jobId, $type, $title]);
}

/** Unread notifications for an employer (most recent first). */
function unread_notifications(int $employerId): array
{
    $stmt = db()->prepare("SELECT * FROM notifications WHERE employer_id = ? AND is_read = 0 ORDER BY created_at DESC");
    $stmt->execute([$employerId]);
    return $stmt->fetchAll();
}

/** Mark all of an employer's notifications as read. */
function mark_notifications_read(int $employerId): void
{
    db()->prepare("UPDATE notifications SET is_read = 1 WHERE employer_id = ? AND is_read = 0")->execute([$employerId]);
}

/* ---------- Admin activity log ---------- */

/** Record an admin action against a posting (job_id may be null after deletion). */
function log_activity(?int $jobId, string $action, ?string $details = null): void
{
    db()->prepare("INSERT INTO activity_log (admin_id, job_id, action, details) VALUES (?,?,?,?)")
        ->execute([current_admin_id() ?: null, $jobId, $action, $details]);
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
