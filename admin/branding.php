<?php
require_once __DIR__ . '/../config/config.php';
require_login();

$tid = current_tenant_id();
if ($tid === 0) { redirect('admin/tenants.php'); } // platform owner has no board to customize

$stmt = db()->prepare("SELECT * FROM tenants WHERE id = ?");
$stmt->execute([$tid]);
$tenant = $stmt->fetch();
if (!$tenant) { redirect('admin/dashboard.php'); }

$set = $tenant['settings'] ? (json_decode($tenant['settings'], true) ?: []) : [];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $brandName = input($_POST, 'brand_name');
    $primary   = trim((string) ($_POST['primary_color'] ?? ''));
    $highlight = trim((string) ($_POST['highlight_color'] ?? ''));

    if ($brandName !== '' && mb_strlen($brandName) > 150) $errors[] = 'Brand name is too long.';
    foreach (['Accent' => $primary, 'Highlight' => $highlight] as $lbl => $c) {
        if ($c !== '' && !preg_match('/^#[0-9a-fA-F]{6}$/', $c)) $errors[] = "$lbl colour must be a hex value like #1D5C9D (or blank).";
    }
    foreach (['Website' => 'social_website', 'LinkedIn' => 'social_linkedin', 'X' => 'social_x', 'Instagram' => 'social_instagram'] as $lbl => $k) {
        $u = input($_POST, $k);
        if ($u !== '' && !filter_var($u, FILTER_VALIDATE_URL)) $errors[] = "$lbl link must be a valid URL (including https://).";
    }

    // Rebuild the settings blob.
    $set = [
        'tagline'          => input($_POST, 'tagline'),
        'highlight_color'  => $highlight,
        'font_theme'       => array_key_exists((string) ($_POST['font_theme'] ?? ''), font_theme_options()) ? $_POST['font_theme'] : 'default',
        'hero_theme'       => ($_POST['hero_theme'] ?? '') === 'light' ? 'light' : 'dark',
        'hero_title'       => input($_POST, 'hero_title'),
        'hero_subtext'     => input($_POST, 'hero_subtext'),
        'about'            => input($_POST, 'about'),
        'show_stats'       => isset($_POST['show_stats']),
        'per_page'         => max(1, min(50, (int) ($_POST['per_page'] ?? 12))),
        'show_salary'      => isset($_POST['show_salary']),
        'enable_apply'     => isset($_POST['enable_apply']),
        'enable_saved'     => isset($_POST['enable_saved']),
        'footer_note'      => input($_POST, 'footer_note'),
        'social_website'   => input($_POST, 'social_website'),
        'social_linkedin'  => input($_POST, 'social_linkedin'),
        'social_x'         => input($_POST, 'social_x'),
        'social_instagram' => input($_POST, 'social_instagram'),
    ];

    // Logo upload / replace / remove.
    $finalLogo = $tenant['logo_path'] ?: null;
    if (empty($errors)) {
        $up = save_uploaded_image('logo');
        if ($up['error']) {
            $errors[] = $up['error'];
        } elseif (!empty($up['path'])) {
            delete_uploaded_image($tenant['logo_path'] ?: null);
            $finalLogo = $up['path'];
        } elseif (!empty($_POST['remove_logo'])) {
            delete_uploaded_image($tenant['logo_path'] ?: null);
            $finalLogo = null;
        }
    }

    if (empty($errors)) {
        db()->prepare("UPDATE tenants SET brand_name = ?, primary_color = ?, logo_path = ?, settings = ? WHERE id = ?")
            ->execute([$brandName ?: null, $primary ?: null, $finalLogo, json_encode($set, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $tid]);
        flash_set('success', 'Customization saved.');
        redirect('admin/branding.php');
    }
    // keep entered values on error
    $tenant['brand_name'] = $brandName;
    $tenant['primary_color'] = $primary;
    $tenant['logo_path'] = $finalLogo;
}

$admin_title = 'Customize';
require __DIR__ . '/includes/admin-header.php';
$v = fn($k) => e((string) ($tenant[$k] ?? ''));
$s = fn($k, $d = '') => e((string) ($set[$k] ?? $d));
$chk = fn($k, $d = true) => (($set[$k] ?? $d) ? 'checked' : '');
?>
<div class="page-head">
  <div><h1>Customize your board</h1><p>Make it yours. Blank fields fall back to sensible defaults.</p></div>
  <a href="<?= url('index.php') ?>" target="_blank" class="btn btn--ghost">View board ↗</a>
</div>

<?php foreach (flash_get() as $f): ?><div class="alert alert--<?= e($f['type']) ?>"><?= e($f['message']) ?></div><?php endforeach; ?>
<?php if ($errors): ?>
  <div class="alert alert--error"><ul style="margin:0.3rem 0 0 1rem;padding:0"><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" novalidate>
  <?= csrf_field() ?>
  <div class="edit-card">

    <div class="edit-section-title">Identity</div>
    <div class="field">
      <label>Brand name <span class="hint">(header, footer, page title)</span></label>
      <input type="text" name="brand_name" value="<?= $v('brand_name') ?>" maxlength="150" placeholder="<?= e($tenant['name']) ?>">
    </div>
    <div class="field">
      <label>Tagline <span class="hint">(search-engine description)</span></label>
      <input type="text" name="tagline" value="<?= $s('tagline') ?>" maxlength="200" placeholder="<?= e(APP_TAGLINE) ?>">
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

    <div class="edit-section-title">Colours</div>
    <div class="field-row">
      <div class="field">
        <label>Accent colour <span class="hint">(buttons &amp; links — blank = default blue)</span></label>
        <div style="display:flex;align-items:center;gap:0.6rem">
          <span aria-hidden="true" style="width:38px;height:38px;border-radius:8px;border:1px solid var(--line-strong);background:<?= $v('primary_color') ?: '#1D5C9D' ?>"></span>
          <input type="text" name="primary_color" value="<?= $v('primary_color') ?>" maxlength="7" placeholder="#1D5C9D" style="max-width:160px">
        </div>
      </div>
      <div class="field">
        <label>Highlight colour <span class="hint">(badges &amp; accents — blank = default yellow)</span></label>
        <div style="display:flex;align-items:center;gap:0.6rem">
          <span aria-hidden="true" style="width:38px;height:38px;border-radius:8px;border:1px solid var(--line-strong);background:<?= $s('highlight_color') ?: '#EAA62C' ?>"></span>
          <input type="text" name="highlight_color" value="<?= $s('highlight_color') ?>" maxlength="7" placeholder="#EAA62C" style="max-width:160px">
        </div>
      </div>
    </div>

    <div class="edit-section-title">Typography &amp; layout</div>
    <div class="field-row">
      <div class="field">
        <label>Font style</label>
        <select name="font_theme">
          <?php foreach (font_theme_options() as $k => $opt): ?>
            <option value="<?= e($k) ?>" <?= ($set['font_theme'] ?? 'default') === $k ? 'selected' : '' ?>><?= e($opt['label']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label>Hero style</label>
        <select name="hero_theme">
          <option value="dark"  <?= ($set['hero_theme'] ?? 'dark') !== 'light' ? 'selected' : '' ?>>Dark (default)</option>
          <option value="light" <?= ($set['hero_theme'] ?? 'dark') === 'light' ? 'selected' : '' ?>>Light</option>
        </select>
      </div>
    </div>

    <div class="edit-section-title">Homepage</div>
    <div class="field">
      <label>Hero headline <span class="hint">(blank = default)</span></label>
      <input type="text" name="hero_title" value="<?= $s('hero_title') ?>" maxlength="120" placeholder="Work worth chasing.">
    </div>
    <div class="field">
      <label>Hero subtext</label>
      <textarea name="hero_subtext" maxlength="400" placeholder="A short line under the headline."><?= $s('hero_subtext') ?></textarea>
    </div>
    <div class="field">
      <label>About section <span class="hint">(optional — shown under the listings)</span></label>
      <textarea name="about" maxlength="2000" placeholder="Tell candidates about your company."><?= $s('about') ?></textarea>
    </div>
    <div class="field" style="display:flex;align-items:center;gap:0.6rem">
      <input type="checkbox" name="show_stats" id="show_stats" value="1" <?= $chk('show_stats') ?> style="width:auto">
      <label for="show_stats" style="margin:0">Show the stats row (open roles / companies)</label>
    </div>

    <div class="edit-section-title">Board behaviour</div>
    <div class="field" style="max-width:220px">
      <label>Jobs per page</label>
      <input type="number" name="per_page" value="<?= (int) ($set['per_page'] ?? 12) ?>" min="1" max="50">
    </div>
    <div class="field" style="display:flex;align-items:center;gap:0.6rem">
      <input type="checkbox" name="show_salary" id="show_salary" value="1" <?= $chk('show_salary') ?> style="width:auto">
      <label for="show_salary" style="margin:0">Show salary ranges on listings</label>
    </div>
    <div class="field" style="display:flex;align-items:center;gap:0.6rem">
      <input type="checkbox" name="enable_apply" id="enable_apply" value="1" <?= $chk('enable_apply') ?> style="width:auto">
      <label for="enable_apply" style="margin:0">Let candidates apply through an on-site form</label>
    </div>
    <div class="field" style="display:flex;align-items:center;gap:0.6rem">
      <input type="checkbox" name="enable_saved" id="enable_saved" value="1" <?= $chk('enable_saved') ?> style="width:auto">
      <label for="enable_saved" style="margin:0">Let visitors save/bookmark jobs</label>
    </div>

    <div class="edit-section-title">Social links</div>
    <div class="field-row">
      <div class="field"><label>Website</label><input type="url" name="social_website" value="<?= $s('social_website') ?>" placeholder="https://"></div>
      <div class="field"><label>LinkedIn</label><input type="url" name="social_linkedin" value="<?= $s('social_linkedin') ?>" placeholder="https://"></div>
    </div>
    <div class="field-row">
      <div class="field"><label>X (Twitter)</label><input type="url" name="social_x" value="<?= $s('social_x') ?>" placeholder="https://"></div>
      <div class="field"><label>Instagram</label><input type="url" name="social_instagram" value="<?= $s('social_instagram') ?>" placeholder="https://"></div>
    </div>

    <div class="edit-section-title">Footer</div>
    <div class="field">
      <label>Footer note <span class="hint">(after the copyright — blank = default)</span></label>
      <input type="text" name="footer_note" value="<?= $s('footer_note') ?>" maxlength="200" placeholder="<?= e(t('footer_copy')) ?>">
    </div>

    <div class="form-actions">
      <a href="<?= url('admin/dashboard.php') ?>" class="btn btn--ghost">Cancel</a>
      <button type="submit" class="btn btn--primary">Save customization</button>
    </div>
  </div>
</form>
<?php require __DIR__ . '/includes/admin-footer.php'; ?>
