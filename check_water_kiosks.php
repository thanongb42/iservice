<?php
require 'config/database.php';

$result = $conn->query("DESCRIBE water_kiosks");

echo "📋 Water Kiosks Table Structure:\n";
echo str_repeat("-", 80) . "\n";
while ($row = $result->fetch_assoc()) {
    echo sprintf("%-20s %-30s %-15s %s\n", 
        $row['Field'], 
        $row['Type'], 
        $row['Null'], 
        $row['Comment']
    );
}

$conn->close();
?>
