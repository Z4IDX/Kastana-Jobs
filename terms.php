<?php
require_once __DIR__ . '/config/config.php';
$ar = is_rtl();
$page_title = $ar ? 'شروط الاستخدام' : 'Terms of use';
$page_desc  = $ar ? 'شروط استخدام موقع كستانة.' : 'Terms for using the Kastana job board.';
require __DIR__ . '/includes/header.php';
?>
<section class="wrap content-page">
  <span class="eyebrow"><?= e(APP_NAME) ?></span>
  <h1><?= $ar ? 'شروط الاستخدام' : 'Terms of use' ?></h1>
  <div class="prose">
    <?php if ($ar): ?>
      <p>باستخدامك <?= e(APP_NAME) ?> فإنك توافق على هذه الشروط. يُتاح الموقع كما هو لعرض فرص عمل مُنتقاة والتقديم عليها مباشرةً لدى أصحاب العمل.</p>
      <h2>استخدام الموقع</h2>
      <p>يُمنع نشر محتوى مضلّل أو احتيالي أو مخالف للقانون. نحتفظ بحق مراجعة أي وظيفة أو رفضها أو إزالتها أو تعليق أي حساب يخالف هذه الشروط.</p>
      <h2>الوظائف والتقديم</h2>
      <p>أصحاب العمل مسؤولون عن دقّة إعلاناتهم. لسنا طرفًا في أي تواصل أو تعاقد بينك وبين صاحب العمل، ولا نضمن توفّر أي وظيفة أو صحّتها.</p>
      <h2>حدود المسؤولية</h2>
      <p>لا نتحمّل مسؤولية أي خسارة ناتجة عن استخدام الموقع أو الاعتماد على محتواه. قد نُحدّث هذه الشروط من وقت لآخر.</p>
    <?php else: ?>
      <p>By using <?= e(APP_NAME) ?> you agree to these terms. The site is provided to list curated roles and to let you apply directly with employers.</p>
      <h2>Using the site</h2>
      <p>Do not post misleading, fraudulent, or unlawful content. We reserve the right to review, reject, or remove any posting, and to suspend any account that breaches these terms.</p>
      <h2>Jobs and applications</h2>
      <p>Employers are responsible for the accuracy of their listings. We are not a party to any communication or contract between you and an employer, and we do not guarantee the availability or accuracy of any role.</p>
      <h2>Limitation of liability</h2>
      <p>We are not liable for any loss arising from use of the site or reliance on its content. We may update these terms from time to time.</p>
    <?php endif; ?>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
