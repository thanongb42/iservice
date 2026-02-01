<?php
/**
 * Get Departments API
 * API สำหรับดึงข้อมูลหน่วยงานแบบ cascade 4 ระดับ
 */

header('Content-Type: application/json');
require_once '../config/database.php';

$response = ['success' => false, 'data' => []];

try {
    $level = intval($_GET['level'] ?? 0);
    $parent_id = isset($_GET['parent_id']) && $_GET['parent_id'] !== '' ? intval($_GET['parent_id']) : null;

    if ($level === 0) {
        // ดึงข้อมูลทั้งหมดสำหรับ initialization
        $sql = "SELECT department_id, parent_department_id, department_code, department_name, short_name, level, level_type
                FROM departments
                WHERE status = 'active'
                ORDER BY level ASC, department_name ASC";
        $result = $conn->query($sql);

        $departments = [];
        while ($row = $result->fetch_assoc()) {
            $departments[] = $row;
        }

        $response['success'] = true;
        $response['data'] = $departments;
    } else {
        // ดึงข้อมูลตาม level และ parent_id
        if ($parent_id === null) {
            // ระดับ 1 (ไม่มี parent) - เรียงตาม department_id
            $sql = "SELECT department_id, department_code, department_name, short_name, level, level_type
                    FROM departments
                    WHERE level = ? AND parent_department_id IS NULL AND status = 'active'
                    ORDER BY department_id ASC";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $level);
        } else {
            // ระดับ 2-4 (มี parent)
            $sql = "SELECT department_id, department_code, department_name, short_name, level, level_type
                    FROM departments
                    WHERE level = ? AND parent_department_id = ? AND status = 'active'
                    ORDER BY department_name ASC";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $level, $parent_id);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        $departments = [];
        while ($row = $result->fetch_assoc()) {
            $departments[] = $row;
        }

        $response['success'] = true;
        $response['data'] = $departments;
    }

} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
$conn->close();
?>
