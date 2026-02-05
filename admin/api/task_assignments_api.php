<?php
/**
 * Task Assignments API
 * AJAX endpoint for task assignment management
 */

header('Content-Type: application/json');
require_once '../../config/database.php';
session_start();

$response = ['success' => false, 'message' => ''];

try {
    // Check login
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('กรุณาเข้าสู่ระบบ');
    }

    $action = $_POST['action'] ?? $_GET['action'] ?? '';

    switch ($action) {
        case 'assign':
            assignTask();
            break;

        case 'update_status':
            updateTaskStatus();
            break;

        case 'reassign':
            reassignTask();
            break;

        case 'cancel':
            cancelTask();
            break;

        case 'get':
            getAssignment();
            break;

        case 'list_by_request':
            listByRequest();
            break;

        case 'list_my_tasks':
            listMyTasks();
            break;

        case 'get_assignable_users':
            getAssignableUsers();
            break;

        case 'get_task_history':
            getTaskHistory();
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

function canAssignTasks() {
    global $conn;
    $user_id = $_SESSION['user_id'];

    // Admin can always assign
    if ($_SESSION['role'] === 'admin') return true;

    // Check if user has a role that can assign
    $stmt = $conn->prepare("
        SELECT COUNT(*) as cnt FROM user_roles ur
        JOIN roles r ON ur.role_id = r.role_id
        WHERE ur.user_id = ? AND ur.is_active = 1 AND r.is_active = 1 AND r.can_assign = 1
    ");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    return $result['cnt'] > 0;
}

function assignTask() {
    global $conn, $response;

    if (!canAssignTasks()) {
        throw new Exception('คุณไม่มีสิทธิ์มอบหมายงาน');
    }

    $request_id = intval($_POST['request_id'] ?? 0);
    $assigned_to = intval($_POST['assigned_to'] ?? 0);
    $assigned_as_role = !empty($_POST['assigned_as_role']) ? intval($_POST['assigned_as_role']) : null;
    $priority = $_POST['priority'] ?? 'normal';
    $due_date = !empty($_POST['due_date']) ? $_POST['due_date'] : null;
    $notes = trim($_POST['notes'] ?? '');
    $assigned_by = $_SESSION['user_id'];

    if ($request_id <= 0 || $assigned_to <= 0) {
        throw new Exception('ข้อมูลไม่ครบถ้วน');
    }

    // Check if already assigned to same user
    $check = $conn->prepare("SELECT assignment_id FROM task_assignments WHERE request_id = ? AND assigned_to = ? AND status NOT IN ('completed', 'cancelled')");
    $check->bind_param('ii', $request_id, $assigned_to);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        throw new Exception('ผู้ใช้นี้ได้รับมอบหมายงานนี้แล้ว');
    }

    // Get service code and fetch event times
    $service_query = "SELECT service_code FROM service_requests WHERE request_id = ?";
    $service_stmt = $conn->prepare($service_query);
    $service_stmt->bind_param('i', $request_id);
    $service_stmt->execute();
    $service_result = $service_stmt->get_result()->fetch_assoc();
    
    if (!$service_result) {
        throw new Exception('ไม่พบรายการขอใช้บริการ');
    }
    
    $service_code = $service_result['service_code'];
    
    // Initialize time variables
    $start_time = null;
    $end_time = null;
    
    // Fetch start_time and end_time from service-specific details if available
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

    $stmt = $conn->prepare("INSERT INTO task_assignments (request_id, assigned_to, assigned_as_role, assigned_by, priority, due_date, notes, start_time, end_time) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('iiiissss', $request_id, $assigned_to, $assigned_as_role, $assigned_by, $priority, $due_date, $notes, $start_time, $end_time);

    if ($stmt->execute()) {
        $assignment_id = $conn->insert_id;

        // Log history
        logTaskHistory($assignment_id, 'assigned', null, 'pending', $assigned_by, 'มอบหมายงานใหม่');

        // Update service request status if pending
        $conn->query("UPDATE service_requests SET status = 'in_progress' WHERE request_id = $request_id AND status = 'pending'");

        $response['success'] = true;
        $response['message'] = 'มอบหมายงานสำเร็จ';
        $response['assignment_id'] = $assignment_id;
    } else {
        throw new Exception('ไม่สามารถมอบหมายงานได้: ' . $stmt->error);
    }
}

function updateTaskStatus() {
    global $conn, $response;

    $assignment_id = intval($_POST['assignment_id'] ?? 0);
    $new_status = $_POST['status'] ?? '';
    $notes = trim($_POST['notes'] ?? '');
    $user_id = $_SESSION['user_id'];

    $valid_statuses = ['accepted', 'in_progress', 'completed'];
    if (!in_array($new_status, $valid_statuses)) {
        throw new Exception('สถานะไม่ถูกต้อง');
    }

    // Get current assignment
    $stmt = $conn->prepare("SELECT * FROM task_assignments WHERE assignment_id = ?");
    $stmt->bind_param('i', $assignment_id);
    $stmt->execute();
    $assignment = $stmt->get_result()->fetch_assoc();

    if (!$assignment) {
        throw new Exception('ไม่พบการมอบหมายงาน');
    }

    // Check permission - must be assigned user or admin
    if ($assignment['assigned_to'] != $user_id && $_SESSION['role'] !== 'admin') {
        throw new Exception('คุณไม่มีสิทธิ์อัปเดตสถานะนี้');
    }

    $old_status = $assignment['status'];

    // Build update query
    $update_fields = ['status = ?'];
    $params = [$new_status];
    $types = 's';

    if ($new_status === 'accepted') {
        $update_fields[] = 'accepted_at = NOW()';
    } elseif ($new_status === 'in_progress') {
        $update_fields[] = 'started_at = NOW()';
    } elseif ($new_status === 'completed') {
        $update_fields[] = 'completed_at = NOW()';
        if ($notes) {
            $update_fields[] = 'completion_notes = ?';
            $params[] = $notes;
            $types .= 's';
        }
    }

    $update_fields[] = 'updated_at = NOW()';
    $params[] = $assignment_id;
    $types .= 'i';

    $sql = "UPDATE task_assignments SET " . implode(', ', $update_fields) . " WHERE assignment_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);

    if ($stmt->execute()) {
        // Log history
        $action_map = [
            'accepted' => 'accepted',
            'in_progress' => 'started',
            'completed' => 'completed'
        ];
        logTaskHistory($assignment_id, $action_map[$new_status], $old_status, $new_status, $user_id, $notes);

        // If all assignments completed, update service request
        if ($new_status === 'completed') {
            checkAndUpdateRequestStatus($assignment['request_id']);
        }

        $response['success'] = true;
        $response['message'] = 'อัปเดตสถานะสำเร็จ';
    } else {
        throw new Exception('ไม่สามารถอัปเดตสถานะได้');
    }
}

function reassignTask() {
    global $conn, $response;

    if (!canAssignTasks()) {
        throw new Exception('คุณไม่มีสิทธิ์มอบหมายงาน');
    }

    $assignment_id = intval($_POST['assignment_id'] ?? 0);
    $new_assigned_to = intval($_POST['assigned_to'] ?? 0);
    $notes = trim($_POST['notes'] ?? '');
    $user_id = $_SESSION['user_id'];

    // Get current assignment
    $stmt = $conn->prepare("SELECT * FROM task_assignments WHERE assignment_id = ?");
    $stmt->bind_param('i', $assignment_id);
    $stmt->execute();
    $assignment = $stmt->get_result()->fetch_assoc();

    if (!$assignment) {
        throw new Exception('ไม่พบการมอบหมายงาน');
    }

    $old_assigned = $assignment['assigned_to'];

    $stmt = $conn->prepare("UPDATE task_assignments SET assigned_to = ?, status = 'pending', accepted_at = NULL, started_at = NULL WHERE assignment_id = ?");
    $stmt->bind_param('ii', $new_assigned_to, $assignment_id);

    if ($stmt->execute()) {
        logTaskHistory($assignment_id, 'reassigned', null, null, $user_id, "เปลี่ยนผู้รับผิดชอบจาก #$old_assigned เป็น #$new_assigned_to. $notes");

        $response['success'] = true;
        $response['message'] = 'เปลี่ยนผู้รับผิดชอบสำเร็จ';
    } else {
        throw new Exception('ไม่สามารถเปลี่ยนผู้รับผิดชอบได้');
    }
}

function cancelTask() {
    global $conn, $response;

    $assignment_id = intval($_POST['assignment_id'] ?? 0);
    $notes = trim($_POST['notes'] ?? '');
    $user_id = $_SESSION['user_id'];

    // Get current assignment
    $stmt = $conn->prepare("SELECT * FROM task_assignments WHERE assignment_id = ?");
    $stmt->bind_param('i', $assignment_id);
    $stmt->execute();
    $assignment = $stmt->get_result()->fetch_assoc();

    if (!$assignment) {
        throw new Exception('ไม่พบการมอบหมายงาน');
    }

    // Check permission
    if (!canAssignTasks() && $assignment['assigned_to'] != $user_id) {
        throw new Exception('คุณไม่มีสิทธิ์ยกเลิกงานนี้');
    }

    $old_status = $assignment['status'];

    $stmt = $conn->prepare("UPDATE task_assignments SET status = 'cancelled' WHERE assignment_id = ?");
    $stmt->bind_param('i', $assignment_id);

    if ($stmt->execute()) {
        logTaskHistory($assignment_id, 'cancelled', $old_status, 'cancelled', $user_id, $notes);

        $response['success'] = true;
        $response['message'] = 'ยกเลิกการมอบหมายสำเร็จ';
    } else {
        throw new Exception('ไม่สามารถยกเลิกได้');
    }
}

function getAssignment() {
    global $conn, $response;

    $assignment_id = intval($_GET['assignment_id'] ?? $_POST['assignment_id'] ?? 0);

    $stmt = $conn->prepare("SELECT * FROM v_task_assignments WHERE assignment_id = ?");
    $stmt->bind_param('i', $assignment_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    if ($result) {
        $response['success'] = true;
        $response['data'] = $result;
    } else {
        throw new Exception('ไม่พบข้อมูล');
    }
}

function listByRequest() {
    global $conn, $response;

    $request_id = intval($_GET['request_id'] ?? $_POST['request_id'] ?? 0);

    $stmt = $conn->prepare("SELECT * FROM v_task_assignments WHERE request_id = ? ORDER BY created_at DESC");
    $stmt->bind_param('i', $request_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $assignments = [];
    while ($row = $result->fetch_assoc()) {
        $assignments[] = $row;
    }

    $response['success'] = true;
    $response['data'] = $assignments;
}

function listMyTasks() {
    global $conn, $response;

    $user_id = $_SESSION['user_id'];
    $status = $_GET['status'] ?? '';

    $sql = "SELECT * FROM v_task_assignments WHERE assigned_to = ?";
    $params = [$user_id];
    $types = 'i';

    if ($status) {
        $sql .= " AND status = ?";
        $params[] = $status;
        $types .= 's';
    } else {
        $sql .= " AND status NOT IN ('completed', 'cancelled')";
    }

    $sql .= " ORDER BY
        CASE priority
            WHEN 'urgent' THEN 1
            WHEN 'high' THEN 2
            WHEN 'normal' THEN 3
            WHEN 'low' THEN 4
        END,
        due_date ASC,
        created_at DESC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    $tasks = [];
    while ($row = $result->fetch_assoc()) {
        $tasks[] = $row;
    }

    $response['success'] = true;
    $response['data'] = $tasks;
}

function getAssignableUsers() {
    global $conn, $response;

    $role_id = isset($_GET['role_id']) ? intval($_GET['role_id']) : null;

    $sql = "
        SELECT DISTINCT u.user_id, u.username, u.first_name, u.last_name, u.profile_image,
               p.prefix_name, d.department_name,
               GROUP_CONCAT(DISTINCT r.role_name ORDER BY r.display_order SEPARATOR ', ') as roles
        FROM users u
        LEFT JOIN prefixes p ON u.prefix_id = p.prefix_id
        LEFT JOIN departments d ON u.department_id = d.department_id
        JOIN user_roles ur ON u.user_id = ur.user_id AND ur.is_active = 1
        JOIN roles r ON ur.role_id = r.role_id AND r.is_active = 1 AND r.can_be_assigned = 1
        WHERE u.status = 'active'
    ";

    if ($role_id) {
        $sql .= " AND ur.role_id = $role_id";
    }

    $sql .= " GROUP BY u.user_id ORDER BY u.first_name ASC";

    $result = $conn->query($sql);
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }

    $response['success'] = true;
    $response['data'] = $users;
}

function getTaskHistory() {
    global $conn, $response;

    $assignment_id = intval($_GET['assignment_id'] ?? $_POST['assignment_id'] ?? 0);

    $stmt = $conn->prepare("
        SELECT th.*, u.username, u.first_name, u.last_name
        FROM task_history th
        JOIN users u ON th.performed_by = u.user_id
        WHERE th.assignment_id = ?
        ORDER BY th.created_at DESC
    ");
    $stmt->bind_param('i', $assignment_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $history = [];
    while ($row = $result->fetch_assoc()) {
        $history[] = $row;
    }

    $response['success'] = true;
    $response['data'] = $history;
}

function logTaskHistory($assignment_id, $action, $old_status, $new_status, $performed_by, $notes = '') {
    global $conn;

    $stmt = $conn->prepare("INSERT INTO task_history (assignment_id, action, old_status, new_status, performed_by, notes) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('isssis', $assignment_id, $action, $old_status, $new_status, $performed_by, $notes);
    $stmt->execute();
}

function checkAndUpdateRequestStatus($request_id) {
    global $conn;

    // Check if all assignments are completed
    $stmt = $conn->prepare("SELECT COUNT(*) as total, SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed FROM task_assignments WHERE request_id = ? AND status != 'cancelled'");
    $stmt->bind_param('i', $request_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    if ($result['total'] > 0 && $result['total'] == $result['completed']) {
        // All tasks completed, update request status
        $conn->query("UPDATE service_requests SET status = 'completed', completed_at = NOW() WHERE request_id = $request_id");
    }
}
?>
