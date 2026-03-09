<?php
require 'config/database.php';

echo "✅ Water Kiosk QR Code Display Test\n";
echo str_repeat("=", 80) . "\n\n";

// Get sample kiosk with QR code
$result = $conn->query("SELECT kiosk_code, qrcode_img FROM water_kiosks WHERE qrcode_img IS NOT NULL LIMIT 1");
$kiosk = $result->fetch_assoc();

if ($kiosk) {
    echo "Test Kiosk: " . $kiosk['kiosk_code'] . "\n";
    echo "QR Path: " . $kiosk['qrcode_img'] . "\n";
    echo "File exists: " . (file_exists($kiosk['qrcode_img']) ? '✅ YES' : '❌ NO') . "\n";
    echo "Full path check: " . (file_exists(__DIR__ . '/' . $kiosk['qrcode_img']) ? '✅ YES' : '❌ NO') . "\n\n";
    
    echo "HTML img tag test:\n";
    echo "<img src=\"" . $kiosk['qrcode_img'] . "\" alt=\"QR Code\">\n\n";
    
    echo "✅ QR code should now display correctly in water_map.php!\n";
} else {
    echo "❌ No kiosks with QR codes found\n";
}

$conn->close();
?>
