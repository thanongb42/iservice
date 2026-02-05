<?php
require_once 'config/database.php';

// Get all tables
echo "=== All Tables in Database ===" . PHP_EOL;
$result = $conn->query("SHOW TABLES");
$tables = [];
while($row = $result->fetch_array()) {
    $tables[] = $row[0];
    echo "- " . $row[0] . PHP_EOL;
}

// Check if task_assignments exists and has time columns
if (in_array('task_assignments', $tables)) {
    echo "\n=== task_assignments columns ===" . PHP_EOL;
    $col_result = $conn->query("DESCRIBE task_assignments");
    while($col = $col_result->fetch_assoc()) {
        echo $col['Field'] . " (" . $col['Type'] . ")" . PHP_EOL;
    }
    
    // Get sample data
    echo "\n=== Sample task_assignments data ===" . PHP_EOL;
    $data = $conn->query("SELECT assignment_id, task_id, assigned_to, start_time, end_time FROM task_assignments LIMIT 5");
    if ($data && $data->num_rows > 0) {
        while($row = $data->fetch_assoc()) {
            echo "Assignment " . $row['assignment_id'] . ": start=" . ($row['start_time'] ?: 'NULL') . ", end=" . ($row['end_time'] ?: 'NULL') . PHP_EOL;
        }
    } else {
        echo "No data found" . PHP_EOL;
    }
}
?>
