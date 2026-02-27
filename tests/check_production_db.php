<?php
/**
 * Production DB Structure Checker
 * เช็คว่า production มีโครงสร้าง DB ที่ต้องการแล้วหรือยัง
 * ลบไฟล์นี้ออกหลังใช้งานเสร็จ
 */
require_once '../config/database.php';

header('Content-Type: application/json; charset=utf-8');

$checks = [];

// 1. prefixes table
$r = $conn->query("SHOW TABLES LIKE 'prefixes'");
$checks['prefixes_table_exists'] = $r->num_rows > 0;

if ($checks['prefixes_table_exists']) {
    $r2 = $conn->query("SELECT COUNT(*) as cnt FROM prefixes");
    $checks['prefixes_row_count'] = (int)$r2->fetch_assoc()['cnt'];
}

// 2. service_requests.requester_prefix_id
$r3 = $conn->query("SHOW COLUMNS FROM service_requests LIKE 'requester_prefix_id'");
$checks['service_requests.requester_prefix_id'] = $r3->num_rows > 0;

// 3. request_internet_details columns
$r4 = $conn->query("SHOW COLUMNS FROM request_internet_details LIKE 'citizen_id'");
$checks['request_internet_details.citizen_id'] = $r4->num_rows > 0;

$r5 = $conn->query("SHOW COLUMNS FROM request_internet_details LIKE 'citizen_id_last3'");
$checks['request_internet_details.citizen_id_last3_still_exists'] = $r5->num_rows > 0;

echo json_encode(['checks' => $checks, 'server' => $_SERVER['HTTP_HOST'] ?? 'unknown'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
