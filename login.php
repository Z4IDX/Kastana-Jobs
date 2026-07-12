<?php
/**
 * Employer sign-in. Rate-limited, constant-time password verification.
 */
require_once __DIR__ . '/config/config.php';

if (is_employer_logged_in()) redirect('employer/dashboard.php');

$error = '';
$ip = client_ip();
$old = ['email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    if (is_locked_out($ip)) {
        $error = t('err_locked', LOGIN_WINDOW_MIN);
    } else {
        $old['email'] = input($_POST, 'email');
        $password = (string) ($_POST['password'] ?? '');

        // 'pending' accounts can sign in (to see their dashboard) but can't post; 'suspended' cannot sign in.
        $stmt = db()->prepare("SELECT * FROM employers WHERE email = ? AND status IN ('active','pending') LIMIT 1");
        $stmt->execute([$old['email']]);
        $employer = $stmt->fetch();

        // Constant-time verify to avoid leaking whether the email exists.
        $dummyHash = '$2y$12$WwG9X326a6h/1dM7stoVzuclq3Br0NG08C5vzlT4mbmeXDrCjkQLi';
        $hash = $employer['password_hash'] ?? $dummyHash;

        if ($employer && password_verify($password, $hash)) {
            record_login_attempt($ip, $old['email'], true);
            if (password_needs_rehash($hash, PASSWORD_BCRYPT, ['cost' => 12])) {
                db()->prepare('UPDATE employers SET password_hash = ? WHERE id = ?')
                    ->execute([password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]), $employer['id']]);
            }
            db()->prepare('UPDATE employers SET last_login = NOW() WHERE id = ?')->execute([$employer['id']]);
            login_employer($employer);
            redirect('employer/dashboard.php');
        } else {
            password_verify($password, $dummyHash); // equalize timing
            record_login_attempt($ip, $old['email'], false);
            $error = t('err_emp_login');
        }
    }
}

$page_title = t('emp_login_title');
require __DIR__ . '/includes/header.php';
?>
<section class="form-page wrap">
  <div class="form-shell">
    <div class="form-intro">
      <span class="eyebrow" style="justify-content:center"><?= e(t('f_intro_eyebrow')) ?></span>
      <h1><?= e(t('emp_login_title')) ?></h1>
      <p><?= e(t('emp_login_lede')) ?></p>
    </div>

    <?php if ($error): ?><div class="alert alert--error"><?= e($error) ?></div><?php endif; ?>

    <div class="form-card">
      <form method="post" action="<?= url('login.php') ?>" novalidate>
        <?= csrf_field() ?>
        <div class="field"><label><?= e(t('emp_email')) ?> <span class="req">*</span></label><input type="email" name="email" value="<?= e($old['email']) ?>" required autofocus></div>
        <div class="field"><label><?= e(t('emp_password')) ?> <span class="req">*</span></label><input type="password" name="password" required></div>
        <button type="submit" class="btn btn--primary btn--block"><?= e(t('emp_login_btn')) ?></button>
      </form>
      <p style="text-align:center;margin-top:1rem;font-size:0.9rem"><a href="<?= url('register.php') ?>"><?= e(t('emp_no_account')) ?></a></p>
    </div>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
