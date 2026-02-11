<?php
require 'config/database.php';

echo "🔄 Migrating water_kiosks table: qr_code_link => qrcode_img\n";
echo str_repeat("-", 70) . "\n";

// Step 1: Check if qr_code_link exists
$checkResult = $conn->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='water_kiosks' AND COLUMN_NAME='qr_code_link'");

if ($checkResult && $checkResult->num_rows > 0) {
    echo "✓ Found qr_code_link column\n";
    
    // Step 2: Check if qrcode_img already exists
    $checkImg = $conn->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='water_kiosks' AND COLUMN_NAME='qrcode_img'");
    
    if ($checkImg && $checkImg->num_rows > 0) {
        echo "✓ qrcode_img column already exists\n";
        echo "\nStep 1: Removing old qr_code_link column...\n";
        
        if ($conn->query("ALTER TABLE water_kiosks DROP COLUMN qr_code_link")) {
            echo "✅ qr_code_link column removed successfully\n";
        } else {
            echo "❌ Error removing qr_code_link: " . $conn->error . "\n";
        }
    } else {
        echo "⚠️  qrcode_img column doesn't exist yet\n";
        echo "\nStep 1: Creating qrcode_img column...\n";
        
        if ($conn->query("ALTER TABLE water_kiosks ADD COLUMN qrcode_img VARCHAR(255) NULL COMMENT 'Path to QR code image' AFTER location_name")) {
            echo "✅ qrcode_img column created\n";
            
            // Step 2: Migrate data from qr_code_link to qrcode_img if needed
            echo "\nStep 2: Populating qrcode_img with standard paths...\n";
            
            if ($conn->query("UPDATE water_kiosks SET qrcode_img = CONCAT('uploads/qrcode_smart_water/qrcode_', kiosk_code, '.png') WHERE kiosk_code LIKE 'RSC%'")) {
                $affected = $conn->affected_rows;
                echo "✅ Updated $affected records with QR code paths\n";
                
                echo "\nStep 3: Removing old qr_code_link column...\n";
                
                if ($conn->query("ALTER TABLE water_kiosks DROP COLUMN qr_code_link")) {
                    echo "✅ qr_code_link column removed successfully\n";
                } else {
                    echo "❌ Error removing qr_code_link: " . $conn->error . "\n";
                }
            } else {
                echo "❌ Error updating qrcode_img: " . $conn->error . "\n";
            }
        } else {
            echo "❌ Error creating qrcode_img: " . $conn->error . "\n";
        }
    }
} else {
    echo "ℹ️  qr_code_link column not found\n";
}

// Step 4: Verify final table structure
echo "\n" . str_repeat("-", 70) . "\n";
echo "📋 Final table structure (relevant columns):\n";
echo str_repeat("-", 70) . "\n";

$result = $conn->query("SELECT COLUMN_NAME, COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='water_kiosks' ORDER BY ORDINAL_POSITION");

$count = 0;
while ($row = $result->fetch_assoc()) {
    $count++;
    echo sprintf("%2d. %-20s %s\n", $count, $row['COLUMN_NAME'], $row['COLUMN_TYPE']);
}

// Step 5: Show sample data
echo "\n" . str_repeat("-", 70) . "\n";
echo "📊 Sample data with qrcode_img:\n";
echo str_repeat("-", 70) . "\n";

$sample = $conn->query("SELECT kiosk_code, location_name, qrcode_img FROM water_kiosks WHERE qrcode_img IS NOT NULL LIMIT 5");

while ($row = $sample->fetch_assoc()) {
    echo sprintf("%-12s | %-30s | %s\n", 
        $row['kiosk_code'], 
        substr($row['location_name'], 0, 28),
        $row['qrcode_img']
    );
}

echo "\n✅ Migration completed successfully!\n";

$conn->close();
?>
