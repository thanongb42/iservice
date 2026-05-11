-- PM2.5 Sensor Registration Table
-- Run this in phpMyAdmin on iservice_db

CREATE TABLE IF NOT EXISTS `pm25_sensors` (
  `id`            INT AUTO_INCREMENT PRIMARY KEY,
  `location_name` VARCHAR(255) NOT NULL COMMENT 'ชื่อสถานที่ติดตั้ง',
  `cid`           VARCHAR(20)  NOT NULL UNIQUE COMMENT 'Device CID',
  `serial_number` VARCHAR(50)  DEFAULT NULL COMMENT 'S/N',
  `sim_number`    VARCHAR(20)  DEFAULT NULL COMMENT 'หมายเลข SIM',
  `lat`           DECIMAL(10,7) DEFAULT NULL COMMENT 'ละติจูด',
  `lng`           DECIMAL(10,7) DEFAULT NULL COMMENT 'ลองจิจูด',
  `is_active`     TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at`    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `pm25_sensors` (`location_name`, `cid`, `serial_number`, `sim_number`) VALUES
('เทศบาลนครรังสิต',                     '349454E09A5C', 'FN212D260001', '0641902931'),
('โรงเรียนนครรังสิตสิริเวชชะพันธ์',      '8C4B1432EBB0', 'FN212D260002', '0642131413'),
('หอประชุมอาคาร 100 ปี ธัญญบุรี',        '58BF25FD48FD', 'FN212D260003', '0641720650'),
('โรงเรียนนครรังสิตเปรมปรีดิ์',          '58BF25FDB358', 'FN212D260004', '0641810059'),
('โรงเรียนมัธยมนครรังสิต',               '349454E089E4', 'FN212D260005', '0641765812'),
('โรงเรียนดวงกมล',                       '8C4B1432F538', 'FN212D260006', '0641822851'),
('โรงเรียนนครรังสิตเทพธัญญะอุปถัมภ์',   '349454E08778', 'FN212D260007', '0641958610'),
('โรงเรียนเพียรปัญญา',                   '8C4B14327C18', 'FN212D260008', '0641839414')
ON DUPLICATE KEY UPDATE
  `serial_number` = VALUES(`serial_number`),
  `sim_number`    = VALUES(`sim_number`);
