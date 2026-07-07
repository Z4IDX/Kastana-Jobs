<?php
require_once __DIR__ . '/../config/config.php';
require_login();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $current = (string)($_POST['current_password'] ?? '');
    $new     = (string)($_POST['new_password'] ?? '');
    $confirm = (string)($_POST['confirm_password'] ?? '');

    $stmt = db()->prepare("SELECT * FROM admins WHERE id=? LIMIT 1");
    $stmt->execute([current_admin_id()]);
    $admin = $stmt->fetch();

    if (!$admin || !password_verify($current, $admin['password_hash'])) {
        $errors[] = 'Your current password is incorrect.';
    }
    if (mb_strlen($new) < 10) {
        $errors[] = 'New password must be at least 10 characters.';
    }
    if (!preg_match('/[A-Z]/', $new) || !preg_match('/[a-z]/', $new) || !preg_match('/\d/', $new)) {
        $errors[] = 'New password must include upper- and lower-case letters and a number.';
    }
    if ($new !== $confirm) {
        $errors[] = 'The new passwords do not match.';
    }

    if (empty($errors)) {
        $hash = password_hash($new, PASSWORD_BCRYPT, ['cost' => 12]);
        db()->prepare("UPDATE admins SET password_hash=? WHERE id=?")
            ->execute([$hash, current_admin_id()]);
        flash_set('success', 'Password updated. Use it next time you sign in.');
        redirect('admin/account.php');
    }
}

$admin_title = 'Account';
require __DIR__ . '/includes/admin-header.php';
?>

<div class="page-head">
  <div>
    <h1>Account</h1>
    <p>Change the password for <strong><?= e($_SESSION['admin_username'] ?? '') ?></strong>.</p>
  </div>
  <a href="<?= url('admin/dashboard.php') ?>" class="btn btn--ghost">← Back</a>
</div>

<?php foreach (flash_get() as $f): ?>
  <div class="alert alert--<?= e($f['type']) ?>"><?= e($f['message']) ?></div>
<?php endforeach; ?>

<?php if (!empty($errors)): ?>
  <div class="alert alert--error">
    <strong>Please fix:</strong>
    <ul style="margin:0.3rem 0 0 1rem;padding:0"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
  </div>
<?php endif; ?>

<div class="edit-card" style="max-width:520px">
  <form method="post" novalidate>
    <?= csrf_field() ?>
    <div class="field">
      <label>Current password <span class="req">*</span></label>
      <input type="password" name="current_password" autocomplete="current-password" required>
    </div>
    <div class="field">
      <label>New password <span class="req">*</span> <span class="hint">(10+ chars, mixed case & a number)</span></label>
      <input type="password" name="new_password" autocomplete="new-password" required>
    </div>
    <div class="field">
      <label>Confirm new password <span class="req">*</span></label>
      <input type="password" name="confirm_password" autocomplete="new-password" required>
    </div>
    <div class="form-actions">
      <button type="submit" class="btn btn--primary">Update password</button>
    </div>
  </form>
</div>

<?php require __DIR__ . '/includes/admin-footer.php'; ?>
