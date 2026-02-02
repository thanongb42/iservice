<?php
/**
 * Test if admin pages can be accessed
 */

session_start();
// Simulate admin session
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'admin';
$_SESSION['username'] = 'admin';

// Change to admin directory
chdir(__DIR__ . '/admin');

// Test system_setting.php
echo "Testing admin/system_setting.php...\n";
ob_start();
try {
    require 'system_setting.php';
    $output = ob_get_clean();
    if (strpos($output, 'ตั้งค่าระบบ') !== false) {
        echo "✓ system_setting.php loaded successfully\n";
    } else {
        echo "✗ Page loaded but header not found\n";
    }
} catch (Exception $e) {
    ob_get_clean();
    echo "✗ Error: " . $e->getMessage() . "\n";
}

// Change back
chdir(__DIR__);

// Test admin_report.php
echo "Testing admin/admin_report.php...\n";
chdir(__DIR__ . '/admin');
ob_start();
try {
    require 'admin_report.php';
    $output = ob_get_clean();
    if (strpos($output, 'รายงาน') !== false) {
        echo "✓ admin_report.php loaded successfully\n";
    } else {
        echo "✗ Page loaded but header not found\n";
    }
} catch (Exception $e) {
    ob_get_clean();
    echo "✗ Error: " . $e->getMessage() . "\n";
}

// Test user-manager.php
echo "Testing admin/user-manager.php...\n";
chdir(__DIR__ . '/admin');
ob_start();
try {
    // Modify query for test since it connects to DB
    require 'user-manager.php';
    $output = ob_get_clean();
    // Check for some unique content
    if (strpos($output, 'จัดการข้อมูลผู้ใช้งานทั้งหมด') !== false) {
        echo "✓ user-manager.php loaded successfully\n";
    } else {
        echo "✗ Page loaded but content not found\n";
    }
} catch (Exception $e) {
    ob_get_clean();
    echo "✗ Error: " . $e->getMessage() . "\n";
}
?>
