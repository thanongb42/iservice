-- =============================================================
-- iService Database Views
-- ไม่มี DEFINER — ใช้ได้ทั้ง localhost และ production
-- SQL SECURITY INVOKER = ใช้สิทธิ์ของ user ที่ login อยู่
-- =============================================================

-- -------------------------------------------------------------
-- v_users_full
-- -------------------------------------------------------------
DROP VIEW IF EXISTS `v_users_full`;
CREATE OR REPLACE SQL SECURITY INVOKER VIEW `v_users_full` AS
SELECT
    `u`.`user_id`,
    `u`.`username`,
    `u`.`prefix_id`,
    `p`.`prefix_name`,
    `u`.`first_name`,
    `u`.`last_name`,
    CONCAT(IFNULL(`p`.`prefix_name`, ''), ' ', `u`.`first_name`, ' ', `u`.`last_name`) AS `full_name`,
    `u`.`email`,
    `u`.`phone`,
    `u`.`role`,
    `u`.`status`,
    `u`.`department_id`,
    `d`.`department_name`,
    `d`.`department_code`,
    `u`.`position`,
    `u`.`profile_image`,
    `u`.`last_login`,
    `u`.`created_at`,
    `u`.`updated_at`
FROM `users` `u`
LEFT JOIN `prefixes` `p` ON `u`.`prefix_id` = `p`.`prefix_id`
LEFT JOIN `departments` `d` ON `u`.`department_id` = `d`.`department_id`;

-- -------------------------------------------------------------
-- v_user_roles
-- -------------------------------------------------------------
DROP VIEW IF EXISTS `v_user_roles`;
CREATE OR REPLACE SQL SECURITY INVOKER VIEW `v_user_roles` AS
SELECT
    `ur`.`id`,
    `ur`.`user_id`,
    `u`.`username`,
    CONCAT(`p`.`prefix_name`, `u`.`first_name`, ' ', `u`.`last_name`) AS `full_name`,
    `ur`.`role_id`,
    `r`.`role_code`,
    `r`.`role_name`,
    `r`.`role_icon`,
    `r`.`role_color`,
    `r`.`can_assign`,
    `r`.`can_be_assigned`,
    `ur`.`is_primary`,
    `ur`.`is_active`,
    `ur`.`assigned_at`,
    `ur`.`assigned_by`,
    `ab`.`username` AS `assigned_by_username`
FROM `user_roles` `ur`
JOIN `users` `u` ON `ur`.`user_id` = `u`.`user_id`
LEFT JOIN `prefixes` `p` ON `u`.`prefix_id` = `p`.`prefix_id`
JOIN `roles` `r` ON `ur`.`role_id` = `r`.`role_id`
LEFT JOIN `users` `ab` ON `ur`.`assigned_by` = `ab`.`user_id`
WHERE `ur`.`is_active` = 1
  AND `r`.`is_active` = 1;

-- -------------------------------------------------------------
-- v_service_requests_full
-- -------------------------------------------------------------
DROP VIEW IF EXISTS `v_service_requests_full`;
CREATE OR REPLACE SQL SECURITY INVOKER VIEW `v_service_requests_full` AS
SELECT
    `r`.`request_id`,
    `r`.`user_id`,
    `r`.`request_code`,
    `r`.`service_code`,
    `r`.`service_name`,
    `r`.`requester_prefix_id`,
    `r`.`requester_name`,
    `r`.`requester_position`,
    `r`.`requester_phone`,
    `r`.`requester_email`,
    `r`.`department_id`,
    `r`.`department_name`,
    `r`.`subject`,
    `r`.`description`,
    `r`.`request_data`,
    `r`.`status`,
    `r`.`priority`,
    `r`.`assigned_to`,
    `r`.`assigned_at`,
    `r`.`admin_notes`,
    `r`.`rejection_reason`,
    `r`.`completion_notes`,
    `r`.`expected_completion_date`,
    `r`.`started_at`,
    `r`.`completed_at`,
    `r`.`cancelled_at`,
    `r`.`created_at`,
    `r`.`updated_at`,
    `ms`.`icon`,
    `ms`.`color_code`,
    `ms`.`service_name_en`,
    `u`.`username`,
    `d`.`department_code`
FROM `service_requests` `r`
LEFT JOIN `my_service` `ms` ON `r`.`service_code` = `ms`.`service_code`
LEFT JOIN `users` `u` ON `r`.`user_id` = `u`.`user_id`
LEFT JOIN `departments` `d` ON `r`.`department_id` = `d`.`department_id`;

-- -------------------------------------------------------------
-- v_task_assignments
-- -------------------------------------------------------------
DROP VIEW IF EXISTS `v_task_assignments`;
CREATE OR REPLACE SQL SECURITY INVOKER VIEW `v_task_assignments` AS
SELECT
    `ta`.`assignment_id`,
    `ta`.`request_id`,
    `sr`.`request_code`,
    `sr`.`service_name`,
    `sr`.`requester_name`,
    `ta`.`assigned_to`,
    `u_to`.`username` AS `assigned_to_username`,
    CONCAT(IFNULL(`p_to`.`prefix_name`, ''), `u_to`.`first_name`, ' ', `u_to`.`last_name`) AS `assigned_to_name`,
    `ta`.`assigned_as_role`,
    `r`.`role_name` AS `assigned_role_name`,
    `r`.`role_icon`,
    `r`.`role_color`,
    `ta`.`assigned_by`,
    `u_by`.`username` AS `assigned_by_username`,
    CONCAT(IFNULL(`p_by`.`prefix_name`, ''), `u_by`.`first_name`, ' ', `u_by`.`last_name`) AS `assigned_by_name`,
    `ta`.`status`,
    `ta`.`priority`,
    `ta`.`due_date`,
    `ta`.`notes`,
    `ta`.`accepted_at`,
    `ta`.`started_at`,
    `ta`.`completed_at`,
    `ta`.`created_at`
FROM `task_assignments` `ta`
JOIN `service_requests` `sr` ON `ta`.`request_id` = `sr`.`request_id`
JOIN `users` `u_to` ON `ta`.`assigned_to` = `u_to`.`user_id`
LEFT JOIN `prefixes` `p_to` ON `u_to`.`prefix_id` = `p_to`.`prefix_id`
JOIN `users` `u_by` ON `ta`.`assigned_by` = `u_by`.`user_id`
LEFT JOIN `prefixes` `p_by` ON `u_by`.`prefix_id` = `p_by`.`prefix_id`
LEFT JOIN `roles` `r` ON `ta`.`assigned_as_role` = `r`.`role_id`;
