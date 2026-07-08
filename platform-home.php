<?php
/**
 * Platform landing page — shown on the root domain (APP_DOMAIN), where there is
 * no company yet. Required by require_active_tenant(); config.php is already loaded.
 */
$page_title = APP_NAME;
require __DIR__ . '/includes/header.php';
?>
<section class="hero">
  <div class="orbs orbs--hero" aria-hidden="true"><span></span><span></span><span></span></div>
  <div class="wrap hero__inner">
    <span class="eyebrow">For companies</span>
    <h1>Your own <em>job board,</em><br>live in minutes.</h1>
    <p class="hero__lede">Launch a curated, bilingual hiring page on your own subdomain. Post roles, review applicants, and manage everything from one dashboard — no code, no setup.</p>
    <div style="margin-top:2.2rem;display:flex;gap:0.8rem;flex-wrap:wrap">
      <a href="<?= url('signup.php') ?>" class="btn btn--honey">List your company <span class="dir-arrow">→</span></a>
      <a href="<?= url('admin/login.php') ?>" class="btn btn--ghost" style="color:#fdf6ec;border-color:rgba(255,255,255,0.3)">Admin sign in</a>
    </div>
  </div>
</section>

<section class="board wrap">
  <div class="board__head"><div><span class="eyebrow">How it works</span><h2>Three steps</h2></div></div>
  <div class="jobs-grid">
    <article class="job-card"><h3 class="job-card__title">1 · Sign up</h3><p style="color:var(--ink-soft)">Pick your company name and a subdomain like <code>you.<?= e(APP_DOMAIN) ?></code>.</p></article>
    <article class="job-card"><h3 class="job-card__title">2 · Get activated</h3><p style="color:var(--ink-soft)">We review and switch your board on. You'll get your own admin login.</p></article>
    <article class="job-card"><h3 class="job-card__title">3 · Start hiring</h3><p style="color:var(--ink-soft)">Post roles and collect applications on your branded, bilingual board.</p></article>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
