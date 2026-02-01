<?php
/**
 * Quick Fix - Update View to Include assigned_full_name
 */

require_once 'config/database.php';

echo "<!DOCTYPE html><html lang='th'><head><meta charset='UTF-8'><title>Fix View</title>";
echo "<style>
body{font-family:Arial,sans-serif;max-width:1000px;margin:20px auto;padding:20px;background:#f5f5f5;}
.success{background:#d1fae5;border:2px solid #10b981;padding:15px;border-radius:8px;margin:10px 0;color:#065f46;}
.error{background:#fee2e2;border:2px solid #ef4444;padding:15px;border-radius:8px;margin:10px 0;color:#991b1b;}
.info{background:#dbeafe;border:2px solid #3b82f6;padding:15px;border-radius:8px;margin:10px 0;color:#1e40af;}
h1,h2{color:#333;}
hr{margin:20px 0;}
</style></head><body>";

echo "<h1>🔧 แก้ไข View - เพิ่ม assigned_full_name</h1>";
echo "<hr>";

// Update view
$sql = "CREATE OR REPLACE VIEW v_service_requests_full AS
SELECT
    sr.id as request_id,
    sr.request_code,
    sr.service_code,
    sr.service_name,
    sr.subject,
    sr.description,
    sr.status,
    sr.priority,
    sr.user_id,
    u.username,
    u.email as user_email,
    CONCAT(COALESCE(up.prefix_name, ''), u.first_name, ' ', u.last_name) as user_full_name,
    sr.department_id,
    d.department_name,
    d.department_code,
    sr.requester_name,
    sr.requester_email,
    sr.requester_phone,
    sr.assigned_to,
    sr.assigned_to as assigned_full_name,
    sr.assigned_to_user_id,
    sr.admin_notes,
    sr.created_at,
    sr.updated_at,
    sr.requested_date,
    sr.completed_date
FROM service_requests sr
LEFT JOIN users u ON sr.user_id = u.user_id
LEFT JOIN prefixes up ON u.prefix_id = up.prefix_id
LEFT JOIN departments d ON sr.department_id = d.department_id";

if ($conn->query($sql)) {
    echo "<div class='success'>";
    echo "<h2>✅ อัพเดท View สำเร็จ!</h2>";
    echo "<p>เพิ่ม column <code>assigned_full_name</code> แล้ว</p>";
    echo "</div>";

    // Test view
    $test = $conn->query("SELECT request_id, service_name, user_full_name, assigned_full_name, status FROM v_service_requests_full LIMIT 3");

    if ($test && $test->num_rows > 0) {
        echo "<div class='info'>";
        echo "<h3>📋 ทดสอบ View:</h3>";
        echo "<table border='1' cellpadding='10' style='border-collapse:collapse;width:100%;background:white;'>";
        echo "<tr style='background:#14b8a6;color:white;'>";
        echo "<th>Request ID</th><th>Service Name</th><th>User</th><th>Assigned To</th><th>Status</th></tr>";

        while ($row = $test->fetch_assoc()) {
            echo "<tr>";
            echo "<td>#" . str_pad($row['request_id'], 4, '0', STR_PAD_LEFT) . "</td>";
            echo "<td>" . htmlspecialchars($row['service_name']) . "</td>";
            echo "<td>" . htmlspecialchars($row['user_full_name'] ?? '-') . "</td>";
            echo "<td>" . htmlspecialchars($row['assigned_full_name'] ?? 'Unassigned') . "</td>";
            echo "<td>" . htmlspecialchars($row['status']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "</div>";
    }

} else {
    echo "<div class='error'>";
    echo "<h3>❌ ไม่สามารถอัพเดท View ได้</h3>";
    echo "<p>Error: " . htmlspecialchars($conn->error) . "</p>";
    echo "</div>";
}

echo "<hr>";
echo "<div style='margin-top:20px;'>";
echo "<a href='admin/service_requests.php' style='display:inline-block;background:#14b8a6;color:white;padding:12px 24px;text-decoration:none;border-radius:8px;font-weight:bold;'>";
echo "📋 ไปที่หน้าจัดการคำขอบริการ</a>";
echo "</div>";

echo "</body></html>";

$conn->close();
?>
