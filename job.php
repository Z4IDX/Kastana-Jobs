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

// Count a view once per session per job (avoids refresh/reload inflation).
if (empty($_SESSION['viewed_jobs'][$id])) {
    db()->prepare("UPDATE jobs SET views = views + 1 WHERE id = ?")->execute([$id]);
    $_SESSION['viewed_jobs'][$id] = true;
    $job['views'] = (int) $job['views'] + 1;
}

// Maintain an ordered, capped list of recently-viewed ids for the homepage strip.
$__recent = array_values(array_diff($_SESSION['recent_jobs'] ?? [], [$id]));
array_unshift($__recent, $id);
$_SESSION['recent_jobs'] = array_slice($__recent, 0, 6);

$salary  = format_salary($job['salary_min'] ?: null, $job['salary_max'] ?: null, $job['salary_currency']);
$initial = strtoupper(mb_substr($job['company_name'], 0, 1));
$title   = job_field($job, 'title');
$loc     = job_field($job, 'location');
$thumb   = $job['thumbnail_path'] ?: $job['image_path'];
$phone   = trim((string) ($job['company_phone'] ?? ''));

// Absolute URL for sharing (WhatsApp needs a full link, not a relative path).
$scheme = (defined('USE_HTTPS') && USE_HTTPS) ? 'https' : 'http';
$absUrl = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? '') . url('job.php?id=' . $job['id']);
$waHref = 'https://wa.me/?text=' . rawurlencode($title . ' — ' . $job['company_name'] . "\n" . $absUrl);

// Similar roles: same category first, then same job type; newest/featured/most-viewed.
$simStmt = db()->prepare(
    "SELECT j.* FROM jobs j
     WHERE j.status='approved' AND (j.expires_at IS NULL OR j.expires_at >= CURDATE())
       AND j.id <> ? AND (j.category_id = ? OR j.job_type = ?)
     ORDER BY (j.category_id <=> ?) DESC, j.is_featured DESC, j.views DESC
     LIMIT 3"
);
$simStmt->execute([$job['id'], $job['category_id'], $job['job_type'], $job['category_id']]);
$similarJobs = $simStmt->fetchAll();

$page_title = $title . ' — ' . $job['company_name'];
$page_desc  = $title . ' · ' . $job['company_name'] . ' · ' . $loc;
require __DIR__ . '/includes/header.php';
?>

<section class="wrap job-detail">
  <a class="back-link" href="<?= url('index.php') ?>"><span class="dir-arrow">←</span> <?= e(t('back_all')) ?></a>

  <div class="job-detail__grid">
    <div>
      <div class="job-detail__header">
        <div class="job-detail__logo" aria-hidden="true"><?php if (!empty($thumb)): ?><img src="<?= url($thumb) ?>" alt="" decoding="async" width="68" height="68"><?php else: ?><?= e($initial) ?><?php endif; ?></div>
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
        <span class="tag"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/></svg><?= e(t('views_count', (int) $job['views'])) ?></span>
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
            <a href="<?= e(url('company.php?c=' . rawurlencode($job['company_name']))) ?>"><?= e($job['company_name']) ?></a>
            <?php if (!empty($job['company_website'])): ?><br><a href="<?= e($job['company_website']) ?>" target="_blank" rel="noopener noreferrer nofollow" style="font-weight:400;font-size:0.85rem"><?= e(t('visit_website')) ?> ↗</a><?php endif; ?>
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

      <a href="<?= e($waHref) ?>" target="_blank" rel="noopener noreferrer" class="btn btn--ghost btn--block" style="margin-top:0.6rem">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2a10 10 0 0 0-8.6 15L2 22l5.1-1.3A10 10 0 1 0 12 2zm0 18a8 8 0 0 1-4.1-1.1l-.3-.2-3 .8.8-2.9-.2-.3A8 8 0 1 1 12 20zm4.5-5.6c-.2-.1-1.5-.7-1.7-.8-.2-.1-.4-.1-.6.1-.2.3-.6.8-.8 1-.1.1-.3.2-.5.1a6.6 6.6 0 0 1-3.3-2.9c-.1-.2 0-.4.1-.5l.4-.5c.1-.2.1-.3.2-.5v-.4l-.8-1.9c-.2-.5-.4-.4-.6-.4h-.5c-.2 0-.4.1-.6.3-.7.7-.9 1.6-.6 2.7.4 1.3 1.2 2.5 2.3 3.5 1.6 1.4 3 1.8 3.7 2 .5.1 1 .1 1.4-.1.4-.2 1.2-.7 1.4-1.3.1-.3.1-.6.1-.7-.1-.1-.2-.2-.4-.2z"/></svg>
        <?= e(t('share_whatsapp')) ?>
      </a>

      <button type="button" class="btn btn--ghost btn--block" data-web-share
              data-share-url="<?= e($absUrl) ?>" data-share-title="<?= e($title . ' — ' . $job['company_name']) ?>"
              style="margin-top:0.6rem;display:none">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="M8.6 13.5l6.8 4M15.4 6.5l-6.8 4"/></svg>
        <?= e(t('share_label')) ?>
      </button>

      <form method="post" action="<?= url('save.php') ?>" class="save-form save-form--inline" style="margin-top:0.6rem">
        <?= csrf_field() ?>
        <input type="hidden" name="job_id" value="<?= (int)$job['id'] ?>">
        <input type="hidden" name="save_action" value="<?= is_job_saved($job['id']) ? 'unsave' : 'save' ?>">
        <input type="hidden" name="return_url" value="<?= e($_SERVER['REQUEST_URI']) ?>">
        <button type="submit" class="save-btn save-btn--wide <?= is_job_saved($job['id']) ? 'is-saved' : '' ?>" aria-pressed="<?= is_job_saved($job['id']) ? 'true' : 'false' ?>"
                aria-label="<?= is_job_saved($job['id']) ? e(t('unsave_job')) : e(t('save_job')) ?>"
                data-label-save="<?= e(t('save_job')) ?>" data-label-unsave="<?= e(t('unsave_job')) ?>"
                data-text-save="<?= e(t('save_this_job')) ?>" data-text-unsave="<?= e(t('saved_label')) ?>">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 4h12a1 1 0 0 1 1 1v15l-7-4-7 4V5a1 1 0 0 1 1-1z"/></svg>
          <span data-save-text><?= is_job_saved($job['id']) ? e(t('saved_label')) : e(t('save_this_job')) ?></span>
        </button>
      </form>

      <p style="text-align:center;font-size:0.8rem;color:var(--ink-faint);margin-top:0.9rem"><?= e(t('mention', APP_NAME)) ?></p>
    </aside>
  </div>

  <?php if ($similarJobs): ?>
  <div class="similar-roles">
    <h2 class="similar-roles__head"><?= e(t('similar_roles')) ?></h2>
    <div class="featured__grid">
      <?php foreach ($similarJobs as $sj):
          $sTitle = job_field($sj, 'title');
          $sLoc   = job_field($sj, 'location');
          $sThumb = $sj['thumbnail_path'] ?: $sj['image_path'];
          $sInit  = strtoupper(mb_substr($sj['company_name'], 0, 1));
      ?>
      <a class="fcard" href="<?= url('job.php?id=' . $sj['id']) ?>">
        <div class="fcard__top">
          <span class="fcard__logo" aria-hidden="true"><?php if (!empty($sThumb)): ?><img src="<?= url($sThumb) ?>" alt="" loading="lazy" decoding="async" width="38" height="38"><?php else: ?><?= e($sInit) ?><?php endif; ?></span>
          <span class="tag"><?= e(job_type_label($sj['job_type'])) ?></span>
        </div>
        <div class="fcard__title"><?= e($sTitle) ?></div>
        <div class="fcard__meta"><?= e($sj['company_name']) ?> · <?= e($sLoc) ?></div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>
</section>

<div class="apply-bar" aria-label="<?= e(t('apply_contact_title')) ?>">
  <div class="wrap apply-bar__inner">
    <?php if (!empty($job['apply_url'])): ?>
      <a href="<?= e($job['apply_url']) ?>" target="_blank" rel="noopener noreferrer nofollow" class="btn btn--primary apply-bar__primary"><?= e(t('apply_now')) ?> ↗</a>
    <?php else: ?>
      <a href="mailto:<?= e($job['company_email']) ?>?subject=<?= rawurlencode('Application: ' . $title) ?>" class="btn btn--primary apply-bar__primary"><?= e(t('apply_email')) ?></a>
    <?php endif; ?>
    <?php if ($phone !== ''): ?>
      <a href="tel:<?= e(preg_replace('/[^0-9+]/', '', $phone)) ?>" class="btn btn--honey" aria-label="<?= e(t('apply_call')) ?>"><?= e(t('apply_call')) ?></a>
    <?php endif; ?>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
