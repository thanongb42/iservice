<?php
require 'config/database.php';

$sql = "ALTER TABLE water_kiosks ADD COLUMN qrcode_img VARCHAR(255) NULL COMMENT 'Path to QR code image' AFTER location_name";

if ($conn->query($sql)) {
    echo "✅ Column qrcode_img added successfully to water_kiosks table\n";
    
    // Verify the column was added
    $result = $conn->query("DESCRIBE water_kiosks");
    echo "\nCurrent table structure:\n";
    while ($row = $result->fetch_assoc()) {
        if ($row['Field'] == 'qrcode_img') {
            echo "✓ qrcode_img: " . $row['Type'] . "\n";
        }
    }
} else {
    if (strpos($conn->error, 'Duplicate column') !== false) {
        echo "ℹ️  Column qrcode_img already exists\n";
    } else {
        echo "❌ Error: " . $conn->error . "\n";
    }
}

$conn->close();
?>
