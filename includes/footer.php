</main>
<footer class="site-footer">
  <div class="wrap site-footer__inner">
    <a href="<?= e(BRAND_URL !== '' ? BRAND_URL : url('index.php')) ?>" class="brand"><img src="<?= e(brand_logo_url()) ?>" alt="<?= e(brand_name()) ?>" class="brand-logo brand-logo--sm"></a>
    <nav class="footer-links">
      <a href="<?= url('index.php') ?>"><?= e(t('footer_browse')) ?></a>
      <a href="<?= url('admin/login.php') ?>"><?= e(t('footer_admin')) ?></a>
    </nav>
    <?php
      $socials = array_filter([
        'Website'   => tenant_setting('social_website'),
        'LinkedIn'  => tenant_setting('social_linkedin'),
        'X'         => tenant_setting('social_x'),
        'Instagram' => tenant_setting('social_instagram'),
      ]);
    ?>
    <?php if ($socials): ?>
    <nav class="footer-links">
      <?php foreach ($socials as $label => $u): ?>
        <a href="<?= e($u) ?>" target="_blank" rel="noopener noreferrer nofollow"><?= e($label) ?> ↗</a>
      <?php endforeach; ?>
    </nav>
    <?php endif; ?>
    <p>&copy; <?= date('Y') ?> <?= e(brand_name()) ?>. <?= e(tenant_setting('footer_note', t('footer_copy'))) ?></p>
  </div>
</footer>
<script src="<?= url('assets/js/main.js') ?>" defer></script>
</body>
</html>
