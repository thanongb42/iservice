<?php
require_once 'config/database.php';

echo "=== task_history table structure ===" . PHP_EOL;
$result = $conn->query("DESCRIBE task_history");
while ($col = $result->fetch_assoc()) {
    echo $col['Field'] . " (" . $col['Type'] . ")" . (($col['Null'] === 'NO') ? " NOT NULL" : "") . PHP_EOL;
}

echo "\n=== Sample task_history data ===" . PHP_EOL;
$data = $conn->query("SELECT * FROM task_history ORDER BY created_at DESC LIMIT 10");
if ($data && $data->num_rows > 0) {
    while ($row = $data->fetch_assoc()) {
        echo "ID: " . $row['id'] . " | Assignment: " . $row['assignment_id'] . " | Action: " . $row['action'] . " | Old->New: " . $row['old_status'] . " -> " . $row['new_status'] . " | By: " . $row['performed_by'] . " | Created: " . $row['created_at'] . PHP_EOL;
    }
} else {
    echo "No history records found" . PHP_EOL;
}
?>
