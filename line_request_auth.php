<?php
/**
 * LINE Login Auth — สำหรับผู้กรอกฟอร์มคำขอ (public, ไม่ต้องมี account)
 * เริ่ม OAuth flow เพื่อดึง LINE User ID มาเก็บใน session
 * จากนั้น redirect กลับไปที่ฟอร์มคำขอ
 */


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/database.php';

// Helper: redirect พร้อม error
function authRedirectError(string $return_to, string $error): void {
    $sep = strpos($return_to, '?') !== false ? '&' : '?';
    header('Location: ' . $return_to . $sep . 'line_error=' . urlencode($error));
    exit;
}

// Validate return_to: ต้องเป็น request-form.php เท่านั้น
$return_to = $_GET['return_to'] ?? '';
if (empty($return_to) || !preg_match('/^request-form\.php(\?.*)?$/', $return_to)) {
    header('Location: index.php');
    exit;
}

$_SESSION['req_line_return_to'] = $return_to;

// ดึง LINE Login Channel ID จาก system_settings
$settings = [];
$stmt = $conn->prepare("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('line_login_channel_id','line_callback_url')");
if ($stmt && $stmt->execute()) {
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    $stmt->close();
}

$channel_id = $settings['line_login_channel_id'] ?? '';
if (empty($channel_id)) {
    authRedirectError($return_to, 'not_configured');
}

// สร้าง callback URL สำหรับ line_request_callback.php
$account_url  = $settings['line_callback_url'] ?? '';
$callback_url = '';  // initialize ก่อนเสมอ

if (!empty($account_url)) {
    $derived = str_replace('/admin/line_callback.php', '/line_request_callback.php', $account_url);
    if ($derived !== $account_url) {
        $callback_url = $derived;
    }
}

if (empty($callback_url)) {
    // Auto-detect จาก current server — ใช้ dirname(SCRIPT_NAME) เพื่อรองรับทั้ง localhost/iservice/ และ domain root
    $proto    = 'https';
    if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
        $proto = strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']);
    } elseif (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' && $_SERVER['HTTPS'] !== '') {
        $proto = 'https';
    }
    $base_path    = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
    $callback_url = $proto . '://' . $_SERVER['HTTP_HOST'] . $base_path . '/line_request_callback.php';
}

// สร้าง state token แบบ stateless (reqform.{timestamp}.{hmac24})
if (!defined('LINE_REQ_STATE_SECRET')) {
    define('LINE_REQ_STATE_SECRET', 'RCM_iService_LINE_Req_2026_@rangsitcity');
}

$timestamp = time();
$hmac  = substr(hash_hmac('sha256', 'reqform.' . $timestamp, LINE_REQ_STATE_SECRET), 0, 24);
$state = 'reqform.' . $timestamp . '.' . $hmac;

$_SESSION['req_line_state'] = $state;

// Redirect ไป LINE Authorization
$params = http_build_query([
    'response_type' => 'code',
    'client_id'     => $channel_id,
    'redirect_uri'  => $callback_url,
    'state'         => $state,
    'scope'         => 'profile',
    'bot_prompt'    => 'aggressive',
]);

header('Location: https://access.line.me/oauth2/v2.1/authorize?' . $params);
exit;
