-- ============================================================
-- iService Production Database Update
-- Date    : 2026-02-27 (v2)
-- Server  : MariaDB 10.4+
-- How to import: phpMyAdmin > iservice_db > Import > choose file
-- ============================================================
--
-- Changes:
--   1. service_requests          — add requester_prefix_id INT
--   2. request_internet_details  — rename citizen_id_last3 -> citizen_id VARCHAR(13)
--   3. prefixes                  — create table + insert 73 records
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- 1. service_requests: add requester_prefix_id
--    (ADD COLUMN IF NOT EXISTS — MariaDB 10.3+)
-- ============================================================
ALTER TABLE `service_requests`
    ADD COLUMN IF NOT EXISTS `requester_prefix_id` INT(11) DEFAULT NULL
        COMMENT 'FK -> prefixes.prefix_id'
    AFTER `user_id`;


-- ============================================================
-- 2. request_internet_details: citizen_id column
--
--   Case A — column is still named citizen_id_last3 (not yet renamed)
--     → uncomment the CHANGE line below
--
--   Case B — column already renamed to citizen_id (local is case B)
--     → skip (ADD IF NOT EXISTS is a no-op)
-- ============================================================

-- Case A (uncomment if citizen_id_last3 still exists on production):
-- ALTER TABLE `request_internet_details`
--     CHANGE `citizen_id_last3` `citizen_id`
--     VARCHAR(13) DEFAULT NULL
--     COMMENT 'หมายเลขบัตรประชาชน 13 หลัก'
--     AFTER `current_issue`;

-- Case B / safe fallback (no-op if already exists):
ALTER TABLE `request_internet_details`
    ADD COLUMN IF NOT EXISTS `citizen_id` VARCHAR(13) DEFAULT NULL
        COMMENT 'หมายเลขบัตรประชาชน 13 หลัก'
    AFTER `current_issue`;


-- ============================================================
-- 3. prefixes table: create + seed 73 records
-- ============================================================
CREATE TABLE IF NOT EXISTS `prefixes` (
    `prefix_id`     INT(11)      NOT NULL AUTO_INCREMENT,
    `prefix_name`   VARCHAR(100) NOT NULL,
    `prefix_short`  VARCHAR(50)  DEFAULT NULL,
    `prefix_type`   ENUM('general','military_army','military_navy',
                         'military_air','police','academic')
                                 DEFAULT 'general',
    `is_active`     TINYINT(1)   DEFAULT 1,
    `display_order` INT(11)      DEFAULT 0,
    PRIMARY KEY (`prefix_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='คำนำหน้าชื่อ';

-- INSERT IGNORE skips rows whose prefix_id already exists
INSERT IGNORE INTO `prefixes`
    (`prefix_id`, `prefix_name`, `prefix_short`, `prefix_type`, `is_active`, `display_order`)
VALUES
-- ทั่วไป
(1,  'นาย',                     'นาย',             'general',       1, 1),
(2,  'นาง',                     'นาง',             'general',       1, 2),
(3,  'นางสาว',                  'น.ส.',            'general',       1, 3),
(4,  'เด็กชาย',                 'ด.ช.',            'general',       1, 4),
(5,  'เด็กหญิง',                'ด.ญ.',            'general',       1, 5),
-- ทหารบก
(6,  'ว่าที่ร้อยตรี',           'ว่าที่ ร.ต.',     'military_army', 1, 10),
(7,  'ว่าที่ร้อยโท',            'ว่าที่ ร.ท.',     'military_army', 1, 11),
(8,  'ว่าที่ร้อยเอก',           'ว่าที่ ร.อ.',     'military_army', 1, 12),
(9,  'ว่าที่ร้อยตรีหญิง',       'ว่าที่ ร.ต.หญิง','military_army', 1, 13),
(10, 'ว่าที่ร้อยโทหญิง',        'ว่าที่ ร.ท.หญิง','military_army', 1, 14),
(11, 'ว่าที่ร้อยเอกหญิง',       'ว่าที่ ร.อ.หญิง','military_army', 1, 15),
(12, 'สิบตรี',                  'สิบตรี',          'military_army', 1, 20),
(13, 'สิบโท',                   'สิบโท',           'military_army', 1, 21),
(14, 'สิบเอก',                  'สิบเอก',          'military_army', 1, 22),
(15, 'สิบตรีหญิง',              'สิบตรีหญิง',      'military_army', 1, 23),
(16, 'สิบโทหญิง',               'สิบโทหญิง',       'military_army', 1, 24),
(17, 'สิบเอกหญิง',              'สิบเอกหญิง',      'military_army', 1, 25),
(18, 'จ่าสิบตรี',               'จ.ส.ต.',          'military_army', 1, 26),
(19, 'จ่าสิบโท',                'จ.ส.ท.',          'military_army', 1, 27),
(20, 'จ่าสิบเอก',               'จ.ส.อ.',          'military_army', 1, 28),
(21, 'จ่าสิบตรีหญิง',           'จ.ส.ต.หญิง',     'military_army', 1, 29),
(22, 'จ่าสิบโทหญิง',            'จ.ส.ท.หญิง',     'military_army', 1, 30),
(23, 'จ่าสิบเอกหญิง',           'จ.ส.อ.หญิง',     'military_army', 1, 31),
(24, 'ร้อยตรี',                 'ร.ต.',            'military_army', 1, 40),
(25, 'ร้อยโท',                  'ร.ท.',            'military_army', 1, 41),
(26, 'ร้อยเอก',                 'ร.อ.',            'military_army', 1, 42),
(27, 'ร้อยตรีหญิง',             'ร.ต.หญิง',       'military_army', 1, 43),
(28, 'ร้อยโทหญิง',              'ร.ท.หญิง',       'military_army', 1, 44),
(29, 'ร้อยเอกหญิง',             'ร.อ.หญิง',       'military_army', 1, 45),
(30, 'พันตรี',                  'พ.ต.',            'military_army', 1, 46),
(31, 'พันโท',                   'พ.ท.',            'military_army', 1, 47),
(32, 'พันเอก',                  'พ.อ.',            'military_army', 1, 48),
(33, 'พันตรีหญิง',              'พ.ต.หญิง',       'military_army', 1, 49),
(34, 'พันโทหญิง',               'พ.ท.หญิง',       'military_army', 1, 50),
(35, 'พันเอกหญิง',              'พ.อ.หญิง',       'military_army', 1, 51),
(36, 'พลตรี',                   'พล.ต.',           'military_army', 1, 52),
(37, 'พลโท',                    'พล.ท.',           'military_army', 1, 53),
(38, 'พลเอก',                   'พล.อ.',           'military_army', 1, 54),
-- ทหารเรือ
(39, 'นาวาตรี',                 'น.ต.',            'military_navy', 1, 60),
(40, 'นาวาโท',                  'น.ท.',            'military_navy', 1, 61),
(41, 'นาวาเอก',                 'น.อ.',            'military_navy', 1, 62),
(42, 'นาวาตรีหญิง',             'น.ต.หญิง',       'military_navy', 1, 63),
(43, 'นาวาโทหญิง',              'น.ท.หญิง',       'military_navy', 1, 64),
(44, 'นาวาเอกหญิง',             'น.อ.หญิง',       'military_navy', 1, 65),
(45, 'พันจ่าตรี',               'พ.จ.ต.',          'military_navy', 1, 66),
(46, 'พันจ่าโท',                'พ.จ.ท.',          'military_navy', 1, 67),
(47, 'พันจ่าเอก',               'พ.จ.อ.',          'military_navy', 1, 68),
-- ทหารอากาศ
(48, 'เรืออากาศตรี',            'ร.อ.',            'military_air',  1, 70),
(49, 'เรืออากาศโท',             'ร.ท.',            'military_air',  1, 71),
(50, 'เรืออากาศเอก',            'ร.อ.',            'military_air',  1, 72),
(51, 'เรืออากาศตรีหญิง',        'ร.อ.หญิง',       'military_air',  1, 73),
(52, 'เรืออากาศโทหญิง',         'ร.ท.หญิง',       'military_air',  1, 74),
(53, 'เรืออากาศเอกหญิง',        'ร.อ.หญิง',       'military_air',  1, 75),
-- ตำรวจ
(54, 'ด้านตำรวจ',               '',                'police',        1, 80),
(55, 'พลตำรวจ',                 'พล.ต.',           'police',        1, 81),
(56, 'ร้อยตำรวจตรี',            'ร.ต.ต.',          'police',        1, 82),
(57, 'ร้อยตำรวจโท',             'ร.ต.ท.',          'police',        1, 83),
(58, 'ร้อยตำรวจเอก',            'ร.ต.อ.',          'police',        1, 84),
(59, 'พันตำรวจตรี',             'พ.ต.ต.',          'police',        1, 85),
(60, 'พันตำรวจโท',              'พ.ต.ท.',          'police',        1, 86),
(61, 'พันตำรวจเอก',             'พ.ต.อ.',          'police',        1, 87),
(62, 'พลตำรวจตรี',              'พล.ต.ต.',         'police',        1, 88),
(63, 'พลตำรวจโท',               'พล.ต.ท.',         'police',        1, 89),
(64, 'พลตำรวจเอก',              'พล.ต.อ.',         'police',        1, 90),
-- วิชาการ
(65, 'ดร.',                     'ดร.',             'academic',      1, 100),
(66, 'ผู้ช่วยศาสตราจารย์',     'ผศ.',             'academic',      1, 101),
(67, 'รองศาสตราจารย์',          'รศ.',             'academic',      1, 102),
(68, 'ศาสตราจารย์',             'ศ.',              'academic',      1, 103),
(69, 'ผู้ช่วยศาสตราจารย์ ดร.', 'ผศ.ดร.',          'academic',      1, 104),
(70, 'รองศาสตราจารย์ ดร.',      'รศ.ดร.',          'academic',      1, 105),
(71, 'ศาสตราจารย์ ดร.',         'ศ.ดร.',           'academic',      1, 106),
(72, 'ศาสตราจารย์พิเศษ',       'ศ.พิเศษ',         'academic',      1, 107),
(73, 'ศาสตราจารย์กิตติคุณ',    'ศ.กิตติคุณ',      'academic',      1, 108);

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- VERIFY AFTER IMPORT (run these SELECT statements to confirm)
-- ============================================================
-- SELECT COUNT(*) FROM prefixes;                         -- expect 73
-- SHOW COLUMNS FROM service_requests LIKE 'requester_prefix_id';
-- SHOW COLUMNS FROM request_internet_details LIKE 'citizen_id';
-- ============================================================
