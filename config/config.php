<?php
/**
 * Kastana Jobs — Core configuration
 * -------------------------------------------------
 * Edit the DATABASE and APP settings below to match your XAMPP setup.
 * Everything else is wired up for you.
 */

/* ------------------------------------------------------------
 *  Machine-specific overrides written by the setup wizard
 *  (install.php). If present, the values it defines win; the
 *  defaults below fill in anything it didn't set. This file is
 *  git-ignored so real credentials never get committed.
 * ------------------------------------------------------------ */
if (is_file(__DIR__ . '/config.local.php')) {
    require __DIR__ . '/config.local.php';
}

/* ============================================================
 *  1. DATABASE  (XAMPP defaults: user "root", empty password)
 * ============================================================ */
defined('DB_HOST')    || define('DB_HOST', '127.0.0.1');
defined('DB_NAME')    || define('DB_NAME', 'kastana_jobs');
defined('DB_USER')    || define('DB_USER', 'root');
defined('DB_PASS')    || define('DB_PASS', '');   // XAMPP default is empty. Set a password in production!
defined('DB_CHARSET') || define('DB_CHARSET', 'utf8mb4');

/* ============================================================
 *  2. APP & BRANDING
 *     White-label: change these (or run install.php) to rebrand.
 *     (The logo is assets/img/logo.png — replace that file too.)
 * ============================================================ */
defined('APP_NAME')    || define('APP_NAME', 'Kastana Jobs');   // brand name shown across the site
defined('APP_TAGLINE') || define('APP_TAGLINE', 'A curated board of hand-reviewed roles from great companies.');
defined('BRAND_URL')   || define('BRAND_URL', '');              // footer brand link; '' = link to the home page

// Base URL of the site (no trailing slash). '' if served from the web root.
defined('BASE_URL')  || define('BASE_URL', '/kastana-jobs');

// Set to true only when running over HTTPS. Enables secure cookies + HSTS.
defined('USE_HTTPS') || define('USE_HTTPS', false);

// Login lockout: max failed attempts within the window before a temporary block.
define('LOGIN_MAX_ATTEMPTS', 5);
define('LOGIN_WINDOW_MIN', 15);     // minutes
define('SESSION_TIMEOUT_MIN', 60);  // auto-logout after inactivity

// Image uploads
define('MAX_UPLOAD_BYTES', 2 * 1024 * 1024);   // 2 MB per image

/* ============================================================
 *  3. ERROR REPORTING
 *     Keep display off in production; log instead.
 * ============================================================ */
error_reporting(E_ALL);
ini_set('display_errors', '0');           // never show raw errors to visitors
ini_set('log_errors', '1');

/* ============================================================
 *  4. SECURE SESSION
 * ============================================================ */
if (session_status() === PHP_SESSION_NONE) {
    session_name('KASTANA_SID');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => USE_HTTPS,      // only sent over HTTPS when enabled
        'httponly' => true,           // not readable by JavaScript (blocks XSS theft)
        'samesite' => 'Strict',       // blocks CSRF via cross-site requests
    ]);
    ini_set('session.use_strict_mode', '1');
    session_start();
}

/* ============================================================
 *  5. SECURITY HEADERS (sent on every page that includes config)
 * ============================================================ */
header('X-Frame-Options: DENY');                     // clickjacking protection
header('X-Content-Type-Options: nosniff');           // stop MIME sniffing
header('Referrer-Policy: strict-origin-when-cross-origin');
header('X-XSS-Protection: 1; mode=block');
header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
// Content Security Policy — allows Google Fonts + our own assets only.
header(
    "Content-Security-Policy: default-src 'self'; " .
    "img-src 'self' data:; " .
    "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; " .
    "font-src 'self' https://fonts.gstatic.com; " .
    "script-src 'self'; " .
    "form-action 'self'; base-uri 'self'; frame-ancestors 'none';"
);
if (USE_HTTPS) {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

/* ============================================================
 *  6. DATABASE CONNECTION (PDO, prepared-statement ready)
 * ============================================================ */
function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,   // real prepared statements
    ];
    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        error_log('DB connection failed: ' . $e->getMessage());
        http_response_code(500);
        exit('The service is temporarily unavailable. Please try again later.');
    }
    return $pdo;
}

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/i18n.php';
require_once __DIR__ . '/../includes/upload.php';
