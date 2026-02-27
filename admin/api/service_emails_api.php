<?php
/**
 * Service Notification Emails API
 * จัดการอีเมลแจ้งเตือนสำหรับแต่ละบริการ
 * Actions: list, add, edit, delete, toggle_active
 */

header('Content-Type: application/json; charset=utf-8');
session_start();
require_once '../../config/database.php';

// Check admin access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'ไม่มีสิทธิ์เข้าถึง'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Auto-create table if not exists
$conn->query("
    CREATE TABLE IF NOT EXISTS `service_notification_emails` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `service_id` int(11) NOT NULL,
      `email` varchar(255) NOT NULL,
      `name` varchar(100) DEFAULT NULL,
      `is_active` tinyint(1) NOT NULL DEFAULT 1,
      `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
      PRIMARY KEY (`id`),
      UNIQUE KEY `unique_service_email` (`service_id`, `email`),
      KEY `service_id` (`service_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

$action = $_REQUEST['action'] ?? '';

try {
    switch ($action) {
        case 'list':
            // Get all emails for a specific service
            $service_id = intval($_GET['service_id'] ?? 0);
            if ($service_id <= 0) {
                throw new Exception('กรุณาระบุ service_id');
            }

            $stmt = $conn->prepare("SELECT * FROM service_notification_emails WHERE service_id = ? ORDER BY name ASC, email ASC");
            $stmt->bind_param('i', $service_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $emails = [];
            while ($row = $result->fetch_assoc()) {
                $emails[] = $row;
            }
            echo json_encode(['success' => true, 'data' => $emails], JSON_UNESCAPED_UNICODE);
            break;

        case 'list_all':
            // Get all emails grouped by service
            $result = $conn->query("
                SELECT sne.*, ms.service_name, ms.service_code, ms.icon, ms.color_code
                FROM service_notification_emails sne
                JOIN my_service ms ON sne.service_id = ms.id
                ORDER BY ms.display_order ASC, ms.service_name ASC, sne.name ASC
            ");
            $emails = [];
            while ($row = $result->fetch_assoc()) {
                $emails[] = $row;
            }
            echo json_encode(['success' => true, 'data' => $emails], JSON_UNESCAPED_UNICODE);
            break;

        case 'add':
            $service_id = intval($_POST['service_id'] ?? 0);
            $email = trim($_POST['email'] ?? '');
            $name = trim($_POST['name'] ?? '');

            if ($service_id <= 0) {
                throw new Exception('กรุณาเลือกบริการ');
            }
            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('กรุณากรอกอีเมลที่ถูกต้อง');
            }

            // Check duplicate
            $check = $conn->prepare("SELECT id FROM service_notification_emails WHERE service_id = ? AND email = ?");
            $check->bind_param('is', $service_id, $email);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                throw new Exception('อีเมลนี้ถูกเพิ่มในบริการนี้แล้ว');
            }

            $stmt = $conn->prepare("INSERT INTO service_notification_emails (service_id, email, name) VALUES (?, ?, ?)");
            $name_val = !empty($name) ? $name : null;
            $stmt->bind_param('iss', $service_id, $email, $name_val);

            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'เพิ่มอีเมลสำเร็จ', 'id' => $conn->insert_id], JSON_UNESCAPED_UNICODE);
            } else {
                throw new Exception('ไม่สามารถเพิ่มอีเมลได้');
            }
            break;

        case 'edit':
            $id = intval($_POST['id'] ?? 0);
            $email = trim($_POST['email'] ?? '');
            $name = trim($_POST['name'] ?? '');

            if ($id <= 0) {
                throw new Exception('กรุณาระบุ ID');
            }
            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('กรุณากรอกอีเมลที่ถูกต้อง');
            }

            // Check duplicate (exclude current record)
            $check = $conn->prepare("SELECT id FROM service_notification_emails WHERE service_id = (SELECT service_id FROM service_notification_emails WHERE id = ?) AND email = ? AND id != ?");
            $check->bind_param('isi', $id, $email, $id);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                throw new Exception('อีเมลนี้ถูกใช้งานในบริการนี้แล้ว');
            }

            $name_val = !empty($name) ? $name : null;
            $stmt = $conn->prepare("UPDATE service_notification_emails SET email = ?, name = ? WHERE id = ?");
            $stmt->bind_param('ssi', $email, $name_val, $id);

            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'แก้ไขอีเมลสำเร็จ'], JSON_UNESCAPED_UNICODE);
            } else {
                throw new Exception('ไม่สามารถแก้ไขอีเมลได้');
            }
            break;

        case 'delete':
            $id = intval($_POST['id'] ?? 0);
            if ($id <= 0) {
                throw new Exception('กรุณาระบุ ID');
            }

            $stmt = $conn->prepare("DELETE FROM service_notification_emails WHERE id = ?");
            $stmt->bind_param('i', $id);

            if ($stmt->execute() && $stmt->affected_rows > 0) {
                echo json_encode(['success' => true, 'message' => 'ลบอีเมลสำเร็จ'], JSON_UNESCAPED_UNICODE);
            } else {
                throw new Exception('ไม่พบรายการที่ต้องการลบ');
            }
            break;

        case 'toggle_active':
            $id = intval($_POST['id'] ?? 0);
            $is_active = intval($_POST['is_active'] ?? 0);

            if ($id <= 0) {
                throw new Exception('กรุณาระบุ ID');
            }

            $stmt = $conn->prepare("UPDATE service_notification_emails SET is_active = ? WHERE id = ?");
            $stmt->bind_param('ii', $is_active, $id);

            if ($stmt->execute()) {
                $status_text = $is_active ? 'เปิดใช้งาน' : 'ปิดใช้งาน';
                echo json_encode(['success' => true, 'message' => $status_text . 'อีเมลสำเร็จ'], JSON_UNESCAPED_UNICODE);
            } else {
                throw new Exception('ไม่สามารถอัปเดตได้');
            }
            break;

        default:
            throw new Exception('ไม่พบ action ที่ต้องการ');
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}

$conn->close();
