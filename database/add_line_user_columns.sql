-- ============================================================
-- LINE Account Linking Migration
-- Run this script ONCE on your database.
-- MySQL 5.7 does not support "ADD COLUMN IF NOT EXISTS",
-- so run only on a fresh schema or comment out columns that exist.
-- ============================================================

-- 1) Add LINE columns to users table
ALTER TABLE `users`
  ADD COLUMN `line_user_id`      VARCHAR(50)  NULL UNIQUE AFTER `profile_image`,
  ADD COLUMN `line_display_name` VARCHAR(100) NULL        AFTER `line_user_id`,
  ADD COLUMN `line_picture_url`  VARCHAR(500) NULL        AFTER `line_display_name`,
  ADD COLUMN `line_linked_at`    DATETIME     NULL        AFTER `line_picture_url`;

-- 2) Add LINE Login credentials to system_settings
--    (uses ON DUPLICATE KEY UPDATE so safe to re-run)
INSERT INTO `system_settings` (`setting_key`, `setting_value`, `setting_type`, `description`) VALUES
  ('line_login_channel_id',     '', 'text',     'LINE Login Channel ID (จาก LINE Developers Console)')
ON DUPLICATE KEY UPDATE `description` = VALUES(`description`);

INSERT INTO `system_settings` (`setting_key`, `setting_value`, `setting_type`, `description`) VALUES
  ('line_login_channel_secret', '', 'password', 'LINE Login Channel Secret')
ON DUPLICATE KEY UPDATE `description` = VALUES(`description`);

INSERT INTO `system_settings` (`setting_key`, `setting_value`, `setting_type`, `description`) VALUES
  ('line_bot_basic_id', '', 'text', 'LINE Bot Basic ID สำหรับลิงก์เพิ่มเพื่อน (เช่น @abc1234d)')
ON DUPLICATE KEY UPDATE `description` = VALUES(`description`);

-- 3) Messaging API token (may already exist — safe to re-run)
INSERT INTO `system_settings` (`setting_key`, `setting_value`, `setting_type`, `description`) VALUES
  ('line_channel_token', '', 'password', 'LINE Messaging API Channel Access Token')
ON DUPLICATE KEY UPDATE `description` = VALUES(`description`);

-- NOTE: The LINE Login callback URL for login.php is auto-derived from line_callback_url:
--   line_callback_url  = https://yourdomain.com/admin/line_callback.php   (account linking)
--   login callback URL = https://yourdomain.com/line_login_callback.php   (login page)
-- Both URLs must be registered in the LINE Login Channel's Callback URL list.
