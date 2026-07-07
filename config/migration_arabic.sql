-- ============================================================
--  Kastana Jobs — Arabic (bilingual) migration
--  Run this ONCE on an existing install (Navicat / phpMyAdmin).
--  Fresh installs already include these columns via database.sql.
-- ============================================================
USE `kastana_jobs`;
SET NAMES utf8mb4;

ALTER TABLE `categories`
  ADD COLUMN IF NOT EXISTS `name_ar` VARCHAR(80) NULL AFTER `name`;

ALTER TABLE `jobs`
  ADD COLUMN IF NOT EXISTS `title_ar`        VARCHAR(150) NULL AFTER `title`,
  ADD COLUMN IF NOT EXISTS `location_ar`     VARCHAR(150) NULL AFTER `location`,
  ADD COLUMN IF NOT EXISTS `description_ar`  TEXT         NULL AFTER `description`,
  ADD COLUMN IF NOT EXISTS `requirements_ar` TEXT         NULL AFTER `requirements`,
  ADD COLUMN IF NOT EXISTS `how_to_apply_ar` TEXT         NULL AFTER `how_to_apply`;

-- Arabic category names
UPDATE `categories` SET `name_ar` = CASE `slug`
  WHEN 'engineering' THEN 'الهندسة والتطوير'
  WHEN 'design'      THEN 'التصميم والإبداع'
  WHEN 'marketing'   THEN 'التسويق والمبيعات'
  WHEN 'product'     THEN 'المنتج والإدارة'
  WHEN 'finance'     THEN 'المالية والقانون'
  WHEN 'operations'  THEN 'العمليات والدعم'
  WHEN 'data'        THEN 'البيانات والتحليلات'
  WHEN 'hr'          THEN 'الموارد البشرية'
  ELSE `name_ar` END;

-- Arabic text for the two sample jobs
UPDATE `jobs` SET
  `title_ar`        = 'مهندس واجهات أمامية أول',
  `location_ar`     = 'عن بُعد (أوروبا)',
  `description_ar`  = 'نبحث عن مهندس واجهات أمامية أول لقيادة بناء نظام التصميم وتطبيق الويب الموجّه للعملاء. ستعمل بشكل وثيق مع فريقي التصميم والمنتج لإطلاق واجهات أنيقة وسهلة الوصول.',
  `requirements_ar` = 'خبرة خمس سنوات فأكثر في جافاسكربت الحديثة، ومعرفة عميقة بـCSS، ودقة عالية في التفاصيل. تُعد خبرة إرشاد المهندسين الآخرين ميزة إضافية.',
  `how_to_apply_ar` = 'أرسل رسالة قصيرة مع رابط لأعمالك أو حساب GitHub.'
  WHERE `slug` = 'senior-frontend-engineer-sample';

UPDATE `jobs` SET
  `title_ar`        = 'مصمّم علامة ومنتج',
  `location_ar`     = 'عمّان، الأردن',
  `description_ar`  = 'انضم إلى فريق صغير وذي خبرة يشكّل الهوية البصرية وتجربة المنتج لسوق متنامٍ. ستمتلك كل شيء من العلامة إلى الواجهة.',
  `requirements_ar` = 'ملف أعمال يُظهر تنوّعًا بين العلامة والمنتج. إتقان Figma ومنهجية منظّمة في التصميم.',
  `how_to_apply_ar` = 'قدّم مع ملف أعمالك. أخبرنا عن مشروع تفتخر به.'
  WHERE `slug` = 'brand-product-designer-sample';
