<?php
require_once __DIR__ . '/config/database.php';

// Check foreign keys
$r = $conn->query("
    SELECT CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
    FROM information_schema.KEY_COLUMN_USAGE
    WHERE TABLE_NAME = 'task_assignments' AND TABLE_SCHEMA = DATABASE() AND REFERENCED_TABLE_NAME IS NOT NULL
");
echo "Foreign keys on task_assignments:" . PHP_EOL;
while($row = $r->fetch_assoc()) {
    echo "  {$row['CONSTRAINT_NAME']}: {$row['COLUMN_NAME']} -> {$row['REFERENCED_TABLE_NAME']}.{$row['REFERENCED_COLUMN_NAME']}" . PHP_EOL;
}

// Test actual insert like the API would do
echo PHP_EOL . "Testing assign_task insert..." . PHP_EOL;
$stmt = $conn->prepare("INSERT INTO task_assignments
    (request_id, assigned_to, assigned_as_role, assigned_by, priority, due_date, notes, status, start_time, end_time, created_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?, NOW())");

if (!$stmt) {
    echo "Prepare failed: " . $conn->error . PHP_EOL;
} else {
    $request_id = 33;
    $assigned_to = 7;
    $assigned_as_role = 0;
    $admin_id = 7;
    $priority = 'normal';
    $due_date = null;
    $notes = 'test';
    $start_time = null;
    $end_time = null;

    $stmt->bind_param('iiiisssss', $request_id, $assigned_to, $assigned_as_role, $admin_id, $priority, $due_date, $notes, $start_time, $end_time);

    if ($stmt->execute()) {
        $id = $stmt->insert_id;
        echo "Insert OK, assignment_id = $id" . PHP_EOL;
        // Clean up
        $conn->query("DELETE FROM task_assignments WHERE assignment_id = $id");
        echo "Cleaned up test row" . PHP_EOL;
    } else {
        echo "Insert FAILED: " . $stmt->error . PHP_EOL;
    }
}
