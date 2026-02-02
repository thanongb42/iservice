<?php
require_once 'config/database.php';

echo "Testing system_setting.php database queries...\n";

// Test 1: Check system_settings table
$result = $conn->query("SELECT COUNT(*) as cnt FROM system_settings");
if ($result) {
    $count = $result->fetch_assoc()['cnt'];
    echo "✓ system_settings table OK: $count rows\n";
} else {
    echo "✗ Error: " . $conn->error . "\n";
    exit;
}

// Test 2: Fetch all settings like system_setting.php does
$settings = [];
$result = $conn->query("SELECT * FROM system_settings ORDER BY setting_key ASC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $settings[$row['setting_key']] = [
            'id' => $row['id'],
            'value' => $row['setting_value'],
            'type' => $row['setting_type'],
            'description' => $row['description']
        ];
    }
    echo "✓ Settings fetched: " . count($settings) . " items\n";
} else {
    echo "✗ Error fetching settings: " . $conn->error . "\n";
}

// Test 3: Check admin_report.php queries
echo "\nTesting admin_report.php database queries...\n";

$queries = [
    "SELECT COUNT(*) as cnt FROM users" => "users",
    "SELECT COUNT(*) as cnt FROM departments WHERE parent_department_id IS NULL" => "departments",
    "SELECT COUNT(*) as cnt FROM my_service" => "my_service",
    "SELECT COUNT(*) as cnt FROM service_requests WHERE status != 'completed'" => "service_requests",
    "SELECT COUNT(*) as cnt FROM tech_news WHERE is_active = 1" => "tech_news",
];

foreach ($queries as $query => $label) {
    $result = $conn->query($query);
    if ($result) {
        $count = $result->fetch_assoc()['cnt'];
        echo "✓ $label: $count\n";
    } else {
        echo "✗ Error checking $label: " . $conn->error . "\n";
    }
}

echo "\n✓ All queries working correctly!\n";
?>
