</main>
<footer class="site-footer">
  <div class="wrap site-footer__inner">
    <a href="<?= e(BRAND_URL !== '' ? BRAND_URL : url('index.php')) ?>" class="brand"><img src="<?= e(brand_logo_url()) ?>" alt="<?= e(brand_name()) ?>" class="brand-logo brand-logo--sm"></a>
    <nav class="footer-links">
      <a href="<?= url('index.php') ?>"><?= e(t('footer_browse')) ?></a>
      <a href="<?= url('admin/login.php') ?>"><?= e(t('footer_admin')) ?></a>
    </nav>
    <p>&copy; <?= date('Y') ?> <?= e(brand_name()) ?>. <?= e(tenant_setting('footer_note', t('footer_copy'))) ?></p>
  </div>
</footer>
<script src="<?= url('assets/js/main.js') ?>" defer></script>
</body>
</html>
