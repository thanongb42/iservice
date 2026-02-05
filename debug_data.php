<?php
require_once 'config/database.php';

echo "=== Sample task_assignments data ===" . PHP_EOL;
$data = $conn->query("SELECT assignment_id, request_id, assigned_to, start_time, end_time, status FROM task_assignments LIMIT 5");
if ($data && $data->num_rows > 0) {
    while($row = $data->fetch_assoc()) {
        echo "Assignment " . $row['assignment_id'] . ": request=" . $row['request_id'] . " | start=" . ($row['start_time'] ?: 'NULL') . " | end=" . ($row['end_time'] ?: 'NULL') . " | status=" . $row['status'] . PHP_EOL;
    }
} else {
    echo "No data found" . PHP_EOL;
}

// Check service_requests
echo "\n=== Check request_photography_details ===" . PHP_EOL;
$photo = $conn->query("SELECT * FROM request_photography_details LIMIT 3");
if ($photo && $photo->num_rows > 0) {
    while($row = $photo->fetch_assoc()) {
        echo "Request ID: " . $row['request_id'] . PHP_EOL;
        foreach($row as $k => $v) {
            echo "  " . $k . ": " . $v . PHP_EOL;
        }
        echo "---" . PHP_EOL;
    }
}
?>
