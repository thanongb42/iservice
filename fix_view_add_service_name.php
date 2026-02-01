<?php
/**
 * Fix View - Add Service Name Mapping
 * แก้ไข View ให้มี service_name จาก service_code
 */

require_once 'config/database.php';

echo "<!DOCTYPE html><html lang='th'><head><meta charset='UTF-8'><title>Fix View</title>";
echo "<style>body{font-family:Arial,sans-serif;max-width:1200px;margin:20px auto;padding:20px;background:#f5f5f5;}";
echo ".success{background:#d1fae5;border:2px solid #10b981;padding:15px;border-radius:8px;margin:10px 0;color:#065f46;}";
echo ".error{background:#fee2e2;border:2px solid #ef4444;padding:15px;border-radius:8px;margin:10px 0;color:#991b1b;}";
echo ".info{background:#dbeafe;border:2px solid #3b82f6;padding:15px;border-radius:8px;margin:10px 0;color:#1e40af;}";
echo "pre{background:#f3f4f6;padding:15px;border-radius:5px;overflow-x:auto;font-size:11px;}";
echo "h1,h2{color:#333;}hr{margin:20px 0;}</style></head><body>";

echo "<h1>🔧 แก้ไข View - เพิ่ม Service Name</h1>";
echo "<hr>";

// Step 1: Check current view
echo "<h2>Step 1: ตรวจสอบ View ปัจจุบัน</h2>";

$check_view = $conn->query("SHOW CREATE VIEW v_service_requests_full");
if ($check_view && $row = $check_view->fetch_assoc()) {
    echo "<div class='info'><strong>View ปัจจุบัน:</strong><br>";
    echo "<pre>" . htmlspecialchars($row['Create View']) . "</pre></div>";
} else {
    echo "<div class='error'>❌ View v_service_requests_full ยังไม่มี</div>";
}

// Step 2: Check table columns
echo "<h2>Step 2: ตรวจสอบ Columns ในตาราง</h2>";

$desc = $conn->query("DESCRIBE service_requests");
$columns = [];
while ($col = $desc->fetch_assoc()) {
    $columns[] = $col['Field'];
}

echo "<div class='info'><strong>Columns:</strong><br>" . implode(', ', $columns) . "</div>";

$has_service_code = in_array('service_code', $columns);
$has_service_name = in_array('service_name', $columns);

echo "<div class='info'>";
echo "- service_code: " . ($has_service_code ? "✅ มี" : "❌ ไม่มี") . "<br>";
echo "- service_name: " . ($has_service_name ? "✅ มี" : "❌ ไม่มี") . "<br>";
echo "</div>";

// Step 3: Drop and recreate view with service_name mapping
echo "<h2>Step 3: สร้าง View ใหม่พร้อม Service Name Mapping</h2>";

// Build view parts
$view_parts = [];

// ID
if (in_array('request_id', $columns)) {
    $view_parts[] = "sr.request_id";
} elseif (in_array('id', $columns)) {
    $view_parts[] = "sr.id as request_id";
}

// Service code
if ($has_service_code) {
    $view_parts[] = "sr.service_code";
}

// Service name - use CASE to map from service_code
if ($has_service_code) {
    $service_name_mapping = "CASE sr.service_code
        WHEN 'EMAIL' THEN 'ขอใช้บริการ Email'
        WHEN 'NAS' THEN 'ขอใช้พื้นที่ NAS'
        WHEN 'IT_SUPPORT' THEN 'ขอรับการสนับสนุนด้าน IT'
        WHEN 'INTERNET' THEN 'ขอใช้บริการ Internet'
        WHEN 'QR_CODE' THEN 'ขอทำ QR Code'
        WHEN 'PHOTOGRAPHY' THEN 'ขอถ่ายภาพกิจกรรม'
        WHEN 'WEB_DESIGN' THEN 'ขอออกแบบเว็บไซต์'
        WHEN 'PRINTER' THEN 'ขอใช้เครื่องพิมพ์'
        ELSE sr.service_code
    END as service_name";
    $view_parts[] = $service_name_mapping;
} elseif ($has_service_name) {
    $view_parts[] = "sr.service_name";
} else {
    $view_parts[] = "'Unknown Service' as service_name";
}

// Other common fields
$common_fields = ['request_code', 'subject', 'description', 'status', 'priority',
                  'created_at', 'updated_at', 'user_id', 'department_id',
                  'assigned_to', 'admin_notes'];

foreach ($common_fields as $field) {
    if (in_array($field, $columns)) {
        $view_parts[] = "sr.$field";
    }
}

// Date fields
$date_fields = ['completed_date', 'completed_at', 'started_at', 'cancelled_at'];
foreach ($date_fields as $field) {
    if (in_array($field, $columns)) {
        $view_parts[] = "sr.$field";
    }
}

// Build JOINs
$joins = [];
$has_user_id = in_array('user_id', $columns);
$has_department_id = in_array('department_id', $columns);
$has_assigned_to = in_array('assigned_to', $columns);

if ($has_user_id) {
    $view_parts[] = "u.username";
    $view_parts[] = "u.email as user_email";
    $view_parts[] = "u.first_name";
    $view_parts[] = "u.last_name";
    $view_parts[] = "CONCAT(IFNULL(up.prefix_name, ''), ' ', u.first_name, ' ', u.last_name) as user_full_name";
    $joins[] = "LEFT JOIN users u ON sr.user_id = u.user_id";
    $joins[] = "LEFT JOIN prefixes up ON u.prefix_id = up.prefix_id";
} else {
    $view_parts[] = "NULL as username";
    $view_parts[] = "NULL as user_email";
    $view_parts[] = "NULL as first_name";
    $view_parts[] = "NULL as last_name";
    $view_parts[] = "NULL as user_full_name";
}

if ($has_department_id) {
    $view_parts[] = "d.department_name";
    $view_parts[] = "d.department_code";
    $joins[] = "LEFT JOIN departments d ON sr.department_id = d.department_id";
} else {
    $view_parts[] = "NULL as department_name";
    $view_parts[] = "NULL as department_code";
}

if ($has_assigned_to) {
    $view_parts[] = "au.username as assigned_username";
    $view_parts[] = "CONCAT(IFNULL(ap.prefix_name, ''), ' ', au.first_name, ' ', au.last_name) as assigned_full_name";
    $joins[] = "LEFT JOIN users au ON sr.assigned_to = au.user_id";
    $joins[] = "LEFT JOIN prefixes ap ON au.prefix_id = ap.prefix_id";
} else {
    $view_parts[] = "NULL as assigned_username";
    $view_parts[] = "NULL as assigned_full_name";
}

// Build final SQL
$view_sql = "CREATE OR REPLACE VIEW v_service_requests_full AS
SELECT
    " . implode(",\n    ", $view_parts) . "
FROM service_requests sr";

if (!empty($joins)) {
    $view_sql .= "\n" . implode("\n", $joins);
}

echo "<div class='info'><strong>SQL ที่จะรัน:</strong><pre>" . htmlspecialchars($view_sql) . "</pre></div>";

// Execute
if ($conn->query($view_sql)) {
    echo "<div class='success'>✅ สร้าง View สำเร็จ!</div>";

    // Test
    echo "<h2>Step 4: ทดสอบ View</h2>";
    $test = $conn->query("SELECT request_id, service_code, service_name, status FROM v_service_requests_full LIMIT 5");

    if ($test && $test->num_rows > 0) {
        echo "<table border='1' cellpadding='10' style='border-collapse:collapse;width:100%;background:white;'>";
        echo "<tr style='background:#14b8a6;color:white;'>";
        echo "<th>Request ID</th><th>Service Code</th><th>Service Name</th><th>Status</th></tr>";

        while ($row = $test->fetch_assoc()) {
            echo "<tr>";
            echo "<td>#" . str_pad($row['request_id'], 4, '0', STR_PAD_LEFT) . "</td>";
            echo "<td>" . htmlspecialchars($row['service_code'] ?? '-') . "</td>";
            echo "<td><strong>" . htmlspecialchars($row['service_name'] ?? '-') . "</strong></td>";
            echo "<td>" . htmlspecialchars($row['status'] ?? '-') . "</td>";
            echo "</tr>";
        }
        echo "</table>";

        echo "<div class='success'>✅ View ทำงานได้! Service Name แสดงผลถูกต้อง</div>";
    } else {
        echo "<div class='info'>⚠️ View ถูกสร้างแล้ว แต่ยังไม่มีข้อมูล</div>";
    }

} else {
    echo "<div class='error'>❌ ไม่สามารถสร้าง View ได้: " . $conn->error . "</div>";
}

echo "<hr>";
echo "<h2>✅ เสร็จสิ้น</h2>";
echo "<div style='margin-top:20px;'>";
echo "<a href='admin/service_requests.php' style='display:inline-block;background:#14b8a6;color:white;padding:12px 24px;text-decoration:none;border-radius:8px;font-weight:bold;'>
📋 ไปที่หน้าจัดการคำขอบริการ
</a>";
echo "</div>";

echo "</body></html>";

$conn->close();
?>
