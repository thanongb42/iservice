<?php
require_once 'config/database.php';

echo "=== Testing Level 1 Departments ===\n\n";

// Check if departments table exists
$tables = $conn->query("SHOW TABLES");
$table_exists = false;
while ($table = $tables->fetch_array()) {
    if ($table[0] === 'departments') {
        $table_exists = true;
        break;
    }
}

if (!$table_exists) {
    echo "❌ Table 'departments' does not exist\n";
    exit;
}

echo "✓ Table 'departments' exists\n\n";

// Check table structure
echo "=== Table Structure ===\n";
$structure = $conn->query("DESCRIBE departments");
while ($col = $structure->fetch_assoc()) {
    echo $col['Field'] . " - " . $col['Type'] . "\n";
}

echo "\n=== Data Check ===\n";

// Count all departments
$count = $conn->query("SELECT COUNT(*) as cnt FROM departments")->fetch_assoc();
echo "Total departments: " . $count['cnt'] . "\n";

// Check level 1 departments
$level1 = $conn->query("SELECT * FROM departments WHERE level = 1")->fetch_all(MYSQLI_ASSOC);
echo "Level 1 departments: " . count($level1) . "\n\n";

if (count($level1) > 0) {
    echo "=== Level 1 Departments ===\n";
    foreach ($level1 as $dept) {
        echo "ID: " . $dept['department_id'] . " | Name: " . $dept['department_name'] . " | Level: " . $dept['level'] . " | Parent: " . ($dept['parent_department_id'] ?? 'NULL') . "\n";
    }
} else {
    echo "❌ No level 1 departments found\n";
}

// Check all departments grouped by level
echo "\n=== All Departments by Level ===\n";
$all = $conn->query("SELECT level, COUNT(*) as cnt FROM departments GROUP BY level ORDER BY level");
while ($row = $all->fetch_assoc()) {
    echo "Level " . $row['level'] . ": " . $row['cnt'] . " departments\n";
}

$conn->close();
?>
