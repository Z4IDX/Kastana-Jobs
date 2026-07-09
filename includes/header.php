<?php require_once __DIR__ . '/../config/config.php'; ?>
<!DOCTYPE html>
<html lang="<?= e(current_lang()) ?>" dir="<?= e(dir_attr()) ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="<?= e($page_desc ?? APP_NAME . ' — ' . APP_TAGLINE) ?>">
  <title><?= e(($page_title ?? t('nav_browse')) . ' · ' . APP_NAME) ?></title>

  <link rel="icon" type="image/svg+xml" href="<?= url('assets/img/favicon.svg') ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,400..700;1,9..144,400..600&family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
  <?php if (is_rtl()): ?>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
  <?php endif; ?>
  <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
</head>
<body>
<header class="site-header">
  <div class="wrap site-header__inner">
    <a href="<?= url('index.php') ?>" class="brand" aria-label="<?= e(APP_NAME) ?>">
      <img src="<?= url('assets/img/logo.png') ?>" alt="<?= e(APP_NAME) ?>" class="brand-logo">
    </a>
    <nav class="nav">
      <a href="<?= url('index.php') ?>"><?= e(t('nav_browse')) ?></a>
      <a href="<?= url('saved.php') ?>"><?= e(t('nav_saved')) ?><?php $savedCount = count(saved_job_ids()); if ($savedCount): ?> (<?= $savedCount ?>)<?php endif; ?></a>
      <a href="<?= e(lang_switch_url()) ?>" class="lang-toggle" aria-label="<?= e(t('switch_lang_aria')) ?>"><?= e(t('switch_to')) ?></a>
      <?php if (is_employer_logged_in()): ?>
        <a href="<?= url('employer/dashboard.php') ?>" class="btn btn--primary btn--sm"><?= e(t('nav_dashboard')) ?></a>
      <?php else: ?>
        <a href="<?= url('login.php') ?>" class="btn btn--primary btn--sm"><?= e(t('nav_post')) ?></a>
      <?php endif; ?>
    </nav>
  </div>
</header>
<main>
