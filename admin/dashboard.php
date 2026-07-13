<?php
require_once __DIR__ . '/../config/config.php';
require_login();

/* ---------- Handle actions (approve / reject / delete) ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = input($_POST, 'action');

    if ($action === 'set_moderation') {
        $m = input($_POST, 'mode');
        if (in_array($m, ['both', 'companies', 'jobs'], true)) {
            set_setting('moderation_mode', $m);
            log_activity(null, 'set_moderation', 'Approval mode: ' . $m);
            flash_set('success', t('ad_mod_saved'));
        }
        redirect('admin/dashboard.php');
    }

    $jobId  = filter_input(INPUT_POST, 'job_id', FILTER_VALIDATE_INT);

    if ($jobId) {
        $lblStmt = db()->prepare("SELECT title, company_name, is_featured, image_path, thumbnail_path, employer_id FROM jobs WHERE id=?");
        $lblStmt->execute([$jobId]);
        $jobRow = $lblStmt->fetch();
        $jobLabel = $jobRow ? $jobRow['title'] . ' — ' . $jobRow['company_name'] : null;

        if ($jobRow) {
            switch ($action) {
                case 'approve':
                    db()->prepare(
                        "UPDATE jobs SET status='approved', approved_at=NOW(), approved_by=? WHERE id=?"
                    )->execute([current_admin_id(), $jobId]);
                    log_activity($jobId, 'approve', $jobLabel);
                    if ($jobRow['employer_id']) notify_employer((int) $jobRow['employer_id'], $jobId, 'approved', $jobRow['title']);
                    flash_set('success', t('ad_fl_approved'));
                    break;

                case 'reject':
                    db()->prepare("UPDATE jobs SET status='rejected' WHERE id=?")->execute([$jobId]);
                    log_activity($jobId, 'reject', $jobLabel);
                    if ($jobRow['employer_id']) notify_employer((int) $jobRow['employer_id'], $jobId, 'rejected', $jobRow['title']);
                    flash_set('info', t('ad_fl_rejected'));
                    break;

                case 'unpublish':
                    db()->prepare("UPDATE jobs SET status='pending' WHERE id=?")->execute([$jobId]);
                    log_activity($jobId, 'unpublish', $jobLabel);
                    flash_set('info', t('ad_fl_unpublished'));
                    break;

                case 'feature':
                    $wasFeatured = (bool) $jobRow['is_featured'];
                    db()->prepare("UPDATE jobs SET is_featured = 1 - is_featured WHERE id=?")->execute([$jobId]);
                    log_activity($jobId, $wasFeatured ? 'unfeature' : 'feature', $jobLabel);
                    flash_set('success', t('ad_fl_featured'));
                    break;

                case 'delete':
                    log_activity($jobId, 'delete', $jobLabel); // logged before delete so the FK is satisfiable
                    delete_uploaded_image($jobRow['image_path'] ?: null);
                    delete_uploaded_image($jobRow['thumbnail_path'] ?: null);
                    db()->prepare("DELETE FROM jobs WHERE id=?")->execute([$jobId]);
                    flash_set('info', t('ad_fl_deleted'));
                    break;
            }
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

$mode = moderation_mode();
$pendingCompanies = (int) db()->query("SELECT COUNT(*) FROM employers WHERE status='pending'")->fetchColumn();

$search = trim(input($_GET, 'q'));
$conds = [];
$qp = [];
if ($tab !== 'all') { $conds[] = "j.status = ?"; $qp[] = $tab; }
if ($search !== '') {
    $conds[] = "(j.title LIKE ? OR j.company_name LIKE ? OR j.company_email LIKE ?)";
    $s = '%' . $search . '%';
    array_push($qp, $s, $s, $s);
}
$whereSql = $conds ? 'WHERE ' . implode(' AND ', $conds) : '';
$orderSql = $tab === 'all' ? "FIELD(j.status,'pending','approved','rejected'), j.created_at DESC" : "j.created_at DESC";
$stmt = db()->prepare(
    "SELECT j.*, c.name AS category_name
     FROM jobs j LEFT JOIN categories c ON c.id=j.category_id
     $whereSql ORDER BY $orderSql"
);
$stmt->execute($qp);
$jobs = $stmt->fetchAll();

$admin_title = t('a_dashboard');
require __DIR__ . '/includes/admin-header.php';
$statusLabels = ['pending' => t('ad_tab_pending'), 'approved' => t('ad_tab_published'), 'rejected' => t('ad_tab_rejected')];
?>

<div class="page-head">
  <div>
    <h1><?= e(t('ad_postings')) ?></h1>
    <p><?= e(t('ad_postings_lede')) ?></p>
  </div>
  <a href="<?= url('admin/edit-job.php') ?>" class="btn btn--honey"><?= e(t('ad_new_posting')) ?></a>
</div>

<?php foreach (flash_get() as $f): ?>
  <div class="alert alert--<?= e($f['type']) ?>"><?= e($f['message']) ?></div>
<?php endforeach; ?>

<div class="stat-row">
  <div class="stat is-pending"><b><?= (int)$counts['pending'] ?></b><span><?= e(t('ad_stat_pending')) ?></span></div>
  <div class="stat is-approved"><b><?= (int)$counts['approved'] ?></b><span><?= e(t('ad_stat_published')) ?></span></div>
  <div class="stat"><b><?= (int)$counts['rejected'] ?></b><span><?= e(t('ad_stat_rejected')) ?></span></div>
  <div class="stat"><b><?= (int)$counts['total'] ?></b><span><?= e(t('ad_stat_total')) ?></span></div>
</div>

<div class="mod-toggle">
  <div class="mod-toggle__label">
    <strong><?= e(t('ad_mod_title')) ?></strong>
    <span><?= e(t('ad_mod_lede')) ?></span>
  </div>
  <div class="mod-toggle__opts">
    <?php foreach (['companies' => t('ad_mod_companies'), 'jobs' => t('ad_mod_jobs'), 'both' => t('ad_mod_both')] as $mk => $ml): ?>
      <form method="post" class="inline-form">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="set_moderation">
        <input type="hidden" name="mode" value="<?= $mk ?>">
        <button class="mod-btn <?= $mode === $mk ? 'is-active' : '' ?>"<?= $mode === $mk ? ' aria-current="true"' : '' ?>><?= e($ml) ?></button>
      </form>
    <?php endforeach; ?>
  </div>
  <a class="mod-toggle__link" href="<?= url('admin/employers.php?status=pending') ?>"><?= e(t('ad_mod_pending_co', $pendingCompanies)) ?> →</a>
</div>

<nav class="tabs">
  <?php
    $tabLabels = [
      'pending'  => [t('ad_tab_pending'),   (int)$counts['pending']],
      'approved' => [t('ad_tab_published'), (int)$counts['approved']],
      'rejected' => [t('ad_tab_rejected'),  (int)$counts['rejected']],
      'all'      => [t('ad_tab_all'),        (int)$counts['total']],
    ];
    foreach ($tabLabels as $key => [$label, $n]):
  ?>
    <a class="tab <?= $tab === $key ? 'is-active' : '' ?>" href="<?= url('admin/dashboard.php?tab=' . $key) ?>">
      <?= e($label) ?><span class="count"><?= $n ?></span>
    </a>
  <?php endforeach; ?>
</nav>

<form method="get" action="<?= url('admin/dashboard.php') ?>" style="display:flex;gap:0.5rem;max-width:440px;margin-bottom:1.2rem">
  <input type="hidden" name="tab" value="<?= e($tab) ?>">
  <input type="search" name="q" value="<?= e($search) ?>" placeholder="<?= e(t('ad_search_ph')) ?>" style="flex:1;padding:0.5rem 0.9rem;border:1px solid var(--line-strong);border-radius:999px;font:inherit">
  <button class="btn btn--ghost btn--sm"><?= e(t('search_btn')) ?></button>
  <?php if ($search !== ''): ?><a class="btn btn--ghost btn--sm" href="<?= url('admin/dashboard.php?tab=' . e($tab)) ?>">✕</a><?php endif; ?>
</form>

<div class="job-list">
  <?php if (empty($jobs)): ?>
    <div class="empty">
      <h3><?= e(t('ad_nothing')) ?></h3>
      <p><?= e(t('ad_no_postings')) ?></p>
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
          <span class="status status--<?= e($job['status']) ?>"><?= e($statusLabels[$job['status']] ?? $job['status']) ?></span>
          <?php if ($job['is_featured']): ?><span class="status" style="background:var(--honey-soft);color:var(--chestnut-deep)"><?= e(t('ad_featured')) ?></span><?php endif; ?>
          <?php if (!empty($job['title_ar'])): ?><span class="status" style="background:#e6eef7;color:#1D5C9D">AR</span><?php endif; ?>
          <?php if (!empty($job['expires_at']) && $job['expires_at'] < date('Y-m-d')): ?><span class="status" style="background:#eee;color:#888"><?= e(t('ad_expired')) ?></span><?php endif; ?>
        </div>
        <div class="jrow__sub"><?= e($job['company_name']) ?> · <?= e($job['location']) ?> · <?= e($job['company_email']) ?></div>
        <div class="jrow__meta">
          <span class="pill"><?= e($job['job_type']) ?></span>
          <?php if ($job['category_name']): ?><span class="pill"><?= e($job['category_name']) ?></span><?php endif; ?>
          <?php if ($salary): ?><span class="pill"><?= e($salary) ?></span><?php endif; ?>
          <span class="pill"><?= e(t('posted')) ?> <?= e(time_ago($job['created_at'])) ?></span>
        </div>
      </div>

      <div class="jrow__actions">
        <?php if ($job['status'] === 'approved'): ?>
          <a href="<?= url('job.php?id=' . $job['id']) ?>" target="_blank" class="btn btn--ghost btn--sm"><?= e(t('ad_view')) ?> ↗</a>
        <?php endif; ?>

        <a href="<?= url('admin/edit-job.php?id=' . $job['id']) ?>" class="btn btn--ghost btn--sm"><?= e(t('ad_edit')) ?></a>

        <?php if ($job['status'] !== 'approved'): ?>
          <form method="post" class="inline-form">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="approve">
            <input type="hidden" name="job_id" value="<?= (int)$job['id'] ?>">
            <input type="hidden" name="return" value="<?= e($tab) ?>">
            <button class="btn btn--green btn--sm"><?= e(t('ad_approve')) ?></button>
          </form>
        <?php endif; ?>

        <?php if ($job['status'] === 'approved'): ?>
          <form method="post" class="inline-form">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="feature">
            <input type="hidden" name="job_id" value="<?= (int)$job['id'] ?>">
            <input type="hidden" name="return" value="<?= e($tab) ?>">
            <button class="btn btn--ghost btn--sm"><?= $job['is_featured'] ? e(t('ad_unfeature')) : e(t('ad_feature')) ?></button>
          </form>
          <form method="post" class="inline-form">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="unpublish">
            <input type="hidden" name="job_id" value="<?= (int)$job['id'] ?>">
            <input type="hidden" name="return" value="<?= e($tab) ?>">
            <button class="btn btn--ghost btn--sm"><?= e(t('ad_unpublish')) ?></button>
          </form>
        <?php endif; ?>

        <?php if ($job['status'] !== 'rejected'): ?>
          <form method="post" class="inline-form">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="reject">
            <input type="hidden" name="job_id" value="<?= (int)$job['id'] ?>">
            <input type="hidden" name="return" value="<?= e($tab) ?>">
            <button class="btn btn--red btn--sm"><?= e(t('ad_reject')) ?></button>
          </form>
        <?php endif; ?>

        <form method="post" class="inline-form" data-confirm="<?= e(t('ad_confirm_delete')) ?>">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="job_id" value="<?= (int)$job['id'] ?>">
          <input type="hidden" name="return" value="<?= e($tab) ?>">
          <button class="btn btn--red btn--sm"><?= e(t('ad_delete')) ?></button>
        </form>
      </div>
    </article>
  <?php endforeach; ?>
</div>

<?php require __DIR__ . '/includes/admin-footer.php'; ?>
