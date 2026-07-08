<?php
/**
 * Job card partial. Expects $job plus the precomputed $salary/$initial/$title/$loc
 * variables from the including page's loop.
 */
$thumb = $job['thumbnail_path'] ?: $job['image_path'];
?>
<article class="job-card reveal <?= $job['is_featured'] ? 'is-featured' : '' ?>">
  <div class="job-card__top">
    <div class="job-card__logo" aria-hidden="true"><?php if (!empty($thumb)): ?><img src="<?= url($thumb) ?>" alt=""><?php else: ?><?= e($initial) ?><?php endif; ?></div>
    <div class="job-card__stamp"><?= e(time_ago($job['created_at'])) ?></div>
  </div>
  <?php if ($job['is_featured']): ?><span class="badge-featured"><?= e(t('featured')) ?></span><?php endif; ?>
  <h3 class="job-card__title"><?= e($title) ?></h3>
  <div class="job-card__company"><?= e($job['company_name']) ?></div>
  <div class="job-card__meta">
    <span class="tag">📍 <?= e($loc) ?></span>
    <span class="tag"><?= e(job_type_label($job['job_type'])) ?></span>
    <?php if ($salary && tenant_flag('show_salary')): ?><span class="tag tag--salary"><?= e($salary) ?></span><?php endif; ?>
  </div>
  <div class="job-card__foot">
    <span class="tag"><?= e(cat_name($job['category_name'] ?? null, $job['category_name_ar'] ?? null) ?: '—') ?></span>
    <a class="job-card__link" href="<?= url('job.php?id=' . $job['id']) ?>"><?= e(t('view_role')) ?> <span class="dir-arrow">→</span></a>
  </div>
  <?php if (tenant_flag('enable_saved')): ?>
  <form method="post" action="<?= url('save.php') ?>" class="save-form">
    <?= csrf_field() ?>
    <input type="hidden" name="job_id" value="<?= (int)$job['id'] ?>">
    <input type="hidden" name="save_action" value="<?= is_job_saved($job['id']) ? 'unsave' : 'save' ?>">
    <input type="hidden" name="return_url" value="<?= e($_SERVER['REQUEST_URI']) ?>">
    <button type="submit" class="save-btn <?= is_job_saved($job['id']) ? 'is-saved' : '' ?>" aria-pressed="<?= is_job_saved($job['id']) ? 'true' : 'false' ?>" aria-label="<?= is_job_saved($job['id']) ? e(t('unsave_job')) : e(t('save_job')) ?>">★</button>
  </form>
  <?php endif; ?>
</article>
