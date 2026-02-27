-- 10. LED Display Request Details
-- คำขอนำสื่อขึ้นจอ LED

CREATE TABLE IF NOT EXISTS `request_led_details` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `request_id` int(11) NOT NULL COMMENT 'FK -> service_requests.request_id',
  `media_title` varchar(255) NOT NULL COMMENT 'ชื่อสื่อ/หัวข้อ',
  `media_type` varchar(50) NOT NULL COMMENT 'ประเภทสื่อ (image, video, animation)',
  `display_location` varchar(255) NOT NULL COMMENT 'ตำแหน่งจอ LED ที่ต้องการ',
  `display_date_start` date NOT NULL COMMENT 'วันเริ่มแสดง',
  `display_date_end` date DEFAULT NULL COMMENT 'วันสิ้นสุดการแสดง',
  `display_time_start` time DEFAULT NULL COMMENT 'เวลาเริ่มแสดง',
  `display_time_end` time DEFAULT NULL COMMENT 'เวลาสิ้นสุดแสดง',
  `duration_seconds` int(11) DEFAULT 15 COMMENT 'ระยะเวลาแสดงต่อรอบ (วินาที)',
  `resolution` varchar(50) DEFAULT NULL COMMENT 'ความละเอียดไฟล์ เช่น 1920x1080',
  `media_file` varchar(500) DEFAULT NULL COMMENT 'ไฟล์สื่อที่อัปโหลด (path)',
  `media_url` varchar(500) DEFAULT NULL COMMENT 'ลิงก์สื่อ (Google Drive, URL)',
  `purpose` text NOT NULL COMMENT 'วัตถุประสงค์/รายละเอียด',
  `special_requirements` text DEFAULT NULL COMMENT 'ความต้องการพิเศษ',
  PRIMARY KEY (`id`),
  KEY `idx_request_id` (`request_id`),
  CONSTRAINT `fk_led_request` FOREIGN KEY (`request_id`) REFERENCES `service_requests` (`request_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
