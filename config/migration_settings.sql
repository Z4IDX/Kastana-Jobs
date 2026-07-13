-- Key/value settings. Currently holds `moderation_mode` (both|companies|jobs):
-- what a new sign-up must have approved before going live. Run once on existing installs.
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `settings` (
  `k` VARCHAR(50) NOT NULL PRIMARY KEY,
  `v` VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `settings` (`k`, `v`) VALUES ('moderation_mode', 'both')
  ON DUPLICATE KEY UPDATE `v` = `v`;
