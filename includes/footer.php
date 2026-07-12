</main>
<footer class="site-footer">
  <div class="wrap site-footer__inner">
    <a href="<?= e(BRAND_URL !== '' ? BRAND_URL : url('index.php')) ?>" class="brand"><img src="<?= url('assets/img/logo.png') ?>" alt="<?= e(APP_NAME) ?>" class="brand-logo brand-logo--sm"></a>
    <nav class="footer-links">
      <a href="<?= url('index.php') ?>"><?= e(t('footer_browse')) ?></a>
      <a href="<?= url('about.php') ?>"><?= e(t('nav_about')) ?></a>
      <a href="<?= url('contact.php') ?>"><?= e(t('nav_contact')) ?></a>
      <a href="<?= url('terms.php') ?>"><?= e(t('nav_terms')) ?></a>
      <a href="<?= url('privacy.php') ?>"><?= e(t('nav_privacy')) ?></a>
      <a href="<?= url('login.php') ?>"><?= e(t('nav_post')) ?></a>
      <a href="<?= url('admin/login.php') ?>"><?= e(t('footer_admin')) ?></a>
    </nav>
    <p>&copy; <?= date('Y') ?> <?= e(APP_NAME) ?>. <?= e(t('footer_copy')) ?></p>
  </div>
</footer>

<?php
  // Mobile bottom tab bar (shown ≤620px via CSS). Primary navigation for phones.
  $__cur = basename($_SERVER['SCRIPT_NAME'] ?? '');
  $__savedCount = count(saved_job_ids());
  $__isEmp = is_employer_logged_in();
?>
<nav class="bottom-nav" aria-label="<?= e(t('nav_browse')) ?>">
  <a href="<?= url('index.php') ?>" class="bottom-nav__item <?= $__cur === 'index.php' ? 'is-active' : '' ?>"<?= $__cur === 'index.php' ? ' aria-current="page"' : '' ?>>
    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 11l8-6 8 6v8a1 1 0 0 1-1 1h-4v-6H9v6H5a1 1 0 0 1-1-1v-8z"/></svg>
    <span><?= e(t('nav_browse')) ?></span>
  </a>
  <a href="<?= url('saved.php') ?>" class="bottom-nav__item <?= $__cur === 'saved.php' ? 'is-active' : '' ?>"<?= $__cur === 'saved.php' ? ' aria-current="page"' : '' ?>>
    <span class="bottom-nav__icon">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 4h12a1 1 0 0 1 1 1v15l-7-4-7 4V5a1 1 0 0 1 1-1z"/></svg>
      <span class="bottom-nav__badge" data-saved-count<?= $__savedCount ? '' : ' hidden' ?>><?= (int)$__savedCount ?></span>
    </span>
    <span><?= e(t('nav_saved')) ?></span>
  </a>
  <?php if ($__isEmp): ?>
  <a href="<?= url('employer/dashboard.php') ?>" class="bottom-nav__item">
    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 5h6v6H4zM14 5h6v6h-6zM4 15h6v6H4zM14 15h6v6h-6z"/></svg>
    <span><?= e(t('nav_dashboard')) ?></span>
  </a>
  <?php else: ?>
  <a href="<?= url('login.php') ?>" class="bottom-nav__item <?= in_array($__cur, ['login.php','register.php'], true) ? 'is-active' : '' ?>">
    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 8v8M8 12h8"/></svg>
    <span><?= e(t('nav_post')) ?></span>
  </a>
  <?php endif; ?>
</nav>

<script src="<?= url('assets/js/main.js') ?>?v=<?= @filemtime(__DIR__ . '/../assets/js/main.js') ?>" defer></script>
</body>
</html>
