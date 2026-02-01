<?php
/**
 * Service Requests API
 * AJAX endpoint for Service Request Management
 */

session_start();
require_once '../../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

try {
    $action = $_REQUEST['action'] ?? '';

    switch ($action) {
        case 'get':
            $id = intval($_GET['id'] ?? 0);

            if ($id <= 0) {
                throw new Exception('ID ไม่ถูกต้อง');
            }

            $stmt = $conn->prepare("SELECT * FROM v_service_requests_full WHERE request_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $response['success'] = true;
                $response['request'] = $result->fetch_assoc();
            } else {
                throw new Exception('ไม่พบข้อมูลคำขอ');
            }
            break;

        case 'update':
            $request_id = intval($_POST['request_id'] ?? 0);
            $status = trim($_POST['status'] ?? '');
            $priority = trim($_POST['priority'] ?? '');
            $assigned_user_id = !empty($_POST['assigned_to']) ? intval($_POST['assigned_to']) : null;
            $admin_notes = trim($_POST['admin_notes'] ?? '');
            $rejection_reason = trim($_POST['rejection_reason'] ?? '');
            $completion_notes = trim($_POST['completion_notes'] ?? '');

            if ($request_id <= 0) {
                throw new Exception('ID ไม่ถูกต้อง');
            }

            if (empty($status) || empty($priority)) {
                throw new Exception('กรุณากรอกสถานะและระดับความสำคัญ');
            }

            $valid_statuses = ['pending', 'in_progress', 'completed', 'cancelled', 'rejected'];
            if (!in_array($status, $valid_statuses)) {
                throw new Exception('สถานะไม่ถูกต้อง');
            }

            $valid_priorities = ['low', 'medium', 'high', 'urgent'];
            if (!in_array($priority, $valid_priorities)) {
                throw new Exception('ระดับความสำคัญไม่ถูกต้อง');
            }

            if ($status === 'rejected' && empty($rejection_reason)) {
                throw new Exception('กรุณาระบุเหตุผลในการปฏิเสธ');
            }

            // Get assigned user's full name if user_id provided
            $assigned_to_text = null;
            if ($assigned_user_id) {
                $user_stmt = $conn->prepare("SELECT CONCAT(COALESCE(p.prefix_name, ''), first_name, ' ', last_name) as full_name
                    FROM users u
                    LEFT JOIN prefixes p ON u.prefix_id = p.prefix_id
                    WHERE user_id = ?");
                $user_stmt->bind_param("i", $assigned_user_id);
                $user_stmt->execute();
                $user_result = $user_stmt->get_result();
                if ($user_result->num_rows > 0) {
                    $assigned_to_text = $user_result->fetch_assoc()['full_name'];
                }
            }

            // Check if completion_notes column exists
            $columns_check = $conn->query("SHOW COLUMNS FROM service_requests LIKE 'completion_notes'");
            $has_completion_notes = $columns_check->num_rows > 0;

            if ($has_completion_notes) {
                $stmt = $conn->prepare("UPDATE service_requests SET
                    status = ?,
                    priority = ?,
                    assigned_to = ?,
                    admin_notes = ?,
                    rejection_reason = ?,
                    completion_notes = ?,
                    updated_at = NOW()
                    WHERE id = ?");

                $stmt->bind_param("ssssssi",
                    $status, $priority, $assigned_to_text, $admin_notes,
                    $rejection_reason, $completion_notes, $request_id
                );
            } else {
                $stmt = $conn->prepare("UPDATE service_requests SET
                    status = ?,
                    priority = ?,
                    assigned_to = ?,
                    admin_notes = ?,
                    rejection_reason = ?,
                    updated_at = NOW()
                    WHERE id = ?");

                $stmt->bind_param("sssssi",
                    $status, $priority, $assigned_to_text, $admin_notes,
                    $rejection_reason, $request_id
                );
            }

            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = 'อัปเดตข้อมูลคำขอเรียบร้อยแล้ว';
            } else {
                throw new Exception('ไม่สามารถอัปเดตข้อมูลได้');
            }
            break;

        case 'update_status':
            $id = intval($_POST['id'] ?? 0);
            $status = trim($_POST['status'] ?? '');
            $admin_notes = trim($_POST['admin_notes'] ?? '');

            if ($id <= 0) {
                throw new Exception('ID ไม่ถูกต้อง');
            }

            $valid_statuses = ['pending', 'in_progress', 'completed', 'cancelled', 'rejected'];
            if (!in_array($status, $valid_statuses)) {
                throw new Exception('สถานะไม่ถูกต้อง');
            }

            // Update status and admin notes
            $sql = "UPDATE service_requests SET status = ?, admin_notes = ?, updated_at = NOW()";

            // If completed, set completed_date (check column name)
            if ($status === 'completed') {
                $col_check = $conn->query("SHOW COLUMNS FROM service_requests LIKE 'completed_at'");
                if ($col_check->num_rows > 0) {
                    $sql .= ", completed_at = NOW()";
                } else {
                    $col_check2 = $conn->query("SHOW COLUMNS FROM service_requests LIKE 'completed_date'");
                    if ($col_check2->num_rows > 0) {
                        $sql .= ", completed_date = NOW()";
                    }
                }
            }

            $sql .= " WHERE id = ?";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssi", $status, $admin_notes, $id);

            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = 'อัปเดตสถานะเรียบร้อยแล้ว';
            } else {
                throw new Exception('ไม่สามารถอัปเดตสถานะได้');
            }
            break;

        case 'assign':
            $id = intval($_POST['id'] ?? 0);
            $user_id = !empty($_POST['assigned_to']) ? intval($_POST['assigned_to']) : null;

            if ($id <= 0) {
                throw new Exception('ID ไม่ถูกต้อง');
            }

            // Check if assigned_to_user_id column exists
            $columns_check = $conn->query("SHOW COLUMNS FROM service_requests LIKE 'assigned_to_user_id'");
            $has_user_id_column = $columns_check->num_rows > 0;

            if ($user_id) {
                // Get user full name from users table
                $user_stmt = $conn->prepare("SELECT CONCAT(COALESCE(p.prefix_name, ''), first_name, ' ', last_name) as full_name
                    FROM users u
                    LEFT JOIN prefixes p ON u.prefix_id = p.prefix_id
                    WHERE user_id = ?");
                $user_stmt->bind_param("i", $user_id);
                $user_stmt->execute();
                $user_result = $user_stmt->get_result();

                if ($user_result->num_rows > 0) {
                    $user_row = $user_result->fetch_assoc();
                    $full_name = $user_row['full_name'];

                    // Update both assigned_to (text) and assigned_to_user_id (if exists)
                    if ($has_user_id_column) {
                        $stmt = $conn->prepare("UPDATE service_requests SET assigned_to = ?, assigned_to_user_id = ?, updated_at = NOW() WHERE id = ?");
                        $stmt->bind_param("sii", $full_name, $user_id, $id);
                    } else {
                        $stmt = $conn->prepare("UPDATE service_requests SET assigned_to = ?, updated_at = NOW() WHERE id = ?");
                        $stmt->bind_param("si", $full_name, $id);
                    }
                } else {
                    throw new Exception('ไม่พบผู้ใช้ที่เลือก');
                }
            } else {
                // Unassign - set to NULL
                if ($has_user_id_column) {
                    $stmt = $conn->prepare("UPDATE service_requests SET assigned_to = NULL, assigned_to_user_id = NULL, updated_at = NOW() WHERE id = ?");
                } else {
                    $stmt = $conn->prepare("UPDATE service_requests SET assigned_to = NULL, updated_at = NOW() WHERE id = ?");
                }
                $stmt->bind_param("i", $id);
            }

            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = $user_id ? 'มอบหมายงานเรียบร้อยแล้ว' : 'ยกเลิกการมอบหมายเรียบร้อยแล้ว';
            } else {
                throw new Exception('ไม่สามารถมอบหมายงานได้: ' . $conn->error);
            }
            break;

        case 'update_priority':
            $id = intval($_POST['id'] ?? 0);
            $priority = trim($_POST['priority'] ?? '');

            if ($id <= 0) {
                throw new Exception('ID ไม่ถูกต้อง');
            }

            $valid_priorities = ['low', 'medium', 'high', 'urgent'];
            if (!in_array($priority, $valid_priorities)) {
                throw new Exception('ระดับความสำคัญไม่ถูกต้อง');
            }

            $stmt = $conn->prepare("UPDATE service_requests SET priority = ?, updated_at = NOW() WHERE id = ?");
            $stmt->bind_param("si", $priority, $id);

            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = 'อัปเดตระดับความสำคัญเรียบร้อยแล้ว';
            } else {
                throw new Exception('ไม่สามารถอัปเดตระดับความสำคัญได้');
            }
            break;

        case 'delete':
            $id = intval($_POST['id'] ?? 0);

            if ($id <= 0) {
                throw new Exception('ID ไม่ถูกต้อง');
            }

            // Check if request exists
            $check_stmt = $conn->prepare("SELECT id FROM service_requests WHERE id = ?");
            $check_stmt->bind_param("i", $id);
            $check_stmt->execute();

            if ($check_stmt->get_result()->num_rows === 0) {
                throw new Exception('ไม่พบคำขอนี้ในระบบ');
            }

            // Delete request (CASCADE will handle related records)
            $stmt = $conn->prepare("DELETE FROM service_requests WHERE id = ?");
            $stmt->bind_param("i", $id);

            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    $response['success'] = true;
                    $response['message'] = 'ลบคำขอเรียบร้อยแล้ว';
                } else {
                    throw new Exception('ไม่พบคำขอที่ต้องการลบ');
                }
            } else {
                throw new Exception('ไม่สามารถลบคำขอได้');
            }
            break;

        case 'bulk_update_status':
            $ids = json_decode($_POST['ids'] ?? '[]', true);
            $status = trim($_POST['status'] ?? '');

            if (empty($ids) || !is_array($ids)) {
                throw new Exception('กรุณาเลือกรายการ');
            }

            $valid_statuses = ['pending', 'in_progress', 'completed', 'cancelled', 'rejected'];
            if (!in_array($status, $valid_statuses)) {
                throw new Exception('สถานะไม่ถูกต้อง');
            }

            $success_count = 0;
            $error_count = 0;

            foreach ($ids as $id) {
                $id = intval($id);
                if ($id > 0) {
                    $stmt = $conn->prepare("UPDATE service_requests SET
                        status = ?,
                        updated_at = NOW()
                        WHERE id = ?");

                    $stmt->bind_param("si", $status, $id);

                    if ($stmt->execute()) {
                        $success_count++;
                    } else {
                        $error_count++;
                    }
                }
            }

            if ($error_count === 0) {
                $response['success'] = true;
                $response['message'] = "อัปเดตสถานะของคำขอ {$success_count} รายการเรียบร้อยแล้ว";
            } else {
                $response['success'] = false;
                $response['message'] = "อัปเดตสำเร็จ {$success_count} รายการ, ล้มเหลว {$error_count} รายการ";
            }
            break;

        default:
            throw new Exception('Action ไม่ถูกต้อง');
    }

} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
$conn->close();
?>
