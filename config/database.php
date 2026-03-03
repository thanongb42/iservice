<?php
/**
 * Database Configuration File
 * ไฟล์ตั้งค่าการเชื่อมต่อฐานข้อมูล
 */

// Database credentials on local environment
// define('DB_HOST', 'localhost');
// define('DB_USER', 'root');
// define('DB_PASS', '');
// define('DB_NAME', 'iservice_db');
// define('DB_CHARSET', 'utf8mb4');


// Database credentials on Hosting Production environment

define('DB_HOST', 'localhost');
define('DB_USER', 'rangsitadmin_iservice');
define('DB_PASS', 'IService@2026');
define('DB_NAME', 'rangsitadmin_iservice_db');
define('DB_CHARSET', 'utf8mb4');


// Create connection
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    
    // Set charset
    $conn->set_charset(DB_CHARSET);

} catch (Exception $e) {
    die("Database Error: " . $e->getMessage());
}

/**
 * PDO Connection (Alternative)
 * เหมาะสำหรับ prepared statements
 */
function getPDO() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $pdo = new PDO($dsn, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        return $pdo;
    } catch (PDOException $e) {
        die("PDO Connection Error: " . $e->getMessage());
    }
}

/**
 * Utility function: Sanitize input
 */
function clean_input($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $conn->real_escape_string($data);
}

/**
 * Fix asset/upload path for display
 * เพิ่ม public/ prefix ให้กับ path ของไฟล์ที่อัปโหลด
 * เพื่อให้เข้าถึงไฟล์ได้ทั้ง localhost และ production
 *
 * @param string $path Path from database (e.g. "uploads/covers/file.jpg")
 * @param bool $from_admin true if called from admin/ subdirectory
 * @return string Fixed path (e.g. "public/uploads/covers/file.jpg")
 */
function fix_asset_path($path, $from_admin = false) {
    if (empty($path)) return $path;
    // Skip external URLs and data URIs
    if (preg_match('/^(https?:\/\/|data:)/', $path)) return $path;
    // Remove any ../ prefix
    $path = preg_replace('/^(\.\.\/)+/', '', $path);
    // Add public/ prefix for upload paths
    if (strpos($path, 'public/') !== 0 && strpos($path, 'uploads/') === 0) {
        $path = 'public/' . $path;
    }
    // For admin pages, prepend ../
    if ($from_admin) {
        $path = '../' . $path;
    }
    return $path;
}

/**
 * Access guard for manager-or-admin pages (service_requests, dashboard)
 * admin OR role_code IN ('manager','all') → ผ่าน
 */
function require_manager_or_admin(string $redirect_if_denied = 'my_tasks.php'): void {
    if (!isset($_SESSION['user_id'])) { header('Location: ../login.php'); exit; }
    if (($_SESSION['role'] ?? '') === 'admin') return;
    global $conn;
    $uid = intval($_SESSION['user_id']);
    $chk = $conn->prepare("SELECT COUNT(*) as cnt FROM user_roles ur JOIN roles r ON ur.role_id=r.role_id WHERE ur.user_id=? AND r.role_code IN ('manager','all') AND ur.is_active=1 AND r.is_active=1");
    $chk->bind_param('i', $uid);
    $chk->execute();
    if ($chk->get_result()->fetch_assoc()['cnt'] > 0) return;
    $role_label = match($_SESSION['role'] ?? '') { 'staff'=>'เจ้าหน้าที่ (Staff)', 'user'=>'ผู้ใช้งานทั่วไป', default=>ucfirst($_SESSION['role'] ?? '-') };
    $_SESSION['flash_error_title'] = 'ไม่มีสิทธิ์เข้าถึง';
    $_SESSION['flash_error']       = "หน้านี้สำหรับผู้จัดการ (Manager) หรือผู้ดูแลระบบเท่านั้น\nสิทธิ์ปัจจุบัน: {$role_label}";
    header('Location: ' . $redirect_if_denied); exit;
}

/**
 * Access guard for admin-only pages
 * - ถ้ายังไม่ได้ login → redirect ไป login.php
 * - ถ้า login แล้วแต่ไม่ใช่ admin → set flash error + redirect ไป my_tasks.php
 *
 * @param string $redirect_if_denied  relative path จากโฟลเดอร์ admin/
 */
function require_admin_role(string $redirect_if_denied = 'my_tasks.php'): void {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ../login.php');
        exit;
    }
    if (($_SESSION['role'] ?? '') !== 'admin') {
        $role_label = match($_SESSION['role'] ?? '') {
            'staff' => 'เจ้าหน้าที่ (Staff)',
            'user'  => 'ผู้ใช้งานทั่วไป (User)',
            default => ucfirst($_SESSION['role'] ?? 'ไม่ทราบ'),
        };
        $_SESSION['flash_error_title'] = 'ไม่มีสิทธิ์เข้าถึง';
        $_SESSION['flash_error']       = "หน้านี้สำหรับผู้ดูแลระบบ (Admin) เท่านั้น\nสิทธิ์ปัจจุบันของคุณ: {$role_label}";
        header('Location: ' . $redirect_if_denied);
        exit;
    }
}

/**
 * Utility function: Check if table exists
 */
function table_exists($table_name) {
    global $conn;
    $result = $conn->query("SHOW TABLES LIKE '$table_name'");
    return $result->num_rows > 0;
}
?>
