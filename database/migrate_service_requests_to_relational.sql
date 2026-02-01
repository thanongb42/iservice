-- ========================================
-- Migration Script: Service Requests to Relational Database
-- แปลง service_requests ให้เป็น Relational Database ที่ถูกต้อง
-- ใช้ user_id และ department_id แท้จริงแทน text fields
-- ========================================

-- Step 1: Backup existing data
-- ========================================
DROP TABLE IF EXISTS service_requests_backup;
CREATE TABLE service_requests_backup AS SELECT * FROM service_requests;

SELECT 'Step 1: ✅ Backup ข้อมูลเดิมเรียบร้อย' as status;


-- Step 2: Add new columns for FKs
-- ========================================
ALTER TABLE service_requests
ADD COLUMN user_id INT NULL COMMENT 'FK to users - ผู้สร้างคำขอ' AFTER id,
ADD COLUMN service_name VARCHAR(100) NULL COMMENT 'ชื่อบริการ' AFTER service_code,
ADD COLUMN subject VARCHAR(255) NULL COMMENT 'หัวข้อคำขอ' AFTER service_name,
ADD COLUMN description TEXT NULL COMMENT 'รายละเอียด' AFTER subject,
ADD COLUMN department_id INT NULL COMMENT 'FK to departments' AFTER description,
ADD COLUMN requester_prefix_id INT NULL COMMENT 'FK to prefixes' AFTER department_id,
ADD COLUMN assigned_to_user_id INT NULL COMMENT 'FK to users - ผู้รับผิดชอบ' AFTER assigned_to;

SELECT 'Step 2: ✅ เพิ่ม columns ใหม่เรียบร้อย' as status;


-- Step 3: Populate service_name from service_code
-- ========================================
UPDATE service_requests
SET service_name = CASE service_code
    WHEN 'EMAIL' THEN 'ขอใช้บริการ Email'
    WHEN 'NAS' THEN 'ขอใช้พื้นที่ NAS'
    WHEN 'IT_SUPPORT' THEN 'ขอรับการสนับสนุนด้าน IT'
    WHEN 'INTERNET' THEN 'ขอใช้บริการ Internet'
    WHEN 'QR_CODE' THEN 'ขอทำ QR Code'
    WHEN 'PHOTOGRAPHY' THEN 'ขอถ่ายภาพกิจกรรม'
    WHEN 'WEB_DESIGN' THEN 'ขอออกแบบเว็บไซต์'
    WHEN 'PRINTER' THEN 'ขอใช้เครื่องพิมพ์'
    ELSE service_code
END;

SELECT 'Step 3: ✅ Populate service_name เรียบร้อย' as status;


-- Step 4: Populate subject and description from notes
-- ========================================
UPDATE service_requests
SET
    subject = CONCAT(service_name, ' - ', requester_name),
    description = COALESCE(notes, CONCAT('คำขอ', service_name, ' จาก ', requester_name));

SELECT 'Step 4: ✅ Populate subject & description เรียบร้อย' as status;


-- Step 5: Create or find users for requesters
-- ========================================
-- Insert requesters as users if they don't exist (based on email)
INSERT INTO users (username, email, first_name, last_name, password, role, status, created_at)
SELECT
    SUBSTRING_INDEX(sr.requester_email, '@', 1) as username,
    sr.requester_email as email,
    SUBSTRING_INDEX(sr.requester_name, ' ', 1) as first_name,
    SUBSTRING_INDEX(sr.requester_name, ' ', -1) as last_name,
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' as password,
    'user' as role,
    'active' as status,
    NOW() as created_at
FROM service_requests sr
WHERE sr.requester_email IS NOT NULL
  AND sr.requester_email != ''
  AND NOT EXISTS (
      SELECT 1 FROM users u WHERE u.email = sr.requester_email
  )
GROUP BY sr.requester_email;

SELECT 'Step 5: ✅ สร้าง users สำหรับ requesters เรียบร้อย' as status;


-- Step 6: Map user_id from email
-- ========================================
UPDATE service_requests sr
INNER JOIN users u ON sr.requester_email = u.email
SET sr.user_id = u.user_id
WHERE sr.requester_email IS NOT NULL AND sr.requester_email != '';

-- For requesters without email, create generic users
INSERT INTO users (username, email, first_name, last_name, password, role, status, created_at)
SELECT
    CONCAT('user_', sr.id) as username,
    CONCAT('user_', sr.id, '@temp.local') as email,
    SUBSTRING_INDEX(sr.requester_name, ' ', 1) as first_name,
    SUBSTRING_INDEX(sr.requester_name, ' ', -1) as last_name,
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' as password,
    'user' as role,
    'active' as status,
    NOW() as created_at
FROM service_requests sr
WHERE (sr.requester_email IS NULL OR sr.requester_email = '')
  AND sr.user_id IS NULL
  AND NOT EXISTS (
      SELECT 1 FROM users u WHERE u.username = CONCAT('user_', sr.id)
  );

UPDATE service_requests sr
INNER JOIN users u ON u.username = CONCAT('user_', sr.id)
SET sr.user_id = u.user_id
WHERE sr.user_id IS NULL;

SELECT 'Step 6: ✅ Map user_id เรียบร้อย' as status;


-- Step 7: Map department_id from department text
-- ========================================
-- Create departments if they don't exist
INSERT INTO departments (department_name, department_code, status, created_at)
SELECT DISTINCT
    sr.department as department_name,
    UPPER(REPLACE(SUBSTRING(sr.department, 1, 20), ' ', '_')) as department_code,
    'active' as status,
    NOW() as created_at
FROM service_requests sr
WHERE sr.department IS NOT NULL
  AND sr.department != ''
  AND NOT EXISTS (
      SELECT 1 FROM departments d WHERE d.department_name = sr.department
  );

-- Map department_id
UPDATE service_requests sr
INNER JOIN departments d ON sr.department = d.department_name
SET sr.department_id = d.department_id
WHERE sr.department IS NOT NULL AND sr.department != '';

SELECT 'Step 7: ✅ Map department_id เรียบร้อย' as status;


-- Step 8: Map assigned_to_user_id from assigned_to text
-- ========================================
-- Create staff users for assigned_to if they don't exist
INSERT INTO users (username, first_name, last_name, email, password, role, status, created_at)
SELECT DISTINCT
    CONCAT('staff_', REPLACE(LOWER(sr.assigned_to), ' ', '_')) as username,
    SUBSTRING_INDEX(SUBSTRING_INDEX(sr.assigned_to, ':', -1), ' ', 1) as first_name,
    SUBSTRING_INDEX(SUBSTRING_INDEX(sr.assigned_to, ':', -1), ' ', -1) as last_name,
    CONCAT(REPLACE(LOWER(sr.assigned_to), ' ', '_'), '@staff.local') as email,
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' as password,
    'staff' as role,
    'active' as status,
    NOW() as created_at
FROM service_requests sr
WHERE sr.assigned_to IS NOT NULL
  AND sr.assigned_to != ''
  AND NOT EXISTS (
      SELECT 1 FROM users u
      WHERE u.username = CONCAT('staff_', REPLACE(LOWER(sr.assigned_to), ' ', '_'))
  );

-- Map assigned_to_user_id
UPDATE service_requests sr
SET sr.assigned_to_user_id = (
    SELECT u.user_id
    FROM users u
    WHERE u.username = CONCAT('staff_', REPLACE(LOWER(sr.assigned_to), ' ', '_'))
    LIMIT 1
)
WHERE sr.assigned_to IS NOT NULL AND sr.assigned_to != '';

SELECT 'Step 8: ✅ Map assigned_to_user_id เรียบร้อย' as status;


-- Step 9: Rename and modify columns
-- ========================================
ALTER TABLE service_requests
CHANGE COLUMN id request_id INT AUTO_INCREMENT,
CHANGE COLUMN notes notes_legacy TEXT COMMENT 'หมายเหตุเดิม (เก็บไว้)',
CHANGE COLUMN department department_legacy VARCHAR(100) COMMENT 'แผนกเดิม (เก็บไว้)',
CHANGE COLUMN assigned_to assigned_to_legacy VARCHAR(100) COMMENT 'ผู้รับผิดชอบเดิม (เก็บไว้)',
MODIFY COLUMN user_id INT NOT NULL COMMENT 'FK to users - ผู้สร้างคำขอ',
MODIFY COLUMN service_name VARCHAR(100) NOT NULL COMMENT 'ชื่อบริการ',
MODIFY COLUMN subject VARCHAR(255) NOT NULL COMMENT 'หัวข้อคำขอ',
MODIFY COLUMN description TEXT NOT NULL COMMENT 'รายละเอียด',
ADD COLUMN assigned_at DATETIME NULL COMMENT 'วันที่มอบหมาย' AFTER assigned_to_user_id,
ADD COLUMN started_at DATETIME NULL COMMENT 'วันที่เริ่มทำ' AFTER assigned_at,
ADD COLUMN cancelled_at DATETIME NULL COMMENT 'วันที่ยกเลิก' AFTER completed_date,
ADD COLUMN completion_notes TEXT NULL COMMENT 'บันทึกการเสร็จสิ้น' AFTER admin_notes,
ADD COLUMN attachment_file VARCHAR(255) NULL COMMENT 'ไฟล์แนบ' AFTER completion_notes,
ADD COLUMN attachment_original_name VARCHAR(255) NULL COMMENT 'ชื่อไฟล์แนบเดิม' AFTER attachment_file,
ADD COLUMN request_data JSON NULL COMMENT 'ข้อมูลเพิ่มเติม' AFTER attachment_original_name;

-- Rename date columns for consistency
ALTER TABLE service_requests
CHANGE COLUMN requested_date created_at_date DATE NOT NULL COMMENT 'วันที่สร้างคำขอ',
CHANGE COLUMN target_date expected_completion_date DATE NULL COMMENT 'วันที่คาดว่าจะเสร็จ',
CHANGE COLUMN completed_date completed_at_date DATE NULL COMMENT 'วันที่เสร็จสิ้น';

-- Add proper timestamps
ALTER TABLE service_requests
ADD COLUMN completed_at DATETIME NULL COMMENT 'วันเวลาเสร็จสิ้น' AFTER completed_at_date;

-- Copy date to datetime
UPDATE service_requests
SET completed_at = TIMESTAMP(completed_at_date)
WHERE completed_at_date IS NOT NULL;

SELECT 'Step 9: ✅ Rename และ modify columns เรียบร้อย' as status;


-- Step 10: Add Foreign Key Constraints
-- ========================================
ALTER TABLE service_requests
ADD CONSTRAINT fk_sr_user
    FOREIGN KEY (user_id) REFERENCES users(user_id)
    ON DELETE CASCADE ON UPDATE CASCADE,

ADD CONSTRAINT fk_sr_department
    FOREIGN KEY (department_id) REFERENCES departments(department_id)
    ON DELETE SET NULL ON UPDATE CASCADE,

ADD CONSTRAINT fk_sr_assigned_user
    FOREIGN KEY (assigned_to_user_id) REFERENCES users(user_id)
    ON DELETE SET NULL ON UPDATE CASCADE,

ADD CONSTRAINT fk_sr_prefix
    FOREIGN KEY (requester_prefix_id) REFERENCES prefixes(prefix_id)
    ON DELETE SET NULL ON UPDATE CASCADE;

SELECT 'Step 10: ✅ เพิ่ม Foreign Keys เรียบร้อย' as status;


-- Step 11: Add Indexes
-- ========================================
ALTER TABLE service_requests
ADD INDEX idx_user_id (user_id),
ADD INDEX idx_service_code (service_code),
ADD INDEX idx_status (status),
ADD INDEX idx_priority (priority),
ADD INDEX idx_department_id (department_id),
ADD INDEX idx_assigned_to (assigned_to_user_id),
ADD INDEX idx_created_at (created_at),
ADD INDEX idx_status_priority (status, priority);

SELECT 'Step 11: ✅ เพิ่ม Indexes เรียบร้อย' as status;


-- Step 12: Create Triggers
-- ========================================
DELIMITER //

-- Trigger: Auto update timestamps
DROP TRIGGER IF EXISTS before_service_request_update//
CREATE TRIGGER before_service_request_update
BEFORE UPDATE ON service_requests
FOR EACH ROW
BEGIN
    -- Set started_at when status changes to in_progress
    IF NEW.status = 'in_progress' AND OLD.status = 'pending' AND NEW.started_at IS NULL THEN
        SET NEW.started_at = NOW();
    END IF;

    -- Set completed_at when status changes to completed
    IF NEW.status = 'completed' AND OLD.status != 'completed' AND NEW.completed_at IS NULL THEN
        SET NEW.completed_at = NOW();
        SET NEW.completed_at_date = CURDATE();
    END IF;

    -- Set cancelled_at when status changes to cancelled
    IF NEW.status = 'cancelled' AND OLD.status != 'cancelled' AND NEW.cancelled_at IS NULL THEN
        SET NEW.cancelled_at = NOW();
    END IF;

    -- Set assigned_at when assigned_to_user_id is set
    IF NEW.assigned_to_user_id IS NOT NULL AND OLD.assigned_to_user_id IS NULL THEN
        SET NEW.assigned_at = NOW();
    END IF;
END//

DELIMITER ;

SELECT 'Step 12: ✅ สร้าง Triggers เรียบร้อย' as status;


-- Step 13: Create or Replace View
-- ========================================
DROP VIEW IF EXISTS v_service_requests_full;

CREATE VIEW v_service_requests_full AS
SELECT
    sr.request_id,
    sr.request_code,
    sr.service_code,
    sr.service_name,
    sr.subject,
    sr.description,
    sr.status,
    sr.priority,
    sr.created_at,
    sr.updated_at,

    -- Requester Info (ข้อมูลเดิมเก็บไว้)
    rp.prefix_name as requester_prefix,
    sr.requester_name,
    sr.requester_email,
    sr.requester_phone,
    sr.position as requester_position,

    -- Department Info
    sr.department_id,
    d.department_name,
    d.department_code,

    -- User Info (ผู้สร้างคำขอ)
    sr.user_id,
    u.username,
    u.email as user_email,
    CONCAT(IFNULL(up.prefix_name, ''), ' ', u.first_name, ' ', u.last_name) as user_full_name,

    -- Assigned Staff Info
    sr.assigned_to_user_id as assigned_to,
    sr.assigned_at,
    au.username as assigned_username,
    CONCAT(IFNULL(ap.prefix_name, ''), ' ', au.first_name, ' ', au.last_name) as assigned_full_name,

    -- Notes and Actions
    sr.admin_notes,
    sr.rejection_reason,
    sr.completion_notes,

    -- Dates
    sr.expected_completion_date,
    sr.started_at,
    sr.completed_at,
    sr.cancelled_at,

    -- Attachments
    sr.attachment_file,
    sr.attachment_original_name,

    -- Additional Data
    sr.request_data

FROM service_requests sr
LEFT JOIN users u ON sr.user_id = u.user_id
LEFT JOIN prefixes up ON u.prefix_id = up.prefix_id
LEFT JOIN prefixes rp ON sr.requester_prefix_id = rp.prefix_id
LEFT JOIN departments d ON sr.department_id = d.department_id
LEFT JOIN users au ON sr.assigned_to_user_id = au.user_id
LEFT JOIN prefixes ap ON au.prefix_id = ap.prefix_id;

SELECT 'Step 13: ✅ สร้าง View ใหม่เรียบร้อย' as status;


-- Step 14: Create Stored Procedures
-- ========================================
DELIMITER //

-- Get service request by ID
DROP PROCEDURE IF EXISTS sp_get_service_request//
CREATE PROCEDURE sp_get_service_request(IN p_request_id INT)
BEGIN
    SELECT * FROM v_service_requests_full WHERE request_id = p_request_id;
END//

-- Get service requests by user
DROP PROCEDURE IF EXISTS sp_get_user_service_requests//
CREATE PROCEDURE sp_get_user_service_requests(IN p_user_id INT)
BEGIN
    SELECT * FROM v_service_requests_full
    WHERE user_id = p_user_id
    ORDER BY created_at DESC;
END//

-- Get service requests by status
DROP PROCEDURE IF EXISTS sp_get_service_requests_by_status//
CREATE PROCEDURE sp_get_service_requests_by_status(IN p_status VARCHAR(50))
BEGIN
    SELECT * FROM v_service_requests_full
    WHERE status = p_status
    ORDER BY priority DESC, created_at DESC;
END//

DELIMITER ;

SELECT 'Step 14: ✅ สร้าง Stored Procedures เรียบร้อย' as status;


-- Step 15: Summary and Verification
-- ========================================
SELECT '========================================' as '';
SELECT 'Migration สำเร็จ! 🎉' as 'STATUS';
SELECT '========================================' as '';

-- Show statistics
SELECT 'สถิติข้อมูล:' as '';
SELECT
    COUNT(*) as 'จำนวนคำขอทั้งหมด',
    SUM(CASE WHEN user_id IS NOT NULL THEN 1 ELSE 0 END) as 'มี user_id',
    SUM(CASE WHEN department_id IS NOT NULL THEN 1 ELSE 0 END) as 'มี department_id',
    SUM(CASE WHEN assigned_to_user_id IS NOT NULL THEN 1 ELSE 0 END) as 'ถูกมอบหมายแล้ว'
FROM service_requests;

-- Show sample data
SELECT 'ข้อมูลตัวอย่าง:' as '';
SELECT
    request_id,
    request_code,
    service_name,
    user_full_name as 'ผู้ขอ',
    department_name as 'แผนก',
    assigned_full_name as 'ผู้รับผิดชอบ',
    status,
    priority
FROM v_service_requests_full
LIMIT 5;

SELECT '========================================' as '';
SELECT 'โครงสร้างใหม่:' as '';
SELECT '✅ ใช้ user_id (FK to users)' as '';
SELECT '✅ ใช้ department_id (FK to departments)' as '';
SELECT '✅ ใช้ assigned_to_user_id (FK to users)' as '';
SELECT '✅ มี service_name, subject, description' as '';
SELECT '✅ มี Foreign Keys และ Indexes' as '';
SELECT '✅ มี Triggers สำหรับ auto-update' as '';
SELECT '✅ มี View และ Stored Procedures' as '';
SELECT '========================================' as '';

-- Backup reminder
SELECT '⚠️ สำคัญ: ข้อมูลเดิมถูก backup ไว้ที่ service_requests_backup' as 'REMINDER';
SELECT 'คอลัมน์เดิม (notes, department, assigned_to) เปลี่ยนชื่อเป็น *_legacy และเก็บไว้' as 'REMINDER';
