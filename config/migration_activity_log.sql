-- ============================================================
--  Kastana Jobs — Activity log migration
--  Run this ONCE on an existing install (Navicat / phpMyAdmin).
--  Fresh installs already include this table via database.sql.
-- ============================================================
USE `kastana_jobs`;
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `activity_log` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `admin_id`   INT UNSIGNED NULL DEFAULT NULL,
  `job_id`     INT UNSIGNED NULL DEFAULT NULL,
  `action`     VARCHAR(30)  NOT NULL,
  `details`    VARCHAR(255) NULL DEFAULT NULL,
  `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_activity_created` (`created_at`),
  KEY `idx_activity_job` (`job_id`),
  CONSTRAINT `fk_activity_admin` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_activity_job` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
