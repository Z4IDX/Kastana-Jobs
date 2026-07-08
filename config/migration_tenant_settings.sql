-- ============================================================
--  Kastana Jobs — Per-tenant settings migration (Phase 2)
--  Run this ONCE on an existing multi-tenant install.
--  Fresh installs already include this column via database.sql.
--
--  A JSON blob of a company's customization options (hero copy, colours,
--  board-behaviour toggles, footer text, …) so new options can be added
--  without further schema changes. Read via tenant_setting().
-- ============================================================
USE `kastana_jobs`;
SET NAMES utf8mb4;

ALTER TABLE `tenants`
  ADD COLUMN IF NOT EXISTS `settings` TEXT NULL DEFAULT NULL AFTER `primary_color`;
