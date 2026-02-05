<?php
session_start();
require_once 'config/database.php';

// Assume user_id = 3 (photographer)
$user_id = 3;

echo "=== All task assignments for user $user_id ===" . PHP_EOL;
$result = $conn->query("
    SELECT ta.*, sr.request_code, sr.service_code
    FROM task_assignments ta
    JOIN service_requests sr ON ta.request_id = sr.request_id
    WHERE ta.assigned_to = $user_id
    ORDER BY ta.assignment_id
");

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "Assignment " . $row['assignment_id'] . ": " . $row['request_code'] . " (" . $row['service_code'] . ")" . PHP_EOL;
        echo "  Request: " . $row['request_id'] . PHP_EOL;
        echo "  Start: " . ($row['start_time'] ?: 'NULL') . PHP_EOL;
        echo "  End: " . ($row['end_time'] ?: 'NULL') . PHP_EOL;
        echo "  Due: " . ($row['due_date'] ?: 'NULL') . PHP_EOL;
        echo "  Status: " . $row['status'] . PHP_EOL;
        echo "---" . PHP_EOL;
    }
    echo "Total: " . $result->num_rows . " assignments" . PHP_EOL;
} else {
    echo "No assignments found for user $user_id" . PHP_EOL;
}
?>
