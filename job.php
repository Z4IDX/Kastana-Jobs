<?php
require_once __DIR__ . '/config/config.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) redirect('index.php');

$stmt = db()->prepare(
    "SELECT j.*, c.name AS category_name, c.name_ar AS category_name_ar
     FROM jobs j LEFT JOIN categories c ON c.id = j.category_id
     WHERE j.id = ? AND j.tenant_id = ? AND j.status = 'approved'
       AND (j.expires_at IS NULL OR j.expires_at >= CURDATE()) LIMIT 1"
);
$stmt->execute([$id, current_tenant_id()]);
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

/* ---------- Apply-through-Kastana contact form ---------- */
$applyErrors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply_job_id'])) {
    require_csrf();
    if (input($_POST, 'hp_confirm') !== '') {          // honeypot
        flash_set('success', t('apply_ok'));
        redirect('job.php?id=' . $id);
    }
    $aName  = input($_POST, 'applicant_name');
    $aEmail = input($_POST, 'applicant_email');
    $aPhone = input($_POST, 'applicant_phone');
    $aNote  = input($_POST, 'applicant_note');

    if (mb_strlen($aName) < 2) $applyErrors[] = t('err_app_name');
    if (!filter_var($aEmail, FILTER_VALIDATE_EMAIL)) $applyErrors[] = t('err_app_email');
    if ($aPhone !== '' && !preg_match('/^[0-9+\-\s()]{6,25}$/', $aPhone)) $applyErrors[] = t('err_app_phone');

    if (empty($applyErrors)) {
        db()->prepare("INSERT INTO applicants (tenant_id, job_id, name, email, phone, cover_note) VALUES (?,?,?,?,?,?)")
            ->execute([current_tenant_id(), $id, $aName, $aEmail, $aPhone ?: null, $aNote ?: null]);
        flash_set('success', t('apply_ok'));
        redirect('job.php?id=' . $id);
    }
}

$salary  = format_salary($job['salary_min'] ?: null, $job['salary_max'] ?: null, $job['salary_currency']);
$initial = strtoupper(mb_substr($job['company_name'], 0, 1));
$title   = job_field($job, 'title');
$loc     = job_field($job, 'location');
$thumb   = $job['thumbnail_path'] ?: $job['image_path'];

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
      </dl>

      <?php if (!empty($job['apply_url'])): ?>
        <a href="<?= e($job['apply_url']) ?>" target="_blank" rel="noopener noreferrer nofollow" class="btn btn--primary btn--block"><?= e(t('apply_now')) ?> ↗</a>
      <?php else: ?>
        <a href="mailto:<?= e($job['company_email']) ?>?subject=<?= rawurlencode('Application: ' . $title) ?>" class="btn btn--primary btn--block"><?= e(t('apply_email')) ?></a>
      <?php endif; ?>

      <button type="button" class="btn btn--ghost btn--block" id="copy-apply-link" style="margin-top:0.6rem"
              data-copy-url="<?= e(url('job.php?id=' . $job['id'])) ?>"
              data-label="<?= e(t('copy_link')) ?>" data-label-done="<?= e(t('copy_link_done')) ?>">
        <?= e(t('copy_link')) ?>
      </button>

      <p style="text-align:center;font-size:0.8rem;color:var(--ink-faint);margin-top:0.9rem"><?= e(t('mention', APP_NAME)) ?></p>

      <?php foreach (flash_get() as $f): ?>
        <div class="alert alert--<?= e($f['type']) ?>" style="margin-top:1rem"><?= e($f['message']) ?></div>
      <?php endforeach; ?>
      <?php if (!empty($applyErrors)): ?>
        <div class="alert alert--error" style="margin-top:1rem">
          <ul class="error-list"><?php foreach ($applyErrors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
        </div>
      <?php endif; ?>

      <h3 style="font-family:var(--font-display);font-size:1.05rem;margin:1.5rem 0 0.5rem"><?= e(t('apply_kastana_title', APP_NAME)) ?></h3>
      <p class="hint"><?= e(t('apply_kastana_lede')) ?></p>
      <form method="post" action="<?= url('job.php?id=' . $job['id']) ?>" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="apply_job_id" value="<?= (int)$job['id'] ?>">
        <div class="hp-field" aria-hidden="true"><label>Leave empty<input type="text" name="hp_confirm" tabindex="-1" autocomplete="off"></label></div>
        <div class="field"><label><?= e(t('f_app_name')) ?> <span class="req">*</span></label><input type="text" name="applicant_name" required></div>
        <div class="field"><label><?= e(t('f_app_email')) ?> <span class="req">*</span></label><input type="email" name="applicant_email" required></div>
        <div class="field"><label><?= e(t('f_app_phone')) ?> <span class="hint"><?= e(t('f_optional')) ?></span></label><input type="tel" name="applicant_phone"></div>
        <div class="field"><label><?= e(t('f_app_note')) ?> <span class="hint"><?= e(t('f_optional')) ?></span></label><textarea name="applicant_note"></textarea></div>
        <button type="submit" class="btn btn--primary btn--block"><?= e(t('f_app_submit')) ?></button>
      </form>
    </aside>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
