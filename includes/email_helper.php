<?php
/**
 * Email Helper Function
 * ใช้สำหรับส่งอีเมลแจ้งเตือนต่างๆ
 */

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

    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $tracking_url = "$protocol://$host/iservice/tracking.php?req=" . $request['request_code'];
    $admin_url    = "$protocol://$host/iservice/admin/service_requests.php";

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
                <tr><td class='label'>วันที่ส่ง</td><td class='value'>" . date('d/m/Y H:i', strtotime($request['created_at'])) . "</td></tr>
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

    $is_localhost = in_array($_SERVER['SERVER_NAME'], ['localhost', '127.0.0.1']);

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

    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $tracking_url = "$protocol://$host/iservice/tracking.php?req=" . $request['request_code'];

    $text  = "📋 คำร้องใหม่เข้าระบบ\n";
    $text .= "────────────────\n";
    $text .= "รหัส: " . $request['request_code'] . "\n";
    $text .= "บริการ: " . $request['service_name'] . "\n";
    $text .= "ผู้ยื่น: " . $request['requester_name'] . "\n";
    $text .= "หน่วยงาน: " . ($request['department_name'] ?? '-') . "\n";
    $text .= "วันที่: " . date('d/m/Y H:i', strtotime($request['created_at'])) . "\n";
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
    $is_localhost = ($_SERVER['SERVER_NAME'] == 'localhost' || $_SERVER['SERVER_NAME'] == '127.0.0.1');
    
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
    // Assume HTTP/HTTPS based on current connection
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'];
    
    // Adjust path if script is in a subdirectory (e.g. /iservice/)
    $script_dir = dirname($_SERVER['PHP_SELF']);
    // Clean up if call stack makes PHP_SELF weird, but usually $_SERVER['REQUEST_URI'] is better base
    // Let's simplified: assume 'iservice' is the root folder name as seen in workspace
    $base_url = "$protocol://$host/iservice/tracking.php?req=" . $request['request_code'];
    
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
                <li><strong>วันที่ส่ง:</strong> " . date('d/m/Y H:i', strtotime($request['created_at'])) . "</li>
                <li><strong>หัวข้อ:</strong> " . htmlspecialchars($request['subject']) . "</li>
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
    $is_localhost = ($_SERVER['SERVER_NAME'] == 'localhost' || $_SERVER['SERVER_NAME'] == '127.0.0.1');

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

    $to = $request['requester_email'];
    $subject = "อัปเดตสถานะคำร้อง: " . $request['service_name'] . " (" . $request['request_code'] . ")";
    
    // Tracking URL
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'];
    $base_url = "$protocol://$host/iservice/tracking.php?req=" . $request['request_code'];
    
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
                <li><strong>วันที่ส่ง:</strong> " . date('d/m/Y H:i', strtotime($request['created_at'])) . "</li>
                <li><strong>หัวข้อ:</strong> " . htmlspecialchars($request['subject']) . "</li>
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
    $headers .= "From: iService Alert <noreply@rangsit.local>" . "\r\n";
    
    // Log or Send
    if ($is_localhost) {
        $log_content = "----------------------------------------\n";
        $log_content .= "UPDATE NOTIFICATION\n";
        $log_content .= "To: $to\nSubject: $subject\nStatus: $status_label\nLink: $base_url\n\n";
        file_put_contents(__DIR__ . '/../storage/email_log.txt', $log_content, FILE_APPEND);
        return true; 
    } else {
        return @mail($to, $subject, $message, $headers);
    }
}
