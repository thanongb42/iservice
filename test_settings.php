<?php
require 'config/database.php';

echo "Testing system_settings table...\n";
$r = $conn->query('SELECT COUNT(*) as c FROM system_settings');
$count = $r->fetch_assoc()['c'];
echo "✓ system_settings table has $count rows\n\n";

echo "Sample settings:\n";
$result = $conn->query("SELECT setting_key, setting_value FROM system_settings LIMIT 5");
while ($row = $result->fetch_assoc()) {
    echo "  - {$row['setting_key']}: {$row['setting_value']}\n";
}
?>
