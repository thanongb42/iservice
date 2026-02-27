-- =====================================================
-- SQL Script: จุดติดตั้งตู้น้ำดื่ม เทศบาลเมืองรังสิต
-- Database: iservice_db
-- =====================================================

CREATE TABLE IF NOT EXISTS `water_kiosks` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `kiosk_code` VARCHAR(20) NOT NULL COMMENT 'รหัสตู้น้ำ',
  `location_name` VARCHAR(255) NOT NULL COMMENT 'สถานที่ติดตั้ง',
  `kiosk_count` INT(11) DEFAULT 1 COMMENT 'จำนวนตู้',
  `latitude` DECIMAL(15,12) NOT NULL,
  `longitude` DECIMAL(15,12) NOT NULL,
  `status` ENUM('active','inactive','maintenance') DEFAULT 'active',
  `qr_code_link` VARCHAR(500) DEFAULT NULL COMMENT 'ลิงก์ QR Code',
  `installed_date` DATE DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_kiosk_code` (`kiosk_code`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='จุดติดตั้งตู้น้ำดื่ม เทศบาลเมืองรังสิต';

INSERT INTO `water_kiosks` (`kiosk_code`, `location_name`, `kiosk_count`, `latitude`, `longitude`, `status`, `qr_code_link`) VALUES
('RSC0001', 'โถงชั้น 1 หน้าห้อง RSSC สำนักงานเทศบาลรังสิต', 1, 13.987992927445, 100.60951463888, 'active', 'https://drive.google.com/file/d/1xsGH5l7z8d0kJnSF1g7qvfMR55Iaczz7/view?usp=sharing'),
('RSC0002', 'โรงเรียนมัธยมนครรังสิต', 1, 13.980344741823, 100.65586465155, 'active', 'https://drive.google.com/file/d/1TKd7kV9gGRc_QwKWCMPhhmGsgWVNnAEe/view?usp=drive_link'),
('RSC0003', 'โรงเรียนดวงกมล', 1, 13.984674615799, 100.63959619971, 'active', 'https://drive.google.com/file/d/1naNd1GxC3Y_14TD9OIvFddkm6F-5RlpH/view?usp=drive_link'),
('RSC0004', 'ตลาดรังสิต', 1, 13.985252418403, 100.61410188675, 'active', 'https://drive.google.com/file/d/1uREXE2G62H16YwB6RVul-hufFos_NY_D/view?usp=drive_link'),
('RSC0005', 'บ้านเอื้ออาทรรังสิต คลอง 1', 1, 13.985793779964, 100.62856435776, 'active', 'https://drive.google.com/file/d/1siYXCJTbnB0jVtG1PzVCshw92ffiBahQ/view?usp=drive_link'),
('RSC0006', 'ศูนย์สาธารณสุขชุมชน 1', 1, 13.97371695013, 100.61096906662, 'active', 'https://drive.google.com/file/d/18cUHw6wcAWyCIoYk3mZMyxg08CCNlGWh/view?usp=drive_link'),
('RSC0007', 'ศูนย์สาธารณสุขชุมชน 2', 1, 13.992040167351, 100.60929536819, 'active', 'https://drive.google.com/file/d/19NU3s6yl9mbQ10E84iYTCECnX27RC9e5/view?usp=drive_link'),
('RSC0008', 'ศูนย์สาธารณสุขชุมชน 3', 1, 13.975257822306, 100.62556028366, 'active', 'https://drive.google.com/file/d/1qeIGVBEPK8vsGgS3BJ-3OiC3qbJwyR6J/view?usp=drive_link'),
('RSC0009', 'ศูนย์สาธารณสุขชุมชน 4', 1, 13.992685617709, 100.62419772148, 'active', 'https://drive.google.com/file/d/1nxHY6FGN4O4yFShegpMX4ZIp72frEHJu/view?usp=drive_link'),
('RSC0010', 'สถานีตำรวจภูธรประตูน้ำจุฬาลงกรณ์', 1, 13.98479434224, 100.61255693436, 'active', 'https://drive.google.com/file/d/1jwDuNc3hLPZndtlOFVasFGFhBgHDe7Fl/view?usp=drive_link'),
('RSC0011', 'อาคารอเนกประสงค์ ชุมชนรันตโกสินทร์ 200 ปี', 1, 13.992165093368, 100.60458004475, 'active', 'https://drive.google.com/file/d/15JCyziqX_0HjRUgZVMhhO2-Nxk_Xx7tV/view?usp=sharing'),
('RSC0012', 'จุดศาลาใหญ่ตรงข้ามโรงเรียนชุมชนประชาธิปัตย์วิทยาคาร', 1, 13.98295695429, 100.61174137527, 'active', 'https://drive.google.com/file/d/1MM7hb78Pw0AVrg5mM3lN2Cr4YdX7_ptT/view?usp=sharing'),
('RSC0013', 'วัดคลอง 1 แก้วนิมิต', 1, 13.971009987555, 100.62478780746, 'active', 'https://drive.google.com/file/d/1px3TyP2yqNt8vN2d_II7hSPw5_UWyfQB/view?usp=drive_link'),
('RSC0014', 'ศูนย์นันทนาการ 200 ปี', 1, 13.99232645604, 100.60933560133, 'active', 'https://drive.google.com/file/d/1KhTVrK-MLgNxw0bRIGpJWi2joKWrALbn/view?usp=drive_link'),
('RSC0015', 'สระว่ายน้ำเทศบาลนครรังสิต', 1, 13.990911296582, 100.60457199821, 'active', 'https://drive.google.com/file/d/1xDS0eZQDCvKKdACle758q0RKgu6N1bzr/view?usp=drive_link'),
('RSC0016', 'จุดติดตั้งของชุมชนสร้างสรรค์นครรังสิต', 1, 13.979317909119, 100.63099351721, 'active', 'https://drive.google.com/file/d/1SjLWB6rTXhxMPJ0BOAGcSYl5cHwnY2fT/view?usp=drive_link'),
('RSC0017', 'อาคารอเนกประสงค์ชุมชนซอยดี', 1, 14.000451700906, 100.6446146965, 'active', 'https://drive.google.com/file/d/1nmqnRISP94o7aEv3QtCTHPNpdhun4ixi/view?usp=drive_link'),
('RSC0018', 'อาคารอเนกประสงค์ชุมชนศรีประจักษ์', 1, 14.00224223514, 100.65045118332, 'active', 'https://drive.google.com/file/d/1xT3hJTh0qU9Yrmvknn7mBNB7Y7dGOyj8/view?usp=drive_link'),
('RSC0019', 'อาคารอเนกประสงค์ชุมชนอยู่เจริญ', 1, 13.999035919774, 100.6524682045, 'active', 'https://drive.google.com/file/d/1ltGNFO2wd5Tv6s9Had9lQSMJ56wGFXGT/view?usp=drive_link'),
('RSC0020', 'อาคารอเนกประสงค์ชุมชนสินสมุทร', 1, 13.973300496421, 100.60766458511, 'active', 'https://drive.google.com/file/d/1JhKrz-Rz2WXWlPqFMQFvB1cGEGwKu74H/view?usp=drive_link'),
('RSC0021', 'อาคารอเนกประสงค์ชุมชนภักดีราชา', 1, 14.005884979864, 100.65008573243, 'active', 'https://drive.google.com/file/d/1_ioqXj0AWS8NuHhmjedSe6l0WnJcHIPR/view?usp=drive_link'),
('RSC0022', 'อาคารอเนกประสงค์ชุมชนซอยเกลียวทอง', 1, 14.004324234145, 100.64736127853, 'active', 'https://drive.google.com/file/d/1jMJMMXkvjotgR29QUuZmx2GjlAMyfObR/view?usp=drive_link'),
('RSC0023', 'อาคารอเนกประสงค์ชุมชนหมู่บ้านวรุณพร', 1, 13.991166399805, 100.65485537062, 'active', 'https://drive.google.com/file/d/1aIT3NO8QRkxxXyQ_GLpo36rpp-HbhWDN/view?usp=drive_link'),
('RSC0024', 'อาคารอเนกประสงค์ชุมชนหมู่บ้านพงษ์ศิริ', 1, 13.99542314933, 100.65809564674, 'active', 'https://drive.google.com/file/d/1f5pVpuEzG0qHmCenO8qY-YuwuCMvk84m/view?usp=drive_link'),
('RSC0025', 'อาคารอเนกประสงค์ชุมชนกรุงเทพเมืองใหม่', 1, 13.976257301446, 100.61611890793, 'active', 'https://drive.google.com/file/d/13xuJXGwgy_LbVh7-4ySAnNwdltDR_cDf/view?usp=drive_link'),
('RSC0026', 'ชุมชนหมู่บ้านฟ้าลากูน', 1, 13.980505039378, 100.6405377388, 'active', 'https://drive.google.com/file/d/1oUVnG4GF8TdDG8xlGJXuadWdzgy_cofY/view?usp=drive_link'),
('RSC0027', 'อาคารอเนกประสงค์ชุมชนหมู่บ้านเปรมปรีด์คันทรีโฮม', 1, 13.982868650941, 100.6544813701, 'active', 'https://drive.google.com/file/d/1JYGouSa2ZjltAFqduP4soewug-ULk086/view?usp=drive_link'),
('RSC0028', 'อาคารอเนกประสงค์ชุมชนรัตนปทุม', 1, 13.995437992574, 100.64054237518, 'active', 'https://drive.google.com/file/d/1vo42zusY_Uep27OXawoDYnmPVnjOIEnu/view?usp=drive_link'),
('RSC0029', 'บ้านเอื้ออาทรรังสิต คลอง 1 ( ศูนย์การศึกษาพิเศษ ประจำจังหวัดปทุมธานี )', 1, 13.979156568949, 100.62897292674, 'active', 'https://drive.google.com/file/d/1208FEZ9kGg194IwnIV4e-IAcVOljGE7r/view?usp=drive_link'),
('RSC0030', 'บ้านเอื้ออาทรรังสิต คลอง 1 ( จุดติดตั้งอาคาร 40)', 1, 13.975524050197, 100.6294652448, 'active', 'https://drive.google.com/file/d/1PngoyfxjahdpZDZVg_WwChNcyQfwBxzy/view?usp=drive_link');