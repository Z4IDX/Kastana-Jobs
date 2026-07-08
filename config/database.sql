-- ============================================================
--  Kastana Jobs — Database Schema
--  Import this file into MySQL/MariaDB (via Navicat or phpMyAdmin)
--  It creates the database, all tables, and a default admin.
-- ============================================================

CREATE DATABASE IF NOT EXISTS `kastana_jobs`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `kastana_jobs`;
SET NAMES utf8mb4;

-- ------------------------------------------------------------
--  Tenants  (one company = one tenant, addressed by subdomain)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tenants` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`          VARCHAR(150) NOT NULL,
  `brand_name`    VARCHAR(150) NULL DEFAULT NULL,   -- optional display name (else `name`)
  `logo_path`     VARCHAR(255) NULL DEFAULT NULL,   -- optional logo (else default logo)
  `primary_color` VARCHAR(7)   NULL DEFAULT NULL,   -- optional #rrggbb accent (else default)
  `subdomain`     VARCHAR(63)  NOT NULL,
  `status`        ENUM('pending','active','suspended') NOT NULL DEFAULT 'pending',
  `created_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `activated_at`  DATETIME     NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tenant_subdomain` (`subdomain`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
--  Admins  (super-admins run the platform; company admins run a tenant)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `admins` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tenant_id`     INT UNSIGNED NULL DEFAULT NULL,   -- NULL = platform super-admin
  `username`      VARCHAR(50)  NOT NULL,
  `email`         VARCHAR(150) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `role`          ENUM('super_admin','company_admin') NOT NULL DEFAULT 'company_admin',
  `last_login`    DATETIME     NULL DEFAULT NULL,
  `created_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_admin_tenant_username` (`tenant_id`, `username`),
  UNIQUE KEY `uq_admin_tenant_email` (`tenant_id`, `email`),
  CONSTRAINT `fk_admin_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
--  Categories
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `categories` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`       VARCHAR(80)  NOT NULL,
  `name_ar`    VARCHAR(80)  NULL DEFAULT NULL,
  `slug`       VARCHAR(100) NOT NULL,
  `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_category_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
--  Jobs
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `jobs` (
  `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tenant_id`       INT UNSIGNED NOT NULL DEFAULT 1,   -- DEFAULT transitional; every write should set it explicitly
  `title`           VARCHAR(150) NOT NULL,
  `title_ar`        VARCHAR(150) NULL DEFAULT NULL,
  `slug`            VARCHAR(200) NOT NULL,
  `company_name`    VARCHAR(150) NOT NULL,
  `company_email`   VARCHAR(150) NOT NULL,
  `company_website` VARCHAR(255) NULL DEFAULT NULL,
  `location`        VARCHAR(150) NOT NULL,
  `location_ar`     VARCHAR(150) NULL DEFAULT NULL,
  `job_type`        ENUM('Full-time','Part-time','Contract','Internship','Remote','Temporary') NOT NULL DEFAULT 'Full-time',
  `category_id`     INT UNSIGNED NULL DEFAULT NULL,
  `salary_min`      INT UNSIGNED NULL DEFAULT NULL,
  `salary_max`      INT UNSIGNED NULL DEFAULT NULL,
  `salary_currency` VARCHAR(8)   NOT NULL DEFAULT 'USD',
  `description`     TEXT         NOT NULL,
  `description_ar`  TEXT         NULL DEFAULT NULL,
  `requirements`    TEXT         NULL DEFAULT NULL,
  `requirements_ar` TEXT         NULL DEFAULT NULL,
  `how_to_apply`    TEXT         NOT NULL,
  `how_to_apply_ar` TEXT         NULL DEFAULT NULL,
  `apply_url`       VARCHAR(255) NULL DEFAULT NULL,
  `image_path`      VARCHAR(255) NULL DEFAULT NULL,
  `thumbnail_path`  VARCHAR(255) NULL DEFAULT NULL,
  `status`          ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `is_featured`     TINYINT(1)   NOT NULL DEFAULT 0,
  `expires_at`      DATE         NULL DEFAULT NULL,
  `created_at`      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `approved_at`     DATETIME     NULL DEFAULT NULL,
  `approved_by`     INT UNSIGNED NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_category` (`category_id`),
  KEY `idx_slug` (`slug`),
  KEY `idx_job_tenant` (`tenant_id`),
  CONSTRAINT `fk_job_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_job_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_job_admin` FOREIGN KEY (`approved_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
--  Login attempts  (brute-force protection)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `login_attempts` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ip_address`   VARCHAR(45)  NOT NULL,
  `username`     VARCHAR(50)  NULL DEFAULT NULL,
  `success`      TINYINT(1)   NOT NULL DEFAULT 0,
  `attempted_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ip_time` (`ip_address`, `attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
--  Applicants  (contact-form applications submitted via job.php)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `applicants` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tenant_id`  INT UNSIGNED NOT NULL DEFAULT 1,
  `job_id`     INT UNSIGNED NOT NULL,
  `name`       VARCHAR(150) NOT NULL,
  `email`      VARCHAR(150) NOT NULL,
  `phone`      VARCHAR(40)  NULL DEFAULT NULL,
  `cover_note` TEXT         NULL DEFAULT NULL,
  `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_applicant_job` (`job_id`),
  KEY `idx_applicant_tenant` (`tenant_id`),
  CONSTRAINT `fk_applicant_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_applicant_job` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
--  Activity log  (admin actions on postings)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `activity_log` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tenant_id`  INT UNSIGNED NOT NULL DEFAULT 1,
  `admin_id`   INT UNSIGNED NULL DEFAULT NULL,
  `job_id`     INT UNSIGNED NULL DEFAULT NULL,
  `action`     VARCHAR(30)  NOT NULL,
  `details`    VARCHAR(255) NULL DEFAULT NULL,
  `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_activity_created` (`created_at`),
  KEY `idx_activity_job` (`job_id`),
  KEY `idx_activity_tenant` (`tenant_id`),
  CONSTRAINT `fk_activity_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_activity_admin` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_activity_job` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
--  Seed data
-- ------------------------------------------------------------
-- A default, active tenant that owns the seed data below.
INSERT INTO `tenants` (`id`, `name`, `subdomain`, `status`, `activated_at`) VALUES
  (1, 'Default', 'app', 'active', NOW())
ON DUPLICATE KEY UPDATE `id` = `id`;

INSERT INTO `categories` (`name`, `name_ar`, `slug`) VALUES
  ('Engineering & Development', 'الهندسة والتطوير',   'engineering'),
  ('Design & Creative',         'التصميم والإبداع',   'design'),
  ('Marketing & Sales',         'التسويق والمبيعات',  'marketing'),
  ('Product & Management',      'المنتج والإدارة',     'product'),
  ('Finance & Legal',           'المالية والقانون',   'finance'),
  ('Operations & Support',      'العمليات والدعم',    'operations'),
  ('Data & Analytics',          'البيانات والتحليلات','data'),
  ('Human Resources',           'الموارد البشرية',    'hr')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `name_ar` = VALUES(`name_ar`);

-- Default admin login:
--   username: admin
--   password: ChangeMe!2025
-- The hash below is a bcrypt hash of that password.
-- IMPORTANT: log in and change it immediately (or run create_admin.php).
INSERT INTO `admins` (`tenant_id`, `username`, `email`, `password_hash`, `role`) VALUES
  (NULL, 'admin', 'admin@kastanajobs.local',
   '$2y$12$WwG9X326a6h/1dM7stoVzuclq3Br0NG08C5vzlT4mbmeXDrCjkQLi', 'super_admin')
ON DUPLICATE KEY UPDATE `username` = VALUES(`username`);

-- A couple of approved sample jobs so the homepage isn't empty on first run.
INSERT INTO `jobs`
  (`title`, `title_ar`, `slug`, `company_name`, `company_email`, `company_website`, `location`, `location_ar`,
   `job_type`, `category_id`, `salary_min`, `salary_max`, `salary_currency`,
   `description`, `description_ar`, `requirements`, `requirements_ar`, `how_to_apply`, `how_to_apply_ar`,
   `apply_url`, `status`, `is_featured`, `approved_at`)
VALUES
  ('Senior Frontend Engineer', 'مهندس واجهات أمامية أول', 'senior-frontend-engineer-sample', 'Northwind Studio',
   'careers@northwind.example', 'https://northwind.example', 'Remote (Europe)', 'عن بُعد (أوروبا)',
   'Full-time', 1, 65000, 90000, 'EUR',
   'We are looking for a senior frontend engineer to lead the build of our design system and customer-facing web app. You will work closely with design and product to ship polished, accessible interfaces.',
   'نبحث عن مهندس واجهات أمامية أول لقيادة بناء نظام التصميم وتطبيق الويب الموجّه للعملاء. ستعمل بشكل وثيق مع فريقي التصميم والمنتج لإطلاق واجهات أنيقة وسهلة الوصول.',
   'Five plus years with modern JavaScript, deep CSS knowledge, and a strong eye for detail. Experience mentoring other engineers is a plus.',
   'خبرة خمس سنوات فأكثر في جافاسكربت الحديثة، ومعرفة عميقة بـCSS، ودقة عالية في التفاصيل. تُعد خبرة إرشاد المهندسين الآخرين ميزة إضافية.',
   'Send a short note and a link to your portfolio or GitHub.', 'أرسل رسالة قصيرة مع رابط لأعمالك أو حساب GitHub.',
   'https://northwind.example/apply', 'approved', 1, NOW()),
  ('Brand & Product Designer', 'مصمّم علامة ومنتج', 'brand-product-designer-sample', 'Kastana Labs',
   'hello@kastana.example', 'https://kastana.example', 'Amman, Jordan', 'عمّان، الأردن',
   'Full-time', 2, 30000, 45000, 'USD',
   'Join a small, senior team shaping the visual identity and product experience of a growing marketplace. You will own everything from brand to interface.',
   'انضم إلى فريق صغير وذي خبرة يشكّل الهوية البصرية وتجربة المنتج لسوق متنامٍ. ستمتلك كل شيء من العلامة إلى الواجهة.',
   'A portfolio that shows range across brand and product. Comfort with Figma and a systematic approach to design.',
   'ملف أعمال يُظهر تنوّعًا بين العلامة والمنتج. إتقان Figma ومنهجية منظّمة في التصميم.',
   'Apply with your portfolio. Tell us about one project you are proud of.', 'قدّم مع ملف أعمالك. أخبرنا عن مشروع تفتخر به.',
   NULL, 'approved', 0, NOW());
