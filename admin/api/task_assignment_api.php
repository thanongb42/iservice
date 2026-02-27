<?php
/**
 * API สำหรับมอบหมายงานตามบทบาท/หน้าที่
 * Role-based task assignment API
 */

// Shutdown handler - catches fatal errors that nothing else can catch
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        // Clear any output
        while (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Fatal Error: ' . $error['message'],
            'debug' => ['file' => basename($error['file']), 'line' => $error['line']]
        ]);
    }
});

// Start output buffering
ob_start();

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// Exception handler
set_exception_handler(function($e) {
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);
    error_log('task_assignment_api error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    echo json_encode([
        'success' => false,
        'message' => 'เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง'
    ]);
    exit();
});

// Start session (suppress warnings - don't convert to exceptions yet)
@session_start();

// NOW set error handler (after session_start)
set_error_handler(function($severity, $message, $file, $line) {
    // Ignore suppressed errors
    if (!(error_reporting() & $severity)) return false;
    throw new ErrorException($message, 0, $severity, $file, $line);
});

// Clear buffered output and set headers
while (ob_get_level()) ob_end_clean();
header('Content-Type: application/json; charset=utf-8');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied - กรุณาล็อกอินใหม่']);
    exit();
}

// Include database config
ob_start();
try {
    require_once '../../config/database.php';
} catch (Exception $e) {
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');
    error_log('task_assignment_api DB config error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล']);
    exit();
}
ob_end_clean();

// Verify DB connection
if (!isset($conn) || !$conn || $conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'DB connection error: ' . ($conn->connect_error ?? 'no connection')]);
    exit();
}

/**
 * Helper: Check if a column exists in a table
 */
function column_exists($conn, $table, $column) {
    $result = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
    return $result && $result->num_rows > 0;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Actions ที่ staff สามารถทำได้ (ไม่ต้องเป็น manager)
$staff_allowed_actions = ['update_status'];

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

// อนุญาต staff actions หรือต้องเป็น manager
if (!$_can_assign && !in_array($action, $staff_allowed_actions)) {
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
    $assigned_as_role = !empty($_POST['assigned_as_role']) ? intval($_POST['assigned_as_role']) : null;
    $priority = $_POST['priority'] ?? 'normal';
    $due_date = !empty($_POST['due_date']) ? $_POST['due_date'] : null;
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

    try {
        if ($service_code === 'PHOTOGRAPHY') {
            $detail_stmt = $conn->prepare("SELECT event_date, event_time_start, event_time_end FROM request_photography_details WHERE request_id = ?");
            if ($detail_stmt) {
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
        } elseif ($service_code === 'MC') {
            $detail_stmt = $conn->prepare("SELECT event_date, event_time_start, event_time_end FROM request_mc_details WHERE request_id = ?");
            if ($detail_stmt) {
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
        }
    } catch (Exception $e) {
        // Detail tables may not exist on production - continue without time data
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
    
    // Create task assignment - dynamically build query based on available columns
    $admin_id = $_SESSION['user_id'];
    
    // Check if start_time/end_time columns exist (may not exist on Production)
    $has_time_columns = column_exists($conn, 'task_assignments', 'start_time');
    
    // Build dynamic INSERT based on what columns exist
    $columns = ['request_id', 'assigned_to', 'assigned_by', 'status', 'created_at'];
    $placeholders = ['?', '?', '?', "'pending'", 'NOW()'];
    $params = [$request_id, $assigned_to, $admin_id];
    $types = 'iii';

    if (column_exists($conn, 'task_assignments', 'priority')) {
        $columns[] = 'priority';
        $placeholders[] = '?';
        $params[] = $priority;
        $types .= 's';
    }
    if (column_exists($conn, 'task_assignments', 'due_date')) {
        $columns[] = 'due_date';
        $placeholders[] = '?';
        $params[] = $due_date;
        $types .= 's';
    }
    if (column_exists($conn, 'task_assignments', 'notes')) {
        $columns[] = 'notes';
        $placeholders[] = '?';
        $params[] = $notes;
        $types .= 's';
    }
    
    // Only include assigned_as_role if it has a valid value and column exists
    if (!empty($assigned_as_role) && column_exists($conn, 'task_assignments', 'assigned_as_role')) {
        $columns[] = 'assigned_as_role';
        $placeholders[] = '?';
        $params[] = $assigned_as_role;
        $types .= 'i';
    }
    
    // Add time columns if they exist and have values
    if ($has_time_columns && ($start_time || $end_time)) {
        $columns[] = 'start_time';
        $placeholders[] = '?';
        $params[] = $start_time;
        $types .= 's';
        
        $columns[] = 'end_time';
        $placeholders[] = '?';
        $params[] = $end_time;
        $types .= 's';
    }
    
    $insert_query = "INSERT INTO task_assignments (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
    
    $insert_stmt = $conn->prepare($insert_query);
    if (!$insert_stmt) {
        echo json_encode(['success' => false, 'message' => 'SQL Prepare Error: ' . $conn->error, 'query' => $insert_query]);
        exit();
    }
    
    $insert_stmt->bind_param($types, ...$params);
    
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

    if ($new_status === 'accepted' && column_exists($conn, 'task_assignments', 'accepted_at')) {
        $update_fields .= ', accepted_at = NOW()';
    } elseif ($new_status === 'in_progress' && column_exists($conn, 'task_assignments', 'started_at')) {
        $update_fields .= ', started_at = NOW()';
    } elseif ($new_status === 'completed' && column_exists($conn, 'task_assignments', 'completed_at')) {
        $update_fields .= ', completed_at = NOW()';
    }
    
    // Update assignment status
    $update_query = "UPDATE task_assignments SET $update_fields WHERE assignment_id = ?";
    $params[] = $assignment_id;
    $types .= 'i';
    
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param($types, ...$params);
    
    if ($update_stmt->execute()) {
        // Log the status change to task history (if table exists)
        try {
            $user_id = $_SESSION['user_id'];
            $action = 'status_change';
            $history_insert = "INSERT INTO task_history (assignment_id, action, old_status, new_status, performed_by, notes)
                              VALUES (?, ?, ?, ?, ?, ?)";
            $history_stmt = $conn->prepare($history_insert);
            if ($history_stmt) {
                $history_stmt->bind_param('issssi', $assignment_id, $action, $old_status, $new_status, $user_id, $notes);
                $history_stmt->execute();
            }
        } catch (Exception $e) {
            // task_history table may not exist on production - skip logging
        }

        // Get request_id for this assignment
        $req_query = "SELECT request_id FROM task_assignments WHERE assignment_id = ?";
        $req_stmt = $conn->prepare($req_query);
        $req_stmt->bind_param('i', $assignment_id);
        $req_stmt->execute();
        $req_result = $req_stmt->get_result()->fetch_assoc();

        if ($req_result) {
            $request_id = $req_result['request_id'];

            if ($new_status === 'accepted' || $new_status === 'in_progress') {
                // Update service_requests.status to in_progress
                $upd_ip = $conn->prepare("UPDATE service_requests SET status = 'in_progress', started_at = COALESCE(started_at, NOW()) WHERE request_id = ?");
                $upd_ip->bind_param("i", $request_id);
                $upd_ip->execute();

            } elseif ($new_status === 'completed') {
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
                    $upd_done = $conn->prepare("UPDATE service_requests SET status = 'completed', completed_at = NOW() WHERE request_id = ?");
                    $upd_done->bind_param("i", $request_id);
                    $upd_done->execute();
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
    
    // Check if start_time/end_time columns exist
    if (!column_exists($conn, 'task_assignments', 'start_time')) {
        // Auto-create columns if missing
        $conn->query("ALTER TABLE task_assignments ADD COLUMN start_time DATETIME DEFAULT NULL AFTER started_at");
        $conn->query("ALTER TABLE task_assignments ADD COLUMN end_time DATETIME DEFAULT NULL AFTER start_time");
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
