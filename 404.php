<?php
require_once __DIR__ . '/config/config.php';
http_response_code(404);
$ar = is_rtl();
$page_title = $ar ? 'الصفحة غير موجودة' : 'Page not found';
require __DIR__ . '/includes/header.php';
?>
<section class="wrap content-page" style="text-align:center">
  <h1 style="font-size:clamp(3rem,10vw,5rem);margin-bottom:0.5rem">404</h1>
  <p class="prose" style="margin:0 auto 1.75rem;max-width:44ch"><?= $ar ? 'عذرًا، لم نتمكّن من العثور على هذه الصفحة. جرّب البحث أو العودة إلى اللوحة.' : "Sorry, we couldn't find that page. Try a search, or head back to the board." ?></p>
  <form class="searchbar" method="get" action="<?= url('index.php') ?>#roles" role="search" style="margin-inline:auto;margin-top:0">
    <input type="search" name="q" placeholder="<?= e(t('search_ph')) ?>" aria-label="<?= e(t('search_btn')) ?>">
    <button type="submit" class="btn btn--primary"><?= e(t('search_btn')) ?></button>
  </form>
  <p style="margin-top:1.5rem"><a class="btn btn--ghost" href="<?= url('index.php') ?>"><?= e(t('nav_browse')) ?></a></p>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
