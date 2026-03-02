<?php
/**
 * LINE Messaging API Helper
 * ส่ง push message ไปยัง LINE user โดยตรง
 */

/**
 * ส่ง push message ไปยัง LINE user ตาม line_user_id
 *
 * @param string $line_user_id  LINE User ID ของผู้รับ
 * @param string $message       ข้อความที่ต้องการส่ง
 * @param mysqli $conn          Database connection
 * @return bool                 true ถ้าส่งสำเร็จ (HTTP 200)
 */
function send_line_push_to_user(string $line_user_id, string $message, $conn): bool {
    if (empty($line_user_id) || empty($message)) {
        return false;
    }

    // Fetch Messaging API token from system_settings
    $stmt = $conn->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'line_channel_token' LIMIT 1");
    if (!$stmt) return false;
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $token = $row['setting_value'] ?? '';

    if (empty($token)) {
        error_log('line_helper: line_channel_token is not configured');
        return false;
    }

    $payload = json_encode([
        'to'       => $line_user_id,
        'messages' => [
            ['type' => 'text', 'text' => $message]
        ]
    ]);

    $ch = curl_init('https://api.line.me/v2/bot/message/push');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token,
        ],
        CURLOPT_TIMEOUT        => 5,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    $result    = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_err  = curl_error($ch);
    curl_close($ch);

    if ($curl_err) {
        error_log('line_helper curl error: ' . $curl_err);
        return false;
    }

    if ($http_code !== 200) {
        error_log('line_helper push failed HTTP ' . $http_code . ': ' . $result);
        return false;
    }

    return true;
}
