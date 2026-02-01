<?php
/**
 * Setup Service Requests System
 * สร้างตารางและ View ที่จำเป็นทั้งหมด
 */

require_once 'config/database.php';

echo "<!DOCTYPE html><html lang='th'><head><meta charset='UTF-8'><title>Setup Service Requests</title>";
echo "<style>body{font-family:Arial,sans-serif;max-width:1000px;margin:20px auto;padding:20px;background:#f5f5f5;}";
echo ".success{background:#d1fae5;border:2px solid #10b981;padding:15px;border-radius:8px;margin:10px 0;color:#065f46;}";
echo ".error{background:#fee2e2;border:2px solid #ef4444;padding:15px;border-radius:8px;margin:10px 0;color:#991b1b;}";
echo ".warning{background:#fef3c7;border:2px solid #f59e0b;padding:15px;border-radius:8px;margin:10px 0;color:#92400e;}";
echo ".info{background:#dbeafe;border:2px solid #3b82f6;padding:15px;border-radius:8px;margin:10px 0;color:#1e40af;}";
echo "h1,h2{color:#333;}hr{margin:20px 0;border:1px solid #ccc;}</style></head><body>";

echo "<h1>🚀 Setup Service Requests System</h1>";
echo "<hr>";

$steps = 0;
$errors = 0;

// Step 1: Check if service_requests table exists
echo "<h2>Step 1: ตรวจสอบตาราง service_requests</h2>";
$check_table = $conn->query("SHOW TABLES LIKE 'service_requests'");
if ($check_table->num_rows === 0) {
    echo "<div class='error'>❌ ตาราง service_requests ยังไม่มี กำลังสร้าง...</div>";

    // Read and execute SQL file
    $sql_file = __DIR__ . '/database/service_requests.sql';
    if (file_exists($sql_file)) {
        $sql_content = file_get_contents($sql_file);

        // Split by delimiter
        $statements = explode(';', $sql_content);

        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (empty($statement)) continue;

            // Skip delimiter commands
            if (stripos($statement, 'DELIMITER') !== false) continue;

            // Replace // with ; for stored procedures
            $statement = str_replace('//', ';', $statement);

            try {
                $conn->query($statement);
            } catch (Exception $e) {
                // Some statements might fail (like CREATE PROCEDURE if delimiter is wrong)
                // We'll continue anyway
            }
        }

        $check_again = $conn->query("SHOW TABLES LIKE 'service_requests'");
        if ($check_again->num_rows > 0) {
            echo "<div class='success'>✅ สร้างตาราง service_requests สำเร็จ!</div>";
            $steps++;
        } else {
            echo "<div class='error'>❌ ไม่สามารถสร้างตารางได้ กรุณาสร้างด้วยตนเองจากไฟล์ database/service_requests.sql</div>";
            $errors++;
        }
    } else {
        echo "<div class='error'>❌ ไม่พบไฟล์ database/service_requests.sql</div>";
        $errors++;
    }
} else {
    echo "<div class='success'>✅ ตาราง service_requests มีอยู่แล้ว</div>";
    $steps++;
}

// Step 2: Create or replace view
echo "<h2>Step 2: สร้าง View v_service_requests_full</h2>";

$view_sql = "CREATE OR REPLACE VIEW v_service_requests_full AS
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
    p.prefix_name as requester_prefix,
    sr.requester_name,
    sr.requester_position,
    sr.requester_phone,
    sr.requester_email,
    sr.department_name,
    d.department_code,
    u.user_id,
    u.username,
    u.email as user_email,
    CONCAT(IFNULL(up.prefix_name, ''), ' ', u.first_name, ' ', u.last_name) as user_full_name,
    sr.assigned_to,
    sr.assigned_at,
    au.username as assigned_username,
    CONCAT(IFNULL(ap.prefix_name, ''), ' ', au.first_name, ' ', au.last_name) as assigned_full_name,
    sr.admin_notes,
    sr.rejection_reason,
    sr.completion_notes,
    sr.expected_completion_date,
    sr.started_at,
    sr.completed_at,
    sr.cancelled_at,
    sr.attachment_file,
    sr.attachment_original_name,
    sr.request_data
FROM service_requests sr
LEFT JOIN users u ON sr.user_id = u.user_id
LEFT JOIN prefixes up ON u.prefix_id = up.prefix_id
LEFT JOIN prefixes p ON sr.requester_prefix_id = p.prefix_id
LEFT JOIN departments d ON sr.department_id = d.department_id
LEFT JOIN users au ON sr.assigned_to = au.user_id
LEFT JOIN prefixes ap ON au.prefix_id = ap.prefix_id";

if ($conn->query($view_sql)) {
    echo "<div class='success'>✅ สร้าง View v_service_requests_full สำเร็จ!</div>";
    $steps++;
} else {
    echo "<div class='error'>❌ ไม่สามารถสร้าง View ได้: " . $conn->error . "</div>";
    $errors++;
}

// Step 3: Test view
echo "<h2>Step 3: ทดสอบ View</h2>";
$test_query = "SELECT request_id, service_name, user_full_name, status, priority
               FROM v_service_requests_full LIMIT 5";
$test_result = $conn->query($test_query);

if ($test_result !== false) {
    echo "<div class='success'>✅ View ทำงานถูกต้อง!</div>";
    $steps++;

    if ($test_result->num_rows > 0) {
        echo "<div class='info'>";
        echo "<strong>ข้อมูลตัวอย่าง:</strong><br>";
        echo "<table border='1' cellpadding='8' style='margin-top:10px;border-collapse:collapse;width:100%;background:white;'>";
        echo "<tr style='background:#f3f4f6;'>";
        echo "<th>Request ID</th><th>Service</th><th>Requester</th><th>Status</th><th>Priority</th>";
        echo "</tr>";

        while ($row = $test_result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>#" . str_pad($row['request_id'], 4, '0', STR_PAD_LEFT) . "</td>";
            echo "<td>" . htmlspecialchars($row['service_name']) . "</td>";
            echo "<td>" . htmlspecialchars($row['user_full_name'] ?? '-') . "</td>";
            echo "<td>" . htmlspecialchars($row['status']) . "</td>";
            echo "<td>" . htmlspecialchars($row['priority']) . "</td>";
            echo "</tr>";
        }
        echo "</table></div>";
    } else {
        echo "<div class='warning'>⚠️ View ถูกสร้างแล้ว แต่ยังไม่มีข้อมูล<br>";
        echo "ข้อมูลจะปรากฏเมื่อมีการสร้างคำขอบริการจากผู้ใช้</div>";
    }
} else {
    echo "<div class='error'>❌ ไม่สามารถทดสอบ View ได้: " . $conn->error . "</div>";
    $errors++;
}

// Summary
echo "<hr>";
echo "<h2>📊 สรุปผลการติดตั้ง</h2>";
if ($errors === 0) {
    echo "<div class='success'>";
    echo "<h3>✅ ติดตั้งสำเร็จทั้งหมด!</h3>";
    echo "<p>ระบบจัดการคำขอบริการพร้อมใช้งานแล้ว</p>";
    echo "<p><strong>เสร็จสิ้น:</strong> $steps/3 ขั้นตอน</p>";
    echo "</div>";
} else {
    echo "<div class='error'>";
    echo "<h3>⚠️ มีข้อผิดพลาด</h3>";
    echo "<p><strong>สำเร็จ:</strong> $steps ขั้นตอน</p>";
    echo "<p><strong>ผิดพลาด:</strong> $errors ขั้นตอน</p>";
    echo "</div>";
}

// Links
echo "<hr>";
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
