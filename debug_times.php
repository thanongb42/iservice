<?php
require_once 'config/database.php';

// Get task assignments with times
$query = "
    SELECT 
        ta.assignment_id, 
        ta.task_id, 
        ta.start_time, 
        ta.end_time,
        t.task_code,
        t.service_code
    FROM task_assignments ta
    JOIN tasks t ON ta.task_id = t.task_id
    LIMIT 10
";

$result = $conn->query($query);

echo "=== Task Assignment Times ===" . PHP_EOL;
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "Assignment ID: " . $row['assignment_id'] . PHP_EOL;
        echo "  Task: " . $row['task_code'] . " (" . $row['service_code'] . ")" . PHP_EOL;
        echo "  Start Time: " . ($row['start_time'] ?? 'NULL') . PHP_EOL;
        echo "  End Time: " . ($row['end_time'] ?? 'NULL') . PHP_EOL;
        echo "---" . PHP_EOL;
    }
} else {
    echo "No task assignments found" . PHP_EOL;
}

// Also check PHOTOGRAPHY table
echo "\n=== PHOTOGRAPHY Event Times ===" . PHP_EOL;
$photo_query = "
    SELECT 
        id,
        event_date,
        event_time_start,
        event_time_end
    FROM PHOTOGRAPHY
    LIMIT 5
";
$photo_result = $conn->query($photo_query);
if ($photo_result && $photo_result->num_rows > 0) {
    while ($row = $photo_result->fetch_assoc()) {
        echo "Photo ID: " . $row['id'] . PHP_EOL;
        echo "  Date: " . $row['event_date'] . PHP_EOL;
        echo "  Start: " . $row['event_time_start'] . PHP_EOL;
        echo "  End: " . $row['event_time_end'] . PHP_EOL;
        echo "---" . PHP_EOL;
    }
} else {
    echo "No PHOTOGRAPHY records found" . PHP_EOL;
}
?>
