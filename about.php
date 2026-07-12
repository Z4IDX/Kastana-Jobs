<?php
require_once __DIR__ . '/config/config.php';
$ar = is_rtl();
$page_title = $ar ? 'من نحن وكيف نعمل' : 'About & how it works';
$page_desc  = $ar ? 'تعرّف على كستانة: لوحة وظائف مُنتقاة ومُراجَعة يدويًا.' : 'Learn about Kastana — a curated, human-reviewed job board.';
require __DIR__ . '/includes/header.php';
?>
<section class="wrap content-page">
  <span class="eyebrow"><?= e(APP_NAME) ?></span>
  <h1><?= $ar ? 'من نحن' : 'About Kastana' ?></h1>
  <div class="prose">
    <?php if ($ar): ?>
      <p><?= e(APP_NAME) ?> لوحة وظائف مُنتقاة في منطقة الشرق الأوسط وشمال أفريقيا. تُراجَع كل وظيفة يدويًا قبل نشرها — بلا رسائل مزعجة ولا إعلانات وهمية، فقط فرص حقيقية من شركات تستحق وقتك.</p>
      <p>نحن لسنا وسيطًا: تتقدّم مباشرةً إلى صاحب العمل عبر الهاتف أو البريد الإلكتروني، دون نموذج تقديم على الموقع.</p>

      <h2>كيف نعمل — للباحثين عن عمل</h2>
      <p>تصفّح الوظائف المعتمدة بحرية دون إنشاء حساب. استخدم البحث والتصفية حسب الفئة والموقع ونوع العمل وتاريخ النشر، واحفظ الوظائف التي تهمّك، ثم تقدّم مباشرةً عبر الاتصال أو البريد الإلكتروني.</p>

      <h2>كيف نعمل — لأصحاب العمل</h2>
      <p>أنشئ حسابًا مجانيًا وانشر وظائفك من لوحة التحكّم الخاصة بك. تدخل كل وظيفة (وكل تعديل) قائمة المراجعة، ويعتمدها فريقنا قبل ظهورها للعامة، فتصلك إشعارات الاعتماد أو الرفض داخل الموقع.</p>
    <?php else: ?>
      <p><?= e(APP_NAME) ?> is a curated job board for the MENA region. Every role is reviewed by a human before it goes live — no spam, no ghost listings, just real opportunities from companies worth your time.</p>
      <p>We're not a middleman: you apply directly to the employer by phone or email, with no on-site application form.</p>

      <h2>How it works — for job seekers</h2>
      <p>Browse approved roles freely, no account needed. Search and filter by category, location, job type, and date posted, save the roles you like, then apply directly by call or email.</p>

      <h2>How it works — for employers</h2>
      <p>Create a free account and post roles from your dashboard. Every posting (and every edit) enters our review queue and is approved by our team before it's public — you'll get on-site notifications when a role is approved or rejected.</p>
    <?php endif; ?>

    <p style="margin-top:1.75rem">
      <a class="btn btn--primary" href="<?= url('index.php') ?>"><?= e(t('nav_browse')) ?></a>
      <a class="btn btn--ghost" href="<?= url('register.php') ?>" style="margin-inline-start:0.5rem"><?= e(t('nav_post')) ?></a>
    </p>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
