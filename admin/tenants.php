<?php
require_once __DIR__ . '/../config/config.php';
require_super_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = input($_POST, 'action');
    $id = filter_input(INPUT_POST, 'tenant_id', FILTER_VALIDATE_INT);
    if ($id) {
        if ($action === 'activate') {
            db()->prepare("UPDATE tenants SET status='active', activated_at=COALESCE(activated_at, NOW()) WHERE id=?")->execute([$id]);
            flash_set('success', 'Company activated — their board is now live.');
        } elseif ($action === 'suspend') {
            db()->prepare("UPDATE tenants SET status='suspended' WHERE id=?")->execute([$id]);
            flash_set('info', 'Company suspended — their board is offline.');
        }
    }
    redirect('admin/tenants.php');
}

$tenants = db()->query(
    "SELECT t.*,
            (SELECT COUNT(*) FROM jobs   j WHERE j.tenant_id = t.id) AS job_count,
            (SELECT COUNT(*) FROM admins a WHERE a.tenant_id = t.id) AS admin_count
     FROM tenants t ORDER BY FIELD(t.status,'pending','active','suspended'), t.created_at DESC"
)->fetchAll();

$statusStyle = [
    'pending'   => 'background:var(--amber-bg);color:var(--amber)',
    'active'    => 'background:var(--green-bg);color:var(--green)',
    'suspended' => 'background:var(--red-bg);color:var(--red)',
];

$admin_title = 'Companies';
require __DIR__ . '/includes/admin-header.php';
?>
<div class="page-head">
  <div><h1>Companies</h1><p>Every company on the platform. Activate a pending signup to switch its board on.</p></div>
</div>

<?php foreach (flash_get() as $f): ?><div class="alert alert--<?= e($f['type']) ?>"><?= e($f['message']) ?></div><?php endforeach; ?>

<div class="job-list">
  <?php if (empty($tenants)): ?>
    <div class="empty"><h3>No companies yet</h3><p>Signups from the platform home will appear here.</p></div>
  <?php endif; ?>
  <?php foreach ($tenants as $t): ?>
    <article class="jrow">
      <div class="jrow__main">
        <div class="jrow__title">
          <?= e($t['name']) ?>
          <span class="status" style="<?= $statusStyle[$t['status']] ?? '' ?>"><?= e($t['status']) ?></span>
        </div>
        <div class="jrow__sub"><a href="//<?= e($t['subdomain']) ?>.<?= e(APP_DOMAIN) ?>/" target="_blank"><?= e($t['subdomain']) ?>.<?= e(APP_DOMAIN) ?> ↗</a></div>
        <div class="jrow__meta">
          <span class="pill"><?= (int)$t['job_count'] ?> jobs</span>
          <span class="pill"><?= (int)$t['admin_count'] ?> admins</span>
          <span class="pill">Joined <?= e(time_ago($t['created_at'])) ?></span>
        </div>
      </div>
      <div class="jrow__actions">
        <?php if ($t['status'] !== 'active'): ?>
          <form method="post" class="inline-form">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="activate">
            <input type="hidden" name="tenant_id" value="<?= (int)$t['id'] ?>">
            <button class="btn btn--green btn--sm">Activate</button>
          </form>
        <?php endif; ?>
        <?php if ($t['status'] === 'active'): ?>
          <form method="post" class="inline-form" data-confirm="Suspend this company? Their board goes offline.">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="suspend">
            <input type="hidden" name="tenant_id" value="<?= (int)$t['id'] ?>">
            <button class="btn btn--red btn--sm">Suspend</button>
          </form>
        <?php endif; ?>
      </div>
    </article>
  <?php endforeach; ?>
</div>
<?php require __DIR__ . '/includes/admin-footer.php'; ?>
