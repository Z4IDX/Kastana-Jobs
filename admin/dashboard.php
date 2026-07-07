<?php
require_once __DIR__ . '/../config/config.php';
require_login();

/* ---------- Handle actions (approve / reject / delete) ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = input($_POST, 'action');
    $jobId  = filter_input(INPUT_POST, 'job_id', FILTER_VALIDATE_INT);

    if ($jobId) {
        $lblStmt = db()->prepare("SELECT title, company_name, is_featured, image_path, thumbnail_path FROM jobs WHERE id=?");
        $lblStmt->execute([$jobId]);
        $jobRow = $lblStmt->fetch();
        $jobLabel = $jobRow ? $jobRow['title'] . ' — ' . $jobRow['company_name'] : null;

        switch ($action) {
            case 'approve':
                db()->prepare(
                    "UPDATE jobs SET status='approved', approved_at=NOW(), approved_by=? WHERE id=?"
                )->execute([current_admin_id(), $jobId]);
                log_activity($jobId, 'approve', $jobLabel);
                flash_set('success', 'Posting approved and published.');
                break;

            case 'reject':
                db()->prepare("UPDATE jobs SET status='rejected' WHERE id=?")->execute([$jobId]);
                log_activity($jobId, 'reject', $jobLabel);
                flash_set('info', 'Posting rejected. It will not appear on the board.');
                break;

            case 'unpublish':
                db()->prepare("UPDATE jobs SET status='pending' WHERE id=?")->execute([$jobId]);
                log_activity($jobId, 'unpublish', $jobLabel);
                flash_set('info', 'Posting moved back to pending.');
                break;

            case 'feature':
                $wasFeatured = $jobRow && (bool) $jobRow['is_featured'];
                db()->prepare("UPDATE jobs SET is_featured = 1 - is_featured WHERE id=?")->execute([$jobId]);
                log_activity($jobId, $wasFeatured ? 'unfeature' : 'feature', $jobLabel);
                flash_set('success', 'Featured status updated.');
                break;

            case 'delete':
                log_activity($jobId, 'delete', $jobLabel); // logged before delete so the FK is satisfiable
                if ($jobRow) {
                    delete_uploaded_image($jobRow['image_path'] ?: null);
                    delete_uploaded_image($jobRow['thumbnail_path'] ?: null);
                }
                db()->prepare("DELETE FROM jobs WHERE id=?")->execute([$jobId]);
                flash_set('info', 'Posting deleted permanently.');
                break;
        }
    }
    redirect('admin/dashboard.php' . (isset($_POST['return']) ? '?tab=' . urlencode($_POST['return']) : ''));
}

/* ---------- Read data ---------- */
$tab = $_GET['tab'] ?? 'pending';
$allowedTabs = ['pending', 'approved', 'rejected', 'all'];
if (!in_array($tab, $allowedTabs, true)) $tab = 'pending';

$counts = db()->query(
    "SELECT
        SUM(status='pending')  AS pending,
        SUM(status='approved') AS approved,
        SUM(status='rejected') AS rejected,
        COUNT(*)               AS total
     FROM jobs"
)->fetch();

if ($tab === 'all') {
    $stmt = db()->query(
        "SELECT j.*, c.name AS category_name,
                (SELECT COUNT(*) FROM applicants a WHERE a.job_id = j.id) AS applicant_count
         FROM jobs j
         LEFT JOIN categories c ON c.id=j.category_id
         ORDER BY FIELD(j.status,'pending','approved','rejected'), j.created_at DESC"
    );
} else {
    $stmt = db()->prepare(
        "SELECT j.*, c.name AS category_name,
                (SELECT COUNT(*) FROM applicants a WHERE a.job_id = j.id) AS applicant_count
         FROM jobs j
         LEFT JOIN categories c ON c.id=j.category_id
         WHERE j.status=? ORDER BY j.created_at DESC"
    );
    $stmt->execute([$tab]);
}
$jobs = $stmt->fetchAll();

$admin_title = 'Dashboard';
require __DIR__ . '/includes/admin-header.php';
?>

<div class="page-head">
  <div>
    <h1>Postings</h1>
    <p>Review submissions, then approve, edit, or reject them.</p>
  </div>
  <a href="<?= url('admin/edit-job.php') ?>" class="btn btn--honey">+ New posting</a>
</div>

<?php foreach (flash_get() as $f): ?>
  <div class="alert alert--<?= e($f['type']) ?>"><?= e($f['message']) ?></div>
<?php endforeach; ?>

<div class="stat-row">
  <div class="stat is-pending"><b><?= (int)$counts['pending'] ?></b><span>Awaiting review</span></div>
  <div class="stat is-approved"><b><?= (int)$counts['approved'] ?></b><span>Published</span></div>
  <div class="stat"><b><?= (int)$counts['rejected'] ?></b><span>Rejected</span></div>
  <div class="stat"><b><?= (int)$counts['total'] ?></b><span>Total</span></div>
</div>

<nav class="tabs">
  <?php
    $tabLabels = [
      'pending'  => ['Pending',  (int)$counts['pending']],
      'approved' => ['Published',(int)$counts['approved']],
      'rejected' => ['Rejected', (int)$counts['rejected']],
      'all'      => ['All',      (int)$counts['total']],
    ];
    foreach ($tabLabels as $key => [$label, $n]):
  ?>
    <a class="tab <?= $tab === $key ? 'is-active' : '' ?>" href="<?= url('admin/dashboard.php?tab=' . $key) ?>">
      <?= e($label) ?><span class="count"><?= $n ?></span>
    </a>
  <?php endforeach; ?>
</nav>

<div class="job-list">
  <?php if (empty($jobs)): ?>
    <div class="empty">
      <h3>Nothing here</h3>
      <p>There are no <?= $tab === 'all' ? '' : e($tab) ?> postings right now.</p>
    </div>
  <?php endif; ?>

  <?php foreach ($jobs as $job):
      $salary = format_salary($job['salary_min'] ?: null, $job['salary_max'] ?: null, $job['salary_currency']);
  ?>
    <article class="jrow<?= !empty($job['image_path']) ? ' has-thumb' : '' ?>">
      <?php if (!empty($job['image_path'])): ?>
        <img class="jrow__thumb" src="<?= url($job['thumbnail_path'] ?: $job['image_path']) ?>" alt="">
      <?php endif; ?>
      <div class="jrow__main">
        <div class="jrow__title">
          <?= e($job['title']) ?>
          <span class="status status--<?= e($job['status']) ?>"><?= e($job['status']) ?></span>
          <?php if ($job['is_featured']): ?><span class="status" style="background:var(--honey-soft);color:var(--chestnut-deep)">Featured</span><?php endif; ?>
          <?php if (!empty($job['title_ar'])): ?><span class="status" style="background:#e6eef7;color:#1D5C9D">AR</span><?php endif; ?>
          <?php if (!empty($job['expires_at']) && $job['expires_at'] < date('Y-m-d')): ?><span class="status" style="background:#eee;color:#888">Expired</span><?php endif; ?>
        </div>
        <div class="jrow__sub"><?= e($job['company_name']) ?> · <?= e($job['location']) ?> · <?= e($job['company_email']) ?></div>
        <div class="jrow__meta">
          <span class="pill"><?= e($job['job_type']) ?></span>
          <?php if ($job['category_name']): ?><span class="pill"><?= e($job['category_name']) ?></span><?php endif; ?>
          <?php if ($salary): ?><span class="pill"><?= e($salary) ?></span><?php endif; ?>
          <span class="pill">Submitted <?= e(time_ago($job['created_at'])) ?></span>
        </div>
      </div>

      <div class="jrow__actions">
        <?php if ($job['status'] === 'approved'): ?>
          <a href="<?= url('job.php?id=' . $job['id']) ?>" target="_blank" class="btn btn--ghost btn--sm">View ↗</a>
        <?php endif; ?>

        <a href="<?= url('admin/edit-job.php?id=' . $job['id']) ?>" class="btn btn--ghost btn--sm">Edit</a>
        <a href="<?= url('admin/applicants.php?job_id=' . $job['id']) ?>" class="btn btn--ghost btn--sm">Applicants (<?= (int)$job['applicant_count'] ?>)</a>

        <?php if ($job['status'] !== 'approved'): ?>
          <form method="post" class="inline-form">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="approve">
            <input type="hidden" name="job_id" value="<?= (int)$job['id'] ?>">
            <input type="hidden" name="return" value="<?= e($tab) ?>">
            <button class="btn btn--green btn--sm">Approve</button>
          </form>
        <?php endif; ?>

        <?php if ($job['status'] === 'approved'): ?>
          <form method="post" class="inline-form">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="feature">
            <input type="hidden" name="job_id" value="<?= (int)$job['id'] ?>">
            <input type="hidden" name="return" value="<?= e($tab) ?>">
            <button class="btn btn--ghost btn--sm"><?= $job['is_featured'] ? 'Unfeature' : 'Feature' ?></button>
          </form>
          <form method="post" class="inline-form">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="unpublish">
            <input type="hidden" name="job_id" value="<?= (int)$job['id'] ?>">
            <input type="hidden" name="return" value="<?= e($tab) ?>">
            <button class="btn btn--ghost btn--sm">Unpublish</button>
          </form>
        <?php endif; ?>

        <?php if ($job['status'] !== 'rejected'): ?>
          <form method="post" class="inline-form">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="reject">
            <input type="hidden" name="job_id" value="<?= (int)$job['id'] ?>">
            <input type="hidden" name="return" value="<?= e($tab) ?>">
            <button class="btn btn--red btn--sm">Reject</button>
          </form>
        <?php endif; ?>

        <form method="post" class="inline-form" data-confirm="Delete this posting permanently? This cannot be undone.">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="job_id" value="<?= (int)$job['id'] ?>">
          <input type="hidden" name="return" value="<?= e($tab) ?>">
          <button class="btn btn--red btn--sm">Delete</button>
        </form>
      </div>
    </article>
  <?php endforeach; ?>
</div>

<?php require __DIR__ . '/includes/admin-footer.php'; ?>
