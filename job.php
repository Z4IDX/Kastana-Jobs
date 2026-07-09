<?php
require_once __DIR__ . '/config/config.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) redirect('index.php');

$stmt = db()->prepare(
    "SELECT j.*, c.name AS category_name, c.name_ar AS category_name_ar
     FROM jobs j LEFT JOIN categories c ON c.id = j.category_id
     WHERE j.id = ? AND j.status = 'approved'
       AND (j.expires_at IS NULL OR j.expires_at >= CURDATE()) LIMIT 1"
);
$stmt->execute([$id]);
$job = $stmt->fetch();

if (!$job) {
    http_response_code(404);
    $page_title = t('nf_title');
    require __DIR__ . '/includes/header.php';
    echo '<section class="wrap job-detail"><a class="back-link" href="' . url('index.php') . '"><span class="dir-arrow">←</span> ' . e(t('back_all')) . '</a>'
       . '<div class="empty"><h3>' . e(t('nf_title')) . '</h3><p>' . e(t('nf_body')) . '</p></div></section>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

$salary  = format_salary($job['salary_min'] ?: null, $job['salary_max'] ?: null, $job['salary_currency']);
$initial = strtoupper(mb_substr($job['company_name'], 0, 1));
$title   = job_field($job, 'title');
$loc     = job_field($job, 'location');
$thumb   = $job['thumbnail_path'] ?: $job['image_path'];
$phone   = trim((string) ($job['company_phone'] ?? ''));

$page_title = $title . ' — ' . $job['company_name'];
$page_desc  = $title . ' · ' . $job['company_name'] . ' · ' . $loc;
require __DIR__ . '/includes/header.php';
?>

<section class="wrap job-detail">
  <a class="back-link" href="<?= url('index.php') ?>"><span class="dir-arrow">←</span> <?= e(t('back_all')) ?></a>

  <div class="job-detail__grid">
    <div>
      <div class="job-detail__header">
        <div class="job-detail__logo" aria-hidden="true"><?php if (!empty($thumb)): ?><img src="<?= url($thumb) ?>" alt=""><?php else: ?><?= e($initial) ?><?php endif; ?></div>
        <div>
          <h1><?= e($title) ?></h1>
          <div class="job-detail__company"><?= e($job['company_name']) ?> · <?= e($loc) ?></div>
        </div>
      </div>

      <div class="job-card__meta" style="margin-bottom:2rem">
        <span class="tag"><?= e(job_type_label($job['job_type'])) ?></span>
        <?php if ($job['category_name']): ?><span class="tag"><?= e(cat_name($job['category_name'], $job['category_name_ar'])) ?></span><?php endif; ?>
        <?php if ($salary): ?><span class="tag tag--salary"><?= e($salary) ?></span><?php endif; ?>
        <span class="tag"><?= e(t('posted')) ?> <?= e(time_ago($job['created_at'])) ?></span>
      </div>

      <div class="prose">
        <h2><?= e(t('about_role')) ?></h2>
        <?= render_text(job_field($job, 'description')) ?>
        <?php if (trim(job_field($job, 'requirements')) !== ''): ?>
          <h2><?= e(t('looking_for')) ?></h2>
          <?= render_text(job_field($job, 'requirements')) ?>
        <?php endif; ?>
        <h2><?= e(t('how_apply')) ?></h2>
        <?= render_text(job_field($job, 'how_to_apply')) ?>
      </div>
    </div>

    <aside class="job-aside">
      <dl>
        <div>
          <dt><?= e(t('a_company')) ?></dt>
          <dd>
            <?php if (!empty($job['company_website'])): ?>
              <a href="<?= e($job['company_website']) ?>" target="_blank" rel="noopener noreferrer nofollow"><?= e($job['company_name']) ?> ↗</a>
            <?php else: ?><?= e($job['company_name']) ?><?php endif; ?>
          </dd>
        </div>
        <div><dt><?= e(t('a_location')) ?></dt><dd><?= e($loc) ?></dd></div>
        <div><dt><?= e(t('a_type')) ?></dt><dd><?= e(job_type_label($job['job_type'])) ?></dd></div>
        <?php if ($salary): ?><div><dt><?= e(t('a_salary')) ?></dt><dd><?= e($salary) ?></dd></div><?php endif; ?>
        <?php if ($phone !== ''): ?><div><dt><?= e(t('a_phone')) ?></dt><dd><a href="tel:<?= e(preg_replace('/[^0-9+]/', '', $phone)) ?>" dir="ltr"><?= e($phone) ?></a></dd></div><?php endif; ?>
      </dl>

      <h3 style="font-family:var(--font-display);font-size:1.05rem;margin:0 0 0.3rem"><?= e(t('apply_contact_title')) ?></h3>
      <p class="hint" style="margin-bottom:0.9rem"><?= e(t('apply_contact_lede')) ?></p>

      <?php if (!empty($job['apply_url'])): ?>
        <a href="<?= e($job['apply_url']) ?>" target="_blank" rel="noopener noreferrer nofollow" class="btn btn--primary btn--block"><?= e(t('apply_now')) ?> ↗</a>
      <?php else: ?>
        <a href="mailto:<?= e($job['company_email']) ?>?subject=<?= rawurlencode('Application: ' . $title) ?>" class="btn btn--primary btn--block"><?= e(t('apply_email')) ?></a>
      <?php endif; ?>

      <?php if ($phone !== ''): ?>
        <a href="tel:<?= e(preg_replace('/[^0-9+]/', '', $phone)) ?>" class="btn btn--honey btn--block" style="margin-top:0.6rem"><?= e(t('apply_call')) ?></a>
      <?php endif; ?>

      <button type="button" class="btn btn--ghost btn--block" id="copy-apply-link" style="margin-top:0.6rem"
              data-copy-url="<?= e(url('job.php?id=' . $job['id'])) ?>"
              data-label="<?= e(t('copy_link')) ?>" data-label-done="<?= e(t('copy_link_done')) ?>">
        <?= e(t('copy_link')) ?>
      </button>

      <p style="text-align:center;font-size:0.8rem;color:var(--ink-faint);margin-top:0.9rem"><?= e(t('mention', APP_NAME)) ?></p>
    </aside>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
