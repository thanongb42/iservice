<?php
// Temporary debug file — DELETE after debugging
error_reporting(E_ALL);
ini_set('display_errors', '1');
header('Content-Type: text/plain; charset=utf-8');

echo "STEP 1: PHP is running, version=" . PHP_VERSION . "\n";
flush();

session_start();
echo "STEP 2: session_start() OK, session_id=" . session_id() . "\n";
flush();

require_once __DIR__ . '/config/database.php';
echo "STEP 3: config/database.php OK\n";
echo "  DB_HOST=" . DB_HOST . " DB_NAME=" . DB_NAME . "\n";
flush();

$stmt = $conn->prepare("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('line_login_channel_id','line_callback_url')");
echo "STEP 4: prepare() result=" . ($stmt ? 'OK' : 'FALSE: ' . $conn->error) . "\n";
flush();

if ($stmt && $stmt->execute()) {
    $settings = [];
    while ($row = $stmt->get_result()->fetch_assoc()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    echo "STEP 5: settings fetched, keys=" . implode(',', array_keys($settings)) . "\n";
    echo "  line_login_channel_id=" . (isset($settings['line_login_channel_id']) ? 'SET' : 'NOT SET') . "\n";
    echo "  line_callback_url=" . ($settings['line_callback_url'] ?? 'NOT SET') . "\n";
} else {
    echo "STEP 5: query failed\n";
}

echo "DONE\n";
