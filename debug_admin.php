<?php
/**
 * Debug Script for Admin Login
 * ตรวจสอบข้อมูล Admin และการเข้าสู่ระบบ
 */

require_once 'config/database.php';

echo "<h2>Admin Login Debug Information</h2>";
echo "<hr>";

// 1. Check if users table exists
echo "<h3>1. Check Tables</h3>";
$tables = ['users', 'prefixes', 'departments'];
foreach ($tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    $exists = $result->num_rows > 0 ? '✅ EXISTS' : '❌ NOT EXISTS';
    echo "Table '$table': $exists<br>";
}
echo "<hr>";

// 2. Check if admin user exists
echo "<h3>2. Check Admin User</h3>";
$stmt = $conn->prepare("SELECT user_id, username, email, role, status,
                        SUBSTRING(password, 1, 30) as pwd_preview,
                        first_name, last_name, prefix_id
                        FROM users WHERE username = ?");
$username = 'admin';
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo "✅ Admin user found!<br><br>";
    $user = $result->fetch_assoc();
    echo "<pre>";
    print_r($user);
    echo "</pre>";

    // 3. Test password verification
    echo "<h3>3. Test Password Verification</h3>";
    $test_password = 'admin123';
    $stored_hash = $user['password']; // Get full hash

    // Get full password hash
    $stmt2 = $conn->prepare("SELECT password FROM users WHERE username = ?");
    $stmt2->bind_param("s", $username);
    $stmt2->execute();
    $result2 = $stmt2->get_result();
    $user_full = $result2->fetch_assoc();
    $full_hash = $user_full['password'];

    echo "Test Password: <strong>$test_password</strong><br>";
    echo "Stored Hash Preview: " . substr($full_hash, 0, 50) . "...<br>";
    echo "Hash Length: " . strlen($full_hash) . " characters<br><br>";

    $verify_result = password_verify($test_password, $full_hash);

    if ($verify_result) {
        echo "✅ <strong style='color: green;'>Password verification SUCCESSFUL!</strong><br>";
    } else {
        echo "❌ <strong style='color: red;'>Password verification FAILED!</strong><br>";
        echo "<br>Expected hash for 'admin123':<br>";
        $correct_hash = password_hash($test_password, PASSWORD_DEFAULT);
        echo substr($correct_hash, 0, 50) . "...<br>";
    }

} else {
    echo "❌ <strong style='color: red;'>Admin user NOT found!</strong><br><br>";
    echo "Creating admin user now...<br><br>";

    // Create admin user
    $password = 'admin123';
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Check if prefix exists
    $prefix_check = $conn->query("SELECT prefix_id FROM prefixes WHERE prefix_id = 1");
    if ($prefix_check->num_rows === 0) {
        echo "⚠️ Warning: Prefix ID 1 doesn't exist. Using NULL for prefix.<br>";
        $prefix_id = null;
    } else {
        $prefix_id = 1;
    }

    $insert_stmt = $conn->prepare("INSERT INTO users
        (prefix_id, username, first_name, last_name, email, password, role, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");

    $first_name = 'ผู้ดูแลระบบ';
    $last_name = 'เทศบาล';
    $email = 'admin@rangsit.go.th';
    $role = 'admin';
    $status = 'active';

    $insert_stmt->bind_param("isssssss",
        $prefix_id, $username, $first_name, $last_name,
        $email, $hashed_password, $role, $status
    );

    if ($insert_stmt->execute()) {
        echo "✅ <strong style='color: green;'>Admin user created successfully!</strong><br>";
        echo "<br>Login Credentials:<br>";
        echo "Username: <strong>admin</strong><br>";
        echo "Password: <strong>admin123</strong><br>";
    } else {
        echo "❌ Failed to create admin user: " . $insert_stmt->error . "<br>";
    }
}

echo "<hr>";

// 4. Test full authentication query
echo "<h3>4. Test Full Authentication Query</h3>";
$test_username = 'admin';
$stmt3 = $conn->prepare("SELECT u.user_id, u.username, p.prefix_name, u.first_name, u.last_name,
                        u.email, u.password, u.role, u.status
                        FROM users u
                        LEFT JOIN prefixes p ON u.prefix_id = p.prefix_id
                        WHERE u.username = ? OR u.email = ?");
$stmt3->bind_param("ss", $test_username, $test_username);
$stmt3->execute();
$result3 = $stmt3->get_result();

if ($result3->num_rows > 0) {
    echo "✅ Authentication query returned " . $result3->num_rows . " row(s)<br><br>";
    $auth_user = $result3->fetch_assoc();
    echo "User Details:<br>";
    echo "- User ID: " . $auth_user['user_id'] . "<br>";
    echo "- Username: " . $auth_user['username'] . "<br>";
    echo "- Email: " . $auth_user['email'] . "<br>";
    echo "- Role: " . $auth_user['role'] . "<br>";
    echo "- Status: " . $auth_user['status'] . "<br>";
    echo "- Prefix: " . ($auth_user['prefix_name'] ?? 'NULL') . "<br>";
    echo "- Full Name: " . $auth_user['first_name'] . " " . $auth_user['last_name'] . "<br>";
} else {
    echo "❌ Authentication query returned no results<br>";
}

echo "<hr>";
echo "<h3>Summary</h3>";
echo "<a href='admin-login.php' style='display: inline-block; background: #14b8a6; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-top: 10px;'>
    Go to Admin Login Page
</a>";
?>
