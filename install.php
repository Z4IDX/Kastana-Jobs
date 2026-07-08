<?php
/**
 * Kastana Jobs — Setup wizard
 * ---------------------------------------------------------------
 * A one-time, self-contained installer. It checks prerequisites,
 * creates the database, imports the schema, creates the first admin,
 * and writes config/config.local.php (which config.php then loads).
 *
 * SECURITY: delete this file after a successful install. It refuses
 * to run once config/config.local.php exists, but deleting it is best.
 */

declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '1'); // this is a local, first-run tool

$LOCAL_CONFIG = __DIR__ . '/config/config.local.php';
$SCHEMA_FILE  = __DIR__ . '/config/database.sql';

// ---- Already installed? Refuse to run. -------------------------------------
$alreadyInstalled = is_file($LOCAL_CONFIG);

// ---- Minimal own session for CSRF (installer doesn't load config.php) -------
session_name('KASTANA_INSTALL');
session_start();
if (empty($_SESSION['itoken'])) {
    $_SESSION['itoken'] = bin2hex(random_bytes(32));
}
$token = $_SESSION['itoken'];

// ---- Prerequisite checks ----------------------------------------------------
function check(bool $ok, string $label, bool $required = true): array
{
    return ['ok' => $ok, 'label' => $label, 'required' => $required];
}
$checks = [
    check(PHP_VERSION_ID >= 80000, 'PHP 8.0 or newer (found ' . PHP_VERSION . ')'),
    check(extension_loaded('pdo_mysql'), 'PDO MySQL extension'),
    check(extension_loaded('mbstring'), 'mbstring extension'),
    check(extension_loaded('fileinfo'), 'fileinfo extension'),
    check(extension_loaded('gd'), 'GD extension (thumbnails)', false),
    check(is_writable(__DIR__ . '/config'), 'config/ folder is writable'),
    check(is_dir(__DIR__ . '/uploads') && is_writable(__DIR__ . '/uploads'), 'uploads/ folder is writable', false),
    check(is_file($SCHEMA_FILE), 'config/database.sql present'),
];
$blocking = array_filter($checks, fn($c) => $c['required'] && !$c['ok']);

// ---- Handle submission ------------------------------------------------------
$errors = [];
$done = false;
$old = [
    'db_host' => '127.0.0.1', 'db_name' => 'kastana_jobs', 'db_user' => 'root', 'db_pass' => '',
    'base_url' => '/kastana-jobs', 'use_https' => false,
    'brand' => 'Kastana Jobs', 'tagline' => 'A curated board of hand-reviewed roles from great companies.',
    'admin_user' => '', 'admin_email' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$alreadyInstalled && !$blocking) {
    if (!hash_equals($token, $_POST['itoken'] ?? '')) {
        $errors[] = 'Security token mismatch — please reload and try again.';
    } else {
        foreach ($old as $k => $v) {
            if ($k === 'use_https') { $old[$k] = !empty($_POST['use_https']); continue; }
            $old[$k] = trim((string)($_POST[$k] ?? ''));
        }
        $adminPass  = (string)($_POST['admin_pass'] ?? '');
        $adminPass2 = (string)($_POST['admin_pass2'] ?? '');

        if ($old['db_name'] === '' || !preg_match('/^[A-Za-z0-9_]+$/', $old['db_name'])) $errors[] = 'Database name must be letters, numbers, or underscores.';
        if ($old['db_user'] === '') $errors[] = 'Database user is required.';
        if ($old['brand'] === '') $errors[] = 'Brand name is required.';
        if (mb_strlen($old['admin_user']) < 3) $errors[] = 'Admin username must be at least 3 characters.';
        if (!filter_var($old['admin_email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid admin email is required.';
        if (strlen($adminPass) < 8) $errors[] = 'Admin password must be at least 8 characters.';
        if ($adminPass !== $adminPass2) $errors[] = 'The two admin passwords do not match.';

        if (!$errors) {
            try {
                // 1. Connect to the server (no DB yet) and create the database.
                $opts = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true];
                $server = new PDO("mysql:host={$old['db_host']};charset=utf8mb4", $old['db_user'], $old['db_pass'], $opts);
                $server->exec("CREATE DATABASE IF NOT EXISTS `{$old['db_name']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                $server->exec("USE `{$old['db_name']}`");

                // 2. Import the schema (strip the file's own CREATE DATABASE / USE so it targets our DB).
                $sql = file_get_contents($SCHEMA_FILE);
                $sql = preg_replace('/CREATE\s+DATABASE.*?;/is', '', $sql, 1);
                $sql = preg_replace('/USE\s+`?\w+`?\s*;/i', '', $sql, 1);
                $server->exec($sql);

                // 3. Replace the seeded default admin with the one just entered.
                $server->exec('DELETE FROM `admins`');
                $ins = $server->prepare('INSERT INTO `admins` (username, email, password_hash) VALUES (?,?,?)');
                $ins->execute([$old['admin_user'], $old['admin_email'], password_hash($adminPass, PASSWORD_BCRYPT, ['cost' => 12])]);

                // 4. Write config/config.local.php.
                $x = fn($v) => var_export($v, true);
                $body = "<?php\n"
                    . "/* Generated by install.php on " . date('Y-m-d H:i') . ". Do NOT commit real credentials. */\n"
                    . "define('DB_HOST', {$x($old['db_host'])});\n"
                    . "define('DB_NAME', {$x($old['db_name'])});\n"
                    . "define('DB_USER', {$x($old['db_user'])});\n"
                    . "define('DB_PASS', {$x($old['db_pass'])});\n"
                    . "define('APP_NAME', {$x($old['brand'])});\n"
                    . "define('APP_TAGLINE', {$x($old['tagline'])});\n"
                    . "define('BASE_URL', {$x(rtrim($old['base_url'], '/'))});\n"
                    . "define('USE_HTTPS', " . ($old['use_https'] ? 'true' : 'false') . ");\n";
                if (file_put_contents($LOCAL_CONFIG, $body) === false) {
                    throw new RuntimeException('Could not write config/config.local.php (check folder permissions).');
                }
                $done = true;
                $alreadyInstalled = true; // lock the form now that we're installed
            } catch (Throwable $e) {
                $errors[] = 'Install failed: ' . $e->getMessage();
            }
        }
    }
}

$base = rtrim($old['base_url'], '/');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex, nofollow">
  <title>Setup · <?= htmlspecialchars($old['brand'] ?: 'Kastana Jobs') ?></title>
  <link rel="stylesheet" href="assets/css/admin.css">
  <style>
    .install-card{width:min(100%,620px)}
    .checks{list-style:none;padding:0;margin:0 0 1.4rem;font-size:0.9rem}
    .checks li{display:flex;gap:0.5rem;padding:0.25rem 0}
    .checks .ok{color:var(--green)} .checks .bad{color:var(--red)} .checks .warn{color:var(--amber)}
  </style>
</head>
<body>
<div class="login-wrap">
  <div class="login-card install-card">
    <div class="admin-brand"><?= htmlspecialchars($old['brand'] ?: 'Kastana Jobs') ?><span class="dot">.</span>Setup</div>

    <?php if ($done): ?>
      <h1>You're all set 🎉</h1>
      <p class="muted">Your site is installed and ready.</p>
      <div class="alert alert--success" style="margin-bottom:1rem">
        <strong>Important:</strong> delete <code>install.php</code> now so it can't be run again.
      </div>
      <a class="btn btn--primary btn--block" href="<?= htmlspecialchars($base) ?>/index.php">Go to the site</a>
      <a class="btn btn--ghost btn--block" style="margin-top:0.6rem" href="<?= htmlspecialchars($base) ?>/admin/login.php">Admin login</a>

    <?php elseif ($alreadyInstalled): ?>
      <h1>Already installed</h1>
      <p class="muted">A <code>config/config.local.php</code> already exists, so setup is locked.</p>
      <div class="alert alert--info">To re-run setup, delete <code>config/config.local.php</code> first. Otherwise, delete <code>install.php</code>.</div>
      <a class="btn btn--ghost btn--block" style="margin-top:0.8rem" href="<?= htmlspecialchars($base) ?>/index.php">Go to the site</a>

    <?php else: ?>
      <h1>Set up your job board</h1>
      <p class="muted">A few details and you're live. This runs once.</p>

      <ul class="checks">
        <?php foreach ($checks as $c): ?>
          <li>
            <span class="<?= $c['ok'] ? 'ok' : ($c['required'] ? 'bad' : 'warn') ?>"><?= $c['ok'] ? '✓' : ($c['required'] ? '✕' : '!') ?></span>
            <span><?= htmlspecialchars($c['label']) ?><?= (!$c['ok'] && !$c['required']) ? ' — optional' : '' ?></span>
          </li>
        <?php endforeach; ?>
      </ul>

      <?php if ($blocking): ?>
        <div class="alert alert--error">Please fix the required items above (✕), then reload this page.</div>
      <?php endif; ?>
      <?php if ($errors): ?>
        <div class="alert alert--error"><ul style="margin:0.3rem 0 0 1rem;padding:0"><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul></div>
      <?php endif; ?>

      <form method="post" novalidate <?= $blocking ? 'style="opacity:.5;pointer-events:none"' : '' ?>>
        <input type="hidden" name="itoken" value="<?= htmlspecialchars($token) ?>">

        <div class="edit-section-title">Database</div>
        <div class="field-row">
          <div class="field"><label>Host</label><input name="db_host" value="<?= htmlspecialchars($old['db_host']) ?>"></div>
          <div class="field"><label>Database name</label><input name="db_name" value="<?= htmlspecialchars($old['db_name']) ?>"></div>
        </div>
        <div class="field-row">
          <div class="field"><label>User</label><input name="db_user" value="<?= htmlspecialchars($old['db_user']) ?>"></div>
          <div class="field"><label>Password</label><input type="password" name="db_pass" value="<?= htmlspecialchars($old['db_pass']) ?>"></div>
        </div>

        <div class="edit-section-title">Site</div>
        <div class="field"><label>Brand name</label><input name="brand" value="<?= htmlspecialchars($old['brand']) ?>" required></div>
        <div class="field"><label>Tagline</label><input name="tagline" value="<?= htmlspecialchars($old['tagline']) ?>"></div>
        <div class="field-row">
          <div class="field"><label>Base URL <span class="hint">(no trailing slash; '' = web root)</span></label><input name="base_url" value="<?= htmlspecialchars($old['base_url']) ?>"></div>
          <div class="field" style="display:flex;align-items:center;gap:0.5rem;margin-top:1.9rem">
            <input type="checkbox" name="use_https" id="use_https" value="1" <?= $old['use_https'] ? 'checked' : '' ?> style="width:auto">
            <label for="use_https" style="margin:0">Served over HTTPS</label>
          </div>
        </div>

        <div class="edit-section-title">Admin account</div>
        <div class="field-row">
          <div class="field"><label>Username</label><input name="admin_user" value="<?= htmlspecialchars($old['admin_user']) ?>" required></div>
          <div class="field"><label>Email</label><input type="email" name="admin_email" value="<?= htmlspecialchars($old['admin_email']) ?>" required></div>
        </div>
        <div class="field-row">
          <div class="field"><label>Password <span class="hint">(min 8 chars)</span></label><input type="password" name="admin_pass" required></div>
          <div class="field"><label>Confirm password</label><input type="password" name="admin_pass2" required></div>
        </div>

        <button type="submit" class="btn btn--primary btn--block">Install</button>
      </form>
    <?php endif; ?>
  </div>
</div>
</body>
</html>
