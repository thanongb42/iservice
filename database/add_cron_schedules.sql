-- Migration: Self-service cron scheduler (replaces external cron-job.org dependency)
-- A single OS-level cron entry runs cron/runner.php every minute; it reads this
-- table to decide which jobs are due and triggers their target_url.
-- Run on both local (iservice_db) and production (rangsitadmin_iservice_db / iservicedb on VM1)

CREATE TABLE IF NOT EXISTS `cron_schedules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `target_url` varchar(255) NOT NULL,
  `interval_minutes` int(11) NOT NULL DEFAULT 15,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_run_at` datetime DEFAULT NULL,
  `last_status` enum('success','error') DEFAULT NULL,
  `last_http_code` int(11) DEFAULT NULL,
  `last_message` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cron_schedule_runs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `schedule_id` int(11) NOT NULL,
  `ran_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('success','error') NOT NULL,
  `http_code` int(11) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `duration_ms` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_schedule_id` (`schedule_id`, `ran_at`),
  CONSTRAINT `fk_csr_schedule` FOREIGN KEY (`schedule_id`) REFERENCES `cron_schedules` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed the existing pm25_cron.php job (currently triggered by a per-job VM1 crontab
-- entry set up during the GDCC VM1 cutover — see .ai/iservice-gdcc-vm1-cutover-runbook).
-- After this migration + the runner.php bootstrap cron is in place, that per-job
-- crontab entry should be removed since cron_schedules + runner.php replaces it.
INSERT INTO `cron_schedules` (`name`, `target_url`, `interval_minutes`, `is_active`)
VALUES ('PM2.5 Data Sync', 'https://iservice.rangsitcity.go.th/pm25_cron.php', 15, 1);
