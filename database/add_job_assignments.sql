-- Migration: Multi-assignee support for internal jobs
-- Creates job_assignments table and migrates existing assigned_to data
-- Run on both local (iservice_db) and production (rangsitadmin_iservice_db)

CREATE TABLE IF NOT EXISTS `job_assignments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `job_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `assigned_by` int(11) DEFAULT NULL,
  `assigned_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_job_user` (`job_id`,`user_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_job_id` (`job_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Migrate existing single assignments from internal_jobs.assigned_to
INSERT IGNORE INTO job_assignments (job_id, user_id, assigned_by)
SELECT job_id, assigned_to, assigned_by
FROM internal_jobs
WHERE assigned_to IS NOT NULL;
