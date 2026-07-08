<?php
require_once __DIR__ . '/../config/config.php';
require_super_admin(); // categories are a shared, platform-managed taxonomy

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = input($_POST, 'action');
    $id = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
    if ($action === 'delete' && $id) {
        db()->prepare("DELETE FROM categories WHERE id=?")->execute([$id]);
        flash_set('info', 'Category deleted. Postings in that category are now uncategorized.');
    }
    redirect('admin/categories.php');
}

$categories = db()->query(
    "SELECT c.*, COUNT(j.id) AS job_count
     FROM categories c LEFT JOIN jobs j ON j.category_id = c.id
     GROUP BY c.id ORDER BY c.name"
)->fetchAll();

$admin_title = 'Categories';
require __DIR__ . '/includes/admin-header.php';
?>

<div class="page-head">
  <div>
    <h1>Categories</h1>
    <p>Manage the categories companies choose from when posting a role.</p>
  </div>
  <a href="<?= url('admin/category-form.php') ?>" class="btn btn--honey">+ New category</a>
</div>

<?php foreach (flash_get() as $f): ?>
  <div class="alert alert--<?= e($f['type']) ?>"><?= e($f['message']) ?></div>
<?php endforeach; ?>

<div class="job-list">
  <?php if (empty($categories)): ?>
    <div class="empty">
      <h3>No categories yet</h3>
      <p>Create one so companies have something to choose from.</p>
    </div>
  <?php endif; ?>

  <?php foreach ($categories as $c): ?>
    <article class="jrow">
      <div class="jrow__main">
        <div class="jrow__title">
          <?= e($c['name']) ?>
          <?php if ($c['name_ar']): ?><span class="status" style="background:#e6eef7;color:#1D5C9D">AR</span><?php endif; ?>
        </div>
        <div class="jrow__sub"><?= e($c['name_ar'] ?: '—') ?> · /<?= e($c['slug']) ?></div>
        <div class="jrow__meta">
          <span class="pill"><?= (int)$c['job_count'] ?> postings</span>
        </div>
      </div>
      <div class="jrow__actions">
        <a href="<?= url('admin/category-form.php?id=' . $c['id']) ?>" class="btn btn--ghost btn--sm">Edit</a>
        <form method="post" class="inline-form" data-confirm="Delete this category? Postings using it will become uncategorized.">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="category_id" value="<?= (int)$c['id'] ?>">
          <button class="btn btn--red btn--sm">Delete</button>
        </form>
      </div>
    </article>
  <?php endforeach; ?>
</div>

<?php require __DIR__ . '/includes/admin-footer.php'; ?>
