-- =========================================================
-- Service Details Tables
-- แยกตารางเก็บรายละเอียดตามประเภทบริการ (Table-per-Type)
-- เชื่อมโยงกับ service_requests ด้วย request_id
-- =========================================================

-- 1. Email Service Details
DROP TABLE IF EXISTS `request_email_details`;
CREATE TABLE `request_email_details` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `request_id` int(11) NOT NULL COMMENT 'FK -> service_requests.request_id',
  `requested_username` varchar(100) NOT NULL COMMENT 'ชื่อ ภาษาอังกฤษ ,นามสกุลภาษาอังกฤษ',
  `email_format` varchar(100) DEFAULT NULL,
  `quota_mb` int(11) DEFAULT 2048 COMMENT 'พื้นที่ (MB)',
  `purpose` text NOT NULL COMMENT 'วัตถุประสงค์',
  `is_new_account` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1=ใหม่, 0=แก้ไขเดิม',
  `existing_email` varchar(100) DEFAULT NULL COMMENT 'อีเมลเดิม (กรณีแก้ไข)',
  PRIMARY KEY (`id`),
  KEY `idx_request_id` (`request_id`),
  CONSTRAINT `fk_email_request` FOREIGN KEY (`request_id`) REFERENCES `service_requests` (`request_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- 2. NAS (Storage) Service Details
DROP TABLE IF EXISTS `request_nas_details`;
CREATE TABLE `request_nas_details` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `request_id` int(11) NOT NULL,
  `folder_name` varchar(200) NOT NULL COMMENT 'ชื่อโฟลเดอร์',
  `storage_size_gb` int(11) NOT NULL COMMENT 'ขนาดที่ขอ (GB)',
  `permission_type` varchar(50) NOT NULL COMMENT 'สิทธิ์ (readonly, readwrite)',
  `shared_with` text NOT NULL COMMENT 'ผู้ใช้งานร่วม',
  `purpose` text NOT NULL,
  `backup_required` tinyint(1) DEFAULT 0 COMMENT 'ต้องการ Backup หรือไม่',
  PRIMARY KEY (`id`),
  KEY `idx_request_id` (`request_id`),
  CONSTRAINT `fk_nas_request` FOREIGN KEY (`request_id`) REFERENCES `service_requests` (`request_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- 3. IT Support Details
DROP TABLE IF EXISTS `request_it_support_details`;
CREATE TABLE `request_it_support_details` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `request_id` int(11) NOT NULL,
  `issue_type` varchar(50) NOT NULL COMMENT 'hardware, software, network',
  `device_type` varchar(100) NOT NULL COMMENT 'PC, Laptop, Printer',
  `device_brand` varchar(100) DEFAULT NULL,
  `symptoms` text NOT NULL COMMENT 'อาการเสีย',
  `location` varchar(200) NOT NULL COMMENT 'สถานที่ตั้งเครื่อง',
  `urgency_level` varchar(20) DEFAULT 'normal',
  `error_message` text DEFAULT NULL,
  `when_occurred` varchar(200) DEFAULT NULL COMMENT 'พบอาการเมื่อไหร่',
  PRIMARY KEY (`id`),
  KEY `idx_request_id` (`request_id`),
  CONSTRAINT `fk_it_request` FOREIGN KEY (`request_id`) REFERENCES `service_requests` (`request_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- 4. Internet/Network Details
DROP TABLE IF EXISTS `request_internet_details`;
CREATE TABLE `request_internet_details` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `request_id` int(11) NOT NULL,
  `request_type` varchar(50) NOT NULL COMMENT 'install, repair, move',
  `location` varchar(200) NOT NULL,
  `building` varchar(100) DEFAULT NULL,
  `room_number` varchar(50) DEFAULT NULL,
  `number_of_users` int(11) DEFAULT 1,
  `required_speed` varchar(50) DEFAULT NULL,
  `current_issue` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_request_id` (`request_id`),
  CONSTRAINT `fk_internet_request` FOREIGN KEY (`request_id`) REFERENCES `service_requests` (`request_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- 5. QR Code Details
DROP TABLE IF EXISTS `request_qrcode_details`;
CREATE TABLE `request_qrcode_details` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `request_id` int(11) NOT NULL,
  `qr_type` varchar(50) NOT NULL COMMENT 'url, text, wifi, vcard',
  `qr_content` text NOT NULL COMMENT 'ข้อมูลใน QR',
  `qr_size` varchar(20) DEFAULT 'medium',
  `color_primary` varchar(20) DEFAULT '#000000',
  `color_background` varchar(20) DEFAULT '#ffffff',
  `logo_url` varchar(255) DEFAULT NULL,
  `output_format` varchar(10) DEFAULT 'png',
  `quantity` int(11) DEFAULT 1,
  `purpose` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_request_id` (`request_id`),
  CONSTRAINT `fk_qrcode_request` FOREIGN KEY (`request_id`) REFERENCES `service_requests` (`request_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- 6. Photography Details
DROP TABLE IF EXISTS `request_photography_details`;
CREATE TABLE `request_photography_details` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `request_id` int(11) NOT NULL,
  `event_name` varchar(255) NOT NULL,
  `event_type` varchar(100) NOT NULL,
  `event_date` date NOT NULL,
  `event_time_start` time NOT NULL,
  `event_time_end` time DEFAULT NULL,
  `event_location` varchar(255) NOT NULL,
  `number_of_photographers` int(11) DEFAULT 1,
  `video_required` tinyint(1) DEFAULT 0,
  `drone_required` tinyint(1) DEFAULT 0,
  `delivery_format` varchar(100) DEFAULT 'digital_link',
  `special_requirements` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_request_id` (`request_id`),
  CONSTRAINT `fk_photo_request` FOREIGN KEY (`request_id`) REFERENCES `service_requests` (`request_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- 7. Web Design Details
DROP TABLE IF EXISTS `request_webdesign_details`;
CREATE TABLE `request_webdesign_details` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `request_id` int(11) NOT NULL,
  `website_type` varchar(50) NOT NULL COMMENT 'new, redesign, maintenance',
  `project_name` varchar(255) NOT NULL,
  `purpose` text NOT NULL,
  `target_audience` text DEFAULT NULL,
  `number_of_pages` int(11) DEFAULT 5,
  `features_required` text DEFAULT NULL,
  `has_existing_site` tinyint(1) DEFAULT 0,
  `existing_url` varchar(255) DEFAULT NULL,
  `domain_name` varchar(255) DEFAULT NULL,
  `hosting_required` tinyint(1) DEFAULT 1,
  `reference_sites` text DEFAULT NULL,
  `color_preferences` text DEFAULT NULL,
  `budget` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_request_id` (`request_id`),
  CONSTRAINT `fk_web_request` FOREIGN KEY (`request_id`) REFERENCES `service_requests` (`request_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- 8. Printer Service Details
DROP TABLE IF EXISTS `request_printer_details`;
CREATE TABLE `request_printer_details` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `request_id` int(11) NOT NULL,
  `issue_type` varchar(50) NOT NULL COMMENT 'repair, toner, install',
  `printer_type` varchar(100) DEFAULT NULL,
  `printer_brand` varchar(100) DEFAULT NULL,
  `printer_model` varchar(100) DEFAULT NULL,
  `serial_number` varchar(100) DEFAULT NULL,
  `location` varchar(200) NOT NULL,
  `problem_description` text DEFAULT NULL,
  `error_code` varchar(50) DEFAULT NULL,
  `toner_color` varchar(50) DEFAULT NULL,
  `supplies_needed` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_request_id` (`request_id`),
  CONSTRAINT `fk_printer_request` FOREIGN KEY (`request_id`) REFERENCES `service_requests` (`request_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- 9. MC (Master of Ceremonies) Request Details
-- คำขอพิธีกร
DROP TABLE IF EXISTS `request_mc_details`;
CREATE TABLE `request_mc_details` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `request_id` int(11) NOT NULL,
  `event_name` varchar(255) NOT NULL COMMENT 'ชื่อโครงการ/กิจกรรม',
  `event_type` varchar(100) DEFAULT NULL COMMENT 'ประเภทงาน (ทางการ, บันเทิง, ฯลฯ)',
  `event_date` date NOT NULL,
  `event_time_start` time NOT NULL,
  `event_time_end` time DEFAULT NULL,
  `location` varchar(255) NOT NULL COMMENT 'สถานที่จัดงาน',
  `mc_count` int(11) DEFAULT 1 COMMENT 'จำนวนพิธีกรที่ต้องการ',
  `language` varchar(50) DEFAULT 'TH' COMMENT 'ภาษา (TH, EN, BOTH)',
  `script_status` varchar(50) DEFAULT 'not_ready' COMMENT 'บทพูด (ready, draft, not_ready)',
  `dress_code` varchar(100) DEFAULT NULL COMMENT 'การแต่งกาย',
  `note` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_request_id` (`request_id`),
  CONSTRAINT `fk_mc_request` FOREIGN KEY (`request_id`) REFERENCES `service_requests` (`request_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
