-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 05, 2026 at 09:53 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `green_theme_db`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_get_user_login` (IN `login_identifier` VARCHAR(255))   BEGIN
    SELECT
        u.user_id,
        u.username,
        p.prefix_name,
        u.first_name,
        u.last_name,
        CONCAT(IFNULL(p.prefix_name, ''), ' ', u.first_name, ' ', u.last_name) AS full_name,
        u.email,
        u.password,
        u.role,
        u.status,
        u.department_id,
        d.department_name
    FROM users u
    LEFT JOIN prefixes p ON u.prefix_id = p.prefix_id
    LEFT JOIN departments d ON u.department_id = d.department_id
    WHERE u.username = login_identifier OR u.email = login_identifier
    LIMIT 1;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_update_last_login` (IN `p_user_id` INT)   BEGIN
    UPDATE users
    SET last_login = NOW()
    WHERE user_id = p_user_id;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `department_id` int(11) NOT NULL COMMENT 'รหัสหน่วยงาน/แผนก',
  `parent_department_id` int(11) DEFAULT NULL COMMENT 'รหัสหน่วยงานแม่ (เชื่อมต่อกับ department_id)',
  `department_code` varchar(20) NOT NULL COMMENT 'รหัสหน่วยงาน',
  `department_name` varchar(255) NOT NULL COMMENT 'ชื่อหน่วยงาน/แผนก',
  `short_name` varchar(5) DEFAULT NULL COMMENT 'ชื่อย่อหน่วยงาน (5 ตัวอักษร)',
  `level` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'ระดับชั้น: 1=สำนัก/กอง, 2=ส่วน, 3=ฝ่าย/กลุ่มงาน, 4=งาน',
  `level_type` varchar(50) DEFAULT NULL COMMENT 'ชนิด: สำนัก, กอง, ส่วน, ฝ่าย, กลุ่มงาน, งาน',
  `manager_user_id` int(11) DEFAULT NULL COMMENT 'หัวหน้าหน่วยงาน',
  `building` varchar(100) DEFAULT NULL COMMENT 'อาคาร',
  `floor` varchar(50) DEFAULT NULL COMMENT 'ชั้น',
  `phone` varchar(20) DEFAULT NULL COMMENT 'เบอร์โทรศัพท์',
  `email` varchar(100) DEFAULT NULL COMMENT 'อีเมล',
  `budget_code` varchar(50) DEFAULT NULL COMMENT 'รหัสงบประมาณ',
  `status` enum('active','inactive') DEFAULT 'active' COMMENT 'สถานะการใช้งาน',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ตารางโครงสร้างหน่วยงาน';

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`department_id`, `parent_department_id`, `department_code`, `department_name`, `short_name`, `level`, `level_type`, `manager_user_id`, `building`, `floor`, `phone`, `email`, `budget_code`, `status`, `created_at`, `updated_at`) VALUES
(1, NULL, 'D001', 'สำนักปลัดเทศบาล', 'สป', 1, 'สำนัก', NULL, '', '', '', '', '', 'active', '2025-10-18 03:54:25', '2025-11-20 04:47:28'),
(2, NULL, 'D002', 'สำนักคลัง', 'สค', 1, 'สำนัก', NULL, '', '', '', '', '', 'active', '2025-10-18 03:54:25', '2025-11-20 04:47:35'),
(3, NULL, 'D003', 'สำนักช่าง', 'สช', 1, 'สำนัก', NULL, '', '', '', '', '', 'active', '2025-10-18 03:54:25', '2025-11-20 04:47:42'),
(4, NULL, 'D004', 'กองสาธารณสุขและสิ่งแวดล้อม', 'กสส', 1, 'กอง', NULL, '', '', '', '', '', 'active', '2025-10-18 03:54:25', '2025-11-20 04:47:54'),
(5, NULL, 'D005', 'กองการศึกษา', 'กศ', 1, 'กอง', NULL, '', '', '', '', '', 'active', '2025-10-18 03:54:25', '2025-11-20 04:48:04'),
(6, NULL, 'D006', 'กองสวัสดิการสังคม', 'กสด', 1, 'กอง', NULL, '', '', '', '', '', 'active', '2025-10-18 03:54:25', '2025-11-20 04:48:14'),
(7, NULL, 'D007', 'กองการเจ้าหน้าที่', 'กจ', 1, 'กอง', NULL, '', '', '', '', '', 'active', '2025-10-18 03:54:25', '2025-11-20 04:48:22'),
(8, NULL, 'D008', 'กองยุทธศาสตร์และงบประมาณ', 'กย', 1, 'กอง', NULL, '', '', '', '', '', 'active', '2025-10-18 03:54:25', '2025-11-20 04:48:31'),
(9, NULL, 'D009', 'ตรวจสอบภายใน', NULL, 1, 'พิเศษ', 0, '', '', '', '', '', 'active', '2025-10-18 03:54:25', '2025-11-23 16:12:40'),
(10, 1, 'D010', 'ส่วนอำนวยการ', NULL, 2, 'ส่วน', NULL, NULL, NULL, NULL, NULL, NULL, 'active', '2025-10-18 03:54:25', '2025-10-18 03:54:25'),
(11, 1, 'D011', 'ส่วนปกครอง', NULL, 2, 'ส่วน', NULL, NULL, NULL, NULL, NULL, NULL, 'active', '2025-10-18 03:54:25', '2025-10-18 03:54:25'),
(12, 1, 'D012', 'กลุ่มงานบริหารงานทั่วไป(สป)', NULL, 2, 'ฝ่าย', NULL, '', '', '', '', '', 'active', '2025-10-18 03:54:25', '2025-11-23 16:09:22'),
(13, 11, 'D013', 'ฝ่ายอำนวยการ', NULL, 3, 'ฝ่าย', NULL, NULL, NULL, NULL, NULL, NULL, 'active', '2025-10-18 03:54:25', '2025-10-18 03:54:25'),
(14, 10, 'D014', 'ฝ่ายส่งเสริมการท่องเที่ยว', NULL, 3, 'ฝ่าย', NULL, NULL, NULL, NULL, NULL, NULL, 'active', '2025-10-18 03:54:25', '2025-10-18 03:54:25'),
(15, 2, 'D015', 'ส่วนบริหารงานคลัง', NULL, 2, 'ส่วน', NULL, NULL, NULL, NULL, NULL, NULL, 'active', '2025-10-18 03:54:25', '2025-10-18 03:54:25'),
(16, 2, 'D016', 'ฝ่ายบริหารงานทั่วไป(สค.)', NULL, 2, 'ฝ่าย', 0, '', '', '', '', '', 'active', '2025-10-18 03:54:25', '2025-11-23 16:10:39'),
(17, 11, 'D017', 'ฝ่ายรักษาความสงบ', NULL, 3, 'ฝ่าย', NULL, NULL, NULL, NULL, NULL, NULL, 'active', '2025-10-18 03:54:25', '2025-10-18 03:54:25'),
(18, 11, 'D018', 'ฝ่ายป้องกันและบรรเทาสาธารณภัย', NULL, 3, 'ฝ่าย', NULL, NULL, NULL, NULL, NULL, NULL, 'active', '2025-10-18 03:54:25', '2025-10-18 03:54:25'),
(19, 11, 'D019', 'ฝ่ายทะเบียนราษฎรและบัตรประจำตัวประชาชน', NULL, 3, 'ฝ่าย', NULL, NULL, NULL, NULL, NULL, NULL, 'active', '2025-10-18 03:54:25', '2025-10-18 03:54:25'),
(20, 11, 'D020', 'กลุ่มงานนิติการ', NULL, 3, 'กลุ่มงาน', NULL, NULL, NULL, NULL, NULL, NULL, 'active', '2025-10-18 03:54:25', '2025-10-18 03:54:25'),
(21, 12, 'D021', 'งานบริหารงานทั่วไป', NULL, 4, 'งาน', NULL, NULL, NULL, NULL, NULL, NULL, 'active', '2025-10-18 03:54:25', '2025-10-18 03:54:25'),
(22, 12, 'D022', 'งานรัฐพิธี', NULL, 4, 'งาน', NULL, NULL, NULL, NULL, NULL, NULL, 'active', '2025-10-18 03:54:25', '2025-10-18 03:54:25'),
(23, 12, 'D023', 'งานพัสดุและการเบิกจ่าย', NULL, 4, 'งาน', NULL, NULL, NULL, NULL, NULL, NULL, 'active', '2025-10-18 03:54:25', '2025-10-18 03:54:25'),
(24, 13, 'D024', 'งานกิจการคณะผู้บริหาร', NULL, 4, 'งาน', NULL, NULL, NULL, NULL, NULL, NULL, 'active', '2025-10-18 03:54:25', '2025-10-18 03:54:25'),
(25, 13, 'D025', 'งานกิจการสภาของเทศบาล', NULL, 4, 'งาน', NULL, NULL, NULL, NULL, NULL, NULL, 'active', '2025-10-18 03:54:25', '2025-10-18 03:54:25'),
(26, 13, 'D026', 'งานการประชุม', NULL, 4, 'งาน', NULL, NULL, NULL, NULL, NULL, NULL, 'active', '2025-10-18 03:54:25', '2025-10-18 03:54:25'),
(27, 14, 'D027', 'งานพัฒนาและส่งเสริมการท่องเที่ยว', NULL, 4, 'งาน', NULL, NULL, NULL, NULL, NULL, NULL, 'active', '2025-10-18 03:54:25', '2025-10-18 03:54:25'),
(28, 14, 'D028', 'งานสนับสนุนกิจการท่องเที่ยว', NULL, 4, 'งาน', NULL, NULL, NULL, NULL, NULL, NULL, 'active', '2025-10-18 03:54:25', '2025-10-18 03:54:25'),
(29, 3, '53603.1', 'ส่วนควบคุมการก่อสร้างอาคารและผังเมือง', NULL, 2, 'ส่วน', NULL, 'เลขที่ 151 ถนนรังสิต-ปทุมธานี ตำบลประชาธิปัตย์ อำเภอธัญบุรี จังหวัดปทุมธานี 12130', '9', '0910109174', '', '', 'active', '2025-10-19 05:26:07', '2025-10-19 05:26:07'),
(30, 3, '53603.2', 'ส่วนการโยธา', NULL, 2, 'ส่วน', NULL, 'เลขที่ 151 ถนนรังสิต-ปทุมธานี ตำบลประชาธิปัตย์ อำเภอธัญบุรี จังหวัดปทุมธานี 12130', '9', '0813902531', '', '', 'active', '2025-10-19 05:27:09', '2025-10-19 05:27:09'),
(31, 3, '53603.3', 'ฝ่ายบริหารงานทั่วไป', NULL, 3, 'ฝ่าย', NULL, '', '', '', '', '', 'active', '2025-10-19 05:28:11', '2025-10-19 05:28:11');

-- --------------------------------------------------------

--
-- Table structure for table `learning_resources`
--

CREATE TABLE `learning_resources` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL COMMENT 'หัวข้อ/ชื่อเรื่อง',
  `description` text DEFAULT NULL COMMENT 'คำอธิบาย/รายละเอียดย่อ',
  `resource_type` varchar(50) NOT NULL COMMENT 'ประเภท: pdf, video, podcast, blog, sourcecode, youtube, flipbook',
  `resource_url` varchar(500) NOT NULL COMMENT 'URL/Link ของทรัพยากร',
  `cover_image` varchar(255) DEFAULT NULL COMMENT 'URL ภาพหน้าปก',
  `category` varchar(100) DEFAULT NULL COMMENT 'หมวดหมู่: คู่มือ, หลักสูตร, บทความ, etc.',
  `author` varchar(100) DEFAULT NULL COMMENT 'ผู้เขียน/ผู้สร้าง',
  `duration` varchar(50) DEFAULT NULL COMMENT 'ระยะเวลา (สำหรับ Video/Podcast)',
  `file_size` varchar(50) DEFAULT NULL COMMENT 'ขนาดไฟล์ (สำหรับ PDF)',
  `tags` varchar(255) DEFAULT NULL COMMENT 'Tags/คำค้นหา (คั่นด้วยคอมม่า)',
  `view_count` int(11) DEFAULT 0 COMMENT 'จำนวนการเข้าชม',
  `is_featured` tinyint(1) DEFAULT 0 COMMENT '1 = แนะนำ, 0 = ปกติ',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1 = แสดง, 0 = ซ่อน',
  `display_order` int(11) NOT NULL DEFAULT 0 COMMENT 'ลำดับการแสดงผล',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `learning_resources`
--

INSERT INTO `learning_resources` (`id`, `title`, `description`, `resource_type`, `resource_url`, `cover_image`, `category`, `author`, `duration`, `file_size`, `tags`, `view_count`, `is_featured`, `is_active`, `display_order`, `created_at`, `updated_at`) VALUES
(1, 'คู่มือการใช้งานอีเมลเทศบาล', 'คู่มือฉบับสมบูรณ์สำหรับการใช้งานระบบอีเมลของเทศบาล รวมถึงการตั้งค่าบนมือถือและคอมพิวเตอร์', 'pdf', 'uploads/resources/1767061449_695337c9a646b.pdf', 'uploads/covers/1767061449_695337c9a7036.jpg', 'คู่มือ', 'ฝ่าย IT', '', '2.5 MB', 'email,คู่มือ,การใช้งาน', 3, 1, 1, 1, '2025-12-29 08:11:02', '2025-12-30 02:24:09'),
(2, 'คู่มือการเชื่อมต่อ WiFi', 'วิธีการเชื่อมต่อ WiFi เทศบาล พร้อมการแก้ไขปัญหาเบื้องต้น', 'pdf', 'uploads/resources/1767022541_69529fcdab396.pdf', 'uploads/covers/1767022567_69529fe7c8d26.jpg', 'คู่มือ', 'ฝ่าย IT', '', '1.8 MB', 'wifi,internet,การเชื่อมต่อ', 2, 0, 1, 2, '2025-12-29 08:11:02', '2025-12-29 15:49:37'),
(3, 'วิธีการใช้งาน NAS Storage', 'สอนการใช้งานระบบ NAS เพื่อจัดเก็บและแชร์ไฟล์ภายในองค์กร', 'video', 'uploads/resources/1767022603_6952a00beb1df.pdf', 'uploads/covers/1767022603_6952a00beb737.jpg', 'หลักสูตร', 'ทีม IT Support', '15:30', '', 'nas,storage,tutorial', 0, 1, 1, 3, '2025-12-29 08:11:02', '2025-12-29 15:36:43'),
(4, 'การสร้าง QR Code ด้วยระบบของเทศบาล', 'คลิปสอนการใช้งานระบบสร้าง QR Code สำหรับประชาสัมพันธ์โครงการ', 'video', 'uploads/resources/1767022924_6952a14ccd04a.pdf', 'uploads/covers/1767022924_6952a14ccd90e.jpg', 'หลักสูตร', 'ฝ่ายประชาสัมพันธ์', '8:45', '', 'qrcode,tutorial,วิธีใช้', 0, 0, 1, 4, '2025-12-29 08:11:02', '2025-12-29 15:42:04'),
(5, 'PHP Programming สำหรับมือใหม่', 'หลักสูตร PHP เบื้องต้นสำหรับพัฒนาเว็บไซต์', 'youtube', 'https://www.youtube.com/watch?v=AAPI_yfi0uU', 'uploads/covers/1767064426_6953436a8141c.jpg', 'หลักสูตร', 'Code Academy', '2:30:00', '', 'php,programming,course', 3, 1, 1, 5, '2025-12-29 08:11:02', '2025-12-30 03:13:46'),
(6, 'IT Talk: เทคนิคการรักษาความปลอดภัยข้อมูล', 'Podcast เกี่ยวกับการรักษาความปลอดภัยของข้อมูลในยุคดิจิทัล', 'podcast', 'podcasts/security-tips.mp3', 'uploads/covers/1767064713_6953448988b7f.jpg', 'Podcast', 'ฝ่าย IT Security', '25:15', '45 MB', 'security,podcast,ความปลอดภัย', 0, 0, 1, 6, '2025-12-29 08:11:02', '2025-12-30 03:18:33'),
(7, '10 เคล็ดลับการใช้คอมพิวเตอร์อย่างปลอดภัย', 'บทความแนะนำวิธีการใช้งานคอมพิวเตอร์ให้ปลอดภัยจากมัลแวร์และแฮกเกอร์', 'blog', 'uploads/resources/1767065118_6953461e71b9a.pdf', 'uploads/covers/1767065065_695345e93d874.jpg', 'บทความ', 'Admin IT', '', '', 'security,tips,คอมพิวเตอร์', 0, 0, 1, 7, '2025-12-29 08:11:02', '2025-12-30 03:25:18'),
(8, 'รายงานประจำปี IT 2567', 'เอกสารรายงานผลการดำเนินงานด้าน IT ประจำปี 2567 แบบ Flipbook', 'flipbook', 'flipbook/annual-report-2024.html', 'uploads/covers/1767073765_695367e571949.jpg', 'รายงาน', 'ฝ่าย IT', '', '', 'รายงาน,annual,2567', 0, 1, 1, 8, '2025-12-29 08:11:02', '2025-12-30 05:49:25'),
(9, 'Source Code: ระบบจองห้องประชุม', 'โค้ดตัวอย่างระบบจองห้องประชุมออนไลน์ พร้อมเอกสารประกอบ', 'sourcecode', 'https://github.com/example/meeting-room', 'uploads/covers/1767074198_695369966c420.png', 'Source Code', 'Dev Team', '', '', 'sourcecode,php,javascript', 0, 0, 1, 9, '2025-12-29 08:11:02', '2025-12-30 05:56:38'),
(10, 'หลักสูตร Microsoft Office ฉบับสมบูรณ์', 'รวมคลิปสอน Word, Excel, PowerPoint สำหรับงานสำนักงาน', 'youtube', 'https://www.youtube.com/playlist?list=PLxxxxxx', 'uploads/covers/1767074211_695369a324f30.png', 'หลักสูตร', 'Microsoft Thailand', '5:00:00', '', 'office,word,excel,powerpoint', 0, 1, 1, 10, '2025-12-29 08:11:02', '2025-12-30 05:56:51');

-- --------------------------------------------------------

--
-- Table structure for table `my_service`
--

CREATE TABLE `my_service` (
  `id` int(11) NOT NULL,
  `service_code` varchar(50) NOT NULL COMMENT 'รหัสบริการ',
  `service_name` varchar(100) NOT NULL COMMENT 'ชื่อบริการ (ภาษาไทย)',
  `service_name_en` varchar(100) NOT NULL COMMENT 'ชื่อบริการ (ภาษาอังกฤษ)',
  `description` text DEFAULT NULL COMMENT 'คำอธิบายบริการ',
  `icon` varchar(50) DEFAULT NULL COMMENT 'Font Awesome icon class',
  `color_code` varchar(20) DEFAULT 'blue' COMMENT 'สี',
  `service_url` varchar(255) DEFAULT '#' COMMENT 'URL สำหรับคลิก',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1 = แสดง, 0 = ซ่อน',
  `display_order` int(11) NOT NULL DEFAULT 0 COMMENT 'ลำดับการแสดงผล',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `my_service`
--

INSERT INTO `my_service` (`id`, `service_code`, `service_name`, `service_name_en`, `description`, `icon`, `color_code`, `service_url`, `is_active`, `display_order`, `created_at`, `updated_at`) VALUES
(1, 'EMAIL', 'อีเมลเทศบาล', 'Email Service', 'ขอเปิดใช้งานอีเมลใหม่, รีเซ็ตรหัสผ่าน, เพิ่มขนาดพื้นที่กล่องจดหมาย', 'fas fa-envelope', 'blue', 'service-email.php', 1, 1, '2025-12-29 06:56:35', '2025-12-29 06:56:35'),
(2, 'INTERNET', 'อินเทอร์เน็ต / WiFi', 'Internet Access', 'ขอรหัสผ่าน WiFi, แจ้งปัญหาเน็ตช้า, ติดตั้งจุดกระจายสัญญาณเพิ่ม', 'fas fa-wifi', 'indigo', 'service-internet.php', 1, 2, '2025-12-29 06:56:35', '2025-12-29 06:56:35'),
(3, 'IT_SUPPORT', 'แจ้งซ่อมระบบ IT', 'IT Support', 'คอมพิวเตอร์เสีย, เครื่องพิมพ์มีปัญหา, ลงโปรแกรม, กำจัดไวรัส', 'fas fa-tools', 'red', 'service-it-support.php', 1, 3, '2025-12-29 06:56:35', '2025-12-29 06:56:35'),
(4, 'NAS', 'พื้นที่เก็บข้อมูล NAS', 'NAS Storage', 'ขอพื้นที่แชร์ไฟล์ส่วนกลาง (Network Attached Storage), กู้คืนข้อมูลหาย', 'fas fa-hdd', 'orange', 'service-nas.php', 1, 4, '2025-12-29 06:56:35', '2025-12-29 06:56:35'),
(5, 'QR_CODE', 'สร้าง QR Code', 'QR Code Generator', 'บริการสร้าง QR Code สำหรับประชาสัมพันธ์โครงการต่างๆ', 'fas fa-qrcode', 'purple', 'service-qrcode.php', 1, 5, '2025-12-29 06:56:35', '2025-12-29 06:56:35'),
(6, 'PHOTOGRAPHY', 'บริการถ่ายภาพ', 'Photography Service', 'จองคิวช่างภาพสำหรับงานพิธี, งานกิจกรรมโครงการเทศบาล', 'fas fa-camera', 'pink', 'service-photo.php', 1, 6, '2025-12-29 06:56:35', '2025-12-29 06:56:35'),
(7, 'WEB_DESIGN', 'ออกแบบเว็บไซต์', 'Web Design', 'ขอให้ออกแบบและพัฒนาเว็บไซต์สำหรับหน่วยงานต่างๆ ,สร้าง Sub Domain', 'fas fa-laptop-code', 'teal', 'service-webdesign.php', 1, 7, '2025-12-29 06:56:35', '2025-12-30 02:43:15'),
(8, 'PRINTER', 'เครื่องพิมพ์และสแกนเนอร์', 'Printer &amp; Scanner', 'แก้ปัญหาเครื่องพิมพ์เบื้องต้น, ติดตั้งเครื่องใหม่, แนะนำวิธีการใช้งาน', 'fas fa-print', 'green', 'service-printer.php', 1, 8, '2025-12-29 06:56:35', '2025-12-30 02:33:04');

-- --------------------------------------------------------

--
-- Table structure for table `nav_menu`
--

CREATE TABLE `nav_menu` (
  `id` int(11) NOT NULL,
  `parent_id` int(11) DEFAULT NULL COMMENT 'NULL = parent menu, มีค่า = submenu',
  `menu_name` varchar(100) NOT NULL COMMENT 'ชื่อเมนู (ภาษาไทย)',
  `menu_name_en` varchar(100) DEFAULT NULL COMMENT 'ชื่อเมนู (ภาษาอังกฤษ)',
  `menu_url` varchar(255) DEFAULT '#' COMMENT 'URL/Link ของเมนู',
  `menu_icon` varchar(50) DEFAULT NULL COMMENT 'Font Awesome icon class (เช่น fas fa-home)',
  `menu_order` int(11) NOT NULL DEFAULT 0 COMMENT 'ลำดับการแสดงผล (เรียงจากน้อยไปมาก)',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1 = แสดง, 0 = ซ่อน',
  `target` varchar(20) DEFAULT '_self' COMMENT '_self, _blank, _parent, _top',
  `description` text DEFAULT NULL COMMENT 'คำอธิบายเพิ่มเติม',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `nav_menu`
--

INSERT INTO `nav_menu` (`id`, `parent_id`, `menu_name`, `menu_name_en`, `menu_url`, `menu_icon`, `menu_order`, `is_active`, `target`, `description`, `created_at`, `updated_at`) VALUES
(1, NULL, 'หน้าแรก', 'Home', 'index.php', NULL, 1, 1, '_self', NULL, '2025-12-29 06:35:10', '2025-12-29 06:35:10'),
(2, NULL, 'บริการออนไลน์', 'Online Services', '#', 'fas fa-globe', 2, 1, '_self', NULL, '2025-12-29 06:35:11', '2025-12-29 06:35:11'),
(3, 2, 'แจ้งซ่อมคอมพิวเตอร์', 'IT Support', 'it-support.php', 'fas fa-tools', 1, 1, '_self', NULL, '2025-12-29 06:35:11', '2025-12-29 06:35:11'),
(4, 2, 'ขอใช้อินเทอร์เน็ต', 'Internet Request', 'internet-request.php', 'fas fa-wifi', 2, 1, '_self', NULL, '2025-12-29 06:35:11', '2025-12-29 06:35:11'),
(5, 2, 'ขอพื้นที่เก็บข้อมูล', 'Storage Request', 'storage-request.php', 'fas fa-hdd', 3, 1, '_self', NULL, '2025-12-29 06:35:11', '2025-12-29 06:35:11'),
(6, NULL, 'คู่มือการใช้งาน', 'User Manual', '#', 'fas fa-book', 3, 1, '_self', NULL, '2025-12-29 06:35:11', '2025-12-29 06:35:11'),
(7, 6, 'Email เทศบาล', 'Email', 'manual-email.php', 'fas fa-envelope', 1, 1, '_self', NULL, '2025-12-29 06:35:11', '2025-12-29 06:35:11'),
(8, 6, 'NAS', 'NAS', 'manual-nas.php', 'fas fa-server', 2, 1, '_self', NULL, '2025-12-29 06:35:11', '2025-12-29 06:35:11'),
(9, 6, 'Internet', 'Internet', 'manual-internet.php', 'fas fa-globe', 3, 1, '_self', NULL, '2025-12-29 06:35:11', '2025-12-29 06:35:11'),
(10, NULL, 'ติดต่อเรา', 'Contact Us', 'contact.php', NULL, 4, 1, '_self', NULL, '2025-12-29 06:35:11', '2025-12-29 06:35:11'),
(11, 10, 'แผนที่ที่ตั้ง', 'map', 'my-location.php', 'fas fa-map', 1, 1, '0', '', '2025-12-29 06:37:20', '2025-12-29 06:37:20');

-- --------------------------------------------------------

--
-- Table structure for table `prefixes`
--

CREATE TABLE `prefixes` (
  `prefix_id` int(11) NOT NULL,
  `prefix_name` varchar(100) NOT NULL,
  `prefix_short` varchar(50) DEFAULT NULL,
  `prefix_type` enum('general','military_army','military_navy','military_air','police','academic') DEFAULT 'general',
  `is_active` tinyint(1) DEFAULT 1,
  `display_order` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `prefixes`
--

INSERT INTO `prefixes` (`prefix_id`, `prefix_name`, `prefix_short`, `prefix_type`, `is_active`, `display_order`) VALUES
(1, 'นาย', 'นาย', 'general', 1, 1),
(2, 'นาง', 'นาง', 'general', 1, 2),
(3, 'นางสาว', 'น.ส.', 'general', 1, 3),
(4, 'เด็กชาย', 'ด.ช.', 'general', 1, 4),
(5, 'เด็กหญิง', 'ด.ญ.', 'general', 1, 5),
(6, 'ว่าที่ร้อยตรี', 'ว่าที่ ร.ต.', 'military_army', 1, 10),
(7, 'ว่าที่ร้อยโท', 'ว่าที่ ร.ท.', 'military_army', 1, 11),
(8, 'ว่าที่ร้อยเอก', 'ว่าที่ ร.อ.', 'military_army', 1, 12),
(9, 'ว่าที่ร้อยตรีหญิง', 'ว่าที่ ร.ต.หญิง', 'military_army', 1, 13),
(10, 'ว่าที่ร้อยโทหญิง', 'ว่าที่ ร.ท.หญิง', 'military_army', 1, 14),
(11, 'ว่าที่ร้อยเอกหญิง', 'ว่าที่ ร.อ.หญิง', 'military_army', 1, 15),
(12, 'สิบตรี', 'สิบตรี', 'military_army', 1, 20),
(13, 'สิบโท', 'สิบโท', 'military_army', 1, 21),
(14, 'สิบเอก', 'สิบเอก', 'military_army', 1, 22),
(15, 'สิบตรีหญิง', 'สิบตรีหญิง', 'military_army', 1, 23),
(16, 'สิบโทหญิง', 'สิบโทหญิง', 'military_army', 1, 24),
(17, 'สิบเอกหญิง', 'สิบเอกหญิง', 'military_army', 1, 25),
(18, 'จ่าสิบตรี', 'จ.ส.ต.', 'military_army', 1, 26),
(19, 'จ่าสิบโท', 'จ.ส.ท.', 'military_army', 1, 27),
(20, 'จ่าสิบเอก', 'จ.ส.อ.', 'military_army', 1, 28),
(21, 'จ่าสิบตรีหญิง', 'จ.ส.ต.หญิง', 'military_army', 1, 29),
(22, 'จ่าสิบโทหญิง', 'จ.ส.ท.หญิง', 'military_army', 1, 30),
(23, 'จ่าสิบเอกหญิง', 'จ.ส.อ.หญิง', 'military_army', 1, 31),
(24, 'ร้อยตรี', 'ร.ต.', 'military_army', 1, 40),
(25, 'ร้อยโท', 'ร.ท.', 'military_army', 1, 41),
(26, 'ร้อยเอก', 'ร.อ.', 'military_army', 1, 42),
(27, 'ร้อยตรีหญิง', 'ร.ต.หญิง', 'military_army', 1, 43),
(28, 'ร้อยโทหญิง', 'ร.ท.หญิง', 'military_army', 1, 44),
(29, 'ร้อยเอกหญิง', 'ร.อ.หญิง', 'military_army', 1, 45),
(30, 'พันตรี', 'พ.ต.', 'military_army', 1, 46),
(31, 'พันโท', 'พ.ท.', 'military_army', 1, 47),
(32, 'พันเอก', 'พ.อ.', 'military_army', 1, 48),
(33, 'พันตรีหญิง', 'พ.ต.หญิง', 'military_army', 1, 49),
(34, 'พันโทหญิง', 'พ.ท.หญิง', 'military_army', 1, 50),
(35, 'พันเอกหญิง', 'พ.อ.หญิง', 'military_army', 1, 51),
(36, 'พลตรี', 'พล.ต.', 'military_army', 1, 52),
(37, 'พลโท', 'พล.ท.', 'military_army', 1, 53),
(38, 'พลเอก', 'พล.อ.', 'military_army', 1, 54),
(39, 'นาวาตรี', 'น.ต.', 'military_navy', 1, 60),
(40, 'นาวาโท', 'น.ท.', 'military_navy', 1, 61),
(41, 'นาวาเอก', 'น.อ.', 'military_navy', 1, 62),
(42, 'นาวาตรีหญิง', 'น.ต.หญิง', 'military_navy', 1, 63),
(43, 'นาวาโทหญิง', 'น.ท.หญิง', 'military_navy', 1, 64),
(44, 'นาวาเอกหญิง', 'น.อ.หญิง', 'military_navy', 1, 65),
(45, 'พันจ่าตรี', 'พ.จ.ต.', 'military_navy', 1, 66),
(46, 'พันจ่าโท', 'พ.จ.ท.', 'military_navy', 1, 67),
(47, 'พันจ่าเอก', 'พ.จ.อ.', 'military_navy', 1, 68),
(48, 'เรืออากาศตรี', 'ร.อ.', 'military_air', 1, 70),
(49, 'เรืออากาศโท', 'ร.ท.', 'military_air', 1, 71),
(50, 'เรืออากาศเอก', 'ร.อ.', 'military_air', 1, 72),
(51, 'เรืออากาศตรีหญิง', 'ร.อ.หญิง', 'military_air', 1, 73),
(52, 'เรืออากาศโทหญิง', 'ร.ท.หญิง', 'military_air', 1, 74),
(53, 'เรืออากาศเอกหญิง', 'ร.อ.หญิง', 'military_air', 1, 75),
(54, 'ด้านตำรวจ', NULL, 'police', 1, 80),
(55, 'พลตำรวจ', 'พล.ต.', 'police', 1, 81),
(56, 'ร้อยตำรวจตรี', 'ร.ต.ต.', 'police', 1, 82),
(57, 'ร้อยตำรวจโท', 'ร.ต.ท.', 'police', 1, 83),
(58, 'ร้อยตำรวจเอก', 'ร.ต.อ.', 'police', 1, 84),
(59, 'พันตำรวจตรี', 'พ.ต.ต.', 'police', 1, 85),
(60, 'พันตำรวจโท', 'พ.ต.ท.', 'police', 1, 86),
(61, 'พันตำรวจเอก', 'พ.ต.อ.', 'police', 1, 87),
(62, 'พลตำรวจตรี', 'พล.ต.ต.', 'police', 1, 88),
(63, 'พลตำรวจโท', 'พล.ต.ท.', 'police', 1, 89),
(64, 'พลตำรวจเอก', 'พล.ต.อ.', 'police', 1, 90),
(65, 'ดร.', 'ดร.', 'academic', 1, 100),
(66, 'ผู้ช่วยศาสตราจารย์', 'ผศ.', 'academic', 1, 101),
(67, 'รองศาสตราจารย์', 'รศ.', 'academic', 1, 102),
(68, 'ศาสตราจารย์', 'ศ.', 'academic', 1, 103),
(69, 'ผู้ช่วยศาสตราจารย์ ดร.', 'ผศ.ดร.', 'academic', 1, 104),
(70, 'รองศาสตราจารย์ ดร.', 'รศ.ดร.', 'academic', 1, 105),
(71, 'ศาสตราจารย์ ดร.', 'ศ.ดร.', 'academic', 1, 106),
(72, 'ศาสตราจารย์พิเศษ', 'ศ.พิเศษ', 'academic', 1, 107),
(73, 'ศาสตราจารย์กิตติคุณ', 'ศ.กิตติคุณ', 'academic', 1, 108);

-- --------------------------------------------------------

--
-- Table structure for table `request_email_details`
--

CREATE TABLE `request_email_details` (
  `id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL COMMENT 'FK to service_requests',
  `requested_username` varchar(100) NOT NULL COMMENT 'Username ที่ต้องการ',
  `email_format` varchar(100) DEFAULT NULL COMMENT 'รูปแบบอีเมล เช่น firstname.lastname@domain',
  `quota_mb` int(11) DEFAULT 1024 COMMENT 'พื้นที่จดหมาย (MB)',
  `purpose` text DEFAULT NULL COMMENT 'วัตถุประสงค์การใช้งาน',
  `is_new_account` tinyint(1) DEFAULT 1 COMMENT '1=สร้างใหม่, 0=ขอเพิ่ม quota/reset password',
  `existing_email` varchar(100) DEFAULT NULL COMMENT 'อีเมลเดิม (กรณี reset/เพิ่ม quota)',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `request_email_details`
--

INSERT INTO `request_email_details` (`id`, `request_id`, `requested_username`, `email_format`, `quota_mb`, `purpose`, `is_new_account`, `existing_email`, `created_at`) VALUES
(1, 1, 'somchai.j', 'somchai.j@rangsit.go.th', 2048, 'ใช้งานราชการทั่วไป ติดต่อประสานงาน', 1, NULL, '2025-12-29 08:26:54'),
(2, 4, 'thanongb42', '', 2048, 'wwwwwwwwwwwwwwwww', 1, '', '2025-12-30 04:05:03'),
(3, 5, 'thanongb42', 'thanongb42@gmail.com', 2048, 'eeeeee', 1, '', '2025-12-30 08:01:10');

-- --------------------------------------------------------

--
-- Table structure for table `request_internet_details`
--

CREATE TABLE `request_internet_details` (
  `id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL COMMENT 'FK to service_requests',
  `request_type` enum('new_wifi','password_reset','signal_issue','speed_issue','other') NOT NULL COMMENT 'ประเภทคำขอ',
  `location` varchar(200) NOT NULL COMMENT 'สถานที่ที่ต้องการติดตั้ง/มีปัญหา',
  `building` varchar(100) DEFAULT NULL COMMENT 'อาคาร',
  `room_number` varchar(50) DEFAULT NULL COMMENT 'เลขห้อง',
  `number_of_users` int(11) DEFAULT NULL COMMENT 'จำนวนผู้ใช้งาน',
  `required_speed` varchar(50) DEFAULT NULL COMMENT 'ความเร็วที่ต้องการ',
  `current_issue` text DEFAULT NULL COMMENT 'ปัญหาที่พบ (กรณีแจ้งปัญหา)',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `request_it_support_details`
--

CREATE TABLE `request_it_support_details` (
  `id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL COMMENT 'FK to service_requests',
  `issue_type` enum('hardware','software','network','other') NOT NULL COMMENT 'ประเภทปัญหา',
  `device_type` varchar(100) DEFAULT NULL COMMENT 'ประเภทอุปกรณ์ เช่น Desktop, Laptop, Printer',
  `device_brand` varchar(100) DEFAULT NULL COMMENT 'ยี่ห้อ/รุ่น',
  `symptoms` text NOT NULL COMMENT 'อาการ/ปัญหาที่พบ',
  `location` varchar(200) NOT NULL COMMENT 'สถานที่/ห้อง',
  `urgency_level` enum('low','medium','high','critical') DEFAULT 'medium' COMMENT 'ระดับความเร่งด่วน',
  `error_message` text DEFAULT NULL COMMENT 'ข้อความ Error (ถ้ามี)',
  `when_occurred` varchar(100) DEFAULT NULL COMMENT 'เกิดขึ้นเมื่อไร',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `request_nas_details`
--

CREATE TABLE `request_nas_details` (
  `id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL COMMENT 'FK to service_requests',
  `folder_name` varchar(100) NOT NULL COMMENT 'ชื่อโฟลเดอร์ที่ต้องการ',
  `storage_size_gb` int(11) NOT NULL COMMENT 'ขนาด Storage (GB)',
  `permission_type` enum('read_only','read_write','full_control') DEFAULT 'read_write' COMMENT 'สิทธิ์การเข้าถึง',
  `shared_with` text DEFAULT NULL COMMENT 'แชร์ให้กับใคร (รายชื่อ)',
  `purpose` text NOT NULL COMMENT 'วัตถุประสงค์การใช้งาน',
  `backup_required` tinyint(1) DEFAULT 1 COMMENT '1=ต้องการ Backup, 0=ไม่ต้องการ',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `request_photography_details`
--

CREATE TABLE `request_photography_details` (
  `id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL COMMENT 'FK to service_requests',
  `event_name` varchar(200) NOT NULL COMMENT 'ชื่องาน/กิจกรรม',
  `event_type` varchar(100) DEFAULT NULL COMMENT 'ประเภทงาน เช่น ประชุม, สัมมนา, พิธี',
  `event_date` date NOT NULL COMMENT 'วันที่จัดงาน',
  `event_time_start` time NOT NULL COMMENT 'เวลาเริ่ม',
  `event_time_end` time DEFAULT NULL COMMENT 'เวลาสิ้นสุด',
  `event_location` varchar(200) NOT NULL COMMENT 'สถานที่จัดงาน',
  `number_of_photographers` int(11) DEFAULT 1 COMMENT 'จำนวนช่างภาพที่ต้องการ',
  `video_required` tinyint(1) DEFAULT 0 COMMENT '1=ต้องการถ่ายวิดีโอด้วย, 0=ถ่ายรูปอย่างเดียว',
  `drone_required` tinyint(1) DEFAULT 0 COMMENT '1=ต้องการถ่ายด้วย Drone, 0=ไม่ต้องการ',
  `delivery_format` varchar(100) DEFAULT 'Digital (Drive)' COMMENT 'รูปแบบการส่งมอบไฟล์',
  `special_requirements` text DEFAULT NULL COMMENT 'ความต้องการพิเศษ',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `request_photography_details`
--

INSERT INTO `request_photography_details` (`id`, `request_id`, `event_name`, `event_type`, `event_date`, `event_time_start`, `event_time_end`, `event_location`, `number_of_photographers`, `video_required`, `drone_required`, `delivery_format`, `special_requirements`, `created_at`) VALUES
(1, 3, 'การประชุมคณะกรรมการเทศบาล ครั้งที่ 1/2568', 'ประชุม', '2025-01-15', '09:00:00', '16:00:00', 'ห้องประชุมใหญ่ ชั้น 5', 1, 1, 0, 'Digital (Drive)', NULL, '2025-12-29 08:26:54');

-- --------------------------------------------------------

--
-- Table structure for table `request_printer_details`
--

CREATE TABLE `request_printer_details` (
  `id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL COMMENT 'FK to service_requests',
  `issue_type` enum('repair','toner_replacement','paper_jam','driver_install','new_installation','other') NOT NULL COMMENT 'ประเภทปัญหา/คำขอ',
  `printer_type` enum('inkjet','laser','multifunction','scanner','plotter','3d_printer') DEFAULT NULL COMMENT 'ประเภทเครื่อง',
  `printer_brand` varchar(100) DEFAULT NULL COMMENT 'ยี่ห้อ',
  `printer_model` varchar(100) DEFAULT NULL COMMENT 'รุ่น',
  `serial_number` varchar(100) DEFAULT NULL COMMENT 'S/N',
  `location` varchar(200) NOT NULL COMMENT 'สถานที่ตั้งเครื่อง',
  `problem_description` text NOT NULL COMMENT 'อธิบายปัญหา',
  `error_code` varchar(50) DEFAULT NULL COMMENT 'Error Code (ถ้ามี)',
  `toner_color` varchar(50) DEFAULT NULL COMMENT 'สีหมึก (กรณีขอเติม)',
  `supplies_needed` text DEFAULT NULL COMMENT 'วัสดุที่ต้องการ',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `request_printer_details`
--

INSERT INTO `request_printer_details` (`id`, `request_id`, `issue_type`, `printer_type`, `printer_brand`, `printer_model`, `serial_number`, `location`, `problem_description`, `error_code`, `toner_color`, `supplies_needed`, `created_at`) VALUES
(1, 2, 'repair', 'laser', 'HP', 'LaserJet Pro M404', NULL, 'อาคาร 2 ชั้น 3 ห้องการเงิน', 'เครื่องพิมพ์เปิดไฟได้ แต่พิมพ์เอกสารไม่ออก มีไฟกระพริบสีแดง', NULL, NULL, NULL, '2025-12-29 08:26:54');

-- --------------------------------------------------------

--
-- Table structure for table `request_qrcode_details`
--

CREATE TABLE `request_qrcode_details` (
  `id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL COMMENT 'FK to service_requests',
  `qr_type` enum('url','text','vcard','wifi','payment') DEFAULT 'url' COMMENT 'ประเภท QR Code',
  `qr_content` text NOT NULL COMMENT 'เนื้อหา/URL ที่ต้องการ',
  `qr_size` enum('small','medium','large','xlarge') DEFAULT 'medium' COMMENT 'ขนาด QR',
  `color_primary` varchar(20) DEFAULT '#000000' COMMENT 'สีหลัก',
  `color_background` varchar(20) DEFAULT '#FFFFFF' COMMENT 'สีพื้นหลัง',
  `logo_url` varchar(255) DEFAULT NULL COMMENT 'Logo กลาง QR (ถ้ามี)',
  `output_format` enum('png','svg','pdf','jpg') DEFAULT 'png' COMMENT 'รูปแบบไฟล์ที่ต้องการ',
  `quantity` int(11) DEFAULT 1 COMMENT 'จำนวนที่ต้องการ',
  `purpose` text DEFAULT NULL COMMENT 'วัตถุประสงค์การใช้งาน',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `request_webdesign_details`
--

CREATE TABLE `request_webdesign_details` (
  `id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL COMMENT 'FK to service_requests',
  `website_type` enum('landing_page','corporate','blog','ecommerce','portal','other') NOT NULL COMMENT 'ประเภทเว็บไซต์',
  `project_name` varchar(200) NOT NULL COMMENT 'ชื่อโครงการ',
  `purpose` text NOT NULL COMMENT 'วัตถุประสงค์/เป้าหมาย',
  `target_audience` varchar(200) DEFAULT NULL COMMENT 'กลุ่มเป้าหมาย',
  `number_of_pages` int(11) DEFAULT NULL COMMENT 'จำนวนหน้าโดยประมาณ',
  `features_required` text DEFAULT NULL COMMENT 'ฟีเจอร์ที่ต้องการ (คั่นด้วย comma)',
  `has_existing_site` tinyint(1) DEFAULT 0 COMMENT '1=มีเว็บเก่าอยู่, 0=สร้างใหม่',
  `existing_url` varchar(255) DEFAULT NULL COMMENT 'URL เว็บเก่า (ถ้ามี)',
  `domain_name` varchar(100) DEFAULT NULL COMMENT 'Domain ที่ต้องการ',
  `hosting_required` tinyint(1) DEFAULT 1 COMMENT '1=ต้องการ Hosting, 0=มีของตัวเอง',
  `reference_sites` text DEFAULT NULL COMMENT 'เว็บอ้างอิง (URL)',
  `color_preferences` varchar(200) DEFAULT NULL COMMENT 'สีที่ต้องการ',
  `budget` varchar(50) DEFAULT NULL COMMENT 'งบประมาณ (ถ้ามี)',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `service_requests`
--

CREATE TABLE `service_requests` (
  `id` int(11) NOT NULL,
  `request_code` varchar(50) NOT NULL COMMENT 'รหัสคำขอ เช่น REQ-2025-0001',
  `service_code` varchar(50) NOT NULL COMMENT 'รหัสบริการ (FK to my_service)',
  `requester_name` varchar(100) NOT NULL COMMENT 'ชื่อผู้ขอ',
  `requester_email` varchar(100) DEFAULT NULL COMMENT 'อีเมลผู้ขอ',
  `requester_phone` varchar(20) DEFAULT NULL COMMENT 'เบอร์โทรผู้ขอ',
  `department` varchar(100) DEFAULT NULL COMMENT 'แผนก/หน่วยงาน',
  `position` varchar(100) DEFAULT NULL COMMENT 'ตำแหน่ง',
  `status` enum('pending','in_progress','completed','rejected','cancelled') NOT NULL DEFAULT 'pending' COMMENT 'สถานะคำขอ',
  `priority` enum('low','medium','high','urgent') NOT NULL DEFAULT 'medium' COMMENT 'ความสำคัญ',
  `assigned_to` varchar(100) DEFAULT NULL COMMENT 'เจ้าหน้าที่ผู้รับผิดชอบ',
  `requested_date` date NOT NULL COMMENT 'วันที่ยื่นคำขอ',
  `target_date` date DEFAULT NULL COMMENT 'วันที่ต้องการให้เสร็จ',
  `completed_date` date DEFAULT NULL COMMENT 'วันที่ดำเนินการเสร็จสิ้น',
  `notes` text DEFAULT NULL COMMENT 'หมายเหตุจากผู้ขอ',
  `admin_notes` text DEFAULT NULL COMMENT 'หมายเหตุจาก Admin/เจ้าหน้าที่',
  `rejection_reason` text DEFAULT NULL COMMENT 'เหตุผลในการปฏิเสธ',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `service_requests`
--

INSERT INTO `service_requests` (`id`, `request_code`, `service_code`, `requester_name`, `requester_email`, `requester_phone`, `department`, `position`, `status`, `priority`, `assigned_to`, `requested_date`, `target_date`, `completed_date`, `notes`, `admin_notes`, `rejection_reason`, `created_at`, `updated_at`) VALUES
(1, 'REQ-2025-0001', 'EMAIL', 'สมชาย ใจดี', 'somchai@temp.mail', '081-234-5678', 'ฝ่ายบริหาร', 'เจ้าหน้าที่', 'pending', 'medium', NULL, '2025-01-02', NULL, NULL, 'ต้องการอีเมลสำหรับงานราชการ', NULL, NULL, '2025-12-29 08:26:54', '2025-12-29 08:26:54'),
(2, 'REQ-2025-0002', 'PRINTER', 'วิภา สุขสันต์', 'wipa@rangsit.go.th', '089-876-5432', 'ฝ่ายการเงิน', NULL, 'in_progress', 'high', 'ช่าง IT: นายสมศักดิ์', '2025-01-03', NULL, NULL, 'เครื่องพิมพ์เอกสารไม่ออก', NULL, NULL, '2025-12-29 08:26:54', '2025-12-29 08:26:54'),
(3, 'REQ-2025-0003', 'PHOTOGRAPHY', 'ประสิทธิ์ วงศ์ดี', 'prasit@rangsit.go.th', NULL, 'ฝ่ายประชาสัมพันธ์', NULL, 'pending', 'medium', NULL, '2025-01-03', '2025-01-15', NULL, 'งานประชุมคณะกรรมการ', NULL, NULL, '2025-12-29 08:26:54', '2025-12-29 08:26:54'),
(4, 'REQ-2025-0004', 'EMAIL', 'ทนงค์ บุญเติม', 'thanongb42@gmail.com', '0910109174', 'งานพัฒนาและส่งเสริมการท่องเที่ยว', 'นักวิชาการคอมพิวเตอร์ปฏิบัติการ', 'pending', 'low', NULL, '2025-12-30', NULL, NULL, '', NULL, NULL, '2025-12-30 04:05:03', '2025-12-30 04:05:03'),
(5, 'REQ-2025-0005', 'EMAIL', 'ทนงค์ บุญเติม', 'thanongb42@gmail.com', '0910109174', 'สำนักช่าง', 'นักวิชาการคอมพิวเตอร์ปฏิบัติการ', 'pending', 'low', NULL, '2025-12-30', '2025-12-31', NULL, 'eeeeeeeeeeeeeee', NULL, NULL, '2025-12-30 08:01:10', '2025-12-30 08:01:10');

-- --------------------------------------------------------

--
-- Table structure for table `tech_news`
--

CREATE TABLE `tech_news` (
  `id` int(11) NOT NULL,
  `title` varchar(500) NOT NULL,
  `description` text NOT NULL,
  `content` text DEFAULT NULL,
  `category` varchar(100) NOT NULL,
  `category_color` varchar(50) DEFAULT 'blue',
  `cover_image` varchar(500) DEFAULT NULL,
  `author` varchar(200) DEFAULT NULL,
  `view_count` int(11) DEFAULT 0,
  `is_pinned` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `display_order` int(11) DEFAULT 0,
  `tags` varchar(500) DEFAULT NULL,
  `published_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tech_news`
--

INSERT INTO `tech_news` (`id`, `title`, `description`, `content`, `category`, `category_color`, `cover_image`, `author`, `view_count`, `is_pinned`, `is_active`, `display_order`, `tags`, `published_date`, `created_at`, `updated_at`) VALUES
(1, 'AI และ Machine Learning ปฏิวัติวงการเทคโนโลยี', 'เทคโนโลยี AI กำลังเปลี่ยนแปลงโลกด้วยความสามารถในการเรียนรู้และประมวลผลข้อมูลขนาดใหญ่', '<p>ปัญญาประดิษฐ์หรือ AI กำลังกลายเป็นเทคโนโลยีหลักที่ขับเคลื่อนการเปลี่ยนแปลงในทุกอุตสาหกรรม จากการวิเคราะห์ข้อมูล ไปจนถึงการพัฒนาผลิตภัณฑ์ใหม่ๆ</p><p>\r\n</p><p>Machine Learning ช่วยให้ระบบสามารถเรียนรู้จากข้อมูลและปรับปรุงประสิทธิภาพได้เอง โดยไม่จำเป็นต้องเขียนโปรแกรมทุกกรณี</p>', 'ปัญญาประดิษฐ์', 'blue', 'uploads/tech_news/tech_1767074435_69536a8328c70.png', 'ทีมข่าวเทคโนโลยี', 0, 1, 1, 1, '0', '2025-12-29', '2025-12-29 16:06:41', '2025-12-30 06:00:35'),
(2, 'Cloud Technology สำหรับองค์กรยุคดิจิทัล', 'เทคโนโลยี Cloud Computing ช่วยให้องค์กรสามารถจัดเก็บและประมวลผลข้อมูลได้อย่างมีประสิทธิภาพ', '<p>Cloud Computing เป็นเทคโนโลยีที่ช่วยลดต้นทุนการดูแลระบบ IT และเพิ่มความยืดหยุ่นในการขยายระบบ</p>\r\n<p>องค์กรสามารถเลือกใช้บริการแบบ IaaS, PaaS หรือ SaaS ตามความต้องการ</p>', 'Cloud Computing', 'green', 'uploads/tech_news/1767024515_6952a7838d831.png', 'ทีมข่าวเทคโนโลยี', 0, 1, 1, 2, '0', '2025-12-28', '2025-12-29 16:06:41', '2025-12-29 16:08:35'),
(3, 'ความปลอดภัยไซเบอร์ในยุคดิจิทัล', 'แนวทางการป้องกันภัยคุกคามทางไซเบอร์และการรักษาความปลอดภัยข้อมูลสำหรับองค์กร', '<p>การรักษาความปลอดภัยข้อมูลเป็นสิ่งสำคัญที่สุดในยุคดิจิทัล องค์กรต้องมีมาตรการป้องกันที่แข็งแกร่ง</p>\r\n<p>รวมถึงการใช้ Encryption, Multi-Factor Authentication และการฝึกอบรมพนักงาน</p>', 'Cybersecurity', 'red', 'uploads/tech_news/1767024589_6952a7cd8af3c.png', 'ทีมข่าวเทคโนโลยี', 0, 1, 1, 3, '0', '2025-12-27', '2025-12-29 16:06:41', '2025-12-29 16:09:49'),
(4, 'Google เปิดตัว AI Model ใหม่ Gemini 2.0 พร้อมความสามารถขั้นสูง', 'Google ประกาศเปิดตัว Gemini 2.0 โมเดล AI รุ่นใหม่ที่มีความสามารถในการประมวลผลและเข้าใจบริบทได้ดีขึ้น', '<p>Gemini 2.0 เป็น AI Model ที่พัฒนาขึ้นใหม่จาก Google มีความสามารถในการเข้าใจและตอบสนองต่อคำถามที่ซับซ้อนได้ดีขึ้น</p>', 'ปัญญาประดิษฐ์', 'blue', 'uploads/tech_news/tech_1767074401_69536a61c6129.png', 'ทีมข่าวเทคโนโลยี', 0, 0, 1, 4, '0', '2025-12-26', '2025-12-29 16:06:41', '2025-12-30 06:00:01'),
(5, 'AWS ประกาศบริการ Cloud Computing รุ่นใหม่ประหยัดพลังงาน 40%', 'Amazon Web Services เปิดตัวบริการ Cloud Computing รุ่นใหม่ที่ช่วยลดการใช้พลังงานและต้นทุน', '<p>AWS ได้พัฒนา Data Center รุ่นใหม่ที่ใช้พลังงานอย่างมีประสิทธิภาพ ช่วยลดค่าใช้จ่ายและลดผลกระทบต่อสิ่งแวดล้อม</p>', 'Cloud Computing', 'green', 'uploads/tech_news/1767024632_6952a7f87160e.jpg', 'ทีมข่าวเทคโนโลยี', 0, 0, 1, 5, '0', '2025-12-25', '2025-12-29 16:06:41', '2025-12-29 16:10:32'),
(6, 'Quantum Encryption เทคโนโลยีการเข้ารหัสยุคใหม่ป้องกันแฮกเกอร์', 'เทคโนโลยีการเข้ารหัสแบบควอนตัมที่ไม่สามารถถูกถอดรหัสได้ด้วยคอมพิวเตอร์ทั่วไป', '<p>Quantum Encryption ใช้หลักการของกลศาสตร์ควอนตัมในการเข้ารหัสข้อมูล ทำให้มีความปลอดภัยสูงสุด</p>', 'Cybersecurity', 'red', 'uploads/tech_news/1767024688_6952a8309982f.webp', 'ทีมข่าวเทคโนโลยี', 0, 0, 1, 6, '0', '2025-12-24', '2025-12-29 16:06:41', '2025-12-29 16:11:28'),
(7, 'EV Station PluZ “Charging HUB”', 'ชาร์จฮับ ชอบมั้ยฮับ ?\r\nEV Station PluZ “Charging HUB”\r\nจุดรวมชาร์จใหม่สำหรับชาวอีวี มั่นใจได้ยิ่งกว่าเดิม!!!', '<p>ชาร์จฮับ ชอบมั้ยฮับ ?</p><p>EV Station PluZ “Charging HUB”</p><p>จุดรวมชาร์จใหม่สำหรับชาวอีวี มั่นใจได้ยิ่งกว่าเดิม!!!</p><p><img src=\"https://static.xx.fbcdn.net/images/emoji.php/v9/t5d/1/16/26a1.png\" alt=\"⚡️\" height=\"16\" width=\"16\"> แรง! กำลังไฟสูงสุด 180 กิโลวัตต์</p><p><img src=\"https://static.xx.fbcdn.net/images/emoji.php/v9/t2/1/16/1f60d.png\" alt=\"😍\" height=\"16\" width=\"16\"> เยอะ! 8 หัวชาร์จ จุก ๆ ต่อสถานี</p><p><img src=\"https://static.xx.fbcdn.net/images/emoji.php/v9/tc8/1/16/1f5fa.png\" alt=\"🗺️\" height=\"16\" width=\"16\"> ครบ! 10 แห่ง ทั่วไทยในปีนี้!!</p><p><img src=\"https://static.xx.fbcdn.net/images/emoji.php/v9/t6c/1/16/1f499.png\" alt=\"💙\" height=\"16\" width=\"16\"> เดินทางไปไหน แวะชาร์จที่</p><p>Charging HUB จาก EV Station PluZ</p><p><a href=\"https://www.facebook.com/watch/hashtag/evstationpluz?__eep__=6%2F&amp;__cft__[0]=AZZwKtLA8OZ0nM2BNzXny8AeKN3afPpJUVRLHkuggAjPpN7Z2_sjrnQgODHK2zRsWEXZtV5HDiVnnB-ojOBtEntQnPGgDncgM6TwKKKReRFs6iBvDj49uZdlQxQce-HXVQRdgik5lqrwn8dZCbwE63KzPKL5EQIVkH43VHq49f2l6XieIMUexdVB6oSF0nZWHF55T-R3PuUzT-DSFNrubIKi&amp;__tn__=*NK-R\" target=\"_blank\" style=\"background-color: transparent; color: rgb(0, 100, 209);\">#EVStationPluZ</a> <a href=\"https://www.facebook.com/watch/hashtag/evcharginghub?__eep__=6%2F&amp;__cft__[0]=AZZwKtLA8OZ0nM2BNzXny8AeKN3afPpJUVRLHkuggAjPpN7Z2_sjrnQgODHK2zRsWEXZtV5HDiVnnB-ojOBtEntQnPGgDncgM6TwKKKReRFs6iBvDj49uZdlQxQce-HXVQRdgik5lqrwn8dZCbwE63KzPKL5EQIVkH43VHq49f2l6XieIMUexdVB6oSF0nZWHF55T-R3PuUzT-DSFNrubIKi&amp;__tn__=*NK-R\" target=\"_blank\" style=\"background-color: transparent; color: rgb(0, 100, 209);\">#EVChargingHUB</a></p><p><a href=\"https://www.facebook.com/watch/hashtag/evstationpluzcharginghub?__eep__=6%2F&amp;__cft__[0]=AZZwKtLA8OZ0nM2BNzXny8AeKN3afPpJUVRLHkuggAjPpN7Z2_sjrnQgODHK2zRsWEXZtV5HDiVnnB-ojOBtEntQnPGgDncgM6TwKKKReRFs6iBvDj49uZdlQxQce-HXVQRdgik5lqrwn8dZCbwE63KzPKL5EQIVkH43VHq49f2l6XieIMUexdVB6oSF0nZWHF55T-R3PuUzT-DSFNrubIKi&amp;__tn__=*NK-R\" target=\"_blank\" style=\"background-color: transparent; color: rgb(0, 100, 209);\">#EVStationPluZChargingHUB</a></p><p><a href=\"https://www.facebook.com/watch/hashtag/%E0%B8%8A%E0%B8%B2%E0%B8%A3%E0%B9%8C%E0%B8%88%E0%B8%84%E0%B8%A7%E0%B8%B2%E0%B8%A1%E0%B8%A1%E0%B8%B1%E0%B9%88%E0%B8%99%E0%B9%83%E0%B8%88%E0%B9%84%E0%B8%9B%E0%B9%84%E0%B8%94%E0%B9%89%E0%B8%97%E0%B8%B8%E0%B8%81%E0%B8%97%E0%B8%B5%E0%B9%88?__eep__=6%2F&amp;__cft__[0]=AZZwKtLA8OZ0nM2BNzXny8AeKN3afPpJUVRLHkuggAjPpN7Z2_sjrnQgODHK2zRsWEXZtV5HDiVnnB-ojOBtEntQnPGgDncgM6TwKKKReRFs6iBvDj49uZdlQxQce-HXVQRdgik5lqrwn8dZCbwE63KzPKL5EQIVkH43VHq49f2l6XieIMUexdVB6oSF0nZWHF55T-R3PuUzT-DSFNrubIKi&amp;__tn__=*NK-R\" target=\"_blank\" style=\"background-color: transparent; color: rgb(0, 100, 209);\">#ชาร์จความมั่นใจไปได้ทุกที่</a></p>', 'EV', 'blue', 'uploads/tech_news/tech_1767059504_6953303095cd2.png', 'ทีมข่าวเทคโนโลยี', 0, 0, 1, 0, '', '2025-12-30', '2025-12-30 01:38:29', '2025-12-30 01:51:44'),
(8, 'OR เดินหน้าขับเคลื่อนธุรกิจยานยนต์ไฟฟ้า ขยายหัวชาร์จทั่วไทย ให้เพียงพอกับจำนวนรถ EV ที่เพิ่มขึ้น', 'OR เดินหน้าขับเคลื่อนธุรกิจยานยนต์ไฟฟ้า ขยายหัวชาร์จทั่วไทย ให้เพียงพอกับจำนวนรถ EV ที่เพิ่มขึ้น ต่อยอดธุรกิจเดิมให้รองรับการเปลี่ยนผ่านสู่พลังงานสะอาด ยกระดับพื้นที่สถานีบริการให้เป็นศูนย์กลางไลฟ์สไตล์ครบวงจร ตอบโจทย์ผู้บริโภคยุคใหม่', '<p>หม่อมหลวงปีกทอง ทองใหญ่ ประธานเจ้าหน้าที่บริหาร บริษัท ปตท. น้ำมันและการค้าปลีก จำกัด (มหาชน) หรือ OR กล่าวถึงแนวทางขับเคลื่อนธุรกิจยานยนต์ไฟฟ้า (EV) ให้สอดรับกับเป้าหมาย Net Zero ปี 2050 ซึ่งเป็นเป้าหมายของประเทศ โดย OR มุ่งพัฒนาเครือข่ายสถานีชาร์จ “EV Station PluZ” ให้ครอบคลุมทั่วไทย พร้อมต่อยอดพื้นที่สถานีบริการให้เป็นศูนย์กลางไลฟ์สไตล์ครบวงจร รองรับพฤติกรรมผู้บริโภคในยุคเปลี่ยนผ่านพลังงาน</p><p>ทั้งนี้ตามเป้าหมายของรัฐบาลที่กำหนดให้ภายในปี 2030 รถยนต์ไฟฟ้าคิดเป็น 30% ของการผลิตรถในประเทศ OR จึงเร่งขยายสถานีชาร์จให้เพียงพอกับจำนวนรถ EV ที่จะเพิ่มขึ้น โดยมีแผนขยายหัวชาร์จแบบ DC ให้ได้ 7,000 หัวทั่วประเทศภายในปี 2030 ซึ่งปัจจุบันมีแล้วกว่า 3,300 หัว ครอบคลุมทั้งในสถานี PTT Station สถานี LPG สถานี NGV รวมถึงศูนย์การค้า โรงพยาบาล โรงแรม และหน่วยงานภาครัฐ</p><p>นอกจากการขยายเครือข่าย EV Station PluZ แล้ว OR ยังเดินหน้าพัฒนาแอปพลิเคชัน “EV Station PluZ” ให้เป็นศูนย์กลางประสบการณ์ผู้ใช้งาน โดยรวบรวมบริการตั้งแต่การจองหัวชาร์จ การชาร์จรถ การชำระเงิน ไปจนถึงการสะสมแต้มไว้ในแอปเดียว พร้อมเปิดศูนย์บริการลูกค้า และปรับปรุงระบบ Quick Charger และ Ultra Fast Charging รองรับรถยนต์ไฟฟ้าทุกรุ่น ทั้งแบบส่วนบุคคลและเชิงพาณิชย์</p><p>หม่อมหลวงปีกทอง ระบุว่า OR ไม่ได้มองธุรกิจ EV เป็นการมาแทนธุรกิจน้ำมัน แต่เป็นการต่อยอดธุรกิจเดิมให้รองรับพลังงานสะอาด และเปิดโอกาสใหม่ในการพัฒนา “OR SPACE” ซึ่งเป็นพื้นที่เชิงพาณิชย์ภายในสถานีบริการน้ำมันที่ปรับโฉมให้สอดคล้องกับไลฟ์สไตล์ของผู้บริโภคยุคใหม่ โดยอาศัยช่วงเวลาการชาร์จรถที่ใช้เวลาราว 30–45 นาที เพื่อเติมบริการอื่นๆ เช่น ร้าน Café Amazon ร้านอาหาร ร้าน Otteri WASH &amp; DRY คลินิกสุขภาพ และร้าน found &amp; found ซึ่งจำหน่ายผลิตภัณฑ์สุขภาพและความงาม</p><p>OR ตั้งเป้าปรับสถานีบริการให้เป็นจุดหมายปลายทางที่ตอบโจทย์ทั้งการเดินทางและการใช้ชีวิต ไม่เพียงแต่เป็นจุดชาร์จรถยนต์ไฟฟ้า แต่ยังเป็นพื้นที่ “ชาร์จพลังชีวิต” ของผู้บริโภคในยุคใหม่ที่ต้องการทั้งความสะดวกสบาย เทคโนโลยี และไลฟ์สไตล์ในที่เดียวกัน</p><p>ทั้งนี้ ผู้สนใจสามารถดาวน์โหลดแอป EV Station PluZ ได้ทั้งบนระบบ iOS และ Android เพื่อค้นหาและใช้งานสถานีชาร์จได้สะดวกยิ่งขึ้น พร้อมติดตามข่าวสารและสิทธิประโยชน์ผ่าน LINE Official Account: @evstationpluz และ Facebook Fanpage: EV Station PluZ.-512 - สำนักข่าวไทย</p>', 'EV', 'blue', 'uploads/tech_news/1767058718_69532d1e50fb4.jpg', 'ทีมข่าวเทคโนโลยี', 0, 0, 1, 0, '', '2025-12-30', '2025-12-30 01:38:38', '2025-12-30 01:38:38');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL COMMENT 'รหัสผู้ใช้',
  `prefix_id` int(11) DEFAULT NULL COMMENT 'รหัสคำนำหน้าชื่อ (FK to prefixes)',
  `username` varchar(50) NOT NULL COMMENT 'ชื่อผู้ใช้สำหรับ login',
  `first_name` varchar(100) NOT NULL COMMENT 'ชื่อ',
  `last_name` varchar(100) NOT NULL COMMENT 'นามสกุล',
  `email` varchar(255) NOT NULL COMMENT 'อีเมล',
  `phone` varchar(20) DEFAULT NULL COMMENT 'เบอร์โทรศัพท์',
  `password` varchar(255) NOT NULL COMMENT 'รหัสผ่าน (hashed)',
  `role` enum('admin','staff','user') DEFAULT 'user' COMMENT 'บทบาท: admin=ผู้ดูแล, staff=เจ้าหน้าที่, user=ผู้ใช้ทั่วไป',
  `status` enum('active','inactive','suspended') DEFAULT 'active' COMMENT 'สถานะ: active=ใช้งาน, inactive=ไม่ใช้งาน, suspended=ระงับ',
  `department_id` int(11) DEFAULT NULL COMMENT 'รหัสหน่วยงาน (FK to departments)',
  `position` varchar(100) DEFAULT NULL COMMENT 'ตำแหน่ง',
  `profile_image` varchar(255) DEFAULT NULL COMMENT 'รูปโปรไฟล์',
  `last_login` datetime DEFAULT NULL COMMENT 'เข้าสู่ระบบครั้งล่าสุด',
  `created_at` datetime DEFAULT current_timestamp() COMMENT 'วันที่สร้าง',
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'วันที่อัปเดต'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `prefix_id`, `username`, `first_name`, `last_name`, `email`, `phone`, `password`, `role`, `status`, `department_id`, `position`, `profile_image`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 1, 'admin', 'ผู้ดูแลระบบ', 'เทศบาล', 'admin@rangsit.go.th', NULL, '$2y$10$21ozRFqVm0kw811hygspk.s0GhsIPd6QutKxy8wffhT6RZl7SeL8i', 'admin', 'active', NULL, NULL, NULL, '2025-12-30 15:31:37', '2025-12-30 14:16:04', '2025-12-30 15:31:37'),
(2, 1, 'somchai', 'สมชาย', 'ใจดี', 'somchai@rangsit.go.th', '0812345678', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff', 'active', NULL, NULL, NULL, NULL, '2025-12-30 14:16:04', '2025-12-30 14:16:04'),
(3, 2, 'somsri', 'สมศรี', 'รักดี', 'somsri@rangsit.go.th', '0823456789', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 'active', NULL, NULL, NULL, NULL, '2025-12-30 14:16:04', '2025-12-30 14:16:04'),
(4, 3, 'suree', 'สุรีย์', 'สว่างใจ', 'suree@rangsit.go.th', '0834567890', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 'active', NULL, NULL, NULL, NULL, '2025-12-30 14:16:04', '2025-12-30 14:16:04'),
(5, 6, 'military_user', 'ธนพล', 'มั่นคง', 'thanaphon@rangsit.go.th', '0845678901', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 'active', NULL, NULL, NULL, NULL, '2025-12-30 14:16:04', '2025-12-30 14:16:04'),
(6, 65, 'doctor_user', 'วิชัย', 'ปัญญา', 'wichai@rangsit.go.th', '0856789012', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 'active', NULL, NULL, NULL, NULL, '2025-12-30 14:16:04', '2025-12-30 14:16:04'),
(7, 1, 'thanong', 'ทนงค์', 'บุญเติม', 'thanongb42@gmail.com', '0910109174', '$2y$10$6FW1HGA7OZ3NmZJuCRf3e.YWsCj3Yt5NKaJ5XgRn3hdMpl0umNb4q', 'admin', 'active', NULL, 'นักวิชาการคอมพิวเตอร์ปฏิบัติการ', NULL, '2025-12-30 14:17:39', '2025-12-30 14:17:33', '2025-12-30 15:24:04');

--
-- Triggers `users`
--
DELIMITER $$
CREATE TRIGGER `before_delete_user` BEFORE DELETE ON `users` FOR EACH ROW BEGIN
    IF OLD.role = 'admin' AND (SELECT COUNT(*) FROM users WHERE role = 'admin') <= 1 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Cannot delete the last admin user';
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_service_requests_full`
-- (See below for the actual view)
--
CREATE TABLE `v_service_requests_full` (
`request_id` int(11)
,`service_code` varchar(50)
,`service_name` varchar(15)
,`request_code` varchar(50)
,`status` enum('pending','in_progress','completed','rejected','cancelled')
,`priority` enum('low','medium','high','urgent')
,`created_at` timestamp
,`updated_at` timestamp
,`assigned_to` varchar(100)
,`admin_notes` text
,`completed_date` date
,`username` binary(0)
,`user_email` binary(0)
,`first_name` binary(0)
,`last_name` binary(0)
,`user_full_name` binary(0)
,`department_name` binary(0)
,`department_code` binary(0)
,`assigned_username` varchar(50)
,`assigned_full_name` varchar(302)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_users_full`
-- (See below for the actual view)
--
CREATE TABLE `v_users_full` (
`user_id` int(11)
,`username` varchar(50)
,`prefix_id` int(11)
,`prefix_name` varchar(100)
,`first_name` varchar(100)
,`last_name` varchar(100)
,`full_name` varchar(302)
,`email` varchar(255)
,`phone` varchar(20)
,`role` enum('admin','staff','user')
,`status` enum('active','inactive','suspended')
,`department_id` int(11)
,`department_name` varchar(255)
,`department_code` varchar(20)
,`position` varchar(100)
,`profile_image` varchar(255)
,`last_login` datetime
,`created_at` datetime
,`updated_at` datetime
);

-- --------------------------------------------------------

--
-- Structure for view `v_service_requests_full`
--
DROP TABLE IF EXISTS `v_service_requests_full`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_service_requests_full`  AS SELECT `sr`.`id` AS `request_id`, `sr`.`service_code` AS `service_code`, 'Unknown Service' AS `service_name`, `sr`.`request_code` AS `request_code`, `sr`.`status` AS `status`, `sr`.`priority` AS `priority`, `sr`.`created_at` AS `created_at`, `sr`.`updated_at` AS `updated_at`, `sr`.`assigned_to` AS `assigned_to`, `sr`.`admin_notes` AS `admin_notes`, `sr`.`completed_date` AS `completed_date`, NULL AS `username`, NULL AS `user_email`, NULL AS `first_name`, NULL AS `last_name`, NULL AS `user_full_name`, NULL AS `department_name`, NULL AS `department_code`, `au`.`username` AS `assigned_username`, concat(ifnull(`ap`.`prefix_name`,''),' ',`au`.`first_name`,' ',`au`.`last_name`) AS `assigned_full_name` FROM ((`service_requests` `sr` left join `users` `au` on(`sr`.`assigned_to` = `au`.`user_id`)) left join `prefixes` `ap` on(`au`.`prefix_id` = `ap`.`prefix_id`)) ;

-- --------------------------------------------------------

--
-- Structure for view `v_users_full`
--
DROP TABLE IF EXISTS `v_users_full`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_users_full`  AS SELECT `u`.`user_id` AS `user_id`, `u`.`username` AS `username`, `u`.`prefix_id` AS `prefix_id`, `p`.`prefix_name` AS `prefix_name`, `u`.`first_name` AS `first_name`, `u`.`last_name` AS `last_name`, concat(ifnull(`p`.`prefix_name`,''),' ',`u`.`first_name`,' ',`u`.`last_name`) AS `full_name`, `u`.`email` AS `email`, `u`.`phone` AS `phone`, `u`.`role` AS `role`, `u`.`status` AS `status`, `u`.`department_id` AS `department_id`, `d`.`department_name` AS `department_name`, `d`.`department_code` AS `department_code`, `u`.`position` AS `position`, `u`.`profile_image` AS `profile_image`, `u`.`last_login` AS `last_login`, `u`.`created_at` AS `created_at`, `u`.`updated_at` AS `updated_at` FROM ((`users` `u` left join `prefixes` `p` on(`u`.`prefix_id` = `p`.`prefix_id`)) left join `departments` `d` on(`u`.`department_id` = `d`.`department_id`)) ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`department_id`),
  ADD UNIQUE KEY `idx_department_code` (`department_code`),
  ADD UNIQUE KEY `unit_name_parent` (`department_name`,`parent_department_id`),
  ADD KEY `parent_department_id` (`parent_department_id`),
  ADD KEY `idx_manager` (`manager_user_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `learning_resources`
--
ALTER TABLE `learning_resources`
  ADD PRIMARY KEY (`id`),
  ADD KEY `resource_type` (`resource_type`),
  ADD KEY `category` (`category`),
  ADD KEY `is_active` (`is_active`),
  ADD KEY `is_featured` (`is_featured`),
  ADD KEY `display_order` (`display_order`);

--
-- Indexes for table `my_service`
--
ALTER TABLE `my_service`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `service_code` (`service_code`),
  ADD KEY `is_active` (`is_active`),
  ADD KEY `display_order` (`display_order`);

--
-- Indexes for table `nav_menu`
--
ALTER TABLE `nav_menu`
  ADD PRIMARY KEY (`id`),
  ADD KEY `parent_id` (`parent_id`),
  ADD KEY `is_active` (`is_active`),
  ADD KEY `menu_order` (`menu_order`);

--
-- Indexes for table `prefixes`
--
ALTER TABLE `prefixes`
  ADD PRIMARY KEY (`prefix_id`);

--
-- Indexes for table `request_email_details`
--
ALTER TABLE `request_email_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `request_id` (`request_id`);

--
-- Indexes for table `request_internet_details`
--
ALTER TABLE `request_internet_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `request_id` (`request_id`);

--
-- Indexes for table `request_it_support_details`
--
ALTER TABLE `request_it_support_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `request_id` (`request_id`);

--
-- Indexes for table `request_nas_details`
--
ALTER TABLE `request_nas_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `request_id` (`request_id`);

--
-- Indexes for table `request_photography_details`
--
ALTER TABLE `request_photography_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `request_id` (`request_id`);

--
-- Indexes for table `request_printer_details`
--
ALTER TABLE `request_printer_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `request_id` (`request_id`);

--
-- Indexes for table `request_qrcode_details`
--
ALTER TABLE `request_qrcode_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `request_id` (`request_id`);

--
-- Indexes for table `request_webdesign_details`
--
ALTER TABLE `request_webdesign_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `request_id` (`request_id`);

--
-- Indexes for table `service_requests`
--
ALTER TABLE `service_requests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `request_code` (`request_code`),
  ADD KEY `service_code` (`service_code`),
  ADD KEY `status` (`status`),
  ADD KEY `priority` (`priority`),
  ADD KEY `requested_date` (`requested_date`);

--
-- Indexes for table `tech_news`
--
ALTER TABLE `tech_news`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pinned` (`is_pinned`,`display_order`),
  ADD KEY `idx_active` (`is_active`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_published` (`published_date`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `username_2` (`username`),
  ADD UNIQUE KEY `email_2` (`email`),
  ADD KEY `prefix_id` (`prefix_id`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_department` (`department_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `department_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'รหัสหน่วยงาน/แผนก', AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `learning_resources`
--
ALTER TABLE `learning_resources`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `my_service`
--
ALTER TABLE `my_service`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `nav_menu`
--
ALTER TABLE `nav_menu`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `prefixes`
--
ALTER TABLE `prefixes`
  MODIFY `prefix_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=74;

--
-- AUTO_INCREMENT for table `request_email_details`
--
ALTER TABLE `request_email_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `request_internet_details`
--
ALTER TABLE `request_internet_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `request_it_support_details`
--
ALTER TABLE `request_it_support_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `request_nas_details`
--
ALTER TABLE `request_nas_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `request_photography_details`
--
ALTER TABLE `request_photography_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `request_printer_details`
--
ALTER TABLE `request_printer_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `request_qrcode_details`
--
ALTER TABLE `request_qrcode_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `request_webdesign_details`
--
ALTER TABLE `request_webdesign_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `service_requests`
--
ALTER TABLE `service_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `tech_news`
--
ALTER TABLE `tech_news`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'รหัสผู้ใช้', AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `request_email_details`
--
ALTER TABLE `request_email_details`
  ADD CONSTRAINT `fk_email_request` FOREIGN KEY (`request_id`) REFERENCES `service_requests` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `request_internet_details`
--
ALTER TABLE `request_internet_details`
  ADD CONSTRAINT `fk_internet_request` FOREIGN KEY (`request_id`) REFERENCES `service_requests` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `request_it_support_details`
--
ALTER TABLE `request_it_support_details`
  ADD CONSTRAINT `fk_itsupport_request` FOREIGN KEY (`request_id`) REFERENCES `service_requests` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `request_nas_details`
--
ALTER TABLE `request_nas_details`
  ADD CONSTRAINT `fk_nas_request` FOREIGN KEY (`request_id`) REFERENCES `service_requests` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `request_photography_details`
--
ALTER TABLE `request_photography_details`
  ADD CONSTRAINT `fk_photo_request` FOREIGN KEY (`request_id`) REFERENCES `service_requests` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `request_printer_details`
--
ALTER TABLE `request_printer_details`
  ADD CONSTRAINT `fk_printer_request` FOREIGN KEY (`request_id`) REFERENCES `service_requests` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `request_qrcode_details`
--
ALTER TABLE `request_qrcode_details`
  ADD CONSTRAINT `fk_qrcode_request` FOREIGN KEY (`request_id`) REFERENCES `service_requests` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `request_webdesign_details`
--
ALTER TABLE `request_webdesign_details`
  ADD CONSTRAINT `fk_webdesign_request` FOREIGN KEY (`request_id`) REFERENCES `service_requests` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`prefix_id`) REFERENCES `prefixes` (`prefix_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `users_ibfk_2` FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
