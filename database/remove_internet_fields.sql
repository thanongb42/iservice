-- =====================================================
-- Remove unnecessary fields from INTERNET service requests
-- Run this script on phpMyAdmin in production
-- =====================================================
-- Date: 2026-02-04
-- Fields removed from form:
--   1. ความสำคัญ (priority) - Hidden from INTERNET form only (column kept in service_requests)
--   2. วันที่ต้องการให้เสร็จ (target_date) - Hidden from INTERNET form only (column kept in service_requests)
--   3. จำนวนผู้ใช้งาน (number_of_users) - REMOVED from request_internet_details
--   4. ความเร็วที่ต้องการ (required_speed) - REMOVED from request_internet_details
-- =====================================================

-- Step 1: Check current table structure
DESCRIBE request_internet_details;

-- Step 2: Remove the columns from request_internet_details table
-- For MySQL 8.0+
ALTER TABLE request_internet_details
DROP COLUMN IF EXISTS number_of_users,
DROP COLUMN IF EXISTS required_speed;

-- Alternative for MySQL 5.7 (run these one by one, ignore errors if column doesn't exist):
-- ALTER TABLE request_internet_details DROP COLUMN number_of_users;
-- ALTER TABLE request_internet_details DROP COLUMN required_speed;

-- Step 3: Verify the table structure after changes
DESCRIBE request_internet_details;

-- Step 4: Show sample data to confirm
SELECT
    rid.id,
    rid.request_id,
    rid.request_type,
    rid.location,
    rid.building,
    rid.room_number,
    rid.current_issue,
    sr.request_code,
    sr.requester_name,
    sr.created_at
FROM request_internet_details rid
JOIN service_requests sr ON rid.request_id = sr.request_id
ORDER BY sr.created_at DESC
LIMIT 10;

-- Success message
SELECT 'SUCCESS: Columns number_of_users and required_speed have been removed from request_internet_details table!' AS result;
