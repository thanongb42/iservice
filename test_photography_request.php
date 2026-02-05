<?php
// Simulate POST request to process_request.php
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = [
    'service' => 'PHOTOGRAPHY',
    'requester_name' => 'Test User',
    'requester_email' => 'test@example.com',
    'requester_phone' => '0812345678',
    'position' => 'Manager',
    'event_name' => 'Company Event',
    'event_type' => 'Corporate',
    'event_date' => '2026-02-15',
    'event_time_start' => '09:00',
    'event_time_end' => '17:00',
    'event_location' => 'Main Hall',
    'number_of_photographers' => 2,
    'video_required' => 1,
    'drone_required' => 0,
    'delivery_format' => 'Digital',
    'special_requirements' => 'None'
];

// Capture output
ob_start();
require_once 'api/process_request.php';
$output = ob_get_clean();

echo "Output from process_request.php:\n";
echo $output;
echo "\n\n";

// Try to parse as JSON
$decoded = json_decode($output, true);
if (json_last_error() === JSON_ERROR_NONE) {
    echo "✓ Valid JSON response\n";
    echo json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
} else {
    echo "✗ Invalid JSON\n";
    echo "Error: " . json_last_error_msg() . "\n";
    echo "First 200 chars:\n";
    echo substr($output, 0, 200);
}
?>
