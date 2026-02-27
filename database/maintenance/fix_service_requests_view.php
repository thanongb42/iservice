<?php
/**
 * Fix Service Requests View
 * ตรวจสอบโครงสร้างตารางและสร้าง view ที่ถูกต้อง
 */

require_once 'config/database.php';

echo "<!DOCTYPE html><html lang='th'><head><meta charset='UTF-8'><title>Fix Service Requests View</title>";
echo "<style>body{font-family:Arial,sans-serif;max-width:1200px;margin:20px auto;padding:20px;background:#f5f5f5;}";
echo ".success{background:#d1fae5;border:2px solid #10b981;padding:15px;border-radius:8px;margin:10px 0;color:#065f46;}";
echo ".error{background:#fee2e2;border:2px solid #ef4444;padding:15px;border-radius:8px;margin:10px 0;color:#991b1b;}";
echo ".info{background:#dbeafe;border:2px solid #3b82f6;padding:15px;border-radius:8px;margin:10px 0;color:#1e40af;}";
echo "pre{background:#f3f4f6;padding:15px;border-radius:5px;overflow-x:auto;}";
echo "h1,h2{color:#333;}hr{margin:20px 0;border:1px solid #ccc;}</style></head><body>";

echo "<h1>🔧 Fix Service Requests View</h1>";
echo "<hr>";

// Step 1: Check table structure
echo "<h2>Step 1: ตรวจสอบโครงสร้างตาราง service_requests</h2>";

$desc_query = "DESCRIBE service_requests";
$desc_result = $conn->query($desc_query);

if ($desc_result) {
    echo "<div class='info'><strong>Columns ในตาราง service_requests:</strong><br><br>";
    echo "<table border='1' cellpadding='8' style='border-collapse:collapse;background:white;'>";
    echo "<tr style='background:#f3f4f6;'><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";

    $columns = [];
    while ($row = $desc_result->fetch_assoc()) {
        $columns[] = $row['Field'];
        echo "<tr>";
        echo "<td><strong>" . htmlspecialchars($row['Field']) . "</strong></td>";
        echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table></div>";

    // Determine the primary key column name
    $id_column = in_array('request_id', $columns) ? 'request_id' : 'id';
    echo "<div class='success'>✅ ตรวจพบ Primary Key: <strong>$id_column</strong></div>";

} else {
    echo "<div class='error'>❌ ไม่สามารถตรวจสอบโครงสร้างตารางได้</div>";
    exit;
}

echo "<hr>";

// Step 2: Create view based on actual table structure
echo "<h2>Step 2: สร้าง View ตามโครงสร้างจริง</h2>";

$view_sql = "CREATE OR REPLACE VIEW v_service_requests_full AS
SELECT
    sr.$id_column as request_id,
    sr.service_code,
    sr.service_name,
    " . (in_array('subject', $columns) ? "sr.subject," : "'N/A' as subject,") . "
    " . (in_array('description', $columns) ? "sr.description," : "'N/A' as description,") . "
    sr.status,
    sr.priority,
    sr.created_at,
    sr.updated_at,
    " . (in_array('requester_prefix_id', $columns) ? "p.prefix_name as requester_prefix," : "NULL as requester_prefix,") . "
    " . (in_array('requester_name', $columns) ? "sr.requester_name," : "CONCAT(u.first_name, ' ', u.last_name) as requester_name,") . "
    " . (in_array('requester_position', $columns) ? "sr.requester_position," : "NULL as requester_position,") . "
    " . (in_array('requester_phone', $columns) ? "sr.requester_phone," : "u.phone as requester_phone,") . "
    " . (in_array('requester_email', $columns) ? "sr.requester_email," : "u.email as requester_email,") . "
    " . (in_array('department_name', $columns) ? "sr.department_name," : "d.department_name,") . "
    d.department_code,
    u.user_id,
    u.username,
    u.email as user_email,
    CONCAT(IFNULL(up.prefix_name, ''), ' ', u.first_name, ' ', u.last_name) as user_full_name,
    sr.assigned_to,
    " . (in_array('assigned_at', $columns) ? "sr.assigned_at," : "NULL as assigned_at,") . "
    au.username as assigned_username,
    CONCAT(IFNULL(ap.prefix_name, ''), ' ', au.first_name, ' ', au.last_name) as assigned_full_name,
    sr.admin_notes,
    " . (in_array('rejection_reason', $columns) ? "sr.rejection_reason," : "NULL as rejection_reason,") . "
    " . (in_array('completion_notes', $columns) ? "sr.completion_notes," : "NULL as completion_notes,") . "
    " . (in_array('expected_completion_date', $columns) ? "sr.expected_completion_date," : "NULL as expected_completion_date,") . "
    " . (in_array('started_at', $columns) ? "sr.started_at," : "NULL as started_at,") . "
    " . (in_array('completed_at', $columns) ? "sr.completed_at," : "sr.completed_date as completed_at,") . "
    " . (in_array('cancelled_at', $columns) ? "sr.cancelled_at," : "NULL as cancelled_at,") . "
    " . (in_array('attachment_file', $columns) ? "sr.attachment_file," : "NULL as attachment_file,") . "
    " . (in_array('attachment_original_name', $columns) ? "sr.attachment_original_name," : "NULL as attachment_original_name,") . "
    " . (in_array('request_data', $columns) ? "sr.request_data" : "NULL as request_data") . "
FROM service_requests sr
LEFT JOIN users u ON sr.user_id = u.user_id
LEFT JOIN prefixes up ON u.prefix_id = up.prefix_id
" . (in_array('requester_prefix_id', $columns) ? "LEFT JOIN prefixes p ON sr.requester_prefix_id = p.prefix_id" : "") . "
LEFT JOIN departments d ON sr.department_id = d.department_id
LEFT JOIN users au ON sr.assigned_to = au.user_id
LEFT JOIN prefixes ap ON au.prefix_id = ap.prefix_id";

echo "<div class='info'><strong>SQL ที่จะรัน:</strong><br><pre>" . htmlspecialchars($view_sql) . "</pre></div>";

if ($conn->query($view_sql)) {
    echo "<div class='success'>✅ สร้าง View v_service_requests_full สำเร็จ!</div>";
} else {
    echo "<div class='error'>❌ ไม่สามารถสร้าง View ได้: " . $conn->error . "</div>";
    echo "</body></html>";
    exit;
}

echo "<hr>";

// Step 3: Test the view
echo "<h2>Step 3: ทดสอบ View</h2>";

$test_query = "SELECT * FROM v_service_requests_full LIMIT 3";
$test_result = $conn->query($test_query);

if ($test_result) {
    echo "<div class='success'>✅ View ทำงานถูกต้อง!</div>";

    if ($test_result->num_rows > 0) {
        echo "<div class='info'><strong>ข้อมูลตัวอย่าง (" . $test_result->num_rows . " รายการ):</strong><br><br>";
        echo "<table border='1' cellpadding='8' style='border-collapse:collapse;width:100%;background:white;'>";
        echo "<tr style='background:#f3f4f6;'>";
        echo "<th>Request ID</th><th>Service</th><th>Requester</th><th>Status</th><th>Priority</th><th>Assigned</th>";
        echo "</tr>";

        while ($row = $test_result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>#" . str_pad($row['request_id'], 4, '0', STR_PAD_LEFT) . "</td>";
            echo "<td>" . htmlspecialchars($row['service_name']) . "</td>";
            echo "<td>" . htmlspecialchars($row['user_full_name'] ?? 'N/A') . "</td>";
            echo "<td>" . htmlspecialchars($row['status']) . "</td>";
            echo "<td>" . htmlspecialchars($row['priority']) . "</td>";
            echo "<td>" . htmlspecialchars($row['assigned_full_name'] ?? '-') . "</td>";
            echo "</tr>";
        }
        echo "</table></div>";
    } else {
        echo "<div class='info'>ℹ️ View ถูกสร้างแล้ว แต่ยังไม่มีข้อมูล</div>";
    }
} else {
    echo "<div class='error'>❌ ไม่สามารถทดสอบ View ได้: " . $conn->error . "</div>";
}

echo "<hr>";
echo "<h2>✅ เสร็จสิ้น</h2>";
echo "<div class='success'>";
echo "<p><strong>ระบบพร้อมใช้งาน!</strong></p>";
echo "<p>View v_service_requests_full ถูกสร้างตาม column ที่มีอยู่จริงในตาราง service_requests</p>";
echo "</div>";

echo "<div style='margin-top:20px;'>";
echo "<a href='admin/service_requests.php' style='display:inline-block;background:#14b8a6;color:white;padding:12px 24px;text-decoration:none;border-radius:8px;font-weight:bold;margin-right:10px;'>
📋 ไปที่หน้าจัดการคำขอบริการ
</a>";
echo "<a href='admin/index.php' style='display:inline-block;background:#6b7280;color:white;padding:12px 24px;text-decoration:none;border-radius:8px;font-weight:bold;'>
🏠 กลับสู่ Admin Dashboard
</a>";
echo "</div>";

echo "</body></html>";

$conn->close();
?>
