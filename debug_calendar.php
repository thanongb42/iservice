<?php
session_start();
require_once 'config/database.php';

$user_id = 11;

// Get tasks data
$tasks_query = "SELECT ta.*, sr.request_code, sr.service_name, sr.requester_name, sr.requester_phone, sr.requester_email,
                u_by.username as assigned_by_username,
                CONCAT(p_by.prefix_name, u_by.first_name, ' ', u_by.last_name) as assigned_by_name,
                r.role_name as assigned_role_name
                FROM task_assignments ta
                JOIN service_requests sr ON ta.request_id = sr.request_id
                JOIN users u_by ON ta.assigned_by = u_by.user_id
                LEFT JOIN prefixes p_by ON u_by.prefix_id = p_by.prefix_id
                LEFT JOIN roles r ON ta.assigned_as_role = r.role_id
                WHERE ta.assigned_to = ?
                ORDER BY ta.due_date ASC";

$tasks_stmt = $conn->prepare($tasks_query);
$tasks_stmt->bind_param('i', $user_id);
$tasks_stmt->execute();
$tasks_result = $tasks_stmt->get_result();

$all_tasks = [];
while ($task = $tasks_result->fetch_assoc()) {
    $all_tasks[] = $task;
}

echo "=== PHP Data ===" . PHP_EOL;
foreach ($all_tasks as $task) {
    echo "Task: " . $task['request_code'] . PHP_EOL;
    echo "  start_time (raw): " . $task['start_time'] . PHP_EOL;
    echo "  start_time (strtotime): " . strtotime($task['start_time']) . PHP_EOL;
    echo "  start_time (date): " . date('Y-m-d', strtotime($task['start_time'])) . PHP_EOL;
}

echo "\n=== JavaScript Data ===" . PHP_EOL;
echo "const tasksData = " . json_encode($all_tasks, JSON_PRETTY_PRINT) . ";" . PHP_EOL;

echo "\n=== Test JavaScript parsing ===" . PHP_EOL;
?>
<script>
const tasksData = <?= json_encode($all_tasks) ?>;

tasksData.forEach(task => {
    if (task.start_time) {
        const startDate = new Date(task.start_time);
        const dateStr = startDate.toISOString().split('T')[0];
        console.log('Task:', task.request_code);
        console.log('  Raw start_time:', task.start_time);
        console.log('  Parsed Date:', startDate);
        console.log('  ISO String:', startDate.toISOString());
        console.log('  Date part:', dateStr);
        console.log('  ---');
    }
});
</script>
<?php
?>
