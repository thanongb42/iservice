<?php
/**
 * Simple Migration Runner - Step by Step
 * รัน migration ทีละ step เพื่อให้แน่ใจว่า ALTER TABLE commit ก่อน UPDATE
 */

require_once 'config/database.php';

echo "<!DOCTYPE html><html lang='th'><head><meta charset='UTF-8'><title>Migration Runner</title>";
echo "<style>
body{font-family:Arial,sans-serif;max-width:1200px;margin:20px auto;padding:20px;background:#f5f5f5;}
.success{background:#d1fae5;border:2px solid #10b981;padding:15px;border-radius:8px;margin:10px 0;color:#065f46;}
.error{background:#fee2e2;border:2px solid #ef4444;padding:15px;border-radius:8px;margin:10px 0;color:#991b1b;}
.warning{background:#fef3c7;border:2px solid #f59e0b;padding:15px;border-radius:8px;margin:10px 0;color:#92400e;}
.info{background:#dbeafe;border:2px solid #3b82f6;padding:15px;border-radius:8px;margin:10px 0;color:#1e40af;}
.step{background:white;padding:15px;margin:10px 0;border-left:4px solid #14b8a6;border-radius:4px;}
h1,h2,h3{color:#333;}
hr{margin:20px 0;border:1px solid #ccc;}
pre{background:#f3f4f6;padding:10px;border-radius:4px;font-size:12px;overflow-x:auto;}
</style></head><body>";

echo "<h1>🚀 Database Migration - Simple Version</h1>";
echo "<hr>";

if (!isset($_POST['confirm'])) {
    echo "<div class='warning'>";
    echo "<h3>⚠️ คำเตือน:</h3>";
    echo "<ul>";
    echo "<li>Migration นี้จะเปลี่ยนโครงสร้างฐานข้อมูล</li>";
    echo "<li>ข้อมูลเดิมจะถูก backup</li>";
    echo "<li>ควร backup ฐานข้อมูลทั้งหมดก่อน</li>";
    echo "</ul>";
    echo "</div>";

    echo "<form method='POST'>";
    echo "<div style='text-align:center;margin:30px 0;'>";
    echo "<button type='submit' name='confirm' value='1' style='background:#10b981;color:white;padding:15px 40px;border:none;border-radius:8px;font-size:18px;font-weight:bold;cursor:pointer;'>";
    echo "✅ รัน Migration";
    echo "</button>";
    echo "</div>";
    echo "</form>";
    echo "</body></html>";
    exit;
}

// Start migration
echo "<h2>📊 กำลังรัน Migration...</h2>";

$errors = [];

// Helper function to run SQL
function runSQL($conn, $sql, $step_name, &$errors) {
    echo "<div class='step'><strong>$step_name</strong></div>";

    try {
        if ($conn->multi_query($sql)) {
            do {
                if ($result = $conn->store_result()) {
                    $result->free();
                }
            } while ($conn->next_result());
        }

        if ($conn->error) {
            $errors[] = ['step' => $step_name, 'error' => $conn->error];
            echo "<div class='error'>❌ Error: " . htmlspecialchars($conn->error) . "</div>";
            return false;
        } else {
            echo "<div class='success'>✅ สำเร็จ</div>";
            return true;
        }
    } catch (Exception $e) {
        $errors[] = ['step' => $step_name, 'error' => $e->getMessage()];
        echo "<div class='error'>❌ Exception: " . htmlspecialchars($e->getMessage()) . "</div>";
        return false;
    }
}

// Step 1: Backup
$sql = "DROP TABLE IF EXISTS service_requests_backup;
CREATE TABLE service_requests_backup AS SELECT * FROM service_requests;";
runSQL($conn, $sql, "Step 1: Backup ข้อมูลเดิม", $errors);

// Step 2: Add columns (split into smaller parts)
echo "<div class='step'><strong>Step 2: เพิ่ม columns ใหม่</strong></div>";

$alter_sqls = [
    "ALTER TABLE service_requests ADD COLUMN user_id INT NULL COMMENT 'FK to users' AFTER id",
    "ALTER TABLE service_requests ADD COLUMN service_name VARCHAR(100) NULL AFTER service_code",
    "ALTER TABLE service_requests ADD COLUMN subject VARCHAR(255) NULL AFTER service_name",
    "ALTER TABLE service_requests ADD COLUMN description TEXT NULL AFTER subject",
    "ALTER TABLE service_requests ADD COLUMN department_id INT NULL AFTER description",
    "ALTER TABLE service_requests ADD COLUMN requester_prefix_id INT NULL AFTER department_id",
    "ALTER TABLE service_requests ADD COLUMN assigned_to_user_id INT NULL AFTER assigned_to"
];

foreach ($alter_sqls as $alter_sql) {
    $conn->query($alter_sql);
    if ($conn->error && strpos($conn->error, 'Duplicate column') === false) {
        echo "<div class='error'>❌ " . htmlspecialchars($conn->error) . "</div>";
        $errors[] = ['step' => 'Add Column', 'error' => $conn->error];
    }
}
echo "<div class='success'>✅ เพิ่ม columns เรียบร้อย</div>";

// Step 3: Populate service_name
$sql = "UPDATE service_requests
SET service_name = CASE service_code
    WHEN 'EMAIL' THEN 'ขอใช้บริการ Email'
    WHEN 'NAS' THEN 'ขอใช้พื้นที่ NAS'
    WHEN 'IT_SUPPORT' THEN 'ขอรับการสนับสนุนด้าน IT'
    WHEN 'INTERNET' THEN 'ขอใช้บริการ Internet'
    WHEN 'QR_CODE' THEN 'ขอทำ QR Code'
    WHEN 'PHOTOGRAPHY' THEN 'ขอถ่ายภาพกิจกรรม'
    WHEN 'WEB_DESIGN' THEN 'ขอออกแบบเว็บไซต์'
    WHEN 'PRINTER' THEN 'ขอใช้เครื่องพิมพ์'
    ELSE service_code
END WHERE service_name IS NULL";
runSQL($conn, $sql, "Step 3: Populate service_name", $errors);

// Step 4: Populate subject & description
$sql = "UPDATE service_requests
SET subject = CONCAT(COALESCE(service_name, 'คำขอ'), ' - ', requester_name),
    description = COALESCE(notes, CONCAT('คำขอ', service_name, ' จาก ', requester_name))
WHERE subject IS NULL OR description IS NULL";
runSQL($conn, $sql, "Step 4: Populate subject & description", $errors);

// Step 5: Create users for requesters
$sql = "INSERT IGNORE INTO users (username, email, first_name, last_name, password, role, status, created_at)
SELECT
    SUBSTRING_INDEX(sr.requester_email, '@', 1) as username,
    sr.requester_email as email,
    SUBSTRING_INDEX(sr.requester_name, ' ', 1) as first_name,
    SUBSTRING_INDEX(sr.requester_name, ' ', -1) as last_name,
    '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' as password,
    'user' as role,
    'active' as status,
    NOW() as created_at
FROM service_requests sr
WHERE sr.requester_email IS NOT NULL
  AND sr.requester_email != ''
GROUP BY sr.requester_email";
runSQL($conn, $sql, "Step 5: สร้าง users จาก requester emails", $errors);

// Step 6: Map user_id
$sql = "UPDATE service_requests sr
INNER JOIN users u ON sr.requester_email = u.email
SET sr.user_id = u.user_id
WHERE sr.requester_email IS NOT NULL AND sr.user_id IS NULL";
runSQL($conn, $sql, "Step 6: Map user_id จาก email", $errors);

// Step 7: Create departments
$sql = "INSERT IGNORE INTO departments (department_name, department_code, status, created_at)
SELECT DISTINCT
    sr.department as department_name,
    UPPER(REPLACE(SUBSTRING(sr.department, 1, 20), ' ', '_')) as department_code,
    'active' as status,
    NOW() as created_at
FROM service_requests sr
WHERE sr.department IS NOT NULL AND sr.department != ''";
runSQL($conn, $sql, "Step 7: สร้าง departments", $errors);

// Step 8: Map department_id
$sql = "UPDATE service_requests sr
INNER JOIN departments d ON sr.department = d.department_name
SET sr.department_id = d.department_id
WHERE sr.department IS NOT NULL AND sr.department_id IS NULL";
runSQL($conn, $sql, "Step 8: Map department_id", $errors);

// Step 9: Create view
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
runSQL($conn, $sql, "Step 9: สร้าง View", $errors);

// Summary
echo "<hr>";
if (empty($errors)) {
    echo "<div class='success'>";
    echo "<h2>✅ Migration สำเร็จ!</h2>";
    echo "<p>ฐานข้อมูลถูกแปลงเป็น Relational Database แล้ว</p>";
    echo "</div>";

    // Stats
    $stats = $conn->query("SELECT
        COUNT(*) as total,
        SUM(CASE WHEN user_id IS NOT NULL THEN 1 ELSE 0 END) as has_user_id,
        SUM(CASE WHEN department_id IS NOT NULL THEN 1 ELSE 0 END) as has_dept_id
    FROM service_requests");

    if ($stats && $row = $stats->fetch_assoc()) {
        echo "<div class='info'>";
        echo "<h3>📊 สถิติ:</h3>";
        echo "<ul>";
        echo "<li>คำขอทั้งหมด: {$row['total']}</li>";
        echo "<li>มี user_id: {$row['has_user_id']}</li>";
        echo "<li>มี department_id: {$row['has_dept_id']}</li>";
        echo "</ul>";
        echo "</div>";
    }

    // Sample
    $sample = $conn->query("SELECT * FROM v_service_requests_full LIMIT 3");
    if ($sample && $sample->num_rows > 0) {
        echo "<div class='info'><h3>📋 ตัวอย่างข้อมูล:</h3><table border='1' cellpadding='8' style='border-collapse:collapse;width:100%;background:white;'>";
        echo "<tr style='background:#14b8a6;color:white;'><th>ID</th><th>Service Name</th><th>User</th><th>Department</th><th>Status</th></tr>";
        while ($r = $sample->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($r['request_id']) . "</td>";
            echo "<td>" . htmlspecialchars($r['service_name']) . "</td>";
            echo "<td>" . htmlspecialchars($r['user_full_name'] ?? $r['requester_name']) . "</td>";
            echo "<td>" . htmlspecialchars($r['department_name'] ?? '-') . "</td>";
            echo "<td>" . htmlspecialchars($r['status']) . "</td>";
            echo "</tr>";
        }
        echo "</table></div>";
    }
} else {
    echo "<div class='error'>";
    echo "<h3>⚠️ Migration มี Errors:</h3>";
    echo "<ul>";
    foreach ($errors as $err) {
        echo "<li><strong>{$err['step']}:</strong> {$err['error']}</li>";
    }
    echo "</ul>";
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
