<?php
/**
 * Test script to verify API data
 */

// Log all POST data
$log_file = __DIR__ . '/api_debug.log';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "\n=== Request at $timestamp ===\n";
    $log_entry .= "Action: " . ($_POST['action'] ?? 'NONE') . "\n";
    $log_entry .= "ID: " . ($_POST['id'] ?? 'NONE') . "\n";
    $log_entry .= "Department Name: " . ($_POST['department_name'] ?? 'NONE') . "\n";
    $log_entry .= "Full POST Data:\n";
    $log_entry .= print_r($_POST, true);
    $log_entry .= "\n";
    
    file_put_contents($log_file, $log_entry, FILE_APPEND);
    echo "<pre>" . htmlspecialchars($log_entry) . "</pre>";
} else {
    echo "No POST data received. Check log file at: " . $log_file;
}
?>
