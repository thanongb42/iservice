<?php
require 'config/database.php';

echo "my_service table structure:\n";
$result = $conn->query("DESCRIBE my_service");
while($row = $result->fetch_assoc()) {
    echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
}
?>
