<?php
require_once __DIR__ . '/../../config/config.php';
require_login(); // every admin page is guarded
?>
<!DOCTYPE html>
<html lang="<?= e(current_lang()) ?>" dir="<?= e(dir_attr()) ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex, nofollow">
  <title><?= e(($admin_title ?? t('a_dashboard')) . ' · ' . APP_NAME . ' Admin') ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400..700&family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
  <?php if (is_rtl()): ?>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
  <?php endif; ?>
  <link rel="icon" type="image/svg+xml" href="<?= url('assets/img/favicon.svg') ?>">
  <link rel="stylesheet" href="<?= url('assets/css/admin.css') ?>">
</head>
<body>
<div class="admin-bar">
  <div class="wrap admin-bar__inner">
    <a href="<?= url('admin/dashboard.php') ?>" class="admin-brand"><?= e(APP_NAME) ?><span class="dot">.</span>Admin</a>
    <div class="admin-bar__right">
      <span class="who"><?= e($_SESSION['admin_username'] ?? '') ?></span>
      <a href="<?= url('admin/dashboard.php') ?>" class="linkout"><?= e(t('a_dashboard')) ?></a>
      <a href="<?= url('admin/categories.php') ?>" class="linkout"><?= e(t('a_categories')) ?></a>
      <a href="<?= url('admin/employers.php') ?>" class="linkout"><?= e(t('a_accounts')) ?></a>
      <a href="<?= url('admin/activity-log.php') ?>" class="linkout"><?= e(t('a_activity')) ?></a>
      <a href="<?= url('admin/account.php') ?>" class="linkout"><?= e(t('a_account')) ?></a>
      <a href="<?= url('index.php') ?>" target="_blank" class="linkout"><?= e(t('a_viewsite')) ?> ↗</a>
      <a href="<?= e(lang_switch_url()) ?>" class="linkout"><?= e(t('switch_to')) ?></a>
      <a href="<?= url('admin/logout.php') ?>" class="btn btn--ghost btn--sm" style="color:#fdf6ec;border-color:rgba(255,255,255,0.3)"><?= e(t('a_signout')) ?></a>
    </div>
  </div>
</div>
<main class="admin-main wrap">
