<?php
/**
 * Fix Admin Password Script
 * แก้ไขรหัสผ่าน Admin เป็น admin123
 */

require_once 'config/database.php';

echo "<h2>Fix Admin Password</h2>";
echo "<hr>";

// New password
$new_password = 'admin123';
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

echo "<h3>Step 1: Check current admin user</h3>";
$stmt = $conn->prepare("SELECT user_id, username, email, role, status FROM users WHERE username = ?");
$username = 'admin';
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    echo "✅ Found admin user:<br>";
    echo "<pre>";
    print_r($user);
    echo "</pre>";

    echo "<h3>Step 2: Update password to 'admin123'</h3>";

    $update_stmt = $conn->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE username = ?");
    $update_stmt->bind_param("ss", $hashed_password, $username);

    if ($update_stmt->execute()) {
        echo "✅ <strong style='color: green;'>Password updated successfully!</strong><br><br>";

        echo "<div style='background: #f0fdfa; border: 2px solid #14b8a6; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
        echo "<h3 style='color: #0d9488; margin-top: 0;'>✅ Admin Login Credentials</h3>";
        echo "<p style='margin: 5px 0;'><strong>Username:</strong> admin</p>";
        echo "<p style='margin: 5px 0;'><strong>Password:</strong> admin123</p>";
        echo "</div>";

        // Verify the new password
        echo "<h3>Step 3: Verify new password</h3>";
        $verify_stmt = $conn->prepare("SELECT password FROM users WHERE username = ?");
        $verify_stmt->bind_param("s", $username);
        $verify_stmt->execute();
        $verify_result = $verify_stmt->get_result();
        $verify_user = $verify_result->fetch_assoc();

        $verification = password_verify($new_password, $verify_user['password']);

        if ($verification) {
            echo "✅ <strong style='color: green;'>Password verification SUCCESSFUL!</strong><br>";
            echo "You can now login with username 'admin' and password 'admin123'<br>";
        } else {
            echo "❌ <strong style='color: red;'>Password verification FAILED!</strong><br>";
        }

    } else {
        echo "❌ Failed to update password: " . $update_stmt->error . "<br>";
    }

} else {
    echo "❌ <strong style='color: red;'>Admin user not found!</strong><br>";
    echo "Creating new admin user...<br><br>";

    // Create admin user
    $insert_stmt = $conn->prepare("INSERT INTO users
        (prefix_id, username, first_name, last_name, email, password, role, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");

    $prefix_id = 1;
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
        echo "✅ <strong style='color: green;'>Admin user created successfully!</strong><br><br>";

        echo "<div style='background: #f0fdfa; border: 2px solid #14b8a6; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
        echo "<h3 style='color: #0d9488; margin-top: 0;'>✅ Admin Login Credentials</h3>";
        echo "<p style='margin: 5px 0;'><strong>Username:</strong> admin</p>";
        echo "<p style='margin: 5px 0;'><strong>Password:</strong> admin123</p>";
        echo "</div>";
    } else {
        echo "❌ Failed to create admin user: " . $insert_stmt->error . "<br>";
    }
}

echo "<hr>";
echo "<div style='margin-top: 20px;'>";
echo "<a href='admin-login.php' style='display: inline-block; background: #14b8a6; color: white; padding: 12px 24px; text-decoration: none; border-radius: 8px; font-weight: bold; margin-right: 10px;'>
    🔐 Go to Admin Login
</a>";
echo "<a href='debug_admin.php' style='display: inline-block; background: #6b7280; color: white; padding: 12px 24px; text-decoration: none; border-radius: 8px; font-weight: bold;'>
    🔍 Run Debug Again
</a>";
echo "</div>";
?>
