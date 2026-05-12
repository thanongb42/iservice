-- ตาราง API Keys สำหรับ PM2.5 Public API
CREATE TABLE IF NOT EXISTS `pm25_api_keys` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `api_key`     VARCHAR(64)  NOT NULL UNIQUE COMMENT 'Bearer token',
  `name`        VARCHAR(100) NOT NULL         COMMENT 'ชื่อระบบ/องค์กรที่ขอใช้',
  `description` VARCHAR(255) DEFAULT NULL,
  `expires_at`  DATE         DEFAULT NULL     COMMENT 'NULL = ไม่หมดอายุ',
  `is_active`   TINYINT(1)   NOT NULL DEFAULT 1,
  `last_used_at`DATETIME     DEFAULT NULL,
  `request_count` INT        NOT NULL DEFAULT 0,
  `created_at`  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
