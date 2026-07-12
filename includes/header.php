<?php
require_once __DIR__ . '/../config/config.php';
// Theme is remembered in a cookie and rendered server-side to avoid a flash of the wrong theme.
$__theme = (($_COOKIE['theme'] ?? '') === 'dark') ? 'dark' : ((($_COOKIE['theme'] ?? '') === 'light') ? 'light' : '');
$__navCur = basename($_SERVER['SCRIPT_NAME'] ?? '');
$__savedCount = count(saved_job_ids());
?>
<!DOCTYPE html>
<html lang="<?= e(current_lang()) ?>" dir="<?= e(dir_attr()) ?>"<?= $__theme ? ' data-theme="' . $__theme . '"' : '' ?>>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="color-scheme" content="light dark">
  <meta name="description" content="<?= e($page_desc ?? APP_NAME . ' — ' . APP_TAGLINE) ?>">
  <title><?= e(($page_title ?? t('nav_browse')) . ' · ' . APP_NAME) ?></title>

  <link rel="icon" type="image/svg+xml" href="<?= url('assets/img/favicon.svg') ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,400..700;1,9..144,400..600&family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
  <?php if (is_rtl()): ?>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
  <?php endif; ?>
  <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>?v=<?= @filemtime(__DIR__ . '/../assets/css/style.css') ?>">
</head>
<body data-t-back="<?= e(t('back_to_top')) ?>" data-t-showpw="<?= e(t('show_password')) ?>" data-t-hidepw="<?= e(t('hide_password')) ?>" data-t-saved="<?= e(t('saved_toast')) ?>" data-t-removed="<?= e(t('removed_toast')) ?>">
<a class="skip-link" href="#main"><?= e(t('skip_content')) ?></a>
<header class="site-header">
  <div class="wrap site-header__inner">
    <a href="<?= url('index.php') ?>" class="brand" aria-label="<?= e(APP_NAME) ?>">
      <img src="<?= url('assets/img/logo.png') ?>" alt="<?= e(APP_NAME) ?>" class="brand-logo">
    </a>
    <nav class="nav">
      <a href="<?= url('index.php') ?>" class="<?= $__navCur === 'index.php' ? 'is-active' : '' ?>"<?= $__navCur === 'index.php' ? ' aria-current="page"' : '' ?>><?= e(t('nav_browse')) ?></a>
      <a href="<?= url('saved.php') ?>" class="<?= $__navCur === 'saved.php' ? 'is-active' : '' ?>"<?= $__navCur === 'saved.php' ? ' aria-current="page"' : '' ?>><?= e(t('nav_saved')) ?> <span class="nav-count" data-saved-count<?= $__savedCount ? '' : ' hidden' ?>>(<?= $__savedCount ?>)</span></a>
      <button type="button" class="theme-toggle" data-theme-toggle aria-label="<?= e(t('toggle_theme')) ?>">
        <svg class="icon-moon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/></svg>
        <svg class="icon-sun" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4 12H2M22 12h-2M5 5l1.4 1.4M17.6 17.6L19 19M19 5l-1.4 1.4M6.4 17.6L5 19"/></svg>
      </button>
      <a href="<?= e(lang_switch_url()) ?>" class="lang-toggle" aria-label="<?= e(t('switch_lang_aria')) ?>"><?= e(t('switch_to')) ?></a>
      <?php if (is_employer_logged_in()): ?>
        <a href="<?= url('employer/dashboard.php') ?>" class="btn btn--primary btn--sm"><?= e(t('nav_dashboard')) ?></a>
      <?php else: ?>
        <a href="<?= url('login.php') ?>" class="btn btn--primary btn--sm"><?= e(t('nav_post')) ?></a>
      <?php endif; ?>
    </nav>
  </div>
</header>
<main id="main" tabindex="-1">
