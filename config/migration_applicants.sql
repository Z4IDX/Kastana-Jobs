-- ============================================================
--  Kastana Jobs — Applicant tracking migration
--  Run this ONCE on an existing install (Navicat / phpMyAdmin).
--  Fresh installs already include this table via database.sql.
-- ============================================================
USE `kastana_jobs`;
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `applicants` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `job_id`     INT UNSIGNED NOT NULL,
  `name`       VARCHAR(150) NOT NULL,
  `email`      VARCHAR(150) NOT NULL,
  `phone`      VARCHAR(40)  NULL DEFAULT NULL,
  `cover_note` TEXT         NULL DEFAULT NULL,
  `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_applicant_job` (`job_id`),
  CONSTRAINT `fk_applicant_job` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
