-- =============================================================
-- FIX nav_menu: Primary Key, Duplicates, Menu Order
-- รันบน phpMyAdmin ทีเดียวจบ
-- Database: rangsitadmin_iservice_db
-- =============================================================

-- -----------------------------------------------
-- STEP 1: ลบ "การใช้งาน Email" ที่ซ้ำ (เก็บไว้ 1 ตัว)
-- มี 7 rows ซ้ำ (id=0, parent_id=6, menu_name='การใช้งาน Email')
-- ลบทั้งหมดก่อน แล้ว INSERT กลับ 1 ตัว
-- -----------------------------------------------
DELETE FROM `nav_menu`
WHERE `parent_id` = 6
  AND `menu_name` = 'การใช้งาน Email';

-- INSERT กลับ 1 ตัว (ยังใช้ id=0 ชั่วคราว จะแก้ใน STEP 2)
INSERT INTO `nav_menu` (`id`, `parent_id`, `menu_name`, `menu_name_en`, `menu_url`, `menu_icon`, `menu_order`, `is_active`, `target`, `description`)
VALUES (0, 6, 'การใช้งาน Email', 'Email User Manual', 'http://iservice.rangsitcity.go.th/resource-detail.php?id=1', 'fas fa-at', 3, 1, '_self', '');

-- -----------------------------------------------
-- STEP 2: แก้ id column ให้เป็น PRIMARY KEY + AUTO_INCREMENT
-- -----------------------------------------------

-- 2.1 สร้างตาราง temp เก็บข้อมูลทั้งหมด (เรียงตาม parent แล้ว order)
CREATE TEMPORARY TABLE `nav_menu_backup` AS
SELECT * FROM `nav_menu`
ORDER BY
    CASE WHEN `parent_id` IS NULL THEN 0 ELSE 1 END,
    COALESCE(`parent_id`, 0),
    `menu_order` ASC,
    `created_at` ASC;

-- 2.2 ลบข้อมูลเดิมทั้งหมด
TRUNCATE TABLE `nav_menu`;

-- 2.3 แก้ column id ให้เป็น AUTO_INCREMENT + PRIMARY KEY
ALTER TABLE `nav_menu`
    MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT,
    ADD PRIMARY KEY (`id`);

-- 2.4 INSERT ข้อมูลกลับ (ไม่ใส่ id ให้ auto_increment สร้างให้ใหม่)
INSERT INTO `nav_menu` (`parent_id`, `menu_name`, `menu_name_en`, `menu_url`, `menu_icon`, `menu_order`, `is_active`, `target`, `description`, `created_at`, `updated_at`)
SELECT `parent_id`, `menu_name`, `menu_name_en`, `menu_url`, `menu_icon`, `menu_order`, `is_active`, `target`, `description`, `created_at`, `updated_at`
FROM `nav_menu_backup`
ORDER BY
    CASE WHEN `parent_id` IS NULL THEN 0 ELSE 1 END,
    COALESCE(`parent_id`, 0),
    `menu_order` ASC,
    `created_at` ASC;

-- 2.5 ลบ temp table
DROP TEMPORARY TABLE `nav_menu_backup`;

-- -----------------------------------------------
-- STEP 3: แก้ parent_id ให้ชี้ไปที่ id ใหม่ที่ถูกต้อง
-- เนื่องจาก id เปลี่ยนแล้ว ต้อง map parent_id ใหม่
-- -----------------------------------------------

-- สร้างตาราง mapping: ชื่อเมนู parent -> id ใหม่
-- Parent menus ที่มี children:
--   "บริการออนไลน์" (เดิม id=2)
--   "คู่มือการใช้งาน" (เดิม id=6)
--   "ติดต่อเรา" (เดิม id=10)

-- Update children ของ "บริการออนไลน์"
UPDATE `nav_menu` c
INNER JOIN `nav_menu` p ON p.`menu_name` = 'บริการออนไลน์' AND p.`parent_id` IS NULL
SET c.`parent_id` = p.`id`
WHERE c.`parent_id` IS NOT NULL
  AND c.`menu_name` IN ('แจ้งซ่อมคอมพิวเตอร์', 'ขอใช้อินเทอร์เน็ต', 'ขอพื้นที่เก็บข้อมูล', 'Email เทศบาล', 'ขอบริการพิธีกร', 'บริการช่างภาพ');

-- Update children ของ "คู่มือการใช้งาน"
UPDATE `nav_menu` c
INNER JOIN `nav_menu` p ON p.`menu_name` = 'คู่มือการใช้งาน' AND p.`parent_id` IS NULL
SET c.`parent_id` = p.`id`
WHERE c.`parent_id` IS NOT NULL
  AND c.`menu_name` IN ('NAS', 'Internet', 'การใช้งาน Email');

-- Update children ของ "ติดต่อเรา"
UPDATE `nav_menu` c
INNER JOIN `nav_menu` p ON p.`menu_name` = 'ติดต่อเรา' AND p.`parent_id` IS NULL
SET c.`parent_id` = p.`id`
WHERE c.`parent_id` IS NOT NULL
  AND c.`menu_name` IN ('แผนที่ที่ตั้ง');

-- -----------------------------------------------
-- STEP 4: จัดลำดับ menu_order ใหม่ (1,2,3...) แยกแต่ละกลุ่ม
-- -----------------------------------------------

-- 4.1 Fix parent menus (1,2,3,4)
SET @ord = 0;
UPDATE `nav_menu`
SET `menu_order` = (@ord := @ord + 1)
WHERE `parent_id` IS NULL
ORDER BY `menu_order` ASC, `id` ASC;

-- 4.2 Fix children ของ "บริการออนไลน์" (1,2,3,4,5,6)
SET @ord = 0;
UPDATE `nav_menu` c
INNER JOIN `nav_menu` p ON p.`menu_name` = 'บริการออนไลน์' AND p.`parent_id` IS NULL
SET c.`menu_order` = (@ord := @ord + 1)
WHERE c.`parent_id` = p.`id`
ORDER BY c.`menu_order` ASC, c.`id` ASC;

-- 4.3 Fix children ของ "คู่มือการใช้งาน" (1,2,3)
SET @ord = 0;
UPDATE `nav_menu` c
INNER JOIN `nav_menu` p ON p.`menu_name` = 'คู่มือการใช้งาน' AND p.`parent_id` IS NULL
SET c.`menu_order` = (@ord := @ord + 1)
WHERE c.`parent_id` = p.`id`
ORDER BY c.`menu_order` ASC, c.`id` ASC;

-- 4.4 Fix children ของ "ติดต่อเรา" (1)
SET @ord = 0;
UPDATE `nav_menu` c
INNER JOIN `nav_menu` p ON p.`menu_name` = 'ติดต่อเรา' AND p.`parent_id` IS NULL
SET c.`menu_order` = (@ord := @ord + 1)
WHERE c.`parent_id` = p.`id`
ORDER BY c.`menu_order` ASC, c.`id` ASC;

-- -----------------------------------------------
-- STEP 5: ตรวจสอบผลลัพธ์
-- -----------------------------------------------
SELECT
    p.`id` AS parent_id,
    p.`menu_name` AS parent_name,
    p.`menu_order` AS parent_order,
    c.`id` AS child_id,
    c.`menu_name` AS child_name,
    c.`menu_order` AS child_order
FROM `nav_menu` p
LEFT JOIN `nav_menu` c ON c.`parent_id` = p.`id`
WHERE p.`parent_id` IS NULL
ORDER BY p.`menu_order`, c.`menu_order`;
