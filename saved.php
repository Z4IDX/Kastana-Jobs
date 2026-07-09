<?php
require_once __DIR__ . '/config/config.php';

$ids = saved_job_ids();
$jobs = [];
if ($ids) {
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = db()->prepare(
        "SELECT j.*, c.name AS category_name, c.name_ar AS category_name_ar, c.slug AS category_slug
         FROM jobs j LEFT JOIN categories c ON c.id=j.category_id
         WHERE j.id IN ($placeholders) AND j.status='approved'
           AND (j.expires_at IS NULL OR j.expires_at >= CURDATE())
         ORDER BY j.created_at DESC"
    );
    $stmt->execute($ids);
    $jobs = $stmt->fetchAll();
}

$page_title = t('nav_saved');
require __DIR__ . '/includes/header.php';
?>

<section class="board wrap" id="roles">
  <div class="board__head">
    <div><h2><?= e(t('nav_saved')) ?></h2></div>
  </div>
  <div class="jobs-grid">
    <?php foreach ($jobs as $job):
        $salary  = format_salary($job['salary_min'] ?: null, $job['salary_max'] ?: null, $job['salary_currency']);
        $initial = strtoupper(mb_substr($job['company_name'], 0, 1));
        $title   = job_field($job, 'title');
        $loc     = job_field($job, 'location');
        include __DIR__ . '/includes/job-card.php';
    endforeach; ?>

    <?php if (empty($jobs)): ?>
      <div class="empty">
        <h3><?= e(t('saved_empty_title')) ?></h3>
        <p><?= e(t('saved_empty_body')) ?></p>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
