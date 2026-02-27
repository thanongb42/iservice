-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Feb 07, 2026 at 12:05 PM
-- Server version: 10.11.16-MariaDB-ubu2204
-- PHP Version: 8.4.17

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `rangsitadmin_iservice_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `nav_menu`
--

CREATE TABLE `nav_menu` (
  `id` int(11) NOT NULL DEFAULT 0,
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
(3, 2, 'แจ้งซ่อมคอมพิวเตอร์', 'IT Support', 'http://iservice.rangsitcity.go.th/request-form.php?service=IT_SUPPORT', 'fas fa-tools', 2, 1, '_self', '', '2025-12-29 06:35:11', '2026-02-07 04:52:01'),
(4, 2, 'ขอใช้อินเทอร์เน็ต', 'Internet Request', 'http://iservice.rangsitcity.go.th/request-form.php?service=INTERNET', 'fas fa-wifi', 1, 1, '_self', '', '2025-12-29 06:35:11', '2026-02-07 04:52:01'),
(5, 2, 'ขอพื้นที่เก็บข้อมูล', 'Storage Request', 'http://iservice.rangsitcity.go.th/request-form.php?service=NAS', 'fas fa-hdd', 3, 1, '_self', '', '2025-12-29 06:35:11', '2026-02-07 04:52:07'),
(6, NULL, 'คู่มือการใช้งาน', 'User Manual', '#', 'fas fa-book', 3, 1, '_self', NULL, '2025-12-29 06:35:11', '2025-12-29 06:35:11'),
(7, 2, 'Email เทศบาล', 'Email', 'http://iservice.rangsitcity.go.th/request-form.php?service=EMAIL', 'fas fa-envelope', 4, 1, '_self', '', '2025-12-29 06:35:11', '2026-02-07 04:59:33'),
(8, 6, 'NAS', 'NAS', 'http://iservice.rangsitcity.go.th/resource-detail.php?id=3', 'fas fa-server', 1, 1, '0', '', '2025-12-29 06:35:11', '2026-02-06 13:59:41'),
(9, 6, 'Internet', 'Internet', 'http://iservice.rangsitcity.go.th/resource-detail.php?id=2', 'fas fa-globe', 2, 1, '0', '', '2025-12-29 06:35:11', '2026-02-06 13:59:55'),
(10, NULL, 'ติดต่อเรา', 'Contact Us', 'contact.php', NULL, 4, 1, '_self', NULL, '2025-12-29 06:35:11', '2025-12-29 06:35:11'),
(11, 10, 'แผนที่ที่ตั้ง', 'map', 'my-location.php', 'fas fa-map', 1, 1, '0', '', '2025-12-29 06:37:20', '2025-12-29 06:37:20'),
(0, 6, 'การใช้งาน Email', 'Email User Manual', 'http://iservice.rangsitcity.go.th/resource-detail.php?id=1', 'fas fa-at', 10, 1, '_self', '', '2026-02-06 14:03:20', '2026-02-07 05:02:56'),
(0, 6, 'การใช้งาน Email', 'Email User Manual', 'http://iservice.rangsitcity.go.th/resource-detail.php?id=1', 'fas fa-at', 10, 1, '_self', '', '2026-02-06 14:11:55', '2026-02-07 05:02:56'),
(0, 6, 'การใช้งาน Email', 'Email User Manual', 'http://iservice.rangsitcity.go.th/resource-detail.php?id=1', 'fas fa-at', 10, 1, '_self', '', '2026-02-06 14:13:38', '2026-02-07 05:02:56'),
(0, 2, 'ขอบริการพิธีกร', 'MC Service', 'http://iservice.rangsitcity.go.th/request-form.php?service=MC', 'fas fa-microphone', 10, 1, '_self', '', '2026-02-07 04:40:19', '2026-02-07 05:02:56'),
(0, 2, 'บริการช่างภาพ', 'photographer service', 'http://iservice.rangsitcity.go.th/request-form.php?service=PHOTOGRAPHY', 'fas fa-camera', 10, 1, '_self', '', '2026-02-07 04:50:17', '2026-02-07 05:02:56'),
(0, 6, 'การใช้งาน Email', 'Email User Manual', 'http://iservice.rangsitcity.go.th/resource-detail.php?id=1', 'fas fa-at', 10, 1, '_self', '', '2026-02-07 04:51:32', '2026-02-07 05:02:56'),
(0, 6, 'การใช้งาน Email', 'Email User Manual', 'http://iservice.rangsitcity.go.th/resource-detail.php?id=1', 'fas fa-at', 10, 1, '_self', '', '2026-02-07 04:58:44', '2026-02-07 05:02:56'),
(0, 6, 'การใช้งาน Email', 'Email User Manual', 'http://iservice.rangsitcity.go.th/resource-detail.php?id=1', 'fas fa-at', 10, 1, '_self', '', '2026-02-07 04:59:48', '2026-02-07 05:02:56'),
(0, 6, 'การใช้งาน Email', 'Email User Manual', 'http://iservice.rangsitcity.go.th/resource-detail.php?id=1', 'fas fa-at', 10, 1, '_self', '', '2026-02-07 05:00:47', '2026-02-07 05:02:56'),
(0, 6, 'การใช้งาน Email', 'Email User Manual', 'http://iservice.rangsitcity.go.th/resource-detail.php?id=1', 'fas fa-at', 10, 1, '_self', '', '2026-02-07 05:02:54', '2026-02-07 05:02:56');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
