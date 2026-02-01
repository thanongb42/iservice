-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 28, 2025 at 03:40 PM
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
-- Database: `rcm-asset`
--

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
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `department_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'รหัสหน่วยงาน/แผนก', AUTO_INCREMENT=32;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
