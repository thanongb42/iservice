<?php
/**
 * LINE OAuth Callback Handler
 */

require_once '../config/database.php';

// ── Constants & Helpers ───────────────────────────────────────────────────────

define('LINE_STATE_SECRET', 'RCM_iService_LINE_OAuth_2026_@rangsitcity');

function detectProtocol(): string {
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') return 'https';
    if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') return 'https';
    if (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on') return 'https';
    if (!empty($_SERVER['HTTP_FRONT_END_HTTPS']) && strtolower($_SERVER['HTTP_FRONT_END_HTTPS']) === 'on') return 'https';
    if (($_SERVER['SERVER_PORT'] ?? '') == '443') return 'https';
    return 'http';
}

// Verify stateless HMAC state — returns user_id (>0) on success, 0 on failure
function lineStateVerify(string $state): int {
    $parts = explode('.', $state);
    if (count($parts) !== 3) {
        error_log('line_callback: state has ' . count($parts) . ' parts (expected 3): ' . $state);
        return 0;
    }
    [$uid_s, $ts_s, $recv_hmac] = $parts;
    $user_id   = intval($uid_s);
    $timestamp = intval($ts_s);
    if ($user_id <= 0 || $timestamp <= 0) {
        error_log('line_callback: invalid user_id or timestamp in state');
        return 0;
    }
    $age = abs(time() - $timestamp);
    if ($age > 900) {
        error_log('line_callback: state expired, age=' . $age . 's');
        return 0;
    }
    $payload  = $user_id . '.' . $timestamp;
    $expected = substr(hash_hmac('sha256', $payload, LINE_STATE_SECRET), 0, 24);
    if (!hash_equals($expected, $recv_hmac)) {
        error_log('line_callback: HMAC mismatch. expected=' . $expected . ' got=' . $recv_hmac);
        return 0;
    }
    return $user_id;
}

function getLineSetting(string $key): string {
    global $conn;
    $stmt = $conn->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ? LIMIT 1");
    if (!$stmt) return '';
    $stmt->bind_param('s', $key);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return $row['setting_value'] ?? '';
}

// ── 1. Verify state ───────────────────────────────────────────────────────────
$raw_state = $_GET['state'] ?? '';
error_log('line_callback: received state=' . $raw_state);

$user_id = lineStateVerify($raw_state);
if ($user_id <= 0) {
    header('Location: user_profile.php?error=' . urlencode('invalid_state'));
    exit;
}

// ── 2. Check for error response from LINE ────────────────────────────────────
if (isset($_GET['error'])) {
    $err_msg = $_GET['error_description'] ?? $_GET['error'];
    header('Location: user_profile.php?error=' . urlencode($err_msg));
    exit;
}

// ── 3. Get authorization code ─────────────────────────────────────────────────
$code = $_GET['code'] ?? '';
if (empty($code)) {
    header('Location: user_profile.php?error=' . urlencode('no_code'));
    exit;
}

// ── 4. Exchange code → access token ──────────────────────────────────────────
$channel_id     = getLineSetting('line_login_channel_id');
$channel_secret = getLineSetting('line_login_channel_secret');

if (empty($channel_id) || empty($channel_secret)) {
    header('Location: user_profile.php?error=' . urlencode('LINE Login ยังไม่ได้ตั้งค่า Channel ID/Secret'));
    exit;
}

$callback_url = getLineSetting('line_callback_url');
if (empty($callback_url)) {
    $protocol     = detectProtocol();
    $callback_url = $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['SCRIPT_NAME'];
}

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
    error_log('line_callback token error: HTTP=' . $token_http . ' curl=' . $curl_err . ' body=' . $token_response);
    header('Location: user_profile.php?error=' . urlencode('token_exchange_failed (HTTP ' . $token_http . ')'));
    exit;
}

$token_json   = json_decode($token_response, true);
$access_token = $token_json['access_token'] ?? '';
if (empty($access_token)) {
    header('Location: user_profile.php?error=' . urlencode('no_access_token'));
    exit;
}

// ── 5. Fetch LINE profile ─────────────────────────────────────────────────────
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
    error_log('line_callback profile error: HTTP=' . $profile_http . ' curl=' . $curl_err);
    header('Location: user_profile.php?error=' . urlencode('profile_fetch_failed'));
    exit;
}

$line_profile = json_decode($profile_response, true);
$line_user_id = $line_profile['userId']      ?? '';
$line_name    = $line_profile['displayName'] ?? '';
$line_picture = $line_profile['pictureUrl']  ?? '';

if (empty($line_user_id)) {
    header('Location: user_profile.php?error=' . urlencode('no_line_user_id'));
    exit;
}

// ── 6. Check if already linked to another account ────────────────────────────
$check = $conn->prepare("SELECT user_id FROM users WHERE line_user_id = ? AND user_id != ? LIMIT 1");
$check->bind_param('si', $line_user_id, $user_id);
$check->execute();
if ($check->get_result()->num_rows > 0) {
    header('Location: user_profile.php?error=' . urlencode('LINE account นี้ถูกใช้งานโดยผู้ใช้อื่นแล้ว'));
    exit;
}

// ── 7. Save to database ───────────────────────────────────────────────────────
$stmt = $conn->prepare("UPDATE users SET line_user_id = ?, line_display_name = ?, line_picture_url = ?, line_linked_at = NOW() WHERE user_id = ?");
$stmt->bind_param('sssi', $line_user_id, $line_name, $line_picture, $user_id);

if ($stmt->execute()) {
    error_log('line_callback: linked user_id=' . $user_id . ' line_user_id=' . $line_user_id);
    header('Location: user_profile.php?success=line_linked');
} else {
    error_log('line_callback db error: ' . $stmt->error);
    header('Location: user_profile.php?error=' . urlencode('db_save_failed'));
}
exit;
