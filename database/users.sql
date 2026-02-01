-- Users Table
-- Complete user management system with prefix support

CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    prefix_id INT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    phone VARCHAR(20) NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'staff', 'user') DEFAULT 'user',
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    department_id INT NULL,
    position VARCHAR(100) NULL,
    profile_image VARCHAR(255) NULL,
    last_login DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (prefix_id) REFERENCES prefixes(prefix_id) ON DELETE SET NULL,
    FOREIGN KEY (department_id) REFERENCES departments(department_id) ON DELETE SET NULL,

    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_status (status),
    INDEX idx_department (department_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default admin user
-- Password: admin123 (hashed with bcrypt)
INSERT INTO users (prefix_id, username, first_name, last_name, email, password, role, status) VALUES
(
    (SELECT prefix_id FROM prefixes WHERE prefix_name = 'นาย' LIMIT 1),
    'admin',
    'ผู้ดูแลระบบ',
    'เทศบาล',
    'admin@rangsit.go.th',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- admin123
    'admin',
    'active'
);

-- Insert sample users for testing
INSERT INTO users (prefix_id, username, first_name, last_name, email, phone, password, role, status) VALUES
(
    (SELECT prefix_id FROM prefixes WHERE prefix_name = 'นาย' LIMIT 1),
    'somchai',
    'สมชาย',
    'ใจดี',
    'somchai@rangsit.go.th',
    '0812345678',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- admin123
    'staff',
    'active'
),
(
    (SELECT prefix_id FROM prefixes WHERE prefix_name = 'นาง' LIMIT 1),
    'somsri',
    'สมศรี',
    'รักดี',
    'somsri@rangsit.go.th',
    '0823456789',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- admin123
    'user',
    'active'
),
(
    (SELECT prefix_id FROM prefixes WHERE prefix_name = 'นางสาว' LIMIT 1),
    'suree',
    'สุรีย์',
    'สว่างใจ',
    'suree@rangsit.go.th',
    '0834567890',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- admin123
    'user',
    'active'
),
(
    (SELECT prefix_id FROM prefixes WHERE prefix_name = 'ว่าที่ร้อยตรี' LIMIT 1),
    'military_user',
    'ธนพล',
    'มั่นคง',
    'thanaphon@rangsit.go.th',
    '0845678901',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- admin123
    'user',
    'active'
),
(
    (SELECT prefix_id FROM prefixes WHERE prefix_name = 'ดร.' LIMIT 1),
    'doctor_user',
    'วิชัย',
    'ปัญญา',
    'wichai@rangsit.go.th',
    '0856789012',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- admin123
    'user',
    'active'
);

-- Add comments to columns
ALTER TABLE users
    MODIFY COLUMN user_id INT AUTO_INCREMENT COMMENT 'รหัสผู้ใช้',
    MODIFY COLUMN prefix_id INT NULL COMMENT 'รหัสคำนำหน้าชื่อ (FK to prefixes)',
    MODIFY COLUMN username VARCHAR(50) NOT NULL UNIQUE COMMENT 'ชื่อผู้ใช้สำหรับ login',
    MODIFY COLUMN first_name VARCHAR(100) NOT NULL COMMENT 'ชื่อ',
    MODIFY COLUMN last_name VARCHAR(100) NOT NULL COMMENT 'นามสกุล',
    MODIFY COLUMN email VARCHAR(255) NOT NULL UNIQUE COMMENT 'อีเมล',
    MODIFY COLUMN phone VARCHAR(20) NULL COMMENT 'เบอร์โทรศัพท์',
    MODIFY COLUMN password VARCHAR(255) NOT NULL COMMENT 'รหัสผ่าน (hashed)',
    MODIFY COLUMN role ENUM('admin', 'staff', 'user') DEFAULT 'user' COMMENT 'บทบาท: admin=ผู้ดูแล, staff=เจ้าหน้าที่, user=ผู้ใช้ทั่วไป',
    MODIFY COLUMN status ENUM('active', 'inactive', 'suspended') DEFAULT 'active' COMMENT 'สถานะ: active=ใช้งาน, inactive=ไม่ใช้งาน, suspended=ระงับ',
    MODIFY COLUMN department_id INT NULL COMMENT 'รหัสหน่วยงาน (FK to departments)',
    MODIFY COLUMN position VARCHAR(100) NULL COMMENT 'ตำแหน่ง',
    MODIFY COLUMN profile_image VARCHAR(255) NULL COMMENT 'รูปโปรไฟล์',
    MODIFY COLUMN last_login DATETIME NULL COMMENT 'เข้าสู่ระบบครั้งล่าสุด',
    MODIFY COLUMN created_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'วันที่สร้าง',
    MODIFY COLUMN updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'วันที่อัปเดต';

-- Create view for full user information with prefix and department
CREATE OR REPLACE VIEW v_users_full AS
SELECT
    u.user_id,
    u.username,
    u.prefix_id,
    p.prefix_name,
    u.first_name,
    u.last_name,
    CONCAT(IFNULL(p.prefix_name, ''), ' ', u.first_name, ' ', u.last_name) AS full_name,
    u.email,
    u.phone,
    u.role,
    u.status,
    u.department_id,
    d.department_name,
    d.department_code,
    u.position,
    u.profile_image,
    u.last_login,
    u.created_at,
    u.updated_at
FROM users u
LEFT JOIN prefixes p ON u.prefix_id = p.prefix_id
LEFT JOIN departments d ON u.department_id = d.department_id;

-- Create stored procedure to get user by username or email
DELIMITER //

CREATE PROCEDURE sp_get_user_login(IN login_identifier VARCHAR(255))
BEGIN
    SELECT
        u.user_id,
        u.username,
        p.prefix_name,
        u.first_name,
        u.last_name,
        CONCAT(IFNULL(p.prefix_name, ''), ' ', u.first_name, ' ', u.last_name) AS full_name,
        u.email,
        u.password,
        u.role,
        u.status,
        u.department_id,
        d.department_name
    FROM users u
    LEFT JOIN prefixes p ON u.prefix_id = p.prefix_id
    LEFT JOIN departments d ON u.department_id = d.department_id
    WHERE u.username = login_identifier OR u.email = login_identifier
    LIMIT 1;
END //

DELIMITER ;

-- Create stored procedure to update last login
DELIMITER //

CREATE PROCEDURE sp_update_last_login(IN p_user_id INT)
BEGIN
    UPDATE users
    SET last_login = NOW()
    WHERE user_id = p_user_id;
END //

DELIMITER ;

-- Create trigger to prevent deleting admin user
DELIMITER //

CREATE TRIGGER before_delete_user
BEFORE DELETE ON users
FOR EACH ROW
BEGIN
    IF OLD.role = 'admin' AND (SELECT COUNT(*) FROM users WHERE role = 'admin') <= 1 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Cannot delete the last admin user';
    END IF;
END //

DELIMITER ;

-- Show table structure
DESC users;

-- Show sample data
SELECT
    user_id,
    username,
    prefix_name,
    full_name,
    email,
    role,
    status,
    department_name
FROM v_users_full
ORDER BY user_id;

-- Show statistics
SELECT
    role,
    COUNT(*) as count,
    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_count,
    SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive_count
FROM users
GROUP BY role;
