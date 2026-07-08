<?php
require_once __DIR__ . '/../config/config.php';

// Already signed in? Go to the dashboard.
if (is_logged_in()) {
    redirect('admin/dashboard.php');
}

$error = '';
$ip = client_ip();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    if (is_locked_out($ip)) {
        $error = 'Too many failed attempts. Please wait ' . LOGIN_WINDOW_MIN . ' minutes and try again.';
    } else {
        $username = input($_POST, 'username');
        $password = (string) ($_POST['password'] ?? '');

        // Authenticate in the current context: the platform owner (super-admin,
        // no tenant) in the super-admin zone, else this subdomain's company admin.
        if (is_super_admin_zone()) {
            $stmt = db()->prepare("SELECT * FROM admins WHERE username = ? AND role = 'super_admin' AND tenant_id IS NULL LIMIT 1");
            $stmt->execute([$username]);
        } else {
            $stmt = db()->prepare("SELECT * FROM admins WHERE username = ? AND role = 'company_admin' AND tenant_id = ? LIMIT 1");
            $stmt->execute([$username, current_tenant_id()]);
        }
        $admin = $stmt->fetch();

        // password_verify is constant-time; we always run it to avoid timing leaks.
        $dummyHash = '$2y$12$WwG9X326a6h/1dM7stoVzuclq3Br0NG08C5vzlT4mbmeXDrCjkQLi';
        $hash = $admin['password_hash'] ?? $dummyHash;

        if ($admin && password_verify($password, $hash)) {
            record_login_attempt($ip, $username, true);

            // Re-hash if the algorithm/cost has since changed.
            if (password_needs_rehash($hash, PASSWORD_BCRYPT, ['cost' => 12])) {
                $newHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                db()->prepare('UPDATE admins SET password_hash = ? WHERE id = ?')
                    ->execute([$newHash, $admin['id']]);
            }

            db()->prepare('UPDATE admins SET last_login = NOW() WHERE id = ?')
                ->execute([$admin['id']]);

            login_admin($admin);
            redirect('admin/dashboard.php');
        } else {
            password_verify($password, $dummyHash); // equalize timing
            record_login_attempt($ip, $username, false);
            $remaining = max(0, LOGIN_MAX_ATTEMPTS - recent_failed_attempts($ip));
            $error = 'Incorrect username or password.'
                   . ($remaining > 0 ? " {$remaining} attempt(s) left." : '');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex, nofollow">
  <title>Sign in · <?= e(APP_NAME) ?> Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400..700&family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
  <link rel="icon" type="image/svg+xml" href="<?= url('assets/img/favicon.svg') ?>">
  <link rel="stylesheet" href="<?= url('assets/css/admin.css') ?>">
</head>
<body>
<div class="login-wrap">
  <div class="login-card">
    <div class="admin-brand"><?= e(APP_NAME) ?><span class="dot">.</span>Admin</div>
    <h1>Welcome back</h1>
    <p class="muted">Sign in to review and manage postings.</p>

    <?php foreach (flash_get() as $f): ?>
      <div class="alert alert--<?= e($f['type']) ?>"><?= e($f['message']) ?></div>
    <?php endforeach; ?>
    <?php if ($error): ?>
      <div class="alert alert--error"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" action="<?= url('admin/login.php') ?>" novalidate>
      <?= csrf_field() ?>
      <div class="field">
        <label for="username">Username</label>
        <input type="text" id="username" name="username" autocomplete="username" required autofocus>
      </div>
      <div class="field">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" autocomplete="current-password" required>
      </div>
      <button type="submit" class="btn btn--primary btn--block">Sign in</button>
    </form>
    <a href="<?= url('index.php') ?>" class="btn btn--ghost btn--block" style="margin-top:0.8rem">← Back to site</a>
  </div>
</div>
</body>
</html>
