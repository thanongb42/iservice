<?php
/**
 * Log QR Code Usage
 * บันทึกสถิติการใช้งาน QR Code Generator
 * Fire-and-forget endpoint - always returns success
 */

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => true]);
    exit;
}

try {
    $qr_type = clean_input($_POST['qr_type'] ?? '');
    $qr_content = clean_input($_POST['qr_content'] ?? '');
    $qr_size = clean_input($_POST['qr_size'] ?? 'medium');
    $color_primary = clean_input($_POST['color_primary'] ?? '#000000');
    $color_background = clean_input($_POST['color_background'] ?? '#ffffff');
    $output_format = clean_input($_POST['output_format'] ?? 'png');
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';

    $stmt = $conn->prepare("INSERT INTO qr_usage_logs (qr_type, qr_content, qr_size, color_primary, color_background, output_format, ip_address) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssss", $qr_type, $qr_content, $qr_size, $color_primary, $color_background, $output_format, $ip);
    $stmt->execute();
} catch (Exception $e) {
    // Silent fail - logging should not affect user experience
}

echo json_encode(['success' => true]);
$conn->close();
?>
