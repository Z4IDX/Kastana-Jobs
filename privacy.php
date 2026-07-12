<?php
require_once __DIR__ . '/config/config.php';
$ar = is_rtl();
$page_title = $ar ? 'سياسة الخصوصية' : 'Privacy policy';
$page_desc  = $ar ? 'كيف نتعامل مع بياناتك في كستانة.' : 'How Kastana handles your data.';
require __DIR__ . '/includes/header.php';
?>
<section class="wrap content-page">
  <span class="eyebrow"><?= e(APP_NAME) ?></span>
  <h1><?= $ar ? 'سياسة الخصوصية' : 'Privacy policy' ?></h1>
  <div class="prose">
    <?php if ($ar): ?>
      <p>نحرص على جمع أقل قدر ممكن من البيانات. لا يحتاج الباحثون عن عمل إلى حساب لتصفّح الوظائف.</p>
      <h2>ما الذي نجمعه</h2>
      <p>يُنشئ أصحاب العمل حسابًا يتضمّن اسم الشركة والبريد الإلكتروني ورقم الهاتف اختياريًا. تُخزَّن كلمات المرور مُشفّرة. تُحفظ الوظائف المفضّلة في ملف تعريف ارتباط (كوكي) على جهازك فقط.</p>
      <h2>ملفات تعريف الارتباط</h2>
      <p>نستخدم كوكيز أساسية للجلسة وحفظ تفضيلاتك (مثل اللغة والوظائف المحفوظة). لا نستخدم كوكيز تتبّع إعلانية.</p>
      <h2>مشاركة البيانات</h2>
      <p>لا نبيع بياناتك. عند التقديم على وظيفة تتواصل مباشرةً مع صاحب العمل عبر الهاتف أو البريد، خارج الموقع.</p>
    <?php else: ?>
      <p>We aim to collect as little data as possible. Job seekers don't need an account to browse.</p>
      <h2>What we collect</h2>
      <p>Employers create an account with a company name, email, and optional phone. Passwords are stored hashed. Saved jobs are kept in a cookie on your own device only.</p>
      <h2>Cookies</h2>
      <p>We use essential cookies for your session and preferences (such as language and saved jobs). We do not use advertising or tracking cookies.</p>
      <h2>Sharing</h2>
      <p>We don't sell your data. When you apply to a role you contact the employer directly by phone or email, off-site.</p>
    <?php endif; ?>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
