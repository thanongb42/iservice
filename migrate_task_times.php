<?php
require 'config/database.php';

// Check if columns exist
$result = $conn->query('DESCRIBE task_assignments');
$columns = [];
while($row = $result->fetch_assoc()) {
    $columns[] = $row['Field'];
}

if (!in_array('start_time', $columns)) {
    $conn->query('ALTER TABLE task_assignments ADD COLUMN start_time DATETIME NULL AFTER started_at');
    echo 'Added start_time column' . PHP_EOL;
}

if (!in_array('end_time', $columns)) {
    $conn->query('ALTER TABLE task_assignments ADD COLUMN end_time DATETIME NULL AFTER completed_at');
    echo 'Added end_time column' . PHP_EOL;
}

echo 'Migration complete' . PHP_EOL;
?>
