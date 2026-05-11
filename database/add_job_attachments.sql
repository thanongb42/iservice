-- Job Attachments: ไฟล์แนบ / URL สำหรับ internal_jobs
-- รันบน production DB ครั้งเดียว
CREATE TABLE IF NOT EXISTS `job_attachments` (
  `id`            INT AUTO_INCREMENT PRIMARY KEY,
  `job_id`        INT NOT NULL,
  `attach_type`   ENUM('file','url') NOT NULL DEFAULT 'file',
  `label`         VARCHAR(500) NULL COMMENT 'ชื่อไฟล์ต้นฉบับ หรือ ชื่อลิงก์',
  `file_path`     VARCHAR(500) NULL COMMENT 'path สัมพัทธ์จาก root เช่น storage/uploads/jobs/xxx.pdf',
  `url`           TEXT NULL COMMENT 'URL สำหรับประเภท url',
  `mime_type`     VARCHAR(100) NULL,
  `file_size`     INT NULL COMMENT 'bytes',
  `uploaded_by`   INT NOT NULL,
  `created_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_job_id` (`job_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
