<?php
/**
 * Test Admin Login Script
 * ทดสอบการเข้าสู่ระบบ Admin แบบละเอียด
 */

session_start();
require_once 'config/database.php';

echo "<h2>🔍 Test Admin Login Process</h2>";
echo "<hr>";

$username = 'admin';
$password = 'admin123';

echo "<h3>ข้อมูลที่ใช้ทดสอบ:</h3>";
echo "Username: <strong>$username</strong><br>";
echo "Password: <strong>$password</strong><br>";
echo "<hr>";

// Step 1: Check if user exists
echo "<h3>Step 1: ตรวจสอบว่ามีผู้ใช้นี้ในระบบหรือไม่</h3>";
$stmt = $conn->prepare("SELECT u.user_id, u.username, p.prefix_name, u.first_name, u.last_name,
                        u.email, u.password, u.role, u.status
                        FROM users u
                        LEFT JOIN prefixes p ON u.prefix_id = p.prefix_id
                        WHERE u.username = ? OR u.email = ?");
$stmt->bind_param("ss", $username, $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "❌ <strong style='color: red;'>ERROR: ไม่พบบัญชีผู้ใช้นี้ในระบบ</strong><br>";
    echo "<hr>";
    echo "<a href='fix_admin_password.php' style='background: #14b8a6; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>สร้างบัญชี Admin</a>";
    exit();
}

echo "✅ พบผู้ใช้ในระบบ<br>";
$user = $result->fetch_assoc();

echo "<pre>";
echo "User ID: " . $user['user_id'] . "\n";
echo "Username: " . $user['username'] . "\n";
echo "Email: " . $user['email'] . "\n";
echo "Role: " . $user['role'] . "\n";
echo "Status: " . $user['status'] . "\n";
echo "First Name: " . $user['first_name'] . "\n";
echo "Last Name: " . $user['last_name'] . "\n";
echo "Prefix: " . ($user['prefix_name'] ?? 'NULL') . "\n";
echo "</pre>";
echo "<hr>";

// Step 2: Check if user is admin
echo "<h3>Step 2: ตรวจสอบ Role</h3>";
if ($user['role'] !== 'admin') {
    echo "❌ <strong style='color: red;'>ERROR: คุณไม่มีสิทธิ์เข้าถึงระบบผู้ดูแล</strong><br>";
    echo "Current role: <strong>" . $user['role'] . "</strong><br>";
    exit();
}
echo "✅ Role เป็น admin<br>";
echo "<hr>";

// Step 3: Check if account is active
echo "<h3>Step 3: ตรวจสอบสถานะบัญชี</h3>";
if ($user['status'] !== 'active') {
    echo "❌ <strong style='color: red;'>ERROR: บัญชีของคุณถูกระงับการใช้งาน</strong><br>";
    echo "Current status: <strong>" . $user['status'] . "</strong><br>";
    exit();
}
echo "✅ สถานะบัญชีเป็น active<br>";
echo "<hr>";

// Step 4: Verify password
echo "<h3>Step 4: ตรวจสอบรหัสผ่าน</h3>";
echo "Testing password: <strong>$password</strong><br>";
echo "Hash in database (first 50 chars): " . substr($user['password'], 0, 50) . "...<br>";
echo "Hash length: " . strlen($user['password']) . " characters<br><br>";

$verify_result = password_verify($password, $user['password']);

if (!$verify_result) {
    echo "❌ <strong style='color: red;'>ERROR: รหัสผ่านไม่ถูกต้อง</strong><br><br>";

    // Generate correct hash for comparison
    $correct_hash = password_hash($password, PASSWORD_DEFAULT);
    echo "Expected hash format: " . substr($correct_hash, 0, 50) . "...<br><br>";

    echo "<div style='background: #fef3c7; border: 2px solid #f59e0b; padding: 15px; border-radius: 8px;'>";
    echo "<strong>⚠️ รหัสผ่านในฐานข้อมูลไม่ตรงกับ 'admin123'</strong><br>";
    echo "<a href='fix_admin_password.php' style='display: inline-block; margin-top: 10px; background: #f59e0b; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>แก้ไขรหัสผ่าน</a>";
    echo "</div>";
    exit();
}
echo "✅ <strong style='color: green;'>รหัสผ่านถูกต้อง!</strong><br>";
echo "<hr>";

// Step 5: Set session
echo "<h3>Step 5: สร้าง Session</h3>";
$_SESSION['user_id'] = $user['user_id'];
$_SESSION['username'] = $user['username'];
$_SESSION['first_name'] = $user['first_name'];
$_SESSION['last_name'] = $user['last_name'];
$_SESSION['prefix_name'] = $user['prefix_name'] ?? '';
$_SESSION['full_name'] = trim(($user['prefix_name'] ?? '') . ' ' . $user['first_name'] . ' ' . $user['last_name']);
$_SESSION['email'] = $user['email'];
$_SESSION['role'] = $user['role'];

echo "✅ Session ถูกสร้างเรียบร้อย<br>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";
echo "<hr>";

// Step 6: Update last login
echo "<h3>Step 6: อัปเดตเวลา Login ล่าสุด</h3>";
$update_stmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
$update_stmt->bind_param("i", $user['user_id']);
if ($update_stmt->execute()) {
    echo "✅ อัปเดต last_login เรียบร้อย<br>";
} else {
    echo "⚠️ ไม่สามารถอัปเดต last_login: " . $update_stmt->error . "<br>";
}
echo "<hr>";

// Final result
echo "<div style='background: #d1fae5; border: 2px solid #10b981; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
echo "<h2 style='color: #065f46; margin-top: 0;'>✅ การทดสอบเสร็จสมบูรณ์!</h2>";
echo "<p style='font-size: 16px;'>ทุกขั้นตอนผ่านการตรวจสอบ การ Login ควรจะใช้งานได้ปกติ</p>";
echo "<div style='margin-top: 15px;'>";
echo "<a href='admin-login.php' style='display: inline-block; background: #10b981; color: white; padding: 12px 24px; text-decoration: none; border-radius: 8px; font-weight: bold; margin-right: 10px;'>
    ไปที่หน้า Admin Login
</a>";
echo "<a href='admin/index.php' style='display: inline-block; background: #0d9488; color: white; padding: 12px 24px; text-decoration: none; border-radius: 8px; font-weight: bold;'>
    ไปที่ Admin Panel (ถ้า Session ยังอยู่)
</a>";
echo "</div>";
echo "</div>";

echo "<hr>";
echo "<h3>🔍 Debug Information</h3>";
echo "<p><a href='debug_admin.php'>Run Debug Script</a> | <a href='fix_admin_password.php'>Fix Password Script</a></p>";
?>
