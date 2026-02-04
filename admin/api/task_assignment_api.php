<?php
/**
 * API สำหรับมอบหมายงานตามบทบาท/หน้าที่
 * Role-based task assignment API
 * 
 * Service Code to Role Mapping:
 * - EMAIL => it_support
 * - NAS => it_support
 * - INTERNET => it_support
 * - IT_SUPPORT => it_support
 * - WEB_DESIGN => it_support
 * - PRINTER => it_support
 * - QR_CODE => graphic_designer
 * - PHOTOGRAPHY => photographer
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

// Service code to required role mapping
$SERVICE_ROLE_MAPPING = [
    'EMAIL' => ['it_support'],
    'NAS' => ['it_support'],
    'INTERNET' => ['it_support'],
    'IT_SUPPORT' => ['it_support'],
    'QR_CODE' => ['graphic_designer'],
    'PHOTOGRAPHY' => ['photographer'],
    'WEB_DESIGN' => ['it_support'],  // Changed to it_support
    'PRINTER' => ['it_support'],
];

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ========================================
// GET: ดึงข้อมูล users ที่เหมาะสมตามบทบาท
// ========================================
if ($action === 'get_available_users') {
    $service_code = $_GET['service_code'] ?? '';
    $request_id = intval($_GET['request_id'] ?? 0);
    
    if (!$service_code || !$request_id) {
        echo json_encode(['success' => false, 'message' => 'Missing parameters']);
        exit();
    }
    
    // Get required roles for this service code
    $required_roles = $SERVICE_ROLE_MAPPING[$service_code] ?? ['manager', 'all'];
    
    // Build role placeholders for query
    $role_placeholders = implode(',', array_fill(0, count($required_roles), '?'));
    
    // Get users with matching roles
    $query = "SELECT DISTINCT
                u.user_id,
                u.username,
                u.first_name,
                u.last_name,
                GROUP_CONCAT(r.role_name SEPARATOR ', ') as roles
              FROM users u
              JOIN user_roles ur ON u.user_id = ur.user_id
              JOIN roles r ON ur.role_id = r.role_id
              WHERE r.role_code IN ($role_placeholders)
              AND ur.is_active = 1
              AND r.is_active = 1
              AND r.can_be_assigned = 1
              AND u.user_id != ?
              GROUP BY u.user_id
              ORDER BY u.first_name, u.last_name";
    
    $stmt = $conn->prepare($query);
    
    // Bind parameters
    $params = array_merge($required_roles, [$_SESSION['user_id']]);
    $types = str_repeat('s', count($required_roles)) . 'i';
    $stmt->bind_param($types, ...$params);
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    
    if (empty($users)) {
        echo json_encode([
            'success' => false,
            'message' => 'ไม่พบผู้ใช้ที่มีบทบาท: ' . implode(', ', $required_roles),
            'required_roles' => $required_roles
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'service_code' => $service_code,
            'required_roles' => $required_roles,
            'users' => $users,
            'message' => 'สามารถมอบหมายให้เฉพาะ: ' . implode(', ', $required_roles)
        ]);
    }
    exit();
}

// ========================================
// GET: ดึงข้อมูล service type
// ========================================
if ($action === 'get_service_info') {
    $service_code = $_GET['service_code'] ?? '';
    
    $query = "SELECT service_name FROM service_requests WHERE service_code = ? LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('s', $service_code);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $service = $result->fetch_assoc();
    $required_roles = $SERVICE_ROLE_MAPPING[$service_code] ?? ['manager', 'all'];
    
    echo json_encode([
        'success' => true,
        'service_code' => $service_code,
        'service_name' => $service['service_name'] ?? $service_code,
        'required_roles' => $required_roles
    ]);
    exit();
}

// ========================================
// POST: สร้าง task assignment
// ========================================
if ($action === 'assign_task') {
    $request_id = intval($_POST['request_id'] ?? 0);
    $assigned_to = intval($_POST['assigned_to'] ?? 0);
    $assigned_as_role = intval($_POST['assigned_as_role'] ?? 0);
    $priority = $_POST['priority'] ?? 'normal';
    $due_date = $_POST['due_date'] ?? null;
    $notes = $_POST['notes'] ?? '';
    
    if (!$request_id || !$assigned_to) {
        echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
        exit();
    }
    
    // Get service code of the request
    $service_query = "SELECT service_code FROM service_requests WHERE request_id = ?";
    $service_stmt = $conn->prepare($service_query);
    $service_stmt->bind_param('i', $request_id);
    $service_stmt->execute();
    $service_result = $service_stmt->get_result()->fetch_assoc();
    
    if (!$service_result) {
        echo json_encode(['success' => false, 'message' => 'Request not found']);
        exit();
    }
    
    $service_code = $service_result['service_code'];
    $required_roles = $SERVICE_ROLE_MAPPING[$service_code] ?? ['manager', 'all'];
    
    // Verify user has one of the required roles
    $check_role_query = "
        SELECT COUNT(*) as cnt FROM user_roles ur
        JOIN roles r ON ur.role_id = r.role_id
        WHERE ur.user_id = ? AND r.role_code IN ('" . implode("','", $required_roles) . "')
        AND ur.is_active = 1 AND r.is_active = 1
    ";
    
    $check_stmt = $conn->prepare("
        SELECT COUNT(*) as cnt FROM user_roles ur
        JOIN roles r ON ur.role_id = r.role_id
        WHERE ur.user_id = ? AND r.role_code IN (" . implode(',', array_fill(0, count($required_roles), '?')) . ")
        AND ur.is_active = 1 AND r.is_active = 1
    ");
    
    $params = array_merge([$assigned_to], $required_roles);
    $types = 'i' . str_repeat('s', count($required_roles));
    $check_stmt->bind_param($types, ...$params);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result()->fetch_assoc();
    
    if ($check_result['cnt'] == 0) {
        echo json_encode([
            'success' => false,
            'message' => 'ผู้ใช้นี้ไม่มีบทบาท: ' . implode(', ', $required_roles),
            'required_roles' => $required_roles
        ]);
        exit();
    }
    
    // Create task assignment
    $insert_query = "INSERT INTO task_assignments 
                    (request_id, assigned_to, assigned_as_role, assigned_by, priority, due_date, notes, status, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW())";
    
    $insert_stmt = $conn->prepare($insert_query);
    $admin_id = $_SESSION['user_id'];
    
    $insert_stmt->bind_param(
        'iiissss',
        $request_id,
        $assigned_to,
        $assigned_as_role,
        $admin_id,
        $priority,
        $due_date,
        $notes
    );
    
    if ($insert_stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'มอบหมายงานสำเร็จ',
            'assignment_id' => $insert_stmt->insert_id
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to create assignment: ' . $conn->error
        ]);
    }
    exit();
}

// Invalid action
http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Invalid action']);
?>
