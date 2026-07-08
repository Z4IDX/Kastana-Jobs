<?php
require_once __DIR__ . '/../config/config.php';
require_login();

$tid = current_tenant_id();
$perPage = 40;
$page = max(1, (int)($_GET['page'] ?? 1));
$totalStmt = db()->prepare("SELECT COUNT(*) FROM activity_log WHERE tenant_id = ?");
$totalStmt->execute([$tid]);
$total = (int) $totalStmt->fetchColumn();
$totalPages = max(1, (int) ceil($total / $perPage));
$page = min($page, $totalPages);

$stmt = db()->prepare(
    "SELECT al.*, ad.username FROM activity_log al
     LEFT JOIN admins ad ON ad.id = al.admin_id
     WHERE al.tenant_id = ?
     ORDER BY al.created_at DESC LIMIT ? OFFSET ?"
);
$stmt->bindValue(1, $tid, PDO::PARAM_INT);
$stmt->bindValue(2, $perPage, PDO::PARAM_INT);
$stmt->bindValue(3, ($page - 1) * $perPage, PDO::PARAM_INT);
$stmt->execute();
$logs = $stmt->fetchAll();

$admin_title = 'Activity log';
require __DIR__ . '/includes/admin-header.php';
?>

<div class="page-head">
  <div>
    <h1>Activity log</h1>
    <p>Every approve/reject/edit/delete action, most recent first.</p>
  </div>
</div>

<div class="job-list">
  <?php if (empty($logs)): ?>
    <div class="empty"><h3>No activity yet</h3></div>
  <?php endif; ?>
  <?php foreach ($logs as $log): ?>
    <article class="jrow">
      <div class="jrow__main">
        <div class="jrow__title">
          <span class="pill"><?= e($log['action']) ?></span>
          <?= e($log['details'] ?: '(posting removed)') ?>
        </div>
        <div class="jrow__sub">
          <?= e($log['username'] ?: 'Unknown admin') ?> ·
          <?= e(date('M j, Y g:ia', strtotime($log['created_at']))) ?>
          <?php if ($log['job_id']): ?> · <a href="<?= url('admin/edit-job.php?id=' . $log['job_id']) ?>">View posting</a><?php endif; ?>
        </div>
      </div>
    </article>
  <?php endforeach; ?>
</div>

<?php if ($totalPages > 1): ?>
  <nav class="pager" aria-label="Pagination">
    <?php if ($page > 1): ?><a class="btn btn--ghost btn--sm" href="<?= url('admin/activity-log.php?page=' . ($page - 1)) ?>">← Prev</a><?php endif; ?>
    <span>Page <?= $page ?> of <?= $totalPages ?></span>
    <?php if ($page < $totalPages): ?><a class="btn btn--ghost btn--sm" href="<?= url('admin/activity-log.php?page=' . ($page + 1)) ?>">Next →</a><?php endif; ?>
  </nav>
<?php endif; ?>

<?php require __DIR__ . '/includes/admin-footer.php'; ?>
