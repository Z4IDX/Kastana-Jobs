</main>
<footer class="site-footer">
  <div class="wrap site-footer__inner">
    <a href="<?= e(BRAND_URL !== '' ? BRAND_URL : url('index.php')) ?>" class="brand"><img src="<?= url('assets/img/logo.png') ?>" alt="<?= e(APP_NAME) ?>" class="brand-logo brand-logo--sm"></a>
    <nav class="footer-links">
      <a href="<?= url('index.php') ?>"><?= e(t('footer_browse')) ?></a>
      <a href="<?= url('admin/login.php') ?>"><?= e(t('footer_admin')) ?></a>
    </nav>
    <p>&copy; <?= date('Y') ?> <?= e(APP_NAME) ?>. <?= e(t('footer_copy')) ?></p>
  </div>
</footer>
<script src="<?= url('assets/js/main.js') ?>" defer></script>
</body>
</html>
