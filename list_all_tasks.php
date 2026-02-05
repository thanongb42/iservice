<?php
require_once 'config/database.php';

echo "=== All users ===" . PHP_EOL;
$result = $conn->query('SELECT user_id, username, first_name, last_name FROM users LIMIT 10');
while($row = $result->fetch_assoc()) {
    echo 'User ' . $row['user_id'] . ': ' . $row['username'] . ' (' . $row['first_name'] . ' ' . $row['last_name'] . ')' . PHP_EOL;
}

echo "\n=== Task assignments for each user ===" . PHP_EOL;
$assignments = $conn->query('
    SELECT 
        ta.assigned_to,
        COUNT(*) as task_count,
        GROUP_CONCAT(ta.assignment_id) as assignment_ids
    FROM task_assignments ta
    GROUP BY ta.assigned_to
');

while($row = $assignments->fetch_assoc()) {
    echo 'User ' . $row['assigned_to'] . ': ' . $row['task_count'] . ' tasks (IDs: ' . $row['assignment_ids'] . ')' . PHP_EOL;
}
?>
