<?php
/**
 * Visitor Tracking API v2
 * - Server-side session management (visitor_hash + 30min window)
 * - Same visitor → same session (ถ้า active ใน 30 นาที)
 * - ออกไป 30+ นาที → session ใหม่
 * - คนเดิมเข้าหลายหน้า → นับ 1 unique visitor
 */
date_default_timezone_set('Asia/Bangkok');
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }

require_once __DIR__ . '/../config/database.php';
$pdo = getPDO();

$body = json_decode(file_get_contents('php://input'), true) ?? [];

$client_sid   = preg_replace('/[^a-zA-Z0-9_]/', '', $body['session_id']    ?? '');
$page_url     = substr($body['page_url']     ?? '', 0, 500);
$page_title   = substr($body['page_title']   ?? '', 0, 255);
$page_cat     = preg_replace('/[^a-z0-9_]/', '', $body['page_category'] ?? 'general');
$referrer     = substr($body['referrer']     ?? '', 0, 500);
$consent      = (int)($body['consent']       ?? 0);

if (!$page_url) { echo json_encode(['ok'=>false]); exit; }

// ── Visitor fingerprint ───────────────────────────────────────────────────────
$ip  = trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '')[0]);
$ua  = $_SERVER['HTTP_USER_AGENT'] ?? '';
$vhash = hash('sha256', $ip . $ua . 'rssc_v2_salt');

$device = 'desktop';
if (preg_match('/Mobile|Android|iPhone/i', $ua)) $device = 'mobile';
elseif (preg_match('/iPad|Tablet/i', $ua))       $device = 'tablet';

$browser = 'Other';
if      (preg_match('/Edg/i',     $ua)) $browser = 'Edge';
elseif  (preg_match('/Chrome/i',  $ua)) $browser = 'Chrome';
elseif  (preg_match('/Firefox/i', $ua)) $browser = 'Firefox';
elseif  (preg_match('/Safari/i',  $ua)) $browser = 'Safari';

try {
    // ── 1. หา active session (ใน 30 นาที) สำหรับ visitor นี้ ─────────────────
    $active = $pdo->prepare("
        SELECT session_id, page_count
        FROM visitor_sessions
        WHERE visitor_hash = ?
          AND last_activity >= DATE_SUB(NOW(), INTERVAL 30 MINUTE)
        ORDER BY last_activity DESC
        LIMIT 1
    ");
    $active->execute([$vhash]);
    $sess = $active->fetch(PDO::FETCH_ASSOC);

    if ($sess) {
        // ── session เดิม: อัปเดต last_activity ─────────────────────────────
        $sid = $sess['session_id'];
        $pdo->prepare("
            UPDATE visitor_sessions
            SET last_activity=NOW(), page_count=page_count+1, consent_given=GREATEST(consent_given,?)
            WHERE session_id=?
        ")->execute([$consent, $sid]);
    } else {
        // ── session ใหม่ ────────────────────────────────────────────────────
        $sid = bin2hex(random_bytes(16));
        $pdo->prepare("
            INSERT INTO visitor_sessions
                (session_id, client_sid, visitor_hash, device_type, browser, referrer, consent_given, page_count)
            VALUES (?,?,?,?,?,?,?,1)
        ")->execute([$sid, $client_sid, $vhash, $device, $browser, $referrer, $consent]);
    }

    // ── 2. บันทึก pageview เสมอ ──────────────────────────────────────────────
    $pdo->prepare("
        INSERT INTO visitor_pageviews
            (session_id, visitor_hash, page_url, page_title, page_category)
        VALUES (?,?,?,?,?)
    ")->execute([$sid, $vhash, $page_url, $page_title, $page_cat]);

    echo json_encode(['ok'=>true, 'sid'=>$sid]);

} catch (Exception $e) {
    echo json_encode(['ok'=>false]);
}
