-- =========================================================
-- iService Project Database (Consolidated Redesign)
-- Based on green_theme_db.sql with Service Request Enhancements
-- Created: 2026-02-02
-- =========================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `iservice_db`
-- (Previously green_theme_db)
--
CREATE DATABASE IF NOT EXISTS `iservice_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `iservice_db`;

-- --------------------------------------------------------
-- 1. Core System Tables (Users, Depts, Prefixes)
-- --------------------------------------------------------

-- Table: prefixes
CREATE TABLE `prefixes` (
  `prefix_id` int(11) NOT NULL AUTO_INCREMENT,
  `prefix_name` varchar(100) NOT NULL,
  `prefix_short` varchar(50) DEFAULT NULL,
  `prefix_type` enum('general','military_army','military_navy','military_air','police','academic') DEFAULT 'general',
  `is_active` tinyint(1) DEFAULT 1,
  `display_order` int(11) DEFAULT 0,
  PRIMARY KEY (`prefix_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `prefixes` (`prefix_id`, `prefix_name`, `prefix_short`, `prefix_type`, `is_active`, `display_order`) VALUES
(1, 'นาย', 'นาย', 'general', 1, 1),
(2, 'นาง', 'นาง', 'general', 1, 2),
(3, 'นางสาว', 'น.ส.', 'general', 1, 3);

-- Table: departments
CREATE TABLE `departments` (
  `department_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'รหัสหน่วยงาน/แผนก',
  `parent_department_id` int(11) DEFAULT NULL COMMENT 'รหัสหน่วยงานแม่ (เชื่อมต่อกับ department_id)',
  `department_code` varchar(20) NOT NULL COMMENT 'รหัสหน่วยงาน',
  `department_name` varchar(255) NOT NULL COMMENT 'ชื่อหน่วยงาน/แผนก',
  `short_name` varchar(5) DEFAULT NULL COMMENT 'ชื่อย่อหน่วยงาน (5 ตัวอักษร)',
  `level` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'ระดับชั้น: 1=สำนัก/กอง, 2=ส่วน, 3=ฝ่าย/กลุ่มงาน, 4=งาน',
  `level_type` varchar(50) DEFAULT NULL COMMENT 'ชนิด: สำนัก, กอง, ส่วน, ฝ่าย, กลุ่มงาน, งาน',
  `manager_user_id` int(11) DEFAULT NULL COMMENT 'หัวหน้าหน่วยงาน',
  `status` enum('active','inactive') DEFAULT 'active' COMMENT 'สถานะการใช้งาน',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`department_id`),
  KEY `idx_parent` (`parent_department_id`),
  KEY `idx_code` (`department_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sample Departments (Truncated for brevity, normally would include full list)
INSERT INTO `departments` (`department_id`, `department_code`, `department_name`, `level`, `level_type`, `status`) VALUES
(1, 'D001', 'สำนักปลัดเทศบาล', 1, 'สำนัก', 'active'),
(2, 'D002', 'สำนักคลัง', 1, 'สำนัก', 'active'),
(3, 'D003', 'สำนักช่าง', 1, 'สำนัก', 'active');

-- Table: users
CREATE TABLE `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL COMMENT 'ชื่อผู้ใช้งาน (สำหรับ Login)',
  `password` varchar(255) NOT NULL,
  `prefix_id` int(11) DEFAULT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role` enum('admin','staff','user') DEFAULT 'user',
  `department_id` int(11) DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL,
  `status` enum('active','inactive','suspended') DEFAULT 'active',
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_dept` (`department_id`),
  KEY `idx_prefix` (`prefix_id`),
  CONSTRAINT `fk_user_dept` FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_user_prefix` FOREIGN KEY (`prefix_id`) REFERENCES `prefixes` (`prefix_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default Admin User (password: admin123)
-- INSERT INTO `users` ... (Skipped for security/brevity in template)

-- Table: password_reset
CREATE TABLE `password_reset` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `token_hash` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  UNIQUE KEY `idx_token` (`token_hash`),
  CONSTRAINT `fk_pwd_reset_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------
-- 2. Service Management Tables (Main System)
-- --------------------------------------------------------

-- Table: my_service (Master Data for Service Types)
CREATE TABLE `my_service` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `service_code` varchar(50) NOT NULL COMMENT 'รหัสบริการ (ภาษาอังกฤษ เช่น EMAIL, INTERNET)',
  `service_name` varchar(100) NOT NULL COMMENT 'ชื่อบริการ (ภาษาไทย)',
  `service_name_en` varchar(100) NOT NULL COMMENT 'ชื่อบริการ (ภาษาอังกฤษ)',
  `description` text DEFAULT NULL COMMENT 'คำอธิบายบริการ',
  `icon` varchar(50) DEFAULT NULL COMMENT 'Font Awesome icon class',
  `color_code` varchar(20) DEFAULT 'blue' COMMENT 'Theme color',
  `service_url` varchar(255) DEFAULT '#' COMMENT 'Link to form',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1 = แสดง, 0 = ซ่อน',
  `display_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `service_code` (`service_code`),
  KEY `is_active` (`is_active`),
  KEY `display_order` (`display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `my_service` (`service_code`, `service_name`, `service_name_en`, `icon`, `color_code`, `service_url`, `display_order`) VALUES
('EMAIL', 'ขอใช้อีเมลองค์กร', 'Email Service', 'fas fa-envelope', 'blue', 'request-form.php?service=EMAIL', 1),
('INTERNET', 'ขอเช่าใช้บริการอินเทอร์เน็ต', 'Internet Service', 'fas fa-wifi', 'indigo', 'request-form.php?service=INTERNET', 2),
('IT_SUPPORT', 'แจ้งซ่อมคอมพิวเตอร์/อุปกรณ์', 'IT Support', 'fas fa-tools', 'red', 'request-form.php?service=IT_SUPPORT', 3),
('NAS', 'ขอใช้พื้นที่จัดเก็บข้อมูล (NAS)', 'File Storage (NAS)', 'fas fa-hdd', 'teal', 'request-form.php?service=NAS', 4),
('WEB_DESIGN', 'ขอออกแบบเว็บไซต์', 'Web Design', 'fas fa-laptop-code', 'purple', 'request-form.php?service=WEB_DESIGN', 5),
('PHOTOGRAPHY', 'ขอถ่ายภาพ/วิดีโอ', 'Photography Service', 'fas fa-camera', 'pink', 'request-form.php?service=PHOTOGRAPHY', 6),
('QR_CODE', 'ขอทำ QR Code', 'QR Code Generator', 'fas fa-qrcode', 'gray', 'request-form.php?service=QR_CODE', 7),
('PRINTER', 'แจ้งซ่อมเครื่องพิมพ์/หมึก', 'Printer Service', 'fas fa-print', 'orange', 'request-form.php?service=PRINTER', 8),
('MC', 'ขอพิธีกร', 'MC Service', 'fas fa-microphone-alt', 'green', 'request-form.php?service=MC', 9);


-- Table: service_requests (Main Transaction Table)
CREATE TABLE `service_requests` (
    `request_id` INT AUTO_INCREMENT PRIMARY KEY COMMENT 'รหัสคำขอ',
    `user_id` INT NOT NULL COMMENT 'รหัสผู้ใช้ที่ขอบริการ (FK to users)',
    `service_code` VARCHAR(50) NOT NULL COMMENT 'รหัสบริการ',
    `service_name` VARCHAR(100) NOT NULL COMMENT 'ชื่อบริการ',

    -- Requester Information Snapshots
    `requester_prefix_id` INT NULL COMMENT 'คำนำหน้าผู้ขอ',
    `requester_name` VARCHAR(200) NOT NULL COMMENT 'ชื่อผู้ขอบริการ',
    `requester_position` VARCHAR(100) NULL COMMENT 'ตำแหน่งผู้ขอ',
    `requester_phone` VARCHAR(20) NULL COMMENT 'เบอร์โทรผู้ขอ',
    `requester_email` VARCHAR(255) NULL COMMENT 'อีเมลผู้ขอ',

    -- Department Info
    `department_id` INT NULL COMMENT 'รหัสหน่วยงาน',
    `department_name` VARCHAR(200) NULL COMMENT 'ชื่อหน่วยงาน',

    -- Common Details
    `subject` VARCHAR(255) NOT NULL COMMENT 'หัวข้อคำขอ',
    `description` TEXT NOT NULL COMMENT 'รายละเอียดคำขอ',
    `request_data` JSON NULL COMMENT 'Legacy/Fallback JSON data',

    -- Status
    `status` ENUM('pending', 'in_progress', 'completed', 'cancelled', 'rejected') DEFAULT 'pending',
    `priority` ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',

    -- Operations
    `assigned_to` INT NULL COMMENT 'มอบหมายให้ (FK to users)',
    `assigned_at` DATETIME NULL,
    `admin_notes` TEXT NULL,
    `rejection_reason` TEXT NULL,
    `completion_notes` TEXT NULL,
    
    -- Dates
    `expected_completion_date` DATE NULL,
    `started_at` DATETIME NULL,
    `completed_at` DATETIME NULL,
    `cancelled_at` DATETIME NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Constraints
    CONSTRAINT `fk_req_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
    CONSTRAINT `fk_req_dept` FOREIGN KEY (`department_id`) REFERENCES `departments`(`department_id`) ON DELETE SET NULL,
    CONSTRAINT `fk_req_assignee` FOREIGN KEY (`assigned_to`) REFERENCES `users`(`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------
-- 3. Service Detail Tables (One-to-One Extensions)
-- --------------------------------------------------------

-- 1. Email Service Details
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

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
