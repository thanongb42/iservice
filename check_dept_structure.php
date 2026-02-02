<?php
require_once 'config/database.php';

echo "Departments table structure:\n";
$result = $conn->query("DESCRIBE departments");
while($row = $result->fetch_assoc()) {
    echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
}
?>
