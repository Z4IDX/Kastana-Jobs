<?php
require_once __DIR__ . '/../config/config.php';
require_super_admin(); // categories are a shared, platform-managed taxonomy

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$isEdit = false;
$cat = ['name' => '', 'name_ar' => ''];

if ($id) {
    $stmt = db()->prepare("SELECT * FROM categories WHERE id=?");
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if ($found) { $cat = $found; $isEdit = true; }
    else { flash_set('error', 'Category not found.'); redirect('admin/categories.php'); }
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $cat['name'] = input($_POST, 'name');
    $cat['name_ar'] = input($_POST, 'name_ar');
    if (mb_strlen($cat['name']) < 2) $errors[] = 'Name is required.';

    if (empty($errors)) {
        $base = slugify($cat['name']);
        $slug = $base;
        $n = 2;
        while (true) {
            $check = db()->prepare("SELECT id FROM categories WHERE slug=? AND id<>?");
            $check->execute([$slug, $id ?: 0]);
            if (!$check->fetch()) break;
            $slug = $base . '-' . $n++;
        }
        if ($isEdit) {
            db()->prepare("UPDATE categories SET name=?, name_ar=?, slug=? WHERE id=?")
                ->execute([$cat['name'], $cat['name_ar'] ?: null, $slug, $id]);
            flash_set('success', 'Category updated.');
        } else {
            db()->prepare("INSERT INTO categories (name, name_ar, slug) VALUES (?,?,?)")
                ->execute([$cat['name'], $cat['name_ar'] ?: null, $slug]);
            flash_set('success', 'Category created.');
        }
        redirect('admin/categories.php');
    }
}

$admin_title = $isEdit ? 'Edit category' : 'New category';
require __DIR__ . '/includes/admin-header.php';
$v = fn($k) => e((string)($cat[$k] ?? ''));
?>

<div class="page-head">
  <div>
    <h1><?= $isEdit ? 'Edit category' : 'New category' ?></h1>
    <p>Categories companies choose from when posting a role.</p>
  </div>
  <a href="<?= url('admin/categories.php') ?>" class="btn btn--ghost">← Back</a>
</div>

<?php if (!empty($errors)): ?>
  <div class="alert alert--error">
    <strong>Please fix:</strong>
    <ul style="margin:0.3rem 0 0 1rem;padding:0"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
  </div>
<?php endif; ?>

<form method="post" novalidate>
  <?= csrf_field() ?>
  <div class="edit-card">
    <div class="field">
      <label>Name <span class="req">*</span></label>
      <input type="text" name="name" value="<?= $v('name') ?>" maxlength="80" required>
    </div>
    <div class="field" dir="rtl">
      <label style="text-align:right">Name (Arabic)</label>
      <input type="text" name="name_ar" value="<?= $v('name_ar') ?>" maxlength="80" dir="rtl">
    </div>
    <div class="form-actions">
      <a href="<?= url('admin/categories.php') ?>" class="btn btn--ghost">Cancel</a>
      <button type="submit" class="btn btn--primary"><?= $isEdit ? 'Save changes' : 'Create category' ?></button>
    </div>
  </div>
</form>

<?php require __DIR__ . '/includes/admin-footer.php'; ?>
