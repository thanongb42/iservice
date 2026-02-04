-- =====================================================
-- Add missing columns to departments table
-- Run this on phpMyAdmin in production
-- =====================================================
-- Date: 2026-02-04
-- Error: Unknown column 'building' in 'INSERT INTO'
-- =====================================================

-- Check current table structure
DESCRIBE departments;

-- Add missing columns if they don't exist
-- Use ALTER TABLE with IF NOT EXISTS (MySQL 8+) or run individually

-- For MySQL 5.7 / MariaDB (run one by one, ignore errors if column exists)
ALTER TABLE departments ADD COLUMN `building` VARCHAR(100) DEFAULT NULL COMMENT 'อาคาร' AFTER `manager_user_id`;
ALTER TABLE departments ADD COLUMN `floor` VARCHAR(50) DEFAULT NULL COMMENT 'ชั้น' AFTER `building`;
ALTER TABLE departments ADD COLUMN `phone` VARCHAR(20) DEFAULT NULL COMMENT 'เบอร์โทรศัพท์' AFTER `floor`;
ALTER TABLE departments ADD COLUMN `email` VARCHAR(100) DEFAULT NULL COMMENT 'อีเมล' AFTER `phone`;
ALTER TABLE departments ADD COLUMN `budget_code` VARCHAR(50) DEFAULT NULL COMMENT 'รหัสงบประมาณ' AFTER `email`;

-- Alternative: Full ALTER TABLE for all columns at once
-- This will fail if columns already exist - use the above individual statements instead
/*
ALTER TABLE departments
ADD COLUMN IF NOT EXISTS `building` VARCHAR(100) DEFAULT NULL COMMENT 'อาคาร' AFTER `manager_user_id`,
ADD COLUMN IF NOT EXISTS `floor` VARCHAR(50) DEFAULT NULL COMMENT 'ชั้น' AFTER `building`,
ADD COLUMN IF NOT EXISTS `phone` VARCHAR(20) DEFAULT NULL COMMENT 'เบอร์โทรศัพท์' AFTER `floor`,
ADD COLUMN IF NOT EXISTS `email` VARCHAR(100) DEFAULT NULL COMMENT 'อีเมล' AFTER `phone`,
ADD COLUMN IF NOT EXISTS `budget_code` VARCHAR(50) DEFAULT NULL COMMENT 'รหัสงบประมาณ' AFTER `email`;
*/

-- Verify the changes
DESCRIBE departments;

-- Show success message
SELECT 'Missing columns added to departments table successfully!' AS result;
