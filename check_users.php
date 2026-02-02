<?php
require_once 'config/database.php';

// Check users in database
$result = $conn->query("SELECT user_id, email, username, role, status FROM users LIMIT 10");
if ($result->num_rows > 0) {
    echo "=== Users in Database ===\n";
    while ($row = $result->fetch_assoc()) {
        echo json_encode($row) . "\n";
    }
} else {
    echo "No users found in database!\n";
}

// Check if users table exists
$result = $conn->query("SHOW TABLES LIKE 'users'");
if ($result->num_rows > 0) {
    echo "\nusers table exists\n";
} else {
    echo "\nERROR: users table does NOT exist!\n";
}

$conn->close();
