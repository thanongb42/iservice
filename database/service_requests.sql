-- Service Requests Table
-- Complete service request management system

CREATE TABLE IF NOT EXISTS service_requests (
    request_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    service_code VARCHAR(50) NOT NULL,
    service_name VARCHAR(100) NOT NULL,

    -- Requester Information
    requester_prefix_id INT NULL,
    requester_name VARCHAR(200) NOT NULL,
    requester_position VARCHAR(100) NULL,
    requester_phone VARCHAR(20) NULL,
    requester_email VARCHAR(255) NULL,

    -- Department Information
    department_id INT NULL,
    department_name VARCHAR(200) NULL,

    -- Request Details
    subject VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    request_data JSON NULL COMMENT 'Additional service-specific data',

    -- Status and Priority
    status ENUM('pending', 'in_progress', 'completed', 'cancelled', 'rejected') DEFAULT 'pending',
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',

    -- Assignment
    assigned_to INT NULL COMMENT 'Admin/Staff user_id',
    assigned_at DATETIME NULL,

    -- Admin Notes and Actions
    admin_notes TEXT NULL,
    rejection_reason TEXT NULL,
    completion_notes TEXT NULL,

    -- File Attachments
    attachment_file VARCHAR(255) NULL,
    attachment_original_name VARCHAR(255) NULL,

    -- Expected and Actual Dates
    expected_completion_date DATE NULL,
    started_at DATETIME NULL,
    completed_at DATETIME NULL,
    cancelled_at DATETIME NULL,

    -- Timestamps
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Foreign Keys
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (requester_prefix_id) REFERENCES prefixes(prefix_id) ON DELETE SET NULL,
    FOREIGN KEY (department_id) REFERENCES departments(department_id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_to) REFERENCES users(user_id) ON DELETE SET NULL,

    -- Indexes
    INDEX idx_user (user_id),
    INDEX idx_service_code (service_code),
    INDEX idx_status (status),
    INDEX idx_priority (priority),
    INDEX idx_assigned_to (assigned_to),
    INDEX idx_created_at (created_at),
    INDEX idx_status_priority (status, priority)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add column comments
ALTER TABLE service_requests
    MODIFY COLUMN request_id INT AUTO_INCREMENT COMMENT 'รหัสคำขอ',
    MODIFY COLUMN user_id INT NOT NULL COMMENT 'รหัสผู้ใช้ที่ขอบริการ (FK to users)',
    MODIFY COLUMN service_code VARCHAR(50) NOT NULL COMMENT 'รหัสบริการ (EMAIL, NAS, IT_SUPPORT, etc.)',
    MODIFY COLUMN service_name VARCHAR(100) NOT NULL COMMENT 'ชื่อบริการ',
    MODIFY COLUMN requester_prefix_id INT NULL COMMENT 'คำนำหน้าผู้ขอ (FK to prefixes)',
    MODIFY COLUMN requester_name VARCHAR(200) NOT NULL COMMENT 'ชื่อผู้ขอบริการ',
    MODIFY COLUMN requester_position VARCHAR(100) NULL COMMENT 'ตำแหน่งผู้ขอ',
    MODIFY COLUMN requester_phone VARCHAR(20) NULL COMMENT 'เบอร์โทรผู้ขอ',
    MODIFY COLUMN requester_email VARCHAR(255) NULL COMMENT 'อีเมลผู้ขอ',
    MODIFY COLUMN department_id INT NULL COMMENT 'รหัสหน่วยงาน (FK to departments)',
    MODIFY COLUMN department_name VARCHAR(200) NULL COMMENT 'ชื่อหน่วยงาน',
    MODIFY COLUMN subject VARCHAR(255) NOT NULL COMMENT 'หัวข้อคำขอ',
    MODIFY COLUMN description TEXT NOT NULL COMMENT 'รายละเอียดคำขอ',
    MODIFY COLUMN request_data JSON NULL COMMENT 'ข้อมูลเพิ่มเติมเฉพาะบริการ (JSON)',
    MODIFY COLUMN status ENUM('pending', 'in_progress', 'completed', 'cancelled', 'rejected') DEFAULT 'pending'
        COMMENT 'สถานะ: pending=รอดำเนินการ, in_progress=กำลังดำเนินการ, completed=เสร็จสิ้น, cancelled=ยกเลิก, rejected=ปฏิเสธ',
    MODIFY COLUMN priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium'
        COMMENT 'ความสำคัญ: low=ต่ำ, medium=ปานกลาง, high=สูง, urgent=เร่งด่วน',
    MODIFY COLUMN assigned_to INT NULL COMMENT 'มอบหมายให้ (FK to users)',
    MODIFY COLUMN assigned_at DATETIME NULL COMMENT 'วันที่มอบหมาย',
    MODIFY COLUMN admin_notes TEXT NULL COMMENT 'หมายเหตุจาก Admin',
    MODIFY COLUMN rejection_reason TEXT NULL COMMENT 'เหตุผลที่ปฏิเสธ',
    MODIFY COLUMN completion_notes TEXT NULL COMMENT 'บันทึกเมื่อเสร็จสิ้น',
    MODIFY COLUMN attachment_file VARCHAR(255) NULL COMMENT 'ไฟล์แนบ (path)',
    MODIFY COLUMN attachment_original_name VARCHAR(255) NULL COMMENT 'ชื่อไฟล์แนบเดิม',
    MODIFY COLUMN expected_completion_date DATE NULL COMMENT 'วันที่คาดว่าจะเสร็จ',
    MODIFY COLUMN started_at DATETIME NULL COMMENT 'วันที่เริ่มดำเนินการ',
    MODIFY COLUMN completed_at DATETIME NULL COMMENT 'วันที่เสร็จสิ้น',
    MODIFY COLUMN cancelled_at DATETIME NULL COMMENT 'วันที่ยกเลิก',
    MODIFY COLUMN created_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'วันที่สร้าง',
    MODIFY COLUMN updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'วันที่อัปเดต';

-- Create view for full service request information
CREATE OR REPLACE VIEW v_service_requests_full AS
SELECT
    sr.request_id,
    sr.service_code,
    sr.service_name,
    sr.subject,
    sr.description,
    sr.status,
    sr.priority,
    sr.created_at,
    sr.updated_at,

    -- Requester Info
    p.prefix_name as requester_prefix,
    sr.requester_name,
    sr.requester_position,
    sr.requester_phone,
    sr.requester_email,

    -- Department Info
    sr.department_name,
    d.department_code,

    -- User Info (who created the request)
    u.user_id,
    u.username,
    u.email as user_email,
    CONCAT(IFNULL(up.prefix_name, ''), ' ', u.first_name, ' ', u.last_name) as user_full_name,

    -- Assigned Staff Info
    sr.assigned_to,
    sr.assigned_at,
    au.username as assigned_username,
    CONCAT(IFNULL(ap.prefix_name, ''), ' ', au.first_name, ' ', au.last_name) as assigned_full_name,

    -- Notes and Dates
    sr.admin_notes,
    sr.rejection_reason,
    sr.completion_notes,
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
LEFT JOIN prefixes p ON sr.requester_prefix_id = p.prefix_id
LEFT JOIN departments d ON sr.department_id = d.department_id
LEFT JOIN users au ON sr.assigned_to = au.user_id
LEFT JOIN prefixes ap ON au.prefix_id = ap.prefix_id;

-- Insert sample service requests
INSERT INTO service_requests
(user_id, service_code, service_name, requester_prefix_id, requester_name, requester_position,
 requester_phone, requester_email, department_id, department_name, subject, description,
 status, priority, request_data)
VALUES
-- Email Account Requests
(
    (SELECT user_id FROM users WHERE username = 'somchai' LIMIT 1),
    'EMAIL',
    'ขอเปิด Email Account',
    (SELECT prefix_id FROM prefixes WHERE prefix_name = 'นาย' LIMIT 1),
    'นาย สมชาย ใจดี',
    'เจ้าหน้าที่ IT',
    '0812345678',
    'somchai@rangsit.go.th',
    1,
    'สำนักปลัด',
    'ขอเปิด Email Account สำหรับพนักงานใหม่',
    'ขอเปิด Email Account สำหรับพนักงานใหม่ที่เข้ามาทำงาน 2 คน เพื่อใช้ในการติดต่อสื่อสารภายในองค์กร',
    'pending',
    'medium',
    JSON_OBJECT(
        'email_type', 'official',
        'number_of_accounts', 2,
        'requested_domains', JSON_ARRAY('rangsit.go.th'),
        'storage_quota', '10GB'
    )
),
(
    (SELECT user_id FROM users WHERE username = 'somsri' LIMIT 1),
    'EMAIL',
    'ขอเปิด Email Account',
    (SELECT prefix_id FROM prefixes WHERE prefix_name = 'นาง' LIMIT 1),
    'นาง สมศรี รักดี',
    'หัวหน้าฝ่าย',
    '0823456789',
    'somsri@rangsit.go.th',
    2,
    'กองคลัง',
    'ขอเปิด Email Account เพิ่มเติม',
    'ขอเปิด Email สำหรับใช้งานฝ่ายบัญชี เพื่อรับเอกสารทางการเงิน',
    'in_progress',
    'high',
    JSON_OBJECT(
        'email_type', 'official',
        'number_of_accounts', 1,
        'requested_domains', JSON_ARRAY('rangsit.go.th'),
        'storage_quota', '20GB'
    )
),

-- NAS Storage Requests
(
    (SELECT user_id FROM users WHERE username = 'suree' LIMIT 1),
    'NAS',
    'ขอใช้พื้นที่ NAS',
    (SELECT prefix_id FROM prefixes WHERE prefix_name = 'นางสาว' LIMIT 1),
    'นางสาว สุรีย์ สว่างใจ',
    'เจ้าหน้าที่ธุรการ',
    '0834567890',
    'suree@rangsit.go.th',
    3,
    'กองช่าง',
    'ขอพื้นที่ NAS สำหรับเก็บไฟล์แบบก่อสร้าง',
    'ขอใช้พื้นที่ NAS ประมาณ 100GB สำหรับเก็บไฟล์แบบก่อสร้าง AutoCAD และรูปภาพ',
    'pending',
    'high',
    JSON_OBJECT(
        'storage_size', '100GB',
        'purpose', 'แบบก่อสร้างและรูปภาพ',
        'access_type', 'shared',
        'users_count', 5
    )
),

-- IT Support Requests
(
    (SELECT user_id FROM users WHERE username = 'admin' LIMIT 1),
    'IT_SUPPORT',
    'แจ้งซ่อม IT',
    (SELECT prefix_id FROM prefixes WHERE prefix_name = 'นาย' LIMIT 1),
    'นาย ผู้ดูแลระบบ เทศบาล',
    'ผู้อำนวยการ',
    '0845678901',
    'admin@rangsit.go.th',
    1,
    'สำนักปลัด',
    'คอมพิวเตอร์เปิดไม่ติด',
    'คอมพิวเตอร์ตัวที่ห้องประชุมใหญ่ เปิดไม่ติด กดปุ่ม Power แล้วไม่มีอะไรเกิดขึ้น',
    'completed',
    'urgent',
    JSON_OBJECT(
        'issue_type', 'hardware',
        'equipment', 'desktop',
        'location', 'ห้องประชุมใหญ่',
        'brand', 'Dell',
        'model', 'OptiPlex 7090'
    )
),
(
    (SELECT user_id FROM users WHERE username = 'somchai' LIMIT 1),
    'IT_SUPPORT',
    'แจ้งซ่อม IT',
    (SELECT prefix_id FROM prefixes WHERE prefix_name = 'นาย' LIMIT 1),
    'นาย สมชาย ใจดี',
    'เจ้าหน้าที่ IT',
    '0812345678',
    'somchai@rangsit.go.th',
    4,
    'กองการศึกษา',
    'Printer ไม่สามารถพิมพ์ได้',
    'Printer HP LaserJet ในห้อง 302 พิมพ์ไม่ออก แสดงข้อความ Error',
    'in_progress',
    'medium',
    JSON_OBJECT(
        'issue_type', 'printer',
        'equipment', 'printer',
        'location', 'ห้อง 302',
        'brand', 'HP',
        'model', 'LaserJet Pro M404dn',
        'error_message', 'Paper Jam Error'
    )
),

-- Internet Requests
(
    (SELECT user_id FROM users WHERE username = 'military_user' LIMIT 1),
    'INTERNET',
    'ขอใช้บริการ Internet',
    (SELECT prefix_id FROM prefixes WHERE prefix_name = 'ว่าที่ร้อยตรี' LIMIT 1),
    'ว่าที่ร้อยตรี ธนพล มั่นคง',
    'เจ้าหน้าที่',
    '0845678901',
    'thanaphon@rangsit.go.th',
    5,
    'กองสาธารณสุข',
    'ขอเพิ่มจุด WiFi ในห้องประชุม',
    'ขอติดตั้ง Access Point เพิ่มเติมในห้องประชุม ชั้น 3 เพื่อรองรับการประชุมออนไลน์',
    'pending',
    'medium',
    JSON_OBJECT(
        'request_type', 'wifi_installation',
        'location', 'ห้องประชุม ชั้น 3',
        'coverage_area', '50 ตารางเมตร',
        'expected_users', 30
    )
),

-- QR Code Requests
(
    (SELECT user_id FROM users WHERE username = 'doctor_user' LIMIT 1),
    'QR_CODE',
    'ขอสร้าง QR Code',
    (SELECT prefix_id FROM prefixes WHERE prefix_name = 'ดร.' LIMIT 1),
    'ดร. วิชัย ปัญญา',
    'นักวิชาการ',
    '0856789012',
    'wichai@rangsit.go.th',
    1,
    'สำนักปลัด',
    'ขอ QR Code สำหรับลงทะเบียนงานสัมมนา',
    'ขอสร้าง QR Code สำหรับใช้ในการลงทะเบียนเข้างานสัมมนาประจำปี โดยเชื่อมโยงกับ Google Form',
    'completed',
    'low',
    JSON_OBJECT(
        'qr_type', 'url',
        'target_url', 'https://forms.google.com/seminar2025',
        'quantity', 1,
        'format', 'PNG',
        'size', '500x500'
    )
),

-- Photography Requests
(
    (SELECT user_id FROM users WHERE username = 'somsri' LIMIT 1),
    'PHOTOGRAPHY',
    'ขอบริการถ่ายภาพ/วีดีโอ',
    (SELECT prefix_id FROM prefixes WHERE prefix_name = 'นาง' LIMIT 1),
    'นาง สมศรี รักดี',
    'หัวหน้าฝ่าย',
    '0823456789',
    'somsri@rangsit.go.th',
    2,
    'กองคลัง',
    'ขอบริการถ่ายภาพกิจกรรม',
    'ขอบริการถ่ายภาพและวีดีโอกิจกรรมวันสำคัญของเทศบาล วันที่ 15 มกราคม 2568',
    'pending',
    'high',
    JSON_OBJECT(
        'service_type', 'photo_video',
        'event_date', '2025-01-15',
        'event_time', '09:00-16:00',
        'location', 'หอประชุมเทศบาล',
        'deliverables', JSON_ARRAY('ภาพถ่ายสูง 100 ภาพ', 'วีดีโอไฮไลท์ 5 นาที')
    )
),

-- Web Design Requests
(
    (SELECT user_id FROM users WHERE username = 'suree' LIMIT 1),
    'WEB_DESIGN',
    'ขอออกแบบเว็บไซต์/กราฟิก',
    (SELECT prefix_id FROM prefixes WHERE prefix_name = 'นางสาว' LIMIT 1),
    'นางสาว สุรีย์ สว่างใจ',
    'เจ้าหน้าที่ธุรการ',
    '0834567890',
    'suree@rangsit.go.th',
    3,
    'กองช่าง',
    'ขอออกแบบโปสเตอร์ประชาสัมพันธ์',
    'ขอออกแบบโปสเตอร์ประชาสัมพันธ์โครงการก่อสร้างถนน ขนาด A3',
    'in_progress',
    'medium',
    JSON_OBJECT(
        'design_type', 'poster',
        'size', 'A3',
        'format', 'PDF',
        'quantity', 1,
        'theme_color', 'blue-green',
        'deadline', '2025-01-20'
    )
),

-- Printer Requests
(
    (SELECT user_id FROM users WHERE username = 'admin' LIMIT 1),
    'PRINTER',
    'ขอใช้บริการ Printer',
    (SELECT prefix_id FROM prefixes WHERE prefix_name = 'นาย' LIMIT 1),
    'นาย ผู้ดูแลระบบ เทศบาล',
    'ผู้อำนวยการ',
    '0845678901',
    'admin@rangsit.go.th',
    1,
    'สำนักปลัด',
    'ขอพิมพ์เอกสารประกอบการประชุม',
    'ขอพิมพ์เอกสารประกอบการประชุม จำนวน 50 ชุด ชุดละ 20 หน้า',
    'cancelled',
    'low',
    JSON_OBJECT(
        'print_type', 'document',
        'pages', 20,
        'copies', 50,
        'color', 'black_white',
        'paper_size', 'A4',
        'binding', 'staple'
    )
);

-- Update some requests with assignment
UPDATE service_requests
SET assigned_to = (SELECT user_id FROM users WHERE role = 'admin' LIMIT 1),
    assigned_at = NOW()
WHERE status IN ('in_progress', 'completed');

-- Update completed requests with completion info
UPDATE service_requests
SET started_at = DATE_SUB(NOW(), INTERVAL 2 DAY),
    completed_at = DATE_SUB(NOW(), INTERVAL 1 DAY),
    completion_notes = 'ดำเนินการเรียบร้อยแล้ว'
WHERE status = 'completed';

-- Update in_progress requests with start time
UPDATE service_requests
SET started_at = DATE_SUB(NOW(), INTERVAL 1 DAY)
WHERE status = 'in_progress';

-- Update cancelled request
UPDATE service_requests
SET cancelled_at = NOW(),
    admin_notes = 'ผู้ขอยกเลิกคำขอเอง'
WHERE status = 'cancelled';

-- Create stored procedure to get service requests by status
DELIMITER //

CREATE PROCEDURE sp_get_requests_by_status(IN p_status VARCHAR(20))
BEGIN
    SELECT * FROM v_service_requests_full
    WHERE status = p_status
    ORDER BY created_at DESC;
END //

DELIMITER ;

-- Create stored procedure to get user's requests
DELIMITER //

CREATE PROCEDURE sp_get_user_requests(IN p_user_id INT)
BEGIN
    SELECT * FROM v_service_requests_full
    WHERE user_id = p_user_id
    ORDER BY created_at DESC;
END //

DELIMITER ;

-- Create stored procedure to update request status
DELIMITER //

CREATE PROCEDURE sp_update_request_status(
    IN p_request_id INT,
    IN p_status VARCHAR(20),
    IN p_admin_notes TEXT,
    IN p_assigned_to INT
)
BEGIN
    DECLARE v_old_status VARCHAR(20);

    -- Get current status
    SELECT status INTO v_old_status FROM service_requests WHERE request_id = p_request_id;

    -- Update status
    UPDATE service_requests
    SET status = p_status,
        admin_notes = COALESCE(p_admin_notes, admin_notes),
        assigned_to = COALESCE(p_assigned_to, assigned_to),
        assigned_at = CASE
            WHEN p_assigned_to IS NOT NULL AND assigned_to IS NULL THEN NOW()
            ELSE assigned_at
        END,
        started_at = CASE
            WHEN p_status = 'in_progress' AND v_old_status = 'pending' THEN NOW()
            ELSE started_at
        END,
        completed_at = CASE
            WHEN p_status = 'completed' THEN NOW()
            ELSE completed_at
        END,
        cancelled_at = CASE
            WHEN p_status = 'cancelled' THEN NOW()
            ELSE cancelled_at
        END,
        updated_at = NOW()
    WHERE request_id = p_request_id;
END //

DELIMITER ;

-- Create trigger to auto-update timestamps
DELIMITER //

CREATE TRIGGER before_service_request_update
BEFORE UPDATE ON service_requests
FOR EACH ROW
BEGIN
    -- Auto set started_at when status changes to in_progress
    IF NEW.status = 'in_progress' AND OLD.status = 'pending' AND NEW.started_at IS NULL THEN
        SET NEW.started_at = NOW();
    END IF;

    -- Auto set completed_at when status changes to completed
    IF NEW.status = 'completed' AND OLD.status != 'completed' AND NEW.completed_at IS NULL THEN
        SET NEW.completed_at = NOW();
    END IF;

    -- Auto set cancelled_at when status changes to cancelled
    IF NEW.status = 'cancelled' AND OLD.status != 'cancelled' AND NEW.cancelled_at IS NULL THEN
        SET NEW.cancelled_at = NOW();
    END IF;

    -- Auto set assigned_at when assigned_to changes
    IF NEW.assigned_to IS NOT NULL AND OLD.assigned_to IS NULL THEN
        SET NEW.assigned_at = NOW();
    END IF;
END //

DELIMITER ;

-- Show table structure
DESC service_requests;

-- Show sample data
SELECT
    request_id,
    service_name,
    requester_name,
    department_name,
    subject,
    status,
    priority,
    assigned_full_name,
    created_at
FROM v_service_requests_full
ORDER BY created_at DESC;

-- Show statistics
SELECT
    service_code,
    service_name,
    COUNT(*) as total_requests,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
FROM service_requests
GROUP BY service_code, service_name
ORDER BY total_requests DESC;

-- Show priority distribution
SELECT
    priority,
    COUNT(*) as count,
    ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM service_requests), 2) as percentage
FROM service_requests
GROUP BY priority
ORDER BY FIELD(priority, 'urgent', 'high', 'medium', 'low');
