-- Add service_type column to internal_jobs
-- Run once on both local and production

ALTER TABLE `internal_jobs`
  ADD COLUMN `service_type` VARCHAR(50) NULL DEFAULT NULL
    COMMENT 'ประเภทบริการ: photography, mc, led, it_support, qr_code, printer, web_design, internet, email, nas, other'
    AFTER `job_type`;
