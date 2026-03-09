<?php
/**
 * Email Helper Function
 * ใช้สำหรับส่งอีเมลแจ้งเตือนต่างๆ
 */

require_once __DIR__ . '/line_helper.php';

/**
 * คืนค่า base path ของแอป ('' บน production root domain, '/iservice' บน localhost)
 * ใช้ DOCUMENT_ROOT เทียบกับ __DIR__ ของ email_helper เพื่อให้ทำงานได้ทั้งสอง env
 */
function _app_base_url(): string {
    $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
        $proto = strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']);
    }
    $host      = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $doc_root  = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
    $app_dir   = rtrim(str_replace('\\', '/', dirname(__DIR__)), '/');
    $base_path = ($doc_root !== '' && strpos($app_dir, $doc_root) === 0)
                 ? substr($app_dir, strlen($doc_root))
                 : '';
    return $proto . '://' . $host . $base_path;
}

/**
 * Notify admin/staff users via Email when a new request is submitted
 */
function notify_admins_new_request($request_id, $conn) {
    // Fetch request details
    $stmt = $conn->prepare("SELECT r.*, m.service_name FROM service_requests r JOIN my_service m ON r.service_code = m.service_code WHERE r.request_id = ?");
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
    $request = $stmt->get_result()->fetch_assoc();
    if (!$request) return;

    // Fetch all admin users with email
    $result = $conn->query("SELECT CONCAT(COALESCE(p.prefix_name, ''), u.first_name, ' ', u.last_name) AS full_name, u.email FROM users u LEFT JOIN prefixes p ON u.prefix_id = p.prefix_id WHERE u.role IN ('admin','staff') AND u.status = 'active' AND u.email != '' AND u.email IS NOT NULL");
    if (!$result || $result->num_rows === 0) return;

    $base         = _app_base_url();
    $tracking_url = $base . '/tracking.php?req=' . $request['request_code'];
    $admin_url    = $base . '/admin/service_requests.php';

    $subject = "[iService] คำร้องใหม่: " . $request['service_name'] . " (" . $request['request_code'] . ")";
    $message = "
    <html><head><style>
        body{font-family:'Sarabun',sans-serif;color:#333;margin:0;padding:0}
        .header{background:#0d9488;color:#fff;padding:20px;text-align:center}
        .content{padding:24px;background:#f9fafb}
        .info-box{background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:16px;margin:16px 0}
        .label{color:#6b7280;font-size:13px}
        .value{font-weight:600;color:#111827}
        .btn{display:inline-block;margin:4px;padding:10px 20px;border-radius:6px;text-decoration:none;font-weight:bold;font-size:14px}
        .btn-teal{background:#0d9488;color:#fff}
        .btn-gray{background:#6b7280;color:#fff}
        .footer{padding:12px;text-align:center;font-size:12px;color:#9ca3af;background:#f3f4f6}
    </style></head><body>
    <div class='header'><h2 style='margin:0'>📋 มีคำร้องใหม่เข้ามา</h2></div>
    <div class='content'>
        <p>มีคำร้องบริการใหม่ถูกส่งเข้าระบบ กรุณาตรวจสอบและดำเนินการ</p>
        <div class='info-box'>
            <table width='100%' cellspacing='0' cellpadding='6'>
                <tr><td class='label'>รหัสคำร้อง</td><td class='value'>" . $request['request_code'] . "</td></tr>
                <tr><td class='label'>บริการ</td><td class='value'>" . htmlspecialchars($request['service_name']) . "</td></tr>
                <tr><td class='label'>ผู้ยื่นคำร้อง</td><td class='value'>" . htmlspecialchars($request['requester_name']) . "</td></tr>
                <tr><td class='label'>หน่วยงาน</td><td class='value'>" . htmlspecialchars($request['department_name'] ?? '-') . "</td></tr>
                <tr><td class='label'>วันที่ส่ง</td><td class='value'>" . thdate('d/m/Y H:i', strtotime($request['created_at'])) . "</td></tr>
            </table>
        </div>
        <div style='text-align:center;margin-top:20px'>
            <a href='$admin_url' class='btn btn-teal'>จัดการคำร้อง</a>
            <a href='$tracking_url' class='btn btn-gray'>ดูสถานะ</a>
        </div>
    </div>
    <div class='footer'>ระบบ iService เทศบาลนครรังสิต — แจ้งเตือนอัตโนมัติ</div>
    </body></html>";

    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8\r\n";
    $headers .= "From: iService Alert <noreply@rangsitcity.go.th>\r\n";

    $is_localhost = defined('IS_LOCAL') ? IS_LOCAL : in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1']);

    while ($user = $result->fetch_assoc()) {
        if ($is_localhost) {
            $log = "[ADMIN_NOTIFY] To: {$user['email']} | {$request['request_code']} | {$request['service_name']}\n";
            file_put_contents(__DIR__ . '/../storage/email_log.txt', $log, FILE_APPEND);
        } else {
            @mail($user['email'], $subject, $message, $headers);
        }
    }
}

/**
 * Send LINE group notification when a new request is submitted
 * ต้องตั้งค่า line_channel_token และ line_group_id ใน system_settings
 */
function send_line_notification($request_id, $conn) {
    // Load LINE settings from system_settings
    $res = $conn->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('line_channel_token','line_group_id')");
    if (!$res) return;

    $settings = [];
    while ($row = $res->fetch_assoc()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }

    $token    = $settings['line_channel_token'] ?? '';
    $group_id = $settings['line_group_id'] ?? '';
    if (empty($token) || empty($group_id)) return;

    // Fetch request details
    $stmt = $conn->prepare("SELECT r.*, m.service_name FROM service_requests r JOIN my_service m ON r.service_code = m.service_code WHERE r.request_id = ?");
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
    $request = $stmt->get_result()->fetch_assoc();
    if (!$request) return;

    $tracking_url = _app_base_url() . '/tracking.php?req=' . $request['request_code'];

    $text  = "📋 คำร้องใหม่เข้าระบบ\n";
    $text .= "────────────────\n";
    $text .= "รหัส: " . $request['request_code'] . "\n";
    $text .= "บริการ: " . $request['service_name'] . "\n";
    $text .= "ผู้ยื่น: " . $request['requester_name'] . "\n";
    $text .= "หน่วยงาน: " . ($request['department_name'] ?? '-') . "\n";
    $text .= "วันที่: " . thdate('d/m/Y H:i', strtotime($request['created_at'])) . "\n";
    $text .= "────────────────\n";
    $text .= "ติดตาม: " . $tracking_url;

    $payload = json_encode([
        'to' => $group_id,
        'messages' => [['type' => 'text', 'text' => $text]]
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
        CURLOPT_TIMEOUT        => 10,
    ]);
    curl_exec($ch);
    curl_close($ch);
}

function send_request_notification($request_id, $conn) {
    // Check if we are running in a local environment without SMTP
    $is_localhost = defined('IS_LOCAL') ? IS_LOCAL : in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1']);
    
    // In a real production environment, you would use PHPMailer here.
    // For now, on XAMPP windows default, mail() usually fails without configuration.
    // However, we will write the logic as if it works, or log it if it fails.
    
    // 1. Fetch Request Details
    $sql = "SELECT r.*, m.service_name 
            FROM service_requests r 
            JOIN my_service m ON r.service_code = m.service_code 
            WHERE r.request_id = ?";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $request = $result->fetch_assoc();
    
    if (!$request) {
        return false;
    }
    
    $to = $request['requester_email'];
    $subject = "ได้รับคำร้องขอใช้บริการ: " . $request['service_name'] . " (" . $request['request_code'] . ")";
    
    // Tracking URL
    $base_url = _app_base_url() . '/tracking.php?req=' . $request['request_code'];
    
    $message = "
    <html>
    <head>
        <title>ยืนยันการรับเรื่อง</title>
        <style>
            body { font-family: 'Sarabun', sans-serif; color: #333; }
            .header { background-color: #0d9488; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; background-color: #f9fafb; }
            .footer { padding: 10px; text-align: center; font-size: 12px; color: #666; }
            .button { background-color: #0d9488; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin-top: 10px; }
        </style>
    </head>
    <body>
        <div class='header'>
            <h1>ได้รับคำร้องแล้ว</h1>
        </div>
        <div class='content'>
            <p>เรียน คุณ " . htmlspecialchars($request['requester_name']) . ",</p>
            <p>ระบบได้รับคำร้องขอใช้บริการ <strong>" . htmlspecialchars($request['service_name']) . "</strong> ของท่านเรียบร้อยแล้ว</p>
            <p><strong>รายละเอียดคำขอ:</strong></p>
            <ul>
                <li><strong>รหัสคำขอ:</strong> " . $request['request_code'] . "</li>
                <li><strong>วันที่ส่ง:</strong> " . thdate('d/m/Y H:i', strtotime($request['created_at'])) . "</li>
                <li><strong>หัวข้อ/รายละเอียด:</strong> " . htmlspecialchars(isset($request['subject']) && $request['subject'] !== '' ? $request['subject'] : (isset($request['description']) ? mb_substr($request['description'], 0, 80) : $request['request_code'])) . "</li>
            </ul>
            <p>ท่านสามารถติดตามสถานะคำร้องได้ที่ลิงก์ด้านล่างนี้:</p>
            <p><a href='$base_url' class='button'>ติดตามสถานะคำร้อง</a></p>
            <p>หรือบันทึกรหัสคำขอของท่านไว้: <strong>" . $request['request_code'] . "</strong></p>
        </div>
        <div class='footer'>
            <p>ระบบ iService เทศบาลนครรังสิต</p>
            <p>อีเมลฉบับนี้เป็นการแจ้งเตือนอัตโนมัติ กรุณาอย่าตอบกลับ</p>
        </div>
    </body>
    </html>
    ";
    
    // Headers
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: iService Alert <noreply@rangsit.local>" . "\r\n";
    
    // Try to send
    if ($is_localhost) {
        // Log to a file instead if on localhost to avoid 'mail() not supported' or config errors blocking execution
        // Or actually attempt it but catch errors
        $log_content = "To: $to\nSubject: $subject\nLink: $base_url\n\n";
        file_put_contents(__DIR__ . '/../storage/email_log.txt', $log_content, FILE_APPEND);
        return true; 
    } else {
        return @mail($to, $subject, $message, $headers);
    }
}

function send_status_update_notification($request_id, $conn, $new_status, $notes = '') {
    // Check if we are running in a local environment without SMTP
    $is_localhost = defined('IS_LOCAL') ? IS_LOCAL : in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1']);

    // 1. Fetch Request Details
    $sql = "SELECT r.*, m.service_name 
            FROM service_requests r 
            JOIN my_service m ON r.service_code = m.service_code 
            WHERE r.request_id = ?";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $request = $result->fetch_assoc();
    
    if (!$request) {
        return false;
    }
    // ส่งเฉพาะเมื่อผู้ยื่นกรอกอีเมล (ไม่ต้อง login ก็รับแจ้งเตือนได้)
    $to = trim($request['requester_email'] ?? '');
    if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    // Status translation
    $status_label = 'รอการดำเนินการ';
    $status_color = '#eab308'; // yellow-500
    switch ($new_status) {
        case 'pending': $status_label = 'รอการดำเนินการ'; break;
        case 'in_progress': $status_label = 'กำลังดำเนินการ'; $status_color = '#3b82f6'; break;
        case 'completed': $status_label = 'เสร็จสิ้น'; $status_color = '#22c55e'; break;
        case 'rejected': $status_label = 'ถูกปฏิเสธ'; $status_color = '#ef4444'; break;
        case 'cancelled': $status_label = 'ยกเลิก'; $status_color = '#6b7280'; break;
    }

    $subject = "อัปเดตสถานะคำร้อง: " . $request['service_name'] . " (" . $request['request_code'] . ")";
    
    // Tracking URL
    $base_url = _app_base_url() . '/tracking.php?req=' . $request['request_code'];
    
    $message = "
    <html>
    <head>
        <title>อัปเดตสถานะคำร้อง</title>
        <style>
            body { font-family: 'Sarabun', sans-serif; color: #333; }
            .header { background-color: $status_color; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; background-color: #f9fafb; }
            .footer { padding: 10px; text-align: center; font-size: 12px; color: #666; }
            .button { background-color: $status_color; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin-top: 10px; }
            .status-badge { background-color: $status_color; color: white; padding: 5px 10px; border-radius: 4px; font-weight: bold; }
        </style>
    </head>
    <body>
        <div class='header'>
            <h1>สถานะคำร้องมีการเปลี่ยนแปลง</h1>
        </div>
        <div class='content'>
            <p>เรียน คุณ " . htmlspecialchars($request['requester_name']) . ",</p>
            <p>คำร้องขอใช้บริการ <strong>" . htmlspecialchars($request['service_name']) . "</strong> ของท่าน มีการอัปเดตสถานะดังนี้:</p>
            
            <p style='text-align: center; margin: 20px 0;'>
                สถานะปัจจุบัน: <span class='status-badge'>$status_label</span>
            </p>

            " . ($notes ? "<p><strong>ข้อความจากเจ้าหน้าที่:</strong> " . nl2br(htmlspecialchars($notes)) . "</p>" : "") . "
            
            <p><strong>รายละเอียดคำขอ:</strong></p>
            <ul>
                <li><strong>รหัสคำขอ:</strong> " . $request['request_code'] . "</li>
                <li><strong>วันที่ส่ง:</strong> " . thdate('d/m/Y H:i', strtotime($request['created_at'])) . "</li>
                <li><strong>หัวข้อ/รายละเอียด:</strong> " . htmlspecialchars(isset($request['subject']) && $request['subject'] !== '' ? $request['subject'] : (isset($request['description']) ? mb_substr($request['description'], 0, 80) : $request['request_code'])) . "</li>
            </ul>
            <p>ท่านสามารถตรวจสอบรายละเอียดเพิ่มเติมได้ที่:</p>
            <p><a href='$base_url' class='button'>ติดตามสถานะคำร้อง</a></p>
        </div>
        <div class='footer'>
            <p>ระบบ iService เทศบาลนครรังสิต</p>
            <p>อีเมลฉบับนี้เป็นการแจ้งเตือนอัตโนมัติ กรุณาอย่าตอบกลับ</p>
        </div>
    </body>
    </html>
    ";
    
    // Headers
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: iService Alert <noreply@rangsitcity.go.th>" . "\r\n";

    // ส่ง LINE push ไปผู้ยื่นคำขอ (ถ้ามี requester_line_user_id)
    send_line_status_to_requester($request_id, $conn, $new_status, $notes);

    // Log or Send email
    if ($is_localhost) {
        $log_content = "----------------------------------------\n";
        $log_content .= "UPDATE NOTIFICATION (requester)\n";
        $log_content .= "To: $to\nSubject: $subject\nStatus: $status_label\nLink: $base_url\n\n";
        file_put_contents(__DIR__ . '/../storage/email_log.txt', $log_content, FILE_APPEND);
        return true;
    } else {
        return @mail($to, $subject, $message, $headers);
    }
}

/**
 * ส่ง LINE push message แจ้งเตือนผู้ยื่นคำขอโดยตรง (ตอนรับคำขอใหม่)
 * ใช้ requester_line_user_id ที่บันทึกไว้ใน service_requests
 */
function send_line_to_requester(int $request_id, $conn): bool {
    $stmt = $conn->prepare("SELECT r.request_code, r.requester_name, r.requester_line_user_id, m.service_name FROM service_requests r JOIN my_service m ON r.service_code = m.service_code WHERE r.request_id = ?");
    if (!$stmt) return false;
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
    $request = $stmt->get_result()->fetch_assoc();

    if (!$request || empty($request['requester_line_user_id'])) return false;

    $tracking_url = _app_base_url() . '/tracking.php?req=' . $request['request_code'];

    $text  = "✅ ระบบได้รับคำขอของคุณแล้ว\n";
    $text .= "────────────────\n";
    $text .= "รหัสคำขอ: " . $request['request_code'] . "\n";
    $text .= "บริการ: " . $request['service_name'] . "\n";
    $text .= "ผู้ยื่น: " . $request['requester_name'] . "\n";
    $text .= "────────────────\n";
    $text .= "ติดตามสถานะ:\n" . $tracking_url;

    return send_line_push_to_user($request['requester_line_user_id'], $text, $conn);
}

/**
 * ส่ง LINE push message แจ้งผู้ยื่นคำขอเมื่อสถานะเปลี่ยน
 */
function send_line_status_to_requester(int $request_id, $conn, string $new_status, string $notes = ''): bool {
    $stmt = $conn->prepare("SELECT r.request_code, r.requester_name, r.requester_line_user_id, m.service_name FROM service_requests r JOIN my_service m ON r.service_code = m.service_code WHERE r.request_id = ?");
    if (!$stmt) return false;
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
    $request = $stmt->get_result()->fetch_assoc();

    if (!$request || empty($request['requester_line_user_id'])) return false;

    $status_label = match ($new_status) {
        'pending'     => '⏳ รอการดำเนินการ',
        'in_progress' => '🔄 กำลังดำเนินการ',
        'completed'   => '✅ เสร็จสิ้น',
        'rejected'    => '❌ ถูกปฏิเสธ',
        'cancelled'   => '🚫 ยกเลิก',
        default       => $new_status,
    };

    $tracking_url = _app_base_url() . '/tracking.php?req=' . $request['request_code'];

    $text  = "📋 อัปเดตสถานะคำขอ\n";
    $text .= "────────────────\n";
    $text .= "รหัส: " . $request['request_code'] . "\n";
    $text .= "บริการ: " . $request['service_name'] . "\n";
    $text .= "สถานะ: " . $status_label . "\n";
    if (!empty($notes)) {
        $text .= "หมายเหตุ: " . $notes . "\n";
    }
    $text .= "────────────────\n";
    $text .= "ติดตามสถานะ:\n" . $tracking_url;

    return send_line_push_to_user($request['requester_line_user_id'], $text, $conn);
}
