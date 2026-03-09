-- เพิ่ม timestamp columns ใน internal_jobs (ถ้ายังไม่มี)
-- Run ใน phpMyAdmin บน production: rangsitadmin_iservice_db

ALTER TABLE `internal_jobs`
    ADD COLUMN IF NOT EXISTS `accepted_at`   DATETIME DEFAULT NULL AFTER `completion_notes`,
    ADD COLUMN IF NOT EXISTS `started_at`    DATETIME DEFAULT NULL AFTER `accepted_at`,
    ADD COLUMN IF NOT EXISTS `completed_at`  DATETIME DEFAULT NULL AFTER `started_at`;
