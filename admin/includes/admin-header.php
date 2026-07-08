<?php
require_once __DIR__ . '/../../config/config.php';
require_login(); // every admin page is guarded
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex, nofollow">
  <title><?= e(($admin_title ?? 'Dashboard') . ' · ' . brand_name() . ' Admin') ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400..700&family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
  <link rel="icon" type="image/svg+xml" href="<?= url('assets/img/favicon.svg') ?>">
  <link rel="stylesheet" href="<?= url('assets/css/admin.css') ?>">
  <?php if ($brandColor = brand_color()): ?>
  <style>:root{ --chestnut: <?= e($brandColor) ?>; }</style>
  <?php endif; ?>
</head>
<body>
<div class="admin-bar">
  <div class="wrap admin-bar__inner">
    <?php $isSuper = current_admin_role() === 'super_admin'; ?>
    <a href="<?= url($isSuper ? 'admin/tenants.php' : 'admin/dashboard.php') ?>" class="admin-brand"><?= e(brand_name()) ?><span class="dot">.</span>Admin</a>
    <div class="admin-bar__right">
      <span class="who"><?= e($_SESSION['admin_username'] ?? '') ?></span>
      <?php if ($isSuper): ?>
        <a href="<?= url('admin/tenants.php') ?>" class="linkout">Companies</a>
        <a href="<?= url('admin/categories.php') ?>" class="linkout">Categories</a>
      <?php else: ?>
        <a href="<?= url('admin/dashboard.php') ?>" class="linkout">Dashboard</a>
        <a href="<?= url('admin/branding.php') ?>" class="linkout">Customize</a>
        <a href="<?= url('admin/activity-log.php') ?>" class="linkout">Activity log</a>
      <?php endif; ?>
      <a href="<?= url('admin/account.php') ?>" class="linkout">Account</a>
      <a href="<?= url('index.php') ?>" target="_blank" class="linkout">View site ↗</a>
      <a href="<?= url('admin/logout.php') ?>" class="btn btn--ghost btn--sm" style="color:#fdf6ec;border-color:rgba(255,255,255,0.3)">Sign out</a>
    </div>
  </div>
</div>
<main class="admin-main wrap">
