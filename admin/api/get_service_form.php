<?php
/**
 * Get Service Form Fields (AJAX endpoint)
 * Returns HTML for service-specific form fields
 */

require_once '../../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Admin check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo '<p class="text-red-500">Unauthorized</p>';
    exit;
}

$code = isset($_GET['code']) ? strtoupper(trim($_GET['code'])) : '';

if (empty($code)) {
    echo '<p class="text-gray-400 text-sm">ไม่ระบุประเภทบริการ</p>';
    exit;
}

// Validate against whitelist from my_service table
$stmt = $conn->prepare("SELECT service_code FROM my_service WHERE service_code = ? AND is_active = 1");
$stmt->bind_param('s', $code);
$stmt->execute();
$valid = $stmt->get_result()->fetch_assoc();

if (!$valid) {
    echo '<p class="text-red-500 text-sm">ประเภทบริการไม่ถูกต้อง</p>';
    exit;
}

$form_file = __DIR__ . '/../../forms/service-form-fields-' . $code . '.php';

if (!file_exists($form_file)) {
    echo '<p class="text-gray-400 text-sm italic">ไม่มีฟอร์มเพิ่มเติมสำหรับบริการนี้</p>';
    exit;
}

// Capture and return the form HTML
ob_start();
include $form_file;
$html = ob_get_clean();
echo $html;
