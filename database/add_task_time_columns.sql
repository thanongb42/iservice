-- =====================================================
-- Migration: Add start_time and end_time to task_assignments
-- เพิ่มคอลัมน์เวลาเริ่มต้นและสิ้นสุดในตารางมอบหมายงาน
-- Run on Production if columns don't exist
-- =====================================================

-- Check and add start_time column
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                   WHERE TABLE_SCHEMA = DATABASE() 
                   AND TABLE_NAME = 'task_assignments' 
                   AND COLUMN_NAME = 'start_time');

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE task_assignments ADD COLUMN start_time DATETIME DEFAULT NULL AFTER started_at', 
    'SELECT "start_time column already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check and add end_time column
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                   WHERE TABLE_SCHEMA = DATABASE() 
                   AND TABLE_NAME = 'task_assignments' 
                   AND COLUMN_NAME = 'end_time');

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE task_assignments ADD COLUMN end_time DATETIME DEFAULT NULL AFTER start_time', 
    'SELECT "end_time column already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verify columns
SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE, COLUMN_DEFAULT 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'task_assignments' 
AND COLUMN_NAME IN ('start_time', 'end_time');
