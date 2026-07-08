<?php
/**
 * Shown for a subdomain that doesn't match a company ($notFound = true) or one
 * that isn't active yet. Required by require_active_tenant(); config is loaded.
 */
$notFound = $notFound ?? true;
$page_title = $notFound ? 'Board not found' : 'Board not live yet';
require __DIR__ . '/includes/header.php';
?>
<section class="wrap" style="padding:5rem 0">
  <div class="empty">
    <h3><?= $notFound ? 'No board at this address' : 'This board isn\'t live yet' ?></h3>
    <p>
      <?= $notFound
        ? 'There\'s no company board here. Check the address, or list your company.'
        : 'This company\'s board is awaiting activation. Please check back soon.' ?>
    </p>
    <p style="margin-top:1rem"><a class="btn btn--ghost btn--sm" href="//<?= e(APP_DOMAIN) ?>/">Go to <?= e(APP_NAME) ?></a></p>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
