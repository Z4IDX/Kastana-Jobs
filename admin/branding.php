<?php
require_once __DIR__ . '/../config/config.php';
require_login();

$tid = current_tenant_id();
if ($tid === 0) { redirect('admin/tenants.php'); } // platform owner has no board to brand

$stmt = db()->prepare("SELECT * FROM tenants WHERE id = ?");
$stmt->execute([$tid]);
$tenant = $stmt->fetch();
if (!$tenant) { redirect('admin/dashboard.php'); }

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $brandName = input($_POST, 'brand_name');
    $color     = trim((string) ($_POST['primary_color'] ?? ''));

    if ($brandName !== '' && mb_strlen($brandName) > 150) $errors[] = 'Brand name is too long.';
    if ($color !== '' && !preg_match('/^#[0-9a-fA-F]{6}$/', $color)) $errors[] = 'Colour must be a hex value like #1D5C9D (or leave it blank).';

    // Logo upload / replace / remove.
    $finalLogo = $tenant['logo_path'] ?: null;
    if (empty($errors)) {
        $up = save_uploaded_image('logo');
        if ($up['error']) {
            $errors[] = $up['error'];
        } elseif (!empty($up['path'])) {
            delete_uploaded_image($tenant['logo_path'] ?: null);
            delete_uploaded_image($tenant['thumbnail_path'] ?? null);
            $finalLogo = $up['path'];
        } elseif (!empty($_POST['remove_logo'])) {
            delete_uploaded_image($tenant['logo_path'] ?: null);
            $finalLogo = null;
        }
    }

    if (empty($errors)) {
        db()->prepare("UPDATE tenants SET brand_name = ?, primary_color = ?, logo_path = ? WHERE id = ?")
            ->execute([$brandName ?: null, $color ?: null, $finalLogo, $tid]);
        flash_set('success', 'Branding saved.');
        redirect('admin/branding.php');
    }
    // keep entered values on error
    $tenant['brand_name'] = $brandName;
    $tenant['primary_color'] = $color;
    $tenant['logo_path'] = $finalLogo;
}

$admin_title = 'Branding';
require __DIR__ . '/includes/admin-header.php';
$v = fn($k) => e((string) ($tenant[$k] ?? ''));
?>
<div class="page-head">
  <div><h1>Branding</h1><p>Make this board your own. Blank fields fall back to the defaults.</p></div>
  <a href="<?= url('index.php') ?>" target="_blank" class="btn btn--ghost">View board ↗</a>
</div>

<?php foreach (flash_get() as $f): ?><div class="alert alert--<?= e($f['type']) ?>"><?= e($f['message']) ?></div><?php endforeach; ?>
<?php if ($errors): ?>
  <div class="alert alert--error"><ul style="margin:0.3rem 0 0 1rem;padding:0"><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" novalidate>
  <?= csrf_field() ?>
  <div class="edit-card">
    <div class="field">
      <label>Brand name <span class="hint">(shown in the header, footer, and title)</span></label>
      <input type="text" name="brand_name" value="<?= $v('brand_name') ?>" maxlength="150" placeholder="<?= e($tenant['name']) ?>">
    </div>

    <div class="field">
      <label>Accent colour <span class="hint">(hex like #1D5C9D — blank uses the default blue)</span></label>
      <div style="display:flex;align-items:center;gap:0.6rem">
        <span aria-hidden="true" style="width:38px;height:38px;border-radius:8px;border:1px solid var(--line-strong);background:<?= $v('primary_color') ?: '#1D5C9D' ?>"></span>
        <input type="text" name="primary_color" value="<?= $v('primary_color') ?>" maxlength="7" placeholder="#1D5C9D" style="max-width:160px">
      </div>
    </div>

    <div class="field">
      <label>Logo <span class="hint">(JPG, PNG, WEBP or GIF, up to 2 MB)</span></label>
      <?php if (!empty($tenant['logo_path'])): ?>
        <div class="img-review">
          <img src="<?= url($tenant['logo_path']) ?>" alt="Current logo" class="img-review__thumb">
          <label style="font-weight:400;font-size:0.88rem;display:flex;gap:0.4rem;align-items:center;margin:0">
            <input type="checkbox" name="remove_logo" value="1" style="width:auto"> Remove logo
          </label>
        </div>
      <?php endif; ?>
      <input type="file" name="logo" accept="image/png,image/jpeg,image/webp,image/gif" class="file-input" style="margin-top:0.5rem">
    </div>

    <div class="form-actions">
      <a href="<?= url('admin/dashboard.php') ?>" class="btn btn--ghost">Cancel</a>
      <button type="submit" class="btn btn--primary">Save branding</button>
    </div>
  </div>
</form>
<?php require __DIR__ . '/includes/admin-footer.php'; ?>
