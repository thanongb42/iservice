<?php
/**
 * Setup Roles and Task Assignment System
 * สร้างตารางบทบาทและระบบมอบหมายงาน
 */

require_once 'config/database.php';

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Setup Roles System</title>";
echo "<style>body{font-family:sans-serif;padding:20px;background:#f5f5f5;}";
echo ".container{max-width:800px;margin:0 auto;background:white;padding:20px;border-radius:8px;box-shadow:0 2px 4px rgba(0,0,0,0.1);}";
echo ".success{color:#059669;background:#d1fae5;padding:10px;margin:5px 0;border-radius:4px;}";
echo ".error{color:#dc2626;background:#fee2e2;padding:10px;margin:5px 0;border-radius:4px;}";
echo ".info{color:#2563eb;background:#dbeafe;padding:10px;margin:5px 0;border-radius:4px;}";
echo "h1{color:#009933;}h2{color:#374151;border-bottom:2px solid #e5e7eb;padding-bottom:10px;}";
echo "</style></head><body><div class='container'>";

echo "<h1>🔧 Setup Roles & Task Assignment System</h1>";
echo "<p>ติดตั้งระบบบทบาทและการมอบหมายงาน</p>";

// Read SQL file
$sql_file = __DIR__ . '/database/roles_and_assignments.sql';
if (!file_exists($sql_file)) {
    echo "<div class='error'>❌ ไม่พบไฟล์ SQL: $sql_file</div>";
    exit;
}

$sql_content = file_get_contents($sql_file);

// Split SQL by semicolon (but not inside quotes)
$statements = [];
$current = '';
$in_string = false;
$string_char = '';

for ($i = 0; $i < strlen($sql_content); $i++) {
    $char = $sql_content[$i];

    if ($in_string) {
        $current .= $char;
        if ($char === $string_char && $sql_content[$i-1] !== '\\') {
            $in_string = false;
        }
    } else {
        if ($char === "'" || $char === '"') {
            $in_string = true;
            $string_char = $char;
            $current .= $char;
        } elseif ($char === ';') {
            $trimmed = trim($current);
            if (!empty($trimmed) && !preg_match('/^--/', $trimmed)) {
                $statements[] = $trimmed;
            }
            $current = '';
        } else {
            $current .= $char;
        }
    }
}

// Execute each statement
echo "<h2>📋 Executing SQL Statements</h2>";

$success_count = 0;
$error_count = 0;
$skip_count = 0;

foreach ($statements as $index => $sql) {
    // Skip comments
    $sql = trim($sql);
    if (empty($sql) || strpos($sql, '--') === 0) {
        continue;
    }

    // Get first few words for display
    $display = substr(preg_replace('/\s+/', ' ', $sql), 0, 80);

    // Check if table already exists for CREATE TABLE
    if (preg_match('/CREATE TABLE IF NOT EXISTS `?(\w+)`?/i', $sql, $matches)) {
        $table_name = $matches[1];
        $check = $conn->query("SHOW TABLES LIKE '$table_name'");
        if ($check && $check->num_rows > 0) {
            echo "<div class='info'>ℹ️ ตาราง <strong>$table_name</strong> มีอยู่แล้ว - ข้าม</div>";
            $skip_count++;
            continue;
        }
    }

    // Execute
    if ($conn->query($sql)) {
        // Determine what was done
        if (preg_match('/^CREATE TABLE/i', $sql)) {
            preg_match('/`(\w+)`/', $sql, $m);
            echo "<div class='success'>✅ สร้างตาราง <strong>{$m[1]}</strong> สำเร็จ</div>";
        } elseif (preg_match('/^INSERT INTO `?(\w+)`?/i', $sql, $m)) {
            $affected = $conn->affected_rows;
            echo "<div class='success'>✅ เพิ่มข้อมูลลงตาราง <strong>{$m[1]}</strong> ($affected rows)</div>";
        } elseif (preg_match('/^CREATE.*VIEW/i', $sql)) {
            preg_match('/VIEW\s+(\w+)/i', $sql, $m);
            echo "<div class='success'>✅ สร้าง View <strong>{$m[1]}</strong> สำเร็จ</div>";
        } else {
            echo "<div class='success'>✅ $display...</div>";
        }
        $success_count++;
    } else {
        // Check for duplicate key error (already exists)
        if ($conn->errno == 1062) {
            echo "<div class='info'>ℹ️ ข้อมูลมีอยู่แล้ว - ข้าม</div>";
            $skip_count++;
        } else {
            echo "<div class='error'>❌ Error: " . $conn->error . "<br><small>$display...</small></div>";
            $error_count++;
        }
    }
}

echo "<h2>📊 Summary</h2>";
echo "<div class='info'>";
echo "✅ สำเร็จ: <strong>$success_count</strong> | ";
echo "ℹ️ ข้าม: <strong>$skip_count</strong> | ";
echo "❌ ผิดพลาด: <strong>$error_count</strong>";
echo "</div>";

// Check what tables exist
echo "<h2>📋 ตารางที่มีในระบบ</h2>";
$tables = ['roles', 'user_roles', 'task_assignments', 'task_history'];
echo "<table style='width:100%;border-collapse:collapse;'>";
echo "<tr style='background:#f3f4f6;'><th style='padding:10px;text-align:left;border:1px solid #e5e7eb;'>ตาราง</th><th style='padding:10px;text-align:left;border:1px solid #e5e7eb;'>สถานะ</th><th style='padding:10px;text-align:left;border:1px solid #e5e7eb;'>จำนวนข้อมูล</th></tr>";

foreach ($tables as $table) {
    $check = $conn->query("SHOW TABLES LIKE '$table'");
    if ($check && $check->num_rows > 0) {
        $count_result = $conn->query("SELECT COUNT(*) as cnt FROM $table");
        $count = $count_result ? $count_result->fetch_assoc()['cnt'] : 0;
        echo "<tr><td style='padding:10px;border:1px solid #e5e7eb;'>$table</td>";
        echo "<td style='padding:10px;border:1px solid #e5e7eb;color:#059669;'>✅ พร้อมใช้งาน</td>";
        echo "<td style='padding:10px;border:1px solid #e5e7eb;'>$count รายการ</td></tr>";
    } else {
        echo "<tr><td style='padding:10px;border:1px solid #e5e7eb;'>$table</td>";
        echo "<td style='padding:10px;border:1px solid #e5e7eb;color:#dc2626;'>❌ ไม่พบ</td>";
        echo "<td style='padding:10px;border:1px solid #e5e7eb;'>-</td></tr>";
    }
}
echo "</table>";

// Show existing roles
$roles_check = $conn->query("SELECT * FROM roles ORDER BY display_order");
if ($roles_check && $roles_check->num_rows > 0) {
    echo "<h2>🎭 บทบาทในระบบ</h2>";
    echo "<div style='display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:10px;'>";
    while ($role = $roles_check->fetch_assoc()) {
        $assign_badge = $role['can_assign'] ? '<span style="background:#dbeafe;color:#2563eb;padding:2px 6px;border-radius:4px;font-size:11px;">มอบหมายได้</span>' : '';
        echo "<div style='background:white;border:1px solid #e5e7eb;border-radius:8px;padding:15px;'>";
        echo "<div style='display:flex;align-items:center;gap:10px;margin-bottom:8px;'>";
        echo "<i class='fas {$role['role_icon']}' style='color:{$role['role_color']};font-size:20px;'></i>";
        echo "<strong>{$role['role_name']}</strong>";
        echo "</div>";
        echo "<div style='color:#6b7280;font-size:13px;'>{$role['description']}</div>";
        echo "<div style='margin-top:8px;'>$assign_badge</div>";
        echo "</div>";
    }
    echo "</div>";
}

echo "<h2>🔗 ลิงก์ที่เกี่ยวข้อง</h2>";
echo "<div style='display:flex;gap:10px;flex-wrap:wrap;'>";
echo "<a href='admin/roles_manager.php' style='background:#009933;color:white;padding:10px 20px;border-radius:6px;text-decoration:none;'>จัดการบทบาท</a>";
echo "<a href='admin/user_roles.php' style='background:#3b82f6;color:white;padding:10px 20px;border-radius:6px;text-decoration:none;'>กำหนดบทบาทผู้ใช้</a>";
echo "<a href='admin/service_requests.php' style='background:#6366f1;color:white;padding:10px 20px;border-radius:6px;text-decoration:none;'>คำขอบริการ</a>";
echo "</div>";

echo "</div>";
echo "<link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'>";
echo "</body></html>";
?>
