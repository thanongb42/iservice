<?php
/**
 * Create Correct View Based on Actual Schema
 * สร้าง View ที่ถูกต้องตามโครงสร้างจริงใน green_theme_db.sql
 */

require_once 'config/database.php';

echo "<!DOCTYPE html><html lang='th'><head><meta charset='UTF-8'><title>Create Correct View</title>";
echo "<style>body{font-family:Arial,sans-serif;max-width:1200px;margin:20px auto;padding:20px;background:#f5f5f5;}";
echo ".success{background:#d1fae5;border:2px solid #10b981;padding:15px;border-radius:8px;margin:10px 0;color:#065f46;}";
echo ".error{background:#fee2e2;border:2px solid #ef4444;padding:15px;border-radius:8px;margin:10px 0;color:#991b1b;}";
echo ".info{background:#dbeafe;border:2px solid #3b82f6;padding:15px;border-radius:8px;margin:10px 0;color:#1e40af;}";
echo "pre{background:#f3f4f6;padding:15px;border-radius:5px;overflow-x:auto;font-size:11px;}";
echo "table{border-collapse:collapse;width:100%;background:white;margin:10px 0;}";
echo "th,td{padding:10px;border:1px solid #ddd;text-align:left;}";
echo "th{background:#14b8a6;color:white;}";
echo "h1,h2{color:#333;}hr{margin:20px 0;}</style></head><body>";

echo "<h1>🔧 สร้าง View ตามโครงสร้างจริง</h1>";
echo "<hr>";

echo "<h2>📋 โครงสร้างตาราง service_requests (จริง):</h2>";
echo "<div class='info'>";
echo "<strong>Primary Key:</strong> id (ไม่ใช่ request_id)<br>";
echo "<strong>Columns:</strong><br>";
echo "- id, request_code, service_code<br>";
echo "- requester_name, requester_email, requester_phone<br>";
echo "- department (text), position<br>";
echo "- status, priority, assigned_to (text)<br>";
echo "- requested_date, target_date, completed_date<br>";
echo "- notes, admin_notes, rejection_reason<br>";
echo "- created_at, updated_at<br><br>";
echo "<strong style='color:#ef4444;'>⚠️ ไม่มี:</strong> user_id, service_name, subject, description, department_id";
echo "</div>";

echo "<hr>";
echo "<h2>🔄 สร้าง View ใหม่:</h2>";

$view_sql = "CREATE OR REPLACE VIEW v_service_requests_full AS
SELECT
    sr.id as request_id,
    sr.request_code,
    sr.service_code,

    -- Map service_code to service_name
    CASE sr.service_code
        WHEN 'EMAIL' THEN 'ขอใช้บริการ Email'
        WHEN 'NAS' THEN 'ขอใช้พื้นที่ NAS'
        WHEN 'IT_SUPPORT' THEN 'ขอรับการสนับสนุนด้าน IT'
        WHEN 'INTERNET' THEN 'ขอใช้บริการ Internet'
        WHEN 'QR_CODE' THEN 'ขอทำ QR Code'
        WHEN 'PHOTOGRAPHY' THEN 'ขอถ่ายภาพกิจกรรม'
        WHEN 'WEB_DESIGN' THEN 'ขอออกแบบเว็บไซต์'
        WHEN 'PRINTER' THEN 'ขอใช้เครื่องพิมพ์'
        ELSE sr.service_code
    END as service_name,

    -- Requester info
    sr.requester_name,
    sr.requester_email,
    sr.requester_phone,
    sr.department as department_name,
    sr.position as requester_position,

    -- Use notes as subject and description
    SUBSTRING(sr.notes, 1, 100) as subject,
    sr.notes as description,

    -- Status and priority
    sr.status,
    sr.priority,

    -- Assignment
    sr.assigned_to,
    NULL as assigned_at,
    sr.assigned_to as assigned_full_name,
    NULL as assigned_username,

    -- Notes
    sr.admin_notes,
    sr.rejection_reason,
    NULL as completion_notes,

    -- Dates
    sr.requested_date as created_at,
    sr.updated_at,
    sr.target_date as expected_completion_date,
    NULL as started_at,
    sr.completed_date as completed_at,
    NULL as cancelled_at,

    -- User info (not available, use requester info)
    NULL as user_id,
    NULL as username,
    sr.requester_email as user_email,
    sr.requester_name as user_full_name,
    NULL as first_name,
    NULL as last_name,

    -- Department
    NULL as department_id,
    NULL as department_code,

    -- Attachments (not in schema)
    NULL as attachment_file,
    NULL as attachment_original_name,
    NULL as request_data

FROM service_requests sr";

echo "<div class='info'><strong>SQL:</strong><pre>" . htmlspecialchars($view_sql) . "</pre></div>";

if ($conn->query($view_sql)) {
    echo "<div class='success'>✅ สร้าง View v_service_requests_full สำเร็จ!</div>";

    // Test
    echo "<h2>🧪 ทดสอบ View:</h2>";
    $test = $conn->query("SELECT request_id, request_code, service_code, service_name, requester_name, status, priority FROM v_service_requests_full LIMIT 5");

    if ($test && $test->num_rows > 0) {
        echo "<table>";
        echo "<tr><th>ID</th><th>Request Code</th><th>Service Code</th><th>Service Name</th><th>Requester</th><th>Status</th><th>Priority</th></tr>";

        while ($row = $test->fetch_assoc()) {
            echo "<tr>";
            echo "<td>#" . str_pad($row['request_id'], 4, '0', STR_PAD_LEFT) . "</td>";
            echo "<td>" . htmlspecialchars($row['request_code']) . "</td>";
            echo "<td>" . htmlspecialchars($row['service_code']) . "</td>";
            echo "<td><strong>" . htmlspecialchars($row['service_name']) . "</strong></td>";
            echo "<td>" . htmlspecialchars($row['requester_name']) . "</td>";
            echo "<td>" . htmlspecialchars($row['status']) . "</td>";
            echo "<td>" . htmlspecialchars($row['priority']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";

        echo "<div class='success'>✅ View ทำงานถูกต้อง! Service Name แสดงเป็นภาษาไทยแล้ว</div>";
    } else {
        echo "<div class='info'>⚠️ View ถูกสร้างแล้ว แต่ยังไม่มีข้อมูล</div>";
    }

} else {
    echo "<div class='error'>❌ ไม่สามารถสร้าง View ได้: " . $conn->error . "</div>";
}

echo "<hr>";
echo "<h2>📝 สรุปการแก้ไข:</h2>";
echo "<div class='info'>";
echo "<strong>Mapping ที่ทำ:</strong><br>";
echo "• id → request_id<br>";
echo "• service_code → service_name (ใช้ CASE WHEN)<br>";
echo "• notes → subject, description<br>";
echo "• requester_name → user_full_name<br>";
echo "• requester_email → user_email<br>";
echo "• assigned_to (text) → assigned_full_name<br>";
echo "• requested_date → created_at<br>";
echo "• target_date → expected_completion_date<br>";
echo "• completed_date → completed_at<br><br>";
echo "<strong style='color:#f59e0b;'>⚠️ ข้อจำกัด:</strong><br>";
echo "• ไม่มี user_id จริง (ไม่ได้ JOIN กับตาราง users)<br>";
echo "• ไม่มี department_id จริง (department เป็น text)<br>";
echo "• assigned_to เป็น text ไม่ใช่ FK ไปหา users<br>";
echo "</div>";

echo "<hr>";
echo "<div style='margin-top:20px;'>";
echo "<a href='admin/service_requests.php' style='display:inline-block;background:#14b8a6;color:white;padding:12px 24px;text-decoration:none;border-radius:8px;font-weight:bold;'>
📋 ทดสอบหน้าจัดการคำขอบริการ
</a>";
echo "</div>";

echo "</body></html>";

$conn->close();
?>
