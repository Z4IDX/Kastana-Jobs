-- ============================================================
--  Kastana Jobs — Multi-tenancy migration (Phase 1 foundation)
--  Run this ONCE on an existing single-board install.
--  Fresh installs already include everything via database.sql.
--
--  Adds a tenants table, scopes jobs/applicants/activity_log to a
--  tenant, and gives admins a role + tenant. Existing data is moved
--  into one default, active tenant so nothing breaks.
-- ============================================================
USE `kastana_jobs`;
SET NAMES utf8mb4;

-- 1. Tenants (one company = one tenant, addressed by subdomain) -------------
CREATE TABLE IF NOT EXISTS `tenants` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`         VARCHAR(150) NOT NULL,
  `subdomain`    VARCHAR(63)  NOT NULL,
  `status`       ENUM('pending','active','suspended') NOT NULL DEFAULT 'pending',
  `created_at`   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `activated_at` DATETIME     NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tenant_subdomain` (`subdomain`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. A default tenant to hold all existing data ----------------------------
INSERT INTO `tenants` (`id`, `name`, `subdomain`, `status`, `activated_at`)
  VALUES (1, 'Default', 'app', 'active', NOW())
  ON DUPLICATE KEY UPDATE `id` = `id`;

-- 3. Scope content tables to a tenant (nullable -> backfill -> not null + FK)
ALTER TABLE `jobs` ADD COLUMN IF NOT EXISTS `tenant_id` INT UNSIGNED NULL AFTER `id`;
UPDATE `jobs` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
ALTER TABLE `jobs` MODIFY `tenant_id` INT UNSIGNED NOT NULL DEFAULT 1;  -- DEFAULT is transitional (tightened once all writes set it explicitly)
ALTER TABLE `jobs` ADD KEY `idx_job_tenant` (`tenant_id`);
ALTER TABLE `jobs` ADD CONSTRAINT `fk_job_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

ALTER TABLE `applicants` ADD COLUMN IF NOT EXISTS `tenant_id` INT UNSIGNED NULL AFTER `id`;
UPDATE `applicants` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
ALTER TABLE `applicants` MODIFY `tenant_id` INT UNSIGNED NOT NULL DEFAULT 1;  -- DEFAULT is transitional (tightened once all writes set it explicitly)
ALTER TABLE `applicants` ADD KEY `idx_applicant_tenant` (`tenant_id`);
ALTER TABLE `applicants` ADD CONSTRAINT `fk_applicant_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

ALTER TABLE `activity_log` ADD COLUMN IF NOT EXISTS `tenant_id` INT UNSIGNED NULL AFTER `id`;
UPDATE `activity_log` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
ALTER TABLE `activity_log` MODIFY `tenant_id` INT UNSIGNED NOT NULL DEFAULT 1;  -- DEFAULT is transitional (tightened once all writes set it explicitly)
ALTER TABLE `activity_log` ADD KEY `idx_activity_tenant` (`tenant_id`);
ALTER TABLE `activity_log` ADD CONSTRAINT `fk_activity_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

-- 4. Admins gain a role + tenant -------------------------------------------
--    Existing admins become platform super-admins (tenant_id = NULL).
--    Company admins (created at signup) carry their tenant_id.
ALTER TABLE `admins` ADD COLUMN IF NOT EXISTS `tenant_id` INT UNSIGNED NULL AFTER `id`;
ALTER TABLE `admins` ADD COLUMN IF NOT EXISTS `role` ENUM('super_admin','company_admin') NOT NULL DEFAULT 'company_admin' AFTER `password_hash`;
UPDATE `admins` SET `role` = 'super_admin', `tenant_id` = NULL;

-- Username/email become unique per tenant (two companies can each have "admin").
ALTER TABLE `admins` DROP INDEX `uq_admin_username`;
ALTER TABLE `admins` DROP INDEX `uq_admin_email`;
ALTER TABLE `admins` ADD UNIQUE KEY `uq_admin_tenant_username` (`tenant_id`, `username`);
ALTER TABLE `admins` ADD UNIQUE KEY `uq_admin_tenant_email` (`tenant_id`, `email`);
ALTER TABLE `admins` ADD CONSTRAINT `fk_admin_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;
