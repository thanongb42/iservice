<?php
require_once 'config/database.php';

echo "=== Updating task_assignments with times from event details ===" . PHP_EOL;

// Get all assignments with NULL times
$get_assignments = "
    SELECT 
        ta.assignment_id,
        ta.request_id,
        sr.service_code,
        ta.start_time,
        ta.end_time
    FROM task_assignments ta
    JOIN service_requests sr ON ta.request_id = sr.request_id
    WHERE ta.start_time IS NULL OR ta.end_time IS NULL
";

$result = $conn->query($get_assignments);
$updated_count = 0;

if ($result && $result->num_rows > 0) {
    while ($assignment = $result->fetch_assoc()) {
        $request_id = $assignment['request_id'];
        $service_code = $assignment['service_code'];
        $assignment_id = $assignment['assignment_id'];
        
        $start_time = null;
        $end_time = null;
        
        // Fetch times based on service code
        if ($service_code === 'PHOTOGRAPHY') {
            $detail = $conn->query("SELECT event_date, event_time_start, event_time_end FROM request_photography_details WHERE request_id = $request_id")->fetch_assoc();
            if ($detail && $detail['event_date']) {
                if ($detail['event_time_start']) {
                    $start_time = $detail['event_date'] . ' ' . $detail['event_time_start'];
                }
                if ($detail['event_time_end']) {
                    $end_time = $detail['event_date'] . ' ' . $detail['event_time_end'];
                }
            }
        } elseif ($service_code === 'MC') {
            $detail = $conn->query("SELECT event_date, event_time_start, event_time_end FROM request_mc_details WHERE request_id = $request_id")->fetch_assoc();
            if ($detail && $detail['event_date']) {
                if ($detail['event_time_start']) {
                    $start_time = $detail['event_date'] . ' ' . $detail['event_time_start'];
                }
                if ($detail['event_time_end']) {
                    $end_time = $detail['event_date'] . ' ' . $detail['event_time_end'];
                }
            }
        }
        
        // Update if we found times
        if ($start_time || $end_time) {
            $update_query = "UPDATE task_assignments SET ";
            $parts = [];
            if ($start_time) {
                $parts[] = "start_time = '$start_time'";
            }
            if ($end_time) {
                $parts[] = "end_time = '$end_time'";
            }
            $update_query .= implode(', ', $parts) . " WHERE assignment_id = $assignment_id";
            
            if ($conn->query($update_query)) {
                echo "✓ Updated assignment $assignment_id: start=$start_time, end=$end_time" . PHP_EOL;
                $updated_count++;
            } else {
                echo "✗ Failed to update assignment $assignment_id" . PHP_EOL;
            }
        }
    }
    echo "\nTotal updated: $updated_count assignments" . PHP_EOL;
} else {
    echo "No assignments found with NULL times" . PHP_EOL;
}

// Show sample of updated data
echo "\n=== Sample updated data ===" . PHP_EOL;
$sample = $conn->query("SELECT assignment_id, request_id, start_time, end_time FROM task_assignments LIMIT 5");
if ($sample) {
    while ($row = $sample->fetch_assoc()) {
        echo "Assignment " . $row['assignment_id'] . ": start=" . ($row['start_time'] ?: 'NULL') . ", end=" . ($row['end_time'] ?: 'NULL') . PHP_EOL;
    }
}
?>
