<?php
require_once 'config/database.php';

// Get admin user's password hash
$stmt = $conn->prepare("SELECT user_id, email, username, password FROM users WHERE role = 'admin' LIMIT 1");
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    echo "Admin User: " . $user['username'] . "\n";
    echo "Email: " . $user['email'] . "\n";
    echo "Password Hash: " . substr($user['password'], 0, 30) . "...\n";
    echo "Hash Type: " . (strpos($user['password'], '$2') === 0 ? "bcrypt" : "unknown") . "\n";
    
    // Test password
    $test_password = "admin123"; // ตัวอย่าง
    $verify = password_verify($test_password, $user['password']);
    echo "Test password_verify (admin123): " . ($verify ? "TRUE" : "FALSE") . "\n";
} else {
    echo "No admin user found\n";
}
$conn->close();
