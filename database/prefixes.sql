-- Create prefixes table for title/rank
CREATE TABLE IF NOT EXISTS prefixes (
    prefix_id INT AUTO_INCREMENT PRIMARY KEY,
    prefix_name VARCHAR(100) NOT NULL,
    prefix_short VARCHAR(50),
    prefix_type ENUM('general', 'military_army', 'military_navy', 'military_air', 'police', 'academic') DEFAULT 'general',
    is_active TINYINT(1) DEFAULT 1,
    display_order INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert common prefixes
INSERT INTO prefixes (prefix_name, prefix_short, prefix_type, display_order) VALUES
-- General prefixes
('นาย', 'นาย', 'general', 1),
('นาง', 'นาง', 'general', 2),
('นางสาว', 'น.ส.', 'general', 3),
('เด็กชาย', 'ด.ช.', 'general', 4),
('เด็กหญิง', 'ด.ญ.', 'general', 5),

-- Military Army - ว่าที่ (Reserve)
('ว่าที่ร้อยตรี', 'ว่าที่ ร.ต.', 'military_army', 10),
('ว่าที่ร้อยโท', 'ว่าที่ ร.ท.', 'military_army', 11),
('ว่าที่ร้อยเอก', 'ว่าที่ ร.อ.', 'military_army', 12),
('ว่าที่ร้อยตรีหญิง', 'ว่าที่ ร.ต.หญิง', 'military_army', 13),
('ว่าที่ร้อยโทหญิง', 'ว่าที่ ร.ท.หญิง', 'military_army', 14),
('ว่าที่ร้อยเอกหญิง', 'ว่าที่ ร.อ.หญิง', 'military_army', 15),

-- Military Army - NCO
('สิบตรี', 'สิบตรี', 'military_army', 20),
('สิบโท', 'สิบโท', 'military_army', 21),
('สิบเอก', 'สิบเอก', 'military_army', 22),
('สิบตรีหญิง', 'สิบตรีหญิง', 'military_army', 23),
('สิบโทหญิง', 'สิบโทหญิง', 'military_army', 24),
('สิบเอกหญิง', 'สิบเอกหญิง', 'military_army', 25),
('จ่าสิบตรี', 'จ.ส.ต.', 'military_army', 26),
('จ่าสิบโท', 'จ.ส.ท.', 'military_army', 27),
('จ่าสิบเอก', 'จ.ส.อ.', 'military_army', 28),
('จ่าสิบตรีหญิง', 'จ.ส.ต.หญิง', 'military_army', 29),
('จ่าสิบโทหญิง', 'จ.ส.ท.หญิง', 'military_army', 30),
('จ่าสิบเอกหญิง', 'จ.ส.อ.หญิง', 'military_army', 31),

-- Military Army - Officers
('ร้อยตรี', 'ร.ต.', 'military_army', 40),
('ร้อยโท', 'ร.ท.', 'military_army', 41),
('ร้อยเอก', 'ร.อ.', 'military_army', 42),
('ร้อยตรีหญิง', 'ร.ต.หญิง', 'military_army', 43),
('ร้อยโทหญิง', 'ร.ท.หญิง', 'military_army', 44),
('ร้อยเอกหญิง', 'ร.อ.หญิง', 'military_army', 45),
('พันตรี', 'พ.ต.', 'military_army', 46),
('พันโท', 'พ.ท.', 'military_army', 47),
('พันเอก', 'พ.อ.', 'military_army', 48),
('พันตรีหญิง', 'พ.ต.หญิง', 'military_army', 49),
('พันโทหญิง', 'พ.ท.หญิง', 'military_army', 50),
('พันเอกหญิง', 'พ.อ.หญิง', 'military_army', 51),
('พลตรี', 'พล.ต.', 'military_army', 52),
('พลโท', 'พล.ท.', 'military_army', 53),
('พลเอก', 'พล.อ.', 'military_army', 54),

-- Military Navy
('นาวาตรี', 'น.ต.', 'military_navy', 60),
('นาวาโท', 'น.ท.', 'military_navy', 61),
('นาวาเอก', 'น.อ.', 'military_navy', 62),
('นาวาตรีหญิง', 'น.ต.หญิง', 'military_navy', 63),
('นาวาโทหญิง', 'น.ท.หญิง', 'military_navy', 64),
('นาวาเอกหญิง', 'น.อ.หญิง', 'military_navy', 65),
('พันจ่าตรี', 'พ.จ.ต.', 'military_navy', 66),
('พันจ่าโท', 'พ.จ.ท.', 'military_navy', 67),
('พันจ่าเอก', 'พ.จ.อ.', 'military_navy', 68),

-- Military Air Force
('เรืออากาศตรี', 'ร.อ.', 'military_air', 70),
('เรืออากาศโท', 'ร.ท.', 'military_air', 71),
('เรืออากาศเอก', 'ร.อ.', 'military_air', 72),
('เรืออากาศตรีหญิง', 'ร.อ.หญิง', 'military_air', 73),
('เรืออากาศโทหญิง', 'ร.ท.หญิง', 'military_air', 74),
('เรืออากาศเอกหญิง', 'ร.อ.หญิง', 'military_air', 75),

-- Police
('ด้านตำรวจ', NULL, 'police', 80),
('พลตำรวจ', 'พล.ต.', 'police', 81),
('ร้อยตำรวจตรี', 'ร.ต.ต.', 'police', 82),
('ร้อยตำรวจโท', 'ร.ต.ท.', 'police', 83),
('ร้อยตำรวจเอก', 'ร.ต.อ.', 'police', 84),
('พันตำรวจตรี', 'พ.ต.ต.', 'police', 85),
('พันตำรวจโท', 'พ.ต.ท.', 'police', 86),
('พันตำรวจเอก', 'พ.ต.อ.', 'police', 87),
('พลตำรวจตรี', 'พล.ต.ต.', 'police', 88),
('พลตำรวจโท', 'พล.ต.ท.', 'police', 89),
('พลตำรวจเอก', 'พล.ต.อ.', 'police', 90),

-- Academic
('ดร.', 'ดร.', 'academic', 100),
('ผู้ช่วยศาสตราจารย์', 'ผศ.', 'academic', 101),
('รองศาสตราจารย์', 'รศ.', 'academic', 102),
('ศาสตราจารย์', 'ศ.', 'academic', 103),
('ผู้ช่วยศาสตราจารย์ ดร.', 'ผศ.ดร.', 'academic', 104),
('รองศาสตราจารย์ ดร.', 'รศ.ดร.', 'academic', 105),
('ศาสตราจารย์ ดร.', 'ศ.ดร.', 'academic', 106),
('ศาสตราจารย์พิเศษ', 'ศ.พิเศษ', 'academic', 107),
('ศาสตราจารย์กิตติคุณ', 'ศ.กิตติคุณ', 'academic', 108);

-- Add index
CREATE INDEX idx_prefix_type ON prefixes(prefix_type, is_active);

-- Note: prefix_id field will be added to users table when running users.sql
