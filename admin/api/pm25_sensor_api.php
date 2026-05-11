<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once '../../config/database.php';
$pdo = getPDO();

$body   = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $body['action'] ?? '';

if ($action === 'update') {
    $id     = (int)($body['id'] ?? 0);
    $name   = trim($body['name'] ?? '');
    $sn     = trim($body['sn']   ?? '');
    $sim    = trim($body['sim']  ?? '');
    $lat    = ($body['lat'] !== '' && $body['lat'] !== null) ? (float)$body['lat'] : null;
    $lng    = ($body['lng'] !== '' && $body['lng'] !== null) ? (float)$body['lng'] : null;
    $active = (int)($body['active'] ?? 1);

    if (!$id || !$name) {
        echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ครบถ้วน']);
        exit;
    }

    $stmt = $pdo->prepare("
        UPDATE pm25_sensors
        SET location_name  = ?,
            serial_number  = ?,
            sim_number     = ?,
            lat            = ?,
            lng            = ?,
            is_active      = ?
        WHERE id = ?
    ");
    $stmt->execute([$name, $sn ?: null, $sim ?: null, $lat, $lng, $active, $id]);

    echo json_encode(['success' => true]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unknown action']);
