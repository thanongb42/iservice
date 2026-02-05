-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Feb 05, 2026 at 09:17 AM
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
-- Table structure for table `request_qrcode_details`
--

CREATE TABLE `request_qrcode_details` (
  `id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL,
  `qr_type` varchar(50) NOT NULL COMMENT 'url, text, wifi, vcard',
  `qr_content` text NOT NULL COMMENT 'ข้อมูลใน QR',
  `qr_size` varchar(20) DEFAULT 'medium',
  `color_primary` varchar(20) DEFAULT '#000000',
  `color_background` varchar(20) DEFAULT '#ffffff',
  `logo_url` varchar(255) DEFAULT NULL,
  `output_format` varchar(10) DEFAULT 'png',
  `quantity` int(11) DEFAULT 1,
  `purpose` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `request_qrcode_details`
--
ALTER TABLE `request_qrcode_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_request_id` (`request_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `request_qrcode_details`
--
ALTER TABLE `request_qrcode_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `request_qrcode_details`
--
ALTER TABLE `request_qrcode_details`
  ADD CONSTRAINT `fk_qrcode_request` FOREIGN KEY (`request_id`) REFERENCES `service_requests` (`request_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
