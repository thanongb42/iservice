<?php
/**
 * LINE Login Callback — สำหรับผู้กรอกฟอร์มคำขอ (public)
 * ไม่ต้องมี account ในระบบ — เพียงแค่เก็บ LINE User ID ใน session
 * แล้ว redirect กลับไปที่ฟอร์มคำขอ
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/database.php';

if (!defined('LINE_REQ_STATE_SECRET')) {
    define('LINE_REQ_STATE_SECRET', 'RCM_iService_LINE_Req_2026_@rangsitcity');
}

$return_to = $_SESSION['req_line_return_to'] ?? 'index.php';
unset($_SESSION['req_line_return_to']);

// Helper: redirect back ไปฟอร์มพร้อม error
function redirectError(string $error, string $return_to): void {
    $sep = strpos($return_to, '?') !== false ? '&' : '?';
    header('Location: ' . $return_to . $sep . 'line_error=' . urlencode($error));
    exit;
}

// Helper: auto-detect protocol
function detectProto(): string {
    if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
        return strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']);
    }
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' && $_SERVER['HTTPS'] !== '') {
        return 'https';
    }
    return 'https'; // production default
}

// ── 1. Verify state ────────────────────────────────────────────────────────────

$state         = $_GET['state'] ?? '';
$session_state = $_SESSION['req_line_state'] ?? '';
unset($_SESSION['req_line_state']);

if (empty($state) || $state !== $session_state) {
    redirectError('invalid_state', $return_to);
}

$parts = explode('.', $state);
if (count($parts) !== 3 || $parts[0] !== 'reqform') {
    redirectError('bad_state_format', $return_to);
}

[, $ts_s, $recv_hmac] = $parts;
$timestamp = intval($ts_s);
if ($timestamp <= 0 || abs(time() - $timestamp) > 900) {
    redirectError('state_expired', $return_to);
}

$expected = substr(hash_hmac('sha256', 'reqform.' . $timestamp, LINE_REQ_STATE_SECRET), 0, 24);
if (!hash_equals($expected, $recv_hmac)) {
    redirectError('state_mismatch', $return_to);
}

// ── 2. Handle LINE error response ──────────────────────────────────────────────

if (isset($_GET['error'])) {
    redirectError($_GET['error_description'] ?? $_GET['error'], $return_to);
}

$code = $_GET['code'] ?? '';
if (empty($code)) {
    redirectError('no_code', $return_to);
}

// ── 3. ดึง settings จาก DB ────────────────────────────────────────────────────

$settings = [];
$stmt = $conn->prepare("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('line_login_channel_id','line_login_channel_secret','line_callback_url')");
if ($stmt && $stmt->execute()) {
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    $stmt->close();
}

$channel_id     = $settings['line_login_channel_id'] ?? '';
$channel_secret = $settings['line_login_channel_secret'] ?? '';

if (empty($channel_id) || empty($channel_secret)) {
    redirectError('not_configured', $return_to);
}

// สร้าง callback URL (ต้องตรงกับที่ใช้ใน line_request_auth.php)
$account_url  = $settings['line_callback_url'] ?? '';
$callback_url = '';  // initialize ก่อนเสมอ

if (!empty($account_url)) {
    $derived = str_replace('/admin/line_callback.php', '/line_request_callback.php', $account_url);
    if ($derived !== $account_url) {
        $callback_url = $derived;
    }
}

if (empty($callback_url)) {
    $base_path    = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
    $callback_url = detectProto() . '://' . $_SERVER['HTTP_HOST'] . $base_path . '/line_request_callback.php';
}

// ── 4. Exchange code → access token ───────────────────────────────────────────

$post_data = http_build_query([
    'grant_type'    => 'authorization_code',
    'code'          => $code,
    'redirect_uri'  => $callback_url,
    'client_id'     => $channel_id,
    'client_secret' => $channel_secret,
]);

$ch = curl_init('https://api.line.me/oauth2/v2.1/token');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $post_data,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
    CURLOPT_TIMEOUT        => 10,
    CURLOPT_SSL_VERIFYPEER => true,
]);
$token_response = curl_exec($ch);
$token_http     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_err       = curl_error($ch);
curl_close($ch);

if ($curl_err || $token_http !== 200) {
    error_log('line_request_callback token error: HTTP=' . $token_http . ' curl=' . $curl_err);
    redirectError('token_failed', $return_to);
}

$token_json   = json_decode($token_response, true);
$access_token = $token_json['access_token'] ?? '';
if (empty($access_token)) {
    redirectError('no_token', $return_to);
}

// ── 5. Fetch LINE profile ──────────────────────────────────────────────────────

$ch = curl_init('https://api.line.me/v2/profile');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $access_token],
    CURLOPT_TIMEOUT        => 10,
    CURLOPT_SSL_VERIFYPEER => true,
]);
$profile_response = curl_exec($ch);
$profile_http     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_err         = curl_error($ch);
curl_close($ch);

if ($curl_err || $profile_http !== 200) {
    error_log('line_request_callback profile error: HTTP=' . $profile_http . ' curl=' . $curl_err);
    redirectError('profile_failed', $return_to);
}

$line_profile      = json_decode($profile_response, true);
$line_user_id      = $line_profile['userId']      ?? '';
$line_display_name = $line_profile['displayName'] ?? '';

if (empty($line_user_id)) {
    redirectError('no_user_id', $return_to);
}

// ── 6. เก็บใน session แล้ว redirect กลับฟอร์ม ────────────────────────────────

$_SESSION['req_line_user_id']      = $line_user_id;
$_SESSION['req_line_display_name'] = $line_display_name;

$sep = strpos($return_to, '?') !== false ? '&' : '?';
header('Location: ' . $return_to . $sep . 'line_connected=1');
exit;
