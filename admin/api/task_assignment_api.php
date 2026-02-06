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

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit();
}

require_once '../../config/database.php';

// Check if user has manager/all role or is legacy admin
$_can_assign = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');
if (!$_can_assign) {
    $chk = $conn->prepare("
        SELECT COUNT(*) as cnt FROM user_roles ur
        JOIN roles r ON ur.role_id = r.role_id
        WHERE ur.user_id = ? AND r.role_code IN ('manager', 'all')
        AND ur.is_active = 1 AND r.is_active = 1
    ");
    $chk->bind_param('i', $_SESSION['user_id']);
    $chk->execute();
    $_can_assign = $chk->get_result()->fetch_assoc()['cnt'] > 0;
}
if (!$_can_assign) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'ไม่มีสิทธิ์มอบหมายงาน']);
    exit();
}

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

    // Always include 'all' role (can do everything)
    if (!in_array('all', $required_roles)) {
        $required_roles[] = 'all';
    }

    // Build role placeholders for query
    $role_placeholders = implode(',', array_fill(0, count($required_roles), '?'));

    // Get users with matching roles (include self - manager may assign to themselves)
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
              GROUP BY u.user_id
              ORDER BY u.first_name, u.last_name";

    $stmt = $conn->prepare($query);

    // Bind parameters
    $params = $required_roles;
    $types = str_repeat('s', count($required_roles));
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
    
    // Get service code and fetch service-specific times
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
    
    // Fetch start_time and end_time from service-specific details if available
    $start_time = null;
    $end_time = null;
    
    if ($service_code === 'PHOTOGRAPHY') {
        $detail_query = "SELECT event_date, event_time_start, event_time_end FROM request_photography_details WHERE request_id = ?";
        $detail_stmt = $conn->prepare($detail_query);
        $detail_stmt->bind_param('i', $request_id);
        $detail_stmt->execute();
        $detail_result = $detail_stmt->get_result()->fetch_assoc();
        
        if ($detail_result) {
            if ($detail_result['event_date'] && $detail_result['event_time_start']) {
                $start_time = $detail_result['event_date'] . ' ' . $detail_result['event_time_start'];
            }
            if ($detail_result['event_date'] && $detail_result['event_time_end']) {
                $end_time = $detail_result['event_date'] . ' ' . $detail_result['event_time_end'];
            }
        }
    } elseif ($service_code === 'MC') {
        $detail_query = "SELECT event_date, event_time_start, event_time_end FROM request_mc_details WHERE request_id = ?";
        $detail_stmt = $conn->prepare($detail_query);
        $detail_stmt->bind_param('i', $request_id);
        $detail_stmt->execute();
        $detail_result = $detail_stmt->get_result()->fetch_assoc();
        
        if ($detail_result) {
            if ($detail_result['event_date'] && $detail_result['event_time_start']) {
                $start_time = $detail_result['event_date'] . ' ' . $detail_result['event_time_start'];
            }
            if ($detail_result['event_date'] && $detail_result['event_time_end']) {
                $end_time = $detail_result['event_date'] . ' ' . $detail_result['event_time_end'];
            }
        }
    }
    
    $required_roles = $SERVICE_ROLE_MAPPING[$service_code] ?? ['manager', 'all'];

    // Always include 'all' role (can do everything)
    if (!in_array('all', $required_roles)) {
        $required_roles[] = 'all';
    }

    // Verify user has one of the required roles
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
                    (request_id, assigned_to, assigned_as_role, assigned_by, priority, due_date, notes, status, start_time, end_time, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?, NOW())";
    
    $insert_stmt = $conn->prepare($insert_query);
    $admin_id = $_SESSION['user_id'];
    
    $insert_stmt->bind_param(
        'iiiisssss',
        $request_id,
        $assigned_to,
        $assigned_as_role,
        $admin_id,
        $priority,
        $due_date,
        $notes,
        $start_time,
        $end_time
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

// ========================================
// POST: Update task assignment status
// ========================================
if ($action === 'update_status') {
    $assignment_id = intval($_POST['assignment_id'] ?? 0);
    $new_status = $_POST['new_status'] ?? '';
    
    if (!$assignment_id || !in_array($new_status, ['accepted', 'in_progress', 'completed', 'cancelled'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
        exit();
    }
    
    // Get assignment to verify it belongs to current user
    $get_query = "SELECT assigned_to, status FROM task_assignments WHERE assignment_id = ?";
    $get_stmt = $conn->prepare($get_query);
    $get_stmt->bind_param('i', $assignment_id);
    $get_stmt->execute();
    $get_result = $get_stmt->get_result()->fetch_assoc();
    
    if (!$get_result || $get_result['assigned_to'] != $_SESSION['user_id']) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Permission denied']);
        exit();
    }
    
    $old_status = $get_result['status'];
    
    // Get notes if provided
    $notes = $_POST['notes'] ?? '';
    
    // Build column updates based on new status
    $update_fields = 'status = ?';
    $params = [$new_status];
    $types = 's';
    
    if ($new_status === 'accepted') {
        $update_fields .= ', accepted_at = NOW()';
    } elseif ($new_status === 'in_progress') {
        $update_fields .= ', started_at = NOW()';
    } elseif ($new_status === 'completed') {
        $update_fields .= ', completed_at = NOW()';
    }
    
    // Update assignment status
    $update_query = "UPDATE task_assignments SET $update_fields WHERE assignment_id = ?";
    $params[] = $assignment_id;
    $types .= 'i';
    
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param($types, ...$params);
    
    if ($update_stmt->execute()) {
        // Log the status change to task history
        $user_id = $_SESSION['user_id'];
        $action = 'status_change';
        $history_insert = "INSERT INTO task_history (assignment_id, action, old_status, new_status, performed_by, notes) 
                          VALUES (?, ?, ?, ?, ?, ?)";
        $history_stmt = $conn->prepare($history_insert);
        // Order: assignment_id(i), action(s), old_status(s), new_status(s), performed_by(i), notes(s)
        $history_stmt->bind_param('issssi', $assignment_id, $action, $old_status, $new_status, $user_id, $notes);
        $history_stmt->execute();
        
        // If completed, check if all assignments are done and update request status
        if ($new_status === 'completed') {
            // Get request_id for this assignment
            $req_query = "SELECT request_id FROM task_assignments WHERE assignment_id = ?";
            $req_stmt = $conn->prepare($req_query);
            $req_stmt->bind_param('i', $assignment_id);
            $req_stmt->execute();
            $req_result = $req_stmt->get_result()->fetch_assoc();
            
            if ($req_result) {
                $request_id = $req_result['request_id'];
                
                // Check if all assignments are completed
                $check_query = "SELECT COUNT(*) as total, SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed 
                               FROM task_assignments 
                               WHERE request_id = ? AND status != 'cancelled'";
                $check_stmt = $conn->prepare($check_query);
                $check_stmt->bind_param('i', $request_id);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result()->fetch_assoc();
                
                // If all tasks completed, update request status
                if ($check_result['total'] > 0 && $check_result['total'] == $check_result['completed']) {
                    $conn->query("UPDATE service_requests SET status = 'completed', completed_at = NOW() WHERE request_id = $request_id");
                }
            }
        }
        
        $status_labels = [
            'accepted' => 'รับงานแล้ว',
            'in_progress' => 'เริ่มดำเนินการแล้ว',
            'completed' => 'ดำเนินการเสร็จสิ้นแล้ว',
            'cancelled' => 'ยกเลิกงานแล้ว'
        ];
        
        echo json_encode([
            'success' => true,
            'message' => $status_labels[$new_status]
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to update status: ' . $conn->error
        ]);
    }
    exit();
}

// ========================================
// POST: Update task assignment times
// ========================================
if ($action === 'update_task_times') {
    $assignment_id = intval($_POST['assignment_id'] ?? 0);
    $start_time = $_POST['start_time'] ?? '';
    $end_time = $_POST['end_time'] ?? '';
    
    if (!$assignment_id) {
        echo json_encode(['success' => false, 'message' => 'Invalid assignment ID']);
        exit();
    }
    
    // Verify assignment belongs to current user or user is admin
    $get_query = "SELECT assigned_to FROM task_assignments WHERE assignment_id = ?";
    $get_stmt = $conn->prepare($get_query);
    $get_stmt->bind_param('i', $assignment_id);
    $get_stmt->execute();
    $get_result = $get_stmt->get_result()->fetch_assoc();
    
    if (!$get_result) {
        echo json_encode(['success' => false, 'message' => 'Assignment not found']);
        exit();
    }
    
    // Check if user is a manager - only managers can update task times
    $is_manager = false;
    $manager_check = $conn->prepare("
        SELECT COUNT(*) as cnt FROM user_roles ur
        JOIN roles r ON ur.role_id = r.role_id
        WHERE ur.user_id = ? AND r.role_code IN ('manager', 'all')
        AND ur.is_active = 1 AND r.is_active = 1
    ");
    $manager_check->bind_param('i', $_SESSION['user_id']);
    $manager_check->execute();
    $manager_result = $manager_check->get_result()->fetch_assoc();
    $is_manager = $manager_result['cnt'] > 0;
    
    // Only managers can update task times (not staff)
    if (!$is_manager && $_SESSION['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'เฉพาะผู้จัดการเท่านั้นที่สามารถแก้ไขเวลาได้']);
        exit();
    }
    
    // Update times
    $update_fields = [];
    $params = [];
    $types = '';
    
    if (!empty($start_time)) {
        $update_fields[] = 'start_time = ?';
        $params[] = $start_time;
        $types .= 's';
    }
    
    if (!empty($end_time)) {
        $update_fields[] = 'end_time = ?';
        $params[] = $end_time;
        $types .= 's';
    }
    
    if (empty($update_fields)) {
        echo json_encode(['success' => false, 'message' => 'No times provided']);
        exit();
    }
    
    $update_fields[] = 'updated_at = NOW()';
    $params[] = $assignment_id;
    $types .= 'i';
    
    $update_query = "UPDATE task_assignments SET " . implode(', ', $update_fields) . " WHERE assignment_id = ?";
    
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param($types, ...$params);
    
    if ($update_stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'บันทึกเวลาทำงานแล้ว'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to update times: ' . $conn->error
        ]);
    }
    exit();
}

// Invalid action
http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Invalid action']);
?>
