<?php
require_once __DIR__ . '/../config/config.php';
require_login();

$jobId = filter_input(INPUT_GET, 'job_id', FILTER_VALIDATE_INT);
if (!$jobId) redirect('admin/dashboard.php');

$jobStmt = db()->prepare("SELECT id, title, company_name FROM jobs WHERE id=? AND tenant_id=?");
$jobStmt->execute([$jobId, current_tenant_id()]);
$job = $jobStmt->fetch();
if (!$job) { flash_set('error', 'That posting no longer exists.'); redirect('admin/dashboard.php'); }

$stmt = db()->prepare("SELECT * FROM applicants WHERE job_id=? AND tenant_id=? ORDER BY created_at DESC");
$stmt->execute([$jobId, current_tenant_id()]);
$applicants = $stmt->fetchAll();

$admin_title = 'Applicants';
require __DIR__ . '/includes/admin-header.php';
?>

<div class="page-head">
  <div>
    <h1>Applicants — <?= e($job['title']) ?></h1>
    <p><?= e($job['company_name']) ?></p>
  </div>
  <a href="<?= url('admin/dashboard.php') ?>" class="btn btn--ghost">← Back</a>
</div>

<div class="job-list">
  <?php if (empty($applicants)): ?>
    <div class="empty"><h3>No applicants yet</h3></div>
  <?php endif; ?>
  <?php foreach ($applicants as $a): ?>
    <article class="jrow">
      <div class="jrow__main">
        <div class="jrow__title"><?= e($a['name']) ?></div>
        <div class="jrow__sub">
          <a href="mailto:<?= e($a['email']) ?>"><?= e($a['email']) ?></a>
          <?php if ($a['phone']): ?> · <?= e($a['phone']) ?><?php endif; ?>
        </div>
        <?php if ($a['cover_note']): ?><div style="margin-top:0.5rem"><?= render_text($a['cover_note']) ?></div><?php endif; ?>
        <div class="jrow__meta"><span class="pill">Applied <?= e(time_ago($a['created_at'])) ?></span></div>
      </div>
    </article>
  <?php endforeach; ?>
</div>

<?php require __DIR__ . '/includes/admin-footer.php'; ?>
