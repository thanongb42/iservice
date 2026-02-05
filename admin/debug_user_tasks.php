<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    echo "Not logged in";
    exit();
}

$user_id = $_SESSION['user_id'];
echo "Current user_id: " . $user_id . "<br>";

// Check tasks count
$query = "SELECT COUNT(*) as cnt FROM task_assignments WHERE assigned_to = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();

echo "Tasks for this user: " . $result['cnt'] . "<br>";

// Check if they have any tasks with start_time set
$query2 = "SELECT ta.assignment_id, ta.request_id, ta.start_time, sr.request_code 
           FROM task_assignments ta
           JOIN service_requests sr ON ta.request_id = sr.request_id
           WHERE ta.assigned_to = ? 
           LIMIT 5";
$stmt2 = $conn->prepare($query2);
$stmt2->bind_param('i', $user_id);
$stmt2->execute();
$tasks = $stmt2->get_result();

echo "<br>Sample tasks:<br>";
while ($task = $tasks->fetch_assoc()) {
    echo "- " . $task['request_code'] . " | start_time: " . ($task['start_time'] ?? 'NULL') . "<br>";
}
?>
