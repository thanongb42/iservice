<?php
require 'config/database.php';

$result = $conn->query('SELECT assignment_id, status, started_at, accepted_at, completed_at FROM task_assignments WHERE assignment_id = 3');
$row = $result->fetch_assoc();

echo "Assignment 3 Data:\n";
echo json_encode($row, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>
