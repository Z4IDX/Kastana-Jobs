<?php
/**
 * Admin: employer account verification. New sign-ups arrive 'pending' and must be
 * approved here before they can post. Also allows suspend / reactivate.
 */
require_once __DIR__ . '/../config/config.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = input($_POST, 'action');
    $id = filter_input(INPUT_POST, 'employer_id', FILTER_VALIDATE_INT);
    if ($id && in_array($action, ['approve', 'suspend', 'activate'], true)) {
        $newStatus = ($action === 'suspend') ? 'suspended' : 'active';
        $nameStmt = db()->prepare("SELECT company_name FROM employers WHERE id = ?");
        $nameStmt->execute([$id]);
        $cn = $nameStmt->fetchColumn();
        if ($cn !== false) {
            db()->prepare("UPDATE employers SET status = ? WHERE id = ?")->execute([$newStatus, $id]);
            log_activity(null, $action . '_employer', ucfirst($action) . ': ' . $cn);
            flash_set('success', 'Account "' . $cn . '" is now ' . $newStatus . '.');
        }
    }
    redirect('admin/employers.php');
}

$filter = in_array($_GET['status'] ?? '', ['pending', 'active', 'suspended'], true) ? $_GET['status'] : 'all';
$sql = "SELECT e.*, (SELECT COUNT(*) FROM jobs j WHERE j.employer_id = e.id) AS job_count FROM employers e";
if ($filter !== 'all') $sql .= " WHERE e.status = " . db()->quote($filter);
$sql .= " ORDER BY (e.status = 'pending') DESC, e.created_at DESC";
$employers = db()->query($sql)->fetchAll();
$pendingCount = (int) db()->query("SELECT COUNT(*) FROM employers WHERE status = 'pending'")->fetchColumn();

$admin_title = 'Accounts';
require __DIR__ . '/includes/admin-header.php';
?>

<div class="page-head">
  <div>
    <h1>Employer accounts</h1>
    <p>Verify new sign-ups before they can post. <strong><?= $pendingCount ?></strong> awaiting review.</p>
  </div>
</div>

<div class="tabs">
  <a class="tab <?= $filter === 'all' ? 'is-active' : '' ?>" href="<?= url('admin/employers.php') ?>">All</a>
  <a class="tab <?= $filter === 'pending' ? 'is-active' : '' ?>" href="<?= url('admin/employers.php?status=pending') ?>">Pending<?= $pendingCount ? ' (' . $pendingCount . ')' : '' ?></a>
  <a class="tab <?= $filter === 'active' ? 'is-active' : '' ?>" href="<?= url('admin/employers.php?status=active') ?>">Active</a>
  <a class="tab <?= $filter === 'suspended' ? 'is-active' : '' ?>" href="<?= url('admin/employers.php?status=suspended') ?>">Suspended</a>
</div>

<?php foreach (flash_get() as $f): ?>
  <div class="alert alert--<?= e($f['type']) ?>"><?= e($f['message']) ?></div>
<?php endforeach; ?>

<div class="job-list">
  <?php if (empty($employers)): ?>
    <div class="empty"><h3>No accounts here</h3><p>Nothing matches this filter yet.</p></div>
  <?php endif; ?>

  <?php foreach ($employers as $e):
      $stClass = $e['status'] === 'active' ? 'status--approved' : ($e['status'] === 'suspended' ? 'status--rejected' : 'status--pending');
  ?>
    <article class="jrow">
      <div class="jrow__main">
        <div class="jrow__title"><?= e($e['company_name']) ?> <span class="status <?= $stClass ?>"><?= e($e['status']) ?></span></div>
        <div class="jrow__sub">
          <?= e($e['email']) ?><?php if ($e['phone']): ?> · <?= e($e['phone']) ?><?php endif; ?>
          <?php if ($e['website']): ?> · <a href="<?= e($e['website']) ?>" target="_blank" rel="noopener noreferrer nofollow"><?= e($e['website']) ?></a><?php endif; ?>
        </div>
        <div class="jrow__meta">
          <span class="pill"><?= (int)$e['job_count'] ?> postings</span>
          <span class="pill">joined <?= e(date('Y-m-d', strtotime($e['created_at']))) ?></span>
        </div>
      </div>
      <div class="jrow__actions">
        <?php if ($e['status'] === 'pending'): ?>
          <form method="post" class="inline-form">
            <?= csrf_field() ?><input type="hidden" name="action" value="approve"><input type="hidden" name="employer_id" value="<?= (int)$e['id'] ?>">
            <button class="btn btn--honey btn--sm">Approve</button>
          </form>
        <?php endif; ?>
        <?php if ($e['status'] !== 'suspended'): ?>
          <form method="post" class="inline-form" data-confirm="Suspend this account? They won't be able to sign in.">
            <?= csrf_field() ?><input type="hidden" name="action" value="suspend"><input type="hidden" name="employer_id" value="<?= (int)$e['id'] ?>">
            <button class="btn btn--red btn--sm">Suspend</button>
          </form>
        <?php else: ?>
          <form method="post" class="inline-form">
            <?= csrf_field() ?><input type="hidden" name="action" value="activate"><input type="hidden" name="employer_id" value="<?= (int)$e['id'] ?>">
            <button class="btn btn--ghost btn--sm">Reactivate</button>
          </form>
        <?php endif; ?>
      </div>
    </article>
  <?php endforeach; ?>
</div>

<?php require __DIR__ . '/includes/admin-footer.php'; ?>
