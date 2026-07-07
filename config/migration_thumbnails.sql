-- ============================================================
--  Kastana Jobs — Thumbnail migration
--  Run this ONCE on an existing install (Navicat / phpMyAdmin).
--  Fresh installs already include this column via database.sql.
-- ============================================================
USE `kastana_jobs`;
SET NAMES utf8mb4;

ALTER TABLE `jobs`
  ADD COLUMN IF NOT EXISTS `thumbnail_path` VARCHAR(255) NULL DEFAULT NULL AFTER `image_path`;
