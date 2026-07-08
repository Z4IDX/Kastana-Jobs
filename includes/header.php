<?php require_once __DIR__ . '/../config/config.php'; ?>
<!DOCTYPE html>
<html lang="<?= e(current_lang()) ?>" dir="<?= e(dir_attr()) ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="<?= e($page_desc ?? brand_name() . ' — ' . APP_TAGLINE) ?>">
  <title><?= e(($page_title ?? t('nav_browse')) . ' · ' . brand_name()) ?></title>

  <link rel="icon" type="image/svg+xml" href="<?= url('assets/img/favicon.svg') ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,400..700;1,9..144,400..600&family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
  <?php if (is_rtl()): ?>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
  <?php endif; ?>
  <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
  <?php if ($brandColor = brand_color()): ?>
  <style>:root{ --chestnut: <?= e($brandColor) ?>; }</style>
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
      <a href="<?= url('saved.php') ?>"><?= e(t('nav_saved')) ?><?php $savedCount = count(saved_job_ids()); if ($savedCount): ?> (<?= $savedCount ?>)<?php endif; ?></a>
      <a href="<?= e(lang_switch_url()) ?>" class="lang-toggle" aria-label="Switch language"><?= e(t('switch_to')) ?></a>
    </nav>
  </div>
</header>
<main>
