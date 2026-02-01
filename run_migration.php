<?php
/**
 * Run Migration Script
 * รัน migration เพื่อแปลง service_requests เป็น relational database
 */

require_once 'config/database.php';

echo "<!DOCTYPE html><html lang='th'><head><meta charset='UTF-8'><title>Run Migration</title>";
echo "<style>
body{font-family:Arial,sans-serif;max-width:1200px;margin:20px auto;padding:20px;background:#f5f5f5;}
.success{background:#d1fae5;border:2px solid #10b981;padding:15px;border-radius:8px;margin:10px 0;color:#065f46;}
.error{background:#fee2e2;border:2px solid #ef4444;padding:15px;border-radius:8px;margin:10px 0;color:#991b1b;}
.warning{background:#fef3c7;border:2px solid #f59e0b;padding:15px;border-radius:8px;margin:10px 0;color:#92400e;}
.info{background:#dbeafe;border:2px solid #3b82f6;padding:15px;border-radius:8px;margin:10px 0;color:#1e40af;}
.step{background:white;padding:15px;margin:10px 0;border-left:4px solid #14b8a6;border-radius:4px;}
table{border-collapse:collapse;width:100%;background:white;margin:10px 0;}
th,td{padding:10px;border:1px solid #ddd;text-align:left;}
th{background:#14b8a6;color:white;}
h1,h2,h3{color:#333;}
hr{margin:20px 0;border:1px solid #ccc;}
.progress{background:#e5e7eb;height:30px;border-radius:15px;overflow:hidden;margin:20px 0;}
.progress-bar{background:linear-gradient(90deg,#14b8a6,#0d9488);height:100%;display:flex;align-items:center;justify-content:center;color:white;font-weight:bold;transition:width 0.3s;}
</style></head><body>";

echo "<h1>🚀 Database Migration to Relational Schema</h1>";
echo "<hr>";

echo "<div class='warning'>";
echo "<h3>⚠️ คำเตือนสำคัญ:</h3>";
echo "<ul>";
echo "<li><strong>Migration นี้จะเปลี่ยนโครงสร้างฐานข้อมูล</strong></li>";
echo "<li>ข้อมูลเดิมจะถูก backup ไว้ที่ตาราง <code>service_requests_backup</code></li>";
echo "<li>ควรทำ backup ฐานข้อมูลทั้งหมดก่อนรัน migration</li>";
echo "<li>กระบวนการนี้อาจใช้เวลาสักครู่</li>";
echo "</ul>";
echo "</div>";

if (isset($_POST['confirm'])) {
    echo "<h2>📊 กำลังรัน Migration...</h2>";
    echo "<div class='progress'><div class='progress-bar' id='progressBar' style='width:0%'>0%</div></div>";

    // Read SQL file
    $sql_file = __DIR__ . '/database/migrate_service_requests_to_relational.sql';

    if (!file_exists($sql_file)) {
        echo "<div class='error'>❌ ไม่พบไฟล์ migration: $sql_file</div>";
        exit;
    }

    $sql_content = file_get_contents($sql_file);

    // Remove DELIMITER statements (not supported in multi_query)
    $sql_content = preg_replace('/DELIMITER\s+\/\/\s*/i', '', $sql_content);
    $sql_content = preg_replace('/DELIMITER\s+;\s*/i', '', $sql_content);

    // Replace // delimiters in procedures/triggers with single semicolons
    $sql_content = preg_replace('/\/\/\s*$/m', ';', $sql_content);

    // Split by steps (looking for SELECT 'Step X: messages)
    preg_match_all("/SELECT 'Step (\d+): (.+?)' as status;/", $sql_content, $matches);
    $total_steps = count($matches[0]);

    echo "<div id='steps'>";

    $current_step = 0;
    $errors = [];

    // Split SQL content by step markers
    $steps = preg_split("/(SELECT 'Step \d+:.*?' as status;)/", $sql_content, -1, PREG_SPLIT_DELIM_CAPTURE);

    for ($i = 0; $i < count($steps); $i++) {
        $chunk = trim($steps[$i]);
        if (empty($chunk)) continue;

        // Check if this is a step marker
        if (preg_match("/SELECT 'Step (\d+): (.+?)' as status/", $chunk, $step_match)) {
            $current_step++;
            $step_num = $step_match[1];
            $step_msg = $step_match[2];

            echo "<div class='step'>";
            echo "<strong>Step $step_num:</strong> $step_msg";
            echo "</div>";

            $progress = ($current_step / $total_steps) * 100;
            echo "<script>
                document.getElementById('progressBar').style.width = '{$progress}%';
                document.getElementById('progressBar').textContent = '" . round($progress) . "%';
            </script>";

            flush();
            ob_flush();
            continue;
        }

        // Execute the SQL chunk
        if (!empty($chunk)) {
            try {
                // Use multi_query for complex statements
                if ($conn->multi_query($chunk)) {
                    do {
                        // Store result if any
                        if ($result = $conn->store_result()) {
                            $result->free();
                        }
                        // Check for more results
                        if (!$conn->more_results()) break;
                    } while ($conn->next_result());
                }

                // Check for errors
                if ($conn->error) {
                    $errors[] = [
                        'statement' => substr($chunk, 0, 100) . '...',
                        'error' => $conn->error
                    ];
                }
            } catch (Exception $e) {
                $errors[] = [
                    'statement' => substr($chunk, 0, 100) . '...',
                    'error' => $e->getMessage()
                ];
            }
        }
    }

    echo "</div>";

    echo "<script>
        document.getElementById('progressBar').style.width = '100%';
        document.getElementById('progressBar').textContent = '100%';
    </script>";

    if (empty($errors)) {
        echo "<div class='success'>";
        echo "<h2>✅ Migration สำเร็จ!</h2>";
        echo "<p>ฐานข้อมูลถูกแปลงเป็น Relational Database แล้ว</p>";
        echo "</div>";

        // Show statistics
        $stats = $conn->query("SELECT
            COUNT(*) as total,
            SUM(CASE WHEN user_id IS NOT NULL THEN 1 ELSE 0 END) as has_user_id,
            SUM(CASE WHEN department_id IS NOT NULL THEN 1 ELSE 0 END) as has_dept_id,
            SUM(CASE WHEN assigned_to_user_id IS NOT NULL THEN 1 ELSE 0 END) as has_assigned
        FROM service_requests");

        if ($stats && $row = $stats->fetch_assoc()) {
            echo "<div class='info'>";
            echo "<h3>📊 สถิติข้อมูล:</h3>";
            echo "<table>";
            echo "<tr><th>รายการ</th><th>จำนวน</th></tr>";
            echo "<tr><td>คำขอทั้งหมด</td><td>{$row['total']}</td></tr>";
            echo "<tr><td>มี user_id</td><td>{$row['has_user_id']}</td></tr>";
            echo "<tr><td>มี department_id</td><td>{$row['has_dept_id']}</td></tr>";
            echo "<tr><td>ถูกมอบหมายแล้ว</td><td>{$row['has_assigned']}</td></tr>";
            echo "</table>";
            echo "</div>";
        }

        // Show sample data
        $sample = $conn->query("SELECT
            request_id, request_code, service_name,
            user_full_name, department_name, assigned_full_name,
            status, priority
        FROM v_service_requests_full LIMIT 5");

        if ($sample && $sample->num_rows > 0) {
            echo "<div class='info'>";
            echo "<h3>📋 ข้อมูลตัวอย่าง:</h3>";
            echo "<table>";
            echo "<tr><th>ID</th><th>Code</th><th>Service</th><th>Requester</th><th>Department</th><th>Assigned</th><th>Status</th></tr>";

            while ($row = $sample->fetch_assoc()) {
                echo "<tr>";
                echo "<td>#" . str_pad($row['request_id'], 4, '0', STR_PAD_LEFT) . "</td>";
                echo "<td>" . htmlspecialchars($row['request_code']) . "</td>";
                echo "<td>" . htmlspecialchars($row['service_name']) . "</td>";
                echo "<td>" . htmlspecialchars($row['user_full_name'] ?? '-') . "</td>";
                echo "<td>" . htmlspecialchars($row['department_name'] ?? '-') . "</td>";
                echo "<td>" . htmlspecialchars($row['assigned_full_name'] ?? '-') . "</td>";
                echo "<td>" . htmlspecialchars($row['status']) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            echo "</div>";
        }

    } else {
        echo "<div class='error'>";
        echo "<h3>⚠️ Migration เสร็จสิ้นแต่มี Error บางส่วน:</h3>";
        echo "<ul>";
        foreach ($errors as $error) {
            echo "<li><strong>Statement:</strong> " . htmlspecialchars($error['statement']) . "<br>";
            echo "<strong>Error:</strong> " . htmlspecialchars($error['error']) . "</li>";
        }
        echo "</ul>";
        echo "</div>";
    }

    echo "<hr>";
    echo "<h3>✅ สิ่งที่เปลี่ยนแปลง:</h3>";
    echo "<div class='success'>";
    echo "<ul>";
    echo "<li>✅ เปลี่ยน <code>id</code> → <code>request_id</code></li>";
    echo "<li>✅ เพิ่ม <code>user_id</code> (FK to users)</li>";
    echo "<li>✅ เพิ่ม <code>department_id</code> (FK to departments)</li>";
    echo "<li>✅ เพิ่ม <code>assigned_to_user_id</code> (FK to users)</li>";
    echo "<li>✅ เพิ่ม <code>service_name</code> จาก service_code</li>";
    echo "<li>✅ เพิ่ม <code>subject</code> และ <code>description</code></li>";
    echo "<li>✅ สร้าง Foreign Keys และ Indexes</li>";
    echo "<li>✅ สร้าง Triggers สำหรับ auto-update timestamps</li>";
    echo "<li>✅ สร้าง View และ Stored Procedures ใหม่</li>";
    echo "<li>✅ Backup ข้อมูลเดิมไว้ที่ <code>service_requests_backup</code></li>";
    echo "</ul>";
    echo "</div>";

    echo "<div style='margin-top:20px;'>";
    echo "<a href='admin/service_requests.php' style='display:inline-block;background:#14b8a6;color:white;padding:12px 24px;text-decoration:none;border-radius:8px;font-weight:bold;margin-right:10px;'>
    📋 ไปที่หน้าจัดการคำขอบริการ
    </a>";
    echo "<a href='check_table_structure.php' style='display:inline-block;background:#6b7280;color:white;padding:12px 24px;text-decoration:none;border-radius:8px;font-weight:bold;'>
    🔍 ตรวจสอบโครงสร้างใหม่
    </a>";
    echo "</div>";

} else {
    // Show confirmation form
    echo "<h2>📋 ก่อนเริ่ม Migration:</h2>";

    // Show current structure
    $current_cols = $conn->query("DESCRIBE service_requests");
    echo "<div class='info'>";
    echo "<h3>โครงสร้างปัจจุบัน:</h3>";
    echo "<table>";
    echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th></tr>";
    while ($col = $current_cols->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($col['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Key']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "</div>";

    // Count records
    $count = $conn->query("SELECT COUNT(*) as cnt FROM service_requests")->fetch_assoc();
    echo "<div class='info'>";
    echo "<h3>จำนวนข้อมูล: <strong>" . $count['cnt'] . "</strong> รายการ</h3>";
    echo "</div>";

    echo "<div class='success'>";
    echo "<h3>📝 Migration จะทำอะไร:</h3>";
    echo "<ol>";
    echo "<li><strong>Backup</strong> ข้อมูลเดิมทั้งหมด</li>";
    echo "<li><strong>เพิ่ม columns</strong> ใหม่ (user_id, department_id, service_name, etc.)</li>";
    echo "<li><strong>Migrate data</strong> จาก text fields ไป FK fields</li>";
    echo "<li><strong>สร้าง users</strong> สำหรับ requesters และ assigned staff</li>";
    echo "<li><strong>สร้าง departments</strong> จาก department text</li>";
    echo "<li><strong>เพิ่ม Foreign Keys</strong> และ Indexes</li>";
    echo "<li><strong>สร้าง Triggers</strong> สำหรับ auto-update</li>";
    echo "<li><strong>สร้าง View</strong> และ Stored Procedures ใหม่</li>";
    echo "</ol>";
    echo "</div>";

    echo "<form method='POST'>";
    echo "<div style='text-align:center;margin:30px 0;'>";
    echo "<button type='submit' name='confirm' value='1' style='background:#10b981;color:white;padding:15px 40px;border:none;border-radius:8px;font-size:18px;font-weight:bold;cursor:pointer;'>";
    echo "✅ ยืนยันและรัน Migration";
    echo "</button>";
    echo "</div>";
    echo "</form>";

    echo "<div class='warning' style='text-align:center;'>";
    echo "<p><strong>⚠️ กรุณา Backup ฐานข้อมูลก่อนรัน Migration</strong></p>";
    echo "</div>";
}

echo "</body></html>";

$conn->close();
?>
