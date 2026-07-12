-- Account verification: new employer sign-ups start 'pending' and must be approved
-- by an admin (admin/employers.php) before they can post. Run once on existing installs.
SET NAMES utf8mb4;

ALTER TABLE `employers`
  MODIFY `status` ENUM('pending','active','suspended') NOT NULL DEFAULT 'active';

-- Column DEFAULT stays 'active' so admin-created/seed accounts remain usable; the
-- open registration form (register.php) inserts status='pending' explicitly.
