<?php
/**
 * Check Service Requests Table Structure
 * ตรวจสอบโครงสร้างตารางจริง
 */

require_once 'config/database.php';

echo "<!DOCTYPE html><html lang='th'><head><meta charset='UTF-8'><title>Check Table Structure</title>";
echo "<style>body{font-family:Arial,sans-serif;max-width:1400px;margin:20px auto;padding:20px;background:#f5f5f5;}";
echo ".info{background:#dbeafe;border:2px solid #3b82f6;padding:15px;border-radius:8px;margin:10px 0;color:#1e40af;}";
echo "table{width:100%;border-collapse:collapse;background:white;margin:10px 0;}";
echo "th,td{padding:10px;border:1px solid #ddd;text-align:left;}";
echo "th{background:#f3f4f6;font-weight:bold;}";
echo "h1,h2{color:#333;}hr{margin:20px 0;border:1px solid #ccc;}</style></head><body>";

echo "<h1>🔍 ตรวจสอบโครงสร้างตาราง service_requests</h1>";
echo "<hr>";

// Check table structure
$desc_query = "DESCRIBE service_requests";
$desc_result = $conn->query($desc_query);

if ($desc_result) {
    echo "<h2>Columns ในตาราง service_requests:</h2>";
    echo "<table>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";

    $columns = [];
    while ($row = $desc_result->fetch_assoc()) {
        $columns[] = $row['Field'];
        echo "<tr>";
        echo "<td><strong>" . htmlspecialchars($row['Field']) . "</strong></td>";
        echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($row['Extra']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";

    echo "<hr>";
    echo "<h2>รายการ Columns ทั้งหมด:</h2>";
    echo "<div class='info'>";
    echo "<pre>" . implode("\n", $columns) . "</pre>";
    echo "<p><strong>จำนวน:</strong> " . count($columns) . " columns</p>";
    echo "</div>";

    // Show sample data
    echo "<hr>";
    echo "<h2>ข้อมูลตัวอย่าง (3 แถวแรก):</h2>";
    $sample = $conn->query("SELECT * FROM service_requests LIMIT 3");

    if ($sample && $sample->num_rows > 0) {
        echo "<div style='overflow-x:auto;'>";
        echo "<table>";
        echo "<tr>";
        foreach ($columns as $col) {
            echo "<th>" . htmlspecialchars($col) . "</th>";
        }
        echo "</tr>";

        while ($row = $sample->fetch_assoc()) {
            echo "<tr>";
            foreach ($columns as $col) {
                $value = $row[$col];
                if (strlen($value) > 50) {
                    $value = substr($value, 0, 50) . '...';
                }
                echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
        echo "</div>";
    } else {
        echo "<div class='info'>ไม่มีข้อมูลในตาราง</div>";
    }

} else {
    echo "<div style='background:#fee2e2;padding:15px;border-radius:8px;color:#991b1b;'>";
    echo "❌ ไม่สามารถตรวจสอบโครงสร้างตารางได้: " . $conn->error;
    echo "</div>";
}

// Check related tables
echo "<hr>";
echo "<h2>ตารางที่เกี่ยวข้อง:</h2>";

$tables = ['users', 'departments', 'prefixes'];
foreach ($tables as $table) {
    $check = $conn->query("SHOW TABLES LIKE '$table'");
    if ($check && $check->num_rows > 0) {
        echo "<div style='display:inline-block;background:#d1fae5;color:#065f46;padding:8px 15px;margin:5px;border-radius:5px;'>";
        echo "✅ $table";
        echo "</div>";
    } else {
        echo "<div style='display:inline-block;background:#fee2e2;color:#991b1b;padding:8px 15px;margin:5px;border-radius:5px;'>";
        echo "❌ $table";
        echo "</div>";
    }
}

echo "<hr>";
echo "<div style='margin-top:20px;'>";
echo "<a href='create_view_from_actual_columns.php' style='display:inline-block;background:#14b8a6;color:white;padding:12px 24px;text-decoration:none;border-radius:8px;font-weight:bold;margin-right:10px;'>
➡️ ต่อไป: สร้าง View อัตโนมัติ
</a>";
echo "</div>";

echo "</body></html>";

$conn->close();
?>
