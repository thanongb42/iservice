-- =============================================================
-- internal_jobs — Manager-created standalone jobs (routine/recurring)
-- ไม่ขึ้นกับ service_requests
-- =============================================================

CREATE TABLE IF NOT EXISTS `internal_jobs` (
    `job_id`            INT(11)       NOT NULL AUTO_INCREMENT,
    `job_code`          VARCHAR(20)   NOT NULL COMMENT 'e.g. JOB-2026-0001',
    `title`             VARCHAR(255)  NOT NULL,
    `description`       TEXT          DEFAULT NULL,
    `job_type`          ENUM('routine','event','project','maintenance','meeting','other') NOT NULL DEFAULT 'routine',
    `priority`          ENUM('low','normal','high','urgent')                              NOT NULL DEFAULT 'normal',
    `status`            ENUM('scheduled','in_progress','completed','cancelled')           NOT NULL DEFAULT 'scheduled',
    `assigned_to`       INT(11)       DEFAULT NULL  COMMENT 'FK → users (nullable)',
    `assigned_by`       INT(11)       NOT NULL      COMMENT 'FK → users (manager)',
    `department_id`     INT(11)       DEFAULT NULL,
    `scheduled_date`    DATE          DEFAULT NULL  COMMENT 'วันที่กำหนดในปฏิทิน',
    `start_time`        TIME          DEFAULT NULL,
    `end_time`          TIME          DEFAULT NULL,
    `due_date`          DATETIME      DEFAULT NULL  COMMENT 'deadline',
    `location`          VARCHAR(255)  DEFAULT NULL,
    `notes`             TEXT          DEFAULT NULL,
    `completion_notes`  TEXT          DEFAULT NULL,
    `accepted_at`       DATETIME      DEFAULT NULL,
    `started_at`        DATETIME      DEFAULT NULL,
    `completed_at`      DATETIME      DEFAULT NULL,
    `created_at`        TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`        TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`job_id`),
    UNIQUE KEY `uq_job_code` (`job_code`),
    KEY `idx_ij_date`        (`scheduled_date`),
    KEY `idx_ij_assigned_to` (`assigned_to`),
    KEY `idx_ij_assigned_by` (`assigned_by`),
    KEY `idx_ij_status`      (`status`),
    KEY `idx_ij_dept`        (`department_id`),
    CONSTRAINT `fk_ij_assigned_to` FOREIGN KEY (`assigned_to`)   REFERENCES `users`       (`user_id`)       ON DELETE SET NULL,
    CONSTRAINT `fk_ij_assigned_by` FOREIGN KEY (`assigned_by`)   REFERENCES `users`       (`user_id`),
    CONSTRAINT `fk_ij_dept`        FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='งานประจำ/งานรูทีน สร้างโดย manager โดยไม่ผ่านคำขอบริการ';
