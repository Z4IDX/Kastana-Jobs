-- ============================================================
--  Kastana Jobs — Per-tenant branding migration (Phase 2)
--  Run this ONCE on an existing multi-tenant install.
--  Fresh installs already include these columns via database.sql.
--
--  Lets each company override the platform brand: display name, logo,
--  and a primary accent colour. All optional — blank falls back to
--  the tenant name / default logo / default palette.
-- ============================================================
USE `kastana_jobs`;
SET NAMES utf8mb4;

ALTER TABLE `tenants`
  ADD COLUMN IF NOT EXISTS `brand_name`    VARCHAR(150) NULL DEFAULT NULL AFTER `name`,
  ADD COLUMN IF NOT EXISTS `logo_path`     VARCHAR(255) NULL DEFAULT NULL AFTER `brand_name`,
  ADD COLUMN IF NOT EXISTS `primary_color` VARCHAR(7)   NULL DEFAULT NULL AFTER `logo_path`;
