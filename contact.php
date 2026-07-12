<?php
require_once __DIR__ . '/config/config.php';
$ar = is_rtl();
$page_title = $ar ? 'اتصل بنا' : 'Contact';
$page_desc  = $ar ? 'كيفية التواصل مع فريق كستانة وأسئلة شائعة.' : 'How to reach the Kastana team, plus common questions.';
$contactEmail = defined('CONTACT_EMAIL') ? CONTACT_EMAIL : 'hello@kastana.example';
require __DIR__ . '/includes/header.php';
?>
<section class="wrap content-page">
  <span class="eyebrow"><?= e(APP_NAME) ?></span>
  <h1><?= $ar ? 'تواصل معنا' : 'Get in touch' ?></h1>
  <div class="prose">
    <?php if ($ar): ?>
      <p>لأي سؤال عن الوظائف أو حسابات أصحاب العمل أو الشراكات، راسلنا على <a href="mailto:<?= e($contactEmail) ?>"><?= e($contactEmail) ?></a> وسنعود إليك في أقرب وقت.</p>
      <h2>أسئلة شائعة</h2>
      <p><strong>كيف أتقدّم لوظيفة؟</strong><br>افتح صفحة الوظيفة وتقدّم مباشرةً عبر زر الاتصال أو البريد الإلكتروني. لا يوجد نموذج تقديم على الموقع.</p>
      <p><strong>هل التصفّح مجاني؟</strong><br>نعم، تصفّح الوظائف وحفظها مجاني تمامًا ولا يتطلّب حسابًا.</p>
      <p><strong>أنا صاحب عمل، كيف أنشر وظيفة؟</strong><br>أنشئ حسابًا مجانيًا ثم انشر من لوحة التحكّم. تُراجَع كل وظيفة قبل نشرها.</p>
    <?php else: ?>
      <p>For anything about roles, employer accounts, or partnerships, email us at <a href="mailto:<?= e($contactEmail) ?>"><?= e($contactEmail) ?></a> and we'll get back to you soon.</p>
      <h2>Common questions</h2>
      <p><strong>How do I apply for a job?</strong><br>Open the job page and apply directly via the Call or Email button. There's no on-site application form.</p>
      <p><strong>Is browsing free?</strong><br>Yes — browsing and saving jobs is completely free and needs no account.</p>
      <p><strong>I'm an employer, how do I post?</strong><br>Create a free account, then post from your dashboard. Every role is reviewed before it goes live.</p>
    <?php endif; ?>

    <p style="margin-top:1.75rem"><a class="btn btn--primary" href="mailto:<?= e($contactEmail) ?>"><?= $ar ? 'راسلنا' : 'Email us' ?></a></p>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
