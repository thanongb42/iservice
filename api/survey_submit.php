<?php
date_default_timezone_set('Asia/Bangkok');
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }

require_once __DIR__ . '/../config/database.php';
$pdo = getPDO();

$body     = json_decode(file_get_contents('php://input'), true) ?? [];
$sid      = preg_replace('/[^a-zA-Z0-9_]/', '', $body['session_id'] ?? '');
$page_url = substr($body['page_url']  ?? '', 0, 500);
$rating   = (int)($body['rating']     ?? 0);
$comment  = substr(trim($body['comment'] ?? ''), 0, 1000);
$category = preg_replace('/[^a-z0-9_]/', '', $body['category'] ?? 'general');

if ($rating < 1 || $rating > 5) {
    echo json_encode(['ok' => false, 'message' => 'invalid_rating']);
    exit;
}

$ip   = trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '')[0]);
$hash = hash('sha256', $ip . ($_SERVER['HTTP_USER_AGENT'] ?? '') . 'rssc_salt');

// ── ตรวจ column ที่มีจริงในตาราง ───────────────────────────────────────────────
try {
    $cols = $pdo->query("DESCRIBE satisfaction_surveys")->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'message' => 'table_not_found']);
    exit;
}
$hasClientSid    = in_array('client_sid',   $cols);
$hasVisitorHash  = in_array('visitor_hash', $cols);
$hasSessionId    = in_array('session_id',   $cols);
$hasIpHash       = in_array('ip_hash',      $cols);

// ── ป้องกัน submit ซ้ำ ────────────────────────────────────────────────────────
try {
    if ($hasVisitorHash) {
        $dup = $pdo->prepare("SELECT id FROM satisfaction_surveys WHERE visitor_hash=? AND page_url=? AND submitted_at>=DATE_SUB(NOW(),INTERVAL 1 HOUR) LIMIT 1");
        $dup->execute([$hash, $page_url]);
    } elseif ($hasIpHash) {
        $dup = $pdo->prepare("SELECT id FROM satisfaction_surveys WHERE ip_hash=? AND page_url=? AND submitted_at>=DATE_SUB(NOW(),INTERVAL 1 HOUR) LIMIT 1");
        $dup->execute([$hash, $page_url]);
    } else {
        $dup = $pdo->prepare("SELECT id FROM satisfaction_surveys WHERE page_url=? AND submitted_at>=DATE_SUB(NOW(),INTERVAL 10 MINUTE) LIMIT 1");
        $dup->execute([$page_url]);
    }
    if ($dup->fetch()) {
        echo json_encode(['ok' => false, 'message' => 'already_submitted']);
        exit;
    }
} catch (Exception $e) {}

// ── INSERT ────────────────────────────────────────────────────────────────────
try {
    // สร้าง INSERT แบบ dynamic ตาม column ที่มีจริง
    $insertCols = ['page_url', 'rating', 'comment', 'category'];
    $insertVals = [$page_url, $rating, $comment ?: null, $category];

    if ($hasClientSid)   { $insertCols[] = 'client_sid';   $insertVals[] = $sid;  }
    if ($hasSessionId)   { $insertCols[] = 'session_id';   $insertVals[] = $sid;  }
    if ($hasVisitorHash) { $insertCols[] = 'visitor_hash'; $insertVals[] = $hash; }
    if ($hasIpHash)      { $insertCols[] = 'ip_hash';      $insertVals[] = $hash; }

    $ph  = implode(',', array_fill(0, count($insertCols), '?'));
    $sql = "INSERT INTO satisfaction_surveys (" . implode(',', $insertCols) . ") VALUES ($ph)";
    $pdo->prepare($sql)->execute($insertVals);

    echo json_encode(['ok' => true]);
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
}
