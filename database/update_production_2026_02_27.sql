-- ============================================================
--  Production Update Script — 2026-02-27
--  iService | เทศบาลนครรังสิต
--  Import via phpMyAdmin → เลือก database ก่อน แล้ว Import ไฟล์นี้
-- ============================================================
-- ใช้ IF NOT EXISTS ทุก CREATE TABLE → import ซ้ำได้ปลอดภัย

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- 1. request_led_details
--    คำขอนำสื่อขึ้นจอ LED
-- ============================================================
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

-- ============================================================
-- 2. service_notification_emails
--    อีเมลแจ้งเตือนเมื่อมีคำขอบริการ
-- ============================================================
CREATE TABLE IF NOT EXISTS `service_notification_emails` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `service_id` int(11) NOT NULL COMMENT 'FK -> my_service.id',
  `email` varchar(255) NOT NULL COMMENT 'อีเมลที่ต้องแจ้งเตือน',
  `name` varchar(100) DEFAULT NULL COMMENT 'ชื่อผู้รับ',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1=เปิดใช้, 0=ปิด',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_service_email` (`service_id`, `email`),
  KEY `service_id` (`service_id`),
  CONSTRAINT `fk_sne_service` FOREIGN KEY (`service_id`) REFERENCES `my_service` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 3. speaker_locations
--    จุดติดตั้งระบบกระจายเสียงชนิดไร้สาย
-- ============================================================
CREATE TABLE IF NOT EXISTS `speaker_locations` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `point_number` INT(11) NOT NULL COMMENT 'หมายเลขจุดติดตั้ง',
  `latitude` DECIMAL(10,6) NOT NULL,
  `longitude` DECIMAL(10,6) NOT NULL,
  `description` VARCHAR(255) DEFAULT NULL COMMENT 'รายละเอียด',
  `zone_group` VARCHAR(100) DEFAULT NULL COMMENT 'กลุ่มพื้นที่',
  `device_count` INT(11) DEFAULT NULL COMMENT 'จำนวนอุปกรณ์',
  `status` ENUM('active','inactive','maintenance') DEFAULT 'active',
  `installed_date` DATE DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_point_number` (`point_number`),
  KEY `idx_zone_group` (`zone_group`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='จุดติดตั้งระบบกระจายเสียงชนิดไร้สาย เทศบาลเมืองรังสิต';

INSERT IGNORE INTO `speaker_locations` (`point_number`, `latitude`, `longitude`, `description`, `zone_group`, `device_count`) VALUES
(1, 13.986146, 100.608983, 'ซอยรังสิต-ปทุมธานี17 มีทั้งหมด5ตัว', 'ซอยรังสิต-ปทุมธานี17', 5),
(2, 13.985542, 100.609037, 'ซอยรังสิต-ปทุมธานี17 มีทั้งหมด5ตัว', 'ซอยรังสิต-ปทุมธานี17', 5),
(3, 13.984684, 100.609135, 'ซอยรังสิต-ปทุมธานี17 มีทั้งหมด5ตัว', 'ซอยรังสิต-ปทุมธานี17', 5),
(4, 13.984301, 100.609115, 'ซอยรังสิต-ปทุมธานี17 มีทั้งหมด5ตัว', 'ซอยรังสิต-ปทุมธานี17', 5),
(5, 13.983539, 100.609219, 'ซอยรังสิต-ปทุมธานี17 มีทั้งหมด5ตัว', 'ซอยรังสิต-ปทุมธานี17', 5),
(6, 13.973244, 100.630384, 'ชุมชนซอย83 11ตัว', 'ชุมชนซอย83', 11),
(7, 13.975583, 100.630200, 'ชุมชนซอย83 11ตัว', 'ชุมชนซอย83', 11),
(8, 13.977291, 100.630067, 'ชุมชนซอย83 11ตัว', 'ชุมชนซอย83', 11),
(9, 13.978779, 100.629961, 'ชุมชนซอย83 11ตัว', 'ชุมชนซอย83', 11),
(10, 13.981234, 100.629677, 'ชุมชนซอย83 11ตัว', 'ชุมชนซอย83', 11),
(11, 13.983062, 100.629544, 'ชุมชนซอย83 11ตัว', 'ชุมชนซอย83', 11),
(12, 13.984465, 100.629409, 'ชุมชนซอย83 11ตัว', 'ชุมชนซอย83', 11),
(13, 13.978666, 100.629492, 'ชุมชนซอย83 11ตัว', 'ชุมชนซอย83', 11),
(14, 13.977364, 100.629619, 'ชุมชนซอย83 11ตัว', 'ชุมชนซอย83', 11),
(15, 13.974967, 100.629812, 'ชุมชนซอย83 11ตัว', 'ชุมชนซอย83', 11),
(16, 13.973008, 100.629958, 'ชุมชนซอย83 11ตัว', 'ชุมชนซอย83', 11),
(17, 13.976846, 100.623361, 'จุดติดตั้ง 17', NULL, NULL),
(18, 13.978176, 100.623137, 'จุดติดตั้ง 18', NULL, NULL),
(19, 13.985044, 100.608272, 'จุดติดตั้ง 19', NULL, NULL),
(20, 13.984105, 100.608119, 'จุดติดตั้ง 20', NULL, NULL),
(21, 13.983500, 100.608625, 'จุดติดตั้ง 21', NULL, NULL),
(22, 13.984417, 100.608689, '**ทั้งหมด7ตัว ซอยสร้างบุญรวมจุดที่22-23', 'ซอยสร้างบุญ', 7),
(23, 13.986043, 100.608502, '**ทั้งหมด7ตัว ซอยสร้างบุญรวมจุดที่22-23', 'ซอยสร้างบุญ', 7),
(24, 13.982228, 100.606856, 'รอบ 2เนฟ', 'รอบ2', NULL),
(25, 13.994206, 100.611625, 'จุดติดตั้ง 25', NULL, NULL),
(26, 13.992734, 100.611501, 'จุดติดตั้ง 26', NULL, NULL),
(27, 13.995550, 100.611695, 'จุดติดตั้ง 27', NULL, NULL),
(28, 13.973092, 100.604954, 'ชุมชนเดชาพัฒนา87', 'ชุมชนเดชาพัฒนา87', NULL),
(29, 13.971030, 100.604905, 'จุดติดตั้ง 29', 'ชุมชนเดชาพัฒนา87', NULL),
(30, 13.969464, 100.605087, 'จุดติดตั้ง 30', 'ชุมชนเดชาพัฒนา87', NULL),
(31, 13.969722, 100.606633, 'จุดติดตั้ง 31', 'ชุมชนเดชาพัฒนา87', NULL),
(32, 13.993287, 100.610436, 'รอบ 3 จุดที่ 1 รป8', 'รป8', NULL),
(33, 13.993715, 100.610593, 'จุดที่ 2 รป8', 'รป8', NULL),
(34, 13.992888, 100.610420, 'จุดที่ 3 รป8', 'รป8', NULL),
(35, 13.992409, 100.610422, 'จุดที่ 4 รป8', 'รป8', NULL),
(36, 13.991973, 100.610165, 'จุดที่5 รป8', 'รป8', NULL),
(37, 13.977681, 100.653918, 'จุดที่1ซอย14 รป54', 'รป54', NULL),
(38, 13.978345, 100.653892, 'จุดที่2 ซอย12 รป54', 'รป54', NULL),
(39, 13.979070, 100.653965, 'จุดที่3ซอย10 รป54', 'รป54', NULL),
(40, 13.979887, 100.653992, 'จุดที่4ซอย8 รป54', 'รป54', NULL),
(41, 13.980652, 100.654033, 'จุดที่5ซอย6 รป54', 'รป54', NULL),
(42, 13.981469, 100.654078, 'จุดที่6 ซอย4 รป54', 'รป54', NULL),
(43, 13.982269, 100.654120, 'จุดที่7ซอย2 รป54', 'รป54', NULL),
(44, 13.982630, 100.654478, 'จุดที่8 รป54', 'รป54', NULL),
(45, 13.981760, 100.654746, 'จุดที่9 รป54', 'รป54', NULL),
(46, 13.981012, 100.654700, 'จุดที่10 รป54', 'รป54', NULL),
(47, 13.980214, 100.654670, 'จุดที่11 รป54', 'รป54', NULL),
(48, 13.979412, 100.654642, 'จุดที่12 รป54', 'รป54', NULL),
(49, 13.978647, 100.654575, 'จุดที่13 รป54', 'รป54', NULL),
(50, 13.977979, 100.654560, 'จุดที่14 รป54', 'รป54', NULL),
(51, 13.991008, 100.655247, 'จุดที่1 ซอย4วรุณพร', 'วรุณพร', NULL),
(52, 13.990750, 100.654965, 'จุดที่2 ซอย5 วรุณพร', 'วรุณพร', NULL),
(53, 13.990474, 100.654834, 'จุดที่3 ซอย6 วรุณพร', 'วรุณพร', NULL),
(54, 13.990175, 100.654747, 'จุดที่4 ซอย7 วรุณพร', 'วรุณพร', NULL),
(55, 13.991314, 100.654892, 'จุดที่5 ซอย3 วรุณพร', 'วรุณพร', NULL),
(56, 13.991761, 100.654363, 'จุด6 วรุณพร', 'วรุณพร', NULL),
(57, 13.987498, 100.605667, 'รอบที่ 4 จุดที่1 ซอยที่1 รป14', 'รป14', NULL),
(58, 13.987952, 100.605528, 'จุดที่2 ซอยที่2 รป14', 'รป14', NULL),
(59, 13.988378, 100.605335, 'จุดที่3 ซอยที่3 รป14', 'รป14', NULL),
(60, 13.988794, 100.605186, 'จุดที่4 ซอยที่4 รป14', 'รป14', NULL),
(61, 13.989226, 100.605001, 'จุดที่5 ซอยที่5 รป14', 'รป14', NULL),
(62, 13.989686, 100.604889, 'จุดที่6 ซอยที่6 รป14', 'รป14', NULL),
(63, 13.990127, 100.604926, 'จุดที่7 ซอยที่7 รป14', 'รป14', NULL),
(64, 13.990551, 100.604663, 'จุดที่8 ซอยที่8 รป14', 'รป14', NULL),
(65, 13.990997, 100.604636, 'จุดที่9 ซอย9 รป14', 'รป14', NULL),
(66, 13.991460, 100.604607, 'จุดที่10 ซอย10 รป14', 'รป14', NULL),
(67, 13.992930, 100.607890, 'จุดที่1ซอย12 รป12', 'รป12', NULL),
(68, 13.992472, 100.608098, 'จุดที่2 ซอย11', 'รป12', NULL),
(69, 13.991813, 100.608082, 'จุดที่3', 'รป12', NULL),
(70, 13.991147, 100.608164, 'จุดที่4 ซอยที่8 รป12', 'รป12', NULL),
(71, 13.990736, 100.608738, 'จุดติดตั้ง 71', 'รป12', NULL),
(72, 13.989743, 100.608403, 'จุดที่6', 'รป12', NULL),
(73, 13.989380, 100.608878, 'จุดที่7 ซอยที่4 รป10', 'รป10', NULL),
(74, 13.988410, 100.608613, 'จุดที่8', 'รป10', NULL),
(75, 13.988016, 100.609096, 'จุดที่9 ซอย1 รป10', 'รป10', NULL),
(76, 14.005443, 100.647788, 'รอบที่ 5 ชุมชนรณชัย รน41', 'ชุมชนรณชัย รน41', NULL),
(77, 14.004105, 100.647816, 'ชุมชนรณชัย จุดที่2 รน41', 'ชุมชนรณชัย รน41', NULL),
(78, 14.002356, 100.647829, 'ชุมชนรณชัย รน41 จุดที่3', 'ชุมชนรณชัย รน41', NULL),
(79, 14.000886, 100.647821, 'ชุมชนรณชัย รน41 จุดที่4', 'ชุมชนรณชัย รน41', NULL),
(80, 13.999587, 100.647826, 'ชุมชนรณชัย รน41 จุดที่5', 'ชุมชนรณชัย รน41', NULL),
(81, 13.998014, 100.647828, 'ชุมชนรณชัย รน41 จุดที่6', 'ชุมชนรณชัย รน41', NULL),
(82, 13.996594, 100.647828, 'ชุมชนรณชัย รน41 จุดที่7', 'ชุมชนรณชัย รน41', NULL),
(83, 13.994765, 100.647814, 'ชุมชนรณชัย รน41 จุดที่8', 'ชุมชนรณชัย รน41', NULL),
(84, 13.993179, 100.647858, 'ชุมชนรณชัย รน41 จุดที่9', 'ชุมชนรณชัย รน41', NULL),
(85, 13.992310, 100.647858, 'ชุมชนรณชัย รน41 จุดที่10', 'ชุมชนรณชัย รน41', NULL),
(86, 13.996136, 100.604141, 'ซอย20 เมน2 จุดที่1', 'ซอย20 เมน2', NULL),
(87, 13.995419, 100.603569, 'ซอย20 เมน2 จุดที่2', 'ซอย20 เมน2', NULL),
(88, 13.995743, 100.605607, 'ซอย20 เมน2 จุดที่3', 'ซอย20 เมน2', NULL),
(89, 13.996007, 100.607399, 'ซอย20 เมน2 จุดที่4', 'ซอย20 เมน2', NULL),
(90, 13.996089, 100.606357, 'ซอย20 เมน2 จุดที่5', 'ซอย20 เมน2', NULL),
(91, 13.996319, 100.605834, 'ซอย20 เมน2 จุดที่6', 'ซอย20 เมน2', NULL),
(92, 13.994528, 100.606578, 'ซอย20 เมน2 จุดที่7', 'ซอย20 เมน2', NULL),
(93, 13.994549, 100.603876, 'ซอย20 เมน2 จุดที่8', 'ซอย20 เมน2', NULL),
(94, 13.995010, 100.603987, 'ซอย20 เมน2 จุดที่9', 'ซอย20 เมน2', NULL),
(95, 13.994455, 100.606152, 'ซอย20 เมน2 จุดที่10', 'ซอย20 เมน2', NULL),
(96, 13.994040, 100.606483, 'ซอย20 เมน2 จุดที่11', 'ซอย20 เมน2', NULL),
(97, 13.993607, 100.606501, 'ซอย20 เมน2 จุดที่12', 'ซอย20 เมน2', NULL),
(98, 13.993143, 100.606491, 'ซอย20 เมน2 จุดที่13', 'ซอย20 เมน2', NULL),
(99, 13.992773, 100.607172, 'ซอย20 เมน2 จุดที่14', 'ซอย20 เมน2', NULL),
(100, 13.992274, 100.606809, 'ซอย20 เมน2 จุดที่15', 'ซอย20 เมน2', NULL),
(101, 13.991785, 100.606707, 'ซอย20 เมน2 จุดที่16', 'ซอย20 เมน2', NULL),
(102, 13.991361, 100.606917, 'ซอย20 เมน2 จุดที่17', 'ซอย20 เมน2', NULL),
(103, 13.991010, 100.607548, 'ซอย20 เมน2 จุดที่18', 'ซอย20 เมน2', NULL),
(104, 13.990417, 100.606698, 'ซอย20 เมน2 จุดที่19', 'ซอย20 เมน2', NULL),
(105, 13.989933, 100.606712, 'เมน2 จุดที่20', 'ซอย20 เมน2', NULL),
(106, 13.989591, 100.607390, 'ซอย20 เมน2 จุดที่21', 'ซอย20 เมน2', NULL),
(107, 13.989066, 100.606987, 'ซอย20 เมน2 จุดที่22', 'ซอย20 เมน2', NULL),
(108, 13.988744, 100.607721, 'ซอย20 เมน2 จุดที่23', 'ซอย20 เมน2', NULL),
(109, 13.988207, 100.607384, 'ซอย20 เมน2 จุดที่24', 'ซอย20 เมน2', NULL),
(110, 13.987850, 100.608081, 'ซอย20 เมน2 จุดที่25', 'ซอย20 เมน2', NULL);

-- ============================================================
-- 4. water_kiosks
--    จุดติดตั้งตู้น้ำดื่ม
-- ============================================================
CREATE TABLE IF NOT EXISTS `water_kiosks` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `kiosk_code` VARCHAR(20) NOT NULL COMMENT 'รหัสตู้น้ำ',
  `location_name` VARCHAR(255) NOT NULL COMMENT 'สถานที่ติดตั้ง',
  `kiosk_count` INT(11) DEFAULT 1 COMMENT 'จำนวนตู้',
  `latitude` DECIMAL(15,12) NOT NULL,
  `longitude` DECIMAL(15,12) NOT NULL,
  `status` ENUM('active','inactive','maintenance') DEFAULT 'active',
  `qr_code_link` VARCHAR(500) DEFAULT NULL COMMENT 'ลิงก์ QR Code',
  `installed_date` DATE DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_kiosk_code` (`kiosk_code`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='จุดติดตั้งตู้น้ำดื่ม เทศบาลเมืองรังสิต';

INSERT IGNORE INTO `water_kiosks` (`kiosk_code`, `location_name`, `kiosk_count`, `latitude`, `longitude`, `status`, `qr_code_link`) VALUES
('RSC0001', 'โถงชั้น 1 หน้าห้อง RSSC สำนักงานเทศบาลรังสิต', 1, 13.987992927445, 100.60951463888, 'active', 'https://drive.google.com/file/d/1xsGH5l7z8d0kJnSF1g7qvfMR55Iaczz7/view?usp=sharing'),
('RSC0002', 'โรงเรียนมัธยมนครรังสิต', 1, 13.980344741823, 100.65586465155, 'active', 'https://drive.google.com/file/d/1TKd7kV9gGRc_QwKWCMPhhmGsgWVNnAEe/view?usp=drive_link'),
('RSC0003', 'โรงเรียนดวงกมล', 1, 13.984674615799, 100.63959619971, 'active', 'https://drive.google.com/file/d/1naNd1GxC3Y_14TD9OIvFddkm6F-5RlpH/view?usp=drive_link'),
('RSC0004', 'ตลาดรังสิต', 1, 13.985252418403, 100.61410188675, 'active', 'https://drive.google.com/file/d/1uREXE2G62H16YwB6RVul-hufFos_NY_D/view?usp=drive_link'),
('RSC0005', 'บ้านเอื้ออาทรรังสิต คลอง 1', 1, 13.985793779964, 100.62856435776, 'active', 'https://drive.google.com/file/d/1siYXCJTbnB0jVtG1PzVCshw92ffiBahQ/view?usp=drive_link'),
('RSC0006', 'ศูนย์สาธารณสุขชุมชน 1', 1, 13.97371695013, 100.61096906662, 'active', 'https://drive.google.com/file/d/18cUHw6wcAWyCIoYk3mZMyxg08CCNlGWh/view?usp=drive_link'),
('RSC0007', 'ศูนย์สาธารณสุขชุมชน 2', 1, 13.992040167351, 100.60929536819, 'active', 'https://drive.google.com/file/d/19NU3s6yl9mbQ10E84iYTCECnX27RC9e5/view?usp=drive_link'),
('RSC0008', 'ศูนย์สาธารณสุขชุมชน 3', 1, 13.975257822306, 100.62556028366, 'active', 'https://drive.google.com/file/d/1qeIGVBEPK8vsGgS3BJ-3OiC3qbJwyR6J/view?usp=drive_link'),
('RSC0009', 'ศูนย์สาธารณสุขชุมชน 4', 1, 13.992685617709, 100.62419772148, 'active', 'https://drive.google.com/file/d/1nxHY6FGN4O4yFShegpMX4ZIp72frEHJu/view?usp=drive_link'),
('RSC0010', 'สถานีตำรวจภูธรประตูน้ำจุฬาลงกรณ์', 1, 13.98479434224, 100.61255693436, 'active', 'https://drive.google.com/file/d/1jwDuNc3hLPZndtlOFVasFGFhBgHDe7Fl/view?usp=drive_link'),
('RSC0011', 'อาคารอเนกประสงค์ ชุมชนรันตโกสินทร์ 200 ปี', 1, 13.992165093368, 100.60458004475, 'active', 'https://drive.google.com/file/d/15JCyziqX_0HjRUgZVMhhO2-Nxk_Xx7tV/view?usp=sharing'),
('RSC0012', 'จุดศาลาใหญ่ตรงข้ามโรงเรียนชุมชนประชาธิปัตย์วิทยาคาร', 1, 13.98295695429, 100.61174137527, 'active', 'https://drive.google.com/file/d/1MM7hb78Pw0AVrg5mM3lN2Cr4YdX7_ptT/view?usp=sharing'),
('RSC0013', 'วัดคลอง 1 แก้วนิมิต', 1, 13.971009987555, 100.62478780746, 'active', 'https://drive.google.com/file/d/1px3TyP2yqNt8vN2d_II7hSPw5_UWyfQB/view?usp=drive_link'),
('RSC0014', 'ศูนย์นันทนาการ 200 ปี', 1, 13.99232645604, 100.60933560133, 'active', 'https://drive.google.com/file/d/1KhTVrK-MLgNxw0bRIGpJWi2joKWrALbn/view?usp=drive_link'),
('RSC0015', 'สระว่ายน้ำเทศบาลนครรังสิต', 1, 13.990911296582, 100.60457199821, 'active', 'https://drive.google.com/file/d/1xDS0eZQDCvKKdACle758q0RKgu6N1bzr/view?usp=drive_link'),
('RSC0016', 'จุดติดตั้งของชุมชนสร้างสรรค์นครรังสิต', 1, 13.979317909119, 100.63099351721, 'active', 'https://drive.google.com/file/d/1SjLWB6rTXhxMPJ0BOAGcSYl5cHwnY2fT/view?usp=drive_link'),
('RSC0017', 'อาคารอเนกประสงค์ชุมชนซอยดี', 1, 14.000451700906, 100.6446146965, 'active', 'https://drive.google.com/file/d/1nmqnRISP94o7aEv3QtCVCshw92ffiBahQ/view?usp=drive_link'),
('RSC0018', 'อาคารอเนกประสงค์ชุมชนศรีประจักษ์', 1, 14.00224223514, 100.65045118332, 'active', 'https://drive.google.com/file/d/1xT3hJTh0qU9Yrmvknn7mBNB7Y7dGOyj8/view?usp=drive_link'),
('RSC0019', 'อาคารอเนกประสงค์ชุมชนอยู่เจริญ', 1, 13.999035919774, 100.6524682045, 'active', 'https://drive.google.com/file/d/1ltGNFO2wd5Tv6s9Had9lQSMJ56wGFXGT/view?usp=drive_link'),
('RSC0020', 'อาคารอเนกประสงค์ชุมชนสินสมุทร', 1, 13.973300496421, 100.60766458511, 'active', 'https://drive.google.com/file/d/1JhKrz-Rz2WXWlPqFMQFvB1cGEGwKu74H/view?usp=drive_link'),
('RSC0021', 'อาคารอเนกประสงค์ชุมชนภักดีราชา', 1, 14.005884979864, 100.65008573243, 'active', 'https://drive.google.com/file/d/1_ioqXj0AWS8NuHhmjedSe6l0WnJcHIPR/view?usp=drive_link'),
('RSC0022', 'อาคารอเนกประสงค์ชุมชนซอยเกลียวทอง', 1, 14.004324234145, 100.64736127853, 'active', 'https://drive.google.com/file/d/1jMJMMXkvjotgR29QUuZmx2GjlAMyfObR/view?usp=drive_link'),
('RSC0023', 'อาคารอเนกประสงค์ชุมชนหมู่บ้านวรุณพร', 1, 13.991166399805, 100.65485537062, 'active', 'https://drive.google.com/file/d/1aIT3NO8QRkxxXyQ_GLpo36rpp-HbhWDN/view?usp=drive_link'),
('RSC0024', 'อาคารอเนกประสงค์ชุมชนหมู่บ้านพงษ์ศิริ', 1, 13.99542314933, 100.65809564674, 'active', 'https://drive.google.com/file/d/1f5pVpuEzG0qHmCenO8qY-YuwuCMvk84m/view?usp=drive_link'),
('RSC0025', 'อาคารอเนกประสงค์ชุมชนกรุงเทพเมืองใหม่', 1, 13.976257301446, 100.61611890793, 'active', 'https://drive.google.com/file/d/13xuJXGwgy_LbVk0ARiKmwkdkHDR_cDf/view?usp=drive_link'),
('RSC0026', 'ชุมชนหมู่บ้านฟ้าลากูน', 1, 13.980505039378, 100.6405377388, 'active', 'https://drive.google.com/file/d/1oUVnG4GF8TdDG8xlGJXuadWdzgy_cofY/view?usp=drive_link'),
('RSC0027', 'อาคารอเนกประสงค์ชุมชนหมู่บ้านเปรมปรีด์คันทรีโฮม', 1, 13.982868650941, 100.6544813701, 'active', 'https://drive.google.com/file/d/1JYGouSa2ZjltAFqduP4soewug-ULk086/view?usp=drive_link'),
('RSC0028', 'อาคารอเนกประสงค์ชุมชนรัตนปทุม', 1, 13.995437992574, 100.64054237518, 'active', 'https://drive.google.com/file/d/1vo42zusY_Uep27OXawoDYnmPVnjOIEnu/view?usp=drive_link'),
('RSC0029', 'บ้านเอื้ออาทรรังสิต คลอง 1 ( ศูนย์การศึกษาพิเศษ ประจำจังหวัดปทุมธานี )', 1, 13.979156568949, 100.62897292674, 'active', 'https://drive.google.com/file/d/1208FEZ9kGg194IwnIV4e-IAcVOljGE7r/view?usp=drive_link'),
('RSC0030', 'บ้านเอื้ออาทรรังสิต คลอง 1 ( จุดติดตั้งอาคาร 40)', 1, 13.975524050197, 100.6294652448, 'active', 'https://drive.google.com/file/d/1PngoyfxjahdpZDZVg_CGNlzJl6gDQfwBxzy/view?usp=drive_link');

-- ============================================================
-- 5. nav_menu
--    เมนูนำทางหน้าเว็บไซต์
-- ============================================================
CREATE TABLE IF NOT EXISTS `nav_menu` (
  `id` int(11) NOT NULL DEFAULT 0,
  `parent_id` int(11) DEFAULT NULL COMMENT 'NULL = parent menu, มีค่า = submenu',
  `menu_name` varchar(100) NOT NULL COMMENT 'ชื่อเมนู (ภาษาไทย)',
  `menu_name_en` varchar(100) DEFAULT NULL COMMENT 'ชื่อเมนู (ภาษาอังกฤษ)',
  `menu_url` varchar(255) DEFAULT '#' COMMENT 'URL/Link ของเมนู',
  `menu_icon` varchar(50) DEFAULT NULL COMMENT 'Font Awesome icon class',
  `menu_order` int(11) NOT NULL DEFAULT 0 COMMENT 'ลำดับการแสดงผล',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1 = แสดง, 0 = ซ่อน',
  `target` varchar(20) DEFAULT '_self',
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ล้างข้อมูลเก่าและ insert ใหม่ทั้งหมด (safe เพราะ managed via admin UI)
TRUNCATE TABLE `nav_menu`;

INSERT INTO `nav_menu` (`id`, `parent_id`, `menu_name`, `menu_name_en`, `menu_url`, `menu_icon`, `menu_order`, `is_active`, `target`, `description`, `created_at`, `updated_at`) VALUES
(1, NULL, 'หน้าแรก', 'Home', 'index.php', NULL, 1, 1, '_self', NULL, '2025-12-29 06:35:10', '2025-12-29 06:35:10'),
(2, NULL, 'บริการออนไลน์', 'Online Services', '#', 'fas fa-globe', 2, 1, '_self', NULL, '2025-12-29 06:35:11', '2025-12-29 06:35:11'),
(3, 2, 'แจ้งซ่อมคอมพิวเตอร์', 'IT Support', 'http://iservice.rangsitcity.go.th/request-form.php?service=IT_SUPPORT', 'fas fa-tools', 2, 1, '_self', '', '2025-12-29 06:35:11', '2026-02-07 04:52:01'),
(4, 2, 'ขอใช้อินเทอร์เน็ต', 'Internet Request', 'http://iservice.rangsitcity.go.th/request-form.php?service=INTERNET', 'fas fa-wifi', 1, 1, '_self', '', '2025-12-29 06:35:11', '2026-02-07 04:52:01'),
(5, 2, 'ขอพื้นที่เก็บข้อมูล', 'Storage Request', 'http://iservice.rangsitcity.go.th/request-form.php?service=NAS', 'fas fa-hdd', 3, 1, '_self', '', '2025-12-29 06:35:11', '2026-02-07 04:52:07'),
(6, NULL, 'คู่มือการใช้งาน', 'User Manual', '#', 'fas fa-book', 3, 1, '_self', NULL, '2025-12-29 06:35:11', '2025-12-29 06:35:11'),
(7, 2, 'Email เทศบาล', 'Email', 'http://iservice.rangsitcity.go.th/request-form.php?service=EMAIL', 'fas fa-envelope', 4, 1, '_self', '', '2025-12-29 06:35:11', '2026-02-07 04:59:33'),
(8, 6, 'NAS', 'NAS', 'http://iservice.rangsitcity.go.th/resource-detail.php?id=3', 'fas fa-server', 1, 1, '0', '', '2025-12-29 06:35:11', '2026-02-06 13:59:41'),
(9, 6, 'Internet', 'Internet', 'http://iservice.rangsitcity.go.th/resource-detail.php?id=2', 'fas fa-globe', 2, 1, '0', '', '2025-12-29 06:35:11', '2026-02-06 13:59:55'),
(10, NULL, 'ติดต่อเรา', 'Contact Us', 'contact.php', NULL, 4, 1, '_self', NULL, '2025-12-29 06:35:11', '2025-12-29 06:35:11'),
(11, 10, 'แผนที่ที่ตั้ง', 'map', 'my-location.php', 'fas fa-map', 1, 1, '0', '', '2025-12-29 06:37:20', '2025-12-29 06:37:20');

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- END OF UPDATE SCRIPT
-- ============================================================
