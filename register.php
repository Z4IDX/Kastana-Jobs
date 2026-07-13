<?php
/**
 * Employer registration (open). Creates an active employer account and signs in.
 */
require_once __DIR__ . '/config/config.php';

if (is_employer_logged_in()) redirect('employer/dashboard.php');

$errors = [];
$old = ['company_name' => '', 'email' => '', 'phone' => '', 'website' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    if (input($_POST, 'website_url') !== '') redirect('register.php'); // honeypot

    $old['company_name'] = input($_POST, 'company_name');
    $old['email']        = input($_POST, 'email');
    $old['phone']        = input($_POST, 'phone');
    $old['website']      = input($_POST, 'website');
    $pass  = (string) ($_POST['password'] ?? '');
    $pass2 = (string) ($_POST['password2'] ?? '');

    if (mb_strlen($old['company_name']) < 2) $errors[] = t('err_emp_company');
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) $errors[] = t('err_emp_email');
    if ($old['website'] !== '' && !filter_var($old['website'], FILTER_VALIDATE_URL)) $errors[] = t('err_website');
    if (strlen($pass) < 8) $errors[] = t('err_emp_pass');
    if ($pass !== $pass2) $errors[] = t('err_emp_pass_match');

    if (!$errors) {
        $chk = db()->prepare("SELECT id FROM employers WHERE email = ?");
        $chk->execute([$old['email']]);
        if ($chk->fetch()) {
            $errors[] = t('err_emp_email_taken');
        } else {
            // Company approval depends on the admin's moderation mode. When companies are
            // moderated, new accounts start 'pending'; otherwise they're active immediately.
            $approveCompanies = in_array(moderation_mode(), ['both', 'companies'], true);
            $newStatus = $approveCompanies ? 'pending' : 'active';
            db()->prepare(
                "INSERT INTO employers (company_name, email, password_hash, phone, website, status)
                 VALUES (?,?,?,?,?,?)"
            )->execute([
                $old['company_name'], $old['email'],
                password_hash($pass, PASSWORD_BCRYPT, ['cost' => 12]),
                $old['phone'] ?: null, $old['website'] ?: null, $newStatus,
            ]);
            $id = (int) db()->lastInsertId();
            login_employer(['id' => $id, 'company_name' => $old['company_name']]);
            flash_set('info', $approveCompanies ? t('emp_pending_notice') : t('emp_welcome'));
            redirect('employer/dashboard.php');
        }
    }
}

$page_title = t('emp_register_title');
require __DIR__ . '/includes/header.php';
$v = fn($k) => e($old[$k] ?? '');
?>
<section class="form-page wrap">
  <div class="form-shell">
    <div class="form-intro">
      <span class="eyebrow" style="justify-content:center"><?= e(t('f_intro_eyebrow')) ?></span>
      <h1><?= e(t('emp_register_title')) ?></h1>
      <p><?= e(t('emp_register_lede')) ?></p>
    </div>

    <?php if ($errors): ?>
      <div class="alert alert--error"><ul class="error-list"><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>

    <div class="form-card">
      <form method="post" action="<?= url('register.php') ?>" novalidate>
        <?= csrf_field() ?>
        <div class="hp-field" aria-hidden="true"><label>Leave empty<input type="text" name="website_url" tabindex="-1" autocomplete="off"></label></div>

        <div class="field"><label><?= e(t('emp_company')) ?> <span class="req">*</span></label><input type="text" name="company_name" value="<?= $v('company_name') ?>" maxlength="150" required></div>
        <div class="field"><label><?= e(t('emp_email')) ?> <span class="req">*</span></label><input type="email" name="email" value="<?= $v('email') ?>" maxlength="150" required></div>
        <div class="field-row">
          <div class="field"><label><?= e(t('emp_phone')) ?> <span class="hint"><?= e(t('f_optional')) ?></span></label><input type="tel" name="phone" value="<?= $v('phone') ?>" maxlength="40" dir="ltr"></div>
          <div class="field"><label><?= e(t('emp_website')) ?> <span class="hint"><?= e(t('f_optional')) ?></span></label><input type="url" name="website" value="<?= $v('website') ?>" placeholder="https://" dir="ltr"></div>
        </div>
        <div class="field-row">
          <div class="field"><label><?= e(t('emp_password')) ?> <span class="req">*</span> <span class="hint">(8+)</span></label><input type="password" name="password" required></div>
          <div class="field"><label><?= e(t('emp_password2')) ?> <span class="req">*</span></label><input type="password" name="password2" required></div>
        </div>

        <button type="submit" class="btn btn--primary btn--block"><?= e(t('emp_register_btn')) ?></button>
      </form>
      <p style="text-align:center;margin-top:1rem;font-size:0.9rem"><a href="<?= url('login.php') ?>"><?= e(t('emp_have_account')) ?></a></p>
    </div>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
