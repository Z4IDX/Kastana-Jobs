-- ============================================================
--  Kastana Jobs — Job view counter ("most viewed")
--  Run this ONCE on an existing install (Navicat / phpMyAdmin).
--  Fresh installs already include this column via database.sql.
-- ============================================================
USE `kastana_jobs`;
SET NAMES utf8mb4;

ALTER TABLE `jobs`
  ADD COLUMN IF NOT EXISTS `views` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `expires_at`;
