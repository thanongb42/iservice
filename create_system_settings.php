<?php
/**
 * Create System Settings Table
 * สร้างตารางเก็บตั้งค่าระบบ
 */

require_once 'config/database.php';

$sql = "CREATE TABLE IF NOT EXISTS system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(255) NOT NULL UNIQUE,
    setting_value LONGTEXT,
    setting_type VARCHAR(50) DEFAULT 'text',
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

if ($conn->query($sql)) {
    echo "✓ Table 'system_settings' created successfully\n";
} else {
    echo "✗ Error creating table: " . $conn->error . "\n";
    exit;
}

// Insert default settings
$default_settings = [
    ['organization_name', 'iService', 'text', 'ชื่อองค์กร'],
    ['organization_phone', '+66-2-XXX-XXXX', 'text', 'เบอร์โทรศัพท์'],
    ['organization_address', '', 'text', 'ที่อยู่'],
    ['logo_image', '', 'text', 'โลโกขององค์กร (path)'],
    ['email_from_name', 'iService', 'text', 'ชื่อผู้ส่งอีเมล'],
    ['email_from_address', 'noreply@iservice.local', 'text', 'อีเมลผู้ส่ง'],
    ['smtp_host', 'localhost', 'text', 'SMTP Server Host'],
    ['smtp_port', '587', 'text', 'SMTP Server Port'],
    ['smtp_username', '', 'text', 'SMTP Username'],
    ['smtp_password', '', 'password', 'SMTP Password'],
    ['smtp_encryption', 'tls', 'text', 'Encryption (tls/ssl)'],
    ['backup_enable', '1', 'boolean', 'เปิดใช้งาน Automatic Backup'],
    ['backup_schedule', 'daily', 'text', 'ตารางเวลา Backup (daily/weekly/monthly)'],
    ['backup_path', '/backups/', 'text', 'Path สำหรับเก็บ Backup'],
    ['app_name', 'iService System', 'text', 'ชื่อแอปพลิเคชัน'],
    ['app_description', 'Integrated Service Management System', 'text', 'คำอธิบาย'],
];

$inserted = 0;
foreach ($default_settings as $setting) {
    $stmt = $conn->prepare("INSERT INTO system_settings (setting_key, setting_value, setting_type, description) VALUES (?, ?, ?, ?)");
    $stmt->bind_param('ssss', $setting[0], $setting[1], $setting[2], $setting[3]);
    if ($stmt->execute()) {
        $inserted++;
        echo "  ✓ {$setting[0]}\n";
    } else {
        echo "  ✗ Error: {$setting[0]} - " . $stmt->error . "\n";
    }
    $stmt->close();
}

echo "\n✓ Inserted $inserted default settings\n";
?>
