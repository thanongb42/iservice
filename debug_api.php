<?php
/**
 * Debug page - ทดสอบ API task assignment
 * เข้าถึง: /debug_api.php
 * ลบทิ้งหลังแก้ไขเสร็จ!
 */
require_once 'config/database.php';

echo "<h2>🔍 Debug: Task Assignment API Check</h2>";
echo "<pre style='background:#f5f5f5; padding:15px; border-radius:8px; font-size:14px;'>";

// 1. Check connection
echo "✅ Database connected: " . DB_NAME . " @ " . DB_HOST . "\n\n";

// 2. Check required tables
$required_tables = ['users', 'roles', 'user_roles', 'service_requests', 'task_assignments', 'task_history', 'prefixes'];
echo "=== TABLES CHECK ===\n";
foreach ($required_tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    $exists = $result->num_rows > 0;
    $count = 0;
    if ($exists) {
        $count_result = $conn->query("SELECT COUNT(*) as cnt FROM `$table`");
        $count = $count_result->fetch_assoc()['cnt'];
    }
    echo ($exists ? "✅" : "❌") . " $table" . ($exists ? " ($count rows)" : " - MISSING!") . "\n";
}

// 3. Check views
echo "\n=== VIEWS CHECK ===\n";
$views = ['v_task_assignments', 'v_user_roles'];
foreach ($views as $view) {
    $result = $conn->query("SHOW TABLES LIKE '$view'");
    $exists = $result->num_rows > 0;
    if ($exists) {
        // Try to query it
        $test = $conn->query("SELECT * FROM `$view` LIMIT 1");
        if ($test) {
            echo "✅ $view (OK)\n";
        } else {
            echo "⚠️ $view (exists but query failed: " . $conn->error . ")\n";
        }
    } else {
        echo "❌ $view - MISSING!\n";
    }
}

// 4. Check task_assignments columns
echo "\n=== task_assignments COLUMNS ===\n";
$columns = $conn->query("SHOW COLUMNS FROM task_assignments");
while ($col = $columns->fetch_assoc()) {
    echo "  {$col['Field']} ({$col['Type']}) " . ($col['Null'] === 'YES' ? 'NULL' : 'NOT NULL') . " Default: {$col['Default']}\n";
}

// 5. Check FK constraints
echo "\n=== FOREIGN KEYS on task_assignments ===\n";
$fk_query = "SELECT CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME 
             FROM information_schema.KEY_COLUMN_USAGE 
             WHERE TABLE_NAME = 'task_assignments' 
             AND TABLE_SCHEMA = '" . DB_NAME . "' 
             AND REFERENCED_TABLE_NAME IS NOT NULL";
$fk_result = $conn->query($fk_query);
if ($fk_result && $fk_result->num_rows > 0) {
    while ($fk = $fk_result->fetch_assoc()) {
        echo "  {$fk['CONSTRAINT_NAME']}: {$fk['COLUMN_NAME']} → {$fk['REFERENCED_TABLE_NAME']}.{$fk['REFERENCED_COLUMN_NAME']}\n";
    }
} else {
    echo "  No foreign keys found\n";
}

// 6. Check roles data
echo "\n=== ROLES DATA ===\n";
$roles = $conn->query("SELECT * FROM roles WHERE is_active = 1 ORDER BY display_order");
if ($roles && $roles->num_rows > 0) {
    while ($r = $roles->fetch_assoc()) {
        echo "  [{$r['role_id']}] {$r['role_code']} - {$r['role_name']}\n";
    }
} else {
    echo "  ❌ No active roles found!\n";
}

// 7. Check user_roles data
echo "\n=== USER_ROLES DATA ===\n";
$ur = $conn->query("SELECT ur.*, u.username, u.first_name, u.last_name, r.role_code, r.role_name 
                     FROM user_roles ur 
                     JOIN users u ON ur.user_id = u.user_id 
                     JOIN roles r ON ur.role_id = r.role_id 
                     WHERE ur.is_active = 1");
if ($ur && $ur->num_rows > 0) {
    while ($row = $ur->fetch_assoc()) {
        echo "  User #{$row['user_id']} ({$row['first_name']} {$row['last_name']}) → {$row['role_code']} ({$row['role_name']})\n";
    }
} else {
    echo "  ❌ No active user roles found! This will cause 'ไม่พบผู้ใช้ที่มีบทบาท' error\n";
}

// 8. Check service_requests
echo "\n=== RECENT SERVICE REQUESTS ===\n";
$sr = $conn->query("SELECT request_id, request_code, service_code, service_name, status FROM service_requests ORDER BY request_id DESC LIMIT 5");
if ($sr && $sr->num_rows > 0) {
    while ($row = $sr->fetch_assoc()) {
        echo "  #{$row['request_id']} [{$row['request_code']}] {$row['service_code']} - {$row['service_name']} ({$row['status']})\n";
    }
} else {
    echo "  ❌ No service requests found!\n";
}

// 9. Test a mock insert (dry run)
echo "\n=== DRY RUN INSERT TEST ===\n";
$test_query = "INSERT INTO task_assignments (request_id, assigned_to, assigned_by, priority, notes, status, created_at) VALUES (0, 0, 0, 'normal', 'test', 'pending', NOW())";
// Don't actually execute, just prepare
$test_stmt = $conn->prepare($test_query);
if ($test_stmt) {
    echo "✅ INSERT prepare OK (without assigned_as_role)\n";
    $test_stmt->close();
} else {
    echo "❌ INSERT prepare FAILED: " . $conn->error . "\n";
}

// 10. Test with assigned_as_role
$test_query2 = "INSERT INTO task_assignments (request_id, assigned_to, assigned_as_role, assigned_by, priority, notes, status, created_at) VALUES (0, 0, NULL, 0, 'normal', 'test', 'pending', NOW())";
$test_stmt2 = $conn->prepare($test_query2);
if ($test_stmt2) {
    echo "✅ INSERT prepare OK (with assigned_as_role = NULL)\n";
    $test_stmt2->close();
} else {
    echo "❌ INSERT prepare FAILED with assigned_as_role: " . $conn->error . "\n";
}

echo "\n=== SESSION CHECK ===\n";
session_start();
if (isset($_SESSION['user_id'])) {
    echo "✅ Logged in as user #{$_SESSION['user_id']} (role: {$_SESSION['role']})\n";
} else {
    echo "⚠️ Not logged in - API will return 'Access denied'\n";
}

echo "</pre>";
echo "<p style='color:red; font-weight:bold;'>⚠️ ลบไฟล์นี้หลังแก้ไขปัญหาเสร็จ!</p>";
?>
