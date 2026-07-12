<?php
require_once __DIR__ . '/../config/config.php';
require_employer();

$empId = current_employer_id();
$stmt = db()->prepare("SELECT * FROM employers WHERE id = ?");
$stmt->execute([$empId]);
$emp = $stmt->fetch();
if (!$emp) { logout_session(); redirect('login.php'); }

$errors = [];
$old = [
    'company_name' => $emp['company_name'],
    'email'        => $emp['email'],
    'phone'        => $emp['phone'] ?? '',
    'website'      => $emp['website'] ?? '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $form = input($_POST, 'form');

    if ($form === 'profile') {
        $old['company_name'] = input($_POST, 'company_name');
        $old['email']        = input($_POST, 'email');
        $old['phone']        = input($_POST, 'phone');
        $old['website']      = input($_POST, 'website');

        if (mb_strlen($old['company_name']) < 2) $errors[] = t('err_emp_company');
        if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) $errors[] = t('err_emp_email');
        if ($old['website'] !== '' && !filter_var($old['website'], FILTER_VALIDATE_URL)) $errors[] = t('err_website');
        if ($old['phone'] !== '' && !preg_match('/^[0-9+\-\s()]{6,40}$/', $old['phone'])) $errors[] = t('err_app_phone');
        if (!$errors) {
            $chk = db()->prepare("SELECT id FROM employers WHERE email = ? AND id <> ?");
            $chk->execute([$old['email'], $empId]);
            if ($chk->fetch()) $errors[] = t('err_emp_email_taken');
        }
        if (!$errors) {
            db()->prepare("UPDATE employers SET company_name=?, email=?, phone=?, website=? WHERE id=?")
                ->execute([$old['company_name'], $old['email'], $old['phone'] ?: null, $old['website'] ?: null, $empId]);
            $_SESSION['employer_name'] = $old['company_name'];
            flash_set('success', t('emp_profile_saved'));
            redirect('employer/account.php');
        }
    } elseif ($form === 'password') {
        $cur  = (string) ($_POST['current_password'] ?? '');
        $new  = (string) ($_POST['new_password'] ?? '');
        $new2 = (string) ($_POST['new_password2'] ?? '');

        if (!password_verify($cur, $emp['password_hash'])) $errors[] = t('err_current_password');
        if (strlen($new) < 8) $errors[] = t('err_emp_pass');
        if ($new !== $new2) $errors[] = t('err_emp_pass_match');
        if (!$errors) {
            db()->prepare("UPDATE employers SET password_hash = ? WHERE id = ?")
                ->execute([password_hash($new, PASSWORD_BCRYPT, ['cost' => 12]), $empId]);
            flash_set('success', t('emp_password_changed'));
            redirect('employer/account.php');
        }
    }
}

$page_title = t('emp_account_title');
require __DIR__ . '/../includes/header.php';
$v = fn($k) => e($old[$k] ?? '');
?>
<section class="form-page wrap">
  <div class="form-shell">
    <div class="form-intro">
      <span class="eyebrow" style="justify-content:center"><?= e(t('emp_account')) ?></span>
      <h1><?= e(t('emp_account_title')) ?></h1>
      <p><a href="<?= url('employer/dashboard.php') ?>"><?= e(t('back_all')) ?></a></p>
    </div>

    <?php foreach (flash_get() as $f): ?>
      <div class="alert alert--<?= e($f['type']) ?>"><?= e($f['message']) ?></div>
    <?php endforeach; ?>
    <?php if ($errors): ?>
      <div class="alert alert--error"><ul class="error-list"><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>

    <div class="form-card">
      <span class="eyebrow" style="margin-bottom:0.8rem"><?= e(t('emp_profile_section')) ?></span>
      <form method="post" action="<?= url('employer/account.php') ?>" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="form" value="profile">
        <div class="field"><label><?= e(t('emp_company')) ?> <span class="req">*</span></label><input type="text" name="company_name" value="<?= $v('company_name') ?>" maxlength="150" required></div>
        <div class="field"><label><?= e(t('emp_email')) ?> <span class="req">*</span></label><input type="email" name="email" value="<?= $v('email') ?>" maxlength="150" required dir="ltr"></div>
        <div class="field-row">
          <div class="field"><label><?= e(t('emp_phone')) ?> <span class="hint"><?= e(t('f_optional')) ?></span></label><input type="tel" name="phone" value="<?= $v('phone') ?>" maxlength="40" dir="ltr"></div>
          <div class="field"><label><?= e(t('emp_website')) ?> <span class="hint"><?= e(t('f_optional')) ?></span></label><input type="url" name="website" value="<?= $v('website') ?>" placeholder="https://" dir="ltr"></div>
        </div>
        <button type="submit" class="btn btn--primary btn--block"><?= e(t('emp_save_profile')) ?></button>
      </form>
    </div>

    <div class="form-card" style="margin-top:1.2rem">
      <span class="eyebrow" style="margin-bottom:0.8rem"><?= e(t('emp_password_section')) ?></span>
      <form method="post" action="<?= url('employer/account.php') ?>" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="form" value="password">
        <div class="field"><label><?= e(t('emp_current_password')) ?> <span class="req">*</span></label><input type="password" name="current_password" required></div>
        <div class="field-row">
          <div class="field"><label><?= e(t('emp_new_password')) ?> <span class="req">*</span> <span class="hint">(8+)</span></label><input type="password" name="new_password" required></div>
          <div class="field"><label><?= e(t('emp_password2')) ?> <span class="req">*</span></label><input type="password" name="new_password2" required></div>
        </div>
        <button type="submit" class="btn btn--primary btn--block"><?= e(t('emp_change_password')) ?></button>
      </form>
    </div>
  </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
