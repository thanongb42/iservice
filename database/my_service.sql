-- ====================================
-- My Service Management System
-- ====================================
-- สร้างตาราง my_service สำหรับจัดการบริการต่างๆ
-- รองรับการแสดงผลแบบ Service Cards
-- ====================================

CREATE TABLE IF NOT EXISTS `my_service` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `service_code` varchar(50) NOT NULL COMMENT 'รหัสบริการ (ภาษาอังกฤษ เช่น EMAIL, INTERNET)',
  `service_name` varchar(100) NOT NULL COMMENT 'ชื่อบริการ (ภาษาไทย)',
  `service_name_en` varchar(100) NOT NULL COMMENT 'ชื่อบริการ (ภาษาอังกฤษ)',
  `description` text DEFAULT NULL COMMENT 'คำอธิบายบริการ',
  `icon` varchar(50) DEFAULT NULL COMMENT 'Font Awesome icon class (เช่น fas fa-envelope)',
  `color_code` varchar(20) DEFAULT 'blue' COMMENT 'สี: blue, red, green, orange, purple, pink, indigo, teal',
  `service_url` varchar(255) DEFAULT '#' COMMENT 'URL สำหรับคลิกเข้าไปใช้บริการ',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1 = แสดง, 0 = ซ่อน',
  `display_order` int(11) NOT NULL DEFAULT 0 COMMENT 'ลำดับการแสดงผล (เรียงจากน้อยไปมาก)',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `service_code` (`service_code`),
  KEY `is_active` (`is_active`),
  KEY `display_order` (`display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================
-- Insert ข้อมูลเริ่มต้น (Sample Data)
-- ====================================

INSERT INTO `my_service` (`service_code`, `service_name`, `service_name_en`, `description`, `icon`, `color_code`, `service_url`, `is_active`, `display_order`) VALUES
('EMAIL', 'อีเมลเทศบาล', 'Email Service', 'ขอเปิดใช้งานอีเมลใหม่, รีเซ็ตรหัสผ่าน, เพิ่มขนาดพื้นที่กล่องจดหมาย', 'fas fa-envelope', 'blue', 'service-email.php', 1, 1),
('INTERNET', 'อินเทอร์เน็ต / WiFi', 'Internet Access', 'ขอรหัสผ่าน WiFi, แจ้งปัญหาเน็ตช้า, ติดตั้งจุดกระจายสัญญาณเพิ่ม', 'fas fa-wifi', 'indigo', 'service-internet.php', 1, 2),
('IT_SUPPORT', 'แจ้งซ่อมระบบ IT', 'IT Support', 'คอมพิวเตอร์เสีย, เครื่องพิมพ์มีปัญหา, ลงโปรแกรม, กำจัดไวรัส', 'fas fa-tools', 'red', 'service-it-support.php', 1, 3),
('NAS', 'พื้นที่เก็บข้อมูล NAS', 'NAS Storage', 'ขอพื้นที่แชร์ไฟล์ส่วนกลาง (Network Attached Storage), กู้คืนข้อมูลหาย', 'fas fa-hdd', 'orange', 'service-nas.php', 1, 4),
('QR_CODE', 'สร้าง QR Code', 'QR Code Generator', 'บริการสร้าง QR Code สำหรับประชาสัมพันธ์โครงการต่างๆ', 'fas fa-qrcode', 'purple', 'service-qrcode.php', 1, 5),
('PHOTOGRAPHY', 'บริการถ่ายภาพ', 'Photography Service', 'จองคิวช่างภาพสำหรับงานพิธี, งานกิจกรรมโครงการเทศบาล', 'fas fa-camera', 'pink', 'service-photo.php', 1, 6),
('WEB_DESIGN', 'ออกแบบเว็บไซต์', 'Web Design', 'ขอให้ออกแบบและพัฒนาเว็บไซต์สำหรับหน่วยงานต่างๆ', 'fas fa-laptop-code', 'teal', 'service-webdesign.php', 1, 7),
('PRINTER', 'เครื่องพิมพ์และสแกนเนอร์', 'Printer & Scanner', 'แจ้งซ่อมเครื่องพิมพ์, เติมหมึก, ซื้อวัสดุสิ้นเปลือง', 'fas fa-print', 'green', 'service-printer.php', 1, 8);

-- ====================================
-- Color Code Reference
-- ====================================
-- blue: สีฟ้า (Email, Default)
-- indigo: สีม่วงน้ำเงิน (Internet)
-- red: สีแดง (IT Support, Emergency)
-- orange: สีส้ม (Storage, Warning)
-- purple: สีม่วง (QR Code)
-- pink: สีชมพู (Photography)
-- teal: สีเขียวน้ำทะเล (Web Design)
-- green: สีเขียว (Printer, Success)
-- gray/slate: สีเทา (Archived/Inactive)
-- yellow: สีเหลือง (ไว้ใช้ในอนาคต)

-- ====================================
-- Query ตัวอย่างสำหรับดึงข้อมูล Service
-- ====================================

-- Query 1: ดึง Service ที่เปิดใช้งานทั้งหมด (เรียงตาม display_order)
-- SELECT * FROM my_service WHERE is_active = 1 ORDER BY display_order ASC;

-- Query 2: นับจำนวน Service แต่ละสี
-- SELECT color_code, COUNT(*) as total FROM my_service WHERE is_active = 1 GROUP BY color_code;

-- Query 3: ค้นหา Service ตามชื่อ
-- SELECT * FROM my_service WHERE service_name LIKE '%email%' OR service_name_en LIKE '%email%';
