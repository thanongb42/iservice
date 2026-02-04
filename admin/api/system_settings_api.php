<?php
/**
 * System Settings API
 * AJAX endpoint for settings management
 */

header('Content-Type: application/json');
require_once '../../config/database.php';
session_start();

$response = ['success' => false, 'message' => ''];

try {
    // Check admin access
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        throw new Exception('ไม่มีสิทธิ์เข้าถึง');
    }

    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'update':
            $tab = $_POST['tab'] ?? '';

            if ($tab === 'organization') {
                updateOrganizationSettings();
            } elseif ($tab === 'email') {
                updateEmailSettings();
            } elseif ($tab === 'backup') {
                updateBackupSettings();
            } else {
                throw new Exception('ไม่พบ tab ที่ระบุ');
            }

            $response['success'] = true;
            $response['message'] = 'บันทึกการเปลี่ยนแปลงสำเร็จแล้ว';
            $response['reload'] = true;
            break;

        case 'test_email':
            testEmailConnection();
            break;

        case 'backup_now':
            createBackupNow();
            break;

        default:
            throw new Exception('Action ไม่ถูกต้อง');
    }

} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
    http_response_code(400);
}

echo json_encode($response);

// ============== FUNCTIONS ==============

function updateOrganizationSettings() {
    global $conn;

    $settings = [
        'organization_name' => $_POST['organization_name'] ?? '',
        'app_name' => $_POST['app_name'] ?? '',
        'organization_phone' => $_POST['organization_phone'] ?? '',
        'organization_address' => $_POST['organization_address'] ?? '',
        'app_description' => $_POST['app_description'] ?? '',
    ];

    // Handle logo upload
    if (isset($_FILES['logo_image']) && $_FILES['logo_image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $filename = $_FILES['logo_image']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed)) {
            throw new Exception('รองรับเฉพาะไฟล์ภาพ (JPG, PNG, GIF, WEBP)');
        }

        $upload_dir = '../../storage/logos/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $new_filename = 'logo_' . time() . '.' . $ext;
        $upload_path = $upload_dir . $new_filename;

        if (move_uploaded_file($_FILES['logo_image']['tmp_name'], $upload_path)) {
            $settings['logo_image'] = 'storage/logos/' . $new_filename;
        } else {
            throw new Exception('ไม่สามารถอัปโหลดไฟล์ได้');
        }
    }

    // Delete logo if requested
    if (isset($_POST['delete_logo'])) {
        $stmt = $conn->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'logo_image'");
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        if ($result && file_exists('../../' . $result['setting_value'])) {
            unlink('../../' . $result['setting_value']);
        }
        $settings['logo_image'] = '';
    }

    // Update or insert settings using INSERT ON DUPLICATE KEY UPDATE
    foreach ($settings as $key => $value) {
        $stmt = $conn->prepare("INSERT INTO system_settings (setting_key, setting_value, setting_type)
                                VALUES (?, ?, 'text')
                                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        $stmt->bind_param('ss', $key, $value);
        if (!$stmt->execute()) {
            throw new Exception('ไม่สามารถบันทึก ' . $key . ': ' . $stmt->error);
        }
        $stmt->close();
    }
}

function updateEmailSettings() {
    global $conn;

    $settings = [
        'smtp_host' => $_POST['smtp_host'] ?? '',
        'smtp_port' => $_POST['smtp_port'] ?? '',
        'smtp_username' => $_POST['smtp_username'] ?? '',
        'smtp_password' => $_POST['smtp_password'] ?? '',
        'smtp_encryption' => $_POST['smtp_encryption'] ?? 'tls',
        'email_from_name' => $_POST['email_from_name'] ?? '',
        'email_from_address' => $_POST['email_from_address'] ?? '',
    ];

    foreach ($settings as $key => $value) {
        $stmt = $conn->prepare("INSERT INTO system_settings (setting_key, setting_value, setting_type)
                                VALUES (?, ?, 'text')
                                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        $stmt->bind_param('ss', $key, $value);
        if (!$stmt->execute()) {
            throw new Exception('ไม่สามารถบันทึก ' . $key . ': ' . $stmt->error);
        }
        $stmt->close();
    }
}

function updateBackupSettings() {
    global $conn;

    $settings = [
        'backup_enable' => $_POST['backup_enable'] ?? '0',
        'backup_schedule' => $_POST['backup_schedule'] ?? 'daily',
        'backup_path' => $_POST['backup_path'] ?? '/backups/',
    ];

    foreach ($settings as $key => $value) {
        $stmt = $conn->prepare("INSERT INTO system_settings (setting_key, setting_value, setting_type)
                                VALUES (?, ?, 'text')
                                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        $stmt->bind_param('ss', $key, $value);
        if (!$stmt->execute()) {
            throw new Exception('ไม่สามารถบันทึก ' . $key . ': ' . $stmt->error);
        }
        $stmt->close();
    }
}

function testEmailConnection() {
    global $conn, $response;
    
    // Get email settings
    $result = $conn->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('smtp_host', 'smtp_port', 'smtp_username', 'smtp_password', 'smtp_encryption', 'email_from_name', 'email_from_address')");
    
    $settings = [];
    while ($row = $result->fetch_assoc()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }

    // Simple test - just check if settings are filled
    if (empty($settings['smtp_host']) || empty($settings['email_from_address'])) {
        throw new Exception('กรุณาตั้งค่า SMTP Host และ Email Address ก่อน');
    }

    // Log test
    $log_file = '../../logs/email_test.log';
    if (!is_dir('../../logs')) {
        mkdir('../../logs', 0755, true);
    }

    $log_content = date('Y-m-d H:i:s') . " - Email Test\n";
    $log_content .= "From: {$settings['email_from_name']} <{$settings['email_from_address']}>\n";
    $log_content .= "SMTP: {$settings['smtp_host']}:{$settings['smtp_port']}\n";
    $log_content .= "Status: Configuration looks correct\n\n";
    
    file_put_contents($log_file, $log_content, FILE_APPEND);

    $response['success'] = true;
    $response['message'] = 'ตั้งค่า Email ดูเหมือนถูกต้อง (ทดสอบโดยบันทึก)';
}

function createBackupNow() {
    global $conn, $response;
    
    // Get database credentials
    $backup_path = '../../storage/backups/';
    if (!is_dir($backup_path)) {
        mkdir($backup_path, 0755, true);
    }

    $db_name = 'green_theme_db';
    $backup_file = $backup_path . $db_name . '_' . date('Y-m-d_H-i-s') . '.sql';

    // Build mysqldump command
    $command = "mysqldump --user=" . escapeshellarg(DB_USER);
    if (!empty(DB_PASS)) {
        $command .= " --password=" . escapeshellarg(DB_PASS);
    }
    $command .= " --host=" . escapeshellarg(DB_HOST);
    $command .= " " . escapeshellarg($db_name) . " > " . escapeshellarg($backup_file);

    // Execute backup
    $output = '';
    $return_code = 0;
    exec($command, $output, $return_code);

    if ($return_code === 0 && file_exists($backup_file)) {
        $file_size = filesize($backup_file);
        $size_mb = round($file_size / 1024 / 1024, 2);
        
        // Log backup
        $log_file = '../../logs/backup.log';
        if (!is_dir('../../logs')) {
            mkdir('../../logs', 0755, true);
        }
        
        $log_content = date('Y-m-d H:i:s') . " - Backup สำเร็จ: " . basename($backup_file) . " ({$size_mb} MB)\n";
        file_put_contents($log_file, $log_content, FILE_APPEND);

        $response['success'] = true;
        $response['message'] = "Backup สำเร็จ ({$size_mb} MB) - " . basename($backup_file);
    } else {
        throw new Exception('ไม่สามารถสร้าง Backup ได้ - ตรวจสอบการตั้งค่า mysqldump');
    }
}
?>
