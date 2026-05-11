-- Migration: Add user_id to job_notification_log for per-assignee dedup
-- Run after add_job_notification_log.sql

-- Drop old unique key (job_id, notify_type) and add user_id column + new unique key
ALTER TABLE `job_notification_log`
  ADD COLUMN IF NOT EXISTS `user_id` INT NULL DEFAULT NULL AFTER `job_id`,
  DROP KEY IF EXISTS `uq_job_notify`,
  ADD UNIQUE KEY `uq_job_user_notify` (`job_id`, `user_id`, `notify_type`);

-- Backfill existing rows: set user_id from internal_jobs.assigned_to
UPDATE job_notification_log jnl
JOIN internal_jobs ij ON jnl.job_id = ij.job_id
SET jnl.user_id = ij.assigned_to
WHERE jnl.user_id IS NULL AND ij.assigned_to IS NOT NULL;
