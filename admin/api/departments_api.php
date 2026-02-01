<?php
/**
 * Departments API
 * AJAX endpoint for Department CRUD operations
 */

header('Content-Type: application/json');
require_once '../../config/database.php';

$response = ['success' => false, 'message' => ''];

// Debug logging
$debug_log = __DIR__ . '/../../debug_api.log';

try {
    $action = $_POST['action'] ?? '';
    
    // Log request
    $log_entry = date('Y-m-d H:i:s') . " | Action: $action | Data: " . json_encode($_POST) . "\n";
    file_put_contents($debug_log, $log_entry, FILE_APPEND);

    switch ($action) {
        case 'add':
        case 'update':
            // Get form data
            $parent_department_id = !empty($_POST['parent_department_id']) ? intval($_POST['parent_department_id']) : null;
            $department_code = strtoupper(trim($_POST['department_code'] ?? ''));
            $department_name = trim($_POST['department_name'] ?? '');
            $short_name = strtoupper(trim($_POST['short_name'] ?? ''));
            $level = intval($_POST['level'] ?? 1);
            $level_type = trim($_POST['level_type'] ?? '');
            $manager_user_id = !empty($_POST['manager_user_id']) ? intval($_POST['manager_user_id']) : null;
            $building = trim($_POST['building'] ?? '');
            $floor = trim($_POST['floor'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $budget_code = trim($_POST['budget_code'] ?? '');
            $status = trim($_POST['status'] ?? 'active');

            // Validation
            if (empty($department_code) || empty($department_name) || $level < 1 || $level > 4) {
                throw new Exception('กรุณากรอกข้อมูลที่จำเป็นให้ครบถ้วน');
            }

            // Validate department_code format
            if (!preg_match('/^[A-Z0-9._-]+$/', $department_code)) {
                throw new Exception('รหัสหน่วยงานต้องเป็นภาษาอังกฤษตัวพิมพ์ใหญ่และตัวเลขเท่านั้น');
            }

            // Validate short_name length
            if (!empty($short_name) && mb_strlen($short_name) > 5) {
                throw new Exception('ชื่อย่อต้องไม่เกิน 5 ตัวอักษร');
            }

            // Validate level_type based on parent's type (if parent exists)
            if ($parent_department_id) {
                $parent_check = $conn->prepare("SELECT level_type FROM departments WHERE department_id = ?");
                $parent_check->bind_param("i", $parent_department_id);
                $parent_check->execute();
                $parent_result = $parent_check->get_result();

                if ($parent_result->num_rows > 0) {
                    $parent = $parent_result->fetch_assoc();
                    $parent_type = $parent['level_type'];

                    // Define valid child types based on parent type
                    $valid_child_types = [
                        'สำนัก' => ['ส่วน', 'ฝ่าย'],
                        'กอง' => ['ฝ่าย'],
                        'พิเศษ' => ['ฝ่าย'],
                        'ส่วน' => ['ฝ่าย'],
                        'ฝ่าย' => ['งาน'],
                        'กลุ่มงาน' => ['งาน']
                    ];

                    if (!empty($level_type) && isset($valid_child_types[$parent_type])) {
                        if (!in_array($level_type, $valid_child_types[$parent_type])) {
                            $allowed = implode(', ', $valid_child_types[$parent_type]);
                            throw new Exception("ประเภทหน่วยงาน \"$level_type\" ไม่สามารถอยู่ใต้ \"$parent_type\" ได้ (อนุญาต: $allowed)");
                        }
                    }
                } else {
                    throw new Exception('หน่วยงานแม่ไม่พบในระบบ');
                }
            }

            if ($action == 'add') {
                // Check duplicate department_code
                $check = $conn->prepare("SELECT department_id FROM departments WHERE department_code = ?");
                $check->bind_param("s", $department_code);
                $check->execute();
                if ($check->get_result()->num_rows > 0) {
                    throw new Exception('รหัสหน่วยงานนี้มีอยู่ในระบบแล้ว');
                }

                // Insert new department
                $stmt = $conn->prepare("INSERT INTO departments (parent_department_id, department_code, department_name, short_name, level, level_type, manager_user_id, building, floor, phone, email, budget_code, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

                $stmt->bind_param("isssissssssss", $parent_department_id, $department_code, $department_name, $short_name, $level, $level_type, $manager_user_id, $building, $floor, $phone, $email, $budget_code, $status);

                if ($stmt->execute()) {
                    $response['success'] = true;
                    $response['message'] = 'เพิ่มหน่วยงานสำเร็จ';
                } else {
                    // Handle duplicate name error from database constraint
                    if (strpos($stmt->error, 'unit_name_parent') !== false) {
                        throw new Exception('ชื่อหน่วยงานนี้มีอยู่ในหน่วยงานแม่นี้แล้ว');
                    }
                    throw new Exception('เกิดข้อผิดพลาดในการเพิ่มหน่วยงาน: ' . $stmt->error);
                }
            } else {
                // Update existing department
                $id = intval($_POST['id'] ?? 0);
                if ($id <= 0) {
                    throw new Exception('ID ไม่ถูกต้อง');
                }

                // Check duplicate department_code (exclude current)
                $check = $conn->prepare("SELECT department_id FROM departments WHERE department_code = ? AND department_id != ?");
                $check->bind_param("si", $department_code, $id);
                $check->execute();
                if ($check->get_result()->num_rows > 0) {
                    throw new Exception('รหัสหน่วยงานนี้มีอยู่ในระบบแล้ว');
                }

                // Prevent setting parent to self or child
                if ($parent_department_id == $id) {
                    throw new Exception('ไม่สามารถตั้งหน่วยงานแม่เป็นตัวเองได้');
                }

                $stmt = $conn->prepare("UPDATE departments SET parent_department_id=?, department_code=?, department_name=?, short_name=?, level=?, level_type=?, manager_user_id=?, building=?, floor=?, phone=?, email=?, budget_code=?, status=? WHERE department_id=?");

                $stmt->bind_param("isssissssssssi", $parent_department_id, $department_code, $department_name, $short_name, $level, $level_type, $manager_user_id, $building, $floor, $phone, $email, $budget_code, $status, $id);

                if ($stmt->execute()) {
                    $response['success'] = true;
                    $response['message'] = 'อัพเดทหน่วยงานสำเร็จ';
                } else {
                    // Handle duplicate name error from database constraint
                    if (strpos($stmt->error, 'unit_name_parent') !== false) {
                        throw new Exception('ชื่อหน่วยงานนี้มีอยู่ในหน่วยงานแม่นี้แล้ว');
                    }
                    throw new Exception('เกิดข้อผิดพลาดในการอัพเดทหน่วยงาน: ' . $stmt->error);
                }
            }
            break;

        case 'delete':
            $id = intval($_POST['id'] ?? 0);
            if ($id <= 0) {
                throw new Exception('ID ไม่ถูกต้อง');
            }

            // Check if department has children
            $check = $conn->prepare("SELECT COUNT(*) as count FROM departments WHERE parent_department_id = ?");
            $check->bind_param("i", $id);
            $check->execute();
            $result = $check->get_result()->fetch_assoc();

            if ($result['count'] > 0) {
                throw new Exception('ไม่สามารถลบได้ เนื่องจากมีหน่วยงานลูกอยู่ กรุณาลบหน่วยงานลูกก่อน');
            }

            $stmt = $conn->prepare("DELETE FROM departments WHERE department_id = ?");
            $stmt->bind_param("i", $id);

            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = 'ลบหน่วยงานสำเร็จ';
            } else {
                throw new Exception('เกิดข้อผิดพลาดในการลบหน่วยงาน');
            }
            break;

        case 'toggle_status':
            $id = intval($_POST['id'] ?? 0);
            $status = trim($_POST['status'] ?? 'active');

            if ($id <= 0) {
                throw new Exception('ID ไม่ถูกต้อง');
            }

            $stmt = $conn->prepare("UPDATE departments SET status = ? WHERE department_id = ?");
            $stmt->bind_param("si", $status, $id);

            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = $status == 'active' ? 'เปิดใช้งานหน่วยงานแล้ว' : 'ปิดใช้งานหน่วยงานแล้ว';
            } else {
                throw new Exception('เกิดข้อผิดพลาดในการเปลี่ยนสถานะ');
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
