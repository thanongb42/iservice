<?php
/**
 * Update v_users_full View
 * อัปเดต View เพื่อเพิ่ม prefix_id และ department_id
 */

require_once 'config/database.php';

echo "<h2>🔄 Update v_users_full View</h2>";
echo "<hr>";

// First, check and add missing columns to users table
$columns_to_add = [
    'profile_image' => "ALTER TABLE users ADD COLUMN profile_image VARCHAR(255) NULL AFTER position",
    'position' => "ALTER TABLE users ADD COLUMN position VARCHAR(100) NULL AFTER department_id",
    'last_login' => "ALTER TABLE users ADD COLUMN last_login DATETIME NULL AFTER profile_image"
];

foreach ($columns_to_add as $column => $alter_sql) {
    $check = $conn->query("SHOW COLUMNS FROM users LIKE '$column'");
    if ($check->num_rows == 0) {
        if ($conn->query($alter_sql)) {
            echo "✅ เพิ่มคอลัมน์ <strong>$column</strong> สำเร็จ<br>";
        } else {
            echo "⚠️ ไม่สามารถเพิ่มคอลัมน์ $column: " . $conn->error . "<br>";
        }
    }
}
echo "<hr>";

// Drop and recreate the view
$sql = "CREATE OR REPLACE VIEW v_users_full AS
SELECT
    u.user_id,
    u.username,
    u.prefix_id,
    p.prefix_name,
    u.first_name,
    u.last_name,
    CONCAT(IFNULL(p.prefix_name, ''), ' ', u.first_name, ' ', u.last_name) AS full_name,
    u.email,
    u.phone,
    u.role,
    u.status,
    u.department_id,
    d.department_name,
    d.department_code,
    u.position,
    u.profile_image,
    u.last_login,
    u.created_at,
    u.updated_at
FROM users u
LEFT JOIN prefixes p ON u.prefix_id = p.prefix_id
LEFT JOIN departments d ON u.department_id = d.department_id";

if ($conn->query($sql)) {
    echo "✅ <strong style='color: green;'>View v_users_full อัปเดตสำเร็จ!</strong><br><br>";

    echo "<div style='background: #d1fae5; border: 2px solid #10b981; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
    echo "<h3 style='color: #065f46; margin-top: 0;'>✅ เพิ่มฟิลด์ใหม่:</h3>";
    echo "<ul style='color: #065f46;'>";
    echo "<li><strong>u.prefix_id</strong> - ID คำนำหน้า (สำหรับ edit modal)</li>";
    echo "<li><strong>u.department_id</strong> - ID หน่วยงาน (สำหรับ edit modal)</li>";
    echo "</ul>";
    echo "</div>";

    // Test the view
    echo "<h3>ทดสอบ View:</h3>";
    $test_query = "SELECT user_id, username, prefix_id, prefix_name, department_id, department_name
                   FROM v_users_full
                   LIMIT 3";
    $test_result = $conn->query($test_query);

    if ($test_result && $test_result->num_rows > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #f3f4f6;'>";
        echo "<th style='padding: 8px;'>User ID</th>";
        echo "<th style='padding: 8px;'>Username</th>";
        echo "<th style='padding: 8px;'>Prefix ID</th>";
        echo "<th style='padding: 8px;'>Prefix Name</th>";
        echo "<th style='padding: 8px;'>Department ID</th>";
        echo "<th style='padding: 8px;'>Department Name</th>";
        echo "</tr>";

        while ($row = $test_result->fetch_assoc()) {
            echo "<tr>";
            echo "<td style='padding: 8px;'>" . $row['user_id'] . "</td>";
            echo "<td style='padding: 8px;'>" . $row['username'] . "</td>";
            echo "<td style='padding: 8px;'>" . ($row['prefix_id'] ?? 'NULL') . "</td>";
            echo "<td style='padding: 8px;'>" . ($row['prefix_name'] ?? 'NULL') . "</td>";
            echo "<td style='padding: 8px;'>" . ($row['department_id'] ?? 'NULL') . "</td>";
            echo "<td style='padding: 8px;'>" . ($row['department_name'] ?? 'NULL') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "<br>";
        echo "✅ View ทำงานถูกต้อง มี prefix_id และ department_id แล้ว!<br>";
    }

} else {
    echo "❌ <strong style='color: red;'>ไม่สามารถอัปเดต View ได้:</strong> " . $conn->error . "<br>";
}

echo "<hr>";
echo "<div style='margin-top: 20px;'>";
echo "<a href='admin/user-manager.php' style='display: inline-block; background: #14b8a6; color: white; padding: 12px 24px; text-decoration: none; border-radius: 8px; font-weight: bold;'>
    👥 ไปที่หน้าจัดการผู้ใช้
</a>";
echo "</div>";

echo "<hr>";
echo "<h3>📝 สิ่งที่เปลี่ยนแปลง:</h3>";
echo "<pre style='background: #f3f4f6; padding: 15px; border-radius: 5px;'>";
echo "เพิ่ม columns ใน v_users_full:\n";
echo "  - u.prefix_id (บรรทัดที่ 128)\n";
echo "  - u.department_id (บรรทัดที่ 137)\n\n";
echo "ตอนนี้ Edit Modal จะแสดงค่าเดิมของคำนำหน้าและหน่วยงานได้แล้ว";
echo "</pre>";
?>
