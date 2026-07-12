<?php
/**
 * Company page — all approved, live roles for one company (grouped by company_name,
 * since company data is denormalised on the jobs table). Read-only, no account needed.
 */
require_once __DIR__ . '/config/config.php';

$company = trim((string) ($_GET['c'] ?? ''));
if ($company === '') redirect('index.php');

$stmt = db()->prepare(
    "SELECT j.*, c.name AS category_name, c.name_ar AS category_name_ar, c.slug AS category_slug
     FROM jobs j LEFT JOIN categories c ON c.id = j.category_id
     WHERE j.company_name = ? AND j.status = 'approved'
       AND (j.expires_at IS NULL OR j.expires_at >= CURDATE())
     ORDER BY j.is_featured DESC, j.created_at DESC"
);
$stmt->execute([$company]);
$jobs = $stmt->fetchAll();

if (!$jobs) {
    http_response_code(404);
    $page_title = $company;
    require __DIR__ . '/includes/header.php';
    echo '<section class="wrap job-detail"><a class="back-link" href="' . url('index.php') . '"><span class="dir-arrow">←</span> ' . e(t('back_all')) . '</a>'
       . '<div class="empty"><h3>' . e($company) . '</h3><p>' . e(t('company_no_jobs')) . '</p></div></section>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

// Derive company logo + website from its postings.
$website = '';
$logo    = '';
foreach ($jobs as $jj) {
    if ($website === '' && !empty($jj['company_website'])) $website = $jj['company_website'];
    if ($logo === '' && ($jj['thumbnail_path'] ?: $jj['image_path'])) $logo = $jj['thumbnail_path'] ?: $jj['image_path'];
}

$page_title = $company;
$page_desc  = $company . ' — ' . t('company_jobs_count', count($jobs));
require __DIR__ . '/includes/header.php';
?>
<section class="wrap company-page">
  <a class="back-link" href="<?= url('index.php') ?>"><span class="dir-arrow">←</span> <?= e(t('back_all')) ?></a>

  <header class="company-head">
    <div class="company-head__logo" aria-hidden="true"><?php if ($logo): ?><img src="<?= url($logo) ?>" alt="" decoding="async" width="64" height="64"><?php else: ?><?= e(strtoupper(mb_substr($company, 0, 1))) ?><?php endif; ?></div>
    <div>
      <h1><?= e($company) ?></h1>
      <p class="company-head__meta">
        <?= e(t('company_jobs_count', count($jobs))) ?>
        <?php if ($website): ?> · <a href="<?= e($website) ?>" target="_blank" rel="noopener noreferrer nofollow"><?= e(t('visit_website')) ?> ↗</a><?php endif; ?>
      </p>
    </div>
  </header>

  <div class="jobs-grid">
    <?php foreach ($jobs as $job):
        $salary  = format_salary($job['salary_min'] ?: null, $job['salary_max'] ?: null, $job['salary_currency']);
        $initial = strtoupper(mb_substr($job['company_name'], 0, 1));
        $title   = job_field($job, 'title');
        $loc     = job_field($job, 'location');
        include __DIR__ . '/includes/job-card.php';
    endforeach; ?>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
