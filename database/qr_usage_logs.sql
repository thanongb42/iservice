-- =============================================
-- QR Code Usage Logs
-- ตารางบันทึกสถิติการใช้งานเครื่องมือสร้าง QR Code
-- (ไม่ผูกกับ service_requests - เป็นบริการฟรี)
-- =============================================

CREATE TABLE IF NOT EXISTS `qr_usage_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `qr_type` VARCHAR(50) NOT NULL COMMENT 'ประเภท QR: url, text, vcard, wifi, payment',
    `qr_content` TEXT NOT NULL COMMENT 'เนื้อหา/URL ที่ใส่ใน QR',
    `qr_size` VARCHAR(20) DEFAULT 'medium' COMMENT 'ขนาด QR',
    `color_primary` VARCHAR(20) DEFAULT '#000000' COMMENT 'สีหลัก',
    `color_background` VARCHAR(20) DEFAULT '#ffffff' COMMENT 'สีพื้นหลัง',
    `output_format` VARCHAR(10) DEFAULT 'png' COMMENT 'รูปแบบไฟล์: png, svg',
    `ip_address` VARCHAR(45) DEFAULT NULL COMMENT 'IP ผู้ใช้งาน',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'วันเวลาที่สร้าง'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
