<?php
/**
 * Test task_assignment_api.php directly
 * ลบทิ้งหลังแก้ไขเสร็จ!
 */
session_start();
require_once 'config/database.php';

// Check if logged in
if (!isset($_SESSION['user_id'])) {
    echo "<h2>❌ ยังไม่ได้ Login</h2>";
    echo "<p>กรุณา <a href='login.php'>Login</a> ก่อนแล้วกลับมาหน้านี้</p>";
    exit;
}

echo "<h2>🔍 Test Task Assignment API - Direct PHP Test</h2>";
echo "<p>Logged in as User #{$_SESSION['user_id']} (role: {$_SESSION['role']})</p>";
echo "<pre style='background:#f5f5f5; padding:15px; border-radius:8px; font-size:13px;'>";

// Test 1: Check tables
echo "=== DB CONNECTION ===\n";
echo "DB: " . DB_NAME . " @ " . DB_HOST . "\n\n";

// Test 2: Simulate what API does - check user_roles for manager permission
echo "=== PERMISSION CHECK (same as API) ===\n";
$chk = $conn->prepare("
    SELECT COUNT(*) as cnt FROM user_roles ur
    JOIN roles r ON ur.role_id = r.role_id
    WHERE ur.user_id = ? AND r.role_code IN ('manager', 'all')
    AND ur.is_active = 1 AND r.is_active = 1
");
$chk->bind_param('i', $_SESSION['user_id']);
$chk->execute();
$result = $chk->get_result()->fetch_assoc();
echo "User #{$_SESSION['user_id']} is manager/all: " . ($result['cnt'] > 0 ? "YES ✅" : "NO ❌") . " (cnt={$result['cnt']})\n\n";

// Test 3: Simulate assign_task INSERT (without actually inserting)
echo "=== SIMULATE INSERT (request_id=37, assigned_to=11) ===\n";
$request_id = 37;
$assigned_to = 11;
$admin_id = $_SESSION['user_id'];

// Check request exists
$sr = $conn->prepare("SELECT request_id, service_code FROM service_requests WHERE request_id = ?");
$sr->bind_param('i', $request_id);
$sr->execute();
$sr_result = $sr->get_result()->fetch_assoc();
echo "Request #$request_id exists: " . ($sr_result ? "YES ✅ (service: {$sr_result['service_code']})" : "NO ❌") . "\n";

// Check assigned_to user exists
$u = $conn->prepare("SELECT user_id, first_name, last_name FROM users WHERE user_id = ?");
$u->bind_param('i', $assigned_to);
$u->execute();
$u_result = $u->get_result()->fetch_assoc();
echo "User #$assigned_to exists: " . ($u_result ? "YES ✅ ({$u_result['first_name']} {$u_result['last_name']})" : "NO ❌") . "\n";

// Check assigned_by user exists
$u2 = $conn->prepare("SELECT user_id, first_name, last_name FROM users WHERE user_id = ?");
$u2->bind_param('i', $admin_id);
$u2->execute();
$u2_result = $u2->get_result()->fetch_assoc();
echo "Assigned_by #$admin_id exists: " . ($u2_result ? "YES ✅ ({$u2_result['first_name']} {$u2_result['last_name']})" : "NO ❌") . "\n";

// Try the actual INSERT
echo "\n=== ACTUAL INSERT TEST ===\n";
$insert_query = "INSERT INTO task_assignments (request_id, assigned_to, assigned_by, priority, notes, status, created_at) VALUES (?, ?, ?, 'normal', 'test from debug page', 'pending', NOW())";
$insert_stmt = $conn->prepare($insert_query);
if (!$insert_stmt) {
    echo "❌ Prepare failed: " . $conn->error . "\n";
} else {
    $insert_stmt->bind_param('iii', $request_id, $assigned_to, $admin_id);
    if ($insert_stmt->execute()) {
        $new_id = $insert_stmt->insert_id;
        echo "✅ INSERT SUCCESS! assignment_id = $new_id\n";
        
        // Delete the test record
        $conn->query("DELETE FROM task_assignments WHERE assignment_id = $new_id");
        echo "🗑️ Test record deleted\n";
    } else {
        echo "❌ INSERT FAILED: " . $insert_stmt->error . "\n";
        echo "   Error code: " . $insert_stmt->errno . "\n";
    }
}

// Test 4: Check .htaccess on production
echo "\n=== .htaccess CHECK ===\n";
$htaccess_file = __DIR__ . '/.htaccess';
if (file_exists($htaccess_file)) {
    echo "Root .htaccess exists\n";
    echo htmlspecialchars(file_get_contents($htaccess_file)) . "\n";
} else {
    echo "No root .htaccess\n";
}

$admin_htaccess = __DIR__ . '/admin/.htaccess';
if (file_exists($admin_htaccess)) {
    echo "\nadmin/.htaccess exists:\n";
    echo htmlspecialchars(file_get_contents($admin_htaccess)) . "\n";
} else {
    echo "\nNo admin/.htaccess\n";
}

$api_htaccess = __DIR__ . '/admin/api/.htaccess';
if (file_exists($api_htaccess)) {
    echo "\nadmin/api/.htaccess exists:\n";
    echo htmlspecialchars(file_get_contents($api_htaccess)) . "\n";
} else {
    echo "\nNo admin/api/.htaccess\n";
}

// Test 5: JavaScript fetch test
echo "\n=== PHP VERSION ===\n";
echo "PHP: " . phpversion() . "\n";
echo "Server: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'unknown') . "\n";

echo "</pre>";

echo "<h3>Test 5: JavaScript Fetch Test (เหมือน request_detail.php ทำ)</h3>";
echo "<div id='js-result' style='background:#f5f5f5; padding:15px; border-radius:8px;'>Testing...</div>";
echo "<script>
async function testFetch() {
    const div = document.getElementById('js-result');
    let html = '';
    
    // Test GET
    try {
        html += '<b>GET test:</b><br>';
        const r1 = await fetch('admin/api/task_assignment_api.php?action=get_available_users&service_code=NAS&request_id=37');
        html += 'Status: ' + r1.status + '<br>';
        const t1 = await r1.text();
        html += 'Response (' + t1.length + ' bytes): <code>' + t1.substring(0, 500) + '</code><br><br>';
    } catch(e) {
        html += 'GET Error: ' + e.message + '<br><br>';
    }
    
    // Test POST
    try {
        html += '<b>POST test:</b><br>';
        const formData = new FormData();
        formData.append('action', 'assign_task');
        formData.append('request_id', '37');
        formData.append('assigned_to', '11');
        formData.append('priority', 'normal');
        formData.append('notes', 'JS fetch test');
        
        const r2 = await fetch('admin/api/task_assignment_api.php', { method: 'POST', body: formData });
        html += 'Status: ' + r2.status + '<br>';
        const t2 = await r2.text();
        html += 'Response (' + t2.length + ' bytes): <code>' + t2.substring(0, 500) + '</code><br>';
        
        // Hex dump
        html += 'Hex: ';
        for(let i = 0; i < Math.min(30, t2.length); i++) {
            html += t2.charCodeAt(i).toString(16).padStart(2, '0') + ' ';
        }
        html += '<br>';
    } catch(e) {
        html += 'POST Error: ' + e.message + '<br>';
    }
    
    div.innerHTML = html;
}
testFetch();
</script>";

echo "<p style='color:red; font-weight:bold;'>⚠️ ลบไฟล์นี้หลังแก้ไขปัญหาเสร็จ!</p>";
?>
