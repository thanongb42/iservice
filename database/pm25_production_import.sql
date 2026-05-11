-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: iservice_db
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `pm25_sensors`
--

DROP TABLE IF EXISTS `pm25_sensors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pm25_sensors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `location_name` varchar(255) NOT NULL COMMENT 'ชื่อสถานที่ติดตั้ง',
  `cid` varchar(20) NOT NULL COMMENT 'Device CID',
  `serial_number` varchar(50) DEFAULT NULL COMMENT 'S/N',
  `sim_number` varchar(20) DEFAULT NULL COMMENT 'หมายเลข SIM',
  `lat` decimal(10,7) DEFAULT NULL COMMENT 'ละติจูด',
  `lng` decimal(10,7) DEFAULT NULL COMMENT 'ลองจิจูด',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `cid` (`cid`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pm25_sensors`
--

LOCK TABLES `pm25_sensors` WRITE;
/*!40000 ALTER TABLE `pm25_sensors` DISABLE KEYS */;
INSERT INTO `pm25_sensors` VALUES (1,'เทศบาลนครรังสิต','349454E09A5C','FN212D260001','0641902931',13.9866344,100.6095498,1,'2026-05-11 09:41:36','2026-05-11 10:16:06'),(2,'โรงเรียนนครรังสิตสิริเวชชะพันธ์','8C4B1432EBB0','FN212D260002','0642131413',13.9923837,100.6239872,1,'2026-05-11 09:41:36','2026-05-11 10:17:11'),(3,'หอประชุมอาคาร 100 ปี ธัญญบุรี','58BF25FD48FC','FN212D260003','0641720650',13.9926483,100.6464740,1,'2026-05-11 09:41:36','2026-05-11 10:29:39'),(4,'โรงเรียนนครรังสิตเปรมปรีดิ์','58BF25FDB358','FN212D260004','0641810059',13.9827666,100.6547142,1,'2026-05-11 09:41:36','2026-05-11 10:21:59'),(5,'โรงเรียนมัธยมนครรังสิต','349454E089E4','FN212D260005','0641765812',13.9799920,100.6555639,1,'2026-05-11 09:41:36','2026-05-11 10:23:23'),(6,'โรงเรียนดวงกมล','8C4B1432F538','FN212D260006','0641822851',13.9843323,100.6391633,1,'2026-05-11 09:41:36','2026-05-11 10:24:20'),(7,'โรงเรียนนครรังสิตเทพธัญญะอุปถัมภ์','349454E08778','FN212D260007','0641958610',13.9750619,100.6256365,1,'2026-05-11 09:41:36','2026-05-11 10:25:36'),(8,'โรงเรียนเพียรปัญญา','8C4B14327C18','FN212D260008','0641839414',13.9734251,100.6108696,1,'2026-05-11 09:41:36','2026-05-11 10:26:38');
/*!40000 ALTER TABLE `pm25_sensors` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-05-11 17:40:22

-- ─────────────────────────────────────────────────────────────────────────────
-- Step 1: ลบ duplicate rows ใน pm25_data (เก็บไว้แค่ id ต่ำสุดต่อ cid+timestamp)
-- ─────────────────────────────────────────────────────────────────────────────
DELETE d FROM pm25_data d
INNER JOIN pm25_data d2
    ON d.cid = d2.cid
   AND d.sensor_timestamp = d2.sensor_timestamp
   AND d.id > d2.id;

-- ─────────────────────────────────────────────────────────────────────────────
-- Step 2: เพิ่ม UNIQUE KEY บน pm25_data (ถ้ามีอยู่แล้วจะข้ามโดยไม่ error)
-- ─────────────────────────────────────────────────────────────────────────────
SET @exist := (
    SELECT COUNT(*) FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name   = 'pm25_data'
      AND index_name   = 'uq_cid_ts'
);
SET @sql := IF(@exist = 0,
    'ALTER TABLE pm25_data ADD UNIQUE KEY uq_cid_ts (cid, sensor_timestamp)',
    'SELECT ''uq_cid_ts already exists, skipped'''
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
