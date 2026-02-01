<?php
/**
 * Create v_service_requests_full View
 * สร้าง View สำหรับแสดงข้อมูลคำขอบริการแบบเต็ม
 */

require_once 'config/database.php';

echo "<h2>🔄 Create v_service_requests_full View</h2>";
echo "<hr>";

// Check if service_requests table exists
$check_table = $conn->query("SHOW TABLES LIKE 'service_requests'");
if ($check_table->num_rows === 0) {
    echo "❌ <strong style='color: red;'>ตาราง service_requests ยังไม่มีในฐานข้อมูล</strong><br>";
    echo "กรุณารันไฟล์ database/service_requests.sql ก่อน<br>";
    exit;
}

echo "✅ ตาราง service_requests มีอยู่แล้ว<br><br>";

// Create or replace view
$sql = "CREATE OR REPLACE VIEW v_service_requests_full AS
SELECT
    sr.request_id,
    sr.service_code,
    sr.service_name,
    sr.subject,
    sr.description,
    sr.status,
    sr.priority,
    sr.created_at,
    sr.updated_at,

    -- Requester Info
    p.prefix_name as requester_prefix,
    sr.requester_name,
    sr.requester_position,
    sr.requester_phone,
    sr.requester_email,

    -- Department Info
    sr.department_name,
    d.department_code,

    -- User Info (who created the request)
    u.user_id,
    u.username,
    u.email as user_email,
    CONCAT(IFNULL(up.prefix_name, ''), ' ', u.first_name, ' ', u.last_name) as user_full_name,

    -- Assigned Staff Info
    sr.assigned_to,
    sr.assigned_at,
    au.username as assigned_username,
    CONCAT(IFNULL(ap.prefix_name, ''), ' ', au.first_name, ' ', au.last_name) as assigned_full_name,

    -- Notes and Dates
    sr.admin_notes,
    sr.rejection_reason,
    sr.completion_notes,
    sr.expected_completion_date,
    sr.started_at,
    sr.completed_at,
    sr.cancelled_at,

    -- Attachments
    sr.attachment_file,
    sr.attachment_original_name,

    -- Additional Data
    sr.request_data

FROM service_requests sr
LEFT JOIN users u ON sr.user_id = u.user_id
LEFT JOIN prefixes up ON u.prefix_id = up.prefix_id
LEFT JOIN prefixes p ON sr.requester_prefix_id = p.prefix_id
LEFT JOIN departments d ON sr.department_id = d.department_id
LEFT JOIN users au ON sr.assigned_to = au.user_id
LEFT JOIN prefixes ap ON au.prefix_id = ap.prefix_id";

if ($conn->query($sql)) {
    echo "✅ <strong style='color: green;'>View v_service_requests_full สร้างสำเร็จ!</strong><br><br>";

    echo "<div style='background: #d1fae5; border: 2px solid #10b981; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
    echo "<h3 style='color: #065f46; margin-top: 0;'>✅ View ที่สร้าง:</h3>";
    echo "<p style='color: #065f46;'>v_service_requests_full - แสดงข้อมูลคำขอบริการพร้อมข้อมูลผู้ใช้, แผนก, และผู้รับผิดชอบ</p>";
    echo "</div>";

    // Test the view
    echo "<h3>ทดสอบ View:</h3>";
    $test_query = "SELECT request_id, service_name, user_full_name, status, priority, assigned_full_name
                   FROM v_service_requests_full
                   LIMIT 5";
    $test_result = $conn->query($test_query);

    if ($test_result && $test_result->num_rows > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #f3f4f6;'>";
        echo "<th style='padding: 8px;'>Request ID</th>";
        echo "<th style='padding: 8px;'>Service</th>";
        echo "<th style='padding: 8px;'>Requester</th>";
        echo "<th style='padding: 8px;'>Status</th>";
        echo "<th style='padding: 8px;'>Priority</th>";
        echo "<th style='padding: 8px;'>Assigned To</th>";
        echo "</tr>";

        while ($row = $test_result->fetch_assoc()) {
            echo "<tr>";
            echo "<td style='padding: 8px;'>#" . str_pad($row['request_id'], 4, '0', STR_PAD_LEFT) . "</td>";
            echo "<td style='padding: 8px;'>" . htmlspecialchars($row['service_name']) . "</td>";
            echo "<td style='padding: 8px;'>" . htmlspecialchars($row['user_full_name']) . "</td>";
            echo "<td style='padding: 8px;'>" . htmlspecialchars($row['status']) . "</td>";
            echo "<td style='padding: 8px;'>" . htmlspecialchars($row['priority']) . "</td>";
            echo "<td style='padding: 8px;'>" . htmlspecialchars($row['assigned_full_name'] ?? '-') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "<br>";
        echo "✅ View ทำงานถูกต้อง! พบข้อมูล " . $test_result->num_rows . " รายการ<br>";
    } else {
        echo "⚠️ View ถูกสร้างแล้ว แต่ยังไม่มีข้อมูลในตาราง service_requests<br>";
        echo "ข้อมูลจะปรากฏเมื่อมีการสร้างคำขอบริการ<br>";
    }

} else {
    echo "❌ <strong style='color: red;'>ไม่สามารถสร้าง View ได้:</strong> " . $conn->error . "<br>";
}

echo "<hr>";
echo "<div style='margin-top: 20px;'>";
echo "<a href='admin/service_requests.php' style='display: inline-block; background: #14b8a6; color: white; padding: 12px 24px; text-decoration: none; border-radius: 8px; font-weight: bold; margin-right: 10px;'>
    📋 ไปที่หน้าจัดการคำขอบริการ
</a>";
echo "<a href='admin/index.php' style='display: inline-block; background: #6b7280; color: white; padding: 12px 24px; text-decoration: none; border-radius: 8px; font-weight: bold;'>
    🏠 กลับสู่ Admin Dashboard
</a>";
echo "</div>";

echo "<hr>";
echo "<h3>📝 Columns ใน View:</h3>";
echo "<pre style='background: #f3f4f6; padding: 15px; border-radius: 5px;'>";
echo "- request_id, service_code, service_name
- subject, description, status, priority
- requester info: requester_prefix, requester_name, requester_position, requester_phone, requester_email
- department info: department_name, department_code
- user info: user_id, username, user_email, user_full_name
- assigned info: assigned_to, assigned_at, assigned_username, assigned_full_name
- notes: admin_notes, rejection_reason, completion_notes
- dates: created_at, updated_at, expected_completion_date, started_at, completed_at, cancelled_at
- attachments: attachment_file, attachment_original_name
- request_data (JSON)
";
echo "</pre>";
?>
