<?php
/**
 * My Service API
 * AJAX endpoint for My Service CRUD operations
 */

header('Content-Type: application/json');
require_once '../../config/database.php';

$response = ['success' => false, 'message' => ''];

try {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'add':
        case 'update':
            // Get form data
            $service_code = strtoupper(trim($_POST['service_code'] ?? ''));
            $service_name = trim($_POST['service_name'] ?? '');
            $service_name_en = trim($_POST['service_name_en'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $icon = trim($_POST['icon'] ?? '');
            $color_code = $_POST['color_code'] ?? 'blue';
            $service_url = trim($_POST['service_url'] ?? '#');
            $display_order = intval($_POST['display_order'] ?? 0);
            $is_active = isset($_POST['is_active']) ? 1 : 0;

            // Validation
            if (empty($service_code) || empty($service_name) || empty($service_name_en) || empty($icon)) {
                throw new Exception('กรุณากรอกข้อมูลที่จำเป็นให้ครบถ้วน');
            }

            // Validate service_code format (English, no spaces)
            if (!preg_match('/^[A-Z0-9_]+$/', $service_code)) {
                throw new Exception('รหัสบริการต้องเป็นภาษาอังกฤษตัวพิมพ์ใหญ่และใช้ _ แทนช่องว่างเท่านั้น');
            }

            if ($action == 'add') {
                // Check duplicate service_code
                $check = $conn->prepare("SELECT id FROM my_service WHERE service_code = ?");
                $check->bind_param("s", $service_code);
                $check->execute();
                if ($check->get_result()->num_rows > 0) {
                    throw new Exception('รหัสบริการนี้มีอยู่ในระบบแล้ว');
                }

                // Insert new service
                $stmt = $conn->prepare("INSERT INTO my_service (service_code, service_name, service_name_en, description, icon, color_code, service_url, display_order, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssssssii", $service_code, $service_name, $service_name_en, $description, $icon, $color_code, $service_url, $display_order, $is_active);

                if ($stmt->execute()) {
                    $response['success'] = true;
                    $response['message'] = 'เพิ่มบริการสำเร็จ';
                } else {
                    throw new Exception('เกิดข้อผิดพลาดในการเพิ่มบริการ: ' . $stmt->error);
                }
            } else {
                // Update existing service
                $id = intval($_POST['id'] ?? 0);
                if ($id <= 0) {
                    throw new Exception('ID ไม่ถูกต้อง');
                }

                // Check duplicate service_code (exclude current)
                $check = $conn->prepare("SELECT id FROM my_service WHERE service_code = ? AND id != ?");
                $check->bind_param("si", $service_code, $id);
                $check->execute();
                if ($check->get_result()->num_rows > 0) {
                    throw new Exception('รหัสบริการนี้มีอยู่ในระบบแล้ว');
                }

                $stmt = $conn->prepare("UPDATE my_service SET service_code=?, service_name=?, service_name_en=?, description=?, icon=?, color_code=?, service_url=?, display_order=?, is_active=? WHERE id=?");
                $stmt->bind_param("sssssssiii", $service_code, $service_name, $service_name_en, $description, $icon, $color_code, $service_url, $display_order, $is_active, $id);

                if ($stmt->execute()) {
                    $response['success'] = true;
                    $response['message'] = 'อัพเดทบริการสำเร็จ';
                } else {
                    throw new Exception('เกิดข้อผิดพลาดในการอัพเดทบริการ: ' . $stmt->error);
                }
            }
            break;

        case 'delete':
            $id = intval($_POST['id'] ?? 0);
            if ($id <= 0) {
                throw new Exception('ID ไม่ถูกต้อง');
            }

            $stmt = $conn->prepare("DELETE FROM my_service WHERE id = ?");
            $stmt->bind_param("i", $id);

            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = 'ลบบริการสำเร็จ';
            } else {
                throw new Exception('เกิดข้อผิดพลาดในการลบบริการ');
            }
            break;

        case 'toggle_active':
            $id = intval($_POST['id'] ?? 0);
            $is_active = intval($_POST['is_active'] ?? 1);

            if ($id <= 0) {
                throw new Exception('ID ไม่ถูกต้อง');
            }

            $stmt = $conn->prepare("UPDATE my_service SET is_active = ? WHERE id = ?");
            $stmt->bind_param("ii", $is_active, $id);

            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = $is_active ? 'เปิดใช้งานบริการแล้ว' : 'ปิดใช้งานบริการแล้ว';
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
