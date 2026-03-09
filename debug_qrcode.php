<?php
require 'config/database.php';

echo "🔍 Debugging Water Kiosk QR Code Display\n";
echo str_repeat("=", 80) . "\n";

// Check database data
echo "\n📊 Database Check:\n";
echo str_repeat("-", 80) . "\n";

$result = $conn->query("SELECT kiosk_code, location_name, qrcode_img FROM water_kiosks LIMIT 5");

while ($row = $result->fetch_assoc()) {
    echo sprintf("Code: %-12s | Location: %-30s | QR Path: %s\n", 
        $row['kiosk_code'],
        substr($row['location_name'], 0, 28),
        $row['qrcode_img'] ?? 'NULL'
    );
}

// Check if directory exists
echo "\n📁 Directory Check:\n";
echo str_repeat("-", 80) . "\n";

$qrdir = __DIR__ . '/uploads/qrcode_smart_water';
if (is_dir($qrdir)) {
    echo "✅ Directory exists: $qrdir\n";
    
    $files = scandir($qrdir);
    $qrfiles = array_filter($files, function($f) { return strpos($f, 'qrcode_') === 0; });
    echo "✓ Found " . count($qrfiles) . " QR code files\n";
    
    echo "\nSample QR code files:\n";
    $sample = array_slice($qrfiles, 0, 5);
    foreach ($sample as $file) {
        $filepath = "$qrdir/$file";
        $size = filesize($filepath);
        echo "  - $file (" . number_format($size) . " bytes)\n";
    }
} else {
    echo "❌ Directory NOT found: $qrdir\n";
    echo "ℹ️  Creating directory...\n";
    
    if (@mkdir($qrdir, 0777, true)) {
        echo "✅ Directory created: $qrdir\n";
    } else {
        echo "❌ Failed to create directory\n";
    }
}

// Check JSON output for JavaScript
echo "\n🔗 JSON Output Sample (as seen by JavaScript):\n";
echo str_repeat("-", 80) . "\n";

$result = $conn->query("SELECT kiosk_code, location_name, latitude, longitude, qrcode_img FROM water_kiosks WHERE status='active' LIMIT 2");

$kiosks = [];
while ($row = $result->fetch_assoc()) {
    $kiosks[] = $row;
}

$json = json_encode($kiosks, JSON_UNESCAPED_UNICODE);
echo $json . "\n";

echo "\n✅ Debug complete!\n";

$conn->close();
?>
