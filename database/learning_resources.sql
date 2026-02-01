-- ====================================
-- Learning Resources Management System
-- ====================================
-- สร้างตาราง learning_resources สำหรับศูนย์รวมการเรียนรู้
-- รองรับ PDF, Video, Podcast, Blog, Source Code
-- ====================================

CREATE TABLE IF NOT EXISTS `learning_resources` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `resource_type` (`resource_type`),
  KEY `category` (`category`),
  KEY `is_active` (`is_active`),
  KEY `is_featured` (`is_featured`),
  KEY `display_order` (`display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================
-- Insert ข้อมูลเริ่มต้น (Sample Data)
-- ====================================

INSERT INTO `learning_resources` (`title`, `description`, `resource_type`, `resource_url`, `cover_image`, `category`, `author`, `duration`, `file_size`, `tags`, `is_featured`, `display_order`) VALUES
-- PDF Documents
('คู่มือการใช้งานอีเมลเทศบาล', 'คู่มือฉบับสมบูรณ์สำหรับการใช้งานระบบอีเมลของเทศบาล รวมถึงการตั้งค่าบนมือถือและคอมพิวเตอร์', 'pdf', 'documents/email-manual.pdf', 'https://via.placeholder.com/400x300/3B82F6/FFFFFF?text=Email+Manual', 'คู่มือ', 'ฝ่าย IT', NULL, '2.5 MB', 'email,คู่มือ,การใช้งาน', 1, 1),

('คู่มือการเชื่อมต่อ WiFi', 'วิธีการเชื่อมต่อ WiFi เทศบาล พร้อมการแก้ไขปัญหาเบื้องต้น', 'pdf', 'documents/wifi-guide.pdf', 'https://via.placeholder.com/400x300/8B5CF6/FFFFFF?text=WiFi+Guide', 'คู่มือ', 'ฝ่าย IT', NULL, '1.8 MB', 'wifi,internet,การเชื่อมต่อ', 0, 2),

-- Videos
('วิธีการใช้งาน NAS Storage', 'สอนการใช้งานระบบ NAS เพื่อจัดเก็บและแชร์ไฟล์ภายในองค์กร', 'video', 'videos/nas-tutorial.mp4', 'https://via.placeholder.com/400x300/EC4899/FFFFFF?text=NAS+Tutorial', 'หลักสูตร', 'ทีม IT Support', '15:30', NULL, 'nas,storage,tutorial', 1, 3),

('การสร้าง QR Code ด้วยระบบของเทศบาล', 'คลิปสอนการใช้งานระบบสร้าง QR Code สำหรับประชาสัมพันธ์โครงการ', 'video', 'videos/qrcode-howto.mp4', 'https://via.placeholder.com/400x300/F59E0B/FFFFFF?text=QR+Code', 'หลักสูตร', 'ฝ่ายประชาสัมพันธ์', '8:45', NULL, 'qrcode,tutorial,วิธีใช้', 0, 4),

-- YouTube Videos
('PHP Programming สำหรับมือใหม่', 'หลักสูตร PHP เบื้องต้นสำหรับพัฒนาเว็บไซต์', 'youtube', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'https://via.placeholder.com/400x300/EF4444/FFFFFF?text=PHP+Course', 'หลักสูตร', 'Code Academy', '2:30:00', NULL, 'php,programming,course', 1, 5),

-- Podcasts
('IT Talk: เทคนิคการรักษาความปลอดภัยข้อมูล', 'Podcast เกี่ยวกับการรักษาความปลอดภัยของข้อมูลในยุคดิจิทัล', 'podcast', 'podcasts/security-tips.mp3', 'https://via.placeholder.com/400x300/10B981/FFFFFF?text=Security+Podcast', 'Podcast', 'ฝ่าย IT Security', '25:15', '45 MB', 'security,podcast,ความปลอดภัย', 0, 6),

-- Blog Articles
('10 เคล็ดลับการใช้คอมพิวเตอร์อย่างปลอดภัย', 'บทความแนะนำวิธีการใช้งานคอมพิวเตอร์ให้ปลอดภัยจากมัลแวร์และแฮกเกอร์', 'blog', 'blog-detail.php?id=1', 'https://via.placeholder.com/400x300/6366F1/FFFFFF?text=Security+Tips', 'บทความ', 'Admin IT', NULL, NULL, 'security,tips,คอมพิวเตอร์', 0, 7),

-- Flipbook
('รายงานประจำปี IT 2567', 'เอกสารรายงานผลการดำเนินงานด้าน IT ประจำปี 2567 แบบ Flipbook', 'flipbook', 'flipbook/annual-report-2024.html', 'https://via.placeholder.com/400x300/14B8A6/FFFFFF?text=Annual+Report', 'รายงาน', 'ฝ่าย IT', NULL, NULL, 'รายงาน,annual,2567', 1, 8),

-- Source Code
('Source Code: ระบบจองห้องประชุม', 'โค้ดตัวอย่างระบบจองห้องประชุมออนไลน์ พร้อมเอกสารประกอบ', 'sourcecode', 'https://github.com/example/meeting-room', 'https://via.placeholder.com/400x300/A855F7/FFFFFF?text=Source+Code', 'Source Code', 'Dev Team', NULL, NULL, 'sourcecode,php,javascript', 0, 9),

-- YouTube Playlist
('หลักสูตร Microsoft Office ฉบับสมบูรณ์', 'รวมคลิปสอน Word, Excel, PowerPoint สำหรับงานสำนักงาน', 'youtube', 'https://www.youtube.com/playlist?list=PLxxxxxx', 'https://via.placeholder.com/400x300/F97316/FFFFFF?text=Office+Course', 'หลักสูตร', 'Microsoft Thailand', '5:00:00', NULL, 'office,word,excel,powerpoint', 1, 10);

-- ====================================
-- Resource Type Reference
-- ====================================
-- pdf: ไฟล์ PDF เอกสาร
-- video: วิดีโอไฟล์ (MP4, etc.)
-- podcast: ไฟล์เสียง (MP3, etc.)
-- blog: บทความ Blog
-- sourcecode: Source Code/GitHub
-- youtube: YouTube Video/Playlist
-- flipbook: PDF Flipbook

-- ====================================
-- Query ตัวอย่าง
-- ====================================

-- Query 1: ดึงทรัพยากรที่เปิดใช้งานทั้งหมด
-- SELECT * FROM learning_resources WHERE is_active = 1 ORDER BY display_order ASC;

-- Query 2: ดึงทรัพยากรแนะนำ
-- SELECT * FROM learning_resources WHERE is_active = 1 AND is_featured = 1 ORDER BY display_order ASC;

-- Query 3: ดึงตามประเภท
-- SELECT * FROM learning_resources WHERE is_active = 1 AND resource_type = 'video' ORDER BY display_order ASC;

-- Query 4: ค้นหาตาม Tags
-- SELECT * FROM learning_resources WHERE is_active = 1 AND tags LIKE '%tutorial%' ORDER BY display_order ASC;

-- Query 5: เพิ่ม view count
-- UPDATE learning_resources SET view_count = view_count + 1 WHERE id = ?;
