<?php
require_once 'config/database.php';

echo "<h1>Setup Related Agencies Table</h1>";

$sql = "CREATE TABLE IF NOT EXISTS related_agencies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL COMMENT 'Organization Name',
    link VARCHAR(255) NOT NULL COMMENT 'Web URL',
    image VARCHAR(255) DEFAULT NULL COMMENT 'Logo URL',
    display_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

if ($conn->query($sql) === TRUE) {
    echo "<p style='color:green;'>Table 'related_agencies' created successfully.</p>";
} else {
    echo "<p style='color:red;'>Error creating table: " . $conn->error . "</p>";
}
?>