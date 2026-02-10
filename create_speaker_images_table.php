<?php
/**
 * Create speaker_images table for CDP speaker system
 */

require_once 'config/database.php';

try {
    $sql = "CREATE TABLE IF NOT EXISTS speaker_images (
        id INT AUTO_INCREMENT PRIMARY KEY,
        location_id INT NOT NULL,
        image_path VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (location_id) REFERENCES speaker_locations(id) ON DELETE CASCADE,
        INDEX idx_location (location_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    if ($conn->query($sql) === TRUE) {
        echo "✅ speaker_images table created successfully!\n";
    } else {
        echo "❌ Error: " . $conn->error . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}
?>
