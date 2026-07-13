<?php
require_once __DIR__ . '/../config/config.php';
require_employer();

$empId = current_employer_id();

$st = db()->prepare("SELECT status FROM employers WHERE id = ?");
$st->execute([$empId]);
$empStatus = $st->fetchColumn() ?: 'pending';
$showPendingBanner = ($empStatus !== 'active') && in_array(moderation_mode(), ['both', 'companies'], true);

// Delete one of the employer's own postings.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && input($_POST, 'action') === 'delete') {
    require_csrf();
    $jid = filter_input(INPUT_POST, 'job_id', FILTER_VALIDATE_INT);
    if ($jid) {
        $row = db()->prepare("SELECT image_path, thumbnail_path FROM jobs WHERE id=? AND employer_id=?");
        $row->execute([$jid, $empId]);
        if ($r = $row->fetch()) {
            delete_uploaded_image($r['image_path'] ?: null);
            delete_uploaded_image($r['thumbnail_path'] ?: null);
            db()->prepare("DELETE FROM jobs WHERE id=? AND employer_id=?")->execute([$jid, $empId]);
            flash_set('success', t('emp_deleted_ok'));
        }
    }
    redirect('employer/dashboard.php');
}

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
<?php if ($showPendingBanner): ?>
<div class="wrap" style="margin-top:1.5rem"><div class="alert alert--info"><?= e(t('emp_pending_banner')) ?></div></div>
<?php endif; ?>
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
      <a href="<?= url('employer/account.php') ?>" class="btn btn--ghost btn--sm"><?= e(t('emp_account')) ?></a>
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
          <form method="post" data-confirm="<?= e(t('emp_confirm_delete')) ?>" style="margin:0">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="job_id" value="<?= (int)$job['id'] ?>">
            <button type="submit" class="tag" style="border:none;cursor:pointer;background:var(--red-bg,#fbeaea);color:var(--red,#c0392b)"><?= e(t('emp_delete')) ?></button>
          </form>
          <span style="display:flex;gap:0.8rem;align-items:center">
            <?php if ($job['status'] === 'approved'): ?><a class="tag" href="<?= url('job.php?id=' . $job['id']) ?>" target="_blank"><?= e(t('view_role')) ?> ↗</a><?php endif; ?>
            <a class="job-card__link" href="<?= url('employer/post-job.php?id=' . $job['id']) ?>"><?= e(t('emp_edit')) ?> <span class="dir-arrow">→</span></a>
          </span>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
