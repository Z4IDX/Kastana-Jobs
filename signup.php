<?php
/**
 * Company signup (platform root). Creates a PENDING tenant + its first
 * company admin. The platform owner activates it later from admin/tenants.php.
 */
require_once __DIR__ . '/config/config.php';

$reserved = ['www','app','admin','api','mail','ftp','smtp','ns','ns1','ns2','test','static','assets','cdn','blog','help','support','status'];
$errors = [];
$old = ['company' => '', 'subdomain' => '', 'admin_user' => '', 'admin_email' => ''];
$done = false;
$doneSub = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    if (input($_POST, 'website_url') !== '') {          // honeypot
        flash_set('success', 'Thanks!'); redirect('signup.php');
    }
    $old['company']     = input($_POST, 'company');
    $old['subdomain']   = strtolower(input($_POST, 'subdomain'));
    $old['admin_user']  = input($_POST, 'admin_user');
    $old['admin_email'] = input($_POST, 'admin_email');
    $pass  = (string) ($_POST['admin_pass'] ?? '');
    $pass2 = (string) ($_POST['admin_pass2'] ?? '');

    if (mb_strlen($old['company']) < 2) $errors[] = 'Company name is required.';
    if (!preg_match('/^[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?$/', $old['subdomain'])) {
        $errors[] = 'Subdomain must be lowercase letters, numbers, and hyphens (2–63 chars).';
    } elseif (in_array($old['subdomain'], $reserved, true)) {
        $errors[] = 'That subdomain is reserved. Please choose another.';
    } else {
        $chk = db()->prepare("SELECT id FROM tenants WHERE subdomain = ?");
        $chk->execute([$old['subdomain']]);
        if ($chk->fetch()) $errors[] = 'That subdomain is already taken.';
    }
    if (mb_strlen($old['admin_user']) < 3) $errors[] = 'Admin username must be at least 3 characters.';
    if (!filter_var($old['admin_email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid admin email is required.';
    if (strlen($pass) < 8) $errors[] = 'Password must be at least 8 characters.';
    if ($pass !== $pass2) $errors[] = 'The two passwords do not match.';

    if (!$errors) {
        try {
            $pdo = db();
            $pdo->beginTransaction();
            $pdo->prepare("INSERT INTO tenants (name, subdomain, status) VALUES (?,?,'pending')")
                ->execute([$old['company'], $old['subdomain']]);
            $tenantId = (int) $pdo->lastInsertId();
            $pdo->prepare("INSERT INTO admins (tenant_id, username, email, password_hash, role) VALUES (?,?,?,?,'company_admin')")
                ->execute([$tenantId, $old['admin_user'], $old['admin_email'], password_hash($pass, PASSWORD_BCRYPT, ['cost' => 12])]);
            $pdo->commit();
            $done = true;
            $doneSub = $old['subdomain'];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $errors[] = 'Something went wrong creating your board. Please try again.';
        }
    }
}

$page_title = 'List your company';
require __DIR__ . '/includes/header.php';
$v = fn($k) => e($old[$k] ?? '');
?>
<section class="form-page wrap">
  <div class="form-shell">
    <div class="form-intro">
      <span class="eyebrow" style="justify-content:center">For companies</span>
      <h1>List your company</h1>
      <p>Create your board on its own subdomain. We'll review and activate it, then you can sign in and start posting.</p>
    </div>

    <?php if ($done): ?>
      <div class="alert alert--success">
        <div>
          <strong>You're on the list!</strong>
          Your board <code><?= e($doneSub) ?>.<?= e(APP_DOMAIN) ?></code> is <strong>pending activation</strong>.
          Once it's switched on, sign in at <code><?= e($doneSub) ?>.<?= e(APP_DOMAIN) ?>/admin</code>.
        </div>
      </div>
    <?php else: ?>
      <?php if ($errors): ?>
        <div class="alert alert--error"><ul class="error-list"><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div>
      <?php endif; ?>
      <div class="form-card">
        <form method="post" action="<?= url('signup.php') ?>" novalidate>
          <?= csrf_field() ?>
          <div class="hp-field" aria-hidden="true"><label>Leave empty<input type="text" name="website_url" tabindex="-1" autocomplete="off"></label></div>

          <div class="field"><label>Company name <span class="req">*</span></label><input type="text" name="company" value="<?= $v('company') ?>" maxlength="150" required></div>
          <div class="field">
            <label>Subdomain <span class="req">*</span></label>
            <div style="display:flex;align-items:center;gap:0.4rem">
              <input type="text" name="subdomain" value="<?= $v('subdomain') ?>" placeholder="your-company" maxlength="63" required style="max-width:220px">
              <span style="color:var(--ink-soft)">.<?= e(APP_DOMAIN) ?></span>
            </div>
          </div>
          <div class="field-row">
            <div class="field"><label>Admin username <span class="req">*</span></label><input type="text" name="admin_user" value="<?= $v('admin_user') ?>" required></div>
            <div class="field"><label>Admin email <span class="req">*</span></label><input type="email" name="admin_email" value="<?= $v('admin_email') ?>" required></div>
          </div>
          <div class="field-row">
            <div class="field"><label>Password <span class="req">*</span> <span class="hint">(min 8)</span></label><input type="password" name="admin_pass" required></div>
            <div class="field"><label>Confirm password <span class="req">*</span></label><input type="password" name="admin_pass2" required></div>
          </div>
          <button type="submit" class="btn btn--primary btn--block">Create my board</button>
        </form>
      </div>
    <?php endif; ?>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
