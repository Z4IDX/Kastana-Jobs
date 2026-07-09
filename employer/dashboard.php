<?php
require_once __DIR__ . '/../config/config.php';
require_employer();

$empId = current_employer_id();
$stmt = db()->prepare(
    "SELECT j.*, c.name AS category_name, c.name_ar AS category_name_ar
     FROM jobs j LEFT JOIN categories c ON c.id = j.category_id
     WHERE j.employer_id = ? ORDER BY j.created_at DESC"
);
$stmt->execute([$empId]);
$jobs = $stmt->fetchAll();

// On-site notifications: show unread ones as a popup, then mark them read.
$notifs = unread_notifications($empId);
if ($notifs) mark_notifications_read($empId);

$page_title = t('emp_dash_title');
require __DIR__ . '/../includes/header.php';

$statusLabel = function (array $j): array {
    if ($j['status'] === 'approved' && !empty($j['expires_at']) && $j['expires_at'] < date('Y-m-d')) {
        return ['st_expired', 'status--rejected'];
    }
    return match ($j['status']) {
        'approved' => ['st_approved', 'status--approved'],
        'rejected' => ['st_rejected', 'status--rejected'],
        default    => ['st_pending', 'status--pending'],
    };
};
?>
<?php if ($notifs): ?>
<div class="toast-stack" id="toast-stack" aria-live="polite">
  <?php foreach ($notifs as $n): $ok = $n['type'] === 'approved'; ?>
    <div class="toast toast--<?= $ok ? 'success' : 'info' ?>" role="status">
      <button type="button" class="toast__close" aria-label="<?= e(t('notif_dismiss')) ?>">✕</button>
      <strong><?= e($ok ? t('notif_approved_title') : t('notif_rejected_title')) ?></strong>
      <p><?= e(t($ok ? 'notif_approved_body' : 'notif_rejected_body', $n['title'] ?? '')) ?></p>
    </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>
<section class="board wrap">
  <div class="board__head">
    <div>
      <span class="eyebrow"><?= e(t('emp_greeting', $_SESSION['employer_name'] ?? '')) ?></span>
      <h2><?= e(t('emp_dash_title')) ?></h2>
      <p style="color:var(--ink-soft);margin-top:0.3rem"><?= e(t('emp_dash_lede')) ?></p>
    </div>
    <div style="display:flex;gap:0.6rem;align-items:center;flex-wrap:wrap">
      <a href="<?= url('employer/post-job.php') ?>" class="btn btn--primary btn--sm">+ <?= e(t('emp_new_posting')) ?></a>
      <a href="<?= url('employer/logout.php') ?>" class="btn btn--ghost btn--sm"><?= e(t('emp_logout')) ?></a>
    </div>
  </div>

  <?php foreach (flash_get() as $f): ?>
    <div class="alert alert--<?= e($f['type']) ?>"><?= e($f['message']) ?></div>
  <?php endforeach; ?>

  <?php if (empty($jobs)): ?>
    <div class="empty"><h3><?= e(t('emp_no_postings')) ?></h3><p><a href="<?= url('employer/post-job.php') ?>">+ <?= e(t('emp_new_posting')) ?></a></p></div>
  <?php endif; ?>

  <div class="jobs-grid">
    <?php foreach ($jobs as $job):
        [$stKey, $stClass] = $statusLabel($job);
        $title = job_field($job, 'title');
        $salary = format_salary($job['salary_min'] ?: null, $job['salary_max'] ?: null, $job['salary_currency']);
    ?>
      <article class="job-card">
        <div class="job-card__top">
          <span class="status <?= $stClass ?>"><?= e(t($stKey)) ?></span>
          <div class="job-card__stamp"><?= e(time_ago($job['created_at'])) ?></div>
        </div>
        <h3 class="job-card__title"><?= e($title) ?></h3>
        <div class="job-card__company"><?= e($job['company_name']) ?> · <?= e(job_field($job, 'location')) ?></div>
        <div class="job-card__meta">
          <span class="tag"><?= e(job_type_label($job['job_type'])) ?></span>
          <?php if ($salary): ?><span class="tag tag--salary"><?= e($salary) ?></span><?php endif; ?>
        </div>
        <div class="job-card__foot">
          <?php if ($job['status'] === 'approved'): ?>
            <a class="tag" href="<?= url('job.php?id=' . $job['id']) ?>" target="_blank"><?= e(t('view_role')) ?> ↗</a>
          <?php else: ?><span></span><?php endif; ?>
          <a class="job-card__link" href="<?= url('employer/post-job.php?id=' . $job['id']) ?>"><?= e(t('emp_edit')) ?> <span class="dir-arrow">→</span></a>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
