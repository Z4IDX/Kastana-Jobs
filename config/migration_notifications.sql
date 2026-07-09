-- ============================================================
--  Kastana Jobs — Employer notifications
--  Run this ONCE on an existing install (Navicat / phpMyAdmin).
--  Fresh installs already include this table via database.sql.
--
--  On-site notifications shown to an employer when a posting of theirs
--  is approved or rejected (there is no email transport).
-- ============================================================
USE `kastana_jobs`;
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `notifications` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `employer_id` INT UNSIGNED NOT NULL,
  `job_id`      INT UNSIGNED NULL DEFAULT NULL,
  `type`        VARCHAR(20)  NOT NULL,             -- 'approved' | 'rejected'
  `title`       VARCHAR(200) NULL DEFAULT NULL,    -- posting title snapshot
  `is_read`     TINYINT(1)   NOT NULL DEFAULT 0,
  `created_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_notif_emp` (`employer_id`, `is_read`),
  CONSTRAINT `fk_notif_emp` FOREIGN KEY (`employer_id`) REFERENCES `employers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_notif_job` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
