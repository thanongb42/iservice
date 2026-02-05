<?php
require_once 'config/database.php';

echo "=== Detailed view of Assignment 1 ===" . PHP_EOL;
$result = $conn->query('
    SELECT 
        ta.*,
        sr.request_code,
        sr.service_code,
        sr.service_name
    FROM task_assignments ta
    JOIN service_requests sr ON ta.request_id = sr.request_id
    WHERE ta.assignment_id = 1
');

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo "Assignment ID: " . $row['assignment_id'] . PHP_EOL;
    echo "Request ID: " . $row['request_id'] . PHP_EOL;
    echo "Request Code: " . $row['request_code'] . PHP_EOL;
    echo "Service: " . $row['service_name'] . " (" . $row['service_code'] . ")" . PHP_EOL;
    echo "Assigned To: " . $row['assigned_to'] . PHP_EOL;
    echo "Status: " . $row['status'] . PHP_EOL;
    echo "Start Time: " . $row['start_time'] . PHP_EOL;
    echo "End Time: " . $row['end_time'] . PHP_EOL;
    echo "Due Date: " . $row['due_date'] . PHP_EOL;
    
    // Check event details
    echo "\n=== Check PHOTOGRAPHY event ===" . PHP_EOL;
    $photo = $conn->query('
        SELECT * FROM request_photography_details WHERE request_id = ' . $row['request_id']
    );
    
    if ($photo && $photo->num_rows > 0) {
        $p = $photo->fetch_assoc();
        echo "Event Date: " . $p['event_date'] . PHP_EOL;
        echo "Event Time: " . $p['event_time_start'] . " - " . $p['event_time_end'] . PHP_EOL;
        echo "Expected Start: " . $p['event_date'] . ' ' . $p['event_time_start'] . PHP_EOL;
        echo "Expected End: " . $p['event_date'] . ' ' . $p['event_time_end'] . PHP_EOL;
    }
}
?>
