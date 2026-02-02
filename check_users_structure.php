<?php
require 'config/database.php';

echo "Users table structure:\n";
$result = $conn->query("DESCRIBE users");
while($row = $result->fetch_assoc()) {
    echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
}
?>
