<?php
require_once 'config/database.php';

echo "=== All task assignments ===" . PHP_EOL;
$result = $conn->query("
    SELECT 
        ta.assignment_id,
        ta.request_id,
        ta.start_time,
        ta.end_time,
        ta.status,
        sr.request_code,
        sr.service_code
    FROM task_assignments ta
    JOIN service_requests sr ON ta.request_id = sr.request_id
    ORDER BY ta.assignment_id
");

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "Assignment " . $row['assignment_id'] . ": " . $row['request_code'] . " (" . $row['service_code'] . ")" . PHP_EOL;
        echo "  Start: " . ($row['start_time'] ?: 'NULL') . PHP_EOL;
        echo "  End: " . ($row['end_time'] ?: 'NULL') . PHP_EOL;
        echo "  Status: " . $row['status'] . PHP_EOL;
        echo "---" . PHP_EOL;
    }
} else {
    echo "No assignments found" . PHP_EOL;
}

echo "\n=== Check PHOTOGRAPHY details ===" . PHP_EOL;
$photo = $conn->query("
    SELECT 
        id,
        request_id,
        event_date,
        event_time_start,
        event_time_end
    FROM request_photography_details
");

if ($photo && $photo->num_rows > 0) {
    while ($row = $photo->fetch_assoc()) {
        echo "Photo ID " . $row['id'] . " (Request " . $row['request_id'] . "): " . $row['event_date'] . " " . $row['event_time_start'] . " - " . $row['event_time_end'] . PHP_EOL;
    }
}
?>
