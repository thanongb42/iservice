<?php
/**
 * Verify CAPTCHA API
 * Returns JSON success/fail without consuming the session
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

$captcha_input = isset($_POST['captcha']) ? trim($_POST['captcha']) : '';
$captcha_session = isset($_SESSION['captcha_code']) ? $_SESSION['captcha_code'] : '';

$valid = !empty($captcha_input) && !empty($captcha_session) && $captcha_input === $captcha_session;

echo json_encode(['success' => $valid]);
?>
