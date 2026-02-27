-- Service Notification Emails
-- ตารางเก็บ email สำหรับแจ้งเตือนเมื่อมีคำขอบริการเข้ามา
-- แต่ละบริการสามารถมีหลาย email ที่ต้องการแจ้งเตือน

CREATE TABLE IF NOT EXISTS `service_notification_emails` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `service_id` int(11) NOT NULL COMMENT 'FK -> my_service.id',
  `email` varchar(255) NOT NULL COMMENT 'อีเมลที่ต้องแจ้งเตือน',
  `name` varchar(100) DEFAULT NULL COMMENT 'ชื่อผู้รับ',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1=เปิดใช้, 0=ปิด',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_service_email` (`service_id`, `email`),
  KEY `service_id` (`service_id`),
  CONSTRAINT `fk_sne_service` FOREIGN KEY (`service_id`) REFERENCES `my_service` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
