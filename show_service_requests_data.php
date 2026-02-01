<?php
/**
 * Show Service Requests Data
 * แสดงข้อมูลจริงในตาราง service_requests
 */

require_once 'config/database.php';

echo "<!DOCTYPE html><html lang='th'><head><meta charset='UTF-8'><title>Service Requests Data</title>";
echo "<style>body{font-family:Arial,sans-serif;max-width:1400px;margin:20px auto;padding:20px;background:#f5f5f5;}";
echo "table{width:100%;border-collapse:collapse;background:white;margin:10px 0;}";
echo "th,td{padding:10px;border:1px solid #ddd;text-align:left;font-size:12px;}";
echo "th{background:#14b8a6;color:white;font-weight:bold;}";
echo ".info{background:#dbeafe;padding:15px;border-radius:8px;margin:10px 0;}";
echo "h1,h2{color:#333;}</style></head><body>";

echo "<h1>📋 ข้อมูลในตาราง service_requests</h1>";
echo "<hr>";

// Show all data
$query = "SELECT * FROM service_requests LIMIT 10";
$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    echo "<h2>ข้อมูล 10 แถวแรก:</h2>";
    echo "<div style='overflow-x:auto;'>";
    echo "<table>";

    // Get column names
    $first_row = $result->fetch_assoc();
    $result->data_seek(0);

    echo "<tr>";
    foreach (array_keys($first_row) as $col) {
        echo "<th>" . htmlspecialchars($col) . "</th>";
    }
    echo "</tr>";

    // Show data
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        foreach ($row as $value) {
            $display = $value;
            if (strlen($display) > 50) {
                $display = substr($display, 0, 50) . '...';
            }
            echo "<td>" . htmlspecialchars($display ?? 'NULL') . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
    echo "</div>";

    echo "<hr>";
    echo "<h2>📊 สรุปข้อมูล:</h2>";

    // Count by status
    $status_query = "SELECT status, COUNT(*) as count FROM service_requests GROUP BY status";
    $status_result = $conn->query($status_query);

    if ($status_result) {
        echo "<div class='info'><strong>จำนวนตาม Status:</strong><br>";
        while ($row = $status_result->fetch_assoc()) {
            echo "- " . htmlspecialchars($row['status']) . ": " . $row['count'] . " รายการ<br>";
        }
        echo "</div>";
    }

    // Check for service_code column
    $desc = $conn->query("DESCRIBE service_requests");
    $columns = [];
    while ($col = $desc->fetch_assoc()) {
        $columns[] = $col['Field'];
    }

    echo "<div class='info'><strong>Columns ในตาราง:</strong><br>";
    echo implode(', ', $columns);
    echo "</div>";

    // If service_code exists, show unique values
    if (in_array('service_code', $columns)) {
        $sc_query = "SELECT DISTINCT service_code FROM service_requests WHERE service_code IS NOT NULL";
        $sc_result = $conn->query($sc_query);

        if ($sc_result && $sc_result->num_rows > 0) {
            echo "<div class='info'><strong>Service Codes ที่พบ:</strong><br>";
            while ($row = $sc_result->fetch_assoc()) {
                echo "- " . htmlspecialchars($row['service_code']) . "<br>";
            }
            echo "</div>";
        }
    }

    // If request_code exists, show pattern
    if (in_array('request_code', $columns)) {
        $rc_query = "SELECT DISTINCT SUBSTRING(request_code, 1, 3) as prefix, COUNT(*) as count
                     FROM service_requests
                     WHERE request_code IS NOT NULL
                     GROUP BY prefix";
        $rc_result = $conn->query($rc_query);

        if ($rc_result && $rc_result->num_rows > 0) {
            echo "<div class='info'><strong>Request Code Patterns:</strong><br>";
            while ($row = $rc_result->fetch_assoc()) {
                echo "- " . htmlspecialchars($row['prefix']) . "xxx: " . $row['count'] . " รายการ<br>";
            }
            echo "</div>";
        }
    }

} else {
    echo "<div class='info'>⚠️ ไม่มีข้อมูลในตาราง service_requests</div>";
}

echo "<hr>";
echo "<h2>🔧 แนะนำการแก้ไข:</h2>";
echo "<div class='info'>";
echo "<p>ถ้าไม่มี column <code>service_code</code> หรือ <code>service_name</code> ให้:</p>";
echo "<ol>";
echo "<li>ดูจาก <code>request_code</code> เพื่อดึง service type (เช่น REQ-001, EMAIL-001)</li>";
echo "<li>หรือเพิ่ม column <code>service_code</code> และ <code>service_name</code> เข้าไป</li>";
echo "<li>หรือแก้ไข view ให้ดึงข้อมูลจาก column ที่มีอยู่</li>";
echo "</ol>";
echo "</div>";

echo "<div style='margin-top:20px;'>";
echo "<a href='admin/service_requests.php' style='display:inline-block;background:#14b8a6;color:white;padding:12px 24px;text-decoration:none;border-radius:8px;font-weight:bold;'>
กลับไปหน้าจัดการคำขอ
</a>";
echo "</div>";

echo "</body></html>";

$conn->close();
?>
