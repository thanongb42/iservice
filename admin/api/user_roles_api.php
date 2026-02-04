<?php
/**
 * API สำหรับจัดการบทบาท/หน้าที่ของผู้ใช้
 * Manage user roles and task assignments
 */

session_start();
header('Content-Type: application/json; charset=utf-8');

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit();
}

require_once '../../config/database.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ========================================
// GET: ดึงข้อมูล roles ของผู้ใช้
// ========================================
if ($action === 'get_user_roles') {
    $user_id = intval($_GET['user_id'] ?? 0);
    
    if (!$user_id) {
        echo json_encode(['success' => false, 'message' => 'Invalid user_id']);
        exit();
    }
    
    // Get current user roles
    $query = "SELECT ur.*, r.role_code, r.role_name, r.role_icon, r.role_color
              FROM user_roles ur
              JOIN roles r ON ur.role_id = r.role_id
              WHERE ur.user_id = ? AND ur.is_active = 1
              ORDER BY ur.is_primary DESC, r.display_order ASC";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $roles = [];
    while ($row = $result->fetch_assoc()) {
        $roles[] = $row;
    }
    
    echo json_encode(['success' => true, 'roles' => $roles]);
    exit();
}

// ========================================
// GET: ดึงข้อมูล roles ทั้งหมดที่สามารถมอบหมายได้
// ========================================
if ($action === 'get_available_roles') {
    $query = "SELECT role_id, role_code, role_name, role_icon, role_color, description, can_be_assigned
              FROM roles
              WHERE is_active = 1 AND can_be_assigned = 1
              ORDER BY display_order ASC";
    
    $result = $conn->query($query);
    
    $roles = [];
    while ($row = $result->fetch_assoc()) {
        $roles[] = $row;
    }
    
    echo json_encode(['success' => true, 'roles' => $roles]);
    exit();
}

// ========================================
// POST: เพิ่ม role ให้ผู้ใช้
// ========================================
if ($action === 'add_role') {
    $user_id = intval($_POST['user_id'] ?? 0);
    $role_id = intval($_POST['role_id'] ?? 0);
    $is_primary = intval($_POST['is_primary'] ?? 0);
    
    if (!$user_id || !$role_id) {
        echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
        exit();
    }
    
    // Check if user exists
    $check_user = $conn->prepare("SELECT user_id FROM users WHERE user_id = ?");
    $check_user->bind_param('i', $user_id);
    $check_user->execute();
    if (!$check_user->get_result()->num_rows) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit();
    }
    
    // Check if role exists
    $check_role = $conn->prepare("SELECT role_id FROM roles WHERE role_id = ? AND is_active = 1");
    $check_role->bind_param('i', $role_id);
    $check_role->execute();
    if (!$check_role->get_result()->num_rows) {
        echo json_encode(['success' => false, 'message' => 'Role not found']);
        exit();
    }
    
    // Check if already assigned
    $check_exist = $conn->prepare("SELECT id FROM user_roles WHERE user_id = ? AND role_id = ?");
    $check_exist->bind_param('ii', $user_id, $role_id);
    $check_exist->execute();
    if ($check_exist->get_result()->num_rows) {
        echo json_encode(['success' => false, 'message' => 'Role already assigned to this user']);
        exit();
    }
    
    // If setting as primary, unset other primary roles
    if ($is_primary) {
        $update_primary = $conn->prepare("UPDATE user_roles SET is_primary = 0 WHERE user_id = ?");
        $update_primary->bind_param('i', $user_id);
        $update_primary->execute();
    }
    
    // Insert new role
    $query = "INSERT INTO user_roles (user_id, role_id, assigned_by, is_primary, is_active, assigned_at)
              VALUES (?, ?, ?, ?, 1, NOW())";
    
    $stmt = $conn->prepare($query);
    $admin_id = $_SESSION['user_id'];
    $stmt->bind_param('iiii', $user_id, $role_id, $admin_id, $is_primary);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Role assigned successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to assign role: ' . $conn->error]);
    }
    exit();
}

// ========================================
// POST: ลบ role ของผู้ใช้
// ========================================
if ($action === 'remove_role') {
    $user_id = intval($_POST['user_id'] ?? 0);
    $role_id = intval($_POST['role_id'] ?? 0);
    
    if (!$user_id || !$role_id) {
        echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
        exit();
    }
    
    $query = "UPDATE user_roles SET is_active = 0 WHERE user_id = ? AND role_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ii', $user_id, $role_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Role removed successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to remove role']);
    }
    exit();
}

// ========================================
// POST: ตั้ง role เป็น primary
// ========================================
if ($action === 'set_primary_role') {
    $user_id = intval($_POST['user_id'] ?? 0);
    $role_id = intval($_POST['role_id'] ?? 0);
    
    if (!$user_id || !$role_id) {
        echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
        exit();
    }
    
    // Unset other primary roles
    $update_other = $conn->prepare("UPDATE user_roles SET is_primary = 0 WHERE user_id = ? AND role_id != ?");
    $update_other->bind_param('ii', $user_id, $role_id);
    $update_other->execute();
    
    // Set this role as primary
    $update_primary = $conn->prepare("UPDATE user_roles SET is_primary = 1 WHERE user_id = ? AND role_id = ?");
    $update_primary->bind_param('ii', $user_id, $role_id);
    
    if ($update_primary->execute()) {
        echo json_encode(['success' => true, 'message' => 'Primary role updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update primary role']);
    }
    exit();
}

// Invalid action
http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Invalid action']);
?>
