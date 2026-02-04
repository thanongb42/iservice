<?php
/**
 * Email Helper Function
 * ใช้สำหรับส่งอีเมลแจ้งเตือนต่างๆ
 */

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
