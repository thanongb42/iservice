<?php
session_start();
require_once 'config/database.php';

$user_id = 11;

// Get tasks data
$tasks_query = "SELECT ta.assignment_id, ta.start_time, ta.end_time, ta.status,
                sr.request_code, sr.service_name
                FROM task_assignments ta
                JOIN service_requests sr ON ta.request_id = sr.request_id
                WHERE ta.assigned_to = ?";

$tasks_stmt = $conn->prepare($tasks_query);
$tasks_stmt->bind_param('i', $user_id);
$tasks_stmt->execute();
$tasks_result = $tasks_stmt->get_result();

echo "=== Task Data ===" . PHP_EOL;
while ($task = $tasks_result->fetch_assoc()) {
    echo "Request Code: " . $task['request_code'] . PHP_EOL;
    echo "Start Time (raw): " . $task['start_time'] . PHP_EOL;
    
    // Extract date like JavaScript does
    $datePart = explode(' ', $task['start_time'])[0];
    echo "Date Part (split): " . $datePart . PHP_EOL;
    
    // Parse with DateTime
    $date = new DateTime($task['start_time']);
    echo "DateTime ISO: " . $date->format('Y-m-d') . PHP_EOL;
    
    // Check if February has 29 days in 2026
    $feb2026 = new DateTime('2026-02-28');
    echo "Feb 28, 2026 exists: yes" . PHP_EOL;
    $feb29 = new DateTime('2026-03-01');
    $feb29->sub(new DateInterval('P1D'));
    echo "Last day of Feb 2026: " . $feb29->format('Y-m-d') . PHP_EOL;
}

echo "\n=== Calendar Logic ===" . PHP_EOL;
echo "If task.start_time = '2026-02-07 08:31:00'" . PHP_EOL;
echo "split(' ')[0] = '2026-02-07'" . PHP_EOL;
echo "Calendar date format when clicking Feb 7:" . PHP_EOL;
$cellDate = new DateTime('2026-02-07');
echo "toISOString().split('T')[0] = " . $cellDate->format('Y-m-d') . PHP_EOL;

echo "\nWait, Feb has only 28 days in 2026 (not leap year)" . PHP_EOL;
echo "So day 8 would be March 1, not Feb 8" . PHP_EOL;
echo "This suggests the issue is somewhere else" . PHP_EOL;
?>
