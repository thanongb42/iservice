-- =====================================================
-- Roles and Task Assignment System
-- ระบบบทบาทและการมอบหมายงาน
-- =====================================================

-- 1. ตารางบทบาท/หน้าที่ (roles)
-- =====================================================
CREATE TABLE IF NOT EXISTS `roles` (
    `role_id` INT(11) NOT NULL AUTO_INCREMENT,
    `role_code` VARCHAR(50) NOT NULL UNIQUE COMMENT 'รหัสบทบาท เช่น photographer, mc, manager',
    `role_name` VARCHAR(100) NOT NULL COMMENT 'ชื่อบทบาท เช่น ช่างภาพ, พิธีกร',
    `role_icon` VARCHAR(50) DEFAULT 'fa-user-tag' COMMENT 'Font Awesome icon',
    `role_color` VARCHAR(20) DEFAULT '#6b7280' COMMENT 'สีของ badge',
    `description` TEXT DEFAULT NULL COMMENT 'คำอธิบายบทบาท',
    `can_assign` TINYINT(1) DEFAULT 0 COMMENT 'สามารถมอบหมายงานให้ผู้อื่นได้',
    `can_be_assigned` TINYINT(1) DEFAULT 1 COMMENT 'สามารถรับมอบหมายงานได้',
    `is_active` TINYINT(1) DEFAULT 1,
    `display_order` INT(11) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`role_id`),
    UNIQUE KEY `idx_role_code` (`role_code`),
    KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. ตารางเชื่อมโยงผู้ใช้กับบทบาท (user_roles)
-- ผู้ใช้ 1 คนมีได้หลายบทบาท
-- =====================================================
CREATE TABLE IF NOT EXISTS `user_roles` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL,
    `role_id` INT(11) NOT NULL,
    `assigned_by` INT(11) DEFAULT NULL COMMENT 'ผู้มอบหมายบทบาท',
    `is_primary` TINYINT(1) DEFAULT 0 COMMENT 'บทบาทหลัก',
    `is_active` TINYINT(1) DEFAULT 1,
    `assigned_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `notes` TEXT DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_user_role` (`user_id`, `role_id`),
    KEY `idx_user` (`user_id`),
    KEY `idx_role` (`role_id`),
    CONSTRAINT `fk_user_roles_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
    CONSTRAINT `fk_user_roles_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`) ON DELETE CASCADE,
    CONSTRAINT `fk_user_roles_assigned_by` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. ตารางการมอบหมายงาน (task_assignments)
-- =====================================================
CREATE TABLE IF NOT EXISTS `task_assignments` (
    `assignment_id` INT(11) NOT NULL AUTO_INCREMENT,
    `request_id` INT(11) NOT NULL COMMENT 'FK to service_requests',
    `assigned_to` INT(11) NOT NULL COMMENT 'ผู้รับมอบหมาย',
    `assigned_as_role` INT(11) DEFAULT NULL COMMENT 'มอบหมายในฐานะบทบาทใด',
    `assigned_by` INT(11) NOT NULL COMMENT 'ผู้มอบหมาย',
    `status` ENUM('pending', 'accepted', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
    `priority` ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
    `due_date` DATETIME DEFAULT NULL COMMENT 'กำหนดส่ง',
    `notes` TEXT DEFAULT NULL COMMENT 'หมายเหตุการมอบหมาย',
    `accepted_at` DATETIME DEFAULT NULL,
    `started_at` DATETIME DEFAULT NULL,
    `completed_at` DATETIME DEFAULT NULL,
    `completion_notes` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`assignment_id`),
    KEY `idx_request` (`request_id`),
    KEY `idx_assigned_to` (`assigned_to`),
    KEY `idx_assigned_by` (`assigned_by`),
    KEY `idx_status` (`status`),
    KEY `idx_due_date` (`due_date`),
    CONSTRAINT `fk_task_request` FOREIGN KEY (`request_id`) REFERENCES `service_requests` (`request_id`) ON DELETE CASCADE,
    CONSTRAINT `fk_task_assigned_to` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
    CONSTRAINT `fk_task_assigned_by` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
    CONSTRAINT `fk_task_role` FOREIGN KEY (`assigned_as_role`) REFERENCES `roles` (`role_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. ตารางประวัติการดำเนินการ (task_history)
-- =====================================================
CREATE TABLE IF NOT EXISTS `task_history` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `assignment_id` INT(11) NOT NULL,
    `action` VARCHAR(50) NOT NULL COMMENT 'assigned, accepted, started, completed, cancelled, reassigned',
    `old_status` VARCHAR(20) DEFAULT NULL,
    `new_status` VARCHAR(20) DEFAULT NULL,
    `performed_by` INT(11) NOT NULL,
    `notes` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_assignment` (`assignment_id`),
    CONSTRAINT `fk_history_assignment` FOREIGN KEY (`assignment_id`) REFERENCES `task_assignments` (`assignment_id`) ON DELETE CASCADE,
    CONSTRAINT `fk_history_user` FOREIGN KEY (`performed_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- ข้อมูลเริ่มต้น - บทบาทพื้นฐาน
-- =====================================================
INSERT INTO `roles` (`role_code`, `role_name`, `role_icon`, `role_color`, `description`, `can_assign`, `can_be_assigned`, `display_order`) VALUES
('all', 'ทำได้ทุกอย่าง', 'fa-star', '#eab308', 'สามารถรับและมอบหมายงานได้ทุกประเภท', 1, 1, 1),
('manager', 'ผู้จัดการ', 'fa-user-tie', '#3b82f6', 'ผู้จัดการที่สามารถมอบหมายงานให้ทีมได้', 1, 1, 2),
('it_support', 'IT Support', 'fa-desktop', '#10b981', 'ดูแลระบบ IT และซ่อมบำรุง', 0, 1, 3),
('photographer', 'ช่างภาพ', 'fa-camera', '#8b5cf6', 'ถ่ายภาพและวิดีโอ', 0, 1, 4),
('mc', 'พิธีกร', 'fa-microphone', '#f59e0b', 'พิธีกรดำเนินรายการ', 0, 1, 5),
('technician', 'ช่างเทคนิค', 'fa-tools', '#6366f1', 'ช่างซ่อมบำรุงทั่วไป', 0, 1, 6),
('network_admin', 'ผู้ดูแลระบบเครือข่าย', 'fa-network-wired', '#06b6d4', 'ดูแลระบบเครือข่ายและ Internet', 0, 1, 7),
('graphic_designer', 'นักออกแบบกราฟิก', 'fa-palette', '#ec4899', 'ออกแบบสื่อและกราฟิก', 0, 1, 8),
('event_coordinator', 'ผู้ประสานงานกิจกรรม', 'fa-calendar-check', '#14b8a6', 'ประสานงานจัดกิจกรรมและงานอีเวนต์', 1, 1, 9),
('driver', 'พนักงานขับรถ', 'fa-car', '#64748b', 'บริการขับรถรับส่ง', 0, 1, 10);

-- =====================================================
-- View สำหรับดูข้อมูลการมอบหมายงาน
-- =====================================================
CREATE OR REPLACE VIEW v_task_assignments AS
SELECT
    ta.assignment_id,
    ta.request_id,
    sr.tracking_number,
    sr.service_name,
    sr.requester_name,
    ta.assigned_to,
    u_to.username AS assigned_to_username,
    CONCAT(p_to.prefix_name, u_to.first_name, ' ', u_to.last_name) AS assigned_to_name,
    ta.assigned_as_role,
    r.role_name AS assigned_role_name,
    r.role_icon,
    r.role_color,
    ta.assigned_by,
    u_by.username AS assigned_by_username,
    CONCAT(p_by.prefix_name, u_by.first_name, ' ', u_by.last_name) AS assigned_by_name,
    ta.status,
    ta.priority,
    ta.due_date,
    ta.notes,
    ta.accepted_at,
    ta.started_at,
    ta.completed_at,
    ta.created_at
FROM task_assignments ta
JOIN service_requests sr ON ta.request_id = sr.request_id
JOIN users u_to ON ta.assigned_to = u_to.user_id
LEFT JOIN prefixes p_to ON u_to.prefix_id = p_to.prefix_id
JOIN users u_by ON ta.assigned_by = u_by.user_id
LEFT JOIN prefixes p_by ON u_by.prefix_id = p_by.prefix_id
LEFT JOIN roles r ON ta.assigned_as_role = r.role_id;

-- =====================================================
-- View สำหรับดูบทบาทของผู้ใช้
-- =====================================================
CREATE OR REPLACE VIEW v_user_roles AS
SELECT
    ur.id,
    ur.user_id,
    u.username,
    CONCAT(p.prefix_name, u.first_name, ' ', u.last_name) AS full_name,
    ur.role_id,
    r.role_code,
    r.role_name,
    r.role_icon,
    r.role_color,
    r.can_assign,
    r.can_be_assigned,
    ur.is_primary,
    ur.is_active,
    ur.assigned_at,
    ur.assigned_by,
    ab.username AS assigned_by_username
FROM user_roles ur
JOIN users u ON ur.user_id = u.user_id
LEFT JOIN prefixes p ON u.prefix_id = p.prefix_id
JOIN roles r ON ur.role_id = r.role_id
LEFT JOIN users ab ON ur.assigned_by = ab.user_id
WHERE ur.is_active = 1 AND r.is_active = 1;
