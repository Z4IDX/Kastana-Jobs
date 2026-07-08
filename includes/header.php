<?php require_once __DIR__ . '/../config/config.php'; ?>
<!DOCTYPE html>
<html lang="<?= e(current_lang()) ?>" dir="<?= e(dir_attr()) ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="<?= e($page_desc ?? brand_name() . ' — ' . tenant_setting('tagline', APP_TAGLINE)) ?>">
  <title><?= e(($page_title ?? t('nav_browse')) . ' · ' . brand_name()) ?></title>

  <link rel="icon" type="image/svg+xml" href="<?= url('assets/img/favicon.svg') ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <?php $fontTheme = current_font_theme(); ?>
  <link href="https://fonts.googleapis.com/css2?family=<?= $fontTheme['families'] ?>&display=swap" rel="stylesheet">
  <?php if (is_rtl()): ?>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
  <?php endif; ?>
  <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
  <?php
    $bc = brand_color();
    $hc = tenant_setting('highlight_color');
    $hcOk = is_string($hc) && preg_match('/^#[0-9a-fA-F]{6}$/', $hc);
    $customFont = tenant_setting('font_theme', 'default') !== 'default';
  ?>
  <?php if ($bc || $hcOk || $customFont): ?>
  <style>:root{<?php if ($bc): ?> --chestnut: <?= e($bc) ?>;<?php endif; ?><?php if ($hcOk): ?> --honey: <?= e($hc) ?>;<?php endif; ?><?php if ($customFont): ?> --font-display: <?= $fontTheme['display'] ?>; --font-body: <?= $fontTheme['body'] ?>;<?php endif; ?> }</style>
  <?php endif; ?>
</head>
<body>
<header class="site-header">
  <div class="wrap site-header__inner">
    <a href="<?= url('index.php') ?>" class="brand" aria-label="<?= e(brand_name()) ?>">
      <img src="<?= e(brand_logo_url()) ?>" alt="<?= e(brand_name()) ?>" class="brand-logo">
    </a>
    <nav class="nav">
      <a href="<?= url('index.php') ?>"><?= e(t('nav_browse')) ?></a>
      <?php if (tenant_flag('enable_saved')): ?>
      <a href="<?= url('saved.php') ?>"><?= e(t('nav_saved')) ?><?php $savedCount = count(saved_job_ids()); if ($savedCount): ?> (<?= $savedCount ?>)<?php endif; ?></a>
      <?php endif; ?>
      <a href="<?= e(lang_switch_url()) ?>" class="lang-toggle" aria-label="Switch language"><?= e(t('switch_to')) ?></a>
    </nav>
  </div>
</header>
<main>
