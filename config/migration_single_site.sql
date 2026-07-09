-- ============================================================
--  Kastana Jobs — Migration: multi-tenant  ->  single-site + employer accounts
--  Run this ONCE on a database that still has the tenant schema.
--  Fresh installs from database.sql already have the new shape.
--
--  What it does:
--    * adds the `employers` table (self-service company accounts)
--    * jobs: drops tenant_id, adds employer_id + company_phone
--    * removes the on-site applications table
--    * drops the tenants table and all tenant scoping
--    * simplifies admins (no tenant_id / role)
--
--  NOTE: existing company-admin accounts have no place in the new model and
--  are removed; recreate posters as employer accounts via register.php.
--  Back up first if the data matters.
-- ============================================================
USE `kastana_jobs`;
SET NAMES utf8mb4;

-- 1. Self-service employer accounts -------------------------------------------
CREATE TABLE IF NOT EXISTS `employers` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_name`  VARCHAR(150) NOT NULL,
  `email`         VARCHAR(150) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `phone`         VARCHAR(40)  NULL DEFAULT NULL,
  `website`       VARCHAR(255) NULL DEFAULT NULL,
  `status`        ENUM('active','suspended') NOT NULL DEFAULT 'active',
  `last_login`    DATETIME     NULL DEFAULT NULL,
  `created_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_employer_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Drop the on-site applications table (applicants now call/email directly) --
DROP TABLE IF EXISTS `applicants`;

-- 3. Jobs: replace tenant_id with employer_id, add company_phone --------------
ALTER TABLE `jobs`
  DROP FOREIGN KEY `fk_job_tenant`;
ALTER TABLE `jobs`
  DROP INDEX `idx_job_tenant`,
  DROP COLUMN `tenant_id`,
  ADD COLUMN `employer_id` INT UNSIGNED NULL DEFAULT NULL AFTER `id`,
  ADD COLUMN `company_phone` VARCHAR(40) NULL DEFAULT NULL AFTER `company_email`,
  ADD KEY `idx_job_employer` (`employer_id`),
  ADD CONSTRAINT `fk_job_employer` FOREIGN KEY (`employer_id`) REFERENCES `employers` (`id`) ON DELETE SET NULL;

-- 4. Activity log: drop tenant scoping ----------------------------------------
ALTER TABLE `activity_log`
  DROP FOREIGN KEY `fk_activity_tenant`;
ALTER TABLE `activity_log`
  DROP INDEX `idx_activity_tenant`,
  DROP COLUMN `tenant_id`;

-- 5. Admins: drop tenant scoping and role -------------------------------------
DELETE FROM `admins` WHERE `role` = 'company_admin';
ALTER TABLE `admins`
  DROP FOREIGN KEY `fk_admin_tenant`;
ALTER TABLE `admins`
  DROP INDEX `uq_admin_tenant_username`,
  DROP INDEX `uq_admin_tenant_email`,
  DROP COLUMN `role`,
  DROP COLUMN `tenant_id`,
  ADD UNIQUE KEY `uq_admin_username` (`username`),
  ADD UNIQUE KEY `uq_admin_email` (`email`);

-- 6. Drop the tenants table (now unreferenced) --------------------------------
DROP TABLE IF EXISTS `tenants`;

-- 7. Employer logins record the email as the username; widen the column -------
ALTER TABLE `login_attempts`
  MODIFY COLUMN `username` VARCHAR(150) NULL DEFAULT NULL;
