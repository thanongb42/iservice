<?php
/**
 * Create View From Actual Columns
 * สร้าง View จากโครงสร้างจริงของตาราง
 */

require_once 'config/database.php';

echo "<!DOCTYPE html><html lang='th'><head><meta charset='UTF-8'><title>Create View</title>";
echo "<style>body{font-family:Arial,sans-serif;max-width:1200px;margin:20px auto;padding:20px;background:#f5f5f5;}";
echo ".success{background:#d1fae5;border:2px solid #10b981;padding:15px;border-radius:8px;margin:10px 0;color:#065f46;}";
echo ".error{background:#fee2e2;border:2px solid #ef4444;padding:15px;border-radius:8px;margin:10px 0;color:#991b1b;}";
echo ".info{background:#dbeafe;border:2px solid #3b82f6;padding:15px;border-radius:8px;margin:10px 0;color:#1e40af;}";
echo "pre{background:#f3f4f6;padding:15px;border-radius:5px;overflow-x:auto;}";
echo "h1,h2{color:#333;}hr{margin:20px 0;border:1px solid #ccc;}</style></head><body>";

echo "<h1>🔧 สร้าง View v_service_requests_full</h1>";
echo "<hr>";

// Get actual columns
$desc_query = "DESCRIBE service_requests";
$desc_result = $conn->query($desc_query);

if (!$desc_result) {
    echo "<div class='error'>❌ ไม่สามารถตรวจสอบโครงสร้างตารางได้</div>";
    exit;
}

$columns = [];
while ($row = $desc_result->fetch_assoc()) {
    $columns[] = $row['Field'];
}

echo "<div class='info'><strong>พบ Columns:</strong> " . count($columns) . " columns<br>";
echo "<code>" . implode(', ', $columns) . "</code></div>";

// Build view SQL dynamically
$view_parts = [];

// Primary key - check both id and request_id
if (in_array('request_id', $columns)) {
    $view_parts[] = "sr.request_id";
} elseif (in_array('id', $columns)) {
    $view_parts[] = "sr.id as request_id";
}

// Service info
if (in_array('service_code', $columns)) {
    $view_parts[] = "sr.service_code";
}
if (in_array('service_name', $columns)) {
    $view_parts[] = "sr.service_name";
} else {
    // Try to construct from request_code or other field
    $view_parts[] = "'Unknown Service' as service_name";
}

// Request code
if (in_array('request_code', $columns)) {
    $view_parts[] = "sr.request_code";
}

// Basic info
$basic_fields = ['subject', 'description', 'status', 'priority', 'created_at', 'updated_at'];
foreach ($basic_fields as $field) {
    if (in_array($field, $columns)) {
        $view_parts[] = "sr.$field";
    }
}

// User ID
if (in_array('user_id', $columns)) {
    $view_parts[] = "sr.user_id";
}

// Department
if (in_array('department_id', $columns)) {
    $view_parts[] = "sr.department_id";
}

// Assignment
if (in_array('assigned_to', $columns)) {
    $view_parts[] = "sr.assigned_to";
}

// Admin notes
if (in_array('admin_notes', $columns)) {
    $view_parts[] = "sr.admin_notes";
}

// Dates
$date_fields = ['completed_date', 'completed_at', 'started_at', 'cancelled_at', 'expected_completion_date'];
foreach ($date_fields as $field) {
    if (in_array($field, $columns)) {
        $view_parts[] = "sr.$field";
    }
}

// Build JOIN clauses based on available columns
$joins = [];
$has_user_id = in_array('user_id', $columns);
$has_department_id = in_array('department_id', $columns);
$has_assigned_to = in_array('assigned_to', $columns);

// Add user info only if user_id exists
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

// Add department info only if department_id exists
if ($has_department_id) {
    $view_parts[] = "d.department_name";
    $view_parts[] = "d.department_code";
    $joins[] = "LEFT JOIN departments d ON sr.department_id = d.department_id";
} else {
    $view_parts[] = "NULL as department_name";
    $view_parts[] = "NULL as department_code";
}

// Add assigned user info only if assigned_to exists
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

echo "<h2>SQL ที่จะรัน:</h2>";
echo "<div class='info'><pre>" . htmlspecialchars($view_sql) . "</pre></div>";

// Execute
if ($conn->query($view_sql)) {
    echo "<div class='success'>✅ สร้าง View v_service_requests_full สำเร็จ!</div>";

    // Test the view
    echo "<h2>ทดสอบ View:</h2>";
    $test = $conn->query("SELECT * FROM v_service_requests_full LIMIT 3");

    if ($test && $test->num_rows > 0) {
        echo "<div class='success'>✅ View ทำงานได้! พบข้อมูล " . $test->num_rows . " รายการ</div>";

        echo "<table border='1' cellpadding='8' style='border-collapse:collapse;width:100%;background:white;margin:10px 0;'>";
        echo "<tr style='background:#f3f4f6;'>";

        // Show all columns
        $first_row = $test->fetch_assoc();
        $test->data_seek(0); // Reset pointer

        foreach (array_keys($first_row) as $col) {
            echo "<th>" . htmlspecialchars($col) . "</th>";
        }
        echo "</tr>";

        while ($row = $test->fetch_assoc()) {
            echo "<tr>";
            foreach ($row as $value) {
                $display = $value;
                if (strlen($display) > 30) {
                    $display = substr($display, 0, 30) . '...';
                }
                echo "<td>" . htmlspecialchars($display ?? '-') . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";

    } elseif ($test) {
        echo "<div class='info'>ℹ️ View ถูกสร้างแล้ว แต่ยังไม่มีข้อมูล</div>";
    } else {
        echo "<div class='error'>⚠️ View ถูกสร้างแล้ว แต่มีปัญหาในการอ่านข้อมูล: " . $conn->error . "</div>";
    }

} else {
    echo "<div class='error'>❌ ไม่สามารถสร้าง View ได้: " . $conn->error . "</div>";
    echo "<div class='info'>กรุณาคัดลอก SQL ด้านบนและรันใน phpMyAdmin เพื่อดูข้อผิดพลาดโดยละเอียด</div>";
}

echo "<hr>";
echo "<h2>✅ เสร็จสิ้น</h2>";
echo "<div style='margin-top:20px;'>";
echo "<a href='admin/service_requests.php' style='display:inline-block;background:#14b8a6;color:white;padding:12px 24px;text-decoration:none;border-radius:8px;font-weight:bold;margin-right:10px;'>
📋 ไปที่หน้าจัดการคำขอบริการ
</a>";
echo "<a href='check_table_structure.php' style='display:inline-block;background:#6b7280;color:white;padding:12px 24px;text-decoration:none;border-radius:8px;font-weight:bold;'>
🔍 ตรวจสอบโครงสร้างอีกครั้ง
</a>";
echo "</div>";

echo "</body></html>";

$conn->close();
?>
