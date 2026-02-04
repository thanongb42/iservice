-- =====================================================
-- Internet Request Types Table
-- ตารางประเภทคำขอบริการ Internet
-- =====================================================
-- Run this on phpMyAdmin in production
-- =====================================================

-- Create table for internet request types
CREATE TABLE IF NOT EXISTS `internet_request_types` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `type_code` VARCHAR(50) NOT NULL UNIQUE COMMENT 'รหัสประเภท',
    `type_name` VARCHAR(100) NOT NULL COMMENT 'ชื่อประเภท (ภาษาไทย)',
    `type_name_en` VARCHAR(100) DEFAULT NULL COMMENT 'ชื่อประเภท (English)',
    `description` TEXT DEFAULT NULL COMMENT 'คำอธิบาย',
    `icon` VARCHAR(50) DEFAULT 'fa-wifi' COMMENT 'Font Awesome icon',
    `display_order` INT(11) DEFAULT 0 COMMENT 'ลำดับการแสดง',
    `is_active` TINYINT(1) DEFAULT 1 COMMENT 'สถานะเปิดใช้งาน',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_active` (`is_active`),
    KEY `idx_order` (`display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default request types
INSERT INTO `internet_request_types` (`type_code`, `type_name`, `type_name_en`, `description`, `icon`, `display_order`, `is_active`) VALUES
('new_wifi', 'ขอรหัสผ่าน WiFi ใหม่', 'Request New WiFi Password', 'ขอรหัสผ่าน WiFi สำหรับผู้ใช้ใหม่', 'fa-key', 1, 1),
('password_reset', 'Reset รหัสผ่าน Internet', 'Reset Internet Password', 'รีเซ็ตรหัสผ่าน Internet สำหรับผู้ใช้เดิม', 'fa-sync', 2, 1),
('new_connection', 'ขอติดตั้งจุดเชื่อมต่อใหม่', 'Request New Connection Point', 'ขอติดตั้ง Access Point หรือจุดเชื่อมต่อใหม่', 'fa-network-wired', 3, 1),
('signal_issue', 'แจ้งปัญหาสัญญาณ WiFi', 'WiFi Signal Issue', 'สัญญาณ WiFi อ่อน หรือไม่เสถียร', 'fa-signal', 4, 1),
('speed_issue', 'แจ้งปัญหาความเร็ว Internet', 'Internet Speed Issue', 'ความเร็ว Internet ช้ากว่าปกติ', 'fa-tachometer-alt', 5, 1),
('cannot_connect', 'เชื่อมต่อไม่ได้', 'Cannot Connect', 'ไม่สามารถเชื่อมต่อ WiFi หรือ Internet ได้', 'fa-times-circle', 6, 1),
('other', 'อื่นๆ', 'Other', 'คำขออื่นๆ ที่ไม่อยู่ในรายการ', 'fa-ellipsis-h', 99, 1)
ON DUPLICATE KEY UPDATE
    type_name = VALUES(type_name),
    type_name_en = VALUES(type_name_en),
    description = VALUES(description);

-- Verify the data
SELECT * FROM internet_request_types ORDER BY display_order;

-- Success message
SELECT 'Internet request types table created successfully!' AS result;
