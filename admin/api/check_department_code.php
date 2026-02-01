<?php
/**
 * Check Department Code API
 * Real-time check if department code already exists
 */

header('Content-Type: application/json');
require_once '../../config/database.php';

$response = ['available' => false];

try {
    $code = strtoupper(trim($_GET['code'] ?? ''));
    $excludeId = intval($_GET['exclude_id'] ?? 0);

    if (empty($code)) {
        echo json_encode(['available' => false, 'message' => 'กรุณากรอกรหัสหน่วยงาน']);
        exit;
    }

    // Check if code exists
    if ($excludeId > 0) {
        // Exclude current ID (for edit mode)
        $stmt = $conn->prepare("SELECT department_id FROM departments WHERE department_code = ? AND department_id != ?");
        $stmt->bind_param("si", $code, $excludeId);
    } else {
        // Check for new record
        $stmt = $conn->prepare("SELECT department_id FROM departments WHERE department_code = ?");
        $stmt->bind_param("s", $code);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $response['available'] = false;
        $response['message'] = 'รหัสนี้ถูกใช้งานแล้ว';
    } else {
        $response['available'] = true;
        $response['message'] = 'รหัสนี้สามารถใช้งานได้';
    }

} catch (Exception $e) {
    $response['available'] = false;
    $response['message'] = 'เกิดข้อผิดพลาด';
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
$conn->close();
?>
