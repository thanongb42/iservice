<?php
/**
 * Roles API
 * AJAX endpoint for roles management
 */

header('Content-Type: application/json');
require_once '../../config/database.php';
session_start();

$response = ['success' => false, 'message' => ''];

try {
    // Check admin access
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        throw new Exception('ไม่มีสิทธิ์เข้าถึง');
    }

    $action = $_POST['action'] ?? $_GET['action'] ?? '';

    switch ($action) {
        case 'add':
            addRole();
            break;

        case 'edit':
            editRole();
            break;

        case 'delete':
            deleteRole();
            break;

        case 'get':
            getRole();
            break;

        case 'list':
            listRoles();
            break;

        case 'assign_user':
            assignUserRole();
            break;

        case 'remove_user_role':
            removeUserRole();
            break;

        case 'get_users_by_role':
            getUsersByRole();
            break;

        case 'get_user_roles':
            getUserRoles();
            break;

        default:
            throw new Exception('Action ไม่ถูกต้อง');
    }

} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
}

echo json_encode($response);

// ============== FUNCTIONS ==============

function addRole() {
    global $conn, $response;

    $role_code = trim($_POST['role_code'] ?? '');
    $role_name = trim($_POST['role_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $role_icon = trim($_POST['role_icon'] ?? 'fa-user-tag');
    $role_color = trim($_POST['role_color'] ?? '#6b7280');
    $display_order = intval($_POST['display_order'] ?? 0);
    $can_assign = intval($_POST['can_assign'] ?? 0);
    $can_be_assigned = intval($_POST['can_be_assigned'] ?? 1);
    $is_active = intval($_POST['is_active'] ?? 1);

    if (empty($role_code) || empty($role_name)) {
        throw new Exception('กรุณากรอกรหัสและชื่อบทบาท');
    }

    // Check duplicate role_code
    $check = $conn->prepare("SELECT role_id FROM roles WHERE role_code = ?");
    $check->bind_param('s', $role_code);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        throw new Exception('รหัสบทบาทนี้มีอยู่แล้ว');
    }

    $stmt = $conn->prepare("INSERT INTO roles (role_code, role_name, description, role_icon, role_color, display_order, can_assign, can_be_assigned, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('sssssiiiii', $role_code, $role_name, $description, $role_icon, $role_color, $display_order, $can_assign, $can_be_assigned, $is_active);

    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = 'เพิ่มบทบาทสำเร็จ';
        $response['role_id'] = $conn->insert_id;
    } else {
        throw new Exception('ไม่สามารถเพิ่มบทบาทได้: ' . $stmt->error);
    }
}

function editRole() {
    global $conn, $response;

    $role_id = intval($_POST['role_id'] ?? 0);
    $role_code = trim($_POST['role_code'] ?? '');
    $role_name = trim($_POST['role_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $role_icon = trim($_POST['role_icon'] ?? 'fa-user-tag');
    $role_color = trim($_POST['role_color'] ?? '#6b7280');
    $display_order = intval($_POST['display_order'] ?? 0);
    $can_assign = intval($_POST['can_assign'] ?? 0);
    $can_be_assigned = intval($_POST['can_be_assigned'] ?? 1);
    $is_active = intval($_POST['is_active'] ?? 1);

    if ($role_id <= 0 || empty($role_code) || empty($role_name)) {
        throw new Exception('ข้อมูลไม่ครบถ้วน');
    }

    // Check duplicate role_code (exclude current)
    $check = $conn->prepare("SELECT role_id FROM roles WHERE role_code = ? AND role_id != ?");
    $check->bind_param('si', $role_code, $role_id);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        throw new Exception('รหัสบทบาทนี้มีอยู่แล้ว');
    }

    $stmt = $conn->prepare("UPDATE roles SET role_code = ?, role_name = ?, description = ?, role_icon = ?, role_color = ?, display_order = ?, can_assign = ?, can_be_assigned = ?, is_active = ? WHERE role_id = ?");
    $stmt->bind_param('sssssiiiiii', $role_code, $role_name, $description, $role_icon, $role_color, $display_order, $can_assign, $can_be_assigned, $is_active, $role_id);

    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = 'แก้ไขบทบาทสำเร็จ';
    } else {
        throw new Exception('ไม่สามารถแก้ไขบทบาทได้: ' . $stmt->error);
    }
}

function deleteRole() {
    global $conn, $response;

    $role_id = intval($_POST['role_id'] ?? 0);

    if ($role_id <= 0) {
        throw new Exception('ไม่พบบทบาทที่ต้องการลบ');
    }

    // Check if it's the 'all' role
    $check = $conn->prepare("SELECT role_code FROM roles WHERE role_id = ?");
    $check->bind_param('i', $role_id);
    $check->execute();
    $result = $check->get_result()->fetch_assoc();

    if ($result && $result['role_code'] === 'all') {
        throw new Exception('ไม่สามารถลบบทบาท "ทำได้ทุกอย่าง" ได้');
    }

    // Delete user_roles first (or they'll be deleted by FK cascade)
    $del_ur = $conn->prepare("DELETE FROM user_roles WHERE role_id = ?");
    $del_ur->bind_param("i", $role_id);
    $del_ur->execute();

    // Delete role
    $stmt = $conn->prepare("DELETE FROM roles WHERE role_id = ?");
    $stmt->bind_param('i', $role_id);

    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = 'ลบบทบาทสำเร็จ';
    } else {
        throw new Exception('ไม่สามารถลบบทบาทได้: ' . $stmt->error);
    }
}

function getRole() {
    global $conn, $response;

    $role_id = intval($_GET['role_id'] ?? $_POST['role_id'] ?? 0);

    $stmt = $conn->prepare("SELECT * FROM roles WHERE role_id = ?");
    $stmt->bind_param('i', $role_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    if ($result) {
        $response['success'] = true;
        $response['data'] = $result;
    } else {
        throw new Exception('ไม่พบบทบาท');
    }
}

function listRoles() {
    global $conn, $response;

    $active_only = isset($_GET['active_only']) ? intval($_GET['active_only']) : 0;

    $sql = "SELECT * FROM roles";
    if ($active_only) {
        $sql .= " WHERE is_active = 1";
    }
    $sql .= " ORDER BY display_order ASC, role_name ASC";

    $result = $conn->query($sql);
    $roles = [];
    while ($row = $result->fetch_assoc()) {
        $roles[] = $row;
    }

    $response['success'] = true;
    $response['data'] = $roles;
}

function assignUserRole() {
    global $conn, $response;

    $user_id = intval($_POST['user_id'] ?? 0);
    $role_id = intval($_POST['role_id'] ?? 0);
    $is_primary = intval($_POST['is_primary'] ?? 0);
    $notes = trim($_POST['notes'] ?? '');
    $assigned_by = $_SESSION['user_id'];

    if ($user_id <= 0 || $role_id <= 0) {
        throw new Exception('ข้อมูลไม่ครบถ้วน');
    }

    // Check if already assigned
    $check = $conn->prepare("SELECT id FROM user_roles WHERE user_id = ? AND role_id = ?");
    $check->bind_param('ii', $user_id, $role_id);
    $check->execute();

    if ($check->get_result()->num_rows > 0) {
        // Update existing
        $stmt = $conn->prepare("UPDATE user_roles SET is_primary = ?, is_active = 1, notes = ?, assigned_by = ?, assigned_at = NOW() WHERE user_id = ? AND role_id = ?");
        $stmt->bind_param('isiii', $is_primary, $notes, $assigned_by, $user_id, $role_id);
    } else {
        // Insert new
        $stmt = $conn->prepare("INSERT INTO user_roles (user_id, role_id, is_primary, notes, assigned_by) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param('iiisi', $user_id, $role_id, $is_primary, $notes, $assigned_by);
    }

    // If setting as primary, unset other primaries
    if ($is_primary) {
        $upd_primary = $conn->prepare("UPDATE user_roles SET is_primary = 0 WHERE user_id = ? AND role_id != ?");
        $upd_primary->bind_param("ii", $user_id, $role_id);
        $upd_primary->execute();
    }

    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = 'กำหนดบทบาทสำเร็จ';
    } else {
        throw new Exception('ไม่สามารถกำหนดบทบาทได้: ' . $stmt->error);
    }
}

function removeUserRole() {
    global $conn, $response;

    $user_id = intval($_POST['user_id'] ?? 0);
    $role_id = intval($_POST['role_id'] ?? 0);

    if ($user_id <= 0 || $role_id <= 0) {
        throw new Exception('ข้อมูลไม่ครบถ้วน');
    }

    $stmt = $conn->prepare("DELETE FROM user_roles WHERE user_id = ? AND role_id = ?");
    $stmt->bind_param('ii', $user_id, $role_id);

    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = 'ยกเลิกบทบาทสำเร็จ';
    } else {
        throw new Exception('ไม่สามารถยกเลิกบทบาทได้');
    }
}

function getUsersByRole() {
    global $conn, $response;

    $role_id = intval($_GET['role_id'] ?? $_POST['role_id'] ?? 0);

    $stmt = $conn->prepare("
        SELECT ur.*, u.username, u.first_name, u.last_name, u.email, u.profile_image,
               p.prefix_name, d.department_name
        FROM user_roles ur
        JOIN users u ON ur.user_id = u.user_id
        LEFT JOIN prefixes p ON u.prefix_id = p.prefix_id
        LEFT JOIN departments d ON u.department_id = d.department_id
        WHERE ur.role_id = ? AND ur.is_active = 1
        ORDER BY ur.is_primary DESC, u.first_name ASC
    ");
    $stmt->bind_param('i', $role_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }

    $response['success'] = true;
    $response['data'] = $users;
}

function getUserRoles() {
    global $conn, $response;

    $user_id = intval($_GET['user_id'] ?? $_POST['user_id'] ?? 0);

    $stmt = $conn->prepare("
        SELECT ur.*, r.role_code, r.role_name, r.role_icon, r.role_color, r.can_assign, r.can_be_assigned
        FROM user_roles ur
        JOIN roles r ON ur.role_id = r.role_id
        WHERE ur.user_id = ? AND ur.is_active = 1 AND r.is_active = 1
        ORDER BY ur.is_primary DESC, r.display_order ASC
    ");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $roles = [];
    while ($row = $result->fetch_assoc()) {
        $roles[] = $row;
    }

    $response['success'] = true;
    $response['data'] = $roles;
}
?>
