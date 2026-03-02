<?php
/**
 * LINE Login Callback — login via LINE account
 * Separate from admin/line_callback.php (which handles account linking)
 *
 * Flow:
 * 1. Verify stateless HMAC state  (format: login.{timestamp}.{hmac24})
 * 2. Exchange authorization code  → access token
 * 3. Fetch LINE profile            → line_user_id
 * 4. Look up user by line_user_id → create PHP session
 * 5. Redirect to dashboard
 */

require_once 'config/database.php';
session_start();

// ── Constants & Helpers ────────────────────────────────────────────────────────

define('LINE_LOGIN_STATE_SECRET', 'RCM_iService_LINE_OAuth_2026_@rangsitcity');

function detectProtocol(): string {
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') return 'https';
    if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') return 'https';
    if (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on') return 'https';
    if (!empty($_SERVER['HTTP_FRONT_END_HTTPS']) && strtolower($_SERVER['HTTP_FRONT_END_HTTPS']) === 'on') return 'https';
    if (($_SERVER['SERVER_PORT'] ?? '') == '443') return 'https';
    return 'http';
}

// Verify stateless state — format: login.{timestamp}.{hmac24}
function lineLoginStateVerify(string $state): bool {
    $parts = explode('.', $state);
    if (count($parts) !== 3 || $parts[0] !== 'login') {
        error_log('line_login_callback: invalid state format: ' . $state);
        return false;
    }
    [, $ts_s, $recv_hmac] = $parts;
    $timestamp = intval($ts_s);
    if ($timestamp <= 0) return false;
    $age = abs(time() - $timestamp);
    if ($age > 900) {
        error_log('line_login_callback: state expired, age=' . $age . 's');
        return false;
    }
    $payload  = 'login.' . $timestamp;
    $expected = substr(hash_hmac('sha256', $payload, LINE_LOGIN_STATE_SECRET), 0, 24);
    if (!hash_equals($expected, $recv_hmac)) {
        error_log('line_login_callback: HMAC mismatch. expected=' . $expected . ' got=' . $recv_hmac);
        return false;
    }
    return true;
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

// Derive login callback URL from the saved account-linking callback URL
function getLoginCallbackUrl(): string {
    $account_url = getLineSetting('line_callback_url');
    if (!empty($account_url)) {
        $derived = str_replace('/admin/line_callback.php', '/line_login_callback.php', $account_url);
        if ($derived !== $account_url) return $derived;
    }
    // Fall back: auto-detect from current script path
    $proto = detectProtocol();
    $dir   = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
    return $proto . '://' . $_SERVER['HTTP_HOST'] . $dir . '/line_login_callback.php';
}

// ── 1. Verify state ────────────────────────────────────────────────────────────

$raw_state = $_GET['state'] ?? '';
error_log('line_login_callback: received state=' . $raw_state);

if (!lineLoginStateVerify($raw_state)) {
    header('Location: login.php?error=' . urlencode('invalid_state'));
    exit;
}

// ── 2. Check LINE error response ───────────────────────────────────────────────

if (isset($_GET['error'])) {
    $err_msg = $_GET['error_description'] ?? $_GET['error'];
    header('Location: login.php?error=' . urlencode($err_msg));
    exit;
}

// ── 3. Get authorization code ──────────────────────────────────────────────────

$code = $_GET['code'] ?? '';
if (empty($code)) {
    header('Location: login.php?error=' . urlencode('no_code'));
    exit;
}

// ── 4. Exchange code → access token ───────────────────────────────────────────

$channel_id     = getLineSetting('line_login_channel_id');
$channel_secret = getLineSetting('line_login_channel_secret');

if (empty($channel_id) || empty($channel_secret)) {
    header('Location: login.php?error=' . urlencode('LINE Login ยังไม่ได้ตั้งค่า Channel ID/Secret'));
    exit;
}

$callback_url = getLoginCallbackUrl();
error_log('line_login_callback: using callback_url=' . $callback_url);

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
    error_log('line_login_callback token error: HTTP=' . $token_http . ' curl=' . $curl_err . ' body=' . $token_response);
    header('Location: login.php?error=' . urlencode('token_exchange_failed (HTTP ' . $token_http . ')'));
    exit;
}

$token_json   = json_decode($token_response, true);
$access_token = $token_json['access_token'] ?? '';
if (empty($access_token)) {
    header('Location: login.php?error=' . urlencode('no_access_token'));
    exit;
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
    error_log('line_login_callback profile error: HTTP=' . $profile_http . ' curl=' . $curl_err);
    header('Location: login.php?error=' . urlencode('profile_fetch_failed'));
    exit;
}

$line_profile = json_decode($profile_response, true);
$line_user_id = $line_profile['userId'] ?? '';

if (empty($line_user_id)) {
    header('Location: login.php?error=' . urlencode('no_line_user_id'));
    exit;
}

// ── 6. Look up user by LINE user ID ───────────────────────────────────────────

$stmt = $conn->prepare("
    SELECT u.user_id, u.first_name, u.last_name, u.email, u.role, u.status, u.username,
           p.prefix_name
    FROM users u
    LEFT JOIN prefixes p ON u.prefix_id = p.prefix_id
    WHERE u.line_user_id = ?
    LIMIT 1
");
$stmt->bind_param('s', $line_user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    // LINE account not linked to any user account
    $msg = 'ไม่พบบัญชีที่เชื่อมต่อ LINE นี้ — กรุณาเข้าสู่ระบบด้วยชื่อผู้ใช้ก่อน แล้วผูก LINE ใน หน้าโปรไฟล์';
    header('Location: login.php?error=' . urlencode($msg));
    exit;
}

if ($user['status'] !== 'active') {
    header('Location: login.php?error=' . urlencode('บัญชีของคุณถูกระงับ กรุณาติดต่อผู้ดูแลระบบ'));
    exit;
}

// ── 7. Create session ──────────────────────────────────────────────────────────

session_regenerate_id(true);
$_SESSION['user_id']     = $user['user_id'];
$_SESSION['username']    = $user['username'];
$_SESSION['prefix_name'] = $user['prefix_name'] ?? '';
$_SESSION['first_name']  = $user['first_name'];
$_SESSION['last_name']   = $user['last_name'];
$_SESSION['email']       = $user['email'];
$_SESSION['role']        = $user['role'];
$_SESSION['full_name']   = trim(($user['prefix_name'] ?? '') . ' ' . $user['first_name'] . ' ' . $user['last_name']);

// Update last login timestamp
$upd = $conn->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
$upd->bind_param('i', $user['user_id']);
$upd->execute();

error_log('line_login_callback: successful login user_id=' . $user['user_id'] . ' role=' . $user['role']);

// ── 8. Redirect to dashboard ───────────────────────────────────────────────────

$redirect = ($user['role'] === 'admin') ? 'admin/index.php' : 'index.php';
header('Location: ' . $redirect);
exit;
