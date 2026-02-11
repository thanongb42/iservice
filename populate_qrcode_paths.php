<?php
require 'config/database.php';

// Update water_kiosks with QR code image paths
$sql = "UPDATE water_kiosks SET qrcode_img = CONCAT('uploads/qrcode_smart_water/qrcode_', kiosk_code, '.png') WHERE kiosk_code LIKE 'RSC%'";

if ($conn->query($sql)) {
    $affected = $conn->affected_rows;
    echo "✅ Updated $affected records with QR code image paths\n";
    
    // Verify the updates
    echo "\n📋 Sample QR Code Paths:\n";
    echo str_repeat("-", 60) . "\n";
    $result = $conn->query("SELECT kiosk_code, qrcode_img FROM water_kiosks WHERE qrcode_img IS NOT NULL ORDER BY kiosk_code LIMIT 10");
    while ($row = $result->fetch_assoc()) {
        echo sprintf("%-15s => %s\n", $row['kiosk_code'], $row['qrcode_img']);
    }
    
    // Count total
    $count = $conn->query("SELECT COUNT(*) as total FROM water_kiosks WHERE qrcode_img IS NOT NULL");
    $total = $count->fetch_assoc();
    echo "\n✓ Total records with QR codes: " . $total['total'] . "\n";
} else {
    echo "❌ Error: " . $conn->error . "\n";
}

$conn->close();
?>
