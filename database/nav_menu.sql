-- ====================================
-- Nav Menu Management System
-- ====================================
-- สร้างตาราง nav_menu สำหรับจัดการเมนูแบบ dynamic
-- รองรับ parent-child menu structure
-- ====================================

CREATE TABLE IF NOT EXISTS `nav_menu` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `parent_id` (`parent_id`),
  KEY `is_active` (`is_active`),
  KEY `menu_order` (`menu_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================
-- Insert ข้อมูลเริ่มต้น (Sample Data)
-- ====================================

-- Parent Menu #1: หน้าแรก
INSERT INTO `nav_menu` (`parent_id`, `menu_name`, `menu_name_en`, `menu_url`, `menu_icon`, `menu_order`, `is_active`) VALUES
(NULL, 'หน้าแรก', 'Home', 'index.php', NULL, 1, 1);

-- Parent Menu #2: บริการออนไลน์
INSERT INTO `nav_menu` (`parent_id`, `menu_name`, `menu_name_en`, `menu_url`, `menu_icon`, `menu_order`, `is_active`) VALUES
(NULL, 'บริการออนไลน์', 'Online Services', '#', 'fas fa-globe', 2, 1);

-- Child Menu ของ บริการออนไลน์
SET @parent_service = LAST_INSERT_ID();
INSERT INTO `nav_menu` (`parent_id`, `menu_name`, `menu_name_en`, `menu_url`, `menu_icon`, `menu_order`, `is_active`) VALUES
(@parent_service, 'แจ้งซ่อมคอมพิวเตอร์', 'IT Support', 'it-support.php', 'fas fa-tools', 1, 1),
(@parent_service, 'ขอใช้อินเทอร์เน็ต', 'Internet Request', 'internet-request.php', 'fas fa-wifi', 2, 1),
(@parent_service, 'ขอพื้นที่เก็บข้อมูล', 'Storage Request', 'storage-request.php', 'fas fa-hdd', 3, 1);

-- Parent Menu #3: คู่มือการใช้งาน
INSERT INTO `nav_menu` (`parent_id`, `menu_name`, `menu_name_en`, `menu_url`, `menu_icon`, `menu_order`, `is_active`) VALUES
(NULL, 'คู่มือการใช้งาน', 'User Manual', '#', 'fas fa-book', 3, 1);

-- Child Menu ของ คู่มือการใช้งาน
SET @parent_manual = LAST_INSERT_ID();
INSERT INTO `nav_menu` (`parent_id`, `menu_name`, `menu_name_en`, `menu_url`, `menu_icon`, `menu_order`, `is_active`) VALUES
(@parent_manual, 'Email เทศบาล', 'Email', 'manual-email.php', 'fas fa-envelope', 1, 1),
(@parent_manual, 'NAS', 'NAS', 'manual-nas.php', 'fas fa-server', 2, 1),
(@parent_manual, 'Internet', 'Internet', 'manual-internet.php', 'fas fa-globe', 3, 1);

-- Parent Menu #4: ติดต่อเรา
INSERT INTO `nav_menu` (`parent_id`, `menu_name`, `menu_name_en`, `menu_url`, `menu_icon`, `menu_order`, `is_active`) VALUES
(NULL, 'ติดต่อเรา', 'Contact Us', 'contact.php', NULL, 4, 1);

-- ====================================
-- Query ตัวอย่างสำหรับดึงข้อมูล Menu
-- ====================================

-- Query 1: ดึง Parent Menu ทั้งหมด (เรียงตาม menu_order)
-- SELECT * FROM nav_menu WHERE parent_id IS NULL AND is_active = 1 ORDER BY menu_order ASC;

-- Query 2: ดึง Child Menu ของ Parent ที่กำหนด (ตัวอย่าง parent_id = 2)
-- SELECT * FROM nav_menu WHERE parent_id = 2 AND is_active = 1 ORDER BY menu_order ASC;

-- Query 3: ดึง Menu แบบ Hierarchical (JOIN)
-- SELECT
--     p.id as parent_id,
--     p.menu_name as parent_name,
--     p.menu_url as parent_url,
--     c.id as child_id,
--     c.menu_name as child_name,
--     c.menu_url as child_url,
--     c.menu_icon as child_icon
-- FROM nav_menu p
-- LEFT JOIN nav_menu c ON p.id = c.parent_id AND c.is_active = 1
-- WHERE p.parent_id IS NULL AND p.is_active = 1
-- ORDER BY p.menu_order ASC, c.menu_order ASC;
