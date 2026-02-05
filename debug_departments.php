<?php
require 'config/database.php';

// Check if departments table exists
$result = $conn->query("SHOW TABLES LIKE 'departments'");
if ($result->num_rows === 0) {
    echo "ERROR: departments table not found\n";
    exit(1);
}

// Try to fetch level 1 departments
$sql = "SELECT department_id, department_code, department_name FROM departments WHERE level = 1 AND parent_department_id IS NULL AND status = 'active' LIMIT 5";
$result = $conn->query($sql);

if ($result) {
    echo "Departments found: " . $result->num_rows . "\n";
    while ($row = $result->fetch_assoc()) {
        echo "  - " . $row['department_name'] . "\n";
    }
} else {
    echo "Query error: " . $conn->error . "\n";
}

// Test API endpoint
echo "\n\nTesting API endpoint...\n";
$output = shell_exec('php api/get_departments.php?level=1 2>&1');
echo "API Output:\n";
echo $output;
?>
