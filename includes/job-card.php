<?php
/**
 * Job card partial. Expects $job plus the precomputed $salary/$initial/$title/$loc
 * variables from the including page's loop.
 */
$thumb = $job['thumbnail_path'] ?: $job['image_path'];
?>
<article class="job-card reveal <?= $job['is_featured'] ? 'is-featured' : '' ?>">
  <div class="job-card__top">
    <div class="job-card__logo" aria-hidden="true"><?php if (!empty($thumb)): ?><img src="<?= url($thumb) ?>" alt="" loading="lazy" decoding="async" width="46" height="46"><?php else: ?><?= e($initial) ?><?php endif; ?></div>
    <div class="job-card__stamp"><?= e(time_ago($job['created_at'])) ?></div>
  </div>
  <?php if ($job['is_featured']): ?><span class="badge-featured"><?= e(t('featured')) ?></span><?php endif; ?>
  <?php if (strtotime($job['created_at']) > time() - 3 * 86400): ?><span class="badge-new"><?= e(t('badge_new')) ?></span><?php endif; ?>
  <h3 class="job-card__title"><?= e($title) ?></h3>
  <div class="job-card__company"><?= e($job['company_name']) ?></div>
  <div class="job-card__meta">
    <span class="tag"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 21s-6-5.3-6-10a6 6 0 0 1 12 0c0 4.7-6 10-6 10z"/><circle cx="12" cy="11" r="2.2"/></svg><?= e($loc) ?></span>
    <span class="tag"><?= e(job_type_label($job['job_type'])) ?></span>
    <?php if ($salary): ?><span class="tag tag--salary"><?= e($salary) ?></span><?php endif; ?>
  </div>
  <div class="job-card__foot">
    <span class="tag"><?= e(cat_name($job['category_name'] ?? null, $job['category_name_ar'] ?? null) ?: '—') ?></span>
    <a class="job-card__link" href="<?= url('job.php?id=' . $job['id']) ?>"><?= e(t('view_role')) ?> <span class="dir-arrow">→</span></a>
  </div>
  <form method="post" action="<?= url('save.php') ?>" class="save-form">
    <?= csrf_field() ?>
    <input type="hidden" name="job_id" value="<?= (int)$job['id'] ?>">
    <input type="hidden" name="save_action" value="<?= is_job_saved($job['id']) ? 'unsave' : 'save' ?>">
    <input type="hidden" name="return_url" value="<?= e($_SERVER['REQUEST_URI']) ?>">
    <button type="submit" class="save-btn <?= is_job_saved($job['id']) ? 'is-saved' : '' ?>" aria-pressed="<?= is_job_saved($job['id']) ? 'true' : 'false' ?>" aria-label="<?= is_job_saved($job['id']) ? e(t('unsave_job')) : e(t('save_job')) ?>" data-label-save="<?= e(t('save_job')) ?>" data-label-unsave="<?= e(t('unsave_job')) ?>"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 4h12a1 1 0 0 1 1 1v15l-7-4-7 4V5a1 1 0 0 1 1-1z"/></svg></button>
  </form>
</article>
