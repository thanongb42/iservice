-- Job Notification Log
-- รันบน production DB ครั้งเดียว
CREATE TABLE IF NOT EXISTS `job_notification_log` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `job_id`      INT NOT NULL,
  `notify_type` ENUM('day_before','one_hour','on_start') NOT NULL,
  `sent_at`     DATETIME DEFAULT CURRENT_TIMESTAMP,
  `success`     TINYINT(1) DEFAULT 0,
  `error_msg`   VARCHAR(255) NULL,
  UNIQUE KEY `uq_job_notify` (`job_id`, `notify_type`),
  KEY `idx_job_id` (`job_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
